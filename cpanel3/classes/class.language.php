<?php
class Languages extends SQL
{
	private $_language_array = null;
	private $_language_array_code = null;
	private $_default_language_id = false;
	private $_default_language_array = false;
	private $_current_language = false;

	public function __construct()
	{
		if (parent::$_sql_success !== true) {
			return;
		}
		$this->_language_array = array();
		if ($r = $this->query("SELECT lng_id,lng_symbol,lng_name,lng_direction,lng_default,lng_icon,lng_css FROM languages ORDER BY lng_id;")) {
			while ($row = $this->fetch_assoc($r)) {
				$row['lng_symbol'] = strtolower($row['lng_symbol']);
				$this->_language_array[$row['lng_id']] = array(
					'id' => $row['lng_id'],
					'name' => $row['lng_name'],
					'symbol' => $row['lng_symbol'],
					'icon' => $row['lng_icon'],
					'css' => $row['lng_css'],
					'dir' => (int)$row['lng_direction'] == 0 ? "ltr" : "rtl",
					'default' => $row['lng_default'],
				);
				$this->_language_array_code[$row['lng_symbol']] = array(
					'id' => $row['lng_id'],
					'name' => $row['lng_name'],
					'symbol' => $row['lng_symbol'],
					'icon' => $row['lng_icon'],
					'css' => $row['lng_css'],
					'dir' => (int)$row['lng_direction'] == 0 ? "ltr" : "rtl",
					'default' => $row['lng_default'],
				);
				if (1 === (int)$row['lng_default']) {
					$this->_default_language_id = $row['lng_id'];
					$this->_default_language_array = array(
						'id' => $row['lng_id'],
						'name' => $row['lng_name'],
						'symbol' => $row['lng_symbol'],
						'icon' => $row['lng_icon'],
						'css' => $row['lng_css'],
						'dir' => (int)$row['lng_direction'] == 0 ? "ltr" : "rtl",
						'default' => $row['lng_default'],
					);
				}
			}
		}
	}
	public function set_current_by_id($id)
	{
		if (isset($this->_language_array[$id])) {
			$this->_current_language = $this->_language_array[$id];
			return true;
		} else {
			return false;
		}
	}
	public function set_current_by_code(string|null $code): bool
	{
		if(is_null($code)){
			return false;
		}
		$code = strtolower($code);
		if (isset($this->_language_array_code[$code])) {
			$this->_current_language = $this->_language_array_code[$code];
			return true;
		} else {
			return false;
		}
	}
	public function get_current()
	{
		return $this->_current_language;
	}
	public function array_key_id()
	{
		return $this->_language_array;
	}
	public function array_key_code()
	{
		return $this->_language_array_code;
	}
	public function default_key_id()
	{
		return $this->_default_language_id;
	}
	public function default_array_key_id()
	{
		return $this->_default_language_array;
	}
	public function check_key_id($id)
	{
		if (isset($this->_language_array[$id])) {
			return true;
		} else {
			return false;
		}
	}
}
