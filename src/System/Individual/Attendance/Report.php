<?php

declare(strict_types=1);

namespace System\Individual\Attendance;

/**
 * v2.18.11 181114
 */
class Report
{

	protected \System\App $app;
	private $_dump = false;
	private $_weekend = array();
	private $_arratt = array();
	private $_arrhol = array();
	private $_regsitration_date = null;
	private $_resignation_date = null;
	private $_arrabsntnotice = array();
	private $_partitionlist = array(0 => array("NaN", "255,255,255", 0));
	private $_user_id = null;
	private $_dateFrom = array(0 => null, 1 => null, 2 => null);
	private $_dateTo = array(0 => null, 1 => null, 2 => null);

	public function __construct(\System\App &$app)
	{
		$this->app = $app;
	}
	public function GetDateFrom($details)
	{
		if (isset($this->_dateFrom[(int) $details]))
			return $this->_dateFrom[(int) $details];
		else
			return null;
	}
	public function GetDateTo($details)
	{
		if (isset($this->_dateTo[(int) $details]))
			return $this->_dateTo[(int) $details];
		else
			return null;
	}


	public function dump()
	{
		echo "<pre>";
		foreach ($this->_arratt as $monthk => $monthv) {
			echo "<b>{$monthk}</b>\n";
			foreach ($monthv as $dayk => $dayv) {
				echo "\t$dayk\n";
				foreach ($dayv as $k => $v) {
					echo "\t\t$k\n";
					echo "\t\t\tOpnID:\t" . $v[2] . "\t\t\tColser ID:\t" . $v[3] . ($v[9] != null ? "\t\t\t<b>Owner: </b>" . $v[9] : "") . "\n";
					echo "\t\t\tStart:\t" . ((int) $v[0] != 0 ? date("Y-m-d H:i:s", $v[0]) : "-") . "\tEarly Start:\t" . ((int) $v[6] != 0 ? date("Y-m-d H:i:s", $v[6]) : "-") . "\n";
					echo "\t\t\tClose:\t" . ((int) $v[1] != 0 ? date("Y-m-d H:i:s", $v[1]) : "-") . "\tLate Finish:\t" . ((int) $v[7] != 0 ? date("Y-m-d H:i:s", $v[7]) : "-") . "\n";
					echo "\t\t\tStatu:\t" . $v[5] . "\t\t\t<span style=\"color:#f03;font-weight:bold\">" . (isset($v[8]) ? $v[8] : "") . "</span>\n";
				}
			}
		}
		echo "</pre>";
	}

	/*
	 * Function: FillGaps
	 * Extend attendance over multiple days if late finish exceeded 23:59:59
	 *
	 *
	 *
	 **/
	private function FillGaps($from, $to, $prt, $ltrid, $latefinish, $earlystart, $closerid, $status, $maincheckinOwner, $CutDownStart = false)
	{
		$smonth = date("Y-m", $from);
		$sdate = date("Y-m-d", $from);
		$fdate = date("Y-m-d", $to);
		$cnt = 0;
		while ($sdate <= $fdate && $cnt <= 31) {
			if (!isset($this->_arratt[$smonth])) {
				$this->_arratt[$smonth] = array();
			}
			if (!isset($this->_arratt[$smonth][$sdate])) {
				$this->_arratt[$smonth][$sdate] = array();
			}
			$time = date("His", $from);

			$this->_arratt[$smonth][$sdate][$time] = array(
				/*0:StartTime*/
				$from,
				/*1:FinishTime*/	0,
				/*2:OpenRecordID*/	$ltrid,
				/*3:CloseRecordID*/	$closerid,
				/*4:Sector*/	$prt,
				/*5:status*/	$status,
				/*6:EarylStart*/	$earlystart,
				/*7:LateFinish*/	$latefinish,
				/*8:*/	"",
				/*9:*/	$maincheckinOwner
			);

			if (isset($CutDownStart) && $CutDownStart != false) {
				$this->_arratt[$smonth][$sdate][$time][10] = $CutDownStart;
			}

			if ($sdate != $fdate) {
				$this->_arratt[$smonth][$sdate][$time][1] = mktime(23, 59, 59, (int) date("m", $from), (int) date("d", $from), (int) date("Y", $from));
			} else {
				$this->_arratt[$smonth][$sdate][$time][1] = $to;
			}
			$from = mktime(0, 0, 0, (int) date("m", $from), (int) date("d", $from) + 1, (int) date("Y", $from));
			$smonth = date("Y-m", $from);
			$sdate = date("Y-m-d", $from);
			$cnt++;
		}
	}


