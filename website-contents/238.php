<?php
use System\Finance\Invoice\enums\Purchase;
use System\Views\PanelView;
$docType = Purchase::Request->value;


if ($app->xhttp) {
	$payload = json_decode(file_get_contents('php://input'), true);
	if (!empty($payload['method']) && $payload['method'] == "fetch") {
		$current = (int) ($payload['page']);
		$current = $current < 0 ? 1 : $current;

		$count = 0;
		$q     = "SELECT COUNT(po_id) FROM inv_main WHERE po_type = $docType AND po_comp_id = {$app->user->company->id}";

		$r = $app->db->query($q);
		if ($r && $row = $r->fetch_array()) {
			$count = $row[0];
		}
		$pages = ceil($count / PanelView::$itemsPerRequest);

		header("res_count: {$count}");
		header("res_pages: {$pages}");
		header("res_current: {$current}");

		$pos = ($current - 1) * PanelView::$itemsPerRequest;

		$q = "SELECT 
				a1.po_id,
				a1.po_serial,
				a2.quotationsCount,
				a1.po_costcenter,
				a1.po_voided,
				a1.po_title,
				DATE_FORMAT(a1.po_date,'%Y-%m-%d') AS po_date,
				DATE_FORMAT(a1.po_date,'%H:%i') AS po_time,
				CONCAT_WS(' ',usr_firstname,usr_lastname) AS doc_usr_name,
				a1.po_close_date,
				ccc_name,ccc_id
				
			FROM
				inv_main AS a1
					JOIN users ON usr_id = a1.po_issuedby_id
					JOIN system_prefix ON prx_id = {$docType}
					JOIN inv_costcenter ON ccc_id = a1.po_costcenter
					JOIN user_costcenter ON a1.po_costcenter = usrccc_ccc_id AND usrccc_usr_id = {$app->user->info->id}
					LEFT JOIN inv_records ON pols_po_id = a1.po_id

					LEFT JOIN (SELECT COUNT(po_id) AS quotationsCount, po_rel FROM inv_main GROUP BY po_rel) AS a2 ON a1.po_id = a2.po_rel
			WHERE
				a1.po_type = {$docType} AND a1.po_comp_id = {$app->user->company->id}
			
			GROUP BY
				a1.po_id
			ORDER BY a1.po_date DESC
			";

		$mysqli_result = $app->db->query($q);
		if ($mysqli_result->num_rows > 0) {
			while ($row = $mysqli_result->fetch_assoc()) {
				$costcenter             = $row['ccc_name'];
				$row['po_title']        = is_null($row['po_title']) || trim($row['po_title']) == "" ? "<i>(Untitled)</i>" : $row['po_title'];
				$row['po_serial']       = $app->prefixList[100][0] . $row['po_costcenter'] . str_pad($row['po_serial'], $app->prefixList[100][1], "0", STR_PAD_LEFT);
				$closed                 = (is_null($row['po_close_date']) ? "Open" : "Closed");
				$row['quotationsCount'] = $row['quotationsCount'] > 0 ? "<span class=\"price-padge\">Quotations {$row['quotationsCount']}</span>" : "<i>(No quotations)</i>";
				echo <<<HTML
					<a class="panel-item invoicing" href="{$fs(240)->dir}/?id={$row['po_id']}" data-listitem_id="{$row['po_id']}" data-href="{$fs(240)->dir}">
						<div>
							<span style="flex: 1">
								<div><h1>{$row['po_serial']}</h1><cite></cite><cite>{$row['po_title']}</cite></div>
								<div><h1></h1><cite></cite><cite>{$row['po_date']}</cite></div>
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