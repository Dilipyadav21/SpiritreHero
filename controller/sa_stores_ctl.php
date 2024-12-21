<?php
include_once 'model/sa_stores_mdl.php';
include_once('helpers/createStoreHelper.php');
include_once('helpers/storeHelper.php');

$path = preg_replace('/controller(?!.*controller).*/', '', __DIR__);
include_once $path . 'libraries/Aws3.php';
$s3Obj = new Aws3;
$login_user_email="";
if(isset($_SESSION['user_email']) && $_SESSION['user_email'] != "") {
	$login_user_email=trim($_SESSION['user_email']);
}
class sa_stores_ctl extends sa_stores_mdl
{
	public $TempSession = "";

	function __construct()
	{
		if (parent::isGET() || parent::isPOST()) {
			if (parent::getVal("method")) {
				$this->checkRequestProcess(parent::getVal("method"));
			} else {
				$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			}
			//$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}

		common::CheckLoginSession();
	}

	function checkRequestProcess($requestFor)
	{
		if ($requestFor != "") {
			switch ($requestFor) {
				case "updateBatched":
					$this->updateBatched();
					break;
				case "moveToProduction":
					$this->moveToProduction();
					break;
				case "addNotes":
					$this->addNotes();
					break;
				case "deleteStore":
					$this->deleteStore();
					break;
				case "updateStatus":
					$this->updateVarificationStatus();
					break;
				case "updateStatus":
					$this->updateVarificationStatus();
					break;
				case "fetch-stkn":
					$this->fetchStoreTokenInfo();
					break;
				case "duplicateStore":
					$this->save_customer_store_data();
					break;
				case "composeMail":
					$this->composeMail();
					break;
				case "bulk_delete":
					$this->bulk_delete();
					break;
				case "export_stores":
					$this->exportStores();
					break;
				case "dateFilter":
					$this->dateFilter();
				case "exportOrdersToPrintavo":
					$this->exportOrdersToPrintavo();
					break;
				case "exportOrdersToPrintavoByGroup":
					$this->exportOrdersToPrintavoGroupWise();
					break;
				case "move_to_archive":
					$this->move_to_archive();
					break;
				case "move_to_unarchive":
					$this->move_to_unarchive();
					break;
				case "archiveStore":
					$this->archiveStore();
					break;
				case "unarchiveStore":
					$this->unarchiveStore();
					break;
				case "bulk_close_store":
					$this->bulkCloseStore();
					break;
				case "resendLoginInfo":
					$this->resendLoginInfo();
					break;
				case "puse_store_syncing":
					$this->puse_store_syncing();
				break;
				case "update_email_sa":
					$this->updateEmailSa();
				break;
			}
		}
	}

	function updateBatched()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "updateBatched") {

				$storeMasterId = parent::getVal("store_master_id");
				$storeBatched = parent::getVal("is_store_batched");

				parent::updateBatched_f_mdl($storeMasterId, $storeBatched);
			}
		}
	}

	function moveToProduction()
	{
		global $s3Obj;
		global $login_user_email;
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "moveToProduction") {
				require_once('lib/class_graphql.php');
				require_once(common::EMAIL_REQUIRE_URL);

				$storeMasterId = parent::getVal("store_master_id");
				$des_for_close_store = common::DESCRIPTION_FOR_CLOSED_STORE;

				$sql = "SELECT id, shop_name, token, timezone FROM shop_management WHERE id = 1";
				$shopInfo = parent::selectTable_f_mdl($sql);

				$headers = array(
					'X-Shopify-Access-Token' => $shopInfo[0]['token']
				);
				$graphql = new Graphql($shopInfo[0]['shop_name'], $headers);

				$sql = "SELECT shop_collection_id FROM store_master WHERE id = " . $storeMasterId;
				$storeInfo = parent::selectTable_f_mdl($sql);

				//change status in database
				parent::moveToProduction_f_mdl($storeMasterId, $des_for_close_store);

				#region - update meta in shopify
				$collection_id = $storeInfo[0]['shop_collection_id'];
				$meta_namespace = common::FLASH_SALE_END_NAMESPACE;
				$meta_key = common::FLASH_SALE_END_KEY;
				$meta_value = common::FLASH_SALE_END_VALUE;
				$des_for_close_store = common::DESCRIPTION_FOR_CLOSED_STORE;

				$query = '{
                  collection(id:"gid://shopify/Collection/' . $collection_id . '"){
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
				if (isset($coll_meta_data['data']['collection']['metafields']['edges']) && !empty($coll_meta_data['data']['collection']['metafields']['edges'])) {
					foreach ($coll_meta_data['data']['collection']['metafields']['edges'] as $meta_edge) {
						if ($meta_edge['node']['namespace'] == $meta_namespace && $meta_edge['node']['key'] == $meta_key) {
							$collection_meta_id_for_close = $meta_edge['node']['id'];
						}
					}
				}
				if (empty($collection_meta_id_for_close)) {
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
                        "id":"gid://shopify/Collection/' . $collection_id . '",
                        "descriptionHtml":"' . $des_for_close_store . '",
                        "metafields": [
                          {
                            "namespace": "' . $meta_namespace . '",
                            "key": "' . $meta_key . '",
                            "value": "' . $meta_value . '",
                            "valueType": "STRING"
                          }
                        ]
                      }
                    }';
					sleep(0.5);
					$graphql->runByMutation($mutationData, $inputData);

					storeHelper::addDraftProduct($storeMasterId, 'Yes', $collection_id);
				}
				#endregion
				/* Task 106 start */
				$updateStoreData = [
					'production_date' => time(),
					'updated_on'       => date('Y-m-d H:i:s')/* Task 117 */
				];
				parent::updateTable_f_mdl('store_master', $updateStoreData, 'id="' . $storeMasterId . '"');
				/* Task 106 end */
				$storeStatusHistoryData =[
					'store_master_id' => $storeMasterId,
					'status'          => '7',
					'created_on'      => date('Y-m-d H:i:s'),
					'updated_by'	  =>"Super Admin <br>(".$login_user_email.")"
				];
				parent::insertTable_f_mdl('store_status_history', $storeStatusHistoryData);
				//send email to store-owner about store-close

				// =========================================================================================================
				$report_pdf_link = $this->generateChecklistReport($storeMasterId);
				$report_csv_link = $this->export_order_report($storeMasterId);
				$report_pdf_link   = '<a href="'.$report_pdf_link.'">here</a>';
				$report_csv_link   = '<a href="'.$report_csv_link.'">here</a>';
				//====================================================================================================

				$ownerInfo = parent::getEmailInfo_f_mdl($storeMasterId);
				if (strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp') !== false) {
					$objAWS = new aws_ses_smtp();
				} else if (strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail') !== false) {
					$objAWS = new sendGridEmail();
				} else {
					$objAWS = new Aws(common::AWS_ACCESS_KEY, common::AWS_SECRET_KEY, common::AWS_REGION);
				}
				$emailData = parent::getEmailTemplateInfo(common::EMAIL_TO_CUSTOMER_ADMIN_WHEN_FLASH_SALE_IS_OVER);
				$subject = $emailData[0]['subject'];
				$to_email = $ownerInfo[0]['email'];
				$from_email = common::AWS_ADMIN_EMAIL;
				$attachment = [];

				//$objAWS::sendEmail($from_email, $to_email, $subject, str_replace(["{{FIRST_NAME}}"], [$ownerInfo['0']['first_name']], $emailData[0]['body']), $attachment);

				$sql = 'SELECT * FROM store_master WHERE id="' . $storeMasterId . '"';
				$store_data = parent::selectTable_f_mdl($sql);
				$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
				$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
				$store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
				$store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;

				//if($store_data[0]['email_notification'] == '1'){
				$objAWS->sendEmail([$to_email], $subject, str_replace(["{{FIRST_NAME}}", "{{STORE_NAME}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}","{{CHECKLIST_REPORT}}","{{ORDER_REPORT}}"], [$ownerInfo['0']['first_name'], $store_data[0]['store_name'],$logo,$store_open_date,$store_last_date,$report_pdf_link,$report_csv_link], $emailData[0]['body']), str_replace(["{{FIRST_NAME}}", "{{STORE_NAME}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}","{{CHECKLIST_REPORT}}","{{ORDER_REPORT}}"], [$ownerInfo['0']['first_name'], $store_data[0]['store_name'],$logo,$store_open_date,$store_last_date,$report_pdf_link,$report_csv_link], $emailData[0]['body']));
				//}
				/*send mail store manager*/
				$store_owner_details_master_id = $ownerInfo[0]['store_owner_id'];
				$sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_details_master_id . '"';
				$smm_data =  parent::selectTable_f_mdl($sql_managerData);
				if (!empty($smm_data)) {
					foreach ($smm_data as $managerData) {
						$to_email   = $managerData['email'];
						$dashBoardUrl = '<a href="' . common::CUSTOMER_ADMIN_DASHBOARD_URL . '" target="_blank">Click here</a>';
						$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, str_replace(["{{FIRST_NAME}}", "{{STORE_NAME}}", "{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}","{{CHECKLIST_REPORT}}","{{ORDER_REPORT}}"], [$managerData['first_name'], $store_data[0]['store_name'], $dashBoardUrl,$logo,$store_open_date,$store_last_date,$report_pdf_link,$report_csv_link], $emailData[0]['body']), str_replace(["{{FIRST_NAME}}", "{{STORE_NAME}}", "{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}","{{CHECKLIST_REPORT}}","{{ORDER_REPORT}}"], [$managerData['first_name'], $store_data[0]['store_name'], $dashBoardUrl,$logo,$store_open_date,$store_last_date,$report_pdf_link,$report_csv_link], $emailData[0]['body']));
					}
				}
				/*send mail store manager end*/
				$sqlcust = 'SELECT cust_name,cust_email,shop_order_number FROM store_orders_master WHERE store_master_id ="'.$storeMasterId.'" AND is_order_cancel ="0" ';
				$Customerdata = parent::selectTable_f_mdl($sqlcust);
				if(!empty($Customerdata)){
					foreach($Customerdata as $objEmail){				
						$this->sendPurchaserEmail($objEmail['shop_order_number'],$objEmail['cust_email'],$objEmail['cust_name'],common::EMAIL_TO_PURCHASERS_FOR_FLASH_SALE_ONLY_NOT_ON_DEMAND,$storeMasterId);
					}
				}

				/*send mail super admin where id=44 */
				$sql= 'SELECT subject,body,recipients FROM `email_templates_master` WHERE id ="44" ';
				$et_data  = parent::selectTable_f_mdl($sql);

				$storeurl   = common::SITE_URL."sa-store-view.php?stkn=&id=".$storeMasterId;

				if(!empty($et_data)){
					$subject = $et_data[0]['subject'];
					$body = $et_data[0]['body'];
					$to_email = common::SUPER_ADMIN_EMAIL;
					$from_email = common::AWS_ADMIN_EMAIL;
					$attachment = [];
					$ccMails = '';
					if($et_data[0]['recipients']){
						$recipients = $et_data[0]['recipients'];
						$recipients = str_replace(' ', '', $recipients);
						$ccMails    = explode(',', $recipients);
					}

					$subject = str_replace('{{STORE_NAME}}',$store_data[0]['store_name'],$subject);
					$body = str_replace('{{STORE_NAME}}', $store_data[0]['store_name'], $body);
					$body = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
					$body = str_replace('{{STORE_URL}}',$storeurl, $body);
					$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body,$ccMails);
				}
				
				/*send mail super admin end*/
				
				$resultArray["isSuccess"] = "1";
				$resultArray["msg"] = "Changes saved successfully.";
				echo json_encode($resultArray, 1);
				exit;
			}
		}
	}

	function addNotes()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "addNotes") {

				$storeMasterId = parent::getVal("store_master_id");
				$store_notes = parent::getVal("store_notes");

				parent::addNotes_f_mdl($storeMasterId, $store_notes);
			}
		}
	}

	function deleteStore()
	{
		global $s3Obj;
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "deleteStore") {

				$storeMasterId = parent::getVal("store_master_id");

				require_once('lib/shopify.php');

				$sql = "SELECT id, shop_name, token FROM `shop_management` WHERE id = 1 LIMIT 1";
				$shop_data = parent::selectTable_f_mdl($sql);

				$sql = 'SELECT store_product_master_id,shop_product_id FROM `store_owner_product_master`
						WHERE store_master_id ="' . $storeMasterId . '"';
				$productData = parent::selectTable_f_mdl($sql);

				$sql = 'SELECT shop_collection_id FROM `store_master`
						WHERE id ="' . $storeMasterId . '"';
				$collectionData = parent::selectTable_f_mdl($sql);

				$shopify = new ShopifyClient($shop_data[0]['shop_name'], $shop_data[0]['token'], common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);

				foreach ($productData as $key => $value) {
					if (!empty($value['shop_product_id'])) {
						try {
							$deleteProduct = $shopify->call('DELETE', '/admin/api/2023-04/products/' . $value['shop_product_id'] . '.json');
						} catch (ShopifyApiException $e) {
							echo "<pre>";
							print_r($e);
						} catch (ShopifyCurlException $e) {
							echo "<pre>";
							print_r($e);
						}
					}
				}

				if (!empty($collectionData[0]['shop_collection_id'])) {
					try {
						$deleteCollection = $shopify->call('DELETE', '/admin/api/2023-04/custom_collections/' . $collectionData[0]['shop_collection_id'] . '.json');
					} catch (ShopifyApiException $e) {
						echo "<pre>";
						print_r($e);
					} catch (ShopifyCurlException $e) {
						echo "<pre>";
						print_r($e);
					}
				}

				parent::deleteTable_f_mdl('store_design_logo_master', 'store_master_id =' . $storeMasterId);
				parent::deleteTable_f_mdl('store_master', 'id =' . $storeMasterId);
				parent::deleteTable_f_mdl('store_orders_master', 'store_master_id =' . $storeMasterId);
				parent::deleteTable_f_mdl('store_order_items_master', 'store_master_id =' . $storeMasterId);
				parent::deleteTable_f_mdl('store_owner_address_master', 'store_master_id =' . $storeMasterId);
				parent::deleteTable_f_mdl('store_owner_flyer', 'store_master_id =' . $storeMasterId);

				parent::deleteTable_f_mdl('store_owner_payouts_master', 'store_master_id =' . $storeMasterId);

				foreach ($productData as $key => $value) {
					parent::deleteTable_f_mdl('store_owner_product_variant_master', 'store_owner_product_master_id =' . $value['store_product_master_id']);
				}
				parent::deleteTable_f_mdl('store_owner_product_master', 'store_master_id =' . $storeMasterId);

				parent::deleteTable_f_mdl('store_owner_request_to_changes', 'store_master_id =' . $storeMasterId);
				parent::deleteTable_f_mdl('store_owner_silver_delivery_address_master', 'store_master_id =' . $storeMasterId);

				$sql = 'SELECT id FROM `store_request_design_master`
						WHERE store_master_id ="' . $storeMasterId . '"';
				$requestDesignData = parent::selectTable_f_mdl($sql);

				foreach ($requestDesignData as $key => $value) {
					parent::deleteTable_f_mdl('store_request_design_reference_images_master', 'store_request_design_master_id =' . $value['id']);
				}

				parent::deleteTable_f_mdl('store_request_design_master', 'store_master_id =' . $storeMasterId);


				parent::deleteTable_f_mdl('store_sort_list_master', 'store_master_id =' . $storeMasterId);
				parent::deleteTable_f_mdl('store_wise_notes_master', 'store_master_id =' . $storeMasterId);
				$s3Obj->deleteArchiveFolder(common::LOGO_MOCKUP_UPLOAD_S3_PATH.$storeMasterId.'/');

				$resultArray = array();
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Store data delete successfully.";

				common::sendJson($resultArray);
			}
		}
	}

	function updateVarificationStatus()
	{
		global $login_user_email;
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "updateStatus") {

				$var_value     = parent::getVal("var_value");

				$var_value = $var_value == '6' ? 1 : $var_value;
				$storeMasterId = parent::getVal("store_master_id");

				$currenDate = date('Y-m-d H:i:s');/* Task 117 */

				$resArray   = parent::updateVarificationStatus_f_mdl($var_value, $storeMasterId, $currenDate,$login_user_email);/* Task 117 add $currenDate */
				$var_value     = parent::getVal("var_value");
				if ($resArray['isSuccess'] == '1') {
					if ($var_value) {
						$this->sendPayoutEmail($storeMasterId, $var_value);
					} else {
						$this->sendPayoutEmail($storeMasterId, $var_value);
					}
				}
			}
		}
	}

	/* Task 117 start */
	public function getUpdatedTimeOfLastStatus($status)
	{
		$statusSql  = 'SELECT updated_on FROM store_status_history WHERE current_status_type="' . $status . '" ORDER BY id DESC limit 1';
		$statusData = parent::selectTable_f_mdl($statusSql);
		return $statusData;
	}
	/* Task 117 end */

	function sendPayoutEmail($storeMasterId, $emailSendId)
	{
		global $s3Obj;
		global $login_user_email;
		$ownerInfo = parent::getEmailInfo_f_mdl($storeMasterId);
		$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
		$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
		#region - Send Mail To Store Admin
		require_once(common::EMAIL_REQUIRE_URL);
		if (strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp') !== false) {
			$objAWS = new aws_ses_smtp();
		} else if (strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail') !== false) {
			$objAWS = new sendGridEmail();
		} else {
			$objAWS = new Aws(common::AWS_ACCESS_KEY, common::AWS_SECRET_KEY, common::AWS_REGION);
		}

		$emailData = '';
		if ($emailSendId == '4') {
			$emailData = parent::getEmailTemplateInfo('22');
		} else if ($emailSendId == '6') {
			$emailData = parent::getEmailTemplateInfo('5');
		} else if ($emailSendId == '2') {
			$emailData = parent::getEmailTemplateInfo(common::REJECT_STORE_EMAIL_ID);
		}

		$subject = (isset($emailData) && !empty($emailData)) ? $emailData[0]['subject'] : '';
		$to_email = $ownerInfo[0]['email'];
		$from_email = common::AWS_ADMIN_EMAIL;
		$attachment = [];
		$dashBoardUrl = '<a href="' . common::CUSTOMER_ADMIN_DASHBOARD_URL . '" target="_blank">Click here</a>';

		//$mailSendStatus = $objAWS::sendEmail($from_email, $to_email, $subject, str_replace(["{{FIRST_NAME}}","{{DASHBOARD_LINK}}"],[$ownerInfo['0']['first_name'],common::CUSTOMER_ADMIN_DASHBOARD_URL],$emailData[0]['body']), $attachment);

		$sql = 'SELECT * FROM store_master WHERE id="' . $storeMasterId . '"';
		$store_data = parent::selectTable_f_mdl($sql);

		$store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
		$store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;

		$mailSendStatus = 1;
		//if($store_data[0]['email_notification'] == '1'){
		if ($emailSendId == '4' || $emailSendId == '6') {
			$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, str_replace(["{{FIRST_NAME}}", "{{STORE_NAME}}", "{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"], [$ownerInfo['0']['first_name'], $store_data[0]['store_name'], $dashBoardUrl,$logo,$store_open_date,$store_last_date], $emailData[0]['body']), str_replace(["{{FIRST_NAME}}", "{{STORE_NAME}}", "{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"], [$ownerInfo['0']['first_name'], $store_data[0]['store_name'], $dashBoardUrl,$logo,$store_open_date,$store_last_date], $emailData[0]['body']));
		} else if ($emailSendId == '2') {
			$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, str_replace(["{{FIRST_NAME}}", "{{STORE_NAME}}", "{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"], [$ownerInfo['0']['first_name'], $store_data[0]['store_name'], common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date], $emailData[0]['body']), str_replace(["{{FIRST_NAME}}", "{{STORE_NAME}}", "{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"], [$ownerInfo['0']['first_name'], $store_data[0]['store_name'], common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date], $emailData[0]['body']));
		}
		//}
		/*send mail store manager*/
		$store_owner_details_master_id = $ownerInfo[0]['store_owner_id'];
		$sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_details_master_id . '"';
		$smm_data =  parent::selectTable_f_mdl($sql_managerData);
		if (!empty($smm_data)) {
			foreach ($smm_data as $managerData) {
				$to_email   = $managerData['email'];
				if ($emailSendId == '4' || $emailSendId == '6') {
					$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, str_replace(["{{FIRST_NAME}}", "{{STORE_NAME}}", "{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"], [$managerData['first_name'], $store_data[0]['store_name'], $dashBoardUrl,$logo,$store_open_date,$store_last_date], $emailData[0]['body']), str_replace(["{{FIRST_NAME}}", "{{STORE_NAME}}", "{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"], [$managerData['first_name'], $store_data[0]['store_name'], $dashBoardUrl,$logo,$store_open_date,$store_last_date], $emailData[0]['body']));
				} else if ($emailSendId == '2') {
					$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, str_replace(["{{FIRST_NAME}}", "{{STORE_NAME}}", "{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"], [$managerData['first_name'], $store_data[0]['store_name'], common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date], $emailData[0]['body']), str_replace(["{{FIRST_NAME}}", "{{STORE_NAME}}", "{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"], [$managerData['first_name'], $store_data[0]['store_name'], common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date], $emailData[0]['body']));
				}
			}
		}
		/*send mail store manager end*/
		#endregion

		/* Task 117 start */
		$status = '';
		$verification_status = $store_data[0]['verification_status'];
		if ($verification_status == '2') {
			$status = '2';
		} elseif ($verification_status == '4') {
			$status = '4';
		} elseif ($verification_status == '1') {
			$status = '6';
		}
		$storeStatusData = [
			'store_master_id'       => $storeMasterId,
			'status'                => $status,
			'created_on'            => date('Y-m-d H:i:s'),
			'updated_by'	  =>"Super Admin <br>(".$login_user_email.")"
		];
		$store_status_history = parent::insertTable_f_mdl('store_status_history', $storeStatusData);
		/* Task 117 end */

		$resultArray = array();

		if ($mailSendStatus) {
			$resultArray["isSuccess"] = "1";
			$resultArray["msg"] = "Changes saved successfully.";
		} else {
			$resultArray["isSuccess"] = "0";
			// $resultArray["msg"] = "Oops! there is some issue during insert. Please try again.";
			$resultArray["msg"] = "Currently email functionality is not working but the store status is changed.";
		}
		common::sendJson($resultArray);
	}

	/* Task start 121 */
	public function getStoresMeetMinimum($storeMasterId)
	{
		$data = array();

		$groupsql           = 'SELECT id,group_name FROM store_owner_product_master WHERE store_master_id="'.$storeMasterId.'" AND is_soft_deleted="0" group by group_name';
		$product_group_name = parent::selectTable_f_mdl($groupsql);
		$status = "No";
		if (!empty($product_group_name)) {
			$minimumMetAarray = array();
			foreach ($product_group_name as $group_name) {
				$store_owner_group_name        = $group_name['group_name'];
				$groupItemSql = 'SELECT id FROM store_owner_product_master WHERE group_name="' . $store_owner_group_name . '" and store_master_id = "' . $storeMasterId . '" AND is_soft_deleted="0" ';
				$groupItemDetails = parent::selectTable_f_mdl($groupItemSql);

				$dataIds = array();
				foreach ($groupItemDetails as $value) {
					$dataIds[] = $value['id'];
				}

				$soldSql = 'SELECT IFNULL(SUM(oim.quantity),0) as sold_items,om.store_master_id,oim.store_owner_product_master_id ,oim.title,om.id as store_order_master_id FROM `store_orders_master` as om INNER JOIN store_order_items_master as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 AND oim.store_owner_product_master_id in(' . implode(',', $dataIds) . ')';
				$qtySold = parent::selectTable_f_mdl($soldSql);
				$soldItems = '';
				if (!empty($qtySold)) {
					$soldItems = $qtySold[0]['sold_items'];
				}

				$minimumsSql = 'SELECT minimum_group_value from minimum_group_product WHERE product_group="' . $store_owner_group_name . '" ';
				$minimum_group_value = parent::selectTable_f_mdl($minimumsSql);

				$group_value = 0;
				if (!empty($minimum_group_value)) {
					$group_value         = $minimum_group_value[0]['minimum_group_value'];
				}

				$minimums            = "";

				if ($soldItems >= $group_value) {
					$minimums = 'Minimums Met';
					$minimumMetAarray[] = 0;
				} else {
					$minimums        = $group_value - $soldItems;
					$minimumMetAarray[] = 1;
				}
			}
			if (in_array("1", $minimumMetAarray)) {
				$status = "No";
			} else {
				$status = "Yes";
			}
		}
		return $status;
	}
	/* Task end 121 */

	function storePagination()
	{
		if (parent::isPOST()) {

			if (parent::getVal("hdn_method") == "store_pagination") {
				$record_count = 0;
				$page = 0;
				$current_page = 1;
				$rows = '10';
				$keyword = '';



				if ((isset($_REQUEST['rows'])) && (!empty($_REQUEST['rows']))) {
					$rows = $_REQUEST['rows'];
				}
				if ((isset($_REQUEST['keyword'])) && (!empty($_REQUEST['keyword']))) {
					$keyword = $_REQUEST['keyword'];
				}
				if ((isset($_REQUEST['current_page'])) && (!empty($_REQUEST['current_page']))) {
					$current_page = $_REQUEST['current_page'];
				}
				$start = ($current_page - 1) * $rows;
				$end = $rows;
				$sort_field = '';
				if (isset($_POST['sort_field']) && !empty($_POST['sort_field'])) {
					$sort_field = $_POST['sort_field'];
				}
				$sort_type = '';
				if (isset($_POST['sort_type']) && !empty($_POST['sort_type'])) {
					$sort_type = $_POST['sort_type'];
				}

				//end fixed, no change for any module

				/*if(isset($_POST['date_range_filter']) && !empty($_POST['date_range_filter'])){
					$dr_arr = explode(' To ',$_POST['date_range_filter']);
					if(isset($dr_arr[0]) && !empty($dr_arr[0]) && isset($dr_arr[1]) && !empty($dr_arr[1]) ){
						$start_ts = strtotime($dr_arr[0].' 0:0');
						$end_ts = strtotime($dr_arr[1].' 23:59');
						$User->set_start_date($start_ts);
						$User->set_end_date($end_ts);
					}
				}*/

				$cond_keyword = '';
				/* if (isset($keyword) && !empty($keyword)) {
					$cond_keyword = "AND (
							sm.store_name LIKE '%$keyword%'
						)";
				} */
				if (isset($keyword) && !empty($keyword)) {
					$cond_keyword = "AND (
						sm.store_name LIKE '%" . trim($keyword) . "%' OR sm.product_name_identifier LIKE '%" . trim($keyword) . "%' OR sm.notes LIKE '%" . trim($keyword) . "%' OR sodm.first_name LIKE '%" . trim($keyword) . "%' OR sodm.last_name LIKE '%" . trim($keyword) . "%' OR sodm.email LIKE '%" . trim($keyword) . "%' OR sodm.phone LIKE '%" . trim($keyword) . "%' OR sodm.organization_name LIKE '%" . trim($keyword) . "%' OR sm.po_details LIKE '%" . trim($keyword) . "%')";
				}
				$cond_status = '';
				if (isset($_POST['store_status'])) {
					$cond_status = 'AND sm.status = "' . $_POST['store_status'] . '"';
				}

				$cond_atchive = '';
				if (isset($_POST['is_archive'])) {
					$cond_archive = 'AND sm.is_archive = "' . $_POST['is_archive'] . '"';
				}

				$cond_order = 'ORDER BY id DESC';
				if (!empty($sort_field)) {
					$cond_order = 'ORDER BY ' . $sort_field . ' ' . $sort_type;
				}

				$verificationStatus = "";
				if ((isset($_POST['verification_status'])) && $_POST['verification_status'] != '') {
					//Task 68
					if ($_POST['verification_status'] == "3") {
						$verificationStatus = 'AND (sm.is_store_batched= 1 AND sstm.sale_type="Flash Sale")';
					} else {
						$verificationStatus = 'AND sm.verification_status = "' . $_REQUEST['verification_status'] . '"';
					}
					//Task 68
				}

				$fullfilmentStatus = "";
				if ((isset($_POST['fullfilment_type'])) && $_POST['fullfilment_type'] != '') {
					$value = "";
					if ($_POST['fullfilment_type'] == 1) {
						$value = "SHIP_1_LOCATION_NOT_SORT";
					} else if ($_POST['fullfilment_type'] == 2) {
						$value = "SHIP_1_LOCATION_SORT";
					} else {
						$value = "SHIP_EACH_FAMILY_HOME";
					}
					if ($_POST['fullfilment_type'] == 4) {
						$fullfilmentStatus = 'AND sm.store_sale_type_master_id=2';
					} else {
						$fullfilmentStatus = 'AND sm.store_sale_type_master_id=1 AND sm.store_fulfillment_type = "' . $value . '"';
					}
				}

				$endDate = '';
				$from_date = '';
				$to_date = '';
				if ((isset($_POST['start_date'])) && $_POST['start_date'] != '') {
					$from_date = $_POST['start_date'];
					// $date =  date('Y-m-d', strtotime('-1 day', strtotime($_POST['start_date'])));
				}

				if ((isset($_POST['end_date'])) && $_POST['end_date'] != '') {
					$to_date = $_POST['end_date'];
					// $to_date =  date('Y-m-d', strtotime('+1 day', strtotime($_POST['end_date'])));
				}
				/* if ((isset($from_date) && $from_date != '')) {
				 	$endDate = ' AND ( sm.production_date > '.$from_date.' AND sm.production_date < '.$to_date.' )';
				 	$endDate=strtotime($endDate);
				   }
				*/

				if ((isset($from_date) && $from_date != '')) {
					$endDate = " AND DATE_FORMAT(FROM_UNIXTIME(sm.`production_date`), '%Y-%m-%d') >= '$from_date' AND DATE_FORMAT(FROM_UNIXTIME(sm.`production_date`), '%Y-%m-%d') <=  '$to_date' ";
					// $endDate=strtotime($endDate);
				}

				if ($_POST['store_status'] == '1' && (isset($from_date) && $from_date != '')) {
					$endDate = " AND DATE_FORMAT(FROM_UNIXTIME(sm.`store_close_date`), '%Y-%m-%d') >= '$from_date' AND DATE_FORMAT(FROM_UNIXTIME(sm.`store_close_date`), '%Y-%m-%d') <=  '$to_date' ";
					// $endDate=strtotime($endDate);
				}

				if($_POST['is_archive'] =='1'){
					//Task 74 add column name Task 117 add colum sm.updated_on,
					$sql = "SELECT count(sm.id) as count, sm.id, sm.shop_collection_handle,sm.is_store_batched, sm.front_side_ink_colors, sm.back_side_ink_colors,sm.store_name,sm.store_open_date,sm.is_fundraising,sm.store_fulfillment_type,sm.store_close_date,sm.store_in_hands_date,sm.verification_status,sstm.sale_type,sodm.first_name,sodm.last_name,sodm.email,sodm.phone,sodm.organization_name,sm.store_owner_details_master_id,sm.total_profit,sm.updated_on,sm.is_exported_to_printavo,sm.printavo_invoice_id,sm.printavo_invoice_number,sm.status,
						(SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = sm.id) as totalItem_sold,
						(SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0) as total_order
						FROM store_master sm INNER JOIN store_owner_details_master sodm ON sm.store_owner_details_master_id = sodm.id INNER JOIN store_sale_type_master sstm ON sm.store_sale_type_master_id = sstm.id WHERE 1
						$cond_keyword
						$verificationStatus
						$fullfilmentStatus
						$cond_archive
						$endDate
					";
				}else{

					$sql = "SELECT count(sm.id) as count, sm.id, sm.shop_collection_handle,sm.is_store_batched, sm.front_side_ink_colors, sm.back_side_ink_colors,sm.store_name,sm.store_open_date,sm.is_fundraising,sm.store_fulfillment_type,sm.store_close_date,sm.store_in_hands_date,sm.verification_status,sstm.sale_type,sodm.first_name,sodm.last_name,sodm.email,sodm.phone,sodm.organization_name,sm.store_owner_details_master_id,sm.total_profit,sm.updated_on,sm.is_exported_to_printavo,sm.printavo_invoice_id,sm.printavo_invoice_number,sm.status,
						(SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = sm.id) as totalItem_sold,
						(SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0) as total_order
						FROM store_master sm INNER JOIN store_owner_details_master sodm ON sm.store_owner_details_master_id = sodm.id INNER JOIN store_sale_type_master sstm ON sm.store_sale_type_master_id = sstm.id WHERE 1
						$cond_keyword
						$verificationStatus
						$fullfilmentStatus
						$cond_archive
						$cond_status
						$endDate
					";
				}

				$all_count = parent::selectTable_f_mdl($sql);

				if($_POST['is_archive'] =='1'){

					$sql1 = "SELECT sm.id, sm.shop_collection_handle,sm.is_store_batched,sm.front_side_ink_colors, sm.back_side_ink_colors,sm.store_name,sm.store_open_date,sm.is_fundraising,sm.store_fulfillment_type,sm.store_close_date,sm.store_in_hands_date,sm.verification_status,sm.notes,sstm.sale_type,sodm.first_name,sodm.last_name,sodm.email,sodm.phone,sodm.organization_name,sm.store_owner_details_master_id,sm.store_sale_type_master_id,sm.total_profit,sm.updated_on,sm.print_date,sm.production_date,sm.po_details,sm.profit_margin,sm.is_exported_to_printavo,sm.printavo_invoice_id,sm.printavo_invoice_number,sm.is_archive,sm.status,
						(SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = sm.id) as totalItem_sold,
						(SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0) as total_order
						FROM store_master sm INNER JOIN store_owner_details_master sodm ON sm.store_owner_details_master_id = sodm.id INNER JOIN store_sale_type_master sstm ON sm.store_sale_type_master_id = sstm.id WHERE 1
						$cond_keyword
						$verificationStatus
						$fullfilmentStatus
						$cond_archive
						$endDate
						$cond_order
						LIMIT $start,$end
					";

				}else{

					//Task 22 add new parameter store_sale_type_master_id //Task 74 add column name Task 117 add colum sm.updated_on,
					$sql1 = "SELECT sm.id, sm.shop_collection_handle,sm.is_store_batched,sm.front_side_ink_colors, sm.back_side_ink_colors,sm.store_name,sm.store_open_date,sm.is_fundraising,sm.store_fulfillment_type,sm.store_close_date,sm.store_in_hands_date,sm.verification_status,sm.notes,sstm.sale_type,sodm.first_name,sodm.last_name,sodm.email,sodm.phone,sodm.organization_name,sm.store_owner_details_master_id,sm.store_sale_type_master_id,sm.total_profit,sm.updated_on,sm.print_date,sm.production_date,sm.po_details,sm.profit_margin,sm.is_exported_to_printavo,sm.printavo_invoice_id,sm.printavo_invoice_number,sm.is_archive,sm.status,
						(SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = sm.id) as totalItem_sold,
						(SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0) as total_order
						FROM store_master sm INNER JOIN store_owner_details_master sodm ON sm.store_owner_details_master_id = sodm.id INNER JOIN store_sale_type_master sstm ON sm.store_sale_type_master_id = sstm.id WHERE 1
						$cond_keyword
						$verificationStatus
						$fullfilmentStatus
						$cond_archive
						$cond_status
						$endDate
						$cond_order
						LIMIT $start,$end
					";
				}

				$all_list = parent::selectTable_f_mdl($sql1);

				if ((isset($all_count[0]['count'])) && (!empty($all_count[0]['count']))) {
					$record_count = $all_count[0]['count'];
					$page = $record_count / $rows;
					$page = ceil($page);
				}
				$sr_start = 0;
				if ($record_count >= 1) {
					$sr_start = (($current_page - 1) * $rows) + 1;
				}
				$sr_end = ($current_page) * $rows;
				if ($record_count <= $sr_end) {
					$sr_end = $record_count;
				}

				if (isset($_POST['pagination_export']) && $_POST['pagination_export'] == 'Y') {
					/*if(isset($all_list) && !empty($all_list)){
						$date_formate=Config::get('constant.DATE_FORMATE');
						$file_full_path = public_path().Config::get('constant.DOWNLOAD_TABLE_LOCATION')."downloaded_table_".time().".csv";
						$file_full_url = asset(Config::get('constant.DOWNLOAD_TABLE_LOCATION')."downloaded_table_".time().".csv");
						$file_for_download_data = fopen($file_full_path,"w");
						fputcsv($file_for_download_data,array('#','Name','Email','Mobile','Add Date'));
						$i=$sr_start;
						foreach ($all_list as $single){
							if($single->add_date!=''){
								$add_date = date($date_formate, $single->add_date);
							}else{
								$add_date = '';
							}
							fputcsv($file_for_download_data,array(
								$i,
								$single->first_name.' '.$single->last_name,
								$single->email,
								$single->mobile,
								$add_date
							));
							$i++;
						}
						fclose($file_for_download_data);
						$this->param['SUCCESS']='TRUE';
						$this->param['file_full_url']=$file_full_url;
					}else{
						$this->param['SUCCESS']='FALSE';
					}
					echo json_encode($this->param,1);*/
				} else {
					$html = '';
					$html .= '<div class="row">';
					$html .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
					$html .= '<div class="table-responsive dropdown-active">'; // Task 54 19/10/2021 Add new class dropdown-active
					$html .= '<table class="table table-bordered table-hover">';

					$html .= '<thead>';
					$html .= '<tr>';
					$html .= '<th><input type="checkbox" id="ckbCheckAll"></th>';
					$html .= '<th>#</th>';
					$html .= '<th>Stores Name</th>';
					$html .= '<th>Customer Profile</th>';
					$html .= '<th class="sort_th" data-sort_field="total_order"># of orders</th>';
					$html .= '<th class="sort_th" data-sort_field="totalItem_sold"># of items sold</th>';
					$html .= '<th class="sort_th" data-sort_field="total_profit">Total Profit</th>';
					$html .= '<th class="sort_th" data-sort_field="total_profit">Profit Margin</th>';
					$html .= '<th class="sort_th" data-sort_field="is_store_batched">Store Status</th>';
					$html .= '<th>Program Type</th>';
					$html .= '<th>Minimums Met</th>';/* Task 121 */
					$html .= '<th class="sort_th" data-sort_field="is_fundraising">Fundraising</th>';
					$html .= '<th class="sort_th" data-sort_field="store_open_date">Open Day</th>';
					$html .= '<th class="sort_th" data-sort_field="store_close_date">Last Day</th>';
					$html .= '<th class="sort_th" data-sort_field="store_in_hands_date">In Hands Date</th>'; //Task 74
					$html .= '<th class="sort_th" data-sort_field="store_in_hands_date">Print Date</th>';
					$html .= '<th class="sort_th" data-sort_field="store_in_hands_date">Close Date</th>';
					$html .= '<th>PO #</th>';
					$html .= '<th>Notes</th>';

					if (@$_POST['store_status'] == '1') {
						$html .= '<th>Verification Status</th>';
					}

					$html .= '<th>Age</th>';

					if (@$_POST['store_status'] == '0') {
						$html .= '<th># Printavo Invoice (Store)</th>';
						$html .= '<th># Printavo Invoice (Group)</th>';
					}

					$html .= '<th>Actions</th>';

					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					if (!empty($all_list)) {
						$sr = $sr_start;
						foreach ($all_list as $single) {
							/* Task start 121 */
							$dataMet = "-";
							if ($single['store_sale_type_master_id'] == "1") {
								$dataMet = self::getStoresMeetMinimum($single['id']);
							}
							/* Task end 121 */
							$html .= '<tr>';
							$html .= '<td><input type="checkbox" value=' . $single["id"] . ' class="checkBoxClass"></td>';
							$html .= '<td>' . $sr . '</td>';
							$html .= '<td>' . $single["store_name"] . '</td>';
							$html .= '<td><button data-id="' . $single["id"] . '" class="btn btn-round btn-primary btn-sm store_info_btn" data-first_name="' . $single["first_name"] . '" data-last_name="' . $single["last_name"] . '" data-email="' . $single["email"] . '" data-phone="' . $single["phone"] . '"  data-organization_name="' . $single["organization_name"] . '"><i class="fa fa-user"></i></button></td>';
							// $html .= '<td>' . $orderData[0]['total_order'] . '</td>';

							// $html .= '<td>' . $soldItemData[0]['totalItem_sold'] .'</td>';
							$totalProfit  = number_format((float)$single['total_profit'], 2);
							$html .= '<td>' . $single['total_order'] . '</td>';
							$html .= '<td>' . $single['totalItem_sold'] . '</td>';
							$html .= '<td>$' . $totalProfit . '</td>';
							$html .= '<td>' . $single['profit_margin'] . '%' . '</td>';
							if (@$_POST['store_status'] == '1') {
								/* task 27 start */
								if ($single["store_sale_type_master_id"] == 1) {
									$html .= '<td style="' . ($single["is_store_batched"] == '1' ? ($dataMet == 'No' ? 'background-color: #FF0000;color: white;' : 'background-color: #4CAF50;color: white;') : '') . '">' . ($single["is_store_batched"] == '1' ? ($dataMet == 'No' ? 'Store ended - Minimum Not Met' : 'Minimum Met') : ($single["verification_status"] == '1' ? 'Store is Live' : 'Pending')) . '</td>';
								} else {
									$html .= '<td>' . (($single["verification_status"] == '1' ? 'Store is Live' : 'Pending')) . '</td>';
								}
								/* task 27 end */
							} else {
								if($_POST['is_archive'] == '1' && $single['status']=='1'){
									if($single["store_sale_type_master_id"]==1){
										$html .= '<td style="' . ($single["is_store_batched"] == '1' ? ($dataMet == 'No' ? 'background-color: #FF0000;color: white;' : 'background-color: #4CAF50;color: white;') : '') . '">' . ($single["is_store_batched"] == '1' ? ($dataMet == 'No' ? 'Store ended - Minimum Not Met' : 'Minimum Met') : ($single["verification_status"] == '1' ? 'Store is Live' : 'Pending')) . '</td>';
									}else{
										$html .= '<td>' . (($single["verification_status"] == '1' ? 'Store is Live' : 'Pending')) . '</td>';
									}
								}else{
									$html .= '<td>Closed</td>';
								}
							}
							/*if($single["is_store_batched"] == 1){
							$html .= '<td><input type="checkbox" data-id='.$single["id"].' class="store_batched" checked></td>';
						}else{
							$html .= '<td><input type="checkbox" data-id='.$single["id"].' class="store_batched"></td>';
						}*/

							$str_arr = explode(":", $single["sale_type"]);
							$html .= '<td>' . $str_arr[0] . '</td>';
							$html .= '<td>' . $dataMet . '</td>';/* Task start */
							$html .= '<td>' . $single["is_fundraising"] . '</td>';

							if (!empty($single['store_open_date'])) {
								$html .= '<td>' . date('m/d/Y', $single["store_open_date"]) . '</td>';
							} else {
								$html .= '<td></td>';
							}
							if (!empty($single['store_close_date'])) {
								$html .= '<td>' . date('m/d/Y', $single["store_close_date"]) . '</td>';
							} else {
								$html .= '<td></td>';
							}
							/* Task 74 */
							if (!empty($single['store_in_hands_date'])) {
								$html .= '<td>' . date('m/d/Y', $single["store_in_hands_date"]) . '</td>';
							} else {
								$html .= '<td></td>';
							}

							if (!empty($single['print_date'])) {
								$html .= '<td>' . date('m/d/Y', $single['print_date']) . '</td>';
							} else {
								$html .= '<td></td>';
							}

							if (!empty($single['production_date'])) {
								$html .= '<td>' . date('m/d/Y', $single['production_date']) . '</td>';
							} else {
								$html .= '<td></td>';
							}

							$html .= '<td>' . $single["po_details"] . '</td>';
							$html .= '<td class="notes_td"><div class="note_text">';
								$storeBasicData_str= $single["notes"];
								$storeBasicData_Length= substr($storeBasicData_str, 0, 100);                                               
							$html .= $storeBasicData_Length;                                               
								if(strlen($storeBasicData_str) > 100) {
							$html .= '<span id="dots">...</span>';
								}
							$html .= '<span id="ReadMoreNotes">';
								$storeBasicData_Rem = substr($storeBasicData_str, 100);
							$html .= $storeBasicData_Rem;
																			
							$html .= '</span>';
							if(strlen($storeBasicData_str) > 100) {
							$html .= '<a href="javascript:void(0);" onclick="ReadMoreFunction()" id="ReadMoreNotesBtn">Read More</a>';
							}
							$html .= '</div></td>';
							// $html .= '<td>' . $single["notes"] . '</td>';

							if (@$_POST['store_status'] == '1') {
								if ($single["verification_status"] == '0') {
									$html .= '<td><button data-href="#" class="btn btn-success waves-effect waves-classic btn-xs approve_btn" data-id=' . $single["id"] . '>Approve</button><button data-href="#" class="btn btn-danger waves-effect waves-classic btn-xs reject_btn" data-id=' . $single["id"] . '>Reject</button></td>';
									//$html .= '<td>Pending</td>';
								} else if ($single["verification_status"] == '1') {
									$html .= '<td><button data-href="#" class="btn btn-success waves-effect waves-classic btn-xs reject_btn" data-id=' . $single["id"] . '>Launched</button></td>';
									//$html .= '<td>Approved</td>';
								} else if ($single["verification_status"] == '4') {
									$html .= '<td><strong>Not Launched Yet</strong></td>';
									//$html .= '<td>Approved</td>';
								} else {
									$html .= '<td><button data-href="#" class="btn btn-danger waves-effect waves-classic btn-xs approve_btn" data-id=' . $single["id"] . '>Rejected</button></td>';
									//$html .= '<td>Rejected</td>';
								}
							}

							/* Task 117 start */
							$validateDate = self::validateDate($single["updated_on"]);
							$age = '';
							if ($validateDate == 1) {
								$age = parent::timeAgo($single["updated_on"], false);
							}
							$html .= '<td>' . $age . '</td>';
							/* Task 117 end */
							$sqlinvoice="SELECT * FROM export_group_to_printavo WHERE store_master_id='".$single['id']."' ";
							$invoice_list = parent::selectTable_f_mdl($sqlinvoice);
							if (@$_POST['store_status'] == '0') {
								if ($single["printavo_invoice_id"]!=0) {
									$printavoInvoiceUrl = common::PRINTAVO_INVOICE_URL;
									$html .= '<td><a target="_blank" href="'.$printavoInvoiceUrl.$single["printavo_invoice_id"].'" >#'.$single["printavo_invoice_number"].'</a></td>';
								}else{
									$html .= '<td></td>';
								}
								if(!empty($invoice_list)){
									$html .= '<td>';
									foreach ($invoice_list as $value) {
										if ($value["printavo_invoice_id"] !='') {
											$printavoInvoiceUrl = common::PRINTAVO_INVOICE_URL;
											$html .= "<a target='_blank' href='".$printavoInvoiceUrl.$value['printavo_invoice_id']."' >#".$value['printavo_invoice_number']."</a>";
											$html .='<br>';
										}
									}
									$html .= '</td>';
								}else{
									$html .= '<td></td>';
								} 
							}

							if (!empty($single["shop_collection_handle"])) {
								$view_btn = '<a target="_blank" role="menuitem" href="https://' . common::PARENT_STORE_NAME . '/collections/' . $single["shop_collection_handle"] . '" class="dropdown-item">View</a>';
							} else {
								$view_btn = '';
							}

							if($single["is_archive"]=='0'){
								$archive_btn = '<button data-href="" href="javascript:void(0)" role="menuitem" class="dropdown-item archive_store" data-id=' . $single["id"] . '>Archive</button>';

							}else{
								$archive_btn = '<button data-href="" href="javascript:void(0)" role="menuitem" class="dropdown-item unarchive_store" data-id=' . $single["id"] . '>Unarchive</button>';
							}

							$html .= '<td><div class="btn-group" role="group">
									  <button type="button" class="btn btn-primary dropdown-toggle" id="exampleGroupDrop1" data-toggle="dropdown" aria-expanded="false">
										Actions
									  </button>
									  
									  <div class="dropdown-menu" aria-labelledby="exampleGroupDrop1" role="menu" x-placement="bottom-start" style="position: absolute; transform: translate3d(0px, 36px, 0px); top: 0px; left: 0px; will-change: transform;">
										
										' . $view_btn . '
										
										<a role="menuitem" href="sa-store-view.php?stkn=' . $_POST['stkn'] . '&id=' . $single["id"] . '" class="dropdown-item stores_view_btn">Edit</a>
										
										<button data-href="" href="javascript:void(0)" role="menuitem" class="dropdown-item stores_login_btn " data-id=' . $single["id"] . '>Login</button>
										
										<button data-href="" href="javascript:void(0)" role="menuitem" class="dropdown-item stores_add_notes_btn " data-id=' . $single["id"] . ' data-note="' . $single["notes"] . '">Add Notes</button>
										
										<button data-href="" href="javascript:void(0)" role="menuitem" class="dropdown-item delete_store" data-id=' . $single["id"] . '>Delete</button>

										' . $archive_btn . '

										<button data-href="" href="javascript:void(0)" role="menuitem" class="dropdown-item duplicate_store" data-store_name="' . $single["store_name"] . '" data-id=' . $single["id"] . ' data-store_owner_master_id=' . $single["store_owner_details_master_id"] . '>Duplicate Store</button>
										<button data-href="" href="javascript:void(0)" role="menuitem" class="dropdown-item resend_login_info" data-store_name="' . $single["store_name"] . '" data-id=' . $single["id"] . ' data-store_owner_master_id=' . $single["store_owner_details_master_id"] . '>Resend Login Info</button>
										';

							/* task 27 start */
							if ($single["is_store_batched"] == '1' && $_POST['store_status'] == '1' && $single["store_sale_type_master_id"] == 1) {
								$html .= '<a role="menuitem" class="dropdown-item move_to_production" data-id=' . $single["id"] . '>Move to Production</a>';
							}

							if (@$_POST['store_status'] == '0') {
								if($single['total_order'] > 0){
									if ($single['is_exported_to_printavo'] == 1) {
										$html .= '<button data-href="" href="javascript:void(0)" disabled role="menuitem" class="dropdown-item" data-id=' . $single["id"] . '>Exported To Printavo</button></td>';
									} else {
										$html .= '<button data-href="" href="javascript:void(0)" role="menuitem" class="dropdown-item export_order_printavo" data-id=' . $single["id"] . '>Export To Printavo</button>';
									}
								}	
							}

							/* task 27 end */
							$html .= '

									  </div>
									</div></td>';
							$html .= '</tr>';
							$sr++;
						}
					} else {
						$html .= '<tr>';
						$html .= '<td colspan="15" align="center">No Record Found</td>';
						$html .= '</tr>';
					}

					$html .= '</tbody>';
					$html .= '</table></br></br></br></br></br>';
					$html .= '</div>';
					$html .= '</div>';
					$html .= '</div>';

					$res['DATA'] = $html;
					$res['page_count'] = $page;
					$res['record_count'] = $record_count;
					$res['sr_start'] = $sr_start;
					$res['sr_end'] = $sr_end;
					echo json_encode($res, 1);
					exit;
				}
			}
		}
	}

	function storeVariantsHundredPagination()
	{
		if (parent::isPOST()) {

			if (parent::getVal("hdn_method") == "store_variants_hundred_pagination") {
				$record_count = 0;
				$page = 0;
				$current_page = 1;
				$rows = '10';
				$keyword = '';

				if ((isset($_REQUEST['rows'])) && (!empty($_REQUEST['rows']))) {
					$rows = $_REQUEST['rows'];
				}
				if ((isset($_REQUEST['keyword'])) && (!empty($_REQUEST['keyword']))) {
					$keyword = $_REQUEST['keyword'];
				}
				if ((isset($_REQUEST['current_page'])) && (!empty($_REQUEST['current_page']))) {
					$current_page = $_REQUEST['current_page'];
				}
				$start = ($current_page - 1) * $rows;
				$end = $rows;
				$sort_field = '';
				if (isset($_POST['sort_field']) && !empty($_POST['sort_field'])) {
					$sort_field = $_POST['sort_field'];
				}
				$sort_type = '';
				if (isset($_POST['sort_type']) && !empty($_POST['sort_type'])) {
					$sort_type = $_POST['sort_type'];
				}

				$cond_keyword = '';
				if (isset($keyword) && !empty($keyword)) {
					$cond_keyword = "AND (
						sm.store_name LIKE '%".trim($keyword)."%' OR
						sm.product_name_identifier LIKE '%" .trim($keyword)."%' OR 
						sodm.organization_name LIKE '%" . trim($keyword) . "%' OR 
						sm.po_details LIKE '%" . trim($keyword) . "%')";
				}

				$cond_status = '';
				// if (isset($_POST['store_status'])) {
				// 	$cond_status = 'AND sm.status = "' . $_POST['store_status'] . '"';
				// }

				$cond_order = 'ORDER BY sm.id DESC';
				if (!empty($sort_field)) {
					$cond_order = 'ORDER BY ' . $sort_field . ' ' . $sort_type;
				}

				
				$sql = "SELECT COUNT(DISTINCT sm.id) as count  FROM
					(SELECT sopm.store_master_id,sopm.id AS product_id,sopm.product_title AS product_title,sopvm.image,COUNT(sopvm.id) AS variant_count
			    FROM store_owner_product_master sopm
			    JOIN store_owner_product_variant_master sopvm ON sopm.id = sopvm.store_owner_product_master_id
			    WHERE sopm.is_soft_deleted ='0' GROUP BY sopm.store_master_id, sopm.id HAVING  COUNT(sopvm.id) > 100) AS product_variants JOIN store_master sm ON sm.id = product_variants.store_master_id 
			    GROUP BY sm.id  
					$cond_keyword
					$cond_status
					LIMIT $start,$end
				";
				$all_count = parent::selectTable_f_mdl($sql);

				$sql1 = "SELECT sm.id,sm.store_name,sm.store_open_date,sm.store_close_date,sm.store_sale_type_master_id,sm.status, MAX(variant_count) AS max_variant_count FROM
					(SELECT sopm.store_master_id,sopm.id AS product_id,sopm.product_title AS product_title,sopvm.image,COUNT(sopvm.id) AS variant_count
			    FROM store_owner_product_master sopm
			    JOIN store_owner_product_variant_master sopvm ON sopm.id = sopvm.store_owner_product_master_id
			    WHERE sopm.is_soft_deleted = '0' GROUP BY sopm.store_master_id, sopm.id HAVING  COUNT(sopvm.id) > 100) AS product_variants JOIN store_master sm ON sm.id = product_variants.store_master_id 
			    GROUP BY sm.id 
					$cond_keyword
					$cond_status
					$cond_order
					LIMIT $start,$end
				";
				$all_list = parent::selectTable_f_mdl($sql1);

				if ((isset($all_count[0]['count'])) && (!empty($all_count[0]['count']))) {
					$record_count = $all_count[0]['count'];
					$page = $record_count / $rows;
					$page = ceil($page);
				}
				$sr_start = 0;
				if ($record_count >= 1) {
					$sr_start = (($current_page - 1) * $rows) + 1;
				}
				$sr_end = ($current_page) * $rows;
				if ($record_count <= $sr_end) {
					$sr_end = $record_count;
				}

				if(isset($_POST['pagination_export']) && $_POST['pagination_export'] == 'Y'){
				}else{
					$html = '';
					$html .= '<div class="row">';
					$html .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
					$html .= '<div class="table-responsive dropdown-active">';
					$html .= '<table class="table table-bordered table-hover">';
					$html .= '<thead>';
					$html .= '<tr>';
					$html .= '<th>#</th>';
					$html .= '<th>Stores Name</th>';
					$html .= '<th>Store Type</th>';
					$html .= '<th>Store Status</th>';
					$html .= '<th>Open Day</th>';
					$html .= '<th>Last Day</th>';
					$html .= '<th>Actions</th>';
					$html .= '</tr>';
					$html .= '</thead>';
					$html .= '<tbody>';
					if (!empty($all_list)) {
						$sr = $sr_start;
						foreach ($all_list as $single) {
							$html .= '<tr>';
							$html .= '<td>'.$sr.'</td>';
							$html .= '<td>'.$single["store_name"].'</td>';
							$html .= '<td>'.(($single["store_sale_type_master_id"] == '1' ? 'Flash Sale' : 'On Demand')).'</td>';
							$html .= '<td>'.(($single["status"] == '1' ? 'Open' : 'Closed')).'</td>';
							if (!empty($single['store_open_date'])) {
								$html .= '<td>' . date('m/d/Y', $single["store_open_date"]) . '</td>';
							} else {
								$html .= '<td></td>';
							}
							if (!empty($single['store_close_date'])) {
								$html .= '<td>' . date('m/d/Y', $single["store_close_date"]) . '</td>';
							} else {
								$html .= '<td></td>';
							}
							$html .= '<td><button type="button" title="Edit" class="btn btn-primary waves-effect waves-classic stores_view_btn" onclick="window.location.href=\'sa-store-view.php?stkn=&id=' . $single["id"] . '\'">Edit </button></td>';
							$html .= '</tr>';
							$sr++;
						}
					} else {
						$html .= '<tr>';
						$html .= '<td colspan="8" align="center">No Record Found</td>';
						$html .= '</tr>';
					}

					$html .= '</tbody>';
					$html .= '</table></br></br></br></br></br>';
					$html .= '</div>';
					$html .= '</div>';
					$html .= '</div>';

					$res['DATA'] = $html;
					$res['page_count'] = $page;
					$res['record_count'] = $record_count;
					$res['sr_start'] = $sr_start;
					$res['sr_end'] = $sr_end;
					echo json_encode($res, 1);
					exit;
				}
			}
		}
	}

	/* Task 117 start */
	public function validateDate($date, $format = 'Y-m-d H:i:s')
	{
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) === $date;
	}
	/* Taskk 117 end*/

	function fetchStoreTokenInfo()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "fetch-stkn") {
				$masterStoreId = parent::getVal("sid");

				parent::fetchStoreTokenInfo_f_mdl($masterStoreId);
			}
		}
	}

	public function save_customer_store_data_old()
	{
		global $s3Obj;
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "duplicateStore" && !empty(parent::getVal("store_master_id")) && !empty(parent::getVal("store_owner_details_master_id"))) {
				$err = [];
				$copy_store_master_id = parent::getVal("store_master_id");
				$store_owner_details_master_id = parent::getVal("store_owner_details_master_id");
				$duplicate_store_name = parent::getVal("duplicate_store_name");

				$duplicate_send_email = parent::getVal("duplicate_send_email");

				if (!empty($err)) {
					$res['SUCCESS'] = 'FALSE';
					$res['MESSAGE'] = '';
					$res['ERR_ARR'] = $err;
				} else {
					$sql = 'SELECT id,token FROM `store_owner_details_master` WHERE id="' . $store_owner_details_master_id . '"';
					$owner_exist =  parent::selectTable_f_mdl($sql);
					if (!empty($owner_exist)) {
						$store_owner_details_master_token = $owner_exist[0]['token'];
					}

					if (!empty($store_owner_details_master_id) && !empty($store_owner_details_master_token)) {
						$sql = 'SELECT * FROM `store_master` WHERE id="' . $copy_store_master_id . '"';
						$store_master_data =  parent::selectTable_f_mdl($sql);

						$store_descriptionOrg = $store_master_data[0]['store_description'];

						/*store_description Org*/
						$sql1 = "SELECT sm.id, sm.shop_collection_handle,sm.is_store_batched,sm.front_side_ink_colors, sm.back_side_ink_colors,sm.sleeve_ink_colors,sm.store_name,sm.store_open_date,sm.is_fundraising,sm.store_fulfillment_type,sm.store_close_date,sm.verification_status,sm.notes,sstm.sale_type,sodm.first_name,sodm.last_name,sodm.email,sodm.phone,sodm.organization_name,sm.status FROM store_master sm INNER JOIN store_owner_details_master sodm ON sm.store_owner_details_master_id = sodm.id INNER JOIN store_sale_type_master sstm ON sm.store_sale_type_master_id = sstm.id WHERE sm.id = " . $copy_store_master_id . "
            ";
						$storeBasicData = parent::selectTable_f_mdl($sql1);
						if ($storeBasicData[0]['status'] == '0') {
							$str_arr = explode(":", $storeBasicData[0]["sale_type"]);
							$program_type = $str_arr[0];
							if ($program_type == "On-Demand") {
								//On-Demand 
								$store_descriptionOrg = common::DESCRIPTION_FOR_OPEN_ONDEMAND_STORE;
							} else {
								if (trim(@$store_master_data[0]['store_fulfillment_type'])) {
									if (trim(@$store_master_data[0]['store_fulfillment_type']) == 'SHIP_1_LOCATION_NOT_SORT') {
										//Silver
										$store_descriptionOrg = common::DESCRIPTION_FOR_OPEN_FLASH_STORE;
									} else if (trim(@$store_master_data[0]['store_fulfillment_type']) == 'SHIP_1_LOCATION_SORT') {
										//Gold
										$store_descriptionOrg = common::DEFAULT_DESCRIPTION_FOR_SILVER_GOLD_FULFILLMENT;
									} else {
										//Platinum
										$store_descriptionOrg = common::DEFAULT_DESCRIPTION_FOR_PLATINUM_FULFILLMENT;
									}
								}
							}
						}
						$store_descriptionOrg = addslashes(trim($store_descriptionOrg));
						/*store_description Org*/

						//insert store details
						$sm_insert_data = [
							'store_owner_details_master_id' => $store_owner_details_master_id,
							'store_organization_type_master_id' => trim($store_master_data[0]['store_organization_type_master_id']),
							'front_side_ink_colors' => trim($store_master_data[0]['front_side_ink_colors']),
							'back_side_ink_colors' => trim($store_master_data[0]['back_side_ink_colors']),
							'sleeve_ink_colors'  => trim($store_master_data[0]['sleeve_ink_colors']),
							'store_fulfillment_type' => trim(@$store_master_data[0]['store_fulfillment_type']),
							'store_sale_type_master_id' => trim($store_master_data[0]['store_sale_type_master_id']),
							'is_fundraising' => trim($store_master_data[0]['is_fundraising']),
							'ct_fundraising_price' => trim($store_master_data[0]['ct_fundraising_price']), // Task 40 Add ct_fundraising_price
							'store_name' => trim($duplicate_store_name),
							'store_description' => $store_descriptionOrg,
							'store_open_date' => $store_master_data[0]['store_open_date'] != '' ? $store_master_data[0]['store_open_date'] : '',
							'store_close_date' => $store_master_data[0]['store_close_date'] != '' ? $store_master_data[0]['store_close_date'] : '',
							'status' => '1',
							'created_on' => @date('Y-m-d H:i:s'),
							'created_on_ts' => time(),
						];
						$store_master_arr = parent::insertTable_f_mdl('store_master', $sm_insert_data);

						if (isset($store_master_arr['insert_id'])) {
							$sql = 'SELECT * FROM `store_owner_address_master` WHERE store_master_id="' . $copy_store_master_id . '"';

							$store_owner_address_master_data =  parent::selectTable_f_mdl($sql);

							//insert owner address details
							$store_master_id = $store_master_arr['insert_id'];
							$soam_insert_data = [
								'store_owner_details_master_id' => $store_owner_details_master_id,
								'store_master_id' => $store_master_id,
								'check_payable_to_name' => trim($store_owner_address_master_data[0]['check_payable_to_name']),
								'address_line_1' => trim($store_owner_address_master_data[0]['address_line_1']),
								'address_line_2' => trim($store_owner_address_master_data[0]['address_line_2']),
								'country' => trim($store_owner_address_master_data[0]['country']),
								'city' => trim($store_owner_address_master_data[0]['city']),
								'state' => trim($store_owner_address_master_data[0]['state']),
								'zip_code' => trim($store_owner_address_master_data[0]['zip_code']),
								'status' => '1',
								'created_on' => @date('Y-m-d H:i:s'),
								'created_on_ts' => time(),
							];
							parent::insertTable_f_mdl('store_owner_address_master', $soam_insert_data);

							//insert owner silver delivery address details
							$sql = 'SELECT * FROM `store_owner_silver_delivery_address_master` WHERE store_master_id="' . $copy_store_master_id . '"';
							$store_owner_silver_delivery_address_master_data =  parent::selectTable_f_mdl($sql);

							$sosdam_insert_data = [
								'store_owner_details_master_id' => $store_owner_details_master_id,
								'store_master_id' => $store_master_id,
								'first_name' => trim($store_owner_silver_delivery_address_master_data[0]['first_name']),
								'last_name' => trim($store_owner_silver_delivery_address_master_data[0]['last_name']),
								'company_name' => trim($store_owner_silver_delivery_address_master_data[0]['company_name']),
								'address_line_1' => trim($store_owner_silver_delivery_address_master_data[0]['address_line_1']),
								'address_line_2' => trim($store_owner_silver_delivery_address_master_data[0]['address_line_2']),
								'country' => trim($store_owner_silver_delivery_address_master_data[0]['country']),
								'city' => trim($store_owner_silver_delivery_address_master_data[0]['city']),
								'state' => trim($store_owner_silver_delivery_address_master_data[0]['state']),
								'zip_code' => trim($store_owner_silver_delivery_address_master_data[0]['zip_code']),
								'is_ship_to_address_added' => trim($store_owner_silver_delivery_address_master_data[0]['is_ship_to_address_added']),
								'st_first_name' => trim($store_owner_silver_delivery_address_master_data[0]['st_first_name']),
								'st_last_name' => trim($store_owner_silver_delivery_address_master_data[0]['st_last_name']),
								'st_company_name' => trim($store_owner_silver_delivery_address_master_data[0]['st_company_name']),
								'st_address_line_1' => trim($store_owner_silver_delivery_address_master_data[0]['st_address_line_1']),
								'st_address_line_2' => trim($store_owner_silver_delivery_address_master_data[0]['st_address_line_2']),
								'st_country' => trim($store_owner_silver_delivery_address_master_data[0]['st_country']),
								'st_city' => trim($store_owner_silver_delivery_address_master_data[0]['st_city']),
								'st_state' => trim($store_owner_silver_delivery_address_master_data[0]['st_state']),
								'st_zip_code' => trim($store_owner_silver_delivery_address_master_data[0]['st_zip_code']),
								'status' => '1',
								'created_on' => @date('Y-m-d H:i:s'),
								'created_on_ts' => time(),
							];
							parent::insertTable_f_mdl('store_owner_silver_delivery_address_master', $sosdam_insert_data);

							//insert logo details
							$sql = 'SELECT * FROM `store_design_logo_master` WHERE store_master_id="' . $copy_store_master_id . '"';
							$store_design_logo_master_data =  parent::selectTable_f_mdl($sql);

							if (!empty($store_design_logo_master_data)) {
								/*Task 56 start 20/10/2021*/
								foreach ($store_design_logo_master_data as $value) {
									$sdlm_insert_data = [
										'store_master_id' => $store_master_id,
										'logo_image' => $value['logo_image'],
										'is_default' => '0',
										'status' => '1',
										'created_on' => @date('Y-m-d H:i:s'),
										'created_on_ts' => time(),
									];
									parent::insertTable_f_mdl('store_design_logo_master', $sdlm_insert_data);
								}
								/*Task 56 end 20/10/2021*/
							}

							//insert products details
							$image_for_flyer = '';

							//now we have sorted array product-id wise
							$sql = 'SELECT * FROM `store_owner_product_master` WHERE store_master_id="' . $copy_store_master_id . '"';
							$pro_list =  parent::selectTable_f_mdl($sql);


							if (!empty($pro_list)) {
								foreach ($pro_list as $single_pro) {
									$old_store_product_master_id = $single_pro['id'];
									//insert product details
									$sopm_insert_data = [
										'store_master_id' => $store_master_id,
										'store_product_master_id' => $single_pro['store_product_master_id'],
										'product_title' => $single_pro['product_title'],
										'product_description' => $single_pro['product_description'],
										'tags' => $single_pro['tags'],
										'status' => '1',
										'is_product_fundraising' => trim($store_master_data[0]['is_fundraising']),
										'created_on' => @date('Y-m-d H:i:s'),
										'created_on_ts' => time()
									];
									$sopm_arr = parent::insertTable_f_mdl('store_owner_product_master', $sopm_insert_data);

									if (isset($sopm_arr['insert_id'])) {
										$sopm_id = $sopm_arr['insert_id'];

										$sql = 'SELECT * FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id="' . $old_store_product_master_id . '"';
										$var_list =  parent::selectTable_f_mdl($sql);

										if (!empty($var_list)) {
											foreach ($var_list as $var_data) {
												$image = $var_data['image'];

												if ($image != '') {
													$image_for_flyer = $image;
												}

												// Task 42 start
												$sql = 'SELECT price,price_on_demand from store_product_variant_master where id="' . $var_data['store_product_variant_master_id'] . '"';
												$storeProductVariantMaster = parent::selectTable_f_mdl($sql);

												/*
												* Front-side and back-side price only added with on-demand store
												* Add front-side as per color catridge price into base price
												*/
												$add_cost = 0;
												if (isset($store_master_data[0]['front_side_ink_colors']) && !empty($store_master_data[0]['front_side_ink_colors'])) {
													$add_cost += intval($store_master_data[0]['front_side_ink_colors']) - 1;
												}

												//Add back-side as per color catridge price into base price
												$add_on_cost = 0; // Task 50 Add new variable for on_demand
												if (isset($store_master_data[0]['back_side_ink_colors']) && !empty($store_master_data[0]['back_side_ink_colors'])) {
													$add_cost   += common::ADD_COST_BACK_SIDE_INK_COLOR + intval($store_master_data[0]['back_side_ink_colors']) - 1;
													$add_on_cost = common::ADD_COST_BACK_SIDE_INK_COLOR; // Task 50 Add new variable for on_demand
												}

												if (isset($store_master_data[0]['sleeve_ink_colors']) && !empty($store_master_data[0]['sleeve_ink_colors'])) {
													$add_cost += common::ADD_COST_BACK_SIDE_INK_COLOR + intval($store_master_data[0]['sleeve_ink_colors'])-1;
												}

												/*
												* ADD_COST_STORE_FULFILLMENT_TYPE_2 means add $2 in base price on flash sale case
												* ADD_COST_STORE_FULFILLMENT_TYPE_3 means add $6 in base price on flash sale case
												*/
												$fullfillmentsql = 'SELECT * FROM `general_settings_master` WHERE id=1';
												$getFullfilmentPrice= parent::selectTable_f_mdl($fullfillmentsql);
												$fullfilment_silver_price='0';
												$fullfilment_gold_price='0';
												$fullfilment_platinum_price='0';
												if(!empty($getFullfilmentPrice)){
													$fullfilment_silver_price   = $getFullfilmentPrice[0]['fullfilment_silver_price'];
													$fullfilment_gold_price   = $getFullfilmentPrice[0]['fullfilment_gold_price'];
													$fullfilment_platinum_price   = $getFullfilmentPrice[0]['fullfilment_platinum_price'];
												}
												$fullfilment_type_price = 0;
												if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
													$fullfilment_type_price = $fullfilment_gold_price;
													//$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_2;
												}
												else if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
													$fullfilment_type_price = $fullfilment_platinum_price;
													//$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_3;
												}else if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=='SHIP_1_LOCATION_NOT_SORT'){
													$fullfilment_type_price = $fullfilment_silver_price;
												}
												

												//To do add bussiness login for fullfilmemnt type & fundrising
												$ondemandPrice  = 0;
												$flashSalePrice = 0;
												if (isset($storeProductVariantMaster[0]['price']) && $storeProductVariantMaster[0]['price_on_demand']) {
													$ondemandPrice = (floatval($storeProductVariantMaster[0]['price_on_demand']) + $add_on_cost);
													$flashSale     = $storeProductVariantMaster[0]['price'] + $add_cost + $fullfilment_type_price;
												} else {
													$ondemandPrice = $var_data['price_on_demand'];
													$flashSale     = $var_data['price'];
												}
												// Task 42 end

												$sopvm_insert_data = [
													'store_owner_product_master_id' => $sopm_id,
													// 'store_product_variant_master_id' => $var_data['id'],
													'store_product_variant_master_id' => $var_data['store_product_variant_master_id'],
													'store_organization_type_master_id' => $var_data['store_organization_type_master_id'],
													// 'price' => $var_data['price'],
													'price' => $flashSale,
													// 'price_on_demand' => $var_data['price_on_demand'],
													'price_on_demand' => $ondemandPrice,
													'fundraising_price' => $var_data['fundraising_price'],
													'front_side_ink_colors_group' 		=> $store_master_data[0]['front_side_ink_colors'],
													'back_side_ink_colors_group' 		=> $store_master_data[0]['back_side_ink_colors'],
													'sleeve_ink_color_group' 		    => $store_master_data[0]['sleeve_ink_colors'],
													'color' => $var_data['color'],
													'size' => $var_data['size'],
													'image' => $var_data['image'],
													'original_image' => $var_data['original_image'],
													'sku' => $var_data['sku'],
													'weight' => $var_data['weight'],
													'status' => '1',
													'created_on' => @date('Y-m-d H:i:s'),
													'created_on_ts' => time()
												];
												parent::insertTable_f_mdl('store_owner_product_variant_master', $sopvm_insert_data);
											}
										}
									}
								}
							}

							//insert sorting details
							$sql = 'SELECT * FROM `store_sort_list_master` WHERE store_master_id="' . $copy_store_master_id . '"';
							$store_sort_list_master_data =  parent::selectTable_f_mdl($sql);

							if (isset($store_sort_list_master_data) && !empty($store_sort_list_master_data)) {
								foreach ($store_sort_list_master_data as $single_sl) {
									$sslm_insert_data = [
										'store_owner_details_master_id' => $store_owner_details_master_id,
										'store_master_id' => $store_master_id,
										'sort_list_name' => $single_sl['sort_list_name'],
										'sort_list_index' => $single_sl['sort_list_index'],
										'status' => '1',
										'created_on' => @date('Y-m-d H:i:s'),
										'created_on_ts' => time(),
									];
									parent::insertTable_f_mdl('store_sort_list_master', $sslm_insert_data);
								}
							}

							//insert request design details
							$sql = 'SELECT * FROM `store_request_design_master` WHERE store_master_id="' . $copy_store_master_id . '"';
							$store_request_design_master_data =  parent::selectTable_f_mdl($sql);

							if (!empty($store_request_design_master_data)) {
								$srdm_insert_data = [
									'store_owner_details_master_id' => $store_owner_details_master_id,
									'store_master_id' => $store_master_id,
									'store_free_design_product_master_id' => trim($store_request_design_master_data[0]['store_free_design_product_master_id']),
									'front_design_text' => trim($store_request_design_master_data[0]['front_design_text']),
									'back_design_text' => trim($store_request_design_master_data[0]['back_design_text']),
									'designer_notes' => trim($store_request_design_master_data[0]['designer_notes']),
									'apparel_color_code' => trim($store_request_design_master_data[0]['apparel_color_code']),
									'ink_color_code' => trim($store_request_design_master_data[0]['ink_color_code']),
									'status' => '1',
									'created_on' => @date('Y-m-d H:i:s'),
									'created_on_ts' => time(),
								];
								$store_request_design_master_arr = parent::insertTable_f_mdl('store_request_design_master', $srdm_insert_data);

								if (isset($store_request_design_master_arr['insert_id'])) {
									$store_request_design_master_id = $store_request_design_master_arr['insert_id'];

									$old_store_request_design_master_id = $store_request_design_master_data[0]['id'];

									$sql = 'SELECT * FROM `store_request_design_reference_images_master` WHERE store_request_design_master_id="' . $old_store_request_design_master_id . '"';
									$srdm_images_arr =  parent::selectTable_f_mdl($sql);

									if (!empty($srdm_images_arr)) {
										foreach ($srdm_images_arr as $single_img) {
											$srdrim_insert_data = [
												'store_request_design_master_id' => $store_request_design_master_id,
												'image' => @$single_imgp['image'],
												'status' => '1',
												'created_on' => @date('Y-m-d H:i:s'),
												'created_on_ts' => time(),
											];
											parent::insertTable_f_mdl('store_request_design_reference_images_master', $srdrim_insert_data);
										}
									}
								}
							}

							try {
								//insert flyer
								// if ($image_for_flyer != '') {
									// $english_color_pdf = '';
									// $english_bw_pdf = '';
									// $spanish_color_pdf = '';
									// $spanish_bw_pdf = '';

									// $api_path = '';
									// $documentData = json_encode([
									// 	'image' => common::IMAGE_UPLOAD_URL . $image_for_flyer,
									// 	'date' => $store_master_data[0]['store_close_date']
									// ]);

									// $template_id = common::PDF_GENERATE_API_FLYER_ENGCLR_TEMPL_ID;
									// $pdf_res = parent::get_pdf_by_api(common::PDF_GENERATE_API_KEY, common::PDF_GENERATE_API_SECRET, common::PDF_GENERATE_API_WORKSPACE, $template_id, $documentData, $api_path);
									// if (isset($pdf_res['pdf_file']) && !empty($pdf_res['pdf_file'])) {
									// 	$english_color_pdf = $pdf_res['pdf_file'];
									// }
									// $template_id = common::PDF_GENERATE_API_FLYER_ENGBW_TEMPL_ID;
									// $pdf_res = parent::get_pdf_by_api(common::PDF_GENERATE_API_KEY, common::PDF_GENERATE_API_SECRET, common::PDF_GENERATE_API_WORKSPACE, $template_id, $documentData, $api_path);
									// if (isset($pdf_res['pdf_file']) && !empty($pdf_res['pdf_file'])) {
									// 	$english_bw_pdf = $pdf_res['pdf_file'];
									// }
									// $template_id = common::PDF_GENERATE_API_FLYER_SPACLR_TEMPL_ID;
									// $pdf_res = parent::get_pdf_by_api(common::PDF_GENERATE_API_KEY, common::PDF_GENERATE_API_SECRET, common::PDF_GENERATE_API_WORKSPACE, $template_id, $documentData, $api_path);
									// if (isset($pdf_res['pdf_file']) && !empty($pdf_res['pdf_file'])) {
									// 	$spanish_color_pdf = $pdf_res['pdf_file'];
									// }
									// $template_id = common::PDF_GENERATE_API_FLYER_SPABW_TEMPL_ID;
									// $pdf_res = parent::get_pdf_by_api(common::PDF_GENERATE_API_KEY, common::PDF_GENERATE_API_SECRET, common::PDF_GENERATE_API_WORKSPACE, $template_id, $documentData, $api_path);
									// if (isset($pdf_res['pdf_file']) && !empty($pdf_res['pdf_file'])) {
									// 	$spanish_bw_pdf = $pdf_res['pdf_file'];
									// }

									// $sof_insert_data = [
									// 	'store_master_id' => $store_master_id,
									// 	'end_date' => $store_master_data[0]['store_close_date'],
									// 	'flyer_title' => 'Flyer for ' . trim('Copy of ' . $store_master_data[0]['store_name']),
									// 	'selected_image_path' => $image_for_flyer,
									// 	'english_color_pdf' => $english_color_pdf,
									// 	'english_bw_pdf' => $english_bw_pdf,
									// 	'spanish_color_pdf' => $spanish_color_pdf,
									// 	'spanish_bw_pdf' => $spanish_bw_pdf,
									// 	'status' => '1',
									// 	'created_on' => @date('Y-m-d H:i:s'),
									// ];
									// parent::insertTable_f_mdl('store_owner_flyer', $sof_insert_data);
								// }
							} catch (Exception $e) {
								return true;
							}

							//send welcome mail to shop owner
							require_once(common::EMAIL_REQUIRE_URL);
							if (strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp') !== false) {
								$objAWS = new aws_ses_smtp();
							} else if (strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail') !== false) {
								$objAWS = new sendGridEmail();
							} else {
								$objAWS = new Aws(common::AWS_ACCESS_KEY, common::AWS_SECRET_KEY, common::AWS_REGION);
							}

							$sql = 'SELECT email, first_name, organization_name FROM `store_owner_details_master` WHERE id="' . $store_owner_details_master_id . '"';
							$sodm_data =  parent::selectTable_f_mdl($sql);

							if (!empty($sodm_data)) {
								$sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_details_master_id . '"';
								$smm_data =  parent::selectTable_f_mdl($sql_managerData);

								$store_name_sql = 'SELECT store_name,store_open_date,store_close_date FROM `store_master` WHERE store_master_id="' . $store_master_id . '"';
								$store_name_data =  $configObj->selectTable_f_mdl($store_name_sql);

								$sql = 'SELECT subject,body FROM `email_templates_master` WHERE id=' . common::NEW_STORE_CREATED_TO_CUSTOMER_ADMIN;
								$et_data = parent::selectTable_f_mdl($sql);
								$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
								$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
								$store_open_date=!empty($store_name_data[0]["store_open_date"]) ? date('m/d/Y', $store_name_data[0]["store_open_date"]) : '' ;
								$store_last_date=!empty($store_name_data[0]["store_close_date"]) ? date('m/d/Y', $store_name_data[0]["store_close_date"]) : '' ;

								if (!empty($et_data)) {
									$subject = $et_data[0]['subject'];
									$body = $et_data[0]['body'];
									$to_email = $sodm_data[0]['email'];
									$from_email = common::AWS_ADMIN_EMAIL;
									$attachment = [];

									$body 		= str_replace('{{FIRST_NAME}}', $sodm_data[0]['first_name'], $body);
									$body       = str_replace('{{STORE_NAME}}', $store_name_data[0]['store_name'], $body);
									$body 		= str_replace('{{DASHBOARD_LINK}}', common::CUSTOMER_ADMIN_DASHBOARD_URL, $body);
									$body 		= str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
									$body       = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
									$body       = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
									//$mailSendStatus = $objAWS::sendEmail($from_email, $to_email, $subject, $body, $attachment);

									$sql = 'SELECT * FROM store_master WHERE id="' . $store_master_id . '"';
									$store_data = parent::selectTable_f_mdl($sql);

									$mailSendStatus = 1;
									//if($store_data[0]['email_notification'] == '1'){
									if ($duplicate_send_email) {
										$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body);
										if (!empty($smm_data)) {
											foreach ($smm_data as $managerData) {
												$to_email   = $managerData['email'];
												$body       = $et_data[0]['body'];
												$body       = str_replace('{{FIRST_NAME}}', $managerData['first_name'], $body);
												$body       = str_replace('{{STORE_NAME}}', $store_name_data[0]['store_name'], $body);
												$body       = str_replace('{{DASHBOARD_LINK}}', common::CUSTOMER_ADMIN_DASHBOARD_URL, $body);
												$body 		= str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
												$body       = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
												$body       = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
												$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body);
											}
										}
									}
								}

								//send mail to super admin
								$sql = 'SELECT subject,body,recipients FROM `email_templates_master` WHERE id=' . common::NEW_STORE_CREATED_TO_SUPER_ADMIN;
								$et_data = parent::selectTable_f_mdl($sql);
								$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
								$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
								$store_open_date=!empty($store_name_data[0]["store_open_date"]) ? date('m/d/Y', $store_name_data[0]["store_open_date"]) : '' ;
								$store_last_date=!empty($store_name_data[0]["store_close_date"]) ? date('m/d/Y', $store_name_data[0]["store_close_date"]) : '' ;

								if (!empty($et_data)) {
									$subject = $et_data[0]['subject'];
									$body = $et_data[0]['body'];

									/* Task 34 start */
									$ccMails = '';
									if ($et_data[0]['recipients']) {
										$recipients = $et_data[0]['recipients'];
										$recipients = str_replace(' ', '', $recipients);
										$ccMails = explode(',', $recipients);
									}
									/* Task 34 end */

									$to_email = common::SUPER_ADMIN_EMAIL;
									$from_email = common::AWS_ADMIN_EMAIL;
									$attachment = [];

									$body = str_replace('{{ORGANIZATION_NAME}}', $sodm_data[0]['organization_name'], $body);
									$body = str_replace('{{STORE_NAME}}', $store_name_data[0]['store_name'], $body);
									$body = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
									$body = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
									$body = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
									//$mailSendStatus = $objAWS::sendEmail($from_email, $to_email, $subject, $body, $attachment);

									$sql = 'SELECT * FROM store_master WHERE id="' . $store_master_id . '"';
									$store_data = parent::selectTable_f_mdl($sql);

									$mailSendStatus = 1;
									//if($store_data[0]['email_notification'] == '1'){
									$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body, $ccMails);
									//}
								}
							}


							$res['SUCCESS'] = 'TRUE';
							$res['MESSAGE'] = 'Store created successfully.';
							$res['token'] = $store_owner_details_master_token;
							$res['redirect_url'] = 'sa-store-view.php?stkn=' . $_POST['stkn'] . '&id=' . $store_master_id;
						} else {
							$res['SUCCESS'] = 'FALSE';
							$res['MESSAGE'] = 'Error while inserting store details. Please check and try again after some time.';
						}
					} else {
						$res['SUCCESS'] = 'FALSE';
						$res['MESSAGE'] = 'Error found in owner details. Please check and try again after some time.';
					}
				}

				common::sendJson($res);
			}
		}
	}

	public function save_customer_store_data()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "duplicateStore" && !empty(parent::getVal("store_master_id")) && !empty(parent::getVal("store_owner_details_master_id"))) {

				$copy_store_master_id = parent::getVal("store_master_id");
				$store_owner_details_master_id = parent::getVal("store_owner_details_master_id");
				$duplicate_store_name = parent::getVal("duplicate_store_name");
				$duplicate_send_email = parent::getVal("duplicate_send_email");

				$reponse = createStoreHelper::saveCustomerStoreData(true, $copy_store_master_id, $store_owner_details_master_id, $duplicate_store_name, $duplicate_send_email);
				echo $reponse;
			}
		}
	}

	public function composeMail()
	{
		global $s3Obj;
		global $login_user_email;
		#region - Send Mail To Store Admin
		require_once(common::EMAIL_REQUIRE_URL);
		$objAWS = '';
		if (strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp') !== false) {
			$objAWS = new aws_ses_smtp();
		} else if (strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail') !== false) {
			$objAWS = new sendGridEmail();
		} else {
			$objAWS = new Aws(common::AWS_ACCESS_KEY, common::AWS_SECRET_KEY, common::AWS_REGION);
		}

		$data = '';
		$res  = array();

		if (!empty($_POST['store_master_ids'])) {
			$data        = implode(',', $_POST['store_master_ids']);
			$sql         = 'SELECT * FROM store_master WHERE id IN(' . $data . ')';
			$store_data  = parent::selectTable_f_mdl($sql);

			$store_sql="SELECT GROUP_CONCAT('<span>',sm.`store_name`,'<br>',sodm.`email`, '</span>') as store_name from store_master as sm INNER JOIN store_owner_details_master as sodm ON sm.`store_owner_details_master_id` = sodm.`id` where sm.id IN($data)";
			$store_list = parent::selectTable_f_mdl($store_sql);
			$store_names=$store_list[0]['store_name'];
			$from_email     = $_POST['from_mail'];
			$subject        = (!empty($_POST['compose_email_subject'])) ? $_POST['compose_email_subject'] : 'Store Notification';
			$store_names=str_replace(",","",$store_names);
			$emailHistoryData = [
				"store_master_ids"            => $data,
				"store_name"   				 => $store_names,
				"from_email"   				 => $from_email,
				"subject"   				 => $subject,
				"update_on"                 => date('Y-m-d H:i:s'),
				"sent_by"				=> "Super Admin <br>(".$login_user_email.")",
			];
			parent::insertTable_f_mdl('compose_email_history',$emailHistoryData);

			foreach ($store_data as $values) {
				$store_name                      = $values['store_name'];
				$store_owner_details_master_id   = $values['store_owner_details_master_id'];
				// Task 85 start update custom email (add item_sold)
				$store_master_id                 = $values['id'];
				$soldSql    = 'SELECT IFNULL(SUM(oim.quantity),0) as sold_items,om.store_master_id,oim.store_owner_product_master_id ,oim.title,om.id as store_order_master_id FROM `store_orders_master` as om INNER JOIN store_order_items_master as oim on om.id = oim.store_orders_master_id WHERE om.is_order_cancel = 0 AND oim.is_deleted = 0 AND oim.store_master_id = "' . $store_master_id . '"';
				$qtySold    =  parent::selectTable_f_mdl($soldSql);

				$item_sold  = '';
				if (!empty($qtySold)) {
					$item_sold  = $qtySold[0]['sold_items'];
				}
				// Task 85 end update custom email (add item_sold)
				$sql1               = 'SELECT id,email,first_name,last_name FROM store_owner_details_master WHERE id = "' . $store_owner_details_master_id . '"';
				$store_owner_data   = parent::selectTable_f_mdl($sql1);
				$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
				$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
				$store_open_date=!empty($values["store_open_date"]) ? date('m/d/Y', $values["store_open_date"]) : '' ;
				$store_last_date=!empty($values["store_close_date"]) ? date('m/d/Y', $values["store_close_date"]) : '' ;
				foreach ($store_owner_data as $Dvalues) {
					$first_name     = $Dvalues['first_name'];
					$last_name      = $Dvalues['last_name'];
					// Task 81 start
					$store_handle   = (!empty($values['shop_collection_handle'])) ? $values['shop_collection_handle'] : '';

					if (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] == "localhost") || ($_SERVER['HTTP_HOST'] == "spirithero-rds.bitcotapps.com")) {
						$store_url        = '<a href="https://spirithero-rds.myshopify.com/collections/' . $store_handle . '">https://spirithero-rds.myshopify.com/collections/' . $store_handle . '</a>';
					} else {
						$store_url        =  '<a href="https://' . common::STORE_DOMAIN . '/collections/' . $store_handle . '">https://' . common::STORE_DOMAIN . '/collections/' . $store_handle . '</a>';
					}
					// Task 81 end
					$to_email       = $Dvalues['email'];
					$subject        = (!empty($_POST['compose_email_subject'])) ? $_POST['compose_email_subject'] : 'Store Notification';
					$body           = (!empty($_POST['message'])) ? $_POST['message'] : '';
					$body           = str_replace(["{{first_name}}", "{{last_name}}", "{{store_name}}", "{{FRONT_STORE_URL}}", "{{#_of_items_sold}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"], [$first_name, $last_name, $store_name, $store_url, $item_sold,$logo,$store_open_date,$store_last_date], $body); // Task 81 add store_Url   Task 85 update custom email (add item_sold)
					$from_email     = $_POST['from_mail'];
					$from_name      = $_POST['from_name'];

					$ccMails        = '';
					$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, '', array(), $from_email, $from_name);

					if ($mailSendStatus == 1) {
						$res['SUCCESS'] = 'TRUE';
						$res['MESSAGE'] = 'Mail send successfully.';
					} else {
						$res['SUCCESS'] = 'FALSE';
						$res['MESSAGE'] = '!Something went wrong.';
					}
				}
			}

		} else {
			$res['MESSAGE'] = '!Something went wrong';
		}
		common::sendJson($res);
		die;
	}

	// Task 95 Bulk delete start 
	public function bulk_delete()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "bulk_delete") {
				$storeMasterId = parent::getVal("store_master_ids");

				foreach ($storeMasterId as $values) {
					parent::deleteTable_f_mdl('store_master', 'id =' . $values);
				}
				$resultArray = array();
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Store data delete successfully.";
				common::sendJson($resultArray);
			}
			die;
		}
	}
	// Task 95 Bulk delete end	

	public function bulkCloseStore()
	{
		global $login_user_email;
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "bulk_close_store") {
				$storeMasterId = parent::getVal("store_master_ids");
				$store_closed = 'Yes';
				$status = '0';
				$production_date = time();
				$store_description = common::DESCRIPTION_FOR_CLOSED_STORE;

				foreach ($storeMasterId as $values) {

					$sql = 'SELECT * FROM store_master WHERE id="'.$values.'"';
					$store_data = parent::selectTable_f_mdl($sql);

					if(empty($store_data[0]['shop_collection_id'])){

						$smupdate_data = [
							'status'	=> $status,
							'production_date'	 => $production_date,
							'store_description' => $store_description,
							'updated_on'         => date('Y-m-d H:i:s')
						];
						parent::updateTable_f_mdl('store_master',$smupdate_data,'id="'.$values.'"');



					}else{

						$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
						if(!empty($shop_data)) {
							global $path;
							require_once($path.'lib/class_graphql.php');

							$headers = array(
								'X-Shopify-Access-Token' => $shop_data[0]['token']
							);
							$graphql = new Graphql($shop_data[0]['shop_name'], $headers);

							$collection_id  = $store_data[0]['shop_collection_id'];
							$meta_namespace = common::FLASH_SALE_END_NAMESPACE;
							$meta_key  = common::FLASH_SALE_END_KEY;
							$meta_value= common::FLASH_SALE_END_VALUE;

							//fetch meta for store-close
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
							$coll_meta_data = $graphql->runByQuery($query);
							$collection_meta_id_for_close = '';
							if(isset($coll_meta_data['data']['collection']['metafields']['edges']) && !empty($coll_meta_data['data']['collection']['metafields']['edges'])){
								foreach($coll_meta_data['data']['collection']['metafields']['edges'] as $meta_edge){
									if($meta_edge['node']['namespace']==$meta_namespace && $meta_edge['node']['key']==$meta_key){
										$collection_meta_id_for_close = $meta_edge['node']['id'];
									}
								}
							}

							//if not exist then add meta for store-close
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
								$graphql->runByMutation($mutationData,$inputData);
								self::addDraftProduct($values,$store_closed,$collection_id);
							}
							
							$smupdate_data = [
								'status'	=> $status,
								'production_date'	 => $production_date,
								'store_description' => $store_description,
								'updated_on'         => date('Y-m-d H:i:s')
							];
							parent::updateTable_f_mdl('store_master',$smupdate_data,'id="'.$values.'"');
						}
					}

					$storeSettingData = [
						"section_name"           => "Store Setting",
						"store_master_id"        => $values,
						"store_type"             => $store_data[0]['store_sale_type_master_id'],
						"store_closed"           => $status,
						"email_notification"     => $store_data[0]['email_notification'],
						"pre_store_type"         => $store_data[0]['store_sale_type_master_id'],
						"pre_store_status"       => $store_data[0]['status'],
						"pre_email_notification" => $store_data[0]['email_notification'],
						"updated_by"             => "Super Admin <br>(".$login_user_email.")",
						"updated_on"             => date('Y-m-d H:i:s')
					];
					parent::insertTable_f_mdl('store_history',$storeSettingData);

					$storeStatusHistoryData =[
						'store_master_id' => $values,
						'status'          => '8',
						'created_on'      => date('Y-m-d H:i:s'),
						'updated_by'	  =>"Super Admin <br>(".$login_user_email.")"
					];
					parent::insertTable_f_mdl('store_status_history', $storeStatusHistoryData);
				}

				$resultArray = array();
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Store closed successfully.";
				common::sendJson($resultArray);
			}
			die;
		}
	}

	public function addDraftProduct($storeId,$closeStore,$collection_id)
	{
		global $path;
		$storeSql = 'SELECT id, shop_name, token, timezone FROM shop_management WHERE id = 1';
		$storeInfo = parent::selectTable_f_mdl($storeSql);
		#region - Initialize Shopify Class Object
		require_once($path.'lib/shopify.php');
		require_once($path.'lib/functions.php');

		$shopifyObject = new ShopifyClient($storeInfo[0]["shop_name"], $storeInfo[0]["token"], common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
		require_once($path.'lib/class_graphql.php');
			
		$headers = array(
			'X-Shopify-Access-Token' => $storeInfo[0]["token"]
		);
		$graphql = new Graphql($storeInfo[0]["shop_name"], $headers);
		#endregion

		$productStatus = '';
		$collectionStatus = '';
		if ($closeStore=='Yes') {
			$productStatus = "DRAFT";
			$collectionStatus = "false";
		}
		else{
			$productStatus = "ACTIVE";
			$collectionStatus = "true";	
		}
			
		$CollectionPublishedArray = [
		    "custom_collection" => [
		      "id" => $collection_id,
		      "published" => $collectionStatus
		    ]
  		];
		$CollectionInfo = $shopifyObject->call('PUT', '/admin/api/2023-04/custom_collections/'.$collection_id.'.json', $CollectionPublishedArray);

		$productSql  = 'SELECT store_master_id,shop_product_id FROM store_owner_product_master WHERE store_master_id = "'.$storeId.'" AND is_soft_deleted="0" ';
		$productData = parent::selectTable_f_mdl($productSql);
		foreach ($productData as $value) {
			//now assign that imageId to variant
			$mutation = 'mutation productUpdate($input: ProductInput!) {
			  productUpdate(input: $input) {
				product {
				  id
				  status
				}
				userErrors {
				  field
				  message
				}
			  }
			}';
			$input = '{
			  "input": {
				"id": "gid://shopify/Product/'.$value['shop_product_id'].'",
				"status": "'.$productStatus.'"
			  }
			}';
			sleep(0.5);
			$dataQl = $graphql->runByMutation($mutation, $input);
		}
	}

	function storeProcessingPagination()
	{
		if (parent::isPOST()) {
			if (parent::getVal("hdn_method") == "storeProcessingPagination") {
				$record_count = 0;
				$page = 0;
				$current_page = 1;
				$rows = '10';
				$keyword = '';

				if ((isset($_REQUEST['rows'])) && (!empty($_REQUEST['rows']))) {
					$rows = $_REQUEST['rows'];
				}
				if ((isset($_REQUEST['keyword'])) && (!empty($_REQUEST['keyword']))) {
					$keyword = $_REQUEST['keyword'];
				}
				if ((isset($_REQUEST['current_page'])) && (!empty($_REQUEST['current_page']))) {
					$current_page = $_REQUEST['current_page'];
				}
				$start = ($current_page - 1) * $rows;
				$end = $rows;
				$sort_field = '';
				if (isset($_POST['sort_field']) && !empty($_POST['sort_field'])) {
					$sort_field = $_POST['sort_field'];
				}
				$sort_type = '';
				if (isset($_POST['sort_type']) && !empty($_POST['sort_type'])) {
					$sort_type = $_POST['sort_type'];
				}


				$cond_keyword = '';
				if (isset($keyword) && !empty($keyword)) {
					$cond_keyword = "AND (
						sm.store_name LIKE '%" . trim($keyword) . "%' OR sm.notes)";
				}
				$cond_status = '';
				if (isset($_POST['store_status'])) {
					$cond_status = 'AND sm.status = "' . $_POST['store_status'] . '"';
				}

				$cond_order = 'ORDER BY id DESC';
				if (!empty($sort_field)) {
					$cond_order = 'ORDER BY ' . $sort_field . ' ' . $sort_type;
				}

				$verificationStatus = "";
				$fullfilmentStatus = "";

				/*$cond_start_end = '';
				if(isset($this->start_date) && !empty($this->start_date) && isset($this->end_date) && !empty($this->end_date) ){
					$cond_start_end = "AND add_date BETWEEN ".$this->start_date." AND ".$this->end_date."";
				}*/
				//Task 74 add column name Task 117 add colum sm.updated_on,
				$sql = "SELECT count(sm.id) as count, sm.id, sm.shop_collection_handle,sm.is_store_batched, sm.front_side_ink_colors, sm.back_side_ink_colors,sm.store_name,sm.store_open_date,sm.is_fundraising,sm.store_fulfillment_type,sm.store_close_date,sm.store_in_hands_date,sm.verification_status,sm.is_collection_created,sm.is_products_synced,sm.approved_date
				 FROM store_master sm WHERE ( 
					(sm.is_products_synced = 0 AND sm.is_collection_created = 0 AND sm.is_manual_store_sync = 1) OR
				  (sm.is_products_synced = 0 AND sm.is_collection_created = 1 AND sm.is_manual_store_sync = 1) OR 
				  (sm.is_products_synced = 1 AND sm.is_collection_created = 1 AND sm.is_manual_store_sync = 0) OR
				  (sm.is_products_synced = 1 AND sm.is_collection_created = 0 AND sm.is_manual_store_sync = 0)) 
				   AND sm.status = 1 AND sm.verification_status = '1'
				$cond_keyword
				$cond_status
			 ";

				$all_count = parent::selectTable_f_mdl($sql);

				//Task 22 add new parameter store_sale_type_master_id //Task 74 add column name Task 117 add colum sm.updated_on,
				$sql1 = "SELECT sm.id,sm.shop_collection_handle,sm.is_store_batched,sm.front_side_ink_colors, sm.back_side_ink_colors,sm.store_name,sm.store_open_date,sm.is_fundraising,sm.store_fulfillment_type,sm.store_close_date,sm.store_in_hands_date,sm.verification_status,sm.notes,sm.is_collection_created,sm.is_products_synced,sm.is_manual_store_sync,sm.approved_date 
				FROM store_master sm WHERE ( 
					(sm.is_products_synced = 0 AND sm.is_collection_created = 0 AND sm.is_manual_store_sync = 1) OR
				  (sm.is_products_synced = 0 AND sm.is_collection_created = 1 AND sm.is_manual_store_sync = 1) OR 
				  (sm.is_products_synced = 1 AND sm.is_collection_created = 1 AND sm.is_manual_store_sync = 0) OR
				  (sm.is_products_synced = 1 AND sm.is_collection_created = 0 AND sm.is_manual_store_sync = 0)) 
				  AND sm.status = 1 AND sm.verification_status = '1'
				$cond_keyword
				$cond_status
				
				ORDER BY sm.approved_date ASC
				LIMIT $start,$end
			 ";

				$all_list = parent::selectTable_f_mdl($sql1);

				if ((isset($all_count[0]['count'])) && (!empty($all_count[0]['count']))) {
					$record_count = $all_count[0]['count'];
					$page = $record_count / $rows;
					$page = ceil($page);
				}
				$sr_start = 0;
				if ($record_count >= 1) {
					$sr_start = (($current_page - 1) * $rows) + 1;
				}
				$sr_end = ($current_page) * $rows;
				if ($record_count <= $sr_end) {
					$sr_end = $record_count;
				}

				if (isset($_POST['pagination_export']) && $_POST['pagination_export'] == 'Y') {
				} else {
					$html = '';
					$html .= '<div class="row">';
					$html .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
					$html .= '<div class="table-responsive dropdown-active">'; // Task 54 19/10/2021 Add new class dropdown-active
					$html .= '<table class="table table-bordered table-hover">';

					$html .= '<thead>';
					$html .= '<tr>';
					$html .= '<th>#</th>';
					$html .= '<th>Store Name</th>';
					$html .= '<th>Launched Date</th>';
					$html .= '<th>Collection Created</th>';
					$html .= '<th>Products Synced</th>';
					$html .= '<th>Variants Synced</th>';
					$html .= '<th>Syncing Status</th>';
					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					if (!empty($all_list)) {
						$sr = $sr_start;
						foreach ($all_list as $single) {
							$checked = '';
							if ($single['is_manual_store_sync'] == 1) {
								$checked = 'checked';
							}
							$totalprosql = "SELECT count(id) as total_product FROM store_owner_product_master WHERE store_master_id='".$single['id']."' AND status='1' AND is_soft_deleted='0' ";
							$total_Prolist = parent::selectTable_f_mdl($totalprosql);
							$total_product='0';
							if(!empty($total_Prolist)){
								$total_product=$total_Prolist[0]['total_product'];
							}
							$syncsql = "SELECT sopm.id,sopm.shop_product_id FROM store_owner_product_master as sopm INNER JOIN store_owner_product_variant_master as sopvm ON sopvm.store_owner_product_master_id=sopm.id WHERE sopm.store_master_id='".$single['id']."' AND (sopvm.shop_variant_id='' OR sopvm.shop_variant_id !='0') AND (sopm.shop_product_id IS NOT NULL) AND sopm.is_soft_deleted='0' group by  sopm.id ";
							$sync_Prolist = parent::selectTable_f_mdl($syncsql);
							$total_sync_product = count($sync_Prolist);

							$totalversql = "SELECT count(sopvm.id) as total_product_variant FROM store_owner_product_variant_master as sopvm INNER JOIN store_owner_product_master as sopm ON sopvm.store_owner_product_master_id=sopm.id WHERE sopm.store_master_id='".$single['id']."' AND sopvm.status='1' AND sopm.is_soft_deleted='0' ";
							$totalverList = parent::selectTable_f_mdl($totalversql);
							$total_product_variant='0';
							if(!empty($totalverList)){
								$total_product_variant=$totalverList[0]['total_product_variant'];
							}

							$syncversql = "SELECT sopm.id,sopm.shop_product_id FROM store_owner_product_master as sopm INNER JOIN store_owner_product_variant_master as sopvm ON sopvm.store_owner_product_master_id=sopm.id WHERE sopm.store_master_id='".$single['id']."' AND (sopvm.shop_variant_id !='') AND (sopvm.shop_product_id !='') AND sopm.is_soft_deleted='0' ";
							$sync_Verlist = parent::selectTable_f_mdl($syncversql);
							$total_sync_variants = count($sync_Verlist);

							$date          = new DateTime($single["approved_date"]);
							$approved_date = $date->format("m/d/Y h:i A");
							
							$collectionCreated = ($single["is_collection_created"] == 1) ? ((!empty($single["shop_collection_handle"])) ? '<a style="margin-right: 10px;" class="btn btn-success" target="_blank" role="menuitem" href="https://'.common::PARENT_STORE_NAME.'/collections/'.$single["shop_collection_handle"].'" >View</a>' :'Yes' ) : "No";
							$productsSynced = ($single["is_products_synced"] == 1) ? "Yes" : "No";
							$html .= '<tr>';
							$html .= '<td>' . $sr . '</td>';
							$html .= '<td><a href="sa-store-view.php?stkn=&id='.$single['id'].'" target="_blank">'.$single["store_name"].'</a></td>';
							$html .= '<td>' .$approved_date. '</td>';
							$html .= '<td>' . $collectionCreated . '</td>';
							$html .= '<td>' . $productsSynced . ' ('.$total_sync_product.'/'.$total_product.')</td>';
							$html .= '<td>' .'('.$total_sync_variants.'/'.$total_product_variant.')</td>';
							$html .= '<td>
									<div class="form-group toggal-email-temp">
										<label class="pt-3">No</label>
										<label class="inex-switch">
											<input type="checkbox" id="is_manual_store_sync" name="is_manual_store_sync" value="'.$single["id"].'" '.$checked.'>
											<span class="inex-slider round"></span>
										</label>
										<label class="pt-3">Yes</label>
									</div>
							</td>';

							$html .= '</tr>';
							$sr++;
						}
					} else {
						$html .= '<tr>';
						$html .= '<td colspan="14" align="center">No Record Found</td>';
						$html .= '</tr>';
					}

					$html .= '</tbody>';
					$html .= '</table></br></br></br></br></br>';
					$html .= '</div>';
					$html .= '</div>';
					$html .= '</div>';

					$res['DATA'] = $html;
					$res['page_count'] = $page;
					$res['record_count'] = $record_count;
					$res['sr_start'] = $sr_start;
					$res['sr_end'] = $sr_end;
					echo json_encode($res, 1);
					exit;
				}
			}
		}
	}

	function exportStores()
	{
		global $s3Obj;
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "export_stores") {
				$storeMasterId = parent::getVal("store_master_ids");
				$resultArray = array();
				$export_file = time() . '-export.csv';
				$export_file_path = 'image_uploads/_export/' . $export_file;
				$export_file_url = common::IMAGE_UPLOAD_URL . '_export/' . $export_file;
				$file_for_export_data = fopen($export_file_path, "w");
				$BOM = "\xEF\xBB\xBF";
				header('Content-Encoding: UTF-8');
				header('Content-type: text/plain; charset=utf-8');
				header('Content-type: text/csv; charset=UTF-8');
				header('Content-Type: text/html; charset=utf-8');
				header('Content-Transfer-Encoding: binary');
				header('Content-type: application/csv');
				header('Content-type: application/excel');
				mb_convert_encoding($export_file_url, 'UTF-16LE', 'UTF-8');
				header("Content-type: application/vnd.ms-excel");
				header('Content-Disposition: attachment; filename=' . $export_file_url);
				fputcsv(
					$file_for_export_data,
					['Store Name', 'Program Type', 'Store Status', 'Organization Name', 'First Day', 'Last Day', 'In Hands Date', 'Print Date', 'Store Close Date ', 'PO #', 'Minimums Met', 'Fundraising', '# Of Orders', '# Of Items Sold', 'Total Profit', 'Profit Margin', 'Notes']
				);
				foreach ($storeMasterId as $values) {

					$store_sql = "SELECT sm.id, sm.shop_collection_handle,sm.is_store_batched,sm.front_side_ink_colors,sm.status, sm.back_side_ink_colors,sm.store_name,sm.store_open_date,sm.is_fundraising,sm.store_fulfillment_type,sm.store_close_date,sm.store_in_hands_date,sm.verification_status,sm.notes,sstm.sale_type,sodm.first_name,sodm.last_name,sodm.email,sodm.phone,sodm.organization_name,sm.store_owner_details_master_id,sm.store_sale_type_master_id,sm.total_profit,sm.updated_on,sm.print_date,sm.production_date,sm.po_details,sm.profit_margin,
					(SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = sm.id) as totalItem_sold,
					(SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0) as total_order
				    FROM store_master sm INNER JOIN store_owner_details_master sodm ON sm.store_owner_details_master_id = sodm.id INNER JOIN store_sale_type_master sstm ON sm.store_sale_type_master_id = sstm.id WHERE sm.id=" . $values . "
				   	";
					$store_data = parent::selectTable_f_mdl($store_sql);
					// echo $store_sql;die;
					if ($store_data[0]['status'] == '1') {
						if ($store_data[0]["store_sale_type_master_id"] == 1) {
							$store_status = ($store_data[0]["is_store_batched"] == '1' ? 'Ready to Batch' : ($store_data[0]["verification_status"] == '1' ? 'Store is Live' : 'Pending'));
						} else {
							$store_status = (($store_data[0]["verification_status"] == '1' ? 'Store is Live' : 'Pending'));
						}
					} else {
						$store_status = 'Closed';
					}
					$store_open_date = '';
					if (!empty($store_data[0]['store_open_date'])) {
						$store_open_date = date('m/d/Y', $store_data[0]["store_open_date"]);
					}
					$store_close_date = '';
					if (!empty($store_data[0]['store_close_date'])) {
						$store_close_date = date('m/d/Y', $store_data[0]["store_close_date"]);
					}
					$store_in_hands_date = '';
					if (!empty($store_data[0]['store_in_hands_date'])) {
						$store_in_hands_date = date('m/d/Y', $store_data[0]["store_in_hands_date"]);
					}
					$print_date = '';
					if (!empty($store_data[0]['print_date'])) {
						$print_date = date('m/d/Y', $store_data[0]['print_date']);
					}
					$production_date = '';
					if (!empty($store_data[0]['production_date'])) {
						$production_date = date('m/d/Y', $store_data[0]['production_date']);
					}
					$dataMet = "-";
					if ($store_data[0]['store_sale_type_master_id'] == "1") {
						$dataMet = self::getStoresMeetMinimum($store_data[0]['id']);
					}
					$totalProfit  = number_format((float)$store_data[0]['total_profit'], 2);

					fputcsv(
						$file_for_export_data,
						[
							trim($store_data[0]['store_name']),
							trim($store_data[0]['sale_type']),
							trim($store_status),
							trim($store_data[0]['organization_name']),
							trim($store_open_date),
							trim($store_close_date),
							trim($store_in_hands_date),
							trim($print_date),
							trim($production_date),
							trim($store_data[0]['po_details']),
							trim($dataMet),
							trim($store_data[0]['is_fundraising']),
							trim($store_data[0]['total_order']),
							trim($store_data[0]['totalItem_sold']),
							trim('$' . $totalProfit),
							trim($store_data[0]['profit_margin'] . '%'),
							trim($store_data[0]['notes'])
						]
					);
				}

				fputcsv(
					$file_for_export_data,
					['']
				);
				$status = true;
				if ($status == true) {
					fclose($file_for_export_data);
					$documentURL = $export_file_url;
					$resultArray['SUCCESS'] = 'TRUE';
					$resultArray['MESSAGE'] = '';
					$resultArray['EXPORT_URL'] = $documentURL; // Task 59
				} else {
					$resultArray['SUCCESS'] = 'FALSE';
					$resultArray['MESSAGE'] = 'Records are not found.';
				}
				common::sendJson($resultArray);
			}
		}
	}

	public function dateFilter()
	{
		if (parent::isPOST()) {

			if (!empty(parent::getVal("method")) && parent::getVal("method") == "dateFilter") {
				$startDate = parent::getVal("startDate");
				$endDate = parent::getVal("endDate");




				$resultArray = array();
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Store data delete successfully.";
				common::sendJson($resultArray);
			}
			die;
		}
	}

	public function exportOrdersToPrintavo()
	{
		global $s3Obj;
		if (parent::isPOST()) {
			$res = [];
			$filterData =[];
			$filterData1 =[];
			$filterData2 =[];
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "exportOrdersToPrintavo") {
				$store_master_id = parent::getVal("store_master_id");
				$ownerSql = "SELECT *,sodm.email,sodm.id as store_owner_details_master_id FROM store_master as sm LEFT JOIN store_owner_details_master as sodm on sodm.id = sm.store_owner_details_master_id where sm.id = " . $store_master_id . "  ";
				$ownerDetails = parent::selectTable_f_mdl($ownerSql);
				$first_name = '';
				$last_name = '';
				$email = '';
				$phone = '';
				$organization_name = '';
				$storeOwnerDetailsMasterId = '';
				$store_name = '';
				$storeId = $store_master_id;
				if (!empty($ownerDetails)) {
					$details = $ownerDetails[0];
					$first_name = $details['first_name'];
					$last_name = $details['last_name'];
					$email = $details['email'];
					$phone = $details['phone'];
					$organization_name = $details['organization_name'];
					$storeOwnerDetailsMasterId = $details['store_owner_details_master_id'];
					$store_name = $details['store_name'];
				}

				$userId = common::PRINTAVO_USER_ID;
				$orderstatus_id = common::PRINTAVO_DEFAULTINVOICE_STATUS;
				$query = $email;
				$customerData = parent::checkExistPrintavoCustomer($query);
				if(isset($customerData['error'])){
					$res['STATUS']  = false;
					$res['MESSAGE'] = $customerData['error'];
				}else{
					if (count($customerData['data']) > 0) {
						$customerId = null; // Default value if no match is found
						foreach ($customerData['data'] as $customer) {
							if ($customer['email'] == $email) {
								$customerId = $customer['id'];
								break; // Exit the loop once a match is found
							}
						}
						if($customerId == null) {
							$resData = parent::createPrintavoCustomer($storeOwnerDetailsMasterId, $first_name, $last_name, $email, $phone, $organization_name, $userId);
							$customerId = $resData['id'];
						}
					} else {
						$resData = parent::createPrintavoCustomer($storeOwnerDetailsMasterId, $first_name, $last_name, $email, $phone, $organization_name, $userId);
						$customerId = $resData['id'];
					}

					$manualorderMasterSql = "SELECT soim.store_master_id, soim.store_owner_product_variant_master_id,soim.title,sum(soim.quantity) AS quantity,soim.price,shop_order_number,sopvm.sku,sopvm.size,sopvm.color,sopvm.image,soim.title,spcm.product_color_name FROM store_order_items_master as soim 
					INNER JOIN store_owner_product_variant_master as sopvm ON soim.store_owner_product_variant_master_id = sopvm.id 
					INNER JOIN store_orders_master as som on som.id = soim.store_orders_master_id
					INNER JOIN `store_master` as sm on sm.id = som.store_master_id
					INNER JOIN `store_product_colors_master` as spcm on spcm.product_color = sopvm.color
					WHERE order_type='2' AND title IN (SELECT title FROM store_order_items_master where store_master_id= ".$store_master_id." group by store_owner_product_variant_master_id) AND sm.status='0' AND sm.is_exported_to_printavo='0' AND som.is_order_cancel = 0 AND soim.is_deleted = 0 AND som.manual_order_number!='' AND soim.store_master_id = ".$store_master_id." group by soim.store_owner_product_variant_master_id";
					$ManualorderMasterData = parent::selectTable_f_mdl($manualorderMasterSql);

					$quickorderMasterSql = "SELECT soim.store_master_id, soim.store_owner_product_variant_master_id,soim.title,sum(soim.quantity) AS quantity,soim.price,shop_order_number,sopvm.sku,sopvm.size,sopvm.color,sopvm.image,soim.title,spcm.product_color_name FROM store_order_items_master as soim 
					INNER JOIN store_owner_product_variant_master as sopvm ON soim.store_owner_product_variant_master_id = sopvm.id 
					INNER JOIN store_orders_master as som on som.id = soim.store_orders_master_id
					INNER JOIN `store_master` as sm on sm.id = som.store_master_id
					INNER JOIN `store_product_colors_master` as spcm on spcm.product_color = sopvm.color
					WHERE order_type='3' AND title IN (SELECT title FROM store_order_items_master where store_master_id= ".$store_master_id." group by store_owner_product_variant_master_id) AND sm.status='0' AND sm.is_exported_to_printavo='0' AND som.is_order_cancel = 0 AND soim.is_deleted = 0 AND som.quickbuy_order_number!='' AND soim.store_master_id = ".$store_master_id." group by soim.store_owner_product_variant_master_id";
					$QuickBuyorderMasterData = parent::selectTable_f_mdl($quickorderMasterSql);

					$orderMasterSql = "SELECT soim.store_master_id, soim.store_owner_product_variant_master_id,soim.title,sum(soim.quantity) AS quantity,soim.price,shop_order_number,sopvm.sku,sopvm.size,sopvm.color,sopvm.image,soim.title,spcm.product_color_name FROM store_order_items_master as soim 
					INNER JOIN store_owner_product_variant_master as sopvm ON soim.store_owner_product_variant_master_id = sopvm.id 
					INNER JOIN store_orders_master as som on som.id = soim.store_orders_master_id
					INNER JOIN `store_master` as sm on sm.id = som.store_master_id
					INNER JOIN `store_product_colors_master` as spcm on spcm.product_color = sopvm.color
					WHERE title IN (SELECT title FROM store_order_items_master where som.order_tags NOT LIKE '%Return_Prime%' AND store_master_id= " . $store_master_id . " group by store_owner_product_variant_master_id) AND sm.status='0' AND sm.is_exported_to_printavo='0' AND som.is_order_cancel = 0 AND soim.is_deleted = 0 AND som.shop_order_number!='' AND soim.store_master_id = " . $store_master_id . " group by soim.store_owner_product_variant_master_id";
					$orderMasterData = parent::selectTable_f_mdl($orderMasterSql);

					//=================Acctual Order=========
					if(!empty($orderMasterData) || !empty($ManualorderMasterData) || !empty($QuickBuyorderMasterData)){
						if (!empty($orderMasterData)) {
						
							$orderArray = [];
							$items = [];
							foreach ($orderMasterData as $key => $objData) {
								$tmpProData = array();
								$color1 = (!empty($objData['product_color_name'])) ? $objData['product_color_name'] : '';
								foreach ($orderMasterData as $keys => $objDatas) {
									$color2 = (!empty($objDatas['product_color_name'])) ? $objDatas['product_color_name'] : '';
									if ($objData['title'] == $objDatas['title'] AND $objData['sku'] == $objDatas['sku']) {
										$tmpProData[$color2][] = $objDatas;
									}
								}
								$size['size_yxs'] = "";
								$size['size_ys'] = "";
								$size['size_ym'] = "";
								$size['size_yl'] = "";
								$size['size_yxl'] = "";
								$size['size_xs'] = "";
								$size['size_s'] = "";
								$size['size_m'] = "";
								$size['size_l'] = "";
								$size['size_xl'] = "";
								$size['size_2xl'] = "";
								$size['size_3xl'] = "";
								$size['size_4xl'] = "";
								$size['size_5xl'] = "";
								$size['size_xs'] = "";
								$size['size_s'] = "";
								$size['size_m'] = "";
								$size['size_l'] = "";
								$size['size_xl'] = "";
								$size['size_2xl'] = "";
								$size['size_3xl'] = "";
								$size['size_4xl'] = "";
								$size['size_5xl'] = "";
								$size['size_6xl'] = "";
								$size['size_other'] = "";
								$productData[$objData['title']] = $tmpProData;
								foreach ($productData[$objData['title']][$color1] as $value) {
									$mocupImage = parent::commonMockups($value['store_owner_product_variant_master_id']);
									$filename = (!empty($value['image'])) ? $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $value['image']) : '';
									if(!empty($mocupImage)){
										$filename = (!empty($value['image'])) ? $s3Obj->getAwsUrl(common::LOGO_MOCKUP_UPLOAD_S3_PATH .$store_master_id.'/'. $mocupImage[0]['image']) : '';
									}
									$ext = pathinfo($filename, PATHINFO_EXTENSION);
									$variantSize = (isset($value['size'])) ? $value['size'] : '';
									switch (true) {
										case strpos($variantSize, 'Youth XS') !== false:
											$size['size_yxs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth S') !== false:
											$size['size_ys'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth M') !== false:
											$size['size_ym'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth L') !== false:
											$size['size_yl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth XL') !== false:
											$size['size_yxl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XS') !== false:
											$size['size_xs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult S') !== false:
											$size['size_s'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult M') !== false:
											$size['size_m'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult L') !== false:
											$size['size_l'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XL') !== false:
											$size['size_xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 2XL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 3XL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXXL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 4XL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXXXL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 5XL') !== false:
											$size['size_5xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 6XL') !== false:
											$size['size_6xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;	
										case strpos($variantSize, '2XL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '3XL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXXL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '4XL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXXXL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '5XL') !== false:
											$size['size_5xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '6XL') !== false:
											$size['size_6xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XS') !== false:
											$size['size_xs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XL') !== false:
											$size['size_xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'S') !== false:
											$size['size_s'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'M') !== false:
											$size['size_m'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'L') !== false:
											$size['size_l'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										
										default:
											$size['size_other'] = (isset($value['quantity'])) ? $value['quantity'] : '';	
									}
									$items[$key] = array_merge([
										"style_description" => (!empty($value['title'])) ? $value['title'] : '',
										"style_number" => (!empty($value['sku'])) ? $value['sku'] : '',
										"unit_cost" => '0.0',
										"color" => (!empty($value['product_color_name'])) ? $value['product_color_name'] : '',
										"category_id" => common::PRINTAVO_INVOICE_ITEM_CATEGORY_ID,
										"images_attributes" => [
											[
												"file_url" => $filename,
												"mime_type" => (!empty($ext)) ? 'image/' . $ext : ''
											]
										]
									], $size);
								}
							}
							$filterData = self::CheckCustomArrayUnique($items);
						}
						//=================Acctual Order End=========
						//=================Export Manual Order=========
						if (!empty($ManualorderMasterData)) {
							$items1 = [];
							foreach ($ManualorderMasterData as $key => $objData) {
								$tmpProData1 = array();
								$color1 = (!empty($objData['product_color_name'])) ? $objData['product_color_name'] : '';
								foreach ($ManualorderMasterData as $keys => $objDatas) {
									$color2 = (!empty($objDatas['product_color_name'])) ? $objDatas['product_color_name'] : '';
									if ($objData['title'] == $objDatas['title'] AND $objData['sku'] == $objDatas['sku']) {
										$tmpProData1[$color2][] = $objDatas;
									}
								}
								$size['size_yxs'] = "";
								$size['size_ys'] = "";
								$size['size_ym'] = "";
								$size['size_yl'] = "";
								$size['size_yxl'] = "";
								$size['size_xs'] = "";
								$size['size_s'] = "";
								$size['size_m'] = "";
								$size['size_l'] = "";
								$size['size_xl'] = "";
								$size['size_2xl'] = "";
								$size['size_3xl'] = "";
								$size['size_4xl'] = "";
								$size['size_5xl'] = "";
								$size['size_xs'] = "";
								$size['size_s'] = "";
								$size['size_m'] = "";
								$size['size_l'] = "";
								$size['size_xl'] = "";
								$size['size_2xl'] = "";
								$size['size_3xl'] = "";
								$size['size_4xl'] = "";
								$size['size_5xl'] = "";
								$size['size_6xl'] = "";
								$size['size_other'] = "";
								$productData[$objData['title']] = $tmpProData1;
								foreach ($productData[$objData['title']][$color1] as $value) {
									$mocupImage = parent::commonMockups($value['store_owner_product_variant_master_id']);
									$filename = (!empty($value['image'])) ? $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $value['image']) : '';
									if(!empty($mocupImage)){
										$filename = (!empty($value['image'])) ? $s3Obj->getAwsUrl(common::LOGO_MOCKUP_UPLOAD_S3_PATH .$store_master_id.'/'. $mocupImage[0]['image']) : '';
									}
									$ext = pathinfo($filename, PATHINFO_EXTENSION);
									$variantSize = (isset($value['size'])) ? $value['size'] : '';
									switch (true) {
										case strpos($variantSize, 'Youth XS') !== false:
											$size['size_yxs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth S') !== false:
											$size['size_ys'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth M') !== false:
											$size['size_ym'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth L') !== false:
											$size['size_yl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth XL') !== false:
											$size['size_yxl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XS') !== false:
											$size['size_xs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult S') !== false:
											$size['size_s'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult M') !== false:
											$size['size_m'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult L') !== false:
											$size['size_l'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XL') !== false:
											$size['size_xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 2XL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 3XL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXXL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 4XL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXXXL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 5XL') !== false:
											$size['size_5xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 6XL') !== false:
											$size['size_6xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;	
										case strpos($variantSize, '2XL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '3XL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXXL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '4XL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXXXL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '5XL') !== false:
											$size['size_5xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '6XL') !== false:
											$size['size_6xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XS') !== false:
											$size['size_xs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XL') !== false:
											$size['size_xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'S') !== false:
											$size['size_s'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'M') !== false:
											$size['size_m'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'L') !== false:
											$size['size_l'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										
										default:
											$size['size_other'] = (isset($value['quantity'])) ? $value['quantity'] : '';	
									}
									$items1[$key] = array_merge([
										"style_description" => (!empty($value['title'])) ? $value['title'] : '',
										"style_number" => (!empty($value['sku'])) ? $value['sku'] : '',
										"unit_cost" => (!empty($value['price'])) ? $value['price'] : '',
										"color" => (!empty($value['product_color_name'])) ? $value['product_color_name'] : '',
										"category_id" => common::PRINTAVO_INVOICE_ITEM_CATEGORY_ID,
										"images_attributes" => [
											[
												"file_url" => $filename,
												"mime_type" => (!empty($ext)) ? 'image/' . $ext : ''
											]
										]
									], $size);
								}
							}
							$filterData1 = self::CheckCustomArrayUnique($items1);
						}
						//=================Export Manual Order End ======
						//=================Export Quick Buy Order =========
						if (!empty($QuickBuyorderMasterData)) {
							$items2 = [];
							foreach ($QuickBuyorderMasterData as $key => $objData) {
								$tmpProData2 = array();
								$color1 = (!empty($objData['product_color_name'])) ? $objData['product_color_name'] : '';
								foreach ($QuickBuyorderMasterData as $keys => $objDatas) {
									$color2 = (!empty($objDatas['product_color_name'])) ? $objDatas['product_color_name'] : '';
									if ($objData['title'] == $objDatas['title'] AND $objData['sku'] == $objDatas['sku']) {
										$tmpProData2[$color2][] = $objDatas;
									}
								}
								$size['size_yxs'] = "";
								$size['size_ys'] = "";
								$size['size_ym'] = "";
								$size['size_yl'] = "";
								$size['size_yxl'] = "";
								$size['size_xs'] = "";
								$size['size_s'] = "";
								$size['size_m'] = "";
								$size['size_l'] = "";
								$size['size_xl'] = "";
								$size['size_2xl'] = "";
								$size['size_3xl'] = "";
								$size['size_4xl'] = "";
								$size['size_5xl'] = "";
								$size['size_xs'] = "";
								$size['size_s'] = "";
								$size['size_m'] = "";
								$size['size_l'] = "";
								$size['size_xl'] = "";
								$size['size_2xl'] = "";
								$size['size_3xl'] = "";
								$size['size_4xl'] = "";
								$size['size_5xl'] = "";
								$size['size_6xl'] = "";
								$size['size_other'] = "";
								$productData[$objData['title']] = $tmpProData2;
								foreach ($productData[$objData['title']][$color1] as $value) {
									$mocupImage = parent::commonMockups($value['store_owner_product_variant_master_id']);
									$filename = (!empty($value['image'])) ? $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $value['image']) : '';
									if(!empty($mocupImage)){
										$filename = (!empty($value['image'])) ? $s3Obj->getAwsUrl(common::LOGO_MOCKUP_UPLOAD_S3_PATH .$store_master_id.'/'. $mocupImage[0]['image']) : '';
									}
									$ext = pathinfo($filename, PATHINFO_EXTENSION);
									$variantSize = (isset($value['size'])) ? $value['size'] : '';
									switch (true) {
										case strpos($variantSize, 'Youth XS') !== false:
											$size['size_yxs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth S') !== false:
											$size['size_ys'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth M') !== false:
											$size['size_ym'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth L') !== false:
											$size['size_yl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth XL') !== false:
											$size['size_yxl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XS') !== false:
											$size['size_xs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult S') !== false:
											$size['size_s'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult M') !== false:
											$size['size_m'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult L') !== false:
											$size['size_l'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XL') !== false:
											$size['size_xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 2XL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 3XL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXXL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 4XL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXXXL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 5XL') !== false:
											$size['size_5xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 6XL') !== false:
											$size['size_6xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;	
										case strpos($variantSize, '2XL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '3XL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXXL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '4XL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXXXL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '5XL') !== false:
											$size['size_5xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '6XL') !== false:
											$size['size_6xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XS') !== false:
											$size['size_xs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XL') !== false:
											$size['size_xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'S') !== false:
											$size['size_s'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'M') !== false:
											$size['size_m'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'L') !== false:
											$size['size_l'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										
										default:
											$size['size_other'] = (isset($value['quantity'])) ? $value['quantity'] : '';	
									}
									$items2[$key] = array_merge([
										"style_description" => (!empty($value['title'])) ? $value['title'] : '',
										"style_number" => (!empty($value['sku'])) ? $value['sku'] : '',
										"unit_cost" => (!empty($value['price'])) ? $value['price'] : '',
										"color" => (!empty($value['product_color_name'])) ? $value['product_color_name'] : '',
										"category_id" => common::PRINTAVO_INVOICE_ITEM_CATEGORY_ID,
										"images_attributes" => [
											[
												"file_url" => $filename,
												"mime_type" => (!empty($ext)) ? 'image/' . $ext : ''
											]
										]
									], $size);
								}
							}
							$filterData2 = self::CheckCustomArrayUnique($items2);
						}
						//=================Export Quick Buy Order End=========
						$orderno_new ='';
						$orderSql = "SELECT GROUP_CONCAT(DISTINCT om.shop_order_id) as orderIds,GROUP_CONCAT(DISTINCT '#',shop_order_number) AS orderNumbers FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 AND om.shop_order_number!='' AND oim.store_master_id = " . $store_master_id . " AND om.order_tags NOT LIKE '%Return_Prime%' ";
						$orderData = parent::selectTable_f_mdl($orderSql);
						$orderNumbers = '';
						$orderIds = '';
						if (!empty($orderData[0]['orderNumbers'])) {
							$orderNumbers 	= $orderData[0]['orderNumbers'];
							$orderIds 		= $orderData[0]['orderIds'];
							$orderno_new 	= $orderNumbers;
						}
						
						$morderSql = "SELECT GROUP_CONCAT(DISTINCT om.manual_order_number) as orderIds,GROUP_CONCAT(DISTINCT '#',manual_order_number) AS orderNumbers FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 AND om.manual_order_number!='' AND oim.store_master_id = ".$store_master_id." ";
						$manualorderData = parent::selectTable_f_mdl($morderSql);
						$orderNumbers = '';
						$orderIds = '';
						if (!empty($manualorderData[0]['orderNumbers'])) {
							$ManualorderNumbers = $manualorderData[0]['orderNumbers'];
							$orderIds 			= $manualorderData[0]['orderIds'];
							$orderno_new		= $orderno_new.','.$ManualorderNumbers;
						}
						
						$QuickorderSql = "SELECT GROUP_CONCAT(DISTINCT om.quickbuy_order_number) as orderIds,GROUP_CONCAT(DISTINCT '#',quickbuy_order_number) AS orderNumbers FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 AND om.quickbuy_order_number!='' AND oim.store_master_id = ".$store_master_id." ";
						$QuickorderData = parent::selectTable_f_mdl($QuickorderSql);
						$orderNumbers = '';
						$orderIds = '';
						if (!empty($QuickorderData[0]['orderNumbers'])) {
							$QuickbuyorderNumbers 	= $QuickorderData[0]['orderNumbers'];
							$orderIds 				= $QuickorderData[0]['orderIds'];
							$orderno_new			= $orderno_new.','.$QuickbuyorderNumbers;
						}
						
						$filterDatanew=(array_merge($filterData,$filterData1,$filterData2));

						$printavoData = [
							"user_id"                     => $userId,
							"customer_id"                 => $customerId,
							"orderstatus_id"              => $orderstatus_id,
							"formatted_customer_due_date" => date("m/d/y"),
							"formatted_due_date"          => date("m/d/y"),
							"production_notes"            => $orderno_new,
							"order_nickname"              => $store_name,
							"lineitems_attributes"        => $filterDatanew
						];

						$jsonData = json_encode($printavoData);
						$response = self::createPrintavoOrder($jsonData);

						if (isset($response['error']) && !empty($response['error'])) {
							$res['STATUS'] = false;
							$res['MESSAGE'] = $response['error'];
						} else {
							$envoiceNumber = $response['visual_id'];
							$envoiceId = $response['id'];
							$updateStoreData = [
								'is_exported_to_printavo' => 1,
								'printavo_invoice_number'=>$envoiceNumber,
								'printavo_invoice_id'=>$envoiceId
							];
							parent::updateTable_f_mdl('store_master', $updateStoreData, 'id="' . $store_master_id . '"');
							$orderIdArray = explode(',', $orderIds);
							$tagData = self::updateOrderTags($orderIdArray, $storeId, $envoiceNumber);
							$res['STATUS'] = true;
							$res['MESSAGE'] = "Order created successfully on printavo";
						}
					}else{

						$res['STATUS'] = false;
						$res['MESSAGE'] = "Order not found";
					}
				}
				echo json_encode($res);die();
			}	
		}
	}

	public function exportOrdersToPrintavoGroupWise()
	{
		global $s3Obj;
		if (parent::isPOST()) {
			$res = [];
			$filterData =[];
			$filterData1 =[];
			$filterData2 =[];
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "exportOrdersToPrintavoByGroup") {
				$store_master_id = parent::getVal("store_master_id");
				$group_nameData = parent::getVal("group_name");
				$ownerSql = "SELECT *,sodm.email,sodm.id as store_owner_details_master_id FROM store_master as sm LEFT JOIN store_owner_details_master as sodm on sodm.id = sm.store_owner_details_master_id where sm.id = " . $store_master_id . "  ";
				$ownerDetails = parent::selectTable_f_mdl($ownerSql);
				$first_name = '';
				$last_name = '';
				$email = '';
				$phone = '';
				$organization_name = '';
				$storeOwnerDetailsMasterId = '';
				$store_name = '';
				$storeId = $store_master_id;
				if (!empty($ownerDetails)) {
					$details = $ownerDetails[0];
					$first_name = $details['first_name'];
					$last_name = $details['last_name'];
					$email = $details['email'];
					$phone = $details['phone'];
					$organization_name = $details['organization_name'];
					$storeOwnerDetailsMasterId = $details['store_owner_details_master_id'];
					$store_name = $details['store_name'];
				}

				$userId = common::PRINTAVO_USER_ID;
				$orderstatus_id = common::PRINTAVO_DEFAULTINVOICE_STATUS;
				$query = $email;
				$customerData = parent::checkExistPrintavoCustomer($query);
				if(isset($customerData['error'])){
					$res['STATUS']  = false;
					$res['MESSAGE'] = $customerData['error'];
				}else{
					if (count($customerData['data']) > 0) {
						$customerId = null; // Default value if no match is found
						foreach ($customerData['data'] as $customer) {
							if ($customer['email'] == $email) {
								$customerId = $customer['id'];
								break; // Exit the loop once a match is found
							}
						}
						if($customerId == null) {
							$resData = parent::createPrintavoCustomer($storeOwnerDetailsMasterId, $first_name, $last_name, $email, $phone, $organization_name, $userId);
							$customerId = $resData['id'];
						}
					} else {
						$resData = parent::createPrintavoCustomer($storeOwnerDetailsMasterId, $first_name, $last_name, $email, $phone, $organization_name, $userId);
						$customerId = $resData['id'];
					}

					$manualorderMasterSql = "SELECT soim.store_master_id, soim.store_owner_product_variant_master_id,soim.title,sum(soim.quantity) AS quantity,soim.price,shop_order_number,sopvm.sku,sopvm.size,sopvm.color,sopvm.image,soim.title,spcm.product_color_name FROM store_order_items_master as soim 
					INNER JOIN store_owner_product_variant_master as sopvm ON soim.store_owner_product_variant_master_id = sopvm.id 
					INNER JOIN store_orders_master as som on som.id = soim.store_orders_master_id
					INNER JOIN `store_master` as sm on sm.id = som.store_master_id
					INNER JOIN `store_owner_product_master` AS sopm ON sopm.id = sopvm.store_owner_product_master_id
					INNER JOIN `store_product_colors_master` as spcm on spcm.product_color = sopvm.color
					WHERE order_type='2' AND title IN (SELECT title FROM store_order_items_master where store_master_id= ".$store_master_id." group by store_owner_product_variant_master_id) AND sm.status='0' AND sm.is_exported_to_printavo='0' AND som.is_order_cancel = 0 AND soim.is_deleted = 0 AND som.manual_order_number!='' AND soim.store_master_id = ".$store_master_id." AND sopm.group_name='".$group_nameData."' group by soim.store_owner_product_variant_master_id";
					$ManualorderMasterData = parent::selectTable_f_mdl($manualorderMasterSql);

					$quickorderMasterSql = "SELECT soim.store_master_id, soim.store_owner_product_variant_master_id,soim.title,sum(soim.quantity) AS quantity,soim.price,shop_order_number,sopvm.sku,sopvm.size,sopvm.color,sopvm.image,soim.title,spcm.product_color_name FROM store_order_items_master as soim 
					INNER JOIN store_owner_product_variant_master as sopvm ON soim.store_owner_product_variant_master_id = sopvm.id 
					INNER JOIN store_orders_master as som on som.id = soim.store_orders_master_id
					INNER JOIN `store_master` as sm on sm.id = som.store_master_id
					INNER JOIN `store_owner_product_master` AS sopm ON sopm.id = sopvm.store_owner_product_master_id
					INNER JOIN `store_product_colors_master` as spcm on spcm.product_color = sopvm.color
					WHERE order_type='3' AND title IN (SELECT title FROM store_order_items_master where store_master_id= ".$store_master_id." group by store_owner_product_variant_master_id) AND sm.status='0' AND sm.is_exported_to_printavo='0' AND som.is_order_cancel = 0 AND soim.is_deleted = 0 AND som.quickbuy_order_number!='' AND soim.store_master_id = ".$store_master_id." AND sopm.group_name='".$group_nameData."' group by soim.store_owner_product_variant_master_id";
					$QuickBuyorderMasterData = parent::selectTable_f_mdl($quickorderMasterSql);

					$orderMasterSql = "SELECT sopm.group_name,sopvm.store_owner_product_master_id,soim.store_master_id, soim.store_owner_product_variant_master_id,soim.title,sum(soim.quantity) AS quantity,soim.price,shop_order_number,sopvm.sku,sopvm.size,sopvm.color,sopvm.image,soim.title,spcm.product_color_name FROM store_order_items_master as soim 
					INNER JOIN store_owner_product_variant_master as sopvm ON soim.store_owner_product_variant_master_id = sopvm.id 
					INNER JOIN store_orders_master as som on som.id = soim.store_orders_master_id
					INNER JOIN `store_master` as sm on sm.id = som.store_master_id
          			INNER JOIN `store_owner_product_master` as sopm on sopm.id = sopvm.store_owner_product_master_id
					INNER JOIN `store_product_colors_master` as spcm on spcm.product_color = sopvm.color
					WHERE title IN (SELECT title FROM store_order_items_master where som.order_tags NOT LIKE '%Return_Prime%' AND store_master_id=  " . $store_master_id . " group by store_owner_product_variant_master_id) AND sm.status='0' AND som.is_order_cancel = 0 AND soim.is_deleted = 0 AND som.shop_order_number!='' AND soim.store_master_id =".$store_master_id." AND sopm.group_name='".$group_nameData."' group by soim.store_owner_product_variant_master_id ";
					
					$orderMasterData = parent::selectTable_f_mdl($orderMasterSql);
					if(!empty($orderMasterData) || !empty($ManualorderMasterData) || !empty($QuickBuyorderMasterData)){
						if (!empty($orderMasterData)) {
							$orderArray = [];
							$items = [];
							foreach ($orderMasterData as $key => $objData) {
								$tmpProData = array();
								$color1 = (!empty($objData['product_color_name'])) ? $objData['product_color_name'] : '';
								foreach ($orderMasterData as $keys => $objDatas) {
									$color2 = (!empty($objDatas['product_color_name'])) ? $objDatas['product_color_name'] : '';
									if ($objData['title'] == $objDatas['title'] AND $objData['sku'] == $objDatas['sku']) {
										$tmpProData[$color2][] = $objDatas;
									}
								}

								$size['size_yxs'] = "";
								$size['size_ys'] = "";
								$size['size_ym'] = "";
								$size['size_yl'] = "";
								$size['size_yxl'] = "";
								$size['size_xs'] = "";
								$size['size_s'] = "";
								$size['size_m'] = "";
								$size['size_l'] = "";
								$size['size_xl'] = "";
								$size['size_2xl'] = "";
								$size['size_3xl'] = "";
								$size['size_4xl'] = "";
								$size['size_5xl'] = "";
								$size['size_xs'] = "";
								$size['size_s'] = "";
								$size['size_m'] = "";
								$size['size_l'] = "";
								$size['size_xl'] = "";
								$size['size_2xl'] = "";
								$size['size_3xl'] = "";
								$size['size_4xl'] = "";
								$size['size_5xl'] = "";
								$size['size_6xl'] = "";
								$size['size_other'] = "";
								
								$productData[$objData['title']] = $tmpProData;
								foreach ($productData[$objData['title']][$color1] as $value) {
									$mocupImage = parent::commonMockups($value['store_owner_product_variant_master_id']);
									$filename = (!empty($value['image'])) ? $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $value['image']) : '';
									if(!empty($mocupImage)){
										$filename = (!empty($value['image'])) ? $s3Obj->getAwsUrl(common::LOGO_MOCKUP_UPLOAD_S3_PATH .$store_master_id.'/'. $mocupImage[0]['image']) : '';
									}
									$ext = pathinfo($filename, PATHINFO_EXTENSION);
									$variantSize = (isset($value['size'])) ? $value['size'] : '';
									
									switch (true) {
										case strpos($variantSize, 'Youth XS') !== false:
											$size['size_yxs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth S') !== false:
											$size['size_ys'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth M') !== false:
											$size['size_ym'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth L') !== false:
											$size['size_yl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth XL') !== false:
											$size['size_yxl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XS') !== false:
											$size['size_xs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult S') !== false:
											$size['size_s'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult M') !== false:
											$size['size_m'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult L') !== false:
											$size['size_l'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XL') !== false:
											$size['size_xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 2XL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 3XL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXXL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 4XL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXXXL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 5XL') !== false:
											$size['size_5xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 6XL') !== false:
											$size['size_6xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '2XL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '3XL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXXL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '4XL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXXXL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '5XL') !== false:
											$size['size_5xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '6XL') !== false:
											$size['size_6xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XS') !== false:
											$size['size_xs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XL') !== false:
											$size['size_xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'S') !== false:
											$size['size_s'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'M') !== false:
											$size['size_m'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'L') !== false:
											$size['size_l'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										default:
											$size['size_other'] = (isset($value['quantity'])) ? $value['quantity'] : '';	
									}
									
									$items[$key] = array_merge([
										"style_description" => (!empty($value['title'])) ? $value['title'] : '',
										"style_number" => (!empty($value['sku'])) ? $value['sku'] : '',
										"unit_cost" => '0.0',
										"color" => (!empty($value['product_color_name'])) ? $value['product_color_name'] : '',
										"category_id" => common::PRINTAVO_INVOICE_ITEM_CATEGORY_ID,
										"images_attributes" => [
											[
												"file_url" => $filename,
												"mime_type" => (!empty($ext)) ? 'image/' . $ext : ''
											]
										]
									], $size);
								}
							}
							$filterData = self::CheckCustomArrayUnique($items);
						}

						if (!empty($ManualorderMasterData)) {
							$items1 = [];
							foreach ($ManualorderMasterData as $key => $objData) {
								$tmpProData1 = array();
								$color1 = (!empty($objData['product_color_name'])) ? $objData['product_color_name'] : '';
								foreach ($ManualorderMasterData as $keys => $objDatas) {
									$color2 = (!empty($objDatas['product_color_name'])) ? $objDatas['product_color_name'] : '';
									if ($objData['title'] == $objDatas['title'] AND $objData['sku'] == $objDatas['sku']) {
										$tmpProData1[$color2][] = $objDatas;
									}
								}

								$size['size_yxs'] = "";
								$size['size_ys'] = "";
								$size['size_ym'] = "";
								$size['size_yl'] = "";
								$size['size_yxl'] = "";
								$size['size_xs'] = "";
								$size['size_s'] = "";
								$size['size_m'] = "";
								$size['size_l'] = "";
								$size['size_xl'] = "";
								$size['size_2xl'] = "";
								$size['size_3xl'] = "";
								$size['size_4xl'] = "";
								$size['size_5xl'] = "";
								$size['size_xs'] = "";
								$size['size_s'] = "";
								$size['size_m'] = "";
								$size['size_l'] = "";
								$size['size_xl'] = "";
								$size['size_2xl'] = "";
								$size['size_3xl'] = "";
								$size['size_4xl'] = "";
								$size['size_5xl'] = "";
								$size['size_6xl'] = "";
								$size['size_other'] = "";
								
								$productData[$objData['title']] = $tmpProData1;
								foreach ($productData[$objData['title']][$color1] as $value) {
									$mocupImage = parent::commonMockups($value['store_owner_product_variant_master_id']);
									$filename = (!empty($value['image'])) ? $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $value['image']) : '';
									if(!empty($mocupImage)){
										$filename = (!empty($value['image'])) ? $s3Obj->getAwsUrl(common::LOGO_MOCKUP_UPLOAD_S3_PATH .$store_master_id.'/'. $mocupImage[0]['image']) : '';
									}
									$ext = pathinfo($filename, PATHINFO_EXTENSION);
									$variantSize = (isset($value['size'])) ? $value['size'] : '';
									
									switch (true) {
										case strpos($variantSize, 'Youth XS') !== false:
											$size['size_yxs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth S') !== false:
											$size['size_ys'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth M') !== false:
											$size['size_ym'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth L') !== false:
											$size['size_yl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth XL') !== false:
											$size['size_yxl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XS') !== false:
											$size['size_xs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult S') !== false:
											$size['size_s'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult M') !== false:
											$size['size_m'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult L') !== false:
											$size['size_l'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XL') !== false:
											$size['size_xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 2XL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 3XL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXXL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 4XL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXXXL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 5XL') !== false:
											$size['size_5xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 6XL') !== false:
											$size['size_6xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '2XL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '3XL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXXL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '4XL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXXXL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '5XL') !== false:
											$size['size_5xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '6XL') !== false:
											$size['size_6xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XS') !== false:
											$size['size_xs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XL') !== false:
											$size['size_xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'S') !== false:
											$size['size_s'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'M') !== false:
											$size['size_m'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'L') !== false:
											$size['size_l'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										default:
											$size['size_other'] = (isset($value['quantity'])) ? $value['quantity'] : '';	
									}
									
									$items1[$key] = array_merge([
										"style_description" => (!empty($value['title'])) ? $value['title'] : '',
										"style_number" => (!empty($value['sku'])) ? $value['sku'] : '',
										"unit_cost" => (!empty($value['price'])) ? $value['price'] : '',
										"color" => (!empty($value['product_color_name'])) ? $value['product_color_name'] : '',
										"category_id" => common::PRINTAVO_INVOICE_ITEM_CATEGORY_ID,
										"images_attributes" => [
											[
												"file_url" => $filename,
												"mime_type" => (!empty($ext)) ? 'image/' . $ext : ''
											]
										]
									], $size);
								}
							}
							$filterData1 = self::CheckCustomArrayUnique($items1);
						}

						if (!empty($QuickBuyorderMasterData)) {
							$items2 = [];
							foreach ($QuickBuyorderMasterData as $key => $objData) {
								$tmpProData2 = array();
								$color1 = (!empty($objData['product_color_name'])) ? $objData['product_color_name'] : '';
								foreach ($QuickBuyorderMasterData as $keys => $objDatas) {
									$color2 = (!empty($objDatas['product_color_name'])) ? $objDatas['product_color_name'] : '';
									if ($objData['title'] == $objDatas['title'] AND $objData['sku'] == $objDatas['sku']) {
										$tmpProData2[$color2][] = $objDatas;
									}
								}

								$size['size_yxs'] = "";
								$size['size_ys'] = "";
								$size['size_ym'] = "";
								$size['size_yl'] = "";
								$size['size_yxl'] = "";
								$size['size_xs'] = "";
								$size['size_s'] = "";
								$size['size_m'] = "";
								$size['size_l'] = "";
								$size['size_xl'] = "";
								$size['size_2xl'] = "";
								$size['size_3xl'] = "";
								$size['size_4xl'] = "";
								$size['size_5xl'] = "";
								$size['size_xs'] = "";
								$size['size_s'] = "";
								$size['size_m'] = "";
								$size['size_l'] = "";
								$size['size_xl'] = "";
								$size['size_2xl'] = "";
								$size['size_3xl'] = "";
								$size['size_4xl'] = "";
								$size['size_5xl'] = "";
								$size['size_6xl'] = "";
								$size['size_other'] = "";
								
								$productData[$objData['title']] = $tmpProData2;

								foreach ($productData[$objData['title']][$color1] as $value) {
									$mocupImage = parent::commonMockups($value['store_owner_product_variant_master_id']);
									$filename = (!empty($value['image'])) ? $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $value['image']) : '';
									if(!empty($mocupImage)){
										$filename = (!empty($value['image'])) ? $s3Obj->getAwsUrl(common::LOGO_MOCKUP_UPLOAD_S3_PATH .$store_master_id.'/'. $mocupImage[0]['image']) : '';
									}
									$ext = pathinfo($filename, PATHINFO_EXTENSION);
									$variantSize = (isset($value['size'])) ? $value['size'] : '';
									//$size =self::getPrintavoSizes($variantSize,$value);
									switch (true) {
										case strpos($variantSize, 'Youth XS') !== false:
											$size['size_yxs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth S') !== false:
											$size['size_ys'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth M') !== false:
											$size['size_ym'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth L') !== false:
											$size['size_yl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Youth XL') !== false:
											$size['size_yxl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XS') !== false:
											$size['size_xs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult S') !== false:
											$size['size_s'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult M') !== false:
											$size['size_m'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult L') !== false:
											$size['size_l'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XL') !== false:
											$size['size_xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 2XL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 3XL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXXL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 4XL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult XXXXL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 5XL') !== false:
											$size['size_5xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'Adult 6XL') !== false:
											$size['size_6xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '2XL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXL') !== false:
											$size['size_2xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '3XL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXXL') !== false:
											$size['size_3xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '4XL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XXXXL') !== false:
											$size['size_4xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '5XL') !== false:
											$size['size_5xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, '6XL') !== false:
											$size['size_6xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XS') !== false:
											$size['size_xs'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'XL') !== false:
											$size['size_xl'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'S') !== false:
											$size['size_s'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'M') !== false:
											$size['size_m'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										case strpos($variantSize, 'L') !== false:
											$size['size_l'] = (isset($value['quantity'])) ? $value['quantity'] : '';
											break;
										
										default:
											$size['size_other'] = (isset($value['quantity'])) ? $value['quantity'] : '';	
									}

									$items2[$key] = array_merge([
										"style_description" => (!empty($value['title'])) ? $value['title'] : '',
										"style_number" => (!empty($value['sku'])) ? $value['sku'] : '',
										"unit_cost" => (!empty($value['price'])) ? $value['price'] : '',
										"color" => (!empty($value['product_color_name'])) ? $value['product_color_name'] : '',
										"category_id" => common::PRINTAVO_INVOICE_ITEM_CATEGORY_ID,
										"images_attributes" => [
											[
												"file_url" => $filename,
												"mime_type" => (!empty($ext)) ? 'image/' . $ext : ''
											]
										]
									], $size);
								}
							}
							$filterData2 = self::CheckCustomArrayUnique($items2);
						}

						$orderno_new ='';
						$orderSql="SELECT GROUP_CONCAT(DISTINCT om.shop_order_id) as orderIds,GROUP_CONCAT(DISTINCT '#',shop_order_number) AS orderNumbers ,sopm.group_name FROM `store_orders_master` as om 
						INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id 
						INNER JOIN `store_owner_product_master` as sopm on sopm.id = oim.store_owner_product_master_id
						WHERE 1 AND om.is_order_cancel = 0 AND sopm.group_name='".$group_nameData."'  AND oim.is_deleted = 0 AND om.shop_order_number!='' AND oim.store_master_id = ".$store_master_id ." AND om.order_tags NOT LIKE '%Return_Prime%' ";
						$orderData = parent::selectTable_f_mdl($orderSql);
						$orderNumbers = '';
						$orderIds = '';
						if (!empty($orderData[0]['orderNumbers'])) {
							$orderNumbers = $orderData[0]['orderNumbers'];
							$orderIds 		= $orderData[0]['orderIds'];
							$orderno_new 	=$orderNumbers;
						}

						$morderSql = "SELECT GROUP_CONCAT(DISTINCT om.manual_order_number) as orderIds,GROUP_CONCAT(DISTINCT '#',manual_order_number) AS orderNumbers ,sopm.group_name FROM `store_orders_master` as om 
						INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id
						INNER JOIN `store_owner_product_master` as sopm on sopm.id = oim.store_owner_product_master_id
						WHERE 1 AND om.is_order_cancel = 0 AND sopm.group_name='".$group_nameData."' AND oim.is_deleted = 0 AND om.manual_order_number!='' AND oim.store_master_id = ".$store_master_id." ";
						$manualorderData = parent::selectTable_f_mdl($morderSql);
						$ManualorderNumbers = '';
						$orderIds = '';
						if (!empty($manualorderData[0]['orderNumbers'])) {
							$ManualorderNumbers = $manualorderData[0]['orderNumbers'];
							$orderIds 					= $manualorderData[0]['orderIds'];
							$orderno_new				=$orderno_new.','.$ManualorderNumbers;
						}
						
						$QuickorderSql = "SELECT GROUP_CONCAT(DISTINCT om.quickbuy_order_number) as orderIds,GROUP_CONCAT(DISTINCT '#',quickbuy_order_number) AS orderNumbers ,sopm.group_name FROM `store_orders_master` as om 
						INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id 
						INNER JOIN `store_owner_product_master` as sopm on sopm.id = oim.store_owner_product_master_id
						WHERE 1 AND om.is_order_cancel = 0 AND sopm.group_name='".$group_nameData."' AND oim.is_deleted = 0 AND om.quickbuy_order_number!='' AND oim.store_master_id = ".$store_master_id." ";
						$QuickorderData = parent::selectTable_f_mdl($QuickorderSql);
						$orderNumbers = '';
						$orderIds = '';
						if (!empty($QuickorderData[0]['orderNumbers'])) {
							$QuickbuyorderNumbers = $QuickorderData[0]['orderNumbers'];
							$orderIds 						= $QuickorderData[0]['orderIds'];
							$orderno_new					=$orderno_new.','.$QuickbuyorderNumbers;
						}

						$filterDatanew=(array_merge($filterData,$filterData1,$filterData2));
						
						$printavoData = [
							"user_id"                     => $userId,
							"customer_id"                 => $customerId,
							"orderstatus_id"              => $orderstatus_id,
							"formatted_customer_due_date" => date("m/d/y"),
							"formatted_due_date"          => date("m/d/y"),
							"production_notes"            => $orderno_new,
							"order_nickname"              => $store_name.'-'.$group_nameData,
							"lineitems_attributes"        => $filterDatanew
						];

						$jsonData = json_encode($printavoData);
						$response = self::createPrintavoOrder($jsonData);

						if (isset($response['error']) && !empty($response['error'])) {
							$res['STATUS'] = false;
							$res['MESSAGE'] = $response['error'];
						}else {
							$envoiceNumber = $response['visual_id'];
							$envoiceId = $response['id'];
							$egtp_insert_data = [
								'store_master_id' => trim($store_master_id),
								'group_name' => trim($group_nameData),
								'printavo_invoice_id' => trim($envoiceId),
								'printavo_invoice_number' => trim($envoiceNumber),
								'created_on' => @date('Y-m-d H:i:s'),
								'created_on_ts' => time(),
							];
							parent::insertTable_f_mdl('export_group_to_printavo',$egtp_insert_data);
							$orderIdArray = explode(',', $orderIds);
							$tagData = self::updateOrderTags($orderIdArray, $storeId, $envoiceNumber);
							$res['STATUS'] = true;
							$res['MESSAGE'] = "Order created successfully on printavo";
						}
					}else {
						$res['STATUS'] = false;
						$res['MESSAGE'] = "Order not found";
					}
				}
				echo json_encode($res);die();
			}	
		}
	}

	public function createPrintavoOrder($jsonData){
		$orderApiBaseUrl = common::PRINTAVO_ORDER_ENDPOINT;
		$printavoEmail = common::PRINTAVO_EMAIL;
		$printavoToken = common::PRINTAVO_TOKEN;
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $orderApiBaseUrl . '?email=' . $printavoEmail . '&token=' . $printavoToken . '',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => $jsonData,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: application/json',
				'Accept-Charset: utf-8',
				'Connection: keep-alive'
			),
		));

		$response = curl_exec($curl);
		curl_close($curl);
		$response = json_decode($response, true);
		return $response;
	}

	public function updateOrderTags($orderIdArray, $storeId,$envoiceNumber){
		require_once('lib/class_graphql.php');
		$sql = "SELECT id, shop_name, token, timezone FROM shop_management WHERE id = 1";
		$shopInfo = parent::selectTable_f_mdl($sql);

		$headers = array(
			'X-Shopify-Access-Token' => $shopInfo[0]['token']
		);
		$graphql = new Graphql($shopInfo[0]['shop_name'], $headers);
		
		for ($i = 0; $i < count($orderIdArray); $i++) {
			$orderId = $orderIdArray[$i];

			$updateOrderData = [
				'order_tags' => "SpiritHero SA ".$storeId.",Printavo Invoice #".$envoiceNumber.""
			];
			
			parent::updateTable_f_mdl('store_orders_master',$updateOrderData,'shop_order_id="'.$orderId.'"');

			$inputData = '{
				"input": {
					"id": "gid://shopify/Order/' . $orderId . '",
					"tags":[
						"SpiritHero SA '.$storeId.'","Printavo Invoice #'.$envoiceNumber.'"
					]
				}
			}';
			$orderUpdateMutation = 'mutation orderUpdate($input: OrderInput!) {
			  orderUpdate(input: $input) {
			    order {
			      id,
			      tags
			    }
			    userErrors {
			      field
			      message
			    }
			  }
			}';
			$updatedResponse = $graphql->runByMutation($orderUpdateMutation, $inputData);
		}
	}

	public function CheckCustomArrayUnique($line_data_arr)
	{
		$result      = array();
		$tmpResult      = array();
		foreach ($line_data_arr as $keys => $values) {
			if (!empty($tmpResult)) {
				$status = true;
				foreach ($tmpResult as $key => $val) {
					if ($val['style_description'] == $values['style_description'] && $val['color'] == $values['color'] && $val['style_number'] == $values['style_number']) {
						$status = false;
					}
				}
				if ($status) {
					$tmpResult[] = $values;
				}
			} else {
				$tmpResult[] = $values;
			}
		}
		return $tmpResult;
	}

	public function getComposerEmailHistory()
	{
		$sql = 'SELECT id,store_master_ids,store_name,owner_email,from_email,subject,update_on,sent_by FROM compose_email_history ORDER BY id DESC';
		$ComposeEmailHistory = parent::selectTable_f_mdl($sql);
		return $ComposeEmailHistory;
	}

	public function getallAdminEmailSA()
	{
		$sql = "SELECT DISTINCT * FROM `users` WHERE role_id='1' AND email !='preetamdhanotiya@bitcot.com' ";
		$all_email_sa = parent::selectTable_f_mdl($sql);
		return $all_email_sa;
	}

	public function move_to_archive()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "move_to_archive") {
				$storeMasterId = parent::getVal("store_master_ids");
				foreach ($storeMasterId as $values) {
					$updateStoreData = [
						'is_archive' => '1'
					];
					parent::updateTable_f_mdl('store_master',$updateStoreData,' id="'.$values.'"');
				}
				$resultArray = array();
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Store archive successfully.";
				common::sendJson($resultArray);
			}
			die;
		}
	}

	public function move_to_unarchive()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "move_to_unarchive") {
				$storeMasterId = parent::getVal("store_master_ids");
				foreach ($storeMasterId as $values) {
					$updateStoreData = [
						'is_archive' => '0'
					];
					parent::updateTable_f_mdl('store_master',$updateStoreData,' id="'.$values.'"');
				}
				$resultArray = array();
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Store unarchive successfully.";
				common::sendJson($resultArray);
			}
			die;
		}
	}

	function archiveStore()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "archiveStore") {
				$storeMasterId = parent::getVal("store_master_id");
				$updateStoreData = [
					'is_archive' => '1'
				];
				parent::updateTable_f_mdl('store_master',$updateStoreData,' id="'.$storeMasterId.'"');
				$resultArray = array();
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Store archived successfully.";
				common::sendJson($resultArray);
			}
		}
	}

	function unarchiveStore()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "unarchiveStore") {
				$storeMasterId = parent::getVal("store_master_id");
				$updateStoreData = [
					'is_archive' => '0'
				];
				parent::updateTable_f_mdl('store_master',$updateStoreData,' id="'.$storeMasterId.'"');
				$resultArray = array();
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Store unarchived successfully.";
				common::sendJson($resultArray);
			}
		}
	}

	function generateChecklistReport($store_master_id){
		$s3Obj = new Aws3;
		$bindHtml  = '';

		$sql = "SELECT * FROM store_master WHERE id = " . $store_master_id;
		$storeInfo = parent::selectTable_f_mdl($sql);
		$store_name =$storeInfo[0]["store_name"];
		$store_name=str_replace("/","_",$store_name);
		$store_sale_type_master_id =$storeInfo[0]["store_sale_type_master_id"];
		$lable_start_date = $storeInfo[0]['approved_date'];
		$lable_end_date = @date('m/d/Y');

		$store_sale_type = '';
		if($store_sale_type_master_id==1){
			$store_sale_type = 'Flash Sale';
		}else{
			$store_sale_type = 'On-Demand';
		}

		$cond_start_date     = '';
		if(isset($lable_start_date) && !empty($lable_start_date)){
			$cond_start_date = ' AND store_orders_master.created_on_ts>="'.strtotime($lable_start_date.' 0:0').'"';
		}
		$cond_end_date       = '';
		if(isset($lable_end_date) && !empty($lable_end_date)){
			$cond_end_date   = ' AND store_orders_master.created_on_ts<="'.strtotime($lable_end_date.' 23:59').'"';
		}

		$storeCondition      = '';
		if($store_master_id > 0){					
			$storeCondition = " AND store_orders_master.store_master_id = '".$store_master_id."'";
		}

		/* Task 100 Add new field personlization in sql*/
		$sql = 'SELECT store_orders_master.*, 
		CASE
			WHEN store_orders_master.order_type = 1 THEN store_orders_master.shop_order_number
			WHEN store_orders_master.order_type = 2 THEN CONCAT_WS("-","MO",store_orders_master.manual_order_number)
			ELSE CONCAT_WS("-","QB",store_orders_master.quickbuy_order_number)
		END AS shop_order_number, store_master.store_fulfillment_type,soim.store_orders_master_id,soim.is_deleted,soim.title,soim.quantity,soim.variant_title,soim.personalization_name FROM `store_orders_master` INNER JOIN store_master ON store_orders_master.store_master_id = store_master.id INNER JOIN store_order_items_master as soim ON store_orders_master.id = soim.store_orders_master_id WHERE 1 
		AND is_order_cancel = 0
		AND soim.is_deleted = 0
		'.$cond_start_date.'
		'.$cond_end_date.'
		'.$storeCondition.'
		ORDER BY student_name ASC;
		';
		$list_data = parent::selectTable_f_mdl($sql);
		
		$bindHtml .="<html>
						<head>
							<style type = 'text/css'>
								@import url('https://fonts.googleapis.com/css?family=Oswald&display=swap');
								table, td, th {
									border: 1px solid black;
								}

								table {
									border-collapse: collapse;
									width: 100%;
									text-align:center;
								}

								th {
									text-align:center;
								}
							</style>
						</head>
					<body>";
		$bindHtml .='<div class="row">
						<div class="col-lg-12">
							<div class="table-responsive">
		<table class=" table table-bordered table-hover">
			<thead>
				<tr>
					<th scope="col">Order#</th>
					<th scope="col">Item Name</th>
					<th scope="col">Size</th>
					<th scope="col">Color</th>
					<th scope="col">Quantity</th>
					<th scope="col">Teacher Name</th>
					<th scope="col">Student Name</th>
					<th scope="col">Personalization</th>
					<th scope="col">Received?</th>
				</tr>
			</thead>';/* Task 100 Add new th personlization*/
		$bindHtml.='<tbody>';
		if(!empty($list_data)){
			foreach ($list_data as $value) {
				$array         =  explode(" / ", $value['variant_title'], 2);
				$variant_color = !empty($array[1])?$array[1] : '';
				$variant_size  = !empty($array[0])?$array[0] : '';
				$bindHtml
				.='<tr>
					<td scope="row">'.$value['shop_order_number'].'</td>
					<td>'.$value['title'].'</td>
					<td>'.$variant_size.'</td>
					<td>'.$variant_color.'</td>
					<td>'.$value['quantity'].'</td>
					<td>'.$value['sortlist_info'].'</td>
					<td>'.$value['student_name'].'</td>
					<td>'.$value['personalization_name'].'</td>
					<td></td>
				</tr>';/* Task 100 Add new td personlization*/
			}
			$bindHtml.='<tbody>';
				$bindHtml .='</table>
						</div>
							</div>
								</div>
									</body>
										</html>';
		}else{
			$bindHtml="";
		}
		
		require_once("html-templates/custom/dompdf/vendor/autoload.php");
		$dompdf = new \Dompdf\Dompdf();
		$dompdf->loadHtml($bindHtml);
		$dompdf->setPaper('A4', 'landscape');
		$dompdf->render();
			
		// $dompdf->stream($store_name.' Checklist Report.pdf',array("Attachment" => false));
		// Generate a unique filename for the PDF
		$store_name=str_replace(" ","_",$store_name);
		$filename = uniqid($store_name.'_checklist_report').'.pdf';
		// Define the output folder path
		$outputPath = 'image_uploads/'.$filename;
		$outputpdf=$dompdf->output(['compress' => 0, 'filename' => $outputPath]);
		file_put_contents($filename, $outputpdf);

		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="' . $filename . '"');

		$s3Obj->putObject('store_owner_reports/'.$filename, file_get_contents($filename));
		$report_pdf_link =$s3Obj->getAwsUrl("store_owner_reports/".$filename);

		return $report_pdf_link;
	}

	public function export_order_report($store_master_id){
		$s3Obj = new Aws3;
		$status = false;

		$sql = "SELECT * FROM store_master WHERE id = " . $store_master_id;
		$storeInfo = parent::selectTable_f_mdl($sql);
		$store_name =$storeInfo[0]["store_name"];
		$store_name=str_replace("/","_",$store_name);
		$store_sale_type_master_id =$storeInfo[0]["store_sale_type_master_id"];
		$lable_start_date = $storeInfo[0]['approved_date'];
		$lable_end_date = @date('m/d/Y');
		
		$cond_start_date = '';
		if(isset($lable_start_date) && !empty($lable_start_date)){
			$cond_start_date = ' AND store_orders_master.created_on_ts>="'.strtotime($lable_start_date.' 0:0').'"';
		}
		$cond_end_date = '';
		if(isset($lable_end_date) && !empty($lable_end_date)){
			$cond_end_date = ' AND store_orders_master.created_on_ts<="'.strtotime($lable_end_date.' 23:59').'"';
		}

		$store_sale_type = '';
		if($store_sale_type_master_id==1){
			$store_sale_type = 'Flash Sale';
		}else{
			$store_sale_type = 'On-Demand';
		}

		$storeCondition = '';
		if($store_master_id > 0){					
			$storeCondition = " AND soim.store_master_id = '".$store_master_id."'";
		}
		
		$groupSql = 'SELECT store_owner_product_master.group_name , soim.id, soim.store_master_id FROM `store_order_items_master` as soim INNER JOIN store_owner_product_master ON store_owner_product_master.id = soim.store_owner_product_master_id INNER JOIN store_orders_master ON store_orders_master.id = soim.store_orders_master_id WHERE is_deleted = 0  AND is_order_cancel = 0
		
		'.$storeCondition.' Group By store_owner_product_master.group_name ORDER BY store_owner_product_master.group_name ASC
		';
		$groupData = parent::selectTable_f_mdl($groupSql);
		$resultArray = array();
		$store_name=str_replace(" ","_",$store_name);
		$export_file = $store_name.'_Order_Report.csv';
		$export_file_path = 'image_uploads/'.$export_file;
		$export_file_url = common::IMAGE_UPLOAD_URL.$export_file;
		$file_for_export_data = fopen($export_file_path,"w");
		$BOM = "\xEF\xBB\xBF";
		
		header('Content-type: text/csv; charset=UTF-8');
		header("Content-Description: File Transfer"); 
    	header("Content-Type: application/octet-stream"); 
    	header('Content-Disposition: attachment; filename="'.$export_file.'";');
		// print_r($groupData);die;
		if(!empty($groupData)){
			foreach($groupData as $groupName){
					$gname = $groupName['group_name'];
					$list_data = array();
					$order_arr = array();
					
				$sql = 'SELECT store_orders_master.*, 
				CASE
					WHEN store_orders_master.order_type = 1 THEN store_orders_master.shop_order_number
					WHEN store_orders_master.order_type = 2 THEN CONCAT_WS("-","MO",store_orders_master.manual_order_number)
					ELSE CONCAT_WS("-","QB",store_orders_master.quickbuy_order_number)
				END AS shop_order_number, soim.id as itemID, store_master.store_fulfillment_type,store_owner_product_master.group_name FROM `store_orders_master` INNER JOIN store_master ON store_orders_master.store_master_id = store_master.id INNER JOIN store_order_items_master as soim ON store_orders_master.id = soim.store_orders_master_id INNER JOIN store_owner_product_master ON store_owner_product_master.id = soim.store_owner_product_master_id WHERE 1 AND store_owner_product_master.group_name = "'.$gname.'"
						AND is_order_cancel = 0 AND is_deleted = 0
						'.$cond_start_date.'
						'.$cond_end_date.'
						'.$storeCondition.' ORDER BY store_orders_master.shop_order_number DESC
						';

				$list_data = parent::selectTable_f_mdl($sql);

				if(!empty($list_data)){
					fputcsv($file_for_export_data,
						['Group Name',$gname]
					);
					fputcsv($file_for_export_data,
						['Order #','Order Date','Item Name','Size','Color','Quantity','SKU','Teacher name','Student Name','Purchaser Email','Purchaser Name','Store Type' ,'Fulfillment Type','Personalization Name','Fundraising Status','Fundraising Amount']
					);

					foreach($list_data as $single_order){
						//Task 57 20/10/2021 add new condition is_deleted = 0
						$sql = 'SELECT * FROM `store_order_items_master`
								WHERE is_deleted = 0 AND id = "'.$single_order['itemID'].'" AND store_orders_master_id =
								'.$single_order['id'].'';
						
						$order_arr = parent::selectTable_f_mdl($sql);

						$sort_list_name = $student_name ='';

						$sort_list_name = $single_order['sortlist_info'];
						$student_name = $single_order['student_name'];

						$order_date = $single_order['created_on'];// New 25/09/2021

						/* Task 34 start */
						$store_fulfillment_type = '';
						if($single_order['store_fulfillment_type'] == 'SHIP_1_LOCATION_NOT_SORT'){
							$store_fulfillment_type = 'Silver';
						}
						else if($single_order["store_fulfillment_type"] == 'SHIP_1_LOCATION_SORT'){
							$store_fulfillment_type = 'Gold';
						}
						else{
							$store_fulfillment_type = 'Platinum';
						}

						$getVariantsIDArr = array();
						if(count($order_arr) > 0){
							
							foreach($order_arr as $single_item){

								/* Task 61 start 01/11/2021*/
								$array =  explode(" / ", $single_item['variant_title'], 2);
								$variant_color     = !empty($array[1])?$array[1]:'';
								$variant_size      = !empty($array[0])?$array[0]:'';
								/* Task 61 end 01/11/2021*/
								$fundraising_amount = number_format((float)$single_item['fundraising_amount'], 2);
								$store_fund_amount = number_format((float)$single_item['store_fund_amount'], 2);
								
								//here we skip first record, because it is already include above
								$getVariantsIDArr[] = $single_item['shop_variant_id'];
								fputcsv($file_for_export_data,
									[
										trim($single_order['shop_order_number']),
										trim($order_date),// New 25/09/2021
										trim($single_item['title']),
										trim($variant_size),
										trim($variant_color),
										// Task 61 end
										trim($single_item['quantity']),
										trim($single_item['sku']),
										trim($sort_list_name),
										trim($student_name),
										trim($single_order['cust_email']),
										trim($single_order['cust_name']),
										trim(explode(':',$single_order['store_sale_type'])[0]),
										trim($store_fulfillment_type),//Task 34
										trim($single_item['personalization_name']),
										trim($single_item['fundraising_status']),
										trim("$".$fundraising_amount)
										

									]
								);
							}

							
						}
						
					}

					fputcsv($file_for_export_data,['']);
				}
			}
			$status = true;
		}
		/* DELETE PRODUCT SECTION START*/

		$sqlDelete = 'SELECT store_orders_master.*, 
		CASE
			WHEN store_orders_master.order_type = 1 THEN store_orders_master.shop_order_number
			WHEN store_orders_master.order_type = 2 THEN CONCAT_WS("-","MO",store_orders_master.manual_order_number)
			ELSE CONCAT_WS("-","QB",store_orders_master.quickbuy_order_number)
		END AS shop_order_number, soim.id as itemID, store_master.store_fulfillment_type 
			FROM `store_orders_master` 
			INNER JOIN store_master ON store_orders_master.store_master_id = store_master.id 
			INNER JOIN store_order_items_master as soim ON store_orders_master.id = soim.store_orders_master_id 
			WHERE 1 AND soim.store_owner_product_master_id NOT IN (SELECT store_owner_product_master.id FROM store_owner_product_master WHERE store_master_id = "'.$store_master_id.'" ) AND is_order_cancel = 0 AND is_deleted = 0
			'.$cond_start_date.'
			'.$cond_end_date.'
			'.$storeCondition.' ORDER BY store_orders_master.shop_order_number DESC
			';
		$dataDelete = parent::selectTable_f_mdl($sqlDelete);

		if(!empty($dataDelete)){
			fputcsv($file_for_export_data,
				['Deleted Product Orders','']
			);
			fputcsv($file_for_export_data,
				['Order #','Order Date','Item Name','Size','Color','Quantity','SKU','Teacher name','Student Name','Purchaser Email','Purchaser Name','Store Type' ,'Fulfillment Type','Personalization Name','Fundraising Status','Fundraising Amount']
			);

			foreach($dataDelete as $single_order){
				//Task 57 20/10/2021 add new condition is_deleted = 0
				$sql = 'SELECT * FROM `store_order_items_master`
						WHERE is_deleted = 0 AND id = "'.$single_order['itemID'].'" AND store_orders_master_id =
						'.$single_order['id'].'';
				
				$order_arr = parent::selectTable_f_mdl($sql);
				$sort_list_name = $student_name ='';
				$sort_list_name = $single_order['sortlist_info'];
				$student_name = $single_order['student_name'];
				$order_date = $single_order['created_on'];// New 25/09/2021

				/* Task 34 start */
				$store_fulfillment_type = '';
				if($single_order['store_fulfillment_type'] == 'SHIP_1_LOCATION_NOT_SORT'){
					$store_fulfillment_type = 'Silver';
				}
				else if($single_order["store_fulfillment_type"] == 'SHIP_1_LOCATION_SORT'){
					$store_fulfillment_type = 'Gold';
				}
				else{
					$store_fulfillment_type = 'Platinum';
				}

				$getVariantsIDArr = array();
				if(count($order_arr) > 0){
					foreach($order_arr as $single_item){

						/* Task 61 start 01/11/2021*/
						$array =  explode(" / ", $single_item['variant_title'], 2);
						$variant_color     = !empty($array[1])?$array[1]:'';
						$variant_size      = !empty($array[0])?$array[0]:'';
						/* Task 61 end 01/11/2021*/
						$fundraising_amount = number_format((float)$single_item['fundraising_amount'], 2);
						$store_fund_amount = number_format((float)$single_item['store_fund_amount'], 2);
						//here we skip first record, because it is already include above
						$getVariantsIDArr[] = $single_item['shop_variant_id'];
						fputcsv($file_for_export_data,
							[
								trim($single_order['shop_order_number']),
								trim($order_date),// New 25/09/2021
								trim($single_item['title']),
								trim($variant_size),
								trim($variant_color),
								// Task 61 end
								trim($single_item['quantity']),
								trim($single_item['sku']),
								trim($sort_list_name),
								trim($student_name),
								trim($single_order['cust_email']),
								trim($single_order['cust_name']),
								trim(explode(':',$single_order['store_sale_type'])[0]),
								trim($store_fulfillment_type),//Task 34
								trim($single_item['personalization_name']),
								trim($single_item['fundraising_status']),
								trim("$".$fundraising_amount)
							]
						);
					}	
				}	
			}
			fputcsv($file_for_export_data,
					['']
			);
			$status = true;
		}
		$mime_type = "text/csv";
			
		/* DELETE PRODUCT SECTION END*/
		fclose($file_for_export_data);
		$s3Obj->putObject('store_owner_reports/'.$export_file, file_get_contents($export_file_url),$mime_type);
		$report_csv_link =$s3Obj->getAwsUrl("store_owner_reports/".$export_file);
		return $report_csv_link;
	}

	function sendPurchaserEmail($shop_order_number,$email,$name,$emailTemplateId,$storeMasterId){
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
		$shop_order_number = "#".$shop_order_number;

		$sql = 'SELECT * FROM store_master WHERE id="'.$storeMasterId.'"';
    	$store_data = parent::selectTable_f_mdl($sql);

		$store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;

		$store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["production_date"]) : '' ;

		$subject = str_replace('{{ORDER_NUMBER}}',$shop_order_number,$subject);
		$subject = str_replace('{{STORE_NAME}}',$store_data[0]['store_name'],$subject);

		$mailSendStatus = 1;
		if($store_data[0]['email_notification'] == '1'){
			$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, str_replace(["{{CUSTOMER_NAME}}","{{STORE_NAME}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}","{{ORDER_NUMBER}}"],[$name,$store_data[0]['store_name'],$logo,$store_open_date,$store_last_date,$shop_order_number],$emailData[0]['body']), str_replace(["{{CUSTOMER_NAME}}","{{STORE_NAME}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}","{{ORDER_NUMBER}}"],[$name,$store_data[0]['store_name'],$logo,$store_open_date,$store_last_date,$shop_order_number],$emailData[0]['body']));
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

	public function resendLoginInfo()
	{
		$s3Obj = new Aws3;
		require_once(common::EMAIL_REQUIRE_URL);
        if(strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp')!==false){
            $objAWS = new aws_ses_smtp();
        }else if(strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail')!==false){
            $objAWS = new sendGridEmail();
        }else{
            $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
        }

		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "resendLoginInfo" && !empty(parent::getVal("store_master_id")) && !empty(parent::getVal("store_owner_details_master_id"))) {

				$copy_store_master_id = parent::getVal("store_master_id");
				$store_owner_details_master_id = parent::getVal("store_owner_details_master_id");
				$store_name = parent::getVal("store_name");

				$sql = 'SELECT * FROM store_owner_details_master WHERE id="'.$store_owner_details_master_id.'"';
				$store_owner_data = parent::selectTable_f_mdl($sql);
				$organization_name='Test';
				if(!empty($store_owner_data[0]['organization_name'])){
					$organization_name=$store_owner_data[0]['organization_name'];
					$words = explode(" ", $organization_name);
					$organization_name = ucfirst($words[0]);
				}
				$randomString=$organization_name.'1234';
				$password	= trim($randomString);
				$psswordmd	=md5($randomString);

				$pass_update_data = [
                    'password' => $psswordmd
                ];
                parent::updateTable_f_mdl('store_owner_details_master',$pass_update_data,'id="'.$store_owner_details_master_id.'"');
					
				$emailData = parent::getEmailTemplateInfo('39');
				$dashboardLink = '<a href="'.common::CUSTOMER_PORTAL_SITE_URL.'index.php?do=stores">Click here</a>';
				$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
				$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
	
				$subject = $emailData[0]['subject'];
				$body = $emailData[0]['body'];
				$from_email = common::AWS_ADMIN_EMAIL;
				
				
				$first_name='';
				$to_email='';
				if(!empty($store_owner_data)){
					$first_name=$store_owner_data[0]['first_name'];
					$to_email=$store_owner_data[0]['email'];
				}
				$body  = str_replace('{{FIRST_NAME}}', $first_name, $body);
				$body  = str_replace('{{STORE_NAME}}', $store_name, $body);
				$body  = str_replace('{{EMAIL}}', $to_email, $body);
				$body  = str_replace('{{PASSWORD}}', $password, $body);
				$body  = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
				$body  = str_replace('{{DASHBOARD_LINK}}', $dashboardLink, $body);

				$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body);
				/*send mail store manager */
				$sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_details_master_id . '"';
				$smm_data =  parent::selectTable_f_mdl($sql_managerData);
				if(!empty($smm_data)){
					foreach ($smm_data as $managerData) {
						$body       = $emailData[0]['body'];
						$to_email   = $managerData['email'];
						$first_name = $managerData['first_name'];
						$body  		= str_replace('{{FIRST_NAME}}', $first_name, $body);
						$body  		= str_replace('{{STORE_NAME}}', $store_name, $body);
						$body  		= str_replace('{{EMAIL}}', $to_email, $body);
						$body  		= str_replace('{{PASSWORD}}', $password, $body);
						$body  		= str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
						$body  		= str_replace('{{DASHBOARD_LINK}}', $dashboardLink, $body);
						$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body);
					}
				}
				/*send mail store manager */
			}
		}

		$resultArray = array();
		if($mailSendStatus){
			$resultArray["isSuccess"] = "1";
			$resultArray["msg"] = "Email sent successfully.";
		}
		else{
			$resultArray["isSuccess"] = "0";
			$resultArray["msg"] = "Oops! there is some issue during sent email. Please try again.";
		}
		echo json_encode($resultArray, 1);exit;

	}

	public function puse_store_syncing()
	{
		global $s3Obj;
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "puse_store_syncing") {
				$resultArray = array();
				$storeMasterId = parent::getVal("store_master_id");
				$is_manual_store_sync = parent::getVal("is_manual_store_sync");
				if($is_manual_store_sync=='0'){
					$updateStoreData = [
						'is_products_synced' => '1',
						'is_manual_store_sync' => '0'
					];
					$resultArray["msg"] = "Store Pused Successfully.";

				}else{

					$updateStoreData = [
						'is_products_synced' => '0',
						'is_manual_store_sync' => '1'
					];
					$resultArray["msg"] = "Store Resume Successfully.";
				}
				parent::updateTable_f_mdl('store_master',$updateStoreData,' id="'.$storeMasterId.'"');
				
				$resultArray["isSuccess"] = "TRUE";
				common::sendJson($resultArray);
			}
		}
	}

	function updateEmailSa()
	{
		$resultArray=[];
		if (!empty(parent::getVal("method")) && parent::getVal("method") == "update_email_sa" ) {
			if(!empty($_POST['store_master_id'])){
				$email = trim($_POST['email']);
				$store_master_id = trim($_POST['store_master_id']);
				$sql = 'SELECT store_owner_details_master_id from store_master where id = "'.$store_master_id.'" ';
				$owner_data = parent::selectTable_f_mdl($sql);
				$store_owner_details_master_id='';
				if(!empty($owner_data)){
					$store_owner_details_master_id=$owner_data[0]['store_owner_details_master_id'];
				}

				$status=0;
				$sql1 = 'SELECT email from store_owner_details_master where email ="'.$email.'" AND id!="'.$store_owner_details_master_id.'" ';
				$store_owner_data = parent::selectTable_f_mdl($sql1);
				if(!empty($store_owner_data)){
					$status=1;
				}else{
					$sql_get_email = 'SELECT email from store_manager_master where email ="'.$email.'" ';
					$list_data = parent::selectTable_f_mdl($sql_get_email);
					if(!empty($list_data)){
						$status=1;
					}else{
						$sql_email = 'SELECT email from users where email ="'.$email.'" ';
						$sa_email = parent::selectTable_f_mdl($sql_email);
						if(!empty($sa_email)){
							$status=1;
						}else{
							$status=0;
						}
					}
				}
				if ($status == 1) {
					$resultArray['SUCCESS'] = 'TRUE';
					$resultArray['MESSAGE'] = 'Email already exist';
					
				} else {
					$resultArray['SUCCESS'] = 'FALSE';
					$resultArray['MESSAGE'] = 'Records are not found.';
				}
			}
		}
		common::sendJson($resultArray);
	}
}