	private function filldays()
	{
		$currentTime = $this->_dateFrom[2];
		while ($currentTime <= $this->_dateTo[2]) {
			$scurrent = mktime(0, 0, 0, (int) date("m", $currentTime), (int) date("d", $currentTime), (int) date("Y", $currentTime));
			$fcurrent = mktime(23, 59, 59, (int) date("m", $currentTime), (int) date("d", $currentTime), (int) date("Y", $currentTime));

			if (!isset($this->_arratt[date("Y-m", $scurrent)][date("Y-m-d", $scurrent)])) {
				$this->_arratt[date("Y-m", $scurrent)][date("Y-m-d", $scurrent)] = array();
				$this->_arratt[date("Y-m", $scurrent)][date("Y-m-d", $scurrent)][date("His", $scurrent)] = array(
					$scurrent,
					$fcurrent,
					0,
					0,
					0,
					0,
					0,
					0,
					"Filled",
					null
				);
			}
			$currentTime = strtotime(" +1 Day", $currentTime);
		}
	}
	private function attsort()
	{
		ksort($this->_arratt);
		foreach ($this->_arratt as $monthk => $monthv) {
			ksort($this->_arratt[$monthk]);
			foreach ($monthv as $datek => $datev) {
				ksort($this->_arratt[$monthk][$datek]);
			}
		}
	}
	private function fillsf()
	{
		//Fill gaps between records, for a reliable presentation

		foreach ($this->_arratt as $monthk => $monthv) {
			foreach ($monthv as $datek => $datev) {
				$temp = null;
				foreach ($datev as $k => $v) {
					if ($temp == null && date("His", $v[0]) != "000000") {
						$this->_arratt[$monthk][$datek]['000000'] = array(
							/*0:StartTime*/
							mktime(0, 0, 0, (int) date("m", $v[0]), (int) date("d", $v[0]), (int) date("Y", $v[0])),
							/*1:FinishTime*/	$v[0],
							/*2:OpenRecordID*/	0,
							/*3:CloseRecordID*/	0,
							/*4:Sector*/	0,
							/*5:status*/	0,
							/*6:EarylStart*/	0,
							/*7:LateFinish*/	0,
							"Starter!",
							/*8:Comments*/	null,
							/*9:Owner*/	null
						);

						$temp = array($v[0], $v[1]);
					} elseif ($temp == null) {
						$temp = array($v[0], $v[1]);
					} elseif ($temp != null) {
						if (date("His", $temp[1]) < date("His", $v[0])) {
							$this->_arratt[$monthk][$datek][date("His", $temp[1])] = array($temp[1], $v[0], 0, 0, 0, 0, 0, 0, null, null);
							$this->_arratt[$monthk][$datek][date("His", $temp[1])][8] = "Middle Earth!";
						}
						$temp = array($v[0], $v[1]);
					}
				}
				if (date("His", $temp[1]) != "235959") {
					$this->_arratt[$monthk][$datek][date("His", $temp[1])] = array(
						$temp[1],
						mktime(23, 59, 59, (int) date("m", $temp[1]), (int) date("d", $temp[1]), (int) date("Y", $temp[1])),
						0,
						0,
						0,
						0,
						0,
						0,
						null,
						null
					);
					$this->_arratt[$monthk][$datek][date("His", $temp[1])][8] = "Closer!";
				}
			}
		}
	}

