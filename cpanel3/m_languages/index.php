<?php 
	include_once "../include/header.php";
	include_once "../include/html.header.php";
?>
<h1 style="font-size:1.2em">Languages managements</h1>
<script type="text/javascript" language="javascript" src="static/jquery/jquery.languages.js"></script>
<script language="javascript">
	var _page='<?php echo $_SERVER['PHP_SELF'];?>',
		_baseurl="<?php echo $_SERVER['HTTP_SYSTEM_ROOT'];?>";
</script>
<div class="btn-set">
	<button id="jQlngAdd"><span style="font-family:theta;color:#06c;padding-right:8px;">&#xe634;</span>Add Language</button>
</div>
<?php 
	echo "<table class=\"bom-table\" style=\"margin-top:10px;\" id=\"jQLN_output\">";
	echo "<thead><tr>
		<td style=\"width:35px;\"></td><td style=\"width:60px\">ID</td><td style=\"width:30px\"></td>
		<td style=\"width:30px\"></td>
		<td style=\"width:30px\">Default</td>
		<td>Name</td><td>Symbol</td>
		<td>Direction</td>
		</tr></thead>";
	echo "<tbody>";
	echo "<tr>";
	include_once ("../ajax/languages.display.php");
	echo "</tr>";
	echo "</tbody></table>";

	include_once ("../include/html.footer.php");
?>
