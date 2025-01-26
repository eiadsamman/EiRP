<?php
declare(strict_types=1);

namespace System\Enum;

enum UnitSystem: int
{
	//Name 	Prefix (Unit Symbol)	
	use \System\Core\EnumLib;
	case Count = 1010;
	case Length = 1020;
	case Mass = 1030;
	case Time = 1040;
	case Volume = 1050;
	case Temperature = 1060;
	case Energy = 1070; //Joule (J), Kilowatt-hour (kWh) ,Calorie (cal) British Thermal Unit (BTU)
	case DataStorage = 1080;

	public function toString(): string
	{
		return match ($this) {
			self::Count => 'Count',
			self::Length => 'Length',
			self::Mass => 'Mass',
			self::Time => 'Time',
			self::Volume => 'Volume',
			self::Temperature => 'Temperature',
			self::Energy => 'Energy',
			self::DataStorage => 'Data Storage',

		};
	}
}