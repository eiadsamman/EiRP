<?php
declare(strict_types=1);

namespace System\IO\RecordManager;

enum InputType: int
{
	use \System\enumLib;
	case Text = 1;
	case Number = 2;
	case Hidden = 3;
	case CheckBox = 4;
	case Radio = 5;
	case SmartListObject = 6;

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
	use \System\enumLib;
	case Int = 1;
	case Float = 2;
	case Text = 3;

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
