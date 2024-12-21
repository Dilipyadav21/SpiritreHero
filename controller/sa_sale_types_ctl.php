<?php
include_once 'model/sa_sale_types_mdl.php';

class sa_sale_types_ctl extends sa_sale_types_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		
		common::CheckLoginSession();
	}
	
	function getSaleTypes(){
		return parent::getSaleTypes_f_mdl();
	}
	
	function deleteSaleTypes(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "delete-sale-type"){
				$id = parent::getVal("stId");
				
				parent::deleteSaleTypes_f_mdl($id);
			}
		}
	}
}
?>