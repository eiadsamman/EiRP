<?php

class Animal
{

	public function __construct()
	{
		$arguments = func_get_args();
		$numberOfArguments = func_num_args();

		if ($numberOfArguments == 1) {
			if (gettype($arguments[0]) == "integer") {
				call_user_func_array(array($this, '__construct1'), $arguments);
			}
			if (gettype($arguments[0]) == "string") {
				call_user_func_array(array($this, '__construct2'), $arguments);
			}
		}

	}

	public function __construct1(int $a)
	{
		echo "I AM INTEGER CONSTRUCTOR<Br />";
	}

	public function __construct2(string $a)
	{
		echo "I AM STRGINGNNGNGNG CONSTRUCTOR<BR/>";
	}

}



$a = new Animal(1);
$a = new Animal("1");





exit;
?>
<div class="btn-set" style="margin-top:50px;margin-left:10px">




	<input type="text" id="date" data-slo=":DATE" data-rangestart="1970-01-01" data-rangeend="2030-12-31" />
	<input type="text" id="number" data-slo=":NUMBER" data-rangestart="-11" value="55" data-rangeend="100" />
	<button id="fucK">Test</button>

</div>

<datalist id="zlist">
	<?= $SmartListObject->systemIndividual($app->user->company->id); ?>
</datalist>


<script type="text/javascript">
	$(document).ready(function (e) {
		$("#date").slo();
		$("#number").slo();
		let list = $(".list").slo();

		list.set(1058);
		/* fucK.addEventListener('click', function (e) {
			console.log(list.first()[0].stamped)
		}); */

	});
</script>