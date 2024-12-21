<?php
include_once 'model/process_on_request_mdl.php';
include_once $path . '/libraries/Aws3.php';
class process_on_request_ctl extends process_on_request_mdl
{
	function __construct(){
		if(parent::isGET() || parent::isPOST()){
            if(!empty(parent::getVal("rfor"))){
                $this->checkRequestProcess(parent::getVal("rfor"));
            }
            else{
                header("HTTP/1.0 404 Not Found");
                exit;
            }
		}
    }
    
    function checkRequestProcess($requestFor){
        if($requestFor != ""){
            switch($requestFor){
                case "get_info":
					$this->getRequestedInfo();
                break;
				case "get_timer_info":
					$this->getCollectionTimerInfo();
                break;
				case "fetch-prod-info":
					$this->fetchProdMinQtyInfo();
                break;
				case "create_draft_order":
					$this->create_draft_order();
                break;
				case "close_store_status":
					$this->closeStoreStatus();
                break;
				case "verify-shopify-store":
					$this->verifyShopifyStore();
                break;
            }
        }
    }
	
	function getRequestedInfo(){
		$res=[];
		$requestCurrent_product = parent::getVal("current_product");
		$collection_id = parent::getVal("collection_id");

		$sql = 'SELECT sm.id as store_master_id,sm.store_fulfillment_type,sstm.sale_type 
		FROM `store_master` as sm
		LEFT JOIN `store_sale_type_master` as sstm ON sm.store_sale_type_master_id = sstm.id 
		WHERE sm.shop_collection_id IN ('.$collection_id.')';

		// $sql = 'SELECT `store_owner_product_master`.store_master_id,`store_master`.store_fulfillment_type,store_sale_type_master.sale_type 
		// FROM `store_owner_product_master` LEFT JOIN `store_master` ON `store_master`.id = `store_owner_product_master`.store_master_id 
		// LEFT JOIN `store_sale_type_master` ON `store_master`.store_sale_type_master_id = `store_sale_type_master`.id 
		// WHERE `store_owner_product_master`.shop_product_id IN ('.$requestCurrent_product.')';
		$store_data = parent::selectTable_f_mdl($sql);
		if (!empty($store_data)) {
			$divHtml = $sale_type=$store_fulfillment_type='';
			foreach ($store_data as $data) {
				if ($data['sale_type'] == 'Flash Sale' && $data['store_fulfillment_type'] != 'SHIP_EACH_FAMILY_HOME') {
					$store_master_id = $data['store_master_id'];
					$sale_type=$data['sale_type'];
					$store_fulfillment_type=$data['store_fulfillment_type'];
					$divHtml .= parent::getSortListNameInfo_f_mdl($store_master_id);
					$resultArray = parent::getSortListOptionInfo_f_mdl($store_master_id);
					break;
				}
			}
			if(!empty($resultArray)){
				$res['srt_info'] = $resultArray;
			}

			$res['SUCCESS'] = 'TRUE';
			$res['divHtml'] = $divHtml;
			$res['sale_type'] = $sale_type;
			$res['store_fulfillment_type'] = $store_fulfillment_type;
		}else{
			$res['SUCCESS'] = 'FALSE';
		}
		
		// $respArray = parent::getRequestedInfo_f_mdl($requestCurrent_product);

		// if(!empty($respArray)){
		// 	// is store has platinum fulfilment, then no need to show sorting list
		// 	$divHtml = '';
		// 	if($respArray[0]['store_fulfillment_type']!='SHIP_EACH_FAMILY_HOME'){
		// 		$store_master_id = $respArray[0]['store_master_id'];
		// 		$divHtml = parent::getSortListNameInfo_f_mdl($store_master_id);

		// 		$resultArray = parent::getSortListOptionInfo_f_mdl($store_master_id);

		// 	}
			
		// 	$res['SUCCESS'] = 'TRUE';
		// 	$res['divHtml'] = $divHtml;
		// 	$res['sale_type'] = $respArray[0]['sale_type'];;
		// 	$res['store_fulfillment_type'] = $respArray[0]['store_fulfillment_type'];

		// 	if(!empty($resultArray)){
		// 		$res['srt_info'] = $resultArray;
		// 	}
		// }else{
		// 	$res['SUCCESS'] = 'FALSE';
		// }
		echo json_encode($res,1);
	}
	
	function getCollectionTimerInfo(){
		#region - Set Post Variables
		$shopName = parent::getVal("shop");
		$collectionHandle = parent::getVal("col_handle");
		#endregion
		
		$collectionInfo = parent::getCollectionTimerInfo_f_mdl($collectionHandle);
		
		$returnArray = array();
		
		if(count($collectionInfo) > 0){
			$returnArray["isSuccess"] = "1";
			$returnArray["mID"] = $collectionInfo[0]["id"];
			$returnArray["cDate"] = '';
			if($collectionInfo[0]["store_close_date"]){
				$returnArray["cDate"] = date("M j, Y H:i:s", $collectionInfo[0]["store_close_date"]);
			}			
		}
		else{
			$returnArray["isSuccess"] = "0";
		}
		
		parent::sendJson($returnArray);
	}
	
