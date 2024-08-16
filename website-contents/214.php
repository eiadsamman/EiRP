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
		if (!empty($payload['party'])) {
			$controller->criteria->party((int) $payload['party']);
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
				echo "<tr data-href=\"{$fs(104)->dir}/?id={$row['acm_id']}\">";

				echo "<td>";
				echo "<div><a href=\"{$fs(104)->dir}/?id={$row['acm_id']}\">{$row['acm_id']}</a></div>";
				echo "<div>{$row['acm_ctime']}</div>";
				echo "<div class=\"in-value value-number " . ($row['atm_value'] <= 0 ? " negative" : "positive") . "\">" . number_format(abs($row['atm_value']), 2) . "</div>";
				echo "<div>" . (!empty($row['comp_id']) && $row['comp_id'] != $app->user->company->id ? "<span class=\"value-hightlight\">[" . $row['comp_name'] . "]</span> " : "") .


					(!is_null($row['_party_comp_id']) ? "<a>{$row['_party_comp_name']}</a><br />" : "") . "{$row['acm_beneficial']}</div>";

				echo "</td>";

				echo "<td class=\"value-comment\">
					<span>{$row['usr_firstname']} {$row['usr_lastname']}</span>
					<span>{$row['accgrp_name']}: {$row['acccat_name']}</span>
					<div>
						<span>" . (str_repeat("<br/>", substr_count($row['acm_comments'] ?? "", "\n"))) . "</span>
						<div>" . (is_null($row['acm_comments']) ? "-" : nl2br($row['acm_comments'])) . "</div>
					</div>
				</td>";

				echo "<td class=\"blank\"></td>";
				echo "<td class=\"value-number final " . ($row['cumulative_sum'] < 0 ? "negative" : "positive") . "\">" . number_format(abs($row['atm_value']), 2) . "</td>";
				echo "</tr>";
			}
		}

		exit;
	}



	?>


	<?php
	$grem = new Gremium\Gremium(true);
	$grem->header()->serve("<h1><span class=\"small-media-hide\">{$app->user->account->type->keyTerm->toString()}: </span>{$app->user->account->name}</h1>" .
		"<cite><span id=\"js-output-total\">0.00</span> {$app->user->account->currency->shortname}</cite>");
	$legend = $grem->menu()->open();
	echo <<<HTML
		<button id="searchButton" class="edge-right edge-left search" data-href="{$fs(170)->dir}" data-target="{$fs(170)->dir}"><span class="small-media-hide"> Search</span></button>
		<input type="button" id="cancelSearchButton" style="display: none;font-family: glyphs" class="edge-right error" data-href="{$fs()->dir}" href="{$fs()->dir}" value="&#xe901;" />
		<span class="flex" style="justify-content: flex-end"><span class="small-media-hide" id="js-output_total-records" >0 records</span></span>
		<input type="button" class="pagination prev edge-left" id="js-input_page-prev" disabled value="&#xe91a;" />
		<input type="text" id="js-input_page-current" placeholder="#" data-slo=":NUMBER" style="width:80px;text-align:center" data-rangestart="1" value="0" data-rangeend="100" />
		<input type="button" class="pagination next" id="js-input_page-next" disabled value="&#xe91d;" />
		<input type="button" class="edge-right " id="js-output_page-total" style="min-width:50px;text-align:center" value="0" />
	HTML;
	$legend->close();


	$grem->article()->open();
	$dummyrows = "";
	for ($i = 0; $i < 3; $i++) {
		$dummyrows .= "<tr><td class=\"placeholder\" colspan=\"6\"></td></tr>";
	}
	echo <<<HTML
		<table class="statment-view hover strip">
			<thead class="table-head" style="top: calc(163px - var(--gremium-header-toggle));background-color: #fff;z-index: 1;">
			<tr>
				<td>ID</td>
				<td>Description</td>
				<td class="blank" style="width: 100%"></td>
				<td class="value-number" style="padding-right:10px;">Balance</td>
				</tr>
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