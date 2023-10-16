<?php

declare(strict_types=1);

namespace System;

class Settings
{
	private array $payload;
	private string $filepath;
	public function __construct(string $filepath)
	{
		$this->filepath = $filepath;
	}

	public function read(): bool
	{
		try {
			if (($d = @file_get_contents($this->filepath)) !== false) {
				$this->payload = json_decode($d, true);
			}
		} catch (\TypeError $e) {
			return false;
		} catch (\Exception $e) {
			return false;
		}

		return true;
	}

	private function template()
	{
		return array(
			"cpanel"   => array(
				'username',
				'password',
			),
			"database" => array(
				'host',
				'username',
				'password',
				'name',
			),
			"site"     => array(
				'version',
				'subdomain',
				'forcehttps',
				'index',
				'auther',
				'title',
				'keywords',
				'description',
				'timezone',
				'errorlog',
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