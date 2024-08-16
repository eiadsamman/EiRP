<?php
function replaceARABIC($str)
{
	$str = str_replace(["أ", "إ", "آ"], "[أإاآ]+", $str);
	$str = str_replace(["ة", "ه"], "[ةه]+", $str);
	$str = str_replace(["ى", "ي"], "[يى]+", $str);
	return $str;
}

$perpage   = 50;
$arr_feild = array(
	"gender" => array("Gender", "G000", "usr_gender", "int"),
	"job" => array("Job", "E002A", "usr_jobtitle", "int"),
	"payment_method" => array("Payment Method", "SALARY_PAYMENT_METHOD", "lbr_payment_method", "int"),
	"work_time" => array("Working Time", "WORKING_TIMES", "lbr_workingtimes", "int"),
	"shift" => array("Shift", "E003", "lbr_shift", "int"),
	"residence" => array("Residence", "E004", "lbr_residential", "int"),
	"transportation" => array("Transportation", "TRANSPORTATION", "lbr_transportation", "int"),
	"resigndate" => array("Resign Date", "DATE", "lbr_resigndate", "string"),
);
if (isset($_POST['bulkeditorsubmit']) && $fs(154)->permission->edit) {
	$q     = "UPDATE users,labour,user_employeeselection SET ";
	$cnt   = 0;
	$smart = "";
	foreach ($arr_feild as $k => $v) {
		if (isset($_POST[$k])) {
			$cnt++;
			if ($v[3] == "int") {
				$_POST[$k] = (int) $_POST[$k];
				if ($_POST[$k] == 0) {
					$q .= $smart . $v[2] . "= NULL ";
				} else {
					$q .= $smart . $v[2] . "=" . (int) $_POST[$k];
				}
			} elseif ($v[3] == "string") {
				$_POST[$k] = trim($_POST[$k]);
				if ($_POST[$k] == "" || $_POST[$k] == "0") {
					$q .= $smart . $v[2] . "= NULL ";
				} else {
					$q .= $smart . $v[2] . "='" . addslashes($_POST[$k]) . "' ";
				}
			}
			$smart = ",";
		}
	}
	$q .= " WHERE usr_id=sel_usremp_emp_id AND lbr_id=sel_usremp_emp_id AND sel_usremp_usr_id={$app->user->info->id};";
	if ($cnt > 0) {
		if ($app->db->query($q)) {
			$app->db->query("DELETE FROM user_employeeselection WHERE sel_usremp_usr_id={$app->user->info->id};");
			echo "success";
		} else {
			echo "fail";
		}
	} else {
		echo "empty";
	}
	exit;
}
if (isset($_POST['bulkeditorform'])) {
	if (!$fs(154)->permission->edit) {
		echo "Permissions denided!<br /><br /><div class=\"btn-set\" style=\"justify-content:center\"><button type=\"button\" id=\"jQcancel\">Cancel</button></div>";
		exit;
	}
	$selectioncount = 0;
	if ($rsel = $app->db->query("SELECT COUNT(sel_usremp_emp_id) AS selectioncount FROM user_employeeselection JOIN labour ON lbr_id = sel_usremp_emp_id  AND lbr_resigndate IS NULL WHERE sel_usremp_usr_id={$app->user->info->id}")) {
		if ($selectioncount = $rsel->fetch_assoc()) {
			$selectioncount = $selectioncount['selectioncount'];
		}
	}
	echo "<table><thead><tr class=\"special\"><td colspan=\"2\">Bulk editor</td></tr></thead><tbody>";
	foreach ($arr_feild as $k => $v) {
		echo "<tr>
			<td style=\"min-width:120px;\">{$v[0]}</td>
			<td>
				<div class=\"btn-set\" style=\"justify-content:center\">
					<label class=\"btn-checkbox\"><input type=\"checkbox\" class=\"jQalter_checkbox\" id=\"jQalter_{$k}\" /><span></span></label>
					<input style=\"min-width:300px;\" class=\"flex jQBulkSLOField\" id=\"jQbulk_{$k}\" data-slo=\"{$v[1]}\" disabled=\"disabled\" type=\"text\" />
				</div>
			</td>
		</tr>";
	}
	echo "<tr><td colspan=\"2\"><div class=\"btn-set\" style=\"justify-content:center\"><button type=\"button\" id=\"jQsubmit_bulk\">Edit `{$selectioncount}` employees</button><button type=\"button\" id=\"jQcancel\">Cancel</button></div></td></tr>";
	echo "</tbody></table>";
	exit;
}

