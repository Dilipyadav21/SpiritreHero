<?php
include_once 'model/sa_add_products_mdl.php';
include_once('helpers/storeHelper.php');

$path = preg_replace('/controller(?!.*controller).*/', '', __DIR__);
include_once $path . 'libraries/Aws3.php';
$s3Obj = new Aws3;

class sa_add_products_ctl extends sa_add_products_mdl
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
		}
		common::CheckLoginSession();
	}
	function checkRequestProcess($requestFor)
	{
		if ($requestFor != "") {
			switch ($requestFor) {
				
				case "get_vendor_products":
					$this->get_vendor_products();
					break;
				case "get_customcat_products":
					$this->get_customcat_products();
					break;
				case "add_temp_vendor_prod":
					$this->add_temp_vendor_prod();
					break;
				case "remove_temp_vendor_prod":
					$this->remove_temp_vendor_prod();
					break;
				case "remove_vendor_prod_by_name":
					$this->remove_vendor_prod_by_name();
					break;
				case "add_vendor_product_in_master":
					$this->add_vendor_product_in_master();
					break;
				case "get_customcat_products_sub_category":
					$this->get_customcat_products_sub_category();
					break;
				case "get_customcat_subcategory":
					$this->get_customcat_subcategory();
					break;
				case "get_sanmar_category":
					$this->get_sanmar_category();
					break;
				case "get_vendor_products_bysearch":
					$this->get_vendor_products_bysearch();
					break;
				case "get_fulfillEngine_printingmethod":
					$this->get_fulfillEngine_printingmethod();
					break;
				case "get_fulfillEngine_category":
					$this->get_fulfillEngine_category();
					break;
				case "get_fulfillEngine_sub_category":
					$this->get_fulfillEngine_sub_category();
					break;
			}
		}
	}

	public function getAllVendor()
	{
		$sql = 'SELECT id,vendor_name FROM store_vendors_master WHERE status="1" ORDER BY id DESC';
		$getVendorData = parent::selectTable_f_mdl($sql);
		return $getVendorData;
	}

	// Task 95 Bulk delete start 
	public function get_vendor_products()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "get_vendor_products") {

				$vendor_name 		= parent::getVal("vendor_name");
				$prod_printmethod 	= parent::getVal("prod_printmethod");
				$category 			= parent::getVal("category");
				$sub_category 		= parent::getVal("sub_category");
				$page 				= parent::getVal("page");

				$cond_keyword='';
				
				if($vendor_name=='SanMar'){

					if (isset($category) && !empty($category)) {
						$cond_keyword = "AND
							category ='".$category."'";
					}
					
					$limit='24';
					$startlimit=($page-1)*$limit;
					$sql = 'SELECT id,unique_key,product_title,product_description,style,product_image,piece_price,case_price,case_sale_price,category FROM sanmar_products_master WHERE product_status="Active" '.$cond_keyword.' GROUP BY style ORDER BY id ASC LIMIT '.$startlimit.','.$limit.' ';
					$getProdData = parent::selectTable_f_mdl($sql);
					
					$html = '';
					$html1 = '';
					$min_cost='0.00';
					$maximum_cost='0.00';
					if(isset($getProdData) && !empty($getProdData)){
						
						foreach ($getProdData as $singleproduct) {
							$sanmar_products_master=$singleproduct['id'];
							$sanmar_prod_unique_id=$singleproduct['unique_key'];
							$category=$singleproduct['category'];
							$sku=$singleproduct['style'];
							if(trim($category)==''){
								$category='Uncategorized';
							}
							$sql = 'SELECT vendor_product_id FROM store_product_master WHERE vendor_product_id="'.$sanmar_prod_unique_id.'" AND is_deleted="0" ';
							$getmasterProdData = parent::selectTable_f_mdl($sql);

							$html1 = '1';
							if(!empty($singleproduct['case_sale_price'])){
								$product_price = $singleproduct['case_sale_price'];
							}else{
								$product_price = $singleproduct['case_price'];
							}

							$sql = 'SELECT MIN(case_price) as minimum_price,MAX(case_price) as maximum_price FROM sanmar_products_master WHERE style="'.$sku.'" ';
							$getProdPriceData = parent::selectTable_f_mdl($sql);
							if(!empty($getProdPriceData)){
								$min_cost		=$getProdPriceData[0]['minimum_price'];
								$maximum_cost	=$getProdPriceData[0]['maximum_price'];
							}

							$html .= '<input type="hidden" class="" value='.$sanmar_prod_unique_id.'>';
							$html .= '<input type="hidden" class="img_product_'.$sanmar_prod_unique_id.'" value="'.$singleproduct['product_image'].'">';
							$html .= '<input type="hidden" class="pro_name_'.$sanmar_prod_unique_id.'" value="'.$singleproduct['product_title'].'">';
							$html .= '<input type="hidden" class="pro_price_'.$sanmar_prod_unique_id.'" value="'.$product_price.'">';
							$html .= '<div class="col-md-3">';
							if(!empty($getmasterProdData)){
								$html .= '<div class="card vendor-products-listing" disabled>';
							}else{
								$html .= '<div class="card vendor-products-listing">';
							}
							$html .= '<p class="pro_img_wrap">';
							$html .= '<div class="ribbon ribbon-clip ribbon-success"><span class="ribbon-inner">'.$category.'</span></div>';
							$html .= '<input type="checkbox" id="choose_pro_id_'.$sanmar_prod_unique_id.'" class="choose-pro choose_pro_id " value='.$sanmar_prod_unique_id.'>';
							
							$html .= '<label for="choose_pro_id_'.$sanmar_prod_unique_id.'" class="label">';
							$html .= '<div for="choose_pro_id_'.$sanmar_prod_unique_id.'" class="vendor-products-list-img"><img src="'.$singleproduct['product_image'].'" class="img_product" alt="Product Image" srcset="'.$singleproduct['product_image'].'"></div>';
							$html .= '</label>';
							$html .= '</p>';
							$html .= '<div class="product__title" align="center">';
							$html .= '<h4>'.$singleproduct['product_title'].'</h4>';
							$html .= '<h4 class="pro__price ">$'.$min_cost.' - $'.$maximum_cost.'</h4>';
							$html .= '</div>';
							$html .= '</div>';
							$html .= '</div>';
						}
					}
					$page=$page+1;
					

					$resultArray = array();
					if(empty($getProdData)){
						$resultArray["isSuccess"] = "False";
						$resultArray["msg"] = "Product Not Found";
						$resultArray['DATA'] = $html;
						$resultArray['vendor'] = 'sanmar';
					}else if($html1 ==''){
						$resultArray["isSuccess"] = "TRUE";
						$resultArray["msg"] = "This page All Product Added  In Master";
						$resultArray['DATA'] = '';
						$resultArray['vendor'] = 'sanmar';
					}else{
						$resultArray["isSuccess"] = "TRUE";
						$resultArray["msg"] = "Product Found Successfully";
						$resultArray['DATA'] = $html;
						$resultArray['vendor'] = 'sanmar';
					}
					
				}elseif($vendor_name=='Teelaunch'){
					$category="Teelaunch";
					$api_key =common::TEELAUNCH_API_KEY;
					$api_endpoint=common::TEELAUNCH_API_ENDPOINTS;
					$limit='5';
					
					try{
						$curl = curl_init();
				
						curl_setopt_array($curl, array(
						CURLOPT_URL => $api_endpoint.'products?limit='.$limit.'&page='.$page,
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_ENCODING => '',
						CURLOPT_MAXREDIRS => 10,
						CURLOPT_TIMEOUT => 0,
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						CURLOPT_CUSTOMREQUEST => 'GET',
						CURLOPT_HTTPHEADER => array(
							'Content-Type: application/json',
							'Accept: application/json',
							'Authorization: Bearer '.$api_key
						),
						));
				
						$response = curl_exec($curl);
				
						curl_close($curl);
				
					} catch(Exception $e){
						echo 'Message: ' .$e->getMessage();
					}

					if (!empty($response)) {
						$resultData = json_decode($response, true);
					}
					// echo "<pre>";print_r($resultData['data']);die;

					$html = '';
					$html1 = '';
					if(isset($resultData['data']) && !empty($resultData['data'])){
						
						foreach ($resultData['data'] as $singleproduct) {
							$teelaunch_pro_id=$singleproduct['id'];
							$teelaunch_pro_accountId=$singleproduct['accountId'];
							$sql = 'SELECT vendor_product_id FROM store_product_master WHERE vendor_product_id="'.$teelaunch_pro_id.'" AND is_deleted="0" ';
							$getProdData = parent::selectTable_f_mdl($sql);
							if(!empty($getProdData)){
								$html1 = '1';
								$product_image ="https://".$singleproduct['product_colors'][0]['product_image'];
								$product_price=(isset($singleproduct['product_colors'][0]['skus']) && !empty($singleproduct['product_colors'][0]['skus'])) ? $singleproduct['product_colors'][0]['skus'][0]['cost'] : $singleproduct['product_colors'][1]['skus'][0]['cost'];
								$html .= '<input type="hidden" class="" value='.$teelaunch_pro_id.'>';
								$html .= '<input type="hidden" class="img_product_'.$teelaunch_pro_id.'" value="'.$product_image.'">';
								$html .= '<input type="hidden" class="pro_name_'.$teelaunch_pro_id.'" value="'.$singleproduct['name'].'">';
								$html .= '<input type="hidden" class="pro_price_'.$teelaunch_pro_id.'" value="'.$product_price.'">';
								$html .= '<div class="col-md-3">';
								$html .= '<div class="card vendor-products-listing" disabled>';
								$html .= '<p class="pro_img_wrap">';
								$html .= '<div class="ribbon ribbon-clip ribbon-success"><span class="ribbon-inner">'.$category.'</span></div>';
								$html .= '<input type="checkbox" id="choose_pro_id_'.$teelaunch_pro_id.'" class="choose-pro choose_pro_id " value='.$teelaunch_pro_id.'>';
								
								$html .= '<label for="choose_pro_id_'.$teelaunch_pro_id.'" class="label">';
								$html .= '<div for="choose_pro_id_'.$teelaunch_pro_id.'" class="vendor-products-list-img"><img src="'.$product_image.'" class="img_product" alt="Product Image" srcset="'.$product_image.'"></div>';
								$html .= '</label>';
								$html .= '</p>';
								$html .= '<div class="product__title" align="center">';
								$html .= '<h4>'.$singleproduct['name'].'</h4>';
								$html .= '<h4 class="pro__price ">$'.$product_price.'</h4>';
								$html .= '<h4 class="card-title">';
								$html .= '<input type="text" class="product_price_input spirithero_price_'.$teelaunch_pro_id.'" data-id="" value="'.$product_price.'" data-toggle="tooltip" data-placement="top" data-trigger="hover" title="">';
								$html .= '</h4>';
								$html .= '</div>';
								$html .= '</div>';
								$html .= '</div>';
							}else{
								$html1 = '1';
								$product_image ="https://teelaunch-2.s3.us-west-2.amazonaws.com".$singleproduct['mainImageUrl'];
								$product_price=(isset($singleproduct['variants'][0]['price']) && !empty($singleproduct['variants'][0]['price'])) ? $singleproduct['variants'][0]['price'] : $singleproduct['variants'][1]['price'];
								$html .= '<input type="hidden" class="" value='.$teelaunch_pro_id.'>';
								$html .= '<input type="hidden" class="img_product_'.$teelaunch_pro_id.'" value="'.$product_image.'">';
								$html .= '<input type="hidden" class="pro_name_'.$teelaunch_pro_id.'" value="'.$singleproduct['name'].'">';
								$html .= '<input type="hidden" class="pro_price_'.$teelaunch_pro_id.'" value="'.$product_price.'">';
								$html .= '<div class="col-md-3">';
								$html .= '<div class="card vendor-products-listing" >';
								$html .= '<p class="pro_img_wrap">';
								$html .= '<div class="ribbon ribbon-clip ribbon-success"><span class="ribbon-inner">'.$category.'</span></div>';
								$html .= '<input type="checkbox" id="choose_pro_id_'.$teelaunch_pro_id.'" class="choose-pro choose_pro_id " value='.$teelaunch_pro_id.'>';
								
								$html .= '<label for="choose_pro_id_'.$teelaunch_pro_id.'" class="label">';
								$html .= '<div for="choose_pro_id_'.$teelaunch_pro_id.'" class="vendor-products-list-img"><img src="'.$product_image.'" class="img_product" alt="Product Image" srcset="'.$product_image.'"></div>';
								$html .= '</label>';
								$html .= '</p>';
								$html .= '<div class="product__title" align="center">';
								$html .= '<h4>'.$singleproduct['name'].'</h4>';
								$html .= '<h4 class="pro__price ">$'.$product_price.'</h4>';
								$html .= '<h4 class="card-title">';
								$html .= '<input type="text" class="product_price_input spirithero_price_'.$teelaunch_pro_id.'" data-id="" value="'.$product_price.'" data-toggle="tooltip" data-placement="top" data-trigger="hover" title="">';
								$html .= '</h4>';
								$html .= '</div>';
								$html .= '</div>';
								$html .= '</div>';
							}
						}
					}
					$page=$page+1;
					

					$resultArray = array();
					if(empty($resultData['data'])){
						$resultArray["isSuccess"] = "False";
						$resultArray["msg"] = "Product Not Found";
						$resultArray['DATA'] = $html;
						$resultArray['vendor'] = 'teelaunch';
					}else if($html1 ==''){
						$resultArray["isSuccess"] = "TRUE";
						$resultArray["msg"] = "This page All Product Added  In Master";
						$resultArray['DATA'] = '';
						$resultArray['vendor'] = 'teelaunch';
					}else{
						$resultArray["isSuccess"] = "TRUE";
						$resultArray["msg"] = "Product Found Successfully";
						$resultArray['DATA'] = $html;
						$resultArray['vendor'] = 'teelaunch';
					}
					// echo json_encode($resultArray)
				}else if($vendor_name=='Fulfillengine'){

					$limit='24';
					$startlimit=($page-1)*$limit;

					if((isset($sub_category) && !empty($sub_category))){
						$cond_keyword = "AND printing_methods  like '%".$prod_printmethod."%'";

						$sql = 'SELECT id,catalog_product_id,catalog_product_name,color_name,size,sku,fulfillengine_price,fulfillengine_image,product_category,product_sub_category FROM fulfillengine_products_master WHERE product_status="Active" '.$cond_keyword.' AND  product_category ="'.$category.'" AND product_sub_category="'.$sub_category.'" GROUP BY catalog_product_id ORDER BY id ASC LIMIT '.$startlimit.','.$limit.' ';

					}else if ((isset($category) && !empty($category))) {
						$cond_keyword = "AND printing_methods  like '%".$prod_printmethod."%'";
						 $sql = 'SELECT id,catalog_product_id,catalog_product_name,color_name,size,sku,fulfillengine_price,fulfillengine_image,product_category,product_sub_category FROM fulfillengine_products_master WHERE product_status="Active" '.$cond_keyword.' AND  product_category ="'.$category.'" GROUP BY catalog_product_id ORDER BY id ASC LIMIT '.$startlimit.','.$limit.' ';
					}else if((isset($prod_printmethod) && !empty($prod_printmethod))){
						$cond_keyword = "AND printing_methods  like '%".$prod_printmethod."%'";
						$sql = 'SELECT id,catalog_product_id,catalog_product_name,color_name,size,sku,fulfillengine_price,fulfillengine_image,product_category,product_sub_category FROM fulfillengine_products_master WHERE product_status="Active" '.$cond_keyword.'  GROUP BY catalog_product_id ORDER BY id ASC LIMIT '.$startlimit.','.$limit.' ';

					}else{
						$sql = 'SELECT id,catalog_product_id,catalog_product_name,color_name,size,sku,fulfillengine_price,fulfillengine_image,product_category,product_sub_category FROM fulfillengine_products_master WHERE product_status="Active" '.$cond_keyword.'  GROUP BY catalog_product_id ORDER BY id ASC LIMIT '.$startlimit.','.$limit.' ';
					}
					
					$getProdData = parent::selectTable_f_mdl($sql);
					// print_r($getProdData);
					// die;
					
					$html = '';
					$html1 = '';
					$min_cost='0.00';
					$maximum_cost='0.00';
					if(isset($getProdData) && !empty($getProdData)){
						
						foreach ($getProdData as $singleproduct) {
							$fulfillengine_products_master=$singleproduct['id'];
							$fulfillengine_catelog_prod_id=$singleproduct['catalog_product_id'];
							$category=$singleproduct['product_sub_category'];
							$sku=$singleproduct['sku'];
							if(trim($category)==''){
								$category='Uncategorized';
							}
							$sql = 'SELECT vendor_product_id FROM store_product_master WHERE vendor_product_id="'.$fulfillengine_catelog_prod_id.'" AND is_deleted="0" ';
							$getmasterProdData = parent::selectTable_f_mdl($sql);

							$html1 = '1';
							$product_price = $singleproduct['fulfillengine_price'];
							
							$sql = 'SELECT MIN(fulfillengine_price) as minimum_price,MAX(fulfillengine_price) as maximum_price FROM fulfillengine_products_master WHERE sku="'.$sku.'" ';
							$getProdPriceData = parent::selectTable_f_mdl($sql);
							if(!empty($getProdPriceData)){
								$min_cost		=$getProdPriceData[0]['minimum_price'];
								$maximum_cost	=$getProdPriceData[0]['maximum_price'];
							}

							$html .= '<input type="hidden" class="" value='.$fulfillengine_catelog_prod_id.'>';
							$html .= '<input type="hidden" class="img_product_'.$fulfillengine_catelog_prod_id.'" value="'.$singleproduct['fulfillengine_image'].'">';
							$html .= '<input type="hidden" class="pro_name_'.$fulfillengine_catelog_prod_id.'" value="'.$singleproduct['catalog_product_name'].'">';
							$html .= '<input type="hidden" class="pro_price_'.$fulfillengine_catelog_prod_id.'" value="'.$product_price.'">';
							$html .= '<div class="col-md-3">';
							if(!empty($getmasterProdData)){
								$html .= '<div class="card vendor-products-listing" disabled>';
							}else{
								$html .= '<div class="card vendor-products-listing">';
							}
							$html .= '<p class="pro_img_wrap">';
							$html .= '<div class="ribbon ribbon-clip ribbon-success"><span class="ribbon-inner">'.$category.'</span></div>';
							$html .= '<input type="checkbox" id="choose_pro_id_'.$fulfillengine_catelog_prod_id.'" class="choose-pro choose_pro_id " value='.$fulfillengine_catelog_prod_id.'>';
							
							$html .= '<label for="choose_pro_id_'.$fulfillengine_catelog_prod_id.'" class="label">';
							$html .= '<div for="choose_pro_id_'.$fulfillengine_catelog_prod_id.'" class="vendor-products-list-img"><img src="'.$singleproduct['fulfillengine_image'].'" class="img_product" alt="Product Image" srcset="'.$singleproduct['fulfillengine_image'].'"></div>';
							$html .= '</label>';
							$html .= '</p>';
							$html .= '<div class="product__title" align="center">';
							$html .= '<h4>'.$singleproduct['catalog_product_name'].' - '.$singleproduct['catalog_product_id'].'</h4>';
							$html .= '<h4 class="pro__price ">$'.$min_cost.' - $'.$maximum_cost.'</h4>';
							$html .= '</div>';
							$html .= '</div>';
							$html .= '</div>';
						}
					}
					$page=$page+1;
					

					$resultArray = array();
					if(empty($getProdData)){
						$resultArray["isSuccess"] = "False";
						$resultArray["msg"] = "Product Not Found";
						$resultArray['DATA'] = $html;
						$resultArray['vendor'] = 'Fulfillengine';
					}else if($html1 ==''){
						$resultArray["isSuccess"] = "TRUE";
						$resultArray["msg"] = "This page All Product Added  In Master";
						$resultArray['DATA'] = '';
						$resultArray['vendor'] = 'Fulfillengine';
					}else{
						$resultArray["isSuccess"] = "TRUE";
						$resultArray["msg"] = "Product Found Successfully";
						$resultArray['DATA'] = $html;
						$resultArray['vendor'] = 'Fulfillengine';
					}
					
				}
			}
		}
		echo json_encode($resultArray);die;
	}

	public function get_customcat_products()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "get_customcat_products") {
				$vendor_name = parent::getVal("vendor_name");
				$category = parent::getVal("category");
				$subcategory = parent::getVal("subcategory");
				$page=parent::getVal("page");
				$limit='20';
				$api_key=common::CUSTOMCAT_API_KEY;
				$api_endpoint=COMMON::CUSTOMCAT_API_ENDPOINTS;
				
				$curl = curl_init();

				curl_setopt_array($curl, array(
				CURLOPT_URL => $api_endpoint.'catalog?api_key='.$api_key.'&category='.$category.'&page='.$page.'&subcategory='.$subcategory.'&limit=24',
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
				$html = '';
				$html1 = '';
				
				if(isset($resultData) && !empty($resultData)){
					$min_cost='0.00';
					$maximum_cost='0.00';
					
					foreach ($resultData as $singleproduct) {
						$product_price1=(isset($singleproduct['product_colors'][0]['skus']) && !empty($singleproduct['product_colors'][0]['skus'])) ? $singleproduct['product_colors'][0]['skus'][0]['cost'] : $singleproduct['product_colors'][1]['skus'][0]['cost'];
						$min_cost=$product_price1;
						$maximum_cost=$product_price1;
						foreach ($singleproduct['product_colors'] as $singleproductcolor) {
							$numbers=[];
							$numbers = array_column($singleproductcolor['skus'], 'cost');
							if(empty($numbers)){
								$numbers[]=$maximum_cost;
							}
							$min = min($numbers);
							$max = max($numbers);
							if($min<$min_cost){
								$min_cost=$min;
							}
							if($max>$maximum_cost){
								$maximum_cost=$max;
							}
						}
						// echo 'minprice'.$min_cost;
						// echo 'maxprice'.$maximum_cost;

						$catalog_product_id=$singleproduct['catalog_product_id'];
						$sql = 'SELECT vendor_product_id FROM store_product_master WHERE vendor_product_id="'.$catalog_product_id.'" AND is_deleted="0" ';
						$getProdData = parent::selectTable_f_mdl($sql);

						$html1 = '1';
						$product_image ="https://".$singleproduct['product_colors'][0]['product_image'];
						$product_price=(isset($singleproduct['product_colors'][0]['skus']) && !empty($singleproduct['product_colors'][0]['skus'])) ? $singleproduct['product_colors'][0]['skus'][0]['cost'] : $singleproduct['product_colors'][1]['skus'][0]['cost'];
						$html .= '<input type="hidden" class="" value='.$catalog_product_id.'>';
						$html .= '<input type="hidden" class="img_product_'.$catalog_product_id.'" value="'.$product_image.'">';
						$html .= '<input type="hidden" class="pro_name_'.$catalog_product_id.'" value="'.$singleproduct['product_name'].'">';
						$html .= '<input type="hidden" class="pro_price_'.$catalog_product_id.'" value="'.$product_price.'">';
						$html .= '<div class="col-md-3">';
						if(!empty($getProdData)){
							$html .= '<div class="card vendor-products-listing" disabled>';
						}else{
							$html .= '<div class="card vendor-products-listing" >';
						}
						$html .= '<p class="pro_img_wrap">';
						$html .= '<div class="ribbon ribbon-clip ribbon-success"><span class="ribbon-inner">'.$category.'</span></div>';
						$html .= '<input type="checkbox" id="choose_pro_id_'.$catalog_product_id.'" class="choose-pro choose_pro_id " value='.$catalog_product_id.'>';
						
						$html .= '<label for="choose_pro_id_'.$catalog_product_id.'" class="label">';
						$html .= '<div for="choose_pro_id_'.$catalog_product_id.'" class="vendor-products-list-img"><img src="'.$product_image.'" class="img_product" alt="Product Image" srcset="'.$product_image.'"></div>';
						$html .= '</label>';
						$html .= '</p>';
						$html .= '<div class="product__title" align="center">';
						$html .= '<h4>'.$singleproduct['product_name'].'</h4>';
						$html .= '<h4 class="pro__price ">$'.$min_cost.' - $'.$maximum_cost.'</h4>';
						$html .= '</div>';
						$html .= '</div>';
						$html .= '</div>';
						
					}
				}
				$page=$page+1;
				// if($html !=''){
				// 	$html .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" align="center">';
				// 	$html .= '<button type="button" class="btn btn-sm btn-dark waves-effect waves-classic" id="custpro_load_more_btn"  data_val="'.$page.'">Load More Products</button>';
				// 	$html .= '</div>';
				// }

				$resultArray = array();
				if(empty($resultData)){
					$resultArray["isSuccess"] = "False";
					$resultArray["msg"] = "Product Not Found";
					$resultArray['DATA'] = $html;
				}else if($html1 ==''){
					$resultArray["isSuccess"] = "TRUE";
					$resultArray["msg"] = "This page All Product Added  In Master";
					$resultArray['DATA'] = '';
				}else{
					$resultArray["isSuccess"] = "TRUE";
					$resultArray["msg"] = "Producty Found Successfully";
					$resultArray['DATA'] = $html;
				}
				echo json_encode($resultArray);
			}
			die;
		}
	}	

	private function getAllProdInSanMar()
	{
		
		$localhostWsdlUrl = '';
		// $localhostWsdlUrl = common::PRODUCT_INFO_By_Category;
		$client= new SoapClient($localhostWsdlUrl, array(
			'trace'=>true,
			'exceptions'=>true
		));
		//web service product query
		$productInfoByCategory=array(
			'category' => 'Caps'
			// [
			// 	'style' => 'PC54',
			// 	'color' => 'Neon Yellow',
			// 	'size' => 'l'
			// ]
		);
		//web service credentials
		$webServiceUser = array(
			'sanMarCustomerNumber' => common::sanMarCustomerNumber,
			'sanMarUserName' => common::sanMarUserName,
			'sanMarUserPassword' => common::sanMarUserPassword
		);
		
		$getProductInfoByCategory= array('arg0' =>$productInfoByCategory,'arg1' =>$webServiceUser );
		//calling the getProductInfoByStyleColorSize method.
		$result=$client->__soapCall('getProductInfoByCategory',array('getProductInfoByCategory' => $getProductInfoByCategory) );
		$array = json_decode(json_encode($result), true);
		echo "<pre>";print_r($array);

		die();
		
	}

	public function add_temp_vendor_prod()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "add_temp_vendor_prod") {
				$vendor_pro_id 		= parent::getVal("vendor_pro_id");
				$vendor_name 		= parent::getVal("vendor_name");
				$vendor_pro_image 	= parent::getVal("vendor_pro_image");
				$vendor_pro_name 	= parent::getVal("vendor_pro_name");
				$vendor_pro_price 	= parent::getVal("vendor_pro_price");
				$spirithero_price 	= parent::getVal("spirithero_price");
				$resData = array();

				$sql = 'SELECT vendor_product_id FROM store_product_master WHERE vendor_product_id="'.$vendor_pro_id.'" AND is_deleted="0" ';
				$getVendorData = parent::selectTable_f_mdl($sql);

				if(!empty($getVendorData)){
					$resData['isSuccess']='0';
					$resData['msg']='This product already available in master product.';
				}else{
					$tvpm_insert_data = [
						'vendor_product_id' => $vendor_pro_id,
						'vendor_name' 		=> $vendor_name,
						'product_image' 	=> $vendor_pro_image,
						'product_name' 		=> $vendor_pro_name,
						'product_price'	 	=> $vendor_pro_price,
						'spiritHero_price' 	=> $spirithero_price,
						'created_on' 		=> @date('Y-m-d H:i:s'),
					];
					$resData=parent::insertTable_f_mdl('temp_vendor_product_master', $tvpm_insert_data);
					$temp_vender_prod_id=$resData['insert_id'];
					$html = '';
					$html = '<div class ="append-prod-data" id="div_appen_prod_'.$vendor_pro_id.'" >';
					$html .= '<button class="temp-prod-delete-btn remove-selected-product" data-id="'.$vendor_pro_id.'" data-toggle="tooltip" data-placement="top" data-trigger="hover" >X</button>';
					$html .= '<div><img src="'.$vendor_pro_image.'" alt="Product Image" class="selected-prod-img"></div>';
					$html .= '</div>';

					$resData['isSuccess'] = '1';
					$resData['DATA'] = $html;
				}
				echo json_encode($resData);die;
			}
			die;
		}
	}

	public function remove_temp_vendor_prod()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "remove_temp_vendor_prod") {
				$vendor_pro_id = parent::getVal("vendor_pro_id");
				$resData=parent::deleteTable_f_mdl('temp_vendor_product_master', 'vendor_product_id ="'. $vendor_pro_id.'"');
				echo json_encode($resData);die;
			}
			die;
		}
	}

	public function getTempVendorProducts()
	{
		$sql = 'SELECT * FROM temp_vendor_product_master ORDER BY id ASC';
		$getVendorTempProdData = parent::selectTable_f_mdl($sql);
		return $getVendorTempProdData;
	}

	public function remove_vendor_prod_by_name()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "remove_vendor_prod_by_name") {
				$vendor_name = parent::getVal("vendor_name");
				parent::deleteTable_f_mdl('temp_vendor_product_master', 'vendor_name !="'.$vendor_name.'" ');
				$sql = 'SELECT * FROM temp_vendor_product_master ORDER BY id ASC';
				$getVendorTempProdData = parent::selectTable_f_mdl($sql);
				$html = '';
				foreach($getVendorTempProdData as $value){
					if(empty($value['is_batch_no']) || $value['is_batch_no']==''){
						$style='style="display:block;"'; 
					 }else{
						$style='style="display:none;"'; 
					 }
					$html .= '<div class="append-prod-data" id="div_appen_prod_'.$value['vendor_product_id'].'">';
					$html .= '<button id ="temp-prod-delete_'.$value['vendor_product_id'].'" class="temp-prod-delete-btn remove-selected-product" data-id="'.$value['vendor_product_id'].'" data-toggle="tooltip" data-placement="top" data-trigger="hover" '.$style.' >X</button>';
					$html .= '<div>';
					$html .= '<img src="'.$value['product_image'].'" alt="Product Image" class="selected-prod-img">';
					$html .= '</div>';
					$html .= '</div>';
				}
				$resData['DATA']=$html;
				echo json_encode($resData);die;
			}
			die;
		}
	}

	public function add_vendor_product_in_master()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "add_vendor_product_in_master") {
				$finalProdArr = parent::getVal("finalProdArr");
				$JsonproductArray = json_encode(array_values($finalProdArr));
				$ver_id  = str_replace(array('[', ']'), '', $JsonproductArray);
				$vender_ids=parent::UpdateBetch_f_mdl(trim($ver_id));

				$url   = common::SITE_URL."cron_add_vender_prod_master.php?finalProdArr=".$vender_ids;

				self::backgroundCurlRequest($url, 'get', [], $vender_ids);

				$resultResp = array();
				$resultResp["isSuccess"] = "1";
				$resultResp["msg"] = "Products will be added in background. You will be notified via email once done.";
				common::sendJson($resultResp);
			}
			die;
		}
	}

	/**
	 * backgroundCurlRequest
	 * This funtion use as a background process
	 * @param  mixed $url
	 * @param  mixed $method
	 * @param  mixed $post_parameters
	 * @param  mixed $storeId
	 * @return void
	 */
	public function backgroundCurlRequest($url, $method='get', $post_parameters =[],$finalProdArr){
		
		if(is_array($post_parameters)){
			$params = "";
			foreach ($post_parameters as $key=>$value){
				$params .= $key."=".urlencode($value).'&';
			}
			$params = rtrim($params, "&");
		} else {
			$params = $post_parameters;
		}
		
		$path="/var/www/html/vender_prod_".date('y-m-d_h:i:s').".json";
		//$command = "/usr/bin/curl -X '".$method."' -d '".$params."' --url '".$url."' >> $path 2> /dev/null &";
        $command = '/usr/bin/curl -H \'Content-Type: application/json\' -d \'' . $params . '\' --url \'' . $url . '\' >> '.$path.' 2> /dev/null &';
		exec($command);
	}

	public function get_customcat_products_sub_category()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "get_customcat_products_sub_category") {
				$vendor_name = parent::getVal("vendor_name");
				$category = parent::getVal("prod_category");
				$subcategory = parent::getVal("sub_category");
				$page=parent::getVal("page");
				$limit='20';
				$api_key=common::CUSTOMCAT_API_KEY;
				$api_endpoint=COMMON::CUSTOMCAT_API_ENDPOINTS;

				$curl = curl_init();

				curl_setopt_array($curl, array(
				CURLOPT_URL => $api_endpoint.'catalog?api_key='.$api_key.'&category='.$category.'&page='.$page.'&subcategory='.$subcategory.'&limit=24',
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
				$html = '';
				$html1 = '';
				
				if(isset($resultData) && !empty($resultData)){
					$min_cost='0.00';
					$maximum_cost='0.00';
					foreach ($resultData as $singleproduct) {
						$product_price1=(isset($singleproduct['product_colors'][0]['skus']) && !empty($singleproduct['product_colors'][0]['skus'])) ? $singleproduct['product_colors'][0]['skus'][0]['cost'] : $singleproduct['product_colors'][1]['skus'][0]['cost'];
						$min_cost=$product_price1;
						$maximum_cost=$product_price1;
						foreach ($singleproduct['product_colors'] as $singleproductcolor) {
							$numbers=[];
							$numbers = array_column($singleproductcolor['skus'], 'cost');
							if(empty($numbers)){
								$numbers[]=$maximum_cost;
							}
							$min = min($numbers);
							$max = max($numbers);
							if($min<$min_cost){
								$min_cost=$min;
							}
							if($max>$maximum_cost){
								$maximum_cost=$max;
							}
						}

						$catalog_product_id=$singleproduct['catalog_product_id'];
						$sql = 'SELECT vendor_product_id FROM store_product_master WHERE vendor_product_id="'.$catalog_product_id.'" AND is_deleted="0" ';
						$getProdData = parent::selectTable_f_mdl($sql);

						$html1 = '1';
						$product_image ="https://".$singleproduct['product_colors'][0]['product_image'];
						$product_price=(isset($singleproduct['product_colors'][0]['skus']) && !empty($singleproduct['product_colors'][0]['skus'])) ? $singleproduct['product_colors'][0]['skus'][0]['cost'] : $singleproduct['product_colors'][1]['skus'][0]['cost'];
						$html .= '<input type="hidden" class="" value='.$catalog_product_id.'>';
						$html .= '<input type="hidden" class="img_product_'.$catalog_product_id.'" value="'.$product_image.'">';
						$html .= '<input type="hidden" class="pro_name_'.$catalog_product_id.'" value="'.$singleproduct['product_name'].'">';
						$html .= '<input type="hidden" class="pro_price_'.$catalog_product_id.'" value="'.$product_price.'">';
						$html .= '<div class="col-md-3">';
						if(!empty($getProdData)){
							$html .= '<div class="card vendor-products-listing" disabled>';
						}else{
							$html .= '<div class="card vendor-products-listing" >';
						}
						$html .= '<p class="pro_img_wrap">';
						$html .= '<div class="ribbon ribbon-clip ribbon-success"><span class="ribbon-inner">'.$category.'</span></div>';
						$html .= '<input type="checkbox" id="choose_pro_id_'.$catalog_product_id.'" class="choose-pro choose_pro_id " value='.$catalog_product_id.'>';
						
						$html .= '<label for="choose_pro_id_'.$catalog_product_id.'" class="label">';
						$html .= '<div for="choose_pro_id_'.$catalog_product_id.'" class="vendor-products-list-img"><img src="'.$product_image.'" class="img_product" alt="Product Image" srcset="'.$product_image.'"></div>';
						$html .= '</label>';
						$html .= '</p>';
						$html .= '<div class="product__title" align="center">';
						$html .= '<h4>'.$singleproduct['product_name'].'</h4>';
						$html .= '<h4 class="pro__price ">$'.$min_cost.' - $'.$maximum_cost.'</h4>';
						$html .= '</div>';
						$html .= '</div>';
						$html .= '</div>';
					}
				}
				$page=$page+1;
				// if($html !=''){
				// 	$html .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" align="center">';
				// 	$html .= '<button type="button" class="btn btn-sm btn-dark waves-effect waves-classic" id="custpro_load_more_btn"  data_val="'.$page.'">Load More Products</button>';
				// 	$html .= '</div>';
				// }

				$resultArray = array();
				if(empty($resultData)){
					$resultArray["isSuccess"] = "False";
					$resultArray["msg"] = "Product Not Found";
					$resultArray['DATA'] = $html;
				}else if($html1 ==''){
					$resultArray["isSuccess"] = "TRUE";
					$resultArray["msg"] = "This page All Product Added  In Master";
					$resultArray['DATA'] = '';
				}else{
					$resultArray["isSuccess"] = "TRUE";
					$resultArray["msg"] = "Producty Found Successfully";
					$resultArray['DATA'] = $html;
				}
				echo json_encode($resultArray);
			}
			die;
		}
	}

	public function get_customcat_subcategory()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "get_customcat_subcategory") {
				$vendor_name = parent::getVal("vendor_name");
				$category = parent::getVal("category");

				$api_key=common::CUSTOMCAT_API_KEY;
				$api_endpoint=COMMON::CUSTOMCAT_API_ENDPOINTS;

				$curl = curl_init();
				curl_setopt_array($curl, array(
				CURLOPT_URL => $api_endpoint.'catalogcategory/?api_key='.$api_key,
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
				$dropdownHtml = '';
				if(isset($resultData) && !empty($resultData)){
					$dropdownHtml .= '<option value="">All</option>';
					foreach ($resultData as $prodsubcategory) {
						$category_data=$prodsubcategory['category'];
						if($category_data==$category){
							foreach ($prodsubcategory['subcategories'] as $subcategory) {
								$dropdownHtml .= '<option value="'.$subcategory.'">'.$subcategory.'</option>';
							}
						}	
					}
				}
				$resultArray['subCategoryData'] = $dropdownHtml;
				
				echo $dropdownHtml;die;
			}
			die;
		}
	}

	public function get_sanmar_category()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "get_sanmar_category") {
				$vendor_name = parent::getVal("vendor_name");
				$resultArray=array();
				
				$sqlcate = "SELECT distinct(category) FROM sanmar_products_master ORDER BY category ASC";
				$sanmarCatData = parent::selectTable_f_mdl($sqlcate);
			
				
				$dropdownHtml = '';
				if(isset($sanmarCatData) && !empty($sanmarCatData)){
					$dropdownHtml .= '<option value="">All</option>';
					foreach ($sanmarCatData as $prodcategory) {
						$category=$prodcategory['category'];
						if(trim($category)==''){
							//$dropdownHtml .= '<option value="'.$category.'">Uncategorized</option>';	
						}else{
							$dropdownHtml .= '<option value="'.$category.'">'.$category.'</option>';
						}	
					}
				}
				$resultArray['SanCategoryData'] = $dropdownHtml;
				
				echo $dropdownHtml;die;
			}
			die;
		}
	}

	public function get_vendor_products_bysearch()
	{
		$resultArray = array();
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "get_vendor_products_bysearch") {
				$vendor_name = parent::getVal("vendor_name");
				$search_keyword = parent::getVal("search_keyword");
				$cond_keyword='';
				
				if($vendor_name=='SanMar'){

					if (isset($search_keyword) && !empty($search_keyword)) {
						$cond_keyword = "AND (
							product_title LIKE '%" . trim($search_keyword) . "%' OR 
							style LIKE '%" . trim($search_keyword) . "%' OR
							category LIKE '%" . trim($search_keyword) . "%' 
						)";
					}
					
					$sql = 'SELECT id,unique_key,product_title,product_description,style,product_image,piece_price,case_price,case_sale_price,category FROM sanmar_products_master WHERE product_status="Active" '.$cond_keyword.' GROUP BY style ORDER BY id ASC ';
					$getProdData = parent::selectTable_f_mdl($sql);
					
					$html = '';
					$html1 = '';
					if(isset($getProdData) && !empty($getProdData)){
						
						foreach ($getProdData as $singleproduct) {
							$sanmar_products_master=$singleproduct['id'];
							$sanmar_prod_unique_id=$singleproduct['unique_key'];
							$category=$singleproduct['category'];
							$sku=$singleproduct['style'];
							if(trim($category)==''){
								$category='Uncategorized';
							}
							$sql = 'SELECT vendor_product_id FROM store_product_master WHERE vendor_product_id="'.$sanmar_prod_unique_id.'" AND is_deleted="0" ';
							$getmasterProdData = parent::selectTable_f_mdl($sql);
							
							$html1 = '1';
							if(!empty($singleproduct['case_sale_price'])){
								$product_price = $singleproduct['case_sale_price'];
							}else{
								$product_price = $singleproduct['case_price'];
							}

							$sql = 'SELECT MIN(case_price) as minimum_price,MAX(case_price) as maximum_price FROM sanmar_products_master WHERE style="'.$sku.'" ';
							$getProdPriceData = parent::selectTable_f_mdl($sql);
							if(!empty($getProdPriceData)){
								$min_cost		=$getProdPriceData[0]['minimum_price'];
								$maximum_cost	=$getProdPriceData[0]['maximum_price'];
							}
							$html .= '<input type="hidden" class="" value='.$sanmar_prod_unique_id.'>';
							$html .= '<input type="hidden" class="img_product_'.$sanmar_prod_unique_id.'" value="'.$singleproduct['product_image'].'">';
							$html .= '<input type="hidden" class="pro_name_'.$sanmar_prod_unique_id.'" value="'.$singleproduct['product_title'].'">';
							$html .= '<input type="hidden" class="pro_price_'.$sanmar_prod_unique_id.'" value="'.$product_price.'">';
							$html .= '<div class="col-md-3">';
							if(!empty($getmasterProdData)){
								$html .= '<div class="card vendor-products-listing" disabled>';
							}else{
								$html .= '<div class="card vendor-products-listing" >';
							}
							$html .= '<p class="pro_img_wrap">';
							$html .= '<div class="ribbon ribbon-clip ribbon-success"><span class="ribbon-inner">'.$category.'</span></div>';
							$html .= '<input type="checkbox" id="choose_pro_id_'.$sanmar_prod_unique_id.'" class="choose-pro choose_pro_id " value='.$sanmar_prod_unique_id.'>';
							$html .= '<label for="choose_pro_id_'.$sanmar_prod_unique_id.'" class="label">';
							$html .= '<div for="choose_pro_id_'.$sanmar_prod_unique_id.'" class="vendor-products-list-img"><img src="'.$singleproduct['product_image'].'" class="img_product" alt="Product Image" srcset="'.$singleproduct['product_image'].'"></div>';
							$html .= '</label>';
							$html .= '</p>';
							$html .= '<div class="product__title" align="center">';
							$html .= '<h4>'.$singleproduct['product_title'].'</h4>';
							$html .= '<h4 class="pro__price ">$'.$min_cost.' - $'.$maximum_cost.'</h4>';
							
							$html .= '</div>';
							$html .= '</div>';
							$html .= '</div>';
						}
					}
					
					$resultArray = array();
					if(empty($getProdData)){
						$resultArray["isSuccess"] = "False";
						$resultArray["msg"] = "Product Not Found";
						$resultArray['DATA'] = $html;
						$resultArray['vendor'] = 'sanmar';
					}else if($html1 ==''){
						$resultArray["isSuccess"] = "TRUE";
						$resultArray["msg"] = "This page All Product Added  In Master";
						$resultArray['DATA'] = '';
						$resultArray['vendor'] = 'sanmar';
					}else{
						$resultArray["isSuccess"] = "TRUE";
						$resultArray["msg"] = "Product Found Successfully";
						$resultArray['DATA'] = $html;
						$resultArray['vendor'] = 'sanmar';
					}
				}else if($vendor_name=='Fulfillengine'){

					if (isset($search_keyword) && !empty($search_keyword)) {
						$cond_keyword = "AND (
							catalog_product_name LIKE '%" . trim($search_keyword) . "%' OR 
							catalog_product_id LIKE '%" . trim($search_keyword) . "%'
						)";
					}

					$sql = 'SELECT id,catalog_product_id,catalog_product_name,color_name,size,sku,fulfillengine_price,fulfillengine_image,product_category,product_sub_category FROM fulfillengine_products_master WHERE product_status="Active" '.$cond_keyword.' GROUP BY catalog_product_id ORDER BY id ASC ';
					$getProdData = parent::selectTable_f_mdl($sql);
					$html = '';
					$html1 = '';
					if(isset($getProdData) && !empty($getProdData)){
						
						foreach ($getProdData as $singleproduct) {
							$fulfill_products_master=$singleproduct['id'];
							$fulfill_prod_unique_id=$singleproduct['catalog_product_id'];
							$category=$singleproduct['product_sub_category'];
							if(trim($category)==''){
								$category='Uncategorized';
							}
							$sku=$singleproduct['sku'];
							$product_price = $singleproduct['fulfillengine_price'];

							$sql = 'SELECT vendor_product_id FROM store_product_master WHERE vendor_product_id="'.$fulfill_prod_unique_id.'" AND is_deleted="0" ';
							$getmasterProdData = parent::selectTable_f_mdl($sql);
							
							$html1 = '1';
							$sql = 'SELECT MIN(fulfillengine_price) as minimum_price,MAX(fulfillengine_price) as maximum_price FROM fulfillengine_products_master WHERE sku="'.$sku.'" ';
							$getProdPriceData = parent::selectTable_f_mdl($sql);
							if(!empty($getProdPriceData)){
								$min_cost		=$getProdPriceData[0]['minimum_price'];
								$maximum_cost	=$getProdPriceData[0]['maximum_price'];
							}
							$html .= '<input type="hidden" class="" value='.$fulfill_prod_unique_id.'>';
							$html .= '<input type="hidden" class="img_product_'.$fulfill_prod_unique_id.'" value="'.$singleproduct['fulfillengine_image'].'">';
							$html .= '<input type="hidden" class="pro_name_'.$fulfill_prod_unique_id.'" value="'.$singleproduct['catalog_product_name'].'">';
							$html .= '<input type="hidden" class="pro_price_'.$fulfill_prod_unique_id.'" value="'.$product_price.'">';
							$html .= '<div class="col-md-3">';
							if(!empty($getmasterProdData)){
								$html .= '<div class="card vendor-products-listing" disabled>';
							}else{
								$html .= '<div class="card vendor-products-listing" >';
							}
							$html .= '<p class="pro_img_wrap">';
							$html .= '<div class="ribbon ribbon-clip ribbon-success"><span class="ribbon-inner">'.$category.'</span></div>';
							$html .= '<input type="checkbox" id="choose_pro_id_'.$fulfill_prod_unique_id.'" class="choose-pro choose_pro_id " value='.$fulfill_prod_unique_id.'>';
							$html .= '<label for="choose_pro_id_'.$fulfill_prod_unique_id.'" class="label">';
							$html .= '<div for="choose_pro_id_'.$fulfill_prod_unique_id.'" class="vendor-products-list-img"><img src="'.$singleproduct['fulfillengine_image'].'" class="img_product" alt="Product Image" srcset="'.$singleproduct['fulfillengine_image'].'"></div>';
							$html .= '</label>';
							$html .= '</p>';
							$html .= '<div class="product__title" align="center">';
							$html .= '<h4>'.$singleproduct['catalog_product_name'].' - '.$singleproduct['catalog_product_id'].'</h4>';
							$html .= '<h4 class="pro__price ">$'.$min_cost.' - $'.$maximum_cost.'</h4>';
							
							$html .= '</div>';
							$html .= '</div>';
							$html .= '</div>';
						}
					}
					
					$resultArray = array();
					if(empty($getProdData)){
						$resultArray["isSuccess"] = "False";
						$resultArray["msg"] = "Product Not Found";
						$resultArray['DATA'] = $html;
						$resultArray['vendor'] = 'Fulfillengine';
					}else if($html1 ==''){
						$resultArray["isSuccess"] = "TRUE";
						$resultArray["msg"] = "This page All Product Added  In Master";
						$resultArray['DATA'] = '';
						$resultArray['vendor'] = 'Fulfillengine';
					}else{
						$resultArray["isSuccess"] = "TRUE";
						$resultArray["msg"] = "Product Found Successfully";
						$resultArray['DATA'] = $html;
						$resultArray['vendor'] = 'Fulfillengine';
					}
				}
			}
			
		}
		echo json_encode($resultArray);die;
	}

	public function get_fulfillEngine_category()
	{
		if (parent::isPOST()){
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "get_fulfillEngine_category") {
				$vendor_name = parent::getVal("vendor_name");
				$printingmethod = parent::getVal("printingmethod");
				$resultArray=array();
				
				$sqlcate ="SELECT DISTINCT product_category FROM fulfillengine_products_master WHERE (printing_methods != '' OR printing_methods IS NOT NULL) AND printing_methods LIKE '%".$printingmethod."%' ORDER BY catalog_product_id DESC ";
				$CategoryData = parent::selectTable_f_mdl($sqlcate);
				$dropdownHtml = '';
				if(isset($CategoryData) && !empty($CategoryData)){
					$dropdownHtml .= '<option value="">All</option>';
					foreach ($CategoryData as $prodcategory) {
						$category=$prodcategory;
						
						if(trim($prodcategory['product_category'])==''){
							//$dropdownHtml .= '<option value="'.$category.'">Uncategorized</option>';	
						}else{
							$dropdownHtml .= '<option value="'.$prodcategory['product_category'].'">'.$prodcategory['product_category'].'</option>';
						}	
					}
				}

				$resultArray['FulfillCategoryData'] = $dropdownHtml;
				
				echo $dropdownHtml;die;
			}
			die;
		}
	}

	public function get_fulfillEngine_sub_category()
	{
		if (parent::isPOST()){
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "get_fulfillEngine_sub_category") {
				$vendor_name = parent::getVal("vendor_name");
				$printingmethod = parent::getVal("printingmethod");
				$prodcategory = parent::getVal("prodcategory");
				$resultArray=array();

				

				$sqlcate ="SELECT DISTINCT product_sub_category FROM fulfillengine_products_master WHERE (printing_methods != '' OR printing_methods IS NOT NULL) AND printing_methods LIKE '%".$printingmethod."%' AND product_category='".$prodcategory."' ORDER BY catalog_product_id DESC ";
				$CategoryData = parent::selectTable_f_mdl($sqlcate);
				
				$dropdownHtml = '';
				$dropdownHtml .= '<option value="">All</option>';
				if(isset($CategoryData) && !empty($CategoryData)){
					
					foreach ($CategoryData as $prodcategory) {
						$category=$prodcategory;
						
						if(trim($prodcategory['product_sub_category'])==''){
							//$dropdownHtml .= '<option value="'.$category.'">Uncategorized</option>';	
						}else{
							$dropdownHtml .= '<option value="'.$prodcategory['product_sub_category'].'">'.$prodcategory['product_sub_category'].'</option>';
						}	
					}
				}
				$resultArray['FulfillsubCategoryData'] = $dropdownHtml;
				
				echo $dropdownHtml;die;
			}
			die;
		}
	}

	public function get_fulfillEngine_printingmethod()
	{
		if (parent::isPOST()){
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "get_fulfillEngine_printingmethod") {
				$vendor_name = parent::getVal("vendor_name");
				$resultArray=array();
				
				$sqlcate = "SELECT GROUP_CONCAT(DISTINCT method) AS printing_methods
				FROM (
				  SELECT DISTINCT
					TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(printing_methods, ',', n.n), ',', -1)) AS method
				  FROM fulfillengine_products_master
				  INNER JOIN (
					SELECT 1 AS n
					UNION SELECT 2
					UNION SELECT 3
				  ) n
				  ON CHAR_LENGTH(printing_methods)
					-CHAR_LENGTH(REPLACE(printing_methods, ',', '')) >= n.n - 1
				) subquery";
				$CategoryData = parent::selectTable_f_mdl($sqlcate);
				
				$dropdownHtml = '';
				if(isset($CategoryData) && !empty($CategoryData)){
					$CategoryData = explode(",", ltrim($CategoryData[0]['printing_methods'], ','));
					$dropdownHtml .= '<option value="">All</option>';
					foreach ($CategoryData as $prodcategory) {
						$category=$prodcategory;
						
						if(trim($category)==''){
							//$dropdownHtml .= '<option value="'.$category.'">Uncategorized</option>';	
						}else{
							$dropdownHtml .= '<option value="'.$category.'">'.$category.'</option>';
						}	
					}
				}
				$resultArray['FulfillCategoryData'] = $dropdownHtml;
				
				echo $dropdownHtml;die;
			}
			die;
		}
	}
}

?>