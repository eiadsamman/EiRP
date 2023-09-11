<?php

namespace System;

class Comparator
{
	private $key;

	public function __construct(string $key)
	{
		$this->key = $key;
	}

	public function __invoke($a, $b)
	{
		return $a[$this->key] <=> $b[$this->key];
	}
}
/* 
$customers = [
	['id' => 1, 'name' => 'John', 'credit' => 20000],
	['id' => 3, 'name' => 'Alice', 'credit' => 10000],
	['id' => 2, 'name' => 'Bob', 'credit' => 15000]
];

// sort customers by names
usort($customers, new Comparator('name'));
print_r($customers);

// sort customers by credit
usort($customers, new Comparator('credit'));
print_r($customers);
 */