if (isset($_POST['employeecheck'])) {
	$id      = (int) $_POST['id'];
	$checked = (int) $_POST['checked'];
	if ($checked == 0) {
		$r = $app->db->query("DELETE FROM user_employeeselection WHERE sel_usremp_usr_id={$app->user->info->id} AND sel_usremp_emp_id=$id;");
	} else {
		$r = $app->db->query("INSERT IGNORE INTO user_employeeselection (sel_usremp_usr_id,sel_usremp_emp_id) VALUES ({$app->user->info->id},$id);");
	}

	if ($r) {
		$selectioncount = 0;
		if ($rsel = $app->db->query("SELECT COUNT(sel_usremp_emp_id) AS selectioncount FROM user_employeeselection WHERE sel_usremp_usr_id={$app->user->info->id}")) {
			if ($selectioncount = $rsel->fetch_assoc()) {
				$selectioncount = $selectioncount['selectioncount'];
			}
		}
		echo $selectioncount;
	} else {
		echo "false";
	}
	exit;
}
if (isset($_POST['clearselection'])) {

	$r = $app->db->query("DELETE FROM user_employeeselection WHERE sel_usremp_usr_id={$app->user->info->id} ;");
	exit;
}
if (isset($_POST['selectsearch'])) {
	$r = $app->db->query(
		"INSERT IGNORE INTO 
			user_employeeselection (sel_usremp_usr_id,sel_usremp_emp_id) 
		SELECT 
		 	{$app->user->info->id}, lbr_id
		FROM 
			labour 
				JOIN users ON usr_id = lbr_id
				LEFT JOIN 
					(
						SELECT
							lsc_id,lty_id,lty_name,lsc_name
						FROM
							labour_section JOIN labour_type ON lty_section=lsc_id
					) AS st ON st.lty_id = usr_jobtitle
		WHERE 
			lbr_resigndate IS NULL AND lbr_id!=1 
			" . (isset($_POST['user'][1]) && (int) $_POST['user'][1] != 0 ? " AND usr_id=" . ((int) $_POST['user'][1]) . "" : "") . "
			" . (isset($_POST['job'][1]) && (int) $_POST['job'][1] != 0 ? " AND usr_jobtitle = " . ((int) $_POST['job'][1]) . "" : "") . "
			" . (isset($_POST['shift'][1]) && (int) $_POST['shift'][1] != 0 ? " AND lbr_shift=" . ((int) $_POST['shift'][1]) . "" : "") . "
			" . (isset($_POST['section'][1]) && (int) $_POST['section'][1] != 0 ? " AND lsc_id=" . ((int) $_POST['section'][1]) . "" : "") . "
			" . (isset($_POST['onlyselection']) ? " AND sel_usremp_emp_id IS NOT NULL" : "") . "
			AND usr_entity = {$app->user->company->id}
		;");
	exit;
}
if (isset($_POST['selectall'])) {
	$r = $app->db->query(
		"INSERT IGNORE INTO 
			user_employeeselection (sel_usremp_usr_id,sel_usremp_emp_id) 
		SELECT 
			{$app->user->info->id},lbr_id 
		FROM 
			labour JOIN users on usr_id = lbr_id
		WHERE 
			lbr_resigndate IS NULL AND lbr_id != 1 AND usr_entity = {$app->user->company->id}  
		;");
	exit;
}
if (isset($_POST['cards']) && $_POST['cards'] == '1') {
	if (
		$r = $app->db->query(
		"SELECT 
			usr_id
		FROM
			labour 
				JOIN users ON usr_id = lbr_id
				LEFT JOIN 
					(
						SELECT
							lsc_id,lty_id
						FROM
							labour_section JOIN labour_type ON lty_section=lsc_id
					) AS st ON st.lty_id = usr_jobtitle
				LEFT JOIN labour_shifts ON lsf_id=lbr_shift
				LEFT JOIN gender ON gnd_id=usr_gender
				LEFT JOIN labour_residentail ON ldn_id=lbr_residential
		WHERE
			( (usr_role & b'001') > 0 )
			" . (isset($_POST['displaysuspended']) ? " AND usr_id=usr_id " : " AND lbr_resigndate IS NULL") . "
			" . (isset($_POST['user'][1]) && (int) $_POST['user'][1] != 0 ? " AND usr_id=" . ((int) $_POST['user'][1]) . "" : "") . "
			" . (isset($_POST['job'][1]) && (int) $_POST['job'][1] != 0 ? " AND usr_jobtitle=" . ((int) $_POST['job'][1]) . "" : "") . "
			" . (isset($_POST['shift'][1]) && (int) $_POST['shift'][1] != 0 ? " AND lbr_shift=" . ((int) $_POST['shift'][1]) . "" : "") . "
			" . (isset($_POST['section'][1]) && (int) $_POST['section'][1] != 0 ? " AND lsc_id=" . ((int) $_POST['section'][1]) . "" : "") . "
			AND usr_entity = {$app->user->company->id}
		ORDER BY
			usr_id
		")
	) {
		while ($row = $r->fetch_assoc()) {
			echo "<input type=\"hidden\" name=\"employees[]\" value=\"{$row['usr_id']}\" />";
		}
	}
	exit;
} elseif (isset($_POST['user'])) {
	$offset     = isset($_POST['offset']) ? (int) $_POST['offset'] : 0;
	$countrow   = true;
	$total      = 0;
	$sort_query = false;
	$sort_list  = array(
		"id" => "usr_id",
		"name" => "usr_fullname",
		"job" => "job_name",
		"register" => "usr_registerdate",
		"rating" => "rating",
		"shift" => "lbr_shift",
		"salary_method" => "lbr_mth_id",
		"work_time" => "lwt_id",
		"residence" => "ldn_id",
	);

	if (isset($_POST['sort_field'], $_POST['sort_dir']) && isset($sort_list[$_POST['sort_field']])) {
		$sort_query = " ORDER BY {$sort_list[$_POST['sort_field']]} " . ((int) $_POST['sort_dir'] == 1 ? "DESC" : "ASC");
	} else {
		$sort_query = " ORDER BY usr_id ";
	}

	if (
		$r = $app->db->query(
			"SELECT 
			COUNT(usr_id) AS count
		FROM
			labour 
				JOIN users ON usr_id=lbr_id
				LEFT JOIN 
					(
						SELECT
							lsc_id,lty_id
						FROM
							labour_section JOIN labour_type ON lty_section=lsc_id
					) AS st ON st.lty_id=usr_jobtitle
				LEFT JOIN labour_shifts ON lsf_id=lbr_shift
				LEFT JOIN gender ON gnd_id=usr_gender
		WHERE
			( (usr_role & b'001') > 0 )
			" . (isset($_POST['displaysuspended']) ? " AND usr_id=usr_id " : " AND lbr_resigndate IS NULL ") . "
			" . (isset($_POST['user'][1]) && (int) $_POST['user'][1] != 0 ? " AND usr_id=" . ((int) $_POST['user'][1]) . "" : "") . "
			" . (isset($_POST['job'][1]) && (int) $_POST['job'][1] != 0 ? " AND usr_jobtitle=" . ((int) $_POST['job'][1]) . "" : "") . "
			" . (isset($_POST['shift'][1]) && (int) $_POST['shift'][1] != 0 ? " AND lbr_shift=" . ((int) $_POST['shift'][1]) . "" : "") . "
			" . (isset($_POST['section'][1]) && (int) $_POST['section'][1] != 0 ? " AND lsc_id=" . ((int) $_POST['section'][1]) . "" : "") . "
			" . (isset($_POST['workingtime'][1]) && (int) $_POST['workingtime'][1] != 0 ? " AND lbr_workingtimes=" . ((int) $_POST['workingtime'][1]) . "" : "") . "
			" . (isset($_POST['paymethod'][1]) && (int) $_POST['paymethod'][1] != 0 ? " AND lbr_payment_method=" . ((int) $_POST['paymethod'][1]) . "" : "") . "
			AND usr_entity = {$app->user->company->id}
		"
		)
	) {
		if ($row = $r->fetch_assoc()) {
			$total = $row['count'];
		}
	}

	if ($offset + 1 > (ceil($total / $perpage))) {
		$offset = (ceil($total / $perpage));
	}
	$selectioncount = 0;
	if ($rsel = $app->db->query("SELECT COUNT(sel_usremp_emp_id) AS selectioncount FROM user_employeeselection JOIN labour ON lbr_id = sel_usremp_emp_id  AND lbr_resigndate IS NULL WHERE sel_usremp_usr_id={$app->user->info->id}")) {
		if ($selectioncount = $rsel->fetch_assoc()) {
			$selectioncount = $selectioncount['selectioncount'];
		}
	}

	//Handle various search keys
	$rawselect = "";
	if (isset($_POST['user'][1]) && (int) $_POST['user'][1] == 0 || (!isset($_POST['user'][1]))) {
		$cols             = array("usr_firstname" => "", "usr_lastname" => "", "usr_id" => "");
		$_POST['user'][0] = empty($_POST['user'][0]) ? " " : $_POST['user'][0];
		$q                = preg_replace('/[^\p{Arabic}\da-z_\- ]/ui', " ", trim($_POST['user'][0]));
		$sq               = ' ';
		$i                = 0;
		$sJS              = "";

		$q     = trim($q);
		$smart = "";
		if ($q == "") {
			$sq .= "(";
			foreach ($cols as $k => $v) {
				$sq .= $smart . " $k rlike '.*' ";
				$smart = " OR ";
			}
			$sq .= " )";
		} else {
			$q = explode(" ", $q);
			for ($i = 0; $i < sizeof($q); $i++) {
				$sq .= "(";
				$smart = "";
				foreach ($cols as $k => $v) {
					$sq .= $smart . " $k RLIKE '.*" . replaceARABIC($q[$i]) . ".*' ";
					$smart = " OR ";
				}
				$sq .= ")";
				if ($i != sizeof($q) - 1)
					$sq .= ' AND ';
			}
		}
		$rawselect = " AND " . $sq;
	}

	if (
		$r = $app->db->query(
			"SELECT 
			usr_id,
			usr_registerdate,
			usr_id,lbr_resigndate,
			CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS usr_fullname,
			CONCAT_WS(', ',lsc_name,lty_name) AS job_name,
			lsf_name,
			(4*((1* COALESCE(SUM(lbrrtg_value),0) / (COALESCE(COUNT(lbrrtg_value),0) + 1)+1)/2)+1) AS rating,
			
			sel_usremp_emp_id,
			trans_name,
			lbr_mth_name,lwt_name,ldn_name,usr_role
		FROM
			labour 
				JOIN users AS _users ON usr_id=lbr_id
				LEFT JOIN 
					(
						SELECT
							lsc_id,lty_id,lty_name,lsc_name
						FROM
							labour_section JOIN labour_type ON lty_section=lsc_id
					) AS st ON st.lty_id=usr_jobtitle
				LEFT JOIN labour_shifts ON lsf_id=lbr_shift
				LEFT JOIN labour_rating ON lbrrtg_lbr_id=lbr_id AND lbrrtg_type=1
				LEFT JOIN user_employeeselection AS sel_empusr ON sel_usremp_emp_id=lbr_id AND sel_usremp_usr_id={$app->user->info->id}
				LEFT JOIN labour_transportation ON lbr_transportation=trans_id
				LEFT JOIN labour_method ON lbr_mth_id = lbr_payment_method
				LEFT JOIN workingtimes ON lwt_id = lbr_workingtimes
				LEFT JOIN labour_residentail ON ldn_id = lbr_residential
				
		WHERE
			( (usr_role & b'001') > 0 )
			" . (isset($_POST['displaysuspended']) ? " AND 1 " : " AND lbr_resigndate IS NULL ") . "
			" . (isset($_POST['user'][1]) && (int) $_POST['user'][1] != 0 ? " AND usr_id=" . ((int) $_POST['user'][1]) . "" : "") . "
			" . (isset($_POST['job'][1]) && (int) $_POST['job'][1] != 0 ? " AND usr_jobtitle=" . ((int) $_POST['job'][1]) . "" : "") . "
			" . (isset($_POST['workingtime'][1]) && (int) $_POST['workingtime'][1] != 0 ? " AND lbr_workingtimes=" . ((int) $_POST['workingtime'][1]) . "" : "") . "
			" . (isset($_POST['paymethod'][1]) && (int) $_POST['paymethod'][1] != 0 ? " AND lbr_payment_method=" . ((int) $_POST['paymethod'][1]) . "" : "") . "
			" . (isset($_POST['shift'][1]) && (int) $_POST['shift'][1] != 0 ? " AND lbr_shift=" . ((int) $_POST['shift'][1]) . "" : "") . "
			" . (isset($_POST['section'][1]) && (int) $_POST['section'][1] != 0 ? " AND lsc_id=" . ((int) $_POST['section'][1]) . "" : "") . "
			" . (isset($_POST['onlyselection']) ? " AND sel_usremp_emp_id IS NOT NULL" : "") . "
			AND usr_entity = {$app->user->company->id}
			$rawselect
		GROUP BY
			usr_id
			$sort_query
		LIMIT
			" . ($offset * $perpage) . ",$perpage
		"
		)
	) {
		while ($row = $r->fetch_assoc()) {
			if ($countrow) {
				echo "<tr>";
				echo "<th class=\"navigator\" style=\"color:#333;position:relative\" colspan=\"" . (11 + ($fs()->permission->edit ? 1 : 0)) . "\"><span class=\"btn-set\">";
				echo "<button id=\"jQselectaction\">Selected <div style=\"display:inline-block;\">$selectioncount</div><span></span></button>";
				echo "<b id=\"jQactionmenu\" data-status=\"off\">";
				echo "<div id=\"jQaction_selectall\"><span>&#xea54;</span>Select all</div>";
				echo "<div id=\"jQaction_clearall\"><span>&#xea56;</span>Clear selection</div>";
				echo "<hr />";
				echo "<div id=\"jQaction_selectsearch\"><span>&#xea5b;</span>Select search results</div>";
				echo "<hr />";
				echo $fs(154)->permission->edit ? "<div id=\"jQaction_bulkeditor\"><span>&#xe997;</span>Edit</div>" : "";
				echo "<hr />";
				echo "<div id=\"jQaction_exportselection\"><span>&#xe961;</span>Export selection</div>";
				echo "<div id=\"jQaction_exportall\"><span>&#xe961;</span>Export all</div>";
				echo "</b>";
				echo "<span>Total count of employees: <b>$total</b></span><span class=\"gap\"></span>";
				echo "<button " . ($offset == 0 ? "disabled=\"disabled\"" : "") . " data-offset=\"" . ($offset - 1) . "\">Previous</button>";
				echo "<input type=\"text\" value=\"Page " . ($offset + 1) . "/" . (ceil($total / $perpage)) . "\" style=\"width:130px;text-align:center\" readonly=\"readonly\" />";
				echo "<button " . ($offset + 1 == (ceil($total / $perpage)) ? "disabled=\"disabled\"" : "") . " data-offset=\"" . ($offset + 1) . "\">Next</button>";
				echo "</span></th></tr>";
				$countrow = false;
			}

			echo "<tr" . (!is_null($row['lbr_resigndate']) ? " class=\"css_suspended\"" : "") . ">";
			echo "<td width=\"10\" class=\"checkbox\"><label><input class=\"jQempcheck\" data-id=\"{$row['usr_id']}\" " . (is_null($row['sel_usremp_emp_id']) ? "" : "checked=\"checked\"") . " type=\"checkbox\" /><span></span></label></td>";
			echo "<td>{$row['usr_id']}</td>";
			echo "<td>" . (!is_null($row['lbr_resigndate']) ? "<span class=\"suspended\"></span>" : "") . "{$row['usr_fullname']}</td>";
			//echo "<td>{$row['lbr_serial']}</td>";
			echo "<td>{$row['job_name']}</td>";
			echo "<td>{$row['lbr_mth_name']}</td>";
			echo "<td>{$row['lwt_name']}</td>";

			echo "<td>{$row['lsf_name']}</td>";
			echo "<td>{$row['usr_registerdate']}</td>";
			//echo "<td>".number_format($row['rating'],2)."</td>";
			echo "<td>{$row['trans_name']}</td>";
			echo "<td>{$row['ldn_name']}</td>";
			if ($fs()->permission->edit) {
				echo "<td class=\"op-edit\"><a href=\"{$fs(134)->dir}?method=update&id={$row['usr_id']}\"></a></td>";
			}
			echo "<td class=\"op-display\"><a href=\"{$fs(108)->dir}?id={$row['usr_id']}\"></a></td>";
			echo "</tr>";
		}
	}



	exit;
}
?>
<style>
	.checkbox {
		padding: 0 !important;
		-webkit-touch-callout: none;
		-webkit-user-select: none;
		-khtml-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
		user-select: none;
	}

	#jQselectaction>span:before {
		content: "\e619";
		font-family: "icomoon";
		position: relative;
		left: 6px;
		top: 1px;
	}

	#jQselectaction>div {
		display: inline-block;
	}


	#jQactionmenu {
		display: none;
		font-weight: normal;
		position: absolute;
		z-index: 9;
		white-space: normal;
		top: 38px;
		min-width: 250px;
		padding: 0px;
		height: auto;
		left: 11px;
	}

	#jQactionmenu>div {
		padding: 6px 10px;
		white-space: nowrap;
		position: relative;
		border: solid 1px transparent;
		color: #333;
		cursor: default;
	}

	#jQactionmenu>div>span {
		font-family: icomoon4;
		display: inline-block;
		padding-right: 10px;
		color: #888;
	}

	#jQactionmenu>div:hover {
		text-shadow: 1px 1px 0 #fff;
		background-color: rgba(82, 168, 236, .1);
		border-color: rgba(82, 168, 236, .75);
		text-decoration: none;
	}

	#jQactionmenu>hr {
		margin: 0;
	}



	.permanent:after {
		font-family: icomoon;
		content: "\e62e";
		display: inline-block;
		color: #fc0;
		margin-right: 5px;
	}

	.suspended:after {
		font-family: icomoon2;
		content: "\f00d";
		display: inline-block;
		color: #f00;
		margin-right: 5px;
	}


	.sa.down>span:before {
		content: "\e615";
		font-family: icomoon;
	}

	.sa.up>span:before {
		content: "\e616";
		font-family: icomoon;
	}

	.sa {
		cursor: default
	}
