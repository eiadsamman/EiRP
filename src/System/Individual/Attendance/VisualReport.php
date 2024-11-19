<?php

declare(strict_types=1);

namespace System\Individual\Attendance;

/**
 * v2.18.11 181114
 */

class AttendaceUnit
{
	public ?\DateTimeImmutable $startTime = null;
	public ?\DateTimeImmutable $endTime = null;
	public int $openRecordId = 0;
	public int $closeRecordId = 0;
	public int $sector = 0;
	public int $status = 0;
	public ?\DateTimeImmutable $earlyStart = null;
	public ?\DateTimeImmutable $lateFinish = null;
	public ?string $comments = null;
	public ?int $ownerRecordId = null;
	public ?int $trimStart = null;

}
class VisualReport
{

	protected \System\App $app;
	private bool $_dump = false;
	private $_weekend = array();
	private $_arratt = array();
	private $_arrhol = array();
	private $_regsitration_date = null;
	private $_resignation_date = null;
	private $_arrabsntnotice = array();
	private $_partitionlist = array(0 => array("NaN", "255,255,255", 0));
	private $_user_id = null;
	private \DateTimeImmutable $_dateFrom;
	private \DateTimeImmutable $_dateTo;

	public function __construct(\System\App &$app)
	{
		$this->app = $app;
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
					echo "\t\t\tOpnID:\t" . $v->openRecordId . "\t\t\tColser ID:\t" . $v->closeRecordId . "\n";
					echo "\t\t\tStart:\t" . ((int) $v->startTime != null ? $v->startTime->format("Y-m-d H:i:s") : "-") . "\tEarly Start:\t" . ($v->earlyStart != null ? $v->earlyStart->format("Y-m-d H:i:s") : "-") . "\n";
					echo "\t\t\tClose:\t" . ((int) $v->endTime != null ? $v->endTime->format("Y-m-d H:i:s") : "-") . "\tLate Finish:\t" . ($v->lateFinish != null ? $v->lateFinish->format("Y-m-d H:i:s") : "-") . "\n";
					echo "\t\t\tStatu:\t" . $v->status . "\t\t\t<span style=\"color:#f03;font-weight:bold\">" . (isset($v->comments) ? $v->comments : "") . "</span>\n";
				}
			}
		}
		echo "</pre>";
	}

	private function FillGaps(AttendaceUnit $unit)
	{
		$cnt       = 0;
		$safeRange = 30;
		$current   = clone $unit->startTime;

		if ($current->diff($unit->endTime)->days == 0)
			return;

		while (!$current->diff($unit->endTime)->invert && $current->diff($unit->endTime)->days >= 0 && $cnt <= $safeRange) {
			$m = $current->format("Y-m");
			$d = $current->format("Y-m-d");
			if (!isset($this->_arratt[$m])) {
				$this->_arratt[$m] = array();
			}

			if (!isset($this->_arratt[$m][$d])) {
				$this->_arratt[$m][$d] = array();
			}

			$time = $current->format("His");

			$au                = new AttendaceUnit();
			$au->startTime     = clone $unit->startTime;
			$au->endTime       = null;
			$au->openRecordId  = $unit->openRecordId;
			$au->closeRecordId = $unit->closeRecordId;
			$au->sector        = $unit->sector;
			$au->status        = $unit->status;
			$au->earlyStart    = clone $unit->earlyStart;
			$au->lateFinish    = clone $unit->lateFinish;
			$au->ownerRecordId = $unit->ownerRecordId;

			$this->_arratt[$m][$d][$time] = $au;

			if ($current->diff($unit->endTime)->days > 0) {
				$endOfDay = new \DateTimeImmutable();
				$endOfDay = $current->setTime(23, 59, 59, 999999);

				$this->_arratt[$m][$d][$time]->endTime = $endOfDay;

				if ($cnt != 0) {
					$startOfDay = new \DateTimeImmutable();
					$startOfDay = $current->setTime(0, 0, 0, 0);

					$this->_arratt[$m][$d][$time]->startTime = $startOfDay;
				}

			} else {

				$mod = new \DateTimeImmutable();
				$mod = $current->setTime(0, 0, 0, 0);

				$this->_arratt[$m][$d][$time]->startTime = $mod;
				$this->_arratt[$m][$d][$time]->endTime   = clone $unit->endTime;
			}


			$current = $current->modify("+1 day");
			$current = $current->setTime(0, 0, 0);

			$cnt++;
		}
	}


	private function filldays()
	{
		$currentTime = new \DateTimeImmutable();
		$currentTime = clone $this->_dateFrom;

		while ($currentTime <= $this->_dateTo) {
			$scurrent = new \DateTimeImmutable();
			$scurrent = $currentTime->setTime(0, 0, 0, 0);

			$fcurrent = new \DateTimeImmutable();
			$fcurrent = $currentTime->setTime(23, 59, 59, 999999);

			if (!isset($this->_arratt[$scurrent->format("Y-m")][$scurrent->format("Y-m-d")])) {
				$this->_arratt[$scurrent->format("Y-m")][$scurrent->format("Y-m-d")] = array();

				$au            = new AttendaceUnit();
				$au->startTime = $scurrent;
				$au->endTime   = $fcurrent;

				$this->_arratt[$scurrent->format("Y-m")][$scurrent->format("Y-m-d")][$scurrent->format("His")] = $au;
			}
			$currentTime = $currentTime->modify("+1 day");
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
		foreach ($this->_arratt as $monthk => $monthv) {
			foreach ($monthv as $datek => $datev) {
				$temp = new \DateTimeImmutable();
				$temp = null;
				foreach ($datev as $v) {
					if (is_null($temp)) {
						if ($v->startTime->format("His") != "000000") {
							$au            = new AttendaceUnit();
							$au->startTime = $v->startTime->setTime(0, 0, 0);
							$au->endTime   = $v->startTime;

							$this->_arratt[$monthk][$datek]['000000'] = $au;
						}
						$temp = $v->endTime;
					} else {

						if ($temp < $v->startTime) {
							$au            = new AttendaceUnit();
							$au->startTime = $temp;
							$au->endTime   = $v->startTime;
							$au->comments  = "Middle earth!";

							$this->_arratt[$monthk][$datek][$temp->format("His")] = $au;
						}
						$temp = $v->endTime;
					}
				}
				if ($temp->format("His") != "235959") {
					$carry = new \DateTimeImmutable();
					$carry = $temp->setTime(23, 59, 59, 999999);

					$au            = new AttendaceUnit();
					$au->startTime = $temp;
					$au->endTime   = $carry;
					$au->comments  = "Closer";

					$this->_arratt[$monthk][$datek][$temp->format("His")] = $au;

				}
			}
		}
	}


	private function GetAbsentWithNotice()
	{
		$q =
			"SELECT
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
			absdays >= '" . $this->_dateFrom->format("Y-m-d 00:00:00") . "' AND absdays < '" . $this->_dateTo->format("Y-m-d 23:59:59") . "'
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
										'" . $this->_dateFrom->format("Y") . "','-',MONTH(cal_date),'-',DAY(cal_date)
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
				holicow >= '" . $this->_dateFrom->format("Y-m-d 00:00:00") . "' AND holicow < '" . $this->_dateTo->format("Y-m-d 23:59:59") . "'";

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
		$r = $this->app->db->query("SELECT UNIX_TIMESTAMP(usr_registerdate) AS usr_registerdate FROM users WHERE usr_id={$this->_user_id};");
		if ($r) {
			if ($row = $r->fetch_assoc()) {
				$this->_regsitration_date = $row['usr_registerdate'];
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





	public function getAttendaceList($userid, $dateFrom, $dateTo, $filldays = false)
	{
		$currentTimeStamp = new \DateTimeImmutable();
		$currentTimeStamp = date("Y-m-d H:i:s", time());

		$this->_arratt        = array();
		$this->_partitionlist = array(0 => array("NaN", "255,255,255", 0));
		$this->_user_id       = (int) $userid;

		$this->_dateFrom = new \DateTimeImmutable(date("Y-m-d", $dateFrom));
		$this->_dateTo   = new \DateTimeImmutable(date("Y-m-d", $dateTo));


		$dateFromFormat = $this->_dateFrom->format("Y-m-d");
		$dateToFormat   = $this->_dateTo->format("Y-m-d");


		$q = "SELECT 
				lbr_id, 
				(IF(ltr_ctime < '{$dateFromFormat} 00:00:00','{$dateFromFormat} 00:00:00',ltr_ctime)) AS _tstart,
				(IF(ltr_otime > '{$dateToFormat} 23:59:59','{$dateToFormat} 23:59:59',COALESCE(ltr_otime,'$currentTimeStamp'))) AS _tend,
				ltr_id,
				TIME_TO_SEC(
					TIMEDIFF( 
						IF(ltr_otime > '{$dateToFormat} 23:59:59','{$dateToFormat} 23:59:59',COALESCE(ltr_otime,'$currentTimeStamp')),
						IF(ltr_ctime < '{$dateFromFormat} 00:00:00','{$dateFromFormat} 00:00:00',ltr_ctime)
					)
				)  AS diff, 
				prt_lbr_perc,prt_color,prt_id,prt_name,comp_name
			FROM
				labour_track
					JOIN labour ON ltr_usr_id = lbr_id 
					JOIN (SELECT comp_name, prt_lbr_perc,prt_color,prt_id,prt_name FROM acc_accounts JOIN companies ON comp_id = prt_company_id) AS prtcomp ON ltr_prt_id = prtcomp.prt_id
			WHERE
				DATE(ltr_ctime) <= '{$dateToFormat}' AND DATE(COALESCE(ltr_otime,'$currentTimeStamp')) >= '{$dateFromFormat}'
				AND lbr_id = {$this->_user_id}
			;";

		//$this->app->errorHandler->customError($q);

		if (
			$ra = $this->app->db->query($q)
		) {
			while ($rowa = $ra->fetch_assoc()) {
				$endTime = new \DateTimeImmutable($rowa['_tend']);
				$strTime = new \DateTimeImmutable($rowa['_tstart']);


				if ($endTime < $strTime) {
					$endTime = $strTime;
				}

				if (!isset($this->_partitionlist[$rowa['prt_id']])) {
					$colorlist                             = array();
					$rowa['prt_color']                     = substr(str_pad($rowa['prt_color'] ?? "000000", 6, "0", STR_PAD_LEFT), 0, 6);
					$colorlist[0]                          = hexdec($rowa['prt_color'][0] . $rowa['prt_color'][1]);
					$colorlist[1]                          = hexdec($rowa['prt_color'][2] . $rowa['prt_color'][4]);
					$colorlist[2]                          = hexdec($rowa['prt_color'][4] . $rowa['prt_color'][5]);
					$this->_partitionlist[$rowa['prt_id']] = array($rowa['comp_name'] . ": " . $rowa['prt_name'], implode(",", $colorlist), (float) $rowa['prt_lbr_perc']);
				}

				$month = $strTime->format("Y-m");
				$date  = $strTime->format("Y-m-d");

				if (!isset($this->_arratt[$month])) {
					$this->_arratt[$month] = array();
				}
				if (!isset($this->_arratt[$month][$date])) {
					$this->_arratt[$month][$date] = array();
				}


				$au                = new AttendaceUnit();
				$au->startTime     = $strTime;
				$au->endTime       = $endTime;
				$au->openRecordId  = (int) $rowa['ltr_id'];
				$au->closeRecordId = (int) $rowa['ltr_id'];
				$au->sector        = (int) $rowa['prt_id'];
				$au->status        = 1;
				$au->earlyStart    = $strTime;
				$au->lateFinish    = $endTime;
				$au->comments      = "";
				$au->ownerRecordId = (int) $rowa['ltr_id'];

				$this->_arratt[$month][$date][$strTime->format("His")] = $au;


				$this->FillGaps($au);
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

		$this->GetHolidaysList();
		$this->GetWeekendList();
		$this->GetAbsentWithNotice();
		$this->GetRegistrationDate();
		$this->GetResignationDate();
	}

	public function formatTime(int|float $time)
	{
		return $this->app->formatTime((int) $time, false);
	}

	public function PrintTable()
	{
		foreach ($this->_arratt as $monthk => $monthv) {
			$month_name = null;
			if (preg_match("/^([0-9]{4})-([0-9]{2})$/i", $monthk, $matches)) {
				if (checkdate((int) $matches[2], 1, (int) $matches[1])) {
					$month_name = date("Y, F", mktime(0, 0, 0, (int) $matches[2], 1, (int) $matches[1]));
				}
			}

			$totalmonth       = 0;
			$totalactualmonth = 0;
			$workingdate      = false;
			echo "<div class=\"btn-set\" 
				style=\"position: sticky;top: calc(144px - var(--gremium-header-toggle));padding: 10px 0px;z-index: 2;
				background-color: var(--root-ribbon-menu-background-color);margin:0px -1px\"><span class=\"flex\" style=\"color: var(--root-font-lightcolor);\">$month_name</span></div><div>";
			echo "<table class=\"attendance\"><tbody>";

			foreach ($monthv as $datek => $datev) {
				$workingdate = false;
				$printdate   = $datek;
				$printday    = $weekday = "";
				if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/i", $datek, $matches)) {
					if (checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
						$workingdate = mktime(0, 0, 0, (int) $matches[2], (int) $matches[3], (int) $matches[1]);
						$printdate   = date("d", $workingdate);
						$printday    = date("D", $workingdate);
						$weekday     = date("w", $workingdate);
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
				$totalminutes       = 0;
				$totalactualminutes = 0;

				foreach ($datev as $v) {
					$diff = ($v->endTime->getTimestamp() - $v->startTime->getTimestamp());

					$actdiff  = ($v->endTime->getTimestamp() - $v->startTime->getTimestamp()) * $this->_partitionlist[$v->sector][2];
					$majortot = $this->formatTime(0);
					$actual   = $this->formatTime(0);

					$per = round($diff / 1440 * 100 / 60, 3);

					if ($v->openRecordId != 0) {
						$totalminutes += $diff;
						$totalmonth += $diff;
						$totalactualminutes += $actdiff;
						$totalactualmonth += $actdiff;
					}


					if ($v->lateFinish && $v->earlyStart) {
						$majortot = $this->formatTime($v->lateFinish->getTimestamp() - $v->earlyStart->getTimestamp());
						$actual   = $this->formatTime(($v->lateFinish->getTimestamp() - $v->earlyStart->getTimestamp()) * $this->_partitionlist[$v->sector][2]);
					}

					echo "<div 
							style=\"width:{$per}%;" . ($v->sector != 0 ? "background-color:rgba(" . $this->_partitionlist[$v->sector][1] . ",1);" : "") . "\"
							" . ($v->openRecordId == 0 ? " class=\"empty\" " : "") . "
							data-clsid=\"{$v->openRecordId}\"
							data-clsprt=\"{$this->_partitionlist[$v->sector][0]}\"
							data-clsstr=\"" . ($v->earlyStart ? $v->earlyStart->format("Y-m-d H:i") : "") . "\"
							data-clsfin=\"" . ($v->lateFinish ? $v->lateFinish->format("Y-m-d H:i") : "") . "\"
							data-clstot=\"{$majortot}\"
							data-clscolor=\"" . ($v->sector != 0 ? $this->_partitionlist[$v->sector][1] : "") . "\"
							data-actual=\"$actual\"
							data-mainowner=\"{$v->ownerRecordId}\"
							> &nbsp;" . (($v->sector != 0 && $v->closeRecordId == 0) ? "<div class=\"inprog\" style=\"color:rgba(" . $this->_partitionlist[$v->sector][1] . ",1);\"></div>" : "") . "</div>";
				}
				if ($totalminutes == 0) {
					$totalminutes = "-";
				} else {
					$difference_between_actualtotal = ($totalminutes) - abs($totalactualminutes);
					$totalminutes                   = $this->formatTime($totalminutes);
					$totalactualminutes             = $this->formatTime($totalactualminutes);
					$difference_between_actualtotal = $this->formatTime($difference_between_actualtotal);
					$totalminutes                   = $totalminutes == "23:59" ? "24:00" : $totalminutes;
				}
				echo "</td><th class=\"details\">$totalminutes</th></tr>";
			}
			echo "";
			$totaldifference  = $this->formatTime($totalmonth - abs($totalactualmonth));
			$totalmonth       = $this->formatTime($totalmonth);
			$totalactualmonth = $this->formatTime($totalactualmonth);
			echo "</tbody></table></div>";
			echo "<div class=\"attendance-monthfooter\">$totaldifference / $totalactualmonth | $totalmonth</div>";
		}
	}
}