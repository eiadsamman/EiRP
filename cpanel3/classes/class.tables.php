<?php
class Tables  {
	private $_pagefile_info_array=null;
	private $_languages=null;
	private $sql;
	
	public function __construct($sql, $languages_instance){
		$this->sql = $sql;
		$this->_languages=$languages_instance;
	}
	
	private function _perpare_fetch($lang,$query,$key_output){
		
		if(!isset($lang) || is_null($lang)){
			if(false!==$this->_languages->get_current()){
				$lang=$this->_languages->get_current()['id'];
			}elseif(false!==$this->_languages->default_key_id()){
				$lang=$this->_languages->default_key_id();
			}else{
				return false;
			}
		}else{
			$lang=(int)$lang;
			if(false===($this->_languages->check_key_id($lang))){
				return false;
			}
		}
		
		$this->_pagefile_info_array=array();
		$f=false;
		if($r=$this->sql->query("
			SELECT 
				trd_id AS id, trd_directory AS directory,
				pfl_value as title, trd_keywords AS keywords, trd_description AS description, trd_parent AS parent,
				trd_css AS css, trd_js AS js, trd_date AS date,
				trd_enable AS enable, trd_visible AS visible,
				trd_attrib6 AS attrib6, trd_attrib1 AS padding, trd_attrib3 AS attrib3, trd_attrib4 AS attrib4, trd_attrib5 AS attrib5,
				trd_header AS header, trd_loader AS loader, trd_param AS param,
				forward 
			FROM 
				pagefile 
					LEFT JOIN pagefile_language ON pfl_trd_id = trd_id AND pfl_lng_id=$lang
					LEFT JOIN (SELECT trd_id AS forwardid, trd_directory AS forward FROM pagefile) AS _forwarder ON trd_forward = _forwarder.forwardid
			WHERE
				$query;")){
			if($row=$this->sql->fetch_assoc($r)){
				$f=true;
				foreach($row as $k=>$v){
					$this->_pagefile_info_array[$k]=is_null($v)?null:$v;
				}
				$hhdr=(string)$this->_pagefile_info_array['header'];
				$this->_pagefile_info_array['header']=array("html-header"=>(isset($hhdr[1])?(int)$hhdr[1]:0) , "contents"=>(isset($hhdr[0])?(int)$hhdr[0]*10:10) );
				$this->_pagefile_info_array['permissions']=array();
				if($rper=$this->sql->query("
					SELECT 
						per_id,pfp_value 
					FROM 
						permissions LEFT JOIN pagefile_permissions ON per_id=pfp_per_id AND pfp_trd_id={$row['id']};")){
					while($rowper=$this->sql->fetch_assoc($rper)){
						$this->_pagefile_info_array['permissions'][$rowper['per_id']]=is_null($rowper['pfp_value'])?0:(int)$rowper['pfp_value'];
					}
				}
			}
		}
		if($f){
			if(array_key_exists($key_output,$this->_pagefile_info_array)){
				if(!isset($this->_pagefile_info_array[$key_output])){return false;}
				if($key_output=="directory"){$this->_pagefile_info_array[$key_output]=trim($this->_pagefile_info_array[$key_output],"\\/");}
				return $this->_pagefile_info_array[$key_output];
			}else{
				$this->_pagefile_info_array['directory']=trim($this->_pagefile_info_array['directory'],"\\/");
				return $this->_pagefile_info_array;
			}
		}else{return false;}
	}
	public function pagefile_info($id,$lang=null,$key_output=null){
		return ($this->_perpare_fetch($lang," pagefile.trd_id=" . (int)$id . "" ,$key_output));
	}
	
	public function PageFromURI($directory,$lang=null,$key_output=null){
		$directory = $this->sql->escape($directory);
		return ($this->_perpare_fetch($lang," pagefile.trd_directory='$directory' ",$key_output));
	}
	
	public function Permissions(int $page_id,int $user_persmission){
		$result = new AllowedActions();
		if($rper=$this->sql->query("
			SELECT 
				per_id,pfp_value 
			FROM 
				permissions LEFT JOIN pagefile_permissions ON per_id=pfp_per_id 
			WHERE 
				pfp_trd_id=$page_id AND pfp_per_id=$user_persmission
			;")){
			if($rowper=$this->sql->fetch_assoc($rper)){
				$result->Translate(is_null($rowper['pfp_value'])?0:(int)$rowper['pfp_value']);
			}
		}
		return $result;
	}
}


class PagefileHierarchy {
	private $parentList=array();
	private $_permissions=null;
	private $_level=0;
	private $sql;
	
	private function Iterate($parent,$nest=1){
		echo "<div>";
		foreach($this->parentList[$parent] as $k=>$v){
			$hadChildren=isset($this->parentList[$k]);
			echo "<b ".($hadChildren?"class=\"nested\"":"")." style=\"padding-left:".($nest*33)."px;\">".
			($v['trd_attrib4']==null?"":"<span ".($v['trd_attrib5']!=null?"style=\"color:#{$v['trd_attrib5']}\"":"").">&#xe{$v['trd_attrib4']};</span>").
			"<a class=\"alink\" href=\"{$v['trd_directory']}\">{$v['pfl_value']}</a></b>";
			if($hadChildren){
				$this->Iterate($k,$nest+1);
			}
		}
		echo "</div>";
	}
	public function __construct($sql, $permissions=null) {
		$this->sql = $sql;
		$output=array();
		if($permissions==null){
			$this->_permissions=0;
		}else{
			$this->_permissions=(int)$permissions;
		}
		$r=$this->sql->query(
			"SELECT 
				trd_directory, trd_id, pfl_value, trd_attrib4, trd_attrib5, trd_parent
			FROM 
				pagefile 
					JOIN pagefile_language ON pfl_trd_Id=trd_id AND pfl_lng_id = 1 
					JOIN pagefile_permissions ON pfp_trd_id=trd_id AND pfp_per_id={$this->_permissions} AND pfp_value > 0
			WHERE 
				trd_enable = 1 AND trd_visible = 1 
			ORDER BY trd_parent,trd_zorder;");
		if($r){
			while($row=$this->sql->fetch_assoc($r)){
				if(!isset($this->parentList[$row['trd_parent']])){
					$this->parentList[$row['trd_parent']]=array();
				}
				$this->parentList[$row['trd_parent']][$row['trd_id']]=$row;
			}
		}
		if(isset($this->parentList[0]) && is_array($this->parentList[0])){
			foreach($this->parentList[0] as $k=>$v){
				echo "<b>".($v['trd_attrib4']==null?"":"<span ".($v['trd_attrib5']!=null?"style=\"color:#{$v['trd_attrib5']}\"":"").">&#xe{$v['trd_attrib4']};</span>")."<a class=\"alink\" href=\"{$v['trd_directory']}\">{$v['pfl_value']}</a></b>";
				if(isset($this->parentList[$k])){
					$this->Iterate($k);
				}
			}
		}
	}
}

class PagefileNode {
	private $nodelist=array();
	private $_permissions=null;
	private $pagefile_id=null;
	public $nodes=array();
	private $_level=0;
	private $sql;
	
	private function Iterate($node):bool{
		if(isset($this->nodelist[$node])){
			$this->nodes[]=$this->nodelist[$node];
			$this->Iterate($this->nodelist[$node]['trd_parent']);
			return true;
		}else{
			return false;
		}
	}
	public function ToHrefTrack():string{
		$output="";
		$del=false;
		foreach(array_reverse($this->nodes) as $k=>$v){
			if($del){
				$output.= "<span style=\"display:inline-block;padding:0px 7px\">»</span>";
			}
			if($this->pagefile_id==$v['trd_id']){
				$output.= "<span style=\"display:inline-block;\">{$v['pfl_value']}</span>";
			}else{
				$output.= "<a href=\"{$v['trd_directory']}\">{$v['pfl_value']}</a>";
			}
			$del=true;
		}
		return $output;
	}
	public function __construct($sql, $pagefile_id,$permissions=null) {
		$this->sql = $sql;
		$this->nodes=array();
		$pagefile_id=(int)$pagefile_id;
		$this->pagefile_id=$pagefile_id;
		if($permissions==null){
			$this->_permissions=0;
		}else{
			$this->_permissions=(int)$permissions;
		}
		$r=$this->sql->query("
			SELECT trd_directory,trd_id,pfl_value,trd_parent
			FROM 
				pagefile 
					JOIN pagefile_language ON pfl_trd_Id=trd_id AND pfl_lng_id = 1 
					JOIN pagefile_permissions ON pfp_trd_id=trd_id AND pfp_per_id={$this->_permissions} AND pfp_value > 0
			WHERE 
				trd_enable = 1 AND trd_visible = 1 
			ORDER BY trd_parent,trd_zorder;");
		if($r){
			while($row=$this->sql->fetch_assoc($r)){
				
				$this->nodelist[$row['trd_id']]=$row;
			}
		}
		$this->Iterate($pagefile_id);
	}
}
