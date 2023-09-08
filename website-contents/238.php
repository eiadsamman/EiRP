<?php
	require_once("admin/class/invoice.php");
	include_once("admin/class/Template/class.template.build.php");
	$_SIDE = new Template\SidePanelBuild();
	
	use Finance\Invoice;

	function SidePanelContent($sql, $user, $pageUrl, $pageTitle){
		$r=$sql->query("
			SELECT 
				_main.po_id,
				CONCAT(prx_value,LPAD(po_serial,prx_placeholder,'0')) AS doc_id,
				_main.po_canceled,
				_main.po_title,
				DATE_FORMAT(_main.po_date,'%Y-%m-%d') AS po_date,
				DATE_FORMAT(_main.po_date,'%H:%i') AS po_time,
				CONCAT_WS(' ',COALESCE(usr_firstname,''),COALESCE(usr_lastname,'')) AS doc_usr_name,
				DATE_FORMAT(_main.po_date,'%W, %M %d, %Y') AS groupDate,
				COUNT(pols_id) AS matcount,
				_sub._subcount AS qutcount,
				_main.po_close_date
			FROM
				inv_main AS _main
					JOIN users ON usr_id = _main.po_usr_id
					JOIN system_prefix ON prx_id=".Invoice::map['MAT_REQ']."
					LEFT JOIN inv_records ON pols_po_id = _main.po_id
					LEFT JOIN (SELECT po_rel, COUNT(po_id) AS _subcount FROM inv_main WHERE po_type = 2 GROUP BY po_rel) AS _sub ON _sub.po_rel = _main.po_id
			WHERE
				_main.po_type = ".Invoice::map['MAT_REQ']." AND  _main.po_comp_id={$user->company->id}
			GROUP BY
				_main.po_id
			ORDER BY _main.po_date DESC
			");
		if($r){
			$arroutput=array();
			while($row=$sql->fetch_assoc($r)){
				if(!isset($arroutput[$row['groupDate']])){
					$arroutput[$row['groupDate']]=array();
				}
				$arroutput[$row['groupDate']][] = $row;
			}
			if(sizeof($arroutput)==0){
				echo "<div><span>No pending requests</span></div>";
			}
			foreach($arroutput as $k=>$v){
				if($k!=null)
					echo "<div><span>{$k}</span></div>";
				foreach($v as $elem_key => $elem_val){
					echo "<a class=\"role-templatelink\" data-role_title=\"$pageTitle\" href=\"$pageUrl/?docid={$elem_val['po_id']}&token=".md5("sysdoc_".$elem_val['po_id'].session_id())."\">
						
						<span><span>{$elem_val['doc_id']}</span><span".($elem_val['po_canceled']==1?" style=\"text-decoration:line-through\"":"").">{$elem_val['po_title']}</span></span>
						<span><span>Items: {$elem_val['matcount']}</span><span>{$elem_val['po_time']}</span></span>
						
						<span><span>Quotations: ".(int)$elem_val['qutcount']."</span><span>{$elem_val['doc_usr_name']}</span></span>
						
						".($elem_val['po_canceled']==1?" <span style=\"color:#f03\"><span>Request Canceled</span></span> ":"")."
						<span class=\"fade\"><span>Status</span>".(is_null($elem_val['po_close_date'])?"<span style=\"color:#093\">Pending</span>":"<span style=\"color:#f03\">Closed</span> ")."</span>
						
						
					</a>";
				}
			}	
			echo '<script type="text/javascript">
				$(function(){
					$(".role-templatelink,#jQlinkNewDoc").Template();
				});
			</script>';
		}
	}
	
	if($h__requested_with_ajax || isset($_POST['TemplateCallback'])){
		SidePanelContent(
			$sql,
			$USER,
			$tables->pagefile_info(240,null,"directory"),
			$c__settings['site']['title']." - ".$tables->pagefile_info(240,null,"title")
		);
		exit;
	}
?>


<?= $_SIDE->HeaderStart()?>
	<div class="btn-set" style="margin-bottom: 10px;">
		<a 
		href="<?php echo $tables->pagefile_info(230,null,"directory");?>" id="jQlinkNewDoc"
		data-role_title="<?php echo $c__settings['site']['title']." - ".$tables->pagefile_info(230,null,"title");?>" class="flex" style="text-align: center;color:#333">New Material Request</a>
	</div>
	<div class="btn-set">
		<input type="text" class="flex" placeholder="Search" name="">
	</div>
<?= $_SIDE->HeaderEnd()?>

<?php 
	echo $_SIDE->BodyStart();
	SidePanelContent(
		$sql,
		$USER,
		$tables->pagefile_info(240,null,"directory"),
		$c__settings['site']['title']." - ".$tables->pagefile_info(240,null,"title")
	);
	echo $_SIDE->BodyEnd()
?>

