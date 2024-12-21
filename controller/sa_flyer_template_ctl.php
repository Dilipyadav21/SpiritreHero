<?php
include_once 'model/sa_flyer_template_mdl.php';

class sa_flyer_template_ctl extends sa_flyer_template_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			
			$this->passedId = parent::getVal("did");
		}
		
		common::CheckLoginSession();
	}
	
	function getTemplateInfo(){
			$this->storeId = parent::getVal("did");
			
			return parent::getTemplateInfo_f_mdl($this->storeId);
	}
}
?>