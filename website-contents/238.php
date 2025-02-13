<?php
use System\Controller\Finance\Invoice\enums\Purchase;
use System\Layout\Views\PanelView;
$docType = Purchase::Request->value;


if ($app->xhttp) {
	$payload = json_decode(file_get_contents('php://input'), true);
	if (!empty($payload['method']) && $payload['method'] == "fetch") {
		$current = (int) ($payload['page']);
		$current = $current < 0 ? 1 : $current;

		$count = 0;
		$q     = "SELECT COUNT(po_id) FROM inv_main JOIN user_costcenter ON po_costcenter = usrccc_ccc_id AND usrccc_usr_id = {$app->user->info->id} WHERE po_type = $docType AND po_comp_id = {$app->user->company->id}";

		$r = $app->db->query($q);
		if ($r && $row = $r->fetch_array()) {
			$count = $row[0];
		}
		$pages = ceil($count / $app->user->recordsPerRequest);

		header("res_count: {$count}");
		header("res_pages: {$pages}");
		header("res_current: {$current}");

		$pos = ($current - 1) * $app->user->recordsPerRequest;

		$q = "SELECT 
				po_id,
				po_serial,
				a2.quotationsCount,
				po_costcenter,
				po_voided,
				po_title,
				DATE_FORMAT(po_date,'%Y-%m-%d') AS po_date,
				DATE_FORMAT(po_date,'%H:%i') AS po_time,
				CONCAT_WS(' ',usr_firstname,usr_lastname) AS doc_usr_name,
				po_close_date,
				ccc_name,ccc_id,
				COUNT(ir.pols_po_id) AS total_orders
			FROM
				inv_main
					JOIN users ON usr_id = po_issuedby_id
					JOIN inv_costcenter ON ccc_id = po_costcenter
					JOIN user_costcenter ON po_costcenter = usrccc_ccc_id AND usrccc_usr_id = {$app->user->info->id}
					LEFT JOIN inv_records ir ON pols_po_id = po_id

					LEFT JOIN (SELECT COUNT(po_id) AS quotationsCount, po_rel FROM inv_main GROUP BY po_rel) AS a2 ON po_id = a2.po_rel
			WHERE
				po_type = {$docType} AND po_comp_id = {$app->user->company->id}
			
			GROUP BY
				po_id
			ORDER BY po_date DESC
			";

		$mysqli_result = $app->db->query($q);
		if ($mysqli_result->num_rows > 0) {
			while ($row = $mysqli_result->fetch_assoc()) {
				$costcenter       = $row['ccc_name'];
				$row['po_title']  = is_null($row['po_title']) || trim($row['po_title']) == "" ? "<i>(Untitled)</i>" : $row['po_title'];
				$row['po_serial'] = $app->branding->formatId(System\Controller\Finance\Invoice\enums\Purchase::Request, $row['po_serial'], "-" . $row['po_costcenter'] . "-");


				$closed                 = (is_null($row['po_close_date']) ? "Open" : "Closed");
				$row['quotationsCount'] = $row['quotationsCount'] > 0 ? "{$row['quotationsCount']} <span class=\"light\">Quotation(s)</span>" : "<span class=\"light\">(No quotations)</span>";
				echo <<<HTML
					<a class="panel-item invoicing" href="{$fs(240)->dir}/?id={$row['po_id']}" data-listitem_id="{$row['po_id']}" data-href="{$fs(240)->dir}">
						<div>
							<span style="flex: 1">
								<div><h1>{$row['po_serial']}</h1><cite></cite><cite>{$row['po_title']}</cite></div>
								<div><h1>{$row['total_orders']} <span class="light">Item(s)</span></h1><cite></cite><h1 style="text-align:right">{$row['po_date']}</h1></div>
								<div><h1>{$row['quotationsCount']}</h1><cite></cite><cite>{$row['doc_usr_name']}</cite></div>
							</span>
						</div>
					</a>
				HTML;
			}
		}

		exit;
	}
}