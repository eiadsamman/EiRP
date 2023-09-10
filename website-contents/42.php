<?php
if(isset($_POST['manual'])){
	$count=(int)$_POST['manual'];
	if($r=$sql->query("
		SELECT 
			usr_id,usr_firstname,usr_lastname,
			lsf_name,lty_name
		FROM
			labour 
				JOIN users ON usr_id=lbr_id
				LEFT JOIN labour_shifts ON lsf_id=lbr_shift
				LEFT JOIN labour_type ON lty_id=lbr_type
		WHERE
			lbr_resigndate IS NULL
		LIMIT 0,$count
		;")){
		while($row=$sql->fetch_assoc($r)){
			echo "<tr data-id=\"{$row['usr_id']}\">";
			echo "<td><input type=\"hidden\" name=\"employees[]\" value=\"{$row['usr_id']}\" />{$row['usr_firstname']} {$row['usr_lastname']}</td>";
			echo "<td></td>";
			echo "<td>{$row['lsf_name']}</td>";
			echo "<td>{$row['lty_name']}</td>";
			echo "<td class=\"op-remove\"><span></span></td>";
			echo "</tr>";
		}
	}
	exit;
}
if(isset($_POST['range'])){	
	if($r=$sql->query("
		SELECT 
			usr_id,usr_firstname,usr_lastname,
			lsf_name,lty_name
		FROM
			labour 
				JOIN users ON usr_id=lbr_id
				LEFT JOIN labour_shifts ON lsf_id=lbr_shift
				LEFT JOIN labour_type ON lty_id=lbr_type
		WHERE
			lbr_resigndate IS NULL AND usr_id>=".((int)$_POST['from'])." AND usr_id<=".((int)$_POST['to'])."
		;")){
		while($row=$sql->fetch_assoc($r)){
			echo "<tr data-id=\"{$row['usr_id']}\">";
			echo "<td><input type=\"hidden\" name=\"employees[]\" value=\"{$row['usr_id']}\" />{$row['usr_firstname']} {$row['usr_lastname']}</td>";
			echo "<td></td>";
			echo "<td>{$row['lsf_name']}</td>";
			echo "<td>{$row['lty_name']}</td>";
			echo "<td class=\"op-remove\"><span></span></td>";
			echo "</tr>";
		}
	}
	exit;
}
if(isset($_POST['employee'])){
	if($r=$sql->query("
		SELECT 
			usr_id,usr_firstname,usr_lastname,
			lsf_name,lty_name
		FROM
			labour 
				JOIN users ON usr_id=lbr_id
				LEFT JOIN labour_shifts ON lsf_id=lbr_shift
				LEFT JOIN labour_type ON lty_id=lbr_type
		WHERE
			lbr_id=".((int)$_POST['employee'])." AND lbr_resigndate IS NULL
		;")){
		while($row=$sql->fetch_assoc($r)){
			echo "<tr data-id=\"{$row['usr_id']}\">";
			echo "<td><input type=\"hidden\" name=\"employees[]\" value=\"{$row['usr_id']}\" />{$row['usr_firstname']} {$row['usr_lastname']}</td>";
			echo "<td></td>";
			echo "<td>{$row['lsf_name']}</td>";
			echo "<td>{$row['lty_name']}</td>";
			echo "<td class=\"op-remove\"><span></span></td>";
			echo "</tr>";
		}
	}
	exit;
}
if(isset($_POST['shift'])){
	if($r=$sql->query("
		SELECT 
			usr_id,usr_firstname,usr_lastname,
			lsf_name,lty_name
		FROM
			labour 
				JOIN users ON usr_id=lbr_id
				LEFT JOIN labour_shifts ON lsf_id=lbr_shift
				LEFT JOIN labour_type ON lty_id=lbr_type
		WHERE
			lbr_shift=".((int)$_POST['shift'])." AND lbr_resigndate IS NULL
		;")){
		while($row=$sql->fetch_assoc($r)){
			echo "<tr data-id=\"{$row['usr_id']}\">";
			echo "<td><input type=\"hidden\" name=\"employees[]\" value=\"{$row['usr_id']}\" />{$row['usr_firstname']} {$row['usr_lastname']}</td>";
			echo "<td></td>";
			echo "<td>{$row['lsf_name']}</td>";
			echo "<td>{$row['lty_name']}</td>";
			echo "<td class=\"op-remove\"><span></span></td>";
			echo "</tr>";
		}
	}
	exit;
}
?>
<table class="bom-table">
	<thead>
		<tr class="special">
			<td>Add employee cards to print</td>
		</tr>
		<tr>
			<td style="color:#333;"><div class="btn-set"><span>By name</span><input type="text" data-slo="E005" id="employee" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;max-width:300px;min-width:200px" /></div></td>
		</tr>
		<!--<tr>
			<td class="btn-set" style="color:#333;"><span>By shift</span><input type="text" data-slo="E003" id="shift" style="max-width:300px;width:100%;min-width:300px" /></td>
		</tr>-->
		<tr>
			<td style="color:#333;"><div class="btn-set"><span>Get random non-verified serials</span><input type="text" id="jQmanualVal" style="width:80px;" value="10" /><button id="jQmanualBtn">Add serial</button></div></td>
		</tr>
		<tr>
			<td style="color:#333;"><div class="btn-set"><span>ID range</span><input type="text" id="jQrangeFrom" style="width:80px;" value="0" /><span>To</span><input type="text" id="jQrangeTo" style="width:80px;" value="0" /><button id="jQrangeBtn">Add serial</button></div></td>
		</tr>
	</tbody>
</table>
<br />
<form action="<?php echo $tables->pagefile_info(28,null,'directory');?>" target="_blank" method="post">
<table class="bom-table hover">
	<tbody>
		<tr class="special">
			<td>Employee</td>
			<td>Serial</td>
			<td>Shift</td>
			<td>Job</td>
			<td style="width:30px;max-width:30px;"></td>
		</tr>
	</tbody>
	<tbody id="employeesOutput"></tbody>
	<tbody>
		<tr>
			<th colspan="5" style="width:100px;color:#333;"><div class="btn-set noselect" style="justify-content:center"><span><label><input type="checkbox" value="1" name="verified" /> <span style="display:inline-block;vertical-align:text-bottom">Print verified</span></label></span><button type="submit">Print Serial</button></div></th>
		</tr>
	</tbody>
</table>
</form>
<script>
$(document).ready(function(e) {
	var $ajax=null;
	$("#jQmanualBtn").on('click',function(){
		var count=$("#jQmanualVal").val();
		$ajax=$.ajax({
			data:{'manual':count},
			url:"<?php echo $fs()->dir;?>",
			type:"POST"
		}).done(function(data){
			$("#employeesOutput").prepend(data);
		}).fail(function(a,b,c){
			messagesys.failure(b);
		});
	});
	var addemployee=function(id){
		$ajax=$.ajax({
			data:{'employee':id},
			url:"<?php echo $fs()->dir;?>",
			type:"POST"
		}).done(function(data){
			var $data=$(data);
			var _id=$data.attr("data-id");
			if(_id==undefined){return false;}
			if($("#employeesOutput > tr[data-id="+_id+"]").length==0){
				$("#employeesOutput").prepend(data);
			}else{
				messagesys.failure("Already exists");
			}
		}).fail(function(a,b,c){
			messagesys.failure(b);
		});
	}
	
	var addshift=function(id){
		$ajax=$.ajax({
			data:{'shift':id},
			url:"<?php echo $fs()->dir;?>",
			type:"POST"
		}).done(function(data){
			$("#employeesOutput").prepend(data);
		}).fail(function(a,b,c){
			messagesys.failure(b);
		});
	}
	
	
	
	var SLO=$("#employee").slo({
		limit:15,
		onselect:function(data){
			if($ajax!=null){$ajax.abort();}
			addemployee(data.hidden);
		}
	});
	var SLOSHIFT=$("#shift").slo({
		limit:15,
		onselect:function(data){
			if($ajax!=null){$ajax.abort();}
			addshift(data.hidden);
		}
	});
	
	$("#employeesOutput").on('click','.op-remove',function(){
		$(this).closest('tr').remove();
	});
	$("#jQrangeBtn").on('click',function(){
		var from=$("#jQrangeFrom").val(),
			to=$("#jQrangeTo").val();
		$.ajax({
			data:{'range':0,'from':from,'to':to},
			url:"<?php echo $fs()->dir;?>",
			type:"POST"
		}).done(function(data){
			$("#employeesOutput").prepend(data);
		});
	});
});
</script>