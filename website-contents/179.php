<?php
use System\Finance\AccountStatement;
use System\SmartListObject;
use System\Template\Gremium;
use System\Template\Gremium\Status;


if (!$app->user->account->role->view) {
	$grem = new Gremium\Gremium(true);
	$grem->header()->status(Status::Exclamation)->serve("<h1>Access restricted!</h1>");
	$grem->legend()->serve("<span class=\"flex\">Loading journal for `{$app->user->account->name}` account failed:</span>");
	$article = $grem->article();
	$article->message .= '<ul style="margin:0">
		<li>Session has expired, loing in again to your account</li>
		<li>Database query failed, contact system administrator</li>
		<li>Viewing account detials is restricted by management, try selecting another account</li>
		<li>Permission denied or not enough privileges to proceed with this document</li>
		<ul>';
	$article->serve();
	unset($grem);
	exit;
}



if ($app->xhttp && isset($_POST['method']) && $_POST['method'] == "statement_report") {
	$controller = new System\Finance\StatementOfAccount\StatementOfAccount($app);

	/* Date input processing */
	$date_start = isset($_POST['from']) ? $app->date_validate($_POST['from']) : false;
	$date_end = isset($_POST['to']) ? $app->date_validate($_POST['to'], true) : false;
	$user_current = abs((int) $_POST['page']);
	if (($date_start && $date_end) && $date_start > $date_end) {
		header("VENDOR_RESULT: DATE_CONFLICT");
		exit;
	}

	if ($date_start)
		$controller->criteria->dateStart(date("Y-m-d", $date_start));
	if ($date_end)
		$controller->criteria->dateEnd(date("Y-m-d", $date_end));


	$count = $sum = $pages = 0;
	$controller->summary($count, $sum);
	$sum = is_null($sum) ? 0 : $sum;
	$count = is_null($count) ? 0 : $count;
	$pages = ceil($count / $controller->criteria->getRecordsPerPage());

	/* Pageination */

	if (isset($_POST['page']) && $user_current > 0) {
		if ($user_current > $pages) {
			$controller->criteria->setCurrentPage($pages);
		} else {
			$controller->criteria->setCurrentPage(($user_current));
		}
	} elseif (isset($_POST['page']) && $user_current == 0) {
		$controller->criteria->setCurrentPage($pages);
	}


	header("VENDOR_FN_COUNT: " . $count);
	header("VENDOR_FN_PAGES: " . $pages);
	header("VENDOR_FN_SUM: " . ($sum < 0 ? "(" : "") . number_format(abs($sum), 2) . ($sum < 0 ? ")" : ""));
	header("VENDOR_FN_CURRENT: " . $controller->criteria->getCurrentPage());

	echo "<table class=\"bom-table statment-view hover strip\">";
	echo "<thead class=\"sticky\" style=\"top: calc(171px - var(--gremium-header-toggle)) ;\">";
	echo "<tr>";
	echo "<td>Date</td>";
	echo "<td>ID</td>";
	echo "<td colspan=\"2\">Description</td>";
	echo "<td class=\"value-number\">Debit</td>";
	echo "<td class=\"value-number\">Credit</td>";
	echo "<td class=\"value-number\" style=\"padding-right:17px;min-width:120px\">Balance</td>";
	echo "</tr>";
	echo "</thead>";
	echo "<tbody>";
	if ($count > 0) {
		$mysqli_result = $controller->chunk();
		if ($mysqli_result->num_rows > 0) {
			while ($row = $mysqli_result->fetch_assoc()) {
				echo "<tr>";
				echo "<td>{$row['acm_ctime']}</td>"; //date("Y-m-d",$row['acm_ctime'])
				echo "<td>{$row['acm_id']}</td>";
				echo "<td>" . ($row['comp_id'] != $app->user->company->id ? "<span class=\"value-hightlight\">[" . $row['comp_name'] . "]</span> " : "") . "{$row['acm_beneficial']}</td>";
				echo "<td class=\"value-comment\">" . (is_null($row['acm_comments']) ? "" : nl2br($row['acm_comments'])) . "</td>";
				echo "<td class=\"value-number\">" . ($row['atm_value'] > 0 ? number_format($row['atm_value'], 2) : "") . "</td>";
				echo "<td class=\"value-number\">" . ($row['atm_value'] <= 0 ? number_format(abs($row['atm_value']), 2) : "") . "</td>";

				echo "<td class=\"value-number final " . ($row['cumulative_sum'] < 0 ? "negative" : "positive") . "\">" . number_format(abs($row['cumulative_sum']), 2) . "</td>";
				echo "</tr>";
			}
		}
	} else {
		echo "<tr>";
		echo "<td colspan=\"3\">No records found</td><td class=\"value-comment\"></td><td colspan=\"3\"></td>";
		echo "</tr>";
	}
	echo "</tbody>";
	if ($controller->criteria->getCurrentPage() == $pages) {
		echo '<tfoot>';
		echo "<tr>";
		echo "<td colspan=\"4\">End of statement</td>";
		echo "<td colspan=\"2\" class=\"value-number\">Final Balance</td>";
		echo "<td class=\"value-number final " . ($sum < 0 ? "negative" : "positive") . "\">" . number_format(abs($sum), 2) . "</td>";
		echo "</tr>";
		echo '</tfoot>';
	}
	echo "</table>";
	exit;
}

