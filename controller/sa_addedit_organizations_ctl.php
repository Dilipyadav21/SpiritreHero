<?php
include_once 'model/sa_addedit_organizations_mdl.php';

class sa_addedit_organizations_ctl extends sa_addedit_organizations_mdl
{
	public $TempSession = "";
	public $passedId = 0;

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			
			$this->passedId = parent::getVal("oid");
		}
		
		common::CheckLoginSession();
	}
	
	function getOrganizationsInfo(){
		return parent::getOrganizationsInfo_f_mdl($this->passedId);
	}
	
	function addEditOrganizations(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "addedit-organization"){
				$this->id = parent::getVal("oId");
				$this->organization_name = parent::getVal("orgName");
				
				$this->organization_short_code = strtolower(preg_replace('/\s+/', '_', $this->organization_name));
				
				$this->status = parent::getVal("orgStatus");
				
				parent::addEditOrganizations_f_mdl();
			}
		}
	}
}
?>