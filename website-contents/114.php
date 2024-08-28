<button popovertarget="my-popover">Open popover</button>

<div id="my-popover" popover style="width: 50vw; height: 50vh; ">
Hellloo
</div>

<?php

exit;
use System\Timeline\Action;
use System\Timeline\Module;
use System\Timeline\Timeline;




$tl = new Timeline($app);

echo "<pre>";



exit;
for ($i = 0; $i <= 20; $i++) {

	$r = rand(0, 100) < 50 ? Action::FinancePayment : Action::FinanceReceipt;
	$tl->register(Module::CRMCustomer, $r, 10004, ["id" => ($i * 3 + 1000), "value" => rand(1000, 99999) . ".00 EGP"]);

}


exit;
//use System\IO\RecordManager;
/* 
$t = new System\IO\RecordManager\Text();
var_export($t->getInputType()); */