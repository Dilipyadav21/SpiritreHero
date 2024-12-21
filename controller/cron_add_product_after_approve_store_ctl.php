<?php
//http://app.spirithero.com/cron-create-store-collections.php
include_once 'model/cron_create_store_collections_mdl.php';
/* Task 59 */
$path  = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';
$s3Obj = new Aws3;
/* Task 59 */

class cron_add_product_after_approve_store_ctl extends cron_create_store_collections_mdl
{
	function __construct(){
		$this->startProductSyncDirect();
	}
	

	function startProductSyncDirect(){
		global $s3Obj;
		//Check email only testing purpose
		/*require_once("lib/class_aws.php");
	    $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
	    $mailSendStatus = $objAWS->sendEmail(["sanjay@bitcot.com"], "startProductSyncDirect", "Message: startProductSyncDirect", "Message: startProductSyncDirect");*/
	    //end Check email..
	    $store_id = $_REQUEST['store_id']?$_REQUEST['store_id']:0;
		#region - Fetch Collection Create Request
		$storeCollectionInfo = parent::getApprovedStoreCollectionInfo_f_mdl($store_id);
		#endregion
		/*echo "11";
		echo "<pre>";print_r($storeCollectionInfo);die;*/

		parent::updateApproveCollectionCreateAndSyncedStatus_f_mdl($store_id, 1);
		
		if(count($storeCollectionInfo) > 0){
			$storeMasterId = $storeCollectionInfo[0]["store_master_id"];
			$storeOwnerDetailsMasterId = $storeCollectionInfo[0]["store_owner_details_master_id"];
			$storeOrganizationTypeMasterId = $storeCollectionInfo[0]["store_organization_type_master_id"];
			$frontSideInkColors = $storeCollectionInfo[0]["front_side_ink_colors"];
			$backSideInkColors = $storeCollectionInfo[0]["back_side_ink_colors"];
			$storeFulfillmentType = $storeCollectionInfo[0]["store_fulfillment_type"];
			$storeSaleTypeMasterId = $storeCollectionInfo[0]["store_sale_type_master_id"];
			$isFundRaising = $storeCollectionInfo[0]["is_fundraising"];
			$storeName = $storeCollectionInfo[0]["store_name"];
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

				

				if(count($productsToCreateIntoStoreInfo) > 0){
					$mapedMasterIdArray = array();
					
					$isAllProductsCreated = false;
					$isAllproductsSyncedToCollect = false;

					foreach($productsToCreateIntoStoreInfo as $objProduct){
						$storeOwnerProductMasterId = $objProduct["store_owner_product_master_id"];
						$productTitle = $storeName."-".$objProduct["product_title"];
						$productDescription = str_replace('"', ' Inch', $objProduct["product_description"]);
						$productTags = $objProduct["tags"];
						$vendor = $objProduct["vendor_name"];
						
						#region - Get Product Variants
						$productWithVariantsInfo = parent::getProductVariantsInfo_f_mdl($storeOwnerProductMasterId);


						/*Cron For Product*/
						$sqlA = "SELECT sopvm.id as store_owner_product_variant_master_id, sopvm.shop_product_id, sopvm.shop_variant_id, sopvm.store_owner_product_master_id, sopvm.store_product_variant_master_id, sopvm.store_organization_type_master_id, sopvm.price, sopvm.price_on_demand, sopvm.fundraising_price, sopvm.color, sopvm.size, sopvm.image, sopvm.original_image, sopvm.sku,sopvm.weight FROM store_owner_product_variant_master sopvm WHERE sopvm.store_owner_product_master_id = '$storeOwnerProductMasterId' AND sopvm.shop_variant_id!=''";
						$totalSyncVarients = parent::selectTable_f_mdl($sqlA);

						$sqlB = "SELECT sopvm.id as store_owner_product_variant_master_id, sopvm.shop_product_id, sopvm.shop_variant_id, sopvm.store_owner_product_master_id, sopvm.store_product_variant_master_id, sopvm.store_organization_type_master_id, sopvm.price, sopvm.price_on_demand, sopvm.fundraising_price, sopvm.color, sopvm.size, sopvm.image, sopvm.original_image, sopvm.sku,sopvm.weight FROM store_owner_product_variant_master sopvm WHERE sopvm.store_owner_product_master_id = '$storeOwnerProductMasterId'";
						$TotalVariantB = parent::selectTable_f_mdl($sqlB);

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
							/*remove duplicate variants from array*/
							
							#region - Collect All Variants Images To Upload
							$allVarImagesStr = "";
							$firstVarMasterId = 0;
							$firstVarTotalPrice = 0;
							$firstVarColorWithColorName = "";
							$variantColorArray = [];
							#endregion


							if(!empty($objProduct["shop_product_id"])){
								
								//product is already existed in shopify, only new variants are added in that

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
											  {"src":"'.$s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$objProdVar["image"]).'"}
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

									}else{ /* task 32 start */
										
										if(isset($createdProductVarInfo['data']['productVariantCreate']['userErrors']) && !empty($createdProductVarInfo['data']['productVariantCreate']['userErrors'])){
											//print_r($createdProductVarInfo['data']['productVariantCreate']['userErrors'][0]['message']);

											parent::updateVariantIdToDB_f_mdl(
												$objProduct["shop_product_id"],
												0,
												$objProdVar['store_owner_product_variant_master_id']
											);
										}
										
									} /* task 32 end */
								}

							}else{

								$allVarsStr = "";
								if(count($productWithVariantsInfo) > 0){
									foreach($productWithVariantsInfo as $objProdVar){
										$allVarImagesStr .= '{"src":"'.$s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$objProdVar["image"]).'"},';

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
										
										$allVarsStr .= '{"price": "'.$tempVarTotalPrice.'", "sku": "'.$objProdVar["sku"].'","weight": '.$weight.',"weightUnit": "OUNCES","options": ["'.$objProdVar["size"].'","'.$varColorWithColorName.'"], "imageSrc": "'.$s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$objProdVar["image"]).'", "inventoryPolicy": "CONTINUE"},';
										
										#endregion
									}

									if($allVarsStr != ""){
										$allVarsStr = trim($allVarsStr, ",");
									}
								}

								#region - Create Product With all Variant Into Shopify Store
								$newProductMutation = '
								mutation productCreate($input: ProductInput!){
									productCreate(input: $input){
										product {
											id
											variants(first:50){
												edges{
													node{
														id title sku
														selectedOptions{ name value }
													}
												}
											}
											images(first: 50){
												edges{
													node{
														id,
														originalSrc
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
										"images":['.trim($allVarImagesStr, ",").'],
										"variants": ['.$allVarsStr.']
								  }
								}';

								$createdProductInfo = $graphql->runByMutation($newProductMutation, $newProductInput);
								
								if(isset($createdProductInfo['data']['productCreate']['product']['id'])){
									$newProductCreateId = str_replace("gid://shopify/Product/", "", $createdProductInfo['data']['productCreate']['product']['id']);
									$newProductVariants = $createdProductInfo['data']['productCreate']['product']['variants']['edges'];

									#region - Update Product Id To DB
									parent::udpateProductIdToDB_f_mdl($storeOwnerProductMasterId, $newProductCreateId);
									#endregion

									#region - Loop Through Set Variant Ids To DB
									if(count($newProductVariants) > 0){
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
										}
									}
									#endregion

									#region - Publish Product On Shopify Store
									if($newProductCreateId != "" && $newProductCreateId != "0"){
										$productPublishArray = json_decode('{"product": {"id": '.$newProductCreateId.',"published": true}}', true);

										try{
											$productPublishInfo = $shopifyObject->call('PUT', '/admin/api/2023-04/products/'.$newProductCreateId.'.json', $productPublishArray);
										}
										catch(ShopifyApiException $e){
										}
										catch(ShopifyCurlException $e){
										}
									}
									#endregion

									#region - Append Image to product
									if(!empty($newProductCreateId) && !empty($objProduct["product_image"])){

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
									      	"src" : "'.$s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$objProduct["product_image"]).'"
									      }
									    ]
									  }
									}';

									$resp = $graphql->runByMutation($newProductMutation, $newProductInput);

									}
									#endregion
								}else{
									$newProductCreateId = '';
								}
							}
						}
						
						if($count_flag == 1){
							$mapedMasterIdArray[] = $storeOwnerProductMasterId;
						}
					}
					
					if(count($mapedMasterIdArray) > 0){
						if(count($mapedMasterIdArray) > 0){
							parent::updateMapedProductsStatus_f_mdl(implode(",", $mapedMasterIdArray));
						}
					}
				}
				#endregion
			}
			#endregion
			
			if($isAllProductsCreated && $isAllproductsSyncedToCollect){
				parent::updateCollectionCreateAndSyncedStatus_f_mdl($storeMasterId);
			}
		}

		parent::updateApproveCollectionCreateAndSyncedStatus_f_mdl($store_id, 0);
	}
}
?>