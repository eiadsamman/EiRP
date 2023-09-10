<?php 
$debug=false;
include_once "admin/class/log.php";

$cacv=0;
function fnConvOnlyNumbers($input){
	$output="";
	for($i=0;$i<strlen($input);$i++){
		if(is_numeric($input[$i]) || in_array($input[$i], array("+","\n"))){
			$output.=$input[$i];
		}
	}
	return $output;
}
function check_date($input){
	if (isset($input) && preg_match("/^([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$input,$match)){
		if(checkdate($match[2],$match[3],$match[1])){
			return true;
		}
    }
    return false;
}

if(isset($_POST['method'],$_POST['sal_workingtime'],$_POST['sal_paymethod'],$_POST['sal_job']) && $_POST['method']=="get_salary_information"){
	if(!$c__actions->edit){
		header(HTTP403);
		//$_UXXER['error'][1]="Permissions denied!";
		exit;
	}
	$_POST['sal_workingtime']=(int)$_POST['sal_workingtime'];
	$_POST['sal_paymethod']=(int)$_POST['sal_paymethod'];
	$_POST['sal_job']=(int)$_POST['sal_job'];

	$output=false;
	$r=$sql->query("SELECT lbr_typ_sal_basic_salary,lbr_typ_sal_variable,lbr_typ_sal_allowance,lbr_typ_sal_transportation FROM labour_type_salary
					WHERE lbr_typ_sal_lty_id={$_POST['sal_job']} AND lbr_typ_sal_lwt_id={$_POST['sal_workingtime']} AND lbr_typ_sal_method={$_POST['sal_paymethod']}");
	if($r){
		if($row=$sql->fetch_assoc($r)){
			$output=array(
				"basic"=>number_format($row['lbr_typ_sal_basic_salary'],2,".",""),
				"variable"=>number_format($row['lbr_typ_sal_variable'],2,".",""),
				"allowance"=>number_format($row['lbr_typ_sal_allowance'],2,".",""),
				"transportation"=>number_format($row['lbr_typ_sal_transportation'],2,".",""),
			);
		}
	}
	if($output)
		echo json_encode($output);
	else
		echo "false";
	exit;
}

if(isset($_POST['emp_method'])){
	//JSON encoded array
	$arrparser=array("result"=>true,"source"=>array());
	//Friendly output messages
	$arrerrors=array(
		"firstname"=>"*",
		"lastname"=>"*",
		"perosnal_image"=>"*",
		"gender"=>"*",
		"phone_list"=>"",
		"residence"=>"*",
		"transportation"=>"*",
		"social_number"=>"*",
		"social_id_image"=>"*",
		"edu_cert_image"=>"*",
		"shift"=>"*",
		"workingtimes"=>"*",
		"payment"=>"*",
		"jobtitle"=>"*",
		"regdate"=>"*",
		"birthdate"=>"*",
		"nationality"=>"*",
		"company"=>"*",
	);
	//Checking input for validity
	$arroutput=array(
		"result"=>true,
		"source"=>array(
			"firstname"=>(isset($_POST['firstname']) && trim($_POST['firstname']) !="" ? addslashes(trim($_POST['firstname'])) : false ),/*required*/
			"lastname"=>(isset($_POST['lastname']) && trim($_POST['lastname']) !="" ? addslashes(trim($_POST['lastname'])) : false ),/*required*/
			"perosnal_image"=>(isset($_POST['perosnal_image']) && is_array($_POST['perosnal_image']) && !empty($_POST['perosnal_image'])?$_POST['perosnal_image'] : null),/*required*/
			"gender"=>(isset($_POST['gender'][1]) && (int)$_POST['gender'][1] != 0 ? (int)$_POST['gender'][1]:false),/*required*/
			"phone_list"=>(isset($_POST['phone_list']) && trim($_POST['phone_list']) !="" ? fnConvOnlyNumbers(trim($_POST['phone_list'])) : null ),
			"residence"=>(isset($_POST['residence'][1]) && (int)$_POST['residence'][1]!=0 ? (int)$_POST['residence'][1]:null),
			"transportation"=>(isset($_POST['transportation'][1]) && (int)$_POST['transportation'][1]!=0 ? (int)$_POST['transportation'][1] : null),
			"social_number"=>(isset($_POST['social_number']) && fnConvOnlyNumbers($_POST['social_number'])!="" ? fnConvOnlyNumbers($_POST['social_number']) : null),/*required*/
			"social_id_image"=>(isset($_POST['social_id_image']) && is_array($_POST['social_id_image']) && !empty($_POST['social_id_image'])?$_POST['social_id_image'] : null),/*required*/
			"edu_cert_image"=>(isset($_POST['edu_cert_image']) && is_array($_POST['edu_cert_image']) && !empty($_POST['edu_cert_image'])?$_POST['edu_cert_image']:null),
			"shift"=>(isset($_POST['shift'][1]) && (int)$_POST['shift'][1]!=0 ? (int)$_POST['shift'][1] : null),
			"workingtimes"=>(isset($_POST['workingtimes'][1]) && (int)$_POST['workingtimes'][1]!=0 ? (int)$_POST['workingtimes'][1] : false),
			"payment"=>(isset($_POST['payment'][1]) && (int)$_POST['payment'][1] !=0 ? (int)$_POST['payment'][1] : false),/*required*/
			"jobtitle"=>(isset($_POST['jobtitle'][1]) && (int)$_POST['jobtitle'][1] !=0 ? (int)$_POST['jobtitle'][1] : false),/*required*/
			"regdate"=>(isset($_POST['regdate'][1]) && check_date($_POST['regdate'][1]) ? $_POST['regdate'][1] : false),/*required*/
			"birthdate"=>(isset($_POST['birthdate'][1]) && check_date($_POST['birthdate'][1]) ? $_POST['birthdate'][1] : false),/*required*/
			"nationality"=>(isset($_POST['nationality'][1]) && (int)$_POST['nationality'][1]!=0 ? (int)$_POST['nationality'][1] : null),
			"company"=>(isset($_POST['company'][1]) && (int)$_POST['company'][1]!=0 ? (int)$_POST['company'][1] : false),/*required*/
		)
	);

	//Check each field for validity (false: required and not presented or invalid, null: not required and not presented or invalid, true: presented and valid)
	foreach($arroutput['source'] as $k=>$v){
		if($v===false){
			$arrparser['result']=false;
			$arrparser['source'][$k]=isset($arrerrors[$k])?$arrerrors[$k]:"";
		}elseif($v===null){
			/*Not required field*/
		}else{
			/*Passed*/
		}
	}
	
	$LowestPermission=0;
	$LowestPermissionQuery=$sql->query("SELECT per_id FROM permissions WHERE per_order = (SELECT MIN(per_order) FROM permissions) LIMIT 1;");
	if( $LowestPermissionQuery ){
		if($LowestPermissionRow = $sql->fetch_assoc($LowestPermissionQuery)){
			$LowestPermission = $LowestPermissionRow['per_id'];
		}
	}

	//echo json_encode($arrparser);
	if($arrparser['result']){
		$sql->autocommit(false);
		$uniqid=uniqid();
		$q=sprintf("
		INSERT INTO 
			users (	
					usr_id,
					usr_username,
					usr_password,
					usr_firstname,
					usr_lastname,
					usr_gender,
					usr_phone_list,
					usr_activate,
					usr_attrib_i2,
					usr_birthdate,
					usr_privileges
				) VALUES (
					NULL,
					'%1\$s',
					'%2\$s',
					'%3\$s',
					'%4\$s',
					%5\$d,
					'%6\$s',
					0,
					0,
					'%7\$s',
					%8\$d
				);
			",
			/*username*/$uniqid,
			/*password*/"",
			/*firstname*/$arroutput['source']['firstname'],
			/*lastname*/$arroutput['source']['lastname'],
			/*gender*/	$arroutput['source']['gender'],
			/*phonelist*/$arroutput['source']['phone_list'],
			/*birthdate*/$arroutput['source']['birthdate'],
			$LowestPermission
		);
		if($sql->query($q)){
			$userid= $sql->insert_id();
			if(
				(isset($arroutput['source']['perosnal_image']) && is_array($arroutput['source']['perosnal_image']) && sizeof($arroutput['source']['perosnal_image'])>0) || 
				(isset($arroutput['source']['social_id_image']) && is_array($arroutput['source']['social_id_image']) && sizeof($arroutput['source']['social_id_image'])>0) 
			){
				if(!$sql->query("UPDATE uploads SET up_rel=$userid WHERE up_id IN (".implode(",", array_merge( $arroutput['source']['perosnal_image'],$arroutput['source']['social_id_image'])) .");")){
					$arrparser['result']=false;
					$arrparser['source']["global"]="Failed to assign uploads to the employee!";
				}
			}
			
			
			if($arrparser['result']){
				$q_labour_insert=sprintf("
				INSERT INTO 
					labour 	(
							lbr_id,
							lbr_type,
							lbr_shift,
							lbr_fixedtime,
							lbr_workingdays,
							lbr_residential,
							lbr_registerdate,
							lbr_resigndate,
							lbr_socialnumber,
							lbr_nationality,
							lbr_payment_method,
							lbr_workingtimes,
							lbr_transportation,
							lbr_company
						) VALUES (
							 %1\$s,
							 %2\$d,
							 %3\$d,
							NULL,
							NULL,
							 %4\$d,
							 '%5\$s',
							 NULL,
							 '%6\$s',
							 %7\$d,
							 %8\$d,
							 %9\$s,
							 %10\$d,
							 %11\$d
						);
					",
					$userid,
					/*jobtitle*/$arroutput['source']['jobtitle'],
					/*shift*/$arroutput['source']['shift'],
					/*residence*/$arroutput['source']['residence'],
					/*regdate*/$arroutput['source']['regdate'],
					/*socialid*/$arroutput['source']['social_number'],
					/*nationality*/$arroutput['source']['nationality'],
					/*paymethod*/$arroutput['source']['payment'],
					/*workingtimes*/is_null($arroutput['source']['workingtimes'])?"NULL":$arroutput['source']['workingtimes'],
					/*transpor*/is_null($arroutput['source']['transportation'])?"NULL":$arroutput['source']['transportation'],
					/*company*/$arroutput['source']['company']
				);
				if(!$sql->query($q_labour_insert)){
					$arrparser['result']=false;
					$arrparser['source']["global"]="Failed to insert the new employee records!".addslashes($sql->error());
				}
			}
		}else{
			$arrparser['result']=false;
			$arrparser['source']["global"]="Failed to insert the new employee records!";
		}
	}
	if($arrparser['result']){
		$arrparser['source']["global"]="Employee added successfully!";
		$sql->commit();
	}else{
		$sql->rollback();
	}
	echo json_encode($arrparser);
	exit;
}
?>
<style>
.custome > tbody > tr > td:nth-child(3){
	width:100%;
}
.custome > tbody > tr > td > input,.custome > tbody > tr > td > span.cssSLO_wrap > input{
	max-width:500px;width:500px;min-width:500px;
}
.required_field{
	color:#f03 !important;
}
</style>

<?php if($debug){?><div class="btn-set"><textarea id="__debug" style="width:100%;height:100px;margin-bottom:20px;" readonly="readonly" placeholder="Debuger"></textarea></div><?php }?>

<form id="jQform">
<input type="hidden" name="emp_method" value="add" />
<table class="bom-table custome">
	<tbody>
		<tr class="special">
			<th colspan="3" class="special">Pesonal information</th>
		</tr>
		<tr>
			<th>First name</th>
			<td class="btn-set"><input type="text" name="firstname" style="width:100%" value="" /></td>
			<td id="jserr_firstname" class="required_field"></td>
		</tr>
		<tr>
			<th>Last name</th>
			<td class="btn-set"><input type="text" name="lastname" style="width:100%" value="" /></td>
			<td id="jserr_lastname" class="required_field"></td>
		</tr>
		<tr>
			<th>Nationality</th>
			<td class="btn-set"><input type="text" name="nationality" id="slonationality" data-slo="COUNTRIES" value="" data-slodefaultid=""></td>
			<td id="jserr_nationality" class="required_field"></td>
		</tr>
		<tr>
			<th>Social ID Card</th>
			<td>
				<div class="btn-set" style="max-width: 500px;">
					<input type="type" name="social_number" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;">
					<button type="button" id="js_upload_trigger_1" class="js_upload_trigger" data-db_rel="usr_attrib_s2">ID Card photo</button>
					<input type="file" id="js_uploader_btn_1" class="js_uploader_btn" multiple="multiple" accept="image/*" />
					<span id="js_upload_list_1" class="js_upload_list"></span>
					<button type="button" id="js_upload_count_1" class="js_upload_count"><span>0</span> files</button>
				</div>
			</td>
			<td><span id="jserr_social_number" class="required_field"></span><span id="jserr_social_id_image" class="required_field"></span></td>
		</tr>
		<tr>
			<th>Personal photo</th>
			<td>
				<div class="btn-set" style="max-width: 500px;">
					<button type="button" id="js_upload_trigger" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" class="js_upload_trigger">Upload perosnal photo</button>
					<input type="file" id="js_uploader_btn" class="js_uploader_btn" accept="image/*" />
					<span id="js_upload_list" class="js_upload_list"></span>
					<button type="button" id="js_upload_count" class="js_upload_count"><span>0</span> files</button>
				</div>
			</td>
			<td id="jserr_perosnal_image" class="required_field"></td>
		</tr>
		<tr>
			<th>Gender</th>
			<td class="btn-set"><input type="text" name="gender" id="slogender" data-slo="G000" value="" data-slodefaultid=""></td>
			<td id="jserr_gender" class="required_field"></td>
		</tr>
		<tr>
			<th>Birthdate</th>
			<td class="btn-set"><input type="text" name="birthdate" id="slobirthdate" data-slo="BIRTHDATE" value="" data-slodefaultid=""></td>
			<td id="jserr_birthdate" class="required_field"></td>
		</tr>
		<tr>
			<th>Phone numbers</th>
			<td class="btn-set"><textarea  name="phone_list" style="width:100%;height:66px;"></textarea></td>
			<td id="jserr_phone_list" class="required_field"></td>
		</tr>
		
		<tr>
			<th>Residence</th>
			<td class="btn-set"><input type="text" name="residence" id="sloresidence" data-slo="E004" value="" data-slodefaultid=""></td>
			<td id="jserr_residence" class="required_field"></td>
		</tr>
		<tr>
			<th>Transportation</th>
			<td class="btn-set"><input type="text" name="transportation" id="slortransportation" data-slo="TRANSPORTATION" value="" data-slodefaultid=""></td>
			<td id="jserr_transportation" class="required_field"></td>
		</tr>
		
		<!-- 
		<tr>
			<th>Educational certificates</th>
			<td>
				<div class="btn-set" style="max-width: 500px;">
					<button type="button" id="js_upload_trigger_2" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" class="js_upload_trigger" data-db_rel="usr_attrib_s2">Certificates photos</button>
					<input type="file" id="js_uploader_btn_2" class="js_uploader_btn" multiple="multiple" accept="image/*" />
					<span id="js_upload_list_2" class="js_upload_list"></span>
					<button type="button" id="js_upload_count_2" class="js_upload_count"><span>0</span> files</button>
				</div>
			</td>
			<td id="jserr_edu_cert_image" class="required_field"></td>
		</tr>
		 -->
		 
		<tr class="special">
			<th colspan="3" class="special">Job details</th>
		</tr>
		<tr>
			<th>Company</th>
			<td class="btn-set"><input type="text" name="company" id="slocompany" data-slo="COMPANY_USER" /></td>
			<td id="jserr_company" class="required_field"></td>
		</tr>
		<tr>
			<th>Registration date</th>
			<td class="btn-set"><input type="text" name="regdate" id="sloregdate" data-slo="DATE" value="<?php echo date("Y-m-d");?>" data-slodefaultid="<?php echo date("Y-m-d");?>" /></td>
			<td id="jserr_regdate" class="required_field"></td>
		</tr>
		<tr>
			<th>Job title</th>
			<td class="btn-set"><input type="text" name="jobtitle" id="slotype" data-slo="E002A" value="" data-slodefaultid=""></td>
			<td id="jserr_jobtitle" class="required_field"></td>
		</tr>
		<tr>
			<th>Working shift</th>
			<td class="btn-set"><input type="text" name="shift" id="sloshift" data-slo="E003" value="" data-slodefaultid=""></td>
			<td id="jserr_shift" class="required_field"></td>
		</tr>
		<tr>
			<th>Working Time</th>
			<td class="btn-set"><input type="text" name="workingtimes" id="sloworkingtimes" data-slo="WORKING_TIMES" value="" data-slodefaultid=""></td>
			<td id="jserr_workingtimes" class="required_field"></td>
		</tr>
		<tr>
			<th>Payment method</th>
			<td class="btn-set"><input type="text" name="payment" id="slopayment" data-slo="SALARY_PAYMENT_METHOD" value="" data-slodefaultid=""></td>
			<td id="jserr_payment" class="required_field"></td>
		</tr>
		<tr class="special">
			<th colspan="3" class="special">Salary details</th>
		</tr>
		<tr>
			<th>Salary</th>
			<td>
				<div class="btn-set" style="max-width:500px">
					<input name="salary_basic" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" />
					<label class="btn-checkbox">
						<input type="checkbox" class="derive_function" data-rel="salary_basic" name="sal_default_salary" />
					<span>Derive from job title</span>
					</label>
				</div>
			</td>
			<td></td>
		</tr>
		<tr>
			<th>Variable</th>
			<td>
				<div class="btn-set" style="max-width:500px">
					<input name="salary_variable" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" />
					<label class="btn-checkbox">
						<input type="checkbox" class="derive_function" data-rel="salary_variable" name="sal_default_variable" />
					<span>Derive from job title</span>
					</label>
				</div>
			</td>
			<td></td>
		</tr>
		<tr>
			<th>Allowance</th>
			<td>
				<div class="btn-set" style="max-width:500px">
					<input name="salary_allowance" style="-webkit-box-flex: 1;-moz-box-flex: 1;-webkit-flex: 1;-ms-flex: 1;flex: 1;" />
					<label class="btn-checkbox">
						<input type="checkbox" class="derive_function" data-rel="salary_allowance" name="sal_default_allowance" />
					<span>Derive from job title</span>
					</label>
				</div>
			</td>
			<td></td>
		</tr>
		<tr>
			<td colspan="3"><div class="btn-set center"><button type="button" id="jQbtnSubmit">Add Employee</button></div></td>
		</tr>
	</tbody>
</table>
</form>

<script>



$(document).ready(function(e) {
	UploadUserPersonalImage=$.Upload({
		objectHandler:$("#js_upload_list"),
		domselector:$("#js_uploader_btn"),
		dombutton:$("#js_upload_trigger"),
		list_button:$("#js_upload_count"),
		emptymessage:"[No files uploaded]",
		upload_url:"<?php echo $tables->pagefile_info(186,null,"directory");?>",
		relatedpagefile:<?php echo App::FILE['Person']['Photo'];?>,
		multiple:false,
		inputname:"perosnal_image",
		onupload:function(output){
		}
		}
	);
	
	UploadUserSocialID=$.Upload({
		objectHandler:$("#js_upload_list_1"),
		domselector:$("#js_uploader_btn_1"),
		dombutton:$("#js_upload_trigger_1"),
		list_button:$("#js_upload_count_1"),
		emptymessage:"[No files uploaded]",
		upload_url:"<?php echo $tables->pagefile_info(186,null,"directory");?>",
		relatedpagefile:190,
		multiple:true,
		inputname:"social_id_image"
		}
	);
	Upload=$.Upload({
		objectHandler:$("#js_upload_list_2"),
		domselector:$("#js_uploader_btn_2"),
		dombutton:$("#js_upload_trigger_2"),
		list_button:$("#js_upload_count_2"),
		emptymessage:"[No files uploaded]",
		upload_url:"<?php echo $tables->pagefile_info(186,null,"directory");?>",
		relatedpagefile:188,
		multiple:true,
		inputname:"edu_cert_image"
		}
	);
	
	var slotype=$("#slotype").slo({
		onselect:function(value){
			get_salary_information();
		},
		ondeselect:function(){
			salary_clear();
		},'limit':10});
	var sloshif=$("#sloshift").slo({'limit':10});
	var sloresi=$("#sloresidence").slo({'limit':10});
	var slogend=$("#slogender").slo({'limit':10});
	var sloregister=$("#sloregdate").slo({'limit':5});
	var slorbirthdate=$("#slobirthdate").slo({'limit':10});
	var slocompany=$("#slocompany").slo({'limit':10});
	
	
	var sloworktimes=$("#sloworkingtimes").slo({
		onselect:function(value){
			get_salary_information();
		},
		ondeselect:function(){
			salary_clear();
		},'limit':10});
	var slorpaymethod=$("#slopayment").slo({
		onselect:function(value){
			get_salary_information();
		},
		ondeselect:function(){
			salary_clear();
		},'limit':10});
	var slortransportation=$("#slortransportation").slo({'limit':10});
	var slonationality=$("#slonationality").slo({'limit':10});
	
	
	
	var clearForm=function(){
		UploadUserPersonalImage.clear();
		UploadUserSocialID.clear();
		$("input[name=firstname]").val("");
		$("input[name=lastname]").val("");
		$("textarea[name=phone_list]").val("");
		$("input[name=social_number]").val("");
		/*slotype.clear();*/
		/*sloshif.clear();*/
		sloresi.clear();
		slogend.clear();
		/*sloregister.clear();*/
		slorbirthdate.clear();
		/*sloworktimes.clear();*/
		/*slorpaymethod.clear();*/
		slortransportation.clear();
		slocompany.clear();
	}
	$("#jQbtnSubmit").on('click',function(){
		var $form=$("#jQform");
		$.ajax({
			url:'<?php echo $fs()->dir;?>',
			type:'POST',
			data:$form.serialize()
		}).done(function(data){
			<?php if($debug){?>$("#__debug").val(data);<?php }?>
			try{
				var json=JSON.parse(data);
			}catch(e){
				messagesys.failure("Server response error");return false;
			}
			$(".required_field").html("");
			if(json.result){
				messagesys.success("Employee record added successfully");
				clearForm();
			}else{
				for(var errkey in json.source){
					if(errkey=="global"){
						messagesys.failure(json.source[errkey]);
					}else{
						$("#jserr_"+errkey).html(json.source[errkey]);
					}
				}
			}
		});
	});
	
	
	var salary_information={
		"salary_basic":"",
		"salary_variable":"",
		"salary_allowance":"",
		"salary_transportation":"",
	};
	
	var get_salary_information=function(){
		$.ajax({
			url:"<?php echo "{$fs()->dir}";?>",
			type:"POST",
			data:{
				'method':'get_salary_information',
				'sal_workingtime':(sloworktimes.hidden[0].val()),
				'sal_paymethod':(slorpaymethod.hidden[0].val()),
				'sal_job':(slotype.hidden[0].val()),
			}
		}).done(function(output){
			if(output=="false"){	
				salary_clear();
				return;
			}
			var json=false;
			try{
				json=JSON.parse(output);
			}catch(e){messagesys.failure("Parsing output failed");return false;}
			
			salary_information['salary_basic']=json.basic;
			salary_information['salary_variable']=json.variable;
			salary_information['salary_allowance']=json.allowance;
			salary_information['salary_transportation']=json.transportation;
			
			if($("[name=sal_default_salary]").prop("checked")){
				$("[name=salary_basic]").val(salary_information['salary_basic']);
			}
			if($("[name=sal_default_variable]").prop("checked")){
				$("[name=salary_variable]").val(salary_information['salary_variable']);
			}
			if($("[name=sal_default_allowance]").prop("checked")){	
				$("[name=salary_allowance]").val(salary_information['salary_allowance']);
			}
		});
	}
	
	
	var salary_clear=function(){
		if($("[name=sal_default_salary]").prop("checked")){
			salary_information['salary_basic']="0.00";
			$("[name=salary_basic]").val(salary_information['salary_basic']);
		}
		if($("[name=sal_default_variable]").prop("checked")){
			salary_information['salary_variable']="0.00";
			$("[name=salary_variable]").val(salary_information['salary_variable']);
		}
		
		if($("[name=sal_default_allowance]").prop("checked")){
			salary_information['salary_allowance']="0.00";
			$("[name=salary_allowance]").val(salary_information['salary_allowance']);
		}
	}
	
	
	$(".derive_function").on('change',function(){
		var $this=$(this);
		var _result=$this.prop("checked");
		$this.parent().prev().prop("disabled",_result);
		if(_result){
			$("[name="+$this.attr('data-rel')+"]").val(salary_information[$this.attr('data-rel')]);
		}
	})
	
	
	
	$("#jQform input[type=text]").first().focus();
});
</script>