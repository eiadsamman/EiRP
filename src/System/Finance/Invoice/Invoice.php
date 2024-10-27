<?php
declare(strict_types=1);

namespace System\Finance\Invoice;
use System\Finance\Currency;
use System\Finance\Invoice\enums\PaymentTerm;
use System\Finance\Invoice\enums\ShippingTerm;
use System\Models\Country;
use System\Models\Material;
use System\Profiles\AccountProfile;
use System\Profiles\CompanyProfile;
use System\Profiles\CostCenterProfile;
use System\Profiles\IndividualProfile;
use System\Profiles\MaterialGategoryProfile;
use System\Profiles\MaterialGroupProfile;
use System\Profiles\MaterialProfile;
use System\Profiles\UnitProfile;
use System\Timeline\Action;
use System\Timeline\Module;
use System\Timeline\Timeline;


const ERROR_ROOT = 901000;


abstract class Invoice
{

	protected Information $information;
	protected array $items;

	public function appendItem(Item $item): void
	{
		if ($item->quantity <= 0) {
			throw new \Exception("Invalid material quantity", ERROR_ROOT + 210);
		}
		if ($item->material->id <= 0) {
			throw new \Exception("Invalid material ID", ERROR_ROOT + 220);
		}
		if ($item->value < 0) {
			throw new \Exception("Invalid material value", ERROR_ROOT + 230);
		}
		$this->items[] = $item;
	}

	public function __construct(protected \System\App &$app)
	{
		$this->information              = new Information();
		$this->items                    = [];
		$this->information->issuedBy    = $this->app->user->info;
		$this->information->companyId   = $this->app->user->company->id;
		$this->information->issuingDate = new \DateTime("now");
		$this->information->voided      = false;
	}

	public function registerSerialNumber(): bool
	{
		$this->app->db->execute_query(
			"UPDATE inv_main
			SET 
				po_serial = (
					SELECT IFNULL(MAX(po_serial) , 1000) + 1 AS doc_serial
					FROM inv_main
					WHERE po_type = ? AND po_comp_id = ? AND po_costcenter = ?
				)
			WHERE po_id = ?;",
			[
				$this->information->type->value,
				$this->app->user->company->id,
				$this->information->costCenter->id,
				$this->information->id
			]
		);
		return $this->app->db->affected_rows > 0;
	}


	public function discountRate(float $discountRate): void
	{
		$this->information->discountRate = $discountRate;
	}
	public function addtionalAmmout(float $addtionalAmmout): void
	{
		$this->information->addtionalAmmout = $addtionalAmmout;
	}

