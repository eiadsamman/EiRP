<?php
class Log extends SQL {
	private $_json=null;
	private $_quiet=false;
	private $_op=array(
		11=>"add-employee",
		12=>"suspend-employee",
		13=>"unsuspend-employee",
		14=>"edit-employee",
		15=>"add-employee-photo",
		16=>"remove-employee-photo",
		90=>"add-management-operation",
		91=>"edit-management-operation",
		92=>"delete-management-operation",
		
		20=>"",
		21=>"",
		22=>"",
		23=>"accounting-transaction-edit",
		24=>"accounting-currency-rates-edit",
		
		
	
	);
	function __construct() {
		
	}
	public function add($user,$operation,$related_id,$trd_id){
		if(parent::$_sql_success!==true){return false;}
		$related_id=(int)$related_id;
		$operation=(int)$operation;
		$trd_id=(int)$trd_id;
		if($this->query("INSERT INTO log (log_usr_id,log_op,log_rel_id,log_date,log_trd_id) VALUES ($user,$operation,$related_id,NOW(),$trd_id);")){
			return true;
		}
		return false;
	}
	
	public function fetch_log($operation){
		$output=array();
		$operation=(int)$operation;
		$r=$this->query("SELECT log_usr_id,log_rel_id,log_date,log_trd_id,log_op FROM log WHERE log_op=23");
		echo $this->error();
		if($r){
			while($row=$this->fetch_assoc($r)){
				$output[]=array(
					"operation"=>$row['log_op'],
					"usr_id"=>$row['log_usr_id'],
					"rel_id"=>$row['log_rel_id'],
					"date"=>$row['log_date'],
					"trd_id"=>$row['log_trd_id'],
					
				);
			}
		}
		return $output;
	}
	
	
}
?>