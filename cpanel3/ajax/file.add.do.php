<?php
	include_once "../include/header.php";
	$json_output=new CPANEL_JSON();
	function checkParent($id){
		global $sql;
		if($id==0){
			 return true;
		}else{
			$r=$sql->query("SELECT trd_id FROM pagefile WHERE trd_id='$id';");
			if($sql->num_rows($r)>0){
				return true;
			}
		}
		return false;
	}
	function checkDirectory($dir,$exclude=null){
		global $sql;
		if(preg_match("$\\\\|\:|\*|\?|\"|\#|\<|\>\|\|$",$dir) || strpos($dir,"//")!==false || trim($dir)==""){
			return 2;
		}else{
			$r=$sql->query("SELECT trd_id FROM pagefile WHERE trd_directory='{$_POST['__page-directory']}' ".($exclude!=null?" AND trd_id!=$exclude ":"").";");
			if($sql->num_rows($r)>0){
				return 4;
			}
		}
		return true;
	}
	function applyLanguage($arrSet,$trd_id,$reset=false){
		global $__special,$sql;
		$sql->autocommit(false);
		$sql->query("DELETE FROM pagefile_language WHERE pfl_trd_id=$trd_id;");
		$trd_id=(int)$trd_id;
		$q="INSERT INTO pagefile_language (pfl_trd_id,pfl_lng_id,pfl_value) VALUES ";
		$smart="";
		
		foreach($arrSet as $nameK=>$nameV){
			if(trim($nameV)!=""){
				$nameV=$sql->escape(strtr($nameV, $__special));
				$q.=$smart."($trd_id,".((int)$nameK).",'$nameV')";
				$smart=",";
			}
		}
		$r= $sql->query($q);
		if($r){
			$sql->commit();
		}else{
			$sql->rollback();
		}
		return $r;
	}
	
	/*Start*/
	if($_POST['__method']=='add' || $_POST['__method']=='edit'){
		if(!checkParent((int)$_POST['__parent'])){echo '1';exit();}
		
		$_POST['__page-directory']=preg_replace("#(/)\\1+#", "$1", $_POST['__page-directory']);
		$_POST['__page-directory']=trim(str_replace(" ","_",rtrim($_POST['__page-directory'],"/")));
		
		$_POST['__page-header']=isset($_POST['__page-header'])?(int)$_POST['__page-header']:0;
		$_POST['__page-contents']=isset($_POST['__page-contents'])?(int)$_POST['__page-contents']:10;
		
		$_POST['__page-loader']=isset($_POST['__page-loader']) && trim($_POST['__page-loader'])!=""?"'".addslashes($_POST['__page-loader'])."'":"NULL";
		$_POST['__page-visible']=isset($_POST['__page-visible'])?1:0;
		$_POST['__page-active']=isset($_POST['__page-active'])?1:0;
		$_POST['__page-forward']=isset($_POST['__page-forward']) && (int)$_POST['__page-forward']!=0?(int)$_POST['__page-forward']:"NULL";
		
		
		$_POST['__page-css']=isset($_POST['__page-css'])?trim(addslashes($_POST['__page-css'])):"";$_POST['__page-css']=$_POST['__page-css']==""?"NULL":"'".$_POST['__page-css']."'";
		$_POST['__page-js']=isset($_POST['__page-js'])?trim(addslashes($_POST['__page-js'])):"";$_POST['__page-js']=$_POST['__page-js']==""?"NULL":"'".$_POST['__page-js']."'";
		$_POST['__page-param']=isset($_POST['__page-param'])?trim(addslashes($_POST['__page-param'])):"";$_POST['__page-param']=$_POST['__page-param']==""?"NULL":"'".$_POST['__page-param']."'";
		


		/*Check folder*/
		$chkFolder=checkDirectory($_POST['__page-directory'],($_POST['__method']=='add'?null:$_POST['__parent']));
		if($chkFolder!==true){
			$json_output->output(false,"Directory checking failed or already exists");
		}
		
		
		$customID=($_POST['__method']=='add'?"NULL":(int)$_POST['__parent']);
		$q=sprintf("
			INSERT INTO pagefile 
			(trd_id,trd_directory,trd_visible,trd_enable,trd_parent,trd_header,trd_loader,trd_forward,trd_css,trd_js) VALUES 
			(%1\$s,%2\$s,%3\$d,%4\$d,%5\$d,%6\$d,%7\$s,%8\$d,%9\$s,%10\$s) 
			ON DUPLICATE KEY UPDATE trd_directory=%2\$s,trd_visible=%3\$d,trd_enable=%4\$d,trd_header=%6\$d,trd_loader=%7\$s,trd_forward=%8\$s,
			trd_css=%9\$s,trd_js=%10\$s,trd_param=%11\$s
			;",
			$customID,
			"'{$_POST['__page-directory']}'",
			"{$_POST['__page-visible']}",
			"{$_POST['__page-active']}",
			"{$_POST['__parent']}",
			($_POST['__page-header']+$_POST['__page-contents']),
			"{$_POST['__page-loader']}",
			"{$_POST['__page-forward']}",
			"{$_POST['__page-css']}",
			"{$_POST['__page-js']}",
			"{$_POST['__page-param']}"
			
		);
		
		$sql->autocommit(false);
		$r=$sql->query($q);
		
		if($r){
			$id=0;
			if($_POST['__method']=='add'){
				$id=$sql->insert_id();
			}else{
				$id=(int)$_POST['__parent'];
			}
			if($id!=0){
				$result=applyLanguage($_POST['__page-name'],$id,(isset($_POST['__method']) && $_POST['__method']=='add'?false:true));
				if(!$result){
					$sql->rollback();
					$json_output->output(false,"Appending page name failed, one field at least is required");
					exit;
				}
				//Remove all previous permissions
				if(!$sql->query("DELETE FROM pagefile_permissions WHERE pfp_trd_id=$id")){
					$sql->rollback();
					$json_output->output(false,"Request failed, permissions fallback failed");
					exit;
				}
				if(isset($_POST['__page-privileges'])){
					$test = "";
					foreach($_POST['__page-privileges'] as $prevk=>$prevv){
						$perstr=(isset($_POST['__page-privileges'][$prevk]['r'])?"1":"0").
							(isset($_POST['__page-privileges'][$prevk]['a'])?"1":"0").
							(isset($_POST['__page-privileges'][$prevk]['e'])?"1":"0").
							(isset($_POST['__page-privileges'][$prevk]['d'])?"1":"0");
							
						$per=bindec($perstr);
						if(!$sql->query("INSERT INTO pagefile_permissions (pfp_trd_id,pfp_per_id,pfp_value) VALUES (
							".((int)$id).",".((int)$prevk).",".((int)$per).");")){
							$json_output->output(false,"Appending permissions failed");
							exit;
						}
					}
				}
				$sql->commit();
				if($_POST['__method']=='edit'){
					$json_output->output(true,"Pagefile edited successfully",null,
						array(
							"directory"=>"{$_POST['__page-directory']}",
							"line"=>isset($_POST['line']) && $_POST['line']=="1"?"1":"0"
						));
					exit;
				}else{
					$json_output->output(true,"Pagefile added successfully",null,array("directory"=>"{$_POST['__page-directory']}"));
					exit;
				}
			}
		}else{
			$sql->rollback();
			$json_output->output(false,"Adding pagefile failed");
			exit;
		}
	}
?>