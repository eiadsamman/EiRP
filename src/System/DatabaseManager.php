<?php

namespace System;


/* 

'table'=>string
'tableselect'=>string
'tablename'=>string
'flexablewidth'=>array()
'order'=>array()
'fields'=>array()
'search'=>array()
'perpage'=>int
'disable-delete'=>bool
'readonly'=>bool
*/
class StructFieldRole
{
	public static $primary = 0;
	public static $hidden = 1;
	public static $text = 2;
	public static $slo = 3;
	public static $sloref = 4;
}
class StructFieldType
{
	public static $Integer = 0;
	public static $Float = 1;
	public static $String = 2;
}


class StructField
{
	public $field;
	public $alias = null;
	public $filed_title = null;
	public $visible = true;
	public $width = null; //null|#px|#%
	public $role = 2;
	public $type = 2;
	public $bypass = false;
	public $slo = null;
	public $slo_field_id = null;
	public $description = "";
	public $default_value = null;
	public $disable_edit = false;
	public $null_value_placeholder = null;
}

class Penumbra
{
	private $fields;
	private $table;
	private $from_statement;
	private $title;
	private $id_set = false;

	function __construct()
	{
		$this->fields = array();
		$this->table = null;
	}
	public function Table(string $table_name): void
	{
		$this->table = $table_name;
	}

	public function from_statement(string $statement): void
	{
		$this->from_statement = $statement;
	}

	public function title(string $title): void
	{
		$this->title = $title;
	}

	public function field_add_ID($field, $title)
	{
		$this->id_set = true;
		$temp = new StructField();
		$temp->role = StructFieldRole::$primary;
		$temp->type = StructFieldType::$Integer;
		$temp->field = $field;
		$temp->filed_title = $title;
		array_push($this->fields, $temp);
	}

	public function output()
	{
		echo "<pre>";
	}
}

//$penumbra = new Penumbra();

//$penumbra->field_add_ID("accdef_id", "ID");

