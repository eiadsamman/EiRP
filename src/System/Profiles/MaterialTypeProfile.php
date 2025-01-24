<?php
declare(strict_types=1);

namespace System\Profiles;

class MaterialTypeProfile
{
	public int $id;


	public function __construct(public ?string $name = null, public ?string $description = null)
	{
	}
	public function __toString(): string
	{
		return (string) $this->description . " [{$this->name}]";
	}

	public function __debugInfo(): array
	{
		return [
			$this->name
		];
	}

}


