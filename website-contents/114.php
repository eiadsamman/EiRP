<?php
include_once("admin/class/slo_datalist.php");

use System\System;
use System\SLO_DataList;

$slo_datalist = new SLO_DataList();

?>
<div class="btn-set">
	<input type="text" id="list" data-slo=":LIST" data-list="zlist" />
	<input type="text" id="date" data-slo=":DATE" data-rangestart="1970-01-01" value="2023-8-26" data-rangeend="2030-12-31" />
	<input type="text" id="number" data-slo=":NUMBER" data-rangestart="-11" value="55" data-rangeend="100" />
</div>

<datalist id="zlist">
	<?= $slo_datalist->hr_person(System::$_user->company->id); ?>
</datalist>
<?php
echo "<pre>" . print_r(System::$_user, true) . "</pre>";
?>


<script type="text/javascript">
	$(document).ready(function(e) {
		$("#date").slo();
		$("#list").slo();
		$("#number").slo();
	});
</script>