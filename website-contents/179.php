<?php
use System\Finance\AccountStatement;
use System\SmartListObject;
use System\Template\Gremium;
use System\Template\Gremium\Status;


if ($app->xhttp && isset($_POST['method']) && $_POST['method'] == "statement_report") {
	$controller = new System\Finance\StatementOfAccount\StatementOfAccount($app);

	/* Date input processing */
	$date_start   = isset($_POST['from']) ? $app->dateValidate($_POST['from']) : false;
	$date_end     = isset($_POST['to']) ? $app->dateValidate($_POST['to'], true) : false;
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
	$sum   = is_null($sum) ? 0 : $sum;
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

	echo "<table class=\"statment-view hover strip\">";
	echo "<thead class=\"table-head\" style=\"top: calc(158px - var(--gremium-header-toggle));background-color:#fff;z-index:1\">";
	echo "<tr>";
	echo "<td>ID</td>";
	echo "<td>Description</td>";
	echo "<td class=\"blank\"></td>";
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

				echo "<td>";
				echo "<div><a href=\"{$fs(104)->dir}/?id={$row['acm_id']}\" dir=\"_blank\">{$row['acm_id']}</a></div>";
				echo "<div>{$row['acm_ctime']}</div>";
				echo "<div>" . ($row['comp_id'] != $app->user->company->id ? "<span class=\"value-hightlight\">[" . $row['comp_name'] . "]</span> " : "") . "{$row['acm_beneficial']}</div>";
				echo "</td>";

				echo "<td class=\"value-comment\">
					<span>{$row['accgrp_name']}: {$row['acccat_name']}</span>
					<div>
						<span>" . (str_repeat("<br/>", substr_count($row['acm_comments'] ?? "", "\n"))) . "</span>
						<div>" . (is_null($row['acm_comments']) ? "-" : nl2br($row['acm_comments'])) . "</div>
					</div>
				</td>";

				echo "<td class=\"blank\"></td>";
				echo "<td class=\"value-number\">" . ($row['atm_value'] > 0 ? number_format($row['atm_value'], 2) : "-") . "</td>";
				echo "<td class=\"value-number\">" . ($row['atm_value'] <= 0 ? number_format(abs($row['atm_value']), 2) : "-") . "</td>";
				echo "<td class=\"value-number final " . ($row['cumulative_sum'] < 0 ? "negative" : "positive") . "\">" . number_format(abs($row['cumulative_sum']), 2) . "</td>";
				echo "</tr>";
			}
		}
	} else {
		echo "<tr>";
		echo "<td></td><td>No records found</td><td></td><td class=\"value-comment\"></td><td></td><td></td>";
		echo "</tr>";
	}
	echo "</tbody>";
	if ($controller->criteria->getCurrentPage() == $pages) {
		echo '<tfoot>';
		echo "<tr>";
		echo "<td></td>";
		echo "<td></td>";
		echo "<td></td>";
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

if (is_null($app->user->account)) {
	$grem = new Gremium\Gremium(true);
	$grem->header()->status(Status::Exclamation)->serve("<h1>No account selected!</h1>");
	$grem->legend()->serve("<span class=\"flex\">Access to this page requires registering a valid account:</span>");
	$article          = $grem->article();
	$article->message .= '<ul style="margin:0">
		<li>Select an account from Account Selection Menu</li>
		<li>Contact system administrator</li>
		<li>Permission denied or not enough privileges to proceed with this document</li>
		<ul>';
	$article->serve();
	unset($grem);
	exit;
} elseif (!$app->user->account->role->view) {
	$grem = new Gremium\Gremium(true);
	$grem->header()->status(Status::Exclamation)->serve("<h1>Access restricted!</h1>");
	$grem->legend()->serve("<span class=\"flex\">Loading journal for `{$app->user->account->name}` account failed:</span>");
	$article          = $grem->article();
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


$initial_values = array(
	'from' => isset($_GET['from']) && $app->dateValidate($_GET['from']) ? date("Y-m-d", $app->dateValidate($_GET['from'])) : "",
	'to' => isset($_GET['to']) && $app->dateValidate($_GET['to']) ? date("Y-m-d", $app->dateValidate($_GET['to'])) : "",
	"page" => isset($_GET['page']) ? abs((int) $_GET['page']) : 0
);



$grem = new Gremium\Gremium(true);

$grem->header()->serve("<h1 class=\"header-title\">{$fs()->title}</h1>" .
	"<ul class=\"small-media-hide\"><li class=\"small-media-hide\">{$app->user->company->name}: {$app->user->account->name}</li></ul>" .
	"<cite><span id=\"js-output-total\">0.00</span>{$app->user->account->currency->shortname}</cite>");

$menu         = $grem->menu()->sticky(false)->open();
$current_date = new DateTime();
$current_date = $current_date->format("Y-m-d");
echo <<<HTML
<input type="text" id="js-input_date-start" style="width:110px" data-slo=":DATE" placeholder="From date" value="{$initial_values['from']}" value=""  />
<input type="text" id="js-input_date-end" style="width:110px" data-slo=":DATE" placeholder="To date" value="{$initial_values['to']}" value="{$current_date}"  />
<button id="js-input_cmd-update">Search</button>
<input type="button" class="edge-right" id="js-input_cmd-export" value="Export" />
HTML;
$menu->close();

$legend = $grem->legend()->open();
echo <<<HTML
<span id="js-output_statements-count">0</span>
<span class="small-media-hide flex"></span>
<input type="button" class="pagination prev edge-left" id="js-input_page-prev" disabled value="&#xE618;" />
<input type="text" id="js-input_page-current" placeholder="#" data-slo=":NUMBER" style="width:80px;text-align:center" data-rangestart="1" value="0" data-rangeend="100" />
<input type="button" class="pagination next" id="js-input_page-next" disabled value="&#xE61B;" />
<input type="button" class="edge-right" id="js-output_page-total" style="min-width:50px;text-align:center" value="0" />
HTML;
$legend->close();

$article = $grem->article()->options(array("nobg"))->open();
echo "<div id=\"js-container-output\" style=\"padding-bottom:50px\"></div>";
$article->close();

unset($grem);
?>
<style>
	.table-head {
		position: sticky;
	}

	table.statment-view>tbody>tr>td:nth-child(1)>div {
		padding: 2px 5px;
	}

	.table-head::before {
		position: absolute;
		display: block;
		content: "";
		width: 100%;
		height: 100%;
		margin-top: -2px;
		border-bottom: double 3px var(--bomtable-border-color);
	}

	td.value-comment {
		width: 100%;
		line-height: 1.3em;
	}

	td.value-comment>span {
		display: block;
		padding: 5px 0px;
		color: rgb(125, 125, 125);
	}

	td.value-comment>div {
		position: relative;
		padding: 5px 0px;
	}

	td.value-comment>div>span {
		display: block;
		width: 0px;
		padding-bottom: 10px;
	}

	td.value-comment>div>div {
		position: absolute;
		padding-bottom: 2px;
		right: 0px;
		left: 0px;
		top: 0px;
		text-overflow: ellipsis;
		overflow-y: hidden;
	}


	td.value-number {
		text-align: right;
		min-width: 100px;
		width: 150px;
	}

	td.value-number.final {
		/* font-weight: bold; */
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

	.statment-view>td.blank {
		display: none;
	}

	@media only screen and (max-width: 800px) {
		.table-head {
			position: relative;
			top: 0 !important
		}

	}

	@media only screen and (max-width: 768px) {
		table.statment-view {
			border: none;
		}

		table.statment-view>thead,
		table.statment-view>tfoot {
			display: none;
		}

		table.statment-view>tbody {
			display: block;
		}

		table.statment-view>tbody>tr {
			border: solid 1px var(--bomtable-border-color);
			display: flex;
			flex-wrap: wrap;
			margin: 5px 0px;
		}

		table.statment-view>tbody>tr>td {
			border: none;
			padding: 8px 10px;
		}

		table.statment-view>tbody>tr>td:nth-child(1) {
			flex: 1;
		}

		table.statment-view>tbody>tr>td:nth-child(1)>div {
			display: inline-block;
			padding: 0 10px 0 0;
		}

		table.statment-view>tbody>tr>td:nth-child(1)>div:last-child {
			display: block;
			padding-top: 10px;
		}

		table.statment-view>tbody>tr>td:nth-child(2) {
			flex: 1;
			flex-basis: 100%;
		}

		table.statment-view>tbody>tr>td:nth-child(3) {
			flex: 1;
			display: none;
		}


		table.statment-view>tbody>tr>td.value-number,
		td.value-number {
			width: auto;
			min-width: auto;
		}

		table.statment-view>tbody>tr>td.value-number:nth-child(4),
		table.statment-view>tbody>tr>td.value-number:nth-child(5) {
			flex: 1 1 50%;
		}

		table.statment-view>tbody>tr>td:nth-child(6) {
			color: var(--root-font-lightcolor);
			flex: 1;
		}


	}

	@media only screen and (max-width: 624px) {
		.header-title {
			display: none;
		}
	}

	@media only screen and (max-width: 480px) {

		.header-title,
		.small-media-hide,
		.menu-date_title {
			display: none;
		}

		#js-output_statements-count {
			flex: 1;
		}
	}
</style>
<form method="post" id="js-form_export" style="display:none;">
	<input type="hidden" name="method" value="statement_export" />
	<input type="hidden" name="page" value="" />
	<input type="hidden" name="from" value="" />
	<input type="hidden" name="to" value="" />
</form>

<script type="text/javascript">
	let pageConfig = {
		url: '<?= $fs()->dir; ?>',
		apptitle: '<?= $app->settings->site['title']; ?>',
		title: '<?= $app->settings->site['title'] . " - " . $fs()->title ?>',
		exporturl: "<?= $fs(13)->dir ?>",
		id: 0
	}
</script>

<script type="module">
	import AccountStatmenet from './static/javascript/modules/finance/accountstatement.js';
	const accountStatement = new AccountStatmenet();
</script>