	function closeStoreStatus(){
		if(isset($_POST['shop']) && !empty($_POST['shop']) && isset($_POST['col_handle']) && !empty($_POST['col_handle'])){
			//from now store will not actually close when end-date over. So we set exit here and ignore below functionality. Store will close when admin move to production.
			$resultArray['isSuccess'] = '1';
			$resultArray['msg'] = '';
			echo json_encode($resultArray,1);
			exit;

			$store_close = '0';
			$des_for_close_store = 'This store is now closed and is no longer accepting orders. Please contact your school if you would like this store reopened. Thanks!';
			$collection_handle = $_POST['col_handle'];
			
			$resArray = parent::updateStoreStatus_f_mdl($store_close,$des_for_close_store,$collection_handle);
			
			if($resArray['isSuccess'] == '1'){
				//get collection id using col-handle
				$sql = 'SELECT id, shop_collection_id, store_sale_type_master_id FROM `store_master` WHERE shop_collection_handle="'.$collection_handle.'"';
				$store_data = parent::selectTable_f_mdl($sql);
				if(isset($store_data[0]['shop_collection_id']) && !empty($store_data[0]['shop_collection_id'])){
					$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
					if(!empty($shop_data)) {
						require_once('lib/class_graphql.php');
						$headers = array(
							'X-Shopify-Access-Token' => $shop_data[0]['token']
						);
						$graphql = new Graphql($shop_data[0]['shop_name'], $headers);

						$collection_id = $store_data[0]['shop_collection_id'];
						$meta_namespace = common::FLASH_SALE_END_NAMESPACE;
						$meta_key = common::FLASH_SALE_END_KEY;
						$meta_value = common::FLASH_SALE_END_VALUE;

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
							$graphql->runByMutation($mutationData,$inputData);
						}
					}

					#region - Email To Customer Admin
					$this->sendCustomerEmail($store_data[0]['id'],common::EMAIL_TO_CUSTOMER_ADMIN_WHEN_FLASH_SALE_IS_OVER);
					#endregion

					#region - Email To Purchaser
					if($store_data[0]["store_sale_type_master_id"] == common::STORE_TYPE_FLASH_SALE_ID){
						$purchaserEmailArray = parent::getPurchaserInfo_f_mdl($store_data[0]["id"]);

						if(!empty($purchaserEmailArray) > 0){
							foreach($purchaserEmailArray as $objEmail){
								$this->sendPurchaserEmail($objEmail['shop_order_number'],$objEmail['cust_email'],$objEmail['cust_name'],common::EMAIL_TO_PURCHASERS_FOR_FLASH_SALE_ONLY_NOT_ON_DEMAND,$store_data[0]["id"]);
							}
						}
					}
					#endregion
				}
				$resultArray["isSuccess"] = "1";
			}else{
				$resultArray["isSuccess"] = "0";
			}
			common::sendJson($resultArray);
		}
	}
	
	function fetchProdMinQtyInfo(){
		#region - Set Post Variables
		$productId = parent::getVal("prod_id");
		#endregion
		
		parent::fetchProdMinQtyInfo_f_mdl($productId);
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
		
		//$mailSendStatus = $objAWS::sendEmail($from_email, $to_email, $subject, str_replace(["{{FIRST_NAME}}","{{BULK_ORDER_LINK}}","{{DASHBOARD_LINK}}"],[$ownerInfo['0']['first_name'],$bulk_order_link,common::CUSTOMER_ADMIN_DASHBOARD_URL],$emailData[0]['body']), $attachment);

		$sql = 'SELECT * FROM store_master WHERE id="'.$storeMasterId.'"';
        $store_data = parent::selectTable_f_mdl($sql);

		$store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
		$store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;

		$mailSendStatus = 1;
		//if($store_data[0]['email_notification'] == '1'){
			$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, str_replace(["{{FIRST_NAME}}","{{STORE_NAME}}","{{BULK_ORDER_LINK}}","{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$ownerInfo['0']['first_name'],$store_data[0]['store_name'],$bulk_order_link,common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date],$emailData[0]['body']), str_replace(["{{FIRST_NAME}}","{{STORE_NAME}}","{{BULK_ORDER_LINK}}","{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$ownerInfo['0']['first_name'],$store_data[0]['store_name'],$bulk_order_link,common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date],$emailData[0]['body']));
		//}
		/*send mail store manager*/
		$store_owner_details_master_id=$ownerInfo[0]['store_owner_id'];
		$sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_details_master_id . '"';
		$smm_data =  parent::selectTable_f_mdl($sql_managerData);
		if(!empty($smm_data)){
			foreach ($smm_data as $managerData) {
				$to_email   = $managerData['email'];
				$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, str_replace(["{{FIRST_NAME}}","{{STORE_NAME}}","{{BULK_ORDER_LINK}}","{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$managerData['first_name'],$store_data[0]['store_name'],$bulk_order_link,common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date],$emailData[0]['body']), str_replace(["{{FIRST_NAME}}","{{STORE_NAME}}","{{BULK_ORDER_LINK}}","{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$managerData['first_name'],$store_data[0]['store_name'],$bulk_order_link,common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date],$emailData[0]['body']));
			}
		}
		/*send mail store manager end*/
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

	function sendPurchaserEmail($shop_order_number,$email,$name,$emailTemplateId,$storeMasterId){
		$s3Obj = new Aws3;
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
		$shop_order_number = "#".$shop_order_number;
		$to_email = $email;
		$from_email = common::AWS_ADMIN_EMAIL;
		$attachment = [];

		//$mailSendStatus = $objAWS::sendEmail($from_email, $to_email, $subject, str_replace("{{FIRST_NAME}}",$name,$emailData[0]['body']), $attachment);

		$sql = 'SELECT * FROM store_master WHERE id="'.$storeMasterId.'"';
        $store_data = parent::selectTable_f_mdl($sql);

		$store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
		$store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;

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
	
	function create_draft_order(){
		$s3Obj = new Aws3;
		require_once(common::EMAIL_REQUIRE_URL);
		if(strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp')!==false){
			$objAWS = new aws_ses_smtp();
		}else if(strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail')!==false){
			$objAWS = new sendGridEmail();
		}else{
			$objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
		}

		if(isset($_POST['shop']) && !empty($_POST['shop']) && isset($_POST['var_ids']) && !empty($_POST['var_ids'])){
			$shop = $_POST['shop'];
			$var_ids_arr = array_unique(explode(',',$_POST['var_ids']));
			$product_count = count($var_ids_arr);
			$revArr = array_reverse($var_ids_arr); #reversing the array
			$var_ids = implode(",", $revArr);

			$sql = 'SELECT `store_owner_product_master`.store_master_id,`store_owner_product_master`.group_name,store_owner_product_variant_master.id as store_owner_prod_ver_id,store_owner_product_variant_master.fundraising_price,store_owner_product_variant_master.price as flash_sale_price,store_owner_product_variant_master.price_on_demand as on_demand_price,store_owner_product_variant_master.shop_variant_id as shop_variant_id
			FROM `store_owner_product_variant_master`
			LEFT JOIN `store_owner_product_master` ON `store_owner_product_master`.id = `store_owner_product_variant_master`.store_owner_product_master_id
			WHERE `store_owner_product_variant_master`.shop_variant_id IN ('.$var_ids.')';
			
			$sopm_data = parent::selectTable_f_mdl($sql);
			
			$generalSettingInfo = parent::getGeneralSettingsInfo_f_mdl();

			if(count($sopm_data) == $product_count){
				$store_ids = array_column($sopm_data,'store_master_id');
				$store_ids = array_unique($store_ids);
				if(count($store_ids)==1){
					$store_master_id = $store_ids[0];
					$cartinfo = html_entity_decode($_POST['cart'], ENT_QUOTES);
					$cartinfo = json_decode($cartinfo, 1);
					if (isset($cartinfo['items']) && !empty($cartinfo['items'])) {
						// fetch shoptoken of arrival shop from our DB
						$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
						if(!empty($shop_data)) {
							require_once('lib/class_graphql.php');
							require_once('lib/shopify.php');
							require_once('lib/php-shopify-sdk/vendor/autoload.php');

							$shop = $shop_data[0]['shop_name'];
							$token = $shop_data[0]['token'];

							$config = array(
								'ShopUrl' => $shop,
								'AccessToken' => $token,
							);
							$shopify = new PHPShopify\ShopifySDK($config);

							$headers = array(
								'X-Shopify-Access-Token' => $token
							);
							$graphql = new Graphql($shop, $headers);

							//fetch store-details
							$sql = 'SELECT store_sale_type_master_id, store_fulfillment_type,store_name,id,store_shipping_status FROM `store_master`
							WHERE `store_master`.id="'.$store_master_id.'"
							';
							$sm_data = parent::selectTable_f_mdl($sql);

							$b_address1 = $b_address2 = $b_city = $b_company = $b_countryCode = $b_firstName = $b_lastName = $b_province = $b_zip = '';
							$s_address1 = $s_address2 = $s_city = $s_company = $s_countryCode = $s_firstName = $s_lastName = $s_province = $s_zip = '';
							if($sm_data[0]['store_fulfillment_type']!='SHIP_EACH_FAMILY_HOME'){
								//only for silver and gold store, we need to set address
								//fetch billing store-address
								$sql = 'SELECT `store_owner_address_master`.*, `store_owner_details_master`.first_name, `store_owner_details_master`.last_name FROM `store_owner_address_master`
								LEFT JOIN `store_owner_details_master` ON `store_owner_details_master`.id = `store_owner_address_master`.store_owner_details_master_id
								WHERE `store_owner_address_master`.store_master_id="'.$store_master_id.'"
								LIMIT 1
								';
								$soam_data = parent::selectTable_f_mdl($sql);
								if(!empty($soam_data)){
									$b_address1 = '';
									$b_address2 = '';
									$b_city = '';
									$b_company = '';
									$b_countryCode = '';
									$b_firstName = '';
									$b_lastName = '';
									$b_province = '';
									$b_zip = '';
								}

								//fetch shipping store-address
								$sql='SELECT * FROM `store_owner_silver_delivery_address_master` inner join store_master on store_master.id=`store_owner_silver_delivery_address_master`.store_master_id 
									WHERE `store_owner_silver_delivery_address_master`.store_master_id="'.$store_master_id.'" AND store_master.store_sale_type_master_id=1 LIMIT 1';
								$sosdam_data = parent::selectTable_f_mdl($sql);
								$s_address1='';
								$s_address2='';
								$s_city='';
								$s_company='';
								$s_countryCode='';
								$s_firstName='';
								$s_lastName='';
								$s_province='';
								$s_zip='';
								if(!empty($sosdam_data)){
									$s_address1 = $sosdam_data[0]['address_line_1'];
									$s_address2 = $sosdam_data[0]['address_line_2'];
									$s_city = $sosdam_data[0]['city'];
									$s_company = $sosdam_data[0]['company_name'];
									$s_countryCode = $sosdam_data[0]['country'];
									$s_firstName = '';
									$s_lastName = '';
									$s_province = $sosdam_data[0]['state'];
									$s_zip = $sosdam_data[0]['zip_code'];
								}
							}

							$items_string = '';
							$num = 1;
							foreach ($cartinfo['items'] as $single_item) {
								//"taxable": false,
								$items_string .= '{
                                    "quantity": ' . $single_item['quantity'] . ',
                                    "variantId": "gid://shopify/ProductVariant/' . $single_item['variant_id'] . '",
                                    "customAttributes" : [';

								$sip = '';
								if (isset($single_item['properties']) && !empty($single_item['properties'])) {
									foreach ($single_item['properties'] as $sp_key => $sp_val) {
										$sip .= '{"key":"' . $sp_key . '","value":"' . $sp_val . '"},';
									}
								}
								$sip = trim($sip, ',');
								$items_string .= $sip . ']';
								$items_string .= '},';
								$num++;
							}
							$items_string = trim($items_string, ',');

							$shippingLine = '';
							if(isset($sm_data[0]['store_shipping_status']) && $sm_data[0]['store_shipping_status']=='No'){
								$shippingLine = '{ "price":0.00, "title":"Free Shipping" }';
							}else{
								if(isset($sm_data[0]['store_sale_type_master_id']) && $sm_data[0]['store_sale_type_master_id']=='1'){
									//flash sale store

									if($sm_data[0]['store_fulfillment_type']=='SHIP_1_LOCATION_NOT_SORT'){
										//silver
										if($generalSettingInfo[0]['shipping_fs_silver_price'] > 0){
											$shippingLine = '{
												"price":'.$generalSettingInfo[0]['shipping_fs_silver_price'].',
												"title":"'.$generalSettingInfo[0]['shipping_fs_silver_title'].'"
											}';
										}else{
											$shippingLine = '{ "price":0.00, "title":"Free Shipping" }';
										}
									}else if($sm_data[0]['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
										//gold
										if($generalSettingInfo[0]['shipping_fs_gold_price'] > 0){
											$shippingLine = '{
												"price":'.$generalSettingInfo[0]['shipping_fs_gold_price'].',
												"title":"'.$generalSettingInfo[0]['shipping_fs_gold_title'].'"
											}';
										}else{
											$shippingLine = '{ "price":0.00, "title":"Free Shipping" }';
										}
									}else if($sm_data[0]['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
										//platinum
										//here logic is like that - first item has separate shipping, then remain all has additional shipping
										//ex. we have 5 item then shipping = 6+2+2+2+2 = 6+(2*4) = 6+(2*(5-1))
										// $total_item = count($cartinfo['items']);
										$item_count = $cartinfo['item_count'];
										$total_platinum_shipping = 0;
										if($generalSettingInfo[0]['shipping_fs_platinum_first_item_price'] > 0){
											$total_platinum_shipping += $generalSettingInfo[0]['shipping_fs_platinum_first_item_price'];
										}
										// if($total_item-1>0 && $generalSettingInfo[0]['shipping_fs_platinum_additional_item_price'] > 0){
										// 	$total_platinum_shipping += ($total_item-1) * $generalSettingInfo[0]['shipping_fs_platinum_additional_item_price'];
										// }

										if($item_count-1>0 && $generalSettingInfo[0]['shipping_fs_platinum_additional_item_price'] > 0){
											$additionItemPrice = (float)(!empty($generalSettingInfo[0]['shipping_fs_platinum_additional_item_price']))?$generalSettingInfo[0]['shipping_fs_platinum_additional_item_price']:0;
											$total_platinum_shipping += ($item_count-1)*$additionItemPrice;
										}

										if($total_platinum_shipping > 0){
											$shippingLine = '{
												"price":'.$total_platinum_shipping.',
												"title":"'.$generalSettingInfo[0]['shipping_fs_platinum_title'].'"
											}';
										}else{
											$shippingLine = '{ "price":0.00, "title":"Free Shipping" }';
										}
									}

								}
								else if(isset($sm_data[0]['store_sale_type_master_id']) && $sm_data[0]['store_sale_type_master_id']=='2'){
									//on-demand store, here free shipping for $75 or more
									$count=0;
									$length=count($sopm_data) -1;
									$on_demand_price_yerd_sign =0;
									$on_demand_qty_yerd_sign=0;
									foreach ($sopm_data as $single_prod) {
										$group_name=$single_prod['group_name'];
										$store_owner_prod_ver_id=$single_prod['store_owner_prod_ver_id'];
										$fundraising_price=$single_prod['fundraising_price'];
										$on_demand_price=$single_prod['on_demand_price'];
										$shop_variant_id=$single_prod['shop_variant_id'];
										$prod_price=($on_demand_price+$fundraising_price);
										$prod_price=$prod_price * 100;
										
										foreach ($cartinfo['items'] as $cartitem_id) {
											if($shop_variant_id==$cartitem_id['id']){
												if($group_name=='Yard Signs'){
													$prod_quantity=$cartitem_id['quantity'];
													$on_demand_qty_yerd_sign  	+= $prod_quantity;
													$cartinfo['total_price'] = $cartinfo['total_price'] - ($prod_price * $prod_quantity) ;
												}
											}
										}
										// if($shop_variant_id==$cartinfo['items'][$length]['id']){
											
											// if($group_name=='Yard Signs'){
											// 	$on_demand_qty_yerd_sign  	+= $prod_quantity;
											// 	echo "==>".$on_demand_qty_yerd_sign."===<";
											// 	echo "==qty>".$prod_quantity;

											// 	$cartinfo['total_price'] = $cartinfo['total_price'] - $prod_price ;
											// }
										// }
										$count ++;
										$length--;
									}

									if($cartinfo['total_price'] >= ($generalSettingInfo[0]['price_limit_shipping_on_demand']*100)){	//here total is in cent, so we multiply other amount with 100
										$total_shipping_ondemand = 0.0;
										if($on_demand_qty_yerd_sign > 0){

											$total_shipping_ondemand += $generalSettingInfo[0]['shipping_yardsign_first_item_price'];

											$additionItemPrice = (float)(!empty($generalSettingInfo[0]['shipping_yardsign_additional_item_price']))?$generalSettingInfo[0]['shipping_yardsign_additional_item_price']:0;


											$total_shipping_ondemand += ($on_demand_qty_yerd_sign-1)*$additionItemPrice;

											if($total_shipping_ondemand > 0){
												
												$shippingLine = '{
													"price": '.$total_shipping_ondemand.',
													"title":"'.$generalSettingInfo[0]['shipping_on_demand_title'].'"
												}';
											}else{
												$shippingLine = '{ "price":0.00, "title":"Free Shipping" }';
											}
										}else{
											$shippingLine = '{ "price":0, "title":"Free Shipping" }';
										}

										
									}else{
										$total_shipping_ondemand = 0;


										if($on_demand_qty_yerd_sign > 0){

											if($generalSettingInfo[0]['shipping_yardsign_first_item_price'] > 0){
												$total_shipping_ondemand += $generalSettingInfo[0]['shipping_yardsign_first_item_price'];
											}

											$additionItemPrice = (float)(!empty($generalSettingInfo[0]['shipping_yardsign_additional_item_price']))?$generalSettingInfo[0]['shipping_yardsign_additional_item_price']:0;

											$total_shipping_ondemand += ($on_demand_qty_yerd_sign-1)*$additionItemPrice;

											if($cartinfo['total_price'] > 0){
												$total_shipping_ondemand += $generalSettingInfo[0]['shipping_on_demand_price'] ;
											}

											if($total_shipping_ondemand > 0){
												$shippingLine = '{
													"price": '.$total_shipping_ondemand.',
													"title":"'.$generalSettingInfo[0]['shipping_on_demand_title'].'"
												}';
											}else{
												$shippingLine = '{ "price":0.00, "title":"Free Shipping" }';
											}
										}else{
											$shippingLine = '{
												"price": '.$generalSettingInfo[0]['shipping_on_demand_price'].',
												"title":"'.$generalSettingInfo[0]['shipping_on_demand_title'].'"
											}';
										}
									}
								}
							}

							/*Coupon code SA Start*/
							$appliedDiscount = '';
							$result=[];
							if(isset($_POST['custom_coupon_code']) && !empty($_POST['custom_coupon_code'])){
								//FIXED_AMOUNT, PERCENTAGE
								$code = $_POST['custom_coupon_code'];
								// print_r($code);
								$sql='SELECT * FROM `coupon_code_series_master` WHERE coupon_code="'.$code.'" ';
					    		$ccsm_data = parent::selectTable_f_mdl($sql);
					    		if(!empty($ccsm_data)){
					    			$coupon_code_master_id=$ccsm_data[0]['coupon_code_master_id'];
					    			$sql='SELECT id,discount_type,discount_code,discount_value,discount_price,minimum_purchase,minimum_purchase_value,discount_code_limit,apply_for,discount_code_start_date,discount_code_end_date,discount_status,apply_storetype,applied_store_checkbox FROM `coupon_code_master` WHERE discount_status="0" AND id="'.$coupon_code_master_id.'" ';
					    		}else{
					    			$sql='SELECT id,discount_type,discount_code,discount_value,discount_price,minimum_purchase,minimum_purchase_value,discount_code_limit,apply_for,discount_code_start_date,discount_code_end_date,discount_status,apply_storetype,applied_store_checkbox FROM `coupon_code_master` WHERE discount_status="0" AND discount_code="'.$code.'" ';
					    		}

    							$ccm_data = parent::selectTable_f_mdl($sql);
    							if (!empty($ccm_data)) {
									$discount_code_id=$ccm_data[0]['id'];
									$discount_type=$ccm_data[0]['discount_type'];
									$discount_code=$ccm_data[0]['discount_code'];
									$discount_value=$ccm_data[0]['discount_value'];
									$discount_price=$ccm_data[0]['discount_price'];
									$discount_code_limit=$ccm_data[0]['discount_code_limit'];
									$discount_code_start_date=$ccm_data[0]['discount_code_start_date'];
									$discount_code_end_date=$ccm_data[0]['discount_code_end_date'];
									$apply_storetype=$ccm_data[0]['apply_storetype'];
									$applied_store_checkbox=$ccm_data[0]['applied_store_checkbox'];
									if($discount_value=='0'){
										$discount_type='percentage';
									}else{
										$discount_type='fixed_amount';
									}

									
									$finalRes[strtolower($code)] = array(
										'code' => $code,
										'value' => trim($discount_price),
										'type' => $discount_type,
										'target_type' => 'line_item',
									);

									if($discount_type=='percentage'){
										$valueType = 'PERCENTAGE';
										if(isset($_POST['applied_discount_amount'])){
											$appliedDiscount = '"appliedDiscount" : {
												"amount" : '.floatval($_POST['applied_discount_amount']).',
												"title" : "'.$code.'",
												"value" : '.trim($discount_price).',
												"valueType" : "'.$valueType.'"
											},';
										}
									}else{
										$valueType = 'FIXED_AMOUNT';
										if(isset($_POST['applied_discount_amount'])){
											$appliedDiscount = '"appliedDiscount" : {
												"amount" : '.trim($_POST['applied_discount_amount']).',
												"title" : "'.$code.'",
												"value" : '.trim($_POST['applied_discount_amount']).',
												"valueType" : "'.$valueType.'"
											},';
										}
									}
								}

							}
							/*Coupon code SA End */

							// $appliedDiscount = '';
							// if(isset($_POST['custom_coupon_code']) && !empty($_POST['custom_coupon_code'])){
							// 	//FIXED_AMOUNT, PERCENTAGE
							// 	$code = $_POST['custom_coupon_code'];

							// 	$descountURL = $shopify->getAdminUrl() . 'discount_codes/lookup.json?code=' . urlencode($code);
							// 	$httpHeaders['X-Shopify-Access-Token'] = $token;
							// 	// @$responseUrl = \PHPShopify\CurlRequest::get($descountURL, $httpHeaders);
							// 	$curl = curl_init();
							// 	curl_setopt_array($curl, array(
							// 	  CURLOPT_URL => $descountURL,
							// 	  CURLOPT_RETURNTRANSFER => true,
							// 	  CURLOPT_ENCODING => '',
							// 	  CURLOPT_MAXREDIRS => 10,
							// 	  CURLOPT_TIMEOUT => 0,
							// 	  CURLOPT_FOLLOWLOCATION => true,
							// 	  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
							// 	  CURLOPT_CUSTOMREQUEST => 'GET',
							// 	  CURLOPT_HTTPHEADER => array(
							// 	    'X-Shopify-Access-Token: '.$token.''
							// 	  ),
							// 	));
							// 	$responseUrl = curl_exec($curl);
							// 	if ($responseUrl != '') {
							// 		// preg_match_all('~<a(.*?)href="([^"]+)"(.*?)>~', $responseUrl, $matches,PREG_PATTERN_ORDER);
							// 		// if (!empty($matches[2])) {
							// 		// 	if (strpos($matches[2][0], '.json') !== false) {
							// 		// 		$result = PHPShopify\HttpRequestJson::get($matches[2][0], $httpHeaders);
							// 		// 	}else{
							// 		// 		$result = PHPShopify\HttpRequestJson::get($matches[2][0].'.json', $httpHeaders);
							// 		// 	}
							// 			$result    =  json_decode($responseUrl,true);
							// 			if (!empty($result) && is_array($result)) {
							// 				$priceRule = new \PHPShopify\PriceRule($result['discount_code']['price_rule_id']);
							// 				$priceRuleRes = $priceRule->get();
							// 				if (!empty($priceRuleRes) && is_array($priceRuleRes)) {
							// 					$finalRes[strtolower($result['discount_code']['code'])] = array(
							// 						'code' => $result['discount_code']['code'],
							// 						'value' => ltrim($priceRuleRes['value'], '-'),
							// 						'type' => $priceRuleRes['value_type'],
							// 						'target_type' => $priceRuleRes['target_type'],
							// 					);
							// 					if($priceRuleRes['value_type']=='percentage'){
							// 						$valueType = 'PERCENTAGE';
							// 						if(isset($_POST['applied_discount_amount'])){
							// 							$appliedDiscount = '"appliedDiscount" : {
							// 								"amount" : '.floatval($_POST['applied_discount_amount']).',
							// 								"title" : "'.$result['discount_code']['code'].'",
							// 								"value" : '.ltrim($priceRuleRes['value'], '-').',
							// 								"valueType" : "'.$valueType.'"
							// 							},';
							// 						}
							// 					}else{
							// 						$valueType = 'FIXED_AMOUNT';
							// 						if(isset($_POST['applied_discount_amount'])){
							// 							$appliedDiscount = '"appliedDiscount" : {
							// 								"amount" : '.ltrim($priceRuleRes['value'], '-').',
							// 								"title" : "'.$result['discount_code']['code'].'",
							// 								"value" : '.floatval($_POST['applied_discount_amount']).',
							// 								"valueType" : "'.$valueType.'"
							// 							},';
							// 						}
							// 					}
							// 				}
							// 			}
							// 		// }
							// 	}
							// }

							if ($items_string != '') {
								$customer_data = '';
								if(isset($_POST['customer_id']) && !empty($_POST['customer_id'])){
									$customer_data = '"customerId": "gid://shopify/Customer/' . $_POST['customer_id'] . '",';
								}

								$cartCustomAttributes = '';
								if(isset($_POST['student_name']) && !empty($_POST['student_name'])){
									$cartCustomAttributes .= '{"key":"student_name","value":"'.$_POST['student_name'].'"},';
								}
								if(isset($_POST['sort_list_name']) && !empty($_POST['sort_list_name'])){
									$stringWord = ucfirst($_POST['sort_list_name']);
									$removedSpace = ucwords(strtolower($stringWord));
									$sortListName = preg_replace('/\s+/', ' ', $removedSpace);
									$cartCustomAttributes .= '{"key":"sort_list_name","value":"'.$sortListName.'"},';
								}
								if(isset($_POST['sort_option_b64']) && !empty($_POST['sort_option_b64'])){
									$sort_option_arr = json_decode(base64_decode($_POST['sort_option_b64']),1);
									if(!empty($sort_option_arr)){
										foreach($sort_option_arr as $k=>$v){
											if($v!=''){
												$cartCustomAttributes .= '{"key":"'.$k.'","value":"'.$v.'"},';
											}
										}
									}
								}

								/*if(isset($_POST['option_text']) && !empty($_POST['option_text'])){
									$cartCustomAttributes .= '{"key":"option_text","value":"'.$_POST['option_text'].'"},';
								}
								if(isset($_POST['option_radio']) && !empty($_POST['option_radio'])){
									$cartCustomAttributes .= '{"key":"option_radio","value":"'.$_POST['option_radio'].'"},';
								}
								if(isset($_POST['option_checkbox']) && !empty($_POST['option_checkbox'])){
									$cartCustomAttributes .= '{"key":"option_checkbox","value":"'.$_POST['option_checkbox'].'"},';
								}
								if(isset($_POST['option_dropdown']) && !empty($_POST['option_dropdown'])){
									$cartCustomAttributes .= '{"key":"option_dropdown","value":"'.$_POST['option_dropdown'].'"},';
								}*/
								$cartCustomAttributes = trim($cartCustomAttributes,',');

								if($b_countryCode==''){
									$b_countryCode = 'US';
								}
								if($s_countryCode==''){
									$s_countryCode = 'US';
								}

								$qry_input = '
								{
                                  "input": {
                                  	'.$customer_data.'
                                    "lineItems": [' . $items_string . '],
                                    "taxExempt" : false,'; //"taxExempt" : true,';

								if($sm_data[0]['store_fulfillment_type']!='SHIP_EACH_FAMILY_HOME'){
									//only for silver and gold store, we need to set address
									$qry_input .= '"shippingAddress" : {
                                    	"address1" : "'.$s_address1.'",
                                    	"address2" : "'.$s_address2.'",
                                    	"city" : "'.$s_city.'",
                                    	"company" : "'.$s_company.'",
                                    	"countryCode" : "'.$s_countryCode.'",
                                    	"firstName" : "'.$s_firstName.'",
                                    	"lastName" : "'.$s_lastName.'",
                                    	"province" : "'.$s_province.'",
                                    	"zip" : "'.$s_zip.'"
                                    },
                                    "billingAddress" : {
                                    	"address1" : "'.$b_address1.'",
                                    	"address2" : "'.$b_address2.'",
                                    	"city" : "'.$b_city.'",
                                    	"company" : "'.$b_company.'",
                                    	"countryCode" : "'.$b_countryCode.'",
                                    	"firstName" : "'.$b_firstName.'",
                                    	"lastName" : "'.$b_lastName.'",
                                    	"province" : "'.$b_province.'",
                                    	"zip" : "'.$b_zip.'"
                                    },';
								}

								$storeId = (isset($sm_data[0]['id']) && !empty($sm_data[0]['id']))?$sm_data[0]['id']:0;
								$qry_input .= ''.$appliedDiscount.'
                                    "shippingLine" : '.$shippingLine.',
									"tags": [ "SpiritHero SA '.$storeId.'" ],
                                    "customAttributes": ['.$cartCustomAttributes.']
                                  }
                                }
                                ';

								$qry_mutation = '
                                mutation draftOrderCreate($input: DraftOrderInput!) {
                                  draftOrderCreate(input: $input) {
                                    userErrors {
                                      field
                                      message
                                    }
                                    draftOrder {
                                      id
                                      invoiceUrl
                                    }
                                  }
                                }
                                ';

								$gr = $graphql->runByMutation($qry_mutation, $qry_input);
								// print_r($gr);die;
								$sql = 'SELECT subject,body,recipients FROM `email_templates_master` WHERE id="41" ';
								$et_data = parent::selectTable_f_mdl($sql);
								$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
								$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
								$file_name="process_on_request_ctl.php";
								if(empty($gr['data']['draftOrderCreate']['draftOrder']['invoiceUrl'])){
									$error_msg="invoiceUrl not generated by sopify side";
									if(!empty($et_data)){
										$subject = $et_data[0]['subject'];
										$body    = $et_data[0]['body'];
										$ccMails = '';
										if($et_data[0]['recipients']){
											$recipients = $et_data[0]['recipients'];
											$recipients = str_replace(' ', '', $recipients);
											$ccMails    = explode(',', $recipients);
										}
										$to_email   = common::SUPER_ADMIN_EMAIL;
										$from_email = common::AWS_ADMIN_EMAIL;
										$attachment = [];
										$body = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
										$body = str_replace('{{API_ERROR_MSG}}',$error_msg,$body);
										$body = str_replace('{{FILE_NAME}}',$file_name, $body);
										$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body,$ccMails);
									}
								}

								if (isset($gr['data']['draftOrderCreate']['draftOrder']['invoiceUrl']) && !empty($gr['data']['draftOrderCreate']['draftOrder']['invoiceUrl'])) {
									$res['SUCCESS'] = 'TRUE';
									$res['MESSAGE'] = '';
									$res['invoiceUrl'] = $gr['data']['draftOrderCreate']['draftOrder']['invoiceUrl'].'?checkout[shipping_address][address1]='.$s_address1.'&checkout[shipping_address][address2]='.$s_address2.'&checkout[shipping_address][city]='.$s_city.'&checkout[shipping_address][company]='.$s_company.'&checkout[shipping_address][country]='.$s_countryCode.'&checkout[shipping_address][first_name]='.$s_firstName.'&checkout[shipping_address][last_name]='.$s_lastName.'&checkout[shipping_address][province]='.$s_province.'&checkout[shipping_address][zip]='.$s_zip;
								} else {
									$res['SUCCESS'] = 'FALSE';
									$res['MESSAGE'] = 'Oh no!  There seems to be an error on our end.  Please contact us at 800-239-9948 or email us at support@spirithero.com.';
								}
							} else {
								$res['SUCCESS'] = 'FALSE';
								$res['MESSAGE'] = 'Something went wrong. Please try again.';
							}
						} else {
							$res['SUCCESS'] = 'FALSE';
							$res['MESSAGE'] = 'Shop is Invalid.';
						}
					} else {
						$res['SUCCESS'] = 'FALSE';
						$res['MESSAGE'] = 'Cart data is not found.';
					}
				}else{
					$res['SUCCESS'] = 'FALSE';
					$res['MESSAGE'] = 'Your cart has multiple products from different stores. Please remove some items and make single store product.';
				}
			}else{
				$res['SUCCESS'] = 'FALSE';
				$res['MESSAGE'] = 'Some product is not from valid store. Please choose proper products.';
			}
		}else{
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'Invalid request.';
		}
		echo json_encode($res,1);
	}

	function verifyShopifyStore()
	{	
		$res = [];
		$res['status'] = true;
		if(isset($_POST['productid'])){
			$sql ="SELECT 
			    sopvm.store_owner_product_master_id,
			    sopvm.shop_product_id,
			    sopm.store_master_id,
			    sm.production_date,
			    sm.status,
			    sm.verification_status
			FROM
			    `store_owner_product_variant_master` AS sopvm
			        LEFT JOIN
			    `store_owner_product_master` AS sopm ON sopm.id = sopvm.store_owner_product_master_id
					LEFT JOIN
			     `store_master` as sm ON sm.id = sopm.store_master_id 
			WHERE
			    sopvm.shop_product_id = '".$_POST['productid']."' AND sm.status = '0' AND (sm.production_date IS NOT NULL OR sm.production_date!='')
			GROUP BY sopvm.shop_product_id";
			$storeData = parent::selectTable_f_mdl($sql);
			if(!empty($storeData[0])){
				$closeStoreDetail = $storeData[0];
				if(!empty($closeStoreDetail['production_date'])){
					$res['status'] = false;
				} elseif ($closeStoreDetail['status'] == '0') {
					$res['status'] = false;
				}
			}
		}
		echo common::sendJson($res,1);
	}
}
