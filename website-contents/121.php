<?php
use System\Views\PanelView;

if ($app->xhttp) {
	$request = json_decode(file_get_contents('php://input'), true);
	if (!empty($request['method']) && $request['method'] == "fetch") {

		if (!$app->user->account) {
			header("res_count: 0");
			header("res_pages: 0");
			header("res_current: 0");
			echo '';
			exit;
		}

		$controller = new System\Finance\StatementOfAccount\StatementOfAccount($app);

		$controller->criteria->setRecordsPerPage(PanelView::$itemsPerRequest);
		//$controller->criteria->statementID(7207);
		//$controller->criteria->statementBeneficiary('مصطفى');

		$user_current = abs((int) $request['page']);
		$count        = $sum = $pages = 0;
		$controller->summary($count, $sum);
		$count = is_null($count) ? 0 : $count;
		$pages = ceil($count / $controller->criteria->getRecordsPerPage());


		if (isset($request['page']) && $user_current > 0) {
			if ($user_current > $pages) {
				$controller->criteria->setCurrentPage($pages);
			} else {
				$controller->criteria->setCurrentPage(($user_current));
			}
		} elseif (isset($request['page']) && $user_current == 0) {
			$controller->criteria->setCurrentPage(1);
		}

		header("res_count: {$count}");
		header("res_pages: {$pages}");
		header("res_current: {$controller->criteria->getCurrentPage()}");

		$downloadurl = $fs(187)->dir;

		if ($count > 0) {
			$mysqli_result = $controller->chunk(false);
			if ($mysqli_result->num_rows > 0) {
				while ($row = $mysqli_result->fetch_assoc()) {
					$pos = $row['atm_value'] >= 0 ? '<span class="stm inc active"></span>' : '<span class="stm pay active"></span>';
					$att = !is_null($row['up_count']) && (int) $row['up_count'] > 0 ? '<span class="atch"></span>' : "";
					$ben = (!is_null($row['_party_comp_id']) ? "<span style=\"color:var(--root-link-color)\">{$row['_party_comp_name']}</span>: " : "") . $row['acm_beneficial'];
					$val = ($row['atm_value'] <= 0 ? "(" . number_format(abs($row['atm_value']), 2) . ")" : "" . number_format(abs($row['atm_value']), 2));
					

					$ini = mb_substr($row['usr_firstname'] ?? "", 0, 1) . " " . mb_substr($row['usr_lastname'] ?? "", 0, 1);
					$car = "hsl(" . ((int) ($row['acm_editor_id']) * 10 % 360) . ", 75%, 50%)";
					$bad = is_null($row['issuer_badge']) ? "initials" : "image";
					$bar = is_null($row['issuer_badge']) ? "<b style=\"background-color:{$car}\">{$ini}</b>" : "<span style=\"background-image:url('{$downloadurl}/?id={$row['issuer_badge']}&pr=t');\"></span>";

					echo <<<HTML
						<a class="panel-item statment-panel" href="{$fs(104)->dir}/?id={$row['acm_id']}" data-listitem_id="{$row['acm_id']}" data-href="{$fs(104)->dir}">
							<div>
								<span style="flex: 1">
									<div><h1>{$ben}</h1><cite>{$att}</cite><cite>{$row['acm_id']}</cite></div>
									<div><cite>{$pos}</cite><h1>{$val}</h1><cite>{$row['acm_ctime']}</cite></div>
									<div><h1>{$row['accgrp_name']}: {$row['acccat_name']}</h1></div>
								</span>
								<i class="padge {$bad}">{$bar}</i>
							</div>
							<div><h1 class="description">{$row['acm_comments']}</h1></div>
						</a>
					HTML;
				}
			}
		}
		exit;

	}
}