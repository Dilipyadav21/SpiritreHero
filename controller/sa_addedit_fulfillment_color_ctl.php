<?php
include_once 'model/sa_addedit_fulfillment_color_mdl.php';

class sa_addedit_fulfillment_color_ctl extends sa_addedit_fulfillment_color_mdl
{
	public $TempSession = "";
	public $passedId = 0;

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			
			$this->passedId = parent::getVal("icid");
		}
		
		common::CheckLoginSession();
	}
	
	function getFulfillmentColorInfo(){
		return parent::getFulfillmentColorInfo_f_mdl($this->passedId);
	}
	
	function addEditFulfillmentColor(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "addedit-fulfillment-col"){
				$this->id = parent::getVal("icId");
				$this->fulfillment_type = parent::getVal("fulfillment_type");
				$this->fulfillment_color = parent::getVal("fulfillmentCol");
				$this->status = parent::getVal("icStatus");
				
				$this->fulfillment_color_name = parent::getVal("icName");
				
				$slug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '',preg_replace('/\s+/', '-', $this->fulfillment_color_name) ));
				
				$this->fulfillment_color_slug = $slug;
				
				parent::addEditFulfillmentColor_f_mdl();
			}
		}
	}
}
?>