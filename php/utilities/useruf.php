<?php

require_once(__ROOT__ . 'php/inc/database.php');
require_once(__ROOT__ . 'local_config/config.php');
require_once('general.php');
require_once(__ROOT__ . 'local_config/lang/'.get_session_language() . '.php');

/**
 * 
 * Updates database fields for given member
 * @param int $member_id
 */
function update_member($member_id){
	
	$params = extract_user_form_values();
	
	echo do_stored_query('update_member', $member_id, 
			$params["custom_member_ref"],
			$params["name"],
			$params["nif"],
			$params["address"],
			$params["city"],
			$params["zip"],
			$params["phone1"],
			$params["phone2"],
			$params["web"],
			$params["notes"],
			$params["active"],
			$params["participant"],
			$params["adult"],
			$params["language"],
			$params["gui_theme"],
			$params["email"]	
	);
	
}


/**
 * 
 * creates a new user and member for the given UF
 * @param unknown_type $uf_id
 */
function create_user_member($uf_id){
	
	
	$params = extract_user_form_values();
	
	$login_exists = validate_field('aixada_user', 'login', $params['login']);
	
	if($login_exists) {
		throw new Exception("The login '" .$params['login']. "' already exists. Please choose another one");
		exit; 
		
	} else {
		echo do_stored_query('new_user_member', 
				$params["login"],
				$params["password"],
				$uf_id,
				$params["custom_member_ref"],
				$params["name"],
				$params["nif"],
				$params["address"],
				$params["city"],
				$params["zip"],
				$params["phone1"],
				$params["phone2"],
				$params["web"],
				$params["notes"],
				$params["active"],
				$params["participant"],
				$params["adult"],
				$params["language"],
				$params["gui_theme"],
				$params["email"]	
			);
	}
}


function extract_user_form_values(){
	
	$fields["login"] = get_param('login','');
	$fields["password"] = crypt(get_param('password',''), "ax");	
	$fields["custom_member_ref"] = get_param('custom_member_ref','');
	$fields["name"] = get_param('name');
	$fields["nif"] = get_param('nif','');
	$fields["address"] = get_param('address','');
	$fields["city"] = get_param('city','');
	$fields["zip"] = get_param('zip','');
	$fields["phone1"] = get_param('phone1','');
	$fields["phone2"] = get_param('phone2','');
	$fields["web"] = get_param('web','');
	$fields["notes"] = get_param('notes','');
	$fields["active"] = isset($_REQUEST['member_active'])? 1:0; //this is a checkbox; gets send when checked, otherwise not
	$fields["participant"] = isset($_REQUEST['participant'])? 1:0;
	$fields["adult"] = get_param('adult',1);	
	$fields["language"] = get_param('language','en');
	$fields["gui_theme"] = get_param('gui_theme','start');
	$fields["email"] = get_param('email','');	
	$fields["uf_id"] = get_param('uf_id',0);
	
	return $fields;
	
}


/**
 * 
 * performs quick checks on fields for a specific table. 
 * @param unknown_type $table
 * @param unknown_type $field
 * @param unknown_type $value
 * @param unknown_type $type
 */
function validate_field($table, $field, $value, $type='exists'){
	

	$db = DBWrap::get_instance(); 
    
	switch ($type){
		case 'exists':
			$rs = $db->Execute('select * from '.$table.' where '.$field.'=:1q', $value);
			if ($rs->fetch_assoc()) {
				return 1;
		    } else {
		    	return 0;
		    }
		    DBWrap::get_instance()->free_next_results();
		break;	
	}
	
}

/**
 * 
 * change user password. Only logged users can change their own password. 
 * @throws Exception
 */
function change_password(){

		$user_id = get_session_user_id();
	
      	$rs = do_stored_query('check_password', $user_id, crypt(get_param('old_password'), 'ax'));
      	$row = $rs->fetch_assoc();
      	if (!$row) {
          	throw new Exception("The old password did not match! Please try again.");
      	} else if ($row['id'] != $user_id){
      		throw new Exception("The user ID did not match the currently logged user. You cannot change the password of other others. ");
      	}
      	      	
      	DBWrap::get_instance()->free_next_results();      
      	do_stored_query('update_password', $user_id, crypt(get_param('password'), 'ax'));
      
      	return 1; 	
}


/**
 * 
 * reset password for given user. only admin can do that. 
 * @param int $user_id
 * @throws Exception
 */
function reset_password($user_id)
{
	global $Text;  
	
	//only admin 
	 if (get_current_role() != 'Hacker Commission')
          throw new Exception("Only Admin can do that!");
	
          
    $sendAsEmail = configuration_vars::get_instance()->internet_connection;
    
    $newPwd = createPassword();
    
    $db = DBWrap::get_instance(); 
    do_stored_query('update_password', $user_id, crypt($newPwd, 'ax'));
    
    
    if ($sendAsEmail){
    	DBWrap::get_instance()->free_next_results();     
		$strSQL = 'SELECT email FROM aixada_user WHERE id = :1q';
    	$rs = $db->Execute($strSQL, $user_id);
    	if($rs->num_rows == 0){
    		throw new Exception("This user has no valid email.");
		}
		
		while ($row = $rs->fetch_assoc()) {
      		$toEmail = $row['email'];
    	}    	
    	
		$subject = "OlideCoop Password Reset";
		$message = "Your password has been reset. The new password is " . $newPwd ."\n\n Please logon with the new password. Under My Account, Settings you can change your password.";
		$from = configuration_vars::get_instance()->admin_email;
		$headers = "From: $from \r\n";
		$headers .= "Reply-To: $from \r\n";
		$headers .= "Return-Path: $from\r\n";
		$headers .= "X-Mailer: PHP \r\n";		
		
		if (mail($toEmail,$subject,$message,$headers)){
			echo $Text['msg_pwd_emailed'];			
			
		} else {
			echo $Text['msg_err_emailed'];		
			
		}    	

    } else {
    	
    	echo $Text['msg_pwd_change'] . $newPwd; 
    	
    }
}

function createPassword($length=8) {
	$chars = "234567890abcdefghijkmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$i = 0;
	$password = "";
	while ($i <= $length) {
		$password .= $chars{mt_rand(0,strlen($chars))};
		$i++;
	}
	return $password;
}
	
?>
