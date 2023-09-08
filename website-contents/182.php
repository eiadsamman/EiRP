<?php

include_once("admin/class/person.php");
include_once("admin/class/Template/class.template.build.php");

use Template\TemplateBuild;
use System\Pool;
use System\SLO_DataList;

$_TEMPLATE = new TemplateBuild("HIFile");
$_TEMPLATE->SetLayout(/*Sticky Title*/true,/*Command Bar*/ true,/*Sticky Frame*/ true);
$_TEMPLATE->FrameTitlesStack(false);
$_TEMPLATE->SetWidth("800px");



if (isset($_POST['method'], $_POST['employeeID']) && $_POST['method'] == "fetchrecord") {
	$perm_personal = $tables->Permissions(227, $USER->info->permissions);
	$perm_job = $tables->Permissions(228, $USER->info->permissions);
	$perm_salary = $tables->Permissions(229, $USER->info->permissions);
	$employeeID = (int)$_POST['employeeID'];

	if ($r = $sql->query("
		SELECT
			usr_firstname,usr_lastname,
			usr_id,usr_username,usr_phone_list,
			usr_attrib_i2,gnd_name,
			lsf_id,lsf_name,
			lty_id,lty_name,lsc_name,
			ldn_id,ldn_name,
			DATE_FORMAT(usr_birthdate,'%d %M, %Y') AS usr_birthdate,
			DATE_FORMAT(lbr_registerdate,'%d %M, %Y') AS lbr_registerdate,
			DATE_FORMAT(lbr_resigndate,'%d %M, %Y') AS lbr_resigndate,
			lbr_socialnumber,
			trans_name,lbr_mth_name,lwt_name,
			lbr_id,cntry_name,
			lbr_fixedsalary,lbr_variable,lbr_allowance,lbr_trans_allowance,
			lbr_typ_sal_basic_salary,lbr_typ_sal_variable,lbr_typ_sal_allowance,lbr_typ_sal_transportation
		FROM
			labour
				JOIN users ON usr_id=lbr_id
				LEFT JOIN labour_shifts ON lbr_shift=lsf_id
				LEFT JOIN countries ON cntry_id=lbr_nationality
				LEFT JOIN (SELECT lty_id,lty_name,lsc_name FROM labour_type JOIN labour_section ON lsc_id=lty_section) AS _labourtype ON lty_id=lbr_type
				LEFT JOIN gender ON gnd_id=usr_gender
				LEFT JOIN labour_residentail ON ldn_id=lbr_residential
				LEFT JOIN labour_method ON lbr_mth_id = lbr_payment_method
				LEFT JOIN workingtimes ON lwt_id = lbr_workingtimes
				LEFT JOIN labour_transportation ON lbr_transportation=trans_id
				LEFT JOIN labour_type_salary ON lbr_typ_sal_lty_id = lbr_type AND lbr_typ_sal_lwt_id = lbr_workingtimes AND lbr_typ_sal_method = lbr_payment_method
		WHERE
			lbr_id=$employeeID AND usr_id!=1;")) {
		if ($row = $sql->fetch_assoc($r)) {
			header("HTTP_X_RESPONSE: SUCCESS");
			header("HTTP_X_PID: " . $row['usr_id']);
			$arr_socialids = array();
			$q_socialid_uploads = $sql->query("SELECT up_id,up_name,up_size,DATE_FORMAT(up_date,'%d %M, %Y') as up_date,up_pagefile FROM uploads WHERE up_rel=$employeeID AND up_deleted=0");
			while ($row_socialid_uploads = $sql->fetch_assoc($q_socialid_uploads)) {
				if (!isset($arr_socialids[$row_socialid_uploads['up_pagefile']])) {
					$arr_socialids[$row_socialid_uploads['up_pagefile']] = array();
				}
				$arr_socialids[$row_socialid_uploads['up_pagefile']][$row_socialid_uploads['up_id']] = array($row_socialid_uploads['up_name'], $row_socialid_uploads['up_size'], $row_socialid_uploads['up_date'], $row_socialid_uploads['up_id']);
			}
			$socialidphotos = "";
			if (isset($arr_socialids[Pool::FILE['Person']['ID']])) {
				foreach ($arr_socialids[Pool::FILE['Person']['ID']] as $k_socialid => $v_socialid) {
					$socialidphotos .= "<a href=\"download/?id={$k_socialid}\" class=\"jq_frame_image\" data-href=\"download/?id={$k_socialid}&pr=v\">view</a>";
				}
			}


			if ($perm_personal->read) {
				$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Personal Information:</span>", false, false, 105);
				echo $_TEMPLATE->NewFrameBodyStart();
				echo '
					<table cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:10px;">
					<tr>
					<td style="width:33%;min-width:200px" align="center">';
				$img = "user.jpg";
				if (
					isset($arr_socialids[Pool::FILE['Person']['Photo']]) && is_array($arr_socialids[Pool::FILE['Person']['Photo']])
					&& sizeof($arr_socialids[Pool::FILE['Person']['Photo']]) > 0
				) {
					$imgid = reset($arr_socialids[Pool::FILE['Person']['Photo']])[3];
					$img = "download/?id={$imgid}&pr=t";
					unset($imgid);
				}
				echo "<div style=\"background-image:url('$img');\" id=\"personal_photo\"></div>";

				echo '</td>
					<td style="width:100%">
						<div class="template-gridLayout">
							<div><span>ID</span><div>' . $row['usr_id'] . '</div></div>
						</div>
						<div class="template-gridLayout">
							<div><span>Nationality</span><div>' . $row['cntry_name'] . '</div></div>
						</div>
						<div class="template-gridLayout">
							<div><span>Name</span><div>' . $row['usr_firstname'] . ' ' . $row['usr_lastname'] . '</div></div>
						</div>
						<div class="template-gridLayout">
							<div><span>Birthdate</span><div>' . (is_null($row['usr_birthdate']) ? "-" : $row['usr_birthdate']) . '</div></div>
						</div>
					</tr>
					</table>

					<div class="template-gridLayout">
						<div><span>Gender</span><div>' . $row['gnd_name'] . '</div></div>
						<div><span>Contact infomration</span><div>' . (is_null($row['usr_phone_list']) ? "-" : $row['usr_phone_list']) . '</div></div>
						<div><span>Residence</span><div>' . (is_null($row['ldn_name']) ? "-" : $row['ldn_name']) . '</div></div>
						<div><div></div></div>
					</div>
					
					<div class="template-gridLayout">
						<div><span>Transportation</span><div>' . (is_null($row['trans_name']) ? "-" : $row['trans_name']) . '</div></div>
						<div><span>Social ID Number</span><div>' . (is_null($row['lbr_socialnumber']) ? "-" : $row['lbr_socialnumber']) . '</div></div>
						<div><span></span><div></div></div>
					</div>

					<div class="template-gridLayout">
						<div><span>References</span><div>' . $socialidphotos . '</div></div>
					</div>';
				echo $_TEMPLATE->NewFrameBodyEnd();
			}

			if ($perm_job->read) {
				$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Job Information:</span>", false, false, 105);
				echo $_TEMPLATE->NewFrameBodyStart();
				echo '
				<div class="template-gridLayout">
					<div><span>Registration date</span><div>' . $row['lbr_registerdate'] . '</div></div>
					<div><span>Resignation date</span><div>' . $row['lbr_resigndate'] . '</div></div>
					<div><div></div></div>
					
				</div>
				<div class="template-gridLayout">
					<div><span>Job title</span><div>' . $row['lsc_name'] . ", " . $row['lty_name'] . '</div></div>
					<div><span>Payment method</span><div>' . $row['lbr_mth_name'] . '</div></div>
					<div><span></span><div></div></div>
				</div>
				
				<div class="template-gridLayout">
					<div><span>Working shift</span><div>' . $row['lsf_name'] . '</div></div>
					<div><span>Working Time</span><div>' . $row['lwt_name'] . '</div></div>
					
					<div><span></span><div></div></div>
				</div>';
				echo $_TEMPLATE->NewFrameBodyEnd();
			}


			if ($perm_salary->read) {
				$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Salary Details:</span>", false, false, 105);
				echo $_TEMPLATE->NewFrameBodyStart();
				echo '<div class="template-gridLayout">
					<div><span>Salary</span><div>' . (is_null($row['lbr_fixedsalary']) ? number_format((float)$row['lbr_typ_sal_basic_salary'], 2, ".", ",") : number_format((float)$row['lbr_fixedsalary'], 2, ".", ",")) . '</div></div>
					<div><span>Variable</span><div>' . (is_null($row['lbr_variable']) ? number_format((float)$row['lbr_typ_sal_variable'], 2, ".", ",") : number_format((float)$row['lbr_variable'], 2, ".", ",")) . '</div></div>
					<div><span>Allowance</span><div>' . (is_null($row['lbr_allowance']) ? number_format((float)$row['lbr_typ_sal_allowance'], 2, ".", ",") : number_format((float)$row['lbr_allowance'], 2, ".", ",")) . '</div></div>
				</div>';
				echo $_TEMPLATE->NewFrameBodyEnd();
			}

			exit;
		} else {
			header("HTTP_X_RESPONSE: ERROR");
			$_TEMPLATE->Title("&nbsp;Not Found!", null, "", "mark-error");
			$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Loading select personnel failed:</span>");
			$_TEMPLATE->NewFrameBody('<ul>
				<li>Personnel ID is invalid</li>
				<li>Session has expired</li>
				<li>Database query failed, contact system administrator</li>
				<li>Permission denied or not enough privileges to proceed with this document</li>
				</ul>
				');
			exit;
		}
	}
	exit;
}

if ($h__requested_with_ajax) {
	exit;
}

include_once("admin/class/slo_datalist.php");
$slo_datalist = new SLO_DataList();
?>

<style type="text/css">
	#personal_photo {
		display: inline-block;
		border: solid 0px #ccc;
		width: 180px;
		height: 180px;
		background-size: 100% auto;
		background-repeat: no-repeat;
		background-position: 100% 50%;
		border-radius: 10px;
	}
</style>
<a id="jQtriggerlink" style="display: none;" href="" target="_blank"></a>
<?php


$_TEMPLATE->Title($pageinfo['title'], null, "<span id=\"jQdomPID\"></span>");

echo $_TEMPLATE->CommandBarStart();
echo "<div class=\"btn-set\">";
echo "<input id=\"employeIDFormSearch\" tabindex=\"1\" type=\"text\" data-slo=\":LIST\" data-list=\"personList\" class=\"flex\" placeholder=\"Employee name or id\" />";
echo "<button type=\"button\" id=\"jQedit\" tabindex=\"2\" disabled>Edit information</button>";
echo "<button type=\"button\" id=\"jQprintIDCard\" tabindex=\"3\" disabled>Print ID Card</button>";
echo "</div>";
echo "<datalist id=\"personList\">" . $slo_datalist->hr_person(Pool::$_user->company->id) . "</datalist>";
echo $_TEMPLATE->CommandBarEnd();

?>



<div id="jQoutput" style="position:relative;"></div>


<script>
	$(document).ready(function(e) {
		let counter = 0;
		const linkTrigger = $("#jQtriggerlink"),
			buttonEdit = $("#jQedit"),
			buttonPrintID = $("#jQprintIDCard"),
			divOutput = $("#jQoutput"),
			spanIDTitle = $("#jQdomPID");
		let queryResponse = false;

		$("#jQoutput").on("click", ".jq_frame_image", function(e) {
			e.preventDefault();
			var viewsrc = $(this).attr("data-href");
			popup.show("<img style=\"max-width:100%;width:100%;margin-bottom:15px;\" src=\"" + viewsrc + "\" />");
		});

		let clear = function() {
			divOutput.html("");
			buttonPrintID.prop("disabled", true);
			buttonEdit.prop("disabled", true);
			spanIDTitle.html("");
			linkTrigger.prop("href", "");
			queryResponse = false;
		}
		var SLO_employeeID = $("#employeIDFormSearch").slo({
			onselect: function(value) {
				history.pushState({
					'method': 'view',
					'id': value.hidden,
					'name': value.value
				}, "<?php echo $tables->pagefile_info(182, null, "title"); ?>", "<?php echo $tables->pagefile_info(182, null, "directory"); ?>/?id=" + value.hidden);
				fn_fetchfile();
			},
			ondeselect: function() {
				clear();
				history.pushState({
					'method': '',
					'id': 0,
					'name': ''
				}, "<?php echo $tables->pagefile_info(182, null, "title"); ?>", "<?php echo $tables->pagefile_info(182, null, "directory"); ?>");
			},
			"limit": 10
		});

		$(".jq_frame_image").on("click", function(e) {
			e.preventDefault();
			var path = $(this).attr("data-href");
			popup.show("<img src=\"" + path + "\" />");
			return false;
		});
		var fn_fetchfile = function(_pushState = true) {
			overlay.show();
			$.ajax({
				data: {
					'method': 'fetchrecord',
					'employeeID': SLO_employeeID.hidden[0].val(),
				},
				url: "<?php echo $pageinfo['directory']; ?>",
				type: "POST"
			}).done(function(o, textStatus, request) {
				let response = request.getResponseHeader('HTTP_X_RESPONSE');
				let responsepid = request.getResponseHeader('HTTP_X_PID');

				if (response == "ERROR") {
					clear();
				} else if (response == "SUCCESS") {
					buttonPrintID.prop("disabled", false);
					buttonEdit.prop("disabled", false);
					divOutput.html(o);
					if (responsepid != undefined) {
						queryResponse = responsepid;
						spanIDTitle.html(responsepid);
					}
				}
			}).fail(function(a, b, c) {
				messagessys.failure(b + " - " + c);
			}).always(function() {
				overlay.hide();
			});
		}
		buttonEdit.on("click", function() {
			if (queryResponse !== false) {
				linkTrigger.prop("href", "<?= $tables->pagefile_info(134, null, "directory") . "/?method=update&id="; ?>" + queryResponse);
				linkTrigger[0].click();
			}
		});
		buttonPrintID.on("click", function() {
			if (queryResponse !== false) {
				linkTrigger.prop("href", "<?= $tables->pagefile_info(28, null, "directory") . "/?id="; ?>" + queryResponse);
				linkTrigger[0].click();
			}
		});
		<?php
		if (isset($_GET['id'])) {
			$_GET['id'] = (int)$_GET['id'];
			$r = $sql->query("SELECT CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) as user_name FROM users WHERE usr_id={$_GET['id']};");
			if ($r && $row = $sql->fetch_assoc($r)) {
				echo 'SLO_employeeID.set("' . $_GET['id'] . '","' . stripcslashes(trim($row['user_name'])) . '");';
				echo 'history.replaceState({\'method\':\'view\', \'id\': ' . (int)$_GET['id'] . ', \'name\': \'' . $row['user_name'] . '\'}, "' . $tables->pagefile_info(182, null, "title") . '", "' . $tables->pagefile_info(182, null, "directory") . '/?id=' . (int)$_GET['id'] . '");';
				echo 'fn_fetchfile(false);';
			}
		}
		?>
		window.onpopstate = function(e) {
			if (e.state && e.state.method == "view") {
				SLO_employeeID.set(e.state.id, e.state.name);
				fn_fetchfile();
			} else {
				clear();
				SLO_employeeID.clear(false);
			}
		};

		SLO_employeeID.focus();

	});
</script>