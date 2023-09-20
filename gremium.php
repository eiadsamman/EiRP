<?php
use System\Template\Gremium;


$grem = new Gremium\Gremium(true, false);

$head = $grem->header()->serve("<h1>Header 1</h1>");

$menu = $grem->menu()->serve("<a href=\"okok\">My button is beautiful</a><span class=\"gap\"></span>");

$grem->menu()->serve("<a href=\"okok\">My button is beautiful</a><span class=\"gap\"></span>");



for ($i = 0; $i < 30; $i++) {
	$grem->legend()->serve("<span class=\"flex\">Header</span>");
	$grem->article()->serve("Data <br />Data <br />Data <br />Data <br />Data <br />Data <br />Data <br />Data <br />Data <br />");
}
$grem->terminate();
