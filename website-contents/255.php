<?php

$r = $app->db->query(
	"SELECT
	mat_id,mat_long_id,mat_name,cat_alias,mattyp_name,
	SUM(pols_issued_qty) AS _matsum
	
FROM
	inv_records 
	JOIN (
		SELECT 
			mat_id,mat_long_id,mat_name,cat_alias,mattyp_name
		FROM
			mat_materials
				JOIN mat_materialtype ON mattyp_id=mat_mattyp_id
				LEFT JOIN 
					(SELECT CONCAT_WS(', ', matcatgrp_name, matcat_name) AS cat_alias , matcat_id 
						FROM mat_category LEFT JOIN mat_categorygroup ON matcat_matcatgrp_id = matcatgrp_id
					) AS _category ON mat_matcat_id=_category.matcat_id
					
	) AS _material ON pols_item_id = mat_id
	
WHERE
	pols_prt_id = {$app->user->account->id}
GROUP BY
	mat_id
");


echo "<table>";
echo "<tbody>";
if($r){
	while($row=$r->fetch_assoc()){
		echo "<tr>";
		echo "<td>{$row['mat_name']}</td>";
		echo "<td>{$row['_matsum']}</td>";
		
		echo "</tr>";
	}
}

echo "</tbody></table>";
