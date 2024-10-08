<?php
declare(strict_types=1);

namespace System\Profiles;

class CostCenterProfile
{
	public int $id;
	public ?string $name;
	public ?float $vatRate;

	public function __construct(int $id, ?string $name = null, ?float $vatRate = null)
	{
		$this->id      = $id;
		$this->name    = $name;
		$this->vatRate = $vatRate;
	}
}