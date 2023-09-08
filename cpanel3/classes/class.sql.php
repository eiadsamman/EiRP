<?php
class SQL extends Settings
{
	protected static $_sql_success = false;
	protected static $_sql_link;
	private $_monitor = false;
	public function __construct()
	{
		self::construct();
	}

	private function construct()
	{
		//Previously opened conncection to db? if so procceed
		if (!is_null(self::$_sql_link)) {
			if (self::check_link())
				return true;
		}
		//Settings fetched successfully?
		if (parent::$_settings_success != true) {
			return false;
		}

		//Settings list
		$settings = parent::$_settings_list;
		//SQL library parameter
		if (!array_key_exists('database', $settings)) {
			return false;
		}

		//Connect
		if (self::connect() == true) {
			//Select
			if (self::open() == true) {
				self::$_sql_success = true;
				return true;
			} else {
				//Close connection on no database selection
				self::$_sql_success = false;
				self::close();
				self::$_sql_link = null;
				return false;
			}
		} else {
			self::$_sql_success = false;
			return false;
		}
	}
	protected function check_link()
	{
		if (is_null(self::$_sql_link)) {
			return false;
		} else {
			if (get_class(self::$_sql_link) == 'mysqli') {
				return true;
			} else {
				return false;
			}
		}
	}
	private function connect()
	{
		if (parent::$_settings_success != true) {
			return false;
		}
		$connect = mysqli_connect(parent::$_settings_list['database']['host'], parent::$_settings_list['database']['username'], parent::$_settings_list['database']['password']);
		if (mysqli_connect_errno() == 0) {
			self::$_sql_link = $connect;
			return true;
		} else {
			return false;
		}
	}
	private function open()
	{
		if (parent::$_settings_success != true) {
			return false;
		}


		if ($this->check_link()) {
			$settings = parent::$_settings_list;
			if (mysqli_select_db(self::$_sql_link, $settings['database']['name']) == true) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	public function __get($name)
	{
		if (strtolower($name) == 'sql_link') {
			if ($this->check_link()) {
				return self::$_sql_link;
			} else {
				return false;
			}
		}
	}
	public function __set($name, $value)
	{
		return false;
	}
	public final function query($query = ""): mysqli_result|bool
	{
		if ($this->check_link()) {
			if ($this->_monitor) {
				$fff = debug_backtrace();
				$output = "";
				foreach ($fff as $k => $v) {
					$output .= (isset($v['file']) ? $v['file'] : "-") . "\t" . (isset($v['line']) ? $v['line'] : "-") . "\n";
				}
				$output = str_replace("\t", "", $query);
				file_put_contents('sqlmonitor.txt', $output . PHP_EOL . str_repeat("-", 40) . PHP_EOL, FILE_APPEND | LOCK_EX);
			}
			$result = mysqli_query(self::$_sql_link, $query);
			if ($result !== false) {
				return $result;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	public final function fetch_assoc($result = null)
	{
		if ($this->check_link()) {
			return mysqli_fetch_assoc($result);
		}
	}
	public final function prepare($statement): mysqli_stmt|bool
	{
		if ($this->check_link()) {
			return mysqli_prepare(self::$_sql_link, $statement);
		}
		return false;
	}

	public final function fetch_row($result = null)
	{
		if ($this->check_link()) {
			return mysqli_fetch_row($result);
		}
	}
	public final function error()
	{
		if ($this->check_link()) {
			return mysqli_error(self::$_sql_link);
		}
	}
	public final function errno()
	{
		if ($this->check_link()) {
			return mysqli_errno(self::$_sql_link);
		}
	}
	public final function free_result($result = null)
	{
		if ($this->check_link()) {
			mysqli_free_result($result);
		}
	}
	public final function insert_id()
	{
		if ($this->check_link()) {
			return mysqli_insert_id(self::$_sql_link);
		}
	}
	public final function close()
	{
		if ($this->check_link()) {
			if (mysqli_close(self::$_sql_link)) {
				self::$_sql_link = null;
				return true;
			} else {
				return false;
			}
		}
	}
	public final function set_charset($string = "utf8")
	{
		if ($this->check_link()) {
			return mysqli_set_charset(self::$_sql_link, $string);
		}
	}
	public final function autocommit($value = "true")
	{
		if ($this->check_link()) {
			return mysqli_autocommit(self::$_sql_link, $value);
		}
	}
	public final function commit()
	{
		if ($this->check_link()) {
			return mysqli_commit(self::$_sql_link);
		}
	}
	public final function rollback()
	{
		if ($this->check_link()) {
			return mysqli_rollback(self::$_sql_link);
		}
	}
	public final function escape($string = "")
	{
		if ($this->check_link()) {
			return mysqli_real_escape_string(self::$_sql_link, $string);
		}
	}
	public final function num_rows($result = null)
	{
		if ($this->check_link()) {
			return mysqli_num_rows($result);
		}
	}
	public final function affected_rows()
	{
		if ($this->check_link()) {
			return mysqli_affected_rows(self::$_sql_link);
		}
	}
}
