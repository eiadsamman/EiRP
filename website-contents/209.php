<?php
	require_once("admin/class/invoice.php");
	require_once("admin/class/accounting.php");
	$invoice= new Invoice();
	$invoice->BuildDocumentNumberPrefixList();
	$accounting=new Accounting();
	$_syscur=$accounting->system_default_currency();
?>
<table class="bom-table">
<thead>
	<tr>
		<td>ID</td>
		<td>Posting Date</td>
		<td>Company</td>
		<td>Title</td>
		<td>Due Date</td>
		<td>Status</td>
		<td>Quotations</td>
		<td style="width:100%;"></td>
	</tr>
</thead>	
<tbody>
<?php 
	$r=$sql->query("
		SELECT 
			_main.po_id,
			_main.po_title,
			DATE_FORMAT(_main.po_date,'%Y-%m-%d') AS po_date,
			DATE_FORMAT(_main.po_due_date,'%Y-%m-%d') AS po_due_date,
			_main.po_close_date,
			_main.po_usr_id,comp_name,
			COUNT(_sub.po_id) AS _qcount
		FROM
			inv_main AS _main
				JOIN users ON usr_id = _main.po_usr_id
				LEFT JOIN inv_main AS _sub ON _sub.po_rel = _main.po_id
				JOIN companies ON comp_id = _main.po_comp_id
		WHERE 
			_main.po_close_date IS NULL AND _main.po_type = 1
		GROUP BY
			_main.po_id
		");
	
	if($r){
		while($row=$sql->fetch_assoc($r)){
			echo "<tr>";
			echo "<td>".$invoice->TranslatePrefix(1,$row['po_id'])."</td>";
			echo "<td>{$row['po_date']}</td>";
			echo "<td>{$row['comp_name']}</td>";
			echo "<td>{$row['po_title']}</td>";
			echo "<td>{$row['po_due_date']}</td>";
			echo "<td>".(is_null($row['po_close_date'])?"Open":"Closed")."</td>";
			echo "<td>{$row['_qcount']}</td>";
			echo "<td><a href=\"".$tables->pagefile_info(233,null,"directory")."/?docid={$row['po_id']}&token=".md5("sysdoc_".$row['po_id'].session_id())."\">Place quotation</a></td>";
			echo "</tr>";
			if($row['_qcount']>0){
				echo "<tr>";
				echo "<td colspan=\"8\" style=\"border-top:double 3px #ccc;\">";
				echo "<table class=\"bom-table\"><tbody>";
				
				$rlist=$sql->query("
					SELECT 
						po_type,po_id,DATE_FORMAT(po_date,'%Y-%m-%d') AS po_date,po_total,po_vat_rate,po_additional_amount,po_discount,po_cur_id,cur_shortname,comp_name,
						CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS doc_usr_name,
						_rates._rate AS _exchangeRate,po_vat_rate,
						po_cur_id
					FROM 
						inv_main 
							JOIN users ON usr_id = po_usr_id
							JOIN currencies ON cur_id = po_cur_id
							JOIN companies ON po_comp_id = comp_id
							LEFT JOIN (
									SELECT _from.curexg_from AS _rate_from,_to.curexg_from AS _rate_to,(_from.curexg_value / _to.curexg_value) AS _rate 
										FROM currency_exchange AS _from INNER JOIN currency_exchange AS _to
								) AS _rates ON _rates._rate_from = po_cur_id AND _rates._rate_to = {$_syscur['id']}
								
					WHERE 
						po_rel={$row['po_id']} AND po_type=2
					ORDER BY 
						po_id
					;");
				
				if($rlist){
					$fao=true;
					while($rowlist = $sql->fetch_assoc($rlist)){
						if($fao){
							$fao=false;
							echo "<tr><td>ID</td><td>Date</td><td>Posted By</td><td>Vendor</td><td>Doc Amount</td><td>Local Cur Amount</td><td>VAT</td></tr>";
							
							echo "";
						}
						echo "<tr>";
						echo "<td><a href=\"".$tables->pagefile_info(234,null,"directory")."/?docid={$rowlist['po_id']}&token=".(md5("sysdoc_".$rowlist['po_id'].session_id()))."\">".$invoice->TranslatePrefix(2,$rowlist['po_id'])."</a></td>";
						echo "<td>{$rowlist['po_date']}</td>";
						echo "<td>{$rowlist['doc_usr_name']}</td>";
						echo "<td>{$rowlist['comp_name']}</td>";
						echo "<td align=\"right\">".number_format($rowlist['po_total'] + ($rowlist['po_total'] * $rowlist['po_vat_rate'] / 100) + $rowlist['po_additional_amount'],2,".",",")." {$rowlist['cur_shortname']}</td>";
						echo "<td align=\"right\">".number_format(($rowlist['po_total']*$rowlist['_exchangeRate']) + (($rowlist['po_total']*$rowlist['_exchangeRate']) * $rowlist['po_vat_rate'] / 100) + ($rowlist['po_addtional_amount']*$rowlist['_exchangeRate']),2,".",",")." {$_syscur['shortname']}</td>";
						echo "<td align=\"right\">".(is_null($rowlist['po_vat_rate'])?"-": number_format($rowlist['po_vat_rate'],2,".",","))."</td>";
						
						echo "<td width=\"100%\"></td>";
						echo "</tr>";
					}
				}
				echo "</tbody></table>";
				echo "</td>";
				echo "</tr>";
			}
			
		}
	}
?>
</tbody>
</table>