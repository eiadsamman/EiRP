<?php
include_once("admin/class/json.php");
if (isset($_POST['new-child']) && isset($_POST['parent'])) {
	$parent = (int)$_POST['parent'];
	if ($parent == 0) {
		$parentName = "Root";
	} else {
		$r = $app->db->query("SELECT hir_name FROM job_hierarchy WHERE hir_id=$parent;");
		if ($r && $row = $r->fetch_assoc()) {
			$parentName = $row['hir_name'];
		} else {
			echo "Invalid child";
		}
	}
	echo "<form class=\"jQaddChild\">
	<input type=\"hidden\" name=\"parent\" value=\"$parent\" />
	<input type=\"hidden\" name=\"append-child\" value=\"\" />
	<div class=\"btn-set\"><span>Add branch in</span><input type=\"text\" style=\"max-width:150px;text-align:center\" readonly value=\"{$parentName}\" /><input name=\"name\" type=\"text\" />";
	echo "<button type=\"submit\">Add</button></form>";
	echo "</div>";
	exit;
}

if (isset($_POST['display-child']) && isset($_POST['id'])) {
	$parent = (int)$_POST['id'];
	if ($parent == 0) {
		echo "<div class=\"btn-set\"><span>Root branch, add a child nodes to this branch to begin</span></div>";
		exit;
	} else {
		$r = $app->db->query("SELECT hir_name,hir_id,hir_parent FROM job_hierarchy WHERE hir_id=$parent;");
		if ($r && $row = $r->fetch_assoc()) {
			$record = $row;
		} else {
			echo "Invalid branch";
		}
	}
	echo "
	<div class=\"btn-set\"><span>Branch</span><span>{$record['hir_id']}</span><input name=\"name\" id=\"jQupdateNameInput\" type=\"text\" value=\"{$record['hir_name']}\" />
	<button type=\"button\" id=\"jQupdateName\" data-id=\"{$record['hir_id']}\">Update</button><button data-id=\"{$record['hir_id']}\" id=\"jQdeleteBranch\">Delete</button>";
	echo "</div><h1 style=\"margin:15px 15px;color:#666\">Job roles</h1>";
	echo "<div class=\"btn-set\"><span>Add job role</span><input type=\"text\" id=\"jQjobRole\" data-id=\"{$record['hir_id']}\" style=\"min-width:305px\" data-slo=\"E002\" /></div>";
	echo "<br /><table class=\"bom-table hover\"><thead><tr><td></td><td width=\"100%\">Job</td></tr></thead><tbody id=\"jQjobListTable\">";
	if ($r = $app->db->query("SELECT jhr_hir_id,jhr_job_id,lty_name,lsc_name 
						FROM job_hierarchyroles 
							JOIN 
							(SELECT 
								lty_id,lty_name,lsc_name
							FROM 
								labour_type JOIN labour_section ON lsc_id=lty_section 
							) AS _muljob ON _muljob.lty_id=jhr_job_id
						WHERE 
							jhr_hir_id={$_POST['id']}")) {
		while ($row = $r->fetch_assoc()) {
			echo "<tr><td class=\"op-remove jQdeleteJobRole\" data-branch=\"{$row['jhr_hir_id']}\" data-job=\"{$row['jhr_job_id']}\"><span></span></td><td>{$row['lsc_name']} {$row['lty_name']}</td></tr>";
		}
	}
	echo "</tbody></table>";

	echo "<h1 style=\"margin:15px 15px;color:#666\">Employees</h1>";

	echo "<div class=\"btn-set\">
			<span>Add employee</span>
			<input type=\"text\" id=\"jQemployee\" data-id=\"{$record['hir_id']}\" 
				style=\"min-width:305px\" data-sloparam=\"{'hir':'" . $record['hir_id'] . "'}\" data-slo=\"HIR_LABOUR\" /></div>";
	echo "<br /><table class=\"bom-table hover\"><thead><tr><td></td><td width=\"100%\">Employee</td><td></td></tr></thead><tbody id=\"jQempListTable\">";
	if ($r = $app->db->query("SELECT 
	CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS empname,lbr_id,hlb_id,lbr_resigndate
	FROM
		labour 
			JOIN users ON lbr_id=usr_id 
			JOIN job_hierarchylabour ON hlb_lbr_id=lbr_id 
	WHERE
		hlb_jhr_id={$record['hir_id']}  
	")) {
		while ($row = $r->fetch_assoc()) {
			echo "<tr><td class=\"op-remove jQdeleteEmployee\" data-branch=\"{$record['hir_id']}\" data-lbr=\"{$row['lbr_id']}\"><span></span></td><td>{$row['empname']}</td>
			<td style=\"color:#f03\">" . (!is_null($row['lbr_resigndate']) ? " Suspended" : "") . "</td></tr>";
		}
	}
	echo "</tbody></table>";

	exit;
}

if (isset($_POST['add-job']) && isset($_POST['branch']) && isset($_POST['job'])) {
	$_POST['job'] = (int)$_POST['job'];
	$_POST['branch'] = (int)$_POST['branch'];
	if ($app->db->query("INSERT INTO job_hierarchyroles (jhr_hir_id,jhr_job_id) VALUES ({$_POST['branch']},{$_POST['job']});")) {
		echo "1";
	} else {
		echo "0";
	}
	exit;
}

if (isset($_POST['delete-job-role']) && isset($_POST['branch']) && isset($_POST['job'])) {
	$json = new JSON();
	$_POST['job'] = (int)$_POST['job'];
	$_POST['branch'] = (int)$_POST['branch'];
	$app->db->autocommit(false);

	$r = true;
	$r &= $app->db->query("DELETE FROM job_hierarchyroles WHERE jhr_hir_id={$_POST['branch']} AND jhr_job_id={$_POST['job']};");
	if ($r) {
		$app->db->commit();
		$json->output(true, "Job role deleted succsesfully");
	} else {
		$app->db->commit();
		$json->output(false, "Deleting job role failed");
	}
	exit;
}

if (isset($_POST['update-branch-name']) && isset($_POST['id']) && isset($_POST['name'])) {
	$_POST['name'] = addslashes($_POST['name']);
	$_POST['id'] = (int)$_POST['id'];
	$json = new JSON();
	if ($r = $app->db->query("UPDATE job_hierarchy SET hir_name='{$_POST['name']}' WHERE hir_id={$_POST['id']}")) {
		$json->output(true, "Branch name udpated successfully", null, array("id" => $_POST['id'], "name" => $_POST['name']));
	} else {
		$json->output(false, $app->db->errno);
	}
	exit;
}

if (isset($_POST['delete-branch']) && isset($_POST['id'])) {
	$_POST['id'] = (int)$_POST['id'];
	$json = new JSON();
	$arrchilds = array();
	function digChilds(&$app, $id, &$arrchilds)
	{
		$r = $app->db->query("SELECT hir_id FROM job_hierarchy WHERE hir_parent=$id");
		if ($r) {
			while ($row = $r->fetch_assoc()) {
				$arrchilds[] = $row['hir_id'];
				digChilds($app, $row['hir_id'], $arrchilds);
			}
		}
	}

	$r = $app->db->query("SELECT hir_id FROM job_hierarchy WHERE hir_parent={$_POST['id']}");
	if ($r) {
		while ($row = $r->fetch_assoc()) {
			$arrchilds[] = $row['hir_id'];
			digChilds($app, $row['hir_id'], $arrchilds);
		}
	}
	$arrchilds[] = $_POST['id'];
	$app->db->autocommit(false);
	$r = true;
	foreach ($arrchilds as $v) {
		$r &= $app->db->query("DELETE FROM job_hierarchy WHERE hir_id=$v");
		$r &= $app->db->query("DELETE FROM job_hierarchyroles WHERE jhr_hir_id=$v");
	}
	if ($r) {
		$app->db->commit();
		$json->output(true, "Branch deleted successfully", null, array("id" => $_POST['id']));
	} else {
		$app->db->rollback();
		$json->output(true, "Failed to delete branch");
	}
	exit;
}

if (isset($_POST['append-child']) && isset($_POST['parent']) && isset($_POST['name'])) {
	$json = new JSON();
	$parent = (int)$_POST['parent'];
	$name = addslashes($_POST['name']);

	$r = $app->db->query("INSERT INTO job_hierarchy (hir_parent,hir_name) VALUES ($parent,'$name');");
	if ($r) {
		$newid = $app->db->insert_id;
		echo $json->output(true, "Child added successfully", null, array("parent" => $parent, "name" => $name, "id" => $newid));
	} else {
		echo $json->output(false, $app->db->errno);
	}
	exit;
}

if (isset($_POST['add-lbr']) && isset($_POST['branch']) && isset($_POST['lbr'])) {
	$json = new JSON();
	$_POST['branch'] = (int)$_POST['branch'];
	$_POST['lbr'] = (int)$_POST['lbr'];
	if ($r = $app->db->query("INSERT INTO job_hierarchylabour (hlb_jhr_id,hlb_lbr_id) VALUES ({$_POST['branch']},{$_POST['lbr']});")) {
		$newid = $app->db->insert_id;
		echo $json->output(true, "Employee added successfully", null, array("branch" => $_POST['branch'], "lbr" => $_POST['lbr']));
	} else {
		echo $json->output(false, $app->db->errno);
	}

	exit;
}

if (isset($_POST['delete-employee-hir']) && isset($_POST['lbr'])) {
	$_POST['lbr'] = (int)$_POST['lbr'];
	$_POST['branch'] = (int)$_POST['branch'];
	$json = new JSON();
	if ($app->db->query("DELETE FROM job_hierarchylabour WHERE hlb_lbr_id={$_POST['lbr']} AND hlb_jhr_id={$_POST['branch']}")) {
		echo $json->output(true, "Employee removed successfully");
	} else {
		echo $json->output(false, "Unable to remove employee from the list");
	}
	exit;
}

?>
<style>
	#css_hierTop {
		display: -webkit-box;
		display: -moz-box;
		display: -ms-flexbox;
		display: -webkit-flex;
		display: flex;
		-webkit-touch-callout: none;
		-webkit-user-select: none;
		-khtml-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
		user-select: none;
	}

	#css_hierLeft {
		white-space: nowrap;
		min-width: 200px;
	}

	#css_hierBody {
		-webkit-box-flex: 1;
		-moz-box-flex: 1;
		-webkit-flex: 1;
		-ms-flex: 1;
		flex: 1;
		border-left: solid 1px #ccc;
		margin-left: 10px;
		padding-left: 10px;
		padding-top: 4px;
	}

	#css_hierLeft h1 {
		width: 100%;
		display: inline-block;
		padding: 5px 10px;
		margin: 0;
		color: #333;
		border: solid 1px #ccc;
		border-radius: 4px;
		font-weight: normal;
		display: -moz-box;
		display: -ms-flexbox;
		display: -webkit-flex;
		display: flex;
		cursor: default;

	}

	#css_hierLeft h1>span {
		-webkit-box-flex: 1;
		-moz-box-flex: 1;
		-webkit-flex: 1;
		-ms-flex: 1;
		flex: 1;
		padding-right: 10px;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	#css_hierLeft h1>button {
		border: none;
		background-color: transparent;
		color: #777;
		cursor: pointer;
		background-color: #ccc;
		color: #fff;
		border-radius: 10px;
	}

	#css_hierLeft h1>i {
		font-style: normal;
		padding: 0px 6px;
		color: #888
	}

	#css_hierLeft h1>button:hover {
		background-color: #06c;
	}

	#css_hierLeft h1>button:active {
		transform: translate(1px, 1px);
	}

	#css_hierLeft h1>button:focus {
		outline: none;
	}

	#css_hierLeft h1:hover {
		text-shadow: 1px 1px 0 #fff;
		background-color: rgba(82, 168, 236, .1);
		border-color: rgba(82, 168, 236, .75);
		text-decoration: none;
	}

	#css_hierLeft div.nest {
		padding-left: 30px;
		margin: 4px 0px;
		max-width: 400px;
	}

	#css_hierLeft div.nest.root {
		padding-left: 0px;
	}

	#css_hierLeft h1.active {
		box-shadow: 0px 0px 3px 1px rgba(82, 168, 236, .75);
		border-color: rgba(82, 168, 236, .75);
	}
