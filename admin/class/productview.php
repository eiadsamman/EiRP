<?php
class ProductView {
	protected $unique=NULL;
	protected  $elem = array('imageList' => NULL,'thumbWidth' => NULL,'thumbHeight' => NULL,'thumbCount' => NULL,'up_dir' => NULL,'pagefolder' => NULL,'title' => NULL);
	public function __get($prop_name){if (isset($this->elem[$prop_name])) {$prop_value = $this->elem[$prop_name];return $prop_value;}}
	public function __set($prop_name, $prop_value){$this->elem[$prop_name] = $prop_value;return true;}
	public function __construct ($imageList=array(),$thumbWidth=80,$thumbHeight=90,$thumbCount=3,$pagefolder='',$title=NULL){
		$this->elem['imageList']=(is_array($imageList)?$imageList:array());
		$this->elem['thumbWidth']=(is_numeric($thumbWidth)?$thumbWidth:80);
		$this->elem['thumbHeight']=(is_numeric($thumbHeight)?$thumbHeight:90);
		$this->elem['thumbCount']=(is_numeric($thumbCount)?$thumbCount:3);
		$this->elem['pagefolder']=$pagefolder;
		$this->elem['title']=($title==NULL?'null':(trim($title)==""?"null":"'".$title."'"));
		$this->unique="pview_".uniqid();
	}
	public function jQueryOutput(){
		if(sizeof($this->elem['imageList'])<=0){return '';}
		return '
		<script type="text/javascript">
			$().ready(function(){
				$(\'.cssPrdPool.'.$this->unique.'\').productView({
				speed:\'fast\',
				thumbsNumber:'.$this->elem['thumbCount'].',	
				thumbHeight:'.$this->elem['thumbHeight'].',
				thumbWidth:'.$this->elem['thumbWidth'].',
				pageStatus:true,
				imageText:true,
				pluginTitle:'.$this->elem['title'].',
				pluginMargin:\'15px 0px 20px 20px\',
				pluginInlarge:true,
				baseurl:\'p/'.$this->elem['pagefolder'].'\'
				});
			});
		</script>';
	}
	public function pluginOutput(){
		if(sizeof($this->elem['imageList'])<=0){return '';}
		$output='';
		$output.='
		<span style="clear:both;height:0px;width:0px;"></span>
		<div class="cssPrdPool '.$this->unique.'">
			<table border="0" cellpadding="0" cellspacing="0" width="100%"><tbody>
				<tr><td align="center" style="padding-top:10px" class="pview-title"></td></tr>
				<tr><td>
					<div style="width:100%;text-align:center;padding-bottom:11px;background:url(\'static/images/loading.gif\') no-repeat 50% 50%;min-height:20px;" class="pview-lb_rigger">
						<img src="static/images/spacer.gif" border="0" />
					</div>
				</td></tr>
				<tr><td align="center" style="padding-bottom:11px" class="pview-description"><div></div></td></tr>
				<tr class="pview-thumblist">
					<td>
					<table border="0" cellpadding="0" cellspacing="0" width="100%"><tbody>
						<tr>
						<th><span class="pview-next cssPrdPrev">&nbsp;</span></th>
						<td width="100%">
							<div class="pview-thumbholder">
								<div>';
									foreach($this->elem['imageList'] as $img){
										$output.= '
										<a>
											<span href="'.$img[2].'" class="pview-thumb" '.(trim($img['name'])==""?"":'title="Volight | '.htmlentities($img['name']).'"').' '.(trim($img['text'])==""?"":'alt="'.htmlentities($img['text']).'"').' rel="'.$img[1].'" id="ims'.$img['id'].'">
												<img src="static/images/spacer.gif" border="0" '.(trim($img['name'])==""?'alt="Volight.com"':'alt="Volight | '.htmlentities($img['name']).'"').' style="background:url(\''.$img[3].'\') no-repeat center 50%;" />
											</span>
										</a>';
									}
									$output.='
								</div>
							</div>
						</td>
						<th><span class="pview-prev cssPrdNext">&nbsp;</span></th>
					</tr>
					</tbody></table>
					</td>
				</tr>
				<tr class="pview-status"><td align="center" style="padding-top:3px;font-weight:bold;font-size:0.8em"></td></tr>
			</tbody></table>';
			
			$output.= '<div style="display:none">';
			foreach($this->elem['imageList'] as $img){
				$output.= '<a class="pview-lightbox" id="jQlbims'.$img['id'].'" href="'.$img[1].'"></a>';
			}
			$output.='</div>';
		$output.= '</div>';
		return $output;
	}
}
?>