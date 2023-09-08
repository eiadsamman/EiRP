<?php
//purchase/rfq/view
require_once("admin/class/invoice.php");
require_once("admin/class/accounting.php");
require_once("admin/class/Template/class.template.build.php");

use Template\TemplateBuild;
use Finance\Invoice;
use Finance\Accounting;
use Finance\DocumentException;
use Finance\DocumentMaterialListException;

$_TEMPLATE 	= new TemplateBuild();
$invoice 	= new Invoice();
$accounting = new Accounting();
$doc_id		= $invoice->DocumentURI();

if($doc_id){
	try {
		$chain = $invoice->Chain($doc_id);
		
		if(sizeof($chain) < 2 || $chain[1] != $doc_id){
			throw new DocumentException("Requested document not found", 31001);
		}
		
		$doc_rm = $invoice->GetMaterialRequestDoc($chain[0]);
		$doc_rfq = $invoice->GetPurchaseQuotationDoc($chain[1]);
		$excrate = 1;
		$_syscur = $accounting->system_default_currency();
		if($_syscur!=false && $doc_rfq['po_cur_id'] != $_syscur['id']){
			$excrate = $accounting->currency_exchange($doc_rfq['po_cur_id'], $_syscur['id']);
		}
		
		$_TEMPLATE->Title("Request for Quotation Record", null, $invoice->TranslatePrefix(Invoice::map['MAT_REQ'],$doc_rm['po_serial']));
		
		
		echo $_TEMPLATE->CommandBarStart();
		echo "<div class=\"btn-set\">";
		echo "<a style=\"color:#333;\" href=\"".$tables->pagefile_info(234,null,"directory")."/\" class=\"bnt-back\"></a>";
		echo "<a style=\"color:#333;\" href=\"".$tables->pagefile_info(240,null,"directory")."/?docid={$chain[0]}&token=".md5("sysdoc_".$chain[0].session_id())."\">".$invoice->TranslatePrefix(Invoice::map['MAT_REQ'],$doc_rm['po_serial'])."</a>";
		echo "<span>".$invoice->TranslatePrefix(Invoice::map['PUR_QUT'],$doc_rfq['po_serial'])."</span>";
		echo "<span class=\"gap\"></span>";
		if(is_null($doc_rm['po_close_date'])){
			echo "<button>Cancel Quotation</button>";
			echo "<a class=\"clr-green\" href=\"".$tables->pagefile_info(244,null,"directory")."/?docid={$doc_rfq['po_id']}&token=".md5("sysdoc_".$doc_id.session_id())."
				\">Rlease Pruchse Order</a>";
		}else{
			echo "<span>Request Closed</span>";
		}
		
		echo "</div>";
		echo $_TEMPLATE->CommandBarEnd();
		
		
		
		
		$doc_value = $doc_rfq['po_total'] - ($doc_rfq['po_total'] * $doc_rfq['po_discount'] / 100) + $doc_rfq['po_additional_amount'];
		$doc_value += $doc_value * $doc_rfq['po_vat_rate'] / 100;
		
		$title="<span class=\"flex\">Quotation Information</span>";
		
		
		$_TEMPLATE->NewFrameTitle($title, false, true);
		echo $_TEMPLATE->NewFrameBodyStart();
		echo '<div class="template-gridLayout">
				<div><span>Cost Center</span><div>'.$doc_rfq['ccc_name'].'</div></div>
				<div><span>Number</span><div>'.$invoice->TranslatePrefix(Invoice::map['PUR_QUT'],$doc_rfq['po_serial']).'</div></div>
				<div><span>Title</span><div>'.$doc_rfq['po_title'].'</div></div>
				
			</div>
			<div class="template-gridLayout">
				<div><span>Creation Date</span><div>'.$doc_rfq['po_date'].'</div></div>
				<div>
					<span>Vendor</span>
					<div>'.$doc_rfq['comp_id'].' - '.$doc_rfq['comp_name'].'</div>'
					.($doc_rfq['po_att_id']!=0?'<div>'.$doc_rfq['att_id'].' - '.$doc_rfq['po_att_name'].'</div>':"").
					'
				</div>
				<div></div>
			</div>
			<div class="template-gridLayout">
				<div><span>Placed By</span><div>'.$invoice->TranslatePrefix(11,$doc_rfq['doc_usr_id']).' - '.$doc_rfq['po_usr_name'].'</div></div>
				<div></div>
			</div>
			<div class="template-gridLayout">
				<div><span>Remarks</span><div>'.nl2br($doc_rfq['po_remarks']).'</div></div>
			</div>';
		if($invoice->Per(248)->read){
			echo '<div class="template-gridLayout inline-title">Financial Information</div>';
			echo '
				<div class="template-gridLayout">
					<div><span>Total</span><div>'.number_format($doc_rfq['po_total'],2,".",",")." ".$doc_rfq['cur_shortname'].'</div></div>
					<div><span>Discount</span><div>'.number_format($doc_rfq['po_discount'],2,".",",").'%</div></div>
					<div><span>Addtional amount</span><div>'.number_format($doc_rfq['po_additional_amount'],2,".",",")." ".$doc_rfq['cur_shortname'].'</div></div>
				</div>
				<div class="template-gridLayout">
					<div><span>VAT Rate</span><div>'.number_format($doc_rfq['po_vat_rate'],2,".",",").'%</div></div>
					<div><span>Grand total</span><div>'.number_format($doc_value,2,".",",")." ".$doc_rfq['cur_shortname'].'</div></div>
					<div><span>Local currency grand total</span><div>'.number_format($doc_value * $excrate ,2,".",",")." ".$_syscur['shortname'].'</div></div>
				</div>
				
				';
			}
		echo $_TEMPLATE->NewFrameBodyEnd();




		
		
		
		$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Quotation Details</span>");
		echo $_TEMPLATE->NewFrameBodyStart();
		echo '<table class="bom-table">
			<thead><tr><td width="100%">Material</td><td align="right" colspan="2">Quantity</td><td align="right">Unit Price</td><td align="right">Inline</td></tr></thead>
			<tbody id="jQmaterialList" style="border:solid 1px #E6E6EB;">';
			$sqlquery_materialList = $invoice->DocGetMaterialList($doc_id);
			if($sqlquery_materialList){
				$bomlegend=null;
				while($row=$sql->fetch_assoc($sqlquery_materialList)){
					if($bomlegend!=$row['pols_bom_part']){
						$bomlegend=$row['pols_bom_part'];
						echo "<tr>";
						echo "<td colspan=\"3\" style=\"color:var(--linkColor);border-left:solid 2px var(--linkColor)\">{$row['_mat_bom']}</td>";
						echo "</tr>";
					}
					echo "<tr>";
					echo "
						<td title=\"{$row['cat_alias']} {$row['mat_name']}\" style=\"padding-left:20px;\">
							<div class=\"template-cascadeOverflow\"><div>{$row['mat_long_id']}</div></div>
							<div class=\"template-cascadeOverflow\"><div>{$row['cat_alias']}, {$row['mat_name']}</div></td>";
					echo "<td align=\"right\">".number_format($row['pols_issued_qty'],$row['unt_decim'],".",",")."</td>";
					echo "<td>{$row['unt_name']}</td>";
					echo "<td align=\"right\">".number_format($row['pols_price'],Invoice::DecimalsNumber($row['pols_price']),".",",")."</td>";
					echo "<td align=\"right\">".number_format($row['pols_price'] * $row['pols_issued_qty'],3,".",",")."</td>";
					echo "</tr>";
				}
			}
		echo'</tbody></table>';
		echo $_TEMPLATE->NewFrameBodyEnd();
		
		echo "<script type=\"text/javascript\">
			Template.HistoryEntry(\"{$pageinfo['directory']}/?docid={$doc_id}&token=".md5("sysdoc_".$doc_id.session_id())."\", \"{$pageinfo['title']}\");
			$(document).ready(function(){
				$(\"#jQsloVendorAccount\").slo().setparam({\"company\":\"{$resp['po_comp_id']}\"});
			});
		</script>";

	} catch(DocumentException $e) {
		$_TEMPLATE->Title("Not Found!", null, "","mark-error");
		$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Loading material request order failed, one or more of the following might be the cause:</span>");
		$_TEMPLATE->NewFrameBody('<ul>
			<li>Material Request document number is invalid</li>
			<li>Session has expired</li>
			<li>Database query failed, contact system administrator</li>
			<li>Permission denied or not enough privileges to proceed with this document</li>
			<ul>');
	} catch (DocumentMaterialListException $e){
		$_TEMPLATE->Title("Materials list error!", null, "","mark-error");
		$_TEMPLATE->NewFrameBody('<ul>
			<li>Plotting materials list failed</li>
			<ul>');
	} finally {
		
	}
}else{
	$_TEMPLATE->Title("Request for Quotation", null, null);

	echo $_TEMPLATE->CommandBarStart();
	echo "<div class=\"btn-set\">";
	echo "<a href=\"".$tables->pagefile_info(230,null,"directory")."\" style=\"color:#333\">New material request</a>";
	echo "<span class=\"gap\"></span>";
	echo "</div>";
	echo $_TEMPLATE->CommandBarEnd();
	
	

	echo $_TEMPLATE->NewFrameBodyStart();
	echo "<table class=\"bom-table strip\">";
	echo "<thead style=\"position:sticky;top:146px;background-color:#fff;outline:solid 1px #ccc\">";
	echo "<tr><td>Cost Center</td><td>Document ID</td><td>Material Request</td><td>Title</td><td>Date</td><td>Items</td><td>Quotations</td><td width=\"100%\">Status</td></tr>";
	echo "</thead>";
	
	$rowsploted=0;
	$pq_type=Invoice::map['PUR_QUT'];
	$rm_type=Invoice::map['MAT_REQ'];
	$r=$sql->query("
		SELECT 
			po_id,po_rel,po_serial,po_rel
			po_canceled,
			po_title,
			DATE_FORMAT(po_date,'%Y-%m-%d') AS po_date,
			DATE_FORMAT(po_date,'%H:%i') AS po_time,
			CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS doc_usr_name,
			COUNT(pols_id) AS matcount,
			po_close_date,ccc_name
		FROM
			inv_main 
				JOIN users ON usr_id = po_usr_id
				LEFT JOIN inv_records ON pols_po_id = po_id
				JOIN inv_costcenter ON ccc_id = po_costcenter
				JOIN user_costcenter ON po_costcenter = usrccc_ccc_id AND usrccc_usr_id={$USER->info->id}
		WHERE
			po_type = $pq_type AND po_comp_id = {$USER->company->id}
		GROUP BY
			po_id
		ORDER BY po_date DESC
		");
	if($r){
		while($row= $sql->fetch_assoc($r)){
			$rowsploted++;
			echo "<tr>";
			echo "<td>{$row['ccc_name']}</td>";
			echo "<td><a href=\"".$tables->pagefile_info(234,null,"directory")."/?docid={$row['po_id']}&token=".md5("sysdoc_".$row['po_id'].session_id())."\">".$invoice->TranslatePrefix(Invoice::map['PUR_QUT'],$row['po_serial'])."</a></td>";
			echo "<td></td>";
			echo "<td>{$row['po_title']}</td>";
			echo "<td>{$row['po_date']}</td>";
			// echo "<td>{$row['doc_usr_name']}</td>";
			echo "<td>{$row['matcount']}</td>";
			echo "<td>".$row['qutcount']."</td>";
			echo "<td>".(is_null($row['po_close_date'])?"Open":"Closed")."</td>";
			echo "</tr>";
		}
	}
	for($irow=$invoice->listview_rows - $rowsploted;$irow>0;$irow--){
		echo "<tr><td colspan=\"7\">&nbsp;</td></tr>";
	}
	
	echo "</table>";
	echo $_TEMPLATE->NewFrameBodyEnd();
	
	echo "<script type=\"text/javascript\">
				Template.HistoryEntry(\"".$tables->pagefile_info(234,null,"directory")."\", \"".$tables->pagefile_info(234,null,"title")."\");
			</script>";
	
}
