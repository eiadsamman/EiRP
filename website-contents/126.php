<?php


exit;
if ($fs()->permission->delete && isset($_POST['delete-absenece-request'])) {
	$_POST['id'] = (int)$_POST['id'];
	if ($app->db->query("DELETE FROM labour_absence_request WHERE lbr_abs_id={$_POST['id']}")) {
		echo "done";
	} else {
		echo $app->db->error;
	}

	exit;
}

if (isset($_POST['list-absenece-report']) && isset($_POST['lbr'])) {

	$abs_types = array();
	$r = $app->db->query("SELECT abs_typ_id,abs_typ_name FROM absence_types");
	if ($r) {
		while ($row = $r->fetch_assoc()) {
			$abs_types[$row['abs_typ_id']] = $row['abs_typ_name'];
		}
	}
	$_POST['lbr'] = (int)$_POST['lbr'];

	$allowed_abs = array();
	$allowed_abs_year = array();

	$r = $app->db->query(
		"SELECT 
			usr_id,usr_firstname,usr_lastname,
			abscal_allowed,abscal_type,abscal_id,abscal_over
		FROM 
			labour 
				JOIN users ON usr_id=lbr_id
				LEFT JOIN absence_calc ON 
				(
					abscal_period_from <= TIMESTAMPDIFF(MONTH, IF(socialinsurance IS NULL,usr_registerdate,socialinsurance), str_to_date('" . date("Y-m-d", mktime(0, 0, 0, date("m"), 1, date("Y"))) . "','%Y-%m-%d')) AND
					abscal_period_to > TIMESTAMPDIFF(MONTH, IF(socialinsurance IS NULL,usr_registerdate,socialinsurance), str_to_date('" . date("Y-m-d", mktime(0, 0, 0, date("m"), 1, date("Y"))) . "','%Y-%m-%d')) 
				)
			
		WHERE
			lbr_id={$_POST['lbr']}
		GROUP BY
			abscal_id

		"
	);
	if ($r)
		while ($row = $r->fetch_assoc()) {
			if (!is_null($row['abscal_id'])) {
				$allowed_abs[$row['abscal_type']] = array($abs_types[$row['abscal_type']], (int)$row['abscal_allowed'], $row['abscal_over'], "year" => array());
			}
		}



	$r = $app->db->query(
		"SELECT
			COUNT(abs_dates) AS abs_count,YEAR(abs_dates) AS grp_year,lbr_abs_type
		FROM
			labour_absence_request main
			JOIN(
				SELECT
					adddate(lbr_abs_start_date,seq) AS abs_dates,
					lbr_abs_id
				FROM
					seq_1_to_365,
					labour_absence_request
				WHERE
					seq <= lbr_abs_days AND lbr_abs_lbr_id={$_POST['lbr']}
				) a ON a.lbr_abs_id=main.lbr_abs_id
			
		WHERE
			abs_dates >= lbr_abs_start_date AND abs_dates < DATE_ADD(lbr_abs_start_date, INTERVAL lbr_abs_days DAY)
			AND lbr_abs_lbr_id={$_POST['lbr']}
		GROUP BY
			lbr_abs_type,grp_year
		ORDER BY
			grp_year DESC,lbr_abs_type
		"
	);

	if ($r) {
		while ($row = $r->fetch_assoc()) {
			if (!isset($allowed_abs_year[$row['grp_year']])) {
				$allowed_abs_year[$row['grp_year']] = array();
			}
			if (!isset($allowed_abs_year[$row['grp_year']][$row['lbr_abs_type']])) {
				$allowed_abs_year[$row['grp_year']][$row['lbr_abs_type']] = 0;
			}
			$allowed_abs_year[$row['grp_year']][$row['lbr_abs_type']] += $row['abs_count'];
		}
	}
	if (!isset($allowed_abs_year[date("Y")])) {
		$allowed_abs_year[date("Y")] = array();
	}
	krsort($allowed_abs_year);


	echo "<table class=\"hover\" style=\"margin-bottom:10px\"><thead>";

	//Print absence type header
	echo "<tr><td></td>";
	foreach ($allowed_abs as $k => $v) {
		echo "<td>{$v[0]}</td>";
	}
	echo "<td width=\"100%\"></td></tr>";

	//Print absence type details
	echo "<tr><td></td>";
	foreach ($allowed_abs as $tk => $tv) {
		echo "<td>{$tv[1]}days / " . ($tv[2]) . "month</td>";
	}
	echo "<td></td></tr>";

	echo "</thead><tbody>";

	//Print absence counts per year/type
	foreach ($allowed_abs_year as $yeark => $yearv) {
		echo "<tr><th>$yeark</th>";
		foreach ($allowed_abs as $tk => $tv) {
			if (isset($yearv[$tk])) {
				echo "<td>{$yearv[$tk]}</td>";
			} else {
				echo "<td>-</td>";
			}
		}
		echo "<td></td></tr>";
	}
	echo "</tbody></table>";



	$r = $app->db->query(
		"SELECT 
			(SELECT CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS empname FROM users WHERE usr_id=lbr_abs_usr_id) AS signername,
			lbr_abs_id,lbr_abs_start_date,lbr_abs_days,lbr_abs_type,abs_typ_name 
		FROM 
			labour_absence_request LEFT JOIN absence_types ON lbr_abs_type=abs_typ_id WHERE lbr_abs_lbr_id={$_POST['lbr']}
		ORDER BY
			lbr_abs_id DESC"
	);
	if ($r) {
		echo "<table class=\"hover\"><thead><tr><td></td><td>ID</td><td>Start date</td><td>Period</td><td>Type</td><td width=\"100%\">Editor</td>
		" . ($fs()->permission->delete ? "<td></td>" : "") . "
		</tr></thead><tbody>";
		while ($row = $r->fetch_assoc()) {
			echo "<tr><td class=\"op-print\" data-id=\"{$row['lbr_abs_id']}\"><span></span></td><td>{$row['lbr_abs_id']}</td><td>{$row['lbr_abs_start_date']}</td><td>{$row['lbr_abs_days']}</td>
			<td>{$row['abs_typ_name']}</td><td>{$row['signername']}</td>" . ($fs()->permission->delete ? "<td class=\"op-remove\" data-id=\"{$row['lbr_abs_id']}\"><span></span></td>" : "") . "</tr>";
		}
		echo "</tbody></table>";
	}


	exit;
}



