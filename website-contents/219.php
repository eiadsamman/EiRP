<?php
use System\Template\Gremium\Gremium;

$imageMimes = array(
	"image/jpeg",
	"image/gif",
	"image/bmp",
	"image/png",
);
function UploadDOM($fileID, $fileMime, $fileTitle, $fileSelected = false, $domField = "")
{
	return "
		<tr>
		<td class=\"checkbox\"><label><input name=\"{$domField}[]\" value=\"$fileID\" type=\"checkbox\"" . ($fileSelected ? "checked=\"checked\"" : "") . " /></label></td>
		<td class=\"op-remove\" data-id=\"$fileID\"><span></span></td>
		<td class=\"content\"><a class=\"js_upload_view\" target=\"_blank\" data-mime=\"$fileMime\" href=\"download/?id=$fileID&amp;pr=v\" data-href=\"download/?pr=v&amp;id=$fileID\">$fileTitle</a></td>
	</tr>";
}


$arr_array_input   = false;
$arr_array_uploads = array();
if (isset($_POST['method'], $_POST['id']) && $_POST['method'] == "update") {
	$employeeID = (int) $_POST['id'];
	$r          = $app->db->query(
		"SELECT
			usr_firstname,
			usr_lastname,
			usr_id,
			usr_username,
			usr_phone_list,
			usr_birthdate,

			usr_registerdate,
			lbr_resigndate,
			lbr_socialnumber,
			
			comp_id,
			comp_name,
			
			gnd_name,
			gnd_id,

			lsf_id,lsf_name,
			lty_id,lty_name,
			lsc_name,
			
			ldn_id,ldn_name,

			
			trans_name,trans_id,lbr_mth_name,lbr_mth_id,lwt_name,lwt_id,
			lbr_id,up_id,cntry_name,cntry_id,
			lbr_fixedsalary,lbr_variable,lbr_allowance,lbr_trans_allowance,
			lbr_typ_sal_basic_salary,lbr_typ_sal_variable,lbr_typ_sal_allowance,lbr_typ_sal_transportation,
			usr_role
		FROM
			labour
				JOIN users ON usr_id=lbr_id
				LEFT JOIN labour_shifts ON lbr_shift=lsf_id
				LEFT JOIN countries ON cntry_id=lbr_nationality
				LEFT JOIN (SELECT lty_id,lty_name,lsc_name FROM labour_type JOIN labour_section ON lsc_id=lty_section) AS _labourtype ON lty_id = usr_jobtitle
				LEFT JOIN gender ON gnd_id=usr_gender
				LEFT JOIN labour_residentail ON ldn_id=lbr_residential
				LEFT JOIN labour_method ON lbr_mth_id = lbr_payment_method
				LEFT JOIN workingtimes ON lwt_id = lbr_workingtimes
				LEFT JOIN labour_transportation ON lbr_transportation=trans_id
				LEFT JOIN uploads ON up_rel=lbr_id AND up_pagefile=" . \System\Attachment\Type::HrPerson->value . " AND up_deleted = 0
				LEFT JOIN labour_type_salary ON lbr_typ_sal_lty_id = usr_jobtitle AND lbr_typ_sal_lwt_id = lbr_workingtimes AND lbr_typ_sal_method = lbr_payment_method
				LEFT JOIN companies ON comp_id = usr_entity
		WHERE
			lbr_id=$employeeID;
		"
	);
	if ($r && $row_emp = $r->fetch_assoc()) {
		$arr_array_input = $row_emp;
	}
}

$q_socialid_uploads_query =
	"SELECT up_id,up_name,up_size,up_date,up_pagefile,up_mime,up_rel 
		FROM uploads 
		WHERE 
			(" . ($arr_array_input != false ? " up_rel={$arr_array_input['usr_id']} OR " : "") . " (up_rel=0 AND up_user={$app->user->info->id}))
			AND up_deleted=0 AND (up_pagefile=" . \System\Attachment\Type::HrPerson->value . " OR up_pagefile=" . \System\Attachment\Type::HrID->value . ") ORDER BY up_rel DESC, up_date DESC;";

