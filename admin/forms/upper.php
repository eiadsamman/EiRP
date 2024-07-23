<?php
ob_start();
ob_implicit_flush(true);
$_v = !empty($app->settings->site['environment']) && $app->settings->site['environment'] === "development" ? uniqid() : $app->settings->site['version'];

use System\Finance\AccountRole;
use System\Personalization\Bookmark;
use System\SmartListObject;

$__workingaccount = false;
$SmartListObject  = new SmartListObject($app);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en" xml:lang="en">

<head>
	<meta charset="utf-8" />
	<base href="<?php echo "{$app->http_root}"; ?>" />
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, interactive-widget=overlays-content" />
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
	<meta name="apple-mobile-web-app-title" content="<?= $app->settings->site['title'] ?>" />
	<meta name="mobile-web-app-capable" content="yes" />
	<meta name="theme-color" content="#f0f0f5" />
	<meta name="description" content="CANDAS ERP System.">
	<link rel="shortcut icon" href="static/images/logo.svg" />
	<meta http-equiv="copyright" content="&copy; <?php echo date("Y") . " {$app->settings->site['auther']}"; ?>" />
	<meta http-equiv="author" content="<?php echo "{$app->settings->site['auther']}"; ?>" />
	<title><?= "{$app->settings->site['title']} - {$fs()->title}" ?></title>

	<link media="screen,print" rel="stylesheet" href="static/style/theme/default.css?rev=<?= $_v ?>" />
	<link media="screen,print" rel="stylesheet" href="static/style/base.css?rev=<?= $_v ?>" />
	<link media="screen,print" rel="stylesheet" href="static/style/modals.css?rev=<?= $_v ?>" />
	<link media="screen,print" rel="stylesheet" href="static/style/gremium.css?rev=<?= $_v ?>" />
	<link media="screen,print" rel="stylesheet" href="static/style/buttons.css?rev=<?= $_v ?>" />

	<script type="text/javascript" src="static/jquery/jquery.min-3.7.1.js"></script>
	<script type="text/javascript" src="static/jquery/jquery-ui.min.js?rev=<?= $_v ?>"></script>
	<script type="text/javascript" src="static/jquery/gui.menus-3.5.js?rev=<?= $_v ?>"></script>
	<script type="text/javascript" src="static/jquery/gui.modals-1.4.js?rev=<?= $_v ?>"></script>
	<script type="text/javascript" src="static/jquery/slo-1.4.js?rev=<?= $_v ?>"></script>

<script type="module">
		import { Application, default as App } from "./static/javascript/modules/app.js";
		import Account from "./static/javascript/modules/finance/account.js";
		import Currency from "./static/javascript/modules/finance/currency.js";
		App.ID = '<?= $app->id . $_v; ?>';
		App.Title = '<?= $app->settings->site['title']; ?>';
		App.BaseURL = '<?php echo "{$app->http_root}"; ?>';
		App.Instance = new Application(App.ID);
		App.Instance.page['id'] = <?= $fs()->id ?>;
		App.Instance.page['dir'] = "<?= $fs()->dir ?>";
		<?php if ($app->user->logged) {
			echo "App.User.id = {$app->user->info->id};";
			echo "App.User.photo = {$app->user->info->photoid};";
			echo "App.User.initials = \"" . mb_substr($app->user->info->firstname, 0, 1) . " " . mb_substr($app->user->info->lastname, 0, 1) . "\";";
			echo "App.Account = new Account(";
			echo ($app->user->company ? $app->user->company->id : "null") . ',';
			if ($app->user->account) {
				echo $app->user->account->id . ',';
				echo '"' . $app->user->account->name . '"' . ',';
				echo "new Currency(";
				echo $app->user->account->currency->id . ',';
				echo '"' . $app->user->account->currency->name . '",';
				echo '"' . $app->user->account->currency->shortname . '",';
				echo '"' . $app->user->account->currency->symbol . '",';
				echo "),";
				echo "\"\",";
				echo "\"{$app->user->account->type->keyTerm->toString()}\"";
			} else {
				echo "0,null,null";
			}
			echo ");";
		} ?>
	</script>
	<?php

	if ($app->view != null) {
		$app->view->htmlAssets("?rev=$_v");
	}


	echo "\n";
	if (array_key_exists('css', $fs()->cdns)) {
		$load = explode(";", $fs()->cdns['css']);
		foreach ($load as $file) {
			if (trim($file) != "")
				echo "\t<link media=\"screen,print\" rel=\"stylesheet\" href=\"static/{$file}?rev={$_v}\" />\n";
		}
	}
	if (array_key_exists('js', $fs()->cdns)) {
		$load = explode(";", $fs()->cdns['js']);
		foreach ($load as $file) {
			if (trim($file) != "")
				echo "\t<script type=\"text/javascript\" src=\"static/{$file}?rev={$_v}\"></script>\n";
		}
	}
	?>
</head>
<?php ob_end_flush(); ?>

