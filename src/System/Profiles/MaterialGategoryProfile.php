<?php
declare(strict_types=1);

namespace System\Profiles;

class MaterialGategoryProfile
{
	public function __construct(public int $id, public string $name, public MaterialGroupProfile $group)
	{
	}
}