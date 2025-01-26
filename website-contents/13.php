<?php
if (!$app->user->account->role->view)
	exit;

ob_start();
header("Accept-Ranges: bytes");
header("Content-Transfer-Encoding: binary");
header('Content-Type: text/csv; charset=utf-8');
header('Cache-Control: max-age=1');
header("Content-Type: application/force-download");
header("Content-Type: application/octet-stream");
header("Content-Type: application/download");
header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Content-Disposition: attachment;filename="Statement of Account ' . date("ymd") . ' [' . $app->user->company->name . ", " . $app->user->account->name . "] " . ".csv");
ob_end_clean();

if (isset($_POST['method']) && $_POST['method'] == "statement_export") {
	$controller = new System\Controller\Finance\StatementOfAccount\StatementOfAccount($app);

	/* Date input processing */
	$date_start   = isset($_POST['from']) ? $app->dateValidate($_POST['from']) : false;
	$date_end     = isset($_POST['to']) ? $app->dateValidate($_POST['to'], true) : false;
	$user_current = abs((int) $_POST['page']);
	if (($date_start && $date_end) && $date_start > $date_end) {
		exit;
	}

	if ($date_start)
		$controller->criteria->dateStart(date("Y-m-d", $date_start));
	if ($date_end)
		$controller->criteria->dateEnd(date("Y-m-d", $date_end));

	$count = $sum = $pages = 0;
	$controller->summary($count, $sum);

	if ($count > 0) {
		$output_pointer = fopen('php://output', 'w');
		fprintf($output_pointer, chr(0xEF) . chr(0xBB) . chr(0xBF));
		fputcsv(
			$output_pointer,
			array("Date", "ID", "Beneficiary", "Description", "Debit", "Credit", "Balance")
		);
		foreach ($controller->complete() as $record) {
			fputcsv(
				$output_pointer,
				array(
					$record['acm_ctime'],
					$record['acm_id'],
					($record['comp_id'] != $app->user->company->id ? "[" . $record['comp_name'] . "]" : "") . $record['acm_beneficial'],
					$record['acm_comments'],
					($record['atm_value'] > 0 ? number_format($record['atm_value'], 2, ".", "") : "0"),
					($record['atm_value'] <= 0 ? number_format(($record['atm_value']), 2, ".", ",") : "0"),
					number_format(($record['cumulative_sum']), 2)
				)
			);
		}
		fclose($output_pointer);
	}
}
exit;