<?php
$defaultcurrency = false;
if ($r = $app->db->query("SELECT cur_id,cur_name,cur_shortname,cur_symbol FROM currencies WHERE cur_default = 1")) {
	if ($row = $r->fetch_assoc()) {
		$defaultcurrency = array();
		$defaultcurrency['id'] = $row['cur_id'];
		$defaultcurrency['name'] = $row['cur_name'];
		$defaultcurrency['symbol'] = $row['cur_symbol'];
		$defaultcurrency['shortname'] = $row['cur_shortname'];
	}
}
if (!$defaultcurrency) {
	echo "System default currency is missing";
} else {

?>
	<table>
		<thead>
			<tr>
				<td>Company</td>
				<td>Type</td>
				<td colspan="4">Account</td>
				<td colspan="2">System</td>
				<td>Transactions count</td>
				<td width="100%"></td>
			</tr>
		</thead>
		<tbody>
			<?php

			if ($r = $app->db->query("
	SELECT 
		prt_company_id,prt_id,prt_name ,comp_name,
		
		SUM( _rates._rate * IF(atm_value IS NULL,0,atm_value) * IF (acm_rejected IS NULL OR acm_rejected!=1,1,0) ) AS atm_value_exchange,
		
		SUM(IF(atm_value IS NULL,0,atm_value) * IF (acm_rejected IS NULL OR acm_rejected!=1,1,0)) AS atm_value_real,
		
		cur_shortname,cur_name,comp_id,
		ptp_id,ptp_name,COUNT(atm_id) AS atm_records_count
	FROM 
		`acc_accounts` 
			LEFT JOIN acc_temp ON prt_id=atm_account_id
			LEFT JOIN acc_main ON acm_id=atm_main
			
			JOIN user_partition ON upr_prt_id=prt_id AND upr_usr_id='{$app->user->info->id}' AND upr_prt_view=1
			JOIN companies ON comp_id=prt_company_id AND comp_id={$app->user->company->id}
			JOIN currencies ON cur_id = prt_currency
			
			
			LEFT JOIN (
				SELECT _from.curexg_from AS _rate_from,_to.curexg_from AS _rate_to,(_from.curexg_value / _to.curexg_value) AS _rate 
					FROM currency_exchange AS _from INNER JOIN currency_exchange AS _to
			) AS _rates ON _rates._rate_from = prt_currency AND _rates._rate_to = {$defaultcurrency['id']}
			
			
			
			
			JOIN
				`acc_accounttype` ON prt_type=ptp_id
	GROUP BY
		prt_id
	ORDER BY
		comp_id,atm_value_exchange DESC;")) {
				$arroutput = array();
				$totalbalance = 0;
				while ($row = $r->fetch_assoc()) {

					if (!isset($arroutput[$row['comp_id']])) {
						$arroutput[$row['comp_id']] = array();
						$arroutput[$row['comp_id']]['balance'] = 0;
						$arroutput[$row['comp_id']]['colspan'] = 0;
						$arroutput[$row['comp_id']]['name'] = $row['comp_name'];
						$arroutput[$row['comp_id']]['types'] = array();
					}

					if (!isset($arroutput[$row['comp_id']]['types'][$row['ptp_id']])) {
						$arroutput[$row['comp_id']]['types'][$row['ptp_id']]['balance'] = 0;
						$arroutput[$row['comp_id']]['types'][$row['ptp_id']]['name'] = $row['ptp_name'];
						$arroutput[$row['comp_id']]['types'][$row['ptp_id']]['accounts'] = array();
					}

					$arroutput[$row['comp_id']]['colspan']++;
					$arroutput[$row['comp_id']]['balance'] += $row['atm_value_exchange'];
					$arroutput[$row['comp_id']]['types'][$row['ptp_id']]['balance'] += $row['atm_value_exchange'];

					$arroutput[$row['comp_id']]['types'][$row['ptp_id']]['accounts'][$row['prt_id']]['name'] = $row['prt_name'];
					$arroutput[$row['comp_id']]['types'][$row['ptp_id']]['accounts'][$row['prt_id']]['balance'] = $row['atm_value_exchange'];
					$arroutput[$row['comp_id']]['types'][$row['ptp_id']]['accounts'][$row['prt_id']]['real'] = $row['atm_value_real'];
					$arroutput[$row['comp_id']]['types'][$row['ptp_id']]['accounts'][$row['prt_id']]['count'] = number_format($row['atm_records_count'], 0, ".", ",");


					$arroutput[$row['comp_id']]['types'][$row['ptp_id']]['accounts'][$row['prt_id']]['currency'] = $row['cur_shortname'];
					$totalbalance += $row['atm_value_exchange'];
				}

				$company_span = true;
				$type_span = true;

				//echo "<pre>".print_r($arroutput,1)."</pre>";
				foreach ($arroutput as $companyk => $companyv) {
					$company_span = true;
					foreach ($companyv['types'] as $typek => $typev) {
						$type_span = true;

						foreach ($typev['accounts'] as $accountID => $account) {
							echo "<tr>";
							if ($company_span) {
								echo "<th rowspan=\"" . ($companyv['colspan'] + (sizeof($companyv['types'])) + 1) . "\">{$companyv['name']}</th>";
								$company_span = false;
							}
							if ($type_span) {
								echo "<th rowspan=\"" . (sizeof($typev['accounts']) + 1) . "\">{$typev['name']}</th>";
								$type_span = false;
							}
							echo "
					<td></td>
					<td>{$account['name']}</td>
					<td style=\"text-align:right;\">" . ($account['real'] < 0 ? "(" . number_format(abs($account['real']), 2, ".", ",") . ")" : number_format(abs($account['real']), 2, ".", ",")) . "</td>
					<td>{$account['currency']}</td>
					<td style=\"text-align:right;\">" . ($account['balance'] < 0 ? "(" . number_format(abs($account['balance']), 2, ".", ",") . ")" : number_format(abs($account['balance']), 2, ".", ",")) . "</td>
					<td>{$defaultcurrency['shortname']}</td>
					<td>{$account['count']}</td>
					<td></td>
					</tr>";
						}
						echo "<tr>
				<td style=\"background-color:rgba(120,120,120,.1);\" colspan=\"3\">" . ($typev['balance'] < 0 ? "Debitor" : "Creditor") . "</td>
				<td style=\"background-color:rgba(120,120,120,.1);text-align:right;\">" . ($typev['balance'] < 0 ? "(" . number_format(abs($typev['balance']), 2, ".", ",") . ")" : number_format(abs($typev['balance']), 2, ".", ",")) . "</td>
				<td style=\"background-color:rgba(120,120,120,.1);\">{$defaultcurrency['shortname']}</td>
				<td colspan=\"3\" style=\"background-color:rgba(120,120,120,.1);font-weight:bold\"></td>
				</tr>";
					}
					echo "<tr>
			<td style=\"background-color:rgba(120,120,120,.25);font-weight:bold\" colspan=\"4\">" . ($companyv['balance'] < 0 ? "Debitor" : "Creditor") . "</td>
			<td style=\"background-color:rgba(120,120,120,.25);font-weight:bold;text-align:right;\">" . ($companyv['balance'] < 0 ? "(" . number_format(abs($companyv['balance']), 2, ".", ",") . ")" : number_format(abs($companyv['balance']), 2, ".", ",")) . "</td>
			<td style=\"background-color:rgba(120,120,120,.25);font-weight:bold\">{$defaultcurrency['shortname']}</td>
			<td colspan=\"3\" style=\"background-color:rgba(120,120,120,.25);font-weight:bold\"></td>
			</tr>";
				}
				echo "<tr>
		<td style=\"background-color:rgba(120,120,120,.4);font-weight:bold\" colspan=\"5\">" . ($totalbalance < 0 ? "Debitor" : "Creditor") . "</td>
		<td style=\"background-color:rgba(120,120,120,.4);font-weight:bold;text-align:right;\">" . ($totalbalance < 0 ? "(" . number_format(abs($totalbalance), 2, ".", ",") . ")" : number_format(abs($totalbalance), 2, ".", ",")) . "</td>
		<td style=\"background-color:rgba(120,120,120,.4);font-weight:bold\">{$defaultcurrency['shortname']}</td>
		<td colspan=\"3\" style=\"background-color:rgba(120,120,120,.4);font-weight:bold\"></td>
		</tr>";
			}
			echo $app->db->error;
			?>
		</tbody>
	</table>
<?php } ?>