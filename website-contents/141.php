<?php
if($c__actions->edit && isset($_POST['change-cwk'],$_POST['status'])){
	$_POST['change-cwk']=(int)$_POST['change-cwk'];
	$_POST['status']=(int)$_POST['status'];
	if($sql->query("UPDATE calendar_weekends SET cwk_status={$_POST['status']} WHERE cwk_id={$_POST['change-cwk']}")){
		echo "1";
	}else{
		echo "0";
	}
	exit;
}
?>
<table class="bom-table hover">
<thead>
<tr>
	<td>Day</td>
	<td width="100%">Working Status</td>
</tr>
</thead>
<tbody>
<?php
$r=$sql->query("SELECT cwk_status,cwk_name,cwk_id FROM calendar_weekends ORDER BY cwk_id");
if($r){
	while($row=$sql->fetch_assoc($r)){
		echo "<tr><td>{$row['cwk_name']}</td><th>";
		if($c__actions->edit){
			echo "<label class=\"ios-io\"><input type=\"checkbox\" class=\"change-we-status\" data-cwk_id=\"{$row['cwk_id']}\" ".($row['cwk_status']==1?"checked=\"checked\"":"")." /><span>&nbsp;</span><div></div></lable>";
		}else{
			echo $row['cwk_status']==1?"<span style=\"color:#06c;font-weight:bold\">On</span>":"<span style=\"color:#888\">Off</span>";
		}
		echo "</th></tr>";
	}
}
?>
</tbody>
</table>
<?php if($c__actions->edit){?>
<script>
$(document).ready(function(e) {
	$(".change-we-status").on('change',function(e){
		var $this=$(this),
			_cwk_id=$this.attr("data-cwk_id"),
			_status=$this.prop("checked");
		$this.prop("disabled",true);
		
		$.ajax({
			url:"<?php echo $pageinfo['directory'];?>",
			type:"POST",
			data:{"change-cwk":_cwk_id,"status":~~_status}
		}).done(function(data){
			if(data=="1"){
				messagesys.success("Weekend day updated successfully");
			}else{
				messagesys.failure("Failed to udpate weekend day, try again");
				$this.prop("checked",!_status);
			}
			$this.prop("disabled",false);
		}).fail(function(a,b,c){
			messagesys.failure(b+" - "+c);
			$this.prop("disabled",false);
		});

	});
});
</script>
<?php }?>