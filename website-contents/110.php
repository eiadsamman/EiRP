<?php
if($c__actions->edit && isset($_POST['remove-user-partition'],$_POST['prt_id'],$_POST['usr_id'])){
	$usr_id=(int)$_POST['usr_id'];
	$prt_id=(int)$_POST['prt_id'];
	
	if($sql->query("DELETE FROM user_partition WHERE upr_usr_id=$usr_id AND upr_prt_id=$prt_id")){
		echo "1";
	}else{
		echo "0";	
	}
	exit;
}
$arr=array();
if($r=$sql->query("
	SELECT 
		usr_id,usr_username,prt_name ,prt_id,prt_name
	FROM
		`acc_accounts`
		LEFT JOIN
			(
				SELECT
					upr_prt_id,usr_id,usr_username
				FROM
					user_partition JOIN users ON usr_id=upr_usr_id
			) AS _a ON _a.upr_prt_id=prt_id
	ORDER BY 
		prt_name,usr_id
	")){
	while($row=$sql->fetch_assoc($r)){
		if(!isset($arr[$row['prt_id']])){
			$arr[$row['prt_id']]=array($row['prt_name'],array());
		}
		if(!is_null($row['usr_id'])){
			$arr[$row['prt_id']][1][$row['usr_id']]=$row['usr_username'];
		}
	}
}
?>
<style>
.p110{
	display:inline-block;border:solid 1px #ccc;
	border-radius:2px;
	padding:4px 7px;
	margin:1px;
	background-color:#fff;
	cursor:default;
	
	white-space:nowrap;
}
.p110:hover{
	border-color:#666;
}
.p110 > span{
	display:inline-block;
	max-width:100px;
	min-width:100px;
	overflow:hidden;
}
<?php if($c__actions->delete){?>
.p110 > span:before{
	font-family:icomoon2;
	content:"\f00d";
	color:#bbb;
	font-size:0.8em;
	display:inline-block;
	padding-right:5px;
}
.p110:hover > span:before{
	color:#f00;
}
<?php }?>
</style>
<table class="bom-table hover">
<thead><td>ID</td><td>Partition</td><td width="100%">Users</td></thead>
<tbody>
<?php
foreach($arr as $k=>$v){
	echo "<tr>";
	echo "<td>$k</td><th>{$v[0]}</th><td style=\"white-space:normal\">";
	
	foreach($v[1] as $kuser=>$vuser){
		echo "<span data-usr_id=\"$kuser\" data-prt_id=\"$k\" class=\"p110 noselect\"><span>{$vuser}</span></span>";
	}
	echo "</td></tr>";
}
?>
</tbody></table>
<?php if($c__actions->delete){?>
<script>
$(document).ready(function(e) {
	$(".p110").on('click',function(){
		var $this=$(this),
			_usr_id=$this.attr("data-usr_id"),
			_prt_id=$this.attr("data-prt_id");
		$.ajax({
			url:"",
			type:"POST",
			data:{"remove-user-partition":"","usr_id":_usr_id,"prt_id":_prt_id}
		}).done(function(data){
			if(data=="1"){
				$this.remove();
				messagesys.success("User-Partition removed");
			}else{
				messagesys.failure("Failed to remove User-Partition, try again");
			}
		});
	});
});
</script>
<?php }?>






































