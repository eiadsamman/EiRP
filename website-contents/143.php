<?php
if ($_SHIFT) {
	$holiday = false;
	$weekend = false;
	//Check shift start if its on holiday day
	$r = $app->db->query(
		"SELECT
			holicow AS cal_date,cal_details
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
									'" . date("Y", $_SHIFT['start']) . "','-',MONTH(cal_date),'-',DAY(cal_date)
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
			holicow = '" . date("Y-m-d", $_SHIFT['start']) . "'"
	);
	if ($r && $row = $r->fetch_assoc()) {
		$holiday = $row['cal_details'];
	}


	$r = $app->db->query(
		"SELECT 
			cwk_pointer 
		FROM 
			calendar_weekends 
		WHERE 
			cwk_status=1 AND cwk_pointer=" . date("w", $_SHIFT['start']) . "
	"
	);
	if ($r && $r->num_rows > 0) {
		$weekend = true;
	}

	if ($holiday) {
		echo "<div class=\"btn-set\"><span>$holiday</span></div><br />";
		exit;
	}
	if ($weekend) {
		echo "<div class=\"btn-set\"><span>" . date("Y-m-d") . " Weekend</span></div>";
		exit;
	}


	$pagefile_display = $tables->pagefile_info(108, null, "directory");
	echo "<table class=\"bom-table hover\"><thead>
	<tr><td>ID</td><td>Employee Name</td><td>Execused</td><td>Last attendance</td></tr>
	</thead><tbody>";
	$r = $app->db->query(
		"SELECT 
		lbr_id ,lbr_shift,_t.ltr_ctime,UNIX_TIMESTAMP(_last.ltr_ctime) AS lastseen,absdays,abs_id,lbr_abs_start_date,lbr_abs_days,
		CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS empname
	FROM 
		labour 
		JOIN users ON usr_id=lbr_id
		LEFT JOIN
		(
			SELECT 
				ltr_ctime,ltr_usr_id
			FROM 
				labour_track 
			WHERE 
				ltr_type=1 
				AND ltr_ctime >= FROM_UNIXTIME(" . ($_SHIFT['start'] - (30 * 60)) . ")
				AND ltr_ctime <= FROM_UNIXTIME(" . ($_SHIFT['finish'] + (30 * 60)) . ")

			GROUP BY
				ltr_usr_id,ltr_ctime
			ORDER BY
				ltr_ctime
		) AS _t ON _t.ltr_usr_id = lbr_id 
		
		LEFT JOIN
		(
			SELECT 
				MAX(ltr_ctime) AS ltr_ctime,ltr_usr_id 
			FROM 
				labour_track 
			WHERE 
				ltr_type=1 
			GROUP BY 
				ltr_usr_id
			
		) AS _last ON _last.ltr_usr_id = lbr_id 
		
		
		
		LEFT JOIN (
			SELECT
				absdays,lbr_abs_lbr_id,main.lbr_abs_id AS abs_id,lbr_abs_start_date,lbr_abs_days
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
			WHERE
				absdays = '" . date("Y-m-d", $_SHIFT['start']) . "'
				
		) AS _s ON _s.lbr_abs_lbr_id=lbr_id 
		
	WHERE 
		lbr_resigndate IS NULL AND lbr_id!=1 
		AND lbr_shift={$_SHIFT['id']}
	GROUP BY
		lbr_id
	HAVING ltr_ctime IS NULL
	ORDER BY 
		lastseen
	;"
	);

	//echo $app->db->error;
	if ($r) {
		echo "<div class=\"btn-set\"><span>Total absences</span><input type=\"text\" readonly=\"readonly\" value=\"" . $r->num_rows . "\" style=\"width:100px;text-align:center;\" /></div><br />";
		while ($row = $r->fetch_assoc()) {
			if (!is_null($row['lastseen'])) {
				$dStart = new DateTime(date("Y-m-d", $_SHIFT['start']));

				$dEnd  = new DateTime(date("Y-m-d", $row['lastseen']));
				$dDiff = $dStart->diff($dEnd);

				echo "<tr>
				<td>{$row['lbr_id']}</td>
				<td class=\"popupInfo\"><a href=\"$pagefile_display?id={$row['lbr_id']}\">{$row['empname']}</a></td>
				
				<td>" . (is_null($row['abs_id']) ? "" : "{$row['abs_id']}: {$row['lbr_abs_start_date']} - {$row['lbr_abs_days']} day(s)") . "</td>
				<td>" . date("Y-m-d H:i", $row['lastseen']) . " - {$dDiff->days} day(s)</td>
				</tr>";
			}
		}
	}
	echo "</tbody></table>";
} else {
	echo "<div class=\"btn-set\"><span>Current time is out of shifts</span></div>";
}


?>
<script>
	$(document).ready(function(e) {

		$(".popupInfo > a").on('click', function(e) {
			e.preventDefault();
			var $this = $(this);
			popup.show("Loading");

			var $ajax = $.ajax({
				type: "POST",
				url: $this.attr("href") + "&ajax",
				data: ""
			}).done(function(data) {
				popup.show(data);
				$("#jQclosePopup").focus();
			});
			return false;
		});

	});
</script>