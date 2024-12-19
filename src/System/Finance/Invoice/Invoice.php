<?php
declare(strict_types=1);

namespace System\Finance\Invoice;
use System\Finance\Currency;
use System\Finance\Invoice\enums\PaymentTerm;
use System\Finance\Invoice\enums\ShippingTerm;
use System\Finance\Invoice\structs\InvoiceDetails;
use System\Finance\Invoice\structs\InvoiceItem;
use System\Models\Material;
use System\Profiles\AccountProfile;
use System\Profiles\CompanyProfile;
use System\Profiles\CostCenterProfile;


const ERROR_ROOT = 901000;


abstract class Invoice
{
	protected InvoiceDetails $information;
	protected array $items;


	public function __construct(protected \System\App &$app)
	{
		$this->information              = new InvoiceDetails();
		$this->items                    = [];
		$this->information->issuedBy    = $this->app->user->info;
		$this->information->companyId   = $this->app->user->company->id;
		$this->information->issuingDate = new \DateTime("now");
		$this->information->voided      = false;
		$this->information->parentId    = 0;
	}

	public function appendItem(InvoiceItem $item): void
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

	private function registerSerialNumber(): bool
	{
		$query          = $this->app->db->execute_query(
			"SELECT 
				(IFNULL(MAX(po_serial) , 1000) + 1)
			FROM 
				inv_main
			WHERE 
				po_type = ? 
				AND po_comp_id = ? 
				AND po_costcenter = ?"
			,
			[
				$this->information->type->value,
				$this->app->user->company->id,
				$this->information->costCenter->id,
			]
		);
		$reservedSerial = 0;
		if ($query && $row = $query->fetch_row()) {
			$reservedSerial = $row[0];
		}

		if ($reservedSerial == 0)
			return false;

		$this->app->db->execute_query(
			"UPDATE inv_main SET po_serial = ? WHERE po_id = ?;",
			[
				$reservedSerial,
				$this->information->id
			]
		);
		return $this->app->db->affected_rows > 0;
	}

	public function totalValue(float $value): void
	{
		$this->information->totalValue = $value;
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
				po_payment_term,
				po_shipping_term,
				po_voided
			) 
			VALUES 
			(?,?,?,?,?, ?,?,?,?,?, ?,?,?,?,?, ?,?,?,?,?, ?,?,?,?,?);",
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
				$this->information->parentId ?? 0,
				$this->information->totalValue,
				$this->information->vatRate,
				0,

				$this->information->addtionalAmmout,
				$this->information->discountRate,
				$this->information->paymentTerm ? $this->information->paymentTerm->value : null,
				$this->information->shippingTerm ? $this->information->shippingTerm->value : null,
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
			$item->id = $this->insertInvoiceItem($item, null);
			foreach ($item->subItems as $subItem) {
				$subItem->id = $this->insertInvoiceItem($subItem, $item->id);
			}

		}
		$this->app->db->commit();
		$this->app->db->autocommit(true);
		return $this->information->id;
	}

	private function insertInvoiceItem(InvoiceItem $invoiceItem, ?int $owner = null): int
	{
		$itemInsert = $this->app->db->execute_query(
			"INSERT INTO inv_records (pols_po_id,pols_item_id,pols_issued_qty,pols_delivered_qty,pols_grouping_item,pols_rel_id,pols_prt_id,pols_price,pols_discount) 
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
			,
			[
				$this->information->id,
				$invoiceItem->material->id,
				$invoiceItem->quantity,
				$invoiceItem->quantityDelivered,
				$invoiceItem->isGroupingItem ? 1 : 0,

				$owner,
				$invoiceItem->accountId,
				$invoiceItem->value,
				$invoiceItem->discount
			]
		);
		if (!$itemInsert) {
			$this->app->db->rollback();
			throw new \Exception("Item insertion failed", ERROR_ROOT + 100);
		}

		return $this->app->db->insert_id;
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

	public function vatRate(float $rate): void
	{
		$this->information->vatRate = $rate;
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

	public function parent(int $id): void
	{
		$this->information->parentId = $id;
	}

}
