<?php

namespace System\Warehouse\Goods;

class Unit
{

	public function __construct(public int $id, public  string $name, public string $category, public  int $precision)
	{
	}
}
