<?php 
	include_once "../include/header.php";
	include_once "../include/html.header.php";
?>
<h1 style="font-size:1.2em">Permissions management</h1>
<script type="text/javascript" language="javascript" src="static/jquery/jquery.permission.js"></script>
<script language="javascript">
	var _page='<?php echo $_SERVER['PHP_SELF'];?>',
		_baseurl="<?php echo $_SERVER['HTTP_SYSTEM_ROOT'];?>";
</script>
<div class="btn-set">
	<button id="jQperAdd"><span style="font-family:theta;color:#06c;padding-right:8px;">&#xe634;</span>Add permission</button>
</div>
<?php 
	echo "<table class=\"bom-table\" style=\"margin-top:10px;\" id=\"jQPR_output\">";
	echo "<thead><tr>
		<td style=\"min-width:35px;\"></td><td style=\"min-width:60px\">ID</td><td colspan=\"2\"></td>
		<td>Name</td><td>Description</td><td width=\"100%\">Level</td><td></td></tr></thead>";
	echo "<tbody>";
	echo "<tr>";
	include_once "../ajax/permisson.display.php";
	echo "</tr>";
	echo "</tbody></table>";

	include_once "../include/html.footer.php";
?>
