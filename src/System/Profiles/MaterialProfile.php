<?php
declare(strict_types=1);

namespace System\Profiles;

class MaterialProfile
{
	public int $id;
	public int $longId;
	public string $name;
	public \DateTime $creationDate;
	public string|null $longName = null;
	public int $subMaterialsCount;
	public float $bomPortion = 0;
	public \System\enums\UnitSystem|null $unitSystem = null;
	public MaterialGategoryProfile $category;
	public ?BrandProfile $brand;
	public float $unitsPerBox = 0;
	public string $eanCode = "";
	public MaterialTypeProfile $type;


	public function __toString(): string
	{
		return $this->name;
	}

	public function __debugInfo(): array
	{
		return [
			$this->name
		];
	}

}


