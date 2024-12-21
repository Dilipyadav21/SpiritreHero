<?php
include_once 'model/sa_store_styles_mdl.php';

class sa_store_styles_ctl extends sa_store_styles_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		
		common::CheckLoginSession();
	}
	
	function getStoreStyles(){
		return parent::getStoreStyles_f_mdl();
	}
	
	function deleteStoreStyles(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "delete-store-style"){
				$id = parent::getVal("ssId");
				
				parent::deleteStoreStyles_f_mdl($id);
			}
		}
	}
}
?>