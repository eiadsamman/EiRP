<?php
include_once("admin/class/Template/class.template.build.php");
use Template\TemplateBuild;


class AttachDeleteException extends Exception{
	public function errorPlot() {
		return $this->getMessage();
	}
}
class AttachDetachException extends Exception{
	public function errorPlot() {
		return $this->getMessage();
	}
}

class AttachLib extends SQL{
	
	public function delete($attach_id){
		$up_id=(int)$attach_id;
		$this->autocommit(false);
		$r=$this->query("DELETE FROM uploads WHERE up_id=$up_id;");
		if($r){
			$dr=true;
			try{
				if(file_exists( $_SERVER['FILE_SYSTEM_ROOT'] . "uploads/" . $up_id ))
					$dr &= @unlink($_SERVER['FILE_SYSTEM_ROOT'] . "uploads/" . $up_id);
				if(file_exists( $_SERVER['FILE_SYSTEM_ROOT'] . "uploads/" . $up_id."_v" ))
					$dr &= @unlink($_SERVER['FILE_SYSTEM_ROOT'] . "uploads/" . $up_id."_v");
				if(file_exists( $_SERVER['FILE_SYSTEM_ROOT'] . "uploads/" . $up_id."_t" ))
					$dr &= @unlink($_SERVER['FILE_SYSTEM_ROOT'] . "uploads/" . $up_id."_t");
			}catch(Exception $e){
			}
			if($dr){
				$this->commit();
				return true;
			}else{
				$this->rollback();
				throw new AttachDeleteException("Deleteing attachment failed", 1);
			}
		}else{
			throw new AttachDeleteException("Deleteing attachment failed", 2);
		}
	}
	
	public function detach($attach_id){
		$up_id=(int)$attach_id;
		$r=$this->query("UPDATE uploads SET up_rel = NULL ,up_active = 0 WHERE up_id=$up_id;");
		if($r){
			return true;
		}else{
			throw new AttachDetachException("Deleteing attachment failed", 1);
		}
	}
	
	public function sqlref(){
		
	}
	
}


?>