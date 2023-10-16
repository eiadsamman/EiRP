<?php
use System\Finance\Transaction\Receipt;
use System\Personalization\FrequentAccountUse;
use System\Template\Gremium;
use System\Finance\Account;
use System\SmartListObject;

$predefined = new \System\Finance\PredefinedRules($app);
$defines    = $predefined->incomeRules();
$accounting = new \System\Finance\Accounting($app);

if ($app->xhttp) {
	$result = array(
		"result"    => false,
		"errno"     => 0,
		"error"     => '',
		'insert_id' => 0
	);
	if (isset($_POST['objective']) && $_POST['objective'] == 'transaction') {
		try {
			$transaction = new Receipt($app);
			$transaction->issuerAccount($app->user->account);
			$transaction->targetAccount(new Account($app, (int) $_POST['target-account'][1]));
			$transaction->date($_POST['date'][0])->category($_POST['category'][1]);
			$transaction->beneficiary($_POST['beneficiary'][0])->value($_POST['value']);
			$transaction->individual($_POST['individual'][1])->description($_POST['description']);
			$transaction->reference($_POST['reference'][0])->relation($_POST['relation']);

			if (isset($_POST['attachments']) && is_array($_POST['attachments'])) {
				$transaction->attachments($_POST['attachments']);
			}

			if ($transaction->post()) {
				$result['result']    = true;
				$result['insert_id'] = $transaction->insert_id;
				$result['balance']   = number_format($app->user->account->getBalance(), 2);
				$result['currency']  = $app->user->account->currency->shortname;
				new FrequentAccountUse($app, (int) $_POST['target-account'][1]);
			} else {
				$result['errno'] = 300;
				$result['error'] = "Transaction posting failed";
			}

		} catch (TypeError $e) {
			$result['errno'] = 300;
			$result['error'] = 'Uknown error, contact system administrator';
			$app->errorHandler->logError($e);
		} catch (System\Exceptions\Finance\TransactionException $e) {
			$result['errno'] = $e->getCode();
			$result['error'] = $e->getMessage();
		} catch (System\Exceptions\Finance\AccountNotFoundException $e) {
			$result['errno'] = 203;
			$result['error'] = "Select a valid account";
		} catch (System\Exceptions\Finance\ForexException $e) {
			$result['errno'] = 300;
			$result['error'] = "Forex conversion failed";
		} catch (\mysqli_sql_exception $e) {
			$result['errno'] = 300;
			$result['error'] = "Server error, try again later";
			$app->errorHandler->logError($e);
		}
		echo json_encode($result);
		exit;
	}
	exit;
}


$SmartListObject = new SmartListObject($app);

