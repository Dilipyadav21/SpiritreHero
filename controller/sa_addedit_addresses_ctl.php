<?php
include_once 'model/sa_addedit_addresses_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';


class sa_addedit_addresses_ctl extends sa_addedit_addresses_mdl
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
				case "add_edit_addresses":
					$this->addEditAddresses();
                break;
                case "email_check_addemail":
					$this->email_check_addemail();
                break;
				
			}
        }
    }
	
	function getAddressesInfo($id){
		return parent::getAddressesInfo_f_mdl($id);
	}
	
	function addEditAddresses(){
       
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "add_edit_addresses"){
				$this->id = parent::getVal("hdn_id");
				$this->address_line_1 = parent::getVal("address_line_1");
				$this->address_line_2 = parent::getVal("address_line_2");
				$this->email = parent::getVal("email");
				$this->country      = parent::getVal("country");
                $this->state       = parent::getVal("state");
                $this->city       = parent::getVal("city");
                $this->zip_code       = parent::getVal("zip_code");
                $this->ship_to       = parent::getVal("ship_to");
                $this->shipping_method       = parent::getVal("shipping_method");
                $this->residence       = parent::getVal("residence");
                $this->status       = parent::getVal("status");
				echo parent::addEditAddresses_f_mdl();
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
}
?>