<?php
use System\Template\Gremium;

if ($app->xhttp) {

	$accounting  = new \System\Finance\Accounting($app);
	$perpage_val = 20;

	$payload = json_decode(file_get_contents('php://input'), true);

	if (isset($payload['objective']) && $payload['objective'] == 'list') {
		$controller = new System\Finance\StatementOfAccount\StatementOfAccount($app);
		$controller->criteria->setRecordsPerPage($perpage_val);

		if (!empty($payload['statement-id'])) {
			$controller->criteria->statementID((int) $payload['statement-id']);
		}
		if (!empty($payload['category'])) {
			$controller->criteria->category((int) $payload['category']);
		}
		if (!empty($payload['beneficiary'])) {
			$controller->criteria->beneficiary($payload['beneficiary']);
		}
		if (!empty($payload['description'])) {
			$controller->criteria->comments($payload['description']);
		}
		if (!empty($payload['date-start']) && $app->dateValidate($payload['date-start'])) {
			$controller->criteria->dateStart($payload['date-start']);
		}
		if (!empty($payload['date-end']) && $app->dateValidate($payload['date-end'])) {
			$controller->criteria->dateEnd($payload['date-end']);
		}


		$user_current = abs((int) $payload['page']);
		$count        = 0;
		$sum          = 0;
		$pages        = 0;
		$controller->summary($count, $sum);
		$count = is_null($count) ? 0 : $count;
		$pages = ceil($count / $controller->criteria->getRecordsPerPage());

		$controller->criteria->setCurrentPage(1);
		if (isset($payload['page']) && $user_current > 0) {
			if ($user_current > $pages) {
				$controller->criteria->setCurrentPage($pages);
			} else {
				$controller->criteria->setCurrentPage(($user_current));
			}
		} elseif (isset($payload['page']) && $user_current == 0) {
			$controller->criteria->setCurrentPage(1);
		}

		header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
		header('Access-Control-Allow-Credentials: true');
		//header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Headers: *");

		header("Vendor-Ouput-Count: $count");
		header("Vendor-Ouput-Pages: $pages");
		header("Vendor-Ouput-Sum: " . ($sum < 0 ? "(" : "") . number_format(abs($sum ?? 0), 2) . ((int) $sum < 0 ? ")" : ""));
		header("Vendor-Ouput-Current: {$controller->criteria->getCurrentPage()}");


		$mysqli_result = $controller->chunk(false);
		if ($mysqli_result->num_rows > 0) {
			while ($row = $mysqli_result->fetch_assoc()) {
				echo "<tr>";
				echo "<td><a href=\"{$fs(104)->dir}/?id={$row['acm_id']}\" data-href=\"{$fs(104)->dir}/?id={$row['acm_id']}\">{$row['acm_id']}</a></td>";
				echo "<td>{$row['acm_ctime']}</td>";
				echo "<td>{$row['acm_beneficial']}</td>";
				echo "<td>{$row['accgrp_name']}: {$row['acccat_name']}</td>";
				echo "<td>" . nl2br($row['acm_comments'] ?? "") . "</td>";
				echo "<td style=\"text-align: right\">
					" . ($row['atm_value'] <= 0 ? "(" . number_format(abs($row['atm_value']), 2) . ")" : "" . number_format(abs($row['atm_value']), 2) . "&nbsp;") . "</td>";
				echo "</tr>";
			}
		}

		exit;
	}



	$grem = new Gremium\Gremium(true);
	$grem->header()->serve("<h1><a href=\"{$fs()->dir}\">{$fs()->title}</a></h1>" .
		"<ul class=\"small-media-hide\"><li>{$app->user->account->type->keyTerm->toString()}: {$app->user->account->name}</li></ul>" .
		"<cite><span id=\"js-output-total\">0.00</span> {$app->user->account->currency->shortname}</cite>");
	$legend = $grem->menu()->open();
	echo <<<HTML
		<button id="searchButton" class="edge-right edge-left" data-href="{$fs(170)->dir}" data-target="{$fs(170)->dir}">Search</button>
		<input type="button" id="cancelSearchButton" style="display: none;" value="Clear" class="edge-right" data-href="{$fs()->dir}" href="{$fs()->dir}" />

		<span class="small-media-hide flex" id="js-output_total-records"  style="justify-content: flex-end">0 records</span>
		<input type="button" class="pagination prev edge-left" id="js-input_page-prev" disabled value="&#xE618;" />
		<input type="text" id="js-input_page-current" placeholder="#" data-slo=":NUMBER" style="width:80px;text-align:center" data-rangestart="1" value="0" data-rangeend="100" />
		<input type="button" class="pagination next" id="js-input_page-next" disabled value="&#xE61B;" />
		<input type="button" class="edge-right" id="js-output_page-total" style="min-width:50px;text-align:center" value="0" />
		
	HTML;
	$legend->close();


	$grem->article()->open();
	$dummyrows = "";
	for ($i = 0; $i < 3; $i++) {
		$dummyrows .= "<tr><td class=\"placeholder\" colspan=\"6\"></td></tr>";
	}
	echo <<<HTML
		<table class="statment-view hover strip">
			<thead class="table-head" style="top: calc(158px - var(--gremium-header-toggle));background-color: #fff;z-index: 1;">
			<tr>
				<td>ID</td>
				<td>Date</td>
				<td>Beneficial</td>
				<td>Category</td>
				<td style="width:100%;">Description</td>
				<td>Value</td>
			</tr>
			</thead>
			<tbody id="js-container-output">
				{$dummyrows}
			</tbody>
		</table>
	HTML;
	$grem->getLast()->close();


	$grem->terminate();
	unset($grem);


}