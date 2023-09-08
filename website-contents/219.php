<?php
include_once("admin/class/person.php");

use System\System;


$perm_personal = $tables->Permissions(227, $USER->info->permissions);
$perm_job = $tables->Permissions(228, $USER->info->permissions);
$perm_salary = $tables->Permissions(229, $USER->info->permissions);


$imageMimes = array(
	"image/jpeg", "image/gif", "image/bmp", "image/png",
);
function UploadDOM($fileID, $fileMime, $fileTitle, $fileSelected = false, $domField = "")
{
	return "
		<span>
			<span class=\"upload_record_pointer\">
				<span class=\"btn-set\">
					<label class=\"btn-checkbox\">
						<input type=\"checkbox\" " . ($fileSelected ? "checked=\"checked\"" : "") . " name=\"{$domField}[]\" value=\"$fileID\">
						<span></span>
						<div></div>
					</label>
					<button type=\"button\" data-id=\"$fileID\" class=\"js_up_delete bnt-remove\"></button>
				</span>
			</span>
			<span class=\"upload_file_details\">
				<a class=\"js_upload_view\" target=\"_blank\" data-mime=\"$fileMime\" href=\"download/?id=$fileID&amp;pr=v\" data-href=\"download/?pr=v&amp;id=$fileID\">$fileTitle</a>
			</span>
		</span>
		";
}


