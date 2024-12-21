<?php
include_once 'model/sa_general_settings_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
class sa_general_settings_ctl extends sa_general_settings_mdl
{
	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		common::CheckLoginSession();
		
	}
	
	function getGeneralSettingsInfo(){
		return parent::getGeneralSettingsInfo_f_mdl();
	}
	
	function addUpdateGeneralSettings(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "add-edit-general_settings"){
				$this->id = parent::getVal("hdn_id");
				$this->is_enable_in_wizard = parent::getVal("is_enable_in_wizard");
				$this->is_enable_in_cust_portal = parent::getVal("is_enable_in_cust_portal");

				$this->shipping_fs_silver_title = parent::getVal("shipping_fs_silver_title");
				$this->shipping_fs_silver_price = parent::getVal("shipping_fs_silver_price");
				$this->shipping_fs_gold_title = parent::getVal("shipping_fs_gold_title");
				$this->shipping_fs_gold_price = parent::getVal("shipping_fs_gold_price");
				$this->shipping_fs_platinum_title = parent::getVal("shipping_fs_platinum_title");
				$this->shipping_fs_platinum_first_item_price = parent::getVal("shipping_fs_platinum_first_item_price");
				$this->shipping_fs_platinum_additional_item_price = parent::getVal("shipping_fs_platinum_additional_item_price");
				$this->shipping_on_demand_title = parent::getVal("shipping_on_demand_title");

				$this->shipping_yardsign_first_item_price = parent::getVal("shipping_yardsign_first_item_price");
				$this->shipping_yardsign_additional_item_price = parent::getVal("shipping_yardsign_additional_item_price");
				$this->shipping_yardsign_title = parent::getVal("shipping_yardsign_title");

				$this->shipping_on_demand_price = parent::getVal("shipping_on_demand_price");
				$this->price_limit_shipping_on_demand = parent::getVal("price_limit_shipping_on_demand");
				$this->is_enable_return_center = parent::getVal("is_enable_return_center");

				$this->is_enable_in_basic = parent::getVal("is_enable_in_basic");
				$this->is_enable_in_bagged = parent::getVal("is_enable_in_bagged");
				$this->is_enable_in_home = parent::getVal("is_enable_in_home");
				
				$this->fullfilment_silver = addslashes(htmlspecialchars(parent::getVal("fullfilment_silver")));
				$this->fullfilment_gold = addslashes(htmlspecialchars(parent::getVal("fullfilment_gold")));
				$this->fullfilment_platinum = addslashes(htmlspecialchars(parent::getVal("fullfilment_platinum")));
				$this->fullfilment_ondemand = addslashes(htmlspecialchars(parent::getVal("fullfilment_ondemand")));
				$this->yard_sign_description = addslashes(htmlspecialchars(parent::getVal("yard_sign_description")));

				$this->terms_flash_sale = addslashes(htmlspecialchars(parent::getVal("terms_flash_sale"))); //Task 70
                $this->terms_on_demand  = addslashes(htmlspecialchars(parent::getVal("terms_on_demand")));  //Task 70

                /* Task 94 start */
                $this->free_shipping_text      = parent::getVal("free_shipping_text");
				$this->away_from_shipping_text = parent::getVal("away_from_shipping_text");
				$this->congrate_shipping_text  = parent::getVal("congrate_shipping_text");
				$this->text_color              = (!empty(parent::getVal("text_color")))?parent::getVal("text_color"):'';
				$this->color                   = (!empty(parent::getVal("color")))?parent::getVal("color"):'';
				$this->is_enable_ship_bar      = (!empty(parent::getVal("is_enable_ship_bar")))?parent::getVal("is_enable_ship_bar"):'';
				$this->font_size               = (!empty(parent::getVal("font_size")))?parent::getVal("font_size"):'';
				$this->font_weight             = (!empty(parent::getVal("font_weight")))?parent::getVal("font_weight"):'';
				$this->text_align              = (!empty(parent::getVal("text_align")))?parent::getVal("text_align"):'';

				/* Task 94 end */
				/*Task maximum color*/
				$this->maximum_color = parent::getVal("maximum_color");
				parent::updateMaximumColor_f_mdl($this->maximum_color);
				/*Task maximum color end */

				/*
				Add and Update : Minimum Group (Product)
				*/

				$group_order = parent::getVal("group_order");
				if($group_order){
					$sqlmaxcolor = 'SELECT maximum_color_value FROM `minimum_group_product` ';			
					$maxcolorDetails = parent::selectTable_f_mdl($sqlmaxcolor);
					$maxcolorvalue='1';
					if(!empty($maxcolorDetails)){
						$maxcolorvalue=$maxcolorDetails[0]['maximum_color_value'];
					}
					foreach($group_order AS $groupOrder => $group_id){
						$group_name = addslashes(htmlspecialchars(parent::getVal('group_name_'.$groupOrder.'')));
						$minimum_group_value = addslashes(htmlspecialchars(parent::getVal('minimums'.$groupOrder.''))); // Task 68add   minimum_group_value
						$maximum_group_value = addslashes(htmlspecialchars(parent::getVal('maximums'.$groupOrder.'')));/* Task 107 add new maximum_group_value */
						if($group_id == 'new'){
							parent::addMinimumProductGroup_f_mdl($group_name, $minimum_group_value ,$groupOrder,$maximum_group_value,$maxcolorvalue); //Task 68 add  minimum_group_value /* Task 107 add new maximum_group_value */
						}else{
							parent::updateMinimumProductGroup_f_mdl($group_id, $group_name, $minimum_group_value ,$groupOrder,$maximum_group_value,$maxcolorvalue); //Task 68 add minimum_group_value /* Task 107 add new maximum_group_value */
						}
					}
				}
				
				/*$group_name = parent::getVal("group_name");
				if($group_name){
					foreach($group_name AS $groupName){
						parent::addMinimumProductGroup_f_mdl($groupName);
					}
				}

				$update_group_name = parent::getVal("update_group_name");
				if($update_group_name){
					foreach($update_group_name AS $group_id => $groupName){
						parent::updateMinimumProductGroup_f_mdl($group_id, $groupName);
					}
				}*/

				$remove_group = parent::getVal("remove_group");
				if($remove_group){
					foreach($remove_group AS $group_id){
						parent::deleteMinimumProductGroup_f_mdl($group_id);
					}
				}
				

				/*2021*/



				if($this->id > 0){
					parent::updateGeneralSettings_f_mdl();
				} else{
					parent::addGeneralSettings_f_mdl();
				}

				$generalSettingInfo = parent::getGeneralSettingsInfo_f_mdl();
				$commonFile = common::SHOPIFY_DIRECTORY_PATH.'/model/common-setting-data.json';
				$json_data = json_encode($generalSettingInfo[0]);
				file_put_contents($commonFile, $json_data);
				
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

	function getGeneralSettingsproductGroup(){
		return parent::getMinimumProductGroup_f_mdl();
	}

	public function getAllStoreList()
	{
		$sql = 'SELECT id,store_name FROM `store_master` WHERE status = 1 and store_name !="" ';			
		return $storeDetails = parent::selectTable_f_mdl($sql);
	}

	public function getStores()
	{
		if(parent::isPOST()){
			if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "getStores"){
				$store_sale_type_master_id = parent::getVal("store_sale_type_id");
				$store_condtion = '';
				if($store_sale_type_master_id!=''){
					$store_condtion = 'AND store_sale_type_master_id = '.$store_sale_type_master_id.'';
				}
				$sql = 'SELECT id,store_name,store_sale_type_master_id FROM `store_master` WHERE status = 1 and store_name !="" '.$store_condtion.' ';			
				$storeDetails = parent::selectTable_f_mdl($sql);
				foreach ($storeDetails as $value) { ?>
					<input type="checkbox" value="<?=$value['id']?>" class="checkBoxClass">&nbsp;&nbsp;<label for=""><?=$value['store_name'] ?></label><br>	
				<?php 
				}
				die;
			}
		}
	}

	public function updateStoreDescription()
	{	
		global $path;
		$res  = array();
		if(parent::isPOST()){
			if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "updateStoreDescription"){
				$store_ids = implode(",",parent::getVal("store_master_ids"));
				$sql          = 'SELECT id,store_fulfillment_type,store_sale_type_master_id,shop_collection_id,additional_info FROM `store_master` WHERE id IN('.$store_ids.') ';		
				$storeDetails = parent::selectTable_f_mdl($sql);
				$res  = array();
				if(!empty($storeDetails)){
					foreach($storeDetails as $value){
						$additional_info = "<p>".(!empty($value['additional_info']))?$value['additional_info']:''."</p>";
						$store_description = '';
						if($value['store_sale_type_master_id'] == 1){
							if(isset($value['store_fulfillment_type']) && $value['store_fulfillment_type']=='SHIP_1_LOCATION_NOT_SORT'){
								$store_description = COMMON::DESCRIPTION_FOR_OPEN_FLASH_STORE;
							}else if(isset($value['store_fulfillment_type']) && $value['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
								$store_description = COMMON::DEFAULT_DESCRIPTION_FOR_SILVER_GOLD_FULFILLMENT;
							}else if(isset($value['store_fulfillment_type']) && $value['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
								$store_description = COMMON::DEFAULT_DESCRIPTION_FOR_PLATINUM_FULFILLMENT;
							}
						}else{
							$store_description = common::DESCRIPTION_FOR_OPEN_ONDEMAND_STORE;
						}

						$updateDescription = [
							'store_description' => $store_description
						];
						
						parent::updateTable_f_mdl('store_master',$updateDescription,'id="'.$value['id'].'"');
						require_once($path.'lib/class_graphql.php');

						$shop      = common::PARENT_STORE_NAME;
						$shop_sql  = "SELECT shop_name, token FROM `shop_management` WHERE shop_name='".$shop."'";
						$shop_data = parent::selectTable_f_mdl($shop_sql);
						if(!empty($shop_data)) {
							require_once($path.'lib/class_graphql.php');

							$shop = $shop_data[0]['shop_name'];
							$token = $shop_data[0]['token'];

							$headers = array(
								'X-Shopify-Access-Token' => $token
							);
							$graphql = new Graphql($shop, $headers);

							$mutationData = 'mutation collectionUpdate($input: CollectionInput!) {
							collectionUpdate(input: $input) {
								collection {
								id
								}
								job {
								id
								}
								userErrors {
								field
								message
								}
							}
							}';
							$inputData = '{
							"input": {
								"id":"gid://shopify/Collection/'.$value['shop_collection_id'].'",
								"descriptionHtml":"'.trim($store_description).$additional_info.'"
							}
							}';
							$graphql->runByMutation($mutationData,$inputData);
						}
					}
					$res['SUCCESS'] = 'TRUE';
					$res['MESSAGE'] = 'Description update successfully.';
				}
				else{
					$res['SUCCESS'] = 'FALSE';
					$res['MESSAGE'] = '!Something went wrong.';
				}
				common::sendJson($res);die;
			}
		}
	}

	/* Task 82 start*/
	function restoreGroup()
	{
		if(parent::isPOST()){
			if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "restoreGroup"){
				$group_id = parent::getVal("group_id");
				$update = parent::restoreMinimumProductGroup_f_mdl($group_id);die();
			}
		}
	}
	/* Task 82 end*/

	public function getFaqVideos()
	{
		$sql     = 'SELECT * FROM `faq_videos`';
		$faqData = parent::selectTable_f_mdl($sql);
		return $faqData;die();
	}

	public function addUpdateFaqSettings(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "add-edit-faq_settings"){
				$res = array();
				$group_order = parent::getVal("group_order");
				if($group_order){
					foreach($group_order as $groupOrder => $faq_id){
						$video_title = parent::getVal('group_name_'.$groupOrder.'');
						$video_url   = parent::getVal('minimums'.$groupOrder.'');
						if($faq_id == 'new'){
							parent::addFaqVideos_f_mdl($video_title, $video_url);
						}else{
							parent::updateFaqVideos_f_mdl($faq_id, $video_title, $video_url);
						}
					}
					$res['SUCCESS'] = 'TRUE';
					$res['MESSAGE'] = 'Faq data save successfully.';
				}
				else{
					$res['SUCCESS'] = 'FALSE';
					$res['MESSAGE'] = '!Something went wrong.';
				}

				$remove_group = parent::getVal("remove_group");
				if($remove_group){
					foreach($remove_group AS $faq_id){
						parent::deleteFaqVideos_f_mdl($faq_id);
					}
					$res['SUCCESS'] = 'TRUE';
					$res['MESSAGE'] = 'Faq delete successfully.';
				}
				echo json_encode($res);die;
			}
		}
	}

	/* Task 110 start */
	public function getProfitDetails()
	{
		$profitSql  = 'SELECT * FROM `profit_cost_details`';
		$profitData = parent::selectTable_f_mdl($profitSql);
		return $profitData;die();
	}

	public function addEditProfitSetting()
	{	if(parent::isPOST()){
			if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "add-edit-profit_settings"){
				
				$label_order   = parent::getVal("group_order");
				if($label_order){
					foreach($label_order AS $labelOrder => $label_id){
						$label_name = addslashes(htmlspecialchars(parent::getVal('group_name_'.$labelOrder.'')));
						$lable_profit = trim(parent::getVal('group_price_'.$labelOrder.''));
						if($label_id == 'new'){
							parent::addLabel_f_mdl($label_name,$lable_profit);
						}else{
							parent::updateLabel_f_mdl($label_id, $label_name,$lable_profit);
						}
					}
					$res['SUCCESS'] = 'TRUE';
					$res['MESSAGE'] = 'Label data save successfully.';
				}
				else{
					$res['SUCCESS'] = 'FALSE';
					$res['MESSAGE'] = '!Something went wrong.';
				}

				$remove_group = parent::getVal("remove_group");
				if($remove_group){
					foreach($remove_group AS $faq_id){
						parent::deleteLabel_f_mdl($faq_id);
					}
					$res['SUCCESS'] = 'TRUE';
					$res['MESSAGE'] = 'Label delete successfully.';
				}
				echo json_encode($res);die;
			}
			
		}
	}
	/* Task 110 end */

	/* Task 111 start */
	public function getApprovalChecks()
	{
		$checksSql  = 'SELECT * FROM `approval_checks_detail`';
		$checksData = parent::selectTable_f_mdl($checksSql);
		return $checksData;die();
	}

	public function addEditApprovalChecks()
	{	if(parent::isPOST()){
			if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "add-edit-approval-checks"){
				$approval_checks   = parent::getVal("group_order");
				if (!empty($approval_checks)) {
					foreach($approval_checks AS $approvalChecks => $checks_id){
						$checksMessage = addslashes(htmlspecialchars(parent::getVal('group_name_'.$approvalChecks.'')));
						if(!empty($checksMessage)){
							if($checks_id == 'new'){
								parent::addApprovalChecks_f_mdl($checksMessage);
							}else{
								parent::updateApprovalChecks_f_mdl($checks_id, $checksMessage);
							}
						}	
					}
					$res['SUCCESS'] = 'TRUE';
					$res['MESSAGE'] = 'Approval checks data save successfully.';
				}
				else{
					$res['SUCCESS'] = 'FALSE';
					$res['MESSAGE'] = '!Something went wrong.';
				}

				$remove_approval_checks = parent::getVal("remove_group");
				if($remove_approval_checks){
					foreach($remove_approval_checks AS $check_id){
						parent::deleteApprovalChecks_f_mdl($check_id);
					}
					$res['SUCCESS'] = 'TRUE';
					$res['MESSAGE'] = 'Approval checks delete successfully.';
				}
				echo json_encode($res);die;
			}
		}
	}
	/* Task 111 end */

	public function update_checked_status()
	{
		if(parent::isPOST()){
			if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "update_checked_status"){
				$group_id = parent::getVal("group_id");
				$is_checked = parent::getVal("is_checked");
				$result=parent::updateProductGroup_f_mdl($group_id,$is_checked);

				$resultArray = array();
				if($result){
					$resultArray["isSuccess"] = "1";
					$resultArray["msg"] = "Settings saved successfully.";
				}
				else{
					$resultArray["isSuccess"] = "0";
					$resultArray["msg"] = "Oops! there is somethimg wrong. Please try again.";
				}
				common::sendJson($resultArray);
			}
		}
	}

	public function addLanguage(){
		$status = false;
		
		if(!empty($_POST['language_text'])){
			$language_text = $_POST['language_text'];
			$language_text_minimumtext =$_POST['language_text_minimumtext'];
			if(parent::isPOST()){
				if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "addLanguage"){
					$status = parent::addLanguage_f_mdl($_POST['lid'], $_POST['language_text'],$_POST['lid_minimumtext'], $_POST['language_text_minimumtext']);
				}
			}
		}

		echo $status;
	}

	public function getLanguage(){
		$checksSql  = 'SELECT * FROM `languages_text`';
		$checksData = parent::selectTable_f_mdl($checksSql);
		return $checksData;die();
	}

	public function updateLableCostCheckedStatus()
	{	if(parent::isPOST()){
			if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "update_costlable_checked_status"){
				$result=array();
				$lable_cost_id   = parent::getVal("lable_cost_id");
				$is_checked   = parent::getVal("is_checked");
				$result=parent::updateLableCostStatus_f_mdl($lable_cost_id,$is_checked);
				common::sendJson($result);
			}
		}
	}

	public function getWelcomeVideoData()
	{
		$sql     = 'SELECT * FROM `welcome_video_master`';
		$videoData = parent::selectTable_f_mdl($sql);
		return $videoData;die();
	}

	public function addEditWelcomeVideo(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "add_edit_welcome_video"){
				$res = array();
				$welcome_video_link = parent::getVal("welcome_video_link");
				$welcome_video_status = parent::getVal("welcome_video_status");
				$id = parent::getVal("id");

				if(empty($id)){
					$soim_insert_data = [
		                'video_link' => $welcome_video_link,
		                'created_on' => @date('Y-m-d H:i:s')
		            ];
		            parent::insertTable_f_mdl('welcome_video_master',$soim_insert_data);

				}else{
					$update_data = [
						'video_link' => $welcome_video_link,
						'video_status' => $welcome_video_status,
						'updated_on' => @date('Y-m-d H:i:s')
					];
					parent::updateTable_f_mdl('welcome_video_master',$update_data,'id="'.$id.'"');
				}
	
				$res['SUCCESS'] = 'TRUE';
				$res['MESSAGE'] = 'Welcome video data save successfully.';
				
				echo json_encode($res);die;
			}
		}
	}

	public function update_fulfillment_price()
	{
		if(parent::isPOST()){
			if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "update_fulfillment_price"){
				$fullfilment_silver_price = parent::getVal("fullfilment_silver_price");
				$fullfilment_gold_price = parent::getVal("fullfilment_gold_price");
				$fullfilment_platinum_price = parent::getVal("fullfilment_platinum_price");

				$update_data = [
					'fullfilment_silver_price' 		=> $fullfilment_silver_price,
					'fullfilment_gold_price' 		=> $fullfilment_gold_price,
					'fullfilment_platinum_price' 	=> $fullfilment_platinum_price
				];
				parent::updateTable_f_mdl('general_settings_master',$update_data,'id="1"');

				$resultArray = array();
				$resultArray["isSuccess"] = "1";
				$resultArray["msg"] = "Settings saved successfully.";
				echo json_encode($resultArray);die;
			}
		}
	}

}
?>
