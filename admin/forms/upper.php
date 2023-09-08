<?php

use System\System;
use Finance\Accounting;
use System\SLO_DataList;


$__helper = false;
$__side_panel = false;
if (isset($fs->use()->parameters) && preg_match("/help([0-9]+)/", $fs->use()->parameters, $match)) {
	$__helper = $tables->pagefile_info((int) $match[1]);
}
if (isset($fs->use()->parameters) && preg_match("/side-panel([0-9]+)/", $fs->use()->parameters, $match)) {
	$__side_panel = $tables->pagefile_info((int) $match[1]);
	$__side_panel__perm = new AllowedActions(System::$_user->info->permissions, $__side_panel['permissions']);
}


$__workingaccount = false;

if (System::$_user->account && System::$_user->account->id) {
	include_once("admin/class/accounting.php");
	$accounting = new Accounting();
	$__workingaccount = $accounting->account_information(System::$_user->account->id);
}

include_once("admin/class/slo_datalist.php");
$slo_datalist = new SLO_DataList();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="<?php echo $Languages->get_current()['dir']; ?>" lang="<?php echo $Languages->get_current()['symbol']; ?>" xml:lang="<?php echo $Languages->get_current()['symbol']; ?>">

<head>
	<meta charset="utf-8" />
	<base href="<?php echo "{$_SERVER['HTTP_SYSTEM_ROOT']}"; ?>" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, interactive-widget=overlays-content" />
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
	<meta name="apple-mobile-web-app-title" content="<?php echo "{$c__settings['site']['title']}"; ?>" />
	<meta name="mobile-web-app-capable" content="yes" />
	<meta name="theme-color" content="#f0f0f5" />
	<link rel="shortcut icon" href="static/images/logo.ico" />
	<meta http-equiv="copyright" content="&copy; <?php echo date("Y") . " {$c__settings['site']['auther']}"; ?>" />
	<meta http-equiv="author" content="<?php echo "{$c__settings['site']['auther']}"; ?>" />
	<title>
		<?php echo "{$c__settings['site']['title']} - " . $fs->use()->title; ?>
	</title>
	<link media="screen,print" rel="stylesheet" href="static/style/style.main.css" />
	<link media="screen,print" rel="stylesheet" href="static/style/style.messagesys.css" />
	<link media="screen,print" rel="stylesheet" href="static/style/style.button.set.css" />
	<link media="screen,print" rel="stylesheet" href="static/style/style.slo.css" />
	<link media="screen,print" rel="stylesheet" href="static/style/style.bom-table.css" />
	<link media="screen,print" rel="stylesheet" href="static/style/style.checkbox.css" />
	<link media="screen,print" rel="stylesheet" href="static/style/style.popup.css" />
	<link media="screen,print" rel="stylesheet" href="static/style/style.ios-checkbox.css" />
	<link media="screen,print" rel="stylesheet" href="static/style/style.template.css" />
	<?php
	if (!is_null($Languages->get_current()['css'])) {
		$langcss = explode(":", $Languages->get_current()['css']);
		foreach ($langcss as $css) {
			echo "	<link media=\"screen,print\" rel=\"stylesheet\" href=\"static/{$css}\" />\n";
		}
	}
	if (!is_null($pageinfo['css'])) {
		$load = explode(":", $pageinfo['css']);
		foreach ($load as $file) {
			if (trim($file) != "") {
				echo "	<link media=\"screen,print\" rel=\"stylesheet\" href=\"static/{$file}\" />\n";
			}
		}
	}
	if (!is_null($fs->use()->parameters)) {
		$params = explode(":", $fs->use()->parameters);
		foreach ($params as $param) {
		}
	}
	?>
	<script type="text/javascript" src="static/jquery/jquery.min.js"></script>
	<script type="text/javascript" src="static/jquery/jquery-ui.min.js"></script>
	<script type="text/javascript" src="static/jquery/ease.js"></script>
	<script type="text/javascript" src="static/jquery/jquery.ui.js"></script>
	<script type="text/javascript" src="static/jquery/slo-1.1.js?rev=230905"></script>
	<script type="text/javascript" src="static/jquery/msgsys-1.0.js"></script>
	<script type="text/javascript" src="static/jquery/popup-1.0.js"></script>
	<script type="text/javascript" src="static/jquery/msgoverlay1.0.js"></script>
	<script type="text/javascript" src="static/jquery/serialize.js"></script>
	<?php if ($__side_panel && $__side_panel__perm->deny == false) {
		echo '<script type="text/javascript" src="static/javascript/template.sidepanel.js"></script>';
	} ?>
	<?php
	if (!is_null($pageinfo['js'])) {
		$load = explode(":", $pageinfo['js']);
		foreach ($load as $file) {
			if (trim($file) != "") {
				echo "	<script type=\"text/javascript\" src=\"static/{$file}\"></script>\n";
			}
		}
	}
	?>