if (isset($_POST['submit-new-absence-request'])) {
	$json = new JSON();
	$_POST['lbr'] = (int)$_POST['lbr'];
	$_POST['period'] = (int)$_POST['period'];
	$_POST['comments'] = addslashes($_POST['comments']);
	$_POST['type'] = (int)$_POST['type'];
	$checkdate = false;
	$rawdate = false;
	if (preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_POST['date'], $match)) {
		if (checkdate($match[2], $match[3], $match[1])) {
			$rawdate = mktime(0, 0, 0, $match[2], $match[3], $match[1]);
			$checkdate = date("Y-m-d", $rawdate);
		}
	}
	if (!$checkdate) {
		$json->output(false, "Select a valid starting date {$_POST['date']}");
	}
	if ($_POST['period'] == 0 || $_POST['period'] < 1) {
		$json->output(false, "Select a valid period");
	}

	if ($_POST['type'] == 0) {
		$json->output(false, "Select the absence type");
	}


	$allowed_abs = false;
	$totalrequest = 0;

	$r = $app->db->query(
		"SELECT 
			usr_id,usr_firstname,usr_lastname,
			abscal_allowed,abscal_type,abscal_id,abscal_over
		FROM 
			labour 
				JOIN users ON usr_id=lbr_id
				LEFT JOIN absence_calc ON 
				(
					abscal_period_from <= TIMESTAMPDIFF(MONTH, IF(socialinsurance IS NULL,usr_registerdate,socialinsurance), str_to_date('" . date("Y-m-d", mktime(0, 0, 0, date("m"), 1, date("Y"))) . "','%Y-%m-%d')) AND
					abscal_period_to > TIMESTAMPDIFF(MONTH, IF(socialinsurance IS NULL,usr_registerdate,socialinsurance), str_to_date('" . date("Y-m-d", mktime(0, 0, 0, date("m"), 1, date("Y"))) . "','%Y-%m-%d')) 
				)
			
		WHERE
			lbr_id={$_POST['lbr']} AND abscal_type={$_POST['type']}
		GROUP BY
			abscal_id
		"
	);
	if ($r) {
		while ($row = $r->fetch_assoc( )) {
			if (!is_null($row['abscal_id'])) {
				$allowed_abs = array((int)$row['abscal_allowed'], $row['abscal_over']);
			}
		}
	}
	if (!$allowed_abs) {
		$json->output(false, "No absence requests allowed for selected type and employee");
	}




	$r = $app->db->query(
		"SELECT
			COUNT(abs_dates) AS abs_count,lbr_abs_type,YEAR(abs_dates) AS grp_year
		FROM
			labour_absence_request main
			JOIN(
				SELECT
					adddate(lbr_abs_start_date,seq) AS abs_dates,
					lbr_abs_id
				FROM
					seq_1_to_100,
					labour_absence_request
				WHERE
					seq <= lbr_abs_days AND lbr_abs_lbr_id={$_POST['lbr']}
				) a ON a.lbr_abs_id=main.lbr_abs_id
			
		WHERE
			abs_dates >= lbr_abs_start_date AND abs_dates < DATE_ADD(lbr_abs_start_date, INTERVAL lbr_abs_days DAY)
			AND lbr_abs_lbr_id={$_POST['lbr']} AND lbr_abs_type={$_POST['type']} 
			AND YEAR(abs_dates) = YEAR('$checkdate')
		GROUP BY
			grp_year
		"
	);

	if ($r) {
		if ($row = $r->fetch_assoc()) {
			$totalrequest = ($row['abs_count']);
		}
	}
	$period = $_POST['period'];

	$future_date = mktime(0, 0, 0, date("m", $rawdate), date("d", $rawdate) + $period, date("Y", $rawdate));
	$arr_periods = array();

	for ($cnt = 0; $cnt < $period; $cnt++) {
		$testing_date = mktime(0, 0, 0, date("m", $rawdate), date("d", $rawdate) + $cnt, date("Y", $rawdate));
		if (!isset($arr_periods[date("Y", $testing_date)])) {
			$arr_periods[date("Y", $testing_date)] = 0;
		}
		$arr_periods[date("Y", $testing_date)]++;
	}

	if (isset($arr_periods[date("Y", $rawdate)])) {
		$arr_periods[date("Y", $rawdate)] += $totalrequest;
	}

	$days_in_range = true;
	foreach ($arr_periods as $k => $v) {
		if ($v > $allowed_abs[0]) {
			$days_in_range = false;
		}
	}

	if (!$days_in_range)
		$json->output(false, "Allowed days for selected request type and employee exceeded the limits");

	$_POST['period'] = $_POST['period'] - 1;
	$r = $app->db->query("SELECT lbr_abs_id 
		FROM labour_absence_request 
		WHERE 
			((	
				DATE_ADD(STR_TO_DATE('$checkdate','%Y-%m-%d'), INTERVAL {$_POST['period']} DAY) >= lbr_abs_start_date
				AND
				DATE_ADD(STR_TO_DATE('$checkdate','%Y-%m-%d'), INTERVAL {$_POST['period']} DAY) <= DATE_ADD(lbr_abs_start_date, INTERVAL (lbr_abs_days -1) DAY)
			)
			OR
			(	
				'$checkdate' >= lbr_abs_start_date
				AND
				'$checkdate' <= DATE_ADD(lbr_abs_start_date, INTERVAL (lbr_abs_days-1) DAY)
			)
			OR
			(	
				'$checkdate' <= lbr_abs_start_date
				AND
				DATE_ADD(STR_TO_DATE('$checkdate','%Y-%m-%d'), INTERVAL {$_POST['period']} DAY) >= DATE_ADD(lbr_abs_start_date, INTERVAL (lbr_abs_days-1) DAY)
			))
			AND lbr_abs_lbr_id={$_POST['lbr']};");
	if ($r) {
		if ($row = $r->fetch_assoc()) {
			$json->output(false, "Employee has already requested an absence on the given date `{$row['lbr_abs_id']}`");
		}
	}

	$stampDate = new \DateTime("now");

	if ($r = $app->db->query("INSERT INTO labour_absence_request (lbr_abs_lbr_id,lbr_abs_usr_id,lbr_abs_start_date,lbr_abs_days,lbr_abs_comments,lbr_abs_type,lbr_abs_issue_date) VALUES (
		{$_POST['lbr']},{$app->user->info->id},'{$_POST['date']}'," . ($_POST['period'] + 1) . ",'{$_POST['comments']}',{$_POST['type']}, '".  $stampDate->format("Y-m-d H:i:s") ."');")) {
		$json->output(true, "Request added successfully", null, array("request_id" => $app->db->insert_id));
	} else {
		$json->output(false, "Failed to submit absence request, try again");
	}
}
?>

