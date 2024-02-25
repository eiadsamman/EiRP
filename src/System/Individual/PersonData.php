<?php

declare(strict_types=1);

namespace System\Individual;

class PersonData
{
	public int $id;
	public string $name;
	public string $username;
	public int $permissions;
	public int $level;
	public int $photoid;
	public $resignDate;
	public $resigned;
	public bool $active;
}
