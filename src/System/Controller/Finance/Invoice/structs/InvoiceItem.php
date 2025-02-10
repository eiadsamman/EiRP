<?php
declare(strict_types=1);

namespace System\Controller\Finance\Invoice\structs;
use System\Enum\UnitSystem;
use System\Profiles\MaterialProfile;
use System\Profiles\UnitProfile;

class InvoiceItem
{
	public int $id;
	public int $invoiceId;
	public MaterialProfile $material;

	public float $quantity;
	public ?float $quantityDelivered = null;
	public bool $isGroupingItem = false;
	public ?int $relatedItem = null;
	public ?int $accountId = null;
	public float $value = 0;
	public ?float $discount = null;
	public ?float $vatValue = null;
	public ?float $taxValue = null;

	public UnitProfile $unit;

	public array $subItems = [];

	public function __debugInfo(): array
	{
		return [
			"Material" => $this->material->id,
			"Unit" => $this->unit->symbol,
			"Quantity" => $this->quantity,
			"GroupingItem" => $this->isGroupingItem,
			"RelatedItem" => $this->subItems
		];
	}


}
