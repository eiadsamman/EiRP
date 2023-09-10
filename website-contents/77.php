<?php
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.str_replace(" ","_",$fs()->title)."_".date("YmdHis").'.xlsx"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header ('Pragma: public'); // HTTP/1.0

require_once $app->root."admin/class/phpexcel/PHPExcel.php";
/** PHPExcel_Cell_AdvancedValueBinder */
require_once $app->root."admin/class/phpexcel/PHPExcel/Cell/AdvancedValueBinder.php";

/** PHPExcel_IOFactory */
require_once $app->root."admin/class/phpexcel/PHPExcel/IOFactory.php";
// Set value binder
PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );


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
$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
$objPHPExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
$objPHPExcel->getActiveSheet()->getPageMargins()->setTop(0.6);
$objPHPExcel->getActiveSheet()->getPageMargins()->setRight(0.25);
$objPHPExcel->getActiveSheet()->getPageMargins()->setLeft(0.25);
$objPHPExcel->getActiveSheet()->getPageMargins()->setBottom(0.5);

$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);

$objPHPExcel->getActiveSheet()->setShowGridlines(true);
$objPHPExcel->getActiveSheet()->setRightToLeft(true);

$objPHPExcel->getActiveSheet()->setTitle($fs()->title);

$arrheader=array("ID","Name","Section","Job","Shift","Gender","Birthdate","Residence","Registration","Salary","Variable","Transportation");

function num2alpha($n){
    for($r = ""; $n >= 0; $n = intval($n / 26) - 1)
        $r = chr($n%26 + 0x41) . $r;
    return $r;
}




$col=0;
foreach($arrheader as $v){
	$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, 1, $v);
	$objPHPExcel->getActiveSheet()->getColumnDimension(num2alpha($col))->setAutoSize(true);
	$col++;
}

$styleArrayHeader = array(
	'borders' => array(
		'outline' => array(//allborders
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
$objPHPExcel->getActiveSheet()->getStyle('A1:'.num2alpha($col-1).'1')->applyFromArray($styleArrayHeader);


$styleArrayEven=array(
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
$styleArrayOdd=array(
	'borders' => array(
		'outline' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
			'color' => array('argb' => 'FFCCCCCC'),
		),
	),
);
if(isset($_POST['user'])){
	if($r=$sql->query("
		SELECT 
			usr_id,usr_birthdate,
			UNIX_TIMESTAMP(lbr_registerdate) AS lbr_registerdate,
			lbr_serial,usr_id,
			
			CONCAT_WS(' ',COALESCE(REPLACE(REPLACE(REPLACE(usr_firstname,'آ','ا'),'إ','ا'),'أ','ا'),''),COALESCE(usr_lastname,'')) AS employeename,
			
			usr_lastname,usr_gender,
			lty_id,lty_name,
			lsf_id,lsf_name,
			gnd_id,gnd_name,
			ldn_id,ldn_name,usr_attrib_i2,
			UNIX_TIMESTAMP(lbr_resigndate) AS lbr_resigndate,
			st.lsc_name,
			
			IF(lbr_fixedsalary IS NOT NULL, 
				lbr_fixedsalary ,
				IF(wt_lbr.lwt_id IS NOT NULL AND lty_salarybasic IS NOT NULL,
					lty_salarybasic * wt_lbr.lwt_value / wt_lty.lwt_value,
					lty_salarybasic
				)
			) AS salary,
			lbr_fixedsalary,
			lbr_variable,
			trans_name
		FROM
			labour 
				JOIN users ON usr_id=lbr_id
				LEFT JOIN 
					(
						SELECT
							lsc_id,lty_id,lty_name,lsc_name,lty_salarybasic,lty_time
						FROM
							labour_section JOIN labour_type ON lty_section=lsc_id
					) AS st ON st.lty_id=lbr_type
				LEFT JOIN labour_shifts ON lsf_id=lbr_shift
				LEFT JOIN gender ON gnd_id=usr_gender
				LEFT JOIN labour_residentail ON ldn_id=lbr_residential

				LEFT JOIN workingtimes AS wt_lbr ON wt_lbr.lwt_id=lbr_fixedtime
				LEFT JOIN workingtimes AS wt_lty ON wt_lty.lwt_id=lty_time
				LEFT JOIN labour_transportation ON lbr_transportation=trans_id
				LEFT JOIN user_employeeselection AS sel_empusr ON sel_usremp_emp_id=lbr_id AND sel_usremp_usr_id={$USER->info->id}
		WHERE
			usr_id!=1 AND lbr_resigndate IS NULL 
			".(isset($_GET['onlyselection'])?" AND sel_usremp_emp_id IS NOT NULL ":"")."

			".(isset($_POST['user'][1]) 		&& (int)$_POST['user'][1]!=0		?" AND usr_id=".((int)$_POST['user'][1])."":"")."
			".(isset($_POST['job'][1]) 		&& (int)$_POST['job'][1]!=0		?" AND lbr_type=".((int)$_POST['job'][1])."":"")."
			".(isset($_POST['shift'][1])	 	&& (int)$_POST['shift'][1]!=0		?" AND lbr_shift=".((int)$_POST['shift'][1])."":"")."
			".(isset($_POST['section'][1])	&& (int)$_POST['section'][1]!=0	?" AND lsc_id=".((int)$_POST['section'][1])."":"")."
		ORDER BY
			employeename")){
		$sat=2;
		$col=0;
		while($row=$sql->fetch_assoc($r)){
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $sat, $row['usr_id']);
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, $sat, $row['employeename']);
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, $sat, $row['lsc_name']);
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(3, $sat, $row['lty_name']);
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(4, $sat, $row['lsf_name']);
			
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(5, $sat, $row['gnd_name']);
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(6, $sat, $row['usr_birthdate']);
			
			
			
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(7, $sat, $row['ldn_name']);
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(8, $sat, date("Y-m-d",$row['lbr_registerdate']));
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(9, $sat, (int)$row['salary']);
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(10, $sat, (int)$row['lbr_variable']);
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(11, $sat, $row['trans_name']);
			if($sat%2==0)
				$objPHPExcel->getActiveSheet()->getStyle("A{$sat}:K{$sat}")->applyFromArray($styleArrayEven);
			else
				$objPHPExcel->getActiveSheet()->getStyle("A{$sat}:K{$sat}")->applyFromArray($styleArrayOdd);
			$sat++;
		}
	}
}
$objPHPExcel->getActiveSheet()->setAutoFilter(
    $objPHPExcel->getActiveSheet()
        ->calculateWorksheetDimension()
);

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
$objPHPExcel->disconnectWorksheets();
unset($objPHPExcel);


exit;


?>