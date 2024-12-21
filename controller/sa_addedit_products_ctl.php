<?php
include_once 'model/sa_addedit_products_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . 'libraries/Aws3.php';
$login_user_email="";
if(isset($_SESSION['user_email']) && $_SESSION['user_email'] != "") {
	$login_user_email=trim($_SESSION['user_email']);
}
class sa_addedit_products_ctl extends sa_addedit_products_mdl
{
	public $TempSession = "";
	public $passedId = 0;

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			
			$this->passedId = parent::getVal("pid");
		}
		
		if(isset($_POST['action'])){
	        $this->checkRequestProcess($_REQUEST['action']);
	    }

		common::CheckLoginSession();
	}
	
	function checkRequestProcess($checkRequest)
	{
		if($checkRequest != ""){
            switch($checkRequest){
                case "save_area_coordinates":
					$this->saveAreaCoordinates();
                break;
				case "save_coordinates":
					$this->saveCoordinates();
                break;
				case "delete-bulk-prod-veriants":
					$this->deleteBulkProdVariant();
                break;
				case "color_filter_dropdown":
					$this->getColorFilterDropdown();
				break;
				case 'saveproductImag':
				$this->saveproductImag();
				break;
				case 'saveproductFeatureImag':
				$this->saveproductFeatureImag();
				break;
				case "master_variant_delete_bulk":
					$this->master_variant_delete_bulk();
                break;
				case "recover-deleted-veriants":
					$this->RecoverDeletedVeriants();
                break;
            }
        }
	}
	function getProductInfo(){
		return parent::getProductInfo_f_mdl($this->passedId);
	}

	function getAllProductSize(){
		return parent::getAllProductSize_f_mdl($this->passedId);
	}
	
	function getProductTypesInfo(){
		return parent::getProductTypesInfo_f_mdl();
	}
	
	function getStoreStylesInfo(){
		return parent::getStoreStylesInfo_f_mdl();
	}
	
	function addEditProducts(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "addedit-product"){
				$s3Obj = new Aws3;
				#region - Set Basic Variables
				$masterProductId = parent::getVal("hdn_id");
				$productTitle = parent::getVal("txt-product-name");
				$product_created_using="Manual";
				$productDesc = parent::getVal("txt-product-desc");
				$tmpProductTags = parent::getVal("txt-product-tags");
				$vendorId = parent::getVal("drp-vendor");
				/*Task 67 start*/
				$personalization=parent::getVal("personalization_field");
				$requiredField=parent::getVal("required_field");
				/*Task 67 end*/
				//echo "<pre>";print_r($_POST);
				//echo "<pre>";$personalization."===".$requiredField;die("sssss");
				
				$personalization = (!empty($personalization) && $personalization=="on")?1:0;
				$requiredField = (!empty($requiredField) && $requiredField=="on")?1:0;
				
				$tmpUploadImageImage = '';
				// if(isset($_FILES['txt-product-image']['name']) && !empty($_FILES['txt-product-image']['name'])){

				// 	$imgUploadPath = "image_uploads/";
				// 	$mimeType          = $_FILES['txt-product-image']['type'];
				// 	$featImageName     = $_FILES['txt-product-image']['name'];
				// 	$tempFeatImageName = $_FILES['txt-product-image']['tmp_name'];
					
				// 	#region - Get Uploaded File's Extension
				// 	$imageExt = strtolower(pathinfo($featImageName, PATHINFO_EXTENSION));
				// 	#endregion

				// 	#region - Can Upload Same Image Using Rand Function
				// 	$tempFinalImageName = rand(1000,1000000).$featImageName;
				// 	#endregion

				// 	$validImgExt = explode(",", common::VALID_IMAGE_EXTENSIONS);

				// 	#region - Check's Valid Format
				// 	if(in_array($imageExt, $validImgExt)){ 
				// 		$imgUploadPath = $imgUploadPath.$tempFinalImageName;
				// 	}
				// 	#endregion

				// 	if($s3Obj->uploadFile($imgUploadPath,$tempFeatImageName,$mimeType)){
				// 		$productFeatImage = $tempFinalImageName;
				// 		$tmpUploadImageImage = $productFeatImage;
				// 	}

				// }

				if(isset($_POST['select_color'])){
					$productVarColorArray = parent::getVal("select_color");
					
					#region - Append Color Tag
					
					$tmpTagCol = ",color_";
					
					foreach($productVarColorArray as $objVarCol){
						$colorNameArry[] = $tmpTagCol.$objVarCol;
					}
					
					$uniqColName = implode(",",array_unique($colorNameArry));
					$colorTagString = str_replace(' ', '_',strtolower($uniqColName));
					#endregion
					
					$tmpProductTags = $tmpProductTags.$colorTagString;
					
					$tmpProductTags = str_replace(" ","",$tmpProductTags);
					$tmpProductTags = str_replace(',',', ',preg_replace('/,+/', ',', $tmpProductTags));
					$tmpProductTags = str_replace(" ","",$tmpProductTags);
					$tmpProductTags = trim($tmpProductTags,',');
					$tmpProductTags = trim($tmpProductTags);
				}
				
				$productTags = $tmpProductTags;
				
				$productVarSize = parent::getVal("txt_size");
				
				$productSKUArray = parent::getVal("txt_sku");
				$productStatus = parent::getVal("drp-status");
				$productMinQtyArray = parent::getVal("txt_min_qty");
				$productWeightArray = parent::getVal("txt_weight");
				
				$productImage = '';
				// if(isset($tmpUploadImageImage) && !empty($tmpUploadImageImage)){
				// 	$productImage = $tmpUploadImageImage;
				// }
				// else{
				// 	$productImage = parent::getVal('old_image');
				// }
				#endregion
				
				$returnArray = array();
				
				#region - Insert/Update Products Info
				if($masterProductId > 0){
					parent::updateProductDetail_f_mdl($masterProductId, $productTitle, $productDesc, $productTags, $personalization, $requiredField, $vendorId,$productStatus); // Task 67 add 2 parameters $personalization, $requiredField
				}
				else{
					$productImagesArray = array();
					
					#region - Save Variants Images First
					if(isset($_FILES['txt_file']['name']) && !empty($_FILES['txt_file']['name'])){
						for($i = 0; $i < count($_FILES['txt_file']['name']); $i++){
							if(isset($_FILES['txt_file']['tmp_name'][$i]) && !empty($_FILES['txt_file']['tmp_name'][$i])){
								$imgUploadPath = "image_uploads/";							
								$mimeType = $_FILES['txt_file']['type'][$i];
								$imageName = $_FILES['txt_file']['name'][$i];
								$tempImageName = $_FILES['txt_file']['tmp_name'][$i];
								
								#region - Get Uploaded File's Extension
								$imageExt = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
								#endregion
								
								#region - Can Upload Same Image Using Rand Function
								$tempFinalImageName = rand(1000,1000000).$imageName;
								#endregion
								
								$validImgExtArray = explode(",", common::VALID_IMAGE_EXTENSIONS);
								
								#region - Check's Valid Format
								if(in_array($imageExt, $validImgExtArray)){ 
									$imgUploadPath = $imgUploadPath.$tempFinalImageName;
								}
								#endregion
								
								#region - Upload File To Dir
								if($s3Obj->uploadFile($imgUploadPath,$tempImageName,$mimeType)){
									$productImagesArray[] = $tempFinalImageName;
								}
								#endregion
							}
						}
					}
					#endregion

					
					$productFeatImagesArray = array();
					
					#region - Save Variants Featured Images First
					if(isset($_FILES['txt_featured_file']) && !empty($_FILES['txt_featured_file'])){
						for($i = 0; $i < count($_FILES['txt_featured_file']['name']); $i++){
							if(isset($_FILES['txt_featured_file']['tmp_name'][$i]) && !empty($_FILES['txt_featured_file']['tmp_name'][$i])){
								$imgUploadPath = "image_uploads/";
							
								$mimeType = $_FILES['txt_featured_file']['type'][$i];
								$featImageName = $_FILES['txt_featured_file']['name'][$i];
								$tempFeatImageName = $_FILES['txt_featured_file']['tmp_name'][$i];
								
								#region - Get Uploaded File's Extension
								$imageExt = strtolower(pathinfo($featImageName, PATHINFO_EXTENSION));
								#endregion
								
								#region - Can Upload Same Image Using Rand Function
								$tempFinalImageName = rand(1000,1000000).$featImageName;
								#endregion
								
								$validImgExtArray = explode(",", common::VALID_IMAGE_EXTENSIONS);
								
								#region - Check's Valid Format
								if(in_array($imageExt, $validImgExtArray)){ 
									$imgUploadPath = $imgUploadPath.$tempFinalImageName;
								}
								#endregion
								
								#region - Upload File To Dir
								if($s3Obj->uploadFile($imgUploadPath,$tempFeatImageName,$mimeType)){
									$productFeatImagesArray[] = $tempFinalImageName;
								}
								#endregion
							}	
						}
					}
					#endregion
					
					#region - Insert Master Products
					$masterProductId = parent::insertMasterProductInfo_f_mdl($productTitle,$product_created_using, $productDesc, $productTags,$personalization, $requiredField, $vendorId); //Task 67 add 2 variables $personalization, $requiredField
					#endregion
					
					#region - Get Organizations List
					$organizationsInfo = parent::getAllOrganizationsInfo_f_mdl();
					#endregion
					
					#region - Create Variants Insert String
					if($masterProductId > 0){
						#region - Split Variants Size & Make Array
						$varSizeArray = explode(",", $productVarSize);
						#endregion
						
						$productsVariantsStr = "";
						$incr = 0;
						$skuIncr = 0;
						
						if(count($varSizeArray) > 0){
							foreach($varSizeArray as $objVarSize){
								#region - Loop Through Color Variants
								foreach($productVarColorArray as $objVarCol){
									$clrCode = parent::getProductColorInfo_f_mdl($objVarCol);
									
									$created_on =  @date('Y-m-d H:i:s');
									$created_on_ts = time();
									$status = '1';
									
									#region - Loop Through Organizations
									foreach($organizationsInfo as $objOrg){
										$postVarOrgName = str_replace(" ", "_", $objOrg["organization_name"]);
										
										$productsVariantsStr .= "(".$masterProductId.", ".
											$objOrg["id"].", '".
											$_POST["txt_flash_sale_".$postVarOrgName][$incr]."', '".
											$_POST["txt_on_demand_".$postVarOrgName][$incr]."', '".
											$clrCode[0]['product_color']."', '".
											$objVarSize."', '".
											@$productImagesArray[$skuIncr]."', '".
											$productSKUArray[$skuIncr]."', '".
											@$productFeatImagesArray[$skuIncr]."', '".
											$productMinQtyArray[$skuIncr]."', '".
											$productWeightArray[$skuIncr]."','".
											$status."','".
											$created_on."','".
											$created_on_ts."'),";
									}
									#endregion
									
									$skuIncr++;
								}
								#endregion
								
								$incr++;
							}
						}
						
						if($productsVariantsStr != ""){
							parent::insertProductVariantsInBulk_f_mdl(rtrim($productsVariantsStr, ","));
						}
					}
					#endregion
					
					$returnArray["isSuccess"] = "1";
					$returnArray["msg"] = "Product Added successfully";
				}
				#endregion
				
				parent::sendJson($returnArray);
			}
		}
    }

	function updateProductVariants(){
		global $login_user_email;
		if(parent::isPOST()){
			if(!empty(parent::getVal("action")) && parent::getVal("action") == "update_product_vars"){
				$change_flash_sale_price = 0;
				$change_on_demand_price  = 0;
				$store_product_master_id=parent::getVal('store_product_master_id');
				$toggle_price_status=parent::getVal('toggle_price_status');

				if(isset($_POST['price_type'])){
					if($_POST['price_type']=='flash_sale'){
						$change_flash_sale_price = parent::getVal("change_product_price");
					}elseif($_POST['price_type']=='on_demand'){
						$change_on_demand_price = parent::getVal("change_product_price");
					}
				}

				$updatePriceData = [
					'store_product_master_id'  => parent::getVal('store_product_master_id'),
					'updated_flash_sale_price' => $change_flash_sale_price,
					'updated_on_demand_price'  => $change_on_demand_price,
					'created_on'               => date('Y-m-d H:i:s'),
					"updated_by"			   => "Super Admin <br>(".$login_user_email.")",
				];
				parent::insertTable_f_mdl('update_price_history',$updatePriceData);

				$sql = 'SELECT distinct(sanmar_size) as all_sizes FROM store_product_variant_master  where store_product_master_id="'.$store_product_master_id.'" ';
				$getProdsizeData = parent::selectTable_f_mdl($sql);

				$s3Obj = new Aws3;
				#region - Save Product Variants Images If Posted
				$buildProductImageStr = "";
				$buildProductFeatureImageStr  ="";
				if(!empty($_FILES)){
					foreach($_FILES as $key=>$tmp_name){
						$imgUploadPath = "image_uploads/";
					
						$mimeType = $_FILES[$key]['type'][0];
						$imageName = $_FILES[$key]['name'][0];
						$tempImageName = $_FILES[$key]['tmp_name'][0];
						
						#region - Get Uploaded File's Extension
						$imageExt = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));
						#endregion
						
						#region - Can Upload Same Image Using Rand Function
						$tempFinalImageName = rand(1000,1000000).$imageName;
						#endregion
						
						$validImgExtArray = explode(",", common::VALID_IMAGE_EXTENSIONS);
						
						#region - Check's Valid Format
						if(in_array($imageExt, $validImgExtArray)){ 
							$imgUploadPath = $imgUploadPath.$tempFinalImageName;
						}
						#endregion
						
						#region - Upload File To Dir
						#if(move_uploaded_file($tempImageName, $imgUploadPath))
						if($s3Obj->uploadFile($imgUploadPath,$tempImageName,$mimeType))
						{
							if (strpos($key, 'var_img_') !== false) {
								$varMasterId = str_replace("var_img_", "", $key);
								
								$buildProductImageStr .= "(".$varMasterId.", '".$tempFinalImageName."'),";
							}
							if (strpos($key, 'var_feature_img_') !== false) {
								$varFeatureMasterId = str_replace("var_feature_img_", "", $key);
								
								$buildProductFeatureImageStr .= "(".$varFeatureMasterId.", '".$tempFinalImageName."'),";
							}
						}
						
						if(move_uploaded_file($imageName, $imgUploadPath)){
							if (strpos($key, 'var_img_') !== false) {
								$varMasterId = str_replace("var_img_", "", $key);
								
								$buildProductImageStr .= "(".$varMasterId.", '".$tempFinalImageName."'),";
							}
							if (strpos($key, 'var_feature_img_') !== false) {
								$varFeatureMasterId = str_replace("var_feature_img_", "", $key);
								
								$buildProductFeatureImageStr .= "(".$varFeatureMasterId.", '".$tempFinalImageName."'),";
							}
						}
						#endregion
					}
				}
				
				#region - Loop & Save Variants Info
				$varJsonInfo = json_decode(parent::getVal("vars_info"));
				
				$varsIds = array();
				$varsUdpateStr = "";

				foreach($varJsonInfo as $objVarInfo){
					$varId = $objVarInfo->varId;
					$sql = 'SELECT * FROM store_product_variant_master where id="'.$varId.'" ';
					$getVerData = parent::selectTable_f_mdl($sql);
					
					// if(!empty($getVerData)){
					// 	$varPriceflash=$getVerData[0]['price'];
					// 	$varPrice_ondemand=$getVerData[0]['price_on_demand'];
					// }else{
					// 	$varPriceflash='0';
					// 	$varPrice_ondemand='0';
					// }
					
					$varPrice = $objVarInfo->varPrice+$change_flash_sale_price;
					$varPrice_on_demand = $objVarInfo->varPrice_on_demand+$change_on_demand_price;
					$varSize = $objVarInfo->varSize;
					$varVarMinQty = $objVarInfo->varVarMinQty;
					$varWeight = $objVarInfo->varWeight;
					
					#endregion
										
					$varsUdpateStr .= "(".$varId.", '".$varPrice."','".$varPrice_on_demand."','".$varSize."','".$varVarMinQty."','".$varWeight."'),";
				}


				if($varsUdpateStr != ""){
					parent::updateProductVariants_f_mdl(trim($varsUdpateStr, ","));
				}
				
				if($buildProductImageStr != ""){
					parent::updateProductVarImages_f_mdl(trim($buildProductImageStr, ","));
				}
				
				if($buildProductFeatureImageStr != ""){
					parent::updateProductFeatureImages_f_mdl(trim($buildProductFeatureImageStr, ","));
				}
				#endregion

				if(!empty($getProdsizeData)){
					foreach($getProdsizeData as $sizevalue){

						$replacedStr = preg_replace('/["\/ ]/', '_', $sizevalue['all_sizes']);
                        $size = preg_replace('/_+/', '_', $replacedStr);

						//$size=str_replace(' ', '_', $sizevalue['all_sizes']);
						//$size = preg_replace('/[ \/]/', '_', $sizevalue['all_sizes']);
                       // $size = preg_replace('/_+/', '_', $size);

						$prod_new_Size=parent::getVal('change_prod_size_'.$size);
						if(!empty($prod_new_Size)){

							$updateVerSizeData = [
								'size'=>$prod_new_Size
							];

							parent::updateTable_f_mdl("store_product_variant_master", $updateVerSizeData, "store_product_master_id='".$store_product_master_id."' AND sanmar_size ='".$sizevalue['all_sizes']."' AND store_organization_type_master_id='1' ");
							//parent::updateTable_f_mdl('store_product_variant_master', $updateVerSizeData, 'store_product_master_id="'.$store_product_master_id.'" AND sanmar_size ="'.$sizevalue['all_sizes'].'" AND store_organization_type_master_id="1" ');
							
						}
					}
				}

				if($toggle_price_status=='true'){
					if(!empty($getProdsizeData)){
						foreach($getProdsizeData as $value){

							$replacedStr = preg_replace('/["\/ ]/', '_', $value['all_sizes']);
                        	$size = preg_replace('/_+/', '_', $replacedStr);

							//$size = preg_replace('/[ \/]/', '_', $value['all_sizes']);
                        	//$size = preg_replace('/_+/', '_', $size);
	
							//$size=str_replace(' ', '_', $value['all_sizes']);
							$prodprice=parent::getVal('product_price_size_'.$size);
							if(!empty($prodprice)){
								if(isset($_POST['price_type'])){
									if($_POST['price_type']=='flash_sale'){
	
										$updateVerData = [
											'price'=>$prodprice
										];
										//parent::updateTable_f_mdl('store_product_variant_master', $updateVerData, 'store_product_master_id="'.$store_product_master_id.'" AND sanmar_size ="'.$value['all_sizes'].'" AND store_organization_type_master_id="1" ');
										parent::updateTable_f_mdl("store_product_variant_master", $updateVerData, "store_product_master_id='".$store_product_master_id."' AND sanmar_size ='".$value['all_sizes']."' AND store_organization_type_master_id='1' ");

									}elseif($_POST['price_type']=='on_demand'){
	
										$updateVerData = [
											'price_on_demand'=>$prodprice
										];
										//parent::updateTable_f_mdl('store_product_variant_master', $updateVerData, 'store_product_master_id="'.$store_product_master_id.'" AND sanmar_size ="'.$value['all_sizes'].'" AND store_organization_type_master_id="1" ');
										parent::updateTable_f_mdl("store_product_variant_master", $updateVerData, "store_product_master_id='".$store_product_master_id."' AND sanmar_size ='".$value['all_sizes']."' AND store_organization_type_master_id='1' ");
									}
								}
							}
						}
					}

				}
				
				$returnArray = array();
				$returnArray["isSuccess"] = "1";
				$returnArray["msg"] = "Product variants updates successfully";
				
				parent::sendJson($returnArray);
			}
		}
	}
	
    function getAllOrganizationsInfo(){
        return parent::getAllOrganizationsInfo_f_mdl();
    }
	
	function getAllSaleTypeInfo(){
		return parent::getAllSaleTypeInfo_f_mdl();
	}
	
	function getAllTagsInfo(){
        return parent::getTagFilterData();
    }
	
	function getProductColorInfo(){
		$colorName = "";
		return parent::getProductColorInfo_f_mdl($colorName);
	}
	
	function getAllVendors(){
		return parent::getAllVendors_f_mdl();
	}

	function deleteProdVariant(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("action")) && parent::getVal("action") == "delete_product_var"){
				$masterProductVariantId = parent::getVal("product_variant_id");

				$updateVerData = [
					'is_ver_deleted'=>'1'
				];
				$returnArray = parent::updateTable_f_mdl('store_product_variant_master', $updateVerData, 'id="'.$masterProductVariantId.'" ');

				parent::sendJson($returnArray);
			}
		}
	}

	function productOrderUpdate($product_id, $order_by){
		parent::productOrderUpdate_f_mdl($product_id, $order_by);
		return true;
	}
	function productGroupUpdate($product_id, $group_id){
		parent::productGroupUpdate_f_mdl($product_id, $group_id);
		return true;
	}
	function productGroupStoreUpdate($store_product_id, $group_name){
		$sql = 'SELECT store_master_id FROM `store_owner_product_master` WHERE  id ="'.$store_product_id.'" ';
		$storeIdData =parent::selectTable_f_mdl($sql);
		$sql = 'SELECT yard_sign_description FROM `general_settings_master` limit 1';
		$yardsign_description =  parent::selectTable_f_mdl($sql);
		if($group_name=='Yard Signs'){
			$sm_update_data = [
				'store_description' => $yardsign_description[0]['yard_sign_description']
			];
			parent::updateTable_f_mdl('store_master',$sm_update_data,'id="'.$storeIdData[0]['store_master_id'].'"');
			
		}
		parent::productGroupStoreUpdate_f_mdl($store_product_id, $group_name);
		return true;
	}
	
	/**
	 * getPersonalizationData
	 * task 67
	 * @param  mixed $shopProductId
	 * @return void
	 */
	function getPersonalizationData($shopProductId)
	{
		$res=[];
		$sql = "SELECT store_product_master_id,is_personalization,is_required,is_item_personalization,is_item_required,personalization_item_label FROM `store_owner_product_master` WHERE shop_product_id = '" . $shopProductId . "' ";
		$storeOwnerProductsMasterDetails = parent::selectTable_f_mdl($sql);
		if(!empty($storeOwnerProductsMasterDetails)){
			$res['is_personalization'] 	= $storeOwnerProductsMasterDetails[0]['is_personalization'];
			$res['is_required'] 		= $storeOwnerProductsMasterDetails[0]['is_required'];
			$res['is_item_personalization'] 	= $storeOwnerProductsMasterDetails[0]['is_item_personalization'];
			$res['is_item_required'] 		= $storeOwnerProductsMasterDetails[0]['is_item_required'];
			$res['personalization_item_label'] 	= $storeOwnerProductsMasterDetails[0]['personalization_item_label'];
		}else{
			$res['is_personalization'] = 0;
			$res['is_required']        = 0;
			$res['is_item_personalization'] = 0;
			$res['is_item_required']        = 0;
			$res['personalization_item_label'] = '';

		}
		
		// $isRequired = 0;
		// $isPersonalization = 0;
		// $masterProductDetails = array();
		// if (isset($storeOwnerProductsMasterDetails[0]['store_product_master_id']) && $storeOwnerProductsMasterDetails[0]['store_product_master_id'] > 0) {
		// 	$sql = "SELECT id,is_personalization,is_required  FROM `store_product_master` WHERE id = '" . $storeOwnerProductsMasterDetails[0]['store_product_master_id'] . "' ";
		// 	$masterProductDetails = parent::selectTable_f_mdl($sql);
		// }

		// if (count($masterProductDetails) > 0) {
		// 	$isPersonalization = $masterProductDetails[0]['is_personalization'];
		// 	$isRequired = $masterProductDetails[0]['is_required'];

		// 	$res['is_personalization'] = $isPersonalization;
		// 	$res['is_required'] = $isRequired;
		// } else {
		// 	$res['is_personalization'] = $isPersonalization;
		// 	$res['is_required'] = $isRequired;
		// }
		common::sendJson($res);
	}

	/* Task 112 start */
	public function getUpdatePriceDetail($id){
		$sql          = 'SELECT * FROM `update_price_history` WHERE store_product_master_id="' . $id . '"';
		$priceDetails =  parent::selectTable_f_mdl($sql);
		return $priceDetails;
	}
	/* Tasl 112 end */

	/* Task 63 start */
	function getPrintLocation($vendorId) {
		$sql = 'SELECT vendor_name FROM store_vendors_master WHERE id = "'.$vendorId.'" AND status = 1';
		$vendorData = parent::selectTable_f_mdl($sql);
		
		$vendor_name = '';
		if (!empty($vendorData)) {
			$vendor_name = $vendorData[0]['vendor_name'];
		}
		$locationData = [];

		if ($vendor_name == 'FulfillEngine') {
		
			$sql = 'SELECT vendor_product_id FROM store_product_master WHERE id = "'.$this->passedId.'" AND is_deleted = 0';
			$vendorProdData = parent::selectTable_f_mdl($sql);
	
			if (!empty($vendorProdData)) {
				$sql = 'SELECT print_locations FROM fulfillengine_products_master WHERE catalog_product_id ="'.$vendorProdData[0]['vendor_product_id'].'" GROUP BY catalog_product_id';
				$vendorProdData = parent::selectTable_f_mdl($sql);
				
				if (!empty($vendorProdData)) {
					$fe_print_locations = explode(',', $vendorProdData[0]['print_locations']);
					$JsonproductArray = json_encode(array_values($fe_print_locations));
					$fe_print_locations  = str_replace (array('[', ']'), '' , $JsonproductArray);
					$sql = 'SELECT id, print_location,default_title FROM print_locations WHERE print_location IN ('.$fe_print_locations.')';
					$locationData = parent::selectTable_f_mdl($sql);
				}
			}
		} else {
			$sql = 'SELECT id, print_location,default_title FROM print_locations';
			$locationData = parent::selectTable_f_mdl($sql);
		}
		return $locationData;
	}

	public function deleteSingleCoords()
	{
		if(parent::isPOST()){
			$response ='';
			if(!empty(parent::getVal("action")) && parent::getVal("action") == "delete_single_coordinates"){
				$id = parent::getVal("single_coords_id");
				$response = parent::deleteTable_f_mdl('logo_coordinates', 'id =' . $id);
				echo json_encode($response);
			}
		}
	}

	public function saveAreaCoordinates()
	{
		if(parent::isPOST()){
		    $response ='';
		    if(!empty($_POST["store_product_master_id"])){
				$store_product_master_id = $_POST["store_product_master_id"];
				$areaTopCoorinates = $_POST['areaTopCoorinates'];
				$areaLeftCoorinates = $_POST['areaLeftCoorinates'];
				$areaWidth = $_POST['areaWidth'];
				$areaHeight = $_POST['areaHeight'];

				$sql         = 'SELECT * FROM `area_coordinates` WHERE store_product_master_id="'.$store_product_master_id.'"';
				$CordDetails =  parent::selectTable_f_mdl($sql);
				
				$coodinatesData = [
					'store_product_master_id' => $store_product_master_id,
					'area_top_coordinates' => $areaTopCoorinates,
					'area_left_coordinates' => $areaLeftCoorinates,
					'area_width' => $areaWidth,
					'area_height' => $areaHeight
				];
				if (!empty($areaTopCoorinates) && !empty($areaLeftCoorinates)) {
					
					if(!empty($CordDetails)){
						$response = parent::updateTable_f_mdl('area_coordinates', $coodinatesData, 'store_product_master_id ="'.$store_product_master_id.'"');
					}else{
						$response = parent::insertTable_f_mdl('area_coordinates',$coodinatesData);
					}
				}
			}	
		}
		echo json_encode($response);die();
	}

	public function getAreaCoords($id){
		$coordsSql  = 'SELECT * FROM area_coordinates WHERE store_product_master_id = '.$id.' ';
		$coordsData = parent::selectTable_f_mdl($coordsSql);
		return $coordsData;die();
	}

	public function saveCoordinates()
	{
		if(parent::isPOST()){
			$response ='';
			if(!empty(parent::getVal("action")) && parent::getVal("action") == "save_coordinates"){
				$store_product_master_id = parent::getVal("store_product_master_id");
				$leftCoordinates          = parent::getVal('leftCoordinates');
				$topCoordinates           = parent::getVal('topCoordinates');
				$printLocation           = parent::getVal('printLocation');
				$logoHeight              = parent::getVal('logoHeight');
				$logoWidth               = parent::getVal('logoWidth');
				
				$locationData            = explode(',', $printLocation);
				$leftCoordinateData       = explode(',', $leftCoordinates);
				$topCoordinateData        = explode(',', $topCoordinates);
				$logoHeightData          = explode(',', $logoHeight);
				$logoWidthData           = explode(',', $logoWidth);

				parent::deleteTable_f_mdl('logo_coordinates', 'store_product_master_id =' . $store_product_master_id);
				for ($i=0; $i < count($locationData); $i++) { 
					$location_id    = $locationData[$i];
					$left_coordinate = $leftCoordinateData[$i];
					$top_coordinate  = $topCoordinateData[$i];
                    $logo_height    = $logoHeightData[$i];
                    $logo_width     = $logoWidthData[$i];
					if (!empty($location_id) && ($left_coordinate !='') && ($top_coordinate !='')) {
						$coodinatesData = [
							'store_product_master_id' => $store_product_master_id,
							'top_coordinates'         => $top_coordinate,
							'left_coordinates'        => $left_coordinate,
							'print_location_id'       => $location_id,
							'logo_height'             => $logo_height,
							'logo_width'              => $logo_width
						];
						$response = parent::insertTable_f_mdl('logo_coordinates',$coodinatesData);
					}
				}
			}
			echo json_encode($response);die();
		}
	}

	public function getCoords($id){
		$coordsSql  = 'SELECT * FROM logo_coordinates WHERE store_product_master_id = '.$id.' ';
		$coordsData = parent::selectTable_f_mdl($coordsSql);
		return $coordsData;die();
	}

	public function getCustomizationDetails($store_product_master_id){
		$sql          = 'SELECT store_product_master_id,image FROM `store_product_variant_master` WHERE store_product_master_id="' . $store_product_master_id . '" AND is_ver_deleted ="0" limit 1';
		$var_list     =  parent::selectTable_f_mdl($sql);
		return $var_list;
	}
	/* Task 63 end */
	public function getAllMasterTag(){
		$sql        = 'SELECT * FROM `product_tag_master` WHERE tag_status="1"';
		$tagDetails =  parent::selectTable_f_mdl($sql);
		return $tagDetails;
	}
	
	function deleteBulkProdVariant(){
		$response =[];
		if(parent::isPOST()){
			if(!empty(parent::getVal("action")) && parent::getVal("action") == "delete-bulk-prod-veriants"){
				parent::deleteBulkProductsVariants_f_mdl(parent::getVal("variants_sizes"),parent::getVal("product_id"));
				$sql         = 'SELECT * FROM `store_product_variant_master` WHERE is_ver_deleted="0" AND store_product_master_id="'.parent::getVal("product_id").'"';
				$prodDetails =  parent::selectTable_f_mdl($sql);
				if(empty($prodDetails)){
					$updateProdData = [
						'is_deleted'=>'1'
					];
					parent::updateTable_f_mdl('store_product_master', $updateProdData, 'id="'.parent::getVal("product_id").'"');
				}
				$response["isSuccess"] = "1";
				$response["msg"] = "Product variant removed successfully";
			}
			parent::sendJson($response);die;
		}
	}

	function getAllDeletedVariantsSize(){
		$varsizeSql  = "SELECT sanmar_size AS deleted_sizes FROM store_product_variant_master WHERE store_product_master_id ='".$this->passedId."' AND is_ver_deleted = '1' GROUP BY sanmar_size HAVING COUNT(DISTINCT color) = (SELECT COUNT(DISTINCT color) FROM store_product_variant_master WHERE store_product_master_id = '".$this->passedId."') ORDER BY sanmar_size DESC";
		$delVarData = parent::selectTable_f_mdl($varsizeSql);
		return $delVarData;die();
	}

	function getAllRevertVariantsSize(){
		$varsizeSql  = "SELECT sanmar_size AS deleted_sizes FROM store_product_variant_master WHERE store_product_master_id ='".$this->passedId."' AND is_ver_deleted = '1'
		GROUP BY sanmar_size HAVING COUNT(DISTINCT color) ORDER BY sanmar_size DESC";
		$delVarData = parent::selectTable_f_mdl($varsizeSql);
		return $delVarData;die();
	}

	public function getColorFilterDropdown($id){
		$sql = 'SELECT spvm.id,spvm.color,spcm.product_color_name FROM `store_product_variant_master` as spvm
			INNER JOIN store_product_colors_master as spcm ON spvm.color= spcm.product_color
			WHERE spvm.store_product_master_id = "'.$id.'" AND spvm.is_ver_deleted="0" GROUP BY spvm.color ORDER BY spvm.color ASC
		';
		$colorInfo = parent::selectTable_f_mdl($sql);
		return $colorInfo;die();
	}

	public function saveproductImag(){
		$s3Obj = new Aws3;
		$postData=$_POST;
		$prod_color=$postData['product_color'];
		if(isset($_FILES) && !empty($_FILES)){
			$upload_dir = common::IMAGE_UPLOAD_S3_PATH;
			foreach($_FILES as $key=>$val){
				$id = str_replace('var_img_','',$key);

				if($id!=''){
					//fetch shop pro-var ids from db
					if(isset($val['name'][0]) && !empty($val['name'][0]) && empty($val['error'][0])){

						$file_arr = explode('.',$val['name'][0]);
						$ext = array_pop($file_arr);
						$file_name = time().rand(100000,999999).'.'.$ext;
						$mimeType = $val['type'][0]; //Task 59

						//Task 59 if(move_uploaded_file($val['tmp_name'][0], $upload_dir.$file_name)){
						if($s3Obj->uploadFile($upload_dir.$file_name,$val['tmp_name'][0],$mimeType))
						{	
							//update new image in database
							$spvm_update_data = [
								'image' => $file_name
							];
							parent::updateTable_f_mdl('store_product_variant_master',$spvm_update_data,'store_product_master_id="'.$postData['product_id'].'" AND color="'.$prod_color.'" AND id="'.$id.'" ');
						}
					}
					
				}
			}
		}
	}

	public function saveproductFeatureImag(){
		$s3Obj = new Aws3;
		$postData=$_POST;
		$prod_color=$postData['product_color'];
		if(isset($_FILES) && !empty($_FILES)){
			$upload_dir = common::IMAGE_UPLOAD_S3_PATH;
			foreach($_FILES as $key=>$val){
				$id = str_replace('var_img_','',$key);

				if($id!=''){
					//fetch shop pro-var ids from db
					if(isset($val['name'][0]) && !empty($val['name'][0]) && empty($val['error'][0])){

						$file_arr = explode('.',$val['name'][0]);
						$ext = array_pop($file_arr);
						$file_name = time().rand(100000,999999).'.'.$ext;
						$mimeType = $val['type'][0]; //Task 59

						//Task 59 if(move_uploaded_file($val['tmp_name'][0], $upload_dir.$file_name)){
						if($s3Obj->uploadFile($upload_dir.$file_name,$val['tmp_name'][0],$mimeType))
						{	
							//update new image in database
							$spvm_update_data = [
								'feature_image' => $file_name
							];
							parent::updateTable_f_mdl('store_product_variant_master',$spvm_update_data,'store_product_master_id="'.$postData['product_id'].'" AND color="'.$prod_color.'" AND id="'.$id.'" ');
						}
					}
					
				}
			}
		}
	}

	public function master_variant_delete_bulk(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("action")) && parent::getVal("action") == "master_variant_delete_bulk"){
				$all_var = parent::getVal("all_var");
				$variant_delete_id = parent::getVal("variant_delete_id");
				$variant_delete_id=explode(",",$variant_delete_id);
				$JsonproductVerArray = json_encode(array_values($variant_delete_id));
				$variants_ids  = str_replace(array('[', ']'), '', $JsonproductVerArray);
				$product_id = parent::getVal("product_id");
				
				$updateVerData = [
					'is_ver_deleted'=>'1'
				];
				$updateProdData = [
					'is_deleted'=>'1'
				];

				if($all_var=="false"){
					$returnArray = parent::updateTable_f_mdl('store_product_variant_master', $updateVerData, 'id IN ('.$variants_ids.') ');
				}else{
					parent::updateTable_f_mdl('store_product_master', $updateProdData, 'id="'.$product_id.'" ');
					$returnArray = parent::updateTable_f_mdl('store_product_variant_master', $updateVerData, 'store_product_master_id="'.$product_id.'" ');
				}
				parent::sendJson($returnArray);
			}
		}
	}

	public function RecoverDeletedVeriants(){
		$response =[];
		if(parent::isPOST()){
			if(!empty(parent::getVal("action")) && parent::getVal("action") == "recover-deleted-veriants"){
				$product_id=parent::getVal("product_id");
				$variants_sizes=parent::getVal("variants_sizes");
				$JsonproductArray = json_encode(array_values($variants_sizes));
				$variants_sizes  = str_replace(array('[',']'),'', $JsonproductArray);

				$update_data = [
					'is_ver_deleted'	=>'0',
					'status'			=>'1'
				];
				parent::updateTable_f_mdl('store_product_variant_master',$update_data,'store_product_master_id="'.$product_id.'" AND sanmar_size IN ('.$variants_sizes.')');
				$response["isSuccess"] = "1";
				$response["msg"] = "The selected variants have been successfully restored.";
			}
			parent::sendJson($response);die;
		}
	}
}
?>