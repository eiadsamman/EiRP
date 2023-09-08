<?php
$offset=isset($_POST['offset'])?(int)$_POST['offset']:0;
	
	$arr_listfixed=array(
		"id"=>isset($_POST['id']) && (int)$_POST['id']!=0?(int)$_POST['id']:null,
		"type"=>isset($_POST['type'][1]) && (int)$_POST['type'][1]!=0?(int)$_POST['type'][1]:null,
		"benifical"=>isset($_POST['benifical'][0]) && trim($_POST['benifical'][0])!=""?str_replace("'\"()\\"," ",$_POST['benifical'][0]):null,
		"benifical_t"=>isset($_POST['benifical_t'][0]) && trim($_POST['benifical_t'][0])!=""?str_replace("'\"()\\"," ",$_POST['benifical_t'][0]):null,
		"reference"=>isset($_POST['reference'][0]) && trim($_POST['reference'][0])!=""?str_replace("'\"()\\"," ",$_POST['reference'][0]):null,
		"employee"=>isset($_POST['employee'][1]) && (int)$_POST['employee'][1]!=0?(int)$_POST['employee'][1]:null,
		"editor"=>isset($_POST['editor'][1]) && (int)$_POST['editor'][1]!=0?(int)$_POST['editor'][1]:null,
		"fromdate"=>null,
		"todate"=>null,
		"month-reference"=>null,
		"display_altered"=>isset($_POST['display_altered'])?true:false,
		"filtercurrency"=>isset($_POST['filtercurrency'][1]) && (int)$_POST['filtercurrency'][1]!=0?(int)$_POST['filtercurrency'][1]:$__systemdefaultcurrency['id'],
		"strict"=>isset($_POST['strict_filter'])?" AND ":" OR ",
	);
	
	if(!isset($currency_list[$arr_listfixed['filtercurrency']])){
		$arr_listfixed['filtercurrency']=$__systemdefaultcurrency['id'];
	}
	
	if(isset($_POST['fromdate'][1]) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$_POST['fromdate'][1],$match)){
		if(checkdate($match[2],$match[3],$match[1])){
			$arr_listfixed['fromdate']=date("Y-m-d",mktime(0,0,0,$match[2],$match[3],$match[1]));
		}
	}
	if(isset($_POST['todate'][1]) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$_POST['todate'][1],$match)){
		if(checkdate($match[2],$match[3],$match[1])){
			$arr_listfixed['todate']=date("Y-m-d",mktime(0,0,0,$match[2],$match[3],$match[1]));
		}
	}
	if(isset($_POST['month-reference'][1]) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$_POST['month-reference'][1],$match)){
		if(checkdate($match[2],$match[3],$match[1])){
			$arr_listfixed['month-reference']=date("Y-m-d",mktime(0,0,0,$match[2],$match[3],$match[1]));
		}
	}
	
	
	
	
	$arr_group=array(
		'type'			=>array("active"=>false,"field"=>"acm_type","related"=>null,"cols"=>"Type","reference"=>array("acctyp_name")),
		'account'		=>array("active"=>false,"field"=>"account_id","related"=>null,"cols"=>"Account","reference"=>array("account_name")),
		'category_family'=>array("active"=>false,"field"=>"accgrp_id","related"=>null,"cols"=>"Category Family","reference"=>array("accgrp_name")),
		'category'		=>array("active"=>false,"field"=>"acccat_id","related"=>"category_family","cols"=>"Category","reference"=>array("acccat_name")),
		'year'			=>array("active"=>false,"field"=>"group_year","related"=>null,"cols"=>"Year","reference"=>array("group_year")),
		'month'			=>array("active"=>false,"field"=>"group_month","related"=>"month","cols"=>"Month","reference"=>array("group_month_name")),
		'benifical'		=>array("active"=>false,"field"=>"acm_usr_id","related"=>null,"cols"=>"Benifical","reference"=>array("acm_usr_id","benifical")),
		'benifical_t'	=>array("active"=>false,"field"=>"acm_beneficial","related"=>null,"cols"=>"Benifical","reference"=>array("acm_beneficial")),
		'reference'		=>array("active"=>false,"field"=>"acm_reference","related"=>null,"cols"=>"Reference","reference"=>array("acm_reference")),
	);
	
	$start_grouping=false;
	if(isset($_POST['group'])){
		foreach($_POST['group'] as $POST_k=>$POST_v){
			if(isset($arr_group[$POST_k]) && $POST_v){
				$arr_group[$POST_k]['active']=true;
			}
		}
		foreach($arr_group as $group_k=>$group_v){
			if($group_v['active']){
				$start_grouping=true;
			}
		}
		$arr_group=Group_list_limit_active($_POST['group'],$arr_group);
	}
	
	
	
	
	//SQL query required fields setup
	$arr_listobjects=array(
		'creditor_account'=>array("active"=>false,"active_exclude"=>false,"fields"=>array("atm_account_id"=>"","account_id"=>"","acm_id"=>"","_credit.atm_account_id"=>""),"exclude"=>array("atm_account_id"=>"","account_id"=>"","acm_id"=>"","_credit.atm_account_id"=>"")),
		'debitor_account'=>array("active"=>false,"active_exclude"=>false,"fields"=>array("atm_account_id"=>"","account_id"=>"","acm_id"=>"","_debit.atm_account_id"=>""),"exclude"=>array("atm_account_id"=>"","account_id"=>"","acm_id"=>"","_debit.atm_account_id"=>"")),
		'category'=>array("active"=>false,"active_exclude"=>false,"fields"=>array("acm_category"=>""),"exclude"=>array("acm_category"=>"")),
		'category_family'=>array("active"=>false,"active_exclude"=>false,"fields"=>array("accgrp_id"=>""),"exclude"=>array("accgrp_id"=>"")),
	);
	
	/*Prepare SQL statement query from the presented POST input*/
	foreach($arr_listobjects as $list=>$val){
		if(isset($_POST[$list]) && is_array($_POST[$list])){
			$smart="";
			$smart_exclude="";
			foreach($_POST[$list] as $k=>$v){
				if((int)$v!=0){
					if(isset($_POST[$list."_exclude"][$k])){
						$arr_listobjects[$list]['active_exclude']=true;
						foreach($arr_listobjects[$list]['fields'] as $field=>$value){
							$arr_listobjects[$list]['exclude'][$field].=$smart_exclude.$field."=".(int)$v;
						}
						$smart_exclude=" OR ";
					}else{
						$arr_listobjects[$list]['active']=true;
						foreach($arr_listobjects[$list]['fields'] as $field=>$value){
							$arr_listobjects[$list]['fields'][$field].=$smart.$field."=".(int)$v;
						}
						$smart=" OR ";
					}
				}
			}
		}
	}
	
	/*Prepare WHERE statement for the filtering query*/
	$active_combinde="";
	if($arr_listobjects['creditor_account']['active'] && $arr_listobjects['debitor_account']['active']){
		$active_combinde=" AND 
								( 
									( ".$arr_listobjects['creditor_account']['fields']['_credit.atm_account_id']." )
									{$arr_listfixed['strict']}
									( ".$arr_listobjects['debitor_account']['fields']['_debit.atm_account_id']." )
								)
								 ";
	}elseif($arr_listobjects['creditor_account']['active']){
		$active_combinde=" AND (".$arr_listobjects['creditor_account']['fields']['_credit.atm_account_id'].")";
	}elseif($arr_listobjects['debitor_account']['active']){
		$active_combinde=" AND (".$arr_listobjects['debitor_account']['fields']['_debit.atm_account_id'].")";
	}
	 
?>