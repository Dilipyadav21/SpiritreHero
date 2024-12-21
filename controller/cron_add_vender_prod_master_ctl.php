<?php
include_once 'model/sa_add_products_mdl.php';
$path  = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';
$s3Obj = new Aws3;
/* add vender product */
class cron_add_vender_prod_master_ctl extends sa_add_products_mdl
{
	function __construct(){
		$this->add_vendor_product_master();
	}
	
	public function add_vendor_product_master(){
		global $path;
        $s3Obj = new Aws3;
        require_once(common::EMAIL_REQUIRE_URL);
        if (strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp') !== false) {
            $objAWS = new aws_ses_smtp();
        } else if (strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail') !== false) {
            $objAWS = new sendGridEmail();
        } else {
            $objAWS = new Aws(common::AWS_ACCESS_KEY, common::AWS_SECRET_KEY, common::AWS_REGION);
        }

        $finalProdArr=$_GET['finalProdArr'];
		if(!empty($finalProdArr)){
			$vender_product_ids=explode(",",$finalProdArr);
			$addedProductNames = [];
			$logFileOpen = fopen("logs.txt", "a+") or die("Unable to open file!");
			$errorText = "</br></br></br>";
			$errorText .= "cron add vender product start time ".date("m/d/Y h:i A");
			$errorText .= "-----------------------------------------------------------------<br>";
			$errorText .=  json_encode($_REQUEST);
			$errorText .= "-----------------------------------------------------------------<br>";
			$errorText .= "</br></br></br>";
			fwrite($logFileOpen, $errorText);
			unset($errorText);
			// $finalProdArr = parent::getVal("finalProdArr");
			foreach ($vender_product_ids as $values) {
				$vendor_product_id=$values;
				$sql = 'SELECT vendor_product_id FROM store_product_master where vendor_product_id="'.$vendor_product_id.'" AND is_deleted="0" ';
				$masterproddata =  parent::selectTable_f_mdl($sql);
				if(empty($masterproddata)){

					$sql = 'SELECT * FROM temp_vendor_product_master WHERE vendor_product_id="'.$vendor_product_id.'"';
					$venderproddata =  parent::selectTable_f_mdl($sql);
					
					if(!empty($venderproddata)){
						$sql1 = 'SELECT id FROM store_vendors_master WHERE status="1" AND vendor_name="'.$venderproddata[0]['vendor_name'].'" ';
						$vender_data = parent::selectTable_f_mdl($sql1);
					}
					if($venderproddata[0]['vendor_name'] =='SanMar'){
						$addedProductNames=self::getSanMarProductByID($vendor_product_id,$vender_data[0]['id'],$addedProductNames);

					}else if($venderproddata[0]['vendor_name'] =='Fulfillengine'){
						$addedProductNames=self::getFulfillEngineProductByID($vendor_product_id,$vender_data[0]['id'],$addedProductNames);

					}else{
						$addedProductNames=self::getCustomCatProductByID($vendor_product_id,$vender_data[0]['id'],$addedProductNames);
					}
					
				}else{
					parent::deleteTable_f_mdl('temp_vendor_product_master','vendor_product_id="'.$vendor_product_id.'"');
				}
			}
			/*Send mail After add Product in Master table */

			$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
			$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';

			$sql= 'SELECT subject,body,recipients FROM `email_templates_master` WHERE id ="36" ';
			$et_data  = parent::selectTable_f_mdl($sql);

			if(!empty($et_data)){
				$subject = $et_data[0]['subject'];
				$to_email = common::SUPER_ADMIN_EMAIL;
				$from_email = common::AWS_ADMIN_EMAIL;
				$attachment = [];
				$ccMails = '';
				if($et_data[0]['recipients']){
					$recipients = $et_data[0]['recipients'];
					$recipients = str_replace(' ', '', $recipients);
					$ccMails    = explode(',', $recipients);
				}
				$productList = implode("<br>", $addedProductNames);  // Join all product names with line breaks
				$body = 'Hi Admin,<br>The following products have been added successfully to the master products list:<br><br>' . $productList . '<br>';

				// $body ='Hi Admin,<br>Selected products have been added successfully in the master products list.';
				$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body,$ccMails);	
			}

			$logFileOpen = fopen("logs.txt", "a+") or die("Unable to open file!");
			$errorText = "</br></br></br>";
			$errorText .= "----------------------------------------------------------------</br>";
			$errorText .= "cron add vender product end time ".date("m/d/Y h:i A");
			$errorText .= "----------------------------------------------------------------</br>";
			$errorText .= "</br></br></br>";
			fwrite($logFileOpen, $errorText);
			unset($errorText);

			$resultResp = array();
			$resultResp["isSuccess"] = "1";
			$resultResp["msg"] = "Product saved successfully.";
		}
	}

    public function getCustomCatProductByID($catalog_product_id,$vendorMasterId,$addedProductNames)
	{
		$s3Obj = new Aws3;
		$api_key=common::CUSTOMCAT_API_KEY;
		$api_endpoint=COMMON::CUSTOMCAT_API_ENDPOINTS;
		$count = 0;
		$variantCount = 0;
		$typreOneOrgTypeMasterId = 1;
		$typreTwoOrgTypeMasterId = 2;
		$isAnyErrorFound = '0';
        $addedProdArr=array();
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
		CURLOPT_URL => $api_endpoint.'catalog/'.$catalog_product_id.'/?api_key='.$api_key,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => 'GET',
		));
		$response = curl_exec($curl);
		curl_close($curl);
		$resultData = [];
		if (!empty($response)) {
			$resultData = json_decode($response, true);
		}
		if(isset($resultData) && !empty($resultData)){
			foreach ($resultData as $singleproduct) {
				$catalog_product_id=$singleproduct['catalog_product_id'];
				$product_created_using="API";
				$addedProductNames[] = trim($singleproduct['product_name']);
				$getInsertProductInfo = parent::adVendorProdToMaster_f_mdl(trim($singleproduct['product_name']),$product_created_using,trim($singleproduct['product_description_bullet4']),trim($singleproduct['product_description_bullet2']),$vendorMasterId,trim($catalog_product_id));
				$insertedProductId = $getInsertProductInfo['master_id'];

				if (strpos(trim($singleproduct['product_description_bullet4']), '3 Patch Shapes') !== false) {
					preg_match_all('/(\w+)\s*\(([\d.]+)\s*x\s*([\d.]+)\)/', trim($singleproduct['product_description_bullet4']), $matches);
					$shapes = $matches[1];
					$widths = $matches[2];
					$heights = $matches[3];
					$update_hatsData = [
						"customcate_hats_shap_circle"=>$shapes[0],
						"customcate_hats_shap_circle_width"=>$widths[0],
						"customcate_hats_shap_circle_height"=>$heights[0],
						"customcate_hats_shap_rectangle"=>$shapes[1],
						"customcate_hats_shap_rectangle_width"=>$widths[1],
						"customcate_hats_shap_rectangle_height"=>$heights[1],
						"customcate_hats_shap_oval"=>$shapes[2],
						"customcate_hats_shap_oval_width"=>$widths[2],
						"customcate_hats_shap_oval_height"=>$heights[2],
					];	
					parent::updateTable_f_mdl('store_product_master',$update_hatsData,'id="'.$insertedProductId.'"');	
				}

				$variantCount = 0;

				if(isset($insertedProductId) && !empty($insertedProductId)){

					foreach ($singleproduct['product_colors'] as $singleproductcolors) {
						$color_code_hexa 			='';
						$product_color_name =$singleproductcolors['color'];
						$back_image 		='https:'.$singleproductcolors['product_image'];
						$product_color_id 	=$singleproductcolors['product_color_id'];
						$product_image 		='https:'.$singleproductcolors['product_image'];
						$typreOneOrgTypeMasterId = 1;
						$typreTwoOrgTypeMasterId = 2;

						if(!empty($product_color_name)){
							$query = 'SELECT product_color FROM store_product_colors_master WHERE product_color_name="'.$product_color_name.'" ';
							$Prodcolordata = parent::selectTable_f_mdl($query);

							$slug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '',preg_replace('/\s+/', '-', $product_color_name) ));
							if(empty($Prodcolordata)){
								$insertNewColorData = [
									'product_color'  		=> "",
									'product_color_name' 	=> $product_color_name,
									'product_color_slug'  	=> $slug,
									'color_family'  		=> '',
									'status'  				=> '1',
									'vendor_id'  			=> $vendorMasterId,
									'created_on'            => date('Y-m-d H:i:s')
								];
								parent::insertTable_f_mdl('store_product_colors_master',$insertNewColorData);
							}else{
								$color_code_hexa=$Prodcolordata[0]['product_color'];
							}
						}

						foreach ($singleproductcolors['skus'] as $singleversku){
							$spiritHero_price=$spiritHero_add_price="0.00";
							$sku 	= $singleversku['catalog_sku_id'];
							$mrsp 	= number_format((float)$singleversku['mrsp'], 2);
							$cost 	= number_format((float)$singleversku['cost'], 2);
							$sizeget 	= $singleversku['size'];
							$size=str_replace('"',"",$sizeget);
							$sanmar_size 	= $singleversku['size'];
							$default_price 				= number_format((float)$singleversku['cost'], 2);
							$default_price_on_demand 	= number_format((float)$singleversku['cost'], 2);
							$in_stock=$singleversku['in_stock'];
							$catelog_color='';
							$imgUploadPath = common::SHOPIFY_DIRECTORY_PATH."/image_uploads/";
							$image = $feature_image = $spirithero_sku = $color_family = '';
							$min_qty= $weight = '0';
							$customcat_sku =$singleversku['catalog_sku_id'];

							$spiritHero_add_price=$cost+$spiritHero_price;
							$spiritHero_add_price=number_format((float)$spiritHero_add_price, 2);

							if(!empty($singleproductcolors['product_image'])){
								
								$filename = $product_image;
								$image_ext = pathinfo($filename, PATHINFO_EXTENSION);
								$image = time().rand(100000,999999).rand(100000,999999).'.'.$image_ext;
								$s3Obj->putObject(common::IMAGE_UPLOAD_S3_PATH.$image, file_get_contents($product_image));
							}
							//back image upload
							if(!empty($singleproductcolors['product_image'])){
								$filename = $back_image;
								$image_ext = pathinfo($filename, PATHINFO_EXTENSION);
								$feature_image = time().rand(100000,999999).rand(100000,999999).'.'.$image_ext;
								//	file_put_contents($imgUploadPath.$feature_image, file_get_contents($csvData[12]));
								$s3Obj->putObject(common::IMAGE_UPLOAD_S3_PATH.$feature_image, file_get_contents($back_image));
							}else{
								$filename = $product_image;
								$image_ext = pathinfo($filename, PATHINFO_EXTENSION);
								$feature_image = time().rand(100000,999999).rand(100000,999999).'.'.$image_ext;
								$s3Obj->putObject(common::IMAGE_UPLOAD_S3_PATH.$feature_image, file_get_contents(trim($product_image)));
							}

							parent::addBulkVariant_f_mdl($insertedProductId,$typreOneOrgTypeMasterId,trim($cost),trim($cost),$color_code_hexa,trim($size),$image,trim($sku),trim($customcat_sku),$spirithero_sku,$feature_image,trim($min_qty),trim($weight),trim($in_stock),trim($product_color_name),trim($product_color_id),trim($mrsp),trim($color_family),trim($catelog_color),trim($sanmar_size),trim($default_price),trim($default_price_on_demand));
							
							//parent::addBulkVariant_f_mdl($insertedProductId,$typreTwoOrgTypeMasterId,trim($cost),trim($cost),$color_code_hexa,trim($size),$image,trim($sku),trim($customcat_sku),$spirithero_sku,$feature_image,trim($min_qty),trim($weight),trim($in_stock),trim($product_color_name),trim($product_color_id),trim($mrsp),trim($color_family),trim($catelog_color),trim($sanmar_size),trim($default_price),trim($default_price_on_demand));
							
							$insertedVariantStatus = $getInsertProductInfo['isSuccess'];
							$variantCount++;
							
						}
					}

					parent::deleteTable_f_mdl('temp_vendor_product_master','vendor_product_id="'.$catalog_product_id.'"');


				}else{
					$isAnyErrorFound = '1';
				}
			}
		}else{
            $isAnyErrorFound = '1';
        }
        return $addedProductNames;

	}

	public function getSanMarProductByID($sanmar_product_unique_id,$vendorMasterId,$addedProductNames)
	{
		$s3Obj = new Aws3;
		$count = 0;
		$variantCount = 0;
		$typreOneOrgTypeMasterId = 1;
		$typreTwoOrgTypeMasterId = 2;
		$tags='';
		$isAnyErrorFound = '0';
        $addedProdArr=array();

		$product_created_using ="API";
		$sql_sanmar_prod= 'SELECT product_title,product_description,style,color_name FROM `sanmar_products_master` WHERE unique_key = "'.$sanmar_product_unique_id.'"';
		$sanameProd_data  = parent::selectTable_f_mdl($sql_sanmar_prod);
		
		if(isset($sanameProd_data) && !empty($sanameProd_data)){
			$addedProductNames[] = $sanameProd_data[0]['product_title'];
			$prod_sku=$sanameProd_data[0]['style'];
			$getInsertProductInfo = parent::adVendorProdToMaster_f_mdl(trim($sanameProd_data[0]['product_title']),$product_created_using,trim($sanameProd_data[0]['product_description']),$tags,$vendorMasterId,trim($sanmar_product_unique_id));
			$insertedProductId = $getInsertProductInfo['master_id'];
			$variantCount = 0;

			if(isset($insertedProductId) && !empty($insertedProductId)){

				$sql_prodcolor= 'SELECT * FROM `sanmar_products_master` WHERE style ="'.$prod_sku.'" GROUP BY color_name';
				$sanameProdColor_data  = parent::selectTable_f_mdl($sql_prodcolor);
				if(isset($sanameProdColor_data) && !empty($sanameProdColor_data)){

					foreach ($sanameProdColor_data as $singleproductcolors) {

						$product_color_name =$singleproductcolors['color_name'];
						if(empty($product_color_name)){
							$product_color_name='White';
						}
						$product_color_sortcode =$singleproductcolors['catelog_color'];

						$typreOneOrgTypeMasterId = 1;
						$typreTwoOrgTypeMasterId = 2;

						if(!empty($product_color_name)){

							$sqlcolorcode= "SELECT product_color FROM store_product_colors_master WHERE product_color_name='".$product_color_name."' LIMIT 1";
							$colorCode  = parent::selectTable_f_mdl($sqlcolorcode);
							

							$slug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '',preg_replace('/\s+/', '-', $product_color_name) ));
							if(empty($colorCode)){
								$insertNewColorData = [
									'product_color'  		=> '',
									'product_color_name' 	=> $product_color_name,
									'product_color_slug'  	=> $slug,
									'color_family'  		=> '',
									'status'  				=> '1',
									'vendor_id'  			=> $vendorMasterId,
									'created_on'            => date('Y-m-d H:i:s')
								];
								parent::insertTable_f_mdl('store_product_colors_master',$insertNewColorData);
							}
						}
					}

					$sql_prodver= 'SELECT * FROM `sanmar_products_master` WHERE style ="'.$prod_sku.'"';
					$sanameProdver_data  = parent::selectTable_f_mdl($sql_prodver);
					if(isset($sanameProdver_data) && !empty($sanameProdver_data)){
						foreach ($sanameProdver_data as $singleversku){
							$sku 	= $singleversku['style'];
							$product_image 	= $singleversku['product_image'];
							$front_model = $singleversku['front_model'];
							$back_model = $singleversku['back_model'];
							$color_name 	= $singleversku['color_name'];
							$size 	= $singleversku['size'];
							$catelog_color=$singleversku['catelog_color'];
							$product_ver_status=$singleversku['product_status'];
							$weight=$singleversku['piece_weight'];
							$imgUploadPath = common::SHOPIFY_DIRECTORY_PATH."/image_uploads/";
							$image = $feature_image = $spirithero_sku = $color_family = $customcat_sku = $product_color_id = $mrsp = '';
							$min_qty = $in_stock ='0';
							if($product_ver_status=='Active'){
								$in_stock='1';
							}
							$product_price='0.00';
							if(!empty($singleversku['case_sale_price'])){
								$product_price = $singleversku['case_sale_price'];
							}else{
								$product_price = $singleversku['case_price'];
							}

							$product_price =number_format((float)$product_price, 2);
                            $product_price = str_replace(",","",$product_price);
							$sanmar_size 	= $singleversku['size'];
							$default_price 	= $product_price;
							$default_price_on_demand 	= $product_price;

							$colorcode= "SELECT product_color FROM store_product_colors_master WHERE product_color_name='".$color_name."' LIMIT 1";
							$colorCodedata  = parent::selectTable_f_mdl($colorcode);
							$color_code_hexa=$colorCodedata[0]['product_color'];

							if(!empty($singleversku['front_model'])){
								$filename = $front_model;
								$image_ext = pathinfo($filename, PATHINFO_EXTENSION);
								$image = time().rand(100000,999999).rand(100000,999999).'.'.$image_ext;
								$s3Obj->putObject(common::IMAGE_UPLOAD_S3_PATH.$image, file_get_contents($front_model));
							}

							if(!empty($singleversku['front_model'])){
								$filename = $front_model;
								$image_ext = pathinfo($filename, PATHINFO_EXTENSION);
								$feature_image = time().rand(100000,999999).rand(100000,999999).'.'.$image_ext;
								$s3Obj->putObject(common::IMAGE_UPLOAD_S3_PATH.$feature_image, file_get_contents($front_model));
							}else{
								$feature_image=$image;
							}

							parent::addBulkVariant_sanmar_f_mdl($insertedProductId,$typreOneOrgTypeMasterId,trim($product_price),trim($product_price),$color_code_hexa,trim($size),$image,trim($sku),trim($customcat_sku),$spirithero_sku,$feature_image,trim($min_qty),trim($weight),trim($in_stock),trim($product_color_name),trim($product_color_id),trim($mrsp),trim($color_family),trim($catelog_color),trim($sanmar_size),trim($default_price),trim($default_price_on_demand));
							//parent::addBulkVariant_sanmar_f_mdl($insertedProductId,$typreTwoOrgTypeMasterId,trim($product_price),trim($product_price),$color_code_hexa,trim($size),$image,trim($sku),trim($customcat_sku),$spirithero_sku,$feature_image,trim($min_qty),trim($weight),trim($in_stock),trim($product_color_name),trim($product_color_id),trim($mrsp),trim($color_family),trim($catelog_color),trim($sanmar_size),trim($default_price),trim($default_price_on_demand));
							$insertedVariantStatus = $getInsertProductInfo['isSuccess'];
							$variantCount++;
						}
						parent::deleteTable_f_mdl('temp_vendor_product_master','vendor_product_id="'.$sanmar_product_unique_id.'"');
					}
				}
			}else{
				$isAnyErrorFound = '1';
			}
		}else{
            $isAnyErrorFound = '1';
        }
        return $addedProductNames;
	}

	public function getFulfillEngineProductByID($fulfillengine_catelog_prod_id,$vendorMasterId,$addedProductNames)
	{
	
		$s3Obj = new Aws3;
		$count = 0;
		$variantCount = 0;
		$typreOneOrgTypeMasterId = 1;
		$tags='';
		$product_description='';
		$isAnyErrorFound = '0';
        $addedProdArr=array();
		$product_created_using ="API";

		$sql_prod= 'SELECT id,catalog_product_id,catalog_product_name,color_name,size,sku,fulfillengine_price,fulfillengine_image FROM fulfillengine_products_master WHERE catalog_product_id = "'.$fulfillengine_catelog_prod_id.'"';
		$FulfillengineProd_data  = parent::selectTable_f_mdl($sql_prod);
		
		if(isset($FulfillengineProd_data) && !empty($FulfillengineProd_data)){
			$prod_sku=$FulfillengineProd_data[0]['sku'];
			$addedProductNames[] = trim($FulfillengineProd_data[0]['catalog_product_name']);
			$getInsertProductInfo = parent::adVendorProdToMaster_f_mdl(trim($FulfillengineProd_data[0]['catalog_product_name']),trim($product_created_using),trim($product_description),$tags,$vendorMasterId,trim($fulfillengine_catelog_prod_id));
			$insertedProductId = $getInsertProductInfo['master_id'];
			$variantCount = 0;

			if(isset($insertedProductId) && !empty($insertedProductId)){

				$sql_prodcolor= 'SELECT * FROM `fulfillengine_products_master` WHERE catalog_product_id ="'.$fulfillengine_catelog_prod_id.'" GROUP BY color_name';
				$ProdColor_data  = parent::selectTable_f_mdl($sql_prodcolor);
				if(isset($ProdColor_data) && !empty($ProdColor_data)){

					foreach ($ProdColor_data as $singleproductcolors) {

						$product_color_name =$singleproductcolors['color_name'];
						if(empty($product_color_name)){
							$product_color_name='White';
						}
						$product_color_sortcode ='';
						$typreOneOrgTypeMasterId = 1;

						if(!empty($product_color_name)){

							$sqlcolorcode= "SELECT product_color FROM store_product_colors_master WHERE product_color_name='".$product_color_name."' LIMIT 1";
							$colorCode  = parent::selectTable_f_mdl($sqlcolorcode);
							

							$slug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '',preg_replace('/\s+/', '-', $product_color_name) ));
							if(empty($colorCode)){
								$insertNewColorData = [
									'product_color'  		=> '',
									'product_color_name' 	=> $product_color_name,
									'product_color_slug'  	=> $slug,
									'color_family'  		=> '',
									'status'  				=> '1',
									'vendor_id'  			=> $vendorMasterId,
									'created_on'            => date('Y-m-d H:i:s')
								];
								parent::insertTable_f_mdl('store_product_colors_master',$insertNewColorData);
							}
						}
					}

					$sql_prodver= 'SELECT * FROM `fulfillengine_products_master` WHERE catalog_product_id ="'.$fulfillengine_catelog_prod_id.'"';
					$Prodver_data  = parent::selectTable_f_mdl($sql_prodver);
					if(isset($Prodver_data) && !empty($Prodver_data)){
						foreach ($Prodver_data as $singleversku){
							$sku 			= $singleversku['sku'];
							$product_image 	= $singleversku['fulfillengine_image'];
							$color_name 	= $singleversku['color_name'];
							$size 			= $singleversku['size'];
							$catelog_color	= '';
							$product_ver_status=$singleversku['product_status'];
							$imgUploadPath = common::SHOPIFY_DIRECTORY_PATH."/image_uploads/";
							$image = $feature_image = $spirithero_sku = $color_family = '';
							$min_qty= $weight = '0';
							$in_stock ='0';
							if($product_ver_status=='Active'){
								$in_stock='1';
							}
							$product_price='0.00';
							if(!empty($singleversku['fulfillengine_price'])){
								$product_price = $singleversku['fulfillengine_price'];
							}

							$sanmar_size 	= $singleversku['size'];
							$default_price 				= $product_price;
							$default_price_on_demand 	= $product_price;

							$colorcode= "SELECT product_color FROM store_product_colors_master WHERE product_color_name='".$color_name."' LIMIT 1";
							$colorCodedata  = parent::selectTable_f_mdl($colorcode);
							$color_code_hexa=$colorCodedata[0]['product_color'];

							// Parse the URL
							$urlParts = parse_url($singleversku['fulfillengine_image']);
							if (isset($urlParts['query'])) {
							    parse_str($urlParts['query'], $queryParameters);
							    unset($queryParameters['_ts']);
							    $newQueryString = http_build_query($queryParameters);
							    $modifiedUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . $urlParts['path'];
							    if (!empty($newQueryString)) {
							        $modifiedUrl .= '?' . $newQueryString;
							    }
							} else {
							    $modifiedUrl = $singleversku['fulfillengine_image'];
							}

							if(!empty($modifiedUrl)){
								$filename = $modifiedUrl;
								$image_ext = pathinfo($filename, PATHINFO_EXTENSION);
								if(empty($image_ext)){
									$product_imageheaders = get_headers($filename);
									if (isset($product_imageheaders[1])) {
									    $content_type = $product_imageheaders[1];
									    $content_type_parts = explode('/', $content_type);
									    $image_ext = isset($content_type_parts[1]) ? $content_type_parts[1] : '';
									      // Add a space after "image_ext" for clarity
									} else {
									    $image_ext='jpg';
									}
								}
								$image = time().rand(100000,999999).rand(100000,999999).'.'.$image_ext;
								$s3Obj->putObject(common::IMAGE_UPLOAD_S3_PATH.$image, file_get_contents($modifiedUrl));
							}
							if(!empty($modifiedUrl)){
								$filename = $modifiedUrl;
								$image_ext = pathinfo($filename, PATHINFO_EXTENSION);
								if(empty($image_ext)){
									$product_imageheaders = get_headers($filename);
									if (isset($product_imageheaders[1])) {
									    $content_type = $product_imageheaders[1];
									    $content_type_parts = explode('/', $content_type);
									    $image_ext = isset($content_type_parts[1]) ? $content_type_parts[1] : '';
									      // Add a space after "image_ext" for clarity
									} else {
									    $image_ext='jpg';
									}
								}
								$feature_image = time().rand(100000,999999).rand(100000,999999).'.'.$image_ext;
								$s3Obj->putObject(common::IMAGE_UPLOAD_S3_PATH.$feature_image, file_get_contents($modifiedUrl));
							}
							
							parent::addBulkVariant_Fulfillment_f_mdl($insertedProductId,$typreOneOrgTypeMasterId,trim($product_price),trim($product_price),$color_code_hexa,trim($size),$image,trim($sku),trim($sku),$sku,$feature_image,trim($in_stock),trim($product_color_name),trim($catelog_color),trim($sanmar_size),trim($default_price),trim($default_price_on_demand));
							
							$insertedVariantStatus = $getInsertProductInfo['isSuccess'];
							$variantCount++;
						}
						parent::deleteTable_f_mdl('temp_vendor_product_master','vendor_product_id="'.$fulfillengine_catelog_prod_id.'"');
					}
				}
			}else{
				$isAnyErrorFound = '1';
			}
		}else{
            $isAnyErrorFound = '1';
        }
        return $addedProductNames;
	}
}
?>