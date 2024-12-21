<?php
include_once 'model/sa_addedit_vendors_mdl.php';

class sa_addedit_vendors_ctl extends sa_addedit_vendors_mdl
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
	
	function getVendorInfo(){
		return parent::getVendorInfo_f_mdl($this->passedId);
	}
	
	function addEditVendor(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "addedit-vendor"){
				$this->id = parent::getVal("vId");
				$this->vendor_name = parent::getVal("vName");
				$this->status      = parent::getVal("vStatus");
                $this->email       = parent::getVal("email");
				$password = '';
				if(!empty(parent::getVal("old_password"))){
					$password = parent::getVal("old_password");
				}else{
					$password = md5(trim(parent::getVal("password")));
				}
				
                $this->password    = $password;
                $this->first_name  = parent::getVal("first_name");
                $this->last_name   = parent::getVal("last_name");
				parent::addEditVendor_f_mdl();
			}
		}
	}

	function getVendorDetail($id)
	{
		$sql="SELECT email,password,first_name,last_name FROM users WHERE vendor_id = '".$id."' ";
		$getData = parent::selectTable_f_mdl($sql);
		return $getData;
	}

	function checkVendorName()
	{
		if(parent::isPOST()){
			$res = [];
			if(!empty(parent::getVal("action")) && parent::getVal("action") == "checkVendorName"){
				$vendorName = parent::getVal("vendorName");
				$sql="SELECT vendor_name FROM store_vendors_master WHERE vendor_name = '".$vendorName."' ";
				$vendorData = parent::selectTable_f_mdl($sql);
				if(!empty($vendorData)){
					$res['status'] = true;
				}else{
					$res['status'] = false;
				}
			}
			echo json_encode($res);die();
		}	
	}
}
?>