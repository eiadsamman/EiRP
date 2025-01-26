<?php
declare(strict_types=1);

namespace System\Profiles;

use System\Lib\Upload\File;

class BrandProfile
{

	public string $name;
	public array $attachments = [];

	public function __construct(public int $id, string $name)
	{
		$this->name = $name;

	}
}