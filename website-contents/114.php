<style>
	#ccc {
		transform: translateX(calc(50vw - 50%)) translateY(10vh);
		height: 400px;
		overflow: auto;
		width: 1000px;
		scroll-snap-type: mandatory;
		scroll-snap-stop: always;
		scroll-snap-type: y mandatory;
	}

	#ccc>div {
		padding: 2px;
		border: solid 1px red;
		border-radius: 3px;

		height: 100px;

		scroll-snap-align: start;
		flex: none;

	}
</style>


<div id="ccc">
	<div></div>
	<div></div>
	<div></div>
	<div></div>
	<div></div>
	<div></div>
	<div></div>
	<div></div>
	<div></div>
	<div></div>
	<div></div>
	<div></div>
	<div></div>
	<div></div>
	<div></div>
	<div></div>


</div>
<?php

exit;
//use System\IO\RecordManager;
/* 
$t = new System\IO\RecordManager\Text();
var_export($t->getInputType()); */