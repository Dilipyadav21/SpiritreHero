<?php
include_once 'model/cron_set_flash_sale_end_mdl.php';
include_once $path . '/libraries/Aws3.php';
class cron_set_flash_sale_end_ctl extends cron_set_flash_sale_end_mdl
{
	function __construct(){
		$this->startFlashSaleUnpublishCron();
	}
	
	function startFlashSaleUnpublishCron(){

		//Check email only testing purpose
		/*require_once("lib/class_aws.php");
	    $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
	    $mailSendStatus = $objAWS->sendEmail(["sanjay@bitcot.com"], "startFlashSaleUnpublishCron", "Message: startFlashSaleUnpublishCron", "Message: startFlashSaleUnpublishCron");*/
	    //end Check email..

		#region - Get 50 Stores Rows
		$storesInfo = parent::getFlashSaleStoresInfo_f_mdl();
		#endregion
		
		if(count($storesInfo) > 0){
			#region - Get Store Info
			$storeInfo = parent::getStoreInfo_f_mdl();
			#endregion
			
			#region - Initialize Shopify Class Object
			require_once('lib/shopify.php');
            $shopifyObject = new ShopifyClient($storeInfo[0]["shop_name"], $storeInfo[0]["token"], common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);

			require_once('lib/class_graphql.php');
			$headers = array(
				'X-Shopify-Access-Token' => $storeInfo[0]['token']
			);
			$graphql = new Graphql($storeInfo[0]['shop_name'], $headers);
			#endregion
			
			#region - Check Loop Through Flash Sale Ends Or Not
			foreach($storesInfo as $objStore){
				$storeMasterId = $objStore["store_master_id"];
				$storeSaleTypeMasterId = $objStore["store_sale_type_master_id"];
				$afterFlashSaleStoreWill = $objStore["after_flash_sale_store_will"];
				$storeCloseDate = $objStore["store_close_date"];
				$shopCollectionId = $objStore["shop_collection_id"];
				
				/*Task 68 Summery mail mail for minimum products groups*/
				$store_name=$objStore['store_name'];
				$first_name=$objStore['first_name'];
				$email=$objStore['email'];
				$store_owner_id=$objStore['store_owner_id'];
				/*Task 68 Summery mail mail for minimum products groups end*/

				$saleTypeMasterId = $objStore["store_sale_type_master_id"];

				if($storeCloseDate < time()){
					$isProcessForUnpublish = false;
					
					#region - Set Collection Updates
					if($storeSaleTypeMasterId == common::STORE_TYPE_FLASH_SALE_ID && $afterFlashSaleStoreWill == common::FLASH_SALE_AFTER_CLOSED){
						$isProcessForUnpublish = true;
					}
					else if($storeSaleTypeMasterId == common::STORE_TYPE_ON_DEMAND_SALE_ID && $afterFlashSaleStoreWill == common::FLASH_SALE_AFTER_CLOSED){
						$isProcessForUnpublish = true;
					}
					else if($storeSaleTypeMasterId == common::STORE_TYPE_FLASH_SALE_ID && $afterFlashSaleStoreWill == common::FLASH_SALE_AFTER_ON_DEMAND){
						#region - Update Store Sale Type To On Demand
						parent::updateStoreTypeId_f_mdl($storeMasterId);
						#endregion
					}
					#endregion
					
					if($isProcessForUnpublish){
						#region - Add Store Close Metafield To Existing Collection
						try{
							//if store end-date over, then store goes in "Batch", not fully closed. When admin move store in production, then store will be close
							$currenDate     = date('Y-m-d H:i:s');/* Task 117 */
							parent::updateBatchStatus_f_mdl($storeMasterId,'1',$currenDate);/* Task 117 add currenDate*/

							#region - updae meta in shopify
							/*$collection_id = $shopCollectionId;
							$meta_namespace = common::FLASH_SALE_END_NAMESPACE;
							$meta_key = common::FLASH_SALE_END_KEY;
							$meta_value = common::FLASH_SALE_END_VALUE;
							$des_for_close_store = common::DESCRIPTION_FOR_CLOSED_STORE;

							$query = '{
							  collection(id:"gid://shopify/Collection/'.$collection_id.'"){
								metafields(first:100){
								  edges{
									node{
									  id namespace key
									}
								  }
								}
							  }
							}';
							sleep(0.5);
							$coll_meta_data = $graphql->runByQuery($query);
							$collection_meta_id_for_close = '';
							if(isset($coll_meta_data['data']['collection']['metafields']['edges']) && !empty($coll_meta_data['data']['collection']['metafields']['edges'])){
								foreach($coll_meta_data['data']['collection']['metafields']['edges'] as $meta_edge){
									if($meta_edge['node']['namespace']==$meta_namespace && $meta_edge['node']['key']==$meta_key){
										$collection_meta_id_for_close = $meta_edge['node']['id'];
									}
								}
							}
							if(empty($collection_meta_id_for_close)){
								$mutationData = 'mutation collectionUpdate($input: CollectionInput!) {
								  collectionUpdate(input: $input) {
									collection {
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
									"id":"gid://shopify/Collection/'.$collection_id.'",
									"descriptionHtml":"'.$des_for_close_store.'",
									"metafields": [
									  {
										"namespace": "'.$meta_namespace.'",
										"key": "'.$meta_key.'",
										"value": "'.$meta_value.'",
										"valueType": "STRING"
									  }
									]
								  }
								}';
								sleep(0.5);
								$graphql->runByMutation($mutationData,$inputData);
							}*/
							#endregion

							//Update Collection Unpublish Status
							//parent::updateCollectionUnpublishStatus_f_mdl($storeMasterId,$des_for_close_store);

							//below line is commented, because now email is not send after close. But anaother cron runs parallel, which send emails before 3 days of store close
							//$this->sendCustomerEmail($storeMasterId,common::EMAIL_TO_CUSTOMER_ADMIN_WHEN_FLASH_SALE_IS_OVER);

							//send mail to super-admin about store-close
							//$this->sendSuperAdminEmail($storeMasterId,common::EMAIL_TO_SUPER_ADMIN_WHEN_FLASH_SALE_IS_OVER);

							#region - Email To Purchaser
							if($saleTypeMasterId == common::STORE_TYPE_FLASH_SALE_ID){

								//send mail to super-admin about store-close
								$this->sendSuperAdminEmail($storeMasterId,common::EMAIL_TO_SUPER_ADMIN_WHEN_FLASH_SALE_IS_OVER);

								/*Task 68 Summery mail mail for minimum products groups*/
								$this->sendMinimumProductSummeryEmail($storeMasterId,$store_name,$first_name,$email,$store_owner_id);
								/*Task 68 Summery mail mail for minimum products groups end*/
								
								$purchaserEmailArray = parent::getPurchaserInfo_f_mdl($storeMasterId);
								
								if(!empty($purchaserEmailArray) > 0){
									foreach($purchaserEmailArray as $objEmail){				
										//$this->sendPurchaserEmail($objEmail['cust_email'],$objEmail['cust_name'],common::EMAIL_TO_PURCHASERS_FOR_FLASH_SALE_ONLY_NOT_ON_DEMAND,$storeMasterId);
									}
								}
							}
							#endregion
						}
						catch(ShopifyApiException $e){
							print_r($e);
						}
						catch(ShopifyCurlException $e){
							print_r($e);
						}
						#endregion
					}
				}
				
				#region - Update Cron Run DateTime
				parent::updateCollectionCronRunDateTime_f_mdl($storeMasterId);
				#endregion
			}
			#endregion
		}
	}

