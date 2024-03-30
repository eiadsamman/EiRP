<?php

declare(strict_types=1);

namespace System\Finance;

class Currency
{
	public function __construct(
		public ?int $id = null,
		public ?string $name = null,
		public ?string $symbol = null,
		public ?string $shortname = null
	) {
	}
}
