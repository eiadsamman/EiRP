<?php
declare(strict_types=1);

namespace System\Enum;

enum Variables: int
{

	//Name 	Prefix (Unit	Symbol)	
	use \System\Core\EnumLib;
	case Generic = 0;
	case Boolean = 3;
	case Count = 0;

	case Percentage = 3;

	case Length = 1000;
	case Area = 1050;
	case Volume = 1050;

	case Angle = 3;

	case Mass = 1010;
	case Time = 1050;
	case Power = 1030;
	case Energy = 1030;
	case Force = 1040;
	case Temperature = 1050;
	case Speed = 1050;
	case Pressure = 1050;
	case Frequency = 1050;
	case Stiffness=32;
	case Hardness=123;


	case ElectricCurrent = 1050;
	case ElectricPowerFactor = 00;
	case ElectricCapacitance = 00;
	case ElectricResistance = 00;
	case ElectricVoltage = 0;

	case LuminousIntensity = 1020;

	case DataStorage=32;


}