	function sendCustomerEmail($storeMasterId,$emailTemplateId){
		$s3Obj = new Aws3;
		$ownerInfo = parent::getEmailInfo_f_mdl($storeMasterId);

		#region - Send Mail To Store Admin
		require_once(common::EMAIL_REQUIRE_URL);
        if(strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp')!==false){
            $objAWS = new aws_ses_smtp();
        }else if(strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail')!==false){
            $objAWS = new sendGridEmail();
        }else{
            $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
        }

		$emailData = parent::getEmailTemplateInfo($emailTemplateId);
		$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
		$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
		$subject = $emailData[0]['subject'];
		$to_email = $ownerInfo['0']['email'];
		$from_email = common::AWS_ADMIN_EMAIL;
		$attachment = [];

		$bulk_order_link = common::CUSTOMER_BULK_ORDER_URL.$storeMasterId;

		//$mailSendStatus = $objAWS::sendEmail($from_email, $to_email, $subject, str_replace(["{{FIRST_NAME}}","{{BULK_ORDER_LINK}}","{{DASHBOARD_LINK}}"],[$ownerInfo['0']['first_name'],$bulk_order_link,common::CUSTOMER_ADMIN_DASHBOARD_URL],$emailData[0]['body']), $attachment);$attachment);

		$sql = 'SELECT * FROM store_master WHERE id="'.$storeMasterId.'"';
        $store_data = parent::selectTable_f_mdl($sql);

		$store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
		$store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;

		$mailSendStatus = 1;
		//if($store_data[0]['email_notification'] == '1'){
			$mailSendStatus = $objAWS->sendEmail([$to_email], $subject,str_replace(["{{FIRST_NAME}}","{{STORE_NAME}}","{{BULK_ORDER_LINK}}","{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$ownerInfo['0']['first_name'],$store_data[0]['store_name'],$bulk_order_link,common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date],$emailData[0]['body']),str_replace(["{{FIRST_NAME}}","{{STORE_NAME}}","{{BULK_ORDER_LINK}}","{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$ownerInfo['0']['first_name'],$store_data[0]['store_name'],$bulk_order_link,common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date],$emailData[0]['body']));
		//}
		#endregion

		$resultArray = array();

		if($mailSendStatus){
			$resultArray["isSuccess"] = "1";
			$resultArray["msg"] = "Changes saved successfully.";
		}
		else{
			$resultArray["isSuccess"] = "0";
			$resultArray["msg"] = "Oops! there is some issue during insert. Please try again.";
		}
		//common::sendJson($resultArray);
	}
	
	function sendSuperAdminEmail($storeMasterId,$emailTemplateId){
		$s3Obj = new Aws3;
		$storeInfo = parent::getEmailInfo_f_mdl($storeMasterId);

		require_once(common::EMAIL_REQUIRE_URL);
        if(strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp')!==false){
            $objAWS = new aws_ses_smtp();
        }else if(strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail')!==false){
            $objAWS = new sendGridEmail();
        }else{
            $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
        }

		$emailData = parent::getEmailTemplateInfo($emailTemplateId);
		$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
		$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
		/* Task 55 start 19/10/2021 */
		$ccMails = '';
		if($emailData[0]['recipients']){
			$recipients = $emailData[0]['recipients'];
			$recipients = str_replace(' ', '', $recipients);
			$ccMails = explode(',', $recipients);
		}
		/* Task 55 end 19/10/2021 */

		$subject = $emailData[0]['subject'];
		$to_email = common::SUPER_ADMIN_EMAIL;
		$from_email = common::AWS_ADMIN_EMAIL;
		$attachment = [];

		//$mailSendStatus = $objAWS::sendEmail($from_email, $to_email, $subject, str_replace(["{{STORE_NAME}}"],[$storeInfo['0']['store_name']],$emailData[0]['body']), $attachment);

		$sql = 'SELECT * FROM store_master WHERE id="'.$storeMasterId.'"';
        $store_data = parent::selectTable_f_mdl($sql);

		$store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
		$store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;

		$mailSendStatus = 1;
		//if($store_data[0]['email_notification'] == '1'){
			$mailSendStatus = $objAWS->sendEmail([$to_email], $subject,str_replace(["{{STORE_NAME}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}" ],[$storeInfo['0']['store_name'],$logo,$store_open_date,$store_last_date],$emailData[0]['body']), str_replace(["{{STORE_NAME}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}" ],[$storeInfo['0']['store_name'],$logo,$store_open_date,$store_last_date],$emailData[0]['body']),$ccMails);// Task 55 19/10/2021 Add $ccMails
		//}
	}

	function sendPurchaserEmail($email,$name,$emailTemplateId,$storeMasterId){
		#region - Send Mail To Store Admin
		$s3Obj = new Aws3;
		require_once(common::EMAIL_REQUIRE_URL);
        if(strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp')!==false){
            $objAWS = new aws_ses_smtp();
        }else if(strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail')!==false){
            $objAWS = new sendGridEmail();
        }else{
            $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
        }
		
		$emailData = parent::getEmailTemplateInfo($emailTemplateId);
		$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
		$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
		$subject = $emailData[0]['subject'];
		$to_email = $email;
		$from_email = common::AWS_ADMIN_EMAIL;
		$attachment = [];
		
		//$mailSendStatus = $objAWS::sendEmail($from_email, $to_email, $subject, str_replace("{{FIRST_NAME}}",$name,$emailData[0]['body']), $attachment);

		$sql = 'SELECT * FROM store_master WHERE id="'.$storeMasterId.'"';
        $store_data = parent::selectTable_f_mdl($sql);

		$store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
		$store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;

		$mailSendStatus = 1;
		if($store_data[0]['email_notification'] == '1'){
			$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, str_replace(["{{CUSTOMER_NAME}}","{{STORE_NAME}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$name,$store_data[0]['store_name'],$logo,$store_open_date,$store_last_date],$emailData[0]['body']), str_replace(["{{CUSTOMER_NAME}}","{{STORE_NAME}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$name,$store_data[0]['store_name'],$logo,$store_open_date,$store_last_date],$emailData[0]['body']));
		}
		#endregion
		
		$resultArray = array();
		 
		if($mailSendStatus){
			$resultArray["isSuccess"] = "1";
			$resultArray["msg"] = "Changes saved successfully.";
		}
		else{
			$resultArray["isSuccess"] = "0";
			$resultArray["msg"] = "Oops! there is some issue during insert. Please try again.";
		}
		//common::sendJson($resultArray);
	}

	/*Task 68 Send Mail for summery of product minimum groups*/
    public function sendMinimumProductSummeryEmail($store_master_id,$store_name,$first_name,$email,$store_owner_id)
    {   
        $s3Obj = new Aws3;
		require_once(common::EMAIL_REQUIRE_URL);
        if (strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp') !== false) {
            $objAWS = new aws_ses_smtp();
        } else if (strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail') !== false) {
            $objAWS = new sendGridEmail();
        } else {
            $objAWS = new Aws(common::AWS_ACCESS_KEY, common::AWS_SECRET_KEY, common::AWS_REGION);
        }

		$store_sql = 'SELECT verification_status,shop_collection_handle FROM `store_master` WHERE id="'.$store_master_id.'"';
		$stor_data = parent::selectTable_f_mdl($store_sql);
		$store_open_date=!empty($stor_data[0]["store_open_date"]) ? date('m/d/Y', $stor_data[0]["store_open_date"]) : '' ;
		$store_last_date=!empty($stor_data[0]["store_close_date"]) ? date('m/d/Y', $stor_data[0]["store_close_date"]) : '' ;

        $product_group_name = parent::getGroupName_f_mdl($store_master_id);
        $emailData = array();
        $minimumMetAarray = array();
		$soldItems = '';
        foreach ($product_group_name as $group_name) {
            $store_owner_group_name        = $group_name['group_name'];
            $groupItemSql = 'SELECT id FROM store_owner_product_master WHERE group_name="'.$store_owner_group_name.'" and store_master_id = "'.$store_master_id.'" ';
            $groupItemDetails = parent::selectTable_f_mdl($groupItemSql);

            $dataIds = array();
            foreach ($groupItemDetails as $value) {
                $dataIds[]=$value['id'];
            }
			
            $soldSql = 'SELECT IFNULL(SUM(oim.quantity),0) as sold_items,om.store_master_id,oim.store_owner_product_master_id ,oim.title,om.id as store_order_master_id FROM `store_orders_master` as om INNER JOIN store_order_items_master as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 AND oim.store_owner_product_master_id in('.implode(',', $dataIds).')';
            $qtySold = parent::selectTable_f_mdl($soldSql);
            $soldItems = '';
            if (!empty($qtySold)) {
                $soldItems = $qtySold[0]['sold_items']; 
            }
            $minimum_group_value = parent::get_minimums_f_mdl($store_owner_group_name);
            $group_value = '';
            if (!empty($minimum_group_value)) {
                $group_value         = $minimum_group_value[0]['minimum_group_value'];
            }
           	

            $minimums            = "";
            if ($soldItems >=$group_value) {
           		$minimums = 'Minimums Met';
           		$minimumMetAarray[]=0;
           	}
            else{
            	$minimums        = $group_value - $soldItems;
            	$minimumMetAarray[]=1;
            }
            
            $emailData[]         = "<br>" . "Product Group Name: :" . $group_name['group_name'] . "<br>" . "Minimum for this product group:$group_value " . "<br>" . "# of items sold: $soldItems " . "<br>" . "# of items needed to meet minimum: $minimums"."<br>";
        }
        $htmlValues='';
        foreach ($emailData as $key => $value) {
           $htmlValues.=$value;
        }
        
        $sql            = 'SELECT subject,body,recipients FROM `email_templates_master` WHERE id = 20';
        $minimums_data  = parent::selectTable_f_mdl($sql);
		$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
		$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
        $body           = '';
        $subject        = '';
        if (!empty($minimums_data)) {
            $body    = $minimums_data[0]['body'];
            $subject = $minimums_data[0]['subject'];
        } 
		$manualOrderLink = '<a href="'.common::CUSTOMER_PORTAL_SITE_URL.'closeStoreList.php?do=closeStoreList">Click here</a>';
        $dashboardLink   = '<a href="'.common::CUSTOMER_PORTAL_SITE_URL.'index.php?do=stores">Click here</a>';   
		$front_store_url ="https://" . common::PARENT_STORE_NAME . "/collections/" .$stor_data[0]["shop_collection_handle"];

        $body     = str_replace('{{FIRST_NAME}}', $first_name, $body);
        $body     = str_replace('{{STORE_NAME}}', $store_name, $body);
        // $body     = str_replace('{{SOLD_ITEMS}}', $soldItems,  $body);
        $body     = str_replace('{{PRODUCTS_SUMMERY}}', $htmlValues, $body);
		$body     = str_replace('{{ORDER_LINK}}', $manualOrderLink, $body);
		$body     = str_replace('{{DASHBOARD_LINK}}', $dashboardLink, $body);
		$body 	  = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
		$body     = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
		$body     = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
		$body     = str_replace('{{FRONT_STORE_URL}}', trim($front_store_url), $body);
        $html     = $body;
        $to_email = $email;
		if($stor_data[0]['verification_status'] == 1){
        
			if (in_array("1", $minimumMetAarray)){
				$objAWS->sendEmail([$to_email], $subject, $html,'');
				/*send mail store manager */
				$sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_id . '"';
				$smm_data =  parent::selectTable_f_mdl($sql_managerData);
				if(!empty($smm_data)){
					foreach ($smm_data as $managerData) {
						$body     = $minimums_data[0]['body'];
						$to_email = $managerData['email'];
						$body     = str_replace('{{FIRST_NAME}}', $managerData['first_name'], $body);
						$body     = str_replace('{{STORE_NAME}}', $store_name, $body);
						// $body     = str_replace('{{SOLD_ITEMS}}', $soldItems,  $body);
						$body     = str_replace('{{PRODUCTS_SUMMERY}}', $htmlValues, $body);
						$body     = str_replace('{{ORDER_LINK}}', $manualOrderLink, $body);
						$body     = str_replace('{{DASHBOARD_LINK}}', $dashboardLink, $body);
						$body 	  = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
						$body     = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
						$body     = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
						$html     = $body;
						$objAWS->sendEmail([$to_email], $subject, $html,'');
					}
				}
				/*send mail store manager */
			}
			else{

			}
		}
    }
}
?>