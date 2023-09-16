<?php
if (isset($_POST['method']) && $_POST['method'] == "updatepermissions") {
	if ($r = $app->db->query("UPDATE users SET 
			usr_privileges=" . ((int)$_POST['permission']) . " WHERE usr_id=" . ((int)$_POST['usr_id']) . ";")) {
		echo "1";
	} else {
		echo "0";
	}
	exit;
}
?><table class="bom-table hover">
	<thead>
		<tr>
			<td>Username</td>
			<td>Full name</td>
		</tr>
	</thead>
	<tbody>
		<?php

		if ($r = $app->db->query(
			"SELECT
				CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS fullname, usr_username, usr_activate, usr_id,per_title,per_id
			FROM
				users 
					JOIN labour ON lbr_id=usr_id
					JOIN permissions ON per_id = usr_privileges
			WHERE
				usr_id != 1 AND lbr_resigndate IS NULL AND usr_privileges != {$app->base_permission} AND usr_activate = 1
			ORDER BY
				per_title
			;"
		)) {
			while ($row = $r->fetch_assoc()) {
				echo "<tr>";
				echo "<td>{$row['usr_username']}</td>";
				echo "<td><a href=\"" . $fs(182)->dir .  "?id={$row['usr_id']}\">{$row['fullname']}</a></td>";
				echo "</tr>";
			}
		}
		?>
	</tbody>
</table>
<script>
	$(document).ready(function(e) {
		$("#jsUser").slo({
			onselect: function(data) {
				var $ajax = $.ajax({
					url: "<?= $fs(60)->dir; ?>",
					type: "POST",
					data: {
						'method': 'updatepermissions',
						'permission': data.hidden,
						'usr_id': data.object.attr("data-usr_id")
					}
				}).done(function(data) {
					if (data == "1") {
						messagesys.success("User permissions updated successfully");
					} else {
						messagesys.failure("Failed to update user permissions");
					}
				});
			}
		});
	});
</script>