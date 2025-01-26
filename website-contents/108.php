<?php

if (isset($_GET['id'])) {
	if (
		$r = $app->db->query(
			"SELECT
			usr_id,usr_username,usr_firstname,usr_birthdate,
			usr_lastname,usr_phone_list,per_title,
			
			gnd_id,gnd_name,
			up_id,
			usr_registerdate,
			lsf_id,lsf_name,
			lty_id,lty_name,lsc_name,
			ldn_id,ldn_name,
			UNIX_TIMESTAMP(lbr_resigndate) AS lbr_resigndate
		FROM
			labour
				JOIN users ON usr_id = lbr_id
				JOIN permissions ON per_id = usr_privileges
				LEFT JOIN labour_shifts ON lbr_shift = lsf_id
				LEFT JOIN uploads ON up_pagefile=" . \System\Lib\Upload\Type::HrPerson->value . " AND up_rel=lbr_id
				LEFT JOIN (
					SELECT
						lty_id,lty_name,lsc_name
					FROM
						labour_type JOIN labour_section ON lsc_id=lty_section
				) AS _labourtype ON lty_id = usr_jobtitle
				
				LEFT JOIN gender ON gnd_id = usr_gender
				LEFT JOIN labour_residentail ON ldn_id=lbr_residential
		WHERE
			lbr_id=" . ((int) $_GET['id']) . "" . ($app->user->info->id != 1 ? " AND usr_id != 1 " : "") . ";"
		)
	) {
		if ($row = $r->fetch_assoc()) {
			echo "<table class=\"hover\" id=\"rowtable\"><tbody>";

			echo "<tr><th class=\"special\">Name</th><td width=\"100%\">{$row['usr_firstname']} {$row['usr_lastname']}";
			echo !is_null($row['lbr_resigndate']) ? " <span class=\"op-error\" style=\"color:#f03\"><span style=\"display:inline-block\"></span>Employee suspended on " . date("Y-m-d", $row['lbr_resigndate']) . "</span>" : "";
			echo "</td>";
			echo "<th rowspan=\"9\" style=\"border-left:solid 1px #ccc;min-width:320px;\">";
			if (is_null($row['up_id'])) {
				echo "<div style=\"width:320px;height:240px;max-width:320px;max-height:240px;\">No photo provided</div>";
			} else {

				echo "<img src=\"" . $fs(187)->dir . "?id={$row['up_id']}&pr=t\" style=\"width:100%\" />";
			}
			echo "</th></tr>";
			echo "<tr><th>Serial</th><td width=\"100%\"><span><span style=\"display:inline-block\"></span></span></td></tr>";
			echo "<tr><th>Registration date</th><td width=\"100%\">{$row['usr_registerdate']}</td></tr>";
			echo "<tr><th>Gender</th><td width=\"100%\">" . ($row['gnd_id'] == null ? "" : $row['gnd_name']) . "</td></tr>";

			echo "<tr><th>Birhtdate</th><td width=\"100%\">" . (is_null($row['usr_birthdate']) ? "" : $row['usr_birthdate']) . "</td></tr>";

			echo "<tr><th>Phone Number</th><td width=\"100%\">{$row['usr_phone_list']}</td></tr>";
			echo "<tr><th>Residence</th><td width=\"100%\">" . ($row['ldn_id'] == null ? "" : $row['ldn_name']) . "</td></tr>";

			echo "<tr><th>Job</th><td width=\"100%\">{$row['lsc_name']} - {$row['lty_name']}</td></tr>";
			echo "<tr><th>Shift</th><td width=\"100%\">" . ($row['lsf_id'] == null ? "" : $row['lsf_name']) . "</td></tr>";

			echo "<tr><th colspan=\"3\"><div class=\"btn-set\" style=\"justify-content:center\"><button id=\"jQclosePopup\" onclick=\"popup.close();\" type=\"button\">&nbsp;&nbsp;Close&nbsp;&nbsp;</button></div></th></tr>";
			echo "</tbody></table></form>";
		} else {
			echo "Employee Not found";
		}
	}
}
