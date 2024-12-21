<?php
include_once 'model/sa_addedit_apparel_color_mdl.php';

class sa_addedit_apparel_color_ctl extends sa_addedit_apparel_color_mdl
{
	public $TempSession = "";
	public $passedId = 0;

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			
			$this->passedId = parent::getVal("acid");
		}
		
		common::CheckLoginSession();
	}
	
	function getApparelColorInfo(){
		return parent::getApparelColorInfo_f_mdl($this->passedId);
	}
	
	function addEditApparelColor(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "addedit-apa-col"){
				$this->id = parent::getVal("acId");
				$this->apparel_color = parent::getVal("apaCol");
				$this->apparel_color_name = parent::getVal("apparel_color_name");
				$this->status = parent::getVal("acStatus");
				
				$slug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '',preg_replace('/\s+/', '-', $this->apparel_color_name) ));
				
				$this->apparel_color_slug = $slug;

				parent::addEditApparelColor_f_mdl();
			}
		}
	}
}
?>