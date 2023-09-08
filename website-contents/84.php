<?php
include_once("admin/class/accounting.php");
$accounting=new Accounting();
$__defaultaccount=$accounting->operation_default_account("salary_report");
$__defaultcurrency=$accounting->account_default_currency($__defaultaccount['id']);


$export_pagefile=$tables->pagefile_info(121,null);
$c__actions			= new AllowedActions($USER->info->permissions,$export_pagefile['permissions']);


if(isset($_POST['salarymonth'])){
	if($__defaultaccount===false){
		exit;
	}elseif($__defaultcurrency===false){
		exit;
	}
	$month=null;
	if(isset($_POST['salarymonth']) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$_POST['salarymonth'],$match)){
		if(checkdate($match[2],$match[3],$match[1])){
			$month=date("Y-m-d",mktime(0,0,0,$match[2],1,$match[1]));
		}
	}
	if($month==null){exit;}
	$page60=$tables->pagefile_info(60,null,"directory");
	$total=$count=0;
	echo "<div><table><tbody id=\"___ajax_tbody\">";
	if($r=$sql->query("
		SELECT
			acm_id,
			SUM(_accounts.atm_value) AS atm_value,
			COUNT(acm_id) AS acm_count,
			
			_accounts._prtid,
			(SELECT CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS usrname FROM 
				users WHERE usr_id=acm_usr_id) AS usrname,
			(SELECT usr_id AS usrid FROM 
				users WHERE usr_id=acm_usr_id) AS usrid
		FROM
			acc_main
				RIGHT JOIN 
					(
					SELECT
						atm_value,atm_main,prt_id AS _prtid
					FROM
						`acc_accounts` 
							LEFT JOIN acc_temp ON prt_id=atm_account_id
							LEFT JOIN currencies ON cur_id = prt_currency
							
					) AS _accounts ON _accounts.atm_main=acm_id
		WHERE
			_prtid={$__defaultaccount['id']} AND acm_rejected=0 
			AND EXTRACT(YEAR_MONTH FROM acm_month)=EXTRACT(YEAR_MONTH FROM '$month') 
		GROUP BY
			acm_usr_id
		ORDER BY
			acm_usr_id;
		")){
		while($row=$sql->fetch_assoc($r)){
			echo "<tr>";
			$total+=$row['atm_value'];
			$count+=1;
			
			echo "<td><a href=\"$page60/?id={$row['usrid']}\" target=\"_blank\">{$row['usrid']}</a></td>";
			echo "<td>{$row['usrname']}</td>";
			echo "<td align=\"right\">".number_format($row['atm_value'],2,".",",")."</td>";
			echo "<td ".($row['acm_count']==1?"style=\"color:#ccc\"":"").">{$row['acm_count']}</td>";
			
			echo "</tr>";
		}
	}
	echo "</tbody></table><div id=\"___ajax_sum\">{";
	echo "\"count\":$count,";
	echo "\"total\":\"".number_format($total,2,".",",")."\"";
	echo "}</div><div id=\"___ajax_debug\"></div></div>";
	exit;
}

if($__defaultaccount===false){
	echo "<div class=\"btn-set\"><span class=\"bnt-error\">&nbsp;No account set for this operation</span></div>";
}elseif($__defaultcurrency===false){
	echo "<div class=\"btn-set\"><span class=\"bnt-error\">&nbsp;No currency provided for working account</span></div>";
}else{
	
	
	echo "
		<div class=\"btn-set\" style=\"margin-bottom:5px;\">
			<button>{$__defaultaccount['name']}</button>
			<input type=\"text\" id=\"jQmonthSelection\" value=\"".date("F ,Y")."\" data-slodefaultid=\"".date("Y-m-01")."\" data-slo=\"MONTH\" />
			<span>Total</span>
			<input type=\"text\" readonly=\"readonly\" id=\"jQtotal\" style=\"width:140px;text-align:right\" value=\"0.00\" />
			<span>{$__defaultcurrency['symbol']}</span>
			<span>Records</span>
			<input type=\"text\" style=\"width:80px;text-align:right\" id=\"jQcount\" readonly=\"readonly\" value=\"0\" />
			".($c__actions->read?"<span class=\"gap\"></span><button id=\"jQexportButton\">Export</button>":"")."
		</div>";
	?>
	<form action="<?php echo $export_pagefile['directory']; ?>" method="post" target="_blank" id="jQexportFrom" style="display:none;">
	<input type="hidden" name="month" value="" />
	</form>
	<table class="bom-table hover">
		<thead>
		<tr>
			<td colspan="2">Employee</td>
			<td align="right" style="min-width:150px;">Salary</td>
			<td width="100%">Records</td>
			
		</tr>
		</thead>
		<tbody id="jQoutput"></tbody>
	</table>
	<script>
	$(document).ready(function(e) {
		$("#jQmonthSelection").slo({
			onselect:function(slodata){
				fetch();
			},ondeselect:function(){
				$("#jQoutput").html("");
				$("#jQtotal").val("0.00");
				$("#jQcount").val("0");
				
			}
		});
		$("#jQexportButton").on('click',function(){
			$("#jQexportFrom").find("[name=month]").val($("#jQmonthSelection_1").val());
			$("#jQexportFrom").submit();
		});
		
		var fetch=function(){
			var month=$("#jQmonthSelection_1").val();
			$.ajax({
				url:'<?php echo $pageinfo['directory'];?>',
				type:"POST",
				data:{'salarymonth':month}
			}).done(function(data){
				var $data=$(data);
				var json=null;
				try{
					json=JSON.parse($data.find("#___ajax_sum").html());
				}catch(e){messagesys.failure("Parsing output failed");return false;}
				
				$("#jQtotal").val(json.total);
				$("#jQcount").val(json.count);
				
				$("#jQoutput").html($data.find("#___ajax_tbody").html());
			});
		}
		fetch();
	});
	</script>
	
	
	
<?php }?>