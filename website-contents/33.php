<?php
use System\Template\Gremium;

$grem = new Gremium\Gremium(true, false);
$grem->header()->prev("href=\"{$fs()->dir}\"")->serve("<h1>My Accounts</h1>");
$grem->menu()->serve("<span>Search</span><input type=\"text\" class=\"flex\" id=\"sectorslo\" data-list=\"accounts-list\" data-slo=\":LIST\">");


$article = $grem->article()->open();

?>
<?php if (!$app->user->company) { ?>
	No company selected
<?php } else { ?>

	<?php
	$accountfound = false;
	$ptp = array();
	if (
		$r = $app->db->query(
			"SELECT prt_id, prt_name,prt_type, cur_shortname, comp_name
				FROM view_financial_accounts JOIN user_partition ON upr_prt_id = prt_id AND upr_usr_id={$app->user->info->id} AND upr_prt_fetch = 1
				WHERE comp_id = {$app->user->company->id}
				ORDER BY comp_name, cur_id, prt_name;"
		)
	) {
		while ($row = $r->fetch_assoc()) {
			$accountfound = true;
			if (!isset($ptp[$row['comp_name']])) {
				$ptp[$row['comp_name']] = array();
			}
			if (!isset($ptp[$row['comp_name']][$row['prt_type']])) {
				$ptp[$row['comp_name']][$row['prt_type']] = array();
			}
			$ptp[$row['comp_name']][$row['prt_type']][] = array($row['prt_id'], $row['prt_name'], $row['cur_shortname']);
		}
	}
	echo "<table id=\"screenSectorCol\"><tbody>";
	foreach ($ptp as $company_k => $company_v) {
		foreach ($company_v as $group_k => $group_v) {
			echo "<tr>";
			echo "<td>$group_k</td><td class=\"sector-select-list\">";
			foreach ($group_v as $account_k => $account_v) {
				echo "<div><div class=\"btn-set\" style=\"margin:3px 0px;\">";
				echo "<span>" . (is_null($account_v[2]) ? "-" : $account_v[2]) . "</span><a href=\"{$fs()->dir}/?--sys_sel-change=account_commit&i={$account_v[0]}\" style=\"\">{$account_v[1]}</a>";
				echo "</div></div>";
			}
			echo "</td>";
			echo "</tr>";
		}
	}
	echo "</tbody></table>";

	?>
	<?php
	if (!$accountfound) {
		echo "<tr><td>No accounts assigned to this company</td></tr>";
	}
?>
<?php } ?>

<?php
$article->close();
unset($grem);
?>
<a href="" id="triggerselector"></a>
<script>
	$(document).ready(function (e) {
		$("#sectorslo").slo({
			onselect: function (data) {
				$("#triggerselector").attr("href", "<?php echo $fs()->dir; ?>/?--sys_sel-change=account_commit&i=" + data.key);
				$("#triggerselector")[0].click();
			}
		}).focus();
	});
</script>