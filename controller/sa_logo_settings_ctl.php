<?php
include_once 'model/sa_logo_settings_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
class sa_logo_settings_ctl extends sa_logo_settings_mdl
{
	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		common::CheckLoginSession();
		
	}
	
	function addUpdateLogoSettings(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "add-edit-logo_settings"){
				$this->id = parent::getVal("hdn_id");
				/*
				Add and Update : Minimum Group (Product)
				*/
				$group_order    = parent::getVal("group_order");
				$group_location = parent::getVal("group_location");
				$group_color    = parent::getVal("group_color");
				
				if($group_order){
					foreach($group_order AS $groupOrder => $group_id){
						$group_name = addslashes(htmlspecialchars(parent::getVal('group_name_'.$groupOrder.'')));
						if (!empty($group_name)) {
							if($group_id == 'new'){
								parent::addPrintSize_f_mdl($group_name,$groupOrder);
							}else{
								parent::updatePrintSize_f_mdl($group_id, $group_name,$groupOrder);
							}
						}
					}
				}

				if($group_location){
					foreach($group_location AS $locationOrder => $location_id){
						$location_name = addslashes(htmlspecialchars(parent::getVal('location_name'.$locationOrder.'')));
						if (!empty($location_name)) {
							if($location_id == 'new1'){
								parent::addPrintLocation_f_mdl($location_name,$locationOrder);
							}else{
								parent::updatePrintLocation_f_mdl($location_id, $location_name,$locationOrder);
							}
						}
					}
				}

				if($group_color){
					foreach($group_color AS $colorOrder => $color_id){
						$color_name = addslashes(htmlspecialchars(parent::getVal('color_name'.$colorOrder.'')));
						if (!empty($color_name)) {
							if($color_id == 'new2'){
								parent::addPantoneColor_f_mdl($color_name,$colorOrder);
							}else{
								parent::updatePantoneColor_f_mdl($color_id, $color_name,$colorOrder);
							}
						}
					}
				}

				$remove_location = parent::getVal("remove_location");
				if($remove_location){
					foreach($remove_location AS $location_id){
						parent::deletePrintLocation_f_mdl($location_id);
					}
				}

				$remove_group = parent::getVal("remove_group");
				if($remove_group){
					foreach($remove_group AS $group_id){
						parent::deletePrintSize_f_mdl($group_id);
					}
				}

				$remove_color = parent::getVal("remove_color");
				if($remove_color){
					foreach($remove_color AS $color_id){
						parent::deletePantoneColor_f_mdl($color_id);
					}
				}

				$resultArray = array();
				$affected_rows = 1;
				if($affected_rows){
					$resultArray["isSuccess"] = "1";
					$resultArray["msg"] = "Changes saved successfully.";
				}
				else{
					$resultArray["isSuccess"] = "0";
					$resultArray["msg"] = "Oops! there is somethimg wrong. Please try again.";
				}
				common::sendJson($resultArray);
			}
		}
	}

	function getLogoSettingsproductGroup(){
		return parent::getPrintSize_f_mdl();
	}

	function getPrintLocationData()
	{
		return parent::getPrintLocation_f_mdl();
	}

	function getPantoneColor()
	{
		return parent::getPantoneColor_f_mdl();
	}
}
?>