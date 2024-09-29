<?php
use System\Models\Company;
use System\Template\Gremium;
use System\Timeline\Action;
use System\Timeline\Module;
use System\Timeline\Timeline;


if ($app->xhttp) {

	if (isset($_POST['method']) && $_POST['method'] == 'new-feedback') {
		$dt         = new \DateTime();
		$result     = array(
			"result" => false,
			"errno" => 0,
			"error" => "",
			"action" => "",
			"issuer" => $app->user->info->fullName(),
			"timestamp" => $dt->format("dS M, Y") . "<i>" . $dt->format("H:i") . "</i>",
			"debug" => ""
		);
		$action     = isset($_POST['action'][1]) ? Action::tryFrom((int) $_POST['action'][1]) : null;
		$remindDeta = null;

		if (isset($_POST['remindDate'])) {
			$remindDeta = Datetime::createFromFormat("Y-m-d", $_POST['remindDate'][1]);
			if ($remindDeta)
				$remindDeta->setTime(0, 0, 0);
			else
				$remindDeta = null;
		}

		if ($action != null && $action != Action::Empty && "" !== trim($_POST['message']) && 0 != (int) $_POST['owner']) {
			$tl = new Timeline($app);


			if ($tl_register = $tl->register(Module::CRMCustomer, $action, (int) $_POST['owner'], null, $_POST['message'], $remindDeta)) {
				$result['result'] = true;
				$result['action'] = $action->name;

				/* Link uploads */
				if (isset($_POST['attachments']) && is_array($_POST['attachments'])) {
					$stmt = $app->db->prepare("UPDATE uploads SET up_rel = $tl_register , up_active = 1  WHERE up_id = ? AND up_user = {$app->user->info->id}");
					foreach ($_POST['attachments'] as &$attach) {
						$stmt->bind_param('i', $attach);
						if (!$stmt->execute()) {
							return false;
						}
					}
				}
			} else {
				$result['errno'] = "2";
				$result['error'] = "Posting new feedback failed, try again later";
			}
		} else {
			$result['errno'] = "1";
			$result['error'] = "Provide all required fields";
		}

		echo json_encode($result);
		exit;
	}

	$perpage_val = 20;
	$id          = !empty($_REQUEST['id']) ? (int) $_REQUEST['id'] : null;
	$party       = new Company($app);

	if ($party->load($id ?? 0)) {
		/* Read timeline and clear notifications */
		$mods = [
			Module::Company->value,
			Module::FinanceCash->value,
			Module::CRMCustomer->value,
			Module::Inventory->value,
		];
		$mods = join(",", $mods);


		$q = "INSERT INTO 
					timeline_track
						(tlrk_tl_id, tlrk_type, tlrk_usr_id)
				SELECT
					tl_id, 1 , {$app->user->info->id}
				FROM
					timeline
						LEFT JOIN `timeline_track` ON tl_id = tlrk_tl_id
				WHERE
					tl_owner = {$party->id} AND tl_module IN ($mods) AND tlrk_tl_id IS NULL;";

		$r = $app->db->execute_query($q);


		$grem = new Gremium\Gremium(true, false);
		$grem->header()->prev("href=\"{$fs(173)->dir}\" data-href=\"{$fs(173)->dir}\"")->serve("<h1>{$party->name}</h1>");//<cite>{$app->prefixList[10][0]}" . str_pad($party->id, $app->prefixList[10][1], "0", STR_PAD_LEFT) . "</cite>
		$grem->title()->serve("<span class=\"flex\">Customer Information</span>");

		$grem->article()->open(); ?>
		<div class="insection-splitview">
			<div style="min-width:250px; max-width: 500px; flex:1;">

				<div class="form">
					<label>
						<h1 id="for-action">Finance Tools</h1>
						<div class="btn-set">
							<span>Balance</span><span><?= number_format($party->financialBalance, 2) . " " . $app->currency->shortname; ?></span>
						</div>
						<div class="btn-set">
							<a class="edge-left" href="<?= $fs(91)->dir ?>/?party=<?= $party->id ?>"  data-href="<?= $fs(91)->dir ?>/?party=<?= $party->id ?>"><?= $fs(91)->title ?></a>
							<a class="edge-right" href="<?= $fs(95)->dir ?>/?party=<?= $party->id ?>" data-href="<?= $fs(95)->dir ?>/?party=<?= $party->id ?>"><?= $fs(95)->title ?></a>
						</div>
						<div class="btn-set">
							<a class="edge-left edge-right">New Invoice</a>
						</div>
					</label>
				</div>

				<div class="form">
					<label for="">
						<h1 id="for-action">Sales Management</h1>
						<div class="btn-set">
							<button class="standard edge-left">New sales order</button>
						</div>
					</label>
				</div>

				<div class="form">
					<label for="">
						<h1 id="for-action">Inventory Planning</h1>
						<div class="btn-set">
							<button class="standard edge-left">Replenish products</button>
						</div>
					</label>
				</div>

			</div>
			<div style="min-width:350px;flex: 2;">
				<div class="form">
					<label>
						<h1>Customer ID / name</h1>
						<div class="btn-set">
							<span><?= $app->prefixList[10][0] . str_pad($party->id, $app->prefixList[10][1], "0", STR_PAD_LEFT); ?></span>
							<span><?= $party->name; ?></span>
						</div>
					</label>
				</div>

				<div class="form">
					<label>
						<h1>Address</h1>
						<div class="btn-set">
							<span>City: </span>
							<span><?= $party->country ? $party->country->name . " - " : "-"; ?><?= $party->city; ?></span>
						</div>
						<div class="btn-set">
							<span>Address: </span>
							<span><?= $party->address; ?></span>
						</div>
						<?php if ($party->longitude && $party->latitude) { ?>
							<div class="btn-set">
								<span>Map location: </span>
								<span><a target="_blank" href="https://www.google.com/maps/@<?= $party->latitude; ?>,<?= $party->longitude; ?>,60m">Goolge
										Maps</a></span>
							</div>
						<?php } ?>
					</label>
				</div>
				<div class="form">
					<label>
						<h1>Contacts</h1>
						<?php
						$cont = explode("\n", $party->contactNumbers ?? "");
						foreach ($cont as $c) {
							echo trim($c != "") ? "<div class=\"btn-set\"><span><a href=\"tel:$c\">$c</a></span></div>" : "";
						}
						?>
					</label>
				</div>

				<?php if ($party->legal && $fs(265)->permission->read) { ?>
					<div class="form">
						<label>
							<h1>Commercial Registration</h1>
							<div class="btn-set">
								<span><?= $party->legal->registrationNumber; ?></span>
							</div>
						</label>
						<label>
							<h1>Tax Registration Number</h1>
							<div class="btn-set">
								<span><?= $party->legal->taxNumber; ?></span>
							</div>
						</label>
					</div>
					<div class="form">
						<label>
							<h1>VAT Registration Number</h1>
							<div class="btn-set">
								<span><?= $party->legal->vatNumber; ?></span>
							</div>
						</label>
					</div>
				<?php } ?>
			</div>
		</div>



		<?php
		$grem->getLast()->close();

		$tl    = new Timeline($app);
		$query = $tl->query($party->id);
		$query->modules(Module::CRMCustomer, Module::FinanceCash, Module::Company);
		$query->actions(Action::FinancePayment, Action::FinanceReceipt, Action::Create, Action::Feedback, Action::Modify, Action::Email, Action::PhoneCall);

		$current_date = new DateTime();
		$current_date = $current_date->format("Y-m-d");

		$grem->title()->serve("<span class=\"flex\">Customer Follow-up</span>");
		$grem->article()->open();


		$prev_docs      = "";
		$accepted_mimes = array("image/jpeg", "image/gif", "image/bmp", "image/png");
		$r_release      = $app->db->query("SELECT up_id,up_name,up_size,up_mime FROM uploads WHERE up_user = {$app->user->info->id} 
			AND up_pagefile=" . \System\Attachment\Type::Timeline->value . " AND up_rel = 0 AND up_deleted = 0 LIMIT 50;");
		if ($r_release) {
			while ($row_release = $r_release->fetch_assoc()) {
				$prev_docs .= \System\Attachment\Template::itemDom($row_release['up_id'], (in_array($row_release['up_mime'], $accepted_mimes) ? "image" : "document"), $row_release['up_name'], false, 'attachments');
			}
		}

		////box-shadow: 10px 0px 10px -15px rgba(100, 100, 100, 0.45);
		echo <<<HTML
		<div class="insection-splitview">
			<div style="min-width:250px; max-width: 500px; flex:1;">
				<form id="newAactionForm" action="{$fs()->dir}">
					<input type="hidden" name="method" value="new-feedback" />
					<input type="hidden" name="owner" value="{$party->id}" />
					<div class="form">
						<label>
							<h1 id="for-action">Action</h1>
							<div class="btn-set">
								<input name="action" id="tlType" placeholder="Feedback action"  class="flex" type="text" data-slo=":LIST" data-list="js-actionsList" />
							</div>
						</label>
					</div>
					<div class="form">
						<label>
							<h1 id="for-message">Feedback comments</h1>
							<div class="btn-set">
								<textarea class="flex" placeholder="" name="message" id="message" style="height: 100px;max-height: 100px;"></textarea>
							</div> 
						</label>
					</div>
					
					<div class="form">
						<label for="">
							<h1>Attachments</h1>
							<div class="btn-set">
								<span id="js_upload_count" class="js_upload_count"><span>0 / 0</span></span>
								<input type="button" id="js_upload_trigger" class="js_upload_trigger edge-right edge-left" value="Upload" />
								<input type="file" id="js_uploader_btn" class="js_uploader_btn" multiple="multiple" accept="image/*" />
								<span id="js_upload_list" class="js_upload_list">
									<div id="UploadDOMHandler">
										<table class="hover">
											<tbody>
												{$prev_docs}
											</tbody>
										</table>
									</div>
								</span>
							</div>
						</label>
					</div>
					<div class="form">
						<label>
							<h1>Next schedule date</h1>
							<div class="btn-set">
								<input id="remindDate" name="remindDate" type="text" placeholder="" class="flex" data-slo=":DATE" title="Next schedule date"
								data-rangestart="{$current_date}" name="scheduledate" />
							</div>
						</label>
					</div>
					<div class="form">
						<label for="">
							<div class="btn-set center">
								<button type="submit">Submit</button>
							</div>
						</label>
					</div>	
				</form>
			</div>

			<div style="min-width:350px;flex: 2;" class="limit-height">
	HTML;
		if ($query->execute()) {
			echo "<div class=\"timeline\" id=\"timelineContainer\">";
			$al = false;
			foreach ($query->fetch() as $entry) {
				$al = true;
				echo "
					<div>
						<span>" . $entry->timestamp->format("dS M, Y") . "<i>" . $entry->timestamp->format("H:i") . "</i></span>
						" . ($entry->action->toString() != "" ? "<h1>{$entry->action->toString()}</h1>" : "") . "
						<div>
							{$entry->describe()}
							" . nl2br($entry->message) . "
						</div>";

				if (sizeof($entry->attachments) > 0) {
					echo "<div class=\"attachments-view\" id=\"attachementsList\">";
					foreach ($entry->attachments as $file) {

						if (explode("/", $file->mime)[0] == "image") {
							echo "<a title=\"{$file->name}\" href=\"{$fs(187)->dir}?id={$file->id}&pr=v\">";
							echo "<img src=\"{$fs(187)->dir}?id={$file->id}&pr=t\" />";
							echo "</a>";
						} else {
							echo "<a title=\"{$file->name}\" href=\"{$fs(187)->dir}?id={$file->id}\" data-attachment=\"force\" target=\"_blank\">";
							$extpos = strrpos($file->name, ".", -1);
							if ($extpos !== false) {
								$ext      = strtolower(substr($file->name, $extpos + 1));
								$clr      = crc32($ext) % 360;
								$filename = substr($file->name, 0, $extpos);
							} else {
								$ext      = ".";
								$clr      = 0;
								$filename = "";
							}
							echo "<span>";
							echo "<span style=\"color: hsl($clr, 50%, 50%);\">{$ext}</span>";
							echo "<div>" . (number_format($file->size / 1024, 0)) . "KB</div>";
							echo "<div>{$filename}</div>";
							echo "</span>";
							echo "</a>";
						}
					}
					echo "</div>";
				}

				echo "
						<cite>{$entry->issuer->fullName()}</cite>
					</div>";
				//$entry->timestamp->getTimezone()->getLocation()['country_code']
			}
			echo "</div>";
		}
		echo <<<HTML
		</div>
		</div>
	HTML;

		$grem->getLast()->close();
		$grem->terminate();
		unset($grem);
		?>

		<datalist id="js-actionsList">
			<?php
			$actionEnums = [
				Action::Feedback,
				Action::PhoneCall,
				Action::Email,
			];

			foreach ($actionEnums as $v) {
				echo "<option data-id=\"{$v->value}\">{$v->toString()}</option>";
			}
			?>
		</datalist>
		<?php

	} elseif ($id == null) {
		$grem = new Gremium\Gremium(true);
		$grem->header()->prev("href=\"{$fs(173)->dir}\" data-href=\"{$fs(173)->dir}\"")->serve("<h1>{$fs(173)->title}</h1>");

		$grem->title()->serve("<span>Bad request</span>");
		$grem->article()->serve(
			<<<HTML
			<ul>
				<li>Empty request or invalid request</li>
				<li>Document is locked or out of scope</li>
				<li>Contact system administrator for further assistance</li>
				<li>Back to <a href="{$fs(173)->dir}" data-href="{$fs(173)->dir}">Customers page</a></li>
			</ul>
			HTML
		);
		unset($grem);
	} else {
		$grem = new Gremium\Gremium(true);
		$grem->header()->prev("href=\"{$fs(173)->dir}\" data-href=\"{$fs(173)->dir}\"")->serve("<h1>{$fs(173)->title}</h1><cite>$id</cite>");
		$grem->title()->serve("<span class=\"small-media-hide\">Requested document is not available</span>");
		$grem->article()->serve(
			<<<HTML
			<ul>
				<li>Document is locked or out of scope</li>
				<li>Contact system administrator for further assistance</li>
				<li>Back to <a href="{$fs(173)->dir}" data-href="{$fs(173)->dir}">Customers page</a></li>
			</ul>
			HTML
		);
		unset($grem);
	}
}