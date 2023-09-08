<?php
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
		var_dump($this->fields);
	}
}

$penumbra = new Penumbra();

$penumbra->field_add_ID("accdef_id", "ID");




$database = array(
	'table' => 'acc_opperationsdefault',
	'tableselect' => '
	acc_opperationsdefault 
		LEFT JOIN (SELECT prt_id,CONCAT(cur_shortname," ", prt_name) AS __inbound_account FROM `acc_accounts` JOIN currencies ON cur_id=prt_currency) AS _inbound_acc ON (_inbound_acc.prt_id=accdef_in_acc_id )
		LEFT JOIN (SELECT prt_id,CONCAT(cur_shortname," ", prt_name) AS __outbound_account FROM `acc_accounts` JOIN currencies ON cur_id=prt_currency) AS _outbound_acc ON (_outbound_acc.prt_id=accdef_out_acc_id )
		
		LEFT JOIN (SELECT acccat_id,CONCAT_WS(" : ",acccat_name,accgrp_name) AS acc_catdet FROM acc_categories JOIN acc_categorygroups ON acccat_group=accgrp_id) AS _cats ON _cats.acccat_id=accdef_category

		LEFT JOIN companies ON comp_id=accdef_company 
		JOIN acc_transtypes ON acctyp_type=accdef_operation
		',
	'tablename' => 'Default accounting settings',



	'fields' => array(
		'accdef_id'  => array(null, 'ID', true, null, 'primary', 'int', true, null, null),


		'comp_name' => array(null, 'Company', true, null, 'slo', 'string', false, 'COMPANIES', 'accdef_company', '<b>list</b> Company name', null, null, "<i>[All companies]</i>"),
		'accdef_company' => array(null, '', false, null, 'sloref', 'int', true, null, null),


		'acctyp_name' => array(null, 'Type', true, null, 'slo', 'string', false, 'ACC_TYPES', 'accdef_operation', '<b>list</b> Transaction', null, null, null),
		'accdef_operation' => array(null, 'Type', false, null, 'sloref', 'int', true, null, null, '<b>int(1-4)</b> transaction type'),


		'accdef_name' => array(null, 'Name', true, null, 'text', 'string', true, null, null, '<b>char(32)</b> operation name, NOTICE: editing or deleteing any <br />entry in this page may cause fatal errors in the system', null, true),

		'__inbound_account' => array(null, 'Inbound account', true, null, 'slo', 'string', false, "ACC", 'accdef_in_acc_id', '<b>list</b> default inbound account', null, null, "<i>[Registered account]</i>"),
		'accdef_in_acc_id' => array(null, '', false, null, 'sloref', 'int', true, null, null),

		'__outbound_account' => array(null, 'Outbound account', true, null, 'slo', 'string', false, 'ACC', 'accdef_out_acc_id', '<b>list</b> default outbound account', null, null, "<i>[Registered account]</i>"),
		'accdef_out_acc_id' => array(null, '', false, null, 'sloref', 'int', true, null, null),


		'acc_catdet' => array(null, 'Default Categroy', true, '100%', 'slo', 'string', false, 'ACC_CAT', 'accdef_category', '<b>list</b> default category'),
		'accdef_category' => array(null, '', false, null, 'sloref', 'int', true, null, null),







		//'accdef_cda'=>array(null,'Default Category Action',true,'100%'	,'text'		,'int'	,true	,null	,null		,'<b>int(0-1)</b> specify if selected category should be linked with selected account',null,false),
	),
	'order' => array('accdef_company', 'accdef_name'),
	// 'disable-delete'=>true,

);

include("website-contents/major.editor.php");
