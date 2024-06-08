<?php
use System\Finance\AccountRole;
use System\Finance\Forex;
use System\Template\Gremium;
use System\Finance\Account;
use System\SmartListObject;


if ($app->xhttp) {

	/**
	 * Edting request model
	 */
	if (isset($_POST['objective'], $_POST['statement-id']) && $_POST['objective'] == 'transaction' && (int) $_POST['statement-id'] != 0) {
		header("Content-Type: application/json; charset=utf-8");
		$result = array(
			"result" => false,
			"errno" => 0,
			"error" => "",
			"insert_id" => 0,
			"type" => "update",
			"debug" => ""
		);
		try {
			$statementNature = !empty($_POST['statement-nature']) && (int) $_POST['statement-nature'][1] != 0 ? (int) $_POST['statement-nature'][1] : 0;

			if ($statementNature == 0) {
				throw new System\Exceptions\Finance\TransactionException("Select a valid statmenet type");
			}

			$accountRole              = [new AccountRole(), new AccountRole()];
			$accountRole[0]->outbound = true;
			$accountRole[1]->inbound  = true;

			if ($statementNature == 1) {
				$transaction = new System\Finance\Transaction\Receipt($app);
				$transaction->issuerAccount(new Account($app, (int) $_POST['target-account'][1], $accountRole[0]));
				$transaction->targetAccount(new Account($app, (int) $_POST['source-account'][1], $accountRole[1]));
			} else {
				$transaction = new System\Finance\Transaction\Payment($app);
				$transaction->issuerAccount(new Account($app, (int) $_POST['source-account'][1], $accountRole[0]));
				$transaction->targetAccount(new Account($app, (int) $_POST['target-account'][1], $accountRole[1]));
			}
			$transaction->openState(empty($_POST['record-status']) ? false : true);
			$transaction->date($_POST['date'][0]);
			$transaction->category($_POST['category'][1] ?? 0);
			$transaction->beneficiary($_POST['beneficiary'][0] ?? "");
			$transaction->value($_POST['value'] ?? 0);
			$transaction->individual($_POST['individual'][1] ?? 0);
			$transaction->description($_POST['description'] ?? "");
			$transaction->reference($_POST['reference'][0] ?? "");
			$transaction->relation($_POST['relation'] ?? 0);

			if (!empty($_POST['exchange-override']) && !empty($_POST['exchange-dir-from']) && !empty($_POST['exchange-dir-to']) && !empty($_POST['exchange-value']) && $_POST['exchange-override'] == "true") {
				$transaction->overrideForex(
					(int) $_POST['exchange-dir-from'],
					(int) $_POST['exchange-dir-to'],
					(float) $_POST['exchange-value']
				);
			}
			
			if (isset($_POST['attachments']) && is_array($_POST['attachments'])) {
				$transaction->attachments($_POST['attachments']);
			}

			if ($transaction->edit((int) $_POST['statement-id'])) {
				$balance             = $app->user->account->getBalance();
				$result['result']    = true;
				$result['insert_id'] = $transaction->insert_id;
				$result['type']      = "update";
				$result['balance']   = ($balance < 0 ? "(" : "") . number_format(abs($balance), 2) . ($balance < 0 ? ")" : "");
				$result['currency']  = $app->user->account->currency->shortname;
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
			$result['error'] = $e->getMessage();
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


	/**
	 * Handle request
	 */

	$id = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : null;
	if (empty($id)) {
		$grem = new Gremium\Gremium();
		$grem->header()->serve("<h1>{$fs()->title}</h1>");//<ul><li></li></ul>
		$grem->article()->serve(
			<<<HTML
		<ul>
			<li>No valid document ID found</li>
			<li>Contact system adminstration for further assistance</li>
			<li>Permission denied or not enough privileges to proceed with this document</li>
		</ul>
		HTML
		);
		unset($grem);
		exit;
	}
	/**
	 * Open transaction record
	 */
	$statement = new System\Finance\Transaction\Statement($app);
	$read      = $statement->read($id ?? 0);
	if (!$read) {
		$grem = new Gremium\Gremium();
		$grem->header()->serve("<h1>{$fs()->title}</h1><cite>$id</cite>");//<ul><li></li></ul>
		$grem->menu()->serve("<span class=\"small-media-hide\">Requested document is not available</span>");
		$grem->article()->serve(
			<<<HTML
		<ul>
			<li>Document `<a data-targettitle="{$fs(104)->title}" data-href="{$fs(104)->dir}" data-targetid="$id" href="{$fs(104)->dir}/?id=$id">$id</a>` is not valid or doesn't exists on the current company scope</li>
			<li>Permission denied or not enough privileges to proceed with this document</li>
			<li>Contact system administrator for further assistance</li>
		</ul>
		HTML
		);
		unset($grem);
		exit;
	}

	/**
	 * Display editorial form
	 * 
	 */

	$SmartListObject = new SmartListObject($app);

	$grem  = new Gremium\Gremium(true);
	$preva = "data-targettitle=\"{$fs(104)->title}\" data-href=\"{$fs(104)->dir}\" data-targetid=\"{$read->id}\" href=\"{$fs(104)->dir}/?id={$read->id}\"";
	$grem->header()->prev($preva)->serve("<h1>{$fs()->title}</h1><cite>{$app->prefixList[13][0]}" . str_pad(($read ? $read->id : ""), $app->prefixList[13][1], "0", STR_PAD_LEFT) . "</cite>");

	$grem->menu()->serve(
		"<span class=\"small-media-hide flex\"></span>" .
		"<button class=\"success edge-left\" id=\"js-input_submit\" tabindex=\"10\">&nbsp;&nbsp;Save</button>"
	);

	$grem->title()->serve("<span class=\"flex\">Statement details</span>");
	$grem->article()->open();
	$current_date = new DateTime();
	$current_date = $current_date->format("Y-m-d");

	$a = abs($read->creditAmount);
	$b = abs($read->debitAmount);
	?>
	<form name="js-ref_form-main" id="js-ref_form-main" action="<?= $fs()->dir; ?>">
		<input type="hidden" name="challenge" value="<?= uniqid(); ?>" />
		<input type="hidden" name="objective" value="transaction" />
		<input type="hidden" name="statement-id" id="statement-id" value="<?= $read->id; ?>" />

		<input type="hidden" name="exchange-override" id="exchange-override" value="true" />
		<input type="hidden" name="exchange-dir-from" id="exchange-dir-from"
			value="<?= $a < $b ? $read->creditor->currency->id : $read->debitor->currency->id; ?>" />
		<input type="hidden" name="exchange-dir-to" id="exchange-dir-to"
			value="<?= $a >= $b ? $read->creditor->currency->id : $read->debitor->currency->id; ?>" />

		<div class="form predefined">
			<label style="min-width:200px;flex:2">
				<h1>Statement ID</h1>
				<div class="btn-set">
					<?= $app->prefixList[13][0] . str_pad($read->id, $app->prefixList[13][1], "0", STR_PAD_LEFT); ?>
				</div>
			</label>
			<label style="min-width:200px;flex:1">
				<h1>Type</h1>
				<div class="btn-set">
					<input tabindex="1" placeholder="Type" data-required title="Transaction type" data-touch="200" type="text" data-slo=":LIST"
						data-list="js-ref_nature-list" class="flex" name="statement-nature" id="statement-nature" value="<?= $read->type->name; ?>" />
				</div>
			</label>
			<label style="min-width:200px;flex:1" for="">
				<h1>Status</h1>
				<div class="btn-set">
					<label><input type="checkbox" name="record-status" name="status" id="status" <?= $read->canceled ? "" : " checked=\"checked\" "; ?> />
						Posted</label>
				</div>
			</label>
		</div>

		<div class="form predefined">
			<label style="min-width:200px;">
				<h1>Creditor</h1>
				<div class="btn-set">
					<input tabindex="1" placeholder="Creditor account" data-required title="Creditor account" data-touch="200" type="text"
						data-slo=":LIST" data-list="js-ref_creditor-list" class="flex" name="source-account" id="source-account" />
				</div>
			</label>
			<label>
				<h1>Debitor</h1>
				<div class="btn-set">
					<input tabindex="2" placeholder="Debitor account" data-required title="Debitor account" data-touch="200" type="text" data-slo=":LIST"
						data-list="js-ref_debitor-list" class="flex" name="target-account" id="target-account" />
				</div>
			</label>
		</div>

		<div class="form predefined">
			<label style="min-width:150px">
				<h1>Date</h1>
				<div class="btn-set">
					<input type="text" placeholder="Post date" class="flex" data-slo=":DATE" data-touch="107" title="Transaction date"
						value="<?= $read->dateTime->format("Y-m-d") ?>" data-rangeend="<?= $current_date ?>" tabindex="3" name="date" data-required />
					<input type="text" placeholder="Due date" class="flex" data-slo=":DATE" data-touch="108" title="Transaction date" value=""
						data-rangeend="<?= $current_date ?>" tabindex="-1" name="duedate" />
				</div>
			</label>
			<label style="min-width:300px">
				<h1>Category</h1>
				<div class="btn-set">
					<input type="text" placeholder="Statement category" data-required data-slo=":LIST" data-touch="105" title="Category"
						data-list="jQcategoryList" tabindex="4" class="flex" name="category" id="category"
						value="<?= $read->category->group . ": " . $read->category->name; ?>" data-slodefaultid="<?= $read->category->id; ?>" />
				</div>
			</label>
		</div>

		<div class="form">
			<label style="flex-basis:0%">
				<h1>Beneficiary</h1>
				<div class="btn-set">
					<input type="text" placeholder="Beneficiary name" data-mandatory class="flex" title="Beneficiary name" data-touch="102" tabindex="5"
						data-slo=":LIST"
						data-source="_/FinanceBeneficiaryList/slo/<?= md5("#Fg32-32-f-" . ($app->user->info->id)); ?>/slo_FinananceBeneficiaries.a"
						name="beneficiary" id="beneficiary" value="<?= $read->beneficiary ?>" data-slodefaultid="<?= $read->beneficiary ?>" />
					<input name="individual" id="individual" type="text" placeholder="Beneficiary ID" class="flex" tabindex="-1" title="System user"
						data-slo=":LIST" data-source="_/UserList/slo/<?= $app->id; ?>/slo_userList.a"
						value="<?= $read->individual ? $read->individual->fullName() : ""; ?>"
						data-slodefaultid="<?= $read->individual ? $read->individual->id : ""; ?>" />
				</div>
			</label>
		</div>

		<div class="form">
			<label style="min-width:300px;" for="">
				<h1>Amount</h1>
				<div class="btn-set">
					<input type="text" inputmode="decimal" placeholder="Payment value" data-required tabindex="5" class="flex" data-touch="101"
						title="Transaction value" min="0" name="value" id="value" value="<?= $read->value; ?>" />
					<span id="currency-hint"><?= $read->currency->shortname ?></span>
				</div>

				<div class="btn-set" id="exchange-form"
					style="margin-top:15px;display: <?= ($read->creditor->currency->id != $read->debitor->currency->id ? "flex" : "none"); ?>;">

					<span>
						<a id="exchange-action" href="<?= $fs(87)->dir; ?>">
							<?= $a < $b ? $read->creditor->currency->shortname : $read->debitor->currency->shortname; ?> →
							<?= $a >= $b ? $read->creditor->currency->shortname : $read->debitor->currency->shortname; ?>
						</a>
					</span>
					<input type="text" inputmode="decimal" data-required style="display:none" placeholder="Exchange rate" id="exchange-value"
						name="exchange-value" data-default="<?= $read->forexRate; ?>" <?= $fs(87)->permission->edit ? "" : "disabled"; ?>
						class="flex" value="<?= (float) number_format($a > $b ? $a / $b : $b / $a, 4); ?>" />

					<span id="exchange-hint" class="flex"><?= number_format($a > $b ? $a / $b : $b / $a, 4); ?></span>
				</div>

			</label>
			<label style="min-width:300px;">
			</label>
		</div>


		<div class="form">
			<label style="flex:0" for="">
				<h1>Attachments</h1>
				<div class="btn-set">
					<span id="js_upload_count" class="js_upload_count"><span>0 / 0</span></span>
					<input type="button" id="js_upload_trigger" class="js_upload_trigger edge-right edge-left" value="Upload" />
					<input type="file" id="js_uploader_btn" class="js_uploader_btn" multiple="multiple" accept="image/*" />
					<span id="js_upload_list" class="js_upload_list">
						<div id="UploadDOMHandler">
							<table class="hover">
								<tbody>
									<?php
									foreach ($read->attachments as $attachment) {
										echo \System\Attachment\Template::itemDom($attachment->id, "image", $attachment->name, true, 'attachments');
									}
									$accepted_mimes = array("image/jpeg", "image/gif", "image/bmp", "image/png");
									$r_release      = $app->db->query("SELECT up_id,up_name,up_size,up_mime FROM uploads WHERE up_user={$app->user->info->id} AND up_pagefile=" . \System\Attachment\Type::FinanceRecord->value . " AND up_rel=0 AND up_deleted=0 LIMIT 50;");
									if ($r_release) {
										while ($row_release = $r_release->fetch_assoc()) {
											echo \System\Attachment\Template::itemDom($row_release['up_id'], (in_array($row_release['up_mime'], $accepted_mimes) ? "image" : "document"), $row_release['up_name'], false, 'attachments');
										}
									}
									?>
								</tbody>
							</table>
						</div>
					</span>
				</div>
			</label>
			<label style="min-width:300px">
				<h1>Reference</h1>
				<div class="btn-set">
					<input type="text" placeholder="Statement reference..." value="<?= $read->reference ?? "" ?>"
						data-slodefaultid="<?= $read->reference ?? "" ?>" data-slo="ACC_REFERENCE" title="Reference" tabindex="-1" name="reference"
						class="flex" />
					<input type="text" placeholder="Related ID" value="<?= $read->relation ?? "" ?>" data-slodefaultid="<?= $read->relation ?? "" ?>"
						style="max-width:100px;min-width:100px;" title="Related transaction ID" tabindex="-1" placeholder="Related ID" name="relation" />
				</div>
			</label>
		</div>

		<div class="form">
			<label>
				<h1>Description</h1>
				<div class="btn-set">
					<textarea type="text" placeholder="Statement description..." data-required tabindex="7" title="Statement Description" data-touch="103"
						style="width:100%;min-width:100%;max-width:100%;min-height:100px;" class="textarea" name="description" id="description"
						rows="7"><?= $read->description ?? "" ?></textarea>
				</div>
			</label>
		</div>

	</form>
	<?php
	$grem->getLast()->close();
	$grem->terminate();
	unset($grem);
	?>
	<div>
		<datalist id="js-ref_creditor-list" style="display: none;">
			<?= $read->creditor ? $SmartListObject->userAccountsOutbound($read->creditor->id, null, \System\Personalization\Identifiers::SystemCountAccountOperation->value) : ""; ?>
		</datalist>
		<datalist id="js-ref_debitor-list" style="display: none;">
			<?= $read->debitor ? $SmartListObject->userAccountsInbound($read->debitor->id, null, \System\Personalization\Identifiers::SystemCountAccountOperation->value) : ""; ?>
		</datalist>
		<datalist id="jQcategoryList">
			<?= $SmartListObject->financialCategories(); ?>
		</datalist>
		<datalist id="js-ref_nature-list">
			<?= $SmartListObject->financialTransactionNature($read->type->value); ?>
		</datalist>
	</div>
	<?php
}
?>