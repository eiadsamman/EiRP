<?php

declare(strict_types=1);

namespace System\Core;

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
	public function __get(string $name): array
	{
		if (array_key_exists($name, $this->payload)) {
			return $this->payload[$name];
		}
		return [];
	}
}