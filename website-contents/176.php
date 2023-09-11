<?php
if (isset($_POST['method'], $_POST['sal_val'], $_POST['job_id']) && $_POST['method'] == "commit-upateds") {
	$_POST['job_id'] = (int)$_POST['job_id'];
	//echo "<pre>".print_r($_POST,1);
	$smart = "";
	$q_insert_values = "INSERT INTO `labour_type_salary` 
								(`lbr_typ_sal_id`,`lbr_typ_sal_lty_id`,`lbr_typ_sal_lwt_id`,`lbr_typ_sal_method`,`lbr_typ_sal_basic_salary`,`lbr_typ_sal_variable`,`lbr_typ_sal_allowance`,`lbr_typ_sal_transportation`) VALUES ";
	foreach ($_POST['sal_val'] as $pay_method_id => $workingtimes) {
		foreach ($workingtimes as $work_times_id => $values) {
			$arr_values = array();
			$arr_values['salary'] = isset($values['salary']) ? (float)$values['salary'] : 0;
			$arr_values['variable'] = isset($values['variable']) ? (float)$values['variable'] : 0;
			$arr_values['allowance'] = isset($values['allowance']) ? (float)$values['allowance'] : 0;
			$arr_values['transportation'] = isset($values['transportation']) ? (float)$values['transportation'] : 0;

			$q_insert_values .= $smart . sprintf(
				"
								(NULL,%1\$d,%2\$d,%3\$d ,%4\$f,%5\$f,%6\$f,%7\$f) 
								",
				$_POST['job_id'],
				$work_times_id,
				$pay_method_id,
				$arr_values['salary'],
				$arr_values['variable'],
				$arr_values['allowance'],
				$arr_values['transportation']
			);

			$smart = ",";
		}
	}
	$q_insert_values .= "ON DUPLICATE KEY UPDATE 
		`lbr_typ_sal_id`=LAST_INSERT_ID(lbr_typ_sal_id),
		`lbr_typ_sal_lty_id`=VALUES(`lbr_typ_sal_lty_id`),
		`lbr_typ_sal_lwt_id`=VALUES(`lbr_typ_sal_lwt_id`),
		`lbr_typ_sal_method`=VALUES(`lbr_typ_sal_method`),
		`lbr_typ_sal_basic_salary`=VALUES(`lbr_typ_sal_basic_salary`),
		`lbr_typ_sal_variable`=VALUES(`lbr_typ_sal_variable`),
		`lbr_typ_sal_allowance`=VALUES(`lbr_typ_sal_allowance`),
		`lbr_typ_sal_transportation`=VALUES(`lbr_typ_sal_transportation`);";
	$r = $app->db->query($q_insert_values);
	if ($app->db->affected_rows > 0) {
		echo "true";
	} else {
		echo "false";
	}
	
	

	exit;
}



