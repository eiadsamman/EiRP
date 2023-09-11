<?php

use System\SmartListObject;

$accounting = new \System\Finance\Accounting($app);
define("TRANSACTION_ATTACHMENT_PAGEFILE", "188");

$__workingaccount = $accounting->account_information($app->user->account->id);
if ($__workingaccount)
	$__workingcurrency = $accounting->account_default_currency($__workingaccount['id']);

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

if (isset($_POST['method']) && $_POST['method'] == 'addstatement') {
	if (!$__workingaccount) {
		_JSON_output(false, $app->user->account->name . " is not a valid account");
	} elseif ($__workingcurrency === false) {
		_JSON_output(false, "No currency provided for selected account");
	}
	if (!$app->user->account->role->inbound) {
		_JSON_output(false, "Inbound rules is not allowed on `{$app->user->account->name}`");
	}


	$creditor = $_POST['creditor'];
	$category = $_POST['category'];
	$value = (float)trim(str_replace(",", "", $_POST['value']));
	$comments = isset($_POST['comments']) && trim($_POST['comments']) != "" ? addslashes($_POST['comments']) : null;
	$benificial = isset($_POST['benificial']) && trim($_POST['benificial']) != "" ? addslashes($_POST['benificial']) : null;
	$reference = isset($_POST['reference']) && trim($_POST['reference']) != "" ? addslashes($_POST['reference']) : null;
	$employee = isset($_POST['employee']) && (int)($_POST['employee']) != 0 ? (int)($_POST['employee']) : false;
	$rel = isset($_POST['rel']) && (int)$_POST['rel'] != 0 ? (int)$_POST['rel'] : "NULL";



	if ($r = $app->db->query("
		SELECT prt_id 
		FROM `acc_accounts` 
			JOIN user_partition ON prt_id=upr_prt_id AND upr_usr_id={$app->user->info->id} AND upr_prt_outbound=1
		WHERE prt_id=" . ((int)$creditor) . ";")) {
		if ($r->num_rows == 0) {
			_JSON_output(false, "Select a valid creditor account with outbound rules", "jQcreditor");
		}
	}


	if ($creditor == $__workingaccount['id']) {
		_JSON_output(false, "Debitor account can't be as same as Creditor account", "jQcreditor");
	}
	if ($r = $app->db->query("SELECT acccat_id FROM acc_categories WHERE acccat_id=$category;")) {
		if ($r->num_rows == 0) {
			_JSON_output(false, "Select the statement category", "jQcategory");
		}
	}
	if (trim($benificial) == "") {
		_JSON_output(false, "Benificial name is required", "jQbeneficial");
	}
	if ((float)$value <= 0) {
		_JSON_output(false, "Enter a valid float value", "jQvalue");
	}


	$creditor_currency = $accounting->account_default_currency($creditor);
	if ($creditor_currency === false) {
		_JSON_output(false, "No currency provided for the creditor account");
	}
	if ((int)$_POST['currency'] == 0) {
		_JSON_output(false, "Enter a valid currency", "jQcurrenyident");
	}



	$date = null;
	if (isset($_POST['trandate']) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_POST['trandate'], $match)) {
		if (checkdate($match[2], $match[3], $match[1])) {
			$date = mktime(0, 0, 0, $match[2], $match[3], $match[1]);
		}
	}
	if ($date == null) {
		_JSON_output(false, "Select the statement date", "jQdate");
	}

	$month = false;
	if (isset($_POST['month']) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $_POST['month'], $match)) {
		if (checkdate($match[2], $match[3], $match[1])) {
			$month = mktime(0, 0, 0, $match[2], $match[3], $match[1]);
		}
	}


	//Prepare attachment list
	$attachments = array();
	if (isset($_POST['attachments']) && is_array($_POST['attachments'])) {
		foreach ($_POST['attachments'] as $VAtt) {
			$attachments[] = (int)$VAtt;
		}
	}


	$value_from = $value;
	$value_to = $value;
	$exchangerate_crd = 1;
	$exchangerate_dbt = 1;
	if ($creditor_currency['id'] != $_POST['currency']) {
		$exchangerate = $accounting->currency_exchange($creditor_currency['id'], $_POST['currency']);
		if ($exchangerate === false) {
			_JSON_output(false, "Unable to exchange currencies with debitor account");
		}
		$value_to =			$exchangerate * $value_to;
		$exchangerate_crd =	$exchangerate;
	}
	if ($_POST['currency'] != $__workingcurrency['id']) {
		$exchangerate = $accounting->currency_exchange($_POST['currency'], $__workingcurrency['id']);
		if ($exchangerate === false) {
			_JSON_output(false, "Unable to exchange currencies with debitor account");
		}
		$value_from =		$exchangerate * $value_from;
		$exchangerate_dbt =	$exchangerate;
	}

	/*
		$balance=null;
		if($r=$app->db->query("SELECT SUM(atm_value) AS zsum FROM acc_temp JOIN acc_main ON acm_id=atm_main WHERE acm_rejected=0 AND atm_account_id={$__workingaccount['id']};")){if($row=$r->fetch_assoc()){$balance=$row['zsum'];}}
		if($balance==null || $balance<=0 || $balance<$value_from){_JSON_output(false,"Insufficient balance","jQvalue");}
	*/

	$result = true;
	$app->db->autocommit(false);

	$qacc_main = sprintf(
		"INSERT INTO acc_main (
		acm_usr_id,
		acm_editor_id,
		acm_ctime,
		acm_time,
		acm_type,
		acm_beneficial,
		acm_category,
		acm_comments,
		acm_reference,
		acm_realvalue,
		acm_realcurrency,
		acm_realcurrency_crd,
		acm_realcurrency_dbt,
		acm_month,
		acm_rel,
		acm_party
		) VALUES (
		%1\$s,
		%2\$d,
		%4\$s,%15\$s,
		%5\$d,
		'%6\$s',
		%7\$d,
		%8\$s,
		%9\$s,
		%10\$f,
		%11\$d,
		%12\$f,
		%13\$f,
		%14\$s,
		%16\$s,
		%17\$d
		);",
		$employee ? (int)$employee : "NULL",
		$app->user->info->id,
		"NULL",
		"'" . date("Y-m-d", $date) . "'",
		1,
		$benificial,
		(int)$category,
		($comments != null ? "'" . $comments . "'" : "NULL"),
		($reference != null ? "'" . $reference . "'" : "NULL"),
		$value,
		(int)$_POST['currency'],
		$exchangerate_crd,
		$exchangerate_dbt,
		($month ? "FROM_UNIXTIME(" . $month . ")" : "NULL"),
		"FROM_UNIXTIME(" . time() . ")",
		$rel,
		$app->user->company->id
	);
	$result &= $app->db->query($qacc_main);

	if (!$result) {
		$app->db->rollback();
		_JSON_output(false, "Database error, reference error 91 at line " . __LINE__);
	}


	$mainid = (int)$app->db->insert_id;

	//INSERT creditor statement
	$qacc_release = sprintf("INSERT INTO acc_temp (atm_account_id,atm_value,atm_dir,atm_main) VALUES (%1\$d,%2\$f,%3\$d,%4\$d);", $creditor, -1 * $value_from, 0, $mainid);
	$result &= $app->db->query($qacc_release);

	//INSERT debitor statement
	$qacc_insert = sprintf("INSERT INTO acc_temp (atm_account_id,atm_value,atm_dir,atm_main) VALUES (%1\$d,%2\$f,%3\$d,%4\$d);", $__workingaccount['id'], $value_to, 1, $mainid);
	$result &= $app->db->query($qacc_insert);

	//Attach uploaded files to the transaction
	if (sizeof($attachments) > 0) {
		$qacc_attach = "UPDATE uploads SET up_rel=$mainid, up_active = 1 WHERE up_id IN (" . implode(",", $attachments) . ") AND up_user = {$app->user->info->id};";
		$result &= $app->db->query($qacc_attach);
	}

	if ($result) {
		$app->db->commit();
		$balance = 0;
		$app->db->query("INSERT INTO user_settings (usrset_usr_id,usrset_name,usrset_usr_defind_name,usrset_value,usrset_time) 
				VALUES ({$app->user->info->id},'system_count_account_operation','$creditor','1',NOW()) ON DUPLICATE KEY UPDATE usrset_value=usrset_value+1;");

		if ($r = $app->db->query("SELECT SUM(atm_value) AS zsum FROM acc_temp JOIN acc_main ON acm_id=atm_main WHERE atm_account_id={$__workingaccount['id']} AND acm_rejected=0;")) {
			if ($row = $r->fetch_assoc()) {
				$balance = $row['zsum'];
			}
		}
		_JSON_output(true, "Statement submited successfully", null, array("newbalance" => number_format((float)$balance, 2, ".", ","), "id" => $mainid, "value" => number_format((float)$value, 2, ".", "")));
	} else {
		$app->db->rollback();
		_JSON_output(false, "Statement insertion failed");
	}
	exit;
}
if ($app->xhttp) {
	exit;
}
/*AJAX-END*/



