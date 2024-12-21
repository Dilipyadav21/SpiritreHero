<?php
include_once 'model/sa_update_products_variants_mdl.php';

class sa_update_products_variants_ctl extends sa_update_products_variants_mdl
{
	public $TempSession = "";
	public $passedId = 0;

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			
			$this->passedId = parent::getVal("pid");
		}
		
		common::CheckLoginSession();
	}
	
	function getProductVariantsInfo(){
		return parent::getProductVariantsInfo_f_mdl($this->passedId);
	}
	
	function getProductColorInfo(){
		return parent::getProductColorInfo_f_mdl();
	}
}
?>