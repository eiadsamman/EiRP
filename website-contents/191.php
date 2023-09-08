<table class="bom-table">
	<tbody>
		<tr>
			<th>File name</th>
			<th>File size</th>
			<th>File modified date</th>
			<th>Directory status</th>
			<th>Moving status</th>
		</tr>
	<?php
	exit;
	$toppath="";
	
	$move="D:\\Dropbox\\Personal\\_\\";
	
	function getlist($p,$level,$dig){
		global $move;
		$d = scandir($p);
		foreach ($d as $dk => $dv) {
			$dv=iconv('ISO-8859-1', 'UTF-8', $dv);
			if(!in_array($dv, array(".",".."))){
				if(is_dir($p."\\".$dv) && $dig){
					getlist($p."\\".$dv,$level+1);
				}elseif(is_file($p."\\".$dv) && strtolower(pathinfo($p."\\".$dv, PATHINFO_EXTENSION))=="jpg" && date("Y-m-d", filemtime($p."\\".$dv))){
					$dd=date("Y-m-d", filemtime($p."\\".$dv));
					echo "<tr>";
					echo "<td>$dv</td>";
					echo "<td>".filesize($p."\\".$dv)."</td>";
					echo "<td>".$move.$dd."</td>";
					
					if(is_dir($move.$dd)){
						echo "<td>DIR-EXI</td>";
					}else{
						$_r=mkdir($move.$dd);
						echo "<td>".($_r?"DIR-DON":"DIR-FIL")."</td>";
					}
					if(!is_file($move.$dd."\\".$dv)){
						if(rename($p."\\".$dv,$move.$dd."\\".$dv)){
							echo "<td>FIL-DON</td>";
						}else{
							echo "<td>FIL-FIL</td>";
						}
					}else{
						echo "<td>FIL-EXI</td>";
					}
					
					
					echo "</tr>";
					
				}
			}
		}
	}

	getlist($toppath,0,false);

	?>
</tbody>
</table>