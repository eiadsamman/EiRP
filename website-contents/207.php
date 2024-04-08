<?php
use System\Template\Gremium;

$grem = new Gremium\Gremium(true, false);
$grem->header()->prev($fs()->dir)->serve("<h1>My Companies</h1>");
$grem->menu()->serve("<span>Search</span><input id=\"compslo\" type=\"text\" class=\"flex\" data-slo=\"COMPANY_USER\" />");
$article = $grem->article()->open();
?>

<div class="btn-set" style="flex-wrap: wrap;">
	<?php
	$r = $app->db->query("SELECT comp_id,comp_name FROM companies JOIN user_company ON urc_usr_comp_id = comp_id AND urc_usr_id = {$app->user->info->id}");
	if ($q) {
		while ($row = $r->fetch_assoc()) {
			printf("<a href=\"%s/?--sys_sel-change=company_commit&i=%d\" style=\"height: 60px;width: 150px;margin-bottom:10px;\">
										<span style=\"display: table-cell;vertical-align: middle;height: 43px;white-space: normal;color:#333;text-align:center;width:130px;\">%s</span>
										</a>", $fs()->dir, (int) $row['comp_id'], $row['comp_name']);
		}
	}
	?>
</div>

<?php
$article->close();
unset($grem);
?>
<a href="" id="triggerselector"></a>
<script>
	$(document).ready(function (e) {
		$("#compslo").slo({
			onselect: function (data) {
				$("#triggerselector").attr("href", "<?php echo $fs()->dir; ?>/?--sys_sel-change=company_commit&i=" + data.key);
				$("#triggerselector")[0].click();
			}
		}).focus();
	});
</script>