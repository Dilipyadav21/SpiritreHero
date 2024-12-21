<?php
include_once 'model/sa_store_manager_addedit_mdl.php';

class sa_store_manager_addedit_ctl extends sa_store_manager_addedit_mdl
{
	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
        if(isset($_REQUEST['action'])){
			$action = $_REQUEST['action'];
			if($action=='edit_store_managers'){
				$this->edit_store_managers();exit;
			}

		}
	}
	
	public function getManagersInfo($id){
		
		$sql ="SELECT password,id,store_owner_id,first_name,last_name,email,mobile,status,create_on FROM `store_manager_master` WHERE id=$id ";
		$datamanagerDetails = parent::selectTable_f_mdl($sql);
        
		if(!empty($datamanagerDetails)){
			return $datamanagerDetails;
		}
	}

	public function edit_store_managers(){
		if (parent::isPOST()) {
			$res='';
			if (!empty(parent::getVal("action")) && parent::getVal("action") == "edit_store_managers") {
				
				$store_manager_id= parent::getVal('store_manager_id');
				$first_name= parent::getVal('first_name');
				$last_name= parent::getVal('last_name');
				$mobile= parent::getVal('mobile');
				$status= parent::getVal('status');
				$password= parent::getVal('sm_password');

				$sql ="SELECT password FROM `store_manager_master` WHERE id=$store_manager_id ";
				$smpassData = parent::selectTable_f_mdl($sql);

				if(!empty($password)){
					$password=md5(trim($password));

				}else{
					$password= $smpassData[0]['password'];
				}
				$res=parent::addEditManager_f_mdl($store_manager_id,$first_name,$last_name,$mobile,$status,$password);
				
			}
			echo json_encode($res);die;
		}
	}
}
?>