<?php
include_once 'model/sa_store_view_mdl.php';

class sa_store_view_ctl extends sa_store_view_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		
		if(isset($_REQUEST['action'])){
			$action = $_REQUEST['action'];
			if($action=='edit_store_basic_post'){
				$this->edit_store_basic_post();exit;
			}else if($action=='fetch_main_product_list'){
				$this->fetch_main_product_list();exit;
			}else if($action=='add_new_products'){
				$this->add_new_products();exit;
			}else if($action=='add_option_sort_list'){
				$this->add_option_sort_list();exit;
			}else if($action=='get_all_products_by_store'){
				$this->get_all_products_by_store();exit;
			}else if($action=='store_product_details_post'){
				$this->store_product_details_post();exit;
			}else if($action=='get_all_variants_by_product'){
				$this->get_all_variants_by_product();exit;
			}else if($action=='color_filter_dropdown'){
				$this->getColorFilterDropdown();exit;
			}else if($action=='save_all_variants_by_product_post'){
				$this->save_all_variants_by_product_post();exit;
			}else if($action=='store_variant_delete_post'){
				$this->store_variant_delete_post();exit;
			}else if($action=='store_product_delete_post'){
				$this->store_product_delete_post();exit;
			}else if($action=='edit_store_apply_logo'){
				$this->edit_store_apply_logo();exit;
			}else if($action=='edit_store_new_logo_post'){
				$this->edit_store_new_logo_post();exit;
			}else if($action=='store_logo_make_default_post'){
				$this->store_logo_make_default_post();exit;
			}else if($action=='store_logo_delete_post'){
				$this->store_logo_delete_post();exit;
			}else if($action=='edit_store_manager_post'){
				$this->edit_store_manager_post();exit;
			}else if($action=='edit_store_manager_permission_post'){
				$this->edit_store_manager_permission_post();exit;
			}else if($action=='send_invitation_email_to_store_manager'){
				$this->send_invitation_email_to_store_manager();exit;
			}else if($action=='edit_store_sort_list_post'){
				$this->edit_store_sort_list_post();exit;
			}else if($action=='edit_store_owner_address_post'){
				$this->edit_store_owner_address_post();exit;
			}else if($action=='edit_store_owner_silver_delivery_address_post'){
				$this->edit_store_owner_silver_delivery_address_post();exit;
			}else if($action=='edit_st_store_owner_silver_delivery_address_post'){
				$this->edit_st_store_owner_silver_delivery_address_post();exit;
			}else if($action=='edit_store_setting_post'){
				$this->edit_store_setting_post();exit;
			}else if($action=='edit_store_fundraising'){
				$this->edit_store_fundraising();exit;
			}else if($action=='save_store_level_settings'){
				$this->save_store_level_settings();exit;
			}else if($action=='only_fundrising_save_store_level_settings'){
				//Task 43 28-09-2021
				$this->only_fundrising_save_store_level_settings();exit;
			}else if($action=='saveproductImag'){
				$this->saveproductImag();exit;
			}else if($action=='updatePrice'){// Task 51 add new action
				$this->updatePrice();exit;
			}
		}
		
		common::CheckLoginSession();
	}

	public function priceOverride($product_id = 0){
		$priceBaseArr = array();
		$override_fts = 0;
		$override_ftt = 0;

		$sql1 = 'SELECT fullfilment_type_second, fullfilment_type_third FROM `general_settings_master` WHERE status=1';
		$priceBase = parent::selectTable_f_mdl($sql1);
		if($priceBase){
			$override_fts = $priceBase[0]['fullfilment_type_second'];
			$override_ftt = $priceBase[0]['fullfilment_type_third'];
		}

		/*if($product_id > 0){
			$sql = "SELECT override_fts, override_ftt FROM `store_product_master` WHERE id='$product_id'";
			$priceBase = parent::selectTable_f_mdl($sql);
			if($priceBase){
				if($priceBase[0]['override_fts'] > 0){
					$override_fts = $priceBase[0]['override_fts'];
				}
				if($priceBase[0]['override_ftt'] > 0){
					$override_ftt = $priceBase[0]['override_ftt'];	
				}
			}
		}*/

		$priceBaseArr['override_fts'] = $override_fts;
		$priceBaseArr['override_ftt'] = $override_ftt;

		return $priceBaseArr;
	}

	public function saveproductImag(){
		if(isset($_FILES) && !empty($_FILES)){
			$upload_dir = 'image_uploads/';

			//fetch default logo for merge with product image
			$sql = 'SELECT logo_image FROM store_design_logo_master WHERE store_master_id="'.$_POST['store_master_id'].'" AND is_default="1"';
			$default_logo_data = parent::selectTable_f_mdl($sql);

			foreach($_FILES as $key=>$val){
				$id = str_replace('var_img_','',$key);

				if($id!=''){
					//fetch shop pro-var ids from db
					$sql = 'SELECT id, shop_product_id, shop_variant_id FROM `store_owner_product_variant_master`
					WHERE id="'.$id.'"
					AND store_owner_product_master_id="'.$_POST['product_id'].'"
					';
					$var_data = parent::selectTable_f_mdl($sql);

					if(!empty($var_data)){
						if(isset($val['name'][0]) && !empty($val['name'][0]) && empty($val['error'][0])){

							$file_arr = explode('.',$val['name'][0]);
							$ext = array_pop($file_arr);
							$file_name = time().rand(100000,999999).'.'.$ext;

							if(move_uploaded_file($val['tmp_name'][0], $upload_dir.$file_name)){
								//update new image in database
								if(isset($default_logo_data[0]['logo_image']) && !empty($default_logo_data[0]['logo_image']) && file_exists($upload_dir.$default_logo_data[0]['logo_image'])){
									$merged_image = parent::merge_two_images($upload_dir,$file_name,$default_logo_data[0]['logo_image']);
								}else{
									$merged_image = $file_name;
								}

								$sopvm_update_data = [
									'image' => $merged_image,
									'original_image' => $file_name
								];
								parent::updateTable_f_mdl('store_owner_product_variant_master',$sopvm_update_data,'id="'.$id.'" AND store_owner_product_master_id="'.$_POST['product_id'].'"');
							}
						}
					}
				}
			}
		}
	}
	
	# store-section
	public function getShopCountryList(){
		require_once('lib/shopify.php');

		$sql = 'SELECT shop_name, token FROM `shop_management` WHERE shop_name="'.common::PARENT_STORE_NAME.'"';
		$shop_data =  parent::selectTable_f_mdl($sql);
		if(isset($shop_data[0]) && !empty($shop_data[0])){
			$shopifyObject = new ShopifyClient($shop_data[0]["shop_name"], $shop_data[0]["token"], common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			try {
				$params = [
					'fields'=>'name,code,provinces'
				];
				$shop_country = $shopifyObject->call('GET', '/admin/api/2023-04/countries.json',$params);
			}
			catch (ShopifyApiException $e)
			{
			}
			catch (ShopifyCurlException $e)
			{
			}
		}

		if(isset($shop_country) && !empty($shop_country)){
			return $shop_country;
		}else{
			return [];
		}
	}
	public function getStoreSaleTypeList(){
		$sql = 'SELECT id, sale_type, sale_short_code FROM `store_sale_type_master` WHERE status=1';
		return parent::selectTable_f_mdl($sql);
	}

	public function getOptionSortList($store_master_id){
		$sql = 'SELECT id, store_master_id, input_type, option_val, label_val,is_required FROM `sort_list_option_master` WHERE store_master_id='.$store_master_id;
		return parent::selectTable_f_mdl($sql);
	}

	public function getGeneralSettings(){
		$sql = 'SELECT * FROM `general_settings_master` WHERE id=1';
		return parent::selectTable_f_mdl($sql);
	}

	public function getTagFilterData($store_master_id){
		$sql = 'SELECT MAX(LENGTH(tags) - LENGTH(REPLACE(tags, ",", "")) + 1) as max_tag_cnt
		FROM `store_owner_product_master`
		WHERE store_master_id = "'.$store_master_id.'"
		';
		$max_tag_cnt_arr = parent::selectTable_f_mdl($sql);
		if(isset($max_tag_cnt_arr[0]) && !empty($max_tag_cnt_arr[0])){
			$max_tag_cnt = $max_tag_cnt_arr[0]['max_tag_cnt'];

			$sql = 'SELECT GROUP_CONCAT(DISTINCT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(tags, ",", n.n), ",", -1)) SEPARATOR "," ) as `uni_tags`
			FROM `store_owner_product_master` t
			CROSS JOIN (SELECT 1 as n ';
			for($i=2;$i<=$max_tag_cnt;$i++){
				$sql .= ' UNION ALL SELECT '.$i;
			}
			$sql .= ' ) n
			ORDER BY `uni_tags`;';
			return parent::selectTable_f_mdl($sql);
		}else{
			return [];
		}
	}
	public function fetch_product_color_family($store_master_id){
		$sql = 'SELECT color,store_product_colors_master.color_family,store_color_family_master.color_family_color FROM `store_owner_product_variant_master`
		
		LEFT JOIN `store_owner_product_master` ON `store_owner_product_master`.id = `store_owner_product_variant_master`.store_owner_product_master_id
		
		LEFT JOIN store_product_colors_master ON store_product_colors_master.product_color = store_owner_product_variant_master.color
		
		LEFT JOIN store_color_family_master ON store_color_family_master.color_family_name = store_product_colors_master.color_family
		
		WHERE `store_owner_product_master`.store_master_id = "'.$store_master_id.'"
		GROUP BY color
		';
		return parent::selectTable_f_mdl($sql);
	}
	public function get_store_details($store_master_id){
		$sql = 'SELECT `store_master`.* FROM `store_master`
		WHERE `store_master`.id="'.$store_master_id.'"
		';
		$list_data = parent::selectTable_f_mdl($sql);
		if(!empty($list_data)){
			//fetch logo data
			$sql = 'SELECT id, logo_image, is_default FROM `store_design_logo_master`
			WHERE store_master_id = "'.$store_master_id.'"
			ORDER BY id DESC
			';
			$logo_data = parent::selectTable_f_mdl($sql);
			$list_data[0]['logo_data'] = $logo_data;

			//fetch manager data
			$sql = 'SELECT id, first_name, last_name, email, mobile, module_permission FROM `store_owner_manager_master`
			WHERE store_master_id = "'.$store_master_id.'"
			ORDER BY first_name ASC
			';
			$manager_data = parent::selectTable_f_mdl($sql);
			$list_data[0]['manager_data'] = $manager_data;

			//fetch sort list
			$sql = 'SELECT sort_list_name, sort_list_index FROM `store_sort_list_master`
			WHERE store_master_id = "'.$store_master_id.'"
			ORDER BY cast(sort_list_index as unsigned) ASC
			';
			$sort_list_data = parent::selectTable_f_mdl($sql);
			$list_data[0]['sort_list_data'] = $sort_list_data;

			//fetch address data
			$sql = 'SELECT * FROM `store_owner_address_master`
			WHERE store_master_id = "'.$store_master_id.'"
			ORDER BY id DESC
			LIMIT 1
			';
			$owner_address_data = parent::selectTable_f_mdl($sql);
			$list_data[0]['owner_address_data'] = $owner_address_data;

			//fetch silver delivery address data
			$sql = 'SELECT * FROM `store_owner_silver_delivery_address_master`
			WHERE store_master_id = "'.$store_master_id.'"
			ORDER BY id DESC
			LIMIT 1
			';
			$owner_silver_delivery_address_data = parent::selectTable_f_mdl($sql);
			$list_data[0]['owner_silver_delivery_address_data'] = $owner_silver_delivery_address_data;

			//fetch request design data
			$sql = 'SELECT `store_request_design_master`.id, front_design_text, back_design_text, designer_notes, apparel_color_code, ink_color_code, `store_free_design_product_master`.product_title, `store_free_design_product_master`.product_image
			FROM `store_request_design_master`
			LEFT JOIN `store_free_design_product_master` ON `store_free_design_product_master`.id = `store_request_design_master`.store_free_design_product_master_id
			WHERE store_request_design_master.store_master_id = "'.$store_master_id.'"
			LIMIT 1
			';
			$request_design_data = parent::selectTable_f_mdl($sql);
			if(!empty($request_design_data)){
				$sql = 'SELECT image FROM `store_request_design_reference_images_master`
				WHERE store_request_design_master_id = "'.$request_design_data[0]['id'].'"
				';
				$request_design_ref_img_data = parent::selectTable_f_mdl($sql);
				$request_design_data[0]['request_design_ref_img_data'] = $request_design_ref_img_data;
			}
			$list_data[0]['request_design_data'] = $request_design_data;

			return $list_data;
		}else{
			header('location:sa-stores.php?stkn='.$_POST['stkn']);
		}
	}
	public function edit_store_basic_post(){
		if(isset($_POST['store_master_id']) && !empty($_POST['store_master_id'])){
			$sql = 'SELECT id, shop_collection_id FROM store_master WHERE id="'.$_POST['store_master_id'].'"';
			$store_data = parent::selectTable_f_mdl($sql);
			if(!empty($store_data)){
				$sm_update_data = [
					'store_name' => trim($_POST['store_name']),
					'store_description' => trim($_POST['store_description']),
					'store_open_date' => $_POST['store_open_date']!=''?strtotime($_POST['store_open_date'].' 0:0'):'',
					'store_close_date' => $_POST['store_close_date']!=''?strtotime($_POST['store_close_date'].' 23:59:59'):''
				];
				parent::updateTable_f_mdl('store_master',$sm_update_data,'id="'.$_POST['store_master_id'].'"');

				// Update Production status
				$close_date = date('Y-m-d', strtotime($_POST['store_close_date'].' 0:0'));
				$currunt_date = date('Y-m-d', time());
				if($close_date >= $currunt_date){
					$production_status = [
						'production_status_id' => '0',
						'is_store_batched' => '0'
					];
					parent::updateTable_f_mdl('store_master',$production_status,'id="'.$_POST['store_master_id'].'"');	
				}
				//update date in flyer
				$sof_update_data = [
					'end_date' => $_POST['store_close_date']
				];
				parent::updateTable_f_mdl('store_owner_flyer',$sof_update_data,'store_master_id="'.$_POST['store_master_id'].'"');

				if(isset($store_data[0]['shop_collection_id']) && !empty($store_data[0]['shop_collection_id'])){
					//if we have collection-id, then update store(collection) data in shopify
					$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
					if(!empty($shop_data)) {
						require_once('lib/class_graphql.php');

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
						  	"id":"gid://shopify/Collection/'.$store_data[0]['shop_collection_id'].'",
						  	"title":"'.trim($_POST['store_name']).'",
						  	"descriptionHtml":"'.trim($_POST['store_description']).'"
						  }
						}';
						$graphql->runByMutation($mutationData,$inputData);
					}
				}

				$_SESSION['SUCCESS'] = 'TRUE';
				$_SESSION['MESSAGE'] = 'Store basic details updated successfully.';
			}else{
				$_SESSION['SUCCESS'] = 'FALSE';
				$_SESSION['MESSAGE'] = 'Store is not found.';
			}
			header('location:sa-store-view.php?stkn='.$_POST['stkn'].'&id='.$_POST['store_master_id'].'&tab=basic');
		}else{
			header('location:sa-stores.php?stkn='.$_POST['stkn'].'');
		}
	}

	public function getNewProTagFilterData(){
		$sql = 'SELECT MAX(LENGTH(tags) - LENGTH(REPLACE(tags, ",", "")) + 1) as max_tag_cnt
		FROM `store_product_master`
		WHERE status=1';
		$max_tag_cnt_arr = parent::selectTable_f_mdl($sql);
		if(isset($max_tag_cnt_arr[0]) && !empty($max_tag_cnt_arr[0])){
			$max_tag_cnt = $max_tag_cnt_arr[0]['max_tag_cnt'];

			$sql = 'SELECT GROUP_CONCAT(DISTINCT TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(tags, ",", n.n), ",", -1)) SEPARATOR "," ) as `uni_tags`
			FROM `store_product_master` t
			CROSS JOIN (SELECT 1 as n ';
			for($i=2;$i<=$max_tag_cnt;$i++){
				$sql .= ' UNION ALL SELECT '.$i;
			}
			$sql .= ' ) n
			ORDER BY `uni_tags`;';
			return parent::selectTable_f_mdl($sql);
		}else{
			return [];
		}
	}
	public function getNewProMinQtyList(){
		$sql = 'SELECT DISTINCT min_qty
		FROM `store_product_variant_master`
		WHERE status=1
		AND min_qty>0 AND min_qty!=""
		GROUP BY min_qty
		ORDER BY CAST(min_qty AS unsigned)
		';
		$min_qty_data = parent::selectTable_f_mdl($sql);
		return $min_qty_data;
	}
	public function fetchNewProductColorFamily($store_organization_type_master_id){
		$sql = 'SELECT `store_product_variant_master`.color, store_product_colors_master.color_family,store_color_family_master.color_family_color
			FROM `store_product_variant_master`
			LEFT JOIN `store_product_master` ON `store_product_master`.id = `store_product_variant_master`.store_product_master_id
			LEFT JOIN store_product_colors_master ON store_product_colors_master.product_color = store_product_variant_master.color
			LEFT JOIN store_color_family_master ON store_color_family_master.color_family_name = store_product_colors_master.color_family
			WHERE `store_product_variant_master`.store_organization_type_master_id = "'.$store_organization_type_master_id.'"
			AND `store_product_variant_master`.status = "1"
			AND `store_product_master`.status = "1"
			GROUP BY `store_product_variant_master`.color
		';
		$cf_list = parent::selectTable_f_mdl($sql);
		return $cf_list;
	}
	public function fetch_main_product_list(){
		
		$start = $_POST['start'];
		$limit = $_POST['limit'];

		// Task 36 start 
		$isFlashSale = '';
		if(isset($_POST['is_flash_sale']) && !empty($_POST['is_flash_sale'])){
			$isFlashSale = 'AND pro.is_flash_sale ="'.$_POST['is_flash_sale'].'"';
		}
		$onDemand = '';
		if(isset($_POST['on_demand']) && !empty($_POST['on_demand'])){
			$onDemand = 'AND pro.on_demand = ("'.$_POST['on_demand'].'")';
		}
		// Task 36 end

		$cond_min_qty = '';
		if(isset($_POST['min_qty']) && !empty($_POST['min_qty'])){
			$min_qty = str_replace(',','","',$_POST['min_qty']);
			$cond_min_qty = ' AND var.min_qty IN ("'.$min_qty.'")';
		}
		$cond_color_family = '';
		if(isset($_POST['color_family']) && !empty($_POST['color_family'])){
			$color_family = str_replace(',','","',$_POST['color_family']);
			$cond_color_family = ' AND var.color IN ("'.$color_family.'")';
		}

		$onGroup = '';
		if(isset($_POST['group_ids']) && !empty($_POST['group_ids'])){
			if($_POST['group_ids'] == 0){
				$onGroup = ' AND (pro.group_id = "" OR pro.group_id = "0")';
			}else{
				$onGroup = ' AND pro.group_id IN ('.$_POST['group_ids'].')';
			}			
		}

		$cond_tags = '';
		if(isset($_POST['filter_tags']) && !empty($_POST['filter_tags'])){
			$tag_group_arr = [];
			$tags_arr = explode(',',$_POST['filter_tags']);
			if(!empty($tags_arr)){
				foreach($tags_arr as $single_tag){
					$t_arr = explode('_',$single_tag);
					$tg_name = $t_arr[0];   //tag-group-name like style, brand,..
					if(!isset($tag_group_arr[$tg_name])){
						$tag_group_arr[$tg_name] = [];
					}
					$tag_group_arr[$tg_name][] = $single_tag;
				}
				if(!empty($tag_group_arr)){
					foreach($tag_group_arr as $single_group){
						$cond_tags .= ' AND (';
						$tmp_tag = '';
						foreach($single_group as $single_tag){
							$tmp_tag .= ' FIND_IN_SET("'.$single_tag.'",pro.`tags`)>0 OR';
						}
						$tmp_tag = trim($tmp_tag,' OR');
						$tmp_tag = trim($tmp_tag,'OR ');
						$cond_tags .= $tmp_tag.' )';
					}
					$cond_tags = trim($cond_tags,' AND');
					$cond_tags = trim($cond_tags,'AND ');
					$cond_tags = 'AND ('.$cond_tags.')';
				}
			}
		}

		$org_id = '';
		if(isset($_POST['store_organization_type_master_id'])){
			$org_id = $_POST['store_organization_type_master_id'];
		}

		//Task 36 add on_demand and is_flash_sale condition 
		//Task 37 new changes in sql
		$sql = 'SELECT * FROM (
		SELECT pro.group_id, var.id as var_id, var.price, var.price_on_demand, var.color, GROUP_CONCAT(DISTINCT var.size) as all_sizes, GROUP_CONCAT(DISTINCT var.color) as all_colors, var.image, var.feature_image, pro.id as pro_id, pro.product_title FROM `store_product_variant_master` as var
		LEFT JOIN `store_product_master` as pro ON pro.id = var.store_product_master_id
		WHERE pro.status = 1
		AND var.status = 1
		AND color = var.color
		AND color != ""
		AND var.store_product_master_id = pro.id
		AND var.store_organization_type_master_id = "'.$org_id.'"
		'.$isFlashSale.'
		'.$onDemand.'
		'.$cond_min_qty.'
		'.$cond_color_family.'
		'.$cond_tags.'
		'.$onGroup.'
		GROUP BY pro_id, color ORDER BY pro.order_by ASC
		) tbl 
		LIMIT '.$start.','.$limit.'
		';

		/*
		ORDER BY cast(var.price as unsigned)
		) tbl
		GROUP BY pro_id, color
		LIMIT '.$start.','.$limit.'
		';
		*/
		// echo $sql;die("sssssss");
		$pro_list = parent::selectTable_f_mdl($sql);
		if(!empty($pro_list)){
			$upload_dir = 'image_uploads/';

			$selected_var_id_arr = [];
			if(isset($_POST['selected_product_json']) && !empty($_POST['selected_product_json']) && $_POST['selected_product_json']!='[]' && $_POST['selected_product_json']!='{}'){
				$selected_product_arr = json_decode($_POST['selected_product_json'],1);
				$selected_var_id_arr = array_column($selected_product_arr,'var_id');
			}
			$fundraising_price = 0;
			if(isset($_POST['fundraising_price']) && !empty($_POST['fundraising_price'])){
				$fundraising_price = $_POST['fundraising_price'];
			}

			$add_cost = 0;
			if(isset($_POST['front_side_ink_colors']) && !empty($_POST['front_side_ink_colors'])){
				$add_cost += intval($_POST['front_side_ink_colors'])-1;
			}
			if(isset($_POST['back_side_ink_colors']) && !empty($_POST['back_side_ink_colors'])){
				$add_cost += common::ADD_COST_BACK_SIDE_INK_COLOR+(intval($_POST['back_side_ink_colors'])-1);
			}
			/*if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
				$add_cost += common::ADD_COST_STORE_FULFILLMENT_TYPE_2;
			}else if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
				$add_cost += common::ADD_COST_STORE_FULFILLMENT_TYPE_3;
			}*/

			$htmlBody = '';

			$htmlBody .= '<table class="table">';
			$htmlBody .= '<tr>';
			$htmlBody .= '<td>#</td>';
			$htmlBody .= '<td colspan="2">Item</td>';
			$htmlBody .= '<td>Cost</td>';
			//$htmlBody .= '<td>Fundraising</td>';
			//$htmlBody .= '<td>Retail Price</td>';
			$htmlBody .= '</tr>';

			$productGroup = $this->get_product_key_group();

			foreach($pro_list as $single_pro){
				$all_clr_arr = explode(',',$single_pro['all_colors']);
				$colorContentHtml = '';
				if(!empty($all_clr_arr)){
					//$colorContentHtml .= '<div>';
					foreach($all_clr_arr as $c){
						$sql = "SELECT product_color_name FROM store_product_colors_master WHERE product_color ='".$c."' LIMIT 1";
						$colorInfo = parent::selectTable_f_mdl($sql);
						
						if(!empty($colorInfo[0]['product_color_name'])){
							$colorName = $colorInfo[0]['product_color_name'];
							$colorContentHtml .= "<div style='margin-bottom: 5px;'><div style='width: 20px;height: 20px;float:left;border-radius: 50%;border:1px solid #ddd;background-color: ".$c.";'>&nbsp;&nbsp;&nbsp;</div>&nbsp;&nbsp;".$colorName."</div>";
						}
					}
					//$colorContentHtml .= '</div>';
				}

				if(in_array($single_pro['var_id'],$selected_var_id_arr)){
					$checked = 'checked';
				}else{
					$checked = '';
				}

				//fetch main variant color name
				$sql = "SELECT product_color_name FROM store_product_colors_master WHERE product_color ='".$single_pro['color']."' LIMIT 1";
				$colorInfo = parent::selectTable_f_mdl($sql);
				$mainVarColorName = '';
				if(!empty($colorInfo[0]['product_color_name'])){
					$mainVarColorName = $colorInfo[0]['product_color_name'];
				}

				/*Price replace 11/08/2021*/
				// $priceOverride = $this->priceOverride($single_pro['pro_id']);
				// $override_ftt = 0;
				// if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
				// 	$override_ftt = $priceOverride['override_fts'];
				// }else if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
				// 	$override_ftt = $priceOverride['override_ftt'];
				// }

				$override_ftt = 0;
				if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
					$override_ftt = common::ADD_COST_STORE_FULFILLMENT_TYPE_2;
				}else if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
					$override_ftt = common::ADD_COST_STORE_FULFILLMENT_TYPE_3;
				}
				$add_cost_new   = $add_cost + $override_ftt;
				$add_demand_new = $add_cost + $override_ftt;
				/*Price replace 11/08/2021*/

				$single_pro['price'] += $add_cost_new; //$add_cost;
				$single_pro['price_on_demand'] += $add_demand_new; //$add_cost;
				if(isset($_POST['store_sale_type_master_id']) && $_POST['store_sale_type_master_id']==2){
					// for on-depand
					$pro_price = $single_pro['price_on_demand'];
				}else{
					// for flash
					$pro_price = $single_pro['price'];
				}

				$calculated_price = floatval($pro_price)+$fundraising_price;

				$group_name_html = '';
				$group_add_name = '';
				if($single_pro['group_id'] > 0){
					$group_name = $productGroup[$single_pro['group_id']];
					if($group_name){
					$group_name_html = '<br><button type="button" class="btn btn-default group_nameCpt"><i class="fa fa-object-group" aria-hidden="true"></i>'.$group_name.'</button>';
						$group_add_name = $group_name;
					}
				}

				if(isset($single_pro['feature_image']) && !empty($single_pro['feature_image']) && file_exists($upload_dir.$single_pro['feature_image'])){
					$pro_image_url = common::IMAGE_UPLOAD_URL.$single_pro['feature_image'];
				}else if(isset($single_pro['image']) && !empty($single_pro['image']) && file_exists($upload_dir.$single_pro['image'])){
					$pro_image_url = common::IMAGE_UPLOAD_URL.$single_pro['image'];
				}else{
					$pro_image_url = 'assets/images/no-image.jpg';
				}

				$htmlBody .= '						<input type="hidden" id="product_price_'.$single_pro['var_id'].'" value="'.$single_pro['price'].'">';
				$htmlBody .= '						<input type="hidden" id="product_price_on_demand_'.$single_pro['var_id'].'" value="'.$single_pro['price_on_demand'].'">';
				$htmlBody .= '<input class="markup_price" type="hidden" id="markup_price_'.$single_pro['var_id'].'" data-id="'.$single_pro['var_id'].'" value="'.$fundraising_price.'">';

				$htmlBody .= '<tr>';
				$htmlBody .= '<td><input type="checkbox" '.$checked.' class="checkBoxClass" name="chk" id="pro_chk_'.$single_pro['var_id'].'" data-id="'.$single_pro['var_id'].'" data-pro_id="'.$single_pro['pro_id'].'" data-title="'.$single_pro['product_title'].'" data-image="'.$pro_image_url.'" data-group="'.$group_add_name.'"></td>';
				$htmlBody .= '<td style="max-width: 100px"><img style="max-width: 100px" src="'.$pro_image_url.'" alt="'.$single_pro['product_title'].'" /></td>';
				$htmlBody .= '<td>';
				$htmlBody .= $single_pro['product_title'].'<br>';
				$htmlBody .= '<button type="button" class="btn btn-default" data-toggle="popover" data-trigger="hover" data-container="body" data-html="true" data-placement="top" title="Available Sizes" data-content="'.str_replace(',','<br>',$single_pro['all_sizes']).'"><img src="store-owners/assets/images/tag-icon.png" class="btn-icon">Sizes: '.(count(explode(',',$single_pro['all_sizes']))).'</button><br>';
				//$htmlBody .= '<button type="button" class="btn btn-default" data-toggle="popover" data-trigger="hover" data-container="body" data-html="true" data-placement="top" title="Available Colors" data-content="'.$colorContentHtml.'"><img src="store-owners/assets/images/color-icon.png" class="btn-icon">Colors: '.(count(explode(',',$single_pro['all_colors']))).' </button>';
				$htmlBody .= '<button type="button" class="btn btn-default"><div class="color-tag" style="background:'.$single_pro["color"].';">&nbsp;</div>'.$mainVarColorName.'</button>';
				
				$htmlBody .= $group_name_html;
				$htmlBody .= '</td>';
				$htmlBody .= '<td id="span_product_price_'.$single_pro['var_id'].'">$'.$pro_price.'</td>';
				//$htmlBody .= '<td><span id="span_final_price_'.$single_pro['var_id'].'">$'.$calculated_price.'</span></td>';
				$htmlBody .= '</tr>';

			}
			$htmlBody .= '</table>';
			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = '';
			$res['htmlBody'] = $htmlBody;
		}else{
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'No records found';
		}
		echo json_encode($res,1);
		//echo json_encode(array_map('utf8_encode', $res),1);

	}
	public function add_new_products(){
		if(isset($_POST['selected_product_json']) && !empty($_POST['selected_product_json']) && $_POST['selected_product_json']!='[]' && $_POST['selected_product_json']!='{}'){
			$upload_dir = 'image_uploads/';
			$store_master_id = $_POST['store_master_id'];

			//first we have direct color group by variant list
			$selected_product_arr = json_decode($_POST['selected_product_json'],1);
			$sorted_product_arr = [];
			$groupNameByProduct = array();
			foreach($selected_product_arr as $single_pro){
				$sorted_product_arr[$single_pro['pro_id']][] = $single_pro;
				$groupNameByProduct[$single_pro['pro_id']] = $single_pro['group_name'];
			}

			//now we have sorted array product-id wise
			$pro_ids = implode(",", array_keys($sorted_product_arr));
			$sql = 'SELECT id,product_title,product_description,tags FROM `store_product_master` WHERE id IN ('.$pro_ids.')';
			$pro_list =  parent::selectTable_f_mdl($sql);


			if(!empty($pro_list)){
				foreach($pro_list as $single_pro){
					$pro_id = $single_pro['id'];

					//first we check if product is already choose in store or not
					$sql = 'SELECT id FROM `store_owner_product_master` WHERE store_master_id="'.$store_master_id.'" AND store_product_master_id="'.$pro_id.'"';
					$pro_exist =  parent::selectTable_f_mdl($sql);
					if(!empty($pro_exist)){
						$sopm_id = $pro_exist[0]['id'];
					}else{
						//if product is not already choosen then insert product details
						$sopm_insert_data = [
							'store_master_id' => $store_master_id,
							'store_product_master_id' => $pro_id,

							'product_title' => $single_pro['product_title'],
							'product_description' => $single_pro['product_description'],
							'tags' => $single_pro['tags'],
							'group_name' => $groupNameByProduct[$pro_id],
							'status' => '1',
							'created_on' => @date('Y-m-d H:i:s'),
							'created_on_ts' => time()
						];
						$sopm_arr = parent::insertTable_f_mdl('store_owner_product_master',$sopm_insert_data);
						if(isset($sopm_arr['insert_id'])){
							$sopm_id = $sopm_arr['insert_id'];
						}
					}


					if(isset($sopm_id) && isset($sorted_product_arr[$pro_id]) && !empty($sorted_product_arr[$pro_id])){
						$newVarCount = 0;
						//currently we have color group by variant list, now we want to expand list with size wise(1 color has multiple size) also, so first we find all details from existed var-ids
						$var_ids = array_column($sorted_product_arr[$pro_id],'var_id');
						$sql = 'SELECT id, store_product_master_id, store_organization_type_master_id, color FROM `store_product_variant_master` WHERE id IN ('.implode(',',$var_ids).')';
						$pre_var_list =  parent::selectTable_f_mdl($sql);

						foreach($pre_var_list as $single_pre_var){

							//now we fetch, all list with NOT groupby color, means we have 3-4 rows for one color based on size
							$var_id_index = array_search($single_pre_var['id'], array_column($sorted_product_arr[$pro_id],'var_id'));
							$selected_var_id = $sorted_product_arr[$pro_id][$var_id_index]['var_id'];
							$price = $sorted_product_arr[$pro_id][$var_id_index]['var_price'];
							$price_on_demand = $sorted_product_arr[$pro_id][$var_id_index]['var_price_on_demand'];
							$fundraising_price = $sorted_product_arr[$pro_id][$var_id_index]['fundraising_price'];

							//first we compare original price to calculated price and find price-difference
							$price_diff = $price_on_demand_diff = 0;
							$sql = 'SELECT price, price_on_demand FROM `store_product_variant_master`
										WHERE id = "'.$selected_var_id.'" ';
							$var_price_data =  parent::selectTable_f_mdl($sql);
							if(!empty($var_price_data)){
								$price_diff = $price - $var_price_data[0]['price'];
								$price_on_demand_diff = $price_on_demand - $var_price_data[0]['price_on_demand'];
							}

							$sql = 'SELECT id, price, price_on_demand, store_product_master_id, store_organization_type_master_id, color, size, image, sku
							FROM `store_product_variant_master`
							WHERE store_product_master_id = "'.$single_pre_var['store_product_master_id'].'"
							AND store_organization_type_master_id = "'.$single_pre_var['store_organization_type_master_id'].'"
							AND color = "'.$single_pre_var['color'].'"
							';
							$var_list =  parent::selectTable_f_mdl($sql);

							if(!empty($var_list)){
								foreach($var_list as $var_data){
									//check variant is already choose or not
									$sql = 'SELECT id FROM store_owner_product_variant_master
									WHERE store_owner_product_master_id = "'.$sopm_id.'"
									AND store_product_variant_master_id = "'.$var_data['id'].'"
									AND store_organization_type_master_id = "'.$var_data['store_organization_type_master_id'].'"
									';
									$var_exist =  parent::selectTable_f_mdl($sql);
									if(empty($var_exist)){
										//if variant is not existed then insert product variant details
										if(isset($logo_image_file) && !empty($logo_image_file) && file_exists($upload_dir.$logo_image_file) && !empty($var_data['image']) && file_exists($upload_dir.$var_data['image'])){
											$image = parent::merge_two_images($upload_dir,$var_data['image'],$logo_image_file);
										}else{
											$image = $var_data['image'];
										}

										$sopvm_insert_data = [
											'store_owner_product_master_id' => $sopm_id,
											'store_product_variant_master_id' => $var_data['id'],
											'store_organization_type_master_id' => $var_data['store_organization_type_master_id'],
											'price' => $var_data['price'] + $price_diff,
											'price_on_demand' => $var_data['price_on_demand'] + $price_on_demand_diff,
											'fundraising_price' => $fundraising_price,
											'color' => $var_data['color'],
											'size' => $var_data['size'],
											'image' => $image,
											'original_image' => $var_data['image'],
											'sku' => $var_data['sku'],
											'status' => '1',
											'created_on' => @date('Y-m-d H:i:s'),
											'created_on_ts' => time()
										];
										parent::insertTable_f_mdl('store_owner_product_variant_master',$sopvm_insert_data);
										$newVarCount++;
									}

								}
							}
						}

						//now open product to sync in shopify
						if($newVarCount>0){
							$sopm_update_data = [
								'is_product_synced_to_collect' => '0'
							];
							parent::updateTable_f_mdl('store_owner_product_master',$sopm_update_data,'id="'.$sopm_id.'"');
						}
					}
				}

				//now open store to sync in shopify
				$sm_update_data = [
					'is_products_synced' => '0'
				];
				parent::updateTable_f_mdl('store_master',$sm_update_data,'id="'.$store_master_id.'"');
			}

			$_SESSION['SUCCESS'] = 'TRUE';
			$_SESSION['MESSAGE'] = 'Products added successfully.';
			$this->synProductWhenCreateNew($_POST['store_master_id']); // Task 19
			header('location:sa-store-view.php?stkn='.$_POST['stkn'].'&id='.$_POST['store_master_id'].'&tab=products');
		}
		else{
			$_SESSION['SUCCESS'] = 'FALSE';
			$_SESSION['MESSAGE'] = 'Products are not found.';
			header('location:sa-stores.php?stkn='.$_POST['stkn']);
		}
	}

	// Task 19
	public function synProductWhenCreateNew($store_id=0){
		$url = common::SITE_URL."cron_add_product_after_approve_store.php?store_id=".$store_id;

        //  Initiate curl
        $ch = curl_init();
        // Disable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // Will return the response, if false it print the response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Set the url
        curl_setopt($ch, CURLOPT_URL,$url);
        // Execute
        $result=curl_exec($ch);
        // Closing
        curl_close($ch);
	}
	// end Task 19

	public function add_option_sort_list(){
		if(isset($_POST['option_sort_list_json']) && !empty($_POST['option_sort_list_json'])){

			$option_sort_list_array = json_decode($_POST['option_sort_list_json'], true);

			parent::deleteTable_f_mdl('sort_list_option_master','store_master_id="'.$_POST['somm_id'].'"');

			foreach ($option_sort_list_array as $objVal) {
				$insert_data = [
					'store_master_id' => $_POST['somm_id'],
					'input_type' => $objVal['inputType'],
					'option_val' => $objVal['option_val'],
					'label_val' => $objVal['label_val'],
					'is_required' => $objVal['is_required']
				];

			$insert_data = parent::insertTable_f_mdl('sort_list_option_master',$insert_data);
			}

			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = 'Successfully inserted.';
		}
		else{
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'Oops! somethings is going wrong.';
		}
		echo json_encode($res,1);
	}

	public function get_product_groups(){		
		$sqlG = "SELECT * FROM minimum_group_product ORDER BY group_order ASC";		
		return $productGroup =  parent::selectTable_f_mdl($sqlG);
	}

	public function get_product_key_group(){
		$groupArr = array();		
		$sqlG = "SELECT * FROM minimum_group_product ORDER BY group_order ASC";		
		$productGroup =  parent::selectTable_f_mdl($sqlG);
		
		if($productGroup){
			foreach ($productGroup as $key => $groupVal) {
				$groupArr[$groupVal['id']] = $groupVal['product_group'];
			}
		}
		return $groupArr;		
	}
	public function get_product_group($group_id){
		$group_name = '';
		if($group_id > 0){
			$sqlG = "SELECT * FROM minimum_group_product WHERE id = '".$group_id."'";		
			$productGroup =  parent::selectTable_f_mdl($sqlG);
			if($productGroup){
				$group_name = $productGroup[0]['product_group'];
			}
		}
		return array('group_id' => $group_id, 'group_name' => $group_name);
	}
	public function get_all_products_by_store(){
		if(isset($_POST['store_master_id']) && !empty($_POST['store_master_id'])){
            $cond_color_family = '';
            if(isset($_POST['color_family']) && !empty($_POST['color_family'])){
                $color_family = str_replace(',','","',$_POST['color_family']);
                $cond_color_family = ' AND sub_var.color IN ("'.$color_family.'")';
            }

            $cond_tags = '';
            if(isset($_POST['filter_tags']) && !empty($_POST['filter_tags'])){
                $tag_group_arr = [];
                $tags_arr = explode(',',$_POST['filter_tags']);
                if(!empty($tags_arr)){
                    foreach($tags_arr as $single_tag){
                        $t_arr = explode('_',$single_tag);
                        $tg_name = $t_arr[0];   //tag-group-name like style, brand,..
                        if(!isset($tag_group_arr[$tg_name])){
                            $tag_group_arr[$tg_name] = [];
                        }
                        $tag_group_arr[$tg_name][] = $single_tag;
                    }
                    if(!empty($tag_group_arr)){
                        foreach($tag_group_arr as $single_group){
                            $cond_tags .= ' AND (';
                            $tmp_tag = '';
                            foreach($single_group as $single_tag){
                                $tmp_tag .= ' FIND_IN_SET("'.$single_tag.'",pro.`tags`)>0 OR';
                            }
                            $tmp_tag = trim($tmp_tag,' OR');
                            $tmp_tag = trim($tmp_tag,'OR ');
                            $cond_tags .= $tmp_tag.' )';
                        }
                        $cond_tags = trim($cond_tags,' AND');
                        $cond_tags = trim($cond_tags,'AND ');
                        $cond_tags = 'AND ('.$cond_tags.')';
                    }
                }
            }

			//Task 37 add store_product_master_id
			//Task 43 Add parameters pro.is_product_fundraising,pro.product_fundraising_price,
			$sql = 'SELECT pro.id,pro.store_product_master_id, pro.product_title,pro.group_name,pro.is_product_fundraising,pro.product_fundraising_price,pro.is_individual,
				(
				SELECT GROUP_CONCAT(DISTINCT sub_var.size) FROM `store_owner_product_variant_master` as sub_var
				WHERE sub_var.store_owner_product_master_id = pro.id
				LIMIT 1
				) as all_sizes,
				(
				SELECT GROUP_CONCAT(DISTINCT sub_var.color) FROM `store_owner_product_variant_master` as sub_var
				WHERE sub_var.store_owner_product_master_id = pro.id
				LIMIT 1
				) as all_colors,
                (
                SELECT GROUP_CONCAT(DISTINCT sub_var.color) FROM `store_owner_product_variant_master` as sub_var
                WHERE sub_var.store_owner_product_master_id = pro.id
                '.$cond_color_family.'
                LIMIT 1
                ) as filter_colors,
				(
				SELECT sub_var.image FROM `store_owner_product_variant_master` as sub_var
				WHERE sub_var.store_owner_product_master_id = pro.id
				'.$cond_color_family.'
				LIMIT 1
				) as image,
				(
				SELECT sub_var.price FROM `store_owner_product_variant_master` as sub_var
				WHERE sub_var.store_owner_product_master_id = pro.id
				LIMIT 1
				) as price,
				(
				SELECT sub_var.price_on_demand FROM `store_owner_product_variant_master` as sub_var
				WHERE sub_var.store_owner_product_master_id = pro.id
				LIMIT 1
				) as price_on_demand,
				(
				SELECT sub_var.fundraising_price FROM `store_owner_product_variant_master` as sub_var
				WHERE sub_var.store_owner_product_master_id = pro.id
				LIMIT 1
				) as fundraising_price
				FROM `store_owner_product_master` as pro
				LEFT JOIN `store_master` ON `store_master`.id = pro.store_master_id
				WHERE pro.store_master_id = "'.$_POST['store_master_id'].'"
				'.$cond_tags.'

				HAVING filter_colors IS NOT NULL
			';
			$list_data = parent::selectTable_f_mdl($sql);
			$divHtml = '';
			$upload_dir = 'image_uploads/';
			$divstoreHtmlArr = array();
			$divstoreHtmlArrOther = '';
			if(isset($list_data) && !empty($list_data)){
				//Task 43 28-09-2021
				$sql = 'SELECT is_fundraising, ct_fundraising_price,store_organization_type_master_id,store_sale_type_master_id FROM `store_master`
					WHERE id="'.$_POST['store_master_id'].'"
					';
				$store_master_details = parent::selectTable_f_mdl($sql);
				//Task 43
				$divHtml .= '<div class="row">';
				foreach($list_data as $single){

					//Task 37 start
					$store_product_master_id =  $single['store_product_master_id'];

					//Task 47 Task 49

					$sql_store_product_master = 'SELECT id,product_title FROM `store_product_master` WHERE id = "'.$store_product_master_id.'" ';
					$is_master_product = parent::selectTable_f_mdl($sql_store_product_master);

					/*
					* Task 42 24-09-21
					*/
					$is_product_fundraising    = $single['is_product_fundraising'];
					$product_fundraising_price = $single['product_fundraising_price'];
					/*end*/

					$variant_sql  = 'SELECT price,price_on_demand,store_organization_type_master_id FROM `store_product_variant_master` WHERE store_product_master_id = "'.$store_product_master_id.'" AND store_organization_type_master_id = "'.$store_master_details[0]['store_organization_type_master_id'].'" '; //Task 49

					$variant_data = parent::selectTable_f_mdl($variant_sql);
					//Task 37 end

					$variant_price = isset($variant_data[0]['price'])?$variant_data[0]['price']:$single['price'];
					$variant_price_on_demand = isset($variant_data[0]['price_on_demand'])?$variant_data[0]['price_on_demand']:$single['price_on_demand'];

					if(isset($single['image']) && !empty($single['image']) && file_exists($upload_dir.$single['image'])){
						$pro_image_url = common::IMAGE_UPLOAD_URL.$single['image'];
					}else{
						$pro_image_url = 'store-owners/assets/images/no-image.jpg';
					}

					//Task 43 24-09-21
					if(isset($single['is_individual']) AND $single['is_individual']=="Yes"){
						$is_product_fundraising    = $single['is_product_fundraising'];
						$product_fundraising_price = $single['product_fundraising_price'];
					}else{
						$is_product_fundraising    = $store_master_details[0]['is_fundraising'];
						$product_fundraising_price = $store_master_details[0]['ct_fundraising_price'];
					}
					//Task 43 end

					$divstoreHtml = '';
					$divstoreHtml .= '<div class="col-lg-3 col-md-3 col-sm-4 col-xs-6" id="pro_div_'.$single['id'].'">';
					$divstoreHtml .= ' 	<div class="card border store-pro-card">';
					
					$divstoreHtml .= ' 	        <input type="checkbox" class="choose-pro choose_pro_for_apply_logo" value="' . $single['id'] . '">';

					//Task 43 24-09-21
					$divstoreHtml .= '<input type="hidden" name="is_product_fundraising" value="'.$is_product_fundraising.'" id="is_product_fundraising_'.$single['id'].'">';
					$divstoreHtml .= '<input type="hidden" name="product_fundraising_price" value="'.$product_fundraising_price.'" id="product_fundraising_price_'.$single['id'].'">';
					//Task 43 end

					/*$divstoreHtml .= '<input type="hidden" name="is_product_fundraising" value="'.$single['is_product_fundraising'].'" id="is_product_fundraising_'.$single['id'].'">';
					$divstoreHtml .= '<input type="hidden" name="product_fundraising_price" value="'.$single['product_fundraising_price'].'" id="product_fundraising_price_'.$single['id'].'">';*/
					
					$divstoreHtml .= '		   	<img class="card-img-top store-product-image img-fluid w-full" src="'.$pro_image_url.'" alt="'.$single['product_title'].'">';
					$divstoreHtml .= '		   	<div align="center">';

					$divstoreHtml .= '			<h4 class="card-title"><input type="text" class="store-pro-title-input store_product_title_input" data-id="'.$single['id'].'" value="'.$single['product_title'].'" data-toggle="tooltip" data-placement="top" data-trigger="hover" data-original-title="After editing title, click anywhere outside of this textbox to save title" title=""></h4>';
					$divstoreHtml .= '			</div>';

					if(isset($_POST['store_sale_type_master_id']) && $_POST['store_sale_type_master_id']=='2'){
						$price_display = 'display:none;';
						$price_on_demand_display = '';
					}else{
						$price_display = '';
						$price_on_demand_display = 'display:none;';
					}

					$divstoreHtml .= '			<ul class="list-group list-group-dividered px-5 mb-0">';
					
					//Task 37 start
					// $divstoreHtml .= '				<li class="list-group-item p-0">Base Price : <span class="product_list_price" style="'.$price_display.'">$'.$single['price'].'</span><span class="product_list_price_on_demand" style="'.$price_on_demand_display.'">$'.$single['price_on_demand'].'</span></li>';
					$divstoreHtml .= '<ul class="list-group list-group-dividered px-5 mb-0">';
					if(isset($_POST['store_sale_type_master_id']) && $_POST['store_sale_type_master_id']=='1'){
						$divstoreHtml .= '<li class="list-group-item p-0">Base Price : <span class="product_list_price" style="'.$price_display.'">$'.$single['price'].'</span><span class="product_list_price_on_demand" style="'.$price_on_demand_display.'">$'.$variant_price_on_demand.'</span></li>';
					}
					else
					{
						$divstoreHtml .= '<li class="list-group-item p-0">Base Price : <span class="product_list_price" style="'.$price_display.'">$'.$variant_price.'</span><span class="product_list_price_on_demand" style="'.$price_on_demand_display.'">$'.$single['price_on_demand'].'</span></li>';
					}	
					//Task 37 end
					
					// Task 49
					if(isset($is_master_product[0]['product_title'])){
						$base_price = 0;
						if(isset($store_master_details[0]['store_sale_type_master_id']) && $store_master_details[0]['store_sale_type_master_id']==1){
							$base_price = $variant_price;
						}else{
							$base_price = $variant_price_on_demand;
						}
                        $divstoreHtml .= '<li class="list-group-item p-0">Base Name : <span>'.$is_master_product[0]['product_title'].'($'.@$base_price.')'.'</span></li>';
                    }
					//Task 49 end

					$divstoreHtml .= '<li class="list-group-item p-0">Size : <button class="btn btn-xs" data-toggle="tooltip" data-placement="top" data-html="true" data-trigger="hover" data-original-title="'.str_replace(',','<br>',$single['all_sizes']).'">'.count(explode(',',$single['all_sizes'])).'</button></li>';
					$divstoreHtml .= '				<li class="list-group-item p-0">Color : '.count(explode(',',$single['all_colors'])).'</li>';

					$fundraising_price = 'None';
					if($single['fundraising_price'] > 0){
						$fundraising_price = '$'.$single['fundraising_price'];
					}
					$divstoreHtml .= '				<li class="list-group-item p-0">Fundraising : '.$fundraising_price.'</li>';

					$groups = $this->get_product_groups();
					if($groups){
						$divstoreHtml .= '<li class="list-group-item p-0">Minimum Group:';
						$divstoreHtml .= '<select name="productGroup29" class="productGroupStore form-control" product_id="'.$single['id'].'">';
						$otherSelected = 'selected';
						foreach ($groups as $groupName) {
							$selectedG = '';
							if($single['group_name'] == '' || $single['group_name'] == 'Other'){
								$otherSelected = 'selected';
							}else if($single['group_name'] == $groupName['product_group']){
								$otherSelected = '';
								$selectedG = 'selected';
							}
							$divstoreHtml .= '<option value="'.$groupName['product_group'].'" '.$selectedG.'>'.$groupName['product_group'].'</option>';
						}
						$divstoreHtml .= '<option value="Other" '.$otherSelected.'>Other</option>';
						$divstoreHtml .= '</select>';
						$divstoreHtml .= '';
					}

					$divstoreHtml .= '			</ul>';
					$divstoreHtml .= '			<div class="card-block deleted_master_product">'; //Task 47

					$divstoreHtml .= '				<button class="btn btn-danger btn-round btn-sm store_product_delete" data-id="'.$single['id'].'" data-toggle="tooltip" data-placement="top" data-trigger="hover" data-original-title="Remove Product" title=""><i class="fa fa-trash"></i></button>';
					$divstoreHtml .= '				<button class="btn btn-primary btn-round btn-sm store_product_variants" data-id="'.$single['id'].'" data-title="'.$single['product_title'].'" data-toggle="tooltip" data-placement="top" data-trigger="hover" data-original-title="Edit Product Options" title=""><i class="fa fa-edit"></i></button>';
					//start Task 47
					if(count($is_master_product)==0){
						$divstoreHtml .= '<span class="flag-size"><img src="img/flag_icon.png"></span>';
					}
					//end Task 47
					$divstoreHtml .= '			</div>';
					$divstoreHtml .= '		</div>';
					$divstoreHtml .=	'</div>';

					if($single['group_name'] == '' || $single['group_name'] == 'Other'){
						$divstoreHtmlArrOther .= $divstoreHtml;
					}else{
						$divstoreHtmlArr[$single['group_name']][] = $divstoreHtml;
					}

				}

				if($divstoreHtmlArr){
					foreach ($divstoreHtmlArr as $group_name => $divStore) {
						if($divStore){
							$divHtml .= '<div class="spirit_view_store">';
							$divHtml .= '<h3>'.$group_name.'</h3>';
							$divHtml .= '</div>';
							foreach($divStore AS $divHtmlContent){
								$divHtml .= $divHtmlContent;
							}
						}
					}
				}
				if($divstoreHtmlArrOther){
					$divHtml .= '<div class="spirit_view_store">';
					$divHtml .= '<h3>Other</h3>';
					$divHtml .= '</div>';
					$divHtml .= $divstoreHtmlArrOther;
				}

				$divHtml .= '</div>';
			}else{
				$divHtml .= '<div style="text-align: center">No data available.</div>';
			}

			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = '';
			$res['divHtml'] = $divHtml;
		}else{
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'Invalid request';
		}

		echo json_encode(array_map('utf8_encode', $res),1);
	}
	public function store_product_details_post(){
		if(isset($_POST['store_master_id']) && !empty($_POST['store_master_id']) &&
			isset($_POST['id']) && !empty($_POST['id']) &&
			isset($_POST['product_title']) && !empty($_POST['product_title'])
		){
			$sql = 'SELECT store_owner_product_master.id, store_owner_product_master.shop_product_id, store_master.store_name FROM `store_owner_product_master`
			LEFT JOIN store_master ON store_master.id = store_owner_product_master.store_master_id
			WHERE store_owner_product_master.id="'.$_POST['id'].'"
			AND store_owner_product_master.store_master_id="'.$_POST['store_master_id'].'"
			';
			$pro_data = parent::selectTable_f_mdl($sql);
			if(!empty($pro_data)){
				$sopm_update_data = [
					'product_title' => trim($_POST['product_title'])
				];
				parent::updateTable_f_mdl('store_owner_product_master',$sopm_update_data,'id="'.$_POST['id'].'" AND store_master_id="'.$_POST['store_master_id'].'"');

				if(isset($pro_data[0]['shop_product_id']) && !empty($pro_data[0]['shop_product_id'])){
					//if we have shopify product id, then we also update title in shopify
					$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
					if(!empty($shop_data)){
						require_once('lib/class_graphql.php');

						$shop = $shop_data[0]['shop_name'];
						$token = $shop_data[0]['token'];

						$headers = array(
							'X-Shopify-Access-Token' => $token
						);
						$graphql = new Graphql($shop, $headers);

						$mutationData = 'mutation productUpdate($input: ProductInput!) {
						  productUpdate(input: $input) {
							product {
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
						  	"id":"gid://shopify/Product/'.$pro_data[0]['shop_product_id'].'",
						  	"title":"'.$pro_data[0]['store_name'].'-'.trim($_POST['product_title']).'"
						  }
						}';
						$graphql->runByMutation($mutationData,$inputData);
					}
				}

				$res['SUCCESS'] = 'TRUE';
				$res['MESSAGE'] = 'Product updated successfully.';
			}else{
				$res['SUCCESS'] = 'FALSE';
				$res['MESSAGE'] = 'Invalid product. Please try again.';
			}
		}else{
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'Invalid request';
		}
		echo json_encode($res,1);
	}
	public function store_product_delete_post(){
		if(isset($_POST['product_delete_id']) && !empty($_POST['product_delete_id'])){

			$deleteProductIdArray = explode(",",$_POST['product_delete_id']);

			foreach($deleteProductIdArray as $deleteId){

				$sql = 'SELECT pro.id, pro.shop_product_id
				FROM `store_owner_product_master` as pro
				LEFT JOIN `store_master` ON `store_master`.id = pro.store_master_id
				WHERE pro.id = "'.$deleteId.'"
				';
				$product_exist = parent::selectTable_f_mdl($sql);
				if(!empty($product_exist)){
					parent::deleteTable_f_mdl('store_owner_product_master','id="'.$deleteId.'"');
					parent::deleteTable_f_mdl('store_owner_product_variant_master','store_owner_product_master_id="'.$deleteId.'"');

					if(isset($product_exist[0]['shop_product_id']) && !empty($product_exist[0]['shop_product_id'])){
						// if we have product-id, then delete product from shopify
						$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
						if(!empty($shop_data)) {
							require_once('lib/class_graphql.php');

							$shop = $shop_data[0]['shop_name'];
							$token = $shop_data[0]['token'];

							$headers = array(
								'X-Shopify-Access-Token' => $token
							);
							$graphql = new Graphql($shop, $headers);

							$mutationData = 'mutation productDelete($input: ProductDeleteInput!) {
							productDelete(input: $input) {
								deletedProductId
								shop {
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
								"id": "gid://shopify/Product/'.$product_exist[0]['shop_product_id'].'"
							}
							}';
							$graphql->runByMutation($mutationData,$inputData);
						}
					}

					$res['SUCCESS'] = 'TRUE';
					$res['MESSAGE'] = 'Product deleted successfully.';
				}else{
					$res['SUCCESS'] = 'FALSE';
					$res['MESSAGE'] = 'Invalid request';
				}
			}
		}else{
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'Invalid request';
		}
		echo json_encode($res,1);
	}

	/*new Fn 01-09-2021*/
	public function get_all_variants_by_product_new(){
		if(isset($_POST['store_master_id']) && !empty($_POST['store_master_id']) && isset($_POST['product_id']) && !empty($_POST['product_id'])){
			$sql = 'SELECT sopvm.id, sopvm.price, sopvm.price_on_demand, sopvm.fundraising_price, sopvm.color, sopvm.size, sopvm.image, sopvm.original_image, sopvm.sku,spcm.product_color_name
				FROM `store_owner_product_variant_master` as sopvm

				LEFT JOIN store_product_colors_master as spcm
				ON sopvm.color = spcm.product_color

				WHERE sopvm.store_owner_product_master_id = "'.$_POST['product_id'].'"
				ORDER BY sopvm.color
			';

			$list_data = parent::selectTable_f_mdl($sql);
			$divHtml = '';
			if(isset($list_data) && !empty($list_data)){
				$divHtml .= '<div class="row">';
				$divHtml .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
				$divHtml .= '<table class="table">';

				$divHtml .= '<tr>';
				$divHtml .= '<th>Image</th>';
				$divHtml .= '<th>Color</th>';
				$divHtml .= '<th>Size</th>';
				$divHtml .= '<th>SKU</th>';
				$divHtml .= '<th>Your&nbsp;Cost&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;+ 
								
							</th>';
								
				$divHtml .= '<th>Fundraising&nbsp;&nbsp;&nbsp;=</th>';
				$divHtml .= '<th>Retail Price</th>';
				//$divHtml .= '<th>Action</th>';
				$divHtml .= '<th>
								<input type="checkbox" class="select_all_variant_for_delete" id="select_all_var_check"></input>

								<button type="button" class="btn btn-danger btn-sm btn-round bulk_variant_delete" data-toggle="tooltip" data-placement="top" data-trigger="hover" data-original-title="Remove Variant" title=""><i class="fa-trash"></i></button>

							</th>';

				$divHtml .= '</tr>';

				$add_cost = 0;
				if(isset($_POST['front_side_ink_colors']) && !empty($_POST['front_side_ink_colors'])){
					$add_cost += intval($_POST['front_side_ink_colors'])-1;
				}
				if(isset($_POST['back_side_ink_colors']) && !empty($_POST['back_side_ink_colors'])){
					$add_cost += common::ADD_COST_BACK_SIDE_INK_COLOR+(intval($_POST['back_side_ink_colors'])-1);
				}
				/*if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
					$add_cost += common::ADD_COST_STORE_FULFILLMENT_TYPE_2;
				}else if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
					$add_cost += common::ADD_COST_STORE_FULFILLMENT_TYPE_3;
				}*/

				$upload_dir = 'image_uploads/';
				foreach($list_data as $single){
					/*Price replace 11/08/2021*/					
					$sqlNew = "SELECT * FROM store_owner_product_master WHERE id = '".$_POST['product_id']."'";
					$listData = parent::selectTable_f_mdl($sqlNew);
					$master_productId = $listData[0]['store_product_master_id'];
					$priceOverride = $this->priceOverride($master_productId);
					$override_ftt = 0;
					if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
						$override_ftt = $priceOverride['override_fts'];
					}else if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
						$override_ftt = $priceOverride['override_ftt'];
					}

					$add_cost_new   = $add_cost + $override_ftt;
					$add_demand_new = $add_cost + $override_ftt;
					/*Price replace 11/08/2021*/

					//here in edit, we want to find original default value, so we deduct cost from price
					$default_price = floatval($single['price']);// - $add_demand_new;//$add_cost;
					$default_price_on_demand = floatval($single['price_on_demand']);// - $add_demand_new;//$add_cost;

					if(isset($single['image']) && !empty($single['image']) && file_exists($upload_dir.$single['image'])){
						$pro_image_url = common::IMAGE_UPLOAD_URL.$single['image'];
					}else if(isset($single['original_image']) && !empty($single['original_image']) && file_exists($upload_dir.$single['original_image'])){
						$pro_image_url = common::IMAGE_UPLOAD_URL.$single['original_image'];
					}else{
						$pro_image_url = 'store-owners/assets/images/no-image.jpg';
					}

					$flashsale_style = '';
					$ondemand_style = 'display:none;';
					if(isset($_POST['store_sale_type_master_id']) && $_POST['store_sale_type_master_id']=='2'){
						$flashsale_style = 'display:none;';
						$ondemand_style = '';
					}



					$divHtml .= '<tr id="var_div_'.$single['id'].'">';

					$divHtml .= '<td class="text-center"><img id="varedit_image_'.$single['id'].'" src="'.$pro_image_url.'" style="width: 100px;">';
					
					$divHtml .= '<input type="file" class="varedit_image_file" id="varedit_image_file_' . $single['id'] . '" data-id="' . $single['id'] . '" style="display:none;"><button type="button" class="btn btn-block btn-dark btn-xs varedit_change_image_btn" data-color="' . $single['color'] . '" data-id="' . $single['id'] . '">Change Image</button>';
					
					$divHtml .= '</td>';

					$divHtml .= '<input type="hidden" value="'.$single['color'].'" class="varedit_color" id="varedit_color_'.$single['id'].'" data-id="'.$single['id'].'">';

					$divHtml .= '<td><span>'.$single['product_color_name'].'</span>';

					$divHtml .= '<td><input type="hidden" value="'.$single['size'].'" class="form-control varedit_size" id="varedit_size_'.$single['id'].'" data-id="'.$single['id'].'">'.$single['size'].'</td>';
					$divHtml .= '<td><input type="hidden" value="'.$single['sku'].'" class="form-control varedit_sku" id="varedit_sku_'.$single['id'].'" data-id="'.$single['id'].'">'.$single['sku'].'</td>';
					$divHtml .= '<td>';
					$divHtml .= '	<input type="hidden" value="'.$default_price.'" id="varedit_default_price_'.$single['id'].'">';

					$divHtml .= '	<input type="hidden" value="'.$override_ftt.'" id="varedit_override_ftt_'.$single['id'].'">';


					// Task 2 07-05-21
					/*$divHtml .= '	<input type="hidden" value="'.$single['price'].'" class="form-control varedit_price" id="varedit_price_'.$single['id'].'" data-id="'.$single['id'].'">';
					$divHtml .= ' 	<span class="flase_sale_price" style="white-space: nowrap;'.$flashsale_style.'">Flash-Sale : $<span id="showedit_price_'.$single['id'].'">'.$single['price'].'</span></span>';*/
					
					$divHtml .= ' 	<span class="flase_sale_price" style="white-space: nowrap;'.$flashsale_style.'">Flash-Sale : $<input type="text" value="'.$single['price'].'00" class="varedit_price" id="varedit_price_'.$single['id'].'" data-id="'.$single['id'].'" style="width:50px;">
					</span>';
					// Task 2 end

					$divHtml .= '	<input type="hidden" value="'.$default_price_on_demand.'" id="varedit_default_price_on_demand_'.$single['id'].'">';
					$divHtml .= '	<input type="hidden" value="'.$single['price_on_demand'].'" class="form-control varedit_price_on_demand" id="varedit_price_on_demand_'.$single['id'].'" data-id="'.$single['id'].'">';
					$divHtml .= ' 	<span class="on_deman_price" style="white-space: nowrap;'.$ondemand_style.'">On-Demand : $<span id="showedit_price_on_demand_'.$single['id'].'">'.$single['price_on_demand'].'</span></span>';

					//$divHtml .= ' 	<span class="on_deman_price" style="white-space: nowrap;'.$ondemand_style.'">On-Demand : $<input type="text" value="'.$single['price_on_demand'].'" class="varedit_price" id="varedit_price_'.$single['id'].'" data-id="'.$single['id'].'" style="width:50px;"><span id="showedit_price_on_demand_'.$single['id'].'">'.$single['price_on_demand'].'</span></span>';

					$divHtml .= '</td>';
					$divHtml .= '<td><span>+&nbsp;&nbsp;$</span><input type="text" value="'.$single['fundraising_price'].'" class="varedit_fundraising_price" id="varedit_fundraising_price_'.$single['id'].'" data-id="'.$single['id'].'" style="width: 50px;"><span>&nbsp;&nbsp;=</span></td>';
					$divHtml .= '<td>';

					//Task 2 New update
					$price_value = empty($single['price'])?0:$single['price'];
					$fundraising_price_value = empty($single['fundraising_price'])?0:$single['fundraising_price'];
					//Task 2 New update

					$divHtml .= '<span class="flase_sale_price" style="white-space: nowrap;'.$flashsale_style.'">Flash-Sale : $<span id="varedit_total_'.$single['id'].'">'.($price_value+$fundraising_price_value).'</span></span>';
					$divHtml .= '<span class="on_deman_price" style="white-space: nowrap;'.$ondemand_style.'">On-Demand : $<span id="varedit_total_on_demand_'.$single['id'].'">'.($single['price_on_demand']+$fundraising_price_value).'</span></span>'; //Task 2 New update
					$divHtml .= '</td>';
					//$divHtml .= '<td>';
					
					//$divHtml .= '<button type="button" class="btn btn-danger btn-sm btn-round varedit_variant_delete" data-id="'.$single['id'].'" data-toggle="tooltip" data-placement="top" data-trigger="hover" data-original-title="Remove Variant" title=""><i class="fa-trash"></i></button>';
					//$divHtml .= '</td>';

					$divHtml .= '<td>';

					$divHtml .= '<input type="checkbox" class="choose_variant choose_variant_for_delete" value="'.$single['id'].'">

					<button type="button" class="btn btn-danger btn-sm btn-round varedit_variant_delete" data-id="'.$single['id'].'" data-toggle="tooltip" data-placement="top" data-trigger="hover" data-original-title="Remove Variant" title=""><i class="fa-trash"></i></button>';

					$divHtml .= '</td>';
					$divHtml .= '</tr>';
				}
				$divHtml .= '</table>';
				$divHtml .= '</div>';
				$divHtml .= '</div>';
			}else{
				$divHtml .= '<div style="text-align: center">No data available.</div>';
			}

			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = '';
			$res['divHtml'] = $divHtml;
		}else{
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'Invalid request';
		}

		echo json_encode($res,1);
	}
	/*new Fn 01-09-2021*/
	public function get_all_variants_by_product(){
		if(isset($_POST['store_master_id']) && !empty($_POST['store_master_id']) && isset($_POST['product_id']) && !empty($_POST['product_id'])){
			$sql = 'SELECT sopvm.id, sopvm.price, sopvm.price_on_demand, sopvm.fundraising_price, sopvm.color, sopvm.size, sopvm.image, sopvm.original_image, sopvm.sku,spcm.product_color_name
				FROM `store_owner_product_variant_master` as sopvm

				LEFT JOIN store_product_colors_master as spcm
				ON sopvm.color = spcm.product_color

				WHERE sopvm.store_owner_product_master_id = "'.$_POST['product_id'].'"
				ORDER BY sopvm.color
			';

			$list_data = parent::selectTable_f_mdl($sql);
			$divHtml = '';
			if(isset($list_data) && !empty($list_data)){
				$divHtml .= '<div class="row">';
				$divHtml .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
				$divHtml .= '<table class="table">';

				$divHtml .= '<tr>';
				$divHtml .= '<th>Image</th>';
				$divHtml .= '<th>Color</th>';
				$divHtml .= '<th>Size</th>';
				$divHtml .= '<th>SKU</th>';
				$divHtml .= '<th>Your&nbsp;Cost&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;+ 
								
							</th>';
								
				$divHtml .= '<th>Fundraising&nbsp;&nbsp;&nbsp;=</th>';
				$divHtml .= '<th>Retail Price</th>';
				//$divHtml .= '<th>Action</th>';
				$divHtml .= '<th>
								<input type="checkbox" class="select_all_variant_for_delete" id="select_all_var_check"></input>

								<button type="button" class="btn btn-danger btn-sm btn-round bulk_variant_delete" data-toggle="tooltip" data-placement="top" data-trigger="hover" data-original-title="Remove Variant" title=""><i class="fa-trash"></i></button>

							</th>';

				$divHtml .= '</tr>';

				$add_cost = 0;
				//Task 36 comment out start
				// if(isset($_POST['front_side_ink_colors']) && !empty($_POST['front_side_ink_colors'])){
				// 	$add_cost += intval($_POST['front_side_ink_colors'])-1;
				// }
				// if(isset($_POST['back_side_ink_colors']) && !empty($_POST['back_side_ink_colors'])){
				// 	$add_cost += common::ADD_COST_BACK_SIDE_INK_COLOR+(intval($_POST['back_side_ink_colors'])-1);
				// }
				/*if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
					$add_cost += common::ADD_COST_STORE_FULFILLMENT_TYPE_2;
				}else if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
					$add_cost += common::ADD_COST_STORE_FULFILLMENT_TYPE_3;
				}*/
				//Task 36 comment out end
				$upload_dir = 'image_uploads/';
				foreach($list_data as $single){
					//here in edit, we want to find original default value, so we deduct cost from price

					//Task 36 comment out start
					/*Price replace 11/08/2021*/					
					// $sqlNew = "SELECT * FROM store_owner_product_master WHERE id = '".$_POST['product_id']."'";
					// $listData = parent::selectTable_f_mdl($sqlNew);
					// $master_productId = $listData[0]['store_product_master_id'];
					// $priceOverride = $this->priceOverride($master_productId);
					// $override_ftt = 0;
					// if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
					// 	$override_ftt = $priceOverride['override_fts'];
					// }else if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
					// 	$override_ftt = $priceOverride['override_ftt'];
					// }

					// $add_cost_new   = $add_cost + $override_ftt;
					// $add_demand_new = $add_cost + $override_ftt;
					/*Price replace 11/08/2021*/
					//Task 36 comment out end

					$default_price = floatval($single['price']) + $add_cost;//$add_cost;// Task 36
					$default_price_on_demand = floatval($single['price_on_demand']) + $add_cost;//$add_cost;// Task 36

					if(isset($single['image']) && !empty($single['image']) && file_exists($upload_dir.$single['image'])){
						$pro_image_url = common::IMAGE_UPLOAD_URL.$single['image'];
					}else if(isset($single['original_image']) && !empty($single['original_image']) && file_exists($upload_dir.$single['original_image'])){
						$pro_image_url = common::IMAGE_UPLOAD_URL.$single['original_image'];
					}else{
						$pro_image_url = 'store-owners/assets/images/no-image.jpg';
					}

					$flashsale_style = '';
					$ondemand_style = 'display:none;';
					if(isset($_POST['store_sale_type_master_id']) && $_POST['store_sale_type_master_id']=='2'){
						$flashsale_style = 'display:none;';
						$ondemand_style = '';
					}

					$divHtml .= '<tr id="var_div_'.$single['id'].'">';

					$divHtml .= '<td class="text-center"><img id="varedit_image_'.$single['id'].'" src="'.$pro_image_url.'" style="width: 100px;">';
				
					$divHtml .= '<input type="file" class="varedit_image_file" id="varedit_image_file_' . $single['id'] . '" data-id="' . $single['id'] . '" style="display:none;"><button type="button" class="btn btn-block btn-dark btn-xs varedit_change_image_btn" data-color="' . $single['color'] . '" data-id="' . $single['id'] . '">Change Image</button>';
					
					$divHtml .= '</td>';

					$divHtml .= '<input type="hidden" value="'.$single['color'].'" class="varedit_color" id="varedit_color_'.$single['id'].'" data-id="'.$single['id'].'">';

					$divHtml .= '<td><span>'.$single['product_color_name'].'</span>';

					$divHtml .= '<td><input type="hidden" value="'.$single['size'].'" class="form-control varedit_size" id="varedit_size_'.$single['id'].'" data-id="'.$single['id'].'">'.$single['size'].'</td>';
					$divHtml .= '<td><input type="hidden" value="'.$single['sku'].'" class="form-control varedit_sku" id="varedit_sku_'.$single['id'].'" data-id="'.$single['id'].'">'.$single['sku'].'</td>';
					$divHtml .= '<td>';
					$divHtml .= '	<input type="hidden" value="'.$default_price.'" id="varedit_default_price_'.$single['id'].'">';
					
					/*$divHtml .= '	<input type="hidden" value="'.$single['price'].'" class="form-control varedit_price" id="varedit_price_'.$single['id'].'" data-id="'.$single['id'].'">';
					$divHtml .= ' 	<span class="flase_sale_price" style="white-space: nowrap;'.$flashsale_style.'">Flash-Sale : $<span id="showedit_price_'.$single['id'].'">'.$single['price'].'</span></span>';*/
					$divHtml .= ' 	<span class="flase_sale_price" style="white-space: nowrap;'.$flashsale_style.'">Flash-Sale : $<input type="text" value="'.$single['price'].'00" class="varedit_price" id="varedit_price_'.$single['id'].'" data-id="'.$single['id'].'" style="width:50px;">
					</span>';

					//Task 34 start
					$divHtml .= '	<input type="hidden" value="'.$default_price_on_demand.'" id="varedit_default_price_on_demand_'.$single['id'].'">';
					// $divHtml .= '	<input type="hidden" value="'.$single['price_on_demand'].'" class="form-control varedit_price_on_demand" id="varedit_price_on_demand_'.$single['id'].'" data-id="'.$single['id'].'">';
					$divHtml .= ' 	<span class="on_deman_price" style="white-space: nowrap;'.$ondemand_style.'">On-Demand : $<input type="text" value="'.$single['price_on_demand'].'" class="varedit_price_on_demand" id="varedit_price_on_demand_'.$single['id'].'" data-id="'.$single['id'].'" style="width:50px;"></span>';
					// $divHtml .= ' 	<span class="on_deman_price" style="white-space: nowrap;'.$ondemand_style.'">On-Demand : $<span id="showedit_price_on_demand_'.$single['id'].'">'.$single['price_on_demand'].'</span></span>';
					//Task 34 end


					$divHtml .= '</td>';
					$divHtml .= '<td><span>+&nbsp;&nbsp;$</span><input type="text" value="'.$single['fundraising_price'].'" class="varedit_fundraising_price" id="varedit_fundraising_price_'.$single['id'].'" data-id="'.$single['id'].'" style="width: 50px;"><span>&nbsp;&nbsp;=</span></td>';
					$divHtml .= '<td>';
					$divHtml .= '<span class="flase_sale_price" style="white-space: nowrap;'.$flashsale_style.'">Flash-Sale : $<span id="varedit_total_'.$single['id'].'">'.($single['price']+$single['fundraising_price']).'</span></span>';
					$divHtml .= '<span class="on_deman_price" style="white-space: nowrap;'.$ondemand_style.'">On-Demand : $<span id="varedit_total_on_demand_'.$single['id'].'">'.($single['price_on_demand']+$single['fundraising_price']).'</span></span>';
					$divHtml .= '</td>';
					//$divHtml .= '<td>';
					
					//$divHtml .= '<button type="button" class="btn btn-danger btn-sm btn-round varedit_variant_delete" data-id="'.$single['id'].'" data-toggle="tooltip" data-placement="top" data-trigger="hover" data-original-title="Remove Variant" title=""><i class="fa-trash"></i></button>';
					//$divHtml .= '</td>';

					$divHtml .= '<td>';

					$divHtml .= '<input type="checkbox" class="choose_variant choose_variant_for_delete" value="'.$single['id'].'">

					<button type="button" class="btn btn-danger btn-sm btn-round varedit_variant_delete" data-id="'.$single['id'].'" data-toggle="tooltip" data-placement="top" data-trigger="hover" data-original-title="Remove Variant" title=""><i class="fa-trash"></i></button>';

					$divHtml .= '</td>';
					$divHtml .= '</tr>';

				}
				$divHtml .= '</table>';
				$divHtml .= '</div>';
				$divHtml .= '</div>';
			}else{
				$divHtml .= '<div style="text-align: center">No data available.</div>';
			}

			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = '';
			$res['divHtml'] = $divHtml;
		}else{
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'Invalid request';
		}

		echo json_encode($res,1);
	}
	public function save_all_variants_by_product_post(){
		if(isset($_POST['product_id']) && !empty($_POST['product_id']) && isset($_POST['store_variant_updated_data_json']) && !empty($_POST['store_variant_updated_data_json']) ){
			$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
			if(!empty($shop_data)) {
				require_once('lib/class_graphql.php');

				$shop = $shop_data[0]['shop_name'];
				$token = $shop_data[0]['token'];

				$headers = array(
					'X-Shopify-Access-Token' => $token
				);
				$graphql = new Graphql($shop, $headers);
			}

			/*
			* Table store_owner_product_master
			* Task 43 update product wise fundrising 24-09-21
			*/
			$storeOwnerProductMaster = [
			    'is_product_fundraising'   => $_POST['is_fundraising'],
			    'product_fundraising_price'=> $_POST['product_fundraising_price'],
			    'is_individual'            => 'Yes'
			];
			parent::updateTable_f_mdl('store_owner_product_master',$storeOwnerProductMaster,'id="'.$_POST['product_id'].'"');
			//end

			$var_arr = json_decode($_POST['store_variant_updated_data_json'],1);
			foreach($var_arr as $single_var){
				if(!empty($single_var['id'])){
					$sql = 'SELECT id, shop_product_id, shop_variant_id FROM `store_owner_product_variant_master`
					WHERE id="'.$single_var['id'].'"
					AND store_owner_product_master_id="'.$_POST['product_id'].'"
					';
					$var_data = parent::selectTable_f_mdl($sql);
					if(!empty($var_data)){
						$sopvm_update_data = [
							'price' => trim($single_var['price']),
							'price_on_demand' => trim($single_var['price_on_demand']),//Task 34
							'fundraising_price' => trim($single_var['fundraising_price']),
							'color' => trim($single_var['color']),
							'size' => trim($single_var['size']),
							'sku' => trim($single_var['sku'])
						];
						parent::updateTable_f_mdl('store_owner_product_variant_master',$sopvm_update_data,'id="'.$single_var['id'].'" AND store_owner_product_master_id="'.$_POST['product_id'].'"');

						if(isset($var_data[0]['shop_product_id']) && !empty($var_data[0]['shop_product_id']) &&
							isset($var_data[0]['shop_variant_id']) && !empty($var_data[0]['shop_variant_id']) &&
							isset($graphql)
						){
							sleep(0.5);

							/*
							* Task 41
							* Issue was: Price not match after product image update
							* resolved issue on 22-09-2021
							*/
							if(isset($_POST['store_sale_type_master_id']) && $_POST['store_sale_type_master_id']==2){
								if($_POST['is_fundraising']=='Yes'){
									$input_price = floatval(trim($single_var['price_on_demand'])) + floatval(trim($single_var['fundraising_price']))+$_POST['price_difference'];
								}else{
									$input_price = floatval(trim($single_var['price_on_demand']))+$_POST['price_difference'];
								}
							}else{
								if($_POST['is_fundraising']=='Yes'){
									$input_price = floatval(trim($single_var['price'])) + floatval(trim($single_var['fundraising_price']))+$_POST['price_difference'];
								}else{
									$input_price = floatval(trim($single_var['price']))+$_POST['price_difference'];
								}
							}
							/*
							* end
							*/ 
							
							$sql = "SELECT product_color_name FROM store_product_colors_master WHERE product_color ='".$single_var['color']."' LIMIT 1";
							$colorInfo = parent::selectTable_f_mdl($sql);
							$mainVarColorName = '';
							if(!empty($colorInfo[0]['product_color_name'])){
								$mainVarColorName = $colorInfo[0]['product_color_name'];
							}

							$mutationData = 'mutation productVariantUpdate($input: ProductVariantInput!) {
							  productVariantUpdate(input: $input) {
								productVariant {
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
								"id":"gid://shopify/ProductVariant/'.$var_data[0]['shop_variant_id'].'",
								"price":"'.( $input_price ).'",
								"sku":"'.trim($single_var['sku']).'",
								"options":["'.trim($single_var['size']).'","'.@$mainVarColorName.'"]
							  }
							}';
							$graphql->runByMutation($mutationData,$inputData);
						}
					}
				}
			}
			
			if(isset($_FILES) && !empty($_FILES)){
				$upload_dir = 'image_uploads/';
				
				//fetch default logo for merge with product image
				$sql = 'SELECT logo_image FROM store_design_logo_master WHERE store_master_id="'.$_POST['store_master_id'].'" AND is_default="1"';
				$default_logo_data = parent::selectTable_f_mdl($sql);

				foreach($_FILES as $key=>$val){
					$id = str_replace('var_img_','',$key);

					if($id!=''){
						//fetch shop pro-var ids from db
						$sql = 'SELECT id, shop_product_id, shop_variant_id FROM `store_owner_product_variant_master`
						WHERE id="'.$id.'"
						AND store_owner_product_master_id="'.$_POST['product_id'].'"
						';
						$var_data = parent::selectTable_f_mdl($sql);

						if(!empty($var_data)){
							
							if(isset($val['name'][0]) && !empty($val['name'][0]) && empty($val['error'][0])){
								

								$file_arr = explode('.',$val['name'][0]);
								$ext = array_pop($file_arr);
								$file_name = time().rand(100000,999999).'.'.$ext;

								if(move_uploaded_file($val['tmp_name'][0], $upload_dir.$file_name)){
									
									//update new image in database
									if(isset($default_logo_data[0]['logo_image']) && !empty($default_logo_data[0]['logo_image']) && file_exists($upload_dir.$default_logo_data[0]['logo_image'])){
										$merged_image = parent::merge_two_images($upload_dir,$file_name,$default_logo_data[0]['logo_image']);
									}else{
										$merged_image = $file_name;
									}

									$sopvm_update_data = [
										'image' => $merged_image,
										'original_image' => $file_name
									];
									parent::updateTable_f_mdl('store_owner_product_variant_master',$sopvm_update_data,'id="'.$id.'" AND store_owner_product_master_id="'.$_POST['product_id'].'"');

									//if we have shopify pro-var ids, then sync image in shopify
									if(isset($var_data[0]['shop_product_id']) && !empty($var_data[0]['shop_product_id']) &&
										isset($var_data[0]['shop_variant_id']) && !empty($var_data[0]['shop_variant_id']) &&
										isset($graphql)
									){
										
										//first we fetch existing image-id from variant for delete
										$gql_query = '{
										  productVariant(id:"gid://shopify/ProductVariant/'.$var_data[0]['shop_variant_id'].'"){
											image{
											  id
											}
										  }
										}';
										$var_img_data = $graphql->runByQuery($gql_query);

										//add new image in main product
										$mutationData = 'mutation productAppendImages($input: ProductAppendImagesInput!) {
										  productAppendImages(input: $input) {
											newImages {
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
											"id": "gid://shopify/Product/'.$var_data[0]['shop_product_id'].'",
											"images": [
											  {
												"src": "'.common::IMAGE_UPLOAD_URL.$merged_image.'"
											  }
											]
										  }
										}';
										sleep(0.5);
										$gqlNewImgData = $graphql->runByMutation($mutationData,$inputData);
										
										if(isset($gqlNewImgData['data']['productAppendImages']['newImages'][0]['id']) && !empty($gqlNewImgData['data']['productAppendImages']['newImages'][0]['id'])){
											$newImgId = $gqlNewImgData['data']['productAppendImages']['newImages'][0]['id'];	//like-this : gid://shopify/ProductImage/11366759465026

											//delete old image from variant
											if(isset($var_img_data['data']['productVariant']['image']['id']) && !empty($var_img_data['data']['productVariant']['image']['id'])){
												$ImgIdForDelete = $var_img_data['data']['productVariant']['image']['id'];
												$mutationData = 'mutation productDeleteImages($id: ID!, $imageIds: [ID!]!) {
												  productDeleteImages(id: $id, imageIds: $imageIds) {
													deletedImageIds
													product {
													  id
													}
													userErrors {
													  field
													  message
													}
												  }
												}';
												$inputData = '{
												  "id": "gid://shopify/Product/'.$var_data[0]['shop_product_id'].'",
												  "imageIds": ["'.$ImgIdForDelete.'"]
												}';
												sleep(0.5);
												$graphql->runByMutation($mutationData,$inputData);
											}

											//assign new image in variant
											$mutationData = 'mutation productVariantUpdate($input: ProductVariantInput!) {
											  productVariantUpdate(input: $input) {
												productVariant {
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
												"id": "gid://shopify/ProductVariant/'.$var_data[0]['shop_variant_id'].'",
												"imageId": "'.$newImgId.'"
											  }
											}';
											sleep(0.5);
											$graphql->runByMutation($mutationData,$inputData);

										}

									}
								}
							}
						}
					}
				}
			}

			/*New Code Update*/
			$sql = 'SELECT id, shop_product_id, shop_variant_id, image FROM `store_owner_product_variant_master`
						WHERE store_owner_product_master_id="'.$_POST['product_id'].'"
						';
			$Productvar_data = parent::selectTable_f_mdl($sql);
			if($Productvar_data){
				foreach ($Productvar_data as $key => $proValue) {
					$shop_product_id = $proValue['shop_product_id'];
					$shop_variant_id = $proValue['shop_variant_id'];
					$merged_image = $image = $proValue['image'];

					$gql_query = '{
					  productVariant(id:"gid://shopify/ProductVariant/'.$shop_variant_id.'"){
						image{
						  id
						}
					  }
					}';
					$var_img_data = $graphql->runByQuery($gql_query);

					//add new image in main product
					$mutationData = 'mutation productAppendImages($input: ProductAppendImagesInput!) {
					  productAppendImages(input: $input) {
						newImages {
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
						"id": "gid://shopify/Product/'.$shop_product_id.'",
						"images": [
						  {
							"src": "'.common::IMAGE_UPLOAD_URL.$merged_image.'"
						  }
						]
					  }
					}';
					sleep(0.5);
					$gqlNewImgData = $graphql->runByMutation($mutationData,$inputData);
					
					if(isset($gqlNewImgData['data']['productAppendImages']['newImages'][0]['id']) && !empty($gqlNewImgData['data']['productAppendImages']['newImages'][0]['id'])){
						$newImgId = $gqlNewImgData['data']['productAppendImages']['newImages'][0]['id'];	//like-this : gid://shopify/ProductImage/11366759465026

						//delete old image from variant
						if(isset($var_img_data['data']['productVariant']['image']['id']) && !empty($var_img_data['data']['productVariant']['image']['id'])){
							$ImgIdForDelete = $var_img_data['data']['productVariant']['image']['id'];
							$mutationData = 'mutation productDeleteImages($id: ID!, $imageIds: [ID!]!) {
							  productDeleteImages(id: $id, imageIds: $imageIds) {
								deletedImageIds
								product {
								  id
								}
								userErrors {
								  field
								  message
								}
							  }
							}';
							$inputData = '{
							  "id": "gid://shopify/Product/'.$shop_product_id.'",
							  "imageIds": ["'.$ImgIdForDelete.'"]
							}';
							sleep(0.5);
							$graphql->runByMutation($mutationData,$inputData);
						}

						//assign new image in variant
						$mutationData = 'mutation productVariantUpdate($input: ProductVariantInput!) {
						  productVariantUpdate(input: $input) {
							productVariant {
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
							"id": "gid://shopify/ProductVariant/'.$shop_variant_id.'",
							"imageId": "'.$newImgId.'"
						  }
						}';
						sleep(0.5);
						$graphql->runByMutation($mutationData,$inputData);

					}

				}
			}
			/*New Code Update*/

			$sql = 'SELECT front_side_ink_colors, back_side_ink_colors, store_fulfillment_type, is_fundraising FROM `store_master`
					WHERE id="'.$_POST['store_master_id'].'"
					';
			$store_master_data = parent::selectTable_f_mdl($sql);

			$store_description = '';
			//Task 38 start
			if (isset($_POST['store_sale_type']) && $_POST['store_sale_type']==1) {
				if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_1_LOCATION_NOT_SORT'){
					$store_description = COMMON::DESCRIPTION_FOR_OPEN_FLASH_STORE;
				}else if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
					$store_description = COMMON::DEFAULT_DESCRIPTION_FOR_SILVER_GOLD_FULFILLMENT;
				}else if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
					$store_description = COMMON::DEFAULT_DESCRIPTION_FOR_PLATINUM_FULFILLMENT;
				}
			}
			else
			{
				$store_description = COMMON::DESCRIPTION_FOR_OPEN_ONDEMAND_STORE;
			}
			//Task 38 end	

			$sm_update_data = [
				'front_side_ink_colors' => trim($_POST['front_side_ink_colors']),
				'back_side_ink_colors' => trim($_POST['back_side_ink_colors']),
				'store_fulfillment_type' => trim($_POST['store_fulfillment_type']),
				'store_description' => trim($store_description),
				// 'is_fundraising' => trim($_POST['is_fundraising']),//Task 43
				'is_bulk_pricing' => trim($_POST['is_bulk_pricing']) //Task 2 new changes
			];
			parent::updateTable_f_mdl('store_master',$sm_update_data,'id="'.$_POST['store_master_id'].'"');

			//if any store level changes are made, then we need to change price for all products
			if( (isset($store_master_data[0]['front_side_ink_colors']) && $store_master_data[0]['front_side_ink_colors']!=$_POST['front_side_ink_colors'])
				OR (isset($store_master_data[0]['back_side_ink_colors']) && $store_master_data[0]['back_side_ink_colors']!=$_POST['back_side_ink_colors'])
				OR (isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']!=$_POST['store_fulfillment_type'])
				OR (isset($store_master_data[0]['is_fundraising']) && $store_master_data[0]['is_fundraising']!=$_POST['is_fundraising'])
			){
				//now fetch all variants of all product of same store excluding current product, bcoz current product is already updated
				$sql = 'SELECT `store_owner_product_variant_master`.id, `store_owner_product_variant_master`.shop_product_id, shop_variant_id, price, price_on_demand, fundraising_price FROM `store_owner_product_variant_master`
					LEFT JOIN store_owner_product_master ON store_owner_product_master.id = `store_owner_product_variant_master`.store_owner_product_master_id
					WHERE store_owner_product_master.store_master_id="'.$_POST['store_master_id'].'"
					AND store_owner_product_variant_master.store_owner_product_master_id!="'.$_POST['product_id'].'"
					';
				$var_data = parent::selectTable_f_mdl($sql);
				if(!empty($var_data)){
					foreach($var_data as $single_var){
						$sopvm_update_data = [
							'price' => trim($single_var['price']+$_POST['price_difference']),
							'price_on_demand' => trim($single_var['price_on_demand']+$_POST['price_difference'])
						];
						parent::updateTable_f_mdl('store_owner_product_variant_master',$sopvm_update_data,'id="'.$single_var['id'].'"');

						if(isset($var_data[0]['shop_product_id']) && !empty($var_data[0]['shop_product_id']) &&
							isset($var_data[0]['shop_variant_id']) && !empty($var_data[0]['shop_variant_id']) &&
							isset($graphql)
						){
							sleep(0.5);
							if(isset($_POST['store_sale_type_master_id']) && $_POST['store_sale_type_master_id']==2){
								if($_POST['is_fundraising']=='Yes'){
									$input_price = floatval(trim($single_var['price_on_demand'])) + floatval(trim($single_var['fundraising_price']))+$_POST['price_difference'];
								}else{
									$input_price = floatval(trim($single_var['price_on_demand']))+$_POST['price_difference'];
								}
							}else{
								if($_POST['is_fundraising']=='Yes'){
									$input_price = floatval(trim($single_var['price'])) + floatval(trim($single_var['fundraising_price']))+$_POST['price_difference'];
								}else{
									$input_price = floatval(trim($single_var['price']))+$_POST['price_difference'];
								}
							}

							$mutationData = 'mutation productVariantUpdate($input: ProductVariantInput!) {
								  productVariantUpdate(input: $input) {
									productVariant {
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
									"id":"gid://shopify/ProductVariant/'.$var_data[0]['shop_variant_id'].'",
									"price":"'.( $input_price ).'"
								  }
								}';
							$graphql->runByMutation($mutationData,$inputData);
						}
					}
				}
			}
			
			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = 'Product variants updated successfully';
			$res['REDIRECT_URL'] = 'sa-store-view.php?stkn='.$_POST['stkn'].'&id='.$_POST['store_master_id'].'&tab=products';
		}else{
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'Invalid request';
		}
		echo json_encode($res,1);
	}

	/**********/
	public function save_store_level_settings(){
		$arraRes = array();
		$ipx = 0;
		$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
		if(!empty($shop_data)) {
			require_once('lib/class_graphql.php');

			$shop = $shop_data[0]['shop_name'];
			$token = $shop_data[0]['token'];

			$headers = array(
				'X-Shopify-Access-Token' => $token
			);
			$graphql = new Graphql($shop, $headers);
		}

		

		$sql = 'SELECT front_side_ink_colors, back_side_ink_colors, store_fulfillment_type, is_fundraising, ct_fundraising_price FROM `store_master`
					WHERE id="'.$_POST['store_master_id'].'"
					';
		$store_master_data = parent::selectTable_f_mdl($sql);

		$store_fulfillment_type = $_POST['store_fulfillment_type'];
		
		$add_color_cost = 0;
		if(isset($_POST['front_side_ink_colors']) && !empty($_POST['front_side_ink_colors'])){
			$add_color_cost += intval($_POST['front_side_ink_colors'])-1;
		}
		if(isset($_POST['back_side_ink_colors']) && !empty($_POST['back_side_ink_colors'])){
			$add_color_cost += common::ADD_COST_BACK_SIDE_INK_COLOR+(intval($_POST['back_side_ink_colors'])-1);
		}

		/*fundraising_price Update*/


		$store_description = '';
		//Task 38 start
		if (isset($_POST['store_sale_type_master_id']) && $_POST['store_sale_type_master_id']==1) {
			if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_1_LOCATION_NOT_SORT'){
				$store_description = COMMON::DESCRIPTION_FOR_OPEN_FLASH_STORE;
			}else if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
				$store_description = COMMON::DEFAULT_DESCRIPTION_FOR_SILVER_GOLD_FULFILLMENT;
			}else if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
				$store_description = COMMON::DEFAULT_DESCRIPTION_FOR_PLATINUM_FULFILLMENT;
			}
		}
		else
		{
			$store_description = COMMON::DESCRIPTION_FOR_OPEN_ONDEMAND_STORE;
		}
		//Task 38 end	

		$sm_update_data = [
			'front_side_ink_colors' => trim($_POST['front_side_ink_colors']),
			'back_side_ink_colors' => trim($_POST['back_side_ink_colors']),
			'store_fulfillment_type' => trim($_POST['store_fulfillment_type']),
			'store_description' => trim($store_description),
			//'is_fundraising' => trim($_POST['is_fundraising']) //Task 43
		];
		parent::updateTable_f_mdl('store_master',$sm_update_data,'id="'.$_POST['store_master_id'].'"');
		//if any store level changes are made, then we need to change price for all products
		if( (isset($store_master_data[0]['front_side_ink_colors']) && $store_master_data[0]['front_side_ink_colors']!=$_POST['front_side_ink_colors'])
			OR (isset($store_master_data[0]['back_side_ink_colors']) && $store_master_data[0]['back_side_ink_colors']!=$_POST['back_side_ink_colors'])
			OR (isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']!=$_POST['store_fulfillment_type'])
			OR (isset($store_master_data[0]['is_fundraising']) && $store_master_data[0]['is_fundraising']!=$_POST['is_fundraising']) 
		){
			//now fetch all variants of all product of same store excluding current product, bcoz current product is already updated
			$sql = 'SELECT store_owner_product_master.store_product_master_id AS master_product_id, `store_owner_product_variant_master`.id, `store_owner_product_variant_master`.shop_product_id, shop_variant_id, price, price_on_demand, fundraising_price,store_product_variant_master_id FROM `store_owner_product_variant_master`
					LEFT JOIN store_owner_product_master ON store_owner_product_master.id = `store_owner_product_variant_master`.store_owner_product_master_id
					WHERE store_owner_product_master.store_master_id="'.$_POST['store_master_id'].'"
					';// Add store_product_variant_master_id Task 36
			$var_data = parent::selectTable_f_mdl($sql);

			//Task 53
			$getGeneralSettingDetails = $this->getGeneralSettingDetails();
			if(isset($getGeneralSettingDetails[0]['is_enable_in_home']) && $getGeneralSettingDetails[0]['is_enable_in_home']==1){
				$is_enable_type_third = "Yes";
			}else{
				$is_enable_type_third = "No";
			}

			if(isset($getGeneralSettingDetails[0]['is_enable_in_bagged']) && $getGeneralSettingDetails[0]['is_enable_in_bagged']==1){
				$is_enable_type_second = "Yes";
			}else{
				$is_enable_type_second = "No";
			}
			//Task 53

			if(!empty($var_data)){
				foreach($var_data as $single_var){

					//Task 53
					$price_update_setting_change = 0;
					if(isset($_POST['store_sale_type_master_id']) && $_POST['store_sale_type_master_id']==1){
						if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=="SHIP_EACH_FAMILY_HOME" && $is_enable_type_third=="No"){
							$price_update_setting_change = COMMON::ADD_COST_STORE_FULFILLMENT_TYPE_3;
						}

						if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=="SHIP_1_LOCATION_SORT" && $is_enable_type_second=="No"){
							$price_update_setting_change = COMMON::ADD_COST_STORE_FULFILLMENT_TYPE_2;
						}
					}
					//Task 53

					$sopvm_update_data = [
						'price' => trim($single_var['price']+$_POST['price_difference'])-$price_update_setting_change,
						'price_on_demand' => trim($single_var['price_on_demand']+$_POST['on_demand_price_difference'])
					];
					parent::updateTable_f_mdl('store_owner_product_variant_master',$sopvm_update_data,'id="'.$single_var['id'].'"');

					//Task 36 end
					if(isset($single_var['shop_product_id']) && !empty($single_var['shop_product_id']) &&
						isset($single_var['shop_variant_id']) && !empty($single_var['shop_variant_id']) &&
						isset($graphql)
					){
						sleep(0.5);

					if(isset($_POST['store_sale_type_master_id']) && $_POST['store_sale_type_master_id']==2){
							$input_price = floatval(trim($single_var['price_on_demand']))+floatval(trim($single_var['fundraising_price']))+$_POST['on_demand_price_difference'];
						}else{
							
							$input_price = floatval(trim($single_var['price']))+floatval(trim($single_var['fundraising_price']))+$_POST['price_difference'];
						}

						$mutationData = 'mutation productVariantUpdate($input: ProductVariantInput!) {
								  productVariantUpdate(input: $input) {
									productVariant {
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
									"id":"gid://shopify/ProductVariant/'.$single_var['shop_variant_id'].'",
									"price":"'.( $input_price ).'"
								  }
								}';
						$graphql->runByMutation($mutationData,$inputData);

						$arraRes[$ipx]['price'] = $input_price;
						$arraRes[$ipx]['shop_variant_id'] = $single_var['shop_variant_id'];
						$ipx++;

					}
				}
			}
		}
		$res['SUCCESS'] = 'TRUE';
		$res['MESSAGE'] = 'Store data updated successfully';
		$res['REDIRECT_URL'] = 'sa-store-view.php?stkn='.$_POST['stkn'].'&id='.$_POST['store_master_id'].'';
		echo json_encode($res,1);
	}
	/************/

	/*
	* Task 43 28-09-21
	* separately save fundrising
	*/
	public function only_fundrising_save_store_level_settings(){
		$arraRes = array();
		$ipx = 0;
		$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
		if(!empty($shop_data)) {
			require_once('lib/class_graphql.php');

			$shop = $shop_data[0]['shop_name'];
			$token = $shop_data[0]['token'];

			$headers = array(
				'X-Shopify-Access-Token' => $token
			);
			$graphql = new Graphql($shop, $headers);
		}

		$sql = 'SELECT front_side_ink_colors, back_side_ink_colors, store_fulfillment_type, is_fundraising, ct_fundraising_price FROM `store_master`
					WHERE id="'.$_POST['store_master_id'].'"
					';
		$store_master_data = parent::selectTable_f_mdl($sql);


		/*fundraising_price Update*/
		$fundraisingPrice = $_POST['ct_fundraising_price'];
		$ct_fundraising   = $_POST['ct_fundraising'];
		$_POST['is_fundraising'] = $ct_fundraising;

		if($ct_fundraising == 'Yes'){
			$sopvmStoreData = [
				'ct_fundraising_price' => $fundraisingPrice
			];
			parent::updateTable_f_mdl('store_master',$sopvmStoreData,'id="'.$_POST['store_master_id'].'"');

			$storeListData = parent::selectTable_f_mdl("SELECT * FROM store_master WHERE id='".$_POST['store_master_id']."'");

			$storeMasterData = parent::selectTable_f_mdl("SELECT * FROM store_owner_product_master WHERE store_master_id = '".$_POST['store_master_id']."'");
			if($storeMasterData){
				foreach ($storeMasterData as $storeMasterProduct) {
					$idProduct = $storeMasterProduct['id'];
					$sopvmUpdateData = [
						'fundraising_price' => trim($fundraisingPrice)
					];
					parent::updateTable_f_mdl('store_owner_product_variant_master',$sopvmUpdateData,'store_owner_product_master_id="'.$idProduct.'"');
				}
			}
		}
		else{
			// Task 43 25-09-21
			$sopvmStoreData = [
				'ct_fundraising_price' => $fundraisingPrice
			];

			parent::updateTable_f_mdl('store_master',$sopvmStoreData,'id="'.$_POST['store_master_id'].'"');

			$storeListData = parent::selectTable_f_mdl("SELECT * FROM store_master WHERE id='".$_POST['store_master_id']."'");
			$storeMasterData = parent::selectTable_f_mdl("SELECT * FROM store_owner_product_master WHERE store_master_id = '".$_POST['store_master_id']."'");
			if($storeMasterData){
				foreach ($storeMasterData as $storeMasterProduct) {
					$idProduct = $storeMasterProduct['id'];
					$sopvmUpdateData = [
						'fundraising_price' => trim($fundraisingPrice)
					];
					parent::updateTable_f_mdl('store_owner_product_variant_master',$sopvmUpdateData,'store_owner_product_master_id="'.$idProduct.'"');
				}
			}	
		}

		$sm_update_data = [
			'is_fundraising' => trim($_POST['is_fundraising'])
		];
		parent::updateTable_f_mdl('store_master',$sm_update_data,'id="'.$_POST['store_master_id'].'"');

		$checkIndividualFundrising = 0;
		if(isset($store_master_data[0]['ct_fundraising_price'])){
			$checkIndividualFundrising = $this->checkIndividualProductFundrising($_POST['store_master_id'],$store_master_data[0]['ct_fundraising_price']);
		}

		if(count($checkIndividualFundrising)>0)
		{
			$storeOwnerProductMaster = [
				'is_product_fundraising'   => 'No',
				'product_fundraising_price'=> 'NULL',
				'is_individual'            => 'No'
			];
			parent::updateTable_f_mdl('store_owner_product_master',$storeOwnerProductMaster,'	store_master_id="'.$_POST['store_master_id'].'"');
		}

		//if any store level changes are made, then we need to change price for all products
		if( (isset($store_master_data[0]['is_fundraising']) && $store_master_data[0]['is_fundraising']!=$_POST['is_fundraising']) 
			OR (isset($store_master_data[0]['ct_fundraising_price']) && $store_master_data[0]['ct_fundraising_price']!=$fundraisingPrice)
			OR (count($checkIndividualFundrising)>0)
		){
			$sql = 'SELECT store_owner_product_master.store_product_master_id AS master_product_id, `store_owner_product_variant_master`.id, `store_owner_product_variant_master`.shop_product_id, shop_variant_id, price, price_on_demand, fundraising_price,store_product_variant_master_id FROM `store_owner_product_variant_master`
					LEFT JOIN store_owner_product_master ON store_owner_product_master.id = `store_owner_product_variant_master`.store_owner_product_master_id
					WHERE store_owner_product_master.store_master_id="'.$_POST['store_master_id'].'"
					';
			$var_data = parent::selectTable_f_mdl($sql);
			if(!empty($var_data)){
				foreach($var_data as $single_var){

					$sopvm_update_data = [
						'price' => trim($single_var['price']+$_POST['price_difference']),
						'price_on_demand' => trim($single_var['price_on_demand']+$_POST['on_demand_price_difference'])
					];
					parent::updateTable_f_mdl('store_owner_product_variant_master',$sopvm_update_data,'id="'.$single_var['id'].'"');

					if(isset($single_var['shop_product_id']) && !empty($single_var['shop_product_id']) &&
						isset($single_var['shop_variant_id']) && !empty($single_var['shop_variant_id']) &&
						isset($graphql)
					){
						sleep(0.5);

					if(isset($_POST['store_sale_type_master_id']) && $_POST['store_sale_type_master_id']==2){
					
							if($_POST['is_fundraising']=='Yes'){
								$input_price = floatval(trim($single_var['price_on_demand'])) + floatval(trim($single_var['fundraising_price']))+$_POST['on_demand_price_difference']; // Task 36 change post variable
							}else{
								$input_price = floatval(trim($single_var['price_on_demand']))+$_POST['on_demand_price_difference']; // Task 36 change post variable
							}
						}else{
							if($_POST['is_fundraising']=='Yes'){
								$input_price = floatval(trim($single_var['price'])) + floatval(trim($single_var['fundraising_price']))+$_POST['price_difference'];
							}else{
								$input_price = floatval(trim($single_var['price']))+$_POST['price_difference'];
							}
						}

						$mutationData = 'mutation productVariantUpdate($input: ProductVariantInput!) {
								  productVariantUpdate(input: $input) {
									productVariant {
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
									"id":"gid://shopify/ProductVariant/'.$single_var['shop_variant_id'].'",
									"price":"'.( $input_price ).'"
								  }
								}';
						$graphql->runByMutation($mutationData,$inputData);

						$arraRes[$ipx]['price'] = $input_price;
						$arraRes[$ipx]['shop_variant_id'] = $single_var['shop_variant_id'];
						$ipx++;

					}
				}
			}
		}
		$res['SUCCESS'] = 'TRUE';
		$res['MESSAGE'] = 'Store data updated successfully';
		$res['REDIRECT_URL'] = 'sa-store-view.php?stkn='.$_POST['stkn'].'&id='.$_POST['store_master_id'].'';
		echo json_encode($res,1);
	}
	//end
	
	public function store_variant_delete_post(){
		if(isset($_POST['variant_delete_id']) && !empty($_POST['variant_delete_id'])){
			$deleteProductIdArray = explode(",",$_POST['variant_delete_id']);

			foreach($deleteProductIdArray as $deleteId){
				$sql = 'SELECT var.id, var.shop_variant_id
				FROM store_owner_product_variant_master as var
				LEFT JOIN `store_owner_product_master` ON `store_owner_product_master`.id = var.store_owner_product_master_id
				LEFT JOIN `store_master` ON `store_master`.id = `store_owner_product_master`.store_master_id
				WHERE var.id = "'.$deleteId.'"
				';
				$var_exist = parent::selectTable_f_mdl($sql);

				if(!empty($var_exist)){
					parent::deleteTable_f_mdl('store_owner_product_variant_master','id="'.$deleteId.'"');

					if(isset($var_exist[0]['shop_variant_id']) && !empty($var_exist[0]['shop_variant_id'])){
						// if we have variant-id , then delete from shopify
						$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
						if(!empty($shop_data)) {
							require_once('lib/class_graphql.php');

							$shop = $shop_data[0]['shop_name'];
							$token = $shop_data[0]['token'];

							$headers = array(
								'X-Shopify-Access-Token' => $token
							);
							$graphql = new Graphql($shop, $headers);

							$mutationData = 'mutation productVariantDelete($id: ID!) {
							productVariantDelete(id: $id) {
								deletedProductVariantId
								product {
								id
								}
								userErrors {
								field
								message
								}
							}
							}';
							$inputData = '{
							"id": "gid://shopify/ProductVariant/'.$var_exist[0]['shop_variant_id'].'"
							}';
							$graphql->runByMutation($mutationData,$inputData);
						}
					}

					$res['SUCCESS'] = 'TRUE';
					$res['MESSAGE'] = 'Variant deleted successfully.';
					$res['REDIRECT_URL'] = 'sa-store-view.php?stkn='.$_POST['stkn'].'&id='.$_POST['store_master_id'].'&tab=products';
				}else{
					$res['SUCCESS'] = 'FALSE';
					$res['MESSAGE'] = 'Invalid request';
				}
			}
		}else{
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'Invalid request';
		}
		echo json_encode($res,1);
	}

	public function edit_store_apply_logo(){
		if(isset($_POST['store_master_id']) && !empty($_POST['store_master_id']) && isset($_POST['al_logo_id']) && !empty($_POST['al_logo_id']) && isset($_POST['al_product_ids']) && !empty($_POST['al_product_ids']) ){
			$upload_dir = 'image_uploads/';

			$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
			if(!empty($shop_data)) {
				require_once('lib/class_graphql.php');

				$shop = $shop_data[0]['shop_name'];
				$token = $shop_data[0]['token'];

				$headers = array(
					'X-Shopify-Access-Token' => $token
				);
				$graphql = new Graphql($shop, $headers);
			}

			$sql = 'SELECT logo_image FROM store_design_logo_master
			WHERE id = "'.$_POST['al_logo_id'].'"
			AND store_master_id = "'.$_POST['store_master_id'].'"
			';
			$logo_data = parent::selectTable_f_mdl($sql);
			//common::IMAGE_UPLOAD_URL

			if(isset($logo_data[0]['logo_image']) && !empty($logo_data[0]['logo_image']) && file_exists($upload_dir.$logo_data[0]['logo_image'])){
				$logo_img = $logo_data[0]['logo_image'];


				$sql = 'SELECT var.id, var.original_image FROM store_owner_product_variant_master as var
				LEFT JOIN store_owner_product_master as pro ON pro.id = var.store_owner_product_master_id
				WHERE var.store_owner_product_master_id IN('.$_POST['al_product_ids'].')
				AND pro.store_master_id = "'.$_POST['store_master_id'].'"
				';
				$var_data = parent::selectTable_f_mdl($sql);
				if(!empty($var_data)){
					$image_for_flyer = '';
					foreach($var_data as $single_var){
						if(isset($single_var['original_image']) && !empty($single_var['original_image']) && file_exists($upload_dir.$single_var['original_image'])){
							//sleep(1);
							$pro_img = $single_var['original_image'];

							$merged_image = parent::merge_two_images($upload_dir,$pro_img,$logo_img);

							if(file_exists($upload_dir.$merged_image)){
								if($image_for_flyer==''){
									$image_for_flyer = $merged_image;
								}

								$sopvm_update_data = [
									'image' => $merged_image
								];
								parent::updateTable_f_mdl('store_owner_product_variant_master',$sopvm_update_data,'id="'.$single_var['id'].'"');

								//if we have shopify pro-var ids, then sync image in shopify
								if(isset($single_var['shop_product_id']) && !empty($single_var['shop_product_id']) &&
									isset($single_var['shop_variant_id']) && !empty($single_var['shop_variant_id']) &&
									isset($graphql)
								){
									//first we fetch existing image-id from variant for delete
									$gql_query = '{
										  productVariant(id:"gid://shopify/ProductVariant/'.$single_var['shop_variant_id'].'"){
											image{
											  id
											}
										  }
										}';
									$var_img_data = $graphql->runByQuery($gql_query);

									//add new image in main product
									$mutationData = 'mutation productAppendImages($input: ProductAppendImagesInput!) {
										  productAppendImages(input: $input) {
											newImages {
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
											"id": "gid://shopify/Product/'.$single_var['shop_product_id'].'",
											"images": [
											  {
												"src": "'.common::IMAGE_UPLOAD_URL.$merged_image.'"
											  }
											]
										  }
										}';
									sleep(0.5);
									$gqlNewImgData = $graphql->runByMutation($mutationData,$inputData);
									if(isset($gqlNewImgData['data']['productAppendImages']['newImages'][0]['id']) && !empty($gqlNewImgData['data']['productAppendImages']['newImages'][0]['id'])){
										$newImgId = $gqlNewImgData['data']['productAppendImages']['newImages'][0]['id'];	//like-this : gid://shopify/ProductImage/11366759465026

										//delete old image from variant
										if(isset($var_img_data['data']['productVariant']['image']['id']) && !empty($var_img_data['data']['productVariant']['image']['id'])){
											$ImgIdForDelete = $var_img_data['data']['productVariant']['image']['id'];
											$mutationData = 'mutation productDeleteImages($id: ID!, $imageIds: [ID!]!) {
												  productDeleteImages(id: $id, imageIds: $imageIds) {
													deletedImageIds
													product {
													  id
													}
													userErrors {
													  field
													  message
													}
												  }
												}';
											$inputData = '{
												  "id": "gid://shopify/Product/'.$single_var['shop_product_id'].'",
												  "imageIds": ["'.$ImgIdForDelete.'"]
												}';
											sleep(0.5);
											$graphql->runByMutation($mutationData,$inputData);
										}

										//assign new image in variant
										$mutationData = 'mutation productVariantUpdate($input: ProductVariantInput!) {
											  productVariantUpdate(input: $input) {
												productVariant {
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
												"id": "gid://shopify/ProductVariant/'.$single_var['shop_variant_id'].'",
												"imageId": "'.$newImgId.'"
											  }
											}';
										sleep(0.5);
										$graphql->runByMutation($mutationData,$inputData);

									}
								}
							}

						}
					}

					if(!empty($image_for_flyer)){
						$sof_update_data = [
							'selected_image_path' => $image_for_flyer
						];
						parent::updateTable_f_mdl('store_owner_flyer',$sof_update_data,'store_master_id="'.$_POST['store_master_id'].'"');
					}

					$_SESSION['SUCCESS'] = 'TRUE';
					$_SESSION['MESSAGE'] = 'Logo applied successfully.';
				}else{
					$_SESSION['SUCCESS'] = 'TRUE';
					$_SESSION['MESSAGE'] = 'Products not found. Please try again.';
				}
			}else{
				$_SESSION['SUCCESS'] = 'TRUE';
				$_SESSION['MESSAGE'] = 'Logo not found. Please try again.';
			}
			header('location:sa-store-view.php?stkn='.$_POST['stkn'].'&id='.$_POST['store_master_id'].'&tab=products');
		}else{
			header('location:sa-stores.php?stkn='.$_POST['stkn']);
		}
	}
	public function edit_store_new_logo_post(){
		if(isset($_POST['store_master_id']) && !empty($_POST['store_master_id'])){
			$upload_dir = 'image_uploads/';
			if(isset($_FILES['new_logo_file']['name']) && !empty($_FILES['new_logo_file']['name']) && empty($_FILES['new_logo_file']['error'])){

				$file_arr = explode('.',$_FILES['new_logo_file']['name']);
				$ext = array_pop($file_arr);
				$file_name = time().rand(100000,999999).'.'.$ext;
				if(move_uploaded_file($_FILES['new_logo_file']['tmp_name'], $upload_dir.$file_name)){
					$sdlm_insert_data = [
						'store_master_id' => $_POST['store_master_id'],
						'logo_image' => $file_name,
						'status' => '1',
						'created_on' => @date('Y-m-d H:i:s'),
						'created_on_ts' => time()
					];
					parent::insertTable_f_mdl('store_design_logo_master',$sdlm_insert_data);

					$_SESSION['SUCCESS'] = 'TRUE';
					$_SESSION['MESSAGE'] = 'Logo uploaded successfully.';
				}
			}
			header('location:sa-store-view.php?stkn='.$_POST['stkn'].'&id='.$_POST['store_master_id'].'&tab=logos');
		}else{
			header('location:sa-stores.php?stkn='.$_POST['stkn']);
		}
	}
	public function store_logo_make_default_post(){
		if(isset($_POST['logo_master_id']) && !empty($_POST['logo_master_id'])){
			$sql = 'SELECT id
			FROM store_design_logo_master
			WHERE id = "'.$_POST['logo_master_id'].'"
			AND store_master_id = "'.$_POST['store_master_id'].'"
			';
			$logo_exist = parent::selectTable_f_mdl($sql);

			if(!empty($logo_exist)){
				//make all 0(remove all from default) for same store
				$sdlm_update_data = [
					'is_default' => '0'
				];
				parent::updateTable_f_mdl('store_design_logo_master',$sdlm_update_data,'store_master_id="'.$_POST['store_master_id'].'"');

				//make current logo as default
				$sdlm_update_data = [
					'is_default' => '1'
				];
				parent::updateTable_f_mdl('store_design_logo_master',$sdlm_update_data,'id="'.$_POST['logo_master_id'].'"');

				$res['SUCCESS'] = 'TRUE';
				$res['MESSAGE'] = 'Logo set as default successfully.';
				$res['REDIRECT_URL'] = 'sa-store-view.php?stkn='.$_POST['stkn'].'&id='.$_POST['store_master_id'].'&tab=logos';
			}else{
				$res['SUCCESS'] = 'FALSE';
				$res['MESSAGE'] = 'Invalid request';
			}
		}else{
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'Invalid request';
		}
		echo json_encode($res,1);
	}
	public function store_logo_delete_post(){
		if(isset($_POST['logo_master_id']) && !empty($_POST['logo_master_id'])){
			$sql = 'SELECT id
			FROM store_design_logo_master
			WHERE id = "'.$_POST['logo_master_id'].'"
			AND store_master_id = "'.$_POST['store_master_id'].'"
			';
			$logo_exist = parent::selectTable_f_mdl($sql);

			if(!empty($logo_exist)){
				parent::deleteTable_f_mdl('store_design_logo_master','id="'.$_POST['logo_master_id'].'"');

				$res['SUCCESS'] = 'TRUE';
				$res['MESSAGE'] = 'Logo deleted successfully.';
			}else{
				$res['SUCCESS'] = 'FALSE';
				$res['MESSAGE'] = 'Invalid request';
			}
		}else{
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'Invalid request';
		}
		echo json_encode($res,1);
	}

	public function edit_store_manager_post(){
		if(isset($_POST['store_master_id']) && !empty($_POST['store_master_id'])){
			//check same email is already existed or not
			$sql = 'SELECT id FROM `store_owner_manager_master`
			WHERE email = "'.$_POST['somm_email'].'"
			AND id!="'.$_POST['somm_id'].'"
			';
			$same_email_exist = parent::selectTable_f_mdl($sql);
			if(!empty($same_email_exist)){
				$_SESSION['SUCCESS'] = 'FALSE';
				$_SESSION['MESSAGE'] = 'Email is already existed in our system. Please try with other one.';
			}else{
				//check same email is already existed or not on owner table
				$sql = 'SELECT id FROM `store_owner_details_master`
				WHERE email = "'.$_POST['somm_email'].'"
				';
				$same_email_exist = parent::selectTable_f_mdl($sql);
				if(!empty($same_email_exist)){
					$_SESSION['SUCCESS'] = 'FALSE';
					$_SESSION['MESSAGE'] = 'Email is already existed in our system. Please try with other one.';
				}else{
					//now we check that is user exist then update data
					$sql = 'SELECT id FROM `store_owner_manager_master` WHERE id="'.$_POST['somm_id'].'" AND store_master_id="'.$_POST['store_master_id'].'"';
					$user_data = parent::selectTable_f_mdl($sql);
					if(!empty($user_data)){
						$somm_update_data = [
							'first_name' => trim($_POST['somm_first_name']),
							'last_name' => trim($_POST['somm_last_name']),
							'email' => trim($_POST['somm_email']),
							'mobile' => trim($_POST['somm_mobile'])
						];
						parent::updateTable_f_mdl('store_owner_manager_master',$somm_update_data,'id="'.$_POST['somm_id'].'"');
						$_SESSION['SUCCESS'] = 'TRUE';
						$_SESSION['MESSAGE'] = 'Manager details updated successfully.';
					}else{
						$somm_insert_data = [
							'store_master_id' => trim($_POST['store_master_id']),
							'first_name' => trim($_POST['somm_first_name']),
							'last_name' => trim($_POST['somm_last_name']),
							'email' => trim($_POST['somm_email']),
							'password' => '',
							'mobile' => trim($_POST['somm_mobile']),
							'status' => '1',
							'created_on' => @date('Y-m-d H:i:s'),
							'created_on_ts' => time()
						];
						$somm_arr = parent::insertTable_f_mdl('store_owner_manager_master',$somm_insert_data);
						if(isset($somm_arr['insert_id'])){
							$this->send_invitation_link_email($somm_arr['insert_id']);
						}

						$_SESSION['SUCCESS'] = 'TRUE';
						$_SESSION['MESSAGE'] = 'Manager details created successfully. Please set permission for access modules.';
					}
				}
			}
			header('location:sa-store-view.php?stkn='.$_POST['stkn'].'&id='.$_POST['store_master_id'].'&tab=store_managers');
		}else{
			header('location:sa-stores.php?stkn='.$_POST['stkn']);
		}
	}
	public function send_invitation_email_to_store_manager(){
		$mail_sent = $this->send_invitation_link_email($_POST['somm_id']);
		if($mail_sent){
			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = 'Mail sent successfully';
		}else{
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'Mail is not sent. Please try again.';
		}
		echo json_encode($res,1);
	}
	public function send_invitation_link_email($somm_id){
		$sql = 'SELECT * FROM `store_owner_manager_master`
		WHERE id="'.$somm_id.'"
		';
		$somm_data = parent::selectTable_f_mdl($sql);
		if(!empty($somm_data)){
			$sql = 'SELECT subject,body FROM `email_templates_master` WHERE id='.common::STORE_MANAGER_ACCOUNT_ACTIVATION_EMAIL;
			$et_data = parent::selectTable_f_mdl($sql);
			if(!empty($et_data)){
				require_once("lib/class_aws.php");
				$objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);

				$subject = $et_data[0]['subject'];
				$body = $et_data[0]['body'];
				$to_email = $somm_data[0]['email'];
				$attachment = [];
				$from_email = common::AWS_ADMIN_EMAIL;

				$token_json = '{"id":"'.$somm_data[0]['id'].'","store_master_id":"'.$somm_data[0]['store_master_id'].'","email":"'.$somm_data[0]['email'].'"}';
				$token = base64_encode($token_json);
				$u = 'https://'.$_SERVER['HTTP_HOST'].'/store-owners/account_activation.php?for=store_manager&email='.$somm_data[0]['email'].'&token='.$token;

				$body = str_replace('{{CUSTOMER_NAME}}',$somm_data[0]['first_name'],$body);
				$body = str_replace('{{ACTIVATION_LINK}}',$u,$body);

				//$mailSendStatus = $objAWS::sendEmail($from_email, $to_email, $subject, $body, $attachment);

				$sql = 'SELECT * FROM store_master WHERE id="'.$somm_data[0]['store_master_id'].'"';
				$store_data = parent::selectTable_f_mdl($sql);

				$mailSendStatus = 1;
				//if($store_data[0]['email_notification'] == '1'){
					$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body);
				//}

				return $mailSendStatus;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
	public function edit_store_manager_permission_post(){
		if(isset($_POST['store_master_id']) && !empty($_POST['store_master_id'])){
			$sql = 'SELECT id FROM `store_owner_manager_master` WHERE id="'.$_POST['sommmp_id'].'" AND store_master_id="'.$_POST['store_master_id'].'"';
			$manager_exist = parent::selectTable_f_mdl($sql);
			if(!empty($manager_exist)){
				$somm_update_data = [
					'module_permission' => trim($_POST['sommmp_module_permission'])
				];
				parent::updateTable_f_mdl('store_owner_manager_master',$somm_update_data,'id="'.$_POST['sommmp_id'].'"');
				$_SESSION['SUCCESS'] = 'TRUE';
				$_SESSION['MESSAGE'] = 'Manager permission updated successfully.';
			}else{
				$_SESSION['SUCCESS'] = 'TRUE';
				$_SESSION['MESSAGE'] = 'Invalid request.';
			}
			header('location:sa-store-view.php?stkn='.$_POST['stkn'].'&id='.$_POST['store_master_id'].'&tab=store_managers');
		}else{
			header('location:sa-stores.php?stkn='.$_POST['stkn']);
		}
	}
	public function edit_store_sort_list_post(){
		if(isset($_POST['store_master_id']) && !empty($_POST['store_master_id'])){
			//first we delete old records
			parent::deleteTable_f_mdl('store_sort_list_master','store_master_id="'.$_POST['store_master_id'].'"');

			if(isset($_POST['sort_list_json']) && !empty($_POST['sort_list_json']) && $_POST['sort_list_json']!='[]' && $_POST['sort_list_json']!='{}'){
				$sort_list_arr = json_decode($_POST['sort_list_json'],1);
				if(!empty($sort_list_arr)){
					$sql = 'SELECT store_owner_details_master_id FROM `store_master` WHERE id = "'.$_POST['store_master_id'].'" ';
					$store_master_data = parent::selectTable_f_mdl($sql);

					$index = 1;
					foreach($sort_list_arr as $single_sl){
						if($single_sl['name']!=''){
							$sslm_insert_data = [
								'store_owner_details_master_id' => @$store_master_data[0]['store_owner_details_master_id'],
								'store_master_id' => $_POST['store_master_id'],
								'sort_list_name' => str_replace('"', "'", $single_sl['name']),
								'sort_list_index' => $index,
								'status' => '1',
								'created_on' => @date('Y-m-d H:i:s'),
								'created_on_ts' => time(),
							];
							parent::insertTable_f_mdl('store_sort_list_master',$sslm_insert_data);
							$index++;
						}
					}
				}
			}

			$_SESSION['SUCCESS'] = 'TRUE';
			$_SESSION['MESSAGE'] = 'Store sort list updated successfully.';
			header('location:sa-store-view.php?stkn='.$_POST['stkn'].'&id='.$_POST['store_master_id'].'&tab=sort_list');
		}else{
			header('location:sa-stores.php?stkn='.$_POST['stkn']);
		}
	}
	public function edit_store_owner_address_post(){
		if(isset($_POST['store_master_id']) && !empty($_POST['store_master_id']) && isset($_POST['oa_id']) && !empty($_POST['oa_id']) ){
			$soam_update_data = [
				'first_name' => trim($_POST['oa_first_name']),
				'last_name' => trim($_POST['oa_last_name']),
				'check_payable_to_name' => trim($_POST['oa_check_payable_to_name']),
				'address_line_1' => trim($_POST['oa_address_line_1']),
				'address_line_2' => trim($_POST['oa_address_line_2']),
				'country' => trim($_POST['oa_country']),
				'city' => trim($_POST['oa_city']),
				'state' => trim($_POST['oa_state']),
				'zip_code' => trim($_POST['oa_zip_code'])
			];
			parent::updateTable_f_mdl('store_owner_address_master',$soam_update_data,'id="'.$_POST['oa_id'].'"');

			$_SESSION['SUCCESS'] = 'TRUE';
			$_SESSION['MESSAGE'] = 'Payment Info updated successfully.';
			header('location:sa-store-view.php?stkn='.$_POST['stkn'].'&id='.$_POST['store_master_id'].'&tab=owner_address');
		}else{
			header('location:sa-stores.php?stkn='.$_POST['stkn']);
		}
	}
	public function edit_store_owner_silver_delivery_address_post(){
		if(isset($_POST['store_master_id']) && !empty($_POST['store_master_id']) && isset($_POST['osda_id']) && !empty($_POST['osda_id']) ){
			$sosdam_update_data = [
				'first_name' => trim($_POST['osda_first_name']),
				'last_name' => trim($_POST['osda_last_name']),
				'company_name' => trim($_POST['osda_company_name']),
				'address_line_1' => trim($_POST['osda_address_line_1']),
				'address_line_2' => trim($_POST['osda_address_line_2']),
				'country' => trim($_POST['osda_country']),
				'city' => trim($_POST['osda_city']),
				'state' => trim($_POST['osda_state']),
				'zip_code' => trim($_POST['osda_zip_code']),
			];
			parent::updateTable_f_mdl('store_owner_silver_delivery_address_master',$sosdam_update_data,'id="'.$_POST['osda_id'].'"');

			$_SESSION['SUCCESS'] = 'TRUE';
			$_SESSION['MESSAGE'] = 'Delivery address details updated successfully.';
			header('location:sa-store-view.php?stkn='.$_POST['stkn'].'&id='.$_POST['store_master_id'].'&tab=delivery_address');
		}else{
			header('location:sa-stores.php?stkn='.$_POST['stkn']);
		}
	}
	public function edit_st_store_owner_silver_delivery_address_post(){
		if(isset($_POST['st_store_master_id']) && !empty($_POST['st_store_master_id']) && isset($_POST['st_osda_id']) && !empty($_POST['st_osda_id']) ){
			$sosdam_update_data = [
				'is_ship_to_address_added' => trim($_POST['is_ship_to_address_added']),
				'st_first_name' => trim($_POST['st_osda_first_name']),
				'st_last_name' => trim($_POST['st_osda_last_name']),
				'st_company_name' => trim($_POST['st_osda_company_name']),
				'st_address_line_1' => trim($_POST['st_osda_address_line_1']),
				'st_address_line_2' => trim($_POST['st_osda_address_line_2']),
				'st_country' => trim($_POST['st_osda_country']),
				'st_city' => trim($_POST['st_osda_city']),
				'st_state' => trim($_POST['st_osda_state']),
				'st_zip_code' => trim($_POST['st_osda_zip_code']),
			];
			parent::updateTable_f_mdl('store_owner_silver_delivery_address_master',$sosdam_update_data,'id="'.$_POST['st_osda_id'].'"');

			$_SESSION['SUCCESS'] = 'TRUE';
			$_SESSION['MESSAGE'] = 'Ship to address details updated successfully.';
			header('location:sa-store-view.php?stkn='.$_POST['stkn'].'&id='.$_POST['id'].'&tab=delivery_address');
		}else{
			header('location:sa-stores.php?stkn='.$_POST['stkn']);
		}
	}
    public function edit_store_setting_post(){
		if(isset($_POST['store_master_id']) && !empty($_POST['store_master_id'])){
			$sql = 'SELECT id, shop_collection_id, store_sale_type_master_id,store_fulfillment_type FROM store_master WHERE id="'.$_POST['store_master_id'].'"';
			$store_data = parent::selectTable_f_mdl($sql);
			if(!empty($store_data)){
				$old_sale_type = $store_data[0]['store_sale_type_master_id'];
				$new_sale_type = trim($_POST['store_sale_type_master_id']);
				
				if(isset($_POST['store_status']) && !empty($_POST['store_status'])){
					$status = '0';	//0 means store closed
					$store_closed = 'Yes';
					$store_description = common::DESCRIPTION_FOR_CLOSED_STORE;
				}else{
					$status = '1';
					$store_closed = 'No';
					if($new_sale_type=='1'){
						if(isset($store_data[0]['store_fulfillment_type']) && $store_data[0]['store_fulfillment_type']=='SHIP_1_LOCATION_NOT_SORT'){
							$store_description = COMMON::DESCRIPTION_FOR_OPEN_FLASH_STORE;
						}else if(isset($store_data[0]['store_fulfillment_type']) && $store_data[0]['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
							$store_description = COMMON::DEFAULT_DESCRIPTION_FOR_SILVER_GOLD_FULFILLMENT;
						}else if(isset($store_data[0]['store_fulfillment_type']) && $store_data[0]['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
							$store_description = COMMON::DEFAULT_DESCRIPTION_FOR_PLATINUM_FULFILLMENT;
						}
					}else{
						$store_description = common::DESCRIPTION_FOR_OPEN_ONDEMAND_STORE;
					}
				}

				if(isset($_POST['email_notification']) && !empty($_POST['email_notification'])){
					$email_notification = '1';
				}else{
					$email_notification = '0';
				}

				$sm_update_data = [
					'store_sale_type_master_id' => $new_sale_type,
					'status' => $status,
					'store_description' => $store_description,
					'email_notification' => $email_notification,
				];
				parent::updateTable_f_mdl('store_master',$sm_update_data,'id="'.$_POST['store_master_id'].'"');

				if(isset($store_data[0]['shop_collection_id']) && !empty($store_data[0]['shop_collection_id'])){
					//if we have collection-id, then set meta in store(collection) in shopify
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

						//update description in shopify
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
							"descriptionHtml":"'.trim($store_description).'"
						  }
						}';
						$graphql->runByMutation($mutationData,$inputData);

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

						if($store_closed=='Yes'){
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
							}
						}else{
							//if meta exist, then delete meta for store-close
							if(isset($collection_meta_id_for_close) && !empty($collection_meta_id_for_close)){
								$mutationData = 'mutation metafieldDelete($input: MetafieldDeleteInput!) {
								  metafieldDelete(input: $input) {
									userErrors {
									  field
									  message
									}
								  }
								}';
								$inputData = '{
								  "input": {
									"id": "'.$collection_meta_id_for_close.'"
								  }
								}';
								$graphql->runByMutation($mutationData,$inputData);
							}
						}
					}

					if($old_sale_type!=$new_sale_type){
						
						#region - Update Price Pending Status = 1
						parent::updatePricePendingStatus_f_mdl($_POST['store_master_id']);
						#endregion
					}
				}

				if($store_closed=='No'){
					$production_status = [
						'production_status_id' => '0',
						'is_store_batched' => '0'
					];
					parent::updateTable_f_mdl('store_master',$production_status,'id="'.$_POST['store_master_id'].'"');
				}

				$_SESSION['SUCCESS'] = 'TRUE';
				if($old_sale_type!=$new_sale_type){
					$_SESSION['MESSAGE'] = 'Store settings have been updated successfully. Please all wait up to 30-minutes for the changes to take effect.';// Task 53 11/10/2021 change the message
				}else{
					$_SESSION['MESSAGE'] = 'Setting updated successfully.';
				}
				
				header('location:sa-store-view.php?stkn='.$_POST['stkn'].'&id='.$_POST['store_master_id'].'&tab=store_setting');
			}else{
				header('location:sa-stores.php?stkn='.$_POST['stkn']);
			}
        }else{
			header('location:sa-stores.php?stkn='.$_POST['stkn']);
        }
    }
	# end-store-section
	/* Task 24 start */
	public function edit_store_fundraising(){
		if(isset($_POST['store_master_id']) && !empty($_POST['store_master_id'])){

			//check same email is already existed or not
			
			//now we check that is user exist then update data
			$sql = 'SELECT id FROM `store_master` WHERE id="'.$_POST['store_master_id'].'" ';
			$user_data = parent::selectTable_f_mdl($sql);

			//print_r($user_data); die;

			if(!empty($user_data)){
				$fund_update_data = [
					'enable_fundraising' => trim($_POST['enable_fundraising']),
					'fundraising_amount' => trim($_POST['fundraising_amount'])
				];
				parent::updateTable_f_mdl('store_master', $fund_update_data, 'id="'.$_POST['store_master_id'].'" ');
				$_SESSION['SUCCESS'] = 'TRUE';
				$_SESSION['MESSAGE'] = 'Fundraising details updated successfully.';
			}

			header('location:sa-store-view.php?stkn='.$_POST['stkn'].'&id='.$_POST['store_master_id'].'&tab=fundraising');
		}else{
			header('location:sa-stores.php');
		}
	}
	/* Task 24 end */
	public function getColorFilterDropdown(){
		$sql = 'SELECT 
				  store_owner_product_variant_master.id,
				  store_owner_product_variant_master.color,
				  store_product_colors_master.product_color_name

				FROM `store_owner_product_variant_master`

				INNER JOIN store_product_colors_master
				ON store_owner_product_variant_master.color= store_product_colors_master.product_color

				WHERE store_owner_product_variant_master.store_owner_product_master_id = "'.$_POST['product_id'].'" 
				GROUP BY store_owner_product_variant_master.color';

		$colorInfo = parent::selectTable_f_mdl($sql);

		$divHtml = '<option value="">Change All Variants</option>';
		if(isset($colorInfo) && !empty($colorInfo)){
			foreach ($colorInfo as $value) {
				$divHtml .= '<option value="'.$value['color'].'">Change '.$value['product_color_name'].' Variants</option>';
			}
		}

		$res['divHtml'] = $divHtml;

		echo json_encode($res,1);
	}

	public function getStoreBasiDetail($store_master_id){
		$sql1="
				SELECT sm.ct_fundraising_price, sm.id, sm.store_organization_type_master_id,org.organization_name as organization_type_name, sm.shop_collection_handle,sm.is_store_batched,sm.front_side_ink_colors, sm.back_side_ink_colors,sm.store_name,sm.store_open_date,sm.is_fundraising,sm.store_fulfillment_type,sm.store_close_date,sm.verification_status,sm.notes,sstm.sale_type,sodm.first_name,sodm.last_name,sodm.email,sodm.phone,sodm.organization_name,sm.status FROM store_master sm INNER JOIN store_owner_details_master sodm ON sm.store_owner_details_master_id = sodm.id INNER JOIN store_sale_type_master sstm ON sm.store_sale_type_master_id = sstm.id 
				LEFT JOIN store_organization_type_master as org ON sm.store_organization_type_master_id = org.id WHERE sm.id = ".$store_master_id."
			";
			
		$all_list = parent::selectTable_f_mdl($sql1);

		return $all_list;
	}

	//Task 36 17-09-21 Task 53
	public function getGeneralSettingDetails(){
		$sql = 'SELECT fullfilment_type_second,fullfilment_type_third,is_enable_in_home,is_enable_in_bagged  FROM general_settings_master WHERE id = 1 LIMIT 1';
		return parent::selectTable_f_mdl($sql);
	}
	//End Task 36

	//Task 43 25-09-21
	public function checkIndividualProductFundrising($store_master_id,$globle_fundraising_price){
		$sql = 'SELECT * FROM `store_owner_product_master` WHERE `store_master_id` = "'.$store_master_id.'" AND `is_individual` = "Yes"';
		return parent::selectTable_f_mdl($sql);
	}
	//End Task 43

	// Task 51 start 
	// this function use for fixed the price
	public function updatePrice()
	{
		$arraRes = array();
		$ipx = 0;
		$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
		if(!empty($shop_data)) {
			require_once('lib/class_graphql.php');

			$shop = $shop_data[0]['shop_name'];
			$token = $shop_data[0]['token'];

			$headers = array(
				'X-Shopify-Access-Token' => $token
			);
			$graphql = new Graphql($shop, $headers);
		}

		if (isset($_POST['store_master_id'])) {
			$sql = 'SELECT id,front_side_ink_colors,back_side_ink_colors,store_fulfillment_type,is_fundraising,ct_fundraising_price,is_bulk_pricing,store_fulfillment_type,store_organization_type_master_id,store_sale_type_master_id FROM `store_master` WHERE id="'.$_POST['store_master_id'].'"';
					$storeDetails = parent::selectTable_f_mdl($sql);
			foreach ($storeDetails as $row) 
			{
				if($row['id'])
				{
					/*
					* Front-side and back-side price only added with on-demand store
					* Add front-side as per color catridge price into base price
					*/ 
					$add_cost = 0;
					if(isset($row['front_side_ink_colors']) && !empty($row['front_side_ink_colors'])){
						$add_cost += intval($row['front_side_ink_colors'])-1;
					}

					//Add back-side as per color catridge price into base price
					$add_on_cost = 0;// Task 50 Add new variable for on_demand
					if(isset($row['back_side_ink_colors']) && !empty($row['back_side_ink_colors'])){
						$add_cost   += common::ADD_COST_BACK_SIDE_INK_COLOR+intval($row['back_side_ink_colors'])-1;
						$add_on_cost = common::ADD_COST_BACK_SIDE_INK_COLOR;// Task 50 Add new variable for on_demand
					}
			
					/*
					* ADD_COST_STORE_FULFILLMENT_TYPE_2 means add $2 in base price on flash sale case
					* ADD_COST_STORE_FULFILLMENT_TYPE_3 means add $6 in base price on flash sale case
					*/
					$fullfilment_type_price = 0;
					if(isset($row['store_fulfillment_type']) && $row['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
						$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_2;
					}
					else if(isset($row['store_fulfillment_type']) && $row['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
						$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_3;
					}

					$sql = 'SELECT id,store_product_master_id from store_owner_product_master where store_master_id="'.$row['id'].'"';
					$storeOwnerProductsMaster = parent::selectTable_f_mdl($sql);
					foreach ($storeOwnerProductsMaster as $row2) 
					{
						if($row2['id'])
						{

							$sql = 'SELECT id,store_owner_product_master_id,store_product_variant_master_id,price,price_on_demand,store_organization_type_master_id,shop_product_id,shop_variant_id,fundraising_price from store_owner_product_variant_master where store_owner_product_master_id="'.$row2['id'].'" AND store_organization_type_master_id = "'.$row['store_organization_type_master_id'].'"';// Task 50 Add 1 new column store_organization_type_master_id and add condition for this						
							$storeOwnerProductsVarientMaster = parent::selectTable_f_mdl($sql);
							foreach ($storeOwnerProductsVarientMaster as $single_var) 
							{
								if($single_var['store_product_variant_master_id'])
								{
									$sql = 'SELECT price,price_on_demand from store_product_variant_master where id="'.$single_var['store_product_variant_master_id'].'"';
									$storeProductVariantMaster = parent::selectTable_f_mdl($sql);

									//To do add bussiness login for fullfilmemnt type & fundrising
									if(isset($storeProductVariantMaster[0]['price']) && $storeProductVariantMaster[0]['price_on_demand'])
									{	
										$flashSalePrice = (floatval($storeProductVariantMaster[0]['price'])+$add_cost+$fullfilment_type_price);//Task 50 Add new variable flashSalePrice
										$ondemandPrice  = (floatval($storeProductVariantMaster[0]['price_on_demand'])+$add_on_cost);// Task 50 Add new variable for on_demand
									}
									else
									{
										$flashSalePrice = $single_var['price'];//Task 50 Add new variable flashSalePrice
										$ondemandPrice  = $single_var['price_on_demand'];
									}
									// Task 42 end

									$updateStoreOwnerProductVariant = [
										'price'           => $flashSalePrice,
										'price_on_demand' => $ondemandPrice,// Task 50 Add new variable for on_demand
									];

									parent::updateTable_f_mdl('store_owner_product_variant_master',$updateStoreOwnerProductVariant,'id="'.$single_var['id'].'"');
									//Update data into store_owner_product_variant_master


									if(isset($single_var['shop_product_id']) && !empty($single_var['shop_product_id']) &&
										isset($single_var['shop_variant_id']) && !empty($single_var['shop_variant_id']) &&
										isset($graphql)
									){
										sleep(0.5);

									if(isset($row['store_sale_type_master_id']) && $row['store_sale_type_master_id']==2){
											$input_price = $ondemandPrice+$single_var['fundraising_price'];
										}else{
											
											$input_price = $flashSalePrice+$single_var['fundraising_price'];
										}

										$mutationData = 'mutation productVariantUpdate($input: ProductVariantInput!) {
												  productVariantUpdate(input: $input) {
													productVariant {
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
													"id":"gid://shopify/ProductVariant/'.$single_var['shop_variant_id'].'",
													"price":"'.( $input_price ).'"
												  }
												}';
										$graphql->runByMutation($mutationData,$inputData);

										$arraRes[$ipx]['price'] = $input_price;
										$arraRes[$ipx]['shop_variant_id'] = $single_var['shop_variant_id'];
										$ipx++;

									}
								}
							}
						}
					}
				}
			}
			echo 1;
		}
		else
		{
			echo 0;
		}
	}

	/*
	* Task 49
	*/
	public function isDeletedProductFromMaster($store_master_id){
		$sql = 'SELECT store_master_id,store_product_master_id,product_title  FROM `store_owner_product_master` WHERE `store_master_id` = "'.$store_master_id.'"';
		$products = parent::selectTable_f_mdl($sql);
		$deleted_product = array();
		if(!empty($products)){
			$i=0;
			foreach($products as $single_product){

				$sql_store_product_master = 'SELECT id,product_title FROM `store_product_master` WHERE id = "'.$single_product['store_product_master_id'].'" ';
				$is_master_product = parent::selectTable_f_mdl($sql_store_product_master);
				if(count(@$is_master_product)==0) {
					$deleted_product[$i]['product_id'] = $single_product['store_product_master_id'];
					$deleted_product[$i]['product_title'] = $single_product['product_title'];
					$i++;
				}
			}
		}
		return $deleted_product;
	}  
}