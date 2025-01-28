<?php
use System\Controller\Finance\Invoice\enums\PaymentTerm;
use System\Controller\Finance\Invoice\enums\Purchase;
use System\Controller\Finance\Invoice\enums\ShippingTerm;
use System\Layout\Views\PanelView;
$docType = Purchase::Quotation->value;


if ($app->xhttp) {
	$payload = json_decode(file_get_contents('php://input'), true);
	if (!empty($payload['method']) && $payload['method'] == "fetch") {
		$current = (int) ($payload['page']);
		$current = $current < 0 ? 1 : $current;

		$count = 0;
		$q     = "SELECT COUNT(po_id) FROM inv_main a1 JOIN user_costcenter ON a1.po_costcenter = usrccc_ccc_id AND usrccc_usr_id = {$app->user->info->id} WHERE a1.po_type = $docType AND a1.po_comp_id = {$app->user->company->id}";

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
				a1.po_id,
				a1.po_rel,
				a1.po_serial,
				a1.po_voided,
				a1.po_title,
				a1.po_close_date,
				a1.po_costcenter,
				a1.po_total,
				a1.po_additional_amount, 
				a1.po_discount,
				a1.po_payment_term,
				a1.po_shipping_term,
				a1.po_voided,
				a1.po_vat_rate,

				cur_shortname,

				a2.po_id AS parent_po_id,
				a2.po_serial AS parent_po_serial,
				a2.po_costcenter AS parent_costcenter,

				DATE_FORMAT(a1.po_date,'%Y-%m-%d') AS po_date,
				DATE_FORMAT(a1.po_date,'%H:%i') AS po_time,

				CONCAT_WS(' ',usr_firstname,usr_lastname) AS doc_usr_name,
				ccc_name,
				ccc_id
			FROM
				inv_main AS a1
					JOIN users ON usr_id = a1.po_issuedby_id
					JOIN inv_costcenter ON ccc_id = a1.po_costcenter
					JOIN user_costcenter ON a1.po_costcenter = usrccc_ccc_id AND usrccc_usr_id = {$app->user->info->id}
					JOIN currencies ON a1.po_cur_id = cur_id
					LEFT JOIN inv_records ON pols_po_id = a1.po_id
					LEFT JOIN inv_main AS a2 ON a2.po_id = a1.po_rel
			WHERE
				a1.po_type = $docType AND a1.po_comp_id = {$app->user->company->id}
			GROUP BY
				a1.po_id
			ORDER BY 
				a1.po_date DESC
			";

		$mysqli_result = $app->db->query($q);

		if ($mysqli_result->num_rows > 0) {
			while ($row = $mysqli_result->fetch_assoc()) {
				$costcenter = $row['ccc_name'];

				$closed = (is_null($row['po_close_date']) ? "Open" : "Closed");

				$serial       = $app->branding->formatId(System\Controller\Finance\Invoice\enums\Purchase::Quotation, $row['po_serial'], "-" . $row['po_costcenter'] . "-");
				$paretnSerial = $app->branding->formatId(System\Controller\Finance\Invoice\enums\Purchase::Request, $row['parent_po_serial'], "-" . $row['parent_costcenter'] . "-");


				$row['po_title'] = empty($row['po_title']) || $row['po_title'] == "" ? "<i>(Untitled)</i>" : $row['po_title'];
				$grandTotal      = number_format(
					(($row['po_total'] * (1 - $row['po_discount'] / 100)) + $row['po_additional_amount'])
					* (!is_null($row['po_vat_rate']) ? 1 + (float) $row['po_vat_rate'] / 100 : 1)
					,
					2
				);

				$paymentTerm  = is_null($row['po_payment_term']) ? "" : PaymentTerm::tryFrom((int) $row['po_payment_term'])->toString();
				$shippingTerm = is_null($row['po_shipping_term']) ? "" : ShippingTerm::tryFrom((int) $row['po_shipping_term'])->name;


				echo <<<HTML
					<a class="panel-item invoicing" href="{$fs(234)->dir}/?id={$row['po_id']}" data-listitem_id="{$row['po_id']}" data-href="{$fs(234)->dir}">
						<div>
							<span style="flex: 1">
								<div><h1>{$serial}</h1><cite> </cite><cite>{$paretnSerial}</cite></div>
								<div><h1>{$paymentTerm}</h1><cite></cite><cite>{$row['po_title']}</cite></div>
								<div><h1>{$shippingTerm}</h1><cite></cite><cite>{$row['po_date']}</cite></div>
								<div><h1>{$grandTotal} {$row['cur_shortname']}</h1><cite></cite><cite>{$row['doc_usr_name']}</cite></div>
								
							</span>
						</div>
					</a>
				HTML;
			}
		}

		exit;
	}
}