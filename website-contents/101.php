<?php

use System\Finance\Account;
use System\Finance\Accounting;
use System\SmartListObject;


function _JSON_output($result, $message, $focus = null, $extra = null)
{
	echo "{";
	echo "\"result\":" . ($result == true ? "true" : "false") . "";
	echo ",\"message\":\"" . addslashes($message) . "\"";
	echo $focus != null ? ",\"focus\":\"$focus\"" : ",\"focus\":false";
	if ($extra != null && is_array($extra)) {
		foreach ($extra as $k => $v) {
			echo ",\"$k\":\"$v\"";
		}
	}
	echo "}";
	exit;
}


$accounting = new Accounting($app);

$transaction_id = null;

if (isset($_POST['id'])) {
	$transaction_id = isset($_POST['id']) ? (int) $_POST['id'] : null;
} elseif (isset($_GET['id'])) {
	$transaction_id = isset($_GET['id']) ? (int) $_GET['id'] : null;
}

if (is_null($transaction_id)) {
	exit;
}


//Check statement if valid for editing and user has permissions to do so
$arr_transaction = null;
if (
	$r = $app->db->query(
		"SELECT 
		acm_id,acm_usr_id,acm_editor_id,acm_ctime,acm_type,acm_beneficial,acm_comments,acm_reference,
		_category._catname,_category._catid,
		CONCAT_WS(' ',COALESCE(_usr.usr_firstname,''),IF(NULLIF(_usr.usr_lastname, '') IS NULL, NULL, _usr.usr_lastname)) AS _usrname,
		CONCAT_WS(' ',COALESCE(_editor.usr_firstname,''),IF(NULLIF(_editor.usr_lastname, '') IS NULL, NULL, _editor.usr_lastname)) AS _editorname,
		acm_rejected,
		acm_realvalue, cur_symbol AS realcurrencyname,cur_id AS realcurrencyid
	FROM 
		acc_main 
			LEFT JOIN 
			(
				SELECT
					acccat_id AS _catid,CONCAT(accgrp_name,\": \",acccat_name) AS _catname
				FROM
					acc_categories JOIN acc_categorygroups  ON acccat_group=accgrp_id
			) AS _category ON _category._catid=acm_category
			LEFT JOIN users AS _usr ON _usr.usr_id=acm_usr_id
			LEFT JOIN users AS _editor ON _editor.usr_id=acm_editor_id
			LEFT JOIN currencies ON cur_id=acm_realcurrency
	WHERE 
		acm_id=$transaction_id;"
	)
) {
	if ($row = $r->fetch_assoc()) {
		$arr_transaction = $row;
	}
}
if (is_null($arr_transaction)) {
	echo "Invalid statement ID";
	exit;
}

//Get Inbound/Outbound records
$arr_transaction['transactions'] = array();
if (
	$r = $app->db->query("
	SELECT 
		atm_id,atm_account_id,atm_value,atm_dir,cur_name,cur_symbol,CONCAT (\"[\", cur_shortname , \"] \" , comp_name ,\": \" , ptp_name, \": \", prt_name) AS prt_name,cur_id
	FROM
		`acc_accounts`
			RIGHT JOIN acc_temp ON prt_id=atm_account_id
			JOIN currencies ON cur_id=prt_currency
			JOIN user_partition ON prt_id=upr_prt_id
			JOIN `acc_accounttype` ON prt_type=ptp_id
			JOIN companies ON prt_company_id=comp_id
	WHERE
		atm_main={$arr_transaction['acm_id']}")
) {
	while ($row = $r->fetch_assoc()) {
		$arr_transaction['transactions'][$row['atm_dir']] = $row;
	}
}




if (isset($_POST['method']) && $_POST['method'] == 'editstatement') {
	if (!$fs()->permission->edit) {
		_JSON_output(false, "No permissions", "jQcreditor");
	}
	$_POST['id'] = (int) $_POST['id'];
	$_POST['type'] = (int) $_POST['type'];

	$_POST['value'] = (float) trim(str_replace(",", "", $_POST['value']));
	$_POST['comments'] = isset($_POST['comments']) && trim($_POST['comments']) != "" ? addslashes($_POST['comments']) : null;
	$_POST['benificial'] = isset($_POST['benificial']) && trim($_POST['benificial']) != "" ? addslashes($_POST['benificial']) : null;
	$_POST['reference'] = isset($_POST['reference']) && trim($_POST['reference']) != "" ? addslashes($_POST['reference']) : null;

	$_POST['status'] = isset($_POST['status']) && (int) $_POST['status'] == 1 ? true : false;

	if ($r = $app->db->query("SELECT prt_id FROM `acc_accounts` WHERE prt_id=" . ((int) $_POST['creditor']) . ";")) {
		if ($r->num_rows == 0) {
			_JSON_output(false, "Select creditor account", "jQcreditor");
		}
	}
	if ($r = $app->db->query("SELECT prt_id FROM `acc_accounts` WHERE prt_id=" . ((int) $_POST['debitor']) . ";")) {
		if ($r->num_rows == 0) {
			_JSON_output(false, "Select creditor account", "jQdebitor");
		}
	}
	if ($_POST['creditor'] == $_POST['debitor']) {
		_JSON_output(false, "Debitor account must not be same as Creditor's", "jQcreditor");
	}
	if ($r = $app->db->query("SELECT acccat_id FROM acc_categories WHERE acccat_id=" . ((int) $_POST['category']) . ";")) {
		if ($r->num_rows == 0) {
			_JSON_output(false, "Select the transaction category", "jQcategory");
		}
	}
	if ($_POST['benificial'] == "") {
		_JSON_output(false, "Enter the name of involved parties in this transaction", "jQbeneficial");
	}

	if ((float) $_POST['value'] <= 0) {
		_JSON_output(false, "Enter a valid float value", "jQvalue");
	}


	$creditor_account = new Account($app, (int) $_POST['creditor']);
	$debitor_account = new Account($app, (int) $_POST['creditor']);

	$date = $app->dateValidate($_POST['trandate']);
	if (!$date) {
		_JSON_output(false, "Select transaction date", "jQdate");
	}

	//Prepare attachment list
	$attachments = array();
	if (isset($_POST['attachments']) && is_array($_POST['attachments'])) {
		foreach ($_POST['attachments'] as $VAtt) {
			$attachments[] = (int) $VAtt;
		}
	}


	$value_from = $_POST['value'];
	$value_to = $_POST['value'];

	//Exchange creditor value with provided currency, do nothing if both have the same currency
	if ($creditor_account->currency->id != $_POST['currency']) {
		$exchangerate = $accounting->currency_exchange($creditor_account->currency->id, $_POST['currency']);
		if ($exchangerate === false) {
			_JSON_output(false, "Unable to exchange currencies with creditor account");
		}
		$value_from = $exchangerate * $value_from;
	}
	//Do the same with registered account
	if ($_POST['currency'] != $debitor_account->currency->id) {
		$exchangerate = $accounting->currency_exchange($_POST['currency'], $debitor_account->currency->id);
		if ($exchangerate === false) {
			_JSON_output(false, "Unable to exchange currencies with debitor account");
		}
		$value_to = $exchangerate * $value_to;
	}




	$result = true;
	//Disable SQL auto commit
	$app->db->autocommit(false);

	//Update the main transaction record
	$qacc_main = sprintf(
		"UPDATE 
			acc_main 
		SET 
			acm_usr_id = %1\$s,
			acm_type = %2\$d,
			acm_beneficial = %3\$s,
			acm_category = %4\$d,
			acm_comments = %5\$s,
			acm_reference = %6\$s,
			acm_rejected = %7\$d,
			acm_realvalue = %9\$f,
			acm_realcurrency = %10\$d
		WHERE 
			acm_id = %8\$d;",

		(int) $_POST['user'] == 0 ? "NULL" : (int) $_POST['user'],
		(int) $_POST['type'],
		($_POST['benificial'] != null ? "'" . $_POST['benificial'] . "'" : "NULL"),
		(int) $_POST['category'],
		($_POST['comments'] != null ? "'" . $_POST['comments'] . "'" : "NULL"),
		($_POST['reference'] != null ? "'" . $_POST['reference'] . "'" : "NULL"),
		($_POST['status'] ? "0" : "1"),
		$arr_transaction['acm_id'],
		$_POST['value'],
		(int) $_POST['currency']
	);




	$result &= $app->db->query($qacc_main);
	if (!$result) {
		$app->db->rollback();
		_JSON_output(false, "Failed to update statement");
	}


	//Update the records
	$qacc_release = sprintf(
		"UPDATE acc_temp SET atm_account_id=%1\$d,atm_value=%2\$f WHERE atm_id=%3\$d;",
		$_POST['creditor'],
		-1 * $value_from,
		$arr_transaction['transactions'][0]['atm_id']
	);
	$result &= $app->db->query($qacc_release);
	$qacc_insert = sprintf(
		"UPDATE acc_temp SET atm_account_id = %1\$d, atm_value = %2\$f WHERE atm_id = %3\$d;",
		$_POST['debitor'],
		$value_to,
		$arr_transaction['transactions'][1]['atm_id']
	);
	$result &= $app->db->query($qacc_insert);


	if (!$result) {
		$app->db->rollback();
		_JSON_output(false, "Failed to update statement");
	}




	//Attach uploaded files to the transaction
	$qacc_attach = "UPDATE uploads SET up_rel=0, up_active = 0 WHERE up_rel = {$arr_transaction['acm_id']} AND up_user = {$app->user->info->id};";
	$result &= $app->db->query($qacc_attach);
	if ($result && sizeof($attachments) > 0) {
		$qacc_attach = "UPDATE uploads SET up_rel={$arr_transaction['acm_id']}, up_active = 1 WHERE up_id IN (" . implode(",", $attachments) . ") AND up_user = {$app->user->info->id};";
		$result &= $app->db->query($qacc_attach);
	}

	if (!$result) {
		$app->db->rollback();
		_JSON_output(false, "Updating attachments failed");
	}



	if ($result) {

		$app->db->commit();
		_JSON_output(true, "Statement updated successfully");
	} else {
		$app->db->rollback();
		_JSON_output(false, "Failed to update statement, statement did not updated");
	}
	exit;
}

$SmartListObject = new SmartListObject($app);
?>
<input type="hidden" id="jQtransactionID" value="<?php echo $arr_transaction['acm_id']; ?>" />
<table class="bom-table" id="jQformTable" style="min-width: 1000px">
	<thead>
		<tr class="special">
			<td colspan="4">Editing transaction statement `
				<?php echo $arr_transaction['acm_id']; ?>`
			</td>
		</tr>
	</thead>
	<tbody>
		<tr>
			<th>Type</th>
			<td>
				<div class="btn-set normal">
					<input tabindex="1" type="text" data-slo=":SELECT" class="flex" id="jQtype" data-list="js-statement-type" />
				</div>
				<datalist id="js-statement-type">
					<?= $SmartListObject->financialTransactionNature((int) $arr_transaction['acm_type']); ?>
				</datalist>
			</td>
			<th>Status</th>
			<td>
				<div class="btn-set normal">
					<label class="btn-checkbox"><input type="checkbox" id="jQstatus" <?php echo $arr_transaction['acm_rejected'] == 1 ? "" : " checked=\"checked\" "; ?> /><span>Active</span></label>
				</div>
			</td>
		</tr>
		<tr>
			<th style="min-width:100px">Creditor</th>
			<td width="50%">
				<div class="btn-set normal">
					<input tabindex="2" type="text" data-slo=":LIST" data-list="jQcreditorList" class="flex" id="jQcreditor" />
					<datalist id="jQcreditorList" style="display: none;">
						<?= $SmartListObject->userAccountsInbound($arr_transaction['transactions'][0]['atm_account_id']); ?>
					</datalist>
				</div>
			</td>
			<th style="min-width:100px">Beneficiary</th>
			<td width="50%">
				<div class="btn-set normal">
					<input type="text" class="flex" tabindex="7" data-slo=":LIST" data-list="beneficialList" id="jQbeneficial" />
				</div>
				<datalist id="beneficialList">
					<?= $SmartListObject->financialBeneficiary($arr_transaction['acm_beneficial']); ?>
				</datalist>

			</td>
		</tr>
		<tr>
			<th>Date</th>
			<td>
				<div class="btn-set normal">
					<input tabindex="3" type="text" data-slo="DATE" class="flex" value="<?= $arr_transaction['acm_ctime']; ?>" data-slodefaultid="<?= $arr_transaction['acm_ctime']; ?>" id="jQdate" />
				</div>
			</td>
			<th>Reference</th>
			<td>
				<div class="btn-set normal">
					<input type="text" class="text" tabindex="8" id="jQreference" data-slo="ACC_REFERENCE" data-slodefaultid="<?php echo $arr_transaction['acm_reference']; ?>"
						value="<?php echo $arr_transaction['acm_reference']; ?>" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" />
				</div>
			</td>
		</tr>
		<tr>
			<th>Debitor</th>
			<td>
				<div class="btn-set normal">
					<input tabindex="4" type="text" data-slo=":LIST" data-list="jQdebitorList" class="flex" id="jQdebitor" />
					<datalist id="jQdebitorList" style="display: none;">
						<?= $SmartListObject->userAccountsInbound($arr_transaction['transactions'][1]['atm_account_id']); ?>
					</datalist>
				</div>

			</td>
			<th>Employee ID</th>
			<td>
				<div class="btn-set normal">
					<input type="text" tabindex="9" class="flex" data-slo="B00S" value="<?php echo $arr_transaction['_usrname']; ?>"
						data-slodefaultid="<?php echo (int) $arr_transaction['acm_usr_id'] != 0 ? $arr_transaction['acm_usr_id'] : ""; ?>" id="jQuser" />
				</div>
			</td>
		</tr>
		<tr>
			<th>Category</th>
			<td>
				<div class="btn-set normal">
					<input type="text" data-slo="ACC_CAT" value="<?php echo $arr_transaction['_catname']; ?>" data-slodefaultid="<?php echo $arr_transaction['_catid']; ?>" tabindex="5" class="flex"
						id="jQcategory" />
				</div>
			</td>
			<th rowspan="2">Comments</th>
			<td rowspan="2">
				<div class="btn-set normal">
					<textarea tabindex="10" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;height:100%;" class="textarea" id="jQcomments"
						rows="4"><?php echo !is_null($arr_transaction['acm_comments']) ? $arr_transaction['acm_comments'] : ""; ?></textarea>
				</div>
			</td>
		</tr>
		<tr>
			<th>Value</th>
			<td>
				<div class="btn-set normal">
					<input type="text" tabindex="6" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;"
						value="<?php echo rtrim(rtrim(number_format(abs($arr_transaction['acm_realvalue']), 7, ".", ""), "0"), "."); ?>" id="jQvalue" /><!--
		--><input tabindex="-1" type="text" data-slo="CURRENCY_SYMBOL" id="jQcurrenyident" value="<?php echo $arr_transaction['realcurrencyname']; ?>"
						data-slodefaultid="<?php echo $arr_transaction['realcurrencyid']; ?>" style="width:62px;" />
				</div>
			</td>
		</tr>
		<tr>
			<td colspan="4">
				<div class="btn-set" style="justify-content:center">
					<button id="js_upload_trigger" class="js_upload_trigger">Attachments</button>
					<input type="file" id="js_uploader_btn" class="js_uploader_btn" multiple="multiple" accept="image/*" />
					<span id="js_upload_list" class="js_upload_list"></span>
					<span id="js_upload_count" class="js_upload_count"><span>0 / 0</span></span>

					<button id="jQsubmit" tabindex="11">Edit</button>
					<?php echo isset($_GET['ajax']) ? "<button id=\"jQpopupCancel\">Cancel</button>" : ""; ?>
				</div>
			</td>
		</tr>
	</tbody>
</table>
<script>
	var $ajax = null;
	$(document).ready(function (e) {
		Upload = $.Upload({
			objectHandler: $("#js_upload_list"),
			domselector: $("#js_uploader_btn"),
			dombutton: $("#js_upload_trigger"),
			list_button: $("#js_upload_count"),
			emptymessage: "[No files uploaded]",
			delete_method: 'permanent',
			upload_url: "<?php echo $fs(186)->dir; ?>",
			relatedpagefile: <?php echo \System\Attachment\Type::FinanceRecord->value; ?>,
			multiple: true,
			inputname: "attachments"
		});
		<?php
		$r_uploads = $app->db->query("
				SELECT 
					up_id,up_name,up_size 
				FROM 
					uploads 
						JOIN pagefile_permissions ON pfp_trd_id=up_pagefile AND pfp_per_id = {$app->user->info->permissions}
				WHERE 
					up_rel={$arr_transaction['acm_id']} AND up_active=1 AND pfp_value>0 AND up_pagefile=" . \System\Attachment\Type::FinanceRecord->value . "");
		if ($r_uploads) {
			while ($row_uploads = $r_uploads->fetch_assoc()) {
				echo "Upload.AddListItem({$row_uploads['up_id']},'{$row_uploads['up_name']}',false,true);";
			}
		}


		$r_uploads = $app->db->query("SELECT up_id,up_name,up_size FROM uploads WHERE up_user={$app->user->info->id} AND up_pagefile=" . \System\Attachment\Type::FinanceRecord->value . " AND up_rel=0 AND up_deleted=0;");
		if ($r_uploads) {
			while ($row_uploads = $r_uploads->fetch_assoc()) {
				echo "Upload.AddListItem({$row_uploads['up_id']},'{$row_uploads['up_name']}',false,false);";
			}
		}
		?>

		var $jQdate = $("#jQdate").slo(),
			$jQcategory = $("#jQcategory").slo(),
			$jQbeneficial = $("#jQbeneficial").slo(),
			$jQcreditor = $("#jQcreditor").slo(),
			$jQdebitor = $("#jQdebitor").slo(),
			$jQtype = $("#jQtype").slo(),
			$jQcurrency = $("#jQcurrenyident").slo(),
			$jQuser = $("#jQuser").slo(),
			$form = $("#jQformTable"),
			$jQreference = $("#jQreference").slo(),
			$jQcomments = $("#jQcomments"),
			$jQvalue = $("#jQvalue"),
			$jQstatus = $("#jQstatus"),

			$jQsubmit = $("#jQsubmit");
		var addStatement = function () {
			var inputStatus = function (status) {
				$jQuser[0].slo.htmltext.prop("disabled", status);
				$jQdate[0].slo.htmltext.prop("disabled", status);
				$jQcategory[0].slo.htmltext.prop("disabled", status);
				$jQbeneficial[0].slo.htmltext.prop("disabled", status);
				$jQcreditor[0].slo.htmltext.prop("disabled", status);
				$jQdebitor[0].slo.htmltext.prop("disabled", status);
				$jQtype[0].slo.htmltext.prop("disabled", status);
				$jQreference[0].slo.htmltext.prop("disabled", status);
				$jQcurrency[0].slo.htmltext.prop("disabled", status);
				$jQvalue.prop("disabled", status);
				$jQcomments.prop("disabled", status);
				$jQsubmit.prop("disabled", status);
			}

			if ($jQtype[0].slo.htmlhidden.val() == "" || $jQtype[0].slo.htmlhidden.val() == "0") {
				messagesys.failure("Select transaction type");
				return false;
			}
			if ($jQcreditor[0].slo.htmlhidden.val() == "" || $jQcreditor[0].slo.htmlhidden.val() == "0") {
				messagesys.failure("Select creditor account");
				return false;
			}
			if ($jQdate[0].slo.htmlhidden.val() == "" || $jQdate[0].slo.htmlhidden.val() == "0") {
				messagesys.failure("Select transaction date");
				return false;
			}
			if ($jQdebitor[0].slo.htmlhidden.val() == "" || $jQdebitor[0].slo.htmlhidden.val() == "0") {
				messagesys.failure("Select debitor account");
				return false;
			}
			if ($jQcategory[0].slo.htmlhidden.val() == "" || $jQcategory[0].slo.htmlhidden.val() == "0") {
				messagesys.failure("Select the transaction category");
				return false;
			}
			if ($jQbeneficial[0].slo.htmlhidden.val().trim() == "") {
				messagesys.failure("Enter the name of involved parties in this transaction");
				return false;
			}

			try {
				_value = parseFloat($jQvalue.val().replace("/[,]/", ""));
			} catch (e) {
				messagesys.failure("Enter a valid falot value");
				$jQvalue.focus().select();
				return false;
			}
			if (isNaN(_value) || _value <= 0) {
				messagesys.failure("Enter a valid float value");
				$jQvalue.focus().select();
				return false;
			}

			if ($jQcurrency[0].slo.htmlhidden.val() == "" || $jQcategory[0].slo.htmlhidden.val() == "0") {
				messagesys.failure("Select the transaction category");
				$jQcurrency.input[0].focus().select();
				return false;
			}

			inputStatus(true);
			var preparePOST = $("#js_upload_list :input").serialize() + '&' + $.param({
				method: 'editstatement',
				id: <?php echo $arr_transaction['acm_id']; ?>,
				type: $jQtype[0].slo.htmlhidden.val(),
				user: $jQuser[0].slo.htmlhidden.val(),
				creditor: $jQcreditor[0].slo.htmlhidden.val(),
				debitor: $jQdebitor[0].slo.htmlhidden.val(),
				category: $jQcategory[0].slo.htmlhidden.val(),
				benificial: $jQbeneficial[0].slo.htmlhidden.val(),
				currency: $jQcurrency[0].slo.htmlhidden.val(),
				value: $jQvalue.val(),
				trandate: $jQdate[0].slo.htmlhidden.val(),
				comments: $jQcomments.val(),
				reference: $jQreference[0].slo.htmlhidden.val(),
				status: $jQstatus.prop("checked") ? "1" : "0"
			});

			$ajax = $.ajax({
				data: preparePOST,
				url: "<?php echo $fs()->dir; ?>/?id=<?php echo $arr_transaction['acm_id']; ?>",
				type: "POST"
			}).done(function (data) {
				var _data = null;
				try {
					_data = JSON.parse(data);
				} catch (e) {
					messagesys.failure("Parsing JSON failed");
					inputStatus(false);
					return false;
				}

				if (_data.result == true) {
					messagesys.success(_data.message);
					inputStatus(false);
					return true;
				} else {
					messagesys.failure(_data.message);
					inputStatus(false);
					if (_data.focus != false)
						$("#" + _data.focus).focus().select();
					return false;
				}
			}).fail(function (a, b, c) {
				messagesys.failure(b + " - " + c);
			}).always(function () {
				inputStatus(false);
			});
		}
		$("#jQsubmit").on('click', function () {
			addStatement();
		});
		$("#jQpopupCancel").on('click', function () {
			if ($ajax != null) {
				$ajax.abort();
			}
			popup.close();
		});
		$jQcreditor.focus();
	});
</script>