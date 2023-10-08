<?php



$f = $fs->children(73);

echo "<pre>";
foreach ($f as $p) {

	echo ($p->enabled ? "1" : "0") . "\t";
	echo ($p->permission->read) . "\t";

	echo "{$p->id}\t{$p->title}<br />";
}





?>