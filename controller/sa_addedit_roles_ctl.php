<?php
include_once 'model/sa_addedit_roles_mdl.php';

class sa_addedit_roles_ctl extends sa_addedit_roles_mdl
{
	public $TempSession = "";
	public $passedId = 0;

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			
			$this->passedId = parent::getVal("rid");
		}
		
		common::CheckLoginSession();
	}
	
	function getRoleInfo(){
		return parent::getRoleInfo_f_mdl($this->passedId);
	}
	
	function addEditRoles(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "addedit-role"){
				$this->id = parent::getVal("rId");
				$this->role_type = parent::getVal("roleType");
				$this->role_short_code = parent::getVal("rShortCode");
				$this->status = parent::getVal("rStatus");
				
				parent::addEditRoles_f_mdl();
			}
		}
	}
}
?>