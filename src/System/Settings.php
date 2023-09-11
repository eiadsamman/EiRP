<?php

declare(strict_types=1);

namespace System;

class Settings
{
	private $payload;
	protected  $_settings_list;
	protected string $file;
	protected  $_settings_list_xmlstyle;
	public function __construct(string $settings_file)
	{
		$this->payload = array();
		$this->file = $settings_file;
	}


	public function read(): bool
	{
		$dom = new \DOMDocument('1.0');
		$temp = $this->template();
		if (@$dom->load($this->file)) {
			$xmlparser = $dom->documentElement;
			if ($xmlparser->hasChildNodes()) {
				foreach ($xmlparser->childNodes as $setting) {
					if ($xmlparser->nodeType == XML_ELEMENT_NODE) {
						if (array_key_exists($setting->nodeName, $temp)) {

							if ($setting->hasChildNodes()) {
								foreach ($setting->childNodes as $vars) {
									if ($vars->nodeType == XML_ELEMENT_NODE) {
										if (array_key_exists($vars->nodeName, $temp[$setting->nodeName])) {
											$temp_value = $vars->nodeValue;
											if ($temp[$setting->nodeName][$vars->nodeName]['attrib'] == "boolean") {
												$temp_value = in_array(trim(strtolower($temp_value)), array("true", "1")) ? true : false;
											} elseif ($temp[$setting->nodeName][$vars->nodeName]['attrib'] == "timezone") {
												$temp_value = trim($temp_value);
												$temp_value = in_array($temp_value, \DateTimeZone::listIdentifiers()) ? $temp_value : "UTC";
											}
											$temp[$setting->nodeName][$vars->nodeName]['value'] = $temp_value;
										}
									}
								}
							}
						}
					}
				}
			}


			unset($xmlparser, $dom);
			foreach ($temp as $k => $v) {
				$this->payload[$k] = array();
				foreach ($v as $vk => $vv) {
					$this->payload[$k][$vk] = $vv['value'];
				}
			}
			unset($temp);
			return true;
		} else {
			unset($temp, $dom);
			return false;
		}
	}

	public function template()
	{
		return array(
			"cpanel" => array(
				'version'		=> array('TYPE' => 'TEXTNODE', 'value' => NULL, 'attrib' => 'VERSION'),
				'license'		=> array('TYPE' => 'CDATA', 'value' => NULL, 'attrib' => 'LICENSE'),
				'username'		=> array('TYPE' => 'CDATA', 'value' => NULL, 'attrib' => ''),
				'password'		=> array('TYPE' => 'CDATA', 'value' => NULL, 'attrib' => ''),
				'database_check' => array('TYPE' => 'TEXTNODE', 'value' => NULL, 'attrib' => 'boolean'),
			),
			"database" => array(
				'host'			=> array('TYPE' => 'CDATA', 'value' => NULL, 'attrib' => ''),
				'username'		=> array('TYPE' => 'CDATA', 'value' => NULL, 'attrib' => ''),
				'password'		=> array('TYPE' => 'CDATA', 'value' => NULL, 'attrib' => 'password'),
				'name'			=> array('TYPE' => 'CDATA', 'value' => NULL, 'attrib' => ''),
			),
			"site" => array(
				'site_version'	=> array('TYPE' => 'TEXTNODE', 'value' => NULL, 'attrib' => ''),
				'subdomain'		=> array('TYPE' => 'CDATA', 'value' => NULL, 'attrib' => ''),
				'forcehttps'	=> array('TYPE' => 'TEXTNODE', 'value' => NULL, 'attrib' => 'boolean'),
				'index'			=> array('TYPE' => 'CDATA', 'value' => NULL, 'attrib' => ''),
				'auther'		=> array('TYPE' => 'CDATA', 'value' => NULL, 'attrib' => ''),
				'title'			=> array('TYPE' => 'CDATA', 'value' => NULL, 'attrib' => ''),
				'keywords'		=> array('TYPE' => 'CDATA', 'value' => NULL, 'attrib' => ''),
				'description'	=> array('TYPE' => 'CDATA', 'value' => NULL, 'attrib' => ''),
				'timezone'		=> array('TYPE' => 'TEXTNODE', 'value' => NULL, 'attrib' => 'timezone'),
				'errorlog'		=> array('TYPE' => 'TEXTNODE', 'value' => NULL, 'attrib' => 'boolean'),
				'errorlogfile'	=> array('TYPE' => 'CDATA', 'value' => NULL, 'attrib' => ''),
			),
		);
	}


	public function __get(string $name): array
	{
		if (array_key_exists($name, $this->payload)) {
			return $this->payload[$name];
		}
		return [];
	}
}
