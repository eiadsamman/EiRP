<?php

use System\Controller\Finance\Accounting;

if (isset($_POST['method']) && $_POST['method'] == "fetch") {
	$accounting = new Accounting($app);
	$currency_list = $accounting->get_currency_list();
	$output = array();
	$input = array(
		"from" => isset($_POST['currency_from'][1]) && isset($currency_list[(int)$_POST['currency_from'][1]]) ? (int)$_POST['currency_from'][1] : false,
		"to" => isset($_POST['currency_to'][1]) && isset($currency_list[(int)$_POST['currency_to'][1]]) ? (int)$_POST['currency_to'][1] : false,
		"type" => isset($_POST['method_type']) && !is_array($_POST['method_type']) && in_array($_POST['method_type'], array("month", "year")) ? $_POST['method_type'] : false,
		"date" => false,
		"date_query" => false
	);

	if ($input['type'] == "month") {
		if (isset($_POST['date'][1]) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_POST['date'][1], $match)) {
			if (checkdate($match[2], $match[3], $match[1])) {
				$input['date'] = mktime(0, 0, 0, $match[2], $match[3], $match[1]);
				$input['date_query'] = " AND (YEAR(curexglog_date)='{$match[1]}' AND MONTH(curexglog_date)='{$match[2]}') ";
			}
		}
	} else {
		if (isset($_POST['date'][1]) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_POST['date'][1], $match)) {
			if (checkdate($match[2], $match[3], $match[1])) {
				$input['date'] = mktime(0, 0, 0, $match[2], $match[3], $match[1]);
				$input['date_query'] = " AND (YEAR(curexglog_date)='{$match[1]}') ";
			}
		}
	}



	foreach ($input as $k => $v) {
		if (!$v) {
			$output['result'] = false;
			$output['field'] = "\"$k\"";
			echo json_encode($output);
			exit;
		}
	}



	$r = $app->db->query(
		"SELECT 
			curexglog_date,UNIX_TIMESTAMP(curexglog_date) as date, (rightmost.curexglogval_value / leftmost.curexglogval_value) AS value
		FROM 
			currency_exchange_log 
				 JOIN 
					currency_exchange_log_values AS leftmost
						ON leftmost.curexglogval_prime=curexglog_id AND leftmost.curexglogval_from={$input['from']} AND leftmost.curexglogval_to={$input['to']}
				 JOIN 
					currency_exchange_log_values AS rightmost
						ON rightmost.curexglogval_prime=curexglog_id AND rightmost.curexglogval_from={$input['to']} AND rightmost.curexglogval_to={$input['from']}
		WHERE 
			1 
			{$input['date_query']} 
		ORDER BY 
			curexglog_date"
	);
	if ($r) {
		if ($r->num_rows == 0) {

			$output['result'] = false;
			$output['field'] = "\"empty\"";
			echo json_encode($output);
		} else {
			$output['result'] = true;
			$output['field'] = "\"\"";
			$output['cur_from'] = $currency_list[$input['from']]['shortname'];
			$output['cur_to'] = $currency_list[$input['to']]['shortname'];
			$output['type'] = $input['type'];


			$output['data'] = array();
			//Get previous rates for first output

			$firstrecord = false;
			$arr_output = array();
			$holded_value = 0;
			$start_date = false;
			$end___date = false;

			while ($row = $r->fetch_assoc()) {
				if (!$firstrecord) {
					$firstrecord = $row['curexglog_date'];
				}
				$arr_output[$row['date']] = $row['value'];
			}


			$rp = $app->db->query("
				SELECT 
					curexglog_date,UNIX_TIMESTAMP(curexglog_date) as date, (rightmost.curexglogval_value / leftmost.curexglogval_value) AS value
				FROM 
					currency_exchange_log 
						 JOIN 
							currency_exchange_log_values AS leftmost
								ON leftmost.curexglogval_prime=curexglog_id AND leftmost.curexglogval_from={$input['from']} AND leftmost.curexglogval_to={$input['to']}
						 JOIN 
							currency_exchange_log_values AS rightmost
								ON rightmost.curexglogval_prime=curexglog_id AND rightmost.curexglogval_from={$input['to']} AND rightmost.curexglogval_to={$input['from']}
				WHERE 
					curexglog_date < '$firstrecord'
				LIMIT 1");

			if ($rp) {
				if ($rowp = $rp->fetch_assoc()) {
					$holded_value = $rowp['value'];
				}
			}


			if ($input['type'] == 'month') {
				$start_date = mktime(0, 0, 0, date("m", $input['date']), 1, date("Y", $input['date']));
				$end___date = mktime(23, 59, 59, date("m", $input['date']), date("t", $input['date']), date("Y", $input['date']));
			} else if ($input['type'] == 'year') {
				$start_date = mktime(0, 0, 0, 1, 1, date("Y", $input['date']));
				$end___date = mktime(23, 59, 59, 12, 31, date("Y", $input['date']));
			}

			$arr_range = array();
			for ($i = $start_date; $i <= $end___date; $i += (60 * 60 * 24)) {
				if (!isset($arr_output[$i])) {
					$output['data'][date("Y-m-d", $i)] = number_format($holded_value, 4, ".", "");
				} else {
					$holded_value = $arr_output[$i];
					$output['data'][date("Y-m-d", $i)] = number_format($arr_output[$i], 4, ".", "");
				}
			}


			echo json_encode($output);
		}
	} else {
		$output['result'] = false;
		$output['field'] = "\"SQL error\"";
		echo json_encode($output);
	}


	exit;
}

?>

