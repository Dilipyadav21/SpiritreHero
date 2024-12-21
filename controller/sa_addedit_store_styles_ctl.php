<?php
include_once 'model/sa_addedit_store_styles_mdl.php';

class sa_addedit_store_styles_ctl extends sa_addedit_store_styles_mdl
{
	public $TempSession = "";
	public $passedId = 0;

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			
			$this->passedId = parent::getVal("ssid");
		}
		
		common::CheckLoginSession();
	}
	
	function getStoreStyleInfo(){
		return parent::getStoreStyleInfo_f_mdl($this->passedId);
	}
	
	function addEditStoreStyles(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "addedit-store-style"){
				$this->id = parent::getVal("ssId");
				$this->style_name = parent::getVal("styleName");
				$this->status = parent::getVal("ssStatus");
				
				parent::addEditStoreStyles_f_mdl();
			}
		}
	}
}
?>