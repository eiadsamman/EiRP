<?php if (!$USER->company) { ?>

	<div style="width:100%;text-align:center;margin-top:20px">
		<div id="screenSector">
			<table class="bom-table">
				<thead>
					<tr class="special">
						<td>
							<h2><b>Account selecting</b></h2>
						</td>
					</tr>
				</thead>
				<tbody>
					<tr class="special alert">
						<td>No company selected</td>
					</tr>
					<tr>
						<td class="btn-set" style="justify-content:center"><a href="<?php echo $fs()->dir; ?>/">Return to `<?php echo $fs()->title; ?>`</a></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

<?php } else { ?>

	<form action="<?php echo $fs()->dir; ?>" method="post">
		<div style="width:100%;text-align:center;margin-top:20px">
			<div id="screenSector">
				<table class="bom-table">
					<thead>
						<tr class="special">
							<td>
								<h2>Select Account</h2>
							</td>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td align="left" style="text-align:left" valign="top">
								<?php
								$accountfound = false;
								$ptp = array();
								if ($r = $app->db->query("
				SELECT 
					prt_id,prt_name,ptp_name,cur_shortname,_fusro.comp_name
				FROM 
					`acc_accounts` 
						JOIN user_partition ON upr_prt_id=prt_id AND upr_usr_id={$USER->info->id} AND upr_prt_fetch=1
						JOIN `acc_accounttype` ON ptp_id=prt_type
						JOIN currencies ON cur_id = prt_currency
						JOIN (
							SELECT
								comp_name,comp_id
							FROM
								companies
									JOIN user_company ON urc_usr_comp_id=comp_id AND urc_usr_id={$USER->info->id}
									JOIN user_settings ON usrset_usr_id={$USER->info->id} AND usrset_name='system_working_company' AND usrset_usr_defind_name='UNIQUE' AND usrset_value=comp_id
						) AS _fusro ON _fusro.comp_id=prt_company_id
						
				ORDER BY
					comp_name,cur_id,ptp_name,prt_name
				;")) {
									while ($row = $r->fetch_assoc()) {
										$accountfound = true;
										if (!isset($ptp[$row['comp_name']])) {
											$ptp[$row['comp_name']] = array();
										}
										if (!isset($ptp[$row['comp_name']][$row['ptp_name']])) {
											$ptp[$row['comp_name']][$row['ptp_name']] = array();
										}
										$ptp[$row['comp_name']][$row['ptp_name']][] = array($row['prt_id'], $row['prt_name'], $row['cur_shortname']);
									}
								}
								echo "<table class=\"bom-table\" id=\"screenSectorCol\">
				<tbody>
					<tr>
						<td></td>
						<td colspan=\"2\"><input id=\"sectorslo\" type=\"text\" style=\"width:100%\" name=\"sectorslo\" data-slo=\"ACC_788\" /></td>
					</tr>";
								$firstrow = null;
								foreach ($ptp as $company_k => $company_v) {
									echo "<tr>";
									$firstrow = true;
									foreach ($company_v as $group_k => $group_v) {
										if ($firstrow) {
											$firstrow = false;
										} else {
											echo "<tr>";
										}
										echo "<td>$group_k</td><td class=\"sector-select-list\">";
										foreach ($group_v as $account_k => $account_v) {
											echo "<div><div class=\"btn-set\" style=\"margin:3px 0px;\">";
											echo "<span>" . (is_null($account_v[2]) ? "-" : $account_v[2]) . "</span><a href=\"{$fs()->dir}/?--sys_sel-change=account_commit&i={$account_v[0]}\" style=\"\">{$account_v[1]}</a> ";
											echo "</div></div>";
										}
										echo "</td></tr>";
									}
								}
								echo "</tbody>
			</table>";

								?>
							</td>
						</tr>
						<?php
						if (!$accountfound) {
							echo "<tr><td>No accounts assigned to this company</td></tr>";
						}
						?>
						<tr>
							<td class="btn-set" style="justify-content:center"><a href="<?php echo $fs()->dir; ?>/">Return to `<?php echo $fs()->title; ?>`</a></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</form>

	<a href="" id="triggerselector"></a>
	<script>
		$(document).ready(function(e) {
			$("#sectorslo").slo({
				onselect: function(data) {
					$("#triggerselector").attr("href", "<?php echo $fs()->dir; ?>/?--sys_sel-change=account_commit&i=" + data.hidden);
					$("#triggerselector")[0].click();
				}
			}).focus();
		});
	</script>


<?php } ?>