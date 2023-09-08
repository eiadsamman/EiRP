<?php

$sqlquery_materialList = $sql->query("
SELECT
	mat_id,mat_long_id,mat_name,cat_alias,mattyp_name,unt_name,unt_decim,
	SUM(pols_issued_qty) AS _matsum
	
FROM
	inv_records 
	JOIN (
		SELECT 
			mat_id,mat_long_id,mat_name,cat_alias,mattyp_name,unt_name,unt_decim
		FROM
			mat_materials
				JOIN mat_materialtype ON mattyp_id=mat_mattyp_id
				JOIN mat_unit ON unt_id = mat_unt_id
				LEFT JOIN 
					(SELECT CONCAT_WS(', ', matcatgrp_name, matcat_name) AS cat_alias , matcat_id 
						FROM mat_category LEFT JOIN mat_categorygroup ON matcat_matcatgrp_id = matcatgrp_id
					) AS _category ON mat_matcat_id=_category.matcat_id
					
	) AS _material ON pols_item_id = mat_id
	
WHERE
	pols_prt_id = {$USER->account->id}
GROUP BY
	mat_id
");


echo "<table class=\"bom-table\">";


echo "<tbody>";
if($sqlquery_materialList){
	while($row=$sql->fetch_assoc($sqlquery_materialList)){
		echo "<tr>";
		echo "<td>{$row['mat_name']}</td>";
		echo "<td>{$row['_matsum']}</td>";
		
		echo "</tr>";
	}
}

echo "</tbody></table>";
?>