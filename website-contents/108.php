<?php
include_once("admin/class/person.php");

if(isset($_GET['id'])){
	if($r=$sql->query("
		SELECT
			_up.usr_id,_up.usr_username,_up.usr_firstname,usr_birthdate,
			_up.usr_lastname,_up.usr_phone_list,_up.per_title,
			_up.usr_attrib_i2,
			gnd_id,gnd_name,
			up_id,
			lbr_registerdate,
			lsf_id,lsf_name,
			lty_id,lty_name,lsc_name,
			ldn_id,ldn_name,
			UNIX_TIMESTAMP(lbr_resigndate) AS lbr_resigndate
		FROM
			labour
				JOIN (
					SELECT 
						usr_images_list,usr_id,usr_username,usr_firstname,usr_lastname,usr_phone_list,per_title,usr_attrib_i2,usr_attrib_i3,usr_gender,usr_birthdate
					FROM
						users LEFT JOIN permissions ON usr_privileges = per_id
					) AS _up ON _up.usr_id=lbr_id
				LEFT JOIN labour_shifts ON lbr_shift=lsf_id
				LEFT JOIN uploads ON up_pagefile=".Pool::FILE['Person']['Photo']." AND up_rel=lbr_id
				LEFT JOIN (
					SELECT
						lty_id,lty_name,lsc_name
					FROM
						labour_type JOIN labour_section ON lsc_id=lty_section
				) AS _labourtype ON lty_id=lbr_type
				
				LEFT JOIN gender ON gnd_id=_up.usr_gender
				LEFT JOIN labour_residentail ON ldn_id=lbr_residential
		WHERE
			lbr_id=".((int)$_GET['id'])."".($USER->info->id!=1?" AND usr_id!=1 ":"").";")){
		if($row=$sql->fetch_assoc($r)){
			echo "<table class=\"bom-table hover\" id=\"rowtable\"><tbody>";
			
			echo "<tr><th class=\"special\">Name</th><td width=\"100%\">{$row['usr_firstname']} {$row['usr_lastname']}";
			echo !is_null($row['lbr_resigndate'])?" <span class=\"op-error\" style=\"color:#f03\"><span style=\"display:inline-block\"></span>Employee suspended on ".date("Y-m-d",$row['lbr_resigndate'])."</span>":"";
			echo "</td>";
			echo "<th rowspan=\"9\" style=\"border-left:solid 1px #ccc;min-width:320px;\">";
			if(is_null($row['up_id'])){
				echo "<div style=\"width:320px;height:240px;max-width:320px;max-height:240px;\">No photo provided</div>";
			}else{
				
				echo "<img src=\"".$tables->pagefile_info(187,null,"directory")."?id={$row['up_id']}&pr=t\" style=\"width:100%\" />";
				
			}
			echo "</th></tr>";
			echo "<tr><th>Serial</th><td width=\"100%\"><span><span style=\"display:inline-block\"></span></span></td></tr>";
			echo "<tr><th>Registration date</th><td width=\"100%\">{$row['lbr_registerdate']}</td></tr>";
			echo "<tr><th>Gender</th><td width=\"100%\">".($row['gnd_id']==null?"":$row['gnd_name'])."</td></tr>";

			echo "<tr><th>Birhtdate</th><td width=\"100%\">".(is_null($row['usr_birthdate'])?"":$row['usr_birthdate'])."</td></tr>";

			echo "<tr><th>Phone Number</th><td width=\"100%\">{$row['usr_phone_list']}</td></tr>";
			echo "<tr><th>Residence</th><td width=\"100%\">".($row['ldn_id']==null?"":$row['ldn_name'])."</td></tr>";
			
			echo "<tr><th>Job</th><td width=\"100%\">{$row['lsc_name']} - {$row['lty_name']}</td></tr>";
			echo "<tr><th>Shift</th><td width=\"100%\">".($row['lsf_id']==null?"":$row['lsf_name'])."</td></tr>";
			if(isset($_GET['ajax'])){}
			echo "<tr><th colspan=\"3\"><div class=\"btn-set\" style=\"justify-content:center\"><button id=\"jQclosePopup\" onclick=\"popup.hide();\" type=\"button\">&nbsp;&nbsp;Close&nbsp;&nbsp;</button></div></th></tr>";
			echo "</tbody></table></form>";
		}else{
			echo "Employee Not found";
		}
		
	}echo $sql->error();
}
































































?>