</head>

<body>
	<span class="header-ribbon noprint">
		<div>
			<div class="btnheader-set header-nav" style="white-space:nowrap">
				<?php
				if (System::$_user->logged && System::$_user->company && System::$_user->company->logo) {
					echo "<a href=\"\" tabindex=\"-1\" title=\"Main Page\" id=\"header-menu-home\" style=\"padding:9px 8px 8px 8px\"><span><img src=\"download/?id=" . System::$_user->company->logo . "&pr=t\" height=\"30\" /></span></a>";
				} else {
					echo "<a href=\"\" tabindex=\"-1\" title=\"Main Page\" id=\"header-menu-home\" class=\"ico-home\"><span></span></a>";
				}
				if (System::$_user->logged) {
					echo "<a id=\"header-menu-button\" title=\"{$fs->use()->id}: {$fs->use()->title}, (Ctrl+m)\"><span style=\"font-family:icomoon4;\">&#xe9bd;</span></a>";
					if ($__helper) {
						echo "<a href=\"{$__helper['directory']}\" title=\"Help\" id=\"jqroot_help\" target=\"_blank\"><span style=\"font-family:icomoon4;\">&#xea09;</span></a>";
					}

					echo "<span class=\"gap\" style=\"text-align:right;\"></span>";
					echo "<a href=\"{$fs->use()->dir}/?--sys_sel-change=company\" tabindex=\"-1\" title=\"Running Company\" id=\"jqroot_com\">" . (System::$_user->company->name ? System::$_user->company->name : "N/A") . "</a>";
					echo "<a href=\"{$fs->use()->dir}/?--sys_sel-change=account\" tabindex=\"-1\" title=\"Running Account\" id=\"jqroot_sec\">" . (isset($__workingaccount['name']) ? "<span id=\"jqroot_accgrp\">" . $__workingaccount['group'] . ": </span>" . $__workingaccount['name'] : "N/A") . "</a>";
					if ($__workingaccount && $__workingaccount['balance'] != false) {
						echo "<span id=\"jqroot_bal\">" . ($__workingaccount['balance'] < 0 ? "(" . number_format(abs($__workingaccount['balance']), 2, ".", ",") . ")" : number_format(abs($__workingaccount['balance']), 2, ".", ","));
						echo " {$__workingaccount['currency']['shortname']}</span>";
					} else {
						echo "<span>{$__workingaccount['currency']['shortname']}</span>";
					}
					echo "<a href=\"user-account/\" tabindex=\"-1\" id=\"header-menu-useraccount-button\"><span style=\"font-family:icomoon4;\" title=\"User Settings\">&#xe971;</span></a>";
					echo "<a href=\"{$fs->use()->dir}/?logout\" tabindex=\"-1\" id=\"header-menu-logout\"><span style=\"font-family:icomoon4;\" title=\"Logout\">&#xe9b6;</span></a>";
				}
				?>
			</div>
		</div>
	</span>
	<a href="" id="PFTrigger" style="display: none;"></a>

	<?php if (System::$_user->logged) { ?>
		<span id="header-menu" class="header-menu lefthand">
			<div>
				<div>
					<header>
						<span class="btn-set">
							<input type="text" class="flex" id="PFSelector" data-slo=":LIST" data-list="PFSelectorList" />
							<datalist id="PFSelectorList" style="display: none;">
								<?php
								$q = "SELECT 
									trd_directory, CONCAT(trd_id,': ', pfl_value) AS pagefile_title, trd_id
								FROM 
									pagefile 
									JOIN pagefile_language ON pfl_trd_id=trd_id AND pfl_lng_id=1 
									JOIN 
										pagefile_permissions ON pfp_trd_id=trd_id AND pfp_per_id=" . System::$_user->info->permissions . "
											LEFT JOIN user_settings ON usrset_usr_defind_name=trd_id AND usrset_usr_id=" . System::$_user->info->id . " AND usrset_name='system_count_page_visit'	
								WHERE 
									trd_enable = 1 AND trd_visible = 1
								ORDER BY
									(usrset_value+0) DESC,pfl_value
								";

								if ($r = System::$sql->query($q)) {
									while ($row = System::$sql->fetch_assoc($r)) {
										echo "<option data-id=\"{$row['trd_directory']}\" data-keywords=\"{$row['trd_id']}\">{$row['pagefile_title']}</option>";
									}
								}
								?>
							</datalist>
						</span>
					</header>
					<div style="white-space:nowrap;" class="menu-items">
						<?php
						echo "<b class=\"index-link\"><span style=\"color:#333;font-family:icomoon;\">&#xe600;</span><a class=\"alink\" href=\"\">Homepage</a></b>";
						/*Ploting template @ class.tables.php */
						new PagefileHierarchy($sql, System::$_user->info->permissions); ?>
					</div>
				</div>
			</div>
		</span>


		<span id="account-menu" class="header-menu righthand">
			<div>
				<div>
					<header>
						<span class="btn-set">
							<input type="text" class="flex" id="account-menu-slo" data-url="<?= $fs->use()->dir ?>" data-list="accounts-list" data-slo=":LIST">
						</span>
						<datalist id="accounts-list">
							<?= $slo_datalist->financial_company_accounts($inbound = null, $outbound = null, $accessible = true, $viewable = null) ?>
						</datalist>
					</header>
					<div style="white-space:nowrap;" class="menu-items">
						<?php
						if (!System::$_user->company) {
							echo "<div style=\"padding-left:15px;\">No company selected</div>";
						} else {
							$ptp = array();
							if (
								$r = $sql->query(
									"SELECT 
									prt_id,prt_name,ptp_name,cur_shortname,_fusro.comp_name,typetermPair
								FROM 
									acc_accounts
									JOIN user_partition ON upr_prt_id=prt_id AND upr_usr_id=" . System::$_user->info->id . " AND upr_prt_fetch=1
									
									JOIN (
										SELECT ptp_id,ptp_name,trmgrp_name, CONCAT_WS(': ', trmgrp_name, ptp_name) AS typetermPair
										FROM acc_accounttype LEFT JOIN acc_termgroup ON trmgrp_id = ptp_termgroup_id
									) AS _account_type ON prt_type = _account_type.ptp_id 
									
									JOIN currencies ON cur_id = prt_currency
									JOIN (
										SELECT
											comp_name,comp_id
										FROM
											companies
												JOIN user_company ON urc_usr_comp_id=comp_id AND urc_usr_id=" . System::$_user->info->id . "
												JOIN user_settings ON usrset_usr_id=" . System::$_user->info->id . " AND usrset_name='system_working_company' AND usrset_usr_defind_name='UNIQUE' AND usrset_value=comp_id
									) AS _fusro ON _fusro.comp_id=prt_company_id
									
									LEFT JOIN user_settings ON usrset_usr_defind_name=prt_id AND usrset_usr_id=" . System::$_user->info->id . " AND usrset_name='system_count_account_selection'
								ORDER BY
									(usrset_value + 0) DESC,cur_id,ptp_name,prt_name
								;"
								)
							) {
								while ($row = $sql->fetch_assoc($r)) {
									if (!isset($ptp[$row['comp_name']])) {
										$ptp[$row['comp_name']] = array();
									}
									if (!isset($ptp[$row['comp_name']][$row['typetermPair']])) {
										$ptp[$row['comp_name']][$row['typetermPair']] = array();
									}
									$ptp[$row['comp_name']][$row['typetermPair']][] = array($row['prt_id'], $row['prt_name'], $row['cur_shortname']);
								}
							}

							$firstrow = null;
							foreach ($ptp as $company_k => $company_v) {
								foreach ($company_v as $group_k => $group_v) {
									echo "<div>$group_k</div>";
									foreach ($group_v as $account_k => $account_v) {
										echo "<a href=\"{$fs->use()->dir}/?--sys_sel-change=account_commit&i={$account_v[0]}\"><span>{$account_v[1]}</span><b>" . (is_null($account_v[2]) ? "-" : $account_v[2]) . "</b></a>";
									}
								}
							}
						} ?>
					</div>
				</div>
			</div>
		</span>

		<span id="company-menu" class="header-menu righthand">
			<div>
				<div>
					<header>
						<span class="btn-set">
							<input type="text" class="flex" id="company-menu-slo" data-url="<?= $fs->use()->dir ?>" data-slo="COMPANY_USER">
						</span>
					</header>
					<div style="white-space:nowrap;" class="menu-items">
						<?php
						$q = $sql->query(
							"SELECT 
							comp_id,comp_name 
						FROM companies 
							JOIN user_company ON urc_usr_comp_id = comp_id AND urc_usr_id = " . System::$_user->info->id . "
							LEFT JOIN user_settings ON usrset_usr_defind_name=comp_id AND usrset_usr_id=" . System::$_user->info->id . " AND usrset_name='system_count_company_selection'
						ORDER BY
							(usrset_value+0) DESC"
						);
						if ($q) {
							while ($row = $sql->fetch_assoc($q)) {
								printf("<a href=\"%s/?--sys_sel-change=company_commit&i=%d\"><span>%s</span></a>", $fs->use()->dir, (int) $row['comp_id'], $row['comp_name']);
							}
						}
						?>
					</div>
				</div>
			</div>
		</span>

		<span id="user-menu" class="header-menu righthand">
			<div>
				<div>
					<div style="white-space:nowrap;" class="menu-items">
						<?php
						$bookmarked = System::bookmarksStatus($fs->use()->id);

						echo "<div>" . System::$_user->info->name . "</div>";
						echo "<a href=\"user-account/\"><span style=\"font-family:icomoon4;flex:0 1 auto;min-width:30px\" title=\"User Settings\">&#xe971;</span><span>Password & Security</span></a>";
						echo "<a href=\"{$fs->find(263)->dir}\"><span style=\"font-family:icomoon4;flex:0 1 auto;min-width:30px\" title=\"User Settings\">&#xe971;</span><span>Bookmarks management</span></a>";
						echo "<a href=\"{$fs->use()->dir}/?logout\"><span style=\"font-family:icomoon4;flex:0 1 auto;min-width:30px\" title=\"Logout\">&#xe9b6;</span><span>Logout</span></a>";
						echo "<div><span class=\"btn-set \"><span class=\"flex\" style=\"padding:10px 0px 0px 0px;background:none;border:none\">Bookmarks</span>";
						if (!$bookmarked) {
							echo "<button type=\"button\" title=\"Add this page to bookmarks\" data-target_id=\"{$fs->use()->id}\" data-bookmark_title=\"{$fs->use()->title}\" data-role=\"add\" id=\"bookmark-button\">Add</button>";
						} else {
							echo "<button type=\"button\" title=\"Remove this page from bookmarks\" data-target_id=\"{$fs->use()->id}\" data-role=\"remove\" id=\"bookmark-button\">Remove</button>";
						}
						echo "</span></div>";

						foreach (System::bookmarksList() as $bookmark) {
							#sticky
							#slo search
							#add/remove from dom
							//color:#{$bookmark['trd_attrib5']}
							echo "<a href=\"{$bookmark['trd_directory']}/\">
						<span style=\"font-family:icomoon4;flex:0 1 auto;min-width:30px;\" title=\"{$bookmark['pfl_value']}\">&#xe{$bookmark['trd_attrib4']};</span><span>{$bookmark['pfl_value']}</span></a>";
						}

						?>
					</div>
				</div>
			</div>
		</span>

	<?php } ?>
	<div>
		<div>
			<div>
				<div>
					<?php if ($__side_panel && $__side_panel__perm->deny == false && is_file("website-contents/" . $__side_panel['id'] . ".php")) { ?>
						<span style="position: absolute;right:0px;top:45px;width:310px">
							<span style="position: fixed;display: block;z-index: 999;font-size: 0.7em;padding-left: 4px;color:#ccc">
								<?= $__side_panel['id']; ?>
							</span>
							<span id="template-sidePanel" data-template_url="<?php echo $tables->pagefile_info($__side_panel['id'], null, "directory"); ?>">
								<div>
									<?php include_once("website-contents/" . $__side_panel['id'] . ".php"); ?>
								</div>
							</span>
						</span>
					<?php } ?>
					<div <?php echo ($__side_panel && $__side_panel__perm->deny == false) ?
								"class=\"template-enableSidePanel\"" : ""; ?> style="padding:15px;padding-top:
						<?php echo (isset($fs->use()->parameters) && strpos($fs->use()->parameters, "no-padding") !== false) ? "40px;" : "59px;"; ?>;" id="body-content">