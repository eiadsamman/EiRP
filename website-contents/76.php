<?php

use System\Individual\Individual;

$controller = new System\Finance\StatementOfAccount\Snapview($app);
$controller->criteria->setCurrentPage(1);
$controller->criteria->setRecordsPerPage(4);

$mysqli_result = $controller->chunk();
if ($mysqli_result) {
	if ($mysqli_result->num_rows > 0) {
		echo <<<HTML
			<div class="tablewidget">
				<div style="padding-bottom:3px;padding-top:3px;">
					<div class="table">
						
			HTML;


		while ($row = $mysqli_result->fetch_assoc()) {
			$padge_type     = empty($row['issuer_badge']) ? "initials" : "image";
			$padge_initials = "" . mb_substr($row['usr_firstname'] ?? "", 0, 1) . " " . mb_substr($row['usr_lastname'] ?? "", 0, 1) . " ";
			$padge_color    = Individual::colorId((int) $row['acm_editor_id']);
			$badge_uri      = !empty($row['issuer_badge']) ?
				"<span style=\"background-image:url('{$fs(187)->dir}/?id={$row['issuer_badge']}&pr=t');\"></span>" :
				"<b style=\"background-color:{$padge_color}\">{$padge_initials}</b>";

			if ($row['atm_value'] < 0) {
				$row['atm_value'] = number_format(abs($row['atm_value']), 2);
				$row['atm_value'] = "(" . $row['atm_value'] . ")";
				$row['atm_value'] .= "<span style=\"font-family: glyphs;color: red;padding: 3px;position: relative; top: 1px\">&#xe91c;</span>";
			} else {
				$row['atm_value'] = number_format(abs($row['atm_value']), 2) . "&nbsp;";
				$row['atm_value'] .= "<span style=\"font-family: glyphs;color: green;padding: 3px;position: relative; top: 1px\">&#xe91b;</span>";
			}
			$time = new DateTime($row['acm_ctime']);
			echo <<<HTML
			<a href="{$app->file->find(104)->dir}/?id={$row['acm_id']}">
				<div class="padge {$padge_type}">{$badge_uri}</div>
				<div style="text-align: right;" class="sup"><div style="text-align:left">{$row['acm_id']}</div>{$row['atm_value']}</div>
				<div class="sup"><div>{$time->format("Y")}</div>{$time->format("d<\s\u\b>S</\s\u\b> M")}</div>
				<div class="ellipsis sup"><div>{$row['acccat_name']}</div>{$row['acm_beneficial']}</div>
				<!-- <td>{$row['acccat_name']}</td> -->
			</a>
		HTML;
		}


		echo <<<HTML
				</div>
			</div>
		</div>
		HTML;
	}
}
