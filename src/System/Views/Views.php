<?php
declare(strict_types=1);

namespace System\Views;

use System\App;

interface Views
{

	public function __construct(App &$app);

	public function render(): void;

}
