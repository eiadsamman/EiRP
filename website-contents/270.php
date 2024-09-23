<?php
use System\Models\Company;
use System\Models\Country;
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
		);


		$cord = false;
		$res  = preg_match_all("/-?([0-9]{1,2}|1[0-7][0-9]|180)(\.[0-9]{1,10})/", $_POST['mapurl'] ?? "", $matches);
		if (is_array($matches) && sizeof($matches) > 0 && sizeof($matches[0]) > 1) {
			$cord = [$matches[0][0], $matches[0][1]];
		}

		$entry = new Company($app);
		try {
			$entry->name           = $_POST['company'] ?? null;
			$entry->city           = $_POST['city'] ?? null;
			$entry->address        = $_POST['address'] ?? null;
			$entry->country        = isset($_POST['country'], $_POST['country'][1]) ? new Country((int) $_POST['country'][1]) : null;
			$entry->contactNumbers = !empty($_POST['phone']) && is_array($_POST['phone']) ? implode("\n", $_POST['phone']) : null;
			if ($cord) {
				$entry->latitude  = (float) $cord[0];
				$entry->longitude = (float) $cord[1];
			}
			if ($entry->add()) {
				$result['result']    = true;
				$result['insert_id'] = $entry->id;

				$tl = new Timeline($app);
				$tl->register(Module::Company, Action::Create, $entry->id);
			} else {
				$result['error'] = "Database error, try again later";
				$result['errno'] = 1;
			}
		} catch (TypeError $e) {
			$result['error'] = "Provide all required fields";
			$result['errno'] = $e->getCode();
		} catch (\System\Exceptions\Company\InvalidData $e) {
			$result['error'] = $e->getMessage();
			$result['errno'] = $e->getCode();
		}

		echo json_encode($result);
		exit;
	}

	$SmartListObject = new SmartListObject($app);

	$grem = new Gremium\Gremium(true);
	$grem->header()->prev("href=\"{$fs(173)->dir}\" data-href=\"{$fs(173)->dir}\"")->serve("<h1>{$fs()->title}</h1><cite></cite><div class=\"btn-set\">
			<button class=\"plus\" id=\"js-input_submit\" tabindex=\"9\">&nbsp;Save</button></div>");

	$grem->article()->open();
	?>
	<form name="js-ref_form-main" id="js-ref_form-main" action="<?= $fs()->dir; ?>">
		<input type="hidden" name="challenge" value="<?= uniqid(); ?>" />
		<input type="hidden" name="objective" value="transaction" />

		<div class="form predefined">
			<label>
				<h1>Customer name</h1>
				<div class="btn-set">
					<input tabindex="1" placeholder="Customer company name" data-required title="Creditor account" type="text" class="flex" name="company"
						id="company" />
				</div>
			</label>
			<label for="" style="min-width:250px">
				<h1>Contact numbers</h1>
				<div class="btn-set">
					<input style="min-width:50px;width:50px" tabindex="2" placeholder="Mobile phone number" title="Phone number" type="text" class="flex"
						name="phone[]" id="phone[]" />
					<input style="min-width:50px;width:50px" tabindex="3" placeholder="Work phone number" title="Phone number" type="text" class="flex"
						name="phone[]" id="phone[]" />
				</div>
			</label>
		</div>


		<div class="form">
			<label>
				<h1>Map location</h1>
				<div class="btn-set">
					<input tabindex="4" placeholder="Map location URL" title="Map location URL" type="text" class="flex" name="mapurl" id="mapurl"
						value="https://www.google.com/maps/@30.0156998,31.420846,60m" />
				</div>
			</label>
			<label for="" style="min-width:250px">
				<h1>Attachments</h1>
				<div class="btn-set">
					<span id="js_upload_count" class="js_upload_count"><span>0 / 0</span></span>
					<input type="button" tabindex="5" id="js_upload_trigger" class="js_upload_trigger edge-right edge-left" value="Upload" />
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

		<div class="form predefined">
			<label>
				<h1>Country</h1>
				<div class="btn-set">
					<input tabindex="6" placeholder="Country name" title="Country name" data-slo="COUNTRIES" type="text" class="flex" name="country"
						id="country" />
				</div>
			</label>
			<label>
				<h1>City</h1>
				<div class="btn-set">
					<input tabindex="7" placeholder="City name" title="City name" type="text" class="flex" name="city" id="city" />
				</div>
			</label>
		</div>

		<div class="form predefined">
			<label>
				<h1>Address</h1>
				<div class="btn-set">
					<textarea tabindex="8" placeholder="Customer address" title="Customer address" type="text" class="flex" name="address"
						style="height:100px;" id="address"></textarea>
				</div>
			</label>
		</div>


	</form>
	<?php
	$grem->getLast()->close();
	$grem->terminate();
	unset($grem);

}
?>