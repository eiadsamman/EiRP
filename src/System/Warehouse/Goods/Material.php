<?php

namespace System\Warehouse\Goods;


class Material implements Goods
{

	public int $id;
	public int $long_id;
	public string $name;
	public string|null $long_name = null;
	public Unit $unit;
	public Group $group;
	public Category $category;
	public Brand|null $brand = null;
	public function create()
	{
	}
}