<form id="jQform">
	<input type="hidden" id="method_type" name="method_type" value="month" />
	<div class="btn-set">
		<span>From</span>
		<input type="text" name="currency_from" value="" data-slo="CURRENCY_SYMBOL" style="width:150px" />
		<span>To</span>
		<input type="text" name="currency_to" value="" data-slo="CURRENCY_SYMBOL" style="width:150px" />

		<span tabindex="0" class="menu" id="jQmethod_selection" style="min-width:150px;">
			<span class="arrow"></span>History by <span data-method="month" class="method" id="jQmethod_title">Month</span>

			<div class="window">
				<span data-method="month">Month</span>
				<span data-method="year">Year</span>
			</div>
		</span>

		<input type="text" id="jQmethod" name="date" style="width:150px;" value="" data-slo="MONTH" placeholder="Select month" />
		<button type="submit">Display</button>
	</div>
</form>
<div id="jQoutput"></div>

<style>
	canvas {
		height: 280px;
		max-height: 280px;
		display: block;
		width: 100%;
	}

	.candiv {
		width: 100%;
		min-width: 370px;
		display: inline-block;
		padding: 20px;
	}

	.candiv>h1 {
		text-align: center;
		font-size: 1.2em;
		margin: 0;
		padding: 0;
		color: #333;
	}

	.legend {
		min-width: 200px;
		font-size: 1.1em;
		line-height: 1.5em;
	}

	.legend>div>span {
		display: inline-block;
		width: 12px;
		height: 12px;
		border-radius: 2px;
		margin-right: 10px;
	}

	.cantable {
		border: solid 1px #ccc;
		margin: 5px 0px;
	}
</style>



<script src="static/javascript/chart.js/Chart.js"></script>
<script>
	$(document).ready(function(e) {

		var method_slo = {
			"month": ["MONTH", "Select month", "Month"],
			"year": ["YEAR", "Select year", "Year"]
		}
		var pageination_list_timer = null;

		$("input[data-slo=CURRENCY_SYMBOL]").slo();
		var slo_method = $("#jQmethod").slo();

		$(".menu_screen,.menu_screen ~ button.menu").on('mouseenter', function() {
			if (pageination_list_timer != null) {
				clearTimeout(pageination_list_timer);
			}
			$(".menu_screen > div").css("display", "block");
		}).on('mouseleave', function() {
			pageination_list_timer = setTimeout(function() {
				$(".menu_screen > div").css("display", "none");
			}, 500);
		});

		$("#jQmethod_selection > div.window > span").on('click', function() {
			var _method = $(this).attr("data-method");
			var $this = $(this);
			if (!!method_slo[_method]) {
				$("#jQmethod").attr("data-slo", method_slo[_method]);
				slo_method.clear();
				slo_method.change(method_slo[_method][0]);
				$(slo_method.input[0]).attr("placeholder", method_slo[_method][1]);
				$("#method_type").val(_method);
				$("#jQmethod_title").html(method_slo[_method][2]);
				$this.closest("span.menu").blur()
			}

		});



		$("#jQform").on('submit', function(e) {
			var $this = $(this);
			e.preventDefault();
			$.ajax({
				url: "management/accounting/currency-exchange/exchange-history/",
				type: "POST",
				data: $this.serialize() + "&method=fetch"
			}).done(function(output) {

				var json = null;
				try {
					json = JSON.parse(output);
				} catch (e) {
					messagesys.failure("Parsing output failed");
					return false;
				}


				if (json.result) {
					var $htmldata = "";
					var label = [];
					var value = [];
					if (json.type == "month") {
						for (var index in json.data) {
							label.push(index);
							value.push(json.data[index]);
						}
					} else if (json.type == "year") {
						var step = 10;
						var jump = 0;
						for (var index in json.data) {
							if (jump % step == 0) {
								label.push(index);
								value.push(json.data[index]);
							} else {
								//label.push("");
							}


							jump += 1;
						}
					}

					$htmldata += "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" class=\"cantable\"><tbody><tr><td width=\"100%\"><div class=\"candiv\"><canvas id=\"chart_exr\"></canvas></div></td><td class=\"legend\" valign=\"middle\">";
					$htmldata += "<div><span style=\"background-color:rgba(0,60,100,1);\"></span>" + json.cur_from + "-" + json.cur_to + "</div></td></tr></tbody></table>";
					$("#jQoutput").html($htmldata);

					var ctx_exr = document.getElementById("chart_exr").getContext("2d");
					var barChart_exr = new Chart(ctx_exr).Line({
						labels: label,
						datasets: [{
							label: json.cur_from + "-" + json.cur_to,
							fillColor: "rgba(0,60,100,0.1)",
							strokeColor: "rgba(0,60,100,0.7)",
							pointColor: "rgba(0,60,100,1)",
							pointStrokeColor: "#aaa",
							pointHighlightFill: "#333",
							pointHighlightStroke: "#333",
							data: value
						}, ]

					}, {
						barStrokeWidth: 1,
						pointDot: true,
						pointDotRadius: 2,
						datasetFill: false,
						bezierCurveTension: 0.4,
						multiTooltipTemplate: " <%if (value>0){%><b><%= (value) %></b><%= datasetLabel %><%}%>",
						tooltipTemplate: "<b><%= (value) %></b><%if (label){%><%=datasetLabel%><%}%>",
						customTooltips: function(tooltip) {
							setCustometooltip(tooltip);
						}
					});

				} else {
					$("#jQoutput").html("");
					switch (json.field) {
						case "from":
							messagesys.failure("Select exchange from currency");
							break;
						case "to":
							messagesys.failure("Select exchange to currency");
							break;
						case "date":
							messagesys.failure("Select date range");
							break;
						case "empty":
							messagesys.success("No records found for selected period");
							break;
					}

				}


			}).fail(function(a, b, c) {
				messagesys.failure(c);
			});
			return false;
		});


	});
</script>