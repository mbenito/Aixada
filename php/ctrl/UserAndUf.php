<?php

define('DS', DIRECTORY_SEPARATOR);
define('__ROOT__', dirname(dirname(dirname(__FILE__))).DS); 

require_once(__ROOT__ . "local_config/config.php");
require_once(__ROOT__ . "php/inc/database.php");
require_once(__ROOT__ . "php/utilities/general.php");
require_once(__ROOT__ . "php/utilities/useruf.php");


if (!isset($_SESSION)) {
    session_start();
 }


try{
   

  switch ($_REQUEST['oper']) {
  	    
  		
  		case 'getUfListing':
        	printXML(stored_query_XML_fields('get_uf_listing', get_param('all',0)));
        	exit;
        	
        case 'createUF':
		   	printXML(stored_query_XML_fields('create_uf', get_param('name'), get_param('mentor_uf',0)));
		   	exit;
 		
	   	case 'editUF':
      		echo do_stored_query('update_uf', get_param('uf_id'), get_param('name'), get_param('is_active'), get_param('mentor_uf',0));
      		exit;
		   
        case 'getUsersWithoutUF':
        	printXML(stored_query_XML_fields('users_without_uf'));
        	exit;
        	
        case 'checkFormField':
        	echo validate_field(get_param('table'), get_param('field'), get_param('value'));
        	exit;

        //listing of all/only active members. 	
        case 'getMemberListing':
        	printXML(stored_query_XML_fields('get_member_listing', get_param('all',0)));
        	exit;
		        	
		//detailed info about member, if member_id is given, otherwise for all members of uf. 
	    case 'getMemberInfo':
	        printXML(stored_query_XML_fields('get_member_info', get_param('member_id',0), get_param('uf_id',0) ));
	        exit;
	        
	    case 'updateMember':
	    	echo update_member(get_param('member_id'));
	    	exit;
	    	
	    //creates a new user and member
	    case 'createUserMember':
	    	echo create_user_member(get_param('uf_id'));
			exit;
			
	    case 'removeMember':
	    	echo do_stored_query('remove_member_from_uf', get_param('member_id'));
       		exit;
	    	
	    case 'getMembersWithoutUF':
	    	printXML(stored_query_XML_fields('get_unassigned_members'));
	    	exit;
	    	
	    case 'searchMember':
	    	printXML(stored_query_XML_fields('search_members', get_param('like')));
	    	exit;
	    	
	    case 'changePassword':
	    	echo change_password();
	        exit; 

	    case 'resetPassword':
	    	echo reset_password(get_param('user_id'));
	    	exit; 
	    	
	    	
	    	  
  case 'assignUsersToUF':
        foreach ($_REQUEST['user_id'] as $id) {
            do_stored_query('assign_user_to_uf', $id, $_REQUEST['uf_id']);
        }
        exit;



  default:
    throw new Exception("ctrlUserAndUf: oper=" . $_REQUEST['oper'] . " not valid in query");
  }
} 

catch(Exception $e) {
  header('HTTP/1.0 401 ' . $e->getMessage());
  die($e->getMessage());
}  


?>