	private function GetStartTime()
	{
		$__start = array();
		$__start['id'] = null;
		$__start['time'] = date("Y-m-d 00:00:00", $this->_dateFrom[2]);


		return $__start;
	}
	private function GetFinishTime($consider_last_checking = false)
	{
		$__finish = array();
		$__finish['id'] = null;
		//date("Y-m-d 23:59:59",$this->_dateTo[2])



		return $__finish;
	}
	private function GetAbsentWithNotice()
	{
		$q = "
		SELECT
			absdays,lbr_abs_comments as comments,lbr_abs_days as period,abs_typ_name as type,lbr_abs_start_date as starts,
			CONCAT_WS(' ',COALESCE(usr_issuer.usr_firstname,''),IF(NULLIF(usr_issuer.usr_lastname, '') IS NULL, NULL, usr_issuer.usr_lastname))  as issuer,
			CONCAT_WS(' ',COALESCE(usr_approval.usr_firstname,''),IF(NULLIF(usr_approval.usr_lastname, '') IS NULL, NULL, usr_approval.usr_lastname))  as approval_name,
			usr_approval.usr_id  as approval_id

			
		FROM
			labour_absence_request main
			JOIN(
				SELECT
					adddate(lbr_abs_start_date, t1*10 + t0) AS absdays ,lbr_abs_id
				FROM
					(select 0 t0 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
					(select 0 t1 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
					
					labour_absence_request
				WHERE
					t1*10 + t0 < lbr_abs_days
				) a ON a.lbr_abs_id=main.lbr_abs_id
			LEFT JOIN
				absence_types ON abs_typ_id=lbr_abs_type
			LEFT JOIN 
				users as usr_issuer ON usr_issuer.usr_id=lbr_abs_usr_id
			LEFT JOIN 
				users as usr_approval ON usr_approval.usr_id=lbr_abs_supervisor
		WHERE
			absdays >= '" . date("Y-m-d 00:00:00", $this->_dateFrom[2]) . "' AND absdays < '" . date("Y-m-d 23:59:59", $this->_dateTo[2]) . "'
			 AND lbr_abs_lbr_id=" . ($this->_user_id) . ";";
		if ($r = $this->app->db->query($q)) {
			while ($row = $r->fetch_assoc()) {
				if (!isset($this->_arrabsntnotice[$row['absdays']])) {
					$this->_arrabsntnotice[$row['absdays']] = array();
				}
				$this->_arrabsntnotice[$row['absdays']][] = $row;
			}
		}
	}
	private function GetWeekendList()
	{
		$q = "SELECT cwk_pointer FROM calendar_weekends WHERE cwk_status=1;";
		if ($r = $this->app->db->query($q)) {
			while ($row = $r->fetch_assoc()) {
				$this->_weekend[$row['cwk_pointer']] = true;
			}
		}
	}
	private function GetHolidaysList()
	{
		//$this->_arrhol;
		$q = "SELECT
				holicow AS cal_date,main.cal_details,main.cal_id,main.cal_editor
			FROM
				calendar main
				JOIN(
					SELECT 
						/*Move calendar record to current year if cal_yearly=1*/
						IF(
							cal_yearly=1,
							adddate(
								STR_TO_DATE(
									CONCAT(
										'" . date("Y", $this->_dateFrom[2]) . "','-',MONTH(cal_date),'-',DAY(cal_date)
									),
									'%Y-%m-%d'
								),t1*10 + t0
							),
							/*Otherwise use date as it is*/
							adddate(cal_date,t1*10 + t0) 
						) AS holicow,
						cal_id
					FROM 
						(select 0 t0 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0,
						(select 0 t1 union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t1,
						
						calendar
					WHERE 
						t1*10 + t0 < cal_period AND cal_op=1 AND cal_owner=0
					) a ON a.cal_id=main.cal_id
			WHERE
				holicow >= '" . date("Y-m-d 00:00:00", $this->_dateFrom[2]) . "' AND holicow < '" . date("Y-m-d 23:59:59", $this->_dateTo[2]) . "'";
		$r = $this->app->db->query($q);
		if ($r) {
			while ($row = $r->fetch_assoc()) {
				if (!isset($this->_arrhol[$row['cal_date']])) {
					$this->_arrhol[$row['cal_date']] = array();
				}
				$this->_arrhol[$row['cal_date']][] = $row;
			}
		}
	}
	private function GetRegistrationDate()
	{
		$r = $this->app->db->query("SELECT UNIX_TIMESTAMP(lbr_registerdate) AS lbr_registerdate FROM labour WHERE lbr_id={$this->_user_id};");
		if ($r) {
			if ($row = $r->fetch_assoc()) {
				$this->_regsitration_date = $row['lbr_registerdate'];
			}
		}
	}
	private function GetResignationDate()
	{
		$r = $this->app->db->query("SELECT UNIX_TIMESTAMP(lbr_resigndate) AS lbr_resigndate FROM labour WHERE lbr_id={$this->_user_id};");
		if ($r) {
			if ($row = $r->fetch_assoc()) {
				$this->_resignation_date = $row['lbr_resigndate'];
			}
		}
	}

	/*
	 * function: getAttendaceList
	 * desc: Prepare _arratt array
	 * param: userid(int)= user id, date(str:yyyy-mm-dd)=report date range, filldays(bool)=fill empty days
	 * returns: null
	 **/
	public function getAttendaceList($userid, $dateFrom, $dateTo, $filldays = false, $consider_last_checking = false)
	{
		$currentTimeStamp = date("Y-m-d H:i:s", time());
		;
		$this->_arratt = array();
		$this->_partitionlist = array(0 => array("NaN", "255,255,255", 0));
		$this->_dateFrom = array(0 => null, 1 => null, 2 => null);
		$this->_dateTo = array(0 => null, 1 => null, 2 => null);

		$this->_user_id = (int) $userid;

		$dateFrom = $dateFrom > 0 ? $dateFrom : time();
		$this->_dateFrom[2] = $dateFrom;
		$this->_dateFrom[1] = date("Y-m-d", $dateFrom);
		$this->_dateFrom[0] = date("F ,Y", $dateFrom);

		$dateTo = $dateTo > 0 ? $dateTo : time();
		$this->_dateTo[2] = $dateTo;
		$this->_dateTo[1] = date("Y-m-d", $dateTo);
		$this->_dateTo[0] = date("F ,Y", $dateTo);



		if (
			$ra = $this->app->db->query(
				"SELECT 
				lbr_id, 
				UNIX_TIMESTAMP(IF(ltr_ctime < '{$this->_dateFrom[1]} 00:00:00','{$this->_dateFrom[1]} 00:00:00',ltr_ctime)) AS _tstart,
				UNIX_TIMESTAMP(IF(ltr_otime > '{$this->_dateTo[1]} 23:59:59','{$this->_dateTo[1]} 23:59:59',COALESCE(ltr_otime,'$currentTimeStamp'))) AS _tend,
				ltr_id,
				TIME_TO_SEC(
					TIMEDIFF( 
						IF(ltr_otime > '{$this->_dateTo[1]} 23:59:59','{$this->_dateTo[1]} 23:59:59',COALESCE(ltr_otime,'$currentTimeStamp')),
						IF(ltr_ctime < '{$this->_dateFrom[1]} 00:00:00','{$this->_dateFrom[1]} 00:00:00',ltr_ctime)
					)
				)  AS diff, 
				prt_lbr_perc,prt_color,prt_id,prt_name,comp_name,ptp_name
			FROM
				labour_track
					JOIN labour ON ltr_usr_id = lbr_id 
					JOIN (
						SELECT 
							comp_name, prt_lbr_perc,prt_color,prt_id,prt_name,ptp_name
						FROM 
							`acc_accounts` 
								JOIN companies ON comp_id = prt_company_id
								JOIN `acc_accounttype`  ON prt_type = ptp_id 
						) AS prtcomp ON ltr_prt_id = prtcomp.prt_id
				
			WHERE
				DATE(ltr_ctime) <= '{$this->_dateTo[1]}' AND DATE(COALESCE(ltr_otime,'$currentTimeStamp')) >= '{$this->_dateFrom[1]}'
				AND lbr_id = {$this->_user_id}
			
			;"
			)
		) {
			while ($rowa = $ra->fetch_assoc()) {
				$rowa['_tend'] = (int) $rowa['_tend'];
				$rowa['_tstart'] = (int) $rowa['_tstart'];

				if ($rowa['_tend'] < $rowa['_tstart']) {
					$rowa['_tend'] = $rowa['_tstart'];
				}

				if (!isset($this->_partitionlist[$rowa['prt_id']])) {
					$colorlist = array();
					$rowa['prt_color'] = substr(str_pad($rowa['prt_color'] ?? "000000", 6, "0", STR_PAD_LEFT), 0, 6);
					$colorlist[0] = hexdec($rowa['prt_color'][0] . $rowa['prt_color'][1]);
					$colorlist[1] = hexdec($rowa['prt_color'][2] . $rowa['prt_color'][4]);
					$colorlist[2] = hexdec($rowa['prt_color'][4] . $rowa['prt_color'][5]);
					$this->_partitionlist[$rowa['prt_id']] = array($rowa['comp_name'] . ": " . $rowa['ptp_name'] . ": " . $rowa['prt_name'], implode(",", $colorlist), (float) $rowa['prt_lbr_perc']);
				}

				$month = date("Y-m", $rowa['_tstart']);
				$date = date("Y-m-d", $rowa['_tstart']);

				if (!isset($this->_arratt[$month])) {
					$this->_arratt[$month] = array();
				}
				if (!isset($this->_arratt[$month][$date])) {
					$this->_arratt[$month][$date] = array();
				}

				$this->_arratt[$month][$date][date("His", $rowa['_tstart'])] = array(
					/*0:StartTime*/
					$rowa['_tstart'],
					/*1:FinishTime*/	$rowa['_tend'],
					/*2:OpenRecordID*/	$rowa['ltr_id'],
					/*3:CloseRecordID*/	$rowa['ltr_id'],
					/*4:Sector*/	$rowa['prt_id'],
					/*5:status*/	1,
					/*6:EarylStart*/	$rowa['_tstart'],
					/*7:LateFinish*/	$rowa['_tend'],
					/*8:Comments*/	"",
					/*9:Owner record*/	$rowa['ltr_id']
				);

				$this->FillGaps(
					/*FromTime*/
					$rowa['_tstart'],
					/*ToTime*/
					$rowa['_tend'],
					/*SectorID*/
					$rowa['prt_id'],
					/*OpenerID*/
					$rowa['ltr_id'],
					/*LateFinish*/
					$rowa['_tend'],
					/*EarlyStart*/
					$rowa['_tstart'],
					/*CloserID*/
					$rowa['ltr_id'],
					/*Status*/
					1,
					/*Owner*/
					$rowa['ltr_id'],
					false
				);
			}

			$this->attsort();
			$this->fillsf();
		}
		if ($filldays) {
			$this->attsort();
			$this->filldays();
		}
		$this->attsort();

		if ($this->_dump) {
			$this->dump();
			exit;
		}
		//Get Holidays List
		$this->GetHolidaysList();

		//Get Weekend days List
		$this->GetWeekendList();

		//Get Employee Absent Notices
		$this->GetAbsentWithNotice();

		//Get Date of Registration
		$this->GetRegistrationDate();

		//Get Date of Resign
		$this->GetResignationDate();
	}


	/*
	 * function: formatTime
	 * desc: Fomrated seconds HH:SS
	 * param: time(int) time in seconds
	 * returns: string
	 **/
	public function formatTime(int|float $time)
	{
		return $this->app->formatTime((int) $time, false);

	}

	/*
	 * function: PrintTable
	 * desc: Print out attendance report for pre-analyzed list _arratt @ getAttendaceList
	 * param: strict(bool) print out only provided month, rejecting any records from previous month
	 * returns: null
	 **/
	public function PrintTable()
	{
		foreach ($this->_arratt as $monthk => $monthv) {
			$month_name = null;
			if (preg_match("/^([0-9]{4})-([0-9]{2})$/i", $monthk, $matches)) {
				if (checkdate((int) $matches[2], 1, (int) $matches[1])) {
					$month_name = date("Y, F", mktime(0, 0, 0, (int) $matches[2], 1, (int) $matches[1]));
				}
			}

			$totalmonth = 0;
			$totalactualmonth = 0;
			$workingdate = false;
			echo "<div class=\"btn-set\" style=\"position: sticky;top: calc(162px - var(--gremium-header-toggle));padding-bottom: 10px;z-index: 2;background-color: var(--root-background-color)\"><span class=\"flex\">$month_name</span></div><div>";
			echo "<table class=\"bom-table attendance\"><tbody>";

			foreach ($monthv as $datek => $datev) {
				$workingdate = false;
				$printdate = $datek;
				$printday = $weekday = "";
				if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/i", $datek, $matches)) {
					if (checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
						$workingdate = mktime(0, 0, 0, (int) $matches[2], (int) $matches[3], (int) $matches[1]);
						$printdate = date("d", $workingdate);
						$printday = date("D", $workingdate);
						$weekday = date("w", $workingdate);
					}
				}
				if (!$workingdate) {
					continue;
				}
				$additional_class = array();
				if (isset($this->_weekend[$weekday]) || isset($this->_arrhol[$datek]) || isset($this->_arrabsntnotice[$datek])) {
					$additional_class[] = "weekend";
				}
				if ($this->_regsitration_date && $workingdate < $this->_regsitration_date) {
					$additional_class[] = "outofrange";
				}
				if ($this->_resignation_date && $workingdate >= $this->_resignation_date) {
					$additional_class[] = "outofrange";
				}

				echo "<tr class=\"" . (implode(" ", $additional_class)) . "\">
					<th>$printdate</th>
					<th>$printday</th>";

				if (isset($this->_weekend[$weekday]) || isset($this->_arrhol[$datek])) {
					echo "<th class=\"calendar-point ccode001\"><div>&#xe953;</div><span>";
					if (isset($this->_weekend[$weekday])) {
						echo "Weekend<br />";
					}
					if (isset($this->_arrhol[$datek])) {
						foreach ($this->_arrhol[$datek] as $key => $value) {
							echo $value['cal_details'] . "<br />";
						}
					}
					echo "</span></th>";
				} else {
					echo "<th></th>";
				}


				if (isset($this->_arrabsntnotice[$datek])) {
					echo "<th class=\"calendar-point ccode002\"><div>&#xe953;</div><span>";
					foreach ($this->_arrabsntnotice[$datek] as $key => $value) {
						echo "<div class=\"calendar-absent-table\">
								<div><span>Type:</span><span>{$value['type']}</span></div>
								<div><span>Issued By:</span><span>{$value['issuer']}</span></div>
								<div><span>Approved By:</span><span>" . (is_null($value['approval_id']) ? "<i style=\"color:#f03;\">Not approved</i>" : "<i style=\"color:#06c\">" . $value['approval_name'] . "</i>") . "</span></div>
								<div><span>Starts:</span><span>{$value['starts']}</span></div>
								<div><span>Period:</span><span>{$value['period']} Day(s)</span></div>
								<div><span>Comments:</span><span>{$value['comments']}</span></div>
							</div>";
					}
					echo "</span></th>";
				} else {
					echo "<th></th>";
				}


				echo "<td class=\"css_attendanceBlocks\">";
				$totalminutes = 0;
				$totalactualminutes = 0;

				foreach ($datev as $k => $v) {
					$diff = ($v[1] - $v[0]);
					$actdiff = ($v[1] - $v[0]) * $this->_partitionlist[$v[4]][2];
					$per = round($diff / 1440 * 100 / 60, 3);
					if ($v[2] != 0) {
						$totalminutes += $diff;
						$totalmonth += $diff;
						$totalactualminutes += $actdiff;
						$totalactualmonth += $actdiff;
					}
					$majortot = $this->formatTime($v[7] - (isset($v[10]) ? $v[10] : $v[6]));
					$diff = $this->formatTime($diff);
					$actual = $this->formatTime(($v[7] - (isset($v[10]) ? $v[10] : $v[6])) * $this->_partitionlist[$v[4]][2]);

					echo "<div 
							style=\"width:{$per}%;" . ($v[4] != 0 ? "background-color:rgba(" . $this->_partitionlist[$v[4]][1] . ",1);" : "") . "\"
							" . ($v[2] == 0 ? " class=\"empty\" " : "") . "
							data-clsid=\"{$v[2]}\"
							data-clsprt=\"{$this->_partitionlist[$v[4]][0]}\"
							data-clsstr=\"" . date("Y-m-d H:i", (isset($v[10]) ? $v[10] : $v[6])) . "\"
							data-clsfin=\"" . date("Y-m-d H:i", $v[7]) . "\"
							data-clstot=\"{$majortot}\"
							data-clscolor=\"" . ($v[4] != 0 ? $this->_partitionlist[$v[4]][1] : "") . "\"
							data-actual=\"$actual\"
							data-mainowner=\"{$v[9]}\"
							>" . (($v[4] != 0 && $v[3] == 0) ? "<div class=\"inprog\" style=\"color:rgba(" . $this->_partitionlist[$v[4]][1] . ",1);\"></div>" : "") . "</div>";
				}
				if ($totalminutes == 0) {
					$totalminutes = "-";
				} else {
					$difference_between_actualtotal = ($totalminutes) - abs($totalactualminutes);
					$totalminutes = $this->formatTime($totalminutes);
					$totalactualminutes = $this->formatTime($totalactualminutes);
					$difference_between_actualtotal = $this->formatTime($difference_between_actualtotal);
					$totalminutes = $totalminutes; //"$difference_between_actualtotal / $totalactualminutes | $totalminutes";
				}
				echo "</td><th class=\"details\">$totalminutes</th></tr>";
			}
			echo "";
			$totaldifference = $this->formatTime($totalmonth - abs($totalactualmonth));
			$totalmonth = $this->formatTime($totalmonth);
			$totalactualmonth = $this->formatTime($totalactualmonth);
			echo "</tbody></table></div>";
			echo "<div class=\"attendance-monthfooter\">$totaldifference / $totalactualmonth | $totalmonth</div>";
		}
	}
}