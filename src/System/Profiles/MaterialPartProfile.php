<?php
declare(strict_types=1);

namespace System\Profiles;

use System\enums\UnitSystem;

class MaterialPartProfile extends MaterialProfile
{

	public UnitSystem|null $unitSystem = null;
	public UnitProfile $unit;
	public float $quantity;
	public int $level;
	public float $tolerance;

}


