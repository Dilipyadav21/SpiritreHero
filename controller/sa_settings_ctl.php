<?php
include_once 'model/sa_settings_mdl.php';

class sa_settings_ctl extends sa_settings_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		
		common::CheckLoginSession();
	}
}
?>