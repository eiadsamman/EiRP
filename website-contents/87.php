<?php
//ALTER TABLE `currency_exchange` ADD `curexg_type` BIT(1) NOT NULL DEFAULT b'1' AFTER `curexg_value`;
if(isset($_POST['selectedCurrency'])){
	$selectedCurrency=(int)$_POST['selectedCurrency'];
	$output=array("result"=>false,"list"=>array(),"sys"=>"");
	
	$systemCurrency=null;
	$r=$sql->query("SELECT cur_shortname FROM currencies WHERE cur_id=$selectedCurrency;");
	if($r){
		if($row=$sql->fetch_assoc($r)){
			$output["sys"]=$row['cur_shortname'];
		}
	}
	$r=$sql->query("
		SELECT * 
		
		FROM 
			currencies 
			JOIN(
				SELECT _from.curexg_from  AS _rate_from, _to.curexg_from AS _rate_to,(_from.curexg_value / _to.curexg_value) AS _rate 
				FROM currency_exchange AS _from INNER JOIN currency_exchange AS _to 
			) AS _rates ON _rates._rate_to = $selectedCurrency AND _rates._rate_from = cur_id
		
		WHERE cur_id!=$selectedCurrency;");
	if($r){
		while($row=$sql->fetch_assoc($r)){
			$output['result']=true;
			$output['list'][]=array(
				$row['cur_id'],
				$row['cur_name'],
				$row['cur_shortname'],
				$row['_rate'],
			);
		}
	}
	echo json_encode($output);
	exit;
}

if(isset($_POST['fieldSellCurTo'])){
	$excur_from = (int)$_POST['fieldCurFrom'][1];
	if($excur_from==0){
		echo "0";
	}
	
	$sql->autocommit(false);
	$r = true;
	$r &= $sql->query("TRUNCATE currency_exchange;");
	$r &= $sql->query("INSERT INTO currency_exchange (curexg_from,curexg_to,curexg_value) VALUES ({$excur_from} , {$excur_from} , 1)  ON DUPLICATE KEY UPDATE curexg_value=1;");
	
	$arr_switching = array();
	foreach($_POST['fieldSellCurTo'] as $cur_id=>$cur_value){
		if((float)$cur_value==0){
			header("HTTP_X_RESPONSE: ZERO");
			exit;
		}
		$arr_switching[]=array($cur_id,$cur_value);
	}
	
	if($r){
		foreach($_POST['fieldSellCurTo'] as $cur_id=>$cur_value){
			$cur_value=(float)$cur_value;
			$r&=$sql->query("INSERT INTO currency_exchange (curexg_from,curexg_to,curexg_value) VALUES ({$cur_id} , {$excur_from} , {$cur_value})  ON DUPLICATE KEY UPDATE curexg_value={$cur_value};");
		}
		/*?? Not sure if required as it can be calculated in class/accounting.php 
		for($i=0;$i<sizeof($arr_switching);$i++){
			for($j=$i+1;$j<sizeof($arr_switching);$j++){
				if((float)$_POST['fieldSellCurTo'][(int)$arr_switching[$j][0]]!=0){
					$v = (float)$_POST['fieldSellCurTo'][(int)$arr_switching[$i][0]] / (float)$_POST['fieldSellCurTo'][(int)$arr_switching[$j][0]];
					$q.=$s."(".(int)$arr_switching[$i][0]." , ".(int)$arr_switching[$j][0]." , ".$v.")";
				}
			}
		}*/
	}
	if($r){
		header("HTTP_X_RESPONSE: SUCCESS");
		$sql->commit();
	}else{
		header("HTTP_X_RESPONSE: DBERR");
		$sql->rollback();
	}
	exit;
}

if ($h__requested_with_ajax){exit;}
?>
<form id="jQpostForm">
<div style="padding:20px 0px 0px 0px;min-width:300px;max-width:800px;background-color: #fff;position: sticky;top:46px;z-index: 20;">
	<div class="btn-set">
		<span class="flex">Currency exchange rates</span>
	</div>
</div>
<div style="background-color:#fff;min-width:300px;max-width:800px;background-color: #fff;">
	<div style="padding-left:10px;margin-top:10px;/*overflow-y: auto;max-height: 253px;*/">
		<table class="bom-table" >
			<tbody>
				<tr>
					<th>Exchange from curreny</th>
					<td width="100%"><div class="btn-set"><input class="flex" name="fieldCurFrom" value="" data-slo="CURRENCY" type="text" id="jQinputFrom" /></div></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<div style="padding:20px 0px 0px 0px;min-width:300px;max-width:800px;background-color: #fff;position: sticky;top:98px;z-index: 20;">
	<div class="btn-set" style="background-color:#fff">
		<span class="flex">Currencies exchnage values</span><button id="jQpostSubmit" type="button">Save rates</button>
	</div>
</div>
<div style="background-color:#fff">
	<?php /*<div style="background-color:#fff;padding-left:10px;margin-top:10px;max-width:800px;"><div class="btn-set"><span class="flex">Selling price</span></div></div>*/?>
	<div style="padding-left:10px;margin-top:10px;max-width:800px;"><table class="bom-table hover"><tbody id="jQtableSellCurrencies"></tbody></table></div>
	
	<?php
	/*<div style="background-color:#fff;padding-left:10px;margin-top:10px;max-width:800px;"><div class="btn-set"><span class="flex">Buying price</span></div></div>
	<div style="padding-left:10px;margin-top:10px;max-width:800px;"><table class="bom-table hover"><tbody id="jQtableBuyCurrencies"></tbody></table></div>*/
	?>
</div>
</form>

<script type="text/javascript">
	$(document).ready(function(){
		$("#jQinputFrom").slo({
			'ondeselect':function(){
				$("#jQtableSellCurrencies").empty();
				$("#jQtableBuyCurrencies").empty();
			},
			'onselect':function(v){
				overlay.show();
				$.ajax({
					url:"<?php echo $pageinfo['directory'];?>",
					type:"POST",
					data:"selectedCurrency=" + v.hidden
				}).done(function(o){
					var json=null;
					try{
						json=JSON.parse(o);
					}catch(e){messagesys.failure("Parsing output failed");return false;}
					
					let tableSellOutput=$("#jQtableSellCurrencies");
					let tableBuyOutput=$("#jQtableBuyCurrencies");
					
					tableSellOutput.empty();
					tableBuyOutput.empty();
					if(json.result){
						for(let c in json.list){
							let tr = $("<tr><td class=\"btn-set small\"><input type=\"text\" value=\""+json.list[c][3]+"\" name=\"fieldSellCurTo["+json.list[c][0]+"]\" /><span>"+json.sys+"</span></td><td width=\"100%\"> = 1 "+json.list[c][2]+" "+json.list[c][1]+"</td></tr>");
							tableSellOutput.append(tr);
							
							//tr = $("<tr><td class=\"btn-set small\"><input type=\"text\" name=\"fieldBuyCurTo["+json.list[c][0]+"]\" /><span>"+json.sys+"</span></td><td width=\"100%\"> = 1 "+json.list[c][2]+" "+json.list[c][1]+"</td></tr>");
							//tableBuyOutput.append(tr);
						}
					}else{
					}
				}).always(function(){
					overlay.hide();
				});
			}
		});
		$("#jQpostForm").on("submit",function(e){
			e.preventDefault;
			return false;
		});
		$("#jQpostSubmit").on("click",function(){
			overlay.show();
			$.ajax({
				url:"<?php echo $pageinfo['directory'];?>",
				type:"POST",
				data:$("#jQpostForm").serialize()
			}).done(function(o, textStatus, request){
				let response=request.getResponseHeader('HTTP_X_RESPONSE');
				if(response=="ZERO"){
					messagesys.failure("All currencies rates are required");
				}else if(response=="SUCCESS"){
					messagesys.success("Exchange rates updated successfully");
				}else if(response=="DBERR"){
					messagesys.failure("Updating exchange rates failed");
				}
			}).always(function(){
				overlay.hide();
			});
		});
		
	});
</script>