if (isset($_POST['method'], $_POST['job-id']) && $_POST['method'] == "update-job-salary") {
	$job_id = (int)$_POST['job-id'];
	$job_info = false;
	$q = $app->db->query("SELECT lty_id,lty_name,lsc_name,lty_section FROM labour_type LEFT JOIN labour_section ON lsc_id=lty_section WHERE lty_id=$job_id ORDER BY lty_id;");
	if ($q) {
		if ($row = $q->fetch_assoc()) {
			$job_info = array();
			$job_info['id'] = $row['lty_id'];
			$job_info['name'] = $row['lty_name'];
			$job_info['section'] = $row['lsc_name'];
		}
	}
	if (!$job_info) {
		echo "false_job";
		exit;
	}
	echo "<form action=\"{$fs()->dir}/\" method=\"post\" id=\"jQform\">
				<input type=\"hidden\" name=\"method\" value=\"commit-upateds\" />
				<input type=\"hidden\" name=\"job_id\" value=\"{$job_info['id']}\" />";
	echo "<table class=\"bom-table hover\" style=\"margin-bottom:15px;\">
			<thead><tr class=\"special\"><td colspan=\"6\"><span><span class=\"vs-add\"><span></span></span>Update Job Salary [{$job_info['section']}/{$job_info['name']}]</span></td></tr></thead>
			
			<tbody><tr><th>Method</th><th>Working Time</th><th>Salary</th><th>Variable</th><th>Allowance (Day)</th><th>Transportation (Day)</th></tr>";

	$_workingtimes = array();
	$q = $app->db->query("SELECT lwt_id,lwt_name FROM workingtimes ORDER BY lwt_id;");
	if ($q) {
		while ($row = $q->fetch_assoc()) {
			$_workingtimes[$row['lwt_id']] = $row['lwt_name'];
		}
	}

	function print_inputrow($name, $value)
	{
		echo "<td><div class=\"btn-set normal\"><input name=\"$name\" value=\"$value\" 
					type=\"text\" style=\"-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;max-width:400px;\" class=\"text\" value=\"\" /></div></td>";
	}

	$q = $app->db->query("SELECT lbr_mth_id,lbr_mth_name FROM labour_method;");
	if ($q) {
		while ($row = $q->fetch_assoc()) {
			echo "<tr>";
			echo "<th rowspan=\"" . sizeof($_workingtimes) . "\">{$row['lbr_mth_name']}</th>";
			$smart = true;


			foreach ($_workingtimes as $_wt_id => $_wt_val) {

				$arr_values = array(
					"salary" => 0,
					"variable" => 0,
					"allowance" => 0,
					"transportation" => 0,
				);
				$r_der_vals = $app->db->query("SELECT 
								lbr_typ_sal_basic_salary,lbr_typ_sal_variable,lbr_typ_sal_allowance,lbr_typ_sal_transportation 
							FROM
								labour_type_salary 
							WHERE 
								lbr_typ_sal_lty_id={$job_info['id']} AND lbr_typ_sal_lwt_id=$_wt_id AND lbr_typ_sal_method={$row['lbr_mth_id']};");
				if ($r_der_vals) {
					if ($row_der_vals = $r_der_vals->fetch_assoc()) {
						$arr_values['salary'] = number_format($row_der_vals['lbr_typ_sal_basic_salary'], 2, ".", "");
						$arr_values['variable'] = number_format($row_der_vals['lbr_typ_sal_variable'], 2, ".", "");
						$arr_values['allowance'] = number_format($row_der_vals['lbr_typ_sal_allowance'], 2, ".", "");
						$arr_values['transportation'] = number_format($row_der_vals['lbr_typ_sal_transportation'], 2, ".", "");
					}
				}


				if ($smart) {
					echo "<td>$_wt_val</td>";
					print_inputrow("sal_val[{$row['lbr_mth_id']}][$_wt_id][salary]", $arr_values['salary']);
					print_inputrow("sal_val[{$row['lbr_mth_id']}][$_wt_id][variable]", $arr_values['variable']);
					print_inputrow("sal_val[{$row['lbr_mth_id']}][$_wt_id][allowance]", $arr_values['allowance']);
					print_inputrow("sal_val[{$row['lbr_mth_id']}][$_wt_id][transportation]", $arr_values['transportation']);
					echo "</tr>";
					$smart = false;
				} else {
					echo "<tr><td>$_wt_val</td>";
					print_inputrow("sal_val[{$row['lbr_mth_id']}][$_wt_id][salary]", $arr_values['salary']);
					print_inputrow("sal_val[{$row['lbr_mth_id']}][$_wt_id][variable]", $arr_values['variable']);
					print_inputrow("sal_val[{$row['lbr_mth_id']}][$_wt_id][allowance]", $arr_values['allowance']);
					print_inputrow("sal_val[{$row['lbr_mth_id']}][$_wt_id][transportation]", $arr_values['transportation']);
					echo "</tr>";
				}
			}
			echo "</tr>";
			//echo "<tr><td>".sizeof($_workingtimes)."</td></tr>";

		}
	}



	echo "
		<tr><td align=\"left\" colspan=\"6\" id=\"jQcommands\">
			<div class=\"btn-set\" style=\"justify-content:center\"><button type=\"submit\">Submit</button><button type=\"button\" id=\"jQcancel\">Cancel</button></div>
		</td></tr>
	</tbody>
	</table></form>";
	exit;
}

echo "<table class=\"bom-table hover\"><thead>";
echo "<tr><td></td><td>ID</td><td>Job Name</td><td width=\"100%\">Job Section</td></tr>";
echo "</thead><tbody>";
$q = $app->db->query("SELECT lty_id,lty_name,lsc_name,lty_section FROM labour_type LEFT JOIN labour_section ON lsc_id=lty_section;");
if ($q) {
	while ($row = $q->fetch_assoc()) {
		echo "<tr data-lty_id=\"{$row['lty_id']}\">";
		echo "<td class=\"op-edit noselect\"><span></span></td>";
		echo "<td>" . $row['lty_id'] . "</td>";
		echo "<td>" . $row['lty_name'] . "</td>";
		echo "<td>" . $row['lsc_name'] . "</td>";

		echo "</tr>";
	}
}



?>
<script type="text/javascript">
	$(document).ready(function(e) {

		$(".op-edit").on('click', function() {
			$this = $(this);
			$id = $this.parent().attr("data-lty_id");
			overlay.show();
			var $ajax = $.ajax({
				type: 'POST',
				url: '<?php echo $fs()->dir; ?>',
				data: {
					'method': 'update-job-salary',
					'job-id': $id
				}
			}).done(function(data) {
				overlay.hide();
				popup.show(data);

				popup.self().find("#jQcancel").on('click', function() {
					popup.hide();
				});
				popup.self().find("#jQcancel").focus();


				popup.self().find("#jQform").on('submit', function(e) {
					e.preventDefault();
					var _ser = $(this).serialize();

					var $ajax = $.ajax({
						type: 'POST',
						url: '<?php echo $fs()->dir; ?>',
						data: _ser,
					}).done(function(data) {
						popup.hide();

						if (data == "true") {
							messagesys.success("Salary updated successfully");
						} else {
							messagesys.failure("Updating salary information failed, SQL Failure");
						}

					}).fail(function(a, b, c) {
						messagesys.failure("Executing request failed, " + b);
					});

					return false;
				});
			}).fail(function(a, b, c) {
				overlay.hide();
				messagesys.failure("Unable to execute your request, " + b);
			});

		})
	});
</script>