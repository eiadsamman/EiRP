<?php
declare(strict_types=1);

namespace System\Profiles;

class UnitProfile
{
	public function __construct(public int $id, public string $symbol, public string $name, public float $rate)
	{
	}
}