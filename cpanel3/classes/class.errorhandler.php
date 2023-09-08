<?php
class ErrorHandler {
	private $_log_error;
	private $_log_error_file;
	public function __construct(){
		$this->_log_error=true;
		
		if(func_num_args()==2){
			if(is_bool(func_get_arg(0)) && is_string(func_get_arg(1))){
				$this->_log_error=func_get_arg(0);
				$this->_log_error_file=(func_get_arg(1));
			}
		}
		set_error_handler(array(&$this,"userErrorHandler"));
	}
	public function trigger($errmsg,$display=true,$log=true,$filename=NULL,$linenum=NULL){
		$filename=(is_null($filename)?"Null":$filename);
		$linenum=(is_null($linenum)?"NaN":(int)($linenum));
		$unique=uniqid('ERR');
		$err  = "<code dir=\"ltr\" style=\"color:#e03;font-family:lucida console;display:inline-block;margin:2px;border:solid 1px #f9a;padding:1px 3px;text-align:left;\">";
		$err .= "<b>".$linenum." @".($filename)."</b>&nbsp;<span style=\"color:#333\">".$errmsg."</span>";
		$err .= "</code>";
		if($display===true){
			echo $err;
		}
		if($log===true && $this->_log_error){
			error_log("Triggered\t".date("Y-m-d H:i:s (T)")."\t".$filename."\t@". $linenum ."\t{$errmsg}\r\n",3,($this->_log_error_file));
		}
	}

	public final function userErrorHandler ($errno, $errmsg, $filename, $linenum, $vars=array()) {
		$errortype = array (1   =>  "Error",2   =>  "Warning",4   =>  "Parsing Error",8   =>  "Notice",16  =>  "Core Error",32  =>  "Core Warning",64  =>  "Compile Error",128 =>  "Compile Warning",256 =>  "User Error",512 =>  "User Warning",1024=>  "User Notice");
		$user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE);
		$unique=uniqid('ERR');
		$err  = "<code dir=\"ltr\" style=\"color:#e03;font-family:lucida console;display:inline-block;margin:2px;border:solid 1px #f9a;padding:1px 3px;text-align:left;\">";
		$err .= "<b>".$linenum." @".($filename)."</b>&nbsp;<span style=\"color:#333\">".$errmsg."</span>";
		if (in_array($errno, $user_errors)){
			$err.=" <b style=\"color:#06c;cursor:pointer;text-align:left;\" onClick=\"getElementById('$unique').style.display='block';\">[+]</b>";
			//wddx_serialize_value($vars,"Variables");
			$arrTemp=array();
			foreach($vars as $vark=>$varv){
				if(!is_object($varv) && !in_array( $vark,array("_SERVER","_REQUEST","_ENV","c__pagefolder_info","c__settings","c__tableid"))){
					$arrTemp[$vark]=$varv;
				}
			}
			$err .='<pre dir="ltr" style="display:none;background-color:#fff;padding:5px;border:solid 1px #f9a;text-align:left;" id="'.$unique.'">'.print_r($arrTemp,true).'</pre>';
		}
		$err .= "</code>";
		
		if($this->_log_error){
			if(!isset($errortype[$errno])){
				$errortype[$errno]="Unknow";
			}
			error_log($errortype[$errno]."\t".date("Y-m-d H:i:s (T)")."\t".$filename."\t@". $linenum ."\t{$errmsg}\r\n",3,($this->_log_error_file));
		}
	}
}
?>