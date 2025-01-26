<?php

use System\Layout\Gremium;

if (isset($_POST['method'], $_POST['employeeID']) && $_POST['method'] == "fetchrecord") {
	$employeeID = (int) $_POST['employeeID'];
	$query      = "SELECT
		usr_firstname, usr_lastname,
		usr_id, usr_username, usr_phone_list,
		gnd_name,
		lsf_id,lsf_name,
		lty_id,lty_name,lsc_name,
		ldn_id,ldn_name,
		
		DATE_FORMAT(usr_birthdate,'%d %M, %Y') AS usr_birthdate,
		DATE_FORMAT(usr_registerdate,'%d %M, %Y') AS usr_registerdate,
		DATE_FORMAT(lbr_resigndate,'%d %M, %Y') AS lbr_resigndate,
		lbr_socialnumber,
		trans_name,lbr_mth_name,lwt_name,
		lbr_id,cntry_name,
		lbr_fixedsalary,lbr_variable,lbr_allowance,lbr_trans_allowance,
		lbr_typ_sal_basic_salary,lbr_typ_sal_variable,lbr_typ_sal_allowance,lbr_typ_sal_transportation
	FROM
		labour
			JOIN users ON usr_id = lbr_id
			LEFT JOIN labour_shifts ON lbr_shift=lsf_id
			LEFT JOIN countries ON cntry_id=lbr_nationality
			LEFT JOIN (SELECT lty_id,lty_name,lsc_name FROM labour_type JOIN labour_section ON lsc_id=lty_section) AS _labourtype ON lty_id = usr_jobtitle
			LEFT JOIN gender ON gnd_id=usr_gender
			LEFT JOIN labour_residentail ON ldn_id=lbr_residential
			LEFT JOIN labour_method ON lbr_mth_id = lbr_payment_method
			LEFT JOIN workingtimes ON lwt_id = lbr_workingtimes
			LEFT JOIN labour_transportation ON lbr_transportation=trans_id
			LEFT JOIN labour_type_salary ON lbr_typ_sal_lty_id = usr_jobtitle AND lbr_typ_sal_lwt_id = lbr_workingtimes AND lbr_typ_sal_method = lbr_payment_method
			JOIN user_company ON urc_usr_id = {$app->user->info->id}
	WHERE
		lbr_id = $employeeID AND usr_id != 1 AND urc_usr_comp_id = usr_entity;";
	$r          = $app->db->query($query);
	if ($r) {
		if ($r->num_rows > 0 && $row = $r->fetch_assoc()) {

			header("HTTP_X_RESPONSE: SUCCESS");
			header("HTTP_X_PID: " . $row['usr_id']);
			$arr_socialids = array();

			$q_socialid_uploads = $app->db->query("SELECT up_id,up_name,up_size,DATE_FORMAT(up_date,'%d %M, %Y') as up_date,up_pagefile FROM uploads WHERE up_rel = $employeeID AND up_deleted=0");
			while ($row_socialid_uploads = $q_socialid_uploads->fetch_assoc()) {
				if (!isset($arr_socialids[$row_socialid_uploads['up_pagefile']])) {
					$arr_socialids[$row_socialid_uploads['up_pagefile']] = array();
				}
				$arr_socialids[$row_socialid_uploads['up_pagefile']][$row_socialid_uploads['up_id']] = array($row_socialid_uploads['up_name'], $row_socialid_uploads['up_size'], $row_socialid_uploads['up_date'], $row_socialid_uploads['up_id']);
			}

			$socialidphotos = "-";
			if (isset($arr_socialids[\System\Lib\Upload\Type::HrID->value])) {
				$socialidphotos = "";
				foreach ($arr_socialids[\System\Lib\Upload\Type::HrID->value] as $record) {
					$socialidphotos .= "<a title=\"{$record[0]}\" href=\"{$fs(187)->dir}/?id={$record[3]}&pr=v\"><img src=\"{$fs(187)->dir}/?id={$record[3]}&pr=t\" /></a>";
				}
			}


			$grem = new Gremium\Gremium(true);
			$grem->header();
			$grem->menu();
			if ($fs(227)->permission->read) {
				$grem->title()->serve("<span class=\"flex\">Personal Information:</span>");
				$grem->article()->open();
				$img = "user.jpg";
				if (
					isset($arr_socialids[\System\Lib\Upload\Type::HrPerson->value]) && is_array($arr_socialids[\System\Lib\Upload\Type::HrPerson->value])
					&& sizeof($arr_socialids[\System\Lib\Upload\Type::HrPerson->value]) > 0
				) {
					$imgid = reset($arr_socialids[\System\Lib\Upload\Type::HrPerson->value])[3];
					$img   = "download/?id={$imgid}&pr=t";
					unset($imgid);
				}

				foreach (['usr_birthdate', 'usr_phone_list', 'ldn_name', 'trans_name', 'lbr_socialnumber'] as $k) {
					$row[$k] = $row[$k] ?? "N/A";
				}

				echo <<<HTML
					<div class="info-headersec">
						<div class="profile-picture"><div style="background-image:url($img);"></div></div>
						<div style="min-width: 260px;">
							<div class="form">
								<label>
								<h1>Name</h1>
									<div>{$row['usr_firstname']} {$row['usr_lastname']}</div>
								</label>
							</div>
							<div class="form">
								<label>
									<h1>ID</h1>
									<div>{$row['usr_id']}</div>
								</label>
								<label>
									<h1>Gender</h1>
									<div>{$row['gnd_name']}</div>
								</label>
								<label>
									<h1>Nationality</h1>
									<div>{$row['cntry_name']}</div>
								</label>
							</div>
							<div class="form">
								<label>
									<h1>Birthdate</h1>
									<div>{$row['usr_birthdate']}</div>
								</label>
								<label>
									<h1>Registration date</h1>
									<div>{$row['usr_registerdate']}</div>
								</label>
								<label>
									<h1>Social ID</h1>
									<div>{$row['lbr_socialnumber']}</div>
								</label>
							</div>

							<div class="form">
								<label>
									<h1>Contact infomration</h1>
									<div>{$row['usr_phone_list']}</div>
								</label>
								<label>
									<h1>Residence</h1>
									<div>{$row['ldn_name']}</div>
								</label>
								<label>
									<h1>Transportation</h1>
									<div>{$row['trans_name']}</div>
								</label>
							</div>
							
						</div>
					</div>
					<div>
						<div class="form">
							<label>
								<h1>References</h1>
								<div style="padding:5px 10px;" class="attachments-view" id="attachementsList">{$socialidphotos}</div>
								
							</label>
						</div>
					</div>

				HTML;

				$grem->getLast()->close();

			}

			if ($fs(228)->permission->read) {
				$grem->title()->serve("<span class=\"flex\">Job Information:</span>");
				$grem->article()->open();
				$resignationMessage = empty($row['lbr_resigndate']) ? "" : "<div class=\"form\"><label><h1>Resignation state</h1><div>Resigned on {$row['lbr_resigndate']}</div></label></div>";

				echo <<<HTML
					<div style="min-width: 260px;">
						{$resignationMessage}
						<div class="form">
							<label>
								<h1>Job title</h1>
								<div>{$row['lsc_name']} {$row['lty_name']}</div>
							</label>
							<label>
								<h1>Payment method</h1>
								<div>{$row['lbr_mth_name']}</div>
							</label>
							<label></label>
						</div>
						<div class="form">
							<label>
								<h1>Working shift</h1>
								<div>{$row['lsf_name']}</div>
							</label>
							<label>
								<h1>Working Time</h1>
								<div>{$row['lwt_name']}</div>
							</label>
							<label></label>
						</div>
					</div>
				HTML;
				$grem->getLast()->close();
			}

			if ($fs(229)->permission->read) {
				$grem->title()->serve("<span class=\"flex\">Salary Details:</span>");
				$grem->article()->open();

				$sd_1 = (is_null($row['lbr_fixedsalary']) ? number_format((float) $row['lbr_typ_sal_basic_salary'], 2, ".", ",") : number_format((float) $row['lbr_fixedsalary'], 2, ".", ",")) . " " . $app->currency->shortname;
				$sd_2 = (is_null($row['lbr_variable']) ? number_format((float) $row['lbr_typ_sal_variable'], 2, ".", ",") : number_format((float) $row['lbr_variable'], 2, ".", ",")) . " " . $app->currency->shortname;
				$sd_3 = (is_null($row['lbr_allowance']) ? number_format((float) $row['lbr_typ_sal_allowance'], 2, ".", ",") : number_format((float) $row['lbr_allowance'], 2, ".", ",")) . " " . $app->currency->shortname;
				echo <<<HTML
					<div style="min-width: 260px;">
						<div class="form">
							<label>
								<h1>Salary</h1>
								<div>{$sd_1}</div>
							</label>
							<label>
								<h1>Variable</h1>
								<div>{$sd_2}</div>
							</label>
							<label>
								<h1>Allowance</h1>
								<div>{$sd_3}<div>
							</label>
						</div>
					</div>
				HTML;
				$grem->getLast()->close();
			}
			$grem->terminate();
			exit;
		} else {

			$grem = new Gremium\Gremium(true);
			header("HTTP_X_RESPONSE: ERROR");
			$grem->header()->status(Gremium\Status::Exclamation)->serve("<h1>Not Found</h1>");
			$grem->title()->serve("<span class=\"flex\">Loading select personnel failed:</span>");
			$grem->article()->serve('<ul>
				<li>Personnel ID is invalid</li>
				<li>Session has expired</li>
				<li>Database query failed, contact system administrator</li>
				<li>Permission denied or not enough privileges to proceed with this document</li>
				</ul>
				');
			$grem->terminate();
			exit;
		}
	}
	exit;
}

if ($app->xhttp) {
	exit;
}

$SmartListObject = new System\SmartListObject($app);
?>

<style type="text/css">
	.attachments-view>a {
		display: inline-block;
		border: solid 2px transparent;
		border-radius: 4px;
		padding: 0;
		width: 104px;
		height: 104px;
	}

	.attachments-view>a:hover {
		border-color: var(--button-border);
		text-decoration: none;
		z-index: 2;
	}

	.attachments-view>a>img {
		margin: 0;
		border-radius: 2px;
		max-width: 100px;
		max-height: 100px;
		position: absolute;
	}

	div.info-headersec {
		display: flex;
		flex-wrap: wrap;
		column-gap: 30px;
	}

	div.info-headersec>div {
		flex: 3;
	}

	div.info-headersec>div.profile-picture {
		flex: 1;
		margin-bottom: 20px;
		text-align: center;
	}

	div.info-headersec>div.profile-picture>div {
		display: inline-block;
		width: 100%;
		margin: 10px;
		border: solid 0px #ccc;
		width: 180px;
		height: 180px;
		background-size: auto 100%;
		background-repeat: no-repeat;
		background-position: 50% 50%;
		border-radius: 50% 50%;
		box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.4);
	}
