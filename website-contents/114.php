<div class="btn-set" style="margin-top:50px;margin-left:10px">


	<span>Test</span>
	<input type="text" />
	<button>Test</button>
	<input type="text" />
	<span>Test</span>

	
	<!-- <input type="text" id="date" data-slo=":DATE" data-rangestart="1970-01-01" data-rangeend="2030-12-31" /> -->
	<!-- <input type="text" id="number" data-slo=":NUMBER" data-rangestart="-11" value="55" data-rangeend="100" />
	<button id="fucK">Test</button> -->

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