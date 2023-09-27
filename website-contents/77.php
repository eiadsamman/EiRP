<?php
$debug = false;
if (!$app->user->company->id) {
	exit;
}


if (!$debug) {
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
	header('Content-Disposition: attachment;filename="Employees records' . date("ymd") . ' [' . $app->user->company->name . "] " . ".csv");

	ob_end_clean();
}

$stmt = $app->db->prepare(
	"SELECT 
			usr_id,
			usr_birthdate,
			usr_gender,
			CONCAT_WS(' ',COALESCE(usr_firstname ,''),COALESCE(usr_lastname,'')) AS employeename,
			
			
			lbr_registerdate,
			lty_id,lty_name,
			lsf_id,lsf_name,
			
			st.lsc_name,
			
			gnd_name,

			IF(lbr_fixedsalary IS NOT NULL, 
				lbr_fixedsalary ,
				IF(wt_lbr.lwt_id IS NOT NULL AND lty_salarybasic IS NOT NULL,
					lty_salarybasic * wt_lbr.lwt_value / wt_lty.lwt_value,
					lty_salarybasic
				)
			) AS salary,

			lbr_fixedsalary,
			lbr_variable
		FROM
			labour 
				JOIN users ON usr_id = lbr_id
				LEFT JOIN 
					(
						SELECT
							lsc_id,lty_id,lty_name,lsc_name,lty_salarybasic,lty_time
						FROM
							labour_section JOIN labour_type ON lty_section=lsc_id
					) AS st ON st.lty_id=lbr_type
				LEFT JOIN labour_shifts ON lsf_id=lbr_shift
				LEFT JOIN gender ON gnd_id=usr_gender

				LEFT JOIN workingtimes AS wt_lbr ON wt_lbr.lwt_id=lbr_fixedtime
				LEFT JOIN workingtimes AS wt_lty ON wt_lty.lwt_id=lty_time
				LEFT JOIN user_employeeselection AS sel_empusr ON sel_usremp_emp_id=lbr_id AND sel_usremp_usr_id={$app->user->info->id}
		WHERE
			usr_id != 1 AND
			lbr_resigndate IS NULL AND
			(lbr_role & B'001') = 1 AND
			lbr_company = {$app->user->company->id} 
			" . (isset($_GET['onlyselection']) ? " AND sel_usremp_emp_id IS NOT NULL " : "") . "
			" . (isset($_POST['user'][1]) && (int) $_POST['user'][1] != 0 ? " AND usr_id = " . ((int) $_POST['user'][1]) . "" : "") . "
			" . (isset($_POST['job'][1]) && (int) $_POST['job'][1] != 0 ? " AND lbr_type = " . ((int) $_POST['job'][1]) . "" : "") . "
			" . (isset($_POST['shift'][1]) && (int) $_POST['shift'][1] != 0 ? " AND lbr_shift = " . ((int) $_POST['shift'][1]) . "" : "") . "
			" . (isset($_POST['section'][1]) && (int) $_POST['section'][1] != 0 ? " AND lsc_id = " . ((int) $_POST['section'][1]) . "" : "") . "
		ORDER BY
		usr_id
			"
);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

	$output_pointer = fopen('php://output', 'w');
		fprintf($output_pointer, chr(0xEF) . chr(0xBB) . chr(0xBF));
		fputcsv(
			$output_pointer,
			array(
				"ID",
				"Name",
				"Gender",
				"Birthdate",
				"Register date",
				"Job title",
			)
		);
	while ($row = $result->fetch_assoc()) {
		fputcsv(
			$output_pointer,
			array(
				$row['usr_id'],
				$row['employeename'],
				$row['gnd_name'],
				$row['usr_birthdate'],
				$row['lbr_registerdate'],
				"{$row['lsc_name']}: {$row['lty_name']}"			)
		);
	}
	fclose($output_pointer);
}
exit;