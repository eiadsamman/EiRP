<?php
header("Content-Type: application/json; charset=utf-8");
$perpage_val = 20;

if ($app->xhttp) {
	$request = json_decode(file_get_contents('php://input'), true);
	if (!empty($request['method']) && $request['method'] == "fetch") {
		$json_output = array(
			"headers" => array(
				"count" => 0,/* Total count of records */
				"pages" => 0,/* Total number of pages */
				"current" => 0,/* Current navigation position on pages variable */
			),
			"contents" => array()
		);
		if (!$app->user->account) {
			echo json_encode($json_output);
			exit;
		}

		$controller = new System\Finance\StatementOfAccount\StatementOfAccount($app);

		$controller->criteria->setRecordsPerPage($perpage_val);
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

		$json_output['headers']['count']       = $count;
		$json_output['headers']['pages']       = $pages;
		$json_output['headers']['landing_uri'] = $fs(104)->dir;
		$json_output['headers']['current']     = $controller->criteria->getCurrentPage();


		if ($count > 0) {
			$mysqli_result = $controller->chunk(false);
			if ($mysqli_result->num_rows > 0) {
				while ($row = $mysqli_result->fetch_assoc()) {
					$json_output['contents'][] = array(
						"id" => $row['acm_id'],
						"value" => ($row['atm_value'] <= 0 ? "(" . number_format(abs($row['atm_value']), 2) . ")" : "" . number_format(abs($row['atm_value']), 2)),
						"positive" => $row['atm_value'] >= 0 ? 1 : 0,
						"date" => $row['acm_ctime'],
						"category" => "{$row['accgrp_name']}: {$row['acccat_name']}",
						"beneficial" => $row['acm_beneficial'],
						"details" => $row['acm_comments'] ?? "",
						"padge_id" => $row['issuer_badge'] ?? 0,
						"padge_initials" => mb_substr($row['usr_firstname'], 0, 1) . " " . mb_substr($row['usr_lastname'], 0, 1),
						"padge_color" => "hsl(" . ((int) ($row['acm_editor_id']) * 10 % 360) . ", 75%, 50%)",
						"attachements" => $row['up_count'] ?? 0
					);
				}
			}
		}
		echo json_encode($json_output);
		exit;

	}
}