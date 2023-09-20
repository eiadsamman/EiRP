<?php

use System\Finance\Accounting;

if ($_SERVER['REQUEST_METHOD'] != "POST") {
	exit;
}
$debug = false;


if (!$debug) {
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	header('Content-Disposition: attachment;filename="' . str_replace(" ", "_", $fs()->title) . "_" . date("YmdHis") . '.xlsx"');
	header('Cache-Control: max-age=0');
	header('Cache-Control: max-age=1');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
	header('Cache-Control: cache, must-revalidate');
	header('Pragma: public');
}

$accounting = new Accounting($app);
$__defaultaccount = $accounting->operation_default_account("salary_report");
$__defaultcurrency = $accounting->account_default_currency($__defaultaccount['id']);
if ($__defaultaccount === false) {
	exit;
} elseif ($__defaultcurrency === false) {
	exit;
}




$month = null;
if (isset($_POST['month']) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_POST['month'], $match)) {
	if (checkdate($match[2], $match[3], $match[1])) {
		$month = date("Y-m-d", mktime(0, 0, 0, $match[2], 1, $match[1]));
	}
}
if ($month == null) {
	exit;
}
$total = $count = 0;


if ($r = $app->db->query("SELECT
			acm_id,
			SUM(_accounts.atm_value) AS atm_value,
			COUNT(acm_id) AS acm_count,
			
			_accounts._prtid,
			(SELECT CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS usrname FROM 
				users WHERE usr_id=acm_usr_id) AS usrname,
			(SELECT usr_id AS usrid FROM 
				users WHERE usr_id=acm_usr_id) AS usrid
		FROM
			acc_main
				RIGHT JOIN 
					(
					SELECT
						atm_value,atm_main,prt_id AS _prtid
					FROM
						`acc_accounts` 
							LEFT JOIN acc_temp ON prt_id=atm_account_id
							LEFT JOIN currencies ON cur_id = prt_currency
							
					) AS _accounts ON _accounts.atm_main=acm_id
		WHERE
			_prtid={$__defaultaccount['id']} AND acm_rejected=0 
			AND EXTRACT(YEAR_MONTH FROM acm_month)=EXTRACT(YEAR_MONTH FROM '$month') 
		GROUP BY
			acm_usr_id
		ORDER BY
			acm_usr_id;")) {

	$sat = 2;
	$col = 0;
	while ($row = $r->fetch_assoc()) {
		$sat++;
	}
}


exit;
