<?php
use System\Timeline\Module;
use System\Views\CRM\Customer;
header("Content-Type: application/json; charset=utf-8");
$mods = [
	Module::Company->value,
	Module::FinanceCash->value,
	Module::CRMCustomer->value,
	Module::Inventory->value,
];
$mods = join(",", $mods);

if ($app->xhttp) {
	$payload = json_decode(file_get_contents('php://input'), true);
	if (!empty($payload['method']) && $payload['method'] == "fetch") {
		$json_output = [
			"headers" => [
				"count" => 0,/* Total count of records */
				"pages" => 0,/* Total number of pages */
				"current" => 0,/* Current navigation position on pages variable */
			],
			"contents" => []
		];


		$current = (int) ($payload['page']);
		$current = $current < 0 ? 1 : $current;

		$count = 0;
		$r     = $app->db->query("SELECT COUNT(comp_id) FROM companies");
		if ($r && $row = $r->fetch_array()) {
			$count = $row[0];
		}
		$pages = ceil($count / Customer::$perpage_val);

		$json_output['headers']['count']       = $count;
		$json_output['headers']['pages']       = $pages;
		$json_output['headers']['landing_uri'] = $fs(173)->dir;
		$json_output['headers']['current']     = $current;

		

		//SELECT up_id,up_name,up_size,up_mime FROM 
		$pos = ($current - 1) * Customer::$perpage_val;
		$q   = "SELECT
				comp_id, comp_name,
				_cashValue, _tltot, _tlread, (_tltot - _tlread) AS _tlnew
			FROM 
				companies
					LEFT JOIN (
						SELECT 
							acm_party, SUM( acm_realvalue * IF( acm_type = 1 , 1 , -1) * curexg_value ) AS _cashValue
						FROM 
							acc_main 
								JOIN currency_exchange ON acm_realcurrency = curexg_from 
						GROUP BY
							acm_party
					) AS _cash
					ON _cash.acm_party = comp_id

					LEFT JOIN 
					(
						SELECT
							tl_owner,
							tl_timestamp,
							COUNT(tl_id) AS _tltot,
							COUNT(timeline_track.tlrk_usr_id) AS _tlread
						FROM
							timeline
							LEFT JOIN timeline_track ON tl_id = tlrk_tl_id AND tlrk_usr_id = {$app->user->info->id}
						WHERE
							tl_module IN ($mods)  
						GROUP BY
							tl_owner
						ORDER BY
							tl_timestamp
					) AS sub_timeline ON sub_timeline.tl_owner = comp_id
					
			ORDER BY 
				_tlnew DESC, tl_timestamp DESC, comp_id

			LIMIT $pos, " . Customer::$perpage_val . ";";

		$mysqli_result = $app->db->query($q);


		if ($mysqli_result->num_rows > 0) {
			while ($row = $mysqli_result->fetch_assoc()) {

				$json_output['contents'][] = [
					"id" => $row['comp_id'],
					"name" => $row['comp_name'],
					"news" => $row['_tlnew'] ?? 0,
					"payments" => is_null($row['_cashValue']) ? "00.0" : number_format($row['_cashValue'], 2) . $app->currency->shortname
				];
			}
		}
		echo json_encode($json_output);
		exit;

	}
}