<style>
	.css_newform {
		display: -webkit-box;
		display: -moz-box;
		display: -ms-flexbox;
		display: -webkit-flex;
		display: flex;
	}

	.css_newform>div {
		-webkit-box-flex: 1;
		-moz-box-flex: 1;
		-webkit-flex: 1;
		-ms-flex: 1;
		flex: 1;
	}

	@media screen and (max-width:1000px) {
		.css_newform {
			display: block;
		}
	}
</style>
<div class="css_newform">
	<div>
		<table>
			<thead>
				<tr>
					<td colspan="2">New absence request</td>
				</tr>
			</thead>
			<tbody id="jQnewForm">
				<tr>
					<th>Employee</th>
					<td width="100%">
						<div class="btn-set"><input id="jQlbr" type="text" style="width:300px;" data-slo="B00S" placeholder="Employee name, serial or id" /></div>
					</td>
				</tr>
				<tr>
					<th>Start on</th>
					<td>
						<div class="btn-set"><input id="jQdate" type="text" style="width:300px;" data-slo="DATE_MONTH_BACK" placeholder="" /></div>
					</td>
				</tr>
				<tr>
					<th>Period</th>
					<td>
						<div class="btn-set" style="max-width:300px;"><input type="text" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" id="jQperiod" /><span>Days</span></div>
					</td>
				</tr>
				<tr>
					<th>Type</th>
					<td>
						<div class="btn-set" style="max-width:300px;"><input type="text" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" data-slo="ABSENCE_TYPE" id="jQtype" /></div>
					</td>
				</tr>
				<tr>
					<th>Comments</th>
					<td>
						<div class="btn-set" style="max-width:300px;"><input type="text" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" id="jQcomments" /></div>
					</td>
				</tr>
				<tr>
					<td></td>
					<td>
						<div class="btn-set"><button id="jQsubmit">Submit</button></div>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	<span style="min-width:10px;min-height:10px;display:block"></span>
	<div>
		<div id="jQabsreport"></div>
	</div>
