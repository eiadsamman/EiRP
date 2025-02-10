<?php
declare(strict_types=1);

namespace System\Profiles;

use System\Enum\UnitSystem;

class MaterialPartProfile extends MaterialProfile
{

	public UnitProfile $unit;
	public float $quantity;
	public int $level;
	public float $tolerance;

}