$SmartListObject = new SmartListObject($app);

$_TEMPLATE = new \System\Template\Body("Test");
$_TEMPLATE->SetLayout(/*Sticky Title*/true,/*Command Bar*/ true,/*Sticky Frame*/ true);
$_TEMPLATE->FrameTitlesStack(true);

if (!$__workingaccount) {
	echo "<div class=\"btn-set\"><button>{$app->user->account->name}</button><span>Is not a valid account</span></div>";
} elseif ($__workingcurrency === false) {
	echo "<div class=\"btn-set\"><span class=\"bnt-error\">&nbsp;No currency provided for selected account</span></div>";
} elseif (!$app->user->account->role->inbound) {
	$_TEMPLATE->Title("&nbsp;Invalid outbound account!", null, "", null);
	$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Selected account is not valid for inbound operations:</span>");
	$_TEMPLATE->NewFrameBody('<ul>
		<li>Inbound accounts are only valid for payment operations, chose a valid account and try again</li>
		<li>Contact system adminstration for further assistance</li>
		<li>Permission denied or not enough privileges to proceed with this document</li>
		</ul>
		<b>Actions</b>
		<ul>
			<li>Goto <a href="' . $fs(99)->dir . '">Ledger report</a></li>
			<li>Goto <a href="' . $fs(95)->dir . '">New Payment</a></li>
		</ul>
		');
} else {
?>
	<iframe style="display:none;" src="" id="jQiframe"></iframe>

	<?php
	$_TEMPLATE->SetWidth("768px");
	$_TEMPLATE->Title("<a class=\"backward\" href=\"{$fs(99)->dir}\"></a>New " . $fs()->title, null, "");

	echo $_TEMPLATE->CommandBarStart();
	echo "<div class=\"btn-set\" style=\"justify-content:flex-end\">";
	echo "<span class=\"gap\">";
	echo "</span><button class=\"clr-green\" id=\"jQsubmit\" tabindex=\"9\">Submit Receipt</button>";
	echo "</div>";
	echo $_TEMPLATE->CommandBarEnd();

	$_TEMPLATE->NewFrameTitle("<span class=\"flex mediabond-hide\">New Receipt Statement</span>");
	echo $_TEMPLATE->NewFrameBodyStart();
	?>
	<table class="bom-table mediabond-table" id="jQformTable">
		<tbody>
			<tr>
				<th>Creditor</th>
				<td>
					<div class="btn-set"><input tabindex="1" type="text" data-slo=":LIST" data-list="jQcreditorList" class="flex" id="jQcreditor" /></div>
					<datalist id="jQcreditorList" style="display: none;">
						<?= $SmartListObject->financial_accounts_outbound(); ?>
					</datalist>

				</td>
			</tr>
			<tr>
				<th>Date</th>
				<td>
					<div class="btn-set">
						<input type="text" class="flex" data-slo=":DATE" value="<?php echo date("Y-m-d"); ?>" data-rangeend="<?php echo date("Y-m-d"); ?>" tabindex="1" id="jQdate" />
						<!-- <span>Month reference</span> -->
						<input id="jQmonth" tabindex="-1" value="" data-slo="MONTH" type="text" style="width:100px;display:none" />
					</div>
				</td>
			</tr>
			<tr>
				<th>Debitor</th>
				<td width="100%">
					<?php
					echo "<div class=\"btn-set\">";
					echo "<button tabindex=\"-1\">{$__workingaccount['group']}: {$__workingaccount['name']}</button>";
					echo "<input type=\"text\" readonly=\"readonly\" class=\"flex\" style=\"text-align:right\" tabindex=\"-1\" id=\"jQbalanceTitle\" value=\"" . number_format($__workingaccount['balance'], 2, ".", ",") . "\" />";
					echo "<span style=\"text-align:center\">{$__workingcurrency['shortname']}</span></div>";
					?>
				</td>
			</tr>
			<tr>
				<th>Category</th>
				<td>
					<div class="btn-set"><input type="text" data-slo=":LIST" data-list="jQcategoryList" tabindex="3" class="flex" id="jQcategory" /></div>
					<datalist id="jQcategoryList">
						<?= $SmartListObject->financial_categories(); ?>
					</datalist>
				</td>
			</tr>
			<tr>
				<th>Beneficial</th>
				<td>
					<div class="btn-set">
						<input type="text" class="flex" tabindex="4" data-slo=":LIST" data-list="jQbeneficialList" id="jQbeneficial" />
						<input type="text" tabindex="-1" data-slo="B00S" id="jQemployee" />
					</div>
					<datalist id="jQbeneficialList">
						<?= $SmartListObject->financial_beneficiary(); ?>
					</datalist>
				</td>
			</tr>
			<tr>
				<th>Value</th>
				<td>
					<div class="btn-set"><input type="number" tabindex="5" class="flex" id="jQvalue" pattern="\d*" min="0" inputmode="decimal" /><input value="<?php echo $__workingcurrency['shortname']; ?>" tabindex="-1" data-slodefaultid="<?php echo $__workingcurrency['id']; ?>" type="text" data-slo="CURRENCY_SYMBOL" id="jQcurrenyident" style="width:100px;" /></div>
					<!-- <div id="dom-div-valueformat" style="padding:10px 11px;color:#888">0.00</div> -->
				</td>
			</tr>
			<tr>
				<th>Attachments</th>
				<td>
					<div class="btn-set" style="justify-content:left">
						<button id="js_upload_trigger" class="js_upload_trigger">Upload</button>
						<input type="file" id="js_uploader_btn" class="js_uploader_btn" multiple="multiple" accept="image/*" />
						<span id="js_upload_list" class="js_upload_list"></span>
						<button id="js_upload_count" class="js_upload_count"><span>0 / 0</span> files</button>
						<!--<label class="btn-checkbox"><input type="checkbox" id="jQprodmode"><span>Prouductive mode</span></label>-->
					</div>
				</td>
			</tr>
			<tr>
				<th>Reference</th>
				<td>
					<div class="btn-set"><input type="text" data-slo="ACC_REFERENCE" tabindex="6" id="jQreference" class="flex" /><input type="text" id="jQrel" style="max-width:100px;min-width:100px;" tabindex="-1" placeholder="Related ID" /></div>
				</td>
			</tr>
			<tr>
				<th>Comments</th>
				<td><textarea type="text" tabindex="8" style="width:100%;" class="textarea" id="jQcomments" rows="7"></textarea></td>
			</tr>
		</tbody>
	</table>

	<?php
	echo $_TEMPLATE->NewFrameBodyEnd();
	$_TEMPLATE->NewFrameTitle("<span class=\"flex\">Session Receipts</span><span id=\"jQtotalEntries\">0</span><input tabindex=\"-1\" type=\"text\" style=\"text-align: right;\" readonly=\"readonly\" id=\"jQtotalTotal\" value=\"0\" />");
	echo $_TEMPLATE->NewFrameBodyStart();
	?>
	<table class="bom-table hover">
		<thead>
			<tr>
				<td>ID</td>
				<td stlye="text-align:right" colspan="2">Amount</td>
				<td>Benificial</td>
				<td colspan="3" stlye="text-align:center" width="100%"></td>
			</tr>
			</tr>
		</thead>
		<tbody id="jQoutput"></tbody>
	</table>

	<?php
	echo $_TEMPLATE->NewFrameBodyEnd();
	?>


	<script type="text/javascript">
		var $ajax = null;
		var trancount = 0;
		var trantotal = 0;
		$(document).ready(function(e) {
			Upload = $.Upload({
				objectHandler: $("#js_upload_list"),
				domselector: $("#js_uploader_btn"),
				dombutton: $("#js_upload_trigger"),
				list_button: $("#js_upload_count"),
				emptymessage: "[No files uploaded]",
				delete_method: 'permanent',
				upload_url: "<?= $fs(186)->dir ?>",
				relatedpagefile: <?php echo TRANSACTION_ATTACHMENT_PAGEFILE; ?>,
				multiple: true,
				inputname: "attachments"
			});
			<?php
			$empty = true;
			$accepted_mimes = array("image/jpeg", "image/gif", "image/bmp", "image/png");
			$r_release = $app->db->query("SELECT up_id,up_name,up_size,up_mime FROM uploads WHERE up_user={$app->user->info->id} AND up_pagefile=" . TRANSACTION_ATTACHMENT_PAGEFILE . " AND up_rel=0 AND up_deleted=0;");
			if ($r_release) {
				while ($row_release = $r_release->fetch_assoc()) {
					$empty = false;
					echo "Upload.AddListItem({$row_release['up_id']},'{$row_release['up_name']}',false,false,'" . (in_array($row_release['up_mime'], $accepted_mimes) ? "image" : "document") . "');";
				}
			}
			?>



			const $jQcreditor = $("#jQcreditor").slo({
					'limit': 10,
					onselect: function() {}
				}),
				$jQcurrency = $("#jQcurrenyident").slo(),
				$jQbeneficial = $("#jQbeneficial").slo({
					'limit': 7,
					onselect: function() {}
				}),
				$jQdate = $("#jQdate").slo(),
				$form = $("#jQformTable"),
				$jQmonth = $("#jQmonth").slo(),
				$jQreference = $("#jQreference").slo(),
				$jQemployee = $("#jQemployee").slo({
					'onselect': function(data) {
						$jQbeneficial.set(data.hidden, data.value);
						$("#jQbeneficial").prop("readonly", true).prop("disabled", true);
					},
					'ondeselect': function() {
						$("#jQbeneficial").prop("readonly", false).prop("disabled", false);
						$jQbeneficial.clear();
					}
				}),
				$jQcategory = $("#jQcategory").slo({
					'onselect': function(data) {}
				}),
				$jQrel = $("#jQrel"),
				domDivValueformat = document.getElementById("dom-div-valueformat");


			$("#jQvalue").on("input keydown keyup mousedown mouseup select contextmenu drop", function() {
				OnlyFloat(this, null, 0);
				/* let val = (Number(this.value.replace(/[^\d\.]/g, "")).numberFormat(4, ".", ","))
				domDivValueformat.innerHTML = $jQcurrency.get()[0].value + " " + (val.toString()) */
			});
			var addStatement = function() {
				var $creditor = $("#jQcreditor_1"),
					$category = $("#jQcategory_1"),
					$currency = $("#jQcurrenyident_1"),
					$date = $("#jQdate_1"),
					$dateSLO = $("#jQdate"),
					$monthSLO = $("#jQmonth"),
					$month = $("#jQmonth_1"),
					$creditorSLO = $("#jQcreditor"),
					$categorySLO = $("#jQcategory"),
					$currencySLO = $("#jQcurrenyident"),
					$benificialSLO = $("#jQbeneficial"),
					$employeeSLO = $("#jQemployee"),
					$employee = $("#jQemployee_1"),
					$value = $("#jQvalue"),
					$comments = $("#jQcomments"),
					$submitbtn = $("#jQsubmit"),
					$reference = $("#jQreference");

				var inputStatus = function(status) {
					$submitbtn.prop("disabled", status);
					$creditorSLO.prop("disabled", status);
					$categorySLO.prop("disabled", status);
					$value.prop("disabled", status);
					$currencySLO.prop("disabled", status);
					$benificialSLO.prop("disabled", status);
					$comments.prop("disabled", status);
					$reference.prop("disabled", status);
					$dateSLO.prop("disabled", status);
					$monthSLO.prop("disabled", status);
					$employeeSLO.prop("disabled", status);
				}
				if ($creditor.val() == "" || $creditor.val() == "0") {
					messagesys.failure("Select debitor account");
					$creditorSLO.focus().select();
					return false;
				}
				if ($creditor.val() == <?php echo $app->user->account->id; ?>) {
					messagesys.failure("Debitor account must not be same as Creditor account");
					$creditorSLO.focus().select();
					return false;
				}
				if ($category.val() == "" || $category.val() == "0") {
					messagesys.failure("Select the transaction category");
					$categorySLO.focus().select();
					return false;
				}
				if ($benificialSLO.val().trim() == "") {
					messagesys.failure("Enter the name of involved parties in this transaction");
					$jQbeneficial.focus();
					return false;
				}

				try {
					_value = parseFloat($value.val());
				} catch (e) {
					messagesys.failure("Enter a valid falot value");
					$value.focus().select();
					return false;
				}
				if (isNaN(_value) || _value <= 0) {
					messagesys.failure("Enter a valid float value");
					$value.focus().select();
					return false;
				}

				if ($currency.val() == "" || $currency.val() == "0") {
					messagesys.failure("Select the transaction currency");
					$currencySLO.focus().select();
					return false;
				}


				inputStatus(true);
				var preparePOST = $("#js_upload_list :input").serialize() + '&' + $.param({
					method: 'addstatement',
					creditor: $creditor.val(),
					category: $category.val(),
					benificial: $benificialSLO.val(),
					currency: $currency.val(),
					value: $value.val(),
					trandate: $date.val(),
					comments: $comments.val(),
					reference: $reference.val(),
					month: $month.val(),
					employee: $employee.val(),
					rel: ~~$jQrel.val(),
				});

				$ajax = $.ajax({
					data: preparePOST,
					url: "<?php echo $fs()->dir; ?>",
					type: "POST"
				}).done(function(data) {
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
						$("#jQoutput").prepend(
							"<tr data-transactionid=\"" + _data.id + "\">" +
							"<td>" + _data.id + "</td>" +
							"<td align=\"right\">" + _data.value + "</td>" +
							"<td>" + $currencySLO.val() + "</td>" +
							"<td>" + $benificialSLO.val() + "</td>" +
							"</tr>"
						);

						if (!$("#jQprodmode").prop("checked")) {
							$jQemployee.clear();
							//$jQreference.clear();
							$jQbeneficial.clear();
							$comments.val("");
						}
						trancount++;
						trantotal += ~~_data.value;
						$("#jQtotalEntries").html(trancount);
						$("#jQtotalTotal").val(trantotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,'));
						$("#jQbalanceTitle").val(_data.newbalance);
						BALANCE_UPDATE();
						inputStatus(false);
						$("#jQbeneficial").prop("readonly", false).prop("disabled", false);
						//$("#jQiframe").attr("src","<?= $fs(142)->dir ?>/?id="+_data.id);
						$value.val("").focus().select();
						Upload.clean();
						BALANCE_UPDATE();
						return true;
					} else {
						messagesys.failure(_data.message);
						inputStatus(false);
						if (_data.focus != false)
							$("#" + _data.focus).focus().select();
						return false;
					}
				}).fail(function(a, b, c) {
					messagesys.failure(b + " - " + c);
				}).always(function() {
					inputStatus(false);
				});
			}
			$("#jQsubmit").on('click', function() {
				addStatement();
			});
			$jQcreditor.focus();
		});
	</script>
<?php } ?>