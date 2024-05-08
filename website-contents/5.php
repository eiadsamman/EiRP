<?php
if (isset($_GET['modify-user'], $_GET['token']) && $_GET['token'] == session_id()) {

	$modify_user = new System\Individual\Employee($app);

	try {
		$modify_user->load((int) $_GET['modify-user']);
	} catch (\System\Exceptions\HR\PersonNotFoundException $e) {
	}

	if ($modify_user->loaded && isset($_POST['invoke'], $_POST['relative']) && $_GET['modify-user'] == $_POST['relative'] && $_POST['invoke'] == "info") {

		$new_premissions = false;
		if (isset($_POST['perm'], $_POST['perm'][1]) && $app->permission((int) $_POST['perm'][1])) {
			$new_premissions = $app->permission((int) $_POST['perm'][1]);
		}

		if ($new_premissions) {
			/* Changin own account is forbiden */
			if (($modify_user->info->id != $app->user->info->id)) {
				echo "00";
				exit;
			}
			/* Change target account permissions to higher levels than current account isn't allowed */
			if (($new_premissions->id > $app->user->info->permissions)) {
				echo "00";
				exit;
			}
		}

		//$app->permission($modify_user->info->permissions)->level > $app->permission($app->user->info->permissions)->level


		if (!preg_match('/^(?=[a-z]{1})(?=.{4,26})(?=[^.]*\.?[^.]*$)(?=[^_]*_?[^_]*$)[\w.]+$/iD', $_POST['username'])) {
			echo "01";
			exit;
		}

		$passwordskip = false;
		if ($_POST['_password'] == "*****") {
			$passwordskip = true;
		}

		if ((strlen($_POST['_password']) < 5 || strlen($_POST['_password']) > 26) && !$passwordskip) {
			echo "02";
			exit;
		}

		if ($r = $app->db->query("SELECT usr_id FROM users WHERE usr_username='{$_POST['username']}' AND usr_id!='{$modify_user->info->id}';")) {
			if ($r->num_rows > 0) {
				echo "03";
				exit;
			}
		}


		$pass = password_hash($_POST['_password'], PASSWORD_BCRYPT, ["cost" => "12"]);
		;
		if (
			$r = $app->db->query(
				"UPDATE users SET 
					usr_username = '{$_POST['username']}',
					usr_password = " . ($passwordskip ? "`usr_password`" : "'$pass'") . ",
					usr_privileges = " . ($new_premissions ? $new_premissions->id : "usr_privileges") . ",
					usr_activate = " . (isset($_POST['active']) ? "1" : "0") . "
			WHERE usr_id='{$modify_user->info->id}';"
			)
		) {
			echo "10";
			exit;
		} else {

			echo "00";
			exit;
		}
	}

	if ($modify_user->loaded && isset($_POST['invoke'], $_POST['id'], $_GET['modify-user'], $_POST['relative'], $_GET['token']) && $_GET['token'] == session_id() && $_GET['modify-user'] == $_POST['relative'] && $_GET['modify-user'] == $modify_user->info->id && $_POST['invoke'] == "add-company") {
		$id = (int) $_POST['id'];
		$r  = $app->db->query("SELECT 
			comp_id,comp_name,urc_usr_comp_id
		FROM 
			companies 
				LEFT JOIN user_company ON urc_usr_id={$modify_user->info->id} AND urc_usr_comp_id=comp_id
		WHERE 
			comp_id = {$id};");
		if ($r) {
			if ($row = $r->fetch_assoc()) {
				if (!is_null($row['urc_usr_comp_id'])) {
					echo "1";
				} else {
					echo "<tr>";
					echo "<td class=\"op-remove noselect\"><span></span></td>";
					echo "<td>{$row['comp_name']}<input type=\"hidden\" name=\"a[{$row['comp_id']}]\" value=\"\" /></td>";
					echo "</tr>";
				}
			} else {
				echo "0";
			}
		} else {
			echo "0";
		}
		exit;
	}

	if ($modify_user->loaded && isset($_POST['invoke'], $_POST['id'], $_POST['relative']) && $_GET['modify-user'] == $_POST['relative'] && $_GET['modify-user'] == $modify_user->info->id && $_POST['invoke'] == "add-costcenter") {
		$id = (int) $_POST['id'];
		$r  = $app->db->query("SELECT 
			ccc_id,ccc_name,usrccc_ccc_id
		FROM 
			inv_costcenter 
				LEFT JOIN  user_costcenter ON usrccc_usr_id={$modify_user->info->id} AND usrccc_ccc_id=ccc_id
		WHERE 
			ccc_id = {$id};");
		if ($r) {
			if ($row = $r->fetch_assoc()) {
				if (!is_null($row['usrccc_ccc_id'])) {
					echo "1";
				} else {
					echo "<tr>";
					echo "<td class=\"op-remove noselect\"><span></span></td>";
					echo "<td>{$row['ccc_name']}<input type=\"hidden\" name=\"a[{$row['ccc_id']}]\" value=\"\" /></td>";
					echo "</tr>";
				}
			} else {
				echo "0";
			}
		} else {
			echo "0";
		}
		exit;
	}

	if ($modify_user->loaded && isset($_POST['invoke'], $_POST['id'], $_POST['relative']) && $_GET['modify-user'] == $_POST['relative'] && $_GET['modify-user'] == $modify_user->info->id && $_POST['invoke'] == "add-account") {
		$id = (int) $_POST['id'];
		$r  = $app->db->query("SELECT 
			prt_id,prt_name,ptp_name,cur_shortname,comp_name,ptp_id,comp_id,upr_prt_id
		FROM 
			`acc_accounts` 
				LEFT JOIN user_partition ON upr_prt_id=prt_id AND upr_usr_id='{$modify_user->info->id}'
				JOIN `acc_accounttype` ON ptp_id=prt_type
				JOIN currencies ON cur_id = prt_currency
				JOIN companies ON comp_id=prt_company_id
		WHERE 
			prt_id = {$id};");
		if ($r) {
			if ($row = $r->fetch_assoc()) {
				if (!is_null($row['upr_prt_id'])) {
					echo "1";
				} else {
					echo "<tr>";
					echo "<td class=\"op-remove noselect\"><span></span></td>";
					echo "<td>{$row['comp_name']}<input type=\"hidden\" name=\"a[{$row['prt_id']}]\" value=\"\" /></td>";
					echo "<td>{$row['ptp_name']}: {$row['prt_name']}</td>";
					echo "<td>{$row['cur_shortname']}</td>";
					echo "<td style=\"padding:0px;\"><label style=\"padding:5px;display:block\"><input name=\"b[{$row['prt_id']}][0]\" type=\"checkbox\" /></label></td>";
					echo "<td style=\"padding:0px;\"><label style=\"padding:5px;display:block\"><input name=\"b[{$row['prt_id']}][1]\" type=\"checkbox\" /></label></td>";
					echo "<td style=\"padding:0px;\"><label style=\"padding:5px;display:block\"><input name=\"b[{$row['prt_id']}][2]\" type=\"checkbox\" /></label></td>";
					echo "<td style=\"padding:0px;\"><label style=\"padding:5px;display:block\"><input name=\"b[{$row['prt_id']}][3]\" type=\"checkbox\" /></label></td>";
					echo "</tr>";
				}
			} else {
				echo "0";
			}
		} else {
			echo "0";
		}
		exit;
	}

	if ($modify_user->loaded && isset($_POST['invoke'], $_GET['modify-user'], $_POST['relative'], $_GET['token']) && $_GET['token'] == session_id() && $_GET['modify-user'] == $_POST['relative'] && $_GET['modify-user'] == $modify_user->info->id && $_POST['invoke'] == "accounts") {
		$app->db->autocommit(false);
		$result = true;
		$result &= $app->db->query("DELETE FROM user_partition WHERE upr_usr_id={$modify_user->info->id};");
		if (isset($_POST['a']) && is_array($_POST['a']) && sizeof($_POST['a']) > 0) {
			$q     = sprintf("INSERT INTO user_partition (upr_usr_id,upr_prt_id,upr_prt_inbound,upr_prt_outbound,upr_prt_fetch,upr_prt_view) VALUES ");
			$smart = "";
			foreach ($_POST['a'] as $k => $v) {
				$acc_id    = (int) $k;
				$bounds    = array();
				$bounds[0] = isset($_POST['b'][$acc_id][0]) ? 1 : 0;
				$bounds[1] = isset($_POST['b'][$acc_id][1]) ? 1 : 0;
				$bounds[2] = isset($_POST['b'][$acc_id][2]) ? 1 : 0;
				$bounds[3] = isset($_POST['b'][$acc_id][3]) ? 1 : 0;
				$q .= $smart . "({$modify_user->info->id},$acc_id,{$bounds[0]},{$bounds[1]},{$bounds[2]},{$bounds[3]})";
				$smart     = ",";
			}
			$result &= $app->db->query($q);
		}
		if ($result) {
			echo "1";
			$app->db->commit();
		} else {
			echo "0";
			$app->db->rollback();
		}
		exit;
	}

	if ($modify_user->loaded && isset($_POST['invoke'], $_GET['modify-user'], $_POST['relative'], $_GET['token']) && $_GET['token'] == session_id() && $_GET['modify-user'] == $_POST['relative'] && $_GET['modify-user'] == $modify_user->info->id && $_POST['invoke'] == "companies") {
		$app->db->autocommit(false);
		$result = true;
		$result &= $app->db->query("DELETE FROM user_company WHERE urc_usr_id={$modify_user->info->id};");
		if (isset($_POST['a']) && is_array($_POST['a']) && sizeof($_POST['a']) > 0) {
			$q     = sprintf("INSERT INTO user_company (urc_usr_id,urc_usr_comp_id) VALUES ");
			$smart = "";
			foreach ($_POST['a'] as $k => $v) {
				$comp_id = (int) $k;
				$q .= $smart . "({$modify_user->info->id},$comp_id)";
				$smart   = ",";
			}
			$result &= $app->db->query($q);
		}

		if ($result) {
			echo "1";
			$app->db->commit();
		} else {
			echo "0";
			$app->db->rollback();
		}
		exit;
	}

	if ($modify_user->loaded && isset($_POST['invoke'], $_GET['modify-user'], $_POST['relative'], $_GET['token']) && $_GET['token'] == session_id() && $_GET['modify-user'] == $_POST['relative'] && $_GET['modify-user'] == $modify_user->info->id && $_POST['invoke'] == "costcenter") {
		$app->db->autocommit(false);
		$result = true;
		$result &= $app->db->query("DELETE FROM user_costcenter WHERE usrccc_usr_id={$modify_user->info->id};");
		if (isset($_POST['a']) && is_array($_POST['a']) && sizeof($_POST['a']) > 0) {
			$q     = sprintf("INSERT INTO user_costcenter (usrccc_usr_id,usrccc_ccc_id) VALUES ");
			$smart = "";
			foreach ($_POST['a'] as $k => $v) {
				$ccc_id = (int) $k;
				$q .= $smart . "({$modify_user->info->id},$ccc_id)";
				$smart  = ",";
			}
			$result &= $app->db->query($q);
		}

		if ($result) {
			echo "1";
			$app->db->commit();
		} else {
			echo "0";
			$app->db->rollback();
		}
		exit;
	}
}
?>

<div style="padding:20px 0px 10px 0px;min-width:300px;max-width:800px;background-color: var(--root-background-color);position: sticky;top:50px;z-index: 50;">
	<form action="<?= $fs()->dir; ?>/" method="GET" id="frmUserSelection">
		<input type="hidden" name="modify-user" id="ModifyUser" value="<?php echo isset($modify_user) && $modify_user->loaded ? $modify_user->info->id : ""; ?>" />
		<input type="hidden" name="token" value="<?php echo session_id(); ?>" />
		<div class="btn-set">
			<span>Employee Name \ ID</span>
			<input type="text" data-slo="B001" class="flex" value="<?php echo isset($modify_user) && $modify_user->loaded ? $modify_user->info->username : ""; ?>" id="inpUserSelection"
				placeholder="Select user..." /><button type="submit">Modify</button>
		</div>
	</form>
</div>

<div id="UserDetailts" style="min-width:300px;max-width:800px;position:relative;">
	<?php if (isset($modify_user) && $modify_user->loaded) { ?>
		<form id="FormInfoModify">
			<input type="hidden" name="relative" value="<?php echo $modify_user->info->id; ?>">
			<input type="hidden" name="invoke" value="info">
			<div style="margin-top:0px;margin-bottom:5px;">
				<div id="FormInfoModifyOverLay" style="display:none;position: absolute;background-color: rgba(230,230,234,0.7);top:0px;left:0px;right:0px;bottom: 0px;z-index: 8;cursor: wait;">
				</div>
				<div class="btn-set" style="position: sticky;top:112px;z-index: 4;padding-top:15px;padding-bottom:0px;background-color:var(--root-background-color)">
					<span class="flex">User Information</span><button id="FormInfoModifySubmitButton" type="button">Save</button>
				</div>
				<div style="padding-left:10px;margin-top:10px;/*overflow-y: auto;max-height: 253px;*/">
					<table>
						<tbody>
							<tr>
								<th>ID</th>
								<td style="width:100%">
									<div class="btn-set"><label class="btn-checkbox"><input type="checkbox" name="active" <?= ($modify_user->info->active ? " checked=\"checked\" " : ""); ?> />
											<span>&nbsp;Active</span></label><input type="text" value="<?= $modify_user->info->id; ?>" class="flex" readonly="readonly" /></div>
								</td>
							</tr>
							<tr>
								<th>Username</th>
								<td>
									<div class="btn-set"><input type="text" placeholder="Username..." name="username" class="flex" value="<?= $modify_user->info->username; ?>" /></div>
								</td>
							</tr>
							<tr>
								<th>Password</th>
								<td>
									<div class="btn-set"><input type="password" placeholder="Password..." name="_password" class="flex" value="*****" /></div>
								</td>
							</tr>
							<?php if ($modify_user->info->id == $app->user->info->id) { ?>
								<tr>
									<th>Permissions</th>
									<td class="btn-set"><input type="text" value="<?= $app->permission($modify_user->info->permissions)->name; ?>" class="flex" readonly="readonly" disabled="disabled" /></td>
								</tr>
							<?php } else { ?>
								<tr>
									<th>Permissions</th>
									<td class="btn-set"><input type="text" name="perm" placeholder="Permissions..." id="ListObjectPerm" class="jQpermsel"
											data-slodefaultid="<?= $app->permission($modify_user->info->permissions)->id; ?>" value="<?= $app->permission($modify_user->info->permissions)->name; ?>"
											data-slo="PERM_LEVEL" class="flex" /></td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
		</form>


		<form id="FormCompanyModify">
			<input type="hidden" name="relative" value="<?php echo $modify_user->info->id; ?>">
			<input type="hidden" name="invoke" value="companies">
			<div style="margin-top:0px;margin-bottom:5px;">
				<div id="FormCompanyModifyOverLay" style="display:none;position: absolute;background-color: rgba(230,230,234,0.7);top:0px;left:0px;right:0px;bottom: 0px;z-index: 8;cursor: wait;">
				</div>
				<div class="btn-set" style="position: sticky;top:112px;z-index: 3;padding-top:15px;padding-bottom:0px;background-color:var(--root-background-color)">
					<span class="flex">Registered Commpanies</span><input class="flex" data-slo="COMPANY" id="ListObjectAddCompany" type="text" placeholder="Add Company..." name=""><button
						id="FormCompanyModifySubmitButton" type="button">Save</button>
				</div>

				<div style="padding-left:10px;margin-top:10px;/*overflow-y: auto;max-height: 253px;*/">
					<table class="hover">
						<thead>
							<tr>
								<td></td>
								<td width="100%">Company</td>
							</tr>
						</thead>
						<tbody id="FormCompanyList">
							<?php
							$r = $app->db->query("SELECT comp_name,comp_id,urc_usr_comp_id FROM companies JOIN user_company ON urc_usr_id={$modify_user->info->id} AND urc_usr_comp_id=comp_id;");
							if ($r) {
								while ($row = $r->fetch_assoc()) {
									echo "<tr>";
									echo "<td class=\"op-remove noselect\" data-account_id=\"{$row['comp_id']}\"><span></span></td>";
									echo "<td>{$row['comp_name']}<input type=\"hidden\" name=\"a[{$row['comp_id']}]\" value=\"\" /></td>";
									echo "</tr>";
								}
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</form>

		<form id="FormCostCenter">
			<input type="hidden" name="relative" value="<?php echo $modify_user->info->id; ?>">
			<input type="hidden" name="invoke" value="costcenter">
			<div style="margin-top:0px;margin-bottom:5px;">
				<div id="FormCompanyModifyOverLay" style="display:none;position: absolute;background-color: rgba(230,230,234,0.7);top:0px;left:0px;right:0px;bottom: 0px;z-index: 8;cursor: wait;">
				</div>
				<div class="btn-set" style="position: sticky;top:112px;z-index: 2;padding-top:15px;padding-bottom:0px;background-color:var(--root-background-color)">
					<span class="flex">Registered Cost Centers</span><input class="flex" data-slo="COSTCENTER" id="ListObjectAddCostCenter" type="text" placeholder="Add Cost Center..." name=""><button
						id="FormCostCenterModifySubmitButton" type="button">Save</button>
				</div>

				<div style="padding-left:10px;margin-top:10px;/*overflow-y: auto;max-height: 253px;*/">
					<table class="hover">
						<thead>
							<tr>
								<td></td>
								<td width="100%">Cost Center</td>
							</tr>
						</thead>
						<tbody id="FormCostCenterList">
							<?php
							$r = $app->db->query("SELECT ccc_id,ccc_name FROM inv_costcenter JOIN user_costcenter  ON usrccc_usr_id={$modify_user->info->id} AND usrccc_ccc_id=ccc_id;");
							if ($r) {
								while ($row = $r->fetch_assoc()) {
									echo "<tr>";
									echo "<td class=\"op-remove noselect\" data-costcenter_id=\"{$row['ccc_id']}\"><span></span></td>";
									echo "<td>{$row['ccc_name']}<input type=\"hidden\" name=\"a[{$row['ccc_id']}]\" value=\"\" /></td>";
									echo "</tr>";
								}
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</form>


		<form id="FormAccountModify">
			<input type="hidden" name="relative" value="<?php echo $modify_user->info->id; ?>">
			<input type="hidden" name="invoke" value="accounts">
			<div style="margin-top:0px;margin-bottom:5px;">
				<div class="btn-set" style="position: sticky;top:112px;z-index: 1;padding-top:15px;padding-bottom:0px;background-color:var(--root-background-color)">
					<span class="flex">Registered Accounts</span><input class="flex" data-slo="ACC_ALL" id="ListObjectAddAccount" type="text" placeholder="Add Account..." name=""><button
						id="FormAccountModifySubmitButton" type="button">Save</button>
				</div>
				<div style="padding-left:10px;margin-top:10px;/*overflow-y: auto;max-height: 253px;*/">
					<table class="hover">
						<thead>
							<tr>
								<td></td>
								<td>Company</td>
								<td>Account</td>
								<td width="100%">Currency</td>
								<td colspan="4" title="Allow account acccess and bounds&#10;Inbound - Outbound - Access - View">Rules
								</td>
							</tr>
						</thead>
						<tbody id="FormAccountList">
							<?php
							$r = $app->db->query("SELECT 
									prt_id,prt_name,ptp_name,cur_shortname,comp_name,upr_prt_id,ptp_id,comp_id,
									upr_prt_inbound,upr_prt_outbound,upr_prt_fetch,upr_prt_view
								FROM 
									`acc_accounts` 
										JOIN user_partition ON upr_prt_id=prt_id AND upr_usr_id='{$modify_user->info->id}'
										JOIN `acc_accounttype` ON ptp_id=prt_type
										JOIN currencies ON cur_id = prt_currency
										JOIN companies ON comp_id=prt_company_id
								ORDER BY
									comp_name,ptp_name,prt_name,cur_id;");
							if ($r) {
								while ($row = $r->fetch_assoc()) {
									echo "<tr>";
									echo "<td class=\"op-remove noselect\"><span></span></td>";
									echo "<td>{$row['comp_name']}<input type=\"hidden\" name=\"a[{$row['prt_id']}]\" value=\"\" /></td>";
									echo "<td>{$row['ptp_name']}: {$row['prt_name']}</td>";
									echo "<td>{$row['cur_shortname']}</td>";
									echo "<td style=\"padding:0px;\"><label style=\"padding:5px;display:block\"><input name=\"b[{$row['prt_id']}][0]\" " . ((int) $row['upr_prt_inbound'] == 1 ? "checked=\"checked\"" : "") . " type=\"checkbox\" /></label></td>";
									echo "<td style=\"padding:0px;\"><label style=\"padding:5px;display:block\"><input name=\"b[{$row['prt_id']}][1]\" " . ((int) $row['upr_prt_outbound'] == 1 ? "checked=\"checked\"" : "") . " type=\"checkbox\" /></label></td>";
									echo "<td style=\"padding:0px;\"><label style=\"padding:5px;display:block\"><input name=\"b[{$row['prt_id']}][2]\" " . ((int) $row['upr_prt_fetch'] == 1 ? "checked=\"checked\"" : "") . " type=\"checkbox\" /></label></td>";
									echo "<td style=\"padding:0px;\"><label style=\"padding:5px;display:block\"><input name=\"b[{$row['prt_id']}][3]\" " . ((int) $row['upr_prt_view'] == 1 ? "checked=\"checked\"" : "") . " type=\"checkbox\" /></label></td>";
									echo "</tr>";
								}
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</form>

	<?php } ?>
</div>


<script>
	$(function () {
		<?php if (isset($modify_user) && $modify_user->loaded) { ?>
			var ListObjectAccounts = $("#ListObjectAddAccount").slo({
				onselect: function (value) {
					if (Operations.accounts.status == 1) {
						return false;
					}
					// overlay.show();
					var $this = $(this);
					Operations.accounts.run();
					var $ajax = $.ajax({
						type: 'POST',
						url: '<?php echo $fs()->dir . "?modify-user={$modify_user->info->id}&token=" . session_id(); ?>',
						data: {
							"invoke": "add-account",
							"id": value.key,
							"relative": <?php echo $modify_user->info->id; ?>
						}
					}).done(function (data) {
						Operations.accounts.stop();
						if (data == "0") {
							messagesys.failure("Assigning account failed");
						} else if (data == "1") {
							messagesys.failure("Account is already assigned");
						} else {
							$("#FormAccountList").prepend(data);
						}
					}).fail(function (a, b, c) {
						messagesys.failure("Failed to execute operation");
					}).always(function () {
						// overlay.hide();
					});
				},
				limit: 6,
				align: "right"
			});


			var ListObjectAddCostCenter = $("#ListObjectAddCostCenter").slo({
				onselect: function (value) {
					if (Operations.costcenter.status == 1) {
						return false;
					}
					// overlay.show();
					var $this = $(this);
					Operations.costcenter.run();
					var $ajax = $.ajax({
						type: 'POST',
						url: '<?php echo $fs()->dir . "?modify-user={$modify_user->info->id}&token=" . session_id(); ?>',
						data: {
							"invoke": "add-costcenter",
							"id": value.key,
							"relative": <?php echo $modify_user->info->id; ?>
						}
					}).done(function (data) {
						Operations.costcenter.stop();
						if (data == "0") {
							messagesys.failure("Assigning cost center failed");
						} else if (data == "1") {
							messagesys.failure("Cost center is already assigned");
						} else {
							$("#FormCostCenterList").prepend(data);
						}
					}).fail(function (a, b, c) {
						messagesys.failure("Failed to execute operation");
					}).always(function () {
						// overlay.hide();
					});
				},
				limit: 3,
				align: "right"
			});

			var ListObjectCompanies = $("#ListObjectAddCompany").slo({
				onselect: function (value) {
					if (Operations.companies.status == 1) {
						return false;
					}
					// overlay.show();
					var $this = $(this);
					Operations.companies.run();
					var $ajax = $.ajax({
						type: 'POST',
						url: '<?php echo $fs()->dir . "?modify-user={$modify_user->info->id}&token=" . session_id(); ?>',
						data: {
							"invoke": "add-company",
							"id": value.key,
							"relative": <?php echo $modify_user->info->id; ?>
						}
					}).done(function (data) {
						Operations.companies.stop();
						if (data == "0") {
							messagesys.failure("Assigning company failed");
						} else if (data == "1") {
							messagesys.failure("Company is already assigned");
						} else {
							$("#FormCompanyList").prepend(data);
						}
					}).fail(function (a, b, c) {
						messagesys.failure("Failed to execute operation");
					}).always(function () {
						// overlay.hide();
					});
				},
				limit: 3,
				align: "right"
			});
			var Operations = {
				"accounts": {
					"status": 0,
					"dom": {
						"controls": [$("#FormAccountModifySubmitButton"), ListObjectAccounts],
						"form": $("#FormAccountModify")
					},
					"run": function () {
						if (this.status == 1) {
							return;
						}
						this.status = 1;
						for (var dom in this.dom.controls)
							this.dom.controls[dom].prop("disabled", true);
					},
					"stop": function () {
						if (this.status == 0) {
							return;
						}
						this.status = 0;
						for (var dom in this.dom.controls)
							this.dom.controls[dom].prop("disabled", false);
					}
				},
				"companies": {
					"status": 0,
					"dom": {
						"controls": [$("#FormCompanyModifySubmitButton"), ListObjectCompanies],
						"form": $("#FormCompanyModify")
					},
					"run": function () {
						if (this.status == 1) {
							return;
						}
						this.status = 1;
						for (var dom in this.dom.controls)
							this.dom.controls[dom].prop("disabled", true);
					},
					"stop": function () {
						if (this.status == 0) {
							return;
						}
						this.status = 0;
						for (var dom in this.dom.controls)
							this.dom.controls[dom].prop("disabled", false);
					}
				},
				"costcenter": {
					"status": 0,
					"dom": {
						"controls": [$("#FormCostCenterModifySubmitButton"), ListObjectCompanies],
						"form": $("#FormCostCenter")
					},
					"run": function () {
						if (this.status == 1) {
							return;
						}
						this.status = 1;
						for (var dom in this.dom.controls)
							this.dom.controls[dom].prop("disabled", true);
					},
					"stop": function () {
						if (this.status == 0) {
							return;
						}
						this.status = 0;
						for (var dom in this.dom.controls)
							this.dom.controls[dom].prop("disabled", false);
					}
				},
				"info": {
					"status": 0,
					"dom": {
						"controls": [$("#FormInfoModifySubmitButton")],
						"form": $("#FormInfoModify")
					},
					"run": function () {
						if (this.status == 1) {
							return;
						}
						this.status = 1;
						for (var dom in this.dom.controls)
							this.dom.controls[dom].prop("disabled", true);
					},
					"stop": function () {
						if (this.status == 0) {
							return;
						}
						this.status = 0;
						for (var dom in this.dom.controls)
							this.dom.controls[dom].prop("disabled", false);
					}
				}
			};

			$("#FormAccountList").on('click', 'tr > td.op-remove', function () {
				$(this).parent().remove();
			});
			$("#FormCompanyList").on('click', 'tr > td.op-remove', function () {
				$(this).parent().remove();
			});
			$("#FormCostCenter").on('click', 'tr > td.op-remove', function () {
				$(this).parent().remove();
			});

			$("#FormInfoModifySubmitButton").on('click', function () {
				if (Operations.info.status == 1) {
					return false;
				}
				var $this = $(this);
				overlay.show();
				Operations.info.run();
				var $ajax = $.ajax({
					type: 'POST',
					url: '<?php echo $fs()->dir . "?modify-user={$modify_user->info->id}&token=" . session_id(); ?>',
					data: Operations.info.dom.form.serialize()
				}).done(function (data) {
					if (data == "00") {
						messagesys.failure("Updating user information failed");
					} else if (data == "01") {
						messagesys.failure("Invalid Username");
					} else if (data == "02") {
						messagesys.failure("Invalid Password");
					} else if (data == "03") {
						messagesys.failure("Username already assigned with another user");
					} else if (data == "07") {
						messagesys.failure("Lowering permission will cause your account to lose access to the system, operaion failed");
					} else if (data == "10") {
						messagesys.success("User information updated successfully");
					} else {
						messagesys.failure("Uknown error");
					}
				}).fail(function (a, b, c) {
					messagesys.failure("Failed to execute operation");
				}).always(function () {
					overlay.hide();
				});
			});

			$("#FormAccountModifySubmitButton").on('click', function () {
				if (Operations.accounts.status == 1) {
					return false;
				}
				var $this = $(this);
				overlay.show();
				Operations.accounts.run();
				var $ajax = $.ajax({
					type: 'POST',
					url: '<?php echo $fs()->dir . "?modify-user={$modify_user->info->id}&token=" . session_id(); ?>',
					data: Operations.accounts.dom.form.serialize()
				}).done(function (data) {
					Operations.accounts.stop();
					if (data == "1")
						messagesys.success("Accounts modifications saved");
					else
						messagesys.failure("Accounts modifications saving failed");
				}).fail(function (a, b, c) {
					messagesys.failure("Failed to execute operation");
				}).always(function () {
					overlay.hide();
				});
			});
			$("#FormCompanyModifySubmitButton").on('click', function () {
				if (Operations.companies.status == 1) {
					return false;
				}
				var $this = $(this);
				overlay.show();
				Operations.companies.run();
				var $ajax = $.ajax({
					type: 'POST',
					url: '<?php echo $fs()->dir . "?modify-user={$modify_user->info->id}&token=" . session_id(); ?>',
					data: Operations.companies.dom.form.serialize()
				}).done(function (data) {
					Operations.companies.stop();
					if (data == "1")
						messagesys.success("Companies modifications saved");
					else
						messagesys.failure("Companies modifications saving failed");
				}).fail(function (a, b, c) {
					messagesys.failure("Failed to execute operation");
				}).always(function () {
					overlay.hide();
				});
			});
			$("#FormCostCenterModifySubmitButton").on('click', function () {
				if (Operations.costcenter.status == 1) {
					return false;
				}
				var $this = $(this);
				overlay.show();
				Operations.costcenter.run();
				var $ajax = $.ajax({
					type: 'POST',
					url: '<?php echo $fs()->dir . "?modify-user={$modify_user->info->id}&token=" . session_id(); ?>',
					data: Operations.costcenter.dom.form.serialize()
				}).done(function (data) {
					Operations.costcenter.stop();
					if (data == "1")
						messagesys.success("Cost center modifications saved");
					else
						messagesys.failure("Cost center modifications saving failed");
				}).fail(function (a, b, c) {
					messagesys.failure("Failed to execute operation");
				}).always(function () {
					overlay.hide();
				});
			});


			$("#FormAccountModify").on('submit', function (e) {
				e.preventDefault();
				return false;
			});
			$("#FormCostCenter").on('submit', function (e) {
				e.preventDefault();
				return false;
			});
			$("#FormCompanyModify").on('submit', function (e) {
				e.preventDefault();
				return false;
			});
			$("#ListObjectPerm").slo({
				limit: 10
			});

		<?php } ?>

		var userinput = $("#inpUserSelection").slo({
			onselect: function (value) {
				$("#ModifyUser").val(value.key);
				$("#frmUserSelection").submit();
			},
			ondeselect: function () {
				$("#UserDetailts").html("");
			},
			limit: 10
		});

		userinput.focus();


		$(document).on('submit', ".jQform", function (e) {
			e.preventDefault();
			var $this = $(this);
			overlay.show();
			var $ajax = $.ajax({
				type: 'GET',
				url: $this.attr("action"),
				data: $this.serialize()
			}).done(function (data) {
				var output;
				try {
					output = JSON.parse(data);
				} catch (e) {
					messagesys.failure("AJAX failed to parse input");
					return false;
				}
				if (output.type == "failur") {
					messagesys.failure(output.message);
				} else {
					messagesys.success(output.message);
				}
			}).fail(function (a, b, c) {
				messagesys.failure("Failed to execute operation");
			}).always(function () {
				overlay.hide();
			});
			return false;
		});
	});
</script>