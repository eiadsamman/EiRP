<?php
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

include_once("admin/class/accounting.php");
$accounting = new Accounting();
$__defaultaccount = $accounting->operation_default_account("salary_report");
$__defaultcurrency = $accounting->account_default_currency($__defaultaccount['id']);
if ($__defaultaccount === false) {
	exit;
} elseif ($__defaultcurrency === false) {
	exit;
}



require_once $app->root . "admin/class/phpexcel/PHPExcel.php";
/** PHPExcel_Cell_AdvancedValueBinder */
require_once $app->root . "admin/class/phpexcel/PHPExcel/Cell/AdvancedValueBinder.php";

/** PHPExcel_IOFactory */
require_once $app->root . "admin/class/phpexcel/PHPExcel/IOFactory.php";
// Set value binder
PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());


$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()->setCreator($USER->info->username)
	->setLastModifiedBy($USER->info->username)
	->setTitle($fs()->title)
	->setSubject($fs()->title)
	->setDescription($fs()->title)
	->setKeywords($fs()->title)
	->setCategory("Report");
$objPHPExcel->setActiveSheetIndex(0);

$objPHPExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&C&H&BNebras Co. | &KFF0000 Treat this document as confidential');
$objPHPExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objPHPExcel->getProperties()->getTitle() . '&C&D' . '&RPage &P of &N');
$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToWidth(1);
$objPHPExcel->getActiveSheet()->getPageSetup()->setFitToHeight(0);
$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
$objPHPExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
$objPHPExcel->getActiveSheet()->getPageMargins()->setTop(0.6);
$objPHPExcel->getActiveSheet()->getPageMargins()->setRight(0.25);
$objPHPExcel->getActiveSheet()->getPageMargins()->setLeft(0.25);
$objPHPExcel->getActiveSheet()->getPageMargins()->setBottom(0.5);

$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

$objPHPExcel->getActiveSheet()->setShowGridlines(false);
$objPHPExcel->getActiveSheet()->setRightToLeft(false);

$objPHPExcel->getActiveSheet()->setTitle($fs()->title);

$arrheader = array("ID", "Employee Name", "Payout value", "Payout records");

function num2alpha($n)
{
	for ($r = ""; $n >= 0; $n = intval($n / 26) - 1)
		$r = chr($n % 26 + 0x41) . $r;
	return $r;
}

$col = 0;
foreach ($arrheader as $v) {
	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, 1, $v);
	$objPHPExcel->getActiveSheet()->getColumnDimension(num2alpha($col))->setAutoSize(true);
	$col++;
}

$styleArrayHeader = array(
	'borders' => array(
		'outline' => array( //allborders
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('argb' => 'FF666666'),
		),
	),
	'alignment' => array(
		'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
	),
	'font' => array(
		'bold' => true,
	),

);
$objPHPExcel->getActiveSheet()->getStyle('A1:' . num2alpha($col - 1) . '1')->applyFromArray($styleArrayHeader);


$styleArrayEven = array(
	'fill' => array(
		'type' => PHPExcel_Style_Fill::FILL_SOLID,
		'startcolor' => array(
			'argb' => 'FFF5F5F5',
		)
	),
	'borders' => array(
		'outline' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('argb' => 'FFCCCCCC'),
		),
	),
);
$styleArrayOdd = array(
	'borders' => array(
		'outline' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('argb' => 'FFCCCCCC'),
		),
	),
);


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
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $sat, $row['usrid']);
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, $sat, $row['usrname']);
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, $sat, number_format($row['atm_value'], 2, ".", ""));
		$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(3, $sat, $row['acm_count']);

		$sat++;
	}
}





$objPHPExcel->getActiveSheet()->setAutoFilter(
	$objPHPExcel->getActiveSheet()
		->calculateWorksheetDimension()
);


$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
if (!$debug)
	$objWriter->save('php://output');
$objPHPExcel->disconnectWorksheets();
unset($objPHPExcel);

exit;
