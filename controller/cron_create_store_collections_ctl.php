<?php
//http://app.spirithero.com/cron-create-store-collections.php
include_once 'model/cron_create_store_collections_mdl.php';
include_once $path . '/libraries/Aws3.php';
/* Task 59 */
$path  = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';
$s3Obj = new Aws3;
/* Task 59 */

// Task 87 start
include $path.'/lib/generate_qr_code.php';
$qrcodeObj = new generate_qr_code();
// Task 87 end

class cron_create_store_collections_ctl extends cron_create_store_collections_mdl
{
	function __construct(){
		$this->startCollectionCreateJob();
	}

	function startCollectionCreateJob(){

		//update status = 1 and strat time 

		global $s3Obj;
		global $qrcodeObj;
		//Check email only testing purpose
		require_once("lib/class_aws.php");
	    $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
	    //$mailSendStatus = $objAWS->sendEmail(["sanjay@bitcot.com"], "startCollectionCreateJob", "Message: startCollectionCreateJob", "Message: startCollectionCreateJob");

		// require_once(common::EMAIL_REQUIRE_URL);
		// $mailAWS = '';
		// if(strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp')!==false){
		// 	$mailAWS = new aws_ses_smtp();
		// }else{
		// 	$mailAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
		// }
	    //end Check email..
	    
		#region - Fetch Collection Create Request
		//die("ssssss");
		$storeCollectionInfo = parent::getStoreCollectionInfo_f_mdl();
		#endregion			
		if(count($storeCollectionInfo) > 0){
			$logFileOpen = fopen("logs.txt", "a+") or die("Unable to open file!");
			$errorText = "</br></br></br>";
			$errorText .= "----------------------------------------------------------------------------";
			$errorText .= "cron start time ".date("m/d/Y h:i A");
			$errorText .= "</br></br></br>";
			fwrite($logFileOpen, $errorText);
			unset($errorText);

			$storeMasterId = $storeCollectionInfo[0]["store_master_id"];
			$storeOwnerDetailsMasterId = $storeCollectionInfo[0]["store_owner_details_master_id"];
			$storeOrganizationTypeMasterId = $storeCollectionInfo[0]["store_organization_type_master_id"];
			$frontSideInkColors = $storeCollectionInfo[0]["front_side_ink_colors"];
			$backSideInkColors = $storeCollectionInfo[0]["back_side_ink_colors"];
			$storeFulfillmentType = $storeCollectionInfo[0]["store_fulfillment_type"];
			$storeSaleTypeMasterId = $storeCollectionInfo[0]["store_sale_type_master_id"];
			$isFundRaising = $storeCollectionInfo[0]["is_fundraising"];
			$storeName = $storeCollectionInfo[0]["store_name"];
			$product_name_identifier = $storeCollectionInfo[0]["product_name_identifier"];
			$storeDescription = str_replace('"','',$storeCollectionInfo[0]["store_description"]);
			$storeOpenDate = $storeCollectionInfo[0]["store_open_date"];
			$storeCloseDate = $storeCollectionInfo[0]["store_close_date"];
			$shopCollectionId = $storeCollectionInfo[0]["shop_collection_id"];
			$isCollectionCreated = $storeCollectionInfo[0]["is_collection_created"];
			$isProductsSynced = $storeCollectionInfo[0]["is_products_synced"];
			
			#region - Collection Handle
			$storeNameArray = explode(" ", $storeName);
			$storeHanlde = $storeNameArray[0];
			#endregion
			
			#region - Get Store Info
			$storeInfo = parent::getStoreInfo_f_mdl();
			#endregion
			
			#region - Split & Get Store TimeZone
			$storeTimeZone = $storeInfo[0]["timezone"];
			$firstSplittedArray = explode(" ", $storeTimeZone);
			$firstIndexVal = $firstSplittedArray[0];
			
			$plusMinusSign = "";
			$firstIndexSplittedArray = array();
			if((stristr($firstIndexVal, '-'))){
				$firstIndexSplittedArray = explode("-", $firstIndexVal);
				$plusMinusSign = "-";
			}
			else if((stristr($firstIndexVal, '+'))){
				$firstIndexSplittedArray = explode("+", $firstIndexVal);
				$plusMinusSign = "+";
			}

			/* This function use for creating customer in printavo */
			self::createCustomerInPrintavo($storeOwnerDetailsMasterId);

			$collectionPublishDate = $storeOpenDate.$plusMinusSign.str_replace(")", "", $firstIndexSplittedArray[1]);
			#endregion
			
			#region - Initialize Shopify Class Object
			 require_once('lib/shopify.php');
			 require_once('lib/functions.php');

            $shopifyObject = new ShopifyClient($storeInfo[0]["shop_name"], $storeInfo[0]["token"], common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			
			require_once('lib/class_graphql.php');
			
			$headers = array(
				'X-Shopify-Access-Token' => $storeInfo[0]["token"]
			);
			$graphql = new Graphql($storeInfo[0]["shop_name"], $headers);
			#endregion
			
			#region - Create Collection
			create_collection_again:
			if(!$isCollectionCreated && $shopCollectionId == ""){
				$isAnyCollectionCreateError = false;
				try{
					$newCollectionArray = json_decode('{"custom_collection":{"title": "'.$storeName.'", "handle": "'.$storeHanlde.'",  "body_html": "'.$storeDescription.'", "published_at": "'.$collectionPublishDate.'"}}', true);
		
					$collectionInfo = $shopifyObject->call('POST', '/admin/api/2023-04/custom_collections.json', $newCollectionArray);
				
					$encodeJsonCollectionInfo = json_encode($collectionInfo);
					$decodeJsonCollectionInfo = json_decode($encodeJsonCollectionInfo);
					
					$shopCollectionId = $decodeJsonCollectionInfo->id;
					$shopCollectionHandle = $decodeJsonCollectionInfo->handle;

					// Task 87 start
					if(!empty($shopCollectionHandle)){
						$qrcodeObj->generateQrCode($storeName,$shopCollectionHandle,$storeMasterId);
						self::sendEmailToStoreOwner($storeOwnerDetailsMasterId,$storeName,$shopCollectionHandle,$storeMasterId);
					}
					// Task 87 end

					// Change Admin Rest API To GraphQL For Create Collection 
					/*$newCollectionMutation  = '
					mutation CollectionCreate($input: CollectionInput!){
						collectionCreate(input: $input){
							userErrors { field, message }
							collection {
								id
								title
								descriptionHtml
								handle
							}	 
						}
					}';
					$newCollectionInput = '{
						"input": {
							"title":"'.$storeName.'",
							"descriptionHtml":"'.$storeDescription.'"
						}
					}';
					$collectionCreationResponse = $graphql->runByMutation($newCollectionMutation, $newCollectionInput);
					//echo "<pre>";print_r($collectionCreationResponse);die("ddddddddd");
					$shopCollectionIdData = $collectionCreationResponse['data']['collectionCreate']['collection']['id'];
				    $shopCollectionIdArray = explode('/', $shopCollectionIdData);
				    $shopCollectionId = end($shopCollectionIdArray);
				    $shopCollectionHandle = $collectionCreationResponse['data']['collectionCreate']['collection']['handle'];

					
					if(isset($collectionCreationResponse['data']['collectionCreate']['userErrors']) && !empty($collectionCreationResponse['data']['collectionCreate']['userErrors'])){
						
						//error log into logs.txt file
						$errorText = "newCollectionArray ".print_r($collectionCreationResponse['data']['collectionCreate']['userErrors'], true);
						fwrite($logFileOpen, $errorText);
						unset($errorText);
						return false;
					}*/
				
					#region - Update Collection Id To DB
					parent::updateCollectionIdToDB_f_mdl($storeMasterId, $shopCollectionId, $shopCollectionHandle);
					#endregion
				}
				catch(ShopifyApiException $e){
					$storeHanlde = $storeHanlde.'1';
					goto create_collection_again;
					$isAnyCollectionCreateError = true;
                }
                catch(ShopifyCurlException $e){
					$storeHanlde = $storeHanlde.'1';
					goto create_collection_again;
					$isAnyCollectionCreateError = true;
                }

				#region - Update Collection Create Status To Store Master Table
				if(!$isAnyCollectionCreateError){
					parent::updateCollectionCreateStatus_f_mdl($storeMasterId);
				}
				#endregion
			}
			#endregion
			

			
			$isAllProductsCreated = true;
			$isAllproductsSyncedToCollect = true;
			
			
			#region - Create Products & Assign To Collection
			if(!$isProductsSynced){	
				#region - Get First 50 Products For Create
				$productsToCreateIntoStoreInfo = parent::getProductsToCreateIntoStore_f_mdl($storeMasterId);
				//echo "<pre>";print_r($productsToCreateIntoStoreInfo);die("yyyyyyyy");
				

				if(count($productsToCreateIntoStoreInfo) > 0){
					$mapedMasterIdArray = array();
					
					$isAllProductsCreated = false;
					$isAllproductsSyncedToCollect = false;

					foreach($productsToCreateIntoStoreInfo as $objProduct){
						$storeOwnerProductMasterId = $objProduct["store_owner_product_master_id"];
						$productTitle = $product_name_identifier."-".$objProduct["product_title"];
						$productDescription = str_replace('"', ' Inch', $objProduct["product_description"]);
						$productTags = $objProduct["tags"];
						$vendor = $objProduct["vendor_name"];

						#region - Get Product Variants
						$productWithVariantsInfo = parent::getProductVariantsInfo_f_mdl($storeOwnerProductMasterId);


						/*Cron For Product*/
						$sqlA = "SELECT sopvm.id as store_owner_product_variant_master_id, sopvm.shop_product_id, sopvm.shop_variant_id, sopvm.store_owner_product_master_id, sopvm.store_product_variant_master_id, sopvm.store_organization_type_master_id, sopvm.price, sopvm.price_on_demand, sopvm.fundraising_price, sopvm.color, sopvm.size, sopvm.image, sopvm.original_image, sopvm.sku,sopvm.weight FROM store_owner_product_variant_master sopvm WHERE sopvm.store_owner_product_master_id = '$storeOwnerProductMasterId' AND sopvm.shop_variant_id!='' AND sopvm.shop_variant_id!=0";
						$totalSyncVarients = parent::selectTable_f_mdl($sqlA);
						// echo "<pre>";print_r($totalSyncVarients);
						$sqlB = "SELECT sopvm.id as store_owner_product_variant_master_id, sopvm.shop_product_id, sopvm.shop_variant_id, sopvm.store_owner_product_master_id, sopvm.store_product_variant_master_id, sopvm.store_organization_type_master_id, sopvm.price, sopvm.price_on_demand, sopvm.fundraising_price, sopvm.color, sopvm.size, sopvm.image, sopvm.original_image, sopvm.sku,sopvm.weight FROM store_owner_product_variant_master sopvm WHERE sopvm.store_owner_product_master_id = '$storeOwnerProductMasterId' AND sopvm.shop_variant_id!=0";
						$TotalVariantB = parent::selectTable_f_mdl($sqlB);
						// print_r($TotalVariantB);die("yyyyyyyy");
						$count_flag = 0;
						if(count($totalSyncVarients) >= count($TotalVariantB) && count($totalSyncVarients) <= 100){
							$count_flag = 1;
						}else{
							if(count($TotalVariantB) > 100 && count($totalSyncVarients) == 100){
								$count_flag = 1;	
							}else if(count($TotalVariantB) > 100 && count($TotalVariantB) == count($totalSyncVarients) ){ /* task 32 start */
								$count_flag = 1;	
							} /* task 32 end */
						}

						/*$count_flag = 0;
						if(count($TotalVariantB) < 25){
							$count_flag = 1;							
						}else if(count($TotalVariantB) <= 100){
							if(count($totalSyncVarients) > 25){
								$count_flag = 1;
							}
						}else{
							if(count($totalSyncVarients) >= 100){
								$count_flag = 1;
							}
						}*/

						/*Cron For Product*/
						#endregion
						if(!empty($productWithVariantsInfo)){

							/*remove duplicate variants from array*/
							$productWithVariantsInfoNewArray = array();
							foreach ($productWithVariantsInfo as $productWithVariantInfo) {
								if ((array_search($productWithVariantInfo['size'], array_column($productWithVariantsInfoNewArray, 'size')) !== FALSE) AND array_search($productWithVariantInfo['color'], array_column($productWithVariantsInfoNewArray, 'color')) !== FALSE) {
								} else {
								  $productWithVariantsInfoNewArray[] = $productWithVariantInfo;
								}
							}
							$productWithVariantsInfo = $productWithVariantsInfoNewArray;
							
							#region - Collect All Variants Images To Upload
							$allVarImagesStr = "";
							$firstVarMasterId = 0;
							$firstVarTotalPrice = 0;
							$firstVarColorWithColorName = "";
							$variantColorArray = [];
							#endregion


							if(!empty($objProduct["shop_product_id"])){
								
								//product is already existed in shopify, only new variants are added in that
								// echo "<pre>"; print_r($productWithVariantsInfo);die("yyyyyyyy");
								foreach($productWithVariantsInfo as $objProdVar){

									if(empty($objProdVar["weight"])){
										$sqlForWeight = "SELECT weight FROM store_product_variant_master WHERE id = '".$objProdVar["store_product_variant_master_id"]."'";
										$getWeightOfProduct = parent::selectTable_f_mdl($sqlForWeight);
										if(!empty($getWeightOfProduct[0]['weight']))
										{
											$weight = $getWeightOfProduct[0]['weight'];
										}else{
											$weight = 0.00;
										}
									}
									else{
										$weight = $objProdVar["weight"];
									}

									if($storeSaleTypeMasterId==2){
										$tempVarTotalPrice = $objProdVar["price_on_demand"] + $objProdVar["fundraising_price"];
									}else{
										$tempVarTotalPrice = $objProdVar["price"] + $objProdVar["fundraising_price"];
									}
									
									#region - Get Color Code Name With Product Colors
									$colorName = parent::getColorCodeInfo_f_mdl($objProdVar["color"]);
									
									$varColorWithColorName = $colorName[0]['product_color_name'];
									#endregion 

									//create variant without image
									$newProductMutation = '
									mutation productVariantCreate($input: ProductVariantInput!) {
									  productVariantCreate(input: $input) {
										product {
										  id
										}
										productVariant {
										  id
										}
										userErrors {
										  field
										  message
										}
									  }
									}';
									$newProductInput = '{
									  "input": {
										"productId":"gid://shopify/Product/'.$objProduct["shop_product_id"].'",
										"price":"'.$tempVarTotalPrice.'",
										"sku":"'.$objProdVar["sku"].'",
										"weight":'.$weight.',
										"weightUnit":"OUNCES",
										"options": ["'.$objProdVar["size"].'","'.$varColorWithColorName.'"],
										"inventoryPolicy": "CONTINUE"
									  }
									}';

									$mocupImage = parent::commonMockups($objProdVar['store_owner_product_variant_master_id']);
									$variantImage = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$objProdVar["image"]);
									if(!empty($mocupImage)){
										$variantImage = $s3Obj->getAwsUrl(common::LOGO_MOCKUP_UPLOAD_S3_PATH.$storeMasterId.'/'.$mocupImage[0]["image"]);
									}

									sleep(0.5);
									$createdProductVarInfo = $graphql->runByMutation($newProductMutation, $newProductInput);
									if(isset($createdProductVarInfo['data']['productVariantCreate']['productVariant']['id'])){
										//now add image in product
										$mutation = 'mutation productAppendImages($input: ProductAppendImagesInput!) {
										  productAppendImages(input: $input) {
											newImages {
											  id
											}
											product {
											  id
											}
											userErrors {
											  field
											  message
											}
										  }
										}';
										$input = '{
										  "input": {
											"id": "gid://shopify/Product/'.$objProduct["shop_product_id"].'",
											"images": [
											  {"src":"'.$variantImage.'"}
											]
										  }
										}';
										sleep(0.5);
										$imgData = $graphql->runByMutation($mutation, $input);
										if(isset($imgData['data']['productAppendImages']['newImages'][0]['id']) && !empty($imgData['data']['productAppendImages']['newImages'][0]['id'])){
											//now assign that imageId to variant
											$mutation = 'mutation productVariantUpdate($input: ProductVariantInput!) {
											  productVariantUpdate(input: $input) {
												product {
												  id
												}
												productVariant {
												  id
												}
												userErrors {
												  field
												  message
												}
											  }
											}';
											$input = '{
											  "input": {
												"id": "'.$createdProductVarInfo['data']['productVariantCreate']['productVariant']['id'].'",
												"imageId": "'.$imgData['data']['productAppendImages']['newImages'][0]['id'].'"
											  }
											}';
											sleep(0.5);
											$graphql->runByMutation($mutation, $input);
										}
									
										parent::updateVariantIdToDB_f_mdl(
											$objProduct["shop_product_id"],
											str_replace('gid://shopify/ProductVariant/','',$createdProductVarInfo['data']['productVariantCreate']['productVariant']['id']),
											$objProdVar['store_owner_product_variant_master_id']
										);
									}
									else{ /* task 32 start */
										if(isset($createdProductVarInfo['data']['productVariantCreate']['userErrors']) && !empty($createdProductVarInfo['data']['productVariantCreate']['userErrors'])){
											//print_r($createdProductVarInfo['data']['productVariantCreate']['userErrors'][0]['message']);

											parent::updateVariantIdToDB_f_mdl(
												$objProduct["shop_product_id"],
												0,
												$objProdVar['store_owner_product_variant_master_id']
											);

											$errorText = "product variant created but not sync in DB product id =".$objProduct["shop_product_id"]." ==> ".print_r($createdProductVarInfo['data']['productVariantCreate']['userErrors'], true);
											fwrite($logFileOpen, $errorText);
											unset($errorText);

											$logFileOpen = fopen("shopify_prod_ver_logs.txt", "a+") or die("Unable to open file!");
											$errorTextver = "----------------------------------------------------------------------------";
											$errorTextver .= "cron start time ".date("m/d/Y h:i A");
											$errorTextver .= "----------------------------------------------------------------------------";
											$errorTextver .= "product variant created but not sync in DB product id =".$objProduct["shop_product_id"]." ==> ".print_r($createdProductVarInfo['data']['productVariantCreate']['userErrors'], true);
											fwrite($logFileOpen, $errorTextver);
											unset($errorTextver);
										}
									} /* task 32 end */
								}
							}else{

								$allVarsStr = "";
								if(count($productWithVariantsInfo) > 0){
									foreach($productWithVariantsInfo as $objProdVar){
										$mocupImage = parent::commonMockups($objProdVar['store_owner_product_variant_master_id']);
										$variantImage = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$objProdVar["image"]);
										if(!empty($mocupImage)){
											$variantImage = $s3Obj->getAwsUrl(common::LOGO_MOCKUP_UPLOAD_S3_PATH.$storeMasterId.'/'.$mocupImage[0]["image"]);
										}
										$allVarImagesStr .= '{"src":"'.$variantImage.'"},';

										#region - Set All Products Variants By Loop

										if($storeSaleTypeMasterId==2){
											$tempVarTotalPrice = $objProdVar["price_on_demand"] + $objProdVar["fundraising_price"];
										}else{
											$tempVarTotalPrice = $objProdVar["price"] + $objProdVar["fundraising_price"];
										}
										
										#region - Get Color Code Name With Product Colors
										$colorName = parent::getColorCodeInfo_f_mdl($objProdVar["color"]);
										
										$varColorWithColorName = $colorName[0]['product_color_name'];

										$variantColorArray[trim($varColorWithColorName)] =  trim($objProdVar["color"]);
										#endregion
										
										if(empty($objProdVar["weight"])){
											$weight = 0.00;
										}
										else{
											$weight = $objProdVar["weight"];
										}
										
										//$allVarsStr .= '{"price": "'.$tempVarTotalPrice.'", "sku": "'.$objProdVar["sku"].'","weight": '.$weight.',"weightUnit": "GRAMS","options": ["'.$objProdVar["size"].'","'.$varColorWithColorName.'"], "imageSrc": "'.common::IMAGE_UPLOAD_URL.$objProdVar["image"].'", "inventoryPolicy": "CONTINUE"},';
										
										$allVarsStr .= '{"price": "'.$tempVarTotalPrice.'", "sku": "'.$objProdVar["sku"].'","weight": '.$weight.',"weightUnit": "OUNCES","options": ["'.$objProdVar["size"].'","'.$varColorWithColorName.'"], "imageSrc": "'.$variantImage.'", "inventoryPolicy": "CONTINUE"},';
										
										#endregion
									}

									if($allVarsStr != ""){
										$allVarsStr = trim($allVarsStr, ",");
									}
								}
							    
								// $getProductInfo      = $shopifyObject->call('GET', '/admin/api/2023-04/collections/'.$shopCollectionId.'/products.json');
								// $productTitleShopify = '';
								// if (count($getProductInfo) > 0) {
								// 	$productTitleShopify = trim($getProductInfo[0]['title']);
								// }

							    /*$getProductInfo= $shopifyObject->call('GET', '/admin/api/2023-04/products.json?title='.urlencode($productTitle));
								$productTitleShopify = '';

								if (count($getProductInfo) > 0) {
									continue;
								}*/
								// create product handle
								/*$productHandle = str_replace("'", '', $productTitle);
								$productHandle = preg_replace("![^a-z0-9]+!i", "-", $productHandle);
								// end create prodcut handle

								// check product title exist or not using GraphQL
								try{
									$getProductTitleMutationQuery = '
				                    {
				                        products(first:1, query:"(handle:"'.$productHandle.'") AND (title:"'.$productTitle.'")") {
				                            edges{
				                                node{
				                                    id
				                                    title
				                                    description
				                                }
				                            }
				                        }   
				                    }';
							        $productTitleResponse = $graphql->runByQuery($getProductTitleMutationQuery);
								}catch(ShopifyApiException $e){
									$errorresponse = $e->getResponse();
									//error log into logs.txt file
									$errorText = "is product exist = ".print_r($errorresponse, true);
									fwrite($logFileOpen, $errorText);
									return false;
								}catch(ShopifyCurlException $e){
									$errorrMessage =$e->getMessage();
									//error log into logs.txt file
									$errorText = "is product exist = ".print_r($errorrMessage, true);
									fwrite($logFileOpen, $errorText);
									return false;
								}
								*/

								/*$productName = urlencode($productTitle); 

								$errorText1 = "</br>"."validate product title = ".$productName."<br>";
								fwrite($logFileOpen, $errorText1);
								unset($errorText1);

								$getProductInfo= $shopifyObject->call('GET', '/admin/api/2023-04/products.json?handle='.$productName);
								$productTitleShopify = '';


								$errorText = "</br>"."Validate product exist = ".print_r($getProductInfo, true)."<br>";
								fwrite($logFileOpen, $errorText);
								unset($errorText);

								if (count($getProductInfo) > 0) {
									$response = $getProductInfo;
									$errorText = "</br>"."check product title = ".print_r($response, true)."<br>";
									fwrite($logFileOpen, $errorText);
									unset($errorText);
									continue;
								}*/

								// create product handle
								$productHandle = str_replace("'", '', $productTitle);
								$productHandle = preg_replace("![^a-z0-9]+!i", "-", $productHandle);
								// end create prodcut handle

								// check product title exist or not using GraphQL
						        $getProductTitleMutationQuery = '
			                    {
			                        products(first:1, query:"(handle:"'.$productHandle.'") AND (title:"'.$productTitle.'")") {
			                            edges{
			                                node{
			                                    id
			                                    title
			                                    description
			                                }
			                            }
			                        }   
			                    }';
			                    $productTitleResponse = $graphql->runByQuery($getProductTitleMutationQuery);
								
								if(isset($productTitleResponse['data']['products']['edges']) && count($productTitleResponse['data']['products']['edges']) > 0){
									$response = $productTitleResponse['data']['products']['edges'];
									$errorText = "check product title = ".print_r($response, true);
									fwrite($logFileOpen, $errorText);
									continue;
								}else{

									//if shop_product_id is null Add new idea
									$productIdCheckShop= $objProduct["store_owner_product_master_id"];
									$sqlCheck = "SELECT id,shop_product_id FROM store_owner_product_master where id =".$productIdCheckShop;
									$productIdCheckShopDetails = parent::selectTable_f_mdl($sqlCheck);
									if(is_null($productIdCheckShopDetails[0]['shop_product_id']) or empty($productIdCheckShopDetails[0]['shop_product_id']))
									{
										#region - Create Product With all Variant Into Shopify Store
										$newProductMutation  = '
										mutation productCreate($input: ProductInput!){
											productCreate(input: $input){
												product {
													id
													variants(first:25){
														edges{
															node{
																id title sku
																selectedOptions{ name value }
															}
														}
													}
												}
												userErrors {
													field
													message
												}
											}
										}';

										
										//htmlentities(sanitize($productDescription))
										$newProductInput = '{
										  "input": {
												"descriptionHtml":"'.sanitize($productDescription).'",
												"collectionsToJoin":["gid://shopify/Collection/'.$shopCollectionId.'"],
												"title":"'.$productTitle.'",
												"vendor":"'.$vendor.'",
												"tags":'.json_encode(explode(", ", $productTags)).',
												"options": ["Size","Color"],
												"variants": ['.$allVarsStr.']
										  }
										}';

										$errorText = "</br>"."Product create case = ".print_r($newProductInput, true)."<br>";
										fwrite($logFileOpen, $errorText);
										unset($errorText);
										
										$createdProductInfo = $graphql->runByMutation($newProductMutation, $newProductInput);

										$errorText = "</br>"."Product create graphQL Response = ".print_r($createdProductInfo, true)."<br>";
										fwrite($logFileOpen, $errorText);
										unset($errorText);

										if(isset($createdProductInfo['data']['productCreate']['product']['id'])){
											$newProductCreateId = str_replace("gid://shopify/Product/", "", $createdProductInfo['data']['productCreate']['product']['id']);
											$newProductVariants = $createdProductInfo['data']['productCreate']['product']['variants']['edges'];

											#region - Update Product Id To DB
											parent::udpateProductIdToDB_f_mdl($storeOwnerProductMasterId, $newProductCreateId);
											#endregion

											#region - Loop Through Set Variant Ids To DB
											if(count($newProductVariants) > 0){
												$errorText = "</br> inside new product varients</br>";
												fwrite($logFileOpen, $errorText);
												unset($errorText);
												foreach($newProductVariants as $objProdVar){
													$tempCurVarId = $objProdVar["node"]["id"];
													$tempCurVarSku = $objProdVar["node"]["sku"];
													$tempCurSelectedOptions = $objProdVar["node"]["selectedOptions"];	// [ { "name": "Color", "value": "Blue" }, { "name": "Size", "value": "S" } ]

													$colorOption = $sizeOption = '';
													foreach($tempCurSelectedOptions as $single_option){
														if($single_option['name']=='Color'){
															$colorOption = $single_option['value'];// Black
														}else if($single_option['name']=='Size'){
															$sizeOption = $single_option['value'];//Youth L (14/16)
														}
													}

													$tempUseVarId = str_replace("gid://shopify/ProductVariant/", "", $tempCurVarId);
													parent::updateProductAndVariantIdToDB_f_mdl($newProductCreateId, $tempUseVarId, $storeOwnerProductMasterId, $sizeOption, $variantColorArray[trim($colorOption)], $tempCurVarSku);

													$sql = "SELECT * FROM store_owner_product_variant_master WHERE store_owner_product_master_id ='".$storeOwnerProductMasterId."' AND size ='".$sizeOption."' AND sku ='".$tempCurVarSku."' AND color ='".$variantColorArray[trim($colorOption)]."' ";
													$productverDetails = parent::selectTable_f_mdl($sql);
													
													$mocupImage = parent::commonMockups($productverDetails[0]['id']);
													$productImage = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$productverDetails[0]['image']);
													if(!empty($mocupImage)){
														$productImage = $s3Obj->getAwsUrl(common::LOGO_MOCKUP_UPLOAD_S3_PATH.$storeMasterId.'/'.$mocupImage[0]["image"]);
													}

													$mutation = '
														mutation productAppendImages($input: ProductAppendImagesInput!) {
														  productAppendImages(input: $input) {
														    newImages {
														      id
														    }
														    product {
														      id
														    }
														    userErrors {
														      field
														      message
														    }
														  }
													}';

													$newProductInput = '{
													  "input": {
													    "id": "'.$createdProductInfo['data']['productCreate']['product']['id'].'",
													    "images": [
													      {
													      	"src" : "'.$productImage.'"
													      }
													    ]
													  }
													}';
													sleep(0.5);
													// $errorText = "</br>Append Product Image ".print_r($newProductInput, true);
													// fwrite($logFileOpen, $errorText);
													// unset($errorText);
													$imgData = $graphql->runByMutation($mutation, $newProductInput);
													// $errorText = "</br>Append Product Image data res ".print_r($imgData, true);
													// fwrite($logFileOpen, $errorText);
													// unset($errorText);

													if(isset($imgData['data']['productAppendImages']['newImages'][0]['id']) && !empty($imgData['data']['productAppendImages']['newImages'][0]['id'])){
														//now assign that imageId to variant
														$mutation = 'mutation productVariantUpdate($input: ProductVariantInput!) {
														  productVariantUpdate(input: $input) {
															product {
															  id
															}
															productVariant {
															  id
															}
															userErrors {
															  field
															  message
															}
														  }
														}';
														$input = '{
														  "input": {
															"id": "'.$tempCurVarId.'",
															"imageId": "'.$imgData['data']['productAppendImages']['newImages'][0]['id'].'"
														  }
														}';
														sleep(0.5);
														$rr=$graphql->runByMutation($mutation, $input);
														// $errorText = "</br>Append Product Image id res ".print_r($rr, true);
														// fwrite($logFileOpen, $errorText);
														// unset($errorText);
													}
												}
											}
											#endregion

											#region - Publish Product On Shopify Store
											if($newProductCreateId != "" && $newProductCreateId != "0"){
												$productPublishArray = json_decode('{"product": {"id": '.$newProductCreateId.',"published": true}}', true);

												try{
													$productPublishInfo = $shopifyObject->call('PUT', '/admin/api/2023-04/products/'.$newProductCreateId.'.json', $productPublishArray);
													$errorText = "<br> product publish ".print_r($productPublishInfo, true)."</br>";
													fwrite($logFileOpen, $errorText);
													unset($errorText);

												}
												catch(ShopifyApiException $e){
												}
												catch(ShopifyCurlException $e){
												}
											}
											#endregion

											#region - Append Image to product
											if(!empty($newProductCreateId) && !empty($objProduct["product_image"])){

												$mocupImage = parent::commonMockups($objProduct['store_owner_product_variant_master_id']);
												$productImage = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$objProduct["product_image"]);
												if(!empty($mocupImage)){
													$productImage = $s3Obj->getAwsUrl(common::LOGO_MOCKUP_UPLOAD_S3_PATH.$storeMasterId.'/'.$mocupImage[0]["image"]);
												}

												$newProductMutation = '
												mutation productAppendImages($input: ProductAppendImagesInput!) {
												  productAppendImages(input: $input) {
												    newImages {
												      id
												    }
												    product {
												      id
												    }
												    userErrors {
												      field
												      message
												    }
												  }
												}';

												$newProductInput = '{
											  "input": {
											    "id": "'.$createdProductInfo['data']['productCreate']['product']['id'].'",
											    "images": [
											      {
											      	"src" : "'.$productImage.'"
											      }
											    ]
											  }
											}';


											$errorText = "</br>Append Product Image ".print_r($newProductInput, true);
											fwrite($logFileOpen, $errorText);
											unset($errorText);


											$resp = $graphql->runByMutation($newProductMutation, $newProductInput);

											}
											#endregion
										}else{
											$newProductCreateId = '';
										}
									}
								}	
							}
						}
						// else
						// {
						// 	$isAllProductsCreated = true;
						// 	$isAllproductsSyncedToCollect = true;
						// }

						$test = "SELECT * FROM store_owner_product_variant_master where store_owner_product_master_id = '$storeOwnerProductMasterId' and shop_product_id = '' and shop_variant_id = '' limit 100";
						$data = parent::selectTable_f_mdl($test);
						// echo "<pre>";print_r($data);die();
						if (count($data) == 0) {
							if($count_flag == 1){
								parent::updateMapedProductsStatus_f_mdl($storeOwnerProductMasterId);
							}
						}
						
						// if($count_flag == 1){
						// 	$mapedMasterIdArray[] = $storeOwnerProductMasterId;
						// }
					}
					
					// if(count($mapedMasterIdArray) > 0){
					// 	if(count($mapedMasterIdArray) > 0){
					// 		parent::updateMapedProductsStatus_f_mdl(implode(",", $mapedMasterIdArray));
					// 	}
					// }
				}
				#endregion
			}
			#endregion	
			if($isAllProductsCreated && $isAllproductsSyncedToCollect){
				parent::updateCollectionCreateAndSyncedStatus_f_mdl($storeMasterId);
			}
			

			//==============Sort Product in Sopify=================
			if(!empty($storeInfo)) {
				require_once('lib/class_graphql.php');
				$shop  = $storeInfo[0]["shop_name"];
				$token = $storeInfo[0]['token'];
				$headers = array(
					'X-Shopify-Access-Token' => $token
				);
				$graphql = new Graphql($shop, $headers);
			}
			$res = [];
			$sortOrder='';
			$sortOrder = "PRICE_ASC";
			$sql = 'SELECT * FROM store_master WHERE id="'.$storeMasterId.'" ';
			$store_data = parent::selectTable_f_mdl($sql);
			$shop_collection_id='';
			if(!empty($store_data)){
				$shop_collection_id=$store_data[0]['shop_collection_id'];
			}

			$mutationData='mutation UpdateCollectionSortOrder($collection: CollectionInput!, $first: Int, $after: String) {
				collectionUpdate(input: $collection) {
					collection {
					id
					sortOrder
					productsCount
					products(first: $first, after: $after) {
						...ProductRow
						__typename
					}
					__typename
					}
					userErrors {
					field
					message
					__typename
					}
					__typename
				}
				}

				fragment ProductRow on ProductConnection {
				edges {
					node {
					id
					title
					status
					featuredImage {
						id
						transformedSrc: url(transform: {maxWidth: 80, maxHeight: 80})
						altText
						__typename
					}
					featuredMedia {
						... on MediaImage {
						id
						__typename
						}
						... on Video {
						id
						__typename
						}
						... on Model3d {
						id
						__typename
						}
						... on ExternalVideo {
						id
						__typename
						}
						preview {
						image {
							id
							transformedSrc: url(transform: {maxWidth: 80, maxHeight: 80})
							__typename
						}
						__typename
						}
						__typename
					}
					__typename
					}
					cursor
					__typename
				}
				pageInfo {
					hasNextPage
					__typename
				}
				__typename
				}';
				
			$input = '{
			"collection": {
				"id": "gid://shopify/Collection/'.$shop_collection_id.'",
				"sortOrder": "'.$sortOrder.'"
			},
			"first": 10,
			"after": null
			}';
			$UpdateData = $graphql->runByMutation($mutationData, $input);

			$errorText = "</br> collection sort product input</br>".$input;
			fwrite($logFileOpen, $errorText);
			unset($errorText);

			$updateprod_sort = [
				"store_sort_product_by"=>$sortOrder
			];	
			$resPonseData = parent::updateTable_f_mdl('store_master',$updateprod_sort,'id="'.$storeMasterId.'"');	
			//==============Sort Product in Sopify end=================
			fclose($logFileOpen);
			//update status = 0 and end time , is_success= 1
		}
	}

	/* Send email to store owner when collection is created */
	public function sendEmailToStoreOwner($storeOwnerDetailsMasterId,$storeName,$storeHanlde,$storeMasterId)
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
		$emailData  = parent::getEmailTemplateInfo('27');
		$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
		$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
		$sql = 'SELECT id,first_name,email FROM `store_owner_details_master` WHERE id = '.$storeOwnerDetailsMasterId.' ';
		$ownerInfo = parent::selectTable_f_mdl($sql);

		$sql = 'SELECT * FROM store_master WHERE id="'.$storeMasterId.'"';
        $store_data = parent::selectTable_f_mdl($sql);

		$store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
		$store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;


		if(!empty($ownerInfo)){
			$to_email   = $ownerInfo[0]['email'];
			$from_email = common::AWS_ADMIN_EMAIL;
			$subject    = $emailData[0]['subject'];
			$subject    = str_replace('{{STORE_NAME}}', $storeName, $subject);
			$storeUrl   = '<a href="https://'.common::PARENT_STORE_NAME.'/collections/'.$storeHanlde.'" target="_blank" class="btn btn-info" style="margin: 5px;">Click Here</a>';
			$body       = $emailData[0]['body'];
			$body       = str_replace('{{OWNER_NAME}}', $ownerInfo[0]['first_name'], $body);
			$body       = str_replace('{{FRONT_STORE_URL}}', $storeUrl, $body);
			$body       = str_replace('{{STORE_NAME}}', $storeName, $body);
			$body 		= str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
			$body       = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
			$body       = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);

			$objAWS->sendEmail([$to_email], $subject, $body, $body);
		}
		/*send mail store manager */
		$sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $storeOwnerDetailsMasterId . '"';
		$smm_data =  parent::selectTable_f_mdl($sql_managerData);
		if(!empty($smm_data)){
			foreach ($smm_data as $managerData) {
				$manager_body       = $emailData[0]['body'];
				$to_email   = $managerData['email'];
				$firstname  = $managerData['first_name'];
				
				$manager_body = str_replace('{{OWNER_NAME}}', $firstname, $manager_body);
				$manager_body = str_replace('{{FRONT_STORE_URL}}', $storeUrl, $manager_body);
				$manager_body = str_replace('{{STORE_NAME}}', $storeName, $manager_body);
				$manager_body = str_replace('{{SPIRITHERO_LOGO}}', $logo, $manager_body);
				$manager_body = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $manager_body);
				$manager_body = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $manager_body);

				$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $manager_body, $manager_body);
			}
		}
		/*send mail store manager */
	}

	public function createCustomerInPrintavo($storeOwnerDetailsMasterId){
        if(!empty($storeOwnerDetailsMasterId)){
            $ownerSql = "SELECT * FROM store_owner_details_master WHERE id = ".$storeOwnerDetailsMasterId." ";
            $ownerDetails = parent::selectTable_f_mdl($ownerSql);
            $first_name = '';
            $last_name = '';
            $email = '';
            $phone = '';
            $organization_name = '';
            if(!empty($ownerDetails)){
               $details = $ownerDetails[0]; 
               $first_name = $details['first_name'];
               $last_name = $details['last_name'];
               $email = $details['email'];
               $phone = $details['phone'];
               $organization_name = $details['organization_name'];
            }
            
            $query = $email;
            $userId = common::PRINTAVO_USER_ID;
           	
           	$arrCount = parent::checkExistPrintavoCustomer($query);
           	
			if(isset($arrCount['data']) && !count($arrCount['data']) > 0){
				parent::createPrintavoCustomer($storeOwnerDetailsMasterId,$first_name,$last_name,$email,$phone,$organization_name,$userId);
			}
        	
        }
    }
}
?>