</style>
<form action="<?= $fs(28)->dir ?>" target="_blank" method="post" id="jQprintform">
	<input type="hidden" id="jQveriforminput" name="verified" value="0" />
	<div id="jQprintformData"></div>
</form>
<form id="searchform">
	<input type="hidden" name="offset" id="offset" value="0" />
	<input type="hidden" name="cards" id="cards" value="0" />
	<input type="hidden" name="sort_field" id="sort_field" value="" />
	<input type="hidden" name="sort_dir" id="sort_dir" value="" />
	<table>
		<tbody>
			<tr>
				<td>
					<div class="btn-set" style="margin-bottom: 10px;">
						<button type="button" id="jQdosearch">Search</button>
						<label class="btn-checkbox"><input type="checkbox" name="onlyselection" id="onlyselection" />
							<span>&nbsp;Show selection only&nbsp;</span></label>
						<label class="btn-checkbox"><input type="checkbox" name="displaysuspended" id="jQdisplaySuspended" /> <span>&nbsp;Display
								Suspended&nbsp;</span></label>
					</div>
					<div class="btn-set">
						<input class="jsFilterFeild flex" name="user" type="text" data-slo="B00S" style="min-width:160px;max-width:220px;" id="user"
							placeholder="Employee name, serial or id" />
						<input class="jsFilterFeild flex" name="section" type="text" data-slo="E001" style="min-width:160px;max-width:220px;"
							id="section" placeholder="Section" />
						<input class="jsFilterFeild flex" name="job" type="text" data-slo="E002A" style="min-width:160px;max-width:220px;" id="job"
							placeholder="Job" />
						<input class="jsFilterFeild flex" name="shift" type="text" data-slo="E003" style="min-width:100px;max-width:220px;" id="shift"
							placeholder="Shift" />
						<input class="jsFilterFeild flex" name="workingtime" type="text" data-slo="WORKING_TIMES"
							style="min-width:100px;max-width:220px;" id="workingtime" placeholder="Working Time" />
						<input class="jsFilterFeild flex" name="paymethod" type="text" data-slo="SALARY_PAYMENT_METHOD"
							style="min-width:100px;max-width:220px;" id="paymethod" placeholder="Salary Payment Method" />
					</div>
				</td>
				<td>
					<div class="btn-set noselect"></div>
				</td>
			</tr>
		</tbody>
	</table>
