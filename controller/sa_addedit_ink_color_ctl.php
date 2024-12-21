<?php
include_once 'model/sa_addedit_ink_color_mdl.php';

class sa_addedit_ink_color_ctl extends sa_addedit_ink_color_mdl
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
	
	function getInkColorInfo(){
		return parent::getInkColorInfo_f_mdl($this->passedId);
	}
	
	function addEditInkColor(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "addedit-ink-col"){
				$this->id = parent::getVal("icId");
				$this->ink_color = parent::getVal("inkCol");
				$this->status = parent::getVal("icStatus");
				
				$this->ink_color_name = parent::getVal("icName");
				
				$slug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '',preg_replace('/\s+/', '-', $this->ink_color_name) ));
				
				$this->ink_color_slug = $slug;
				
				parent::addEditInkColor_f_mdl();
			}
		}
	}
}
?>