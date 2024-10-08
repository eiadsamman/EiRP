<?php
declare(strict_types=1);

namespace System\Profiles;

class MaterialProfile
{
	public int $id;
	public int $longId;
	public string $name;
	public string|null $longName = null;
	public int $subMaterialsCount;
	public float $bomPortion = 0;

	public UnitProfile $unit;

	public MaterialGategoryProfile $category;
	public ?BrandProfile $brand;


	public function __tostring(): string
	{
		return $this->name;

	}

	public function __debuginfo(): array
	{
		return [
			$this->name
		];
	}

}


