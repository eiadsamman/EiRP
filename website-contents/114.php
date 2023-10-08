<?php

use System\SmartListObject;


$SmartListObject = new SmartListObject($app);

?>
<div class="btn-set page" style="margin-top:50px;margin-left:10px">
<input type="text" />
<input type="button" value="Button" />
<button>Test</button>

	<input type="text" class="list" data-slo=":SELECT" data-list="zlist" />
	<input type="text" class="list" data-slo="ACC_REFERENCE" />
	<!-- <input type="text" id="date" data-slo=":DATE" value="2023-10-01" data-rangeend="2030-12-31" />
	<input type="text" id="number" data-slo=":NUMBER" data-rangestart="-11" value="55" data-rangeend="100" /> -->
	<button id="fucK">Test</button>

</div>

<datalist id="zlist">
	<?= $SmartListObject->financialTransactionNature(); ?>
</datalist>


<script type="text/javascript">
	$(document).ready(function (e) {
		const slos = $(".page [data-slo]").slo();
		slos.clear();
		slos.each(function (e) {
			if (this.id == "date") {
				this.slo.events.onselect = function (e) {
					console.log(e);
				}
			}
		});

		//list.set(1058);
		/* fucK.addEventListener('click', function (e) {
			console.log(list.first()[0].stamped)   
		}); */

	});





</script>



<?php
exit;
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




<script>
	const submiter = document.getElementById("js-input_button-submit");
	const form = document.getElementById("js-input_form-main");
	const BreakException = {};
	const slo_objects = $(".--slo-object").slo();


	const shit = async () => {
		try {
			const formData = new FormData(form);
			formData.append("shit", "");
			let response = await fetch('<?= $fs()->dir; ?>', {
				method: 'POST',
				headers: {
					"Application-From": "same",
				},
				body: formData,
			});
			const result = await response.text()

			console.log(result);
		} catch (error) {
			//console.log(error);
		}
	};


	form.addEventListener("submit", function (e) {
		e.preventDefault();
		try {
			slo_objects.each(function (e) {
				/* if (this.dataset.skip == undefined && e.slo.stamped != true) {
					e.focus()
					throw BreakException;
				} */
			});
			if (isNaN(parseFloat(document.getElementsByName('value')[0].value))) {
				document.getElementsByName('value')[0].focus();
				throw BreakException;
			}
		} catch (e) {
			messagesys.failure("Required field")
			return false;
		}
		//shit();
		return false;
	});

</script>