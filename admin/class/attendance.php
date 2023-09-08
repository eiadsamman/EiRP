<?php

namespace System\Person;

include_once("admin/class/accounting.php");
include_once("admin/class/person.php");
include_once("admin/class/employee.php");


use Exception;
use System\Pool;
use mysqli_result;

class AlreadyCheckedoutException extends Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}
class NotSignedInException extends Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}
class AttendanceSectorException extends Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}
class AttendanceTimeLimitException extends Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}
class AttendanceDuplicateCheckin extends Exception
{
	public function errorPlot()
	{
		return $this->getMessage();
	}
}




class Attendance extends Employee
{
	private $_silent = false;
	private $_discardtimelimit = 10; /*Drop repeated input within given time is seconds, must be greater than 2 seconds or otherwise it will cause malfunctioning*/
	private $defaultCheckInAccount = false;
	public $integrityCheck = true;



	public function DefaultCheckInAccount($companyID)
	{
		if ((int)$companyID == 0) {
			return false;
		}
		$racc = Pool::$sql->query("
			SELECT 
				prt_id
			FROM 
				`acc_accounts` 
					JOIN partitionlabour ON prtlbr_prt_id =prt_id AND prtlbr_op = 1
			WHERE
				prt_company_id = " . ((int)$companyID) . "
			");
		if ($racc) {
			if ($rowacc = Pool::$sql->fetch_assoc($racc)) {
				return (int)$rowacc['prt_id'];
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
		$racc = Pool::$sql->query("
			SELECT 
				prt_id,prtlbr_name
			FROM 
				`acc_accounts` 
					JOIN partitionlabour ON prtlbr_prt_id =prt_id AND prtlbr_op = 2
			WHERE
				prt_company_id = $company
			");
		if ($racc) {
			$output = array();
			while ($rowacc = Pool::$sql->fetch_assoc($racc)) {
				$output[] = array((int)$rowacc['prt_id'], $rowacc['prtlbr_name']);
			}
			return $output;
		}
		return array();
	}

	public function CheckOut($signerID, $customTime = null)
	{
		$time			= $customTime == null ? time() : (int)$customTime;
		$resultquery	= true;
		$runningAtt 	= $this->GetRunningAttendance();

		if ($runningAtt['id'] == false) {
			throw new AlreadyCheckedoutException("Already checked out", 21003);
		}

		Pool::$sql->autocommit(false);
		$resultquery &= Pool::$sql->query("UPDATE labour_track SET ltr_otime = FROM_UNIXTIME({$time}), ltr_osigner=$signerID WHERE ltr_id = {$runningAtt['id']};");
		if ($resultquery) {
			Pool::$sql->commit();
			return true;
		} else {
			Pool::$sql->rollback();
			return false;
		}
	}

	public function CheckIn($signerID, $pointID = null, $customTime = null)
	{
		$time			= $customTime == null ? time() : (int)$customTime;
		$signerID		= (int) $signerID;
		$accessPoint	= 0;
		$resultquery	= true;
		$runningAtt 	= $this->GetRunningAttendance();
		Pool::$sql->autocommit(false);

		if ($pointID == null) {
			if (!$this->defaultCheckInAccount) {
				throw new AttendanceSectorException("Invalid sector", 21005);
			}
			if ($this->info->resigned) {
				throw new PersonResignedException("Resigned employee", 21002);
			}
			$accessPoint = $this->defaultCheckInAccount;
		} else {
			if ((int)$pointID == 0) {
				throw new AttendanceSectorException("Invalid sector", 21005);
			}
			$accessPoint = (int)$pointID;
		}

		if ($runningAtt['id'] == false && $pointID != null) {
			throw new NotSignedInException("Not Signed In", 21004);
		} elseif ($runningAtt['id'] == false && $pointID == null) {
			//Continue normal
		} elseif ($runningAtt['id'] != false && ($time - $runningAtt['time']) < $this->_discardtimelimit) {
			throw new AttendanceTimeLimitException("Time limit", 21006);
		} elseif ($runningAtt['id'] != false && $pointID == null) {
			/*Suspicious check-in with no previous check-out, drop latest record by setting the time diff to 0*/
			//$resultquery &= Pool::$sql->query("UPDATE labour_track SET ltr_otime = FROM_UNIXTIME({$runningAtt['time']}), ltr_osigner=$signerID WHERE ltr_id = {$runningAtt['id']};");
			throw new AttendanceDuplicateCheckin("Duplicate checking", 21007);
		} elseif ($runningAtt['id'] != false && $pointID != null) {
			//Close current record
			$resultquery &= Pool::$sql->query("UPDATE labour_track SET ltr_otime = FROM_UNIXTIME({$time}), ltr_osigner=$signerID WHERE ltr_id = {$runningAtt['id']};");
		}

		if ($resultquery) {
			$resultquery &= Pool::$sql->query("INSERT INTO labour_track (ltr_ctime,ltr_usr_id,ltr_csigner,ltr_prt_id) VALUES (FROM_UNIXTIME($time),{$this->info->id},{$signerID},$accessPoint)");
		}

		if ($resultquery) {
			Pool::$sql->commit();
			return true;
		} else {
			Pool::$sql->rollback();
			return false;
		}
	}


	private function AttendanceQuery($dateFrom, $dateTo, $parameters): mysqli_result|bool
	{
		$r = ("
			SELECT
				{$parameters['::select']}
			FROM 
				(SELECT 
					ltr_usr_id,
					ltr_prt_id,
					ltr_ctime, 
					ltr_otime, 
					DATE(ltr_ctime + INTERVAL integers.i DAY) AS att_date,
					CASE
						WHEN DATE(ltr_ctime + INTERVAL i DAY)  = DATE(ltr_ctime) AND DATE(ltr_ctime + INTERVAL i DAY)  = DATE(ltr_otime) THEN TIME_TO_SEC(TIMEDIFF(ltr_otime, ltr_ctime))
						WHEN DATE(ltr_ctime + INTERVAL i DAY)  = DATE(ltr_ctime) AND DATE(ltr_ctime + INTERVAL i DAY) != DATE(ltr_otime) THEN TIME_TO_SEC(TIMEDIFF(STR_TO_DATE(CONCAT(DATE(ltr_ctime + INTERVAL i DAY), ' ', '23:59:59'), '%Y-%m-%d %H:%i:%s') , ltr_ctime))
						WHEN DATE(ltr_ctime + INTERVAL i DAY) != DATE(ltr_ctime) AND DATE(ltr_ctime + INTERVAL i DAY)  = DATE(ltr_otime) THEN TIME_TO_SEC(TIMEDIFF(ltr_otime, STR_TO_DATE(CONCAT(DATE(ltr_ctime + INTERVAL i DAY), ' ', '00:00:00'), '%Y-%m-%d %H:%i:%s') ))
						ELSE 86400
					END * atttable.prt_lbr_perc AS att_time
					
				FROM
					(
						SELECT 
							ltr_id, ltr_usr_id, ltr_prt_id,prt_lbr_perc,
							ltr_ctime,
							COALESCE(ltr_otime,'{$dateTo}') AS ltr_otime
						FROM
							labour_track
								JOIN `acc_accounts` ON prt_id = ltr_prt_id
								JOIN labour ON lbr_id = ltr_usr_id
						WHERE 
							1
							
							
					) AS atttable

					INNER JOIN (
						SELECT
							 t1 + t2 * 10 AS i
						FROM
							(select 0 t1 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
							(select 0 t2 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t2
					) AS integers ON integers.i <= DATEDIFF(atttable.ltr_otime, atttable.ltr_ctime)
					
					
					
				WHERE
					DATE(ltr_ctime + INTERVAL i DAY)  >= '{$dateFrom}'
					AND
					DATE(ltr_ctime + INTERVAL i DAY)  <= '{$dateTo}'
					
				) AS joiner
				
				
				JOIN (
					SELECT 
						ltr_ctime AS latestRecordIn, ltr_otime AS latestRecordOut, ltr_usr_id AS latestRecordUser
					FROM
						labour_track AS latestRecordTable
						JOIN(
							SELECT
								MAX(ltr_id) AS ltr_id
							FROM
								labour_track
							GROUP BY ltr_usr_id
						) AS latestRecordEach ON latestRecordEach.ltr_id = latestRecordTable.ltr_id
					
				) AS latestRecord ON latestRecord.latestRecordUser = joiner.ltr_usr_id
				JOIN 
					(
						SELECT
							lbr_id ,usr_firstname,usr_lastname,lbr_mth_name,lbr_company,lbr_payment_method,lty_section,lbr_type,up_id
						FROM 
							labour 
							JOIN users ON lbr_id = usr_id
							LEFT JOIN
								(
									SELECT
										lty_name,lsc_name,lty_id,lty_time,lty_salarybasic,lty_section
									FROM
										labour_section JOIN labour_type ON lsc_id=lty_section
								) AS _mol ON _mol.lty_id = lbr_type
							
							LEFT JOIN workingtimes ON lwt_id = lbr_workingtimes
							LEFT JOIN labour_method ON lbr_mth_id = lbr_payment_method
							LEFT JOIN uploads ON (up_pagefile=" . Pool::FILE['Person']['Photo'] . ") AND up_rel=lbr_id AND up_deleted=0
							
					) AS personDetails ON personDetails.lbr_id = joiner.ltr_usr_id
							
			
			WHERE
				lbr_company = {$parameters['company']}
				" . (isset($parameters['paymethod']) && (int)$parameters['paymethod'] != 0 ? " AND lbr_payment_method=" . ($parameters['paymethod']) : "") . " 
				" . (isset($parameters['section']) && !is_null($parameters['section']) && (int)$parameters['section'] != 0 ? " AND lty_section=" . ((int)$parameters['section']) : "") . "
				" . (isset($parameters['job']) && !is_null($parameters['job']) && (int)$parameters['job'] != 0 ? " AND lbr_type=" . ((int)$parameters['job']) : "") . "
				
			GROUP BY
				{$parameters['::group']}
			
			{$parameters['::order']}
		");


		return Pool::$sql->query($r);
	}

	public function ReportOngoingBySector(array $parameters = array()): mysqli_result|bool
	{
		$r = ("
			SELECT
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
						ltr_otime is null AND ltr_prt_id = {$parameters['sector']}
					GROUP BY
						ltr_usr_id
				) AS lastJoin ON lastJoin._ltr_ctime = ltr_ctime AND lastJoin._ltr_usr_id = ltr_usr_id 


				JOIN (
					SELECT
						lbr_id ,usr_firstname,up_id,usr_lastname
					FROM 
						labour 
							JOIN users ON lbr_id = usr_id
							LEFT JOIN uploads ON (up_pagefile=" . Pool::FILE['Person']['Photo'] . ") AND up_rel=lbr_id AND up_deleted=0
					WHERE
						lbr_company = {$parameters['company']}

				) AS personDetails ON personDetails.lbr_id = ltr_usr_id
			WHERE
				ltr_prt_id = {$parameters['sector']}
			ORDER BY 
				ltr_ctime DESC, lbr_id
				
		");

		return Pool::$sql->query($r);
	}



	public function ReportOngoing($parameters = array()): mysqli_result|bool
	{
		$dateTo = date("Y-m-d H:i:s", time());
		$orderby = "ltr_ctime DESC, lbr_id";
		if (isset($parameters['::order'])) {
			$orderby = $parameters['::order'];
		}
		$r = ("
			SELECT
				ltr_ctime,
				ltr_otime,
				ltr_prt_id,
				lbr_id,
				usr_firstname,
				usr_lastname,
				lbr_mth_name,
				lbr_company,
				lbr_payment_method,
				lty_section,
				lbr_type,
				up_id,
				lty_name,
				lsc_name,
				TIME_TO_SEC(TIMEDIFF('{$dateTo}', _lci_time)) AS diff,
				DATE_FORMAT(_lci_time, '%Y-%m-%d') AS ltr_ctime_date,
				DATE_FORMAT(_lci_time, '%H:%i') AS ltr_ctime_time,
				prt_name
				
			FROM 
				labour_track 
				
				INNER JOIN 
				(
					SELECT 
						MAX(ltr_ctime) AS _ltr_ctime, 
						ltr_usr_id AS _ltr_usr_id ,
						prt_name
					FROM 
						labour_track 
							JOIN `acc_accounts` ON prt_id = ltr_prt_id
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
						`acc_accounts`
							JOIN partitionlabour ON prtlbr_prt_id = prt_id AND prtlbr_op = 1
							JOIN labour_track ON ltr_prt_id = prt_id
					GROUP BY 
						_ltr_usr_id 
				) AS lastCheckin
					ON 
						lastCheckin._ltr_usr_id = ltr_usr_id
				
				JOIN 
				
					(
						SELECT
							lbr_id ,usr_firstname,usr_lastname,lbr_mth_name,lbr_company,lbr_payment_method,lty_section,lbr_type,up_id,lty_name,lsc_name
						FROM 
							labour 
							JOIN users ON lbr_id = usr_id
							LEFT JOIN
								(
									SELECT
										lty_name,lsc_name,lty_id,lty_time,lty_salarybasic,lty_section
									FROM
										labour_section JOIN labour_type ON lsc_id=lty_section
								) AS _mol ON _mol.lty_id = lbr_type
							
							LEFT JOIN workingtimes ON lwt_id = lbr_workingtimes
							LEFT JOIN labour_method ON lbr_mth_id = lbr_payment_method
							LEFT JOIN uploads ON (up_pagefile=" . Pool::FILE['Person']['Photo'] . ") AND up_rel=lbr_id AND up_deleted=0
						WHERE
							lbr_company = {$parameters['company']}
							" . (isset($parameters['paymethod']) && (int)$parameters['paymethod'] != 0 ? " AND lbr_payment_method=" . ($parameters['paymethod']) : "") . " 
							" . (isset($parameters['section']) && !is_null($parameters['section']) && (int)$parameters['section'] != 0 ? " AND lty_section=" . ((int)$parameters['section']) : "") . "
							" . (isset($parameters['job']) && !is_null($parameters['job']) && (int)$parameters['job'] != 0 ? " AND lbr_type=" . ((int)$parameters['job']) : "") . "
							
					) AS personDetails ON personDetails.lbr_id = ltr_usr_id
			WHERE
				1
			ORDER BY 
				{$orderby}
				
		");

		return Pool::$sql->query($r);
	}

	public function ReportToday(array $parameters = array()): mysqli_result|bool
	{
		$dateFrom = date("Y-m-d 00:00:00", time());
		$dateTo = date("Y-m-d H:i:s", time());

		$parameters['::group'] = " ltr_usr_id ";
		$parameters['::order'] = " ORDER BY lty_section, lbr_id ";
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
				lbr_type";
		return $this->AttendanceQuery($dateFrom, $dateTo, $parameters);
	}


	public function ReportSummary($dateFrom, $dateTo, $id = null, $parameters = array()): mysqli_result|bool
	{
		$parameters['::group'] = " ltr_usr_id, att_date ";
		$parameters['::order'] = " ORDER BY lbr_id ";
		$parameters['::select'] = "
				ltr_usr_id AS personID,
				SUM(att_time) AS timeAttended,
				att_date,
				usr_firstname,
				usr_lastname,
				lbr_mth_name,
				lbr_company,
				lbr_payment_method,
				lty_section,
				lbr_type";
		return $this->AttendanceQuery($dateFrom, $dateTo, $parameters);
	}
}
