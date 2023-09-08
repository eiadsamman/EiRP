<?php
class Location extends SQL{
	private $_location_array=null;
	public function location_build($id,$root=true,$current=true,$fields="trd_directory,pfl_value",$lang=1){
		if(parent::$_sql_success!==true){return false;}
		$tempid=$id;
		$this->_location_array=array();
		
		$maxExecution=0;
		$arrFT=explode(",",$fields);
		if(sizeof($arrFT)>0){
			
			$lang=(int)$lang;
			$arrF=array();$cnt=0;$query="";
			foreach($arrFT as $f){
				$cnt++;
				$arrF["val".$cnt]=$f;
				$query.=",`$f` as val".$cnt;
			}
			do{
				$maxExecution++;
				if($r=$this->query("
					SELECT 
						trd_id,trd_parent $query 
					FROM 
						pagefile 
							LEFT JOIN pagefile_language ON trd_id=pfl_trd_id AND pfl_lng_id=$lang 
					WHERE 
						trd_id='$id';")){
					if($row=$this->fetch_assoc($r)){
						if(($row['trd_id']==$tempid) && $current==true ){
							$this->_location_array[$row['trd_id']]=array();
							foreach($arrF as $valK=>$valV){array_push($this->_location_array[$row['trd_id']],$row[$valK]);}
						}elseif($row['trd_id']!=$tempid){
							$this->_location_array[$row['trd_id']]=array();
							foreach($arrF as $valK=>$valV){array_push($this->_location_array[$row['trd_id']],$row[$valK]);}
						}
						
						$id=$row['trd_parent'];
					}else{break;}
				}else{break;}
			}while($this->num_rows($r)>0 && $maxExecution<99);
			$this->_location_array=array_reverse($this->_location_array,true);
		}
		return $this->location_trace();
	}
	public function location_trace(){
		if(!is_null($this->_location_array)){
			return $this->_location_array;
		}else{
			return false;
		}
	}
}
?>