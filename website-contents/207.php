<form action="<?php echo $fs()->dir;?>" method="post">
<div style="width:100%;text-align:center;margin-top:20px">
<div id="screenSector">
<table class="bom-table">
	<thead><tr class="special"><td><h2><b>Select Working Company</b></h2></td></tr></thead>
	<tbody>
		
	<tr>
		<td align="left" style="text-align:left" valign="top">
		<table class="bom-table">
			<tbody>
				<tr>
					<td></td>
					<td colspan="2"><input id="compslo" type="text" style="width:100%" data-slo="COMPANY_USER" /></td>
				</tr>
			</tbody>
		</table>
	</tr>
	<tr>
		<td>
			<div class="btn-set">
				<?php
					$q=$sql->query("SELECT comp_id,comp_name FROM companies JOIN user_company ON urc_usr_comp_id = comp_id AND urc_usr_id = {$USER->info->id}");
					if($q){
						while($row=$sql->fetch_assoc($q)){
							printf("<a href=\"%s/?--sys_sel-change=company_commit&i=%d\" style=\"height: 60px;width: 150px;\">
								<span style=\"display: table-cell;vertical-align: middle;height: 43px;white-space: normal;color:#333;text-align:center;width:130px;\">%s</span>
							</a>",$fs()->dir,(int)$row['comp_id'],$row['comp_name']);
						}
					}
				?>
			</div>
		</td>
	</tr>
	<tr>
		<td class="btn-set" style="justify-content:center"><a href="<?php echo $fs()->dir;?>/">Return to `<?php echo $fs()->title;?>`</a></td>
	</tr>
	</tbody>
</table>
</div></div>
</form>

<a href="" id="triggerselector"></a>
<script>
$(document).ready(function(e) {
	$("#compslo").slo({
		onselect:function(data){
			$("#triggerselector").attr("href","<?php echo $fs()->dir;?>/?--sys_sel-change=company_commit&i="+data.hidden);
			$("#triggerselector")[0].click();
		},limit:10
	}).focus();
});
</script>