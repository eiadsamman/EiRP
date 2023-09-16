<?php

$debug = false;

use System\App;
use System\SmartListObject;


function fnConvOnlyNumbers($input)
{
	$output = "";
	for ($i = 0; $i < strlen($input); $i++) {
		if (is_numeric($input[$i]) || in_array($input[$i], array("+", "\n"))) {
			$output .= $input[$i];
		}
	}
	return $output;
}
function check_date($input)
{
	if (isset($input) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $input, $match)) {
		if (checkdate($match[2], $match[3], $match[1])) {
			return true;
		}
	}
	return false;
}

if (isset($_POST['method'], $_POST['sal_workingtime'], $_POST['sal_paymethod'], $_POST['sal_job']) && $_POST['method'] == "get_salary_information") {
	if (!$c__actions->edit) {
		$app->responseStatus->BadRequest->response();
	}
	$_POST['sal_workingtime'] = (int)$_POST['sal_workingtime'];
	$_POST['sal_paymethod'] = (int)$_POST['sal_paymethod'];
	$_POST['sal_job'] = (int)$_POST['sal_job'];

	$output = false;
	$r = $app->db->query("SELECT lbr_typ_sal_basic_salary,lbr_typ_sal_variable,lbr_typ_sal_allowance,lbr_typ_sal_transportation FROM labour_type_salary
						WHERE lbr_typ_sal_lty_id={$_POST['sal_job']} AND lbr_typ_sal_lwt_id={$_POST['sal_workingtime']} AND lbr_typ_sal_method={$_POST['sal_paymethod']}");
	if ($r) {
		if ($row = $r->fetch_assoc()) {
			$output = array(
				"basic" => number_format($row['lbr_typ_sal_basic_salary'], 2, ".", ""),
				"variable" => number_format($row['lbr_typ_sal_variable'], 2, ".", ""),
				"allowance" => number_format($row['lbr_typ_sal_allowance'], 2, ".", ""),
				"transportation" => number_format($row['lbr_typ_sal_transportation'], 2, ".", ""),
			);
		}
	}
	if ($output)
		echo json_encode($output);
	else
		echo "false";
	exit;
}
$arr_array_input = false;


if (isset($_POST['EmployeeFormMethod'], $_POST['EmployeeFormID'], $_POST['Token']) && $_POST['EmployeeFormMethod'] == "proccessHandler") {
	$defaultPermissionID = 0;
	$rminper = $app->db->query("SELECT per_id FROM `permissions` WHERE `per_order` = ( SELECT MIN(`per_order`) FROM `permissions` );");
	if ($rminper) {
		if ($rminperRow = $rminper->fetch_assoc()) {
			$defaultPermissionID = (int)$rminperRow['per_id'];
		}
	}

	$arrparser = array("result" => true, "type" => 1, "source" => array());
	if ($_POST['Token'] != session_id()) {
		$arrparser['result'] = false;
		$arrparser['source']["global"] = "Invalid token";
	}

	$employeeID = (int)$_POST['EmployeeFormID'];
	if ((int)$employeeID == 0) {
		$arrparser['type'] = 0;
	} else {
		$arrparser['type'] = 1;
	}

	//Friendly output messages
	$arrerrors = array(
		"firstname" => "Firstname",
		"lastname" => "Lastname",
		"nationality" => "Nationality",
		"social_number" => "Social Number",
		"social_id_image" => "Social ID Image",
		"perosnal_image" => "Personal Image",
		"gender" => "Gender",
		"birthdate" => "Birthdate",
		"phone_list" => "",
		"residence" => "Residence",
		"transportation" => "Transportation",
		"edu_cert_image" => "*",
		"company" => "Company",
		"regdate" => "Registartion date",
		"jobtitle" => "Job title",
		"workingtimes" => "Working times",
		"payment" => "Payment method",
		"shift" => "Working shift",
	);
	//Check input validity
	$arroutput = array(
		"result" => true,
		"source" => array(
			"firstname" => (isset($_POST['firstname']) && trim($_POST['firstname']) != "" ? "'" . addslashes(trim($_POST['firstname'])) . "'" : false),/*required*/
			"lastname" => (isset($_POST['lastname']) && trim($_POST['lastname']) != "" ? "'" . addslashes(trim($_POST['lastname'])) . "'" : null),
			"nationality" => (isset($_POST['nationality'][1]) && (int)$_POST['nationality'][1] != 0 ? (int)$_POST['nationality'][1] : null),
			"social_number" => (isset($_POST['social_number']) && fnConvOnlyNumbers($_POST['social_number']) != "" ? fnConvOnlyNumbers($_POST['social_number']) : null),
			"social_id_image" => (isset($_POST['social_id_image']) && is_array($_POST['social_id_image']) && !empty($_POST['social_id_image']) ? $_POST['social_id_image'] : null),
			"perosnal_image" => (isset($_POST['perosnal_image']) && is_array($_POST['perosnal_image']) && !empty($_POST['perosnal_image']) ? $_POST['perosnal_image'] : null),
			"gender" => (isset($_POST['gender'][1]) && (int)$_POST['gender'][1] != 0 ? (int)$_POST['gender'][1] : null),
			"birthdate" => (isset($_POST['birthdate'][1]) && check_date($_POST['birthdate'][1]) ? "'" . $_POST['birthdate'][1] . "'" : null),
			"phone_list" => (isset($_POST['phone_list']) && trim($_POST['phone_list']) != "" ? "'" . fnConvOnlyNumbers(trim($_POST['phone_list'])) . "'" : null),

			"residence" => (isset($_POST['residence'][1]) && (int)$_POST['residence'][1] != 0 ? (int)$_POST['residence'][1] : null),
			"transportation" => (isset($_POST['transportation'][1]) && (int)$_POST['transportation'][1] != 0 ? (int)$_POST['transportation'][1] : null),
			"edu_cert_image" => (isset($_POST['edu_cert_image']) && is_array($_POST['edu_cert_image']) && !empty($_POST['edu_cert_image']) ? $_POST['edu_cert_image'] : null),
			"company" => (isset($_POST['company'][1]) && (int)$_POST['company'][1] != 0 ? (int)$_POST['company'][1] : false),/*required*/
			"regdate" => (isset($_POST['regdate'][1]) && check_date($_POST['regdate'][1]) ? "'" . $_POST['regdate'][1] . "'" : false),/*required*/
			"resdate" => (isset($_POST['resdate'][1]) && check_date($_POST['resdate'][1]) ? "'" . $_POST['resdate'][1] . "'" : null),
			"jobtitle" => (isset($_POST['jobtitle'][1]) && (int)$_POST['jobtitle'][1] != 0 ? (int)$_POST['jobtitle'][1] : false),/*required*/
			"workingtimes" => (isset($_POST['workingtimes'][1]) && (int)$_POST['workingtimes'][1] != 0 ? (int)$_POST['workingtimes'][1] : false),/*required*/
			"payment" => (isset($_POST['payment'][1]) && (int)$_POST['payment'][1] != 0 ? (int)$_POST['payment'][1] : false),/*required*/
			"shift" => (isset($_POST['shift'][1]) && (int)$_POST['shift'][1] != 0 ? (int)$_POST['shift'][1] : null),
		)
	);

	//Check each field validity (false: required and not presented or invalid, null: not required and not presented or invalid, true: presented and valid)
	foreach ($arroutput['source'] as $k => $v) {
		if ($v === false) {
			$arrparser['result'] = false;
			$arrparser['source'][$k] = isset($arrerrors[$k]) ? $arrerrors[$k] : "";
		} elseif ($v === null) {
			/*Not required field*/
			$arroutput['source'][$k] = "NULL";
		} else {
			/*Passed*/
		}
	}
	//Role bin proccessing
	$roleoutput = (isset($_POST['role'][3]) ? "1" : "0") . (isset($_POST['role'][2]) ? "1" : "0") . (isset($_POST['role'][1]) ? "1" : "0");
	$roleoutput = bindec($roleoutput);

	if ($arrparser['result']) {
		$app->db->autocommit(false);
		$q = sprintf(
			"
				INSERT INTO 
					users
				(
					usr_id,
					usr_username,
					usr_password,
					usr_firstname,
					usr_lastname,
					usr_gender,
					usr_phone_list,
					usr_activate,
					usr_birthdate,
					usr_privileges
					) VALUES 
				(	%8\$s,
					'%1\$s',
					'%2\$s',
					%3\$s,
					%4\$s,
					%5\$d,
					%6\$s,
					0,
					%7\$s,
					$defaultPermissionID
				)
				 ON DUPLICATE KEY UPDATE usr_id=LAST_INSERT_ID(usr_id),
					usr_firstname=%3\$s,
					usr_lastname=%4\$s,
					usr_gender=%5\$d,
					usr_phone_list=%6\$s,
					usr_birthdate=%7\$s
				 	;
				",
			/*username*/
			uniqid(),
			/*password*/
			"",
			/*firstname*/
			$arroutput['source']['firstname'],
			/*lastname*/
			$arroutput['source']['lastname'],
			/*gender*/
			$arroutput['source']['gender'],
			/*phonelist*/
			$arroutput['source']['phone_list'],
			/*birthdate*/
			$arroutput['source']['birthdate'],
			/*userid*/
			($employeeID === 0 ? "NULL" : $employeeID)
		);

		if ($app->db->query($q)) {
			$UserID = $app->db->insert_id;


			$UploadsIDs = "";
			$UploadsFound = false;
			$UploadsSep = "";
			if ((isset($arroutput['source']['perosnal_image']) && is_array($arroutput['source']['perosnal_image']) && sizeof((array)$arroutput['source']['perosnal_image']) > 0)) {
				foreach ((array)$arroutput['source']['perosnal_image'] as $UploadItem) {
					$UploadsFound = true;
					$UploadsIDs .= $UploadsSep . (int)$UploadItem;
					$UploadsSep = ",";
				}
			}
			if ((isset($arroutput['source']['social_id_image']) && is_array($arroutput['source']['social_id_image']) && sizeof((array)$arroutput['source']['social_id_image']) > 0)) {
				foreach ((array)$arroutput['source']['social_id_image'] as $UploadItem) {
					$UploadsFound = true;
					$UploadsIDs .= $UploadsSep . (int)$UploadItem;
					$UploadsSep = ",";
				}
			}
			$releaseUploads = $app->db->query("UPDATE uploads SET up_rel=0 WHERE up_rel=$UserID AND (up_pagefile=" . $app->scope->individual->portrait . " OR up_pagefile=" . $app->scope->individual->social_id . ");");
			if ($releaseUploads) {
				if ($UploadsFound) {
					if (!$app->db->query("UPDATE uploads SET up_rel=$UserID WHERE up_id IN ({$UploadsIDs}) AND (up_pagefile=" . $app->scope->individual->portrait . " OR up_pagefile=" . $app->scope->individual->social_id . ");")) {
						$arrparser['result'] = false;
						$arrparser['source']["global"] = "Assinging uploads to the employee failed";
					}
				}
			} else {
				$arrparser['result'] = false;
				$arrparser['source']["global"] = "Assinging uploads to the employee failed";
			}




			if ($arrparser['result']) {
				$q_labour_insert = sprintf(
					"
						INSERT INTO 
							labour 
								(
									lbr_id,
									lbr_type,
									lbr_shift,
									lbr_fixedtime,
									lbr_workingdays,
									lbr_residential,
									lbr_registerdate,
									lbr_socialnumber,
									lbr_nationality,
									lbr_payment_method,
									lbr_workingtimes,
									lbr_transportation,
									lbr_resigndate,
									lbr_company
									" . ($fs(229)->permission->edit ? ",lbr_fixedsalary,lbr_variable,lbr_allowance" : "") . "
									,lbr_role
									) VALUES 
								(
									%1\$d,
									%2\$d,
									%3\$d,
									NULL,
									NULL,
									%4\$d,
									%5\$s,
									%6\$s,
									%7\$d,
									%8\$d,
									%9\$s,
									%10\$d,
									%11\$s,
									%12\$d
									" . ($fs(229)->permission->edit ? ",%13\$s,%14\$s,%15\$s" : "") . "
									,%16\$d
								)
						 ON DUPLICATE KEY UPDATE lbr_id=LAST_INSERT_ID(lbr_id),
						 	lbr_type=%2\$d,
							lbr_shift=%3\$d,
							lbr_fixedtime=NULL,
							lbr_workingdays=NULL,
							lbr_residential=%4\$d,
							lbr_registerdate=%5\$s,
							lbr_socialnumber=%6\$s,
							lbr_nationality=%7\$d,
							lbr_payment_method=%8\$d,
							lbr_workingtimes=%9\$s,
							lbr_transportation=%10\$d,
							lbr_resigndate=%11\$s,
							lbr_company=%12\$d
							" . ($fs(229)->permission->edit ? ",lbr_fixedsalary=%13\$s,lbr_variable=%14\$s,lbr_allowance=%15\$s" : "") . "
							,lbr_role=%16\$d
							;",
					/* 1: userid*/
					$UserID,
					/* 2: jobtitle*/
					$arroutput['source']['jobtitle'],
					/* 3: shift*/
					$arroutput['source']['shift'],
					/* 4: residence*/
					$arroutput['source']['residence'],
					/* 5: regdate*/
					$arroutput['source']['regdate'],
					/* 6: socialid*/
					$arroutput['source']['social_number'],
					/* 7: nationality*/
					$arroutput['source']['nationality'],
					/* 8: paymethod*/
					$arroutput['source']['payment'],
					/* 9: workingtimes*/
					$arroutput['source']['workingtimes'],
					/*10: transpor*/
					is_null($arroutput['source']['transportation']) ? "NULL" : $arroutput['source']['transportation'],
					/*11: resign*/
					is_null($arroutput['source']['resdate']) ? "NULL" : $arroutput['source']['resdate'],
					/*12: company*/
					$arroutput['source']['company'],
					/*13: salary*/
					(isset($_POST['sal_default_salary']) ? "NULL" : (float)$_POST['salary_basic']),
					/*14: variable*/
					(isset($_POST['sal_default_variable']) ? "NULL" : (float)$_POST['salary_variable']),
					/*15: allowance*/
					(isset($_POST['sal_default_allowance']) ? "NULL" : (float)$_POST['salary_allowance']),
					/*16: role*/
					$roleoutput

				);
				if (!$app->db->query($q_labour_insert)) {
					$arrparser['result'] = false;
					$arrparser['source']["global"] = "Updating employee information failed!";
				}
			}
		} else {
			$arrparser['result'] = false;
			$arrparser['source']["global"] = "Updating employee information failed!";
		}
	}
	if ($arrparser['result']) {
		$arrparser['source']["global"] = "Employee information updated successfully!";
		$app->db->commit();
	} else {
		$app->db->rollback();
	}
	echo json_encode($arrparser);
	exit;
}

$UserFound = false;
if (isset($_GET['method'], $_GET['id'])) {
	$UserQuery = $app->db->query("SELECT usr_id,usr_firstname, usr_lastname FROM users WHERE usr_id =" . ((int)$_GET['id']) . "");
	if ($UserQuery) {
		if ($UserRow = $UserQuery->fetch_assoc()) {
			$UserFound = $UserRow;
		}
	}
}

if ($app->xhttp) {
	exit;
}

$SmartListObject = new System\SmartListObject($app);

?>

<div style="padding:20px 0px 10px 0px;min-width:300px;max-width:800px;background-color: #fff;position: sticky;top:43px;z-index: 20;">
	<div class="btn-set">
		<span>Employee Name \ ID</span><input type="text" id="employeIDFormSearch" data-slo=":LIST" data-list="personList" class="flex" value="<?php echo $UserFound ? $UserFound['usr_firstname'] . " " . $UserFound['usr_lastname'] : ""; ?>" <?php echo $UserFound ? "data-slodefaultid=\"" . $UserFound['usr_id'] . "\"" : ""; ?> placeholder="Select user..." />
	</div>
	<datalist id="personList">
		<?= $SmartListObject->system_individual($app->user->company->id); ?>
	</datalist>
</div>

<div style="position: relative;min-width:300px;max-width:800px;">
	<div id="FormModifyOverLay" style="display:none;position: absolute;background-color: rgba(230,230,234,0.7);top:0px;left:0px;right:0px;bottom: 0px;z-index: 15;cursor: wait;"></div>
	<div id="UILoad"></div>
</div>

<script type="text/javascript">
	$(document).ready(function(e) {
		var $FormModifyOverLay = $("#FormModifyOverLay");
		var $UILoad = $("#UILoad");
		var $Form = $("#EmployeeForm");

		var Loader = function(method, id) {
			$FormModifyOverLay.show();
			$.ajax({
				url: '<?= $fs(219)->dir ?>',
				type: 'POST',
				data: {
					'method': method,
					'id': id,
					'frole': <?= isset($fixedrole) ? $fixedrole : 0; ?>
				}
			}).done(function(data) {
				SLO_employeeID.enable();
				$("#employeIDFormSearch").css("cursor", "text");

				$FormModifyOverLay.hide();
				$UILoad.html(data);
				RaiseEvenets();
			});
		}

		<?php
		if ($UserFound) {
			echo "history.replaceState({'method':'update', 'id': {$UserFound['usr_id']}, 'name': '{$UserFound['usr_firstname']} {$UserFound['usr_lastname']}'}, '" . $fs(134)->title . "', '" .  $fs(134)->dir  . "/?method=update&id={$UserFound['usr_id']}');";
			echo "Loader(\"update\", {$UserFound['usr_id']})";
		} else {
			echo "history.replaceState({'method':'', 'id': 0, 'name': ''}, '" .  $fs(50)->title  . "', '" . $fs(50)->dir . "');";
			echo "Loader(\"add\", 0)";
		}
		?>

		var SLO_employeeID = $("#employeIDFormSearch").slo({
			onselect: function(value) {
				//SLO_employeeID.disable();
				// SLO_employeeID.disable();
				$("#employeIDFormSearch").css("cursor", "wait");

				history.pushState({
					'method': 'update',
					'id': value.hidden,
					'name': value.value
				}, "<?= $fs(134)->title ?>", "<?= $fs(134)->dir ?>/?method=update&id=" + value.hidden);
				Loader("update", value.hidden);
			},
			ondeselect: function() {
				//SLO_employeeID.disable();
				$("#employeIDFormSearch").css("cursor", "wait");

				history.pushState({
					'method': '',
					'id': 0,
					'name': ''
				}, "<?= $fs(50)->dir ?>", "<?= $fs(50)->dir ?>");
				Loader("add", 0);
			},
			"limit": 10,
		});

		SLO_employeeID.disable();
		$("#employeIDFormSearch").css("cursor", "wait");

		window.onpopstate = function(e) {
			if (e.state.method == "update") {
				SLO_employeeID.set(e.state.id, e.state.name);
				Loader("update", e.state.id);
			} else {
				SLO_employeeID.clear(false);
				Loader("add", 0);
			}
		};
		<?php if ($fs(229)->permission->edit) { ?>
			var salary_clear = function() {
				$("input[name=salary_basic]").attr("data-basicvalue", "0.00");
				$("input[name=salary_variable]").attr("data-basicvalue", "0.00");
				$("input[name=salary_allowance]").attr("data-basicvalue", "0.00");
				if ($("[name=sal_default_salary]").prop("checked")) {
					$("[name=salary_basic]").val("0.00").prop("disabled", true);
				}
				if ($("[name=sal_default_variable]").prop("checked")) {
					$("[name=salary_variable]").val("0.00").prop("disabled", true);
				}
				if ($("[name=sal_default_allowance]").prop("checked")) {
					$("[name=salary_allowance]").val("0.00").prop("disabled", true);
				}
			}

			var get_salary_information = function() {
				$.ajax({
					url: "<?php echo "{$fs()->dir}"; ?>",
					type: "POST",
					data: {
						'method': 'get_salary_information',
						'sal_workingtime': ($("#sloworkingtimes_1").val()),
						'sal_paymethod': ($("#slopayment_1").val()),
						'sal_job': ($("#slotype_1").val()),
					}
				}).done(function(output) {
					if (output == "false") {
						salary_clear();
						return;
					}
					var json = false;
					try {
						json = JSON.parse(output);
					} catch (e) {
						messagesys.failure("Parsing output failed");
						return false;
					}

					$("input[name=salary_basic]").attr("data-basicvalue", json.basic);
					$("input[name=salary_variable]").attr("data-basicvalue", json.variable);
					$("input[name=salary_allowance]").attr("data-basicvalue", json.allowance);

					if ($("[name=sal_default_salary]").prop("checked")) {
						$("[name=salary_basic]").val($("input[name=salary_basic]").attr("data-basicvalue"));
					}
					if ($("[name=sal_default_variable]").prop("checked")) {
						$("[name=salary_variable]").val($("input[name=salary_variable]").attr("data-basicvalue"));
					}
					if ($("[name=sal_default_allowance]").prop("checked")) {
						$("[name=salary_allowance]").val($("input[name=salary_allowance]").attr("data-basicvalue"));
					}
				});
			}
		<?php } ?>

		var RaiseEvenets = function() {
			var UploadUserPersonalImage = $.Upload({
				objectHandler: $("#js_upload_list"),
				domselector: $("#js_uploader_btn"),
				dombutton: $("#js_upload_trigger"),
				list_button: $("#js_upload_count"),
				emptymessage: "[No files uploaded]",
				upload_url: "<?= $fs(186)->dir ?>",
				relatedpagefile: <?php echo $app->scope->individual->portrait; ?>,
				multiple: false,
				inputname: "perosnal_image",
				domhandler: $("#UploadPersonalDOMHandler"),
				align: "right",
				onupload: function(output) {}
			}).update();


			var UploadUserSocialID = $.Upload({
				objectHandler: $("#js_upload_list_1"),
				domselector: $("#js_uploader_btn_1"),
				dombutton: $("#js_upload_trigger_1"),
				list_button: $("#js_upload_count_1"),
				emptymessage: "[No files uploaded]",
				upload_url: "<?= $fs(134)->dir ?>",
				relatedpagefile: <?php echo $app->scope->individual->social_id; ?>,
				multiple: true,
				inputname: "social_id_image",
				align: "right",
				domhandler: $("#UploadSocialDOMHandler"),
			}).update();

			var slotype = $("#slotype").slo({
					onselect: function(value) {
						get_salary_information();
					},
					ondeselect: function() {
						salary_clear();
					},
					'limit': 10
				}),
				sloshif = $("#sloshift").slo({
					'limit': 10
				}),
				sloresi = $("#sloresidence").slo({
					'limit': 10
				}),
				slogend = $("#slogender").slo({
					'limit': 10
				}),
				sloregister = $("#sloregdate").slo({
					'limit': 5
				}),
				sloresister = $("#sloresdate").slo({
					'limit': 5
				}),
				slorbirthdate = $("#slobirthdate").slo({
					'limit': 10
				}),
				slocompany = $("#slocompany").slo({
					'limit': 10
				}),
				sloworktimes = $("#sloworkingtimes").slo({
					onselect: function(value) {
						get_salary_information();
					},
					ondeselect: function() {
						salary_clear();
					},
					'limit': 10
				}),
				slorpaymethod = $("#slopayment").slo({
					onselect: function(value) {
						get_salary_information();
					},
					ondeselect: function() {
						salary_clear();
					},
					'limit': 10
				}),
				slortransportation = $("#slortransportation").slo({
					'limit': 10
				}),
				slonationality = $("#slonationality").slo({
					'limit': 10
				});

			<?php if ($fs(229)->permission->edit) { ?>
				$(".derive_function").on('change', function() {
					var $this = $(this);
					var _result = $this.prop("checked");
					$this.parent().prev().prop("disabled", _result);
					if (_result) {
						$("[name=" + $this.attr('data-rel') + "]").val($("input[name=" + $this.attr('data-rel') + "]").attr("data-basicvalue"));
					} else {
						$("[name=" + $this.attr('data-rel') + "]").val("0.00");
					}
				});
			<?php } ?>


			$Form = $("#EmployeeForm");
			$Form.on('submit', function(e) {
				$FormModifyOverLay.show();
				$(".EmployeeFormSubmitButton").prop("disabled", true);
				e.preventDefault();
				$.ajax({
					url: '<?php echo $fs()->dir; ?>',
					type: 'POST',
					data: $Form.serialize()
				}).done(function(data) {

					$FormModifyOverLay.hide();
					$(".EmployeeFormSubmitButton").prop("disabled", false);

					try {
						var json = JSON.parse(data);
					} catch (e) {
						messagesys.failure("Server response error");
						return false;
					}
					if (json.result) {
						if (json.type == 0) {
							messagesys.success("Employee added successfully");
							$("#lastname").val("");
							$("#phone_list").val("");
							$("#social_number").val("");
							UploadUserPersonalImage.clean();
							UploadUserSocialID.clean();
							sloshif.clear();
							sloresi.clear();
							slogend.clear();
							sloregister.clear();
							sloresister.clear();
							slorbirthdate.clear();
							slocompany.clear();
							sloworktimes.clear();
							slorpaymethod.clear();
							slortransportation.clear();
							slonationality.clear();
							slotype.clear();
							<?php if ($fs(229)->permission->edit) { ?>
								$("[name=sal_default_salary]").prop("checked", true);
								$("[name=sal_default_variable]").prop("checked", true);
								$("[name=sal_default_allowance]").prop("checked", true);
								salary_clear();
							<?php } ?>
							$("#firstname").val("").focus();
						} else {
							messagesys.success("Employee record updated successfully");
						}
					} else {
						var outputMessage = "Feilds `";
						var smartSep = false;
						for (var errkey in json.source) {
							outputMessage += (smartSep ? ", " : "") + json.source[errkey];
							smartSep = true;
						}
						outputMessage += "` are required";
						messagesys.failure(outputMessage);
					}
				});
				return false;
			});

		}

	});
</script>