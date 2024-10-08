<?php
declare(strict_types=1);

namespace System\Profiles;

class BrandProfile
{
	public function __construct(public int $id, public string $name, public ?string $logo = null)
	{
	}
}