<?php
class Settings
{
	private $_settings_array;
	protected static $_settings_list;
	protected static $_settings_file;
	protected static $_settings_success = false;
	public function __construct(string $settingsfile)
	{
		$this->_settings_array = array();
		$settingsfile          = @realpath($settingsfile);
		if (@is_file($settingsfile)) {
			self::$_settings_file = $settingsfile;
		} else {
			self::$_settings_file = null;
		}
	}
	public final function settings_fetch()
	{
		self::$_settings_success = true;
		if (($d = @file_get_contents(self::$_settings_file)) !== false) {
			self::$_settings_list = json_decode($d, true);
		}
		return true;
	}

	public final function settings_read()
	{
		if (is_null(self::$_settings_list)) {
			return false;
		}

		return self::$_settings_list;
	}
}
?>