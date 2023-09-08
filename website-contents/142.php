<?php
function numtotext($numberValue){
    $textResult = ''; 
	$numberValue=(int)$numberValue;
    $numberValue = "$numberValue";
    if($numberValue[0] == '-'){
        $textResult .= 'سالب ';
        $numberValue = substr($numberValue,1);
    }
    $numberValue = (int) $numberValue;    
    $def = array(   "0" => 'صفر',"1" => 'واحد',"2" => 'اثنان',"3" => 'ثلاث',"4" => 'اربع',"5" => 'خمس',"6" => 'ست',"7" => 'سبع',"8" => 'ثماني',"9" => 'تسع',"10" => 'عشر',"11" => 'أحد عشر',"12" => 'اثنا عشر',"100" => 'مائة',"200" => 'مئتان',"1000" => 'ألف',"2000" => 'ألفين',"1000000" => 'مليون',"2000000" => 'مليونان');
    if(isset($def[$numberValue])) {
        if($numberValue < 11 && $numberValue > 2){
            $textResult .= $def[$numberValue].'ة';
        }
        else{
            $textResult .= $def[$numberValue];
        }
    }
    else{
        $tensCheck = $numberValue%10;
        $numberValue = "$numberValue";
        
        for($x = strlen($numberValue); $x > 0; $x--){
            $places[$x] = $numberValue[strlen($numberValue)-$x];
        }
        switch(count($places)){
            case 2: // 2 numbers
            case 1: // or 1 number
            {
                $textResult .= ($places[1] != 0) ? $def[$places[1]].(($places[1] > 2 || $places[2] == 1) ? 'ة' : '').(($places[2] != 1) ? ' و' : ' ') : '';
                $textResult .= (($places[2] > 2) ? $def[$places[2]].'ون' : $def[10].(($places[2] != 2) ? '' : 'ون'));                
            }
            break;
            case 3: // 3 numbers
            {
                $lastTwo = (int) $places[2].$places[1];
                $textResult .= ($places[3] > 2) ? $def[$places[3]].' '.$def[100] : $def[(int) $places[3]."00"];
                if($lastTwo != 0){
                    $textResult .= ' و'.numtotext($lastTwo);
                }
            }
            break; 
            case 4: // 4 numbrs
            {
                $lastThree = (int) $places[3].$places[2].$places[1];
                $textResult .= ($places[4] > 2) ? $def[$places[4]].'ة الاف' : $def[(int) $places[4]."000"];
                if($lastThree != 0){
                    $textResult .= ' و'.numtotext($lastThree);
                }
            }
            break;
            case 5: // 5 numbers
            {    
                $lastThree = (int) $places[3].$places[2].$places[1];
                $textResult .= numtotext((int) $places[5].$places[4]).((((int) $places[5].$places[4]) != 10) ? ' الفاً' : ' الاف');
                if($lastThree != 0){
                    $textResult .= ' و'.numtotext($lastThree);
                }
            }
            break;
            case 6: // 6 numbers
            {    
                $lastThree = (int) $places[3].$places[2].$places[1];
                $textResult .= numtotext((int) $places[6].$places[5].$places[4]).((((int) $places[5].$places[4]) != 10) ? ' الفاً' : ' الاف');
                if($lastThree != 0){
                    $textResult .= ' و'.numtotext($lastThree);
                }
            }
            break;
            case 7: // 7 numbers 1 mill
            {    
                $textResult .= ($places[7] > 2) ? $def[$places[7]].' ملايين' : $def[(int) $places[7]."000000"];
                $textResult .= ' و';
                $textResult .= numtotext((int) $places[6].$places[5].$places[4].$places[3].$places[2].$places[1]);
            }
            break;
            case 8: // 8 numbers 10 mill
            case 9: // 9 numbers 100 mill
            {    
                $places[9] = (isset($places[9])) ? $places[9] : '';
                $firstThree = (int) $places[9].$places[8].$places[7];
                $textResult .=     numtotext($firstThree);
                $textResult .=    ($firstThree < 11) ? ' ملايين ' : ' مليونا ';
                if(((int) $places[6].$places[5].$places[4].$places[3].$places[2].$places[1]) != 0){
                    $textResult .= ' و';
                    $textResult .=    numtotext((int) $places[6].$places[5].$places[4].$places[3].$places[2].$places[1]);
                }
            }
            break;
            default:
            {
                $textResult = '!';
            }
        }

    }
    return $textResult;
}

