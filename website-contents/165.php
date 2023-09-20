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
		$r = $app->db->query("SELECT usrset_id,usrset_value,usrset_usr_defind_name,UNIX_TIMESTAMP(usrset_time) AS usrset_time FROM user_settings WHERE usrset_usr_id={$app->user->info->id} AND usrset_type = " . \System\Personalization\Identifiers::AccountCustomeQuerySave->value . ";");
		if ($r) {
			if ($r->num_rows == 0) {
				echo "<tr><td colspan=\"4\">No saved queries</td></tr>";
			} else {
				while ($row = $r->fetch_assoc()) {
					echo "<tr data-id=\"{$row['usrset_id']}\"><td style=\"width:10px;\" class=\"op-donwload jQload_query\"><span></span></td><td style=\"width:300px;\">" . $row['usrset_usr_defind_name'] . "</td>
					<td>" . date("Y-m-d H:i:s", $row['usrset_time']) . "<td class=\"op-remove jQremove_query\"><span></span></td></tr>";
				}
				$r->free_result();
			}
		}
		?>
	</tbody>
</table>
<div class="btn-set" style="justify-content:center;margin-top:10px;"><button type="button"
		id="jQload_cancel">Close</button></div>