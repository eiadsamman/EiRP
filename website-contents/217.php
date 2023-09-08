<?php 

$r=$sql->query("
	SELECT 
		comp_name,cur_shortname,ptp_name,prt_name,SUM(atm_value) AS accTotal,prt_id,upr_prt_fetch
	FROM 
		`acc_accounts`  
			JOIN currencies ON cur_id=prt_currency
			JOIN user_partition ON prt_id=upr_prt_id AND upr_usr_id={$USER->info->id} AND upr_prt_view=1
			JOIN `acc_accounttype` ON prt_type=ptp_id
			JOIN companies ON prt_company_id=comp_id 
			JOIN user_company ON urc_usr_comp_id = prt_company_id AND urc_usr_id = {$USER->info->id}
			LEFT JOIN user_settings ON usrset_usr_defind_name=prt_id AND usrset_usr_id={$USER->info->id} AND usrset_name='system_count_account_selection'
			
			LEFT JOIN (
				SELECT acm_usr_id,atm_account_id,acm_id,atm_value,acm_rejected
				FROM acc_main JOIN acc_temp ON acm_id=atm_main
				WHERE acm_rejected=0
			) AS subq_acc ON subq_acc.atm_account_id=prt_id
			
	WHERE
		acm_rejected=0
	GROUP BY
		prt_id
	ORDER BY 
		(usrset_value+0) DESC, comp_name ASC,ptp_name ASC, prt_name ASC
	LIMIT 5;
		");
if($r){
	echo "
	<div>
		<div class=\"widgetWQU\">
			<div>";
				while($row=$sql->fetch_assoc($r)){
					echo "<div><div class=\"btn-set\" style=\"flex-wrap: nowrap\">";
						if((int)$row['upr_prt_fetch'] == 1){
							echo "<a class=\"flex\" href=\"?--sys_sel-change=account_commit&i={$row['prt_id']}\" title=\"{$row['comp_name']}: {$row['ptp_name']}: {$row['prt_name']}\">{$row['comp_name']}: {$row['ptp_name']}: {$row['prt_name']}</a>";
						}else{
							echo "<span class=\"nofetch\" title=\"{$row['comp_name']}: {$row['ptp_name']}: {$row['prt_name']}\">{$row['comp_name']}: {$row['ptp_name']}: {$row['prt_name']}</span>";
						}
						echo "<input type=\"text\" style=\"width:25%;min-width:120px;text-align:right;\" readonly=\"readonly\" tabindex=\"-1\" value=\"".($row['accTotal']<0?"(".number_format(abs($row['accTotal']),2,".",",").")":number_format(abs($row['accTotal']),2,".",","))."\" />";
						echo "<span style=\"width:60px;text-align:center;\">".$row['cur_shortname']."</span>";
					echo "</div></div>";
				}
				echo "
			</div>
		</div>
	</div>";

}

?>