$system = new System();

$statement_type=array(
	1=>array("ايصال قبض نقدية","Payer","استلمنا من السادة"),
	2=>array("ايصال دفع نقدية","Payee","ادفعوا لأمر السادة"),
);

$statement=false;
if(isset($_GET['id'])){
	$_GET['id']=(int)$_GET['id'];
	$r=$sql->query("
		SELECT 
			acm_id,
			CONCAT_WS(' ',COALESCE(_editor.usr_firstname,''),IF(NULLIF(_editor.usr_lastname, '') IS NULL, NULL, _editor.usr_lastname)) AS _usr_editor_name,
			UNIX_TIMESTAMP(acm_ctime) AS acm_ctime,acm_type,acm_beneficial,acm_comments,acm_realvalue,acm_realcurrency,
			cur_name,cur_symbol,atm_account_id,_merge.comp_id,acm_reference
		FROM 
			acc_main
				LEFT JOIN users AS _editor ON _editor.usr_id=acm_editor_id
				JOIN currencies ON cur_id=acm_realcurrency
				JOIN (
					SELECT 
						comp_id,atm_account_id,atm_main,atm_dir
					FROM
						`acc_accounts` 
							JOIN acc_temp ON atm_account_id = prt_id
							JOIN companies ON prt_id
					) AS _merge ON _merge.atm_main = acm_id AND _merge.atm_dir=IF(acm_type=1,1,0)
		WHERE
			acm_rejected=0 AND acm_id={$_GET['id']}
		");

	if($r && $row=$sql->fetch_assoc($r)){
		$statement=$row;
	}
}

if($statement){?>

<style type="text/css">
	@font-face {
		font-family: 'Urd';
		src:url('<?= $_SERVER['HTTP_SYSTEM_ROOT'];?>static/fonts/UrdType.ttf');
		src:url('<?= $_SERVER['HTTP_SYSTEM_ROOT'];?>static/fonts/UrdType.ttf') format('truetype');
		font-weight: normal;
		font-style: normal;
	}
	
	@font-face {
		font-family: 'ge_ss_two';
		src:url('<?= $_SERVER['HTTP_SYSTEM_ROOT'];?>static/fonts/ge-ss-two-bold.otf');
		font-weight: bold;
		font-style: normal;
	}
	@font-face {
		font-family: 'ge_ss_two';
		src:url('<?= $_SERVER['HTTP_SYSTEM_ROOT'];?>static/fonts/ge-ss-two-light.otf');
		font-weight: lighter;
		font-style: normal;
	}
	
	
	
	.o-flex {
		display: flex;
		align-items: center;
 		width: 100%;
	}
	.o-flex.right{
		flex-direction: row-reverse;
	}
	.o-flex.top{
		align-items: flex-start;
	}
	
	
	.o-title{
		text-align: right;
		font-size: 1em;
		color:#06c;
		font-family: ge_ss_two;
		padding: 4px 10px;
		min-width: 120px;
	}
	.o-content{
		font-family: Tahoma;
		text-align: right;
		padding: 4px 20px;
		background-color: #eee;
		border-radius: 4px;
	}
	.o-content.fill{
		flex:1;
	}
</style>

<table cellpadding="3" cellspacing="0" width="100%" style="border-collapse: collapse;font-size: 10pt;">
	<tbody>
		<tr>
			<td style="height:100px">
				<div class="o-flex right">
					<div style="flex: 1;text-align: right;font-size: 2em;color:#111;font-family: ge_ss_two;white-space: nowrap;">
						<?php echo $statement_type[$statement['acm_type']][0];?>
					</div>
					
					<div><?php if(!is_null($USER->company)){?>
						<img height="50" src="<?= $_SERVER['HTTP_SYSTEM_ROOT'];?>download/?id=<?= $USER->company->logo;?>&pr=t" />
					<?php }?></div>
				</div>
			</td>
		</tr>
		<tr>
			<td>
				<div class="o-flex right">
					<div class="o-title">التاريــــــــــــــــــــــــــخ</div>
					<div class="o-content"><?php echo date("Y-m-d",$statement['acm_ctime']);?></div>
					<div style="flex:1;text-align: center;"><img src="<?=$_SERVER['HTTP_SYSTEM_ROOT'].$tables->pagefile_info(15,null,"directory")."/?c=".$system->TranslatePrefix(13,$statement['acm_id'])."&f=1&t=20";?>" /></div>
					<div class="o-title" style="min-width:auto;">رقم</div>
					<div class="o-content"><?php echo $system->TranslatePrefix(13,$statement['acm_id']);?></div>
					
				</div>
			</td>
		</tr>
		
		<tr>
			<td>
				<div class="o-flex right">
					<div class="o-title"><?php echo $statement_type[$statement['acm_type']][2];?></div>
					<div class="o-content fill"><?php echo $statement['acm_beneficial'];?></div>
				</div>
			</td>
		</tr>
		
		<tr>
			<td>
				<div class="o-flex right">
					<div class="o-title">مبــــــــلغاً وقـــــدره</div>
					<div class="o-content fill"><?php echo number_format(abs($statement['acm_realvalue']),2,".",",");?><span></div>
					<div class="o-title" style="min-width: auto"><?= $statement['cur_symbol'];?></div>
					<div class="o-title" style="min-width: auto">نقداً / شيك رقم</div>
					<div class="o-content" style="min-width:130px;"><?= is_null($statement['acm_reference'])?"&nbsp;":$statement['acm_reference'];?></div>
					
				</div>
			</td>
		</tr>
		
		<tr>
			<td>
				<div class="o-flex right">
					<div class="o-title">&nbsp;</div>
					<div class="o-content fill" style="border-radius:0px 4px 4px 0px"><?php echo "فقط ".numtotext($statement['acm_realvalue']);?>&nbsp;لا غير</div>
					<div class="o-content" style="border-radius:4px 0px 0px 4px"><?= $statement['cur_name'];?></div>
				</div>
			</td>
		</tr>
		
		<tr>
			<td>
				<div class="o-flex right">
					<div class="o-title">وذلك لقـــــــــــــــــاء</div>
					<div class="o-content fill"><?php echo is_null($statement['acm_comments'])?"&nbsp;":nl2br($statement['acm_comments']);?></div>
				</div>
			</td>
		</tr>
		
		<tr>
			<td>
			</td>
		</tr>

		<tr>
			<td>
				<div class="o-flex right">
					<div class="o-title">المســـــــــــــــــــتلم</div>
					<div class="o-content"><?php echo $statement['acm_type']==1?$statement['_usr_editor_name']:$statement['acm_beneficial'];?></div>
					
					<div style="flex: 1;"></div>
					<div class="o-title">رقم الحساب</div>
					<div class="o-content"><?php echo $system->paddingPrefix(10,$statement['comp_id'])."-".$system->paddingPrefix(12,$statement['atm_account_id'])."-".$system->paddingPrefix(14, $USER->account->currency->id);?></div>
				</div>
			</td>
		</tr>
		<tr>
			<td>
				<div class="o-flex right top">
					<div class="o-title">التوقــــــــــــــــــــــيع</div>
					<div class="o-content" style="width:20%;height:1cm">&nbsp;</div>
				</div>
			</td>
		</tr>
		
		<tr>
			<td>
				<div class="o-flex right">
					<div class="o-title" style="flex:1;text-align:center;color: #555;">لا تعتمد الايصالات التي لا تحمل ختم الشركة وتوقيع منظم الايصال</div>
				</div>
			</td>
		</tr>
		
	</tbody>
</table>

<?php }else{?>
<script>
	window.onload=function(){
		alert("Invalid statement ID");
	};
</script>
<?php }?>