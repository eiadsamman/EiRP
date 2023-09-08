<?php
echo "
<div>
	<div class=\"widgetWQT\">";
		$dateTo = date("Y-m-d H:i:s", time());
		
		$r=("
			SELECT
				prt_name,COUNT(_ltr_usr_id) AS partcount
			FROM 
				labour_track 
				
				INNER JOIN 
				(
					SELECT 
						MAX(ltr_ctime) AS _ltr_ctime, 
						ltr_usr_id AS _ltr_usr_id ,
						prt_name
					FROM 
						labour_track 
							JOIN `acc_accounts` ON prt_id = ltr_prt_id
					WHERE
						ltr_otime IS null
					GROUP BY 
						_ltr_usr_id 
				) AS lastJoin ON lastJoin._ltr_ctime = ltr_ctime AND lastJoin._ltr_usr_id = ltr_usr_id
				
				JOIN 
					labour ON lbr_company = {$USER->company->id} AND ltr_usr_id = lbr_id
			GROUP BY prt_name
		
		");
		$r=$sql->query($r);
		if($r){
			while($row=$sql->fetch_assoc($r)){
				echo $row['prt_name']."-".$row['partcount']." Employees";
			}
		}

		echo "
	</div>
</div>";
?>