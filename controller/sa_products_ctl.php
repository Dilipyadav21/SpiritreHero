<?php
include_once 'model/sa_products_mdl.php';
include_once $path . 'libraries/Aws3.php';
$s3Obj = new Aws3;
class sa_products_ctl extends sa_products_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		
		common::CheckLoginSession();

		if(isset($_REQUEST['action'])){
			$action = $_REQUEST['action'];
			if($action=='updateDemand'){
				$this->updateDemand();exit;
			}else if($action=='updateFlashSale'){
				$this->updateFlashSale();exit;
			}else if($action=='delete-bulk-prod'){
				$this->bulk_delete();exit;
			}else if($action=='get_area_coords'){
				$this->get_area_coords();exit;
			}else if($action=='download_product_bulk'){
				$this->downloadProductBulk();exit;
			}else if($action=='download_product_with_items_bulk'){
				$this->downloadProductWithItemsBulk();exit;
			}else if($action=='check_template_name_exist_or_not'){
				$this->checkTemplateNameExistOrNot();exit;
			}else if($action=='add_product_in_template'){
				$this->addProductInTemplate();exit;
			}else if($action=='recover_deleted_variants'){
				$this->RecoverDeletedVariants();exit;
			}else if($action=='sync_additional_color_vendor'){
				$this->SyncAdditionalColorVendor();exit;
			}

		}
	}

	public function updateDemand()
	{
		if (isset($_POST['on_demand'])) {
			$productId = $_POST['product_id'];
			if ($_POST['on_demand']==1) {
				$onDemand = 0;	
			}
			elseif ($_POST['on_demand']==0) {
				$onDemand = 1;
			}
			parent::updateDemand_f_mdl($productId,$onDemand);
		}
		
	}
	public function updateFlashSale()
	{
		if (isset($_POST['is_flash_sale'])) {
			$productId     = $_POST['product_id'];
			if ($_POST['is_flash_sale']==1) {
				$isFlashSale = 0;	
			}
			elseif ($_POST['is_flash_sale']==0) {
				$isFlashSale = 1;
			}
			parent::updateSale_f_mdl($productId,$isFlashSale);
		}
	}
	
	function getAllProducts(){
		return parent::getAllProducts_f_mdl();
	}
	
	function productsPagination(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "products_pagination")
			{
				$record_count=0;
				$page=0;
				$current_page=1;
				$rows='10';
				$keyword='';
				
				if( (isset($_REQUEST['rows']))&&(!empty($_REQUEST['rows'])) ){
					$rows=$_REQUEST['rows'];
				}
				if( (isset($_REQUEST['keyword']))&&(!empty($_REQUEST['keyword'])) ){
					$keyword=$_REQUEST['keyword'];
				}
				if( (isset($_REQUEST['current_page']))&&(!empty($_REQUEST['current_page'])) ){
					$current_page=$_REQUEST['current_page'];
				}
				$start=($current_page-1)*$rows;
				$end=$rows;
				$sort_field = '';
				if(isset($_POST['sort_field']) && !empty($_POST['sort_field'])){
					$sort_field = $_POST['sort_field'];
				}
				$sort_type = '';
				if(isset($_POST['sort_type']) && !empty($_POST['sort_type'])){
					$sort_type = $_POST['sort_type'];
				}

				$store_type = "";
                if($_POST['store_type'] == 'is_flash_sale') {
                    $store_type = 'AND spm.is_flash_sale="1" ';
             	}
				if($_POST['store_type'] =='on_demand'){
                    $store_type = 'AND spm.on_demand="1" ';
                }

				$vendor_id = '';
				if(isset($_POST['vendor_id']) && !empty($_POST['vendor_id'])){
					$vendor_id = "AND spm.vendor_id = '".trim($_POST['vendor_id'])."' ";
				}

				$minimum_group = '';
				if(isset($_POST['minimum_group']) && !empty($_POST['minimum_group'])){
					$minimum_group = "AND spm.group_id = '".trim($_POST['minimum_group'])."' ";
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
				if(isset($keyword) && !empty($keyword)){
					$cond_keyword = "AND (
							spm.product_title LIKE '%".trim($keyword)."%' OR
							spm.product_created_using LIKE '%".trim($keyword)."%' OR
							svm.vendor_name LIKE '%".trim($keyword)."%' OR
							spm.vendor_product_id LIKE '%".trim($keyword)."%' OR
							spvm.sku LIKE '%".trim($keyword)."%' 
						)";
				}
				//$cond_order = 'ORDER BY spm.id DESC';
				$cond_order = 'ORDER BY spm.order_by ASC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}
				
				$cond_tab_status = 'AND is_deleted = "0" AND ( group_id !="0" AND (is_flash_sale="1" OR on_demand="1" ) ) ';
				
				$cond_group = 'GROUP BY spm.id ';
				$sql="
						SELECT count(spm.id) as count FROM store_product_master spm
						WHERE 1
						$cond_keyword $store_type $vendor_id $minimum_group $cond_tab_status ORDER BY spm.order_by ASC
					";
				$all_count = parent::selectTable_f_mdl($sql);
				
				$sql1="
					SELECT spm.vendor_product_id,spm.group_id, spm.id, spm.product_title, spm.is_flash_sale, spm.on_demand, spm.status,spm.vendor_id,spm.tags,spm.product_created_using,svm.vendor_name,ac.area_top_coordinates,ac.area_left_coordinates,ac.area_width,ac.area_height,ac.id as area_coords_id,spvm.image,
					(SELECT COUNT(spvm_sub.id) FROM store_product_variant_master AS spvm_sub WHERE spvm_sub.store_product_master_id = spm.id  AND spvm_sub.is_ver_deleted = '1') AS total_deleted_variants
					FROM store_product_master as spm INNER JOIN store_vendors_master as svm ON svm.id=spm.vendor_id INNER JOIN store_product_variant_master as spvm ON spvm.store_product_master_id= spm.id LEFT JOIN area_coordinates as ac ON ac.store_product_master_id= spm.id WHERE spvm.is_ver_deleted ='0'
					$cond_keyword
					$store_type
					$vendor_id
					$minimum_group
					$cond_tab_status
					$cond_group
					$cond_order
					LIMIT $start,$end
				";
				$all_list = parent::selectTable_f_mdl($sql1);

				if( (isset($all_count[0]['count']))&&(!empty($all_count[0]['count'])) ){
					$record_count=$all_count[0]['count'];
					$page=$record_count/$rows;
					$page=ceil($page);
				}
				$sr_start=0;
				if($record_count>=1){
					$sr_start=(($current_page-1)*$rows)+1;
				}
				$sr_end=($current_page)*$rows;
				if($record_count<=$sr_end){
					$sr_end=$record_count;
				}
				
				if(isset($_POST['pagination_export']) && $_POST['pagination_export']=='Y'){
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
				}else{
					$html = '';
					$html .= '<div class="row">';
					$html .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
					$html .= '<div class="table-responsive">';
					$html .= '<table class="table table-bordered table-hover master-products">';

					$html .= '<thead>';
					$html .= '<tr>';
					$html .= '<th>#</th>';
					$html .= '<th><input type="checkbox" id="ckbCheckAll"></th>';
					$html .= '<th>Vendor Product Id</th>';
					$html .= '<th>Product Title</th>';
					$html .= '<th>Flash Sale Price</th>';
					$html .= '<th>On Demand Price</th>';
					$html .= '<th>Created From</th>';
					$html .= '<th>Vendor</th>';
					$html .= '<th>Logo Max Width/Height</th>';
					$html .= '<th>Tags</th>';
					$html .= '<th>Minimum Group</th>';
					$html .= '<th width="12%">Store Type</th>';
					$html .= '<th>Status</th>';
					$html .= '<th width="18%">Actions</th>';
					
					

					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					/*
						get all product groups
					*/
					$productGroups = parent::getMinimumProductGroup_f_mdl();
					$sql1="SELECT GROUP_CONCAT(DISTINCT tag) as alltag FROM product_tag_master WHERE tag_status = '1' ORDER BY tag ASC";
					$productCustomTag = parent::selectTable_f_mdl($sql1);
					
					$all_custom_tags=[];
					if(!empty($productCustomTag)){
						$all_custom_tags = explode(',', $productCustomTag[0]['alltag']);
					}
					if(!empty($all_list))
					{
						$sr = $sr_start;
						foreach($all_list as $single){

							$sql = 'SELECT MIN(price_on_demand) as minimum_price_on_demand,MAX(price_on_demand) as maximum_price_on_demand ,MIN(price) as minimum_price_flash_sale,MAX(price) as maximum_price_flash_sale FROM store_product_variant_master WHERE store_product_master_id = "'.$single['id'].'" AND is_ver_deleted="0" ';
							$getProdPriceData = parent::selectTable_f_mdl($sql);
							$minimum_price_flash_sale=$maximum_price_flash_sale=$minimum_price_on_demand=$maximum_price_on_demand='';
							if(!empty($getProdPriceData)){
								$minimum_price_flash_sale		=$getProdPriceData[0]['minimum_price_flash_sale'];
								$maximum_price_flash_sale		=$getProdPriceData[0]['maximum_price_flash_sale'];
								$minimum_price_on_demand		=$getProdPriceData[0]['minimum_price_on_demand'];
								$maximum_price_on_demand	    =$getProdPriceData[0]['maximum_price_on_demand'];
							}

							if ($single['is_flash_sale']==1) {
								$flashSaleChecked = 'checked';
							}
							else
							{
								$flashSaleChecked = '';
							}
							if ($single['on_demand']==1) {
								$onDemandChecked = 'checked';
							}
							else
							{
								$onDemandChecked = '';
							}
							$html .= '<tr>';
							$html .= '<td>'.$sr.'</td>';
							$html .= '<td><input type="checkbox" value=' . $single["id"] . ' class="checkBoxClass"></td>';
							$html .= '<td>'.$single["vendor_product_id"].'</td>';
							$html .= '<td>'.$single["product_title"].'<button type="button" data-area_top_coordinates="'.$single["area_top_coordinates"].'" data-area_left_coordinates="'.$single["area_left_coordinates"].'" data-area_width="'.$single["area_width"].'" data-area_height="'.$single["area_height"].'" data-id="'.$single["id"].'"" data-area_coords_id="'.$single["area_coords_id"].'" data-product_image="'.common::AWS_URL.common::IMAGE_UPLOAD_PATH.$single["image"].'" class="btn btn-info btn-round btn-sm area_customization_btn" data-original-title="Customization" style="margin-left:10px;"><i class="fa-solid fa-object-group"></i></button></td>';
							$html .= '<td>$'.$minimum_price_flash_sale.' - $'.$maximum_price_flash_sale.'</td>';
							$html .= '<td>$'.$minimum_price_on_demand.' - $'.$maximum_price_on_demand.'</td>';
							$html .= '<td>'.$single["product_created_using"].'</td>';
							$html .= '<td>'.$single["vendor_name"].'</td>';
							if($single["vendor_name"]=='FulfillEngine'){
								$print_locations_dimensions = [];
								$sql_store_product_master = 'SELECT fpm.print_locations FROM fulfillengine_products_master as fpm INNER JOIN store_product_master as spm ON fpm.catalog_product_id = spm.vendor_product_id WHERE spm.id = "'.$single["id"].'" GROUP BY fpm.catalog_product_id ';
								$is_master_product = parent::selectTable_f_mdl($sql_store_product_master);
								if(!empty($is_master_product)){
									$print_locations = explode(',', $is_master_product[0]['print_locations']);
									$sql_fulfillengine_product_master = 'SELECT fpm.print_locations';
									foreach ($print_locations as $location) {
										$sql_fulfillengine_product_master .= ",fpm.{$location}_width AS {$location}_max_width";
										$sql_fulfillengine_product_master .= ",fpm.{$location}_height AS {$location}_max_height";
									}
									$sql_fulfillengine_product_master .= ' FROM fulfillengine_products_master AS fpm INNER JOIN store_product_master AS spm ON fpm.catalog_product_id = spm.vendor_product_id WHERE spm.id = "'.$single["id"].'" GROUP BY fpm.print_locations';
									$fe_print_locations_info = parent::selectTable_f_mdl($sql_fulfillengine_product_master);
									foreach ($fe_print_locations_info as $location_info) {
										$print_location = $fe_print_locations_info[0]['print_locations'];
										$dimensions = [];
										foreach ($print_locations as $location) {
											$max_width = $location_info["{$location}_max_width"];
											$max_height = $location_info["{$location}_max_height"];
											$dimensions[$location] = ['max_width' => $max_width, 'max_height' => $max_height];
										}
										$print_locations_dimensions[$print_location] = $dimensions;
									}
								}else{
									$print_locations = '';
									$print_locations_dimensions=[];
								}
								$html .= '<td>';
								foreach ($print_locations_dimensions as $labels => $values) {
									$total_print_locations = count(explode(",", $labels));
									foreach ($values as $label => $dim) {
										$html .= ucfirst($label) . ' : width '.$dim['max_width'].', height '.$dim['max_height'].'<br>';
									}
								}	
								$html .= '</td>';							
							}else{
								$html .= '<td></td>';
							}
							$matchtag='';
							if(!empty($single['tags'])){
								$tag_array = explode(',', $single['tags']);
								$matchtag_array = array_intersect($all_custom_tags, $tag_array);
								$matchtag = implode(', ', $matchtag_array);
							}
							$html .= '<td>'.$matchtag.'</td>';
							
							$GroupHtml  = '';
							$GroupHtml .= '<select name="productGroup'.$single["id"].'" name="productGroup'.$single["id"].'" class="productGroup form-control" product_id="'.$single["id"].'">';
							$GroupHtml .= '<option value="">Select one</option>';
							if($productGroups){
								foreach ($productGroups as $Groups) {
									$selected = "";
									if($single['group_id'] == $Groups['id']){
										$selected = "selected";
									}
									$GroupHtml .= '<option value="'.$Groups['id'].'" '.$selected.'>'.$Groups['group_name'].'</option>';
								}
							}							
							$GroupHtml .= '</select>';

							$html .= '<td>'.$GroupHtml.'</td>';

							$html .= '<input type="hidden" name="product_id" id="product_id" value="'.$single["id"].'"><td><input type="checkbox" id="flash_id_'.$single["id"].'" class="is_flash_sale" onclick="onclickSale('.$single["is_flash_sale"].','.$single["id"].')" name="is_flash_sale" value="'.$single["is_flash_sale"].'" '.$flashSaleChecked.'>  <label for="flash_id_'.$single["id"].'">Flash Sale</label> <br><input type="checkbox" id="demand_id_'.$single["id"].'" class="on_demand" onclick="onclickDemand('.$single["on_demand"].','.$single["id"].')" name="on_demand" value="'.$single["on_demand"].'" '.$onDemandChecked.'>  <label for="demand_id_'.$single["id"].'">On Demand</label></td>';

							if($single["status"]){
								$html .= '<td>Active</td>';
							}
							else{
								$html .= '<td>Deactive</td>';
							}
							
							//start Task 46 30-09-21
							//$html .= '<td><button type="button" class="btn btn-primary waves-effect waves-classic btn-addedit-product" data-href="sa-addedit-products.php?stkn='.parent::getVal("stkn").'&pid='.$single["id"].'">Edit</button><button type="button" class="btn btn-danger waves-effect waves-classic btn-confirm-delete-product" data-id="'.$single["id"].'">Delete</button></td>';

							$html .= '<td><button type="button" class="btn btn-primary waves-effect waves-classic btn-addedit-product" data-href="sa-addedit-products.php?stkn='.parent::getVal("stkn").'&pid='.$single["id"].'">Edit</button><button type="button" class="btn btn-danger waves-effect waves-classic btn-confirm-delete-product" data-id="'.$single["id"].'" >Delete</button></button><button type="button" class="btn btn-success waves-effect waves-classic btn-download-product" id="download_product" data-id="'.$single["id"].'"><i class="fa fa-download" style="font-weight: bold;font-size: 13px;"></i></button>';
							if($single['total_deleted_variants'] > 0){
								$html .= '<button type="button" class="btn btn-primary waves-effect waves-classic btn-recover-deleted-variants" id="recover_deleted_variants_'.$single["id"].'" data-id="'.$single["id"].'" data-original-title="Recover All Deleted Variants" data-toggle="tooltip" data-placement="top" data-trigger="hover" title="Recover All Deleted Variants"><img class="undo-delete-img" src="img/undo-delete.png"></button>';
							}
							if(!empty($single["vendor_product_id"]) && $single["vendor_name"] =='SanMar'){
								$html .= '<button type="button" class="btn btn-primary waves-effect waves-classic btn-sync-addtional-product-color" id="sync_addtional_product_color_'.$single["id"].'" data-id="'.$single["id"].'" data-vendor_product_id="'.$single["vendor_product_id"].'" data-vendor_name="'.$single["vendor_name"].'"  data-original-title="Sync additional color" data-toggle="tooltip" data-placement="top" data-trigger="hover" title="Sync additional color" style="margin-left: 5px;"><span id="add_vendor_color_'.$single["id"].'"></span><i class="fa fa-refresh" style="font-weight: bold;font-size: 13px;"></i></button>';
							}
							$html .= '</td>';
							
							// $html .= '<td><button type="button" class="btn btn-primary waves-effect waves-classic btn-addedit-product" data-href="sa-addedit-products.php?stkn='.parent::getVal("stkn").'&pid='.$single["id"].'">Edit</button></td>';							
							//end Task 46							
							
							$html .= '</tr>';	
							$sr++;
						}
					}
					else{
						$html .= '<tr>';
						$html .= '<td colspan="8" align="center">No Record Found</td>';
						$html .= '</tr>';
					}
					
					$html .= '</tbody>';
					$html .= '</table>';
					$html .= '</div>';
					$html .= '</div>';
					$html .= '</div>';

					$res['DATA'] = $html;
					$res['page_count'] = $page;
					$res['record_count']=$record_count;
					$res['sr_start']=$sr_start;
					$res['sr_end']=$sr_end;
					
					echo json_encode($res,1);
					exit;
				}
			}
		}
	}
	
	function deleteProducts(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "delete-prod"){
				$id = parent::getVal("prodId");
				parent::deleteProductsVariants_f_mdl($id);
				parent::deleteProducts_f_mdl($id);
			}
		}
	}

	function exportProducts(){
		global $s3Obj;
		if(parent::isPOST()){

			if(!empty(parent::getVal("method")) && parent::getVal("method") == "export_products"){
				$product_id = parent::getVal("product_id");
				$resultArray = array();

				if(!empty($product_id)){
					$prod_sql=" SELECT spm.id, spm.product_title,spm.product_description,spm.tags,spm.status,spm.vendor_id,spm.is_flash_sale,spm.on_demand,spm.is_deleted,svm.vendor_name FROM store_product_master spm INNER JOIN store_vendors_master svm ON svm.id=spm.vendor_id  WHERE spm.id=".$product_id." AND spm.is_deleted = 0 ";
					$pro_data = parent::selectTable_f_mdl($prod_sql);

					if(!empty($pro_data)){

						$pro_ver_sql=" SELECT * FROM store_product_variant_master WHERE store_product_master_id=".$product_id." AND status = 1 ";
						$pro_ver_data = parent::selectTable_f_mdl($pro_ver_sql);

						$a = []; $b = []; $c = []; $d = [];
						foreach ($pro_ver_data as $key => $value) {

							$color_sql=" SELECT product_color_name FROM store_product_colors_master WHERE product_color='".$value['color']."' ";
							$color_name_data = parent::selectTable_f_mdl($color_sql);
							$color_name='';
							if(!empty($color_name_data)){
								$color_name=$color_name_data[0]['product_color_name'];
							}

							if(!empty($a)){
								if($a[$key-1] == $value['color']  && $b[$key-1] == $value['size']){

									$a[$key] = $value['color'];
									$b[$key] = $value['size'];
									$c[$key] = $value['store_organization_type_master_id'];

									if($value['store_organization_type_master_id'] == 1){
										$d[$key-1]['p1_'.$value['store_organization_type_master_id']] = $value['price'];
										$d[$key-1]['p2_'.$value['store_organization_type_master_id']] = $value['price_on_demand'];
									}else{
										$d[$key-1]['p3'] = $value['price'];
										$d[$key-1]['p4'] = $value['price_on_demand'];
									}
								}else{
									$a[$key] = $value['color'];
									$b[$key] = $value['size'];
									$c[$key] = $value['store_organization_type_master_id'];

									$d[$key] = [
										'color' => $value['color'],
										'color_name' => $color_name,
										'size' => $value['size'],
										'image' => $value['image'],
										'sku' => $value['sku'],
										'feature_image' => $value['feature_image'],
										'min_qty' => $value['min_qty'],
										'weight' => $value['weight'],
										'sanmar_size' => $value['sanmar_size'],
										'default_price' => $value['default_price']
									];
									if($value['store_organization_type_master_id'] == 1){
										$d[$key]['p1_'.$value['store_organization_type_master_id']] = $value['price'];
										$d[$key]['p2_'.$value['store_organization_type_master_id']] = $value['price_on_demand'];
										$d[$key]['p3'] = '';
										$d[$key]['p4'] = '';
									}else{
										$d[$key]['p3'] = $value['price'];
										$d[$key]['p4'] = $value['price_on_demand'];
										$d[$key]['p1_1'] = '';
										$d[$key]['p2_1'] = '';
									}
								}
							}else{
								$a[$key] = $value['color'];
								$b[$key] = $value['size'];
								$c[$key] = $value['store_organization_type_master_id'];

								$d[$key] = [
									'color' => $value['color'],
									'color_name' => $color_name,
									'size' => $value['size'],
									'image' => $value['image'],
									'sku' => $value['sku'],
									'feature_image' => $value['feature_image'],
									'min_qty' => $value['min_qty'],
									'weight' => $value['weight'],
									'sanmar_size' => $value['sanmar_size'],
									'default_price' => $value['default_price']
								];
								if($value['store_organization_type_master_id'] == 1){
										$d[$key]['p1_'.$value['store_organization_type_master_id']] = $value['price'];
										$d[$key]['p2_'.$value['store_organization_type_master_id']] = $value['price_on_demand'];
										$d[$key]['p3'] = '';
										$d[$key]['p4'] = '';
									}else{
										$d[$key]['p3'] = $value['price'];
										$d[$key]['p4'] = $value['price_on_demand'];
										$d[$key]['p1_1'] = '';
										$d[$key]['p2_1'] = '';
									}
							}
						}
						// echo "<pre>";print_r($d);die;
						$resultArray = array();
						$product_name=$pro_data[0]['product_title'];
						$res = preg_replace('/[^a-zA-Z0-9_ -]/s','',$product_name);
						$product_name = str_replace(' ', '_', $res);
						$product_name = str_replace('-', '_', $product_name);
						$product_name_get=strtolower($product_name);
						// $export_file = time() . '-export.csv';
						$export_file = $product_name_get.'.csv';
						$export_file_path = 'image_uploads/_export/' . $export_file;
						$export_file_url = common::IMAGE_UPLOAD_URL.'_export/' . $export_file;
						$file_for_export_data = fopen($export_file_path,"w");
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
						header('Content-Disposition: attachment; filename='.$export_file_url);

						//$pro_data[0]['product_description'] = preg_replace('/[^A-Za-z0-9\s]/', '', $pro_data[0]['product_description']);//remove all special charactor
						$pro_data[0]['product_description']=str_replace(",","",$pro_data[0]['product_description']);
						fputcsv($file_for_export_data,
							['Product Title','Product Description','Tags','Vendor','Flash Sale Large','Flash Sale Small','On-Demand Large','On-Demand Small','Color','Size ','Image','SKU' ,'Feature Image','Min Qty','Weight (in oz)','Default Size ','Default Price']
						);
						fputcsv($file_for_export_data,
							[
								trim($pro_data[0]['product_title']),
								trim($pro_data[0]['product_description']),
								trim($pro_data[0]['tags']),
								trim($pro_data[0]['vendor_name']),
								trim($d[0]['p1_1']),
								trim($d[0]['p3']),
								trim($d[0]['p2_1']),
								trim($d[0]['p4']),
								trim($d[0]['color_name']),
								trim($d[0]['size']),
								trim(common::AWS_URL.common::IMAGE_UPLOAD_PATH.$d[0]['image']),
								trim($d[0]['sku']),
								trim(common::AWS_URL.common::IMAGE_UPLOAD_PATH.$d[0]['feature_image']),
								trim($d[0]['min_qty']),
								trim($d[0]['weight']),
								trim($d[0]['sanmar_size']),
								trim($d[0]['default_price']),
							]
						);

						unset($d[0]);
						// echo "<pre>";print_r($d);die;
						if(!empty($d)){
							foreach($d as $value){
								$image=common::AWS_URL.common::IMAGE_UPLOAD_PATH.$value['image'];
								$feature_image=common::AWS_URL.common::IMAGE_UPLOAD_PATH.$value['feature_image'];
								fputcsv($file_for_export_data,
									[
										trim(''),
										trim(''),
										trim(''),
										trim(''),
										trim($value['p1_1']),
										trim($value['p3']),
										trim($value['p2_1']),
										trim($value['p4']),
										trim($value['color_name']),
										trim($value['size']),
										trim($image),
										trim($value['sku']),
										trim($feature_image),
										trim($value['min_qty']),
										trim($value['weight']),
										trim($value['sanmar_size']),
										trim($value['default_price'])
									]
								);
							}
						}

						fputcsv($file_for_export_data,
							['']
						);
						$status = true;

						if($status == true){
							/* fwrite($file_for_export_data, $BOM); */
							fclose($file_for_export_data);
							$documentURL = $export_file_url;
							//Task 59
							
							$resultArray['SUCCESS']='TRUE';
							$resultArray['MESSAGE']='';
							$resultArray['EXPORT_URL']=$documentURL; // Task 59
						}else{
							$resultArray['SUCCESS'] = 'FALSE';
							$resultArray['MESSAGE'] = 'Records are not found.';
						}	
						common::sendJson($resultArray);
					}
				}
			}
		}
	}

	function bulk_delete()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("action")) && parent::getVal("action") == "delete-bulk-prod") {
				$ProducteMasterId = parent::getVal("product_ids");
				parent::deleteBulkProductsVariants_f_mdl($ProducteMasterId);
				parent::deleteBulkProducts_f_mdl($ProducteMasterId);
				
			}
			die;
		}
	}

	function get_area_coords(){
		$res=[];
		if (parent::isPOST()) {
			if (!empty(parent::getVal("action")) && parent::getVal("action") == "get_area_coords") {
				$area_coords_id = parent::getVal("area_coords_id");
				$sql = 'SELECT * FROM area_coordinates WHERE id="'.$area_coords_id.'" ';
				$areaData= parent::selectTable_f_mdl($sql);
				if(!empty($areaData)){
					$area_left_coordinates 	= $areaData[0]['area_left_coordinates'];
					$area_top_coordinates 	= $areaData[0]['area_top_coordinates'];
					$area_height 			= $areaData[0]['area_height'];
					$area_width 			= $areaData[0]['area_width'];
				}else{
					$area_left_coordinates 	= '';
					$area_top_coordinates 	= '';
					$area_height 			= ''; 
					$area_width 			= ''; 
				}	

				$res['area_left_coordinates'] = $area_left_coordinates;
				$res['area_top_coordinates'] = $area_top_coordinates;
				$res['area_height']=$area_height;
				$res['area_width']=$area_width;
			}
			
		}
		common::sendJson($res);
	}

	function getAllProductVendors(){
		$sql = 'select spm.vendor_id,svm.vendor_name from store_product_master as spm INNER JOIN store_vendors_master as svm ON svm.id=spm.vendor_id  group by vendor_id';
		$vendorData= parent::selectTable_f_mdl($sql);
		return $vendorData;
	}

	function getAllProductGroups(){
		$productGroups = parent::getMinimumProductGroup_f_mdl();
		return $productGroups;
	}

	function downloadProductBulk(){
		global $s3Obj;
		$resultArray = array();
		if(parent::isPOST()){
			$export_file = 'products-'.time().'.csv';
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

			$ProducteMasterId = parent::getVal("product_ids");
			$JsonproductArray = json_encode(array_values($ProducteMasterId));
			$product_master_ids  = str_replace (array('[', ']'), '' , $JsonproductArray);
			$sql = 'SELECT spm.id,spm.vendor_product_id, spm.product_title,spm.product_description,spm.tags,spm.status,spm.vendor_id,spm.is_flash_sale,spm.on_demand,spm.is_deleted,svm.vendor_name,spm.created_on,spm.product_created_using FROM store_product_master spm INNER JOIN store_vendors_master svm ON svm.id=spm.vendor_id  WHERE spm.id IN ('.$product_master_ids.') AND spm.is_deleted ="0" ';
			$pro_data = parent::selectTable_f_mdl($sql);
			fputcsv(
				$file_for_export_data,
				['Product Title','Product Description','Product Tags','Store Type','Added From','Vendor Name','Vendor Product Id','Created Date']
			);
			foreach ($pro_data as $singleProd) {
				$created_date = date('m/d/Y h:i A', strtotime($singleProd['created_on']));
				$store_flash=$store_ondemand=$product_created_using='';
				if($singleProd['is_flash_sale']==1){
					$store_flash = 'Flash Sale';
				}
				if($singleProd['on_demand']==1){
					$store_ondemand = 'On Demand';
				}
				if(!empty($singleProd['product_created_using'])){
					$product_created_using = $singleProd['product_created_using'];
				}
				
				fputcsv(
					$file_for_export_data,
					[
						trim($singleProd['product_title']),
						trim($singleProd['product_description']),
						trim($singleProd['tags']),
						trim($store_flash . (($store_flash && $store_ondemand) ? ' / ' : '') . $store_ondemand),
						trim($product_created_using),
						trim($singleProd['vendor_name']),
						trim($singleProd['vendor_product_id']),
						trim($created_date)
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
				$resultArray['SUCCESS'] = 'TRUE';
				$resultArray['MESSAGE'] = '';
				$resultArray['EXPORT_URL'] = $export_file_url; // Task 59
			} else {
				$resultArray['SUCCESS'] = 'FALSE';
				$resultArray['MESSAGE'] = 'Records are not found.';
			}
		
			common::sendJson($resultArray);
		}
	}

	function downloadProductWithItemsBulk(){
		global $s3Obj;
		if(parent::isPOST()){
			$resultArray = array();
			$ProducteMasterId = parent::getVal("product_ids");
			
			$JsonproductArray = json_encode(array_values($ProducteMasterId));
			$product_master_ids  = str_replace (array('[', ']'), '' , $JsonproductArray);

			$export_file = 'products-with-variants-'.time().'.csv';
			$export_file_path = 'image_uploads/_export/' . $export_file;
			$export_file_url = common::IMAGE_UPLOAD_URL.'_export/' . $export_file;
			$file_for_export_data = fopen($export_file_path,"w");
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
			header('Content-Disposition: attachment; filename='.$export_file_url);

			//$pro_data[0]['product_description'] = preg_replace('/[^A-Za-z0-9\s]/', '', $pro_data[0]['product_description']);//remove all special charactor
			
			fputcsv($file_for_export_data,
				['Product Title','Product Description','Tags','Vendor','Flash Sale Large','Flash Sale Small','On-Demand Large','On-Demand Small','Color','Size ','Image','SKU' ,'Feature Image','Min Qty','Weight (in oz)','Default Size ','Default Price']
			);

			if(!empty($ProducteMasterId)){
				foreach($ProducteMasterId as $product_id){
					$prod_sql=" SELECT spm.id, spm.product_title,spm.product_description,spm.tags,spm.status,spm.vendor_id,spm.is_flash_sale,spm.on_demand,spm.is_deleted,svm.vendor_name FROM store_product_master spm INNER JOIN store_vendors_master svm ON svm.id=spm.vendor_id  WHERE spm.id=".$product_id." AND spm.is_deleted = 0 ";
					$pro_data = parent::selectTable_f_mdl($prod_sql);

					if(!empty($pro_data)){

						$pro_ver_sql=" SELECT * FROM store_product_variant_master WHERE store_product_master_id=".$product_id." AND status = 1 ";
						$pro_ver_data = parent::selectTable_f_mdl($pro_ver_sql);
						$a = []; $b = []; $c = []; $d = [];
						foreach ($pro_ver_data as $key => $value) {

							$color_sql=" SELECT product_color_name FROM store_product_colors_master WHERE product_color='".$value['color']."' ";
							$color_name_data = parent::selectTable_f_mdl($color_sql);
							$color_name='';
							if(!empty($color_name_data)){
								$color_name=$color_name_data[0]['product_color_name'];
							}

							if(!empty($a)){
								if($a[$key-1] == $value['color']  && $b[$key-1] == $value['size']){

									$a[$key] = $value['color'];
									$b[$key] = $value['size'];
									$c[$key] = $value['store_organization_type_master_id'];

									if($value['store_organization_type_master_id'] == 1){
										$d[$key-1]['p1_'.$value['store_organization_type_master_id']] = $value['price'];
										$d[$key-1]['p2_'.$value['store_organization_type_master_id']] = $value['price_on_demand'];
									}else{
										$d[$key-1]['p3'] = $value['price'];
										$d[$key-1]['p4'] = $value['price_on_demand'];
									}
								}else{
									$a[$key] = $value['color'];
									$b[$key] = $value['size'];
									$c[$key] = $value['store_organization_type_master_id'];

									$d[$key] = [
										'color' => $value['color'],
										'color_name' => $color_name,
										'size' => $value['size'],
										'image' => $value['image'],
										'sku' => $value['sku'],
										'feature_image' => $value['feature_image'],
										'min_qty' => $value['min_qty'],
										'weight' => $value['weight'],
										'sanmar_size' => $value['sanmar_size'],
										'default_price' => $value['default_price']
									];
									if($value['store_organization_type_master_id'] == 1){
										$d[$key]['p1_'.$value['store_organization_type_master_id']] = $value['price'];
										$d[$key]['p2_'.$value['store_organization_type_master_id']] = $value['price_on_demand'];
										$d[$key]['p3'] = '';
										$d[$key]['p4'] = '';
									}else{
										$d[$key]['p3'] = $value['price'];
										$d[$key]['p4'] = $value['price_on_demand'];
										$d[$key]['p1_1'] = '';
										$d[$key]['p2_1'] = '';
									}
								}
							}else{
								$a[$key] = $value['color'];
								$b[$key] = $value['size'];
								$c[$key] = $value['store_organization_type_master_id'];

								$d[$key] = [
									'color' => $value['color'],
									'color_name' => $color_name,
									'size' => $value['size'],
									'image' => $value['image'],
									'sku' => $value['sku'],
									'feature_image' => $value['feature_image'],
									'min_qty' => $value['min_qty'],
									'weight' => $value['weight'],
									'sanmar_size' => $value['sanmar_size'],
									'default_price' => $value['default_price']
								];
								if($value['store_organization_type_master_id'] == 1){
										$d[$key]['p1_'.$value['store_organization_type_master_id']] = $value['price'];
										$d[$key]['p2_'.$value['store_organization_type_master_id']] = $value['price_on_demand'];
										$d[$key]['p3'] = '';
										$d[$key]['p4'] = '';
									}else{
										$d[$key]['p3'] = $value['price'];
										$d[$key]['p4'] = $value['price_on_demand'];
										$d[$key]['p1_1'] = '';
										$d[$key]['p2_1'] = '';
									}
							}
						}
						// echo "<pre>";print_r($d);die;
						$pro_data[0]['product_description']=str_replace(",","",$pro_data[0]['product_description']);
						fputcsv($file_for_export_data,
							[
								trim($pro_data[0]['product_title']),
								trim($pro_data[0]['product_description']),
								trim($pro_data[0]['tags']),
								trim($pro_data[0]['vendor_name']),
								trim($d[0]['p1_1']),
								trim($d[0]['p3']),
								trim($d[0]['p2_1']),
								trim($d[0]['p4']),
								trim($d[0]['color_name']),
								trim($d[0]['size']),
								trim(common::AWS_URL.common::IMAGE_UPLOAD_PATH.$d[0]['image']),
								trim($d[0]['sku']),
								trim(common::AWS_URL.common::IMAGE_UPLOAD_PATH.$d[0]['feature_image']),
								trim($d[0]['min_qty']),
								trim($d[0]['weight']),
								trim($d[0]['sanmar_size']),
								trim($d[0]['default_price']),
							]
						);

						unset($d[0]);
						// echo "<pre>";print_r($d);die;
						if(!empty($d)){
							foreach($d as $value){
								$image=common::AWS_URL.common::IMAGE_UPLOAD_PATH.$value['image'];
								$feature_image=common::AWS_URL.common::IMAGE_UPLOAD_PATH.$value['feature_image'];
								fputcsv($file_for_export_data,
									[
										trim(''),
										trim(''),
										trim(''),
										trim(''),
										trim($value['p1_1']),
										trim($value['p3']),
										trim($value['p2_1']),
										trim($value['p4']),
										trim($value['color_name']),
										trim($value['size']),
										trim($image),
										trim($value['sku']),
										trim($feature_image),
										trim($value['min_qty']),
										trim($value['weight']),
										trim($value['sanmar_size']),
										trim($value['default_price'])
									]
								);
							}
						}
						$status = true;
						
					}
				}

				
				if($status == true){
					/* fwrite($file_for_export_data, $BOM); */
					fclose($file_for_export_data);
					$documentURL = $export_file_url;
					//Task 59
					
					$resultArray['SUCCESS']='TRUE';
					$resultArray['MESSAGE']='';
					$resultArray['EXPORT_URL']=$documentURL; // Task 59
				}else{
					$resultArray['SUCCESS'] = 'FALSE';
					$resultArray['MESSAGE'] = 'Records are not found.';
				}	
				common::sendJson($resultArray);
			}
		
		}
	}

	function getAllProductTemplates(){
		$sql = "SELECT * FROM product_template_master order by template_name ASC";
		$productTemplateData= parent::selectTable_f_mdl($sql);
		return $productTemplateData;
	}

	function checkTemplateNameExistOrNot(){
		if(parent::isPOST()){
			$resultArray = array();
			$new_template_name = trim(parent::getVal("new_template_name"));
			
			$sql=" SELECT * FROM product_template_master WHERE template_name='".$new_template_name."' ";
			$templateData = parent::selectTable_f_mdl($sql);
			if(!empty($templateData)){
				$resultArray["isSuccess"] = "0";
				$resultArray["msg"] = "Template name is already exist. Please try another name.";
			}else{
				$resultArray["isSuccess"] = "1";
				$resultArray["msg"] = "";
			}
			common::sendJson($resultArray);
		}
	}

	function addProductInTemplate(){
		if(parent::isPOST()){
			$resultArray = array();
			$template_name = trim(parent::getVal("template_name"));
			$ProducteMasterId = parent::getVal("product_ids");
			$ptoduct_add_to = parent::getVal("ptoduct_add_to");
			if($ptoduct_add_to=='new_template'){
				$addTeam = [
					'template_name'       => $template_name,
				];
				parent::insertTable_f_mdl('product_template_master',$addTeam);

				$sql=" SELECT * FROM product_template_master WHERE template_name='".$template_name."' ";
			}else{
				$sql=" SELECT * FROM product_template_master WHERE id='".$template_name."' ";
			}

			
			$templateData = parent::selectTable_f_mdl($sql);
			if(!empty($templateData)){
				foreach($ProducteMasterId as $product_id){
					$sql=" SELECT * FROM product_templates_master_details WHERE product_templates_master_id='".$templateData[0]['id']."' AND  store_product_master_id ='".$product_id."' ";
					$ProductData = parent::selectTable_f_mdl($sql);
					if(empty($ProductData)){
						$add_teamlate_product = [
							'store_product_master_id'       => $product_id,
							'product_templates_master_id'   => $templateData[0]['id'],
							'created_on'            		=> date('Y-m-d H:i:s')
						];
						parent::insertTable_f_mdl('product_templates_master_details',$add_teamlate_product);
					}
				}
			}
			$resultArray["isSuccess"] = "1";
			$resultArray["msg"] = "Products have been successfully added to the template.";
			common::sendJson($resultArray);
		}
	}

	public function RecoverDeletedVariants(){
		if(parent::isPOST()){
			$resultArray = array();
			$product_master_id = parent::getVal("product_id");
			$update_data = [
				'is_ver_deleted'	=>'0',
				'status'			=>'1'
			];
			parent::updateTable_f_mdl('store_product_variant_master',$update_data,'store_product_master_id="'.$product_master_id.'"');
			$resultArray["isSuccess"] = "1";
			$resultArray["msg"] = "The deleted variants have been successfully restored.";
			common::sendJson($resultArray);
		}
	}

	function SyncAdditionalColorVendor() {
		global $path;
	    $s3Obj = new Aws3;

		if (parent::isPOST()) {
			$resultArray = array();
			$vendor_product_id = trim(parent::getVal("vendor_product_id"));
			$product_id = parent::getVal("product_id");
			$vendor_name = parent::getVal("vendor_name");

			// Retrieve all colors for the product in store_product_variant_master
			$sqlsku = "SELECT DISTINCT color FROM store_product_variant_master 
					   WHERE store_product_master_id = '".$product_id."' 
					   AND store_organization_type_master_id = '1' 
					   AND is_ver_deleted = '0' 
					   AND status = '1'";
			$sanmarSkuData = parent::selectTable_f_mdl($sqlsku);

			// Initialize empty array if no colors found
			$existingColors = array();
			if (!empty($sanmarSkuData)) {
				foreach ($sanmarSkuData as $colorRow) {
					$existingColors[] = $colorRow['color']; // Collect all existing colors
				}
			}

			// Check if the vendor is SanMar
			if ($vendor_name == 'SanMar') {
				// Get the style based on the vendor product ID
				$sqlsku = "SELECT style FROM sanmar_products_master 
						   WHERE unique_key = '".$vendor_product_id."' 
						   GROUP BY color_name";
				$sanmarSkuData = parent::selectTable_f_mdl($sqlsku);

				if (!empty($sanmarSkuData)) {
					$style = $sanmarSkuData[0]['style']; // Get the style for querying

					// Retrieve all colors for the product from sanmar_products_master
					$sql = "SELECT DISTINCT spm.color_name, spcm.product_color 
							FROM sanmar_products_master AS spm 
							LEFT JOIN store_product_colors_master AS spcm 
							ON spcm.product_color_name = spm.color_name 
							WHERE spm.style = '".$style."' 
							ORDER BY spm.color_name";
					$ProductColorData = parent::selectTable_f_mdl($sql);

					if (!empty($ProductColorData)) {
						// Loop through each SanMar product color and check if it exists
						foreach ($ProductColorData as $singleColor) {
							$sanmarColor = $singleColor['product_color'];

							if (!in_array($sanmarColor, $existingColors)) {
								// If color is not found in existing store product variants, insert a new variant
								// Retrieve specific SanMar product details for the missing color
								$sql = "SELECT spm.*, spcm.product_color 
										FROM sanmar_products_master AS spm 
										LEFT JOIN store_product_colors_master AS spcm 
										ON spcm.product_color_name = spm.color_name 
										WHERE spm.style = '".$style."' 
										AND spm.color_name = '".$singleColor['color_name']."'";

								$SanmarProductData = parent::selectTable_f_mdl($sql);

								foreach ($SanmarProductData as $singleversku) {
									// Prepare all product details for insertion
									$sku = $singleversku['style'];
									$product_image = $singleversku['product_image'];
									$front_model = $singleversku['front_model'];
									$color_name = $singleversku['color_name'];
									$color_code_hexa = $singleversku['product_color'];
									$size = $singleversku['size'];
									$catelog_color = $singleversku['catelog_color'];
									$product_ver_status = $singleversku['product_status'];
									$weight = $singleversku['piece_weight'];
									$created_on = @date('Y-m-d H:i:s');
									$created_on_ts = time();
									$image = $feature_image = $spirithero_sku = $color_family = $customcat_sku = $product_color_id = $mrsp = '';
									$min_qty = $in_stock = '0';

									// Set in-stock status based on product status
									if ($product_ver_status == 'Active') {
										$in_stock = '1';
									}

									// Calculate product price
									$product_price = !empty($singleversku['case_sale_price']) ? $singleversku['case_sale_price'] : $singleversku['case_price'];
									$product_price = number_format((float)$product_price, 2);
									$product_price = str_replace(",", "", $product_price);

									$sanmar_size = $singleversku['size'];
									$default_price = $product_price;
									$default_price_on_demand = $product_price;

									// Handle image upload
									if (!empty($singleversku['front_model'])) {
										$filename = $front_model;
										$image_ext = pathinfo($filename, PATHINFO_EXTENSION);
										$image = time().rand(100000,999999).rand(100000,999999).'.'.$image_ext;
										$s3Obj->putObject(common::IMAGE_UPLOAD_S3_PATH.$image, file_get_contents($front_model));
									}

									if (!empty($singleversku['front_model'])) {
										$filename = $front_model;
										$image_ext = pathinfo($filename, PATHINFO_EXTENSION);
										$feature_image = time().rand(100000,999999).rand(100000,999999).'.'.$image_ext;
										$s3Obj->putObject(common::IMAGE_UPLOAD_S3_PATH.$feature_image, file_get_contents($front_model));
									} else {
										$feature_image = $image;
									}

									// Prepare data for inserting the new product variant
									$add_teamlate_product = [
										'store_product_master_id' => $product_id,
										'store_organization_type_master_id' => '1',
										'price' => $product_price,
										'price_on_demand' => $product_price,
										'color' => $color_code_hexa,
										'size' => $size,
										'image' => $image,
										'sku' => $sku,
										'customcat_sku' => $customcat_sku,
										'spirithero_sku' => $spirithero_sku,
										'feature_image' => $feature_image,
										'min_qty' => $min_qty,
										'weight' => $weight,
										'in_stock' => $in_stock,
										'color_name' => $color_name,
										'prod_color_id' => $product_color_id,
										'prod_mrsp' => $mrsp,
										'color_family' => $color_family,
										'sanmar_color_code' => $catelog_color,
										'created_on' => $created_on,
										'created_on_ts' => $created_on_ts,
										'sanmar_size' => $sanmar_size,
										'default_price' => $default_price,
										'default_price_on_demand' => $default_price_on_demand
									];

									// Insert the new product variant into store_product_variant_master
									parent::insertTable_f_mdl('store_product_variant_master', $add_teamlate_product);
								}
							}
						}
					}
				}
			}

			$resultArray["SUCCESS"] = "TRUE";
			$resultArray["MESSAGE"] = "Products synced successfully.";
			common::sendJson($resultArray); // Uncomment this if you want to send JSON response
		}
	}
}
?>