</style>

<div id="css_hierTop">
	<div id="css_hierLeft">
		<div class="nest root" data-id="0">
			<h1 data-parent="0" class="jQplot"><span>Root</span><button class="jQappendChild">+</button></h1>
			<?php
			function digHierarchy(&$app, $parent)
			{
				if ($r = $app->db->query("
					SELECT 
						hir_name,hir_id,hir_parent,count(hlb_id) AS jobcount 
					FROM 
						job_hierarchy 
							LEFT JOIN job_hierarchylabour ON hlb_jhr_id=hir_id 
					WHERE 
						hir_parent=$parent GROUP BY hir_id ORDER BY hir_id;")) {
					while ($row = $r->fetch_assoc()) {
						echo "<div class=\"nest\" data-id=\"{$row['hir_id']}\">";
						echo "<h1 data-parent=\"{$row['hir_id']}\" class=\"jQplot\"><span>{$row['hir_name']}</span><i>{$row['jobcount']}</i><button class=\"jQappendChild\">+</button></h1>";
						digHierarchy($app, $row['hir_id']);
						echo "</div>";
					}
				}
			}
			digHierarchy($app, 0);

			?>
		</div>
	</div>
	<div id="css_hierBody">
		<div id="jQhierBody">
		</div>
	</div>
</div>
<script>
	$(document).ready(function(e) {
		$("#css_hierLeft").on('click', ".jQappendChild", function() {
			var _parent = $(this).parent().attr("data-parent");
			$("#css_hierLeft h1").removeClass("active");
			$(this).closest("h1").addClass("active");
			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: {
					'new-child': '',
					'parent': _parent
				}
			}).done(function(data) {
				$("#jQhierBody").html(data);
			});
		});
		$("#css_hierLeft").on('click', ".jQplot", function(e) {
			if (e.target.tagName == "BUTTON") {
				return
			}
			var _id = $(this).attr("data-parent");

			$("#css_hierLeft h1").removeClass("active");
			var $h1 = $(this).closest("h1").addClass("active");

			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: {
					'display-child': '',
					'id': _id
				}
			}).done(function(data) {
				$("#jQhierBody").html(data);
				$("#jQhierBody").find("#jQjobRole").slo({
					limit: 10,
					onselect: function(output) {
						var me = $(output.object[0]);
						var branch_id = me.attr("data-id");
						var job_id = output.hidden;
						$.ajax({
							url: "<?php echo $fs()->dir; ?>",
							type: "POST",
							data: {
								'add-job': '',
								'branch': branch_id,
								'job': job_id
							}
						}).done(function(ajaxjob) {
							if (ajaxjob == 1) {
								$("#jQjobListTable").append("<tr><td class=\"op-remove jQdeleteJobRole\" data-branch=\"" + branch_id + "\" data-job=\"" + job_id + "\"><span></span></td><td>" + output.value + "</td></tr>");
								output.this.clear();
							} else {
								messagesys.failure("Job role already exists in current branch");
							}
						});
					}
				});
				$("#jQhierBody").find("#jQemployee").slo({
					limit: 10,
					onselect: function(output) {
						var me = $(output.object[0]);
						var branch_id = me.attr("data-id");
						var lbr_id = output.hidden;
						$.ajax({
							url: "<?php echo $fs()->dir; ?>",
							type: "POST",
							data: {
								'add-lbr': '',
								'branch': branch_id,
								'lbr': lbr_id
							}
						}).done(function(ajaxemp) {
							var json = null;
							try {
								json = JSON.parse(ajaxemp);
							} catch (e) {
								messagesys.failure("Unable to fetch output, refresh the page");
								return;
							}
							if (json.result) {

								$("#jQempListTable").append("<tr><td class=\"op-remove jQdeleteEmployee\" data-branch=\"" + json.branch + "\" data-lbr=\"" + json.lbr + "\"><span></span></td><td>" + output.value + "</td></tr>");
								output.this.clear();
							} else {
								messagesys.failure("Employee already exists in current branch");
							}
						});
					}
				});
			});

		});
		$("#jQhierBody").on('submit', 'form.jQaddChild', function(e) {
			e.preventDefault();
			var $this = $(this)
			var $form = $(this).serialize();
			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: $form
			}).done(function(data) {
				var json = null;
				try {
					json = JSON.parse(data);
				} catch (e) {
					messagesys.failure("Unable to fetch output, refresh the page");
					return;
				}
				if (json.result) {
					var newelement = "<div class=\"nest\" data-id=\"" + json.id + "\">";
					newelement += "<h1 data-parent=\"" + json.id + "\" class=\"jQplot\"><span>" + json.name + "</span><i>0</i><button class=\"jQappendChild\">+</button></h1>";
					newelement += "</div>";
					$(".nest[data-id=" + json.parent + "]").append(newelement);
					messagesys.success("Branch added successfully");
					$this.find("input[name=name]").val("");
				} else {
					messagesys.failure(json.message);
				}
			});
			return false;
		});

		$("#jQhierBody").on('click', 'button#jQupdateName', function() {
			var _id = $(this).attr("data-id"),
				_newname = $("#jQupdateNameInput").val();
			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: {
					'update-branch-name': '',
					'id': _id,
					'name': _newname
				}
			}).done(function(data) {
				var json = null;
				try {
					json = JSON.parse(data);
				} catch (e) {
					messagesys.failure("Unable to fetch output, refresh the page");
					return;
				}
				if (json.result) {
					messagesys.success("Branch name updated successfully");
					$(".nest[data-id=" + json.id + "] > h1 > span").html(json.name);
				} else {
					if (json.message == "1062") {
						messagesys.failure("Branch name already exists in this group");
					} else {
						messagesys.failure(json.message);
					}
				}
			});
		});
		$("#jQhierBody").on('click', 'button#jQdeleteBranch', function() {
			var _id = $(this).attr("data-id");
			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: {
					'delete-branch': '',
					'id': _id
				}
			}).done(function(data) {
				var json = null;
				try {
					json = JSON.parse(data);
				} catch (e) {
					messagesys.failure("Unable to fetch output, refresh the page");
					return;
				}
				if (json.result) {
					messagesys.success(json.message);
					$(".nest[data-id=" + json.id + "]").remove();
					$("#jQhierBody").html("");
				} else {
					messagesys.failure(json.message);
				}
			});
		});

		$("#jQhierBody").on('click', '.jQdeleteEmployee', function() {
			var $this = $(this);
			var branch = $this.attr("data-branch");
			var lbr = $this.attr("data-lbr");

			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: {
					'delete-employee-hir': '',
					'branch': branch,
					'lbr': lbr
				}
			}).done(function(data) {
				var json = null;
				try {
					json = JSON.parse(data);
				} catch (e) {
					messagesys.failure("Unable to fetch output, refresh the page");
					return;
				}
				if (json.result) {
					messagesys.success(json.message);
					$this.closest("tr").remove();
				} else {
					messagesys.failure(json.message);
				}
			});
		});


		$("#jQhierBody").on('click', '.jQdeleteJobRole', function() {
			var $this = $(this);
			var branch = $this.attr("data-branch");
			var job = $this.attr("data-job");

			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: {
					'delete-job-role': '',
					'job': job,
					'branch': branch
				}
			}).done(function(data) {
				var json = null;
				try {
					json = JSON.parse(data);
				} catch (e) {
					messagesys.failure("Unable to fetch output, refresh the page");
					return;
				}
				if (json.result) {
					messagesys.success(json.message);
					$this.closest("tr").remove();
					$("#jQempListTable").find("[data-branch=" + branch + "]").closest("tr").remove();
				} else {
					messagesys.failure(json.message);
				}
			});
		});

	});
</script>