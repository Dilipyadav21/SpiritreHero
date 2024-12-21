<?php
include_once 'model/sa_po_settings_mdl.php';

class sa_po_settings_ctl extends sa_po_settings_mdl
{
	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		common::CheckLoginSession();
		
	}
	
	function getPoSettingsInfo(){
		return parent::getPoSettingsInfo_f_mdl();
	}
	
	function addUpdatePoSettings(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "add-edit-po_settings"){
				$this->id = parent::getVal("hdn_id");
				$this->po_number = parent::getVal("po_number");
				$this->po_account = parent::getVal("po_account");
				$this->company_name = parent::getVal("company_name");				
				$this->po_notes = parent::getVal("po_notes");
				$this->po_bill_to = parent::getVal("po_bill_to");
				$this->po_ship_to = parent::getVal("po_ship_to");
				
				if($this->id > 0){
					parent::updatePoSettings_f_mdl();
				}
				else{
					parent::addPoSettings_f_mdl();
				}
			}
		}
	}
}
?>