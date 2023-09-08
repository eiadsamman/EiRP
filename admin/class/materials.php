<?php
class Materials extends SQL {
	public function __construct() {
		self::construct();
	}
	
	private function construct(){
		//Previously opened conncection to db? if so porcceed
		if(!is_null(self::$_sql_link)){
			if(self::check_link())
				return true;
		}
	}
	
	public function Create($param=array(
			"long_id"=>"",
			"ean_code"=>null,
			"unit"=>null,
			"name"=>null,
			"longname"=>null,
			"date"=>null,
			"type"=>null,
			"thershold"=>null,
		)){
		
		if(parent::$_sql_success!==true){return false;}
		
		
		if(preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$param['date'],$match)){
			if(!checkdate($match[2],$match[3],$match[1])){
				$_param['date']=false;
			}
		}else{$_param['date']=false;}
		
		
		$param['long_id']=addslashes(trim($param['long_id']));
		$param['name']=addslashes(trim($param['name']));
		$param['longname']=addslashes(trim($param['longname']));
		$param['ean_code']=addslashes(trim($param['ean_code']));
		$param['unit']=(int)$param['unit'];
		$param['type']=(int)$param['type'];

		if($param['part_number']==""){}
		if($param['desc']==""){}
		if($param['ean_code']==""){}
		if($param['unit']==0){}
		if($param['type']==0){}
		if($param['vendor_id']==0){}
		if(false===$_param['date']){
			//throw new Exception("Error",1);
			return false;	
		}
		
		
		
		$ins_query=sprintf("
			INSERT INTO mat_materials (
			 	mat_pn,
			 	mat_vendor_code,
			  	mat_ean,
			  	mat_unit_id,
			  	mat_description,
			  	mat_date,
			  	mat_type,
			  	mat_vendor,
			  	mat_threshold,
			  	mat_long_id
			) VALUES (
				\"%1\$s\",
				\"%2\$s\",
				\"%3\$s\",
				%4\$d,
				\"%5\$s\",
				\"%6\$s\",
				%7\$d,
				%8\$d,
				0,
				0
			);
			",
			$param["part_number"],
			$param["vendor_code"],
			$param["ean_code"],
			$param["unit"],
			$param["desc"],
			$param["date"],
			$param["type"],
			$param["vendor_id"]
		);
		
		$r=$this->query($ins_query);
		
		if($r){
			$temp=$this->insert_id();
			$rtemp=$this->query("
					UPDATE mat_materials 
						JOIN mat_materialtype ON mattyp_id=mat_type 
						JOIN companies ON comp_id=mat_vendor
						JOIN mat_unit ON mat_unit_id=unt_id 
					SET mat_long_id = CONCAT(IF(comp_sys_default=1,{$this->_matsource[1]},{$this->_matsource[0]}),mattyp_id,LPAD(mat_id,{$this->_seqpad},'0'),0)  
					WHERE mat_id=$temp;
					");
			return $temp;
		}else{
			
			return false;
		}
	}
	
	public function WOMaterials($wo_id){
		$wo_id=(int)$wo_id;
		$output=array();
		$r=$this->query("
			SELECT 
				mat_id,mat_description,mat_long_id,mat_pn,mattyp_name,mat_date,unt_name,unt_decim,comp_name,wol_qty
			FROM 
			
				mat_materials
					JOIN mat_materialtype ON mattyp_id=mat_type 
					JOIN companies ON comp_id=mat_vendor
					JOIN mat_unit ON mat_unit_id=unt_id
					JOIN mat_wo_list ON wol_item_id = mat_id AND wol_wo_id = $wo_id
			ORDER BY
				mat_bom_level
			");
		if($r){
			while($row=$this->fetch_assoc($r)){
				$output[$row['mat_id']]=$row;
			}
			return $output;
		}else{
			return false;
		}
	}
	
	
	public function Fetch($mat_id){
		$mat_id=(int)$mat_id;
		$output=array();
		$r=$this->query("
			SELECT 
				mat_id,mat_name,mat_longname,mat_long_id,mattyp_name,mat_date,unt_name,unt_decim
			FROM 
				mat_materials
					JOIN mat_materialtype ON mattyp_id=mat_mattyp_id  
					JOIN mat_unit ON mat_unt_id=unt_id
					JOIN mat_bom_schematic ON mat_sch_type_id=mat_mattyp_id 
					LEFT JOIN 
						(
							SELECT 
								CONCAT_WS(\", \", matcatgrp_name, matcat_name) AS cat_alias, matcat_id 
							FROM 
								mat_category LEFT JOIN mat_categorygroup ON matcat_matcatgrp_id = matcatgrp_id
						) AS _category ON mat_matcat_id=_category.matcat_id
			WHERE
				mat_id=$mat_id;
			");
		if($r){
			if($row=$this->fetch_assoc($r)){
				$output=$row;
			}
			return $output;
		}else{
			return false;
		}
	}
	
	
	
	public function BOMGetNodes($mat_id){
		$mat_id=(int)$mat_id;
		$output=array();
		$r=$this->query("
			SELECT 
				mat_id,mat_name,mat_long_id,mattyp_name,mat_date,unt_name,unt_decim,mat_bom_quantity
			FROM 
				mat_materials
					JOIN mat_materialtype ON mattyp_id=mat_mattyp_id 
					JOIN mat_unit ON mat_unt_id=unt_id
					JOIN mat_bom ON mat_bom_part_id=mat_id
			WHERE
				mat_bom_mat_id=$mat_id
			ORDER BY
				mat_bom_level
			;
			");
		if($r){
			while($row=$this->fetch_assoc($r)){
				$output[$row['mat_id']]=$row;
			}
			return $output;
		}else{
			return false;
		}
	}
	
}
?>