$arr_array_input = false;
$arr_array_uploads = array();
if (isset($_POST['method'], $_POST['id']) && $_POST['method'] == "update") {
	$employeeID = (int)$_POST['id'];
	$r_emp = $sql->query("
		SELECT
			usr_firstname,usr_lastname,
			usr_id,usr_username,usr_phone_list,
			usr_attrib_i2,gnd_name,gnd_id,
			lsf_id,lsf_name,
			lty_id,lty_name,lsc_name,
			ldn_id,ldn_name,
			DATE_FORMAT(usr_birthdate,'%d %M, %Y') AS usr_birthdate_format,
			usr_birthdate,
			DATE_FORMAT(lbr_registerdate,'%d %M, %Y') AS lbr_registerdate_format,
			lbr_registerdate AS lbr_registerdate,
			DATE_FORMAT(lbr_resigndate,'%d %M, %Y') AS lbr_resigndate_format,
			lbr_resigndate AS lbr_resigndate,
			lbr_socialnumber,
			trans_name,trans_id,lbr_mth_name,lbr_mth_id,lwt_name,lwt_id,
			lbr_id,up_id,cntry_name,cntry_id,
			lbr_fixedsalary,lbr_variable,lbr_allowance,lbr_trans_allowance,
			lbr_typ_sal_basic_salary,lbr_typ_sal_variable,lbr_typ_sal_allowance,lbr_typ_sal_transportation,
			comp_id,comp_name,
			lbr_role
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
				LEFT JOIN uploads ON up_rel=lbr_id AND up_pagefile=" . System::FILE['Person']['Photo'] . " AND up_deleted=0
				LEFT JOIN labour_type_salary ON lbr_typ_sal_lty_id = lbr_type AND lbr_typ_sal_lwt_id = lbr_workingtimes AND lbr_typ_sal_method = lbr_payment_method
				LEFT JOIN companies ON comp_id = lbr_company
		WHERE
			lbr_id=$employeeID;
		");
	if ($r_emp) {
		if ($row_emp = $sql->fetch_assoc($r_emp)) {
			$arr_array_input = $row_emp;
		}
	}
}

$q_socialid_uploads_query = "
		SELECT up_id,up_name,up_size,up_date,up_pagefile,up_mime,up_rel 
		FROM uploads 
		WHERE 
			(" . ($arr_array_input != false ? " up_rel={$arr_array_input['usr_id']} OR " : "") . " (up_rel=0 AND up_user={$USER->info->id}))
			AND up_deleted=0 AND (up_pagefile=" . System::FILE['Person']['Photo'] . " OR up_pagefile=" . System::FILE['Person']['ID'] . ") ORDER BY up_rel DESC, up_date DESC;";
$q_socialid_uploads = $sql->query($q_socialid_uploads_query);
while ($row_socialid_uploads = $sql->fetch_assoc($q_socialid_uploads)) {
	if (!isset($arr_array_uploads[$row_socialid_uploads['up_pagefile']])) {
		$arr_array_uploads[$row_socialid_uploads['up_pagefile']] = array();
	}
	$arr_array_uploads[$row_socialid_uploads['up_pagefile']][$row_socialid_uploads['up_id']] = array($row_socialid_uploads['up_name'], $row_socialid_uploads['up_size'], $row_socialid_uploads['up_date'], $row_socialid_uploads['up_mime'], $row_socialid_uploads['up_rel']);
}

?>
<form id="EmployeeForm" method="POST">
	<input type="hidden" name="EmployeeFormMethod" value="proccessHandler" />
	<input type="hidden" name="Token" value="<?php echo session_id(); ?>" />
	<input type="hidden" name="EmployeeFormID" value="<?php echo $arr_array_input != false ? $arr_array_input['usr_id'] : "0"; ?>">

	<?php
	if (($arr_array_input != false && $perm_personal->edit) || ($arr_array_input == false && $perm_personal->add)) {
	?>
		<div>
			<div class="btn-set" style="position: sticky;top:103px;z-index: 19;padding-top:15px;padding-bottom:0px;background-color:#fff"><span class="flex"><b>Personal Information</b></span><button type="submit" class="EmployeeFormSubmitButton"><?php echo $arr_array_input != false ? "Update" : "Add"; ?></button></div>
			<div style="padding-left:10px;margin-top:10px;/*overflow-y: auto;max-height: 253px;*/">
				<table class="bom-table mediabond-table">
					<tbody>
						<?php if ($arr_array_input != false) { ?>
							<tr>
								<th>ID</th>
								<td>
									<div class="btn-set"><input type="text" class="flex" disabled="disabled" value="<?php echo $arr_array_input['usr_id']; ?>" /></div>
								</td>
							</tr>
						<?php } ?>
						<tr>
							<th style="max-width: 100px;width:100px;min-width:100px">First name</th>
							<td>
								<div class="btn-set"><input type="text" name="firstname" id="firstname" class="flex" value="<?php echo $arr_array_input != false ? $arr_array_input['usr_firstname'] : ""; ?>" /></div>
							</td>
						</tr>
						<tr>
							<th>Last name</th>
							<td>
								<div class="btn-set"><input type="text" name="lastname" id="lastname" class="flex" value="<?php echo $arr_array_input != false ? $arr_array_input['usr_lastname'] : ""; ?>" /></div>
							</td>
						</tr>
						<tr>
							<th>Nationality</th>
							<td>
								<div class="btn-set"><input type="text" name="nationality" id="slonationality" class="flex" data-slo="COUNTRIES" value="<?php echo $arr_array_input != false ? $arr_array_input['cntry_name'] : ""; ?>" <?php echo $arr_array_input != false ? "data-slodefaultid=\"{$arr_array_input['cntry_id']}\" " : ""; ?>>
								</div>
							</td>
						</tr>
						<tr>
							<th>Social ID Card</th>
							<td>
								<div class="btn-set">

									<button type="button" id="js_upload_trigger_1" class="js_upload_trigger" data-db_rel="usr_attrib_s2">Upload</button>
									<input type="file" id="js_uploader_btn_1" class="js_uploader_btn" multiple="multiple" accept="image/*" />
									<span id="js_upload_list_1" class="js_upload_list">
										<div id="UploadSocialDOMHandler">
											<?php
											if (isset($arr_array_uploads[190]) && is_array($arr_array_uploads[190])) {
												foreach ($arr_array_uploads[190] as $fileIndex => $file) {
													echo UploadDOM($fileIndex, in_array($file[3], $imageMimes) ? "image" : "document", $file[0], ((int)$file[4] == 0 ? false : true), "social_id_image");
												}
											}
											?>
										</div>
									</span>
									<button type="button" id="js_upload_count_1" class="js_upload_count"><span>0</span> files</button>
									<span>No.</span>
									<input type="text" value="<?php echo $arr_array_input != false ? $arr_array_input['lbr_socialnumber'] : ""; ?>" name="social_number" id="social_number" style="min-width:0px;width:100%">
								</div>
							</td>
						</tr>
						<tr>
							<th>Personal photo</th>
							<td>
								<div class="btn-set">
									<button type="button" id="js_upload_trigger" class="js_upload_trigger " style="max-width:100px">Upload</button>
									<input type="file" id="js_uploader_btn" class="js_uploader_btn" accept="image/*" />
									<span id="js_upload_list" class="js_upload_list">
										<div id="UploadPersonalDOMHandler">
											<?php
											if (isset($arr_array_uploads[System::FILE['Person']['Photo']]) && is_array($arr_array_uploads[System::FILE['Person']['Photo']])) {
												foreach ($arr_array_uploads[System::FILE['Person']['Photo']] as $fileIndex => $file) {
													echo UploadDOM($fileIndex, in_array($file[3], $imageMimes) ? "image" : "document", $file[0], ((int)$file[4] == 0 ? false : true), "perosnal_image");
												}
											}
											?>
										</div>
									</span>
									<button type="button" id="js_upload_count" class="js_upload_count"><span>0</span> files</button>
								</div>
							</td>
						</tr>
						<tr>
							<th>Gender</th>
							<td>
								<div class="btn-set"><input type="text" name="gender" class="flex" id="slogender" data-slo="G000" value="<?php echo $arr_array_input != false ? $arr_array_input['gnd_name'] : ""; ?>" <?php echo $arr_array_input != false ? "data-slodefaultid=\"{$arr_array_input['gnd_id']}\" " : ""; ?>>
								</div>
							</td>
						</tr>
						<tr>
							<th>Birthdate</th>
							<td>
								<div class="btn-set"><input type="text" name="birthdate" class="flex" id="slobirthdate" data-slo="BIRTHDATE" value="<?php echo $arr_array_input != false ? $arr_array_input['usr_birthdate_format'] : ""; ?>" <?php echo $arr_array_input != false ? "data-slodefaultid=\"{$arr_array_input['usr_birthdate']}\" " : ""; ?>>
								</div>
							</td>
						</tr>
						<tr>
							<th>Phone numbers</th>
							<td class="btn-set"><textarea name="phone_list" id="phone_list" style="width:100%;height:66px;"><?php echo $arr_array_input != false ? $arr_array_input['usr_phone_list'] : ""; ?></textarea></td>
						</tr>

						<tr>
							<th>Residence</th>
							<td>
								<div class="btn-set"><input type="text" name="residence" class="flex" id="sloresidence" data-slo="E004" value="<?php echo $arr_array_input != false ? $arr_array_input['ldn_name'] : ""; ?>" <?php echo $arr_array_input != false ? "data-slodefaultid=\"{$arr_array_input['ldn_id']}\" " : ""; ?>>
								</div>
							</td>
						</tr>
						<tr>
							<th>Transportation</th>
							<td>
								<div class="btn-set"><input type="text" name="transportation" class="flex" id="slortransportation" data-slo="TRANSPORTATION" value="<?php echo $arr_array_input != false ? $arr_array_input['trans_name'] : ""; ?>" <?php echo $arr_array_input != false ? " data-slodefaultid=\"{$arr_array_input['trans_id']}\" " : ""; ?>>
								</div>
							</td>
						</tr>

					</tbody>
				</table>
			</div>
		</div>
	<?php
	}
	?>








	<?php
	if (($arr_array_input != false && $perm_job->edit) || ($arr_array_input == false && $perm_job->add)) {
	?>
		<div style="margin-top:5px;margin-bottom:20px;">
			<div class="btn-set" style="position: sticky;top:103px;z-index: 19;padding-top:15px;padding-bottom:0px;background-color:#fff"><span class="flex"><b>Job Information</b></span><button type="submit" class="EmployeeFormSubmitButton"><?php echo $arr_array_input != false ? "Update" : "Add"; ?></button></div>
			<div style="padding-left:10px;margin-top:10px;/*overflow-y: auto;max-height: 253px;*/">
				<table class="bom-table mediabond-table">
					<tbody>

						<tr>
							<th style="max-width: 100px;width:100px;min-width:100px">Company</th>
							<td style="width: 100%;">
								<div class="btn-set"><input type="text" class="flex" name="company" id="slocompany" data-slo="COMPANY_USER" value="<?php echo $arr_array_input != false ? $arr_array_input['comp_name'] : ""; ?>" <?php echo $arr_array_input != false ? " data-slodefaultid=\"{$arr_array_input['comp_id']}\" " : ""; ?>>
								</div>
							</td>
						</tr>

						<tr>
							<th>Role</th>
							<td>
								<div class="btn-set">
									<?php
									$r = array(1 => false, 2 => false, 3 => false);
									$r[1] = isset($_POST['frole']) && $_POST['frole'] == 1 ? true : false;
									$r[2] = isset($_POST['frole']) && $_POST['frole'] == 2 ? true : false;
									$r[3] = isset($_POST['frole']) && $_POST['frole'] == 3 ? true : false;
									$r[1] = $arr_array_input == false ? $r[1] : (sprintf('%03b', $arr_array_input['lbr_role'])[2] == "1" ? true : false);
									$r[2] = $arr_array_input == false ? $r[2] : (sprintf('%03b', $arr_array_input['lbr_role'])[1] == "1" ? true : false);
									$r[3] = $arr_array_input == false ? $r[3] : (sprintf('%03b', $arr_array_input['lbr_role'])[0] == "1" ? true : false);
									?>
									<label class="btn-checkbox"><input type="checkbox" name="role[1]" <?php echo $r[1] ? "checked=\"checked\"" : ""; ?> /> <span>Employee</span></label>
									<label class="btn-checkbox"><input type="checkbox" name="role[2]" <?php echo $r[2] ? "checked=\"checked\"" : ""; ?> /> <span>Client</span></label>
									<label class="btn-checkbox"><input type="checkbox" name="role[3]" <?php echo $r[3] ? "checked=\"checked\"" : ""; ?> /> <span>Vendor</span></label>
								</div>
							</td>
						</tr>

						<tr>
							<th>Register date</th>
							<td>
								<div class="btn-set"><input type="text" name="regdate" class="flex" id="sloregdate" data-slo="DATE" value="<?php echo $arr_array_input != false ? $arr_array_input['lbr_registerdate_format'] : ""; ?>" <?php echo $arr_array_input != false ? " data-slodefaultid=\"{$arr_array_input['lbr_registerdate']}\" " : ""; ?>>
								</div>
							</td>
						</tr>
						<?php if ($arr_array_input != false) { ?>
							<tr>
								<th>Resign date</th>
								<td>
									<div class="btn-set"><input type="text" name="resdate" class="flex" id="sloresdate" data-slo="DATE" value="<?php echo $arr_array_input != false ? $arr_array_input['lbr_resigndate_format'] : ""; ?>" <?php echo $arr_array_input != false ? " data-slodefaultid=\"{$arr_array_input['lbr_resigndate']}\" " : ""; ?>>
									</div>
								</td>
							</tr>
						<?php } ?>
						<tr>
							<th>Job title</th>
							<td>
								<div class="btn-set"><input type="text" name="jobtitle" class="flex" id="slotype" data-slo="E002A" value="<?php echo $arr_array_input != false ? $arr_array_input['lsc_name'] . ", " . $arr_array_input['lty_name'] : ""; ?>" <?php echo $arr_array_input != false ? " data-slodefaultid=\"{$arr_array_input['lty_id']}\" " : ""; ?>>
								</div>
							</td>
						</tr>
						<tr>
							<th>Working shift</th>
							<td>
								<div class="btn-set"><input type="text" name="shift" class="flex" id="sloshift" data-slo="E003" value="<?php echo $arr_array_input != false ? $arr_array_input['lsf_name'] : ""; ?>" <?php echo $arr_array_input != false ? " data-slodefaultid=\"{$arr_array_input['lsf_id']}\" " : ""; ?>>
								</div>
							</td>
						</tr>
						<tr>
							<th>Working Time</th>
							<td>
								<div class="btn-set"><input type="text" name="workingtimes" class="flex" id="sloworkingtimes" data-slo="WORKING_TIMES" value="<?php echo $arr_array_input != false ? $arr_array_input['lwt_name'] : ""; ?>" <?php echo $arr_array_input != false ? " data-slodefaultid=\"{$arr_array_input['lwt_id']}\" " : ""; ?>>
								</div>
							</td>
						</tr>
						<tr>
							<th>Payment method</th>
							<td>
								<div class="btn-set"><input type="text" name="payment" class="flex" id="slopayment" data-slo="SALARY_PAYMENT_METHOD" value="<?php echo $arr_array_input != false ? $arr_array_input['lbr_mth_name'] : ""; ?>" <?php echo $arr_array_input != false ? " data-slodefaultid=\"{$arr_array_input['lbr_mth_id']}\" " : ""; ?>>
								</div>
							</td>
						</tr>

					</tbody>
				</table>
			</div>
		</div>
	<?php
	}
	?>







	<?php
	if (($arr_array_input != false && $perm_salary->edit) || ($arr_array_input == false && $perm_salary->add)) {
	?>
		<div style="display:block;min-width:300px;max-width:800px;margin-top:5px;margin-bottom:20px;position: relative;">
			<div id="FormInfoModifyOverLay" style="display:none;position: absolute;background-color: rgba(230,230,234,0.7);top:0px;left:0px;right:0px;bottom: 0px;z-index: 8;cursor: wait;"></div>
			<div class="btn-set" style="position: sticky;top:103px;z-index: 19;padding-top:15px;padding-bottom:0px;background-color:#fff"><span class="flex"><b>Salary Details</b></span><button type="submit" class="EmployeeFormSubmitButton"><?php echo $arr_array_input != false ? "Update" : "Add"; ?></button></div>
			<div style="padding-left:10px;margin-top:10px;">
				<table class="bom-table mediabond-table">
					<tbody>
						<tr>
							<th style="max-width: 100px;width:100px;min-width:100px">Salary</th>
							<td>
								<div class="btn-set">
									<input name="salary_basic" <?php echo (is_array($arr_array_input) && is_null($arr_array_input['lbr_fixedsalary']) ? "disabled=\"disabled\"" : ""); ?> value="<?php echo (is_array($arr_array_input) && is_null($arr_array_input['lbr_fixedsalary']) ? number_format((float)$arr_array_input['lbr_typ_sal_basic_salary'], 2, ".", "") : number_format((float)$arr_array_input['lbr_fixedsalary'], 2, ".", "")) ?>" data-basicvalue="<?php echo is_array($arr_array_input) ? number_format((float)$arr_array_input['lbr_typ_sal_basic_salary'], 2, ".", "") : ""; ?>" class="flex" />
									<label class="btn-checkbox">
										<input type="checkbox" class="derive_function" data-rel="salary_basic" name="sal_default_salary" <?php echo (is_null($arr_array_input['lbr_fixedsalary']) ? "checked=\"checked\"" : ""); ?> />
										<span>Default</span>
									</label>
								</div>
							</td>
						</tr>

						<tr>
							<th>Variable</th>
							<td>
								<div class="btn-set">
									<input name="salary_variable" <?php echo (is_array($arr_array_input) && is_null($arr_array_input['lbr_variable']) ? "disabled=\"disabled\"" : ""); ?> value="<?php echo (is_array($arr_array_input) && is_null($arr_array_input['lbr_variable']) ? number_format((float)$arr_array_input['lbr_typ_sal_variable'], 2, ".", "") : number_format((float)$arr_array_input['lbr_variable'], 2, ".", "")) ?>" data-basicvalue="<?php echo is_array($arr_array_input) ? number_format((float)$arr_array_input['lbr_typ_sal_variable'], 2, ".", "") : ""; ?>" class="flex" />
									<label class="btn-checkbox">
										<input type="checkbox" class="derive_function" data-rel="salary_variable" name="sal_default_variable" <?php echo (is_null($arr_array_input['lbr_variable']) ? "checked=\"checked\"" : ""); ?> />
										<span>Default</span>
									</label>
								</div>
							</td>
						</tr>

						<tr>
							<th>Allowance</th>
							<td>
								<div class="btn-set">
									<input name="salary_allowance" <?php echo (is_array($arr_array_input) && is_null($arr_array_input['lbr_allowance']) ? "disabled=\"disabled\"" : ""); ?> value="<?php echo (is_array($arr_array_input) && is_null($arr_array_input['lbr_allowance']) ? number_format((float)$arr_array_input['lbr_typ_sal_allowance'], 2, ".", "") : number_format((float)$arr_array_input['lbr_allowance'], 2, ".", "")) ?>" data-basicvalue="<?php echo is_array($arr_array_input) ? number_format((float)$arr_array_input['lbr_typ_sal_allowance'], 2, ".", "") : ""; ?>" class="flex" />
									<label class="btn-checkbox">
										<input type="checkbox" class="derive_function" data-rel="salary_allowance" name="sal_default_allowance" <?php echo (is_null($arr_array_input['lbr_allowance']) ? "checked=\"checked\"" : ""); ?> />
										<span>Default</span>
									</label>
								</div>
							</td>
						</tr>

					</tbody>
				</table>
			</div>
		</div>
	<?php
	}
	?>

</form>