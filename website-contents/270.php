<?php
use System\Template\Gremium;
use System\SmartListObject;
use System\Timeline\Action;
use System\Timeline\Module;
use System\Timeline\Timeline;


if ($app->xhttp) {
	if (isset($_POST['objective']) && $_POST['objective'] == 'transaction') {
		header("Content-Type: application/json; charset=utf-8");
		$result = array(
			"result" => false,
			"errno" => 0,
			"error" => "",
			"insert_id" => 0,
			"type" => "insert",
			"debug" => ""
		);
		/* try {
															$transaction = new System\Finance\Transaction\Receipt($app);
															$transaction->issuerAccount($app->user->account);
															
															if (isset($_POST['attachments']) && is_array($_POST['attachments'])) {
																$transaction->attachments($_POST['attachments']);
															}

															if ($transaction->post()) {
																new System\Personalization\FrequentAccountUse($app, (int) $_POST['target-account'][1]);
																$balance             = $app->user->account->getBalance();
																$result['result']    = true;
																$result['insert_id'] = $transaction->insert_id;
																$result['type']      = "receipt";
																$result['balance']   = ($balance < 0 ? "(" : "") . number_format(abs($balance), 2) . ($balance < 0 ? ")" : "");
																$result['currency']  = $app->user->account->currency->shortname;

																$tl = new Timeline($app);
																$tl->register(Module::FinanceCash, Action::FinanceReceipt, $transaction->insert_id);
																if (!empty($_POST['party'])) {
																	$tl->register(Module::CRMCustomer, Action::FinanceReceipt, $_POST['party'][1], ["id" => $transaction->insert_id, "value" => number_format((float) $_POST['value'], 2, ".", ",") . " " . $app->user->account->currency->shortname]);
																}
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
														} */
		echo json_encode($result);
		exit;
	}

	$SmartListObject = new SmartListObject($app);

	$grem = new Gremium\Gremium(true);
	$grem->header()->prev("href=\"{$fs(173)->dir}\" data-href=\"{$fs(173)->dir}\"")->serve("<h1>{$fs()->title}</h1><cite></cite><div class=\"btn-set\">
			<button class=\"plus\" id=\"js-input_submit\" tabindex=\"9\">&nbsp;Add Customer</button></div>");

	$grem->article()->open();
	?>
	<form name="js-ref_form-main" id="js-ref_form-main" action="<?= $fs()->dir; ?>">
		<input type="hidden" name="challenge" value="<?= uniqid(); ?>" />
		<input type="hidden" name="objective" value="transaction" />

		<div class="form predefined">
			<label style="max-width:560px;">
				<h1>Customer name</h1>
				<div class="btn-set">
					<input tabindex="1" placeholder="Customer company name" data-required title="Creditor account" type="text" class="flex" name="name"
						id="name" />
				</div>
			</label>
		</div>


		<div class="form predefined">
			<label>
				<h1>Country</h1>
				<div class="btn-set">
					<input tabindex="2" placeholder="Country name" data-required title="Country name" type="text" class="flex" name="country"
						id="country" />
				</div>
			</label>
			<label>
				<h1>City</h1>
				<div class="btn-set">
					<input tabindex="3" placeholder="City name" data-required title="City name" type="text" class="flex" name="city" id="city" />
				</div>
			</label>
		</div>


		<div class="form predefined">
			<label>
				<h1>Address</h1>
				<div class="btn-set">
					<input tabindex="4" placeholder="Customer address" data-required title="Customer address" type="text" class="flex" name="address"
						id="address" />
				</div>
			</label>
		</div>

		<div class="form predefined">
			<label style="max-width:560px;" for="">
				<h1>Contact numbers</h1>
				<div class="btn-set">
					<input tabindex="5" placeholder="" data-required title="Phone number" type="text" class="flex" name="phone[]" id="phone[]" />
				</div>
				<div class="btn-set" style="margin-top:15px;">
					<input tabindex="5" data-required title="Phone number" type="text" class="flex" name="phone[]" id="phone[]" />
				</div>
			</label>
		</div>


		<div class="form">
			<label style="flex:0" for="">
				<h1>Attachments</h1>
				<div class="btn-set">
					<span id="js_upload_count" class="js_upload_count"><span>0 / 0</span></span>
					<input type="button" tabindex="6" id="js_upload_trigger" class="js_upload_trigger edge-right edge-left" value="Upload" />
					<input type="file" id="js_uploader_btn" class="js_uploader_btn" multiple="multiple" accept="image/*" />
					<span id="js_upload_list" class="js_upload_list">
						<div id="UploadDOMHandler">
							<table class="hover">
								<tbody>
									<?php
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

		</div>


	</form>
	<?php
	$grem->getLast()->close();
	$grem->terminate();
	unset($grem);
	?>

	<div>

		<datalist id="datalistCountries" style="display: none;">
			<?= $SmartListObject->userAccountsOutbound(null, [$app->user->account->id], \System\Personalization\Identifiers::SystemCountAccountOperation->value); ?>
		</datalist>
	</div>
	<?php

}
?>