if (is_null($app->user->account) || !$app->user->account->role->inbound) {
	$grem = new Gremium\Gremium();
	$grem->header()->status(Gremium\Status::Exclamation)->serve("<h1>Invalid inbound account!</h1>");
	$grem->legend()->serve("<span class=\"flex\">Selected account is not valid for inbound operations:</span>");
	$grem->article()->serve(
		<<<HTML
		<ul>
			<li>Receipts require an account with inbound rules, chose a valid account and try again</li>
			<li>Contact system adminstration for further assistance</li>
			<li>Permission denied or not enough privileges to proceed with this document</li>
		</ul>
		<b>Actions</b>
		<ul>
			<li>Goto <a href="{$fs(99)->dir}">Ledger report</a></li>
			<li>Goto <a href="{$fs(95)->dir}">New Payment</a></li>
		</ul>
		HTML
	);
	unset($grem);
	exit;

} else {
	?>
	<iframe style="display:none;" src="" id="jQiframe"></iframe>

	<form name="js-ref_form-main" id="js-ref_form-main">
		<input type="hidden" name="challenge" value="<?= uniqid(); ?>" />
		<input type="hidden" name="objective" value="transaction" />
		<?php
		$grem = new Gremium\Gremium(true);
		$grem->header()->prev($fs(179)->dir)->serve("<h1>{$fs()->title}</h1><cite></cite><div class=\"btn-set\"><button id=\"js-input_submit\" tabindex=\"9\">Submit Receipt</button></div>");
		if (sizeof($defines) > 0) {
			$grem->menu()->sticky(false)->open();
			echo "<input placeholder=\"Actions...\" type=\"text\" id=\"js-defines\" data-slo=\":LIST\" tabindex=\"-1\" data-list=\"defines\" />";
			$grem->getLast()->close();
		}
		$grem->article()->open();
		?>
		<table class="bom-table mediabond-table" id="js-ref_table-main">
			<tbody>
				<tr class="predefined">
					<th>Creditor</th>
					<td class="btn-set">
						<input tabindex="1" data-required title="Creditor account" data-touch="200" type="text" data-slo=":LIST" data-list="js-ref_creditor-list" class="flex" name="target-account"
							id="target-account" />
					</td>
				</tr>
				<tr class="predefined">
					<th>Date</th>
					<td class="btn-set">
						<input type="text" class="flex" data-required data-slo=":DATE" data-touch="107" title="Transaction date" value="<?php echo date("Y-m-d"); ?>"
							data-rangeend="<?php echo date("Y-m-d"); ?>" tabindex="2" name="date" />
					</td>
				</tr>
				<tr class="predefined">
					<th>Debitor</th>
					<td class="btn-set">
						<?php
						if ($app->user->account->role->view) {
							echo "<input id=\"issuer-account-balance\" type=\"text\" readonly=\"readonly\" class=\"flex\" abindex=\"-1\" value=\"" . number_format($app->user->account->balance, 2, ".", ",") . "\" />";
						}
						echo "<span>{$app->user->account->currency->shortname}</span>";
						echo "<span>{$app->user->account->type->name}: {$app->user->account->name}</span>";
						?>
					</td>
				</tr>
				<tr class="predefined">
					<th>Category</th>
					<td class="btn-set">
						<input type="text" data-required data-slo=":LIST" data-touch="105" title="Category" data-list="jQcategoryList" tabindex="3" class="flex" name="category" id="category" />
					</td>
				</tr>
				<tr>
					<th>Beneficiary</th>
					<td class="btn-set">
						<input type="text" data-required class="flex" title="Beneficiary name" data-touch="102" tabindex="4" data-slo=":LIST" data-list="js-ref_beneficiary-list" name="beneficiary"
							id="beneficiary" />
						<input type="text" tabindex="-1" title="System user" data-slo="B00S" name="individual" id="individual" />
					</td>
				</tr>
				<tr>
					<th>Value</th>
					<td class="btn-set">
						<input type="number" data-required tabindex="5" class="flex" data-touch="101" title="Transaction value" pattern="\d*" min="0" inputmode="decimal" name="value" id="value" />
					</td>
				</tr>
				<tr>
					<th>Attachments</th>
					<td>
						<div class="btn-set" style="justify-content:left">
							<input type="button" id="js_upload_trigger" class="js_upload_trigger" value="Upload" />
							<input type="file" id="js_uploader_btn" class="js_uploader_btn" multiple="multiple" accept="image/*" />
							<span id="js_upload_list" class="js_upload_list"></span>
							<span id="js_upload_count" class="js_upload_count" ><span>0 / 0</span> files</span>
						</div>
					</td>
				</tr>
				<tr>
					<th>Reference</th>
					<td class="btn-set">
						<input type="text" data-slo="ACC_REFERENCE" title="Reference" tabindex="6" name="reference" class="flex" />
						<input type="text" style="max-width:100px;min-width:100px;" title="Related transaction ID" tabindex="-1" placeholder="Related ID" name="relation" />
					</td>
				</tr>
				<tr>
					<th>Description</th>
					<td class="btn-set">
						<textarea type="text" data-required tabindex="8" title="Statement Description" data-touch="103" style="width:100%;min-width:100%;max-width:100%;min-height:100px;" class="textarea"
							name="description" id="description" rows="7"></textarea>
					</td>
				</tr>
			</tbody>
		</table>
	</form>
	<br />
	<?php
	$grem->getLast()->close();

	$grem->legend()->serve("<span class=\"flex\">Session Receipts</span>");
	$grem->article()->open();
	?>
	<table class="bom-table hover">
		<thead>
			<tr>
				<td>ID</td>
				<td stlye="text-align:right" colspan="2">Amount</td>
				<td style="width:100%">Benificial</td>
			</tr>
		</thead>
		<tbody id="jQoutput"></tbody>
	</table>
	<?php
	$grem->getLast()->close();
	unset($grem);
	?>
	<div>
		<datalist id="defines">
			<?php
			if (sizeof($defines) > 0)
				foreach ($defines as $rule)
					echo "<option data-id = \"{$rule->id}\" data-account_bound = \"{$rule->outbound_account}\" data-category = \"{$rule->category}\" >{$rule->name}</option>";
			?>
		</datalist>
		<datalist id="js-ref_creditor-list" style="display: none;">
			<?= $SmartListObject->userAccountsOutbound(null, [$app->user->account->id], \System\Personalization\Identifiers::SystemCountAccountOperation->value); ?>
		</datalist>
		<datalist id="jQcategoryList">
			<?= $SmartListObject->financialCategories(); ?>
		</datalist>
		<datalist id="js-ref_beneficiary-list">
			<?= $SmartListObject->financialBeneficiary(); ?>
		</datalist>
	</div>


	<script type="text/javascript">
		const callurl = "<?= $fs()->dir; ?>";
		$(document).ready(function (e) {
			Upload = $.Upload({
				objectHandler: $("#js_upload_list"),
				domselector: $("#js_uploader_btn"),
				dombutton: $("#js_upload_trigger"),
				list_button: $("#js_upload_count"),
				emptymessage: "[No files uploaded]",
				delete_method: 'permanent',
				upload_url: "<?= $fs(186)->dir ?>",
				relatedpagefile: <?= \System\Attachment\Type::FinanceRecord->value; ?>,
				multiple: true,
				inputname: "attachments"
			});
			<?php
			$accepted_mimes = array("image/jpeg", "image/gif", "image/bmp", "image/png");
			$r_release      = $app->db->query("SELECT up_id,up_name,up_size,up_mime FROM uploads WHERE up_user={$app->user->info->id} AND up_pagefile=" . \System\Attachment\Type::FinanceRecord->value . " AND up_rel=0 AND up_deleted=0 LIMIT 50;");
			if ($r_release) {
				while ($row_release = $r_release->fetch_assoc()) {
					echo "Upload.AddListItem({$row_release['up_id']},'{$row_release['up_name']}',false,false,'" . (in_array($row_release['up_mime'], $accepted_mimes) ? "image" : "document") . "');";
				}
			}
			?>
		});
	</script>
	<script src="static/javascript/Transactions.js"></script>
<?php } ?>