</div>

<iframe style="display:none;" src="" id="jQiframe"></iframe>

<script>
	$(document).ready(function(e) {
		var selected_employee = null;
		$("#jQabsreport").on('click', '.op-print', function() {
			var id = $(this).attr('data-id');
			$("#jQiframe").attr("src", "<?= $fs(130)->dir ?>/?id=" + id);
		});

		$("#jQabsreport").on('click', '.op-remove', function() {
			var $this = $(this),
				id = $this.attr('data-id');
			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: {
					'delete-absenece-request': '',
					'id': id
				}
			}).done(function(output) {
				if (output == "done") {
					$this.closest("tr").remove();
					messagesys.success("Request deleted successfully");
					update_view();
				} else {
					messagesys.failure(output);
				}
			});
		});

		var update_view = function() {
			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: {
					'list-absenece-report': '',
					'lbr': selected_employee
				}
			}).done(function(output) {
				$("#jQabsreport").html(output);
			});
		}


		$("#jQlbr").slo({
			onselect: function(data) {
				selected_employee = data.key;
				update_view();
			},
			ondeselect: function(data) {
				selected_employee = null;
				$("#jQabsreport").html("");
			}
		});
		$("#jQdate").slo();
		$("#jQtype").slo();
		$("#jQsubmit").on('click', function() {
			var lbr = $("#jQlbr_1").val(),
				date = $("#jQdate_1").val(),
				period = $("#jQperiod").val(),
				type = $("#jQtype_1").val(),
				comments = $("#jQcomments").val();
			period = parseInt(period);
			period = isNaN(period) ? 0 : period;
			lbr = parseInt(lbr);
			lbr = isNaN(lbr) ? 0 : lbr;
			type = parseInt(type);
			type = isNaN(type) ? 0 : type;

			if (lbr == 0) {
				messagesys.failure("Select employee from the list");
				$("#jQlbr").focus();
				return;
			}
			if (date == "") {
				messagesys.failure("Select absence starting date");
				$("#jQdate").focus();
				return;
			}
			if (period < 1) {
				messagesys.failure("Select a valid absence period");
				$("#jQperiod").focus();
				return;
			}
			if (type == 0) {
				messagesys.failure("Select absence type");
				$("#jQtype").focus();
				return;
			}
			$("#jQnewForm").find("input,button").prop("disabled", true);
			$.ajax({
				url: "<?php echo $fs()->dir; ?>",
				type: "POST",
				data: {
					'submit-new-absence-request': '',
					'lbr': lbr,
					'date': date,
					'period': period,
					'comments': comments,
					'type': type
				}
			}).done(function(output) {
				try {
					var json = JSON.parse(output);
				} catch (e) {
					messagesys.failure("Failed to parse output");
					return;
				}
				if (json.result) {

					$.ajax({
						url: "<?php echo $fs()->dir; ?>",
						type: "POST",
						data: {
							'list-absenece-report': '',
							'lbr': lbr
						}
					}).done(function(output) {
						$("#jQabsreport").html(output);
					});
					$("#jQiframe").attr("src", "<?= $fs(130)->dir ?>/?id=" + json.request_id);
					messagesys.success(json.message);
				} else {
					messagesys.failure(json.message);
				}

			}).fail(function(a, b, c) {
				messagesys.failure(b + " - " + c);
			}).always(function() {
				$("#jQnewForm").find("input,button").prop("disabled", false);
			});
		});
	});
</script>