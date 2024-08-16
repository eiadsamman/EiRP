<?php

$controller = new System\Finance\StatementOfAccount\Snapview($app);
$controller->criteria->setCurrentPage(1);
$controller->criteria->setRecordsPerPage(4);

echo <<<HTML
<div class="tablewidget">
	<div style="padding-bottom:3px;padding-top:3px;">
		<table>
			<tbody>
HTML;
$mysqli_result = $controller->chunk();
if ($mysqli_result->num_rows > 0) {
	while ($row = $mysqli_result->fetch_assoc()) {

		$padge_id       = $row['issuer_badge'];
		$padge_type     = empty($row['issuer_badge']) ? "initials" : "image";
		$padge_initials = "" . mb_substr($row['usr_firstname'], 0, 1) . " " . mb_substr($row['usr_lastname'], 0, 1) . " ";
		$padge_color    = "hsl(" . ((int) ($row['acm_editor_id']) * 10 % 360) . ", 75%, 50%)";

		$badge_uri = !empty($row['issuer_badge']) ?
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

		echo <<<HTML
			<tr>
				<td class="padge {$padge_type}">{$badge_uri}</i></td>
				<td style="text-align: right;">{$row['atm_value']}</td>
				<td>{$row['acm_ctime']}</td>
				<td>{$row['acm_beneficial']}</td>
				<td>{$row['acccat_name']}</td>
			</tr>
		HTML;
	}
}

echo <<<HTML
				</table>
		</table>
	</div>
</div>
HTML;
