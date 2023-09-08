<?php 
	include_once "../include/header.php";
	include_once "../include/html.header.php";
	$raw=true;$rawoutput=array();
	$_POST['p']=isset($_GET['p'])?addslashes($_GET['p']):"";
	include_once "../ajax/file.trace.php";
	$ps=isset($rawoutput['root']) && $rawoutput['root']?"disabled=\"disabled\"":"";
	$pi=isset($rawoutput['id'])?$rawoutput['id']:"";
	$pd=isset($rawoutput['directory'])?$rawoutput['directory']:"";
?>
<h1 style="font-size:1.2em">Pagefiles management</h1>
<script language="javascript">
	var _page='<?php echo $_SERVER['PHP_SELF'];?>',
		pf_p='<?php echo isset($_POST['p'])?$_POST['p']:"";?>',
		pf_s="",
		pf_method=1,
		p_history=false,
		p_previous='<?php echo (isset($_POST['p'])?$_POST['p']:(isset($_POST['s'])?$_POST['s']:""));?>',
		p_listing=<?php echo isset($_POST['p'])?"true":"false";?>,
		_baseurl="<?php echo $_SERVER['HTTP_SYSTEM_ROOT'];?>";
</script>
<script type="application/javascript" src="static/jquery/jquery.main.js"></script>

<table border="0" cellpadding="0" cellspacing="0" width="100%"><tbody><tr><td width="100%">
<div class="btn-set opicon">
	<span id="jQpagefile_id" style="min-width: 60px;text-align: right;background-color: #fff;">0</span>
	<button id="jQrefresh">&#xe63d;</button>
	<button id="jQclearSearch" class="g" style="display:none;">&#xe638;<span style="font-family:helvetica, Georgia ,Geneva, Verdana, sans-serif;display:inline-block;margin-left:10px;position:relative;bottom:1px;">Clear search</span></button>
	
	<button id="jQaddchild" 	class="b" 	data-pf_id="<?php echo $pi;?>">&#xe634;</button>
	<button id="jQeditchild" 	<?php echo $ps;?> 	class="g" 	data-pf_id="<?php echo $pi;?>">&#xe602;</button>
	<button id="jQmovechild" 	<?php echo $ps;?> 				data-pf_id="<?php echo $pi;?>">&#xe64c;</button>
	<button id="jQdeletechild" 	<?php echo $ps;?> 	class="r" 	data-pf_id="<?php echo $pi;?>">&#xe638;</button>
	<a href="<?php echo $_SERVER['HTTP_SYSTEM_ROOT'].$pd;?>" target="_blank" id="jQopenchild">Open</a>
	<span class="gap" id="jQtracer"><div><?php echo isset($rawoutput['trace'])?$rawoutput['trace']:"";?></div></span>
	
	<span id="jQcount"><?php echo isset($rawoutput['count'])?$rawoutput['count']:"";?> pages</span>
</div></td><td>&nbsp;</td>
<td>
<form action="" id="jQPF_searchForm">
	<span class="btn-set">
		<input type="text" id="jQPF_searchField" style="width:200px;" placeholder="Search..." />
	</span>
</form>
</td></tr></tbody></table>
<?php 
	echo "<table class=\"bom-table\" id=\"jQPF_output\" style=\"margin-top:10px;\"><thead>";
	echo "<tr><td style=\"min-width:35px;\"></td><td style=\"min-width:60px\">ID</td><td colspan=\"5\"></td><td width=\"50%\">Directory</td><td width=\"50%\">Name</td></tr>";
	echo "</thead><tbody>";
	
	$_POST['p']=isset($_GET['p'])?addslashes($_GET['p']):"";
	include_once ("../ajax/file.display.php");
	echo "</tbody></table>";
	include_once ("../include/html.footer.php");
?>