<table class="bom-table hover">
<thead>
	<tr>
		<td colspan="2" style="width:300px;">Query name</td>
		<td>Query date</td>
		<td></td>
	</tr>
</thead>
<tbody>
	<?php
	$r=$sql->query("SELECT usrset_id,usrset_value,usrset_usr_defind_name,UNIX_TIMESTAMP(usrset_time) AS usrset_time FROM user_settings WHERE usrset_usr_id={$USER->info->id} AND usrset_name='account_custome_query_save'");
	if($r){
		if($sql->num_rows($r)==0){
			echo "<tr><td colspan=\"4\">No saved queries</td></tr>";
		}else{
			while($row=$sql->fetch_assoc($r)){
				echo "<tr data-id=\"{$row['usrset_id']}\"><td style=\"width:10px;\" class=\"op-donwload jQload_query\"><span></span></td><td style=\"width:300px;\">".$row['usrset_usr_defind_name']."</td>
					<td>".date("Y-m-d H:i:s",$row['usrset_time'])."<td class=\"op-remove jQremove_query\"><span></span></td></tr>";
			}
		}
	}
	?>
</tbody>
</table>
<div class="btn-set" style="justify-content:center;margin-top:10px;"><button type="button" id="jQload_cancel">Close</button></div>
<?php $sql->free_result($r);?>