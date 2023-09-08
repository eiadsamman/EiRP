<?php
class MySession  {
	private $_mysession_name=null;
	private $_mysession_success=false;
	
	public function mysession_start($session_name){
		return session_start();
	}
}
?>