</form>
<br />
<table class="hover">
	<thead>
		<tr class="special">
			<td data-feild="id"><span></span></td>
			<td class="sa down" data-feild="id"><span>ID</span></td>

			<td class="sa" data-feild="name"><span>Name</span></td>
			<!--<td><span>Serial</span></td>-->
			<td class="sa" data-feild="job"><span>Job</span></td>

			<td class="sa" data-feild="salary_method"><span>Payment Method</span></td>
			<td class="sa" data-feild="work_time"><span>Working Time</span></td>

			<td class="sa" data-feild="shift"><span>Shift</span></td>
			<td class="sa" data-feild="register"><span>Registration Date</span></td>
			<!--<td class="sa" data-feild="rating"><span>Rating</span></td>-->
			<td class="sa" data-feild="transportation"><span>Transportation</span></td>
			<td class="sa" data-feild="residence"><span>Residence</span></td>

			<?php echo ($fs()->permission->edit ? "<td style=\"width:10px;\"></td>" : "") ?>
			<td style="width:10px"></td>
		</tr>
	</thead>
	<tbody id="jQoutput"></tbody>
</table>

<script>
	$(document).ready(function (e) {

		$("#doprint").on('click', function () {
			$("#cards").val("1");
			var serialized = $("#searchform").serialize();
			$("#cards").val("0");
			$.ajax({
				data: serialized,
				url: '<?php echo $fs()->dir; ?>',
				type: 'POST'
			}).done(function (data) {
				$("#jQprintformData").html(data);
				$("#jQprintform").submit();
			});
		});
		$("#jQvericheck").on('change', function () {
			$("#jQveriforminput").val($("#jQvericheck").prop("checked") ? "1" : "0");
		});
		$("#jQoutput").on('click', "#jQaction_exportselection", function () {
			$("#searchform").attr("target", "_blank");
			$("#searchform").attr("method", "post");
			$("#searchform").attr("action", "<?= $fs(77)->dir ?>/?onlyselection");

			var serialized = $("#searchform").serialize();
			$("#searchform").submit();

			$("#searchform").attr("target", "");
			$("#searchform").attr("method", "");
			$("#searchform").attr("action", "");
		});
		$("#jQoutput").on('click', "#jQaction_exportall", function () {
			$("#searchform").attr("target", "_blank");
			$("#searchform").attr("method", "post");
			$("#searchform").attr("action", "<?= $fs(77)->dir ?>/");

			var serialized = $("#searchform").serialize();
			$("#searchform").submit();

			$("#searchform").attr("target", "");
			$("#searchform").attr("method", "");
			$("#searchform").attr("action", "");
		});

		$("#jQoutput").on('click', '#jQselectaction', function () {
			var $menu = $("#jQactionmenu");
			if ($menu.attr('data-status') == 'off') {
				$menu.show();
				$menu.attr('data-status', 'on');
			} else {
				$menu.hide();
				$menu.attr('data-status', 'off');
			}
		});

		$("#jQoutput").on('click', '#jQaction_bulkeditor', function () {
			var $menu = $("#jQactionmenu");
			$menu.hide();
			$menu.attr('data-status', 'off');
			overlay.show();
			$.ajax({
				data: {
					"bulkeditorform": ""
				},
				url: '<?php echo $fs()->dir; ?>',
				type: 'POST'
			}).done(function (data) {
				popup.content(data);
				popup.show();
				overlay.hide();

				$(popup.controller()).find("#jQcancel").on('click', function () {
					popup.close();
				});
				$(popup.controller()).find(".jQalter_checkbox").on('change', function () {
					var $checkbox = $(this);
					$checkbox.closest("div").find("input[type=text]").prop("disabled", !$checkbox.prop("checked"));
				});

				$(popup.controller()).find(".jQBulkSLOField").slo({
					limit: 10
				}).disable();

				$(popup.controller()).find("#jQsubmit_bulk").on('click', function () {
					let ajaxdata = {};
					let fields = {};

					<?php foreach ($arr_feild as $k => $v) {
						echo "fields['$k']={};";
					} ?>
					for (field in fields) {
						if ($("#jQalter_" + field).prop("checked")) {
							ajaxdata[field] = $("#jQbulk_" + field + "_1").val();
						}
					}
					ajaxdata['bulkeditorsubmit'] = '';
					overlay.show();
					$.ajax({
						data: ajaxdata,
						url: '<?php echo $fs()->dir; ?>',
						type: 'POST'
					}).done(function (bulkboutput) {
						overlay.hide();
						if (bulkboutput == "empty") {
							messagesys.success("Nothing to edit");
						} else if (bulkboutput == "success") {
							messagesys.success("Employees information edited successfully");
							popup.close();
							ajaxcall();
						} else if (bulkboutput == "fail") {
							messagesys.failure("Edting employees information failed");
						} else {
							messagesys.failure("Unknown error");
						}
					});
				});
			});
		});


		$("#jQdosearch").on('click', function () {
			ajaxcall();
		});
		$("#jQoutput").on('click', '#jQaction_clearall', function () {
			var $menu = $("#jQactionmenu");
			overlay.show();
			$menu.hide();
			$menu.attr('data-status', 'off');
			$.ajax({
				data: {
					"clearselection": ""
				},
				url: '<?php echo $fs()->dir; ?>',
				type: 'POST'
			}).done(function (data) {
				ajaxcall();
				overlay.hide();
			});
		});
		$("#jQoutput").on('click', '#jQaction_selectall', function () {
			var $menu = $("#jQactionmenu");
			overlay.show();
			$menu.hide();
			$menu.attr('data-status', 'off');
			$.ajax({
				data: {
					'selectall': '0'
				},
				url: '<?php echo $fs()->dir; ?>',
				type: 'POST'
			}).done(function (data) {
				ajaxcall();
				overlay.hide();
			});
		});
		$("#jQoutput").on('click', '#jQaction_selectsearch', function () {
			var $menu = $("#jQactionmenu");
			overlay.show();
			$menu.hide();
			$menu.attr('data-status', 'off');
			$.ajax({
				data: $("#searchform").serialize() + '&selectsearch=0',
				url: '<?php echo $fs()->dir; ?>',
				type: 'POST'
			}).done(function (data) {
				ajaxcall();
				overlay.hide();
			});
		});


		$("#onlyselection").on('change', function () {
			ajaxcall();
		});


		var ajaxcall = function () {
			$.ajax({
				data: $("#searchform").serialize(),
				url: '<?php echo $fs()->dir; ?>',
				type: 'POST'
			}).done(function (data) {
				$data = $(data);
				$("#jQoutput").html(data);
			});
		}


		$("#jQoutput").on('change', '.jQempcheck', function () {
			var _this = $(this);
			var _check = _this.prop('checked');
			var _id = _this.attr("data-id");
			$.ajax({
				data: {
					"employeecheck": "",
					"id": _id,
					"checked": (_check ? "1" : "0")
				},
				url: '<?php echo $fs()->dir; ?>',
				type: 'POST'
			}).done(function (output) {
				if (output == "false") {

				} else {
					$("#jQselectaction").find("div").html(output);
				}
			});
		});


		$(".sa").on('click', function () {
			$(".sa").removeClass("down").removeClass("up");
			var _dir = 0;
			if ($("#sort_field").val() == $(this).attr("data-feild")) {
				_dir = ~~$("#sort_dir").val();
				_dir = _dir == 1 ? 0 : 1;
				$("#sort_dir").val(_dir);
			} else {
				$("#sort_dir").val(0);
				_dir = 0;
			}
			$(this).addClass(_dir == 0 ? "down" : "up");
			$("#sort_field").val($(this).attr("data-feild"));
			ajaxcall();
		});

		$("#jQoutput").on('click', 'button[data-offset]', function () {
			var offset = $(this).attr('data-offset');
			$("#offset").val(offset);
			ajaxcall();
		});
		$("#jQoutput").on('click', ".op-display > a", function (e) {
			e.preventDefault();
			var $this = $(this);
			overlay.show();

			var $ajax = $.ajax({
				type: "POST",
				url: $this.attr("href") + "&ajax",
				data: ""
			}).done(function (data) {
				overlay.hide()
				popup.content(data);
				popup.show();
			});
			return false;
		});
		var userinput = $(".jsFilterFeild").slo({
			onselect: function (value) {
				$("#offset").val("0");
				ajaxcall();
			},
			ondeselect: function () {
				$("#offset").val("0");
				ajaxcall();
			},
			limit: 10
		});



		$("#jQdisplaySuspended").on('change', function () {
			ajaxcall();
		});
		ajaxcall();
	});
</script>