if ($app->xhttp) {
	exit;
}
$initial_values = array(
	'from' => isset($_GET['from']) && $app->date_validate($_GET['from']) ? date("Y-m-d", $app->date_validate($_GET['from'])) : "",
	'to' => isset($_GET['to']) && $app->date_validate($_GET['to']) ? date("Y-m-d", $app->date_validate($_GET['to'])) : date("Y-m-d"),
	"page" => isset($_GET['page']) ? abs((int) $_GET['page']) : 0
);



$SmartListObject = new SmartListObject($app);
$grem = new Gremium\Gremium(false);

$grem->header()->serve("<h1>{$fs()->title}</h1><ul><li>{$app->user->company->name}: {$app->user->account->name}</li></ul><cite><span id=\"js-output-total\">0.00</span>{$app->user->account->currency->shortname}</cite>");

$menu = $grem->menu()->sticky(false)->open();
echo "<span>Date</span>";
echo "<input type=\"text\" id=\"js-input_date-start\" style=\"width:120px\" data-slo=\":DATE\" placeholder=\"From\" value=\"{$initial_values['from']}\" value=\"\"  />";
echo "<input type=\"text\" id=\"js-input_date-end\" style=\"width:120px\" data-slo=\":DATE\" placeholder=\"To\" value=\"{$initial_values['to']}\" value=\"" . date("Y-m-d") . "\"  />";
echo "<button id=\"js-input_cmd-update\">Search</button>";
echo "<button id=\"js-input_cmd-export\">Export</button>";
echo "<span class=\"gap\"></span>";
$menu->close();

$legend = $grem->legend()->open();
echo "<span id=\"js-output_stetements-count\">0</span>";
echo "<span class=\"flex\">Account statements</span>";
echo "<button class=\"pagination prev\" id=\"js-input_page-prev\" disabled></button>";
echo "<input type=\"text\" id=\"js-input_page-current\" data-slo=\":NUMBER\" style=\"width:80px;text-align:center\" data-rangestart=\"1\" value=\"0\" data-rangeend=\"100\" />";
echo "<button class=\"pagination next\" id=\"js-input_page-next\" disabled></button>";
echo "<button type=\"text\" id=\"js-output_page-total\" style=\"min-width:50px;text-align:center\">0</button>";
$legend->close();

$article = $grem->article()->open();
echo "<div id=\"js-container-output\" style=\"padding-bottom:50px\"></div>";
$article->close();

unset($grem);
?>
<style>
	td.value-comment {
		width: 100%
	}

	td.value-number {
		text-align: right;
		min-width: 100px;
		width: 150px;
	}

	td.value-number.final {
		font-weight: bold;
	}

	td.value-number.final.negative {}

	td.value-number.final::after,
	td.value-number.final::before {
		display: inline-block;
		width: 8px;
		font-weight: normal;
	}

	td.value-number.final.positive::after {
		content: " ";
	}

	td.value-number.final.negative::after {
		content: ")";
		text-align: right;
	}

	td.value-number.final.negative::before {
		content: "(";
		text-align: left;
	}

	span.value-hightlight {
		color: crimson;
	}
</style>
<form method="post" id="js-form_export" style="display:none;">
	<input type="hidden" name="method" value="statement_export" />
	<input type="hidden" name="page" value="" />
	<input type="hidden" name="from" value="" />
	<input type="hidden" name="to" value="" />
