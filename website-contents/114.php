<?php
include_once("admin/class/SmartListObject.php");

use System\App;
use System\SmartListObject;

$SmartListObject = new SmartListObject();

?>
<div class="btn-set">
	<input type="text" id="list" data-slo=":LIST" data-list="zlist" />
	<input type="text" id="date" data-slo=":DATE" data-rangestart="1970-01-01" value="2023-8-26" data-rangeend="2030-12-31" />
	<input type="text" id="number" data-slo=":NUMBER" data-rangestart="-11" value="55" data-rangeend="100" />
</div>

<datalist id="zlist">
	<?= $SmartListObject->hr_person(App::$_user->company->id); ?>
</datalist>
<?php
echo "<pre>" . print_r(App::$_user, true) . "</pre>";
?>


<script type="text/javascript">
	$(document).ready(function(e) {
		$("#date").slo();
		$("#list").slo();
		$("#number").slo();
	});
</script>