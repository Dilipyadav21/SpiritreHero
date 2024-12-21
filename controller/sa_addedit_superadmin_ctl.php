<?php
include_once 'model/sa_addedit_superadmin_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';


class sa_addedit_superadmin_ctl extends sa_addedit_superadmin_mdl
{
	public $TempSession = "";
	function __construct()
	{
		if (parent::isGET() || parent::isPOST()) {
			if(parent::getVal("method")){
				$this->checkRequestProcess(parent::getVal("method"));
			}else{
				$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			}
			//$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		common::CheckLoginSession();
	}

    function checkRequestProcess($requestFor){
        if($requestFor != ""){
            switch($requestFor){
				case "add_edit_super_admin":
					$this->addEditSuperAdmin();
                break;
                case "email_check_addemail":
					$this->email_check_addemail();
                break;
				case "change_super_admin_password":
					$this->change_super_admin_password();
				break;
			}
        }
    }
	
	function getSuperAdminInfo($id){
		return parent::getSuperAdminInfo_f_mdl($id);
	}
	
	function addEditSuperAdmin(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "add_edit_super_admin"){
				$this->id = parent::getVal("id");
				$this->role_id = "1";
				$this->first_name = parent::getVal("first_name");
				$this->last_name      = parent::getVal("last_name");
                $this->email       = parent::getVal("email");
                $this->status       = parent::getVal("status");
				$password = '';
				if(!empty(parent::getVal("old_password"))){
					$password = parent::getVal("old_password");
				}else{
					$password = md5(trim(parent::getVal("password")));
				}
                $this->password    = $password;
				echo parent::addEditSuperadmin_f_mdl();
			}
		}
        
	}

    public function email_check_addemail(){
		if (parent::isPOST()) {
			$res='';
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "email_check_addemail") {
				$email= parent::getVal('email');
				$id= parent::getVal('id');
				$status=0;
				if(!empty($id)){
					$sql = 'SELECT email from users where email = "'.$email.'" AND id!="'.$id.'" ';
					$list_data = parent::selectTable_f_mdl($sql);
				}
				else{
					$sql1 = 'SELECT email from store_owner_details_master where email ="'.$email.'" ';
					$store_owner_data = parent::selectTable_f_mdl($sql1);
					if(!empty($store_owner_data)){
						$status=1;
					}else{

						$sql_get_email = 'SELECT email from store_manager_master where email ="'.$email.'" ';
						$list_data = parent::selectTable_f_mdl($sql_get_email);
                        if(!empty($list_data)){
                            $status=1;
                        }else{
                            $sql_email = 'SELECT email from users where email ="'.$email.'" ';
						    $sa_email = parent::selectTable_f_mdl($sql_email);
                            if(!empty($sa_email)){
                                $status=1;
                            }else{
                                $status=0;
                            }
                        }
					}
				}
				
				echo $status;
			}
			die;
		}
	}

	function change_super_admin_password(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "change_super_admin_password"){
				$this->id = parent::getVal("id");
				$this->password = md5(trim(parent::getVal("password")));
				echo parent::changePasswordSuperadmin_f_mdl();
			}
		}
        
	}

}
?>