</form>
<script type="text/javascript" src="static\javascript\Navigator.js"></script>
<script type="text/javascript">
	$(document).ready(function (e) {
		nav = new Navigator({
			"page": <?php echo $initial_values['page']; ?>,
			"from": "",
			"to": "",
		}, "<?= $fs()->dir ?>");

		let slo_date_start = $("#js-input_date-start").slo({
			onselect: function (e) { nav.setProperty("from", e.hidden); },
			ondeselect: function (e) { nav.setProperty("from", ""); }
		});
		let slo_date_end = $("#js-input_date-end").slo({
			onselect: function (e) { nav.setProperty("to", e.hidden); },
			ondeselect: function (e) { nav.setProperty("to", ""); }
		});
		let slo_page_current = $("#js-input_page-current").slo({
			onselect: function (e) {
				nav.setProperty("page", e.hidden);
				nav.pushState();
				xhttp_request(nav);
			}
		});
		let js_container_output = $("#js-container-output");
		let js_input_cmd_update = $("#js-input_cmd-update");
		let js_input_cmd_next = $("#js-input_page-next");
		let js_input_cmd_prev = $("#js-input_page-prev");
		let js_output_total = $("#js-output-total");
		let js_output_page_total = $("#js-output_page-total");
		let js_output_stetements_count = $("#js-output_stetements-count");
		let js_input_cmd_export = $("#js-input_cmd-export");
		let js_form_export = $("#js-form_export");
		let total_pages = 1;

		nav.setProperty("from", slo_date_start.get()[0].id);
		nav.setProperty("to", slo_date_end.get()[0].id);

		xhttp_request = function (nav) {
			js_input_cmd_prev.attr("disabled", nav.getProperty("page") == 1);
			js_input_cmd_next.attr("disabled", parseInt(nav.getProperty("page")) >= total_pages);
			overlay.show();
			$.ajax({
				data: { ...nav.history_state, ...{ "method": "statement_report" } },
				url: '<?php echo $fs()->dir; ?>',
				type: 'POST'
			}).done(function (output, textStatus, request) {
				let response = request.getResponseHeader('VENDOR_RESULT');
				let fn_sum = request.getResponseHeader('VENDOR_FN_SUM');
				let fn_count = parseInt(request.getResponseHeader('VENDOR_FN_COUNT'));
				let fn_current = parseInt(parseInt(request.getResponseHeader('VENDOR_FN_CURRENT')));
				total_pages = parseInt(request.getResponseHeader('VENDOR_FN_PAGES'));
				if (response == 'DATE_CONFLICT') {
					messagesys.failure("Date range is not valid");
					js_input_cmd_next.attr("disabled", true);
					js_input_cmd_prev.attr("disabled", true);
					slo_page_current.disable()
					return;
				}

				nav.setProperty("page", fn_current);
				slo_page_current.set(fn_current, fn_current);

				slo_page_current.input[0][0].dataset.rangeend = total_pages;
				slo_page_current.reinit();

				if (total_pages == 1) {
					js_input_cmd_next.attr("disabled", true);
					js_input_cmd_prev.attr("disabled", true);
					slo_page_current.disable()
				} else if (total_pages > 1) {
					slo_page_current.enable()
					if (nav.getProperty("page") == 0) {
						js_input_cmd_next.attr("disabled", true);
					} else if (nav.getProperty("page") >= total_pages) {
						js_input_cmd_next.attr("disabled", true);
					} else {
						js_input_cmd_next.attr("disabled", false);
					}
				}

				js_output_stetements_count.html(fn_count);
				js_output_total.html(fn_sum);
				js_output_page_total.html(total_pages);
				js_container_output.html(output);

				const y = js_output_total[0].getBoundingClientRect().top + window.scrollY;
				window.scroll({
					top: 0,
					behavior: 'smooth'
				});
			}).always(function () {
				overlay.hide();
			});
		};

		js_input_cmd_export.on('click', function (e) {
			overlay.show();
			js_form_export.find("[name=page]").val(nav.getProperty('page'));
			js_form_export.find("[name=from]").val(nav.getProperty('from'));
			js_form_export.find("[name=to]").val(nav.getProperty('to'));
			js_form_export.attr("method", "post");
			js_form_export.attr("action", "<?= $fs(13)->dir ?>/?");
			js_form_export.submit();

			setTimeout(() => {
				overlay.hide();
			}, 1000);

		});


		nav.onPopState(function () {
			slo_page_current.set(nav.getProperty("page"), nav.getProperty("page"));
			slo_date_start.set(nav.getProperty("from"), nav.getProperty("from"));
			slo_date_end.set(nav.getProperty("to"), nav.getProperty("to"));
			xhttp_request(nav);
		});

		js_output_page_total.on("click", function () {
			nav.setProperty("page", 0);
			nav.pushState();
			xhttp_request(nav)
		});
		/* Events binding */
		js_input_cmd_next.on("click", function () {
			if (parseInt(nav.getProperty("page")) >= total_pages) { return; };
			nav.setProperty("page", parseInt(nav.getProperty("page")) + 1);
			nav.pushState();
			js_input_cmd_prev.attr("disabled", false);
			slo_page_current.set(nav.getProperty("page"), nav.getProperty("page"));
			xhttp_request(nav);
		});
		js_input_cmd_prev.on("click", function () {
			if (parseInt(nav.getProperty("page")) <= 1) { return; };
			nav.setProperty("page", parseInt(nav.getProperty("page")) - 1);
			nav.pushState();
			slo_page_current.set(nav.getProperty("page"), nav.getProperty("page"))
			xhttp_request(nav);
		});

		js_input_cmd_update.on('click', function () {
			nav.setProperty("page", 0);
			js_input_cmd_prev.attr("disabled", true);
			nav.pushState();
			slo_page_current.input[0][0].dataset.rangeend = 1;
			slo_page_current.reinit();

			xhttp_request(nav);
		});
		xhttp_request(nav);
	});
</script>