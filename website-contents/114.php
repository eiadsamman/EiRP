<?php

$term = System\Finance\Invoice\enums\PaymentTerm::EndOfMonth;


$d = new \DateTime("now");

echo "<pre>";
var_dump($d);
var_dump($term->getDueDate($d));