	public function post(): bool|int
	{
		$this->app->db->autocommit(false);
		$this->validatefiled();
		$material = new Material($this->app);

		$result = $this->app->db->execute_query(
			"INSERT INTO inv_main 
			(
				po_comp_id,
				po_costcenter,
				po_cur_id,
				po_type,
				po_issuedby_id,

				po_departement_id,
				po_serial,
				po_title,
				po_date,
				po_due_date,

				po_close_date,
				po_client_id,
				po_shipto_id,
				po_billto_id,
				po_attention_id,

				po_remarks,
				po_rel,
				po_total,
				po_vat_rate,
				po_tax_rate,

				po_additional_amount,
				po_discount,
				po_shipping_term,
				po_voided
			) 
			VALUES 
			(?,?,?,?,?, ?,?,?,?,?, ?,?,?,?,?, ?,?,?,?,?, ?,?,?,?);",
			[
				$this->information->companyId,
				$this->information->costCenter->id,
				$this->information->currency->id ?? null,
				$this->information->type->value,
				$this->information->issuedBy->id,

				$this->information->departement->id ?? 0,
				0,/* Serial */
				$this->information->title,
				$this->information->issuingDate->format("Y-m-d H:i:s"),
				null,

				null,
				$this->information->client->id,/* Client ID */
				null,
				0,
				0,

				$this->information->comments,
				0,
				0,
				0,
				0,

				0,
				0,
				"",
				$this->information->voided
			]
		);

		if ($result) {
			$this->information->id = $this->app->db->insert_id;
			$this->registerSerialNumber();
		} else {
			$this->app->db->rollback();
			throw new \Exception("Item insertion failed", ERROR_ROOT + 100);
		}

		foreach ($this->items as $item) {
			$itemInsert = $this->app->db->execute_query(
				"INSERT INTO inv_records (
					pols_po_id,
					pols_item_id,
					pols_issued_qty,
					pols_delivered_qty,
					pols_grouping_item,
					
					pols_rel_id,
					pols_prt_id,
					pols_price,
					pols_discount
				) VALUES (
					?, ?, ?, ?, ?, ?, ?, ?, ?
				)"
				,
				[
					$this->information->id,
					$item->material->id,
					$item->quantity,
					$item->quantityDelivered,
					$item->isGroupingItem ? "1" : "0",

					$item->relatedItem,
					$item->accountId,
					$item->value,
					$item->discount
				]
			);
			if (!$itemInsert) {
				$this->app->db->rollback();
				throw new \Exception("Item insertion failed", ERROR_ROOT + 100);
			}
			$item->id = $this->app->db->insert_id;
			if ($item->isGroupingItem) {
				foreach ($material->children($item->material->id) as $mat) {
					$itemInsert = $this->app->db->execute_query(
						"INSERT INTO inv_records (
							pols_po_id,
							pols_item_id,
							pols_issued_qty,
							pols_delivered_qty,
							pols_grouping_item,
							
							pols_rel_id,
							pols_prt_id,
							pols_price,
							pols_discount
						) VALUES (
							?, ?, ?, ?, ?, ?, ?, ?, ?
						)"
						,
						[
							$this->information->id,
							$mat->id,
							$item->quantity * $mat->bomPortion,
							$item->quantityDelivered * $mat->bomPortion,
							"0",

							$item->id,
							$item->accountId,
							$item->value,
							$item->discount
						]
					);
					if (!$itemInsert) {
						$this->app->db->rollback();
						throw new \Exception("Item insertion failed", ERROR_ROOT + 100);
					}
				}
			}
		}
		$this->app->db->commit();
		$this->app->db->autocommit(true);

		$tl = new Timeline($this->app);
		$tl->register(module: Module::InvoicingMaterialRequest, action: Action::Create, owner: $this->information->id);

		return $this->information->id;
	}

	private function validatefiled(): bool
	{
		if (sizeof($this->items) == 0) {
			throw new \Exception("No items found or or items list is empty", ERROR_ROOT + 110);
		}
		return true;
	}

	public function costCenter(CostCenterProfile|int $costCenter): void
	{
		if ($costCenter instanceof CostCenterProfile) {
			$this->information->costCenter = $costCenter;
		} else {
			if ($costCenter <= 0) {
				throw new \Exception("Invalid cost center", ERROR_ROOT + 120);
			}
		}
		$this->information->costCenter = new CostCenterProfile($costCenter);
	}


	public function paymentTerm(PaymentTerm|int $paymentTerm): void
	{
		if ($paymentTerm instanceof PaymentTerm) {
			$this->information->paymentTerm = $paymentTerm;
		} else {
			if ($paymentTerm <= 0) {
				throw new \Exception("Invalid payment term", ERROR_ROOT + 180);
			}
			$this->information->paymentTerm = PaymentTerm::tryFrom($paymentTerm);
		}
	}

	public function shippingTerm(ShippingTerm|int $shippingTerm): void
	{
		if ($shippingTerm instanceof ShippingTerm) {
			$this->information->shippingTerm = $shippingTerm;
		} else {
			if ($shippingTerm <= 0) {
				throw new \Exception("Invalid shipping term", ERROR_ROOT + 190);
			}
			$this->information->shippingTerm = ShippingTerm::tryFrom($shippingTerm);
		}
	}


	public function curreny(Currency|int|null $currency): void
	{
		if (is_null($currency)) {
			$this->information->currency = null;
		} elseif ($currency instanceof Currency) {
			$this->information->currency = $currency;
		} else {
			if ($currency <= 0) {
				throw new \Exception("Invalid currency", ERROR_ROOT + 130);
			}
			$this->information->currency = new Currency($currency);
		}
	}

	public function title(string $title): void
	{
		$this->information->title = trim($title);
	}

	public function departement(AccountProfile|int $account): void
	{
		if ($account instanceof AccountProfile) {
			$this->information->departement = $account;
		} else {
			if ($account <= 0) {
				throw new \Exception("Invalid account number", ERROR_ROOT + 150);
			}
			$this->information->departement     = new AccountProfile();
			$this->information->departement->id = $account;
		}
	}

	public function comments(string $comments): void
	{
		$this->information->comments = trim($comments) == "" ? null : $comments;
	}

	public function client(CompanyProfile|int $client): void
	{
		if ($client instanceof CompanyProfile) {
			$this->information->client = $client;
		} else {
			if ($client <= 0) {
				throw new \Exception("Invalid client information", ERROR_ROOT + 140);
			}
			$this->information->client     = new CompanyProfile();
			$this->information->client->id = $client;
		}
	}

	public function read(int $id): Information|bool
	{
		$output = new Information();

		$result = $this->app->db->execute_query(
			"SELECT
				po_id,
				po_comp_id,
				po_costcenter,
				po_cur_id,
				po_type,
				po_issuedby_id,

				po_departement_id,
				po_serial,
				po_title,
				po_date,
				po_due_date,

				po_close_date,
				po_client_id,
				po_shipto_id,
				po_billto_id,
				po_attention_id,

				po_remarks,
				po_rel,
				po_total,
				po_vat_rate,
				po_tax_rate,

				po_additional_amount,
				po_discount,
				po_payment_term,
				po_shipping_term,
				po_voided,
				
				/* OUTER JOINS */
				cur_id, cur_name, cur_shortname, cur_symbol,

				issuer.usr_id AS _issuer_id,
				issuer.usr_firstname AS _issuer_firstname,
				issuer.usr_lastname AS _issuer_lastname,

				client_company.comp_id AS _client_company_id,
				client_company.comp_name AS _client_company_name,
				client_company.comp_address AS _client_company_address,
				client_company.comp_country AS _client_company_country,
				client_company.comp_city AS _client_company_city,
				client_company.comp_tellist AS _client_company_tellist,
				client_company.cntry_name AS _client_company_country_name,
				client_company.cntry_code AS _client_company_country_code,
				
				usrccc_usr_id,
				ccc_name,ccc_id, ccc_vat,
				prt_id, prt_name

			FROM
				inv_main
					LEFT JOIN currencies ON po_cur_id = cur_id
					LEFT JOIN user_costcenter ON po_costcenter = usrccc_ccc_id AND usrccc_usr_id = {$this->app->user->info->id}
					JOIN (SELECT comp_id,comp_name,comp_address,comp_country,comp_city,comp_tellist,cntry_name,cntry_code FROM companies LEFT JOIN countries ON comp_country = cntry_id  ) AS client_company ON client_company.comp_id = po_client_id
					JOIN users AS issuer ON issuer.usr_id = po_issuedby_id
					JOIN inv_costcenter ON ccc_id = po_costcenter
					LEFT JOIN acc_accounts ON prt_id = po_departement_id
			WHERE
				po_id = ?
			",
			[
				$id
			]
		);

		if ($result) {

			if ($row = $result->fetch_assoc()) {
				if (is_null($row['usrccc_usr_id'])) {
					throw new \Exception("Permissions denied", ERROR_ROOT + 300);
				}


				$output->id           = (int) $row['po_id'];
				$output->serialNumber = (int) $row['po_serial'];
				$output->costCenter   = new CostCenterProfile((int) $row['po_costcenter'], $row['ccc_name'], (float) $row['ccc_vat']);

				$output->currency          = is_null($row['po_cur_id']) ? null : new Currency((int) $row['cur_id'], $row['cur_name'], $row['cur_symbol'], $row['cur_shortname']);
				$output->relatedDocumentId = (int) $row['po_rel'];
				$output->totalValue        = (float) $row['po_total'];
				$output->vatRate           = (float) $row['po_vat_rate'];
				$output->taxRate           = (float) $row['po_tax_rate'];
				$output->addtionalAmmout   = (float) $row['po_additional_amount'];
				$output->discountRate      = (float) $row['po_discount'];
				$output->shippingTerm      = is_null($row['po_shipping_term']) ? null : ShippingTerm::tryFrom((int) $row['po_shipping_term']);
				$output->paymentTerm       = is_null($row['po_payment_term']) ? null : PaymentTerm::tryFrom((int) $row['po_payment_term']);

				$output->voided              = (int) $row['po_voided'] == 1;
				$output->issuedBy            = new IndividualProfile();
				$output->issuedBy->id        = (int) $row['_issuer_id'];
				$output->issuedBy->firstname = $row['_issuer_firstname'];
				$output->issuedBy->lastname  = $row['_issuer_lastname'];


				if (!is_null($row['prt_id'])) {
					$output->departement       = new AccountProfile();
					$output->departement->id   = (int) $row['prt_id'];
					$output->departement->name = $row['prt_name'];
				} else {
					$output->departement = null;
				}


				if (!is_null($row['_client_company_id'])) {
					$output->client          = new CompanyProfile();
					$output->client->id      = (int) $row['_client_company_id'];
					$output->client->name    = $row['_client_company_name'];
					$output->client->address = $row['_client_company_address'];
					if (!is_null($row['_client_company_country_name'])) {
						$output->client->country       = new Country($row['_client_company_id']);
						$output->client->country->name = $row['_client_company_country_name'] ?? "";
						$output->client->country->code = (string) $row['_client_company_country_code'];
					}
					$output->client->city           = $row['_client_company_city'];
					$output->client->contactNumbers = $row['_client_company_tellist'];
				}
				$output->title       = $row['po_title'];
				$output->comments    = $row['po_remarks'];
				$output->issuingDate = new \DateTime($row['po_date']);
				$output->dueDate     = is_null($row['po_due_date']) ? null : new \DateTime($row['po_due_date']);
				$output->closeDate   = is_null($row['po_close_date']) ? null : new \DateTime($row['po_close_date']);
				return $output;
			}
		}
		return false;
	}

	public function items(int $invoiceId): \Generator
	{
		$item   = null;
		$result = $this->app->db->execute_query(
			"SELECT
				pols_id,
				pols_po_id,
				pols_item_id,
				pols_issued_qty,
				pols_delivered_qty,
				pols_grouping_item,
				
				pols_rel_id,
				pols_prt_id,
				pols_price,
				pols_discount,

				/* Material  */
				mat_id,mat_name, mat_long_id,mat_longname,
				unt_id, unt_name, unt_category,unt_decim,
				matcatgrp_name, matcatgrp_id, matcat_name, matcat_id,
				brand_id, brand_name
			FROM 
				inv_records 
				JOIN (
					SELECT 
						mat_id,mat_name, mat_long_id,mat_longname,
						unt_id, unt_name, unt_category,unt_decim,
						matcatgrp_name, matcatgrp_id, matcat_name, matcat_id,
						brand_id, brand_name
					FROM 
						mat_materials 
							JOIN mat_unit ON mat_unt_id = unt_id
							JOIN mat_materialtype ON mat_mattyp_id = mattyp_id
							LEFT JOIN brands ON brand_id = mat_brand_id
							JOIN 
								(SELECT matcatgrp_name, matcatgrp_id, matcat_name, matcat_id FROM mat_category JOIN mat_categorygroup ON matcat_matcatgrp_id = matcatgrp_id) 
								AS _category ON mat_matcat_id=_category.matcat_id
					) AS materialProfile ON materialProfile.mat_id = pols_item_id 
			WHERE
				pols_po_id = ?
			ORDER BY
				pols_id",
			[
				$invoiceId
			]
		);

		if ($result) {
			while ($itemRow = $result->fetch_assoc()) {
				$item                 = new Item();
				$item->id             = $itemRow['pols_id'];
				$item->isGroupingItem = (int) $itemRow['pols_grouping_item'] == 1;

				$item->material           = new MaterialProfile();
				$item->material->id       = (int) $itemRow['pols_item_id'];
				$item->material->longId   = (int) $itemRow['mat_long_id'];
				$item->material->name     = $itemRow['mat_name'];
				$item->material->category = new MaterialGategoryProfile(
					(int) $itemRow['matcat_id'],
					$itemRow['matcat_name'],
					new MaterialGroupProfile(
						(int) $itemRow['matcatgrp_id'],
						$itemRow['matcatgrp_name']
					)
				);
				$item->material->longName = $itemRow['mat_longname'];
				$item->material->unit     = new UnitProfile((int) $itemRow['unt_id'], $itemRow['unt_name'], $itemRow['unt_category'], (int) $itemRow['unt_decim']);

				$item->relatedItem       = is_null($itemRow['pols_rel_id']) ? null : (int) $itemRow['pols_rel_id'];
				$item->quantity          = (float) $itemRow['pols_issued_qty'];
				$item->quantityDelivered = is_null($itemRow['pols_delivered_qty']) ? null : (float) $itemRow['pols_delivered_qty'];
				$item->value             = (float) ($itemRow['pols_price']);
				$item->discount          = is_null($itemRow['pols_discount']) ? null : (float) $itemRow['pols_discount'];
				yield $item;

			}
		}
	}


}
