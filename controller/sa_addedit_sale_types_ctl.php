<?php
include_once 'model/sa_addedit_sale_types_mdl.php';

class sa_addedit_sale_types_ctl extends sa_addedit_sale_types_mdl
{
	public $TempSession = "";
	public $passedId = 0;

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			
			$this->passedId = parent::getVal("stid");
		}
		
		common::CheckLoginSession();
	}
	
	function getSaleTypeInfo(){
		return parent::getSaleTypeInfo_f_mdl($this->passedId);
	}
	
	function addEditSaleTypes(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "addedit-sale-type"){
				$this->id = parent::getVal("stId");
				$this->sale_type = parent::getVal("saleType");
				$this->sale_short_code = parent::getVal("stShortCode");
				$this->status = parent::getVal("stStatus");
				
				parent::addEditSaleTypes_f_mdl();
			}
		}
	}
}
?>