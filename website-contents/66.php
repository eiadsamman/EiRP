<?php
if(isset($_POST['method']) && $_POST['method']=="updatepermissions"){
	if($r=$sql->query("UPDATE users SET 
			usr_attrib_i2=".((int)$_POST['permission']==1?"0":"1").",
			usr_privileges=".((int)$_POST['permission'])." WHERE usr_id=".((int)$_POST['usr_id']).";")){
		echo "1";
	}else{
		echo "0";
	}
	exit;
}
?><table class="bom-table hover">
<thead>
	<tr>
		<td>Username</td>
		<td>Full name</td>
		<td>Permissions</td>
	</tr>
</thead>
<tbody>
<?php

if($r=$sql->query("
	SELECT
		CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS fullname,
		usr_username,usr_activate,
		usr_id,per_title,per_id
	FROM
		users 
			JOIN labour ON lbr_id=usr_id
			JOIN permissions ON per_id=usr_privileges
	WHERE
		usr_attrib_i2=1 AND usr_id!=1 AND lbr_resigndate IS NULL
	ORDER BY
		per_title
	;
	")){
	while($row=$sql->fetch_assoc($r)){
		echo "<tr>";
		echo "<td>{$row['usr_username']}</td>";
		echo "<td><a href=\"" . $tables->pagefile_info(60,null,"directory") .  "?id={$row['usr_id']}\">{$row['fullname']}</a></td>";
		echo "<td class=\"btn-set normal\"><input type=\"text\" id=\"jsUser\" data-slo=\"C001\" data-usr_id=\"{$row['usr_id']}\" data-slodefaultid=\"{$row['per_id']}\" value=\"{$row['per_title']}\" /></td>";
		echo "</tr>";
	}
}
?>
</tbody>
</table>
<script>
$(document).ready(function(e) {
	$("#jsUser").slo({
		onselect:function(data){
			var $ajax=$.ajax({
				url:"",
				type:"POST",
				data:{'method':'updatepermissions','permission':data.hidden,'usr_id':data.object.attr("data-usr_id")}
			}).done(function(data){
				if(data=="1"){
					messagesys.success("User permissions updated successfully");
				}else{
					messagesys.failure("Failed to update user permissions");
				}
			});
		}
	});
});
</script>

