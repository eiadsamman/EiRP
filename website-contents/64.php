<?php
function inner(&$app, $id, $selectedPermission)
{
	static $cnt = 1;
	if ($r = $app->db->query("SELECT 
						trd_id,trd_directory,pfl_value,pfp_value
					FROM 
						pagefile 
							JOIN pagefile_language ON pfl_trd_id=trd_id AND pfl_lng_id=1
							LEFT JOIN pagefile_permissions ON pfp_trd_id=trd_id AND pfp_per_id=$selectedPermission
					WHERE 
						trd_parent = $id
					ORDER BY 
						trd_zorder;")) {
		if ($r->num_rows > 0) {
			$cnt++;
			$clr = 255 - ($cnt * 20);

			while ($row = $r->fetch_assoc()) {
				echo "<tr id=\"{$row['trd_id']}\"><td>{$row['trd_id']}</td>";
				if ($row['pfl_value'] != "-") {
					echo "<td><div style=\"margin-left:" . (($cnt) * 20) . "px;\"><a href=\"{$row['trd_directory']}\" target=\"_blank\">" . $row['pfl_value'] . "</a></div></td>";
				} else {
					echo "<td><div style=\"margin-left:" . (($cnt) * 20) . "px;\">[Seperator]</div></td>";
				}
				$c__actions	= new AllowedActions((int)$selectedPermission, array($selectedPermission => $row['pfp_value']));
				echo "<td><div class=\"btn-set operations\" data-trd_id=\"{$row['trd_id']}\" data-trd_per=\"$selectedPermission\">";
				echo "<label class=\"btn-checkbox\">										<input name=\"read\" 	type=\"checkbox\" 	" . ($c__actions->read == true ? " checked=\"checked\"" : "") . " /><span>&nbsp;Read&nbsp;</span></label>";
				echo "<label class=\"btn-checkbox rel " . ($c__actions->read == true ? "" : " disabled") . "\"><input name=\"add\" 	type=\"checkbox\" 	" . ($c__actions->add == true && $c__actions->read == true ? " checked=\"checked\"" : "") . "/><span>&nbsp;Add&nbsp;</span></label>";
				echo "<label class=\"btn-checkbox rel " . ($c__actions->read == true ? "" : " disabled") . "\"><input name=\"edit\" 	type=\"checkbox\" 	" . ($c__actions->edit == true && $c__actions->read == true ? " checked=\"checked\"" : "") . "/><span>&nbsp;Edit&nbsp;</span></label>";
				echo "<label class=\"btn-checkbox rel " . ($c__actions->read == true ? "" : " disabled") . "\"><input name=\"delete\" 	type=\"checkbox\" 	" . ($c__actions->delete == true && $c__actions->read == true ? " checked=\"checked\"" : "") . "/><span>&nbsp;Delete&nbsp;</span></label>";
				echo "</div>";
				echo "</td>";
				echo "</tr>";
				inner($pp, $row['trd_id'], $selectedPermission);
			}
			$cnt--;
		}
	}
}
if (isset($_POST['permission'])) {
	if ($r = $app->db->query("
		SELECT 
			trd_directory,trd_id,pfl_value,pfp_value
		FROM 
			pagefile 
				JOIN pagefile_language ON pfl_trd_id=trd_id AND pfl_lng_id=1 
				LEFT JOIN pagefile_permissions ON pfp_trd_id=trd_id AND pfp_per_id={$_POST['permission']}
		WHERE 
			trd_parent=0
		ORDER BY 
			trd_zorder;")) {
		while ($row = $r->fetch_assoc()) {
			echo  "<tr id=\"{$row['trd_id']}\"><td>{$row['trd_id']}</td>";
			echo  "<td><a href=\"{$row['trd_directory']}\" target=\"_blank\">" . $row['pfl_value'] . "</a></td>";
			$c__actions			= new AllowedActions((int)$_POST['permission'], array($_POST['permission'] => $row['pfp_value']));
			echo  "<td><div class=\"btn-set operations\" data-trd_id=\"{$row['trd_id']}\" data-trd_per=\"{$_POST['permission']}\">";
			echo  "<label class=\"btn-checkbox\">										<input name=\"read\" type=\"checkbox\" " . ($c__actions->read == true ? " checked=\"checked\"" : "") . " /><span>&nbsp;Read&nbsp;</span></label>";
			echo  "<label class=\"btn-checkbox rel " . ($c__actions->read == true ? "" : " disabled") . "\"><input name=\"add\" type=\"checkbox\" " . ($c__actions->add == true && $c__actions->read == true ? " checked=\"checked\"" : "") . "/><span>&nbsp;Add&nbsp;</span></label>";
			echo  "<label class=\"btn-checkbox rel " . ($c__actions->read == true ? "" : " disabled") . "\"><input name=\"edit\" type=\"checkbox\" " . ($c__actions->edit == true && $c__actions->read == true ? " checked=\"checked\"" : "") . "/><span>&nbsp;Edit&nbsp;</span></label>";
			echo  "<label class=\"btn-checkbox rel " . ($c__actions->read == true ? "" : " disabled") . "\"><input name=\"delete\" type=\"checkbox\" " . ($c__actions->delete == true && $c__actions->read == true ? " checked=\"checked\"" : "") . "/><span>&nbsp;Delete&nbsp;</span></label>";
			echo  "</div>";
			echo  "</td>";
			echo  "</tr>";
			inner($app, $row['trd_id'], (int)$_POST['permission']);
		}
	}
	exit;
}

if (isset($_POST['method'], $_POST['xtrd'], $_POST['xper']) && $_POST['method'] == 'update') {
	$_POST['xtrd'] = (int)$_POST['xtrd'];
	$_POST['xper'] = (int)$_POST['xper'];

	$param = (isset($_POST['read']) && (int)$_POST['read'] == 1 ? "1" : "0") .
		(isset($_POST['add']) && (int)$_POST['add'] == 1 ? "1" : "0") .
		(isset($_POST['edit']) && (int)$_POST['edit'] == 1 ? "1" : "0") .
		(isset($_POST['delete']) && (int)$_POST['delete'] == 1 ? "1" : "0");

	$per = bindec($param);

	if ($r = $app->db->query("INSERT INTO pagefile_permissions (pfp_trd_id,pfp_per_id,pfp_value) VALUES ({$_POST['xtrd']},{$_POST['xper']},$per) ON DUPLICATE KEY UPDATE pfp_value=$per;")) {
		echo "true";
		exit;
	} else {
		echo "Unable to udpate pagefile permissons";
		exit;
	}

}
?>
<div class="btn-set">
	<span>Permission</span><input type="text" id="jQperm" data-slo="C001" style="width:350px;" />
</div>
<table class="bom-table hover" style="margin-top:15px;">
	<thead>
		<tr>
			<td>#</td>
			<td width="100%">Page name</td>
			<td></td>
		</tr>
	</thead>
	<tbody id="jQoutput"></tbody>
</table>
<script>
	$(document).ready(function(e) {
		$("#jQperm").slo({
			'limit': 15,
			onselect: function(data) {
				var $ajax = $.ajax({
					url: "<?php echo $fs()->dir; ?>",
					type: "POST",
					data: {
						'permission': data.hidden
					}
				}).done(function(data) {
					$("#jQoutput").html(data);
				});
			},
			ondeselect: function(obj) {
				$("#jQoutput").html("");
			}
		});
		var update = function($obj) {
			var contain = $obj.parent().parent(),
				trd = contain.attr("data-trd_id"),
				prm = contain.attr("data-trd_per"),
				param = {},
				type = null;
			contain.find("input").each(function() {
				param[$(this).attr("name")] = $(this).prop("checked") ? "1" : "0";
			});

			var $ajax = $.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: {
					'method': 'update',
					'read': param.read,
					'add': param.add,
					'edit': param.edit,
					'delete': param.delete,
					'xtrd': trd,
					'xper': prm
				}
			}).done(function(data) {
				if (data == "true") {
					messagesys.success("Pagefile permissions updated successfully");
				} else {
					messagesys.failure(data);
				}
			});
		}
		$("#jQoutput").on('change', "div.operations > label > input", function() {
			var type = $(this).attr("name");
			var $this = $(this);
			var contain = $this.parent().parent();
			if (type == "read") {
				if ($this.prop("checked")) {
					contain.find("label.rel").removeClass("disabled");
				} else {
					contain.find("label.rel").addClass("disabled");
					contain.find("label.rel").find("input").prop("checked", false);
				}
			}
			update($this);
		});
	});
</script>