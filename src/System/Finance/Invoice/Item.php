<?php
declare(strict_types=1);

namespace System\Finance\Invoice;
use System\Profiles\MaterialProfile;

class Item
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


	public function __debuginfo(): array
	{
		return [
			$this->material
		];
	}
}
