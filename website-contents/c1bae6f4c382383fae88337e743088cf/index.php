<?php
if(isset($_GET['normal'])){
	@unlink($_SERVER['FILE_SYSTEM_ROOT']."c1bae6f4c382383fae88337e743088cf");
	header("Location: {$app->http_root}");
	exit;
}
?><html>
<head></head>
<body style="font-family: verdana;font-size:0.7em;">
<?php
$arr_results=array();
$arr_tables=array(
	"h00"=>array("-","System Fincancial Configurations"),
	
	"t06"=>array("Business fields","business_field","truncate",100),
	"t09"=>array("Companies","companies","truncate",1000),
	"t02"=>array("Accounts category groups","acc_categorygroups","truncate",1000),
	"t01"=>array("Accounts categories","acc_categories","truncate",10000),
	"t26"=>array("Accounts type","acc_accounttype","truncate",100),
	"t25"=>array("Accounts","acc_accounts","truncate",1000),
	"t03"=>array("Fincancial statements","acc_main","truncate",100000),
	"t05"=>array("Fincancial statements records","acc_temp","truncate",100000),
	"t04"=>array("Account default operations","acc_predefines","truncate",0),
	"t14"=>array("Invoices","invoices","truncate",100000),
	

	"h01"=>array("-","System Users and Labour Configurations"),
	"t28"=>array("Labours","labour","truncate",10000,array("INSERT INTO labour (lbr_id,lbr_registerdate,lbr_payment_method,lbr_workingtimes) VALUES (1,'".date("Y-m-d")."',0,0);")),
	"t29"=>array("Users","users","truncate",10000,array("INSERT INTO users (usr_id,usr_username,usr_password,usr_firstname,usr_privileges,usr_activate) VALUES (1,'admin','admin','Admin',1,1);")),
	"t30"=>array("Pagefile permissions","pagefile_permissions","truncate",0,array("INSERT INTO pagefile_permissions (pfp_trd_id,pfp_per_id,pfp_value) SELECT trd_id,1,9 FROM pagefile;")), //Full permission for Administrator[1]
	"t31"=>array("Users sessions","users_sessions","truncate",0),
	"t32"=>array("User companies","user_company","truncate",0),
	"t33"=>array("User accounts","user_partition","truncate",0),
	"t34"=>array("User settings","user_settings","truncate",0),
	"t27"=>array("Uploads","uploads","truncate",0),
	
	
	"h02"=>array("-","System Calendar Configurations"),
	"t07"=>array("Calendar","calendar","truncate",1000),
	"t08"=>array("Calendar weekends","calendar_weekends","update",array("UPDATE calendar_weekends SET cwk_status=1;","UPDATE calendar_weekends SET cwk_status=1 WHERE cwk_id=7;")),
	
	
	"h03"=>array("-","System Currencies Configurations"),
	"t10"=>array("Currencies","currencies","truncate",1000,array("INSERT INTO currencies (cur_name,cur_shortname,cur_symbol,cur_default) VALUES ('United States dollar','USD','$',1);")),
	"t11"=>array("Currency exchange","currency_exchange","truncate",0),
	"t12"=>array("Currency exchange log","currency_exchange_log","truncate",0),
	"t13"=>array("Currency exchange log values","currency_exchange_log_values","truncate",0),
	
	
	"h04"=>array("-","System Labour Configurations"),
	"t15"=>array("Labour absence requests","labour_absence_request","truncate",10000),
	"t16"=>array("Labour ratings","labour_rating","truncate",0),
	"t17"=>array("Labour residentail","labour_residentail","truncate",1000),
	"t18"=>array("Labour section","labour_section","truncate",1000),
	"t19"=>array("Labour shifts","labour_shifts","truncate",100),
	"t20"=>array("Labour attendance tracking","labour_track","truncate",0),
	"t21"=>array("Labour transportation","labour_transportation","truncate",1000),
	"t22"=>array("Labour working field","labour_type","truncate",1000),
	"t23"=>array("Labour salary type","labour_type_salary","truncate",0),
	
	"h05"=>array("-","System Variables"),
	"t24"=>array("Log","log","truncate",0),
	
);

if(is_array($_POST) && sizeof($_POST)>0){
	foreach($_POST as $k=>$v){
		if(isset($arr_tables[$k][2]) && $arr_tables[$k][2]=="truncate"){
			$app->db->autocommit(false);
			$r=true;
			$r&=$app->db->query("TRUNCATE `{$arr_tables[$k][1]}`");
			if($r){
				if(isset($arr_tables[$k][3])){
					$auto_inc=(int)$arr_tables[$k][3];
					if($auto_inc>0){
						$r&=$app->db->query("ALTER TABLE `{$arr_tables[$k][1]}` AUTO_INCREMENT=$auto_inc;");
					}
				}
				if(isset($arr_tables[$k][4]) && is_array($arr_tables[$k][4])){
					foreach($arr_tables[$k][4] as $kq=>$addquery){
						$r&=$app->db->query($addquery);
					}
				}
			}
			if(!$r){
				$arr_results[$k]=false;
				$app->db->rollback();
			}else{
				$arr_results[$k]=true;
				$app->db->commit();
			}
		}elseif(isset($arr_tables[$k][2]) && $arr_tables[$k][2]=="update"){
			$app->db->autocommit(false);
			$r=true;
			if(isset($arr_tables[$k][3]) && is_array($arr_tables[$k][3])){
				foreach($arr_tables[$k][3] as $kq=>$addquery){
					$r&=$app->db->query($addquery);
				}
			}
			if(!$r){
				$arr_results[$k]=false;
				$app->db->rollback();
			}else{
				$arr_results[$k]=true;
				$app->db->commit();
			}
		}
	}
}
?>
<form action="" method="POST">
	<h1>Select database tables to initialize</h1>
	<h2>Selected database :`<?php echo $c__settings['database']['name'];?>`</h2>
<?php 
	$opened=false;
	foreach($arr_tables as $k=>$v){
		
		if($v[0]=="-"){
			if($opened){echo "</div>";}
			echo "<div style=\"border:solid 1px #999;padding:10px;margin:3px;\">";
			echo "<h3 style=\"margin-top:0px;margin-bottom:5px;\">{$v[1]}</h3>";
			$opened=true;
		}else{
			$proccessed=null;
			if(isset($arr_results[$k])){
				$proccessed=$arr_results[$k];
			}
			echo "<label style=\"padding:2px;display:inline-block;width:300px;background-color:#eee;margin:1px;\"><input name=\"$k\" type=\"checkbox\" ".(isset($_POST[$k]) && !$proccessed?" checked=\"checked\"":"")." />&nbsp;&nbsp;&nbsp;&nbsp;{$v[0]}";
			if($proccessed!=null){
				echo $proccessed?"<br/><span style=\"padding-left:10px;color:#096;\">Initialized successfully</span>":"<br/><span style=\"padding-left:10px;color:#f03;\">Initialized failed</span>";
			}else{
				echo "<br/><span style=\"padding-left:10px;color:#999;\">Pending</span>";
			}
			echo "</label>";
		}
	}
?>
</div>
<hr /><button style="padding: 10px;margin:10px;" type="submit">Reset System Database</button><br />
<a href="?normal=true">Continue to the system normaly</a>
</form>
</body>
</html>