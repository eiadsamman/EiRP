<?php

declare(strict_types=1);

namespace System;

use System\Profiles\UnitProfile;

class Unit
{
	private array $map;
	private array $systemBaseUnits;

	public function __construct()
	{
		$this->map = [];

		$this->systemBaseUnits = [
			\System\Enum\UnitSystem::Length->value => 1,
			\System\Enum\UnitSystem::Mass->value => 1,
			\System\Enum\UnitSystem::Count->value => 1,
			\System\Enum\UnitSystem::Volume->value => 1,
		];
		$this->mapInit();
	}

	public function list(int $system): array
	{
		if (array_key_exists($system, $this->map)) {
			return $this->map[$system];
		}
		return [];
	}

	public function getUnit(int $systemId, int $unitId): UnitProfile|false
	{
		if (array_key_exists($systemId, $this->map)) {
			if (array_key_exists($unitId, $this->map[$systemId])) {
				return $this->map[$systemId][$unitId];
			}
		}
		return false;
	}

	public function defaultUnit(int $system): int|bool
	{
		if (array_key_exists($system, $this->systemBaseUnits)) {
			return $this->systemBaseUnits[$system];
		}
		return false;
	}
	private function mapInit(): void
	{
		$this->map[\System\Enum\UnitSystem::Length->value] =
			[
				1 => new UnitProfile(1, "m", "Metre", 1),
				2 => new UnitProfile(2, "cm", "Centi Metre", pow(10, -2)),
				3 => new UnitProfile(3, "mm", "Mili Metre", pow(10, -3)),
				4 => new UnitProfile(4, "μm", "Micro Metre", pow(10, -6)),
				5 => new UnitProfile(5, "nm", "Nano Metre", pow(10, -9)),
				6 => new UnitProfile(6, "km", "Kilo Metre", pow(10, 3)),
			];

		$this->map[\System\Enum\UnitSystem::Volume->value] =
			[
				1 => new UnitProfile(1, "L", "Liter", 1),
				2 => new UnitProfile(2, "mL", "Mili Liter", pow(10, -3)),
				3 => new UnitProfile(3, "dL", "Mili Liter", pow(10, -1)),
				4 => new UnitProfile(4, "m³", "Cubic Meter", pow(10, 3)),
				5 => new UnitProfile(5, "cm³", "Cubic Centi-Meter", pow(10, -3)),
				6 => new UnitProfile(6, "in³", "Cubic Inch", 0.0163871),
				7 => new UnitProfile(7, "ft³", "Cubic Foot", 28.3168),
			];
		$this->map[\System\Enum\UnitSystem::Mass->value]   =
			[
				1 => new UnitProfile(1, "g", "gram", 1),
				2 => new UnitProfile(2, "kg", "kilo-gram", pow(10, 3)),
				3 => new UnitProfile(3, "T", "metric-ton", pow(10, 6)),
			];
		$this->map[\System\Enum\UnitSystem::Count->value]  =
			[
				1 => new UnitProfile(1, "EA", "Piece", 1),
			];

		$this->map[\System\Enum\UnitSystem::Time->value] =
			[
				1 => new UnitProfile(1, "s", "second", 1),
				2 => new UnitProfile(2, "ms", "mili-second", pow(10, -3)),
				3 => new UnitProfile(3, "μs", "micro-second", pow(10, -6)),
			];
	}
}
