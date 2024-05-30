<?php
declare(strict_types=1);

namespace System\IO\RecordManager;

enum InputType: int
{
	case Text = 1;
	case Number = 2;
	case Hidden = 3;
	case CheckBox = 4;
	case Radio = 5;
	case SmartListObject = 6;
	public static function names(): array
	{
		return array_column(self::cases(), 'name');
	}
	public static function values(): array
	{
		return array_column(self::cases(), 'value');
	}
	public static function array(): array
	{
		return array_combine(self::values(), self::names());
	}
}


interface InputFieldInterface
{
	public function __construct();
}
abstract class InputField implements InputFieldInterface
{
	protected InputType $inputType;
	public function __construct()
	{
	}
	public function getInputType(): InputType
	{
		return $this->inputType;
	}
}
class Text extends InputField
{
	public function __construct()
	{
		$this->inputType = InputType::Text;
	}
}


enum RecordType: int
{
	case Int = 1;
	case Float = 2;
	case Text = 3;
	public static function names(): array
	{
		return array_column(self::cases(), 'name');
	}
	public static function values(): array
	{
		return array_column(self::cases(), 'value');
	}
	public static function array(): array
	{
		return array_combine(self::values(), self::names());
	}
}


class DatabaseColumn
{
	public int $id;
	public string $name;
	public string $alias;
	public ?string $displayWidth;
	public ?bool $primaryKey = false;
	public ?bool $readonly = false;
	public ?bool $disabled = true;
	public ?string $inputPlaceholder;
	public RecordType $type;

	public InputField $inputField;

}

class RecordManager
{
	public $readOnly = false;

}