</style>
<a id="jQtriggerlink" style="display: none;" href="" target="_blank"></a>
<?php

echo "<datalist id=\"personList\">" . $SmartListObject->systemIndividual($app->user->company->id) . "</datalist>";
$grem = new Gremium\Gremium(true);
$grem->header()->serve("<h1>{$fs()->title}</h1><ul><li id=\"jQdomPID\"></li></ul>");

$grem->menu()->open();
echo "<input id=\"employeIDFormSearch\" tabindex=\"1\" type=\"text\" data-slo=\":LIST\" data-list=\"personList\" class=\"flex\" placeholder=\"Employee name or id\" />";
echo "<input type=\"button\" id=\"jQedit\" tabindex=\"2\" disabled value=\"Edit information\" />";
echo "<input type=\"button\" id=\"jQprintIDCard\" tabindex=\"3\" disabled value=\"Print ID Card\" />";
$grem->getLast()->close();

?>

<div id="jQoutput" style="position:relative;"></div>

<script type="module">
	import { Popup } from './static/javascript/modules/gui/popup.js';
	$(document).ready(function (e) {
		let counter = 0;
		const linkTrigger = $("#jQtriggerlink"),
			buttonEdit = $("#jQedit"),
			buttonPrintID = $("#jQprintIDCard"),
			divOutput = $("#jQoutput"),
			spanIDTitle = $("#jQdomPID");
		let queryResponse = false;

		function addEvents() {
			document.getElementById("attachementsList")?.childNodes.forEach(elm => {
				elm.addEventListener("click", (e) => {
					e.preventDefault();
					let popAtt = new Popup();
					popAtt.addEventListener("close", function (p) {
						this.destroy();
					});
					popAtt.contentForm({ title: "Attachement preview" }, "<div style=\"text-align: center;\"><img style=\"max-width:600px;width:100%\" src=\"" + elm.href + "\" /></div>");
					popAtt.show();
				});
			});
		}

		let clear = function () {
			divOutput.html("");
			buttonPrintID.prop("disabled", true);
			buttonEdit.prop("disabled", true);
			spanIDTitle.html("");
			linkTrigger.prop("href", "");
			queryResponse = false;
		}
		var SLO_employeeID = $("#employeIDFormSearch").slo({
			onselect: function (value) {
				history.pushState({
					'method': 'view',
					'id': value.key,
					'name': value.value
				}, "<?= $fs(182)->title ?>", "<?= $fs(182)->dir ?>/?id=" + value.key);
				fn_fetchfile();
			},
			ondeselect: function () {

			},
			"limit": 10
		});

		var fn_fetchfile = function (_pushState = true) {
			overlay.show();
			$.ajax({
				data: {
					'method': 'fetchrecord',
					'employeeID': SLO_employeeID[0].slo.htmlhidden.val(),
				},
				url: "<?php echo $fs()->dir; ?>",
				type: "POST"
			}).done(function (o, textStatus, request) {
				let response = request.getResponseHeader('HTTP_X_RESPONSE');
				let responsepid = request.getResponseHeader('HTTP_X_PID');
				
				if (response == "ERROR") {
					clear();
					buttonPrintID.prop("disabled", true);
					buttonEdit.prop("disabled", true);
					divOutput.html(o);
					spanIDTitle.html("");
				} else if (response == "SUCCESS") {
					buttonPrintID.prop("disabled", false);
					buttonEdit.prop("disabled", false);
					divOutput.html(o);
					if (responsepid != undefined) {
						queryResponse = responsepid;
						spanIDTitle.html(responsepid);
					}
				}
				addEvents()
			}).fail(function (a, b, c) {
				messagessys.failure(b + " - " + c);
			}).always(function () {
				overlay.hide();
			});
		}
		buttonEdit.on("click", function () {
			if (queryResponse !== false) {
				linkTrigger.prop("href", "<?= $fs(134)->dir . "/?method=update&id="; ?>" + queryResponse);
				linkTrigger[0].click();
			}
		});
		buttonPrintID.on("click", function () {
			if (queryResponse !== false) {
				linkTrigger.prop("href", "<?= $fs(28)->dir . "/?id="; ?>" + queryResponse);
				linkTrigger[0].click();
			}
		});
		<?php
		if (isset($_GET['id'])) {
			$_GET['id'] = (int) $_GET['id'];
			$r          = $app->db->query("SELECT CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) as user_name FROM users WHERE usr_id={$_GET['id']};");
			if ($r && $row = $r->fetch_assoc()) {
				echo 'SLO_employeeID.set("' . $_GET['id'] . '","' . stripcslashes(trim($row['user_name'])) . '");';
				echo 'history.replaceState({\'method\':\'view\', \'id\': ' . (int) $_GET['id'] . ', \'name\': \'' . $row['user_name'] . '\'}, "' . $fs(182)->title . '", "' . $fs(182)->dir . '/?id=' . (int) $_GET['id'] . '");';
				echo 'fn_fetchfile(false);';
			}
		}
		?>
		window.onpopstate = function (e) {
			if (e.state && e.state.method == "view") {
				SLO_employeeID.set(e.state.id, e.state.name);
				fn_fetchfile();
			} else {
				clear();
				SLO_employeeID.clear(false);
			}
		};

		SLO_employeeID.focus();

	});
</script>