<body class="theme-default <?= isset($themeDarkMode) ? $themeDarkMode->mode : ""; ?>"
	data-mode="<?= isset($themeDarkMode) ? $themeDarkMode->mode : ""; ?>">
	<a href="" id="PFTrigger" style="display: none;"></a>
	<?php if ($app->user->logged && !$fs()->permission->deny) { ?>
		<span class="header-ribbon noprint">
			<div>
				<div class="btnheader-set" style="white-space:nowrap">
					<?php
					if ($app->user->logged && $app->user->company && $app->user->company->logo) {
						echo "<a href=\"\" tabindex=\"-1\" id=\"header-menu-home\" style=\"padding:9px 8px 8px 8px\" title=\"Company `{$app->user->company->name}`\"><span><img alt=\"{$app->user->company->name} Logo\" src=\"download/?id={$app->user->company->logo}&pr=t\" height=\"30\" /></span></a>";
					} else {
						echo "<a href=\"\" tabindex=\"-1\" title=\"Homepage\" id=\"header-menu-home\" class=\"ico-home\"><span></span></a>";
					}
					if ($app->user->logged) {
						echo "<a id=\"header-menu-button\" title=\"{$fs()->id}: {$fs()->title}, (Ctrl+m)\" href=\"{$fs()->dir}\"><span style=\"font-family:icomoon4;\">&#xe9bd;</span></a>";
						echo "<span class=\"gap\" style=\"text-align:right;\"></span>";
						echo "<a href=\"{$fs()->dir}/?--sys_sel-change=company\" tabindex=\"-1\" title=\"Running Company\" id=\"jqroot_com\">" . ($app->user->company ? $app->user->company->name : "N/A") . "</a>";
						echo "<a href=\"{$fs()->dir}/?--sys_sel-change=account\" tabindex=\"-1\" title=\"Running Account\" id=\"jqroot_sec\">" . (isset($app->user->account) ? "<span class=\"mediabond-hide\">{$app->user->account->type->name}: </span>{$app->user->account->name}" : "N/A") . "</a>";
						if ($app->user->account && $app->user->account->role->view) {
							echo "<span id=\"jqroot_bal\">" . ($app->user->account->balance < 0 ? "(" . number_format(abs($app->user->account->balance), 2, ".", ",") . ")" : number_format(abs($app->user->account->balance), 2, ".", ","));
							echo " {$app->user->account->currency->shortname}</span>";
						} elseif ($app->user->account) {
							echo "<span>{$app->user->account->currency->shortname}</span>";
						}
						echo "<a tabindex=\"-1\" class=\"mediabond-hide toggleLightMode\" href=\"{$fs()->dir}/\" title=\"Toggle Dark Mode\"><span style=\"font-family:icomoon4;\">&#xe9d4;</span></a>";
						echo "<a href=\"{$fs(27)->dir}/\" tabindex=\"-1\" id=\"header-menu-useraccount-button\" title=\"User Settings\"><span style=\"font-family:icomoon4;\">&#xe971;</span></a>"; //<cite>1</cite>
						echo "<a href=\"/?logout=" . uniqid() . "\" tabindex=\"-1\" id=\"header-menu-logout\" title=\"Logout\"><span style=\"font-family:icomoon4;\">&#xe9b6;</span></a>";
					}
					?>
				</div>
			</div>
		</span>
		<span id="header-menu" class="header-menu lefthand">
			<div>
				<div>
					<header>
						<span class="btn-set"><input type="text" class="flex" id="PFSelector" data-slo=":LIST"
								data-source="_/MenuListItems/slo/<?= md5("#Fg32-32-f-" . ($app->user->info->id)); ?>/slo_listitems.a"
								placeholder="Goto page..." /></span>
					</header>
					<div style="white-space:nowrap;" class="menu-items"
						data-chunk_source="_/MenuListItems/html/<?= md5("#Fg32-32-f-" . ($app->user->info->id)); ?>/menu_listitems.a"
						data-content_type="html"></div>
				</div>
			</div>
		</span>

		<span id="account-menu" class="header-menu righthand">
			<div>
				<div>
					<header>
						<span class="btn-set">
							<input type="text" class="flex" id="account-menu-slo" data-url="<?= $fs()->dir ?>" data-list="accounts-list" data-slo=":LIST"
								placeholder="Select an account...">
						</span>
						<datalist id="accounts-list">
							<?php
							if ($app->user->company) {
								$role         = new AccountRole();
								$role->access = true;
								echo $SmartListObject->userAccounts($role, $app->user->company->id);
								unset($role);
							}
							?>
						</datalist>
					</header>
					<div style="white-space:nowrap;" class="menu-items" id="menu-account-selection">
						<?php
						if (!$app->user->company) {
							echo "<div style=\"padding-left:15px;\">No company selected</div>";
						} else {
							$ptp = array();
							if (
								$r = $app->db->query(
									"SELECT 
									prt_id,prt_name,ptp_name,cur_shortname,_fusro.comp_name,typetermPair
								FROM 
									acc_accounts
									JOIN user_partition ON upr_prt_id = prt_id AND upr_usr_id = {$app->user->info->id} AND upr_prt_fetch = 1
									
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
												JOIN user_company ON urc_usr_comp_id=comp_id AND urc_usr_id = {$app->user->info->id}
												JOIN user_settings ON usrset_usr_id = {$app->user->info->id} AND usrset_type = " . \System\Personalization\Identifiers::SystemWorkingCompany->value . " AND usrset_usr_defind_name='UNIQUE' AND usrset_value=comp_id
									) AS _fusro ON _fusro.comp_id=prt_company_id
									
									LEFT JOIN user_settings ON usrset_usr_defind_name=prt_id AND usrset_usr_id = {$app->user->info->id} AND usrset_type = " . \System\Personalization\Identifiers::SystemCountAccountSelection->value . "
								ORDER BY
									(usrset_value + 0) DESC,cur_id,ptp_name,prt_name
								;"
								)
							) {
								while ($row = $r->fetch_assoc()) {
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
										echo "<a href=\"{$fs()->dir}/?--sys_sel-change=account_commit&i={$account_v[0]}\" data-account_id=\"{$account_v[0]}\" title=\"`{$account_v[1]}` Account\"><span>{$account_v[1]}</span><b>" . (is_null($account_v[2]) ? "-" : $account_v[2]) . "</b></a>";
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
							<input type="text" class="flex" id="company-menu-slo" data-url="<?= $fs()->dir ?>" data-slo=":LIST" data-list="company-list"
								placeholder="Select a company...">
						</span>
						<datalist id="company-list">
							<?= $SmartListObject->userCompanies(); ?>
						</datalist>
					</header>
					<div style="white-space:nowrap;" class="menu-items" id="menu-company-selection">
						<?php
						$r = $app->db->query(
							"SELECT 
							comp_id,comp_name 
						FROM companies 
							JOIN user_company ON urc_usr_comp_id = comp_id AND urc_usr_id = " . $app->user->info->id . "
							LEFT JOIN user_settings ON usrset_usr_defind_name=comp_id AND usrset_usr_id=" . $app->user->info->id . " AND usrset_type = " . \System\Personalization\Identifiers::SystemCountCompanySelection->value . "
						ORDER BY
							(usrset_value+0) DESC"
						);
						if ($r) {
							while ($row = $r->fetch_assoc()) {
								printf('<a href="%1$s/?--sys_sel-change=company_commit&i=%2$d" data-company_id="%2$d" title="`%3$s` Company"><span>%3$s</span></a>', $fs()->dir, (int) $row['comp_id'], $row['comp_name']);
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
						$bookmark   = new Bookmark($app);
						$bookmarked = $bookmark->isBookmarked($fs()->id);

						echo "<div>{$app->user->info->fullName()}</div>";
						echo "<a href=\"{$fs(27)->dir}/\"><span style=\"font-family:icomoon4;flex:0 1 auto;min-width:30px\" title=\"Preferences & Security\">&#xe971;</span><span>Preferences & Security</span></a>";
						echo "<a href=\"{$fs()->dir}/\" class=\"toggleLightMode\"><span style=\"font-family:icomoon4;flex:0 1 auto;min-width:30px\" title=\"Toggle Dark Mode\">&#xe9d4;</span><span>Toggle Dark Mode</span></a>";
						echo "<a href=\"{$fs(263)->dir}/\"><span style=\"font-family:icomoon4;flex:0 1 auto;min-width:30px\" title=\"Bookmarks\">&#xe9d9;</span><span>Bookmarks</span></a>"; //e9d7
						echo "<a href=\"{$fs(17)->dir}/\"><span style=\"font-family:icomoon4;flex:0 1 auto;min-width:30px\" title=\"Settings\">&#xe994;</span><span>Settings</span></a>";
						echo "<a href=\"{$fs()->dir}/?logout=" . uniqid() . "\"><span style=\"font-family:icomoon4;flex:0 1 auto;min-width:30px\" title=\"Logout\">&#xe9b6;</span><span>Logout</span></a>";
						echo "<div><span class=\"btn-set \"><span class=\"flex\" style=\"padding:10px 0px 0px 0px;background:none;border:none\">Bookmarks</span>";
						if (!$bookmarked) {
							echo "<button type=\"button\" class=\"edge-left\" title=\"Add this page to bookmarks\" data-target_id=\"{$fs()->id}\" data-bookmark_title=\"{$fs()->title}\" data-role=\"add\" id=\"bookmark-button\">Add</button>";
						} else {
							echo "<button type=\"button\" class=\"edge-left\" title=\"Remove this page from bookmarks\" data-target_id=\"{$fs()->id}\" data-role=\"remove\" id=\"bookmark-button\">Remove</button>";
						}
						echo "</span></div>";

						foreach ($bookmark->list() as $bookmark) {
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

		<article>
			<div id="body-content">

			<?php } ?>