$r = $app->db->query($q_socialid_uploads_query);
while ($row_socialid_uploads = $r->fetch_assoc()) {
	if (!isset($arr_array_uploads[$row_socialid_uploads['up_pagefile']])) {
		$arr_array_uploads[$row_socialid_uploads['up_pagefile']] = array();
	}
	$arr_array_uploads[$row_socialid_uploads['up_pagefile']][$row_socialid_uploads['up_id']] = array($row_socialid_uploads['up_name'], $row_socialid_uploads['up_size'], $row_socialid_uploads['up_date'], $row_socialid_uploads['up_mime'], $row_socialid_uploads['up_rel']);
}

$grem = new Gremium(true, false);

$grem->header();
$grem->menu();

?>

<input type="hidden" name="EmployeeFormMethod" value="proccessHandler" />
<input type="hidden" name="Token" value="<?php echo session_id(); ?>" />
<input type="hidden" name="EmployeeFormID" value="<?php echo $arr_array_input != false ? $arr_array_input['usr_id'] : "0"; ?>">

<?php
if (($arr_array_input != false && $fs(227)->permission->edit) || ($arr_array_input == false && $fs(227)->permission->add)) {

	$grem->title()->serve("<span class=\"flex\">Personal Information</span>");
	$grem->article()->open();
	?>
	<?php /* if ($arr_array_input != false) { ?>
<div class="form predefined">
	<label>
		<h1>Personal ID</h1>
		<div class="btn-set">
			<input type="text" class="flex" readonly="readonly" value="<?php echo $arr_array_input['usr_id']; ?>" />
		</div>
	</label>
</div>
<?php } */ ?>


	<div class="form predefined">
		<label>
			<h1>Full name</h1>
			<div class="btn-set">
				<input type="text" name="firstname" id="firstname" placeholder="Surname name" class="flex"
					value="<?php echo $arr_array_input != false ? $arr_array_input['usr_firstname'] : ""; ?>" />
				<input type="text" name="lastname" id="lastname" placeholder="Family name" class="flex"
					value="<?php echo $arr_array_input != false ? $arr_array_input['usr_lastname'] : ""; ?>" />
			</div>
		</label>
	</div>

	<div class="form predefined">
		<label for="">
			<h1>Role</h1>
			<div class="btn-set">
				<?php
				$r    = array(0 => 0, 1 => false, 2 => false, 3 => false);
				$r[1] = isset($_POST['frole']) && $_POST['frole'] == 1 ? true : false;
				$r[2] = isset($_POST['frole']) && $_POST['frole'] == 2 ? true : false;
				$r[3] = isset($_POST['frole']) && $_POST['frole'] == 3 ? true : false;
				$r[1] = $arr_array_input == false ? $r[1] : (sprintf('%03b', $arr_array_input['usr_role'])[2] == "1" ? true : false);
				$r[2] = $arr_array_input == false ? $r[2] : (sprintf('%03b', $arr_array_input['usr_role'])[1] == "1" ? true : false);
				$r[3] = $arr_array_input == false ? $r[3] : (sprintf('%03b', $arr_array_input['usr_role'])[0] == "1" ? true : false);
				?>
				<label class="btn-checkbox"><input type="checkbox" name="role[1]" <?php echo $r[1] ? " checked=\"checked\" " : ""; ?> /><span>Employee</span></label>
				<label class="btn-checkbox"><input type="checkbox" name="role[2]" <?php echo $r[2] ? "checked=\"checked\"" : ""; ?> /><span>Client</span></label>
				<label class="btn-checkbox"><input type="checkbox" name="role[3]" <?php echo $r[3] ? "checked=\"checked\"" : ""; ?> /><span>Vendor</span></label>
			</div>
		</label>
		<label>
			<h1>Register date</h1>
			<div class="btn-set">
				<span><?php echo $arr_array_input != false ? $arr_array_input['usr_registerdate'] : "-"; ?></span>
			</div>
		</label>
	</div>

	<div class="form predefined">
		<label>
			<h1>Company</h1>
			<div class="btn-set">
				<input type="text" class="flex" name="company" id="slocompany" data-slo="COMPANY_USER"
					value="<?php echo $arr_array_input != false ? $arr_array_input['comp_name'] : ""; ?>" <?php echo $arr_array_input != false ? " data-slodefaultid=\"{$arr_array_input['comp_id']}\" " : ""; ?>>
			</div>
		</label>
		<label>
			<h1>Job Title</h1>
			<div class="btn-set">
				<input type="text" name="jobtitle" class="flex" id="slotype" data-slo="E002A"
					value="<?php echo $arr_array_input != false ? $arr_array_input['lsc_name'] . ", " . $arr_array_input['lty_name'] : ""; ?>" <?php echo $arr_array_input != false ? " data-slodefaultid=\"{$arr_array_input['lty_id']}\" " : ""; ?>>
			</div>
		</label>
	</div>

	<div class="form predefined">
		<label>
			<h1>Gender</h1>
			<div class="btn-set">
				<input type="text" name="gender" class="flex" id="slogender" data-slo="G000"
					value="<?php echo $arr_array_input != false ? $arr_array_input['gnd_name'] : ""; ?>" <?php echo $arr_array_input != false ? "data-slodefaultid=\"{$arr_array_input['gnd_id']}\" " : ""; ?>>
			</div>
		</label>
		<label>
			<h1>Birthdate</h1>
			<div class="btn-set">
				<input type="text" name="birthdate" class="flex" id="slobirthdate" data-slo="BIRTHDATE"
					value="<?php echo $arr_array_input != false ? $arr_array_input['usr_birthdate'] : ""; ?>" <?php echo $arr_array_input != false ? "data-slodefaultid=\"{$arr_array_input['usr_birthdate']}\" " : ""; ?>>
			</div>
		</label>
	</div>
	<div class="form predefined">


		<label>
			<h1>Nationality</h1>
			<div class="btn-set">
				<input type="text" name="nationality" id="slonationality" class="flex" data-slo="COUNTRIES"
					value="<?php echo $arr_array_input != false ? $arr_array_input['cntry_name'] : ""; ?>" <?php echo $arr_array_input != false ? "data-slodefaultid=\"{$arr_array_input['cntry_id']}\" " : ""; ?>>
			</div>
		</label>


	</div>
	<div class="form predefined">
		<label>
			<h1>Contact numbers</h1>
			<div class="btn-set">
				<input name="phone_list" id="phone_list" class="flex"
					value="<?php echo $arr_array_input != false ? $arr_array_input['usr_phone_list'] : ""; ?>" />
			</div>
		</label>
		<label>
		</label>
	</div>

	<div class="form predefined">
		<label for="">
			<h1>ID Card</h1>
			<div class="btn-set">
				<span id="js_upload_count_1" class="js_upload_count"><span>0</span></span>
				<input type="button" id="js_upload_trigger_1" class="js_upload_trigger" value="Upload" />
				<input type="file" id="js_uploader_btn_1" class="js_uploader_btn" multiple="multiple" accept="image/*" />
				<span id="js_upload_list_1" class="js_upload_list">
					<div id="UploadSocialDOMHandler">
						<table class="hover">
							<tbody>
								<?php
								if (isset($arr_array_uploads[190]) && is_array($arr_array_uploads[190])) {
									foreach ($arr_array_uploads[190] as $fileIndex => $file) {
										echo UploadDOM($fileIndex, in_array($file[3], $imageMimes) ? "image" : "document", $file[0], ((int) $file[4] == 0 ? false : true), "social_id_image");
									}
								}
								?>
							</tbody>
						</table>
					</div>
				</span>
				<input type="text" value="<?php echo $arr_array_input != false ? $arr_array_input['lbr_socialnumber'] : ""; ?>" name="social_number"
					id="social_number" class="flex" placeholder="ID Number">
			</div>
		</label>
	</div>
	<div class="form predefined">
		<label for="">
			<h1>Personal photo</h1>
			<div class="btn-set">
				<span id="js_upload_count" class="js_upload_count"><span>0</span></span>
				<input type="button" id="js_upload_trigger" class="js_upload_trigger " style="max-width:100px" value="Upload" />
				<input type="file" id="js_uploader_btn" class="js_uploader_btn" accept="image/*" />
				<span id="js_upload_list" class="js_upload_list">
					<div id="UploadPersonalDOMHandler">
						<table class="hover">
							<tbody>
								<?php
								if (isset($arr_array_uploads[\System\Attachment\Type::HrPerson->value]) && is_array($arr_array_uploads[\System\Attachment\Type::HrPerson->value])) {
									foreach ($arr_array_uploads[\System\Attachment\Type::HrPerson->value] as $fileIndex => $file) {
										echo UploadDOM($fileIndex, in_array($file[3], $imageMimes) ? "image" : "document", $file[0], ((int) $file[4] == 0 ? false : true), "perosnal_image");
									}
								}
								?>
							</tbody>
						</table>
					</div>
				</span>
			</div>
		</label>
	</div>

	<?php
	$grem->getLast()->close();
} ?>
<br />


<?php
if (($arr_array_input != false && $fs(228)->permission->edit) || ($arr_array_input == false && $fs(228)->permission->add)) {
	$grem->title()->serve("<span class=\"flex\">Employment Contract</span>");
	$grem->article()->open();
	?>

	<div class="form predefined">
		<?php if ($arr_array_input != false) { ?>
			<label>
				<h1>Resign date</h1>
				<div class="btn-set">
					<input type="text" name="resdate" class="flex" id="sloresdate" data-slo="DATE"
						value="<?php echo $arr_array_input != false ? $arr_array_input['lbr_resigndate'] : ""; ?>" <?php echo $arr_array_input != false ? " data-slodefaultid=\"{$arr_array_input['lbr_resigndate']}\" " : ""; ?>>

				</div>
			</label>
		<?php } ?>
	</div>

	<div class="form predefined">
		<label>
			<h1>Residence</h1>
			<div class="btn-set">
				<input type="text" name="residence" class="flex" id="sloresidence" data-slo="E004"
					value="<?php echo $arr_array_input != false ? $arr_array_input['ldn_name'] : ""; ?>" <?php echo $arr_array_input != false ? "data-slodefaultid=\"{$arr_array_input['ldn_id']}\" " : ""; ?>>

			</div>
		</label>
		<label>
			<h1>Transportation</h1>
			<div class="btn-set">
				<input type="text" name="transportation" class="flex" id="slortransportation" data-slo="TRANSPORTATION"
					value="<?php echo $arr_array_input != false ? $arr_array_input['trans_name'] : ""; ?>" <?php echo $arr_array_input != false ? " data-slodefaultid=\"{$arr_array_input['trans_id']}\" " : ""; ?>>

			</div>
		</label>
	</div>
	<div class="form predefined">
		<label>
			<h1>Payment method</h1>
			<div class="btn-set">
				<input type="text" name="payment" class="flex" id="slopayment" data-slo="SALARY_PAYMENT_METHOD"
					value="<?php echo $arr_array_input != false ? $arr_array_input['lbr_mth_name'] : ""; ?>" <?php echo $arr_array_input != false ? " data-slodefaultid=\"{$arr_array_input['lbr_mth_id']}\" " : ""; ?>>
			</div>
		</label>
		<label>
			<h1>Working shift</h1>
			<div class="btn-set">
				<input type="text" name="shift" class="flex" id="sloshift" data-slo="E003"
					value="<?php echo $arr_array_input != false ? $arr_array_input['lsf_name'] : ""; ?>" <?php echo $arr_array_input != false ? " data-slodefaultid=\"{$arr_array_input['lsf_id']}\" " : ""; ?>>
			</div>
		</label>
		<label>
			<h1>Time group</h1>
			<div class="btn-set">
				<input type="text" name="workingtimes" class="flex" id="sloworkingtimes" data-slo="WORKING_TIMES"
					value="<?php echo $arr_array_input != false ? $arr_array_input['lwt_name'] : ""; ?>" <?php echo $arr_array_input != false ? " data-slodefaultid=\"{$arr_array_input['lwt_id']}\" " : ""; ?>>

			</div>
		</label>
	</div>

	<?php if (($arr_array_input != false && $fs(229)->permission->edit) || ($arr_array_input == false && $fs(229)->permission->add)) {

		?>
		<h1>Salary details</h1>
		<div class="form predefined" style="padding-left:20px; border-left: solid 2px var(--input_border-color)">
			<label for="">
				<h1>Salary</h1>
				<div class="btn-set">
					<input type="text" name="salary_basic" <?= ($arr_array_input && is_null($arr_array_input['lbr_fixedsalary']) ? "disabled=\"disabled\"" : ""); ?>
						value="<?php echo ($arr_array_input ? (is_null($arr_array_input['lbr_fixedsalary']) ? number_format((float) $arr_array_input['lbr_typ_sal_basic_salary'], 2, ".", "") : number_format((float) $arr_array_input['lbr_fixedsalary'], 2, ".", "")) : "") ?>"
						data-basicvalue="<?= $arr_array_input ? number_format((float) $arr_array_input['lbr_typ_sal_basic_salary'], 2, ".", "") : ""; ?>"
						class="flex" />
					<label class="btn-checkbox">
						<input type="checkbox" class="derive_function" data-rel="salary_basic" name="sal_default_salary" <?= $arr_array_input && (is_null($arr_array_input['lbr_fixedsalary']) ? "checked=\"checked\"" : ""); ?> />
						<span>Default</span>
					</label>

				</div>
			</label>
		</div>

		<div class="form predefined" style="padding-left:20px; border-left: solid 2px var(--input_border-color)">
			<label for="">
				<h1>Variable</h1>
				<div class="btn-set">
					<input type="text" name="salary_variable" <?= ($arr_array_input && is_null($arr_array_input['lbr_variable']) ? "disabled=\"disabled\"" : ""); ?>
						value="<?= ($arr_array_input ? (is_null($arr_array_input['lbr_variable']) ? number_format((float) $arr_array_input['lbr_typ_sal_variable'], 2, ".", "") : number_format((float) $arr_array_input['lbr_variable'], 2, ".", "")) : "") ?>"
						data-basicvalue="<?= $arr_array_input ? number_format((float) $arr_array_input['lbr_typ_sal_variable'], 2, ".", "") : ""; ?>"
						class="flex" />
					<label class="btn-checkbox">
						<input type="checkbox" class="derive_function" data-rel="salary_variable" name="sal_default_variable" <?= ($arr_array_input && is_null($arr_array_input['lbr_variable']) ? "checked=\"checked\"" : ""); ?> />
						<span>Default</span>
					</label>
				</div>
			</label>
		</div>

		<div class="form predefined" style="padding-left:20px; border-left: solid 2px var(--input_border-color)">
			<label for="">
				<h1>Allowance</h1>
				<div class="btn-set">
					<input type="text" name="salary_allowance" <?= ($arr_array_input && is_null($arr_array_input['lbr_allowance']) ? "disabled=\"disabled\"" : ""); ?>
						value="<?= ($arr_array_input ? (is_null($arr_array_input['lbr_allowance']) ? number_format((float) $arr_array_input['lbr_typ_sal_allowance'], 2, ".", "") : number_format((float) $arr_array_input['lbr_allowance'], 2, ".", "")) : "") ?>"
						data-basicvalue="<?= $arr_array_input ? number_format((float) $arr_array_input['lbr_typ_sal_allowance'], 2, ".", "") : ""; ?>"
						class="flex" />
					<label class="btn-checkbox">
						<input type="checkbox" class="derive_function" data-rel="salary_allowance" name="sal_default_allowance" <?= ($arr_array_input && is_null($arr_array_input['lbr_allowance']) ? "checked=\"checked\"" : ""); ?> />
						<span>Default</span>
					</label>
				</div>
			</label>
		</div>
	<?php } ?>
	<?php
	$grem->getLast()->close();
} ?>