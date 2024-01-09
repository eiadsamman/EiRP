<?php
use System\Template\Gremium;

$arr_feild = array(
	"gender" => array("Gender", "G000", "usr_gender"),
	"job" => array("Job", "E002A", "lbr_type"),
	"shift" => array("Shift", "E003", "lbr_shift"),
	"residence" => array("Residence", "E004", "lbr_residential"),
	"transportation" => array("Transportation", "TRANSPORTATION", "lbr_transportation"),
);

$grem = new Gremium\Gremium(false, false);

function GetEmployeesList(&$app, $fs, $simulate_template, &$grem)
{
	if($simulate_template){
		$grem->header();
		$grem->menu();
	}
	$list = array();
	$group_list = array(
		"job_section" => 'lsc_name',
		"job_title" => 'jobtitle_group',
		"residence" => 'ldn_name',
		"gender" => 'gnd_name',
		"shift" => 'lsf_name',
		"transportation" => 'trans_name',
	);
	$group_name = isset($_POST['group']) && isset($group_list[$_POST['group']]) ? $group_list[$_POST['group']] : false;
	$q = "SELECT 
			usr_firstname,usr_id,usr_lastname,lbr_resigndate,gnd_name,
			lsc_name,lbr_permanentdate,CONCAT(lsc_name,' - ',lty_name) as jobtitle_group,ldn_name,trans_name,sel_usremp_emp_id,
			lbr_fixedsalary,lbr_variable,lty_salarybasic,lsf_name,up_id
		FROM
			labour
				JOIN users ON lbr_id=usr_id
				LEFT JOIN labour_residentail ON lbr_residential=ldn_id
				LEFT JOIN 
				(
					SELECT
						lsc_name,lty_id,lsc_id,lty_name,lty_salarybasic
					FROM 
						labour_section JOIN labour_type ON lsc_id=lty_section
				) AS _labour_type ON _labour_type.lty_id=lbr_type
				
				LEFT JOIN labour_transportation ON lbr_transportation=trans_id
				LEFT JOIN labour_shifts ON lsf_id=lbr_shift
				LEFT JOIN user_employeeselection AS sel_empusr ON sel_usremp_emp_id = lbr_id AND sel_usremp_usr_id = {$app->user->info->id}
				LEFT JOIN gender ON gnd_id = usr_gender
				JOIN companies ON comp_id = lbr_company AND lbr_company = {$app->user->company->id}
				LEFT JOIN uploads ON (up_pagefile=" . \System\Attachment\Type::HrPerson->value . " ) AND up_rel=lbr_id AND up_deleted=0
		WHERE
			( (lbr_role & b'001') > 0 ) AND lbr_resigndate IS NULL 
		GROUP BY
			lbr_id
		ORDER BY
			" . ($group_name ? $group_name . ",lsc_id,usr_gender,lbr_id" : "lsc_id,usr_gender,lbr_id") . "";

	if ($r = $app->db->query($q)) {

		while ($row = $r->fetch_assoc()) {
			if (!isset($list[$group_name ? $row[$group_name] : ""])) {
				$list[$group_name ? $row[$group_name] : ""] = array();
			}
			$list[$group_name ? $row[$group_name] : ""][] = $row;
		}
		foreach ($list as $k => $type) {
			$grem->legend()->serve("<span class=\"flex\">" . (is_null($k) || $k == "" ? "[No group]" : $k) . "</span><span style=\"min-width:130px;text-align:right\">" . sizeof($type) . "</span>");
			$grem->article()->options(array("nobg"))->open();

			foreach ($type as $row) {
				$personalPhoto = "";
				if (!is_null($row['up_id']) && (int) $row['up_id'] != 0) {
					$personalPhoto = " style=\"background-image:url('" . $fs(187)->dir . "?id={$row['up_id']}&pr=t')\"";
				} else {
					$personalPhoto = " style=\"background-image:url('user.jpg')\"";
				}
				if ($fs(182)->permission->read) {
					$temphref = " href=\"{$fs(182)->dir}/?id={$row['usr_id']}\" target=\"_blank\" ";
				} else {
					$temphref = "";
				}
				echo "<a class=\"empCard\" data-record=\"{$row['usr_id']}\" data-checked=\"" . (!is_null($row['sel_usremp_emp_id']) ? "true" : "false") . "\" $temphref>
						<div{$personalPhoto}></div>
						<span>{$row['usr_id']}<br />{$row['usr_firstname']} {$row['usr_lastname']}</span>";
				echo "</a>";
			}
			$grem->getLast()->close();
			echo '<br />';
		}
	}

}
function GetTotalEmployees(&$app): int
{
	if (
		$r = $app->db->query(
			"SELECT 
				COUNT(lbr_id) AS count 
			FROM 
				labour 
					JOIN companies ON comp_id = lbr_company AND lbr_company = {$app->user->company->id}	
			WHERE 
				( (lbr_role & b'001') > 0 ) AND lbr_resigndate IS NULL;")
	) {
		if ($row = $r->fetch_assoc()) {
			return (int) $row['count'];
		}
	}
	return 0;
}
function GetSelectedEmployees(&$app)
{

	$selectioncount = 0;
	if (
		$r = $app->db->query("
		SELECT 
			COUNT(sel_usremp_emp_id) AS selectioncount 
		FROM 
			user_employeeselection JOIN labour ON lbr_id = sel_usremp_emp_id  AND lbr_resigndate IS NULL
		WHERE 
			sel_usremp_usr_id={$app->user->info->id}")
	) {
		if ($selectioncount = $r->fetch_assoc()) {
			$selectioncount = $selectioncount['selectioncount'];
		}
	}
	return $selectioncount;
}
if (isset($_POST['method']) && $_POST['method'] == 'fetch') {
	$limit_selection = isset($_POST['limitselection']) && (int) $_POST['limitselection'] == 1 ? true : false;
	GetEmployeesList($app, $fs, true, $grem);
	exit;
}
if (isset($_POST['method']) && $_POST['method'] == 'call_selected_employees_count') {
	echo GetSelectedEmployees($app);
	exit;
}
if (isset($_POST['method']) && $_POST['method'] == 'call_all_employees_count') {
	echo GetTotalEmployees($app);
	exit;
}


$grem->header()->serve("<h1>{$fs()->title}</h1><cite>" . GetTotalEmployees($app) . "</cite>");

$grem->menu()->open();
?>
<button id="jQrefresh" type="button">Refresh</button>
<input type="text" id="js-input-list_group" data-slo=":SELECT" placeholder="Group options..." readonly style="width:170px;" data-list="js-data-list_group" />
<?php $grem->getLast()->close(); ?>

<div class="emp_list gremium" id="emp_list">
	<?php
	$_POST['group'] = 'job_section';
	GetEmployeesList($app, $fs, false, $grem);
	?>
</div>

<datalist id="js-data-list_group">
	<option data-id="gender">Gender</option>
	<option data-id="job_section">Job Section</option>
	<option data-id="job_title">Job Title</option>
	<option data-id="shift">Shift</option>
	<option data-id="residence">Residence</option>
	<option data-id="transportation">Transportation</option>
</datalist>

<script>
	$(document).ready(function (e) {
		let group_cmd = null;
		$("#js-input-list_group").slo({
			onselect: function (e) {
				group_cmd = (e.hidden);
				ajaxcall();
			},
			ondeselect: function () {
				group_cmd = "no_group";
				ajaxcall();
			}
		})
		var ajaxcall = function () {
			overlay.show();
			$.ajax({
				data: {
					'method': 'fetch',
					'group': group_cmd
				},
				url: '<?php echo $fs()->dir; ?>',
				type: 'POST'
			}).done(function (data) {
				$data = $(data);
				$("#emp_list").html(data);
				overlay.hide();
			});
		}

		$("#jQrefresh").on('click', function () {
			ajaxcall();
		});


	});
</script>