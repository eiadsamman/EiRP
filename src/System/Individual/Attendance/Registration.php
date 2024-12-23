<?php

declare(strict_types=1);

namespace System\Individual\Attendance;


use mysqli_result;
use System\Exceptions\HR\PersonResignedException;



class Registration extends \System\Individual\Employee
{
	private $_silent = false;
	private $_discardtimelimit = 10; /*Drop repeated input within given time is seconds, must be greater than 2 seconds or otherwise it will cause some issues*/
	private $defaultCheckInAccount = false;
	public $integrityCheck = true;

	protected \System\App $app;

	public function __construct(\System\App &$app)
	{
		$this->app = $app;
	}

	public function DefaultCheckInAccount($companyID)
	{
		if ((int) $companyID == 0) {
			return false;
		}
		$r = $this->app->db->query("
			SELECT 
				prt_id
			FROM 
				`acc_accounts` 
					JOIN partitionlabour ON prtlbr_prt_id =prt_id AND prtlbr_op = 1
			WHERE
				prt_company_id = " . ((int) $companyID) . "
			");
		if ($r) {
			if ($rowacc = $r->fetch_assoc()) {
				return (int) $rowacc['prt_id'];
			}
		}
		return false;
	}

	public function SetDefaultCheckInAccount($companyID)
	{
		$this->defaultCheckInAccount = $this->DefaultCheckInAccount($companyID);
	}

	public function DefaultCheckInternalAccounts($company): array
	{
		if (!$company) {
			return array();
		}
		$r = $this->app->db->query("
			SELECT 
				prt_id,prtlbr_name
			FROM 
				`acc_accounts` 
					JOIN partitionlabour ON prtlbr_prt_id =prt_id AND prtlbr_op = 2
			WHERE
				prt_company_id = $company
			");
		if ($r) {
			$output = array();
			while ($rowacc = $r->fetch_assoc()) {
				$output[] = array((int) $rowacc['prt_id'], $rowacc['prtlbr_name']);
			}
			return $output;
		}
		return array();
	}

	public function CheckOut($customTime = null)
	{
		$time      = $customTime == null ? time() : (int) $customTime;
		$time      = new \DateTime();
		$timestamp = $time->format("Y-m-d H:i:s");

		$resultquery = true;
		$runningAtt  = $this->GetRunningAttendance();

		if ($runningAtt['id'] == false) {
			throw new ExceptionCheckedout("Already checked out", 21003);
		}

		$this->app->db->autocommit(false);
		$resultquery &= $this->app->db->query("UPDATE labour_track SET ltr_otime = '$timestamp', ltr_osigner = {$this->app->user->info->id} WHERE ltr_id = {$runningAtt['id']};");
		if ($resultquery) {
			$this->app->db->commit();
			return true;
		} else {
			$this->app->db->rollback();
			return false;
		}
	}

	public function CheckIn($pointID = null, $customTime = null)
	{
		$time = $customTime == null ? time() : (int) $customTime;

		$time        = new \DateTime();
		$timestamp   = $time->format("Y-m-d H:i:s");
		$accessPoint = 0;
		$resultquery = true;
		$runningAtt  = $this->GetRunningAttendance();

		$this->app->db->autocommit(false);

		if ($pointID == null) {
			if (!$this->defaultCheckInAccount) {
				throw new LocationInvalid("Invalid sector", 21005);
			}
			if ($this->info->resigned) {
				throw new PersonResignedException("Resigned employee", 21002);
			}
			$accessPoint = $this->defaultCheckInAccount;
		} else {
			if ((int) $pointID == 0) {
				throw new LocationInvalid("Invalid sector", 21005);
			}
			$accessPoint = (int) $pointID;
		}

		if ($runningAtt['id'] == false && $pointID != null) {
			throw new ExceptionNotSignedIn("Not Signed In", 21004);
		} elseif ($runningAtt['id'] == false && $pointID == null) {
			//Continue normal
		} elseif ($runningAtt['id'] != false && ($time->getTimestamp() - $runningAtt['time']->getTimestamp()) < $this->_discardtimelimit) {
			throw new ExceptionTimeLimit("Time limit", 21006);
		} elseif ($runningAtt['id'] != false && $pointID == null) {
			/*Suspicious check-in with no previous check-out, drop latest record by setting the time diff to 0*/
			//$resultquery &= $this->app->db->query("UPDATE labour_track SET ltr_otime = ({ $runningAtt['time']->getTimestamp()}), ltr_osigner=$signerID WHERE ltr_id = {$runningAtt['id']};");
			throw new ExceptionDuplicateCheckin("Duplicate checking", 21007);
		} elseif ($runningAtt['id'] != false && $pointID != null) {
			//Close current record

			$resultquery &= $this->app->db->query("UPDATE labour_track SET ltr_otime = '$timestamp', ltr_osigner={$this->app->user->info->id} WHERE ltr_id = {$runningAtt['id']};");
		}

		if ($resultquery) {
			$resultquery &= $this->app->db->query("INSERT INTO labour_track (ltr_ctime,ltr_usr_id,ltr_csigner,ltr_prt_id) VALUES ('$timestamp', {$this->info->id},{$this->app->user->info->id},$accessPoint)");
		}

		if ($resultquery) {
			$this->app->db->commit();
			return true;
		} else {
			$this->app->db->rollback();
			return false;
		}
	}


	private function AttendanceQuery($dateFrom, $dateTo, $parameters): mysqli_result|bool
	{
		/*  WITH RECURSIVE nrows(date) AS (
		 *	SELECT DATE("2024-11-23") UNION ALL 
		 *	SELECT DATE_ADD(date, INTERVAL 1 day) FROM nrows WHERE date < CURRENT_DATE
		 * )
		 * SELECT date FROM nrows 
		 * 
		 * 
		 * 
		 * 
		 * WITH ranked_messages AS (
		 *  SELECT m.*, ROW_NUMBER() OVER (PARTITION BY name ORDER BY id DESC) AS rn
		 *  FROM messages AS m
		 * )
		 * SELECT * FROM ranked_messages WHERE rn = 1;
		 * 
		 * 
		 */

		$dateFromShift = $dateFrom->modify('+2 month');
		$dateToShift   = $dateTo->modify('-2 month');
		var_dump($parameters['company']);
		$r = (
			"SELECT
				{$parameters['::select']}
			FROM 
				(
					SELECT 
						ltr_usr_id,
						ltr_prt_id,
						ltr_ctime, 
						ltr_otime, 
						DATE(ltr_ctime + INTERVAL integers.seq DAY) AS att_date,
						CASE
							WHEN DATE(ltr_ctime + INTERVAL seq DAY)  = DATE(ltr_ctime) AND DATE(ltr_ctime + INTERVAL seq DAY)  = DATE(ltr_otime) THEN TIME_TO_SEC(TIMEDIFF(ltr_otime, ltr_ctime))
							WHEN DATE(ltr_ctime + INTERVAL seq DAY)  = DATE(ltr_ctime) AND DATE(ltr_ctime + INTERVAL seq DAY) != DATE(ltr_otime) THEN TIME_TO_SEC(TIMEDIFF(STR_TO_DATE(CONCAT(DATE(ltr_ctime + INTERVAL seq DAY), ' ', '23:59:59'), '%Y-%m-%d %H:%i:%s') , ltr_ctime))
							WHEN DATE(ltr_ctime + INTERVAL seq DAY) != DATE(ltr_ctime) AND DATE(ltr_ctime + INTERVAL seq DAY)  = DATE(ltr_otime) THEN TIME_TO_SEC(TIMEDIFF(ltr_otime, STR_TO_DATE(CONCAT(DATE(ltr_ctime + INTERVAL seq DAY), ' ', '00:00:00'), '%Y-%m-%d %H:%i:%s') ))
							ELSE 86400
						END * atttable.prt_lbr_perc AS att_time
						
					FROM
						(
							SELECT 
								ltr_id, ltr_usr_id, ltr_prt_id,prt_lbr_perc,ltr_ctime,COALESCE(ltr_otime,'{$dateTo->format("Y-m-d H:i:s")}') AS ltr_otime
							FROM
								labour_track
									JOIN acc_accounts ON prt_id = ltr_prt_id AND prt_company_id = {$parameters['company']}
							WHERE
								ltr_ctime < '{$dateFromShift->format("Y-m-d H:i:s")}' AND ltr_ctime > '{$dateToShift->format("Y-m-d H:i:s")}'

						) AS atttable

						INNER JOIN (
							SELECT seq FROM seq_1_to_60
						) AS integers ON integers.seq <= DATEDIFF(atttable.ltr_otime, atttable.ltr_ctime)
						
					WHERE
						DATE(ltr_ctime + INTERVAL seq DAY)  >= '{$dateFrom->format("Y-m-d H:i:s")}'
						AND
						DATE(ltr_ctime + INTERVAL seq DAY)  <= '{$dateTo->format("Y-m-d H:i:s")}'
					
				) AS joiner
				
				JOIN (
					SELECT 
						ltr_ctime AS latestRecordIn, ltr_otime AS latestRecordOut, ltr_usr_id AS latestRecordUser
					FROM
						labour_track AS latestRecordTable
						JOIN(
							SELECT MAX(ltr_id) AS ltr_id
							FROM labour_track JOIN acc_accounts ON prt_id = ltr_prt_id AND prt_company_id = {$parameters['company']}
							WHERE ltr_ctime < '{$dateFromShift->format("Y-m-d H:i:s")}' AND ltr_ctime > '{$dateToShift->format("Y-m-d H:i:s")}'
							GROUP BY ltr_usr_id
							
						) AS latestRecordEach ON latestRecordEach.ltr_id = latestRecordTable.ltr_id
					WHERE
						ltr_ctime < '{$dateFromShift->format("Y-m-d H:i:s")}' AND ltr_ctime > '{$dateToShift->format("Y-m-d H:i:s")}'

				) AS latestRecord ON latestRecord.latestRecordUser = joiner.ltr_usr_id
				JOIN 
					(
						SELECT
							lbr_id ,usr_firstname,usr_lastname,lbr_mth_name,usr_entity,lbr_payment_method,lty_section,usr_jobtitle,up_id
						FROM 
							labour 
							JOIN users ON lbr_id = usr_id
							LEFT JOIN (SELECT lty_name,lsc_name,lty_id,lty_time,lty_salarybasic,lty_section FROM labour_section JOIN labour_type ON lsc_id=lty_section) AS _mol ON _mol.lty_id = usr_jobtitle
							LEFT JOIN workingtimes ON lwt_id = lbr_workingtimes
							LEFT JOIN labour_method ON lbr_mth_id = lbr_payment_method
							LEFT JOIN uploads ON (up_pagefile=" . \System\Attachment\Type::HrPerson->value . ") AND up_rel=lbr_id AND up_deleted=0
					) AS personDetails ON personDetails.lbr_id = joiner.ltr_usr_id
							

			WHERE
				1
				" . (isset($parameters['paymethod']) && (int) $parameters['paymethod'] != 0 ? " AND lbr_payment_method=" . ($parameters['paymethod']) : "") . " 
				" . (isset($parameters['section']) && !is_null($parameters['section']) && (int) $parameters['section'] != 0 ? " AND lty_section=" . ((int) $parameters['section']) : "") . "
				" . (isset($parameters['job']) && !is_null($parameters['job']) && (int) $parameters['job'] != 0 ? " AND usr_jobtitle = " . ((int) $parameters['job']) : "") . "
				
			GROUP BY
				{$parameters['::group']}

			{$parameters['::order']}
		");

		return $this->app->db->query($r);
	}

	public function ReportOngoingBySector(array $parameters = array()): mysqli_result|bool
	{
		$var1 = \System\Attachment\Type::HrPerson->value;
		$r    = (
			"SELECT
				lbr_id, usr_firstname,usr_lastname, up_id
			FROM 
				labour_track 

				INNER JOIN (
					SELECT
						MAX(ltr_ctime) AS _ltr_ctime,
						ltr_usr_id AS _ltr_usr_id
					FROM
						labour_track
					WHERE
						ltr_otime IS NULL AND ltr_prt_id = {$parameters['sector']}
					GROUP BY
						ltr_usr_id
				) AS lastJoin ON lastJoin._ltr_ctime = ltr_ctime AND lastJoin._ltr_usr_id = ltr_usr_id 


				JOIN (
					SELECT
						lbr_id ,usr_firstname,up_id,usr_lastname
					FROM 
						labour 
							JOIN users ON lbr_id = usr_id
							LEFT JOIN uploads ON (up_pagefile = $var1) AND up_rel=lbr_id AND up_deleted=0
				) AS personDetails ON personDetails.lbr_id = ltr_usr_id
				
			WHERE
				ltr_prt_id = {$parameters['sector']}
			ORDER BY 
				ltr_ctime DESC, lbr_id
				
		");
		return $this->app->db->query($r);
	}



	public function ReportOngoing($parameters = array()): mysqli_result|bool
	{
		$dateTo  = date("Y-m-d H:i:s", time());
		$orderby = "ltr_ctime DESC, lbr_id";
		if (isset($parameters['::order'])) {
			$orderby = $parameters['::order'];
		}
		$r = (
			"SELECT
				up_id,
				
				ltr_ctime,
				ltr_otime,
				ltr_prt_id,

				usr_entity,
				usr_firstname,
				usr_lastname,
				
				lbr_id,
				usr_jobtitle,
				lbr_mth_name,
				lty_section,
				lbr_payment_method,
				
				lty_name,
				lsc_name,
				TIME_TO_SEC(TIMEDIFF('{$dateTo}', _lci_time)) AS diff,
				DATE_FORMAT(_lci_time, '%Y-%m-%d') AS ltr_ctime_date,
				DATE_FORMAT(_lci_time, '%H:%i') AS ltr_ctime_time
				
			FROM 
				labour_track 
				
				INNER JOIN 
				(
					SELECT 
						MAX(ltr_ctime) AS _ltr_ctime, 
						ltr_usr_id AS _ltr_usr_id 
					FROM 
						labour_track 
							JOIN acc_accounts ON prt_id = ltr_prt_id AND prt_company_id = {$parameters['company']}
					WHERE
						ltr_otime IS null
					GROUP BY 
						_ltr_usr_id 
				) AS lastJoin
					ON 
						lastJoin._ltr_ctime = ltr_ctime AND 
						lastJoin._ltr_usr_id = ltr_usr_id
				
				INNER JOIN 
				(
					SELECT 
						MAX(ltr_ctime) AS _lci_time, 
						ltr_usr_id AS _ltr_usr_id 
					FROM 
						labour_track
							JOIN partitionlabour ON prtlbr_prt_id = ltr_prt_id AND prtlbr_op = 1
							JOIN acc_accounts ON prt_id = ltr_prt_id AND prt_company_id = {$parameters['company']}
					GROUP BY 
						_ltr_usr_id 
				) AS lastCheckin
					ON 
						lastCheckin._ltr_usr_id = ltr_usr_id
				
				JOIN 
					(
						SELECT
							lbr_id ,usr_firstname,usr_lastname,lbr_mth_name,usr_entity,lbr_payment_method,lty_section,usr_jobtitle,up_id,lty_name,lsc_name
						FROM 
							labour 
							JOIN users ON lbr_id = usr_id
							LEFT JOIN
								(
									SELECT
										lty_name,lsc_name,lty_id,lty_time,lty_salarybasic,lty_section
									FROM
										labour_section JOIN labour_type ON lsc_id=lty_section
								) AS _mol ON _mol.lty_id = usr_jobtitle
							
							LEFT JOIN workingtimes ON lwt_id = lbr_workingtimes
							LEFT JOIN labour_method ON lbr_mth_id = lbr_payment_method
							LEFT JOIN uploads ON (up_pagefile=" . \System\Attachment\Type::HrPerson->value . ") AND up_rel=lbr_id AND up_deleted=0
						WHERE
							usr_entity = {$parameters['company']}
							" . (isset($parameters['paymethod']) && (int) $parameters['paymethod'] != 0 ? " AND lbr_payment_method=" . ($parameters['paymethod']) : "") . " 
							" . (isset($parameters['section']) && !is_null($parameters['section']) && (int) $parameters['section'] != 0 ? " AND lty_section=" . ((int) $parameters['section']) : "") . "
							" . (isset($parameters['job']) && !is_null($parameters['job']) && (int) $parameters['job'] != 0 ? " AND usr_jobtitle = " . ((int) $parameters['job']) : "") . "
							
					) AS personDetails ON personDetails.lbr_id = ltr_usr_id
			WHERE
				1
			ORDER BY 
				{$orderby}
				
		");

		return $this->app->db->query($r);
	}

	public function ReportToday(array $parameters = array()): mysqli_result|bool
	{

		$dateFrom = new \DateTimeImmutable("now");
		$dateFrom = $dateFrom->setTime(0, 0, 0);
		$dateTo   = new \DateTimeImmutable("now");



		$parameters['::group']  = " ltr_usr_id ";
		$parameters['::order']  = " ORDER BY lty_section, lbr_id ";
		$parameters['::select'] = "
			ltr_usr_id AS personID,
			SUM(att_time) AS timeAttended,
			att_date,
			ltr_ctime,
			usr_firstname,
			usr_lastname,
			latestRecordIn,
			latestRecordOut,
			lbr_mth_name,
			ltr_prt_id,
			lty_section,
			up_id,
			usr_jobtitle";

		return $this->AttendanceQuery($dateFrom, $dateTo, $parameters);
	}


	public function ReportSummary($dateFrom, $dateTo, $id = null, $parameters = array()): mysqli_result|bool
	{
		$dateFrom = new \DateTimeImmutable($dateFrom);
		$dateTo   = new \DateTimeImmutable($dateTo);

		$parameters['::group']  = " ltr_usr_id, att_date ";
		$parameters['::order']  = " ORDER BY lbr_id ";
		$parameters['::select'] = "
				ltr_usr_id AS personID,
				SUM(att_time) AS timeAttended,
				att_date,
				usr_firstname,
				usr_lastname,
				lbr_mth_name,
				usr_entity,
				lbr_payment_method,
				lty_section,
				usr_jobtitle";
		return $this->AttendanceQuery($dateFrom, $dateTo, $parameters);
	}
}
