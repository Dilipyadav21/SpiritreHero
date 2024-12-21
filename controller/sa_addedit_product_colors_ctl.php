<?php
include_once 'model/sa_addedit_product_colors_mdl.php';

class sa_addedit_product_colors_ctl extends sa_addedit_product_colors_mdl
{
	public $TempSession = "";
	public $passedId = 0;

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			if(parent::getVal("method")){

				$this->checkRequestProcess(parent::getVal("method"));
			}else{
				$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			}
			
			$this->passedId = parent::getVal("pcId");
		}
		
		common::CheckLoginSession();
	}

	function checkRequestProcess($requestFor){
        if($requestFor != ""){
            switch($requestFor){
				case "colorname_check_add":
					$this->colorname_check_add();
                break;
				case "colorname_check_edit":
					$this->colorname_check_edit();
                break;
				case "colorcode_check_add":
					$this->colorcode_check_add();
                break;
				case "colorcode_check_edit":
					$this->colorcode_check_edit();
                break;
			}
        }
    }

    public function colorname_check_add(){
		if (parent::isPOST()) {
			$res='';
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "colorname_check_add") {
				$color_name= parent::getVal('color_name');
				$id= parent::getVal('id');
				
				if($id == 0){
					$query = 'SELECT product_color_name FROM store_product_colors_master WHERE  product_color_name="'.$color_name.'" ';
					$colornamedata = parent::selectTable_f_mdl($query);
				}
				if(!empty($colornamedata)){
					$status=1;
				}else{
					$status=0;
				}
				echo $status;
			}
			die;
		}
	}

	public function colorname_check_edit(){
		if (parent::isPOST()) {
			$res='';
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "colorname_check_edit") {
				$color_name= parent::getVal('color_name');
				$id= parent::getVal('id');
				
				
				if($id > 0){
					$query = 'SELECT product_color_name FROM store_product_colors_master WHERE id !="'.$id.'" AND product_color_name="'.$color_name.'" ';
					
					$colornamedata = parent::selectTable_f_mdl($query);

				}
				if(!empty($colornamedata)){
					$status=1;
				}else{
					$status=0;
				}
				echo $status;
			}
			die;
		}
	}

	public function colorcode_check_add(){
		if (parent::isPOST()) {
			$res='';
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "colorcode_check_add") {
				$color_code_hex= parent::getVal('color_code_hex');
				$id= parent::getVal('id');
				
				if($id == 0){
					$query = 'SELECT product_color FROM store_product_colors_master WHERE  product_color="'.$color_code_hex.'" ';
					$colorcodedata = parent::selectTable_f_mdl($query);
				}
				if(!empty($colorcodedata)){
					$status=1;
				}else{
					$status=0;
				}
				echo $status;
			}
			die;
		}
	}

	public function colorcode_check_edit(){
		if (parent::isPOST()) {
			$res='';
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "colorcode_check_edit") {
				$color_code_hex= parent::getVal('color_code_hex');
				$id= parent::getVal('id');
				
				if($id >0){
					$query = 'SELECT product_color_name FROM store_product_colors_master WHERE id !="'.$id.'" AND product_color="'.$color_code_hex.'" ';
					$colorcodedata = parent::selectTable_f_mdl($query);
				}
				if(!empty($colorcodedata)){
					$status=1;
				}else{
					$status=0;
				}
				echo $status;
			}
			die;
		}
	}
	
	function getInkColorInfo(){
		return parent::getInkColorInfo_f_mdl($this->passedId);
	}
	
	function getFamilyColorDropdownInfo(){
		return parent::getFamilyColorDropdownInfo_f_mdl($this->passedId);
	}
	
	function addEditInkColor(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "addedit-prod-cols"){

				$this->id = parent::getVal("productId");
				$this->product_color = parent::getVal("productCol");
				$this->status = parent::getVal("productStatus");
				$this->product_color_name = parent::getVal("productColorName");
				$this->color_family = parent::getVal("selectedColorFamily");

				$clrExisted = parent::checkProductColorExist_f_mdl($this->id,$this->product_color,$this->product_color_name);
				if(empty($clrExisted)){
					$slug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '',preg_replace('/\s+/', '-', $this->product_color_name) ));
					$this->product_color_slug = $slug;
					parent::addEditInkColor_f_mdl();
				}else{
					$resultArray["isSuccess"] = "0";
					$resultArray["msg"] = "Color Hex code is already exists. Please try other color-code.";
					common::sendJson($resultArray);
				}
			}
		}
	}
}
?>