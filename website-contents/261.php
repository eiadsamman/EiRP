<?php
use System\Individual\Attendance\Registration;
include_once("admin/class/attendance.php");
$att=new Attendance();
$loc = $att->DefaultCheckInternalAccounts($USER->company->id);


class APIException extends Exception {
    public function JSON(){
        $arr = Array(
            "errno"=>$this->getCode(),
            "error"=>$this->getMessage()
            );
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    }
}
 


class API extends App{
    protected $result;
    protected $key;
    
    function __construct(){
       
        $arh = apache_request_headers();
        
        //if(!isset($arh['User-Agent']) || !preg_match("/insomnia|express/i", $arh['User-Agent'])){
        //   throw new APIException("Invalid auth key", 98);
        //}
        
        if(!isset($arh['Auth']) || $arh['Auth'] !="583b460b436f4e14d6bcbaf865f815d6"){
            //throw new APIException("Invalid auth key", 99);
        }
        header('Content-Type: application/json; charset=utf-8');
        $this->result=array();
        $this->result['error']=0;
    }
    public function Respone(): string{
        return json_encode($this->result, JSON_UNESCAPED_UNICODE);
    }
    
}

class APIAttendance extends API{ 
    private $employee_id;
    private $target_id;
    
    function __construct(){
        parent::__construct();
        $this->target_id=null;
    }
    
    public function UserCheck($emp_id): bool{
        if((int)$emp_id==0){
            $this->result['error'] = 22002; 
            throw new APIException("Invalid employee ID", $this->result['error']);
        }
     	$query = App::$sql->query("SELECT usr_firstname,usr_id, usr_lastname FROM users WHERE usr_id = ".(int)$emp_id.";");
		if($query && $row=App::$sql->fetch_assoc($query)){
		    $this->employee_id = (int)$emp_id;
		    $this->result['id'] = $row['usr_id'];
		    $this->result['name'] = $row['usr_firstname']." ".$row['usr_lastname'];
		    return true;
		}else{
		    $this->result['error']=22002;
		    throw new APIException("Invalid employee ID", $this->result['error']);
		}
    }
    
    public function TargetCheck($tr_id): bool{
        
        if((int)$tr_id==0){
            $this->result['error'] = 22011;
            throw new APIException("Invalid target ID", $this->result['error']);
        }
     	$query = App::$sql->query("SELECT prtlbr_id, prtlbr_prt_id, prtlbr_name FROM partitionlabour WHERE prtlbr_prt_id = ".(int)$tr_id." AND prtlbr_op=2;");
		if($query && $row=App::$sql->fetch_assoc($query)){
		    $this->target_id = (int)$tr_id;
		    $this->result['target'] = $row['prtlbr_name']; 
		    
		    return true;
		}else{
		    $this->result['error']=22012;
		    throw new APIException("Invalid target ID", $this->result['error']);
		}
    }
    
    public function RegisterAttendance(): bool{
       
        if( is_null($this->target_id)  ||  is_null($this->employee_id)){
            return false;
        }

        
        try{
		    $att = new Registration($app);
		    $att->load($this->employee_id);
		
		    $ratt 	= $att->CheckIn($this->target_id);
    		if($ratt){
    		    $this->result['error']=0;
    			return true;
    		}else{
    		    $this->result['error']=22004;
    		    throw new APIException("Attendance registering failed", $this->result['error']);
    		}
		}catch(Exception $e){
		    $this->result['error']=$e->getCode();
		    throw new APIException($e->getMessage(), $this->result['error']);
		}
    }
}

if(isset($_GET['type']) && $_GET['type']=="attn"){
    try{
        $api = new APIAttendance();
        try{
            if($api->UserCheck($_GET['id'])){
                if(isset($_GET['target']) && $api->TargetCheck($_GET['target'])){
                    if($api->RegisterAttendance()){
                         echo $api->Respone();
                    }
                }else{
                    if($api->RegisterAttendance()){
                         echo $api->Respone();
                    }
                }
            }
        }catch(APIException $e) {
            $e->JSON();
        }
    }catch(APIException $eInvalidAuth){
        $eInvalidAuth->JSON();
    }
}








?>