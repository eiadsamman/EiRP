<?php
$debug = false;

use System\Template\Gremium\Gremium;

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

function validateInput($input, $type, $default = null)
{
	if ($input == null)
		return false;
	if ($type == "string") {
		return isset($input) && trim($input) != "" ? "'" . addslashes(trim($input)) . "'" : $default;
	} elseif ($type == "int") {
		return isset($input) && (int) $input != 0 ? (int) $input : $default;
	} elseif ($type == "date") {
		return check_date($input) ? "'{$input}'" : $default;
	} elseif ($type == "array") {
		return isset($input) && is_array($input) && sizeof($input) > 0 ? $input : $default;
	}
}

if (isset($_POST['method'], $_POST['sal_workingtime'], $_POST['sal_paymethod'], $_POST['sal_job']) && $_POST['method'] == "get_salary_information") {
	if (!$fs()->permission->edit) {
		$app->responseStatus->BadRequest->response();
	}
	$_POST['sal_workingtime'] = (int) $_POST['sal_workingtime'];
	$_POST['sal_paymethod']   = (int) $_POST['sal_paymethod'];
	$_POST['sal_job']         = (int) $_POST['sal_job'];

	$output = false;
	$r      = $app->db->query("SELECT lbr_typ_sal_basic_salary,lbr_typ_sal_variable,lbr_typ_sal_allowance,lbr_typ_sal_transportation FROM labour_type_salary
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
	$currentDate         = new DateTime();
	$rminper             = $app->db->query("SELECT per_id FROM `permissions` WHERE `per_order` = ( SELECT MIN(`per_order`) FROM `permissions` );");
	if ($rminper) {
		if ($rminperRow = $rminper->fetch_assoc()) {
			$defaultPermissionID = (int) $rminperRow['per_id'];
		}
	}

	$arrparser = array("result" => true, "type" => 1, "source" => array());
	if ($_POST['Token'] != session_id()) {
		$arrparser['result']           = false;
		$arrparser['source']["global"] = "Invalid token";
	}

	$employeeID = (int) $_POST['EmployeeFormID'];
	if ((int) $employeeID == 0) {
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
		"jobtitle" => "Job title",
		"workingtimes" => "Working times",
		"payment" => "Payment method",
		"shift" => "Working shift",
	);
	//Check input validity
	$arroutput = array(
		"result" => true,
		"source" => array(
			"firstname" => (validateInput($_POST['firstname'] ?? null, "string", false) ? "'" . addslashes(trim($_POST['firstname'])) . "'" : false),
			"lastname" => (validateInput($_POST['lastname'] ?? null, "string") ? "'" . addslashes(trim($_POST['lastname'])) . "'" : null),
			"nationality" => (validateInput($_POST['nationality'][1] ?? null, "int") ? (int) $_POST['nationality'][1] : null),
			"social_number" => (isset($_POST['social_number']) && fnConvOnlyNumbers($_POST['social_number']) != "" ? fnConvOnlyNumbers($_POST['social_number']) : null),
			"social_id_image" => (validateInput($_POST['social_id_image'] ?? null, "array") ? $_POST['social_id_image'] : null),
			"perosnal_image" => (validateInput($_POST['perosnal_image'] ?? null, "array") ? $_POST['perosnal_image'] : null),
			"gender" => (validateInput($_POST['gender'][1] ?? null, "int") ? (int) $_POST['gender'][1] : null),
			"birthdate" => (validateInput($_POST['birthdate'][1] ?? null, "date") ? "'" . $_POST['birthdate'][1] . "'" : null),
			"phone_list" => (validateInput($_POST['phone_list'] ?? null, "string") != "" ? "'" . fnConvOnlyNumbers(trim($_POST['phone_list'])) . "'" : null),
			"residence" => (validateInput($_POST['residence'][1] ?? null, "int") ? (int) $_POST['residence'][1] : null),
			"transportation" => (validateInput($_POST['transportation'][1] ?? null, "int") ? (int) $_POST['transportation'][1] : null),
			"edu_cert_image" => (validateInput($_POST['edu_cert_image'] ?? null, "array") ? $_POST['edu_cert_image'] : null),
			"company" => (validateInput($_POST['company'][1] ?? null, "int") ? (int) $_POST['company'][1] : false),
			"resdate" => (validateInput($_POST['resdate'][1] ?? null, "date") ? "'" . $_POST['resdate'][1] . "'" : null),
			"jobtitle" => (validateInput($_POST['jobtitle'][1] ?? null, "int") ? (int) $_POST['jobtitle'][1] : false),
			"workingtimes" => (validateInput($_POST['workingtimes'][1] ?? null, "int") ? (int) $_POST['workingtimes'][1] : null),
			"payment" => (validateInput($_POST['payment'][1] ?? null, "int") ? (int) $_POST['payment'][1] : null),
			"shift" => (validateInput($_POST['shift'][1] ?? null, "int") ? (int) $_POST['shift'][1] : null),
		)
	);

	//Check each field validity (false: required and not presented or invalid, null: not required and not presented or invalid, true: presented and valid)
	foreach ($arroutput['source'] as $k => $v) {
		if ($v === false) {
			$arrparser['result']     = false;
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
			"INSERT INTO 
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
				usr_privileges,
				usr_registerdate,
				usr_role,
				usr_entity,
				usr_jobtitle
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
				$defaultPermissionID,
				%9\$s,
				%10\$d,
				%11\$d,
				%12\$d
			)
				ON DUPLICATE KEY UPDATE usr_id=LAST_INSERT_ID(usr_id),
				usr_firstname = %3\$s,
				usr_lastname = %4\$s,
				usr_gender = %5\$d,
				usr_phone_list = %6\$s,
				usr_birthdate = %7\$s,
				usr_role = %10\$d,
				usr_entity = %11\$d,
				usr_jobtitle = %12\$d
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
			$employeeID === 0 ? "NULL" : $employeeID,
			/*Register Date*/
			"'" . $currentDate->format("Y-m-d") . "'",
			/*Role (Customer, Vendor, Employee)*/
			$roleoutput,
			/*Company*/
			$arroutput['source']['company'],
			/*Job title*/
			$arroutput['source']['jobtitle'],
		);

		if ($app->db->query($q)) {
			$UserID       = $app->db->insert_id;
			$UploadsIDs   = "";
			$UploadsFound = false;
			$UploadsSep   = "";
			if ((isset($arroutput['source']['perosnal_image']) && is_array($arroutput['source']['perosnal_image']) && sizeof((array) $arroutput['source']['perosnal_image']) > 0)) {
				foreach ((array) $arroutput['source']['perosnal_image'] as $UploadItem) {
					$UploadsFound = true;
					$UploadsIDs .= $UploadsSep . (int) $UploadItem;
					$UploadsSep   = ",";
				}
			}
			if ((isset($arroutput['source']['social_id_image']) && is_array($arroutput['source']['social_id_image']) && sizeof((array) $arroutput['source']['social_id_image']) > 0)) {
				foreach ((array) $arroutput['source']['social_id_image'] as $UploadItem) {
					$UploadsFound = true;
					$UploadsIDs .= $UploadsSep . (int) $UploadItem;
					$UploadsSep   = ",";
				}
			}
			$releaseUploads = $app->db->query("UPDATE uploads SET up_rel=0 WHERE up_rel=$UserID AND (up_pagefile=" . \System\Attachment\Type::HrPerson->value . " OR up_pagefile=" . \System\Attachment\Type::HrID->value . ");");
			if ($releaseUploads) {
				if ($UploadsFound) {
					if (!$app->db->query("UPDATE uploads SET up_rel=$UserID WHERE up_id IN ({$UploadsIDs}) AND (up_pagefile=" . \System\Attachment\Type::HrPerson->value . " OR up_pagefile=" . \System\Attachment\Type::HrID->value . ");")) {
						$arrparser['result']           = false;
						$arrparser['source']["global"] = "Assinging uploads to the employee failed";
					}
				}
			} else {
				$arrparser['result']           = false;
				$arrparser['source']["global"] = "Assinging uploads to the employee failed";
			}




			if ($arrparser['result']) {
				$q_labour_insert = sprintf(
					"INSERT INTO 
						labour 
							(
								lbr_id,
								lbr_shift,
								lbr_residential,
								lbr_socialnumber,
								lbr_nationality,
								lbr_payment_method,
								lbr_workingtimes,
								lbr_transportation,
								lbr_resigndate
								" . ($fs(229)->permission->edit ? ",lbr_fixedsalary,lbr_variable,lbr_allowance" : "") . "
								) VALUES 
							(
								%1\$d,
								%2\$d,
								%3\$d,
								%4\$s,
								%5\$d,
								%6\$d,
								%7\$s,
								%8\$d,
								%9\$s
								" . ($fs(229)->permission->edit ? ",%10\$s,%11\$s,%12\$s" : "") . "
							)
						 ON DUPLICATE KEY UPDATE lbr_id=LAST_INSERT_ID(lbr_id),
							lbr_shift = %2\$d,
							lbr_residential = %3\$d,
							lbr_socialnumber = %4\$s,
							lbr_nationality = %5\$d,
							lbr_payment_method = %6\$d,
							lbr_workingtimes = %7\$s,
							lbr_transportation = %8\$d,
							lbr_resigndate = %9\$s
							" . ($fs(229)->permission->edit ? ",lbr_fixedsalary = %10\$s, lbr_variable = %11\$s, lbr_allowance = %12\$s" : "") . ";",
					/* 1: userid*/
					$UserID,
					/* 3: shift*/
					$arroutput['source']['shift'],
					/* 4: residence*/
					$arroutput['source']['residence'],
					/* 5: socialid*/
					$arroutput['source']['social_number'],
					/* 6: nationality*/
					$arroutput['source']['nationality'],
					/* 7: paymethod*/
					$arroutput['source']['payment'],
					/* 8: workingtimes*/
					$arroutput['source']['workingtimes'],
					/* 9: transpor*/
					is_null($arroutput['source']['transportation']) ? "NULL" : $arroutput['source']['transportation'],
					/* 10: resign*/
					is_null($arroutput['source']['resdate']) ? "NULL" : $arroutput['source']['resdate'],
					/* 11: salary*/
					isset($_POST['sal_default_salary']) ? "NULL" : (float) $_POST['salary_basic'],
					/* 12: variable*/
					isset($_POST['sal_default_variable']) ? "NULL" : (float) $_POST['salary_variable'],
					/* 13: allowance*/
					isset($_POST['sal_default_allowance']) ? "NULL" : (float) $_POST['salary_allowance']
				);
				if (!$app->db->query($q_labour_insert)) {
					$arrparser['result']           = false;
					$arrparser['source']["global"] = "Updating employee information failed!";
				}
			}
		} else {
			$arrparser['result']           = false;
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
	$UserQuery = $app->db->query("SELECT usr_id,usr_firstname, usr_lastname FROM users WHERE usr_id =" . ((int) $_GET['id']) . "");
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

echo "<form id=\"EmployeeForm\" method=\"POST\">";
$grem = new Gremium(true);
$grem->header()->prev("href=\"{$fs(30)->dir}\"")->serve("<h1 id=\"js-output_headertitle\">" . ($UserFound ? "Edit employee" : "Add employee") . "</h1><ul id=\"js-output_headercite\">" . ($UserFound ? "<li>{$UserFound['usr_id']}</li>" : "") . "</ul>");
$grem->menu()->open();
echo "<input type=\"text\" id=\"employeIDFormSearch\" data-slo=\":LIST\" data-list=\"personList\" class=\"flex\" placeholder=\"Select user...\" />";
echo "<button id=\"js-input_submit-button\" type=\"button\">Save</button>";
$grem->getLast()->close();
echo ("<div id=\"UILoad\"></div>");
$grem->terminate();
echo "</form>";
?>

<datalist id="personList">
	<?= $SmartListObject->systemIndividual($app->user->company->id, $UserFound ? $UserFound['usr_id'] : null); ?>
</datalist>

<script type="text/javascript">
	$(document).ready(function (e) {
		var busy = false;
		const js_output_headertitle = $("#js-output_headertitle"),
			js_output_headercite = $("#js-output_headercite");
		var $UILoad = $("#UILoad");
		var $Form = $("#EmployeeForm");
		var Loader = function (method, id) {
			$.ajax({
				url: '<?= $fs(219)->dir ?>',
				type: 'POST',
				data: {
					'method': method,
					'id': id,
					'frole': <?= isset($fixedrole) ? $fixedrole : 0; ?>
				}
			}).done(function (data) {
				SLO_employeeID.enable();
				$("#employeIDFormSearch").css("cursor", "text");
				$UILoad.html(data);
				RaiseEvenets();
			});
		}

		<?php
		if ($UserFound) {
			echo "Loader(\"update\", {$UserFound['usr_id']});";
		} else {
			echo "Loader(\"add\", 0)";
		}
		?>

		var SLO_employeeID = $("#employeIDFormSearch").slo({
			onselect: function (value) {
				//SLO_employeeID.disable();
				$("#employeIDFormSearch").css("cursor", "wait");
				history.pushState({
					'method': 'update',
					'id': value.key,
					'name': value.value
				}, null, "<?= $fs(134)->dir ?>/?method=update&id=" + value.key);
				Loader("update", value.key);
				js_output_headertitle.html("Edit employee");
				js_output_headercite.html("<li>" + value.key + "</li>");
			},
			ondeselect: function () {
				$("#employeIDFormSearch").css("cursor", "wait");
				history.pushState({
					'method': '',
					'id': 0,
					'name': ''
				}, null, "<?= $fs(50)->dir ?>");
				Loader("add", 0);
				js_output_headertitle.html("Add employee");
				js_output_headercite.html("");
			},
			"limit": 10,
		});

		SLO_employeeID.disable();
		$("#employeIDFormSearch").css("cursor", "wait");

		window.onpopstate = function (e) {
			if (e.state.method == "update") {
				SLO_employeeID.set(e.state.id, e.state.name);
				Loader("update", e.state.id);
				js_output_headertitle.html("Edit employee");
				js_output_headercite.html("<li>" + e.state.id + "</li>");
			} else {
				SLO_employeeID.clear(false);
				Loader("add", 0);
				js_output_headertitle.html("Add employee");
				js_output_headercite.html("");
			}
		};
		<?php if ($fs(229)->permission->edit) { ?>
			var salary_clear = function () { $("input[name=salary_basic]").attr("data-basicvalue", "0.00"); $("input[name=salary_variable]").attr("data-basicvalue", "0.00"); $("input[name=salary_allowance]").attr("data-basicvalue", "0.00"); if ($("[name=sal_default_salary]").prop("checked")) { $("[name=salary_basic]").val("0.00").prop("disabled", true); } if ($("[name=sal_default_variable]").prop("checked")) { $("[name=salary_variable]").val("0.00").prop("disabled", true); } if ($("[name=sal_default_allowance]").prop("checked")) { $("[name=salary_allowance]").val("0.00").prop("disabled", true); } }
			var get_salary_information = function () {
				$.ajax({ url: "<?php echo "{$fs()->dir}"; ?>", type: "POST", data: { 'method': 'get_salary_information', 'sal_workingtime': ($("#sloworkingtimes_1").val()), 'sal_paymethod': ($("#slopayment_1").val()), 'sal_job': ($("#slotype_1").val()), } }).done(function (output) {
					if (output == "false") { salary_clear(); return; } var json = false; try { json = JSON.parse(output); } catch (e) { messagesys.failure("Parsing output failed"); return false; }
					$("input[name=salary_basic]").attr("data-basicvalue", json.basic); $("input[name=salary_variable]").attr("data-basicvalue", json.variable); $("input[name=salary_allowance]").attr("data-basicvalue", json.allowance);
					if ($("[name=sal_default_salary]").prop("checked")) { $("[name=salary_basic]").val($("input[name=salary_basic]").attr("data-basicvalue")); } if ($("[name=sal_default_variable]").prop("checked")) { $("[name=salary_variable]").val($("input[name=salary_variable]").attr("data-basicvalue")); } if ($("[name=sal_default_allowance]").prop("checked")) { $("[name=salary_allowance]").val($("input[name=salary_allowance]").attr("data-basicvalue")); }
				});
			}
		<?php } ?>

		var RaiseEvenets = function () {
			var UploadUserPersonalImage = $.Upload({
				objectHandler: $("#js_upload_list"),
				domselector: $("#js_uploader_btn"),
				dombutton: $("#js_upload_trigger"),
				list_button: $("#js_upload_count"),
				emptymessage: "[No files uploaded]",
				upload_url: "<?= $fs(186)->dir ?>",
				relatedpagefile: <?php echo \System\Attachment\Type::HrPerson->value; ?>,
				multiple: false,
				inputname: "perosnal_image",
				domhandler: $("#UploadPersonalDOMHandler"),
				align: "right",
				onupload: function (output) { }
			});
			UploadUserPersonalImage.update();


			var UploadUserSocialID = $.Upload({
				objectHandler: $("#js_upload_list_1"),
				domselector: $("#js_uploader_btn_1"),
				dombutton: $("#js_upload_trigger_1"),
				list_button: $("#js_upload_count_1"),
				emptymessage: "[No files uploaded]",
				upload_url: "<?= $fs(186)->dir ?>",
				relatedpagefile: <?php echo \System\Attachment\Type::HrID->value; ?>,
				multiple: true,
				inputname: "social_id_image",
				align: "right",
				domhandler: $("#UploadSocialDOMHandler"),
			});
			UploadUserSocialID.update();

			var slotype = $("#slotype").slo({
				onselect: function (value) {
					get_salary_information();
				},
				ondeselect: function () {
					salary_clear();
				},
				'limit': 10
			}),
				sloshif = $("#sloshift").slo(),
				sloresi = $("#sloresidence").slo(),
				slogend = $("#slogender").slo(),
				sloresister = $("#sloresdate").slo(),
				slorbirthdate = $("#slobirthdate").slo(),
				slocompany = $("#slocompany").slo(),
				sloworktimes = $("#sloworkingtimes").slo({
					onselect: function (value) {
						get_salary_information();
					},
					ondeselect: function () {
						salary_clear();
					},
					'limit': 10
				}),
				slorpaymethod = $("#slopayment").slo({
					onselect: function (value) {
						get_salary_information();
					},
					ondeselect: function () {
						salary_clear();
					},
					'limit': 10
				}),
				slortransportation = $("#slortransportation").slo(),
				slonationality = $("#slonationality").slo();

			<?php if ($fs(229)->permission->edit) { ?>
				$(".derive_function").on('change', function () { var $this = $(this); var _result = $this.prop("checked"); $this.parent().prev().prop("disabled", _result); if (_result) { $("[name=" + $this.attr('data-rel') + "]").val($("input[name=" + $this.attr('data-rel') + "]").attr("data-basicvalue")); } else { $("[name=" + $this.attr('data-rel') + "]").val("0.00"); } });
			<?php } ?>


			$Form = $("#EmployeeForm");
			$("#js-input_submit-button").on("click", () => {
				$Form.submit();
			});
			$Form.on('submit', function (e) {
				e.preventDefault();
				if (busy) return false;
				busy = true;
				$("#js-input_submit-button").prop("disabled", true);
				$.ajax({
					url: '<?php echo $fs()->dir; ?>',
					type: 'POST',
					data: $Form.serialize()
				}).done(function (data) {
					$("#js-input_submit-button").prop("disabled", false);
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
							sloresister.clear();
							slorbirthdate.clear();
							slocompany.clear();
							sloworktimes.clear();
							slorpaymethod.clear();
							slortransportation.clear();
							slonationality.clear();
							slotype.clear();
							<?php if ($fs(229)->permission->edit) { ?>								$("[name=sal_default_salary]").prop("checked", true); $("[name=sal_default_variable]").prop("checked", true); $("[name=sal_default_allowance]").prop("checked", true); salary_clear();
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
					busy = false;
				}).always(() => {
					busy = false;
				});
				return false;
			});
		}
	});
</script>