<?php
include_once 'model/sa_production_mdl.php';

class sa_production_ctl extends sa_production_mdl
{
	public $production_status = '';
	public $edit_production_status = '';
	public $production_status_id = 0;
	
	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		common::CheckLoginSession();
		
	}
	
	function getProductionStatusInfo(){
		return parent::getProductionStatusInfo_f_mdl();
	}
	
	function getSpfGeneralSettingsInfo($id){
		return parent::getSpfGeneralSettingsInfo_f_mdl($id);
	}

	function getFulfilmentSettingsInfo(){
		return parent::getFulfilmentSettingsInfo_f_mdl();
	}

	function getPrefilledDropdownInfo(){
		return parent::getPrefilledDropdownInfo_f_mdl();
	}

	function updateProductionTaskStatus(){
		if(parent::isGET() || parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "change_task_status"){
				$status_id = parent::getVal("status_id");
				$task_id = parent::getVal("task_id");
				
				parent::updateProductionTaskStatus_f_mdl($status_id,$task_id);
			}
		}
	}
	
	function addProductionStatus(){
		if(parent::isGET() || parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "add_production_status"){
				$this->production_status = parent::getVal("add_production_status");

				parent::addProductionStatus_f_mdl();
			}
		}
	}
	
	function updateProductionStatus(){
		if(parent::isGET() || parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "edit_production_status"){
				$this->edit_production_status = parent::getVal("edit_production_status");
				$this->production_status_id = parent::getVal("production_status_id");

				parent::updateProductionStatus_f_mdl();
			}
		}
	}
	
	function deleteProductionStatus(){
		if(parent::isGET() || parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "delete_production_status"){
				$this->production_status_id = parent::getVal("production_status_id");
				
				parent::deleteProductionStatus_f_mdl();
			}
		}
	}

	function selectStoreWiseNotes(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "select_store_wise_notes"){

				$store_master_id = parent::getVal("store_master_id");

				parent::selectStoreWiseNotes_f_mdl($store_master_id);
			}
		}
		
	}

	function selectedPrefilledNotes(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "selected_prefilled_notes"){

				$selected_prefilled_id = parent::getVal("selected_prefilled_id");

				parent::selectedPrefilledNotes_f_mdl($selected_prefilled_id);
			}
		}
	}

	function insertStoreWiseNotes(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "insert_store_wise_notes"){
				$store_master_id = parent::getVal("store_master_id");
				$notesArray = parent::getVal("notesArray");
				$selectedPrefilledNoteId = parent::getVal("selectedPrefilledNoteId");

				parent::deleteStoreWiseNotes_f_mdl($store_master_id);

				if(!empty($selectedPrefilledNoteId)){
					$resultArray = parent::fetchPrefilledNotes_f_mdl($selectedPrefilledNoteId);

					if(!empty($resultArray)){
						foreach($resultArray as $prefilledData){
							parent::insertStoreWiseNotes_f_mdl($store_master_id,$prefilledData['notes'],$prefilledData['is_done']);
						}
					}
				}	

				if(!empty($notesArray)){
					$is_done = 0;
					foreach ($notesArray as $value) {
						if(count($value) > 1){
							$is_done = 1;
						}else{
							$is_done = 0;
						}

						parent::insertStoreWiseNotes_f_mdl($store_master_id,$value[0],$is_done);

					}
				}
			}
		}
	}

	function insertPrefilledNotes(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "insert_prefilled_notes"){
				$prefilled_status = parent::getVal("prefilled_status");
				$prefilledTitle = parent::getVal("prefilledTitle");
				$selectedPrefilledNoteId = parent::getVal("selectedPrefilledNoteId");
				$notesArray = parent::getVal("notesArray");

				if($prefilled_status == 'is_edit_node'){
					parent::updatePrefiiledTitle_f_mdl($selectedPrefilledNoteId,$prefilledTitle);

					parent::deletePrefilledNotes_f_mdl($selectedPrefilledNoteId);

					$is_done = 0;
					foreach ($notesArray as $value) {
						if(count($value) > 1){
							$is_done = 1;
						}else{
							$is_done = 0;
						}
						
						parent::insertPrefilledNotes_f_mdl($selectedPrefilledNoteId,$value[0],$is_done);
					}

				}else{
					$respData = parent::insertPrefilled_f_mdl($prefilledTitle);

					$insertedId = $respData['insert_id'];

					if($respData['isSuccess'] == '1'){
						$is_done = 0;
						foreach ($notesArray as $value) {
							if(count($value) > 1){
								$is_done = 1;
							}else{
								$is_done = 0;
							}
							
							parent::insertPrefilledNotes_f_mdl($insertedId,$value[0],$is_done);
						}
					}
				}
			}
		}
	}

	function changeProductionStatusOrder(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "change_status_order"){
				$statusArray = parent::getVal("statusArray");

				$sql = 'SELECT id, status_order FROM production_status';
				$statusIdArray = parent::selectTable_f_mdl($sql);

				if(count($statusIdArray) == count($statusArray)){
					$seq_order = 1;
					for($i=0;$i< count($statusIdArray);$i++){
						$update_data = [
							'status_order' => $seq_order
						];
						parent::updateTable_f_mdl('production_status',$update_data,'id="'.$statusArray[$i].'"');

						$seq_order++;
					}
				}
			}
		}
	}
}
?>