<?php
include_once 'model/sa_store_view_mdl.php';
include_once('helpers/storeHelper.php');
ini_set('max_execution_time', '600');
include_once $path . 'libraries/Aws3.php';
$s3Obj = new Aws3;
$login_user_email="";
if(isset($_SESSION['user_email']) && $_SESSION['user_email'] != "") {
	$login_user_email=trim($_SESSION['user_email']);
}

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
			}else if($action=='size_filter_dropdown'){
				$this->getSizeFilterDropdown();exit;
			}else if($action=='save_all_variants_by_product_post'){
				$this->save_all_variants_by_product_post();exit;
			}else if($action=='save_all_variants_images_post'){
				$this->save_all_variants_images_post();exit;
			}else if($action=='store_variant_delete_post'){
				$this->store_variant_delete_post();exit;
			}else if($action=='store_product_delete_post'){
				$this->store_product_delete_post();exit;
			}else if($action=='product_soft_delete'){
				$this->product_soft_delete();exit;
			}else if($action=='edit_store_apply_logo'){
				$this->edit_store_apply_logo();exit;
			}else if($action=='edit_store_new_logo_post'){
				$this->edit_store_new_logo_post();exit;
			}else if($action=='store_logo_make_default_post'){
				$this->store_logo_make_default_post();exit;
			}else if($action=='store_logo_delete_post'){
				$this->store_logo_delete_post();exit;
			}else if($action=='send_invitation_email_to_store_manager'){
				$this->send_invitation_email_to_store_manager();exit;
			}else if($action=='edit_store_sort_list_post'){
				$this->edit_store_sort_list_post();exit;
			}else if($action=='edit_store_owner_address_post'){
				$this->edit_store_owner_address_post();exit;
			}else if($action=='edit_store_owner_silver_delivery_address_post'){
				$this->edit_store_owner_silver_delivery_address_post();exit;
			}else if($action=='edit_store_setting_post'){
				$this->edit_store_setting_post();exit;
			}else if($action=='edit_store_fundraising'){
				$this->edit_store_fundraising();exit;
			}else if($action=='save_store_level_settings'){
				$this->save_store_level_settings();exit;
			}else if($action=='only_fundrising_save_store_level_settings'){
				$this->only_fundrising_save_store_level_settings();exit;
			}else if($action=='saveproductImag'){
				$this->saveproductImag();exit;
			}else if($action=='updatePrice'){
				$this->updatePrice();exit;
			}else if($action=='getImageUrlFromS3'){
				$this->getImageUrlFromS3();exit;
			}else if ($action == 'getProductAdditionalColor') {
				$this->getProductAdditionalColor();
				exit;
			}else if ($action == 'update_product_color') {
				$this->update_product_color();
				exit;
			}else if ($action == 'change_group_price') {
				$this->change_group_price();
				exit;
			}else if ($action == 'getChangeGroupPriceHistory') {
				$this->getChangeGroupPriceHistory();
				exit;
			}else if($action=='addNotes'){
				$this->addNotes();exit;
			}else if($action=='delete_ship_to_address'){ 
				$this->delete_ship_to_address();exit;
			}else if($action=='create_duplicate_product'){ /* Task 92 add new action create_duplicate_product*/
				$this->create_duplicate_product();exit;
			}else if($action=='getProductColor'){ /* Task 92 add new action create_duplicate_product*/
				$this->getProductColor();exit;
			}else if($action=='getProductColorByGroup'){ /* Task 92 add new action create_duplicate_product*/
				$this->getProductColorByGroup();exit;
			}else if($action=='getVariantColor'){ /* Task 92 add new action create_duplicate_product*/
				$this->getVariantColor();exit;
			}else if($action=='updateProfile'){
				$this->updateProfile();exit;
			}else if($action=='showProfitReport'){
				$this->showProfitReport();exit;
			}else if($action=='get_product_byGroup'){
				$this->get_product_byGroup();exit;
			}else if($action=='getPriceData'){
				$this->getPriceData();exit;
			}else if($action=='getSizeData'){
				$this->getSizeData();exit;
			}else if($action=='addToCart'){
				$this->addToCart();exit;
			}else if($action=='getAddToCart'){
				$this->getAddToCart();exit;
			}else if($action=='deleteProductCart'){
				$this->deleteProductCart();exit;
			}else if($action=='updateToCart'){
				$this->updateToCart();exit;
			}else if($action=='sendEmailOrderData'){
				$this->sendEmailOrderData();exit;
			}else if($action=='checkSelectedGroupProduct'){
				$this->checkSelectedGroupProduct();exit;
			}else if($action=='update_lock_status'){
				$this->UpdateLockStatus();exit;
			}else if($action=='create_duplicate_group'){
				$this->create_duplicate_group();exit;
			}// Task 63 start Logo setting add new action start task
			else if($action=='save_logo_setting'){
				$this->save_logo_setting();exit;
			}else if($action=='save_global_setting'){
				$this->save_global_setting();exit;
			}else if($action=='save_global_setting_without_mockup'){
				$this->save_global_setting_without_mockup();exit;
			}else if($action=='logo_asigned'){
				$this->logo_asigned();exit;
			}else if($action=='save_single_product_setting'){
				$this->save_single_product_setting();exit;
			}else if($action=='getAssignedLogoDeatails'){
				$this->getAssignedLogoDeatails();exit;
			}else if($action=='check_logo_setting'){
				$this->check_logo_setting();exit;
			}else if($action=='getVariantImage'){
				$this->getVariantImage();exit;
			}else if($action=='saveSingleProduct'){
				$this->saveSingleProduct();exit;
			}else if($action=='mergeImages'){
				$this->mergeImages();exit;
			}else if($action=='uploadLogo'){ /* Task 105 add new action uploadLogo*/
				$this->uploadLogo();exit;
			}else if($action=='fetch-stkn'){ /* Task 105 add new action uploadLogo*/
				$this->fetchStoreTokenInfo();exit;
			}else if($action=='getCustomizationDetails'){ /* Task 105 add new action uploadLogo*/
				$this->getCustomizationDetails();exit;
			}else if($action=='get_all_product_byGroup'){ /*quickbuy task  */
				$this->get_all_product_byGroup();exit;
			}else if($action=='addToCartQuickBuy'){
				$this->addToCartQuickBuy();exit;
			}
			// Task 63 end Logo setting add new function start task
			else if($action=='saveOrUpdateRedirects'){ /* Task 105 add new action uploadLogo*/
				$this->saveOrUpdateRedirects();exit;
			}else if($action=='getStoreHandle'){
				$this->getStoreHandle();exit;
			}else if($action=='deleteRedirects'){
				$this->deleteRedirects();exit;
			}elseif($action=='changeStoreGroup') {
				$this->changeStoreGroup();exit;
			}else if($action=='update_total_profit'){
				$this->update_total_profit();exit;
			}else if($action=='follow_up_email'){
				$this->followUpEmail();exit;
			}else if($action=='approved_products_sa'){
				$this->approved_products_sa();exit;
			}else if($action=='store_product_sorting'){
				$this->store_product_sorting();exit;
			}else if($action=='store_product_history'){
				$this->store_product_history();exit;
			}elseif($action=='update_email_sa') {
				$this->updateEmailStoreOwner();exit;
			}elseif($action=='changeManagerToOwner') {
				$this->changeManagerToOwner();exit;
			}elseif($action=='get_product_based_on_type') {
				$this->get_product_based_on_type();exit;
			}else if($action=='store_product_deleteassignlogo'){
				$this->store_product_deleteassignlogo();exit;
			}else if($action=='store_bulk_product_assignLogo_delete_post'){
				$this->store_bulk_product_assignLogo_delete_post();exit;
			}elseif($action=='get_product_colors_logo') {
				$this->getProductColorsLogo();exit;
			}elseif($action=='delete_assigned_printfile') {
				$this->deleteAssignedPrintFile();exit;
			}elseif($action=='add_newtag_product_group') {
				$this->add_newtag_product_group();exit;
			}elseif($action=='update_tag_store_product') {
				$this->update_tag_store_product();exit;
			}elseif($action=='get_all_store_product_tag') {
				$this->get_all_store_product_tag();exit;
			}elseif($action=='check_add_product_name_identifier') {
				$this->check_add_product_name_identifier();exit;
			}elseif($action=='get_all_template_products_colors') {
				$this->get_all_template_products_colors();exit;
			}elseif($action=='add_template_product_store') {
				$this->add_template_product_store();exit;
			}else if($action=='update_persionalization_details_post'){
				$this->update_persionalization_details_post();exit;
			}else if ($action == 'getProductAdditionalColorGroup') {
				$this->getProductAdditionalColorGroup();
				exit;
			}else if ($action == 'update_product_color_group') {
				$this->update_product_color_group();
				exit;
			}else if($action=='add_identifier_group_products'){
				$this->add_identifier_group_products();exit;
			}else if($action=='check_product_prefix'){
                $this->check_product_prefix();exit;
            }else if($action=='save_personalization_label'){
				$this->save_personalization_label();exit;
			}else if ($action == 'change_group_ink_price') {
				$this->change_group_ink_price();
				exit;
			}else if ($action == 'getChangeGroupInkPriceHistory') {
				$this->getChangeGroupInkPriceHistory();
				exit;
			}else if ($action == 'getGroupInkCost') {
				$this->getGroupInkCost();
				exit;
			}


		}
		common::CheckLoginSession();
	}
	
	/**
	 * saveproductImag
	 * @return void
	 */
	public function saveproductImag(){
		storeHelper::saveProductImag($_POST,true);
	}
	
	/**
	 * getShopCountryList
	 * @return void
	 */
	public function getShopCountryList(){
		$reponse = storeHelper::getShopCountryList();
		return $reponse;
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
		/* Task 90 add new colum store_color_family_master.color_image in sql */
		$sql = 'SELECT color,store_product_colors_master.color_family,store_color_family_master.color_family_color,store_color_family_master.color_image FROM `store_owner_product_variant_master`
		
		LEFT JOIN `store_owner_product_master` ON `store_owner_product_master`.id = `store_owner_product_variant_master`.store_owner_product_master_id
		
		LEFT JOIN store_product_colors_master ON store_product_colors_master.product_color = store_owner_product_variant_master.color
		
		LEFT JOIN store_color_family_master ON store_color_family_master.color_family_name = store_product_colors_master.color_family
		
		WHERE `store_owner_product_master`.store_master_id = "'.$store_master_id.'" AND `store_owner_product_master`.is_soft_deleted ="0"
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
			$sql = 'SELECT * FROM `store_design_logo_master`
			WHERE store_master_id = "'.$store_master_id.'" AND is_deleted = "0"
			ORDER BY id DESC
			';
			$logo_data = parent::selectTable_f_mdl($sql);
			$list_data[0]['logo_data'] = $logo_data;

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

			//fetch prod fund price and status
			$sql = 'SELECT * FROM `store_owner_product_master`
			WHERE store_master_id = "'.$store_master_id.'"
			ORDER BY id DESC
			LIMIT 1
			';
			$store_prod_data = parent::selectTable_f_mdl($sql);
			$list_data[0]['store_prod_data'] = $store_prod_data;

			$assignLogosql = 'SELECT sopm.id,sopm.associate_with_logo_id,spm.vendor_id,svm.vendor_name FROM `store_owner_product_master` as sopm INNER JOIN store_product_master as spm ON spm.id=sopm.store_product_master_id INNER JOIN store_vendors_master as svm ON svm.id=spm.vendor_id
				WHERE sopm.store_master_id = "'.$store_master_id.'"  AND sopm.is_soft_deleted="0" AND (svm.vendor_name="CustomCat" OR svm.vendor_name="FulfillEngine") AND (sopm.associate_with_logo_id="" OR sopm.associate_with_logo_id is NULL)
				ORDER BY id DESC LIMIT 1
			';
			$store_assignlogo_data = parent::selectTable_f_mdl($assignLogosql);
			
			$list_data[0]['assign_logo_data'] = $store_assignlogo_data;

			return $list_data;
		}else{
			header('location:sa-stores.php?stkn='.$_POST['stkn']);
		}
	}
	
	/**
	 * edit_store_basic_post
	 * @return void
	 */
	public function edit_store_basic_post(){
		storeHelper::editStoreBasicPost($_POST,true);
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
		/* Task 90 add new colum store_color_family_master.color_image in sql */
		$sql = 'SELECT `store_product_variant_master`.color, store_product_colors_master.color_family,store_color_family_master.color_family_color,store_color_family_master.color_image
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
	
	/**
	 * fetch_main_product_list
	 * @return void
	 */
	public function fetch_main_product_list(){
		$reponse = storeHelper::fetchMainProductList($_POST,true);
		echo $reponse;
	}
	
	/**
	 * add_new_products
	 * @return void
	 */
	public function add_new_products(){
		storeHelper::addNewProducts($_POST,true);
	}
	
	/**
	 * add_option_sort_list
	 * @return void
	 */
	public function add_option_sort_list(){
		$reponse = storeHelper::addOptionSortList($_POST);
		echo $reponse;
	}
	
	/**
	 * get_product_groups
	 * @return void
	 */
	public function get_product_groups(){		
		$sqlG = "SELECT * FROM minimum_group_product WHERE is_deleted = '0' ORDER BY group_order ASC";/* Task 82 Add where condition is_deleted = 0*/
		return $productGroup =  parent::selectTable_f_mdl($sqlG);
	}

	/**
	 * get_product_groups_checked
	 * @return void
	 */
	public function get_all_product_group_checked(){		
		$sqlG = "SELECT * FROM minimum_group_product WHERE is_deleted = '0' AND is_checked='0' ORDER BY group_order ASC";/* Task 82 Add where condition is_deleted = 0*/
		return $productGroup =  parent::selectTable_f_mdl($sqlG);
	}


	/*in later move on utility helper*/
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
	
	/**
	 * get_all_products_by_store
	 * @return void
	 */
	public function get_all_products_by_store(){
		//$postData,$fromSuperAdmin,$condLoginUser
		$reponse = storeHelper::getAllProductsByStore($_POST,true,false);
		echo $reponse;
	}
	
	/**
	 * get_product_byGroup
	 * @return void
	 */
	public function get_product_byGroup(){
		//$postData,$fromSuperAdmin,$condLoginUser
		$reponse = storeHelper::getProductByGroup($_POST,true,false);
		echo $reponse;
	}

	/**
	 * store_product_details_post
	 * @return void
	 */
	public function store_product_details_post(){
		$reponse = storeHelper::storeProductDetailsPost($_POST);
		echo $reponse;
	}
	
	/**
	 * store_product_delete_post
	 * @return void
	 */
	public function store_product_delete_post(){
		$reponse = storeHelper::storeProductDeletePost($_POST,true,false);
		echo $reponse;
	}

	/**
	 * product_soft_delete
	 * @return void
	 */
	public function product_soft_delete(){
		if (isset($_POST['product_delete_id'])) {
			$softDeleteSql = 'SELECT shop_product_id FROM `store_owner_product_master` WHERE id="'.$_POST['product_delete_id'].'"';
			$softDeleteData =  parent::selectTable_f_mdl($softDeleteSql);
			if (!empty($softDeleteData)) {
				$shop_product_id = $softDeleteData[0]['shop_product_id'];
			}

			require_once('lib/class_graphql.php');

			if(!empty($shop_product_id)){
				$shop_data =parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
				$shop  = $shop_data[0]['shop_name'];
				$token = $shop_data[0]['token'];

				$headers = array(
					'X-Shopify-Access-Token' => $token
				);
				$graphql = new Graphql($shop, $headers);

				$mutationData = 'mutation productChangeStatus($productId: ID!, $status: ProductStatus!) {
				  productChangeStatus(productId: $productId, status: $status) {
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

				$inputData = '{
					"productId": "gid://shopify/Product/'.$shop_product_id.'",
					"status":"DRAFT"
				}';
				$graphql->runByMutation($mutationData,$inputData);
			}
			$sopm_update_data = [
				'is_soft_deleted' => 1
			];
			$res = parent::updateTable_f_mdl('store_owner_product_master',$sopm_update_data,'id="'.$_POST['product_delete_id'].'"');
			echo json_encode($res);
		}
		die();
	}
	
	/**
	 * get_all_variants_by_product
	 * @return void
	 */
	public function get_all_variants_by_product(){
		$reponse = storeHelper::getAllVariantsByProduct($_POST,true);
		echo $reponse;
	}
	
	/**
	 * save_all_variants_by_product_post
	 * @return void
	 */
	public function save_all_variants_by_product_post(){
		$reponse = storeHelper::saveAllVariantsByProductPost($_POST,true);
		echo $reponse;
	}

	public function save_all_variants_images_post(){
		$reponse = storeHelper::saveAllVariantsImagesPost($_POST,true);
		echo $reponse;
	}

	public function getImageUrlFromS3()
	{
		$reponse = storeHelper::getImageUrlFromS3($_POST);
		echo json_encode($reponse);die;
	}
		
	/**
	 * save_store_level_settings
	 * @return void
	 */
	public function save_store_level_settings(){
		$reponse = storeHelper::saveStoreLevelSettings($_POST,false);
		echo $reponse;
	}

	/*
	* Task 43 28-09-21
	* separately save fundrising
	*/
	public function only_fundrising_save_store_level_settings(){
		global $login_user_email;
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

		$sql = 'SELECT front_side_ink_colors, back_side_ink_colors,sleeve_ink_colors, store_fulfillment_type, is_fundraising, ct_fundraising_price FROM `store_master`
					WHERE id="'.$_POST['store_master_id'].'"
					';
		$store_master_data = parent::selectTable_f_mdl($sql);


		/*fundraising_price Update*/
		$fundraisingPrice = $_POST['ct_fundraising_price'];
		$ct_fundraising   = $_POST['ct_fundraising'];
		$_POST['is_fundraising'] = $ct_fundraising;

		if($ct_fundraising == 'Yes'){
			$sopvmStoreData = [
				'ct_fundraising_price' => $fundraisingPrice,
				'is_fundraising' => $ct_fundraising
			];
			$resPonseData = parent::updateTable_f_mdl('store_master',$sopvmStoreData,'id="'.$_POST['store_master_id'].'"');

			$storeListData = parent::selectTable_f_mdl("SELECT * FROM store_master WHERE id='".$_POST['store_master_id']."'");

			$storeMasterData = parent::selectTable_f_mdl("SELECT * FROM store_owner_product_master WHERE store_master_id = '".$_POST['store_master_id']."'");
			if($storeMasterData){
				foreach ($storeMasterData as $storeMasterProduct) {
					$idProduct = $storeMasterProduct['id'];
					$sopmUpdateData = [
						'is_product_fundraising' => $ct_fundraising
					];
					parent::updateTable_f_mdl('store_owner_product_master',$sopmUpdateData,'id="'.$idProduct.'"');
					$sopvmUpdateData = [
						'fundraising_price' => trim($fundraisingPrice),
					];
					parent::updateTable_f_mdl('store_owner_product_variant_master',$sopvmUpdateData,'store_owner_product_master_id="'.$idProduct.'"');
				}
			}
		}
		else{
			// Task 43 25-09-21
			$sopvmStoreData = [
				'ct_fundraising_price' => $fundraisingPrice,
				'is_fundraising' => $ct_fundraising
			];

			$resPonseData = parent::updateTable_f_mdl('store_master',$sopvmStoreData,'id="'.$_POST['store_master_id'].'"');

			$storeListData = parent::selectTable_f_mdl("SELECT * FROM store_master WHERE id='".$_POST['store_master_id']."'");
			$storeMasterData = parent::selectTable_f_mdl("SELECT * FROM store_owner_product_master WHERE store_master_id = '".$_POST['store_master_id']."'");
			if($storeMasterData){
				foreach ($storeMasterData as $storeMasterProduct) {
					$idProduct = $storeMasterProduct['id'];
					$sopmUpdateData = [
						'is_product_fundraising' => $ct_fundraising
					];
					parent::updateTable_f_mdl('store_owner_product_master',$sopmUpdateData,'id="'.$idProduct.'"');

					$sopvmUpdateData = [
						'fundraising_price' => trim($fundraisingPrice)
					];
					parent::updateTable_f_mdl('store_owner_product_variant_master',$sopvmUpdateData,'store_owner_product_master_id="'.$idProduct.'"');
				}
			}	
		}
		$isSuccess    = $resPonseData['isSuccess'];
		
		$fundraisingSetting = [
			"section_name"    => "Fundraising Setting",
			"store_master_id" => $_POST['store_master_id'],
			"pre_ct_fundraising_price"=>$store_master_data[0]['ct_fundraising_price'],
			"ct_fundraising_price"=>$fundraisingPrice,
			"pre_ct_fundraising"=>(isset($store_master_data[0]['is_fundraising']) && ($store_master_data[0]['is_fundraising']=="Yes"))?1:0,
			"ct_fundraising"=>(isset($ct_fundraising) && ($ct_fundraising=="Yes"))?1:0,
			"updated_by"=> "Super Admin <br>(".$login_user_email.")",
			"updated_on"=> date('Y-m-d H:i:s')
		];
		parent::insertTable_f_mdl('store_history',$fundraisingSetting);
		
		$checkIndividualFundrising = 0;
		//if any store level changes are made, then we need to change price for all products
		if( (isset($store_master_data[0]['is_fundraising']) && $store_master_data[0]['is_fundraising']!=$_POST['is_fundraising']) 
			OR (isset($store_master_data[0]['ct_fundraising_price']) && $store_master_data[0]['ct_fundraising_price']!=$fundraisingPrice)
			// OR (count($checkIndividualFundrising)>0)
		){
			$sql = 'SELECT store_owner_product_master.store_product_master_id AS master_product_id, `store_owner_product_variant_master`.id, `store_owner_product_variant_master`.shop_product_id, shop_variant_id, price, price_on_demand, fundraising_price,store_product_variant_master_id FROM `store_owner_product_variant_master`
					LEFT JOIN store_owner_product_master ON store_owner_product_master.id = `store_owner_product_variant_master`.store_owner_product_master_id
					WHERE store_owner_product_master.store_master_id="'.$_POST['store_master_id'].'"
					';
			$var_data = parent::selectTable_f_mdl($sql);
			if(!empty($var_data)){
				foreach($var_data as $single_var){

					$sopvm_update_data = [
						'price' => trim($single_var['price']+(int)$_POST['price_difference']),
						'price_on_demand' => trim($single_var['price_on_demand']+(int)$_POST['on_demand_price_difference'])
					];
					parent::updateTable_f_mdl('store_owner_product_variant_master',$sopvm_update_data,'id="'.$single_var['id'].'"');

					if(isset($single_var['shop_product_id']) && !empty($single_var['shop_product_id']) &&
						isset($single_var['shop_variant_id']) && !empty($single_var['shop_variant_id']) &&
						isset($graphql)
					){
						sleep(0.5);

					if(isset($_POST['store_sale_type_master_id']) && $_POST['store_sale_type_master_id']==2){
					
							if($_POST['is_fundraising']=='Yes'){
								$input_price = floatval(trim($single_var['price_on_demand'])) + floatval(trim($single_var['fundraising_price']))+(int)$_POST['on_demand_price_difference']; // Task 36 change post variable
							}else{
								$input_price = floatval(trim($single_var['price_on_demand']))+(int)$_POST['on_demand_price_difference']; // Task 36 change post variable
							}
						}else{
							if($_POST['is_fundraising']=='Yes'){
								$input_price = floatval(trim($single_var['price'])) + floatval(trim($single_var['fundraising_price']))+(int)$_POST['price_difference'];
							}else{
								$input_price = floatval(trim($single_var['price']))+(int)$_POST['price_difference'];
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
		$reponse = storeHelper::storeVariantDeletePost($_POST,true);
		echo $reponse;
	}

	public function edit_store_apply_logo(){
		storeHelper::editStoreApplyLogo($_POST,true);
	}
	
	/**
	 * edit_store_new_logo_post
	 *
	 * @return void
	 */
	public function edit_store_new_logo_post(){
		storeHelper::editStoreNewLogoPost($_POST,true);
	}
		
	/**
	 * store_logo_make_default_post
	 *
	 * @return void
	 */
	public function store_logo_make_default_post(){
		$reponse = storeHelper::storeLogoMakeDefaultPost($_POST,true);
		echo $reponse;
	}
	
	/**
	 * store_logo_delete_post
	 *
	 * @return void
	 */
	public function store_logo_delete_post(){
		$reponse = storeHelper::storeLogoDeletePost($_POST);
		echo $reponse;
	}
	
	/**
	 * edit_store_sort_list_post
	 * @return void
	 */
	public function edit_store_sort_list_post(){
		storeHelper::editStoreSortListPost($_POST,true);
	}
	
	/**
	 * edit_store_owner_address_post
	 * @return void
	 */
	public function edit_store_owner_address_post(){
		storeHelper::editStoreOwnerAddressPost($_POST,true);
	}
	
	/**
	 * edit_store_owner_silver_delivery_address_post
	 * @return void
	 */
	public function edit_store_owner_silver_delivery_address_post(){
		storeHelper::editStoreOwnerSilverDeliveryAddressPost($_POST,true);
	}
    /**
     * edit_store_setting_post
     * This funtion use for tab: Store Setting
     * @return void
     */
    public function edit_store_setting_post(){
		storeHelper::editStoreSettingPostData($_POST,true);
	}
	
	/**
	 * edit_store_fundraising
	 * This funtion use for tab: Fundraising bar
	 * @return void
	 */
	public function edit_store_fundraising(){
		storeHelper::editStoreFundraising($_POST,true);
	}
	
	/**
	 * getColorFilterDropdown
	 * @return void
	 */
	public function getColorFilterDropdown(){
		$response = storeHelper::getColorFilterDropdown($_POST);
		echo $response;
	}

	/**
	 * getSizeFilterDropdown
	 * @return void
	 */
	public function getSizeFilterDropdown(){
		$response = storeHelper::getSizeFilterDropdown($_POST);
		echo $response;
	}
	
	/**
	 * getStoreBasiDetail
	 * @param  mixed $store_master_id
	 * @return void
	 */
	public function getStoreBasiDetail($store_master_id){

		/* Logo setting add new column associate_with_global_id*/
		$sql1="
				SELECT sm.associate_with_global_id,sm.ct_fundraising_price, sm.id, sm.store_organization_type_master_id,org.organization_name as organization_type_name, sm.shop_collection_handle,sm.is_store_batched,sm.front_side_ink_colors, sm.back_side_ink_colors,sm.store_name,sm.store_open_date,sm.is_fundraising,sm.store_fulfillment_type,sm.store_close_date,sm.verification_status,sm.notes,sstm.sale_type,sodm.first_name,sodm.last_name,sodm.email,sodm.phone,sodm.organization_name,sm.status,sm.print_date,sm.follow_up_email_is_sent,
				(SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = sm.id) as totalItem_sold,

				(SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = sm.id and om.order_type=1) as actual_orderItem_sold,
				(SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = sm.id and om.order_type=2) as manual_orderItem_sold,
				(SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = sm.id and om.order_type=3) as quickbuy_orderItem_sold,
				(SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0) as total_order, 
				(SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0 AND order_type=1) as total_actual_order,
				(SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0 AND order_type=2) as total_manual_order,
				(SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0 AND order_type=3) as total_quickbuy_order,
				(SELECT IFNULL(SUM(total_price),0) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0) as total_order_price,
				(SELECT IFNULL(SUM(total_price),0) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0 AND order_type=1) as total_actual_order_price,  
				(SELECT IFNULL(SUM(total_fundraising_amount),0) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0) as total_fund_amount,
				(SELECT IFNULL(SUM(soim.fundraising_amount),0) FROM store_order_items_master as soim INNER JOIN store_orders_master as som ON som.id=soim.store_orders_master_id  WHERE som.is_order_cancel = 0 AND soim.is_deleted = 0 and soim.store_master_id ='".$store_master_id."') as total_item_fund_amount
				FROM store_master sm INNER JOIN store_owner_details_master sodm ON sm.store_owner_details_master_id = sodm.id INNER JOIN store_sale_type_master sstm ON sm.store_sale_type_master_id = sstm.id 
				LEFT JOIN store_organization_type_master as org ON sm.store_organization_type_master_id = org.id WHERE sm.id = ".$store_master_id."
			";
			
		$all_list = parent::selectTable_f_mdl($sql1);
		return $all_list;
	}

	//Task 36 17-09-21 Task 53
	public function getGeneralSettingDetails(){
		$sql = 'SELECT fullfilment_type_second,fullfilment_type_third,is_enable_in_home,is_enable_in_bagged,is_enable_in_basic  FROM general_settings_master WHERE id = 1 LIMIT 1';
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
		global $login_user_email;
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
			$sql = 'SELECT id,front_side_ink_colors,back_side_ink_colors,sleeve_ink_colors,is_back_enable,store_fulfillment_type,is_fundraising,ct_fundraising_price,is_bulk_pricing,store_fulfillment_type,store_organization_type_master_id,store_sale_type_master_id FROM `store_master` WHERE id="'.$_POST['store_master_id'].'"';
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

					if (isset($row['sleeve_ink_colors']) && !empty($row['sleeve_ink_colors'])) {
						$add_cost += common::ADD_COST_BACK_SIDE_INK_COLOR + intval($row['sleeve_ink_colors'])-1;
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
					if(isset($row['store_fulfillment_type']) && $row['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
						$fullfilment_type_price = $fullfilment_gold_price;
						//$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_2;
					}
					else if(isset($row['store_fulfillment_type']) && $row['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
						$fullfilment_type_price = $fullfilment_platinum_price;
						//$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_3;
					}else if(isset($row['store_fulfillment_type']) && $row['store_fulfillment_type']=='SHIP_1_LOCATION_NOT_SORT'){
						$fullfilment_type_price = $fullfilment_silver_price;
					}

					$sql = 'SELECT id,store_product_master_id,group_name from store_owner_product_master where store_master_id="'.$row['id'].'"';
					$storeOwnerProductsMaster = parent::selectTable_f_mdl($sql);
					foreach ($storeOwnerProductsMaster as $row2) 
					{
						if($row2['id'])
						{
							$group_name=$row2['group_name'];
							$store_product_master_id=$row2['store_product_master_id'];
							$sql = 'SELECT id,store_owner_product_master_id,store_product_variant_master_id,price,price_on_demand,store_organization_type_master_id,shop_product_id,shop_variant_id,fundraising_price from store_owner_product_variant_master where store_owner_product_master_id="'.$row2['id'].'" AND store_organization_type_master_id = "'.$row['store_organization_type_master_id'].'"';// Task 50 Add 1 new column store_organization_type_master_id and add condition for this						
							$storeOwnerProductsVarientMaster = parent::selectTable_f_mdl($sql);
							foreach ($storeOwnerProductsVarientMaster as $single_var) 
							{
								if($single_var['store_product_variant_master_id'])
								{
									$sql = 'SELECT price,price_on_demand from store_product_variant_master where id="'.$single_var['store_product_variant_master_id'].'" AND is_ver_deleted="0" ';
									$storeProductVariantMaster = parent::selectTable_f_mdl($sql);

									$sqlmaster_group = 'SELECT id,group_id,is_eligible_sleeve_print from store_product_master where id="'.$store_product_master_id.'" AND is_deleted="0" ';
                  					$storeProductMasterGroup = parent::selectTable_f_mdl($sqlmaster_group);
									$group_id='';
									$is_eligible_sleeve_print='0';
									if(!empty($storeProductMasterGroup)){
										$group_id=$storeProductMasterGroup[0]['group_id'];
										$is_eligible_sleeve_print=$storeProductMasterGroup[0]['is_eligible_sleeve_print'];
									}
                  
									//To do add bussiness login for fullfilmemnt type & fundrising
									if(isset($storeProductVariantMaster[0]['price']) && $storeProductVariantMaster[0]['price_on_demand'])
									{	
										if($group_id=='9'){
											$flashSalePrice = (floatval($storeProductVariantMaster[0]['price']));
											$ondemandPrice  = (floatval($storeProductVariantMaster[0]['price_on_demand']));
										}else{

											$flashSalePrice = (floatval($storeProductVariantMaster[0]['price'])+$add_cost+$fullfilment_type_price);//Task 50 Add new variable flashSalePrice
											$ondemandPrice  = (floatval($storeProductVariantMaster[0]['price_on_demand'])+$add_on_cost);// Task 50 Add new variable for on_demand
										}
										//$flashSalePrice = (floatval($storeProductVariantMaster[0]['price'])+$add_cost+$fullfilment_type_price);//Task 50 Add new variable flashSalePrice
										//$ondemandPrice  = (floatval($storeProductVariantMaster[0]['price_on_demand'])+$add_on_cost);// Task 50 Add new variable for on_demand
									}
									else
									{
										$flashSalePrice = $single_var['price'];//Task 50 Add new variable flashSalePrice
										$ondemandPrice  = $single_var['price_on_demand'];
									}
									// Task 42 end
									if($is_eligible_sleeve_print=='0'){
										$flashSalePrice = $flashSalePrice - (common::ADD_COST_BACK_SIDE_INK_COLOR + intval($row['sleeve_ink_colors'])-1);
										$updateStoreOwnerProductVariant = [
											'front_side_ink_colors_group' 	=> $row['front_side_ink_colors'],
											'back_side_ink_colors_group'  	=> $row['back_side_ink_colors'],
											'sleeve_ink_color_group' 		=> '0',
											'is_back_enable_group'  	  	=> $row['is_back_enable'],
											'price'           				=> $flashSalePrice,
											'price_on_demand' 				=> $ondemandPrice,// Task 50 Add new variable for on_demand
										];
									}else{
										$updateStoreOwnerProductVariant = [
											'front_side_ink_colors_group' 	=> $row['front_side_ink_colors'],
											'back_side_ink_colors_group'  	=> $row['back_side_ink_colors'],
											'sleeve_ink_color_group' 		=> $row['sleeve_ink_colors'],
											'is_back_enable_group'  	  	=> $row['is_back_enable'],
											'price'           				=> $flashSalePrice,
											'price_on_demand' 				=> $ondemandPrice,// Task 50 Add new variable for on_demand
										];
									}
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

					$sql = 'SELECT Distinct(group_name) as groupname FROM `store_owner_product_master` WHERE  is_soft_deleted="0" AND store_master_id="'.$_POST['store_master_id'].'"  ';
					$ownerGroupData =parent::selectTable_f_mdl($sql);
					$storeType = '';
					if($row['store_sale_type_master_id']==1){
						$storeType = "Flash Sale";
					}else{
						$storeType = "On-Demand";
					}
					if(!empty($ownerGroupData)){
						foreach($ownerGroupData as $singlegroup){
							$changeGroupInkPriceData = [
								'store_master_id' 				=>$_POST['store_master_id'],
								'group_name'      				=>$singlegroup['groupname'],
								'store_type'      				=>$storeType,
								'changed_front_ink_group_price' =>trim($row['front_side_ink_colors']),
								'changed_back_ink_group_price'  =>trim($row['back_side_ink_colors']),
								'changed_sleeve_ink_group_price'=>trim($row['sleeve_ink_colors']),
								'back_side_ink_ondemand'   		=>trim($row['is_back_enable']),
								'created_on'      				=>date('Y-m-d H:i:s'),
								"updated_by"      				=> "Super Admin <br>(".$login_user_email.")",
							];
							parent::insertTable_f_mdl('group_ink_price_history',$changeGroupInkPriceData);
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
	// Task 77 start
	public function addNotes()
	{
		if(!empty($_POST['store_master_id'])){
			$store_notes = (!empty($_POST['store_notes']))?$_POST['store_notes']:'';
			parent::addNotes_f_mdl($_POST['store_master_id'], $store_notes);
		}
		
	}
	// Task 77 end

	// Task 63 Logo setting add new function start task
	public function getPrintSize()
	{
		return parent::getPrintSize_f_mdl();
	}

	public function getPrintLocation()
	{
		return parent::getPrintLocation_f_mdl();
	}

	public function getPantoneColor()
	{
		return parent::getPantoneColor_f_mdl();
	}


	public function getPrintSizeByID($id=0)
	{
		return parent::getPrintSizeByID_f_mdl($id);
	}

	public function getPrintLocationByID($id=0)
	{
		return parent::getPrintLocationByID_f_mdl($id);
	}

	public function getPantoneColorByID($id=0)
	{
		return parent::getPantoneColorByID_f_mdl($id);
	}

	public function getApplicableColorsName($applicable_colors){
		$applicable_color=explode(',',$applicable_colors);
		$JsoncolorArray = json_encode(array_values($applicable_color));
		$colorCodeValues  = str_replace (array('[', ']'), '' , $JsoncolorArray);
		$existSql = "SELECT product_color, product_color_name FROM store_product_colors_master WHERE product_color IN ($colorCodeValues) 
		";
		return parent::selectTable_f_mdl($existSql);
	}

	/**
	* get_product_groups
	* @return void
	*/
	public function getProductsGroupsByStore($store_master_id){		
		$sqlG = 'SELECT mg.product_group, mg.id, sopm.group_name, sopm.store_master_id FROM `minimum_group_product` as mg INNER JOIN store_owner_product_master as sopm on mg.product_group = sopm.group_name WHERE 1 AND sopm.store_master_id = '.$store_master_id.' group by sopm.group_name ' ;		
		return $productGroup =  parent::selectTable_f_mdl($sqlG);
	}

	public function getAllProductGroups(){		
		$sqlG = "SELECT * FROM minimum_group_product WHERE is_deleted = '0' ORDER BY group_order ASC";/* Task 82 Add where condition is_deleted = 0*/	
		return $productGroup =  parent::selectTable_f_mdl($sqlG);
	}

	// Task 63 Logo setting end task

	//Task 80 start 	
	/**
	 * delete_ship_to_address
	 *
	 * @return void
	 */
	public function delete_ship_to_address(){
		if(!empty($_POST['store_master_id'])){
			return parent::updateShipAddress($_POST['store_master_id']);
		}
	}
	//Task 80 end

	/* Task 92 start */
	public function create_duplicate_product()
	{
		$res =  array();
		if(!empty($_POST['store_master_id'])){
			$productColors = '';
			$productCode = array();
			if(sizeof($_POST['productColorArray']) > 0){
				$productColors = $_POST['productColorArray'];
				foreach ($productColors as $value) {
					$sql1      = 'SELECT product_color FROM store_product_colors_master where product_color_name = "'.trim($value).'" ';
					$colorName = parent::selectTable_f_mdl($sql1);
					if (!empty($colorName)) {
						foreach ($colorName as $valueC) {
							$productCode[] = $valueC['product_color'];
						}
					}
				}
			}
			$JsonproductArray = json_encode(array_values($productCode));
			$colorCodeValues  = str_replace (array('[', ']'), '' , $JsonproductArray);
			$store_master_id = $_POST['store_master_id'];
			$sql = 'SELECT * FROM `store_master` WHERE id="' . $store_master_id . '"';
			$store_master_data =  parent::selectTable_f_mdl($sql);

			$product_id      = (!empty(trim($_POST['product_id'])))? trim($_POST['product_id']):'';
			$product_name    = (!empty(trim($_POST['product_name'])))? trim($_POST['product_name']):'';

			$sql='SELECT product_title FROM store_owner_product_master WHERE store_master_id="'.$store_master_id. '"';
			$pro_data_new = parent::selectTable_f_mdl($sql);
			if(!empty($pro_data_new)){
				foreach ($pro_data_new as $single_product) {
					$product_title=$single_product['product_title'];
					if(trim($product_title) == trim($product_name)){
						$res['SUCCESS'] = 'FALSE';
						$res['MESSAGE'] = 'This Product is already available in this store.';
						echo json_encode($res);die;
					}
				}
			}

			$sql = 'SELECT yard_sign_description FROM `general_settings_master` limit 1';
			$yardsign_description =  parent::selectTable_f_mdl($sql);
			if($_POST['group_name']=='Yard Signs'){
				$sm_update_data = [
					'store_description' => $yardsign_description[0]['yard_sign_description']
				];
				parent::updateTable_f_mdl('store_master',$sm_update_data,'id="'.$store_master_id.'"');
			}

			$sql = 'SELECT * FROM `store_owner_product_master` WHERE store_master_id="' . $store_master_id . '" AND id = "'.$product_id.'" ';
			$pro_list =  parent::selectTable_f_mdl($sql);
			if (!empty($pro_list)) {
				foreach ($pro_list as $single_pro) {
					$old_store_product_master_id = $single_pro['id'];
					$existSql = 'SELECT * FROM `store_product_variant_master` WHERE store_product_master_id="' . $single_pro['store_product_master_id'] . '" AND color IN('.$colorCodeValues.') and store_organization_type_master_id = '.$_POST["store_organization_type_master_id"].' AND is_ver_deleted="0" ';
					$existData =  parent::selectTable_f_mdl($existSql);
					// echo "<pre>";print_r($existData);die;

					if($single_pro['store_product_master_id']=='789' || $single_pro['store_product_master_id']=='169'){
						$is_persionalization = '1';
						$is_require = '1';
					}else{
						$is_persionalization = '0';
						$is_require = '0';
					}
					if (!empty($existData)) {
						//insert product details
						$sopm_insert_data = [
							'store_master_id'              => $store_master_id,
							'store_product_master_id'      => $single_pro['store_product_master_id'],
							'product_title'                => $product_name,
							'product_description'          => $single_pro['product_description'],
							'tags'                         => $single_pro['tags'],
							'status'                       => '1',
							'is_personalization' 		   => $is_persionalization,
							'is_required'		 		   => $is_require,
							'created_on'                   => @date('Y-m-d H:i:s'),
							'created_on_ts'                => time(),
							'group_name'                   => $_POST['group_name'],
							'is_product_synced_to_collect' => '0'
						];
						$sopm_arr = parent::insertTable_f_mdl('store_owner_product_master', $sopm_insert_data);
						$sqlmasterprod = 'SELECT id,is_eligible_sleeve_print from store_product_master where id="'.$single_pro['store_product_master_id'].'" AND is_deleted="0" ';
						$productMasterdata = parent::selectTable_f_mdl($sqlmasterprod);
						$is_eligible_sleeve_print='0';
						if(!empty($productMasterdata)){
							$is_eligible_sleeve_print=$productMasterdata[0]['is_eligible_sleeve_print'];
						}
						if (isset($sopm_arr['insert_id'])) {
							$sopm_id  = $sopm_arr['insert_id'];

							$sql = 'SELECT sopvm.front_side_ink_colors_group,sopvm.back_side_ink_colors_group,sopvm.sleeve_ink_color_group,sopvm.is_back_enable_group FROM store_owner_product_master as sopm INNER JOIN store_owner_product_variant_master as sopvm ON sopm.id=sopvm.store_owner_product_master_id WHERE sopm.store_master_id="'.$store_master_id.'" AND sopm.group_name="'.$_POST['group_name'].'" LIMIT 1';
							$prodInkCostGroup =  parent::selectTable_f_mdl($sql);

							$sqlGroupHistory = 'SELECT changed_sleeve_ink_group_price FROM group_ink_price_history WHERE store_master_id="'.$store_master_id.'" AND  group_name="'.$_POST['group_name'].'" ORDER BY id DESC LIMIT 1';
							$prodInkCostGroupHistoryData =  parent::selectTable_f_mdl($sqlGroupHistory);
							$prodInkCostGroupHistory='0';
							if(!empty($prodInkCostGroupHistoryData)){
								$prodInkCostGroupHistory = $prodInkCostGroupHistoryData[0]['changed_sleeve_ink_group_price'];
							}

							$sql = 'SELECT * FROM `store_product_variant_master` WHERE store_product_master_id="' . $single_pro['store_product_master_id'] . '" AND color IN('.$colorCodeValues.') AND store_organization_type_master_id = '.$_POST["store_organization_type_master_id"].' AND is_ver_deleted="0" ';
							$var_list =  parent::selectTable_f_mdl($sql);
							if (!empty($var_list)) {
								foreach ($var_list as $var_data) {
									$image = $var_data['image'];
									
									// Task 42 start
									$sql = 'SELECT price,price_on_demand from store_product_variant_master where id="'.$var_data['id'].'" AND is_ver_deleted="0" ';
									$storeProductVariantMaster = parent::selectTable_f_mdl($sql);

									$add_cost = 0;
									if(isset($prodInkCostGroup[0]['front_side_ink_colors_group']) && !empty($prodInkCostGroup[0]['front_side_ink_colors_group'])){
										$add_cost += intval($prodInkCostGroup[0]['front_side_ink_colors_group'])-1;
									}else if(isset($store_master_data[0]['front_side_ink_colors']) && !empty($store_master_data[0]['front_side_ink_colors'])){
										$add_cost += intval($store_master_data[0]['front_side_ink_colors'])-1;
									}

									$add_on_cost = 0;
									if(isset($prodInkCostGroup[0]['back_side_ink_colors_group']) && !empty($prodInkCostGroup[0]['back_side_ink_colors_group'])){
										$add_cost   += common::ADD_COST_BACK_SIDE_INK_COLOR+intval($prodInkCostGroup[0]['back_side_ink_colors_group'])-1;
										$add_on_cost = common::ADD_COST_BACK_SIDE_INK_COLOR;
									}else if(isset($store_master_data[0]['back_side_ink_colors']) && !empty($store_master_data[0]['back_side_ink_colors'])){
										$add_cost   += common::ADD_COST_BACK_SIDE_INK_COLOR+intval($store_master_data[0]['back_side_ink_colors'])-1;
										$add_on_cost = common::ADD_COST_BACK_SIDE_INK_COLOR;
									}

									if(isset($prodInkCostGroupHistory) && !empty($prodInkCostGroupHistory)){
										$add_cost += common::ADD_COST_BACK_SIDE_INK_COLOR + intval($prodInkCostGroupHistory)-1;
									}else if(isset($store_master_data[0]['sleeve_ink_colors']) && !empty($store_master_data[0]['sleeve_ink_colors'])){
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
									}else if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
										$fullfilment_type_price = $fullfilment_platinum_price;
										//$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_3;
									}else if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=='SHIP_1_LOCATION_NOT_SORT'){
										$fullfilment_type_price = $fullfilment_silver_price;
										//$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_3;
									}
									
									$sqlmaster_group = 'SELECT id,group_id from store_product_master where id="'.$single_pro['store_product_master_id'].'" AND is_deleted="0" ';
									$storeProductMasterGroup = parent::selectTable_f_mdl($sqlmaster_group);
									$group_id='';
									if(!empty($storeProductMasterGroup)){
										$group_id=$storeProductMasterGroup[0]['group_id'];
									}

									//To do add bussiness login for fullfilmemnt type & fundrising
									$ondemandPrice  = 0;
									$flashSalePrice = 0;
									if(isset($storeProductVariantMaster[0]['price']) && $storeProductVariantMaster[0]['price_on_demand']){
										if($group_id=='9'){
											$ondemandPrice  = (floatval($storeProductVariantMaster[0]['price_on_demand']));
											$flashSalePrice = $storeProductVariantMaster[0]['price'];
										}else{
											$ondemandPrice  = (floatval($storeProductVariantMaster[0]['price_on_demand'])+$add_on_cost);
											$flashSalePrice = $storeProductVariantMaster[0]['price']+$add_cost+$fullfilment_type_price;
										}
									}else{
										$ondemandPrice  = $var_data['price_on_demand'];
										$flashSalePrice = $var_data['price'];
									}

									if(!empty($prodInkCostGroup)){
										$front_side_ink_colors_group=trim($prodInkCostGroup[0]['front_side_ink_colors_group']);
										$back_side_ink_colors_group=trim($prodInkCostGroup[0]['back_side_ink_colors_group']);
										$sleeve_ink_color_group=trim($prodInkCostGroup[0]['sleeve_ink_color_group']);
										$is_back_enable_group=trim($prodInkCostGroup[0]['is_back_enable_group']);
									}else{
										$front_side_ink_colors_group=trim($store_master_data[0]['front_side_ink_colors']);
										$back_side_ink_colors_group=trim($store_master_data[0]['back_side_ink_colors']);
										$sleeve_ink_color_group=trim($store_master_data[0]['sleeve_ink_colors']);
										$is_back_enable_group=trim($store_master_data[0]['is_back_enable']);
									}

									if($is_eligible_sleeve_print=='0'){
										if(isset($prodInkCostGroupHistory) && !empty($prodInkCostGroupHistory)){
											$sleevecost = common::ADD_COST_BACK_SIDE_INK_COLOR + intval($prodInkCostGroupHistory)-1;
										}else if(isset($store_master_data[0]['sleeve_ink_colors']) && !empty($store_master_data[0]['sleeve_ink_colors'])){
											$sleevecost = common::ADD_COST_BACK_SIDE_INK_COLOR + intval($store_master_data[0]['sleeve_ink_colors'])-1;
										}
										$flashSalePrice = $flashSalePrice - $sleevecost;
									
										$sopvm_insert_data = [
											'store_owner_product_master_id'     => $sopm_id,
											'store_product_variant_master_id'   => $var_data['id'],
											'store_organization_type_master_id' => $var_data['store_organization_type_master_id'],
											'price'                             => $flashSalePrice,
											'price_on_demand'                   => $ondemandPrice,
											'fundraising_price'                 => $_POST['fundraising_price'],
											'color'     		                => $var_data['color'],
											'size'      		                => $var_data['size'],
											'image'                             => $var_data['image'],
											'original_image'                    => $var_data['feature_image'],
											'sku' 				                => $var_data['sku'],
											'weight' 			                => $var_data['weight'],
											'front_side_ink_colors_group' 		=> $front_side_ink_colors_group,
											'back_side_ink_colors_group' 		=> $back_side_ink_colors_group,
											'sleeve_ink_color_group' 		    => $sleeve_ink_color_group,
											'is_back_enable_group'  	 		=> $is_back_enable_group,
											'status' 			                => '1',
											'created_on' 		                => @date('Y-m-d H:i:s'),
											'created_on_ts' 	                => time()
										];
									}else{
										$sopvm_insert_data = [
											'store_owner_product_master_id'     => $sopm_id,
											'store_product_variant_master_id'   => $var_data['id'],
											'store_organization_type_master_id' => $var_data['store_organization_type_master_id'],
											'price'                             => $flashSalePrice,
											'price_on_demand'                   => $ondemandPrice,
											'fundraising_price'                 => $_POST['fundraising_price'],
											'color'     		                => $var_data['color'],
											'size'      		                => $var_data['size'],
											'image'                             => $var_data['image'],
											'original_image'                    => $var_data['feature_image'],
											'sku' 				                => $var_data['sku'],
											'weight' 			                => $var_data['weight'],
											'front_side_ink_colors_group' 		=> $front_side_ink_colors_group,
											'back_side_ink_colors_group' 		=> $back_side_ink_colors_group,
											'sleeve_ink_color_group' 		    => '0',
											'is_back_enable_group'  	 		=> $is_back_enable_group,
											'status' 			                => '1',
											'created_on' 		                => @date('Y-m-d H:i:s'),
											'created_on_ts' 	                => time()
										];

									}
									parent::insertTable_f_mdl('store_owner_product_variant_master', $sopvm_insert_data);
								}
							}
							//now open store to sync in shopify
							$sm_update_data = [
								'is_products_synced' => '0',
								'is_manual_store_sync' => '1'
							];
							parent::updateTable_f_mdl('store_master',$sm_update_data,'id="'.$store_master_id.'"');
						}
						$res['SUCCESS'] = 'TRUE';
						$res['MESSAGE'] = 'Product created successfully.';
					}
					else{
						$res['SUCCESS'] = 'FALSE';
						$res['MESSAGE'] = 'Error while inserting store details. Please check and try again after some time.';
					}
				}

				$varSql ="SELECT count(*) as totalVariant FROM store_owner_product_master as sopm LEFT JOIN store_owner_product_variant_master as sopvm ON sopvm.store_owner_product_master_id = sopm.id where sopm.store_master_id = '$store_master_id' and sopvm.id!='' ";
				$varInfo = parent::selectTable_f_mdl($varSql);
				$totalVariant = 0;
				if(!empty($varInfo)){
					$varData = $varInfo[0];
					$totalVariant = $varData['totalVariant'];
				}
				$updatVariantCount = [
					"total_variants_count"=>$totalVariant
				];
				parent::updateTable_f_mdl('store_master',$updatVariantCount,'id="'.$store_master_id.'"');
			}
			else{
				$res['SUCCESS'] = 'FALSE';
				$res['MESSAGE'] = 'Error while inserting store details. Please check and try again after some time.';
			}
			echo json_encode($res);
		}
		die;
	}
	
	public function getProductColor()
	{	/* Task 115 add group_name in this function */
		$response = array();
		if(!empty($_POST['product_id'])){
			$strownSql     = 'SELECT id,store_product_master_id,group_name FROM store_owner_product_master WHERE id ='.$_POST['product_id'].' ';
			$getStrProduct = parent::selectTable_f_mdl($strownSql);
			$groupName = '';
			if(!empty($getStrProduct)){
				$storeProductId = $getStrProduct[0]['store_product_master_id'];
				$groupName      = $getStrProduct[0]['group_name'];
			}

			$store_organization_type_master_id = $_POST['store_organization_type_master_id'];
			$sql = 'SELECT `store_product_variant_master`.color, store_product_colors_master.product_color,store_product_colors_master.product_color_name,store_color_family_master.color_image
				FROM `store_product_variant_master`
				LEFT JOIN `store_product_master` ON `store_product_master`.id = `store_product_variant_master`.store_product_master_id
				LEFT JOIN store_product_colors_master ON store_product_colors_master.product_color = store_product_variant_master.color
				LEFT JOIN store_color_family_master ON store_color_family_master.color_family_name = store_product_colors_master.color_family
				WHERE `store_product_variant_master`.store_organization_type_master_id = "'.$store_organization_type_master_id.'"
				AND `store_product_variant_master`.status = "1" AND `store_product_variant_master`.store_product_master_id ='.$storeProductId.'
				AND `store_product_master`.status = "1" GROUP BY `store_product_variant_master`.color';
			$getColor = parent::selectTable_f_mdl($sql);
			
			$htmlBody = '';
			if(!empty($getColor)){
				foreach($getColor as $single_color){
		            $clrfamilyName = $single_color['product_color_name'];
		            $clrfamilycode = $single_color['product_color'];

		            $htmlBody .= '<div class="checkbox-custom checkbox-primary">';
		            $htmlBody .= '<input type="checkbox" class="product_color_family new_pro_parent_'.str_replace(' ','_',$clrfamilyName).'" value="'.$clrfamilyName.'">';
		            $htmlBody .='<label for=""><span class="color_group_span" style="background-color:'.$clrfamilycode.'">&nbsp;&nbsp;&nbsp;&nbsp;</span>'.$clrfamilyName;
		            $htmlBody .= '</label>';
		            $htmlBody .= '</div>';
		        }
		        $response['group_name'] = $groupName;
		        $response['htmlBody']   = $htmlBody;
			}else{
				$response['htmlBody'] = '';
			}
        }else{
        	$response['htmlBody'] = '';
        }
        echo json_encode($response);die();
	}

	public function getProductColorByGroup()
	{	/* Task 115 add group_name in this function */
		$response = array();
		if(!empty($_POST['group_name'])){
			$strownSql     = 'SELECT group_concat(store_product_master_id) as master_product_ids FROM store_owner_product_master WHERE store_master_id="'.$_POST['store_master_id'].'" AND  group_name ="'.$_POST['group_name'].'" AND is_soft_deleted="0" ';
			$getStrProduct = parent::selectTable_f_mdl($strownSql);
			$storeProductId = '';
			if(!empty($getStrProduct)){
				$storeProductId = $getStrProduct[0]['master_product_ids'];
			}
			$sql = 'SELECT `store_product_variant_master`.color, store_product_colors_master.product_color,store_product_colors_master.product_color_name,store_color_family_master.color_image
				FROM `store_product_variant_master`
				LEFT JOIN `store_product_master` ON `store_product_master`.id = `store_product_variant_master`.store_product_master_id
				LEFT JOIN store_product_colors_master ON store_product_colors_master.product_color = store_product_variant_master.color
				LEFT JOIN store_color_family_master ON store_color_family_master.color_family_name = store_product_colors_master.color_family
				WHERE `store_product_variant_master`.status = "1" AND `store_product_variant_master`.store_product_master_id IN ('.$storeProductId.')
				AND `store_product_master`.status = "1" GROUP BY `store_product_variant_master`.color';
			$getColor = parent::selectTable_f_mdl($sql);
			
			$htmlBody = '';
			if(!empty($getColor)){
				foreach($getColor as $single_color){
		            $clrfamilyName = $single_color['product_color_name'];
		            $clrfamilycode = $single_color['product_color'];

		            $htmlBody .= '<div class="checkbox-custom checkbox-primary">';
		            $htmlBody .= '<input type="checkbox" class="product_color_family_group new_pro_parent_'.str_replace(' ','_',$clrfamilyName).'" value="'.$clrfamilyName.'">';
		            $htmlBody .='<label for=""><span class="color_group_span" style="background-color:'.$clrfamilycode.'">&nbsp;&nbsp;&nbsp;&nbsp;</span>'.$clrfamilyName;
		            $htmlBody .= '</label>';
		            $htmlBody .= '</div>';
		        }
		        $response['htmlBody']   = $htmlBody;
			}else{
				$response['htmlBody'] = '';
			}
        }else{
        	$response['htmlBody'] = '';
        }
        echo json_encode($response);die();
	}

	public function getVariantColor()
	{
		if(isset($_POST['product_id']) && !empty($_POST['product_id'])){
			$sql      = 'SELECT color FROM store_owner_product_variant_master where store_owner_product_master_id = '.$_POST['product_id'].' group by color';
			$getColor = parent::selectTable_f_mdl($sql);
			
			$res = array();
			if (!empty($getColor)) {
				$color ='';
				foreach ($getColor as $value) {
					$color     = $value['color'];
					$sql1      = 'SELECT product_color_name,product_color FROM store_product_colors_master where product_color = "'.$color.'" ';
					$colorName = parent::selectTable_f_mdl($sql1);
					if (!empty($colorName)) {
						foreach ($colorName as $valueC) {
							$res[] = $valueC['product_color_name'];
						}
					}
				}
			}
			echo json_encode($res);die();
		}
	}
	/* Task 92 end */

	public function updateProfile()
	{	
		$res =  array();
		if (isset($_POST['store_master_id']) && !empty($_POST['store_master_id'])) {
			$email=(!empty($_POST['store_email']))?$_POST['store_email']:'';
			$sql = 'SELECT store_owner_details_master_id FROM `store_master` WHERE id="' . $_POST['store_master_id'] . '"';
			$ownerDetail       =  parent::selectTable_f_mdl($sql);
			if(!empty($ownerDetail)){
				$store_owner_details_master_id=$ownerDetail[0]['store_owner_details_master_id'];
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
			if ($status=='1'){
				$res['SUCCESS'] = 'FALSE';
				$res['MESSAGE'] = 'Email already exist';
				echo json_encode($res);die();
			}else{
				if(!empty($ownerDetail)){
					$updateProfileInfo = [
						'first_name'        => (!empty($_POST['first_name']))?$_POST['first_name']:'',
						'last_name'         => (!empty($_POST['last_name']))?$_POST['last_name']:'',
						'email'             => (!empty($_POST['store_email']))?$_POST['store_email']:'',
						'phone'             => (!empty($_POST['store_phone']))?$_POST['store_phone']:'',
						'organization_name' => (!empty($_POST['store_orgname']))?$_POST['store_orgname']:''
					];
					parent::updateTable_f_mdl('store_owner_details_master',$updateProfileInfo,'id="'.$ownerDetail[0]['store_owner_details_master_id'].'"');
					$res['SUCCESS'] = 'TRUE';
					$res['MESSAGE'] = 'Profile update successfully.';
				}else{
					$res['SUCCESS'] = 'FALSE';
					$res['MESSAGE'] = 'Oops!there is some internal issues occured. Please try after some time.';
				}

			}
		}else{
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'Oops!there is some internal issues occured. Please try after some time.';
		}
		echo json_encode($res);die();
	}

		/* Task 110 start */
	public function getLabelDetails($store_master_id)
	{

		$profitSql  = 'SELECT *, "0" AS profit_value FROM `profit_cost_details` ';
		$profitDataAll = parent::selectTable_f_mdl($profitSql);
		$array = array();

		$storeSql  = 'SELECT is_old_order FROM store_master where id = "'.$store_master_id.'" ';
		$storeData = parent::selectTable_f_mdl($storeSql);
		
		if(!empty($profitDataAll))
		{
			$arrslug=array();
			foreach($profitDataAll as $value)
			{
				$profitSql  = 'SELECT store_profit.profit_value,profit_cost_details.cost_label,profit_cost_details.id,profit_cost_details.cost_slug,profit_cost_details.is_checked FROM store_profit LEFT JOIN profit_cost_details ON store_profit.profit_label_id = profit_cost_details.id where store_profit.store_master_id = "'.$store_master_id.'" AND profit_cost_details.id =  "'.$value['id'].'" ';
				$profitData = parent::selectTable_f_mdl($profitSql);
				if(!empty($profitData))
				{
					$array[] = array(
						'profit_value' => $profitData[0]['profit_value'], 
						'cost_label'   => $profitData[0]['cost_label'], 
						'cost_slug'   => $profitData[0]['cost_slug'], 
						'is_checked'   => $profitData[0]['is_checked'], 
						'id'           => $profitData[0]['id'],
						'default_profit' => $storeData[0]['is_old_order'],
					);
				}
				else{
				
					$array[] = array(
						'profit_value' => $value['lable_profit'], 
						'cost_label'   => $value['cost_label'], 
						'cost_slug'   => $value['cost_slug'], 
						'id'           => $value['id'],
						'is_checked'   => $value['is_checked'],
						'default_profit' =>$storeData[0]['is_old_order']
					);
				}
			}
		}
						
		$profitData = $array;
		return $profitData;die();
	}

	public function getApparelCostDetails($store_master_id)
	{
		$sanmar_apprial_cost='0.00';
		$apparelSql  = 'SELECT sanmar_apprial_cost,is_old_order FROM `store_master` WHERE id="'.$store_master_id.'" ';
		$apparelDataAll = parent::selectTable_f_mdl($apparelSql);
		return $apparelDataAll;die();
	}

	public function showProfitReport()
	{	
		$res =  array();
		if (isset($_POST['store_master_id']) && !empty($_POST['store_master_id'])) {
			$saleSql = 'SELECT soim.id,soim.store_master_id,sm.store_name AS title,soim.is_deleted,SUM(soim.quantity * soim.price) AS total_sale,som.store_sale_type FROM store_order_items_master AS soim INNER JOIN store_orders_master AS som ON soim.store_orders_master_id = som.id INNER JOIN store_master AS sm ON soim.store_master_id = sm.id WHERE 1 AND soim.is_deleted = 0 AND som.is_order_cancel = "0" AND som.order_type="1" AND som.store_master_id = '.$_POST['store_master_id'].' ';
			$saleData = parent::selectTable_f_mdl($saleSql);

			$saleSql2 = 'SELECT soim.id,soim.store_master_id,sm.store_name AS title,soim.is_deleted,SUM(soim.quantity * soim.price) AS total_sale,som.store_sale_type FROM store_order_items_master AS soim INNER JOIN store_orders_master AS som ON soim.store_orders_master_id = som.id INNER JOIN store_master AS sm ON soim.store_master_id = sm.id WHERE 1 AND soim.is_deleted = 0 AND som.is_order_cancel = "0" AND som.order_type="2" AND som.store_master_id = '.$_POST['store_master_id'].' ';
			$saleData2 = parent::selectTable_f_mdl($saleSql2);
			$total_sale_manual='';
			if(!empty($saleData2)){
				$total_sale_manual = $saleData2[0]['total_sale'];
			}

			$saleSql3 = 'SELECT soim.id,soim.store_master_id,sm.store_name AS title,soim.is_deleted,SUM(soim.quantity * soim.price) AS total_sale,som.store_sale_type FROM store_order_items_master AS soim INNER JOIN store_orders_master AS som ON soim.store_orders_master_id = som.id INNER JOIN store_master AS sm ON soim.store_master_id = sm.id WHERE 1 AND soim.is_deleted = 0 AND som.is_order_cancel = "0" AND som.order_type="3" AND som.store_master_id = '.$_POST['store_master_id'].' ';
			$saleData3 = parent::selectTable_f_mdl($saleSql3);
			$total_sale_quickbuy='';
			if(!empty($saleData3)){
				$total_sale_quickbuy = $saleData3[0]['total_sale'];
			}

			$discountCode = 'select SUM(discount_code_amount) as discount_code_amount from store_orders_master  WHERE store_master_id="'.$_POST['store_master_id'].'" AND is_order_cancel="0" ';
			$discountData = parent::selectTable_f_mdl($discountCode);
			$discountAmount='0.00';
			if(!empty($discountData)){
				$discountAmount = $discountData[0]['discount_code_amount'];
			}
			
			if(!empty($saleData)){
				
				$total_sale = $saleData[0]['total_sale'];
				$fundSql ='SELECT IFNULL(SUM(soim.fundraising_amount),0) as total_fundraising_amount FROM store_order_items_master as soim INNER JOIN store_orders_master as som ON som.id=soim.store_orders_master_id  WHERE som.is_order_cancel ="0" AND som.order_type = "1" AND soim.is_deleted ="0" AND soim.store_master_id ="'.$_POST['store_master_id'].'" ';
				$fundData = parent::selectTable_f_mdl($fundSql);
				$fundraising_amount = $fundData[0]['total_fundraising_amount'];
				
				$html = '';
				$label_values = 0;
				$printCost = 0.0;
				$checked_lable_cost=0.00;
                $unchecked_lable_cost=0.00;
				$totalSale          = number_format((float)$total_sale, 2);
				$totalSale = str_replace(",","",$totalSale);
				$total_sale_manual          = number_format((float)$total_sale_manual, 2);
				$total_sale_manual = str_replace(",","",$total_sale_manual);
				$total_sale_quickbuy          = number_format((float)$total_sale_quickbuy, 2);
				$total_sale_quickbuy = str_replace(",","",$total_sale_quickbuy);

				$discountAmount          = number_format((float)$discountAmount, 2);
				$discountAmount 		 = str_replace(",","",$discountAmount);


				$total_gross_sale = $total_sale+$total_sale_manual+$total_sale_quickbuy;
				$total_gross_sale          = number_format((float)$total_gross_sale, 2);
				$total_gross_sale = str_replace(",","",$total_gross_sale);

                $fundraising_amount = number_format((float)$fundraising_amount, 2);
                $fundraising_amount = str_replace(",","",$fundraising_amount);
                $html .='<tr><td><strong>Gross Sales (Actual+Manual+Quick)</strong></td><td style="color: #45A92B;"><strong>$'.$total_gross_sale.'( ' .$totalSale.'+'.$total_sale_manual.'+'.$total_sale_quickbuy.')</strong></td></tr>';
                $html .='<tr><td>Fundraising Amount</td><td style="color: #ff0000;">$'.$fundraising_amount.'</td></tr>';
                $html .='<tr><td>Discount Amount</td><td style="color: #ff0000;">$'.$discountAmount.'</td></tr>';
               
                parent::deleteTable_f_mdl('store_profit', 'store_master_id =' . $_POST['store_master_id']);
				$craditcardfee=0.00;
				$apprel_cost=number_format((float)$_POST['apprel_cost'], 2);
				$is_old_order=$_POST['is_old_order'];
				$apparel_cost_labelval='';
				foreach ($_POST['cost_details'] as $value) { 
					$label_id     = $value['label_id'];
					if($label_id=='2'){
						$apparel_cost_labelval=number_format((float)$value['label_values'] ,2);
					}
                    $label_values += str_replace(",","",$value['label_values']);
					$label_values = str_replace(",","",$label_values);
                    $label_name   = $value['label_name'];
                    $cost_slug   = $value['cost_slug'];
                    $is_checked   = $value['is_checked'];
                    $totalItem_sold   = $value['totalItem_sold'];
                    $total_order_price   = $value['total_order_price'];
                    $total_order   = $value['total_order'];
                    $costLabel = ucwords(str_replace('_', ' ', trim($label_name)));
                    $costVal   = str_replace(",","",$value['label_values']);

					if($is_checked=='1'){
						$printCostLabel = str_replace(",","",$value['label_values']) * $totalItem_sold;
						$printCostLabel = str_replace(",","",$printCostLabel);
						$printCost = number_format((float)($printCostLabel-str_replace(",","",$value['label_values'])), 2);
						$checked_lable_cost +=$printCostLabel;
						$checked_lable_cost = str_replace(",","",$checked_lable_cost);
						$html .='<tr><td>'.$costLabel.'</td><td style="color: #ff0000;">$'.number_format((float)$printCostLabel, 2).'</td></tr>';
					}else{
						
						$unchecked_lable_cost += str_replace(",","",$value['label_values']);
						$unchecked_lable_cost = str_replace(",","",$unchecked_lable_cost);

						$total_card_fee=0.00;
						if($label_id=='12'){
							$card_fee= $total_order_price*2.9/100;
							$no_of_order_fee=$total_order * 0.30;
							$total_card_fee=$card_fee + $no_of_order_fee;
							$total_card_fee=number_format((float)$total_card_fee, 2);
							$total_card_fee = str_replace(",","",$total_card_fee);
							$craditcardfee=$total_card_fee;
							$html .='<tr><td>'.$costLabel.'</td><td style="color: #ff0000;">$'.$total_card_fee.'</td></tr>';
						}else{
							$html .='<tr><td>'.$costLabel.'</td><td style="color: #ff0000;">$'.number_format((float)$costVal, 2).'</td></tr>';
						}
						
					}
					if($label_id=='2'){
						if($apparel_cost_labelval ==$apprel_cost){
							$costVal='0.00';
						}
					}
                    $profitCostData = [
                    	'store_master_id' => $_POST['store_master_id'],
                    	'profit_name'     => $costLabel,
                    	'profit_value'    => $costVal,
                    	'profit_label_id' => $label_id
                    ];
					parent::insertTable_f_mdl('store_profit',$profitCostData);
				}
				if($apparel_cost_labelval ==$apprel_cost){
					$is_old_orderupdate='0';
				}else{
					$is_old_orderupdate='1';
				}

				if($is_old_order=='0'){
                    $apprel_cost= $apprel_cost;
                }else{
                    $apprel_cost='0.00';
                }


                if($is_old_order == '0'){

					if($apparel_cost_labelval ==$apprel_cost){
						$costVal='0.00';
					}
                    $total_lable_price = ($checked_lable_cost+$unchecked_lable_cost + $fundraising_amount+$discountAmount+$craditcardfee);
                }else{ 
                    $total_lable_price = ($checked_lable_cost+$unchecked_lable_cost + $fundraising_amount+$discountAmount+$craditcardfee+$apparel_cost_labelval);
                }

				
				$lablrprice   = number_format( (float) $total_lable_price, 2, '.', '');
				$lablrprice = str_replace(",","",$lablrprice);

				$total_profit= (float)$total_gross_sale-$lablrprice;
				$totalProfit  = (float)$total_profit;

				// $total_profit = (float)$total_sale - ((float)$label_values)-($fundraising_amount)-($printCost);
				// $printCost = (float)($total_profit - $printCost);
				$totalProData = [
					'total_profit' =>$totalProfit,
					'is_old_order' =>$is_old_orderupdate
				];
				parent::updateTable_f_mdl('store_master',$totalProData,'id="'.$_POST['store_master_id'].'"');
				$totalProfit  = number_format((float)$totalProfit, 2);
                $html .='<tr><td><strong>Total Profit</strong></td><td style="color: #45A92B;"><strong>$'.$totalProfit.'</strong></td></tr>';
				
				$res['profitHtml'] = $html;
				$res['STATUS']     = "TRUE";
			}
			else{
				$res['STATUS'] = "FALSE";
			}
		}
		else{
			$res['STATUS'] = "FALSE";
		}
		echo json_encode($res);die();	
	}

	public function getStoreProfitDetails($label_id,$store_master_id)
	{
		$storeProfitSql  = 'SELECT * FROM `store_profit`';
		$storeProfitData = parent::selectTable_f_mdl($storeProfitSql);
		return $storeProfitData;die();
	}

	public function editDefaultProfit($store_master_id)
	{	
		$res =  array();
		if (isset($store_master_id) && !empty($store_master_id)) {
			$saleSql = 'SELECT soim.id,soim.store_master_id,sm.store_name AS title,soim.is_deleted,SUM(soim.quantity * soim.price) AS total_sale,som.store_sale_type FROM store_order_items_master AS soim INNER JOIN store_orders_master AS som ON soim.store_orders_master_id = som.id INNER JOIN store_master AS sm ON soim.store_master_id = sm.id WHERE 1 AND soim.is_deleted = 0 AND som.is_order_cancel = "0"  AND som.order_type="1" AND som.store_master_id = '.$store_master_id.' ';
			$saleData = parent::selectTable_f_mdl($saleSql);
			if(!empty($saleData)){
				$total_sale = $saleData[0]['total_sale'];
				$fundSql='SELECT IFNULL(SUM(soim.fundraising_amount),0) as total_fundraising_amount FROM store_order_items_master as soim INNER JOIN store_orders_master as som ON som.id=soim.store_orders_master_id  WHERE som.is_order_cancel ="0" AND soim.is_deleted ="0" AND soim.store_master_id ="'.$store_master_id.'" ';
				$fundData = parent::selectTable_f_mdl($fundSql);
				$fundraising_amount = $fundData[0]['total_fundraising_amount'];
				$res['total_sale']         = $total_sale;
				$res['fundraising_amount'] = $fundraising_amount;
			}
			else{
				$res['total_sale']         = '';
				$res['fundraising_amount'] = '';
			}

			$saleSql2 = 'SELECT soim.id,soim.store_master_id,sm.store_name AS title,soim.is_deleted,SUM(soim.quantity * soim.price) AS total_sale,som.store_sale_type FROM store_order_items_master AS soim INNER JOIN store_orders_master AS som ON soim.store_orders_master_id = som.id INNER JOIN store_master AS sm ON soim.store_master_id = sm.id WHERE 1 AND soim.is_deleted = 0 AND som.is_order_cancel = "0"  AND som.order_type="2" AND som.store_master_id = '.$store_master_id.' ';
			$saleData2 = parent::selectTable_f_mdl($saleSql2);
			$total_sale_manual='';
			if(!empty($saleData2)){
				$total_sale_manual = $saleData2[0]['total_sale'];
				$res['total_sale_manual']         = $total_sale_manual;
			}else{
				$res['total_sale_manual']         = '';
			}

			$saleSql3 = 'SELECT soim.id,soim.store_master_id,sm.store_name AS title,soim.is_deleted,SUM(soim.quantity * soim.price) AS total_sale,som.store_sale_type FROM store_order_items_master AS soim INNER JOIN store_orders_master AS som ON soim.store_orders_master_id = som.id INNER JOIN store_master AS sm ON soim.store_master_id = sm.id WHERE 1 AND soim.is_deleted = 0 AND som.is_order_cancel = "0"  AND som.order_type="3" AND som.store_master_id = '.$store_master_id.' ';
			$saleData3 = parent::selectTable_f_mdl($saleSql3);
			$total_sale_quickbuy='';
			if(!empty($saleData3)){
				$total_sale_quickbuy = $saleData3[0]['total_sale'];
				$res['total_sale_quickbuy']         = $total_sale_quickbuy;
			}else{
				$res['total_sale_quickbuy']         = '';
			}

			$discountCode = 'select SUM(discount_code_amount) as discount_code_amount from store_orders_master  WHERE store_master_id="'.$store_master_id.'" AND is_order_cancel="0" ';
			$discountData = parent::selectTable_f_mdl($discountCode);
			$discountAmount='0.00';
			if(!empty($discountData)){
				$discountAmount = $discountData[0]['discount_code_amount'];
				$res['discountAmount']         = $discountAmount;
			}else{
				$res['discountAmount']         = '0.00';
			}
			
		}
		else{
			$res['total_sale']           = '';
			$res['total_sale_manual']    = '';
			$res['total_sale_quickbuy']  = '';
			$res['fundraising_amount']   = '';
			$res['discountAmount']   = '0.00';
		}
		return $res; die();	
	}

	/* Task 118 start */
	public function getSizeData(){
		$response = storeHelper::getVariantsSize($_POST);
		echo $response;
	}


	public function getPriceData(){
		$response = storeHelper::getVariantsPrice($_POST);
		echo $response;
	}

	public function addToCart(){
		$response = storeHelper::productsAddToCart($_POST);
		echo $response;
	}

	public function getAddToCart(){
		$store_master_id = $_GET['store_master_id'];
		$response = storeHelper::getProductsAddToCart($store_master_id,true);
		return $response;
	}

	public function deleteProductCart(){
		$response = storeHelper::deleteCartProduct($_POST);
		echo $response;
	}

	public function updateToCart(){
		$response = storeHelper::updateProductsToCart($_POST);
		echo $response;
	}

	public function sendEmailOrderData(){
		$response = storeHelper::sendEmailOrderDetails($_POST);
		echo $response;
	}
	/* Task 118 start */

	/* Task 117 start */
	public function getStatusHistory($store_master_id)
	{
		$sql = 'SELECT * FROM store_status_history WHERE store_master_id="'.$store_master_id.'"';
		$status_data = parent::selectTable_f_mdl($sql);

		$html = '';
		if(!empty($status_data)){ 
            foreach ($status_data as $value){
            	$statusName  = '';
            	$source      = $value['created_on'];
				$date        = new DateTime($source);
				$createdDate = $date->format("m/d/Y h:i A");

            	if($value['status']==0){
            		$statusName = 'Created';
            	}elseif($value['status']==1){
            		$statusName = 'Launched';
            	}elseif($value['status']==2){
            		$statusName = 'Rejected';
            	}elseif($value['status']==4) {
            		$statusName = 'SA Approved - Not Launched';
            	}elseif($value['status']==5) {
            		$statusName = 'Duplicated From ('.$value['old_store_name'].')';
            	}elseif($value['status']==6) {
            		$statusName = 'Launched by SA';
            	}elseif($value['status']==7) {
            		$statusName = 'Moved to Production';
            	}elseif($value['status']==8) {
            		$statusName = 'Store Closed';
            	}elseif($value['status']==9) {
            		$statusName = 'Store Opened';
            	}elseif($value['status']==10) {
            		$statusName = 'Created by SA';
            	}
				$html.="<tr>
				<td>".$statusName."</td>
				<td>".$createdDate."</td>
				<td>".$value['updated_by']."</td>
				</tr>";   
            }
        } 
		echo $html;
	}

	public function getProductAdditionalColor()
	{
		global $s3Obj;
		$response = array();
		if (!empty($_POST['product_id'])) {
			$strownSql     = 'SELECT id,store_product_master_id FROM store_owner_product_master WHERE id =' . $_POST['product_id'] . ' ';
			$getStrProduct = parent::selectTable_f_mdl($strownSql);
			if (!empty($getStrProduct)) {
				$storeProductId = $getStrProduct[0]['store_product_master_id'];
			}

			$store_organization_type_master_id = $_POST['store_organization_type_master_id'];
			$sql = 'SELECT `store_product_variant_master`.color, store_product_colors_master.product_color,store_product_colors_master.product_color_name,store_color_family_master.color_image
				FROM `store_product_variant_master`
				LEFT JOIN `store_product_master` ON `store_product_master`.id = `store_product_variant_master`.store_product_master_id
				LEFT JOIN store_product_colors_master ON store_product_colors_master.product_color = store_product_variant_master.color
				LEFT JOIN store_color_family_master ON store_color_family_master.color_family_name = store_product_colors_master.color_family
				WHERE `store_product_variant_master`.store_organization_type_master_id = "' . $store_organization_type_master_id . '"
				AND `store_product_variant_master`.status = "1" AND `store_product_variant_master`.store_product_master_id =' . $storeProductId . '
				AND `store_product_master`.status = "1" GROUP BY `store_product_variant_master`.color';
			$getColor = parent::selectTable_f_mdl($sql);

			$htmlBody = '';
			if (!empty($getColor)) {
				foreach ($getColor as $single_color) {
					$clrfamilyName = $single_color['product_color_name'];
					$clrfamilycode = $single_color['product_color'];
					$colorImage = $single_color['color_image'];

					$htmlBody .= '<div class="checkbox-custom checkbox-primary">';
					$htmlBody .= '<input type="checkbox" class="additional_color_product" value="' . $clrfamilyName . '">';
					if($clrfamilyName == 'Tie-Dye' || $clrfamilyName == 'Tie-Dye Mask'){
						if(!empty($colorImage)){
							$htmlBody .= '<label class="family_color" style=""><img style="width: 18px;height: 18px;border-radius: 50%;border: 1px solid transparent;position: relative;box-shadow: 0 0px 2px #000000;margin-left: 1px;margin-bottom:2px" src="'.$s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$colorImage).'">'.'<span style="margin-left: 4px;">'.$clrfamilyName.'</span>';
						}
						else{
							$htmlBody .= '<label for=""><span class="color_group_span" style="background-color:'.$clrfamilycode.'">&nbsp;&nbsp;&nbsp;&nbsp;</span>'.$clrfamilyName;
						}
					}else{
							$htmlBody .= '<label for=""><span class="color_group_span" style="background-color:' . $clrfamilycode . '">&nbsp;&nbsp;&nbsp;&nbsp;</span>' . $clrfamilyName;
					}
					$htmlBody .= '</label>';
					$htmlBody .= '</div>';
				}
				$response['htmlBody'] = $htmlBody;
			} else {
				$response['htmlBody'] = '';
			}
		} else {
			$response['htmlBody'] = '';
		}
		echo json_encode($response);
		die();
	}

	public function update_product_color()
	{
		$priceAlert='yes';
		$store_master_id = $_POST['store_master_id'];
		$store_owner_product_master_id = $_POST['product_id'];
		$store_organization_type_master_id = $_POST['store_organization_type_master_id'];
		$color_arr = $_POST['color_arr'];

		$sql = 'SELECT * FROM `store_master` WHERE id="' . $store_master_id . '"';
		$store_master_data =  parent::selectTable_f_mdl($sql); 

		$sql = 'SELECT color FROM store_owner_product_variant_master where store_owner_product_master_id = ' . $store_owner_product_master_id . ' group by color';
		$getColor = parent::selectTable_f_mdl($sql);
		foreach ($getColor as $valueColor) {
			$colorarr_get[] = $valueColor['color'];
		}
		$res =  array();
		if (!empty($store_owner_product_master_id)) {
			$productColors = '';
			$productCode = array();
			if (sizeof($color_arr) > 0) {
				foreach ($color_arr as $value) {
					$sql1      = 'SELECT product_color FROM store_product_colors_master where product_color_name = "' . $value . '" ';
					$colorName = parent::selectTable_f_mdl($sql1);
					if (!empty($colorName)) {
						foreach ($colorName as $valueC) {
							$productCode[] = $valueC['product_color'];
						}
					}
				}
			}

			$productCode = array_diff($productCode, $colorarr_get);
			$JsonproductArray = json_encode(array_values($productCode));
			$colorCodeValues  = str_replace(array('[', ']'), '', $JsonproductArray);

			if (isset($colorCodeValues) && !empty($colorCodeValues)) {

				$sql = 'SELECT * FROM `store_owner_product_master` WHERE store_master_id="' . $store_master_id . '" AND id = "' . $store_owner_product_master_id . '" ';
				$pro_list =  parent::selectTable_f_mdl($sql);
				if (!empty($pro_list)) {

					foreach ($pro_list as $single_pro) {
						$storeowner_product_master_id = $single_pro['id'];
						$funddata= 'SELECT fundraising_price FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id ='.$storeowner_product_master_id.' ';
						$fundres =  parent::selectTable_f_mdl($funddata);
						$fundraising_price = 0;
						if(!empty($fundres)){
							$fundraising_price = $fundres[0]['fundraising_price'];
						}
					
						if (!empty($colorCodeValues)) {
							$existSql = 'SELECT * FROM `store_product_variant_master` WHERE store_product_master_id="' . $single_pro['store_product_master_id'] . '" AND color IN(' . $colorCodeValues . ') and store_organization_type_master_id = ' . $_POST["store_organization_type_master_id"] . ' AND is_ver_deleted = "0" ';
						} else {
							$existSql = 'SELECT * FROM `store_product_variant_master` WHERE store_product_master_id="' . $single_pro['store_product_master_id'] . '" and store_organization_type_master_id = ' . $_POST["store_organization_type_master_id"] . ' AND is_ver_deleted = "0"';
						}
						$existData =  parent::selectTable_f_mdl($existSql);

						if (!empty($existData)) {

							$sqlmasterprod = 'SELECT id,is_eligible_sleeve_print from store_product_master where id="'.$single_pro['store_product_master_id'].'" AND is_deleted="0" ';
							$productMasterdata = parent::selectTable_f_mdl($sqlmasterprod);
							$is_eligible_sleeve_print='0';
							if(!empty($productMasterdata)){
								$is_eligible_sleeve_print=$productMasterdata[0]['is_eligible_sleeve_print'];
							}
							//insert product details
							if (isset($store_owner_product_master_id)) {
								$sql = 'SELECT sopvm.front_side_ink_colors_group,sopvm.back_side_ink_colors_group,sopvm.sleeve_ink_color_group,sopvm.is_back_enable_group FROM store_owner_product_master as sopm INNER JOIN store_owner_product_variant_master as sopvm ON sopm.id=sopvm.store_owner_product_master_id WHERE sopm.store_master_id="'.$store_master_id.'" AND sopm.group_name="'.$single_pro['group_name'].'" LIMIT 1';
								$prodInkCostGroup =  parent::selectTable_f_mdl($sql);

								$sqlGroupHistory = 'SELECT changed_sleeve_ink_group_price FROM group_ink_price_history WHERE store_master_id="'.$store_master_id.'" AND  group_name="'.$single_pro['group_name'].'" ORDER BY id DESC LIMIT 1';
								$prodInkCostGroupHistoryData =  parent::selectTable_f_mdl($sqlGroupHistory);
								$prodInkCostGroupHistory='0';
								if(!empty($prodInkCostGroupHistoryData)){
									$prodInkCostGroupHistory = $prodInkCostGroupHistoryData[0]['changed_sleeve_ink_group_price'];
								}

								$sql = 'SELECT * FROM `store_product_variant_master` WHERE store_product_master_id="' . $single_pro['store_product_master_id'] . '" AND color IN(' . $colorCodeValues . ') AND store_organization_type_master_id = ' . $_POST["store_organization_type_master_id"] . ' AND is_ver_deleted = "0" ';
								// print_r($sql);die;
								$var_list =  parent::selectTable_f_mdl($sql);

								if (!empty($var_list)) {
									foreach ($var_list as $var_data) {
										$image = $var_data['image'];

										// Task 42 start
										$sql = 'SELECT price,price_on_demand from store_product_variant_master where id="' . $var_data['id'] . '" AND is_ver_deleted = "0"';
										$storeProductVariantMaster = parent::selectTable_f_mdl($sql);
										
										/*
										* Front-side and back-side price only added with on-demand store
										* Add front-side as per color catridge price into base price
										*/
										$add_cost = 0;
										if(isset($prodInkCostGroup[0]['front_side_ink_colors_group']) && !empty($prodInkCostGroup[0]['front_side_ink_colors_group'])){
											$add_cost += intval($prodInkCostGroup[0]['front_side_ink_colors_group'])-1;
										}else if(isset($store_master_data[0]['front_side_ink_colors']) && !empty($store_master_data[0]['front_side_ink_colors'])){
											$add_cost += intval($store_master_data[0]['front_side_ink_colors'])-1;
										}

										$add_on_cost = 0;
										if(isset($prodInkCostGroup[0]['back_side_ink_colors_group']) && !empty($prodInkCostGroup[0]['back_side_ink_colors_group'])){
											$add_cost   += common::ADD_COST_BACK_SIDE_INK_COLOR+intval($prodInkCostGroup[0]['back_side_ink_colors_group'])-1;
											$add_on_cost = common::ADD_COST_BACK_SIDE_INK_COLOR;
										}else if(isset($store_master_data[0]['back_side_ink_colors']) && !empty($store_master_data[0]['back_side_ink_colors'])){
											$add_cost   += common::ADD_COST_BACK_SIDE_INK_COLOR+intval($store_master_data[0]['back_side_ink_colors'])-1;
											$add_on_cost = common::ADD_COST_BACK_SIDE_INK_COLOR;
										}

										if(isset($prodInkCostGroupHistory) && !empty($prodInkCostGroupHistory)){
											$add_cost += common::ADD_COST_BACK_SIDE_INK_COLOR + intval($prodInkCostGroupHistory)-1;
										}else if(isset($store_master_data[0]['sleeve_ink_colors']) && !empty($store_master_data[0]['sleeve_ink_colors'])){
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
										if (isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type'] == 'SHIP_1_LOCATION_SORT') {
											$fullfilment_type_price = $fullfilment_gold_price;
											//$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_2;
										}else if (isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type'] == 'SHIP_EACH_FAMILY_HOME') {
											$fullfilment_type_price = $fullfilment_platinum_price;
											//$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_3;
										}else if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=='SHIP_1_LOCATION_NOT_SORT'){
											$fullfilment_type_price = $fullfilment_silver_price;
										}
										
										//To do add bussiness login for fullfilmemnt type & fundrising
										$ondemandPrice  = 0;
										$flashSalePrice = 0;
										if (isset($storeProductVariantMaster[0]['price']) && $storeProductVariantMaster[0]['price_on_demand']) {
											$ondemandPrice  = (floatval($storeProductVariantMaster[0]['price_on_demand']) + $add_on_cost);
											$flashSalePrice = $storeProductVariantMaster[0]['price'] + $add_cost + $fullfilment_type_price;
										} else {
											$ondemandPrice  = (floatval($storeProductVariantMaster[0]['price_on_demand']) + $add_on_cost);
											$flashSalePrice = $storeProductVariantMaster[0]['price'] + $add_cost + $fullfilment_type_price;
											//$ondemandPrice  = $var_data['price_on_demand'];
											//$flashSalePrice = $var_data['price'];
										}

										if(!empty($prodInkCostGroup)){
											$front_side_ink_colors_group=trim($prodInkCostGroup[0]['front_side_ink_colors_group']);
											$back_side_ink_colors_group=trim($prodInkCostGroup[0]['back_side_ink_colors_group']);
											$sleeve_ink_color_group=trim($prodInkCostGroup[0]['sleeve_ink_color_group']);
											$is_back_enable_group=trim($prodInkCostGroup[0]['is_back_enable_group']);
										}else{
											$front_side_ink_colors_group=trim($store_master_data[0]['front_side_ink_colors']);
											$back_side_ink_colors_group=trim($store_master_data[0]['back_side_ink_colors']);
											$sleeve_ink_color_group=trim($store_master_data[0]['sleeve_ink_colors']);
											$is_back_enable_group=trim($store_master_data[0]['is_back_enable']);
										}
	

										$sql = 'SELECT id,price,price_on_demand,store_owner_product_master_id FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id="'.$store_owner_product_master_id.'" AND store_organization_type_master_id ='.$_POST["store_organization_type_master_id"].' LIMIT 1 ';								
										$StoreVerData =  parent::selectTable_f_mdl($sql);
										if(!empty($StoreVerData)){
											if($StoreVerData[0]['price']==$flashSalePrice &&  $StoreVerData[0]['price_on_demand']==$ondemandPrice){
												$priceAlert='no';
											}
										}

										if($is_eligible_sleeve_print=='0'){
											if(isset($prodInkCostGroupHistory) && !empty($prodInkCostGroupHistory)){
												$sleevecost = common::ADD_COST_BACK_SIDE_INK_COLOR + intval($prodInkCostGroupHistory)-1;
											}else if(isset($store_master_data[0]['sleeve_ink_colors']) && !empty($store_master_data[0]['sleeve_ink_colors'])){
												$sleevecost = common::ADD_COST_BACK_SIDE_INK_COLOR + intval($store_master_data[0]['sleeve_ink_colors'])-1;
											}
											$flashSalePrice = $flashSalePrice - $sleevecost;

											$sopvm_insert_data = [
												'store_owner_product_master_id'     => $store_owner_product_master_id,
												'store_product_variant_master_id'   => $var_data['id'],
												'store_organization_type_master_id' => $var_data['store_organization_type_master_id'],
												'price'                             => $flashSalePrice,
												'price_on_demand'                   => $ondemandPrice,
												'fundraising_price'					=> $fundraising_price,
												'color'     		                => $var_data['color'],
												'size'      		                => $var_data['size'],
												'image'                             => $var_data['image'],
												'original_image'                    => $var_data['feature_image'],
												'sku' 				                => $var_data['sku'],
												'weight' 			                => $var_data['weight'],
												'front_side_ink_colors_group' 		=> $front_side_ink_colors_group,
												'back_side_ink_colors_group' 		=> $back_side_ink_colors_group,
												'sleeve_ink_color_group' 		    => '0',
												'is_back_enable_group'  	 		=> $is_back_enable_group,
												'status' 			                => '1',
												'created_on' 		                => @date('Y-m-d H:i:s'),
												'created_on_ts' 	                => time()
											];
										}else{
											$sopvm_insert_data = [
												'store_owner_product_master_id'     => $store_owner_product_master_id,
												'store_product_variant_master_id'   => $var_data['id'],
												'store_organization_type_master_id' => $var_data['store_organization_type_master_id'],
												'price'                             => $flashSalePrice,
												'price_on_demand'                   => $ondemandPrice,
												'fundraising_price'					=> $fundraising_price,
												'color'     		                => $var_data['color'],
												'size'      		                => $var_data['size'],
												'image'                             => $var_data['image'],
												'original_image'                    => $var_data['feature_image'],
												'sku' 				                => $var_data['sku'],
												'weight' 			                => $var_data['weight'],
												'front_side_ink_colors_group' 		=> $front_side_ink_colors_group,
												'back_side_ink_colors_group' 		=> $back_side_ink_colors_group,
												'sleeve_ink_color_group' 		    => $sleeve_ink_color_group,
												'is_back_enable_group'  	 		=> $is_back_enable_group,
												'status' 			                => '1',
												'created_on' 		                => @date('Y-m-d H:i:s'),
												'created_on_ts' 	                => time()
											];
										}
										$ger = parent::insertTable_f_mdl('store_owner_product_variant_master', $sopvm_insert_data);
										// print_r($ger);die;
									}
									$sopm_update_data = [
										'is_product_synced_to_collect' => '0'
									];
									parent::updateTable_f_mdl('store_owner_product_master',$sopm_update_data,'id="'.$storeowner_product_master_id.'"');
								}
								//now open store to sync in shopify
								$sm_update_data = [
									'is_products_synced' => '0',
									'is_manual_store_sync' => '1'
								];
								parent::updateTable_f_mdl('store_master', $sm_update_data, 'id="' . $store_master_id . '"');
							}
							$res['SUCCESS'] = 'TRUE';
							$res['priceAlert']=$priceAlert;
							$res['MESSAGE'] = 'Color added successfully.';
						} else {
							$res['SUCCESS'] = 'FALSE';
							$res['priceAlert']=$priceAlert;
							$res['MESSAGE'] = 'Error while inserting addtional color. Please check and try again after some time.';
						}
					}
				} else {
					$res['SUCCESS'] = 'FALSE';
					$res['priceAlert']=$priceAlert;
					$res['MESSAGE'] = 'Error while inserting addtional color. Please check and try again after some time.';
				}
			} else {
				$res['SUCCESS'] = 'FALSE';
				$res['priceAlert']=$priceAlert;
				$res['MESSAGE'] = 'Please select additional color';
			}
			echo json_encode($res);
		}
		die;
	}

	public function change_group_price()
	{
		global $login_user_email;
		$res =  array();
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
		if(!empty($_POST['store_master_id']) && !empty($_POST['group_name'])){
			$store_master_id = $_POST['store_master_id'];
			$group_name      = $_POST['group_name'];
			$changed_price   = $_POST['changed_price'];
			$store_sale_type_master_id = $_POST['store_sale_type_master_id'];

			$sql = 'SELECT id FROM `store_owner_product_master` WHERE store_master_id="' . $store_master_id . '" AND group_name = "'.$group_name.'" ';
			$pro_list =  parent::selectTable_f_mdl($sql);
			$store_owner_product_master_id = [];
			if (!empty($pro_list)) {
				foreach ($pro_list as $single_pro) {
					$store_owner_product_master_id[] = $single_pro['id'];
				}						
			}

			$sql = 'SELECT id,price_on_demand,price,fundraising_price,shop_variant_id,store_owner_product_master_id FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id IN('.implode(",",$store_owner_product_master_id).') ';
			$var_list =  parent::selectTable_f_mdl($sql);
			if (!empty($var_list)) {
				foreach ($var_list as $Varvalue) {
					$input_price = 0;
					if($store_sale_type_master_id==1){
						$sopvm_update_data = [
							'price' => $Varvalue['price']+$changed_price
						];
						$input_price = $Varvalue['price']+$changed_price+$Varvalue['fundraising_price'];
					}
					else{
						$sopvm_update_data = [
							'price_on_demand' => $Varvalue['price_on_demand']+$changed_price
						];
						$input_price = $Varvalue['price_on_demand']+$changed_price+$Varvalue['fundraising_price'];
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
								"id":"gid://shopify/ProductVariant/'.$Varvalue['shop_variant_id'].'",
								"price":"'.( $input_price ).'"
								}
							}';
					$graphql->runByMutation($mutationData,$inputData);

					parent::updateTable_f_mdl('store_owner_product_variant_master',$sopvm_update_data,'id="'.$Varvalue['id'].'"');
				}

				$storeType = '';
				if($store_sale_type_master_id==1){
					$storeType = "Flash Sale";
				}else{
					$storeType = "On-Demand";
				}
				$changeGroupPriceData = [
					'store_master_id' =>$store_master_id,
					'group_name'      =>$group_name,
					'store_type'      =>$storeType,
					'changed_price'   =>$changed_price,
					'created_on'      =>date('Y-m-d H:i:s'),
					"updated_by"      => "Super Admin <br>(".$login_user_email.")",
				];
				parent::insertTable_f_mdl('group_price_history',$changeGroupPriceData);

				$res['SUCCESS'] = 'TRUE';
				$res['MESSAGE'] = 'Price changed successfully.';
			}else{
				$res['SUCCESS'] = 'FALSE';
				$res['MESSAGE'] = 'Product variant not found.';
			}
		}else{
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'Error while inserting store details. Please check and try again after some time.';
		}
		echo json_encode($res);die();	
	}

	public function getChangeGroupPriceHistory()
	{	
		$html = '';
		if(!empty($_POST['store_master_id']) && !empty($_POST['group_name'])){
			$store_master_id = $_POST['store_master_id'];
			$group_name      = $_POST['group_name'];
			$sql = 'SELECT * FROM group_price_history WHERE store_master_id="'.$store_master_id.'" AND group_name ="'.$group_name.'" ';
			$priceData = parent::selectTable_f_mdl($sql);
			if(!empty($priceData)){ 
				foreach ($priceData as $value){
					$source      = $value['created_on'];
					$date        = new DateTime($source);
					$createdDate = $date->format("Y-m-d h:i A");
					$html.="<tr>
					<td>".$value['group_name']."</td>
					<td>".$value['changed_price']."</td>
					<td>".$value['store_type']."</td>
					<td>".$createdDate."</td>
					<td>".$value['updated_by']."</td>
					</tr>";   
				}
			} 
		}
		echo $html;

	}

	/* check selected Product for group*/
	public function checkSelectedGroupProduct(){
		$response = storeHelper::checkSelectedGroupProductHelper($_POST);
		echo $response;
	}

	/* Task 117 start */
	public function UpdateLockStatus(){
		$res =  array();
		if (!empty($_POST['store_master_id'])) {
			$store_master_id=$_POST['store_master_id'];
			$is_lock_status=$_POST['is_lock_status'];
			$sm_update_data = [
				'is_lock_status' => $is_lock_status
			];
			// print_r($sm_update_data);die;
			$update_status=parent::updateTable_f_mdl('store_master', $sm_update_data, 'id="' . $store_master_id . '"');
			if(!empty($update_status)){
				$res['SUCCESS'] = 'TRUE';
				$res['MESSAGE'] = 'Setting saved successfully.';
			}else{
				$res['SUCCESS'] = 'FALSE';
				$res['MESSAGE'] = 'An error occured.';
			}
		}else{	
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'Oops!there is some internal issues occured. Please try after some time.';
		}
		echo json_encode($res);die();
	}

	/* Task 63 start */
	public function save_logo_setting(){
		if(isset($_POST['store_master_id']) && !empty($_POST['store_master_id'])){
			$logo_id       = $_POST['logo_id'];
			$print_size     = (!empty($_POST['print_size']))?$_POST['print_size']:'';
			$prodcolor_logo_array  = (!empty($_POST['prodcolor_logo']))?$_POST['prodcolor_logo']:'';
			$hexValues = array_column($prodcolor_logo_array, 'logo_color_hex');
			$applicable_colors = implode(',',$hexValues);

			$pantone_color_array  = (!empty($_POST['pantone_color']))?$_POST['pantone_color']:'';
			foreach (array_keys($pantone_color_array, '0') as $key) {
				unset($pantone_color_array[$key]);
			}
			$pantone_color = implode(",",$pantone_color_array);
			$print_location = (!empty($_POST['print_location']))?$_POST['print_location']:'';
			return parent::logoSetting_f_mdl($logo_id,$print_size,$pantone_color,$print_location,$applicable_colors);
		}
		die;
	}

	public function save_single_product_setting(){
		$reponseArray =array();
		if(isset($_POST['single_product_id']) && !empty($_POST['single_product_id'])){
			$logo_id       = $_POST['logo_id'];
			$PostlogoId    = $_POST['logo_id'];
			$checkCoordsSql = 'SELECT sopm.store_product_master_id,sdlm.applicable_colors FROM store_owner_product_master as sopm LEFT JOIN logo_coordinates as lc on lc.store_product_master_id = sopm.store_product_master_id LEFT JOIN store_design_logo_master as sdlm on sdlm.print_location = lc.print_location_id WHERE sopm.id = '.$_POST['single_product_id'].' AND sdlm.id = '.$PostlogoId.' ';
			$coordsData = parent::selectTable_f_mdl($checkCoordsSql);
			if(!empty($coordsData)){
				$applicable_colors=$coordsData[0]['applicable_colors'];
				$applicable_color=explode(',',$applicable_colors);
				$JsoncolorArray = json_encode(array_values($applicable_color));
				$colorCodeValues  = str_replace (array('[', ']'), '' , $JsoncolorArray);

				$checkcolorverSql = "SELECT store_owner_product_master_id FROM store_owner_product_variant_master WHERE store_owner_product_master_id = '".$_POST['single_product_id']."' AND color IN ($colorCodeValues) GROUP BY color ";
				$varData = parent::selectTable_f_mdl($checkcolorverSql);
				// echo "<pre>";print_r($varData);
			}else{
				$varData =[];
			}

			if(!empty($varData)){
				$store_product_master_id = '';
				$sql = 'SELECT store_product_master_id,associate_with_logo_id FROM store_owner_product_master WHERE id="'.$_POST['single_product_id'].'" ';
				$logoData = parent::selectTable_f_mdl($sql);
				if(!empty($logoData[0]['associate_with_logo_id'])){
					$existing_ids = explode(',', $logoData[0]['associate_with_logo_id']);
					if(!in_array($PostlogoId, $existing_ids)) {
						$existing_ids[] = $PostlogoId;
					}
					$logoId = implode(',', $existing_ids);
				}else{
					$logoId=$PostlogoId;
				}
				if(!empty($logoData)){
					$store_product_master_id=$logoData[0]['store_product_master_id'];
				}
				$vendorsql = 'SELECT vendor_id FROM store_product_master WHERE id="'.$store_product_master_id.'" ';
				$vendorsqlData = parent::selectTable_f_mdl($vendorsql);
				$vendor_id = '';
				if(!empty($vendorsqlData)){
					$vendor_id = $vendorsqlData[0]['vendor_id'];
				}
				$associateLogoData = ['associate_with_logo_id' => $logoId];
				$UpdateLogoData = parent::updateTable_f_mdl('store_owner_product_master',$associateLogoData,'id="'.$_POST['single_product_id'].'"');

				if($vendor_id=='24'){
					$associateLogoData = ['associate_with_logo_id' => $PostlogoId];
					parent::updateTable_f_mdl('store_owner_product_variant_master',$associateLogoData,'store_owner_product_master_id="'.$_POST['single_product_id'].'"');
				}

				if ($UpdateLogoData['isSuccess'] == 1){
					$reponseArray["isSuccess"] = "1";
					$reponseArray["msg"]       = "Print file assigned successfully.";
				}else{
					if($UpdateLogoData['isSuccess'] == 0){
						$reponseArray["isSuccess"] = "1";
						$reponseArray["msg"]       = "This print file is already associate with this product.";
					}else{
						$reponseArray["isSuccess"] = "0";
						$reponseArray["msg"]       = "Oops!there is some internal issues occured. Please try after some time.";
					}
				}
			}else{
				$reponseArray["isSuccess"] = "0";
				$reponseArray["msg"]       = "The selected products lack either color, coordinates, or both";
			}		
		}else{
			$reponseArray['isSuccess'] = '0';
			$reponseArray['msg'] = 'Oops!there is some internal issues occured. Please try after some time.';
		}
		echo json_encode($reponseArray,1);
		die;
	}

	public function check_logo_setting()
	{
		$logoId = $_POST['logo_id'];
		if (!empty($logoId)) {
			$res  = array();
			$sql  = 'SELECT print_size, pantone_color, print_location FROM store_design_logo_master WHERE id = '.$logoId.'';
			$data = parent::selectTable_f_mdl($sql);
			if(!empty($data)){
				if($data[0]['print_location'] == ''){
					$res['isSuccess'] = '1';
					$res['msg'] = 'Please set the logo setting';
				}
				if(isset($_POST['product_id']) && !empty($_POST['product_id'])){
					$checkCoordsSql = 'SELECT sopm.store_product_master_id FROM store_owner_product_master as sopm LEFT JOIN logo_coordinates as lc on lc.store_product_master_id = sopm.store_product_master_id WHERE sopm.id = '.$_POST['product_id'].' AND lc.print_location_id = '.$data[0]["print_location"].' ';
					$coordsData = parent::selectTable_f_mdl($checkCoordsSql);
					if (empty($coordsData)) {
						$res['isSuccess'] = '1';
						$res['msg'] = 'Please set correct location';
					}
				}
			}
			else{ 
				$res['isSuccess'] = '0';
			}
			echo json_encode($res);
		}
	}

	public function save_global_setting()
	{
		/* Remove all images from local */
		self::execInBackground(common::SITE_URL . "remove-images-background.php");
		$reponseArray = array();
		global $path;
		global $s3Obj;
		$message='';
		$res =  array();
		$valueArray =  array();
		$valueArrayENG =  array();
		$store_master_id    = (!empty($_POST['store_master_id'])) ? $_POST['store_master_id'] : '';
		$logowidth_inchglobal    = (!empty($_POST['logowidth_inchglobal'])) ? $_POST['logowidth_inchglobal'] : '';
		if (isset($store_master_id) && !empty($store_master_id)) {
			$res = array();
			$res['resultData']=$res['resultDataENG']=[];
			if (!empty($_POST['groupData'])) {
				foreach ($_POST['groupData'] as $value) {
					$logoId         = $value['logo_id'];
					$PostlogoId     = $value['logo_id'];
					$prductsGroupId = explode(',', $value['prducts_groups']);
					$JsonGroupArray = json_encode(array_values($prductsGroupId));
					$groupNames     = str_replace(array('[', ']'), '', $JsonGroupArray);

					$grouparray = explode(',', $groupNames);
					// Remove the unwanted value ("Engraving")
					$group_array = array_diff($grouparray, array('"Engraving"'));
					$group_array = array_values($group_array);
					$groupnameWithoutEngraving = implode(',', $group_array);

					foreach ($prductsGroupId as $value2) {
						$groupName = $value2;
					$groupItemSql = 'SELECT sopm.id FROM store_owner_product_master as sopm  LEFT JOIN logo_coordinates as lc ON lc.store_product_master_id = sopm.store_product_master_id WHERE sopm.group_name="' . $groupName . '" and sopm.store_master_id = "' . $_POST['store_master_id'] . '" AND sopm.is_soft_deleted="0" ';
						$groupItemDetails = parent::selectTable_f_mdl($groupItemSql);
						$dataIds = array();
						if (!empty($groupItemDetails)) {
							foreach ($groupItemDetails as $value1) {
								$dataIds[] = $value1['id'];
							}
						}
						
						$i = 0;
						if (!empty($dataIds)) {
							foreach ($dataIds as $value) {
								$checkCoordsSql = 'SELECT sopm.store_product_master_id,sdlm.applicable_colors FROM store_owner_product_master as sopm LEFT JOIN logo_coordinates as lc on lc.store_product_master_id = sopm.store_product_master_id LEFT JOIN store_design_logo_master as sdlm on sdlm.print_location = lc.print_location_id WHERE sopm.id = '.$value.' AND sdlm.id = '.$PostlogoId.' ';
								$coordsData = parent::selectTable_f_mdl($checkCoordsSql);
								if(!empty($coordsData)){
									$applicable_colors=$coordsData[0]['applicable_colors'];
									$applicable_color=explode(',',$applicable_colors);
									$JsoncolorArray = json_encode(array_values($applicable_color));
									$colorCodeValues  = str_replace (array('[', ']'), '' , $JsoncolorArray);

									$checkcolorverSql = "SELECT store_owner_product_master_id FROM store_owner_product_variant_master WHERE store_owner_product_master_id = '".$value."' AND color IN ($colorCodeValues) GROUP BY color ";
									$varData = parent::selectTable_f_mdl($checkcolorverSql);
									// echo "<pre>";print_r($varData);
								}else{
									$varData =[];
								}
								if(!empty($varData)){
									$sql = 'SELECT associate_with_logo_id FROM store_owner_product_master WHERE id="'.$value.'" ';
									$logoData = parent::selectTable_f_mdl($sql);
									if(!empty($logoData[0]['associate_with_logo_id'])){
										$existing_ids = explode(',', $logoData[0]['associate_with_logo_id']);
										if(!in_array($PostlogoId, $existing_ids)) {
											$existing_ids[] = $PostlogoId;
										}
										$logoId = implode(',', $existing_ids);
									}else{
										$logoId=$PostlogoId;
									}
									$logoData1 = [
										'associate_with_logo_id' => $logoId
									];
									
									$UpdateResponse = parent::updateTable_f_mdl('store_owner_product_master', $logoData1, 'id="' . $value . '"');
								
									if ($i == 0) {
										if ($UpdateResponse['isSuccess'] == 1) {
											$res["isSuccess"] = "1";
											$res["msg"]       = "Changes saved successfully.";
										} else {
											if ($UpdateResponse['isSuccess'] == 0) {
												$res["isSuccess"] = "1";
												$res["msg"]       = "This logo is already associate with this group.";
											} else {
												$res["isSuccess"] = "0";
												$res["msg"]       = "Oops!there is some internal issues occured. Please try after some time.";
											}
										}
									}
								}
								$i++;
							}
						}
					}
					// sleep(0.5);
					$top_coordinates = $left_coordinates = $logo_width = $logo_height = $area_top_coordinates=$area_left_coordinates=$area_width = $area_height = '';

					 $allProduct = 'SELECT 
						    store_owner_product_master.associate_with_logo_id,
						    store_owner_product_master.product_title,
						    store_owner_product_master.store_master_id,
						    store_owner_product_master.id AS store_owner_product_master_id,
						    store_owner_product_master.store_product_master_id AS store_product_master_id,
						    store_design_logo_master.logo_image,
						    store_design_logo_master.print_location,
						    logo_coordinates.top_coordinates,
						    logo_coordinates.left_coordinates,
						    logo_coordinates.logo_width,
						    logo_coordinates.logo_height,
						    sopvm.id,
						    sopvm.color,
						    sopvm.store_owner_product_master_id,
						    sopvm.original_image,
						    sopvm.size,
						    ac.area_top_coordinates,
						    ac.area_left_coordinates,
						    store_design_logo_master.applicable_colors
						FROM
						    `store_owner_product_master`
						        INNER JOIN
						    logo_coordinates ON logo_coordinates.store_product_master_id = store_owner_product_master.store_product_master_id
						        INNER JOIN
						    store_design_logo_master ON store_design_logo_master.id IN ('.$PostlogoId.')
						        AND store_design_logo_master.print_location = logo_coordinates.print_location_id
						        LEFT JOIN
						    `store_owner_product_variant_master` AS sopvm ON sopvm.store_owner_product_master_id = store_owner_product_master.id
						        LEFT JOIN
						    area_coordinates AS ac ON ac.store_product_master_id = store_owner_product_master.store_product_master_id
						WHERE
						    store_owner_product_master.store_master_id = "'.$store_master_id.'"
						        AND store_owner_product_master.group_name IN ('.$groupnameWithoutEngraving.')
						        AND store_owner_product_master.is_soft_deleted = "0"
						        AND sopvm.original_image !=""
						        AND FIND_IN_SET("'.$PostlogoId.'", store_owner_product_master.associate_with_logo_id) > 0
						GROUP BY (SELECT applicable_colors FROM store_design_logo_master WHERE id = '.$PostlogoId.') , store_owner_product_master.id
						';

					$products = parent::selectTable_f_mdl($allProduct);
					// print_r($products);die;

					if (!empty($products)) {
						foreach ($products as $key => $value) {
							$print_location_id = (!empty($value['print_location']))?$value['print_location']:0;
							$store_owner_product_master_id = $value['store_owner_product_master_id'];
							$store_product_master_id = $value['store_product_master_id'];

							$sqlprintloc = 'SELECT default_title,print_location FROM `print_locations` WHERE id = "'.$print_location_id.'"';
							$printlocResult = parent::selectTable_f_mdl($sqlprintloc);
							if(!empty($printlocResult)){
								$print_location_name	= $printlocResult[0]['print_location'];
								$default_title			= $printlocResult[0]['default_title'];
							}

							$coordsSql = 'SELECT * FROM logo_coordinates as lc LEFT JOIN area_coordinates as ac ON lc.store_product_master_id = ac.store_product_master_id WHERE lc.store_product_master_id = '.$store_product_master_id.' AND lc.print_location_id = '.$print_location_id.' ';
							$coordsResult = parent::selectTable_f_mdl($coordsSql);

							if(isset($coordsResult)  && !empty($coordsResult)){
								$top_coordinates  		= $coordsResult[0]['top_coordinates'];
								$left_coordinates 		= $coordsResult[0]['left_coordinates'];
								$logo_width       		= $coordsResult[0]['logo_width'];
								$logo_height      		= $coordsResult[0]['logo_height'];
								$area_top_coordinates  	= $coordsResult[0]['area_top_coordinates'];
								$area_left_coordinates 	= $coordsResult[0]['area_left_coordinates'];
								$area_width       		= $coordsResult[0]['area_width'];
								$area_height      		= $coordsResult[0]['area_height'];
							}

							$masterProSql = 'SELECT * FROM store_product_master as spm LEFT JOIN fulfillengine_products_master as fpm ON fpm.catalog_product_id = spm.vendor_product_id WHERE spm.id = "'.$store_product_master_id.'" GROUP BY fpm.catalog_product_id ';
							$masterProSqlResult = parent::selectTable_f_mdl($masterProSql);

							$sql_vendor = 'SELECT id,vendor_name FROM `store_vendors_master` WHERE id = "'.$masterProSqlResult[0]['vendor_id'].'" ';
							$vendorNameData = parent::selectTable_f_mdl($sql_vendor);
							$product_vendor='';
							if(!empty($vendorNameData)){
								$product_vendor=$vendorNameData[0]['vendor_name'];
							}
							$applicable_color=explode(',',$value['applicable_colors']);
							$JsoncolorArray = json_encode(array_values($applicable_color));
							$colorCodeValues  = str_replace (array('[', ']'), '' , $JsoncolorArray);


							$sqlvar='SELECT * FROM store_owner_product_variant_master WHERE store_owner_product_master_id ="'.$store_owner_product_master_id.'" AND color IN ('.$colorCodeValues.') GROUP BY color';
							$variantData = parent::selectTable_f_mdl($sqlvar);
							if(!empty($variantData)){
								foreach($variantData as $singl_variant){

								
									$imageUrl = '';
									if (!empty($singl_variant['original_image'])) {
										$filename = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $singl_variant['original_image']);
										$tmpPath = $path . common::IMAGE_UPLOAD_S3_PATH . $singl_variant['original_image'];
										@file_put_contents($tmpPath, @file_get_contents($filename));
										$imageUrl = common::SITE_URL . common::IMAGE_UPLOAD_S3_PATH . $singl_variant['original_image'];
									}

									$logoUrl = '';
									if (!empty($value['logo_image'])) {
										$logoImage = $value['logo_image'];
										$logoFile = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $logoImage);
										$tmpPath1 = $path . common::IMAGE_UPLOAD_S3_PATH . $logoImage;
										@file_put_contents($tmpPath1, @file_get_contents($logoFile));
										$logoUrl = common::SITE_URL . common::IMAGE_UPLOAD_S3_PATH . $logoImage;

									 	// Get image data from URL
							            $ActualLogowidth=$ActualLogoheight='';
							            if(!empty($logoUrl)){
											$imagegetData 		= file_get_contents($logoUrl);
											$logoImgimage 		= imagecreatefromstring($imagegetData);
											$imageInfo 			= getimagesize($logoUrl);
											$ActualLogowidth 	= $imageInfo[0];
											$ActualLogoheight 	= $imageInfo[1];
											$dpi 				= imageresolution($logoImgimage);
											$logowidth_inch		= $ActualLogowidth/$dpi[0];
											$logoheight_inch	= $ActualLogoheight/$dpi[0];
											$logo_dpi			= $dpi[0];
											$logowidth_inch 	= number_format($logowidth_inch,'2','.','');
											$logoheight_inch 	= number_format($logoheight_inch,'2','.','');
										}
									}
									$maxwidthinch=$maxheightinch='';
									
									if($product_vendor=='FulfillEngine'){
										if(!empty($masterProSqlResult) && !empty($print_location_name)){
											$maxwidthinch  = $masterProSqlResult[0][$print_location_name . "_width"];
											$maxheightinch = $masterProSqlResult[0][$print_location_name . "_height"];
											
											$maxwidthinch 			= !empty($maxwidthinch) ? $maxwidthinch : 1;
											$maxheightinch 			= !empty($maxheightinch) ? $maxheightinch : 1;
											$perinch_pixel_width 	= ceil($area_width / $maxwidthinch);
											$perinch_pixel_height 	= ceil($area_height / $maxheightinch);
											$original_width 		= $ActualLogowidth; // in pixels
											$original_height 		= $ActualLogoheight; // in pixels
											if($maxwidthinch >= $logowidth_inchglobal){
												$resized_width 			= $logowidth_inchglobal * $perinch_pixel_width;
											}else{
												$resized_width 			= $maxwidthinch * $perinch_pixel_width;
											}
											$resized_width 			= number_format($resized_width , 2);  // in pixels
											$scaling_factor 		= $resized_width / $original_width;
											// Calculate the new height based on the scaling factor
											$resized_height 		= $original_height * $scaling_factor;
											$resized_height 		= number_format($resized_height , 2);
											$pixels_per_inch 		= $perinch_pixel_width; 
											// Calculate the width and height in inches
											$width_in_inches 		= number_format($resized_width / $pixels_per_inch , 2);
											$height_in_inches 		= number_format($resized_height / $pixels_per_inch , 2);

											// Calculate left coordinate for alignment
											if($print_location_name=='left_chest'){
												$left_coordinate = $area_width - $resized_width;
											}else if($print_location_name=='right_chest'){
												$left_coordinate = 0;
											}else if($print_location_name=='front'){
												$left_coordinate = ($area_width - $resized_width) / 2;
											}else{
												$left_coordinate = ($area_width - $resized_width) / 2;
											}

											$update_logowidthdata = [
												'associate_with_logo_id' =>trim($PostlogoId),
												'assign_logo_width' => trim($resized_width),
												'assign_logo_height' => trim($resized_height),
												'assign_logo_leftcoordinates' => trim($left_coordinate),
												'assign_logo_topcoordinates' => trim($value['top_coordinates']),
												'assign_logo_heightinch' => trim($height_in_inches),
												'assign_logo_widthinch' => trim($width_in_inches),
											];
											parent::updateTable_f_mdl('store_owner_product_variant_master',$update_logowidthdata,'store_owner_product_master_id="'.$value['store_owner_product_master_id'].'" AND color="'.$singl_variant['color'].'" ');
											$valueArray[] = array(
												"store_master_id"  				=> $value['store_master_id'],
												'product_title'    				=> $value['product_title'],
												"print_location"   				=> $value['print_location'],
												'IMAGE_URL'        				=> $imageUrl,
												'LOGO_URL'         				=> $logoUrl,
												'TOP_COORDINATES'  				=> $value['top_coordinates'],
												'LEFT_COORDINATES' 				=> $left_coordinate,
												'LOGO_WIDTH'       				=> $resized_width,
												'LOGO_HEIGHT'      				=> $logo_height,
												'LOGO_WIDTH_INCH'  				=> $width_in_inches,
												'LOGO_HEIGHT_INCH' 				=> $resized_height,
												'AREA_TOP_COORDINATES'  		=> $value['area_top_coordinates'],
												'AREA_LEFT_COORDINATES' 		=> $value['area_left_coordinates'],
												'COLOR'            				=> $singl_variant['color'],
												'store_owner_product_master_id' => $value['store_owner_product_master_id'],
											);
											
										}
									}else{
										$update_logowidthdata = [
											'associate_with_logo_id' =>trim($PostlogoId),
											'assign_logo_width' => trim($value['logo_width']),
											'assign_logo_height' => trim($value['logo_height']),
											'assign_logo_leftcoordinates' => trim($value['left_coordinates']),
											'assign_logo_topcoordinates' => trim($value['top_coordinates']),
											'assign_logo_heightinch' => trim($logowidth_inch),
											'assign_logo_widthinch' => trim($logoheight_inch),
										];
										parent::updateTable_f_mdl('store_owner_product_variant_master',$update_logowidthdata,'store_owner_product_master_id="'.$value['store_owner_product_master_id'].'" AND color="'.$singl_variant['color'].'" ');

										$valueArray[] = array(
											"store_master_id"  => $value['store_master_id'],
											'product_title'    => $value['product_title'],
											"print_location"   => $value['print_location'],
											'IMAGE_URL'        => $imageUrl,
											'LOGO_URL'         => $logoUrl,
											'TOP_COORDINATES'  => $value['top_coordinates'],
											'LEFT_COORDINATES' => $value['left_coordinates'],
											'LOGO_WIDTH'       => $value['logo_width'],
											'LOGO_HEIGHT'      => $value['logo_height'],
											'LOGO_WIDTH_INCH' => $logowidth_inch,
											'LOGO_HEIGHT_INCH' => $logoheight_inch,
											'AREA_TOP_COORDINATES'  => $value['area_top_coordinates'],
											'AREA_LEFT_COORDINATES' => $value['area_left_coordinates'],
											'COLOR'            => $singl_variant['color'],
											'store_owner_product_master_id' => $value['store_owner_product_master_id'],
										);
									}
								}

							}
						}
						
						$res['SUCCESS']  = true;
						$res['resultData'] = $valueArray;
						$res['MESSAGE'] = $message;

					} else {
						$res['SUCCESS']    = false;
						$res['resultData'] = $valueArray;
						$res['MESSAGE'] = 'The selected products/groups lack either color, coordinates, or both.';
					}
					
					$key = array_search('"Engraving"', $grouparray);
					if ($key !== false) {
						$allProduct = 'SELECT  
							store_owner_product_master.associate_with_logo_id,
							store_owner_product_master.product_title,
							store_owner_product_master.store_master_id,
							store_owner_product_master.id AS store_owner_product_master_id,
							store_owner_product_master.store_product_master_id AS store_product_master_id,
							store_design_logo_master.logo_image,
							store_design_logo_master.print_location,
							logo_coordinates.top_coordinates,
							logo_coordinates.left_coordinates,
							logo_coordinates.logo_width,
							logo_coordinates.logo_height,
							sopvm.id,
							sopvm.color,
							sopvm.store_owner_product_master_id,
							sopvm.original_image,
							sopvm.size,
							ac.area_top_coordinates,
							ac.area_left_coordinates,
							store_design_logo_master.applicable_colors
							FROM
							`store_owner_product_master`
								INNER JOIN
							logo_coordinates ON logo_coordinates.store_product_master_id = store_owner_product_master.store_product_master_id
								INNER JOIN
						    store_design_logo_master ON store_design_logo_master.id IN ('.$PostlogoId.')
						        AND store_design_logo_master.print_location = logo_coordinates.print_location_id
								LEFT JOIN
							`store_owner_product_variant_master` AS sopvm ON sopvm.store_owner_product_master_id = store_owner_product_master.id
							LEFT JOIN
						    area_coordinates AS ac ON ac.store_product_master_id = store_owner_product_master.store_product_master_id
							WHERE
							store_owner_product_master.store_master_id = "'.$store_master_id.'"
								AND store_owner_product_master.group_name IN("Engraving")
								AND store_owner_product_master.is_soft_deleted = "0"
								AND sopvm.original_image !=""
						        AND FIND_IN_SET("'.$PostlogoId.'", store_owner_product_master.associate_with_logo_id) > 0
								GROUP BY (SELECT applicable_colors FROM store_design_logo_master WHERE id = '.$PostlogoId.') , store_owner_product_master.id
								
						';
						$productsEng = parent::selectTable_f_mdl($allProduct);
						if (!empty($productsEng)) {
							foreach ($productsEng as $key => $value) {

								$print_location_id 				= (!empty($value['print_location']))?$value['print_location']:0;
								$store_owner_product_master_id 	= $value['store_owner_product_master_id'];
								$store_product_master_id 		= $value['store_product_master_id'];

								$sqlprintloc = 'SELECT default_title,print_location FROM `print_locations` WHERE id = "'.$print_location_id.'"';
								$printlocResult = parent::selectTable_f_mdl($sqlprintloc);
								if(!empty($printlocResult)){
									$print_location_name	= $printlocResult[0]['print_location'];
									$default_title			= $printlocResult[0]['default_title'];
								}

								$coordsSql = 'SELECT * FROM logo_coordinates as lc LEFT JOIN area_coordinates as ac ON lc.store_product_master_id = ac.store_product_master_id WHERE lc.store_product_master_id = '.$store_product_master_id.' AND lc.print_location_id = '.$print_location_id.' ';
								$coordsResult = parent::selectTable_f_mdl($coordsSql);
								if(isset($coordsResult)  && !empty($coordsResult)){
									$top_coordinates  		= $coordsResult[0]['top_coordinates'];
									$left_coordinates 		= $coordsResult[0]['left_coordinates'];
									$logo_width       		= $coordsResult[0]['logo_width'];
									$logo_height      		= $coordsResult[0]['logo_height'];
									$area_top_coordinates  	= $coordsResult[0]['area_top_coordinates'];
									$area_left_coordinates 	= $coordsResult[0]['area_left_coordinates'];
									$area_width       		= $coordsResult[0]['area_width'];
									$area_height      		= $coordsResult[0]['area_height'];
								}

								$masterProSql = 'SELECT * FROM store_product_master as spm LEFT JOIN fulfillengine_products_master as fpm ON fpm.catalog_product_id = spm.vendor_product_id WHERE spm.id = "'.$store_product_master_id.'" GROUP BY fpm.catalog_product_id ';
								$masterProSqlResult = parent::selectTable_f_mdl($masterProSql);

								$sql_vendor = 'SELECT id,vendor_name FROM `store_vendors_master` WHERE id = "'.$masterProSqlResult[0]['vendor_id'].'" ';
								$vendorNameData = parent::selectTable_f_mdl($sql_vendor);
								$product_vendor='';
								if(!empty($vendorNameData)){
									$product_vendor = $vendorNameData[0]['vendor_name'];
								}

								$applicable_color=explode(',',$value['applicable_colors']);
								$JsoncolorArray = json_encode(array_values($applicable_color));
								$colorCodeValues  = str_replace (array('[', ']'), '' , $JsoncolorArray);

								$sqlvar='SELECT * FROM store_owner_product_variant_master WHERE store_owner_product_master_id ="'.$store_owner_product_master_id.'" AND color IN ('.$colorCodeValues.') GROUP BY color';
								$variantData = parent::selectTable_f_mdl($sqlvar);
								if(!empty($variantData)){
									foreach($variantData as $singl_variant){
										$imageUrl = '';
										if (!empty($singl_variant['original_image'])) {
											$filename = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $singl_variant['original_image']);
											$tmpPath = $path . common::IMAGE_UPLOAD_S3_PATH . $singl_variant['original_image'];
											@file_put_contents($tmpPath, @file_get_contents($filename));
											$imageUrl = common::SITE_URL . common::IMAGE_UPLOAD_S3_PATH . $singl_variant['original_image'];
										}
			
										$logoUrl = '';
										if (!empty($value['logo_image'])) {
											$logoImage = $value['logo_image'];
											$logoFile = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $logoImage);

											$headers = get_headers($logoFile);
											if($headers && strpos($headers[0], '200')){
											}else{
												die('Image file not found.');
											}
											// Determine image type (JPEG or PNG) based on file extension
											$imageType = pathinfo($logoFile, PATHINFO_EXTENSION);
											// Create image resource from the input image based on image type
											if ($imageType === 'jpg' || $imageType === 'jpeg') {
												$inputImage = imagecreatefromjpeg($logoFile);
											} elseif ($imageType === 'png') {
												$inputImage = imagecreatefrompng($logoFile);
											} else {
												die('Unsupported image type.');
											}
											// Check if image creation was successful
											if (!$inputImage) {
												die('Unable to create image from file.');
											}
											// Get image dimensions
											$width = imagesx($inputImage);
											$height = imagesy($inputImage);
											// Create a new image with true color
											$outputImage = imagecreatetruecolor($width, $height);
											// Check if image creation was successful
											if (!$outputImage) {
												die('Unable to create true color image.');
											}

											imagesavealpha($outputImage, true);
											imagealphablending($outputImage, false);
											// Set the alpha channel for the entire image to fully transparent
											$transparentColor = imagecolorallocatealpha($outputImage, 0, 0, 0, 127);
											imagefill($outputImage, 0, 0, $transparentColor);
											// Copy the input image onto the transparent image without blending
											imagecopy($outputImage, $inputImage, 0, 0, 0, 0, $width, $height);
											// Apply a color filter to change the color to silver
											imagefilter($outputImage, IMG_FILTER_GRAYSCALE);
											//imagefilter($outputImage, IMG_FILTER_BRIGHTNESS, 30); // Increase brightness
											imagefilter($outputImage, IMG_FILTER_COLORIZE, 192, 192, 192); // Colorize to silver
											$destination_folder = $path . common::IMAGE_UPLOAD_S3_PATH . "eng_".$logoImage;

											// Save the modified image to a file based on image type
											if ($imageType === 'jpg' || $imageType === 'jpeg') {
												imagejpeg($outputImage, $destination_folder);
											} elseif ($imageType === 'png') {
												imagepng($outputImage, $destination_folder);
											}
											// Destroy the image resources to free up memory
											imagedestroy($inputImage);
											imagedestroy($outputImage);
											$logoUrleng = common::SITE_URL . common::IMAGE_UPLOAD_S3_PATH ."eng_".$logoImage;

											// Get image data from URL
											$ActualLogowidth=$ActualLogoheight='';
											if(!empty($logoUrleng)){
												$imagegetData 		= file_get_contents($logoUrleng);
												$logoImgimage 		= imagecreatefromstring($imagegetData);
												$imageInfo 			= getimagesize($logoUrleng);
												$ActualLogowidth 	= $imageInfo[0];
												$ActualLogoheight 	= $imageInfo[1];
												$dpi 				= imageresolution($logoImgimage);
												$logowidth_inch		= $ActualLogowidth/$dpi[0];
												$logoheight_inch	= $ActualLogoheight/$dpi[0];
												$logo_dpi			= $dpi[0];
			
												$logowidth_inch     = number_format($logowidth_inch,'2','.','');
												$logoheight_inch 	= number_format($logoheight_inch,'2','.','');
											}
										}

										$maxwidthinch=$maxheightinch='';
										if($product_vendor=='FulfillEngine'){
											if(!empty($masterProSqlResult) && !empty($print_location_name)){
												$maxwidthinch  = $masterProSqlResult[0][$print_location_name . "_width"];
												$maxheightinch = $masterProSqlResult[0][$print_location_name . "_height"];
												
												$maxwidthinch 			= !empty($maxwidthinch) ? $maxwidthinch : 1;
												$maxheightinch 			= !empty($maxheightinch) ? $maxheightinch : 1;
												$perinch_pixel_width 	= ceil($area_width / $maxwidthinch);
												$perinch_pixel_height 	= ceil($area_height / $maxheightinch);
												
												$original_width 		= $ActualLogowidth; // in pixels
												$original_height 		= $ActualLogoheight; // in pixels
												if($maxwidthinch >= $logowidth_inchglobal){
													$resized_width 			= $logowidth_inchglobal * $perinch_pixel_width;
												}else{
													$resized_width 			= $maxwidthinch * $perinch_pixel_width;
												}
												$resized_width 			= number_format($resized_width , 2);  // in pixels
												$scaling_factor 		= $resized_width / $original_width;
												// Calculate the new height based on the scaling factor
												$resized_height 		= $original_height * $scaling_factor;
												$resized_height 		= number_format($resized_height , 2);
												$pixels_per_inch 		= $perinch_pixel_width; 
												// Calculate the width and height in inches
												$width_in_inches 		= number_format($resized_width / $pixels_per_inch , 2);
												$height_in_inches 		= number_format($resized_height / $pixels_per_inch , 2);

												// Calculate left coordinate for alignment
												if($print_location_name=='left_chest'){
													$left_coordinate = $area_width - $resized_width;
												}else if($print_location_name=='right_chest'){
													$left_coordinate = 0;
												}else if($print_location_name=='front'){
													$left_coordinate = ($area_width - $resized_width) / 2;
												}else{
													$left_coordinate = ($area_width - $resized_width) / 2;
												}
											
												$update_logowidthdata = [
													'associate_with_logo_id' =>trim($PostlogoId),
													'assign_logo_width' => trim($resized_width),
													'assign_logo_height' => trim($resized_height),
													'assign_logo_leftcoordinates' => trim($left_coordinate),
													'assign_logo_topcoordinates' => trim($value['top_coordinates']),
													'assign_logo_heightinch' => trim($height_in_inches),
													'assign_logo_widthinch' => trim($width_in_inches),
												];
												parent::updateTable_f_mdl('store_owner_product_variant_master',$update_logowidthdata,'store_owner_product_master_id="'.$value['store_owner_product_master_id'].'" AND color="'.$singl_variant['color'].'" ');

												$valueArrayENG[] = array(
													"store_master_id"  				=> $value['store_master_id'],
													'product_title'    				=> $value['product_title'],
													"print_location"   				=> $value['print_location'],
													'IMAGE_URL'        				=> $imageUrl,
													'LOGO_URL'         				=> $logoUrleng,
													'TOP_COORDINATES'  				=> $value['top_coordinates'],
													'LEFT_COORDINATES' 				=> $left_coordinate,
													'LOGO_WIDTH'       				=> $resized_width,
													'LOGO_HEIGHT'      				=> $logo_height,
													'LOGO_WIDTH_INCH' 				=> $width_in_inches,
													'LOGO_HEIGHT_INCH' 				=> $resized_height,
													'AREA_TOP_COORDINATES'  		=> $value['area_top_coordinates'],
													'AREA_LEFT_COORDINATES' 		=> $value['area_left_coordinates'],
													'COLOR'            				=> $singl_variant['color'],
													'store_owner_product_master_id' => $value['store_owner_product_master_id'],
												);
												
											}
										}else{

											$update_logowidthdata = [
												'associate_with_logo_id' 		=>trim($PostlogoId),
												'assign_logo_width' 			=> trim($value['logo_width']),
												'assign_logo_height' 			=> trim($value['logo_height']),
												'assign_logo_leftcoordinates' 	=> trim($value['left_coordinates']),
												'assign_logo_topcoordinates' 	=> trim($value['top_coordinates']),
												'assign_logo_heightinch' 		=> trim($logowidth_inch),
												'assign_logo_widthinch' 		=> trim($logoheight_inch),
											];
											parent::updateTable_f_mdl('store_owner_product_variant_master',$update_logowidthdata,'store_owner_product_master_id="'.$value['store_owner_product_master_id'].'" AND color="'.$singl_variant['color'].'" ');
			
											$valueArrayENG[] = array(
												"store_master_id"  				=> $value['store_master_id'],
												'product_title'    				=> $value['product_title'],
												"print_location"   				=> $value['print_location'],
												'IMAGE_URL'        				=> $imageUrl,
												'LOGO_URL'         				=> $logoUrleng,
												'TOP_COORDINATES'  				=> $value['top_coordinates'],
												'LEFT_COORDINATES' 				=> $value['left_coordinates'],
												'LOGO_WIDTH'       				=> $value['logo_width'],
												'LOGO_HEIGHT'      				=> $value['logo_height'],
												'LOGO_WIDTH_INCH' 				=> $logowidth_inch,
												'LOGO_HEIGHT_INCH' 				=> $logoheight_inch,
												'AREA_TOP_COORDINATES'  		=> $value['area_top_coordinates'],
												'AREA_LEFT_COORDINATES' 		=> $value['area_left_coordinates'],
												'COLOR'            				=> $singl_variant['color'],
												'store_owner_product_master_id' => $value['store_owner_product_master_id'],
											);
										}

									}
								}

							}
							$res['SUCCESS']  = true;
							$res['resultDataENG'] = $valueArrayENG;
							$res['MESSAGE'] = $message;
						} else {
							$res['SUCCESS']    = false;
							$res['resultDataENG'] = $valueArrayENG;
							$res['MESSAGE'] = 'Product not found.';
						}
					}
				}
			}
		}
		if(!empty($res['resultData']) || !empty($res['resultDataENG'])){
			$res['SUCCESS']  = true;
		}else{
			$res['SUCCESS']  = false;
		}
		echo json_encode($res);
		die();
	}

	public function save_global_setting_without_mockup()
	{
		/* Remove all images from local */
		self::execInBackground(common::SITE_URL . "remove-images-background.php");
		$reponseArray = array();
		global $path;
		global $s3Obj;
		$message='';
		$res =  array();
		$valueArray =  array();
		$valueArrayENG =  array();
		$store_master_id    = (!empty($_POST['store_master_id'])) ? $_POST['store_master_id'] : '';
		$logowidth_inchglobal    = (!empty($_POST['logowidth_inchglobal'])) ? $_POST['logowidth_inchglobal'] : '';
		if (isset($store_master_id) && !empty($store_master_id)) {
			$res = array();
			$res['resultData']=$res['resultDataENG']=[];
			if (!empty($_POST['groupData'])) {
				foreach ($_POST['groupData'] as $value) {
					$logoId         = $value['logo_id'];
					$PostlogoId     = $value['logo_id'];
					$prductsGroupId = explode(',', $value['prducts_groups']);
					$JsonGroupArray = json_encode(array_values($prductsGroupId));
					$groupNames     = str_replace(array('[', ']'), '', $JsonGroupArray);

					$grouparray = explode(',', $groupNames);
					// Remove the unwanted value ("Engraving")
					$group_array = array_diff($grouparray, array('"Engraving"'));
					$group_array = array_values($group_array);
					$groupnameWithoutEngraving = implode(',', $group_array);

					foreach ($prductsGroupId as $value2) {
						$groupName = $value2;
						$groupItemSql = 'SELECT sopm.id FROM store_owner_product_master as sopm  LEFT JOIN logo_coordinates as lc ON lc.store_product_master_id = sopm.store_product_master_id WHERE sopm.group_name="' . $groupName . '" and sopm.store_master_id = "' . $_POST['store_master_id'] . '" AND sopm.is_soft_deleted="0" ';
						$groupItemDetails = parent::selectTable_f_mdl($groupItemSql);
						$dataIds = array();
						if (!empty($groupItemDetails)) {
							foreach ($groupItemDetails as $value1) {
								$dataIds[] = $value1['id'];
							}
						}

						$i = 0;
						if (!empty($dataIds)) {
							foreach ($dataIds as $value) {
								$checkCoordsSql = 'SELECT sopm.store_product_master_id,sdlm.applicable_colors FROM store_owner_product_master as sopm LEFT JOIN logo_coordinates as lc on lc.store_product_master_id = sopm.store_product_master_id LEFT JOIN store_design_logo_master as sdlm on sdlm.print_location = lc.print_location_id WHERE sopm.id = '.$value.' AND sdlm.id = '.$PostlogoId.' ';
								$coordsData = parent::selectTable_f_mdl($checkCoordsSql);
								if(!empty($coordsData)){
									$applicable_colors=$coordsData[0]['applicable_colors'];
									$applicable_color=explode(',',$applicable_colors);
									$JsoncolorArray = json_encode(array_values($applicable_color));
									$colorCodeValues  = str_replace (array('[', ']'), '' , $JsoncolorArray);

									$checkcolorverSql = "SELECT store_owner_product_master_id FROM store_owner_product_variant_master WHERE store_owner_product_master_id = '".$value."' AND color IN ($colorCodeValues) GROUP BY color ";
									$varData = parent::selectTable_f_mdl($checkcolorverSql);
									// echo "<pre>";print_r($varData);
								}else{
									$varData =[];
								}
								if(!empty($varData)){
									$sql = 'SELECT associate_with_logo_id FROM store_owner_product_master WHERE id="'.$value.'" ';
									$logoData = parent::selectTable_f_mdl($sql);
									if(!empty($logoData[0]['associate_with_logo_id'])){
										$existing_ids = explode(',', $logoData[0]['associate_with_logo_id']);
										if(!in_array($PostlogoId, $existing_ids)) {
											$existing_ids[] = $PostlogoId;
										}
										$logoId = implode(',', $existing_ids);
									}else{
										$logoId=$PostlogoId;
									}
									$logoDataupdate = [
										'associate_with_logo_id' => $logoId
									];
									$UpdateResponse = parent::updateTable_f_mdl('store_owner_product_master', $logoDataupdate, 'id="' . $value . '"');
								
									if ($i == 0) {
										if ($UpdateResponse['isSuccess'] == 1) {
											$res["isSuccess"] = "1";
											$res["msg"]       = "Changes saved successfully.";
										} else {
											if ($UpdateResponse['isSuccess'] == 0) {
												$res["isSuccess"] = "1";
												$res["msg"]       = "This logo is already associate with this group.";
											} else {
												$res["isSuccess"] = "0";
												$res["msg"]       = "Oops!there is some internal issues occured. Please try after some time.";
											}
										}
									}
								}
								$i++;
							}
						}
					}
					// sleep(0.5);
					$top_coordinates = $left_coordinates = $logo_width = $logo_height = $area_top_coordinates=$area_left_coordinates=$area_width = $area_height = '';

					$allProduct = 'SELECT 
						    store_owner_product_master.associate_with_logo_id,
						    store_owner_product_master.product_title,
						    store_owner_product_master.store_master_id,
						    store_owner_product_master.id AS store_owner_product_master_id,
						    store_owner_product_master.store_product_master_id AS store_product_master_id,
						    store_design_logo_master.logo_image,
						    store_design_logo_master.print_location,
						    logo_coordinates.top_coordinates,
						    logo_coordinates.left_coordinates,
						    logo_coordinates.logo_width,
						    logo_coordinates.logo_height,
						    sopvm.id,
						    sopvm.color,
						    sopvm.store_owner_product_master_id,
						    sopvm.original_image,
						    sopvm.size,
						    ac.area_top_coordinates,
						    ac.area_left_coordinates,
						    store_design_logo_master.applicable_colors
						FROM
						    `store_owner_product_master`
						        INNER JOIN
						    logo_coordinates ON logo_coordinates.store_product_master_id = store_owner_product_master.store_product_master_id
						        INNER JOIN
						    store_design_logo_master ON store_design_logo_master.id IN ('.$PostlogoId.')
						        AND store_design_logo_master.print_location = logo_coordinates.print_location_id
						        LEFT JOIN
						    `store_owner_product_variant_master` AS sopvm ON sopvm.store_owner_product_master_id = store_owner_product_master.id
						        LEFT JOIN
						    area_coordinates AS ac ON ac.store_product_master_id = store_owner_product_master.store_product_master_id
						WHERE
						    store_owner_product_master.store_master_id = "'.$store_master_id.'"
						        AND store_owner_product_master.group_name IN ('.$groupnameWithoutEngraving.')
						        AND store_owner_product_master.is_soft_deleted = "0"
						        AND sopvm.original_image !=""
						        AND FIND_IN_SET("'.$PostlogoId.'", store_owner_product_master.associate_with_logo_id) > 0
						GROUP BY (SELECT applicable_colors FROM store_design_logo_master WHERE id = '.$PostlogoId.') , store_owner_product_master.id
					';
					$products = parent::selectTable_f_mdl($allProduct);

					if (!empty($products)) {
						foreach ($products as $key => $value) {
							$print_location_id = (!empty($value['print_location']))?$value['print_location']:0;
							$store_owner_product_master_id = $value['store_owner_product_master_id'];
							$store_product_master_id = $value['store_product_master_id'];

							$sqlprintloc = 'SELECT default_title,print_location FROM `print_locations` WHERE id = "'.$print_location_id.'"';
							$printlocResult = parent::selectTable_f_mdl($sqlprintloc);
							if(!empty($printlocResult)){
								$print_location_name	= $printlocResult[0]['print_location'];
								$default_title			= $printlocResult[0]['default_title'];
							}

							$coordsSql = 'SELECT * FROM logo_coordinates as lc LEFT JOIN area_coordinates as ac ON lc.store_product_master_id = ac.store_product_master_id WHERE lc.store_product_master_id = '.$store_product_master_id.' AND lc.print_location_id = '.$print_location_id.' ';
							$coordsResult = parent::selectTable_f_mdl($coordsSql);
							if(isset($coordsResult)  && !empty($coordsResult)){
								$top_coordinates  		= $coordsResult[0]['top_coordinates'];
								$left_coordinates 		= $coordsResult[0]['left_coordinates'];
								$logo_width       		= $coordsResult[0]['logo_width'];
								$logo_height      		= $coordsResult[0]['logo_height'];
								$area_top_coordinates  	= $coordsResult[0]['area_top_coordinates'];
								$area_left_coordinates 	= $coordsResult[0]['area_left_coordinates'];
								$area_width       		= $coordsResult[0]['area_width'];
								$area_height      		= $coordsResult[0]['area_height'];
							}

							$masterProSql = 'SELECT * FROM store_product_master as spm LEFT JOIN fulfillengine_products_master as fpm ON fpm.catalog_product_id = spm.vendor_product_id WHERE spm.id = "'.$store_product_master_id.'" GROUP BY fpm.catalog_product_id ';
							$masterProSqlResult = parent::selectTable_f_mdl($masterProSql);

							$sql_vendor = 'SELECT id,vendor_name FROM `store_vendors_master` WHERE id = "'.$masterProSqlResult[0]['vendor_id'].'" ';
							$vendorNameData = parent::selectTable_f_mdl($sql_vendor);
							$product_vendor='';
							if(!empty($vendorNameData)){
								$product_vendor=$vendorNameData[0]['vendor_name'];
							}
							$applicable_color=explode(',',$value['applicable_colors']);
							$JsoncolorArray = json_encode(array_values($applicable_color));
							$colorCodeValues  = str_replace (array('[', ']'), '' , $JsoncolorArray);

							$sqlvar='SELECT * FROM store_owner_product_variant_master WHERE store_owner_product_master_id ="'.$store_owner_product_master_id.'" AND color IN ('.$colorCodeValues.') GROUP BY color';
							$variantData = parent::selectTable_f_mdl($sqlvar);

							if(!empty($variantData)){
								foreach($variantData as $singl_variant){
									$imageUrl = '';
									if (!empty($singl_variant['original_image'])) {
										$filename = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $singl_variant['original_image']);
										$tmpPath = $path . common::IMAGE_UPLOAD_S3_PATH . $singl_variant['original_image'];
										@file_put_contents($tmpPath, @file_get_contents($filename));
										$imageUrl = common::SITE_URL . common::IMAGE_UPLOAD_S3_PATH . $singl_variant['original_image'];
									}

									$logoUrl = '';
									if (!empty($value['logo_image'])) {
										$logoImage = $value['logo_image'];
										$logoFile = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $logoImage);
										$tmpPath1 = $path . common::IMAGE_UPLOAD_S3_PATH . $logoImage;
										@file_put_contents($tmpPath1, @file_get_contents($logoFile));
										$logoUrl = common::SITE_URL . common::IMAGE_UPLOAD_S3_PATH . $logoImage;

										// Get image data from URL
										$ActualLogowidth=$ActualLogoheight='';
										if(!empty($logoUrl)){
											$imagegetData 		= file_get_contents($logoUrl);
											$logoImgimage 		= imagecreatefromstring($imagegetData);
											$imageInfo 			= getimagesize($logoUrl);
											$ActualLogowidth 	= $imageInfo[0];
											$ActualLogoheight 	= $imageInfo[1];
											$dpi 				= imageresolution($logoImgimage);
											$logowidth_inch		= $ActualLogowidth/$dpi[0];
											$logoheight_inch	= $ActualLogoheight/$dpi[0];
											$logo_dpi			= $dpi[0];
											$logowidth_inch 	= number_format($logowidth_inch,'2','.','');
											$logoheight_inch 	= number_format($logoheight_inch,'2','.','');
										}
									}
									$maxwidthinch=$maxheightinch='';
									
									if($product_vendor=='FulfillEngine'){
										if(!empty($masterProSqlResult) && !empty($print_location_name)){
											$maxwidthinch  = $masterProSqlResult[0][$print_location_name . "_width"];
											$maxheightinch = $masterProSqlResult[0][$print_location_name . "_height"];
											
											$maxwidthinch 			= !empty($maxwidthinch) ? $maxwidthinch : 1;
											$maxheightinch 			= !empty($maxheightinch) ? $maxheightinch : 1;
											$perinch_pixel_width 	= ceil($area_width / $maxwidthinch);
											$perinch_pixel_height 	= ceil($area_height / $maxheightinch);
											$original_width 		= $ActualLogowidth; // in pixels
											$original_height 		= $ActualLogoheight; // in pixels
											if($maxwidthinch >= $logowidth_inchglobal){
												$resized_width 			= $logowidth_inchglobal * $perinch_pixel_width;
											}else{
												$resized_width 			= $maxwidthinch * $perinch_pixel_width;
											}
											$resized_width 			= number_format($resized_width , 2);  // in pixels
											$scaling_factor 		= $resized_width / $original_width;
											// Calculate the new height based on the scaling factor
											$resized_height 		= $original_height * $scaling_factor;
											$resized_height 		= number_format($resized_height , 2);
											$pixels_per_inch 		= $perinch_pixel_width; 
											// Calculate the width and height in inches
											$width_in_inches 		= number_format($resized_width / $pixels_per_inch , 2);
											$height_in_inches 		= number_format($resized_height / $pixels_per_inch , 2);

											$update_logowidthdata = [
												'associate_with_logo_id' =>trim($PostlogoId),
												'assign_logo_width' => trim($resized_width),
												'assign_logo_height' => trim($resized_height),
												'assign_logo_leftcoordinates' => trim($value['left_coordinates']),
												'assign_logo_topcoordinates' => trim($value['top_coordinates']),
												'assign_logo_heightinch' => trim($height_in_inches),
												'assign_logo_widthinch' => trim($width_in_inches),
											];
											parent::updateTable_f_mdl('store_owner_product_variant_master',$update_logowidthdata,'store_owner_product_master_id="'.$value['store_owner_product_master_id'].'" AND color="'.$singl_variant['color'].'" ');
											$valueArray[] = array(
												"store_master_id"  				=> $value['store_master_id'],
												'store_owner_product_master_id' => $value['store_owner_product_master_id'],
											);
											
										}
									}else{
										$update_logowidthdata = [
											'associate_with_logo_id' =>trim($PostlogoId),
											'assign_logo_width' => trim($value['logo_width']),
											'assign_logo_height' => trim($value['logo_height']),
											'assign_logo_leftcoordinates' => trim($value['left_coordinates']),
											'assign_logo_topcoordinates' => trim($value['top_coordinates']),
											'assign_logo_heightinch' => trim($logowidth_inch),
											'assign_logo_widthinch' => trim($logoheight_inch),
										];
										parent::updateTable_f_mdl('store_owner_product_variant_master',$update_logowidthdata,'store_owner_product_master_id="'.$value['store_owner_product_master_id'].'" AND color="'.$singl_variant['color'].'" ');

										$valueArray[] = array(
											"store_master_id"  => $value['store_master_id'],
											'store_owner_product_master_id' => $value['store_owner_product_master_id'],
										);
									}

								}
							}

									
						}
						$res['SUCCESS']  = true;
						$res['resultData'] = $valueArray;
						$res['MESSAGE'] = $message;

					} else {
						$res['SUCCESS']    = false;
						$res['resultData'] = $valueArray;
						$res['MESSAGE'] = 'The selected products/groups lack either color, coordinates, or both.';
					}

					$key = array_search('"Engraving"', $grouparray);
					if ($key !== false) {
						$allProduct = 'SELECT  
							store_owner_product_master.associate_with_logo_id,
							store_owner_product_master.product_title,
							store_owner_product_master.store_master_id,
							store_owner_product_master.id AS store_owner_product_master_id,
							store_owner_product_master.store_product_master_id AS store_product_master_id,
							store_design_logo_master.logo_image,
							store_design_logo_master.print_location,
							logo_coordinates.top_coordinates,
							logo_coordinates.left_coordinates,
							logo_coordinates.logo_width,
							logo_coordinates.logo_height,
							sopvm.id,
							sopvm.color,
							sopvm.store_owner_product_master_id,
							sopvm.original_image,
							sopvm.size,
							ac.area_top_coordinates,
							ac.area_left_coordinates,
							store_design_logo_master.applicable_colors
							FROM
							`store_owner_product_master`
								INNER JOIN
							logo_coordinates ON logo_coordinates.store_product_master_id = store_owner_product_master.store_product_master_id
								INNER JOIN
						    store_design_logo_master ON store_design_logo_master.id IN ('.$PostlogoId.')
						        AND store_design_logo_master.print_location = logo_coordinates.print_location_id
								LEFT JOIN
							`store_owner_product_variant_master` AS sopvm ON sopvm.store_owner_product_master_id = store_owner_product_master.id
								LEFT JOIN
						    area_coordinates AS ac ON ac.store_product_master_id = store_owner_product_master.store_product_master_id
							WHERE
							store_owner_product_master.store_master_id = "'.$store_master_id.'"
								AND store_owner_product_master.group_name IN("Engraving")
								AND store_owner_product_master.is_soft_deleted = "0"
								AND sopvm.original_image !=""
								AND FIND_IN_SET("'.$PostlogoId.'", store_owner_product_master.associate_with_logo_id) > 0
								GROUP BY (SELECT applicable_colors FROM store_design_logo_master WHERE id = '.$PostlogoId.') , store_owner_product_master.id
						';
						$productsEng = parent::selectTable_f_mdl($allProduct);
						if (!empty($productsEng)) {
							foreach ($productsEng as $key => $value) {

								$print_location_id 				= (!empty($value['print_location']))?$value['print_location']:0;
								$store_owner_product_master_id 	= $value['store_owner_product_master_id'];
								$store_product_master_id 		= $value['store_product_master_id'];

								$sqlprintloc = 'SELECT default_title,print_location FROM `print_locations` WHERE id = "'.$print_location_id.'"';
								$printlocResult = parent::selectTable_f_mdl($sqlprintloc);
								if(!empty($printlocResult)){
									$print_location_name	= $printlocResult[0]['print_location'];
									$default_title			= $printlocResult[0]['default_title'];
								}

								$coordsSql = 'SELECT * FROM logo_coordinates as lc LEFT JOIN area_coordinates as ac ON lc.store_product_master_id = ac.store_product_master_id WHERE lc.store_product_master_id = '.$store_product_master_id.' AND lc.print_location_id = '.$print_location_id.' ';
								$coordsResult = parent::selectTable_f_mdl($coordsSql);
								if(isset($coordsResult)  && !empty($coordsResult)){
									$top_coordinates  		= $coordsResult[0]['top_coordinates'];
									$left_coordinates 		= $coordsResult[0]['left_coordinates'];
									$logo_width       		= $coordsResult[0]['logo_width'];
									$logo_height      		= $coordsResult[0]['logo_height'];
									$area_top_coordinates  	= $coordsResult[0]['area_top_coordinates'];
									$area_left_coordinates 	= $coordsResult[0]['area_left_coordinates'];
									$area_width       		= $coordsResult[0]['area_width'];
									$area_height      		= $coordsResult[0]['area_height'];
								}

								$masterProSql = 'SELECT * FROM store_product_master as spm LEFT JOIN fulfillengine_products_master as fpm ON fpm.catalog_product_id = spm.vendor_product_id WHERE spm.id = "'.$store_product_master_id.'" GROUP BY fpm.catalog_product_id ';
								$masterProSqlResult = parent::selectTable_f_mdl($masterProSql);

								$sql_vendor = 'SELECT id,vendor_name FROM `store_vendors_master` WHERE id = "'.$masterProSqlResult[0]['vendor_id'].'" ';
								$vendorNameData = parent::selectTable_f_mdl($sql_vendor);
								$product_vendor='';
								if(!empty($vendorNameData)){
									$product_vendor = $vendorNameData[0]['vendor_name'];
								}
								$applicable_color=explode(',',$value['applicable_colors']);
								$JsoncolorArray = json_encode(array_values($applicable_color));
								$colorCodeValues  = str_replace (array('[', ']'), '' , $JsoncolorArray);

								$sqlvar='SELECT * FROM store_owner_product_variant_master WHERE store_owner_product_master_id ="'.$store_owner_product_master_id.'" AND color IN ('.$colorCodeValues.') GROUP BY color';
								$variantData = parent::selectTable_f_mdl($sqlvar);
								if(!empty($variantData)){
									foreach($variantData as $singl_variant){
										$imageUrl = '';
										if (!empty($singl_variant['original_image'])) {
											$filename = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $singl_variant['original_image']);
											$tmpPath = $path . common::IMAGE_UPLOAD_S3_PATH . $singl_variant['original_image'];
											@file_put_contents($tmpPath, @file_get_contents($filename));
											$imageUrl = common::SITE_URL . common::IMAGE_UPLOAD_S3_PATH . $singl_variant['original_image'];
										}
			
										$logoUrl = '';
										if (!empty($value['logo_image'])) {
											$logoImage = $value['logo_image'];
											$logoFile = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $logoImage);

											$headers = get_headers($logoFile);
											if($headers && strpos($headers[0], '200')){
											}else{
												die('Image file not found.');
											}
											// Determine image type (JPEG or PNG) based on file extension
											$imageType = pathinfo($logoFile, PATHINFO_EXTENSION);
											// Create image resource from the input image based on image type
											if ($imageType === 'jpg' || $imageType === 'jpeg') {
												$inputImage = imagecreatefromjpeg($logoFile);
											} elseif ($imageType === 'png') {
												$inputImage = imagecreatefrompng($logoFile);
											} else {
												die('Unsupported image type.');
											}
											// Check if image creation was successful
											if (!$inputImage) {
												die('Unable to create image from file.');
											}
											// Get image dimensions
											$width = imagesx($inputImage);
											$height = imagesy($inputImage);
											// Create a new image with true color
											$outputImage = imagecreatetruecolor($width, $height);
											// Check if image creation was successful
											if (!$outputImage) {
												die('Unable to create true color image.');
											}

											imagesavealpha($outputImage, true);
											imagealphablending($outputImage, false);
											// Set the alpha channel for the entire image to fully transparent
											$transparentColor = imagecolorallocatealpha($outputImage, 0, 0, 0, 127);
											imagefill($outputImage, 0, 0, $transparentColor);
											// Copy the input image onto the transparent image without blending
											imagecopy($outputImage, $inputImage, 0, 0, 0, 0, $width, $height);
											// Apply a color filter to change the color to silver
											imagefilter($outputImage, IMG_FILTER_GRAYSCALE);
											//imagefilter($outputImage, IMG_FILTER_BRIGHTNESS, 30); // Increase brightness
											imagefilter($outputImage, IMG_FILTER_COLORIZE, 192, 192, 192); // Colorize to silver
											$destination_folder = $path . common::IMAGE_UPLOAD_S3_PATH . "eng_".$logoImage;

											// Save the modified image to a file based on image type
											if ($imageType === 'jpg' || $imageType === 'jpeg') {
												imagejpeg($outputImage, $destination_folder);
											} elseif ($imageType === 'png') {
												imagepng($outputImage, $destination_folder);
											}
											// Destroy the image resources to free up memory
											imagedestroy($inputImage);
											imagedestroy($outputImage);
											$logoUrleng = common::SITE_URL . common::IMAGE_UPLOAD_S3_PATH ."eng_".$logoImage;

											// Get image data from URL
											$ActualLogowidth=$ActualLogoheight='';
											if(!empty($logoUrleng)){
												$imagegetData 		= file_get_contents($logoUrleng);
												$logoImgimage 		= imagecreatefromstring($imagegetData);
												$imageInfo 			= getimagesize($logoUrleng);
												$ActualLogowidth 	= $imageInfo[0];
												$ActualLogoheight 	= $imageInfo[1];
												$dpi 				= imageresolution($logoImgimage);
												$logowidth_inch		= $ActualLogowidth/$dpi[0];
												$logoheight_inch	= $ActualLogoheight/$dpi[0];
												$logo_dpi			= $dpi[0];
			
												$logowidth_inch     = number_format($logowidth_inch,'2','.','');
												$logoheight_inch 	= number_format($logoheight_inch,'2','.','');
											}
										}

										$maxwidthinch=$maxheightinch='';
										if($product_vendor=='FulfillEngine'){
											if(!empty($masterProSqlResult) && !empty($print_location_name)){
												$maxwidthinch  = $masterProSqlResult[0][$print_location_name . "_width"];
												$maxheightinch = $masterProSqlResult[0][$print_location_name . "_height"];
												
												$maxwidthinch 			= !empty($maxwidthinch) ? $maxwidthinch : 1;
												$maxheightinch 			= !empty($maxheightinch) ? $maxheightinch : 1;
												$perinch_pixel_width 	= ceil($area_width / $maxwidthinch);
												$perinch_pixel_height 	= ceil($area_height / $maxheightinch);
												
												$original_width 		= $ActualLogowidth; // in pixels
												$original_height 		= $ActualLogoheight; // in pixels
												if($maxwidthinch >= $logowidth_inchglobal){
													$resized_width 			= $logowidth_inchglobal * $perinch_pixel_width;
												}else{
													$resized_width 			= $maxwidthinch * $perinch_pixel_width;
												}
												$resized_width 			= number_format($resized_width , 2);  // in pixels
												$scaling_factor 		= $resized_width / $original_width;
												// Calculate the new height based on the scaling factor
												$resized_height 		= $original_height * $scaling_factor;
												$resized_height 		= number_format($resized_height , 2);
												$pixels_per_inch 		= $perinch_pixel_width; 
												// Calculate the width and height in inches
												$width_in_inches 		= number_format($resized_width / $pixels_per_inch , 2);
												$height_in_inches 		= number_format($resized_height / $pixels_per_inch , 2);
											
												$update_logowidthdata = [
													'associate_with_logo_id' =>trim($PostlogoId),
													'assign_logo_width' => trim($resized_width),
													'assign_logo_height' => trim($resized_height),
													'assign_logo_leftcoordinates' => trim($value['left_coordinates']),
													'assign_logo_topcoordinates' => trim($value['top_coordinates']),
													'assign_logo_heightinch' => trim($height_in_inches),
													'assign_logo_widthinch' => trim($width_in_inches),
												];
												parent::updateTable_f_mdl('store_owner_product_variant_master',$update_logowidthdata,'store_owner_product_master_id="'.$value['store_owner_product_master_id'].'" AND color="'.$singl_variant['color'].'" ');

												$valueArrayENG[] = array(
													"store_master_id"  				=> $value['store_master_id'],
													'store_owner_product_master_id' => $value['store_owner_product_master_id'],
												);
												
											}
										}else{

											$update_logowidthdata = [
												'associate_with_logo_id' 		=>trim($PostlogoId),
												'assign_logo_width' 			=> trim($value['logo_width']),
												'assign_logo_height' 			=> trim($value['logo_height']),
												'assign_logo_leftcoordinates' 	=> trim($value['left_coordinates']),
												'assign_logo_topcoordinates' 	=> trim($value['top_coordinates']),
												'assign_logo_heightinch' 		=> trim($logowidth_inch),
												'assign_logo_widthinch' 		=> trim($logoheight_inch),
											];
											parent::updateTable_f_mdl('store_owner_product_variant_master',$update_logowidthdata,'store_owner_product_master_id="'.$value['store_owner_product_master_id'].'" AND color="'.$singl_variant['color'].'" ');
			
											$valueArrayENG[] = array(
												"store_master_id"  				=> $value['store_master_id'],
												'store_owner_product_master_id' => $value['store_owner_product_master_id'],
											);
										}
									}
								}	
							}
							$res['SUCCESS']  = true;
							$res['resultDataENG'] = $valueArrayENG;
							$res['MESSAGE'] = $message;
						} else {
							$res['SUCCESS']    = false;
							$res['resultDataENG'] = $valueArrayENG;
							$res['MESSAGE'] = 'Product not found.';
						}
					}
				}
			}
		}
		if(!empty($res['resultData']) || !empty($res['resultDataENG'])){
			$res['SUCCESS']  = true;
		}else{
			$res['SUCCESS']  = false;
		}
		echo json_encode($res);
		die();
	}

	public function logo_asigned()
	{
		/* Remove all images from local */
		self::execInBackground(common::SITE_URL . "remove-images-background.php");

		$reponseArray = array();
		global $path;
		global $s3Obj;
		$res =  array();
		$valueArray =  array();
		$store_master_id    = (!empty($_POST['store_master_id'])) ? $_POST['store_master_id'] : '';
		$totalProdCountStore=$totalProdAssignCountStore=$totalProdUnassignCountStore='0';
		if (isset($store_master_id) && !empty($store_master_id)) {
			$res = array();
			if (!empty($_POST['groupData'])) {
				foreach ($_POST['groupData'] as $value) {
					$logoId         = $value['logo_id'];
					$PostlogoId     = $value['logo_id'];
					$prductsGroupId = explode(',', $value['prducts_groups']);
					$JsonGroupArray = json_encode(array_values($prductsGroupId));
					$groupNames     = str_replace(array('[', ']'), '', $JsonGroupArray);

					$groupData = [
						'assigned_group' => $value['prducts_groups']
					];
					parent::updateTable_f_mdl('store_design_logo_master', $groupData, 'id="' . $value['logo_id'] . '"');

					foreach ($prductsGroupId as $value2) {
						$groupName = $value2;
						$groupItemSql = 'SELECT id FROM store_owner_product_master WHERE group_name="' . $groupName . '" and store_master_id = "' . $_POST['store_master_id'] . '" AND is_soft_deleted="0" ';
						$groupItemDetails = parent::selectTable_f_mdl($groupItemSql);

						$dataIds = array();
						if (!empty($groupItemDetails)) {
							foreach ($groupItemDetails as $value1) {
								$dataIds[] = $value1['id'];
							}
						}

						$i = 0;
						$totalcountProd=count($dataIds);
						$totalProdCountStore +=$totalcountProd;
						$assignlogoCount=0;
						$unassignlogoCount=0;
						if (!empty($dataIds)) {
							foreach ($dataIds as $value) {
								 $checkCoordsSql = 'SELECT sopm.store_product_master_id,sdlm.applicable_colors FROM store_owner_product_master as sopm LEFT JOIN logo_coordinates as lc on lc.store_product_master_id = sopm.store_product_master_id LEFT JOIN store_design_logo_master as sdlm on sdlm.print_location = lc.print_location_id WHERE sopm.id = '.$value.' AND sdlm.id = '.$PostlogoId.' ';
								$coordsData = parent::selectTable_f_mdl($checkCoordsSql);
								if(!empty($coordsData)){
									$applicable_colors=$coordsData[0]['applicable_colors'];
									$applicable_color=explode(',',$applicable_colors);
									$JsoncolorArray = json_encode(array_values($applicable_color));
									$colorCodeValues  = str_replace (array('[', ']'), '' , $JsoncolorArray);

									 $checkcolorverSql = "SELECT store_owner_product_master_id FROM store_owner_product_variant_master WHERE store_owner_product_master_id = '".$value."' AND color IN ($colorCodeValues) ";
									$varData = parent::selectTable_f_mdl($checkcolorverSql);
									//echo "<pre>";print_r($varData);
								}else{
									$varData =[];
								}
								if(!empty($varData)){
									$store_product_master_id = '';
									$assignlogoCount++;
									$sql = 'SELECT store_product_master_id,associate_with_logo_id FROM store_owner_product_master WHERE id="'.$value.'" ';
									$logoData = parent::selectTable_f_mdl($sql);
									if(!empty($logoData[0]['associate_with_logo_id'])){
										$existing_ids = explode(',', $logoData[0]['associate_with_logo_id']);
										if(!in_array($PostlogoId, $existing_ids)) {
											$existing_ids[] = $PostlogoId;
										}
										$logoId = implode(',', $existing_ids);
									}else{
										$logoId=$PostlogoId;
									}

									if(!empty($logoData)){
										$store_product_master_id=$logoData[0]['store_product_master_id'];
									}
									$vendorsql = 'SELECT vendor_id FROM store_product_master WHERE id="'.$store_product_master_id.'" ';
									$vendorsqlData = parent::selectTable_f_mdl($vendorsql);
									$vendor_id = '';
									if(!empty($vendorsqlData)){
										$vendor_id = $vendorsqlData[0]['vendor_id'];
									}

									$logoDataupdate = [
										'associate_with_logo_id' => $logoId
									];
									$UpdateResponse = parent::updateTable_f_mdl('store_owner_product_master', $logoDataupdate, 'id="' . $value . '"');

									if($vendor_id=='24'){
										$associateLogoData = ['associate_with_logo_id' => $PostlogoId];
										parent::updateTable_f_mdl('store_owner_product_variant_master',$associateLogoData,'store_owner_product_master_id="'.$value.'"');
									}
									if ($i == 0) {
										if ($UpdateResponse['isSuccess'] == 1) {
											$res["isSuccess"] = "1";
											$res["msg"]       = "Logo assigned successfully.";
										} else {
											if ($UpdateResponse['isSuccess'] == 0) {
												$res["isSuccess"] = "1";
												$res["msg"]       = "This logo is already associate with this group.";
											} else {
												$res["isSuccess"] = "0";
												$res["msg"]       = "Oops!there is some internal issues occured. Please try after some time.";
											}
										}
									}
								}else{
									$unassignlogoCount++;
								}
								$i++;
							}
						}
						$totalProdAssignCountStore +=$assignlogoCount;
                    	$totalProdUnassignCountStore +=$unassignlogoCount;
					}	
				}
			}
			
			if($totalProdUnassignCountStore==$totalProdCountStore){
				$res["isSuccess"] = "0";
				$res["msg"]       = "No products available with selected print location or color.";
			}else if($totalProdAssignCountStore==$totalProdCountStore){
				$res["isSuccess"] = "1";
				$res["msg"]       = "Logo assigned successfully.";
			}else{
				$res["isSuccess"] = "2";
				$res["msg"]       = "Logo assigned on ".$totalProdAssignCountStore." product(s) and not assigned on ".$totalProdUnassignCountStore." product(s).";
			}
			echo json_encode($res);
			die();
		}
	}

	public function saveSingleProduct()
	{
		/* Remove all images from local */
		self::execInBackground(common::SITE_URL . "remove-images-background.php");
		global $path;
		global $s3Obj;
		$res = array();
		$valueArray =  array();
		if (isset($_POST['store_master_id']) && !empty($_POST['store_master_id'])) {
			// print_r($_POST);die();
			$logoFor = $_POST['logoFor'];
			$product_id = $_POST['product_id'];
			$logo_id = $_POST['logo_id'];
			$variant_color_name = $_POST['variant_color_name'];
			$left_coordinates = $_POST['left_coordinates'];
			$top_coordinates = $_POST['top_coordinates'];
			$logo_height = $_POST['logo_height'];
			$logo_width = $_POST['logo_width'];
			$area_top = $_POST['area_top'];
			$area_left = $_POST['area_left'];
			$logo_heightinch = $_POST['logo_heightinch'];
			$logo_widthinch = $_POST['logo_widthinch'];

			$applicable_colors = explode(',', $_POST['applicable_colors']);
			$JsonproductArray = json_encode(array_values($applicable_colors));
			$applicable_colors  = str_replace (array('[', ']'), '' , $JsonproductArray);


			$sql  = 'SELECT id,logo_image FROM store_design_logo_master WHERE id = ' . $logo_id . '';
			$logoData = parent::selectTable_f_mdl($sql);

			$pro_group_sql  = 'SELECT group_name FROM store_owner_product_master WHERE id ="'.$product_id.'" ';
			$prodGroupData = parent::selectTable_f_mdl($pro_group_sql);

			$logoUrl = '';
			if (!empty($logoData)) {

				if (!empty($logoData[0]['logo_image'])) {

					if(!empty($prodGroupData)){
						if($prodGroupData[0]['group_name']=='Engraving'){
							$logoImage = $logoData[0]['logo_image'];
							$logoFile = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $logoImage);
							$headers = get_headers($logoFile);
							if ($headers && strpos($headers[0], '200')) {
							} else {
								die('Image file not found.');
							}
							$imageType = pathinfo($logoFile, PATHINFO_EXTENSION);
							if ($imageType === 'jpg' || $imageType === 'jpeg') {
								$inputImage = imagecreatefromjpeg($logoFile);
							} elseif ($imageType === 'png') {
								$inputImage = imagecreatefrompng($logoFile);
							} else {
								die('Unsupported image type.');
							}
							if (!$inputImage) {
								die('Unable to create image from file.');
							}
							$width = imagesx($inputImage);
							$height = imagesy($inputImage);
							$outputImage = imagecreatetruecolor($width, $height);
							if (!$outputImage) {
								die('Unable to create true color image.');
							}
							imagesavealpha($outputImage, true);
							imagealphablending($outputImage, false);
							$transparentColor = imagecolorallocatealpha($outputImage, 0, 0, 0, 127);
							imagefill($outputImage, 0, 0, $transparentColor);
							imagecopy($outputImage, $inputImage, 0, 0, 0, 0, $width, $height);
							imagefilter($outputImage, IMG_FILTER_GRAYSCALE);
							//imagefilter($outputImage, IMG_FILTER_BRIGHTNESS, 30); // Increase brightness
							imagefilter($outputImage, IMG_FILTER_COLORIZE, 192, 192, 192); // Colorize to silver
							$destination_folder = $path . common::IMAGE_UPLOAD_S3_PATH . "single_eng_".$logoImage;
							if ($imageType === 'jpg' || $imageType === 'jpeg') {
								imagejpeg($outputImage, $destination_folder);
							} elseif ($imageType === 'png') {
								imagepng($outputImage, $destination_folder);
							}
							// Destroy the image resources to free up memory
							imagedestroy($inputImage);
							imagedestroy($outputImage);
							$logoUrl = common::SITE_URL . common::IMAGE_UPLOAD_S3_PATH ."single_eng_".$logoImage;
							
						}else{
							$logoImage = $logoData[0]['logo_image'];
							$logoFile = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $logoImage);
							$tmpPath1 = $path . common::IMAGE_UPLOAD_S3_PATH . $logoImage;
							@file_put_contents($tmpPath1, @file_get_contents($logoFile));
							$logoUrl = common::SITE_URL . common::IMAGE_UPLOAD_S3_PATH . $logoImage;
						}
					}
				}
			}

			// $logoUrl = '';
			// if (!empty($logoData)) {
			// 	if (!empty($logoData[0]['logo_image'])) {
			// 		$logoImage = $logoData[0]['logo_image'];
			// 		$logoFile = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $logoImage);
			// 		$tmpPath1 = $path . common::IMAGE_UPLOAD_S3_PATH . $logoImage;
			// 		@file_put_contents($tmpPath1, @file_get_contents($logoFile));
			// 		$logoUrl = common::SITE_URL . common::IMAGE_UPLOAD_S3_PATH . $logoImage;
			// 	}
			// }
			if($logoFor=="save_without_mockup"){
				$update_logowidthdata = [
					// 'associate_with_logo_id' => $logo_id,
					'assign_logo_width' => trim($logo_width),
					'assign_logo_height' => trim($logo_height),
					'assign_logo_leftcoordinates' => trim($left_coordinates),
					'assign_logo_topcoordinates' => trim($top_coordinates),
					'assign_logo_heightinch' => trim($logo_heightinch),
					'assign_logo_widthinch' => trim($logo_widthinch),
				];
				parent::updateTable_f_mdl('store_owner_product_variant_master',$update_logowidthdata,'store_owner_product_master_id="'.$product_id.'" ');
				
				if(empty($variant_color_name)){
					$varSql       = 'SELECT id,original_image,color FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id =' . $product_id . ' group by color';
				}else{
					$varSql       = 'SELECT id,original_image,color FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id =' . $product_id . ' AND color = "' . $variant_color_name . '" group by color';
				}
			}else{
				$update_logowidthdata = [
					'associate_with_logo_id' => $logo_id,
					'assign_logo_width' => trim($logo_width),
					'assign_logo_height' => trim($logo_height),
					'assign_logo_leftcoordinates' => trim($left_coordinates),
					'assign_logo_topcoordinates' => trim($top_coordinates),
					'assign_logo_heightinch' => trim($logo_heightinch),
					'assign_logo_widthinch' => trim($logo_widthinch),
				];
				
				if(empty($variant_color_name)){
					parent::updateTable_f_mdl('store_owner_product_variant_master',$update_logowidthdata,'store_owner_product_master_id="'.$product_id.'" AND color IN ('.$applicable_colors.') ');
				}else{
					parent::updateTable_f_mdl('store_owner_product_variant_master',$update_logowidthdata,'store_owner_product_master_id="'.$product_id.'" AND color = "'.$variant_color_name.'" ');
				}
				
				if(empty($variant_color_name)){
					$varSql       = 'SELECT id,original_image,color FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id =' . $product_id . ' AND color IN ('.$applicable_colors.') group by color';
				}else{
					$varSql       = 'SELECT id,original_image,color FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id =' . $product_id . ' AND color = "' . $variant_color_name . '" group by color';
				}
			}
			$varColors    =  parent::selectTable_f_mdl($varSql);
			if (!empty($varColors)) {
				if($logoFor=="save_without_mockup"){
				}else{
					foreach ($varColors as $key => $value) {
						if (!empty($value['original_image'])) {
							$filename = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $value['original_image']);
							$tmpPath = $path . common::IMAGE_UPLOAD_S3_PATH . $value['original_image'];
							@file_put_contents($tmpPath, @file_get_contents($filename));
							$imageUrl = common::SITE_URL . common::IMAGE_UPLOAD_S3_PATH . $value['original_image'];
						} else {
							$imageUrl = '';
						}
	
						$valueArray[] = array(
							"store_master_id" => $_POST['store_master_id'],
							'product_title' => '',
							"print_location" => '',
							'IMAGE_URL' => $imageUrl,
							'LOGO_URL' => $logoUrl,
							'TOP_COORDINATES' => $top_coordinates,
							'LEFT_COORDINATES' => $left_coordinates,
							'LOGO_WIDTH' => $logo_width,
							'LOGO_HEIGHT' => $logo_height,
							'AREA_TOP_COORDINATES' => $area_top,
							'AREA_LEFT_COORDINATES' =>$area_left,
							'COLOR' => $value['color'],
							'store_owner_product_master_id'=> $product_id
						);
					}
				}

				$res['SUCCESS']    = true;
				$res['logoFor']    = $logoFor;
				$res['resultData'] = $valueArray;
			} else {
				$res['SUCCESS']    = false;
				$res['logoFor']    = $logoFor;
				$res['resultData'] = $valueArray;
			}
		} else {
			$res['SUCCESS']    = false;
			$res['logoFor']    = '';
			$res['resultData'] = $valueArray;
		}
		echo json_encode($res);
		die();
	}

	public function mergeImages()
	{
		global $s3Obj;
		global $path;
		$response = array();
		$imgData = array();
		if (isset($_POST['imgArray']) && !empty($_POST['imgArray'])) {
			$i = 0;
			// $s3Obj->deleteArchiveFolder($mockupFolder);
			foreach ($_POST['imgArray'] as $key => $value) {
				$base64Data                            = $value['img_code'];
				$store_owner_product_master_id         = trim($value['store_owner_product_master_id']);
				$variantColor                          = $value['color'];
				$colorSql      = 'SELECT product_color_name FROM store_product_colors_master where product_color = "' . trim($variantColor) . '" ';
				$colorName = parent::selectTable_f_mdl($colorSql);
				$product_color_name = '';
				if (!empty($colorName)) {
					if (!empty($colorName)) {
						$product_color_name = $colorName[0]['product_color_name'];
						$product_color_name = str_replace("/", "", $product_color_name);
						$product_color_name = str_replace("/ ", "", $product_color_name);
						$product_color_name = str_replace(" ", "", $product_color_name);
					}
				}
				$imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '',$base64Data));
				$imageExtension = time().'_'.$store_owner_product_master_id.'_'.$product_color_name.'.png';
				$mockupFolder = common::LOGO_MOCKUP_UPLOAD_S3_PATH.$value['store_master_id'].'/';
				$filepath = $mockupFolder.$imageExtension; // or image.jpg
				$mime_type = "image/png";
				$mockupSql = "SELECT store_owner_product_master_id,image FROM `store_logo_mockups_master` WHERE (image!='' or image IS NOT NULL) and store_owner_product_master_id =".$store_owner_product_master_id." and color = '".trim($variantColor)."' ";
				$mockupData  =  parent::selectTable_f_mdl($mockupSql);
				if(!empty($mockupData)){
				    $s3Obj->deleteObject($mockupFolder.$mockupData[0]['image']);
				}
				
				// $existStorDir = $s3Obj->FileExists($mockupFolder);
				// if(!$existStorDir){
				// 	$s3Obj->putObject($mockupFolder, '', '');
				// }
				
				$s3Obj->putObject($filepath, $imageData, $mime_type);
				$variantSql = 'SELECT id,image FROM `store_owner_product_variant_master` WHERE color = "' . trim($variantColor) . '" and store_owner_product_master_id =' . $store_owner_product_master_id . ' ';
				$varImgData  =  parent::selectTable_f_mdl($variantSql);
				$response = [];
				if (!empty($varImgData)) {
					foreach (array_chunk($varImgData,20) as $chunksB) {
						foreach ($chunksB as $key => $Vvalue) {
							$response['updatedImageProductsIds'][$key] = [
								'store_owner_product_master_id'         => $store_owner_product_master_id,
								'store_owner_product_variant_master_id' => $Vvalue['id'],
								'store_master_id'                       => $value['store_master_id'],
								'image'                                 => $s3Obj->getAwsUrl($filepath),
							    'mockup_image'	                        => $imageExtension,
							    'color'                                 => $variantColor
							];
						}
					}	
					self::execInBackground(common::SITE_URL . "update-shopify-images-background.php?response=" . urlencode(serialize($response)));	
				}
				$i++;
			}
		}
		echo json_encode($response);
		die();
	}

	public function getAssignedLogoDeatails(){
		if (isset($_GET['assignedLogoId'])) {
			$logoId = $_GET['assignedLogoId'];
			$sql   ='SELECT id,print_size,pantone_color,print_location,applicable_colors FROM store_design_logo_master WHERE id = '.$logoId.'';
			$data  =parent::selectTable_f_mdl($sql);

			$print_size_id     = '';
			$pantone_color_id  = '';
			$print_location_id = '';
			$applicableColors  = '';
			
			if (!empty($data)) {
				$print_size_id     = $data[0]['print_size'];
				$pantone_color_id  = $data[0]['pantone_color'];
				$print_location_id = $data[0]['print_location'];

				$applicable_colors = $data[0]['applicable_colors'];
				$applicable_color=explode(',',$applicable_colors);
				$JsoncolorArray = json_encode(array_values($applicable_color));
				$colorCodeValues  = str_replace (array('[', ']'), '' , $JsoncolorArray);
				//$colornameSql = "SELECT GROUP_CONCAT(product_color_name SEPARATOR ', ') as product_color_name FROM store_product_colors_master WHERE product_color IN ($colorCodeValues) ";

				$colornameSql   ="SELECT product_color, product_color_name FROM store_product_colors_master WHERE product_color IN ($colorCodeValues) ";
				$colornameSData =parent::selectTable_f_mdl($colornameSql);
				if (!empty($colornameSData)) {
					foreach ($colornameSData as $key => $color) {
						$colorCode = $color['product_color'];
						$colorName = $color['product_color_name'];
						$labelId = "logocolor_" . $key;

						$applicableColors .= "<label for=\"{$labelId}\" class=\"logocolor\">";
						$applicableColors .= "<span class=\"color_group_span\" style=\"background-color:{$colorCode}\" title=\"{$colorName}\">&nbsp;&nbsp;&nbsp;&nbsp;</span>";
						$applicableColors .= "{$colorName}</label>";
					}
				}
			}

			
			$sql1   ='SELECT * FROM print_sizes WHERE id = '.$print_size_id.'';
			$data1  =parent::selectTable_f_mdl($sql1);

			$print_size = '';
			if (!empty($data1)) {
				$print_size = $data1[0]['print_size'];
			}
			$sql2   ='SELECT * FROM pantone_colors WHERE id IN('.$pantone_color_id.')';
			$data2  =parent::selectTable_f_mdl($sql2);

			$pantone_color = '';
			if (!empty($data2)) {
				$colorArray = array();
				foreach ($data2 as $value) {
					$colorArray[] = $value['color_family'];
				}
				$pantone_color = implode(',', $colorArray);
			}

			$sql3   ='SELECT * FROM print_locations WHERE id = '.$print_location_id.'';
			$data3  =parent::selectTable_f_mdl($sql3);

			$print_location = '';
			if (!empty($data3)) {
				$print_location = $data3[0]['print_location'];
			}
			echo '<div class="associate_info assigned_logo_info logo-setting-col-location"><div class="associat_list assigned_logo_list"><div class="associate_title"><b class="mr-1">Location</b>'.$print_location.'</div></div><div class="associat_list assigned_logo_list "><div class="associate_title"><b class="mr-1">Color</b> '.$applicableColors.'</div></div></div>';
			die();
		}
	}

	public function getVariantImage(){
		/* Remove all images from local */
		self::execInBackground(common::SITE_URL."remove-images-background.php");

		if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
			global $path;
			global $s3Obj;
			$res =  array();
			$product_id   = (!empty($_POST['product_id']))?$_POST['product_id']:'';
			$variantColor = (!empty($_POST['variantColor']))?$_POST['variantColor']:'';
			if(empty($variantColor)){
				$sql = 'SELECT original_image,store_owner_product_master_id,color FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id="' . $product_id . '" group by color  LIMIT 1';
			}else{
				$sql = 'SELECT original_image,store_owner_product_master_id,color FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id="' . $product_id . '" AND color ="'.$variantColor.'" group by color ';
			}
			$var_list     =  parent::selectTable_f_mdl($sql);
			if (!empty($var_list)) {
				$variantImage = $var_list[0]['original_image'];
				$imgFile = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$variantImage);
				$tmpPath1 = $path.common::IMAGE_UPLOAD_S3_PATH.$variantImage;
	            @file_put_contents($tmpPath1, @file_get_contents($imgFile));
	            $variantImageUrl =common::SITE_URL.common::IMAGE_UPLOAD_S3_PATH.$variantImage;
	            $res['variant_image']    = $variantImageUrl;
	            $res['STATUS']           = "TRUE";
			}else{
				$res['variant_image']    = '';
				$res['STATUS']           = "FALSE";
			}
			echo json_encode($res);die();
		}
	}

	public function getDesabledGroup($store_master_id,$groupName)
	{	
		if ($store_master_id) {
			$sql          = 'SELECT * FROM `store_design_logo_master` WHERE store_master_id = '.$store_master_id.' AND FIND_IN_SET("'.$groupName.'",assigned_group) ';
			$desableGroup = parent::selectTable_f_mdl($sql);
			if(!empty($desableGroup)){
				echo "disabled";
			}else{
				echo "";
			}
		}
		else{
			echo "";
		}
	}

	function fetchStoreTokenInfo()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("action")) && parent::getVal("action") == "fetch-stkn") {
				$masterStoreId = parent::getVal("sid");

				parent::fetchStoreTokenInfo_f_mdl($masterStoreId);
			}
		}
	}

	public function getCustomizationDetails(){
		global $path;
		global $s3Obj;
		$res =  array();
		$product_id = (!empty($_POST['product_id']))?$_POST['product_id']:'';
		$logo_id    = (!empty($_POST['logo_id']))?$_POST['logo_id']:'';
		
		$varSql       = 'SELECT color,store_owner_product_master_id,assign_logo_width,assign_logo_height,assign_logo_leftcoordinates,assign_logo_topcoordinates FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id='.$product_id.' group by color ';
		$varColors    =  parent::selectTable_f_mdl($varSql);
		$variant_color =array();
		if(!empty($varColors)){
			$i = 0;
			foreach ($varColors as $value) {
				$sql1      = 'SELECT product_color_name,product_color FROM store_product_colors_master where product_color = "'.$value['color'].'" ';
				$colorName = parent::selectTable_f_mdl($sql1);
				$product_color_name = '';
				if(!empty($colorName)){
					$product_color_name = $colorName[0]['product_color_name'];
				}

				$variant_color[$i]= array(
					"color"              => $value['color'],
					"product_color_name" => $product_color_name
				);
				$i++;
			}
		}else{
			$varColors[0]['assign_logo_width']='';
			$varColors[0]['assign_logo_height']='';
		}
		$sql          = 'SELECT original_image,associate_with_logo_id,store_owner_product_master_id,color FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id="' . $product_id . '"';
		$var_list     =  parent::selectTable_f_mdl($sql);
		$imageUrl     = '';
		$productImage = '';
		$divassignPrintFiles = '';
		if(!empty($var_list)){
			$sqlNew = 'SELECT associate_with_logo_id FROM store_owner_product_master WHERE id="'.$product_id.'" ';
	        $logoIdData = parent::selectTable_f_mdl($sqlNew);
	        if(empty($logoIdData)){
	        	$logoIdData[0]['associate_with_logo_id']='';
	        }
			$sqlNew = 'SELECT id,logo_image,applicable_colors FROM store_design_logo_master WHERE  id IN ('.$logoIdData[0]['associate_with_logo_id'].')';
	        $assignlogofiles = parent::selectTable_f_mdl($sqlNew);
	        if(!empty($assignlogofiles)){
	            foreach($assignlogofiles as $singlelogofile){
	                $logoImage= $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$singlelogofile['logo_image']);
	                $imgPath = pathinfo($logoImage, PATHINFO_EXTENSION);
	                $imgExt = explode("?",$imgPath);
	                if($imgExt[0] == 'ai'){
	                    $divassignPrintFiles .= '<input style="text-indent: 118%;white-space: nowrap;overflow: hidden;" title="Assigned Print File" onclick="appendLogoImage(this,'.$singlelogofile['id'].')" class="print_file_image" id="logo_image_file_'.$singlelogofile['id'].'" value="'.$singlelogofile['id'].'" type="image" src='.$logoImage.' alt="" width="48" height="48" applicable_colors="'.$singlelogofile['applicable_colors'].'" />';
	                }
	                else{
	                    $divassignPrintFiles .= '<input title="Assigned Logo" onclick="appendLogoImage(this,'.$singlelogofile['id'].')" class="print_file_image" id="logo_image_file_'.$singlelogofile['id'].'" value="'.$singlelogofile['id'].'" type="image" src='.$logoImage.' alt="" width="48" height="48" applicable_colors="'.$singlelogofile['applicable_colors'].'" />';
	                }
	            }
	        }

			$sqlNew = 'SELECT logo_image,print_location FROM `store_design_logo_master` WHERE id = "'.$logo_id.'"';
			$logoResult = parent::selectTable_f_mdl($sqlNew);
			$logoImage  = '';
			$logoUrl    = '';
			$print_location_id =$print_location_name=$default_title= '';
			if(!empty($logoResult)){
				$print_location_id = (!empty($logoResult[0]['print_location']))?$logoResult[0]['print_location']:0;
				$logoImage = $logoResult[0]['logo_image'];
				$logoFile = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$logoImage);
				$tmpPath1 = $path.common::IMAGE_UPLOAD_S3_PATH.$logoImage;
	            @file_put_contents($tmpPath1, @file_get_contents($logoFile));
	            $logoUrl =common::SITE_URL.common::IMAGE_UPLOAD_S3_PATH.$logoImage;
			}

			$store_product_master_id = '';
			$top_coordinates  = '';
			$left_coordinates = '';
			$logo_width       = '';
			$logo_height      = '';
			$resized_height      = '';
			$area_top_coordinates = '';
			$area_left_coordinates = '';
			$area_width = '';
			$area_height = '';
			$maxwidthinch=$maxheightinch='';
			$width_in_inches=$height_in_inches='';

			$ActualLogowidth=$ActualLogoheight='';
            if(!empty($logoUrl)){
				$imagegetData 		= file_get_contents($logoUrl);
				$logoImgimage 		= imagecreatefromstring($imagegetData);
				$imageInfo 			= getimagesize($logoUrl);
				$ActualLogowidth 	= $imageInfo[0];
				$ActualLogoheight 	= $imageInfo[1];
				$dpi 				= imageresolution($logoImgimage);
				$logowidth_inch		= $ActualLogowidth/$dpi[0];
				$logoheight_inch	= $ActualLogoheight/$dpi[0];
				$logo_dpi			= $dpi[0];
				$logowidth_inch 	= number_format($logowidth_inch,'2','.','');
				$logoheight_inch 	= number_format($logoheight_inch,'2','.','');
			}

			$sqlprintloc = 'SELECT default_title,print_location FROM `print_locations` WHERE id = "'.$print_location_id.'"';
			$printlocResult = parent::selectTable_f_mdl($sqlprintloc);
			if(!empty($printlocResult)){
				$print_location_name=$printlocResult[0]['print_location'];
				$default_title=$printlocResult[0]['default_title'];
			}

			$productImage = $var_list[0]['original_image'];
			$color        = $var_list[0]['color'];
			$store_owner_product_master_id = $var_list[0]['store_owner_product_master_id'];
			$productSql = 'SELECT store_product_master_id FROM store_owner_product_master WHERE id = '.$store_owner_product_master_id.' AND is_soft_deleted="0" ';
			$proRes     = parent::selectTable_f_mdl($productSql);

			if(!empty($proRes)){
				$store_product_master_id = $proRes[0]['store_product_master_id'];
				$coordsSql = 'SELECT * FROM logo_coordinates as lc LEFT JOIN area_coordinates as ac ON lc.store_product_master_id = ac.store_product_master_id WHERE lc.store_product_master_id = '.$store_product_master_id.' AND lc.print_location_id = '.$print_location_id.' ';
				$coordsResult = parent::selectTable_f_mdl($coordsSql);
				if(isset($coordsResult)  && !empty($coordsResult)){
					$top_coordinates  = $coordsResult[0]['top_coordinates'];
					$left_coordinates = $coordsResult[0]['left_coordinates'];
					$logo_width       = $coordsResult[0]['logo_width'];
					$logo_height      = $coordsResult[0]['logo_height'];

					$area_top_coordinates  = $coordsResult[0]['area_top_coordinates'];
					$area_left_coordinates = $coordsResult[0]['area_left_coordinates'];
					$area_width       = $coordsResult[0]['area_width'];
					$area_height      = $coordsResult[0]['area_height'];
				}

				$masterProSql = 'SELECT * FROM store_product_master as spm LEFT JOIN fulfillengine_products_master as fpm ON fpm.catalog_product_id = spm.vendor_product_id WHERE spm.id = "'.$store_product_master_id.'" GROUP BY fpm.catalog_product_id ';
				$masterProSqlResult = parent::selectTable_f_mdl($masterProSql);

				$sql_vendor = 'SELECT id,vendor_name FROM `store_vendors_master` WHERE id = "'.$masterProSqlResult[0]['vendor_id'].'" ';
				$vendorNameData = parent::selectTable_f_mdl($sql_vendor);
				$product_vendor='';
				if(!empty($vendorNameData)){
					$product_vendor=$vendorNameData[0]['vendor_name'];
				}

				if($area_width==''){
					$area_width=1;
					$area_height=1;
				}
				if($logo_width==''){
					$logo_width=1;
				}
				if($ActualLogowidth==''){
					$ActualLogowidth=$ActualLogoheight=$logo_dpi=1;
				}

				if($default_title=='FulfillEngine'){
					
					if(!empty($masterProSqlResult) && !empty($print_location_name)){
						$maxwidthinch  = $masterProSqlResult[0][$print_location_name . "_width"];
						$maxheightinch = $masterProSqlResult[0][$print_location_name . "_height"];

						$maxwidthinch 			= !empty($maxwidthinch) ? $maxwidthinch : 1;
						$maxheightinch 			= !empty($maxheightinch) ? $maxheightinch : 1;
						$perinch_pixel_width 	= ceil($area_width / $maxwidthinch);
						$perinch_pixel_height 	= ceil($area_height / $maxheightinch);
						$original_width 		= $ActualLogowidth; // in pixels
						$original_height 		= $ActualLogoheight; // in pixels
						$logowidth_inchglobal 	= $logo_width / $perinch_pixel_width;
						$logowidth_inchglobal 	= number_format($logowidth_inchglobal , 2);  // in inch
						$scaling_factor 		= $logo_width / $original_width;
						// Calculate the new height based on the scaling factor
						$resized_height 		= $original_height * $scaling_factor;
						$resized_height 		= number_format($resized_height , 2);
						$pixels_per_inch 		= $perinch_pixel_width; 
						// Calculate the width and height in inches
						$width_in_inches 		= number_format($logo_width / $pixels_per_inch , 2);
						$height_in_inches 		= number_format($resized_height / $pixels_per_inch , 2);
					}
				}else{
					$maxwidthinch =  1;
					$maxheightinch = 1;
					$perinch_pixel_width = ceil($area_width / $maxwidthinch);
					$perinch_pixel_height = ceil($area_height / $maxheightinch);
					$original_width = $ActualLogowidth; // in pixels
					$original_height = $ActualLogoheight; // in pixels
					$logo_width = $logo_width; // in pixels, assuming $logo_width is defined somewhere
					// Calculate the aspect ratio of the original logo
					$aspect_ratio = $original_width / $original_height;
					// Calculate the height based on the aspect ratio
					$logo_height = $logo_width / $aspect_ratio;
					// Calculate scaling factor based on the maximum width
					$scaling_factor_width = $logo_width / $original_width;
					// Calculate scaling factor based on the maximum height
					$scaling_factor_height = $logo_height / $original_height;
					// Use the smaller scaling factor to maintain aspect ratio
					$scaling_factor = min($scaling_factor_width, $scaling_factor_height);
					// Calculate the new width and height based on the scaling factor
					$resized_width = $original_width * $scaling_factor;
					$resized_height = $original_height * $scaling_factor;
					// Convert resized dimensions to inches using pixels per inch
					$width_in_inches = number_format($resized_width / $perinch_pixel_width, 2);
					$height_in_inches = number_format($resized_height / $perinch_pixel_height, 2);
				}

			}

			if($varColors[0]['assign_logo_width']=='' && $varColors[0]['assign_logo_height']==''){				
				$logo_width       = $logo_width;
				$logo_height      = $resized_height;
				$top_coordinates  =	$top_coordinates;
				$left_coordinates = $left_coordinates;
			}else{
				$logo_width       = $varColors[0]['assign_logo_width'];
				$logo_height      = $varColors[0]['assign_logo_height'];
				$top_coordinates  = $varColors[0]['assign_logo_topcoordinates'];
				$left_coordinates = $varColors[0]['assign_logo_leftcoordinates'];
			}

			$filename = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$productImage);
			$tmpPath = $path.common::IMAGE_UPLOAD_S3_PATH.$productImage;
            @file_put_contents($tmpPath, @file_get_contents($filename));
            $imageUrl =common::SITE_URL.common::IMAGE_UPLOAD_S3_PATH.$productImage;

            

            $res['SUCCESS']          = 'TRUE';
			$res['IMAGE_URL']        = $imageUrl;
			$res['LOGO_URL']         = $logoUrl;
			$res['TOP_COORDINATES']  = $top_coordinates;
			$res['LEFT_COORDINATES'] = $left_coordinates;
			$res['COLOR']            = $color;
			$res['variant_color']    = $variant_color;
			$res['LOGO_WIDTH']       = $logo_width;
			$res['LOGO_HEIGHT']      = $logo_height;

			$res['maxwidthinch']        = $maxwidthinch;
			$res['maxheightinch']       = $maxheightinch;
			$res['print_location_name'] = $print_location_name;
			$res['logowidth_inch']      = $width_in_inches;
			$res['logoheight_inch']     = $height_in_inches;
			$res['logo_dpi']            = $logo_dpi;
			$res['product_vendor']      = $product_vendor;

			$res['AREA_TOP_COORDINATES']  = $area_top_coordinates;
			$res['AREA_LEFT_COORDINATES'] = $area_left_coordinates;
			$res['AREA_LOGO_WIDTH']       = $area_width;
			$res['AREA_LOGO_HEIGHT']      = $area_height;
			$res['divassignPrintFiles']   = $divassignPrintFiles;
		}else{
			$res['SUCCESS']          = 'FALSE';
			$res['IMAGE_URL']        = '';
			$res['LOGO_URL']         = '';
			$res['TOP_COORDINATES']  = '';
			$res['LEFT_COORDINATES'] = '';
			$res['COLOR']            = '';
			$res['variant_color']    = '';
			$res['LOGO_WIDTH']       = '';
			$res['LOGO_HEIGHT']      = '';

			$res['maxwidthinch']        = '';
			$res['maxheightinch']       = '';
			$res['print_location_name'] = '';
			$res['logowidth_inch']      = '';
			$res['logoheight_inch']     = '';
			$res['logo_dpi']            = '';
			$res['product_vendor']		= '';

			$res['AREA_TOP_COORDINATES']  = '';
			$res['AREA_LEFT_COORDINATES'] = '';
			$res['AREA_LOGO_WIDTH']       = '';
			$res['AREA_LOGO_HEIGHT']      = '';
			$res['divassignPrintFiles']   = '';
		}
		echo json_encode($res);die();
	}

	// public function uploadLogo()
	// {
	// 	global $s3Obj;
	// 	global $path;
	// 	$res =  array();
	// 	if (isset($_POST['product_id'])) {
	// 		$product_id         = (!empty($_POST['product_id']))?$_POST['product_id']:'';
	// 		$variant_color_name = (!empty($_POST['variant_color_name']))?$_POST['variant_color_name']:'';
	// 		$variantSize        = (!empty($_POST['variantSize']))?$_POST['variantSize']:'dummy';
	// 		$sql                = 'SELECT id FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id="' . $product_id . '" AND color = "'.$variant_color_name.'" AND size like "%'.$variantSize.'%"';
	// 		$var_list           =  parent::selectTable_f_mdl($sql);
	// 		if (!empty($var_list)) {
	// 			$baseFromJavascript = $_POST['data'];
	// 			// remove the part that we don't need from the provided image and decode it
	// 			$data     = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $baseFromJavascript));
	// 			$imageExtension = time().'_canvasImage.jpeg';
	// 			$filepath       = common::IMAGE_UPLOAD_S3_PATH.$imageExtension; // or image.jpg
	// 			$s3Obj->putObject($filepath,$data);
	// 			foreach ($var_list as $value) {
	// 				$UpdateData = [
	// 					'image' => $imageExtension
	// 				];
	// 				parent::updateTable_f_mdl('store_owner_product_variant_master',$UpdateData,'id="'.$value['id'].'"');
	// 			}
	// 			$res['SUCCESS'] = 'TRUE';
	// 			$res['MESSAGE'] = 'Logo Assigned successfully.';
	// 		}else{
	// 			$res['SUCCESS'] = 'FALSE';
	// 			$res['MESSAGE'] = 'Please choose correct combination!';
	// 		}
	// 	}
	// 	echo json_encode($res);die();
	// }
	/* Task 63 end */

	public function saveOrUpdateRedirects()
	{
		$res = [];
		if(!empty($_POST['redirect_from']) && !empty($_POST['redirect_to'])){
			$redirectData = '{"redirect":{"path": "'.$_POST['redirect_from'].'", "target": "'.$_POST['redirect_to'].'"}}';
			$curl = curl_init();
			$shopifyStoreUrl = '';
			$shopifyStoreUrl = "https://".common::PARENT_STORE_NAME."/admin/api/2023-04/redirects.json";
			curl_setopt_array($curl, array(
			  CURLOPT_URL => ''.$shopifyStoreUrl.'',
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'POST',
			  CURLOPT_POSTFIELDS =>$redirectData,
			  CURLOPT_HTTPHEADER => array(
			    'X-Shopify-Access-Token: '.common::SHOPIFY_SECRET_PASSWORD.'',
			    'Content-Type: application/json'
			  ),
			));

			$response = curl_exec($curl);

			curl_close($curl);
			$data = json_decode($response,TRUE);
			if(isset($data['redirect']['id'])){
				$redirectionInsert = [
					"redirect_to"         => $_POST['redirect_to'],
					"redirect_from"       => $_POST['redirect_from'],
					"redirect_from_store" => $_POST['redirect_from_store'],
					"owners_store_name"   => $_POST['owners_store_name'],
					"store_master_id"     => $_POST['store_master_id'],
					"redirection_id"      => trim($data['redirect']['id'])
				];
				parent::insertTable_f_mdl('url_redirections',$redirectionInsert);

				$res['STATUS']  = 'TRUE';
				$res['MESSAGE'] = 'Redirect url created successfully';
			}else{
				$res['STATUS']  = 'FALSE';
				$res['MESSAGE'] = '!Something went wrong';
			}
		}
		echo json_encode($res);die();
	}

	public function getOwnerStores($store_owner_detail_master_id,$store_master_id){
		$sql = "SELECT id,store_name,shop_collection_handle FROM store_master where id !=".$store_master_id." AND store_owner_details_master_id = '".$store_owner_detail_master_id."' AND verification_status = '1' AND status = '1' AND shop_collection_handle!='' ";
		$getOwnerStores = parent::selectTable_f_mdl($sql);
		return $getOwnerStores;
	}

	public function getStoreHandle()
	{
		$handle = '';
		if (isset($_POST['owner_store_id'])) {
			$owner_store_id = (!empty($_POST['owner_store_id']))?$_POST['owner_store_id']:'';
			$sql = "SELECT shop_collection_handle FROM store_master where id = '".$owner_store_id."' ";
			$getStoreHandle = parent::selectTable_f_mdl($sql);
			if(!empty($getStoreHandle)){
				$handle = '/collections/'.$getStoreHandle[0]['shop_collection_handle'];
			}
		}
		echo $handle;
	}

	public function getRedirects($store_master_id)
	{
		$sql = "SELECT * FROM url_redirections WHERE store_master_id = ".$store_master_id." ";
		$getRedirects = parent::selectTable_f_mdl($sql);
		return $getRedirects;
	}

	public function deleteRedirects()
	{
		$res = [];
		if (isset($_POST['redirect_id']) && !empty($_POST['redirect_id'])) {
			$shopifyStoreUrl = '';
	        $shopifyStoreUrl = "https://".common::PARENT_STORE_NAME."/admin/api/2023-04/redirects/".$_POST['redirect_id'].".json";

	        $curl = curl_init();
			curl_setopt_array($curl, array(
			  CURLOPT_URL => ''.$shopifyStoreUrl.'',
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'DELETE',
			  CURLOPT_HTTPHEADER => array(
			    'X-Shopify-Access-Token: '.common::SHOPIFY_SECRET_PASSWORD.'',
			    'Content-Type: application/json'
			  ),
			));

			$response = curl_exec($curl);
			$status = 0;
			if($response){
				$status = 1;
			}

			if($status==1){
				parent::deleteTable_f_mdl('url_redirections', 'redirection_id ='.$_POST['redirect_id']);
				$res['STATUS']  = 'TRUE';
				$res['MESSAGE'] = 'Redirect url deleted successfully';
			}else{
				$res['STATUS']  = 'FALSE';
				$res['MESSAGE'] = '!Something went wrong';
			}
		}
		echo json_encode($res);die();
	}

	public function create_duplicate_group()
	{
		$res =  array();
		if(!empty($_POST['store_master_id'])){
			$store_master_id = trim($_POST['store_master_id']);
			$groupname_old=trim($_POST['groupname_old']);
			$duplicate_group_name=trim($_POST['duplicate_group_name']);
			$duplicate_group_keyword=trim($_POST['duplicate_group_keyword']);
			$sql = 'SELECT * FROM `store_master` WHERE id="' . $store_master_id . '"';
			$store_master_data =  parent::selectTable_f_mdl($sql);
			$sql='SELECT product_title FROM store_owner_product_master WHERE store_master_id="'.$store_master_id. '"';
			$pro_data_new = parent::selectTable_f_mdl($sql);
			$sql1='SELECT product_title FROM store_owner_product_master WHERE store_master_id="' . $store_master_id . '" AND group_name="'.$groupname_old.'"';
			$pro_data_old = parent::selectTable_f_mdl($sql1);

			foreach ($pro_data_new as $single_product) {
				$product_title=trim($single_product['product_title']);
				$unique_keyget = substr($product_title, strpos($product_title, "_") + 1); 

				if($unique_keyget==$duplicate_group_keyword){
					$res['SUCCESS'] = 'FALSE';
					$res['MESSAGE'] = 'This keyword is already used in this store.';
					echo json_encode($res);die;
				}
			}

			$sql = 'SELECT yard_sign_description FROM `general_settings_master` limit 1';
			$yardsign_description =  parent::selectTable_f_mdl($sql);
			if($duplicate_group_name=='Yard Signs'){
				$sm_update_data = [
					'store_description' => $yardsign_description[0]['yard_sign_description']
				];
				parent::updateTable_f_mdl('store_master',$sm_update_data,'id="'.$store_master_id.'"');
			}

			$sql = 'SELECT * FROM `store_owner_product_master` WHERE store_master_id="' . $store_master_id . '" AND group_name = "'.$groupname_old.'" AND is_soft_deleted="0" ';
			$pro_list =  parent::selectTable_f_mdl($sql);
			if (!empty($pro_list)) {
				foreach ($pro_list as $single_pro) {
					$store_owner_product_master_id = $single_pro['id'];
					$fundSql= 'SELECT fundraising_price,color FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id = '.$store_owner_product_master_id.' ';
					$fundData =  parent::selectTable_f_mdl($fundSql);
					$fundraising_price = 0;
					if(!empty($fundData)){
						$fundraising_price = $fundData[0]['fundraising_price'];
					}
					
					$productColors = '';
					$productCode = array();
					if(isset($_POST['productColorArray']) && sizeof($_POST['productColorArray']) > 0){
						$productColors = $_POST['productColorArray'];
						foreach ($productColors as $value) {
							$sql1      = 'SELECT product_color FROM store_product_colors_master where product_color_name = "'.trim($value).'" ';
							$colorName = parent::selectTable_f_mdl($sql1);
							if (!empty($colorName)) {
								foreach ($colorName as $valueC) {
									$productCode[] = $valueC['product_color'];
								}
							}
						}
						$JsonproductArray = json_encode(array_values($productCode));
						$colorCodeValues  = str_replace (array('[', ']'), '' , $JsonproductArray);
					}else{
						$colorArray = [];
						if(!empty($fundData)){
							$fundraising_price = $fundData[0]['fundraising_price'];
							foreach ($fundData as $Fvalue) {
								$colorArray[]= $Fvalue['color'];
							}
						}

						$JsonproductArray = json_encode(array_values($colorArray));
						$colorCodeValues  = str_replace(array('[', ']'), '', $JsonproductArray);
					}

					if($single_pro['store_product_master_id']=='789' || $single_pro['store_product_master_id']=='169'){
						$is_persionalization = '1';
						$is_require = '1';
					}else{
						$is_persionalization = '0';
						$is_require = '0';
					}
	
					$existSql = 'SELECT * FROM `store_product_variant_master` WHERE color IN ('.$colorCodeValues.') AND store_product_master_id="'.$single_pro['store_product_master_id'].'" and store_organization_type_master_id = '.$_POST["store_organization_type_master_id"].' AND is_ver_deleted="0" ';
					$existData =  parent::selectTable_f_mdl($existSql);
					if (!empty($existData)) {
						//insert product details
						$sopm_insert_data = [
							'store_master_id'              => $store_master_id,
							'store_product_master_id'      => $single_pro['store_product_master_id'],
							'product_title'                => $single_pro['product_title']."_".$duplicate_group_keyword,
							'product_description'          => $single_pro['product_description'],
							'tags'                         => $single_pro['tags'],
							'status'                       => '1',
							'is_personalization' 		   => $is_persionalization,
							'is_required'		 		   => $is_require,
							'created_on'                   => @date('Y-m-d H:i:s'),
							'created_on_ts'                => time(),
							'group_name'                   => $duplicate_group_name,
							'is_product_synced_to_collect' => '0'
						];
						$sopm_arr = parent::insertTable_f_mdl('store_owner_product_master', $sopm_insert_data);
						$sqlmasterprod = 'SELECT id,is_eligible_sleeve_print from store_product_master where id="'.$single_pro['store_product_master_id'].'" AND is_deleted="0" ';
						$productMasterdata = parent::selectTable_f_mdl($sqlmasterprod);
						$is_eligible_sleeve_print='0';
						if(!empty($productMasterdata)){
							$is_eligible_sleeve_print=$productMasterdata[0]['is_eligible_sleeve_print'];
						}
						if (isset($sopm_arr['insert_id'])) {
							$sopm_id  = $sopm_arr['insert_id'];

							$sql = 'SELECT sopvm.front_side_ink_colors_group,sopvm.back_side_ink_colors_group,sopvm.sleeve_ink_color_group,sopvm.is_back_enable_group FROM store_owner_product_master as sopm INNER JOIN store_owner_product_variant_master as sopvm ON sopm.id=sopvm.store_owner_product_master_id WHERE sopm.store_master_id="'.$store_master_id.'" AND sopm.group_name="'.$duplicate_group_name.'" LIMIT 1';
							$prodInkCostGroup =  parent::selectTable_f_mdl($sql);

							$sqlGroupHistory = 'SELECT changed_sleeve_ink_group_price FROM group_ink_price_history WHERE store_master_id="'.$store_master_id.'" AND  group_name="'.$duplicate_group_name.'" ORDER BY id DESC LIMIT 1';
							$prodInkCostGroupHistoryData =  parent::selectTable_f_mdl($sqlGroupHistory);
							$prodInkCostGroupHistory='0';
							if(!empty($prodInkCostGroupHistoryData)){
								$prodInkCostGroupHistory = $prodInkCostGroupHistoryData[0]['changed_sleeve_ink_group_price'];
							}

							foreach ($existData as $var_data) {
								$image = $var_data['image'];

								$sql = 'SELECT price,price_on_demand from store_product_variant_master where id="'.$var_data['id'].'" AND is_ver_deleted="0" ';
								$storeProductVariantMaster = parent::selectTable_f_mdl($sql);

								$add_cost = 0;
								if(isset($prodInkCostGroup[0]['front_side_ink_colors_group']) && !empty($prodInkCostGroup[0]['front_side_ink_colors_group'])){
									$add_cost += intval($prodInkCostGroup[0]['front_side_ink_colors_group'])-1;
								}else if(isset($store_master_data[0]['front_side_ink_colors']) && !empty($store_master_data[0]['front_side_ink_colors'])){
									$add_cost += intval($store_master_data[0]['front_side_ink_colors'])-1;
								}

								$add_on_cost = 0;// Task 50 Add new variable for on_demand
								if(isset($prodInkCostGroup[0]['back_side_ink_colors_group']) && !empty($prodInkCostGroup[0]['back_side_ink_colors_group'])){
									$add_cost   += common::ADD_COST_BACK_SIDE_INK_COLOR+intval($prodInkCostGroup[0]['back_side_ink_colors_group'])-1;
									$add_on_cost = common::ADD_COST_BACK_SIDE_INK_COLOR;// Task 50 Add new variable for on_demand
								}else if(isset($store_master_data[0]['back_side_ink_colors']) && !empty($store_master_data[0]['back_side_ink_colors'])){
									$add_cost   += common::ADD_COST_BACK_SIDE_INK_COLOR+intval($store_master_data[0]['back_side_ink_colors'])-1;
									$add_on_cost = common::ADD_COST_BACK_SIDE_INK_COLOR;// Task 50 Add new variable for on_demand
								}

								if(isset($prodInkCostGroupHistory) && !empty($prodInkCostGroupHistory)){
									$add_cost += common::ADD_COST_BACK_SIDE_INK_COLOR + intval($prodInkCostGroupHistory)-1;
								}else if(isset($store_master_data[0]['sleeve_ink_colors']) && !empty($store_master_data[0]['sleeve_ink_colors'])){
									$add_cost += common::ADD_COST_BACK_SIDE_INK_COLOR + intval($store_master_data[0]['sleeve_ink_colors'])-1;
								}

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
								}else if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
									$fullfilment_type_price = $fullfilment_platinum_price;
									//$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_3;
								}else if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=='SHIP_1_LOCATION_NOT_SORT'){
									$fullfilment_type_price = $fullfilment_silver_price;
									//$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_3;
								}
								
								$sqlmaster_group = 'SELECT id,group_id from store_product_master where id="'.$single_pro['store_product_master_id'].'" AND is_deleted="0" ';
								$storeProductMasterGroup = parent::selectTable_f_mdl($sqlmaster_group);
								$group_id='';
								if(!empty($storeProductMasterGroup)){
									$group_id=$storeProductMasterGroup[0]['group_id'];
								}
								$ondemandPrice  = 0;
								$flashSalePrice = 0;
								if(isset($storeProductVariantMaster[0]['price']) && $storeProductVariantMaster[0]['price_on_demand']){
									if($group_id=='9'){
										$ondemandPrice = (floatval($storeProductVariantMaster[0]['price_on_demand']));
										$flashSalePrice     = $storeProductVariantMaster[0]['price'];
									}else{
										$ondemandPrice  = (floatval($storeProductVariantMaster[0]['price_on_demand'])+$add_on_cost);
										$flashSalePrice = $storeProductVariantMaster[0]['price']+$add_cost+$fullfilment_type_price;
									}
								}else{
									$ondemandPrice  = $var_data['price_on_demand'];
									$flashSalePrice = $var_data['price'];
								}

								if(!empty($prodInkCostGroup)){
									$front_side_ink_colors_group=trim($prodInkCostGroup[0]['front_side_ink_colors_group']);
									$back_side_ink_colors_group=trim($prodInkCostGroup[0]['back_side_ink_colors_group']);
									$sleeve_ink_color_group=trim($prodInkCostGroup[0]['sleeve_ink_color_group']);
									$is_back_enable_group=trim($prodInkCostGroup[0]['is_back_enable_group']);
								}else{
									$front_side_ink_colors_group=trim($store_master_data[0]['front_side_ink_colors']);
									$back_side_ink_colors_group=trim($store_master_data[0]['back_side_ink_colors']);
									$sleeve_ink_color_group=trim($store_master_data[0]['sleeve_ink_colors']);
									$is_back_enable_group=trim($store_master_data[0]['is_back_enable']);
								}

								if($is_eligible_sleeve_print=='0'){
									if(isset($prodInkCostGroupHistory) && !empty($prodInkCostGroupHistory)){
										$sleevecost =common::ADD_COST_BACK_SIDE_INK_COLOR + intval($prodInkCostGroupHistory)-1;
									}else if(isset($store_master_data[0]['sleeve_ink_colors']) && !empty($store_master_data[0]['sleeve_ink_colors'])){
										$sleevecost =common::ADD_COST_BACK_SIDE_INK_COLOR + intval($store_master_data[0]['sleeve_ink_colors'])-1;
									}
									$flashSalePrice = $flashSalePrice - $sleevecost;

									$sopvm_insert_data = [
										'store_owner_product_master_id'     => $sopm_id,
										'store_product_variant_master_id'   => $var_data['id'],
										'store_organization_type_master_id' => $var_data['store_organization_type_master_id'],
										'price'                             => $flashSalePrice,
										'price_on_demand'                   => $ondemandPrice,
										'fundraising_price'                 => $fundraising_price,
										'color'     		                => $var_data['color'],
										'size'      		                => $var_data['size'],
										'image'                             => $var_data['image'],
										'original_image'                    => $var_data['feature_image'],
										'sku' 				                => $var_data['sku'],
										'weight' 			                => $var_data['weight'],
										'front_side_ink_colors_group' 		=> $front_side_ink_colors_group,
										'back_side_ink_colors_group' 		=> $back_side_ink_colors_group,
										'sleeve_ink_color_group' 		    => '0',
										'is_back_enable_group'  	 		=> $is_back_enable_group,
										'status' 			                => '1',
										'created_on' 		                => @date('Y-m-d H:i:s'),
										'created_on_ts' 	                => time()
									];
								}else{
									$sopvm_insert_data = [
										'store_owner_product_master_id'     => $sopm_id,
										'store_product_variant_master_id'   => $var_data['id'],
										'store_organization_type_master_id' => $var_data['store_organization_type_master_id'],
										'price'                             => $flashSalePrice,
										'price_on_demand'                   => $ondemandPrice,
										'fundraising_price'                 => $fundraising_price,
										'color'     		                => $var_data['color'],
										'size'      		                => $var_data['size'],
										'image'                             => $var_data['image'],
										'original_image'                    => $var_data['feature_image'],
										'sku' 				                => $var_data['sku'],
										'weight' 			                => $var_data['weight'],
										'front_side_ink_colors_group' 		=> $front_side_ink_colors_group,
										'back_side_ink_colors_group' 		=> $back_side_ink_colors_group,
										'sleeve_ink_color_group' 		    => $sleeve_ink_color_group,
										'is_back_enable_group'  	 		=> $is_back_enable_group,
										'status' 			                => '1',
										'created_on' 		                => @date('Y-m-d H:i:s'),
										'created_on_ts' 	                => time()
									];
								}
								$res=parent::insertTable_f_mdl('store_owner_product_variant_master', $sopvm_insert_data);
							// print_r($res);die;
							}
							
							//now open store to sync in shopify
							$sm_update_data = [
								'is_products_synced' => '0',
								'is_manual_store_sync' => '1'
							];
							parent::updateTable_f_mdl('store_master',$sm_update_data,'id="'.$store_master_id.'"');
						}
						$res['SUCCESS'] = 'TRUE';
						$res['MESSAGE'] = 'Group duplicated successfully.';
					}
				}

				$varSql ="SELECT count(*) as totalVariant FROM store_owner_product_master as sopm LEFT JOIN store_owner_product_variant_master as sopvm ON sopvm.store_owner_product_master_id = sopm.id where sopm.store_master_id = '$store_master_id' and sopvm.id!='' ";
				$varInfo = parent::selectTable_f_mdl($varSql);
				$totalVariant = 0;
				if(!empty($varInfo)){
					$varData = $varInfo[0];
					$totalVariant = $varData['totalVariant'];
				}
				$updatVariantCount = [
					"total_variants_count"=>$totalVariant
				];
				parent::updateTable_f_mdl('store_master',$updatVariantCount,'id="'.$store_master_id.'"');
			}
			else{
				$res['SUCCESS'] = 'FALSE';
				$res['MESSAGE'] = 'Error while inserting store details. Please check and try again after some time.';
			}
			echo json_encode($res);
		}
		die;
	}

	public function getStoreBasicHistory($store_master_id,$section)
	{
		$sql = 'SELECT store_name,pre_store_name,start_date,pre_start_date,last_date,pre_last_date,due_date,pre_due_date,store_print_date,pre_store_print_date,store_po_details,pre_store_po_details,updated_by,updated_on FROM store_history WHERE store_master_id="'.$store_master_id.'" AND section_name = "'.$section.'" ORDER BY id DESC';
		$storeHistory = parent::selectTable_f_mdl($sql);
		return $storeHistory;
	}

	public function getStoreShippingHistory($store_master_id,$section)
	{
		$sql = 'SELECT pre_shipping_status,shipping_status,updated_by,updated_on FROM store_history WHERE store_master_id="'.$store_master_id.'" AND section_name = "'.$section.'" ORDER BY id DESC';
		$storeHistory = parent::selectTable_f_mdl($sql);
		return $storeHistory;
	}

	public function getStoreSettingOrganizationTypeHistory($store_master_id,$section)
	{
		$sql = 'SELECT organization_type,pre_organization_type,updated_on,updated_by FROM store_history WHERE store_master_id="'.$store_master_id.'" AND section_name = "'.$section.'" ORDER BY id DESC';
		$storeSettingHistory = parent::selectTable_f_mdl($sql);
		return $storeSettingHistory;
	}

	public function getStoreSettingHistory($store_master_id,$section)
	{
		$sql = 'SELECT store_type,pre_store_type,store_closed,pre_store_status,email_notification,pre_email_notification,updated_on,updated_by FROM store_history WHERE store_master_id="'.$store_master_id.'" AND section_name = "'.$section.'" ORDER BY id DESC';
		$storeSettingHistory = parent::selectTable_f_mdl($sql);
		return $storeSettingHistory;
	}

	public function getStoreFundraisingHistory($store_master_id,$section)
	{
		$sql = 'SELECT fundraising_bar_status,pre_fundraising_bar_status,fundraising_bar_goal,pre_fundraising_bar_goal,updated_on,updated_by FROM store_history WHERE store_master_id="'.$store_master_id.'" AND section_name = "'.$section.'" ORDER BY id DESC';
		$storeFundraisingHistory = parent::selectTable_f_mdl($sql);
		return $storeFundraisingHistory;
	}

	public function getStoreLevelSettingHistory($store_master_id,$section)
	{
		$sql = 'SELECT pre_front_side_ink_colors,pre_back_side_ink_colors,pre_store_fulfillment_type,front_side_ink_colors,back_side_ink_colors,store_fulfillment_type,updated_on,updated_by FROM store_history WHERE store_master_id="'.$store_master_id.'" AND section_name = "'.$section.'" ORDER BY id DESC';
		$storeFundraisingHistory = parent::selectTable_f_mdl($sql);
		return $storeFundraisingHistory;
	}

	public function getStoreFundraisingSettingHistory($store_master_id,$section)
	{
		$sql = 'SELECT pre_ct_fundraising_price,ct_fundraising_price,pre_ct_fundraising,ct_fundraising,updated_on,updated_by FROM store_history WHERE store_master_id="'.$store_master_id.'" AND section_name = "'.$section.'" ORDER BY id DESC';
		$storeFundraisingHistory = parent::selectTable_f_mdl($sql);
		return $storeFundraisingHistory;
	}

	/*quick buy task */
	public function get_all_product_byGroup(){
		//$postData,$fromSuperAdmin,$condLoginUser
		$reponse = storeHelper::getAllProductByGroup($_POST,true,false);
		echo $reponse;
	}

	public function addToCartQuickBuy(){
		$response = storeHelper::productsaddToCartQuickBuy($_POST);
		echo $response;
	}

	public function getOrganizationTypeList(){
		$sql = 'SELECT id, organization_name,status FROM `store_organization_type_master`';
		return parent::selectTable_f_mdl($sql);
	}

	public function getManagerList($store_master_id){
		$smm_data=[];
		$sql = 'SELECT sodm.id as store_owner_details_master_id,sodm.first_name,sodm.last_name,sodm.email,sodm.phone,sm.id as store_master_id FROM `store_owner_details_master` as sodm INNER JOIN store_master as sm ON sm.store_owner_details_master_id=sodm.id WHERE sm.id = "'.$store_master_id.'" ';
		$ownerInfo = parent::selectTable_f_mdl($sql);
		if(!empty($ownerInfo)){
			$sql_managerData = 'SELECT id,first_name,last_name,email,mobile,store_owner_id FROM store_manager_master where status="0" AND store_owner_id="' .$ownerInfo[0]['store_owner_details_master_id'] . '"';
			$smm_data =  parent::selectTable_f_mdl($sql_managerData);
		}
		return $smm_data;
	}

	public function changeStoreGroup()
	{	
		global $path;
		global $login_user_email;
		$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
		if(!empty($shop_data)) {
			require_once( $path.'lib/class_graphql.php');
			$shop  = $shop_data[0]['shop_name'];
			$token = $shop_data[0]['token'];
			$headers = array(
				'X-Shopify-Access-Token' => $token
			);
			$graphql = new Graphql($shop, $headers);
		}
		$res = [];
		if(!empty($_POST['store_master_id'])){
			$change_store_organization_type_master_id = $_POST['change_store_organization_type_master_id'];
			$old_store_organization_type_master_id = $_POST['old_store_organization_type_master_id'];
			$store_sale_type_master_id = $_POST['store_sale_type_master_id'];
			$store_master_id = $_POST['store_master_id'];
			if($change_store_organization_type_master_id != $old_store_organization_type_master_id){
				$sql = 'SELECT sopvm.id as product_variant_id,sopm.id as store_owner_product_master_id,sopm.group_name, sopm.store_product_master_id,sopvm.color,sopvm.price as so_price ,sopvm.price_on_demand as so_on_price,sopvm.store_organization_type_master_id,sopvm.shop_variant_id,sopvm.fundraising_price,sopvm.size FROM `store_owner_product_master` as sopm LEFT JOIN store_owner_product_variant_master as sopvm ON sopvm.store_owner_product_master_id = sopm.id WHERE sopm.`store_master_id` = "'.$store_master_id.'"';
				$getProducts = parent::selectTable_f_mdl($sql);
				if(!empty($getProducts)){
					foreach ($getProducts as $value) {
						$store_owner_product_master_id = $value['store_owner_product_master_id'];
						$store_product_master_id = $value['store_product_master_id'];
						$size = $value['size'];
						$group_name = $value['group_name'];
						$mspVariantSql = "SELECT id,price,price_on_demand,store_organization_type_master_id FROM store_product_variant_master where color = '".$value['color']."' AND store_organization_type_master_id = ".$change_store_organization_type_master_id." AND size = '".$size."' AND store_product_master_id = ".$store_product_master_id."  ";
						$getMspVariants = parent::selectTable_f_mdl($mspVariantSql);
						if(!empty($getMspVariants)){
							$add_cost = 0;
							if(isset($_POST['front_side_ink_colors']) && !empty($_POST['front_side_ink_colors'])){
								$add_cost += intval($_POST['front_side_ink_colors'])-1;
							}

							$add_on_cost = 0;
							if(isset($_POST['back_side_ink_colors']) && !empty($_POST['back_side_ink_colors'])){
								$add_cost   += common::ADD_COST_BACK_SIDE_INK_COLOR+intval($_POST['back_side_ink_colors'])-1;
								$add_on_cost = common::ADD_COST_BACK_SIDE_INK_COLOR;
							}
							
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
							if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
								$fullfilment_type_price = $fullfilment_gold_price;
								//$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_2;
							}else if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
								$fullfilment_type_price = $fullfilment_platinum_price;
								//$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_3;
							}else if(isset($_POST['store_fulfillment_type']) && $_POST['store_fulfillment_type']=='SHIP_1_LOCATION_NOT_SORT'){
								$fullfilment_type_price = $fullfilment_silver_price;
							}

							$sqlmaster_group = 'SELECT id,group_id from store_product_master where id="'.$store_product_master_id.'" AND is_deleted="0" ';
                  			$storeProductMasterGroup = parent::selectTable_f_mdl($sqlmaster_group);
							$group_id='';
							if(!empty($storeProductMasterGroup)){
								$group_id=$storeProductMasterGroup[0]['group_id'];
							}

							if($group_id=='9'){
								$ondemandPrice  = (floatval($getMspVariants[0]['price_on_demand']));
								$flashSalePrice = $getMspVariants[0]['price'];
							}else{
								$ondemandPrice  = (floatval($getMspVariants[0]['price_on_demand'])+$add_on_cost);
								$flashSalePrice = $getMspVariants[0]['price']+$add_cost+$fullfilment_type_price;
							}
							
							$updateVarData = [
								"store_product_variant_master_id"=>$getMspVariants[0]['id'],
								"store_organization_type_master_id"=>$change_store_organization_type_master_id,
								"price"=>$flashSalePrice,
								"price_on_demand"=>$ondemandPrice
							];

							// echo "<pre>";print_r($updateVarData);

							parent::updateVariant_f_mdl($getMspVariants[0]['id'],$change_store_organization_type_master_id,$flashSalePrice,$ondemandPrice,$value['product_variant_id']);
							
							$inputPrice = 0;
							if($store_sale_type_master_id==2){
								$inputPrice = $ondemandPrice + $value["fundraising_price"];
							}else{
								$inputPrice = $flashSalePrice + $value["fundraising_price"];
							}

							if(!empty($value['shop_variant_id'])){
								$shop_variant_id = $value['shop_variant_id'];
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
										"id":"gid://shopify/ProductVariant/'.$shop_variant_id.'",
										"price":"'.( $inputPrice ).'"
									}
								}';
								$dataVar = $graphql->runByMutation($mutationData,$inputData);
							}
						}	
					}
					$updateOrg = [
						"store_organization_type_master_id"=>$change_store_organization_type_master_id
					];
						
					$resPonseData = parent::updateTable_f_mdl('store_master',$updateOrg,'id="'.$store_master_id.'"');	
					$isSuccess    = $resPonseData['isSuccess'];
					
					$updatedBy = "Super Admin <br>(".$login_user_email.")";
					$basicData = [
						"section_name"    => "Store Setting",
						"store_master_id"    => trim($store_master_id),
						"pre_organization_type" =>  trim($old_store_organization_type_master_id),
						"organization_type"      => trim($change_store_organization_type_master_id),
						"store_type"             => $store_sale_type_master_id,
						"pre_store_type"         => $store_sale_type_master_id,
						"updated_by"      => $updatedBy,
						"updated_on"      => date('Y-m-d H:i:s')
					];
					$ress=parent::insertTable_f_mdl('store_history',$basicData);
					// echo "<pre>";print_r($ress);die();
					
					$res['SUCCESS'] = 'TRUE';
					$res['MESSAGE'] = 'organization type switched successfully.';	
				}else{
					$res['SUCCESS'] = 'FALSE';
					$res['MESSAGE'] = 'Product not found.';
				}
			}else{
				$res['SUCCESS'] = 'FALSE';
				$res['MESSAGE'] = 'The store is already in the current type.';
			}	
		}
		echo json_encode($res);die();
	}

	public function update_total_profit()
	{
		if (isset($_POST['store_master_id']) && !empty($_POST['store_master_id'])) {
			$total_profit=$_POST['total_profit'];
			$total_profit = str_replace(",","",$total_profit);
			$gross_sale=$_POST['gross_sale'];
			$gross_sale = str_replace(",","",$gross_sale);
			$fund_amount=$_POST['fund_amount'];
			$fund_amount = str_replace(",","",$fund_amount);
			$actual_gross_sale=$gross_sale-$fund_amount;
			$actual_gross_sale = str_replace(",","",$actual_gross_sale);
			if($actual_gross_sale=='0.00'){
				$profit_margin='0.00';
			}else{
				$profit_margin=($total_profit/$actual_gross_sale)*100;
				$profit_margin=number_format((float)$profit_margin, 2);
			}
			$profit_margin = str_replace(",","",$profit_margin);
			$totalProData = [
				'total_profit' =>(float)$total_profit,
				'profit_margin'=>$profit_margin
			];
			return parent::updateTable_f_mdl('store_master',$totalProData,'id="'.$_POST['store_master_id'].'"');
		}
	}
	
	public function execInBackground($url){
        $params = "";
        $path="/var/www/html/image_uploads/exec_error.json";
        $command = '/usr/bin/curl -H \'Content-Type: application/json\' -d \'' . $params . '\' --url \'' . $url . '\' >> '.$path.' 2> /dev/null &';
        exec($command);
    }

    public function removeLocalImagesBackground(){
        $folder_path = common::IMAGE_UPLOAD_S3_PATH;
		$files = glob($folder_path.'*'); 
		foreach($files as $file) {
		    if(is_file($file)){
		        if($file != "image_uploads/spirit.png" && $file != "image_uploads/resize.png" && $file != "image_uploads/avtaar.jpg"){
		        	unlink($file);
		        } 
			}
		}
    }

	public function updateShopifyImagesBackground($response)
    {	
		$store_master_id = '';
    	global $path;
		global $s3Obj;
    	$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
		if(!empty($shop_data)) {
			require_once( $path.'lib/class_graphql.php');
			$shop  = $shop_data[0]['shop_name'];
			$token = $shop_data[0]['token'];
			$headers = array(
				'X-Shopify-Access-Token' => $token
			);
			$graphql = new Graphql($shop, $headers);
		}

		if(!empty($response)){
			$store_master_id = $response['updatedImageProductsIds'][0]['store_master_id'];
			$handleSql = 'SELECT id,shop_collection_handle FROM store_master WHERE id = '.$store_master_id.' ';
			$store_data = parent::selectTable_f_mdl($handleSql);

			// if (!empty($store_data[0]["shop_collection_handle"])) {
				foreach (array_chunk($response['updatedImageProductsIds'],20) as $chunks) {
					foreach ($chunks as $value){
						$sql = 'SELECT id, shop_product_id, shop_variant_id FROM `store_owner_product_variant_master`
							WHERE id="'.$value['store_owner_product_variant_master_id'].'"
							AND store_owner_product_master_id="'.$value['store_owner_product_master_id'].'"';
						$var_data = parent::selectTable_f_mdl($sql);
						if(!empty($var_data)){
							$date = date('Y-m-d H:i:s');
							$mockupSql = "SELECT image,store_owner_product_variant_master_id FROM `store_logo_mockups_master` WHERE (image!='' or image IS NOT NULL) and store_owner_product_variant_master_id ='".$value['store_owner_product_variant_master_id']."' ";
							$mocupImage =  parent::selectTable_f_mdl($mockupSql);
							if(!empty($mocupImage)){
								$updateLogoMockupData = [
									'updated_date'                          => $date,
									'image'	                                => $value['mockup_image']
								];
								parent::updateTable_f_mdl('store_logo_mockups_master',$updateLogoMockupData,'store_owner_product_variant_master_id="'.$value['store_owner_product_variant_master_id'].'"');
							}else{
								$insertLogoMockupData = [
									'store_owner_product_master_id'         => $value['store_owner_product_master_id'],
									'store_owner_product_variant_master_id' => $value['store_owner_product_variant_master_id'],
									'store_master_id'                       => $value['store_master_id'],
									'image'	                                => $value['mockup_image'],
									'color'                                 => $value['color'],
									'created_date'                          => $date,
									'updated_date'                          => $date
								];
								parent::insertTable_f_mdl('store_logo_mockups_master',$insertLogoMockupData);
							}
							if(isset($var_data[0]['shop_product_id']) && !empty($var_data[0]['shop_product_id']) && isset($var_data[0]['shop_variant_id']) && !empty($var_data[0]['shop_variant_id']) &&
											isset($graphql)
										)
							{
								// $data = storeHelper::reorderingMediaFeatureImage($graphql,$var_data[0]['shop_product_id']);
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
								//Task 59
								$inputData = '{
								  "input": {
									"id": "gid://shopify/Product/'.$var_data[0]['shop_product_id'].'",
									"images": [
									  {
										"src": "'.$value['image'].'"
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
			// }
		}
    }

	public function getStoresMeetMinimum($storeMasterId){
		$data = array();

		$groupsql 			= 'SELECT id,group_name FROM store_owner_product_master WHERE store_master_id="'.$storeMasterId.'" AND is_soft_deleted="0" group by group_name';
		$product_group_name = parent::selectTable_f_mdl($groupsql);
		$status = "No";
		if(!empty($product_group_name)){
			$minimumMetAarray = array();
	    foreach ($product_group_name as $group_name) {
	      $store_owner_group_name        = $group_name['group_name'];
	      $groupItemSql = 'SELECT id FROM store_owner_product_master WHERE group_name="'.$store_owner_group_name.'" and store_master_id = "'.$storeMasterId.'" AND is_soft_deleted="0" ';
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

	      $minimumsSql = 'SELECT minimum_group_value from minimum_group_product WHERE product_group="'.$store_owner_group_name.'" ';
	      $minimum_group_value = parent::selectTable_f_mdl($minimumsSql);

	      $group_value = 0;
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
	    }
			if (in_array("1", $minimumMetAarray)){
				$status = "No";
			}
			else{
				$status = "Yes";
			}  
		}
		return $status;
	}

	public function followUpEmail(){
		$res=array();
		$response = storeHelper::sendFollowUpEmail($_POST);
		if($response){
			$res['isSuccess'] = '1';
			$res['msg'] = 'Mail sent successfully.';
		}else{
			$res['isSuccess'] = '0';
			$res['msg'] = '!Something went wrong.';
		}
		echo json_encode($res);die();
	}

	public function getFollowUpEmailHistory($store_master_id)
	{
		$sql = 'SELECT cust_email,update_on,sent_by FROM follow_up_email_history WHERE store_master_id="'.$store_master_id.'" ORDER BY id DESC';
		$FollowupEmailHistory = parent::selectTable_f_mdl($sql);
		return $FollowupEmailHistory;
	}

	public function getComposerEmailHistory($store_master_id)
	{
		
		$sql='SELECT id,store_master_ids,store_name,owner_email,from_email,subject,update_on,sent_by FROM compose_email_history WHERE  FIND_IN_SET("'.$store_master_id.'",store_master_ids) > 0 ORDER BY id DESC';
		$ComposeEmailHistory = parent::selectTable_f_mdl($sql);
		return $ComposeEmailHistory;
	}

	public function approved_products_sa()
	{
		$res =  array();
		if(!empty($_POST['store_master_id'])){
			$store_master_id = $_POST['store_master_id'];
			$store_owner_request_product_ids=$_POST['store_owner_request_product_ids'];
			$upload_dir = common::IMAGE_UPLOAD_S3_PATH;
			$sorp_ids=implode(",",$store_owner_request_product_ids);

			$sql = "SELECT *,
			(SELECT group_concat(store_product_master_id)  FROM store_owner_request_product_master WHERE id IN (".$sorp_ids.") ) as master_prod_id
			FROM store_owner_request_product_master WHERE store_master_id=".$store_master_id." AND  id IN (".$sorp_ids.")";
			$pro_list =  parent::selectTable_f_mdl($sql);
			
			if(!empty($pro_list)){
				foreach($pro_list as $single_pro){
					$pro_id = $single_pro['store_product_master_id'];
					$request_pro_id = $single_pro['id'];
					$is_product_fundraising = $single_pro['is_product_fundraising'];
					$product_fundraising_price = $single_pro['product_fundraising_price'];
					$group_name = $single_pro['group_name'];

					$sql1 = "SELECT * FROM store_owner_request_product_variant_master as sorpvm INNER JOIN store_owner_request_product_master as sorpm ON sorpm.id=sorpvm.store_owner_request_product_master_id WHERE sorpm.store_master_id=".$store_master_id." AND sorpm.store_product_master_id=".$pro_id."  group by sorpvm.color ";
					$pre_var_list =  parent::selectTable_f_mdl($sql1);

					if($pro_id=='789' || $pro_id=='169'){
						$is_persionalization = '1';
						$is_require = '1';
					}else{
						$is_persionalization = '0';
						$is_require = '0';
					}
				
					//first we check if product is already choose in store or not
					$sql = 'SELECT id FROM `store_owner_product_master` WHERE store_master_id="'.$store_master_id.'" AND store_product_master_id="'.$pro_id.'"';
					$pro_exist =  parent::selectTable_f_mdl($sql);
					if(!empty($pro_exist)){
						$sopm_id = $pro_exist[0]['id'];
					}else{
						//if product is not already choosen then insert product details
						$sopm_insert_data = [
							'store_master_id' 			=> $store_master_id,
							'store_product_master_id' 	=> $pro_id,
							'product_title'       		=> $single_pro['product_title'],
							'product_description' 		=> $single_pro['product_description'],
							'tags'         				=> $single_pro['tags'],
							'group_name'   				=> $single_pro['group_name'],
							'status'       				=> '1',
							'is_personalization' 		=> $is_persionalization,
							'is_required'		 		=> $is_require,
							'created_on'   				=> @date('Y-m-d H:i:s'),
							'created_on_ts'				=> time()
						];
						// print_r($sopm_insert_data);
						$sopm_arr    = parent::insertTable_f_mdl('store_owner_product_master',$sopm_insert_data);
						if(isset($sopm_arr['insert_id'])){
							$sopm_id = $sopm_arr['insert_id'];
						}
					}


					if(isset($sopm_id) && isset($pro_id) && !empty($pro_id)){
						$newVarCount = 0;
						//currently we have color group by variant list, now we want to expand list with size wise(1 color has multiple size) also, so first we find all details from existed var-ids
						foreach($pre_var_list as $single_pre_var){

							$sql = 'SELECT id, price, price_on_demand, store_product_master_id, store_organization_type_master_id, color, size, image, sku
							FROM `store_product_variant_master`
							WHERE store_product_master_id = "'.$pro_id.'"
							AND store_organization_type_master_id = "'.$single_pre_var['store_organization_type_master_id'].'"
							AND is_ver_deleted="0"
							AND color = "'.$single_pre_var['color'].'"
							';
							$var_list =  parent::selectTable_f_mdl($sql);

							if(!empty($var_list)){
								foreach($var_list as $var_data){
									$store_product_variant_master_id=$var_data['id'];

									$sqldata = 'SELECT * FROM store_owner_request_product_variant_master WHERE  store_product_variant_master_id = "'.$var_data['id'].'" ';
									$sql_reqver = parent::selectTable_f_mdl($sqldata);
									if(!empty($sql_reqver)){
										$store_product_variant_master_id=$sql_reqver[0]['store_product_variant_master_id'];
										$verprice=$sql_reqver[0]['price'];
										$verprice_on_demand=$sql_reqver[0]['price_on_demand'];
										$verfundraising_price=$sql_reqver[0]['fundraising_price'];
									}
									//check variant is already choose or not
									$sql = 'SELECT id FROM store_owner_product_variant_master
									WHERE store_owner_product_master_id = "'.$sopm_id.'"
									AND store_product_variant_master_id = "'.$var_data['id'].'"
									AND store_organization_type_master_id = "'.$var_data['store_organization_type_master_id'].'"
									';
									$var_exist = parent::selectTable_f_mdl($sql);

									if(empty($var_exist)){
										//if variant is not existed then insert product variant details
										$image = $var_data['image'];
										$price=$var_data['price'];
										$price_on_demand=$var_data['price_on_demand'];
										

										$sopvm_insert_data = [
											'store_owner_product_master_id'   => $sopm_id,
											'store_product_variant_master_id' => $var_data['id'],
											'store_organization_type_master_id' => $var_data['store_organization_type_master_id'],
											'price' => $verprice,
											'price_on_demand' => $verprice_on_demand,
											'fundraising_price' => $verfundraising_price,
											'color' => $var_data['color'],
											'size'  => $var_data['size'],
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

						$sqlSPV = 'SELECT id,shop_product_id FROM `store_owner_product_master` WHERE id="'.$sopm_id.'"';
						$existSPV =  parent::selectTable_f_mdl($sqlSPV);
						if(!empty($existSPV)){
							if(!empty($existSPV[0]['shop_product_id'])){
								$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
								global $path;
								require_once($path.'lib/class_graphql.php');

								$shop  = $shop_data[0]['shop_name'];
								$token = $shop_data[0]['token'];

								$headers = array(
									'X-Shopify-Access-Token' => $token
								);
								$graphql = new Graphql($shop, $headers);

								$mutationData = 'mutation productChangeStatus($productId: ID!, $status: ProductStatus!) {
									productChangeStatus(productId: $productId, status: $status) {
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
				
								$inputData = '{
									"productId": "gid://shopify/Product/'.$existSPV[0]['shop_product_id'].'",
									"status":"ACTIVE"
								}';
								$graphql->runByMutation($mutationData,$inputData);
							}
						}

						$sdUpdate = [
							'is_soft_deleted' => '0',
						];
						parent::updateTable_f_mdl('store_owner_product_master',$sdUpdate,'id="'.$sopm_id.'"');

						// now open product to sync in shopify
						if($newVarCount>0){
							$sopm_update_data = [
								'is_product_synced_to_collect' => '0'
							];
							parent::updateTable_f_mdl('store_owner_product_master',$sopm_update_data,'id="'.$sopm_id.'"');
						}
					}

					// Delete Request product after approved
					parent::deleteTable_f_mdl('store_owner_request_product_master','id="'.$request_pro_id.'"');
					parent::deleteTable_f_mdl('store_owner_request_product_variant_master','store_owner_request_product_master_id="'.$request_pro_id.'"');
				}

				//now open store to sync in shopify
				$sm_update_data = [
					'is_products_synced' => '0',
					'is_manual_store_sync' => '1'
				];
				parent::updateTable_f_mdl('store_master',$sm_update_data,'id="'.$store_master_id.'"');
			}

			$res=array();
			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = 'product approved successfully';
			echo json_encode($res);
		}
		die;
	}

	public function store_product_sorting()
	{	
		global $path;
		$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
		if(!empty($shop_data)) {
			require_once( $path.'lib/class_graphql.php');
			$shop  = $shop_data[0]['shop_name'];
			$token = $shop_data[0]['token'];
			$headers = array(
				'X-Shopify-Access-Token' => $token
			);
			$graphql = new Graphql($shop, $headers);
		}
		$res = [];
		$sortOrder='';
		if(!empty($_POST['store_master_id'])){
			$store_master_id = $_POST['store_master_id'];
			$sortOrder = trim($_POST['sort_product_by']);

			$sql = 'SELECT * FROM store_master WHERE id="'.$store_master_id.'" ';
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

				
			$updateprod_sort = [
				"store_sort_product_by"=>$sortOrder
			];	
			
			$resPonseData = parent::updateTable_f_mdl('store_master',$updateprod_sort,'id="'.$store_master_id.'"');	
			$isSuccess    = $resPonseData['isSuccess'];
			
			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = 'Products Sorted Successfully.';
				
		}
		// echo "<pre>";print_r($updateprod_sort);die;
		echo json_encode($res);die();
	}

	public function getStoreProductDetail($store_master_id){

		$sqlprod='SELECT * FROM store_owner_product_master WHERE store_master_id ="'.$store_master_id.'" AND is_soft_deleted="0" ';
		$all_prod = parent::selectTable_f_mdl($sqlprod);	
		return $all_prod;
	}

	public function store_product_history()
	{	
		$res = [];
		if(!empty($_POST['store_owner_prod_id'])){
			$store_master_id = $_POST['store_master_id'];
			$store_owner_prod_id = trim($_POST['store_owner_prod_id']);

			$sql = 'SELECT store_master_id,store_owner_product_master_id,product_fundraising_status_old,product_fundraising_price_old,product_fundraising_status_new,product_fundraising_price_new,updated_by,updated_on FROM store_product_history WHERE store_owner_product_master_id="'.$store_owner_prod_id.'" ORDER BY id DESC';
			$prodHistorydata = parent::selectTable_f_mdl($sql);
			
			$html = '';
			$html .='<div id="tab1" class="tab-content-single">
						<table class="table table-bordered table-hover">
							<thead>
								<tr>
									<th>Details</th>
									<th>Updated Date</th>
									<th>Updated By</th>
								</tr>
							</thead>
							<tbody>';
								
							if(!empty($prodHistorydata)){
								foreach ($prodHistorydata as $value) { 
									$html .= '<tr>';
										$html .= '<td>';
											$html .= '<div class="history-details-sec">';
												$html .= '<table class="table-bordered">';
													$html .= '<thead>';
														$html .= '<tr>';
															$html .= '<th>Field Name</th>';
															$html .= '<th>Old Value</th>';
															$html .= '<th>New Value</th>';
														$html .= '</tr>';
													$html .= '</thead>';
													$html .= '<tbody>';
														$html .= '<tr>';
															$html .= '<td>Fundraising Status</td>';
															$html .= '<td>'.$value['product_fundraising_status_old'] .'</td>';
															$html .= '<td>'.$value['product_fundraising_status_new'].'</td>';
														$html .= '</tr>';
														$html .= '<tr>';
															$html .= '<td>Fundraising Amount</td>';
															$html .= '<td>'.$value['product_fundraising_price_old'].'</td>';
															$html .= '<td>'.$value['product_fundraising_price_new'].'</td>';
														$html .= '</tr>';
													$html .= '</tbody>';
												$html .= '</table>';
											$html .= '</div>';
										$html .= '</td>';
										$html .= '<td>'.date('m/d/Y h:i A',strtotime($value['updated_on'])).'</td>';
										$html .= '<td style="max-width: 150px;word-break: break-all;">'. $value['updated_by'].'</td>';   
									$html .= '</tr>';
								}
							}			
						$html .='</tbody>                                         
						</table>
					</div>
			';
			$res['DATA'] = $html;
			$res['SUCCESS'] = 'TRUE';	
		}
		echo json_encode($res);die();
	}

	public function updateEmailStoreOwner(){
		global $login_user_email;
		$res = [];
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
			echo $status;
		}
		die;
	}

	public function changeManagerToOwner()
	{	
		global $login_user_email;
		$res = [];
		if(!empty($_POST['store_master_id'])){
			$manager_id = trim($_POST['manager_id']);
			$store_master_id = trim($_POST['store_master_id']);
			$store_owner_id = trim($_POST['store_owner_id']);

			$sql_managerData = 'SELECT id,first_name,last_name,email,mobile FROM store_manager_master where status="0" AND id="' .$manager_id . '"';
			$smm_data =  parent::selectTable_f_mdl($sql_managerData);
			if(!empty($smm_data)){
				$manager_first_name	=$smm_data[0]['first_name'];
				$manager_last_name	=$smm_data[0]['last_name'];
				$manager_email		=$smm_data[0]['email'];
				$manager_mobile		=$smm_data[0]['mobile'];

				$updateData = [
					"first_name"	=>$manager_first_name,
					"last_name"		=>$manager_last_name,
					"email"			=>$manager_email,
					"phone"			=>$manager_mobile
				];
				$resPonseData 	= parent::updateTable_f_mdl('store_owner_details_master',$updateData,'id="'.$store_owner_id.'"');
				$resData		= parent::deleteTable_f_mdl('store_manager_master', 'id ="'. $manager_id.'"');
			}
			
			//$updatedBy = "Super Admin <br>(".$login_user_email.")";
			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = 'Switch manager to owner successfully.';
		}
		echo json_encode($res);die();
	}

	public function get_product_based_on_type()
	{	
		global $login_user_email;
		$res = [];
		$htmlBody='';
		if(!empty($_POST['store_master_id'])){
			$store_master_id 			= trim($_POST['store_master_id']);
			$store_sale_type_master_id  = trim($_POST['store_sale_type_master_id']);

			$sql= "SELECT id,store_master_id,store_product_master_id,product_title FROM store_owner_product_master  WHERE is_soft_deleted='0' AND store_master_id = '".$store_master_id."' ";
			$storeProdData = parent::selectTable_f_mdl($sql);
			if(!empty($storeProdData)){
				foreach($storeProdData as $singleProd){
					$store_owner_product_master_id   = $singleProd['id'];
					$store_product_master_id         = $singleProd['store_product_master_id'];
					$product_title                 = $singleProd['product_title'];
					$sqlprod= "SELECT id,is_flash_sale,on_demand FROM `store_product_master`  WHERE id = '".$store_product_master_id."' ";
					$masterProdData = parent::selectTable_f_mdl($sqlprod);
					if(!empty($masterProdData)){
						if($store_sale_type_master_id == '1' &&  $masterProdData[0]['is_flash_sale'] == '0'){
							$htmlBody .= '<span>'.$product_title.'</span>';
						}elseif($store_sale_type_master_id == '1' &&  $masterProdData[0]['is_flash_sale'] == '1'){
						}elseif($store_sale_type_master_id == '2' &&  $masterProdData[0]['on_demand'] == '0'){
							$htmlBody .= '<span>'.$product_title.'</span>';
						}else{
						}
					}
				}
			}
			
			//$updatedBy = "Super Admin <br>(".$login_user_email.")";
			$res['SUCCESS'] = 'TRUE';
			$res['htmlBody'] = $htmlBody;
		}
		echo json_encode($res);die();
	}

	public function store_product_deleteassignlogo(){
		$reponse = storeHelper::storeProductDeleteAssignLogo($_POST,true,false);
		echo $reponse;
	}

	public function deleteAssignedPrintFile(){
		$reponse = storeHelper::deleteAssignedPrintFile($_POST,true,false);
		echo $reponse;
	}

	public function store_bulk_product_assignLogo_delete_post(){
		$reponse = storeHelper::storeBulkProductAssignLogoDeletePost($_POST,true,false);
		echo $reponse;
	}

	public function getProductColorsLogo(){
    	$data = [];
	    if(!empty($_POST['store_master_id'])){
	        $store_master_id = trim($_POST['store_master_id']);
	        $store_design_logo_master_id = trim($_POST['logo_id']);

	        $sql = 'SELECT * FROM store_design_logo_master WHERE store_master_id="'.$store_master_id.'" AND id="'.$store_design_logo_master_id.'" ';
	        $logoColorData = parent::selectTable_f_mdl($sql);
	        if(!empty($logoColorData)){
	            $logoColors = $logoColorData[0]['applicable_colors'];
	        }

			$sql = 'SELECT associate_with_logo_id FROM store_owner_product_master WHERE FIND_IN_SET("'.$store_design_logo_master_id.'", associate_with_logo_id) AND store_master_id="'.$store_master_id.'"';
	        $logoAssignData = parent::selectTable_f_mdl($sql);
	        if(!empty($logoAssignData)){
	            $logoassignstatus = 'logoAssigned_true';
	        }else{
	        	$logoassignstatus = 'logoAssigned_false';
	        }


	        // Retrieve product color data
	        $sql = 'SELECT sopm.id as store_owner_prod_id, sopvm.id as store_owner_ver_id, sopm.product_title, sopm.is_soft_deleted, sopvm.color, sopvm.size, spcm.product_color_name FROM store_owner_product_variant_master as sopvm INNER JOIN store_owner_product_master as sopm ON sopm.id=sopvm.store_owner_product_master_id INNER JOIN store_product_colors_master as spcm ON spcm.product_color=sopvm.color WHERE sopm.is_soft_deleted="0" AND sopm.store_master_id="'.$store_master_id.'" GROUP BY sopvm.color ';
	        $prodColorData = parent::selectTable_f_mdl($sql);

	        // Construct HTML for checkboxes
	        $prodcolor = '';
	        if(!empty($prodColorData)){
	            $prodcolor .='<label for="" class="logo-prod-color-sec">Logo For:</label>';
				$prodcolor .= '<div class="logo-prod-color-sec">
					<input type="checkbox" id="ckbCheckAllcolor"class="ckbCheckAllcolor">
					<label for="ckbCheckAllcolor" class="logocolor">All</label>&nbsp;
				</div>';
	            foreach ($prodColorData as $single_color) {
	                $checkboxValue = $single_color['color'];
	                if (in_array($checkboxValue, explode(',', $logoColors))) {
	                    $prodcolor .= '<div class="logo-prod-color-sec">
	                        <input type="checkbox" id="logocolor_'.$single_color['store_owner_ver_id'].'" value="'.$checkboxValue.'" class="checkBoxClass logo_color_for" product_color_name="'.$single_color['product_color_name'].'" checked>
	                        <label for="logocolor_'.$single_color['store_owner_ver_id'].'" class="logocolor"> <span class="color_group_span" style="background-color:'.$checkboxValue.'">&nbsp;&nbsp;&nbsp;&nbsp;</span>'.$single_color['product_color_name'].'</label>
	                    </div>';
	                } else {
	                    $prodcolor .= '<div class="logo-prod-color-sec">
	                        <input type="checkbox" id="logocolor_'.$single_color['store_owner_ver_id'].'" value="'.$checkboxValue.'" class="checkBoxClass logo_color_for" product_color_name="'.$single_color['product_color_name'].'">
	                        <label for="logocolor_'.$single_color['store_owner_ver_id'].'" class="logocolor"> <span class="color_group_span" style="background-color:'.$checkboxValue.'">&nbsp;&nbsp;&nbsp;&nbsp;</span>'.$single_color['product_color_name'].'</label>
	                    </div>';
	                }
	            }
	        }
	        $data['logo_colors_for'] = $prodcolor;
			$data['logoassignstatus'] = $logoassignstatus;
	    }
	    echo json_encode($data, 1);
	}

	public function getAllStoreProductTag(){		
		$sqlstooreprodTag = "SELECT id,tag,tag_status,created_on FROM store_product_tag_master ORDER BY tag ASC";/* Task 82 Add where condition is_deleted = 0*/
		return $storeProductTagData =  parent::selectTable_f_mdl($sqlstooreprodTag);
	}

	public function add_newtag_product_group()
	{	
		global $login_user_email;
		$res = [];
		if(!empty($_POST['store_master_id'])){
			$ptoduct_groupname = trim($_POST['ptoduct_groupname']);
			$store_master_id = trim($_POST['store_master_id']);
			$prod_selected_tag = $_POST['selected_tag'];
			$product_tags = implode(',', $prod_selected_tag);
			$sql_prodData = 'SELECT * FROM store_owner_product_master where is_soft_deleted="0" AND group_name="'.$ptoduct_groupname.'" AND store_master_id="' .$store_master_id . '"';
			$storeProddata =  parent::selectTable_f_mdl($sql_prodData);
			if(!empty($storeProddata)){
				foreach($storeProddata as $singleeprod){
					$sopm_id	        		=$singleeprod['id'];
					$shop_product_id			=$singleeprod['shop_product_id'];
					$store_product_master_id	=$singleeprod['store_product_master_id'];
					$group_name					=$singleeprod['group_name'];
					$tags					    =$singleeprod['tags'];
					if (!empty($tags)) {
						$merged_tags_array = array_unique(array_merge(explode(',', $tags), explode(',', $product_tags)));
    					$merged_tags = implode(',', $merged_tags_array);
						//$merged_tags = $tags.','.$product_tags;
					} else {
						$merged_tags = $product_tags;
					}
					$updateData = [
						"tags"			=>$merged_tags
					];
					$resPonseData 	= parent::updateTable_f_mdl('store_owner_product_master',$updateData,'id="'.$sopm_id.'"');
					if(!empty($shop_product_id)){
						global $path;
						require_once($path.'lib/class_graphql.php');
						$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
						if(!empty($shop_data)){
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
										tags
									}
									userErrors {
										field
										message
									}
								}
							}';
							
							// Assuming $tags is a comma-separated string
							$tagsArray = explode(',', $merged_tags);
							$inputData = json_encode([
								"input" => [
									"id" => "gid://shopify/Product/" . $shop_product_id,
									"tags" => $tagsArray
								]
							]);
							$graphql->runByMutation($mutationData,$inputData);
						}

					}

				}
			}
			
			//$updatedBy = "Super Admin <br>(".$login_user_email.")";
			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = 'New Tag(s) added successfully.';
		}
		echo json_encode($res);die();
	}

	public function update_tag_store_product()
	{	
		global $login_user_email;
		$res = [];
		if(!empty($_POST['store_master_id'])){
			$sopm_id = trim($_POST['sopm_id']);
			$store_master_id = trim($_POST['store_master_id']);
			if(isset($_POST['selected_tag'])){
				$selected_tag = $_POST['selected_tag'];
			}else{
				$selected_tag=[];
			}
			if(isset($_POST['unselected_tag'])){
				$unselected_tag = $_POST['unselected_tag'];
			}else{
				$unselected_tag=[];
			}
			$product_tags = implode(',', $selected_tag);
			$unselectproduct_tags = implode(',', $unselected_tag);
			$sql_prodData = 'SELECT * FROM store_owner_product_master where is_soft_deleted="0" AND id="'.$sopm_id.'" AND store_master_id="' .$store_master_id . '"';
			$storeProddata =  parent::selectTable_f_mdl($sql_prodData);
			if(!empty($storeProddata)){

				$shop_product_id		 = $storeProddata[0]['shop_product_id'];
				$store_product_master_id = $storeProddata[0]['store_product_master_id'];
				$group_name 			 = $storeProddata[0]['group_name'];
				$tags			     	 = $storeProddata[0]['tags'];
				$merged_tags='';
				if (!empty($tags)) {
						$filteredTagsArray = array_diff(explode(',', $tags), $unselected_tag);
						$merged_unselect_tags = implode(',', $filteredTagsArray);
						$unselecttagData = [
							"tags"	=>$merged_unselect_tags
						];
						$resPonseData 	= parent::updateTable_f_mdl('store_owner_product_master',$unselecttagData,'id="'.$sopm_id.'"');

						$merged_tags_array = array_unique(array_merge(explode(',', $merged_unselect_tags), explode(',', $product_tags)));
    					$merged_tags = implode(',', $merged_tags_array);
					} else {
						$merged_tags = $product_tags;
					}
				$updateData = [
					"tags"	=>$merged_tags
				];
				
				$resPonseData 	= parent::updateTable_f_mdl('store_owner_product_master',$updateData,'id="'.$sopm_id.'"');
				if(!empty($shop_product_id)){
					global $path;
					require_once($path.'lib/class_graphql.php');
					$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
					if(!empty($shop_data)){
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
									tags
								}
								userErrors {
									field
									message
								}
							}
						}';
						
						// Assuming $tags is a comma-separated string
						$tagsArray = explode(',', $merged_tags);
						$inputData = json_encode([
							"input" => [
								"id" => "gid://shopify/Product/" . $shop_product_id,
								"tags" => $tagsArray
							]
						]);
						$graphql->runByMutation($mutationData,$inputData);
					}

				}
			}
			
			//$updatedBy = "Super Admin <br>(".$login_user_email.")";
			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = 'Tag(s) updated successfully.';
		}
		echo json_encode($res);die();
	}

	public function get_all_store_product_tag(){	
		$res = [];
		if(!empty($_POST['store_master_id'])){

			$store_master_id = trim($_POST['store_master_id']);
			$sqltagData = "SELECT id,tag,tag_status,created_on FROM store_product_tag_master ORDER BY tag ASC ";
			$storeTagData =  parent::selectTable_f_mdl($sqltagData);
			
			$res['SUCCESS'] = 'TRUE';
			$res['storeTagData'] = $storeTagData;
		}
		echo json_encode($res);die();
	}

	public function check_add_product_name_identifier(){
		if (parent::isPOST()) {
			if (!empty(parent::getVal("action")) && parent::getVal("action") == "check_add_product_name_identifier") {
				$product_name_identifier= trim(parent::getVal('product_name_identifier'));
				$store_name= trim(parent::getVal('store_name'));
				$store_master_id= trim(parent::getVal('store_master_id'));
				$sql = 'SELECT product_name_identifier from store_master where product_name_identifier ="'.$product_name_identifier.'" AND id != "'.$store_master_id.'" ';
				$SelectData = parent::selectTable_f_mdl($sql);
				if(!empty($SelectData)){
					$status=1;
				}else{
					$status=0;
				}
				echo $status;
			}
			die;
		}
	}

	public function getProductTemplateList(){
		$sql = 'SELECT id, template_name FROM `product_template_master` ORDER BY template_name ASC';
		return parent::selectTable_f_mdl($sql);
	}

	function get_all_template_products_colors(){
		global $s3Obj;
		global $login_user_email;
        $s3Obj = new Aws3;
		$res = [];

		if(!empty(parent::getVal("action")) && parent::getVal("action") == "get_all_template_products_colors"){
			
			$template_id= trim(parent::getVal('template_id'));
			$template_name= trim(parent::getVal('template_name'));
			$prodcolorFamily=$prodcolor='';
			// Retrieve product color data
			$sql = "SELECT spvm.id,spvm.color,spcm.product_color_name,scfm.color_family_name,scfm.color_family_color,scfm.color_image,scfm.id as color_mamily_id FROM product_templates_master_details as ptmd INNER JOIN store_product_master as spm ON spm.id=ptmd.store_product_master_id AND spm.is_deleted='0' INNER JOIN store_product_variant_master as spvm ON spvm.store_product_master_id=spm.id AND spvm.is_ver_deleted='0' LEFT JOIN store_product_colors_master as spcm ON spcm.product_color=spvm.color LEFT JOIN store_color_family_master as scfm ON scfm.color_family_name = spcm.color_family  WHERE ptmd.product_templates_master_id='".$template_id."' group by scfm.color_family_color order by scfm.color_family_color ";
	        $prodColorFamilyData = parent::selectTable_f_mdl($sql);
			if(!empty($prodColorFamilyData)){
				$prodcolorFamily .='<ul class="new_pro_family_list_ul">';
				foreach ($prodColorFamilyData as $single_colorfamily) {
					$clrfamilyName = $single_colorfamily['color_family_name'];
					$clrfamilycode = $single_colorfamily['color_family_color'];
					$color_mamily_id = $single_colorfamily['color_mamily_id'];
					if(!empty($clrfamilycode)){

						$sql = "SELECT spvm.id,spvm.color,spcm.product_color_name,spcm.id as product_color_id FROM product_templates_master_details as ptmd INNER JOIN store_product_master as spm ON spm.id=ptmd.store_product_master_id AND spm.is_deleted='0' INNER JOIN store_product_variant_master as spvm ON spvm.store_product_master_id=spm.id AND spvm.is_ver_deleted='0' LEFT JOIN store_product_colors_master as spcm ON spcm.product_color=spvm.color LEFT JOIN store_color_family_master as scfm ON scfm.color_family_name = spcm.color_family  WHERE ptmd.product_templates_master_id='".$template_id."' AND spcm.color_family='".$clrfamilyName."'  group by spcm.product_color_name order by spcm.product_color_name ";
		        		$prodColorData = parent::selectTable_f_mdl($sql);
						$prodcolor = '';
						if(!empty($prodColorData)){
							$prodcolor .= '<ul class="product-color-ul" id="colorList_'.$color_mamily_id.'" style="display:none;">';
							foreach ($prodColorData as $single_color) {
								$checkboxValue = $single_color['color'];
								$prodcolor .= '<li class="logo-prod-color-sec"><div>
									<input type="checkbox" id="logocolor_variantid_'.$single_color['id'].'" value="'.$checkboxValue.'" class="checkBoxClass logo_color_for colorfamily_'.$color_mamily_id.'" product_color_name="'.$single_color['product_color_name'].'" colorfamily_id="'.$color_mamily_id.'">
									<label for="logocolor_variantid_'.$single_color['id'].'" class="logocolor"> <span class="color_group_span" style="background-color:'.$checkboxValue.'">&nbsp;&nbsp;&nbsp;&nbsp;</span>'.$single_color['product_color_name'].'</label>
								</div></li>';
							}
							$prodcolor .= '</ul>';
						}else{
							$prodcolor .= '<div class="logo-prod-color-sec" style="color:red;">
								<label for="logocolor_variantid_0" class="logocolor">No product found in selected template</label>
							</div>';
						}


						if($clrfamilyName == 'Tie-Dye'){
							if(!empty($single_colorfamily['color_image'])){
								$prodcolorFamily .= '<li><div class="logo-prod-color-sec">
									<span class="toggleColors" data-target="#colorList_'.$color_mamily_id.'"><i class="fa fa-plus"></i></span>
									<input type="checkbox" id="logocolorfamily_variantid_'.$single_colorfamily['id'].'" value="'.$clrfamilycode.'" class="checkBoxClassfamily color_family_'.$color_mamily_id.'  temp-prod-color-family" product_color_family_name="'.$clrfamilyName.'" colorfamily_id="'.$color_mamily_id.'">
									<label for="logocolorfamily_variantid_'.$single_colorfamily['id'].'" class="logocolorfamily"><img style="width: 18px;height: 18px;border-radius: 50%;border: 1px solid transparent;position: relative;box-shadow: 0 0px 2px #000000;margin-left: 1px;margin-bottom:2px" src="'.$s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$single_colorfamily['color_image']).'"> <span class="color_family_group_span" style="margin-left: 4px;">&nbsp;&nbsp;&nbsp;&nbsp;</span>'.$clrfamilyName.'</label>
								</div>';
								$prodcolorFamily .=$prodcolor;
								$prodcolorFamily .='</li>';
							}
							else{
								$prodcolorFamily .= '<li><div class="logo-prod-color-sec">
									<span class="toggleColors" data-target="#colorList_'.$color_mamily_id.'"><i class="fa fa-plus"></i></span>
									<input type="checkbox" id="logocolorfamily_variantid_'.$single_colorfamily['id'].'" value="'.$clrfamilycode.'" class="checkBoxClassfamily color_family_'.$color_mamily_id.' temp-prod-color-family" product_color_family_name="'.$clrfamilyName.'" colorfamily_id="'.$color_mamily_id.'">
									<label for="logocolorfamily_variantid_'.$single_colorfamily['id'].'" class="logocolorfamily"> <span class="color_family_group_span" style="background-color:'.$clrfamilycode.'">&nbsp;&nbsp;&nbsp;&nbsp;</span>'.$clrfamilyName.'</label>
								</div>';
								$prodcolorFamily .=$prodcolor;
								$prodcolorFamily .='</li>';
							}
						}else{
							$prodcolorFamily .= '<li><div class="logo-prod-color-sec">
								<span class="toggleColors" data-target="#colorList_'.$color_mamily_id.'"><i class="fa fa-plus"></i></span>
								<input type="checkbox" id="logocolorfamily_variantid_'.$single_colorfamily['id'].'" value="'.$clrfamilycode.'" class="checkBoxClassfamily color_family_'.$color_mamily_id.' temp-prod-color-family" product_color_family_name="'.$clrfamilyName.'" colorfamily_id="'.$color_mamily_id.'">
								<label for="logocolorfamily_variantid_'.$single_colorfamily['id'].'" class="logocolorfamily"><span class="color_family_group_span" style="background-color:'.$clrfamilycode.'">&nbsp;&nbsp;&nbsp;&nbsp;</span>'.$clrfamilyName.' (Color Family)</label>
							</div>';
							$prodcolorFamily .=$prodcolor;
							$prodcolorFamily .='</li>';
						}
					}
				}
				$prodcolorFamily .='</ul">';
			}else{
				$prodcolorFamily .= '<div class="logo-prod-color-family-sec" style="color:red;">
							<label for="logocolorfamily_variantid_0" class="logocolorfamily">No product found in selected template</label>
						</div>';
			}

	        $res['product_colors'] = $prodcolorFamily;
			
		}
		// echo json_encode($res,1);
		common::sendJson($res);
	}

	function add_template_product_store(){
		global $s3Obj;
		global $login_user_email;
        $s3Obj = new Aws3;
		$res = [];

		if(!empty(parent::getVal("action")) && parent::getVal("action") == "add_template_product_store"){
			
			$store_master_id= trim(parent::getVal('store_master_id'));
			$store_sale_type= trim(parent::getVal('store_sale_type'));
			
			$product_template_id=trim(parent::getVal("product_template"));
			$product_colors=[];
			if(isset($_POST['product_colors']) && !empty($_POST['product_colors'])){
				$product_colors=$_POST['product_colors'];
			}

			//add template products
			if(!empty($product_template_id)){
				
				$prodsql="SELECT store_product_master_id FROM product_templates_master_details WHERE product_templates_master_id='".$product_template_id."' ";
				$prodsql_data =  parent::selectTable_f_mdl($prodsql);
				foreach($prodsql_data as $singleprod){
					$pro_id=$singleprod['store_product_master_id'];
					if($store_sale_type=='1'){
						$sql="SELECT * FROM store_product_master WHERE id='".$singleprod['store_product_master_id']."' AND is_flash_sale='1' AND is_deleted='0' ";
					}else{
						$sql="SELECT * FROM store_product_master WHERE id='".$singleprod['store_product_master_id']."' AND on_demand='1' AND is_deleted='0' ";
					}
					
					$master_proddata =  parent::selectTable_f_mdl($sql);
					if(empty($master_proddata)){
						$groupid='';
						$is_eligible_sleeve_print='0';
					}else{
						$groupid=$master_proddata[0]['group_id'];
						$is_eligible_sleeve_print=$master_proddata[0]['is_eligible_sleeve_print'];
						$sql="SELECT product_group FROM minimum_group_product WHERE id='".$groupid."' ";
						$groupdata =  parent::selectTable_f_mdl($sql);
						if(!empty($groupdata)){
							$group_name=$groupdata[0]['product_group'];
						}else{
							$group_name='';
						}

						if($master_proddata[0]['id']=='789' || $master_proddata[0]['id']=='169'){
							$is_persionalization = '1';
							$is_require = '1';
						}else{
							$is_persionalization = '0';
							$is_require = '0';
						}

						//first we check if product is already choose in store or not
						$sql = 'SELECT id FROM `store_owner_product_master` WHERE store_master_id="'.$store_master_id.'" AND store_product_master_id="'.$pro_id.'"';
						$pro_exist =  parent::selectTable_f_mdl($sql);
						if(!empty($pro_exist)){
							$sopm_id = $pro_exist[0]['id'];
						}else{
							$sopm_insert_data = [
								'store_master_id' 			=> $store_master_id,
								'is_product_fundraising'	=> "No",
								'store_product_master_id' 	=> $master_proddata[0]['id'],
								'product_title'       		=> $master_proddata[0]['product_title'],
								'product_description' 		=> $master_proddata[0]['product_description'],
								'tags'         				=> $master_proddata[0]['tags'],
								'group_name'   				=> $group_name,
								'status'       				=> '1',
								'is_personalization' 		=> $is_persionalization,
								'is_required'		 		=> $is_require,
								'created_on'   				=> @date('Y-m-d H:i:s'),
								'created_on_ts'				=> time()
							];
							$sopm_arr    = parent::insertTable_f_mdl('store_owner_product_master',$sopm_insert_data);
							if(isset($sopm_arr['insert_id'])){
								$sopm_id = $sopm_arr['insert_id'];
							}
						}

						$sql = 'SELECT sopvm.front_side_ink_colors_group,sopvm.back_side_ink_colors_group,sopvm.sleeve_ink_color_group,sopvm.is_back_enable_group FROM store_owner_product_master as sopm INNER JOIN store_owner_product_variant_master as sopvm ON sopm.id=sopvm.store_owner_product_master_id WHERE sopm.store_master_id="'.$store_master_id.'" AND sopm.group_name="'.$group_name.'" LIMIT 1';
						$prodInkCostGroup =  parent::selectTable_f_mdl($sql);

						$sqlGroupHistory = 'SELECT changed_sleeve_ink_group_price FROM group_ink_price_history WHERE store_master_id="'.$store_master_id.'" AND  group_name="'.$group_name.'" ORDER BY id DESC LIMIT 1';
						$prodInkCostGroupHistoryData =  parent::selectTable_f_mdl($sqlGroupHistory);
						$prodInkCostGroupHistory='0';
						if(!empty($prodInkCostGroupHistoryData)){
							$prodInkCostGroupHistory = $prodInkCostGroupHistoryData[0]['changed_sleeve_ink_group_price'];
						}


						if(isset($sopm_id) && isset($pro_id) && !empty($pro_id)){
							$newVarCount = 0;
							$JsonproductArray = json_encode(array_values($product_colors));
							$colorCodeValues  = str_replace (array('[', ']'), '' , $JsonproductArray);
							$sql = 'SELECT * FROM `store_master` WHERE id="' . $store_master_id . '"';
							$store_master_data =  parent::selectTable_f_mdl($sql);
							$sql = 'SELECT * FROM `store_product_variant_master` WHERE store_product_master_id="'.$pro_id.'" AND color IN('.$colorCodeValues.') AND store_organization_type_master_id ="1" AND is_ver_deleted="0" ';
							$var_list =  parent::selectTable_f_mdl($sql);

							if (!empty($var_list)) {
								foreach ($var_list as $var_data) {
									$image = $var_data['image'];
									
									// Task 42 start
									$sql = 'SELECT price,price_on_demand from store_product_variant_master where id="'.$var_data['id'].'" AND is_ver_deleted="0" ';
									$storeProductVariantMaster = parent::selectTable_f_mdl($sql);

									$add_cost = 0;
									if(isset($prodInkCostGroup[0]['front_side_ink_colors_group']) && !empty($prodInkCostGroup[0]['front_side_ink_colors_group'])){
										$add_cost += intval($prodInkCostGroup[0]['front_side_ink_colors_group'])-1;
									}else if(isset($store_master_data[0]['front_side_ink_colors']) && !empty($store_master_data[0]['front_side_ink_colors'])){
										$add_cost += intval($store_master_data[0]['front_side_ink_colors'])-1;
									}

									$add_on_cost = 0;
									if(isset($prodInkCostGroup[0]['back_side_ink_colors_group']) && !empty($prodInkCostGroup[0]['back_side_ink_colors_group'])){
										$add_cost   += common::ADD_COST_BACK_SIDE_INK_COLOR+intval($prodInkCostGroup[0]['back_side_ink_colors_group'])-1;
										$add_on_cost = common::ADD_COST_BACK_SIDE_INK_COLOR;
									}else if(isset($store_master_data[0]['back_side_ink_colors']) && !empty($store_master_data[0]['back_side_ink_colors'])){
										$add_cost   += common::ADD_COST_BACK_SIDE_INK_COLOR+intval($store_master_data[0]['back_side_ink_colors'])-1;
										$add_on_cost = common::ADD_COST_BACK_SIDE_INK_COLOR;
									}

									if(isset($prodInkCostGroupHistory) && !empty($prodInkCostGroupHistory)){
										$add_cost += common::ADD_COST_BACK_SIDE_INK_COLOR + intval($prodInkCostGroupHistory)-1;
									}else if(isset($store_master_data[0]['sleeve_ink_colors']) && !empty($store_master_data[0]['sleeve_ink_colors'])){
										$add_cost += common::ADD_COST_BACK_SIDE_INK_COLOR + intval($store_master_data[0]['sleeve_ink_colors'])-1;
									}
									
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
									}else if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
										$fullfilment_type_price = $fullfilment_platinum_price;
									}else if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=='SHIP_1_LOCATION_NOT_SORT'){
										$fullfilment_type_price = $fullfilment_silver_price;
									}
									
									$sqlmaster_group = 'SELECT id,group_id from store_product_master where id="'.$master_proddata[0]['id'].'" AND is_deleted="0" ';
									$storeProductMasterGroup = parent::selectTable_f_mdl($sqlmaster_group);
									$group_id='';
									if(!empty($storeProductMasterGroup)){
										$group_id=$storeProductMasterGroup[0]['group_id'];
									}

									//To do add bussiness login for fullfilmemnt type & fundrising
									$ondemandPrice  = 0;
									$flashSalePrice = 0;
									if(isset($storeProductVariantMaster[0]['price']) && $storeProductVariantMaster[0]['price_on_demand']){
										if($group_id=='9'){
											$ondemandPrice  = (floatval($storeProductVariantMaster[0]['price_on_demand']));
											$flashSalePrice = $storeProductVariantMaster[0]['price'];
										}else{
											$ondemandPrice  = (floatval($storeProductVariantMaster[0]['price_on_demand'])+$add_on_cost);
											$flashSalePrice = $storeProductVariantMaster[0]['price']+$add_cost+$fullfilment_type_price;
										}
									}else{
										$ondemandPrice  = $var_data['price_on_demand'];
										$flashSalePrice = $var_data['price'];
									}

									if(!empty($prodInkCostGroup)){
										$front_side_ink_colors_group=trim($prodInkCostGroup[0]['front_side_ink_colors_group']);
										$back_side_ink_colors_group=trim($prodInkCostGroup[0]['back_side_ink_colors_group']);
										$sleeve_ink_color_group=trim($prodInkCostGroup[0]['sleeve_ink_color_group']);
										$is_back_enable_group=trim($prodInkCostGroup[0]['is_back_enable_group']);
									}else{
										$front_side_ink_colors_group=trim($store_master_data[0]['front_side_ink_colors']);
										$back_side_ink_colors_group=trim($store_master_data[0]['back_side_ink_colors']);
										$sleeve_ink_color_group=trim($store_master_data[0]['sleeve_ink_colors']);
										$is_back_enable_group=trim($store_master_data[0]['is_back_enable']);
									}


									//check variant is already choose or not
									$sql = 'SELECT id FROM store_owner_product_variant_master
									WHERE store_owner_product_master_id = "'.$sopm_id.'"
									AND store_product_variant_master_id = "'.$var_data['id'].'"
									AND store_organization_type_master_id = "'.$var_data['store_organization_type_master_id'].'"
									';
									$var_exist = parent::selectTable_f_mdl($sql);
									if(!empty($var_exist)){
										if($is_eligible_sleeve_print=='0'){
											if(isset($prodInkCostGroupHistory) && !empty($prodInkCostGroupHistory)){
												$sleevecost = common::ADD_COST_BACK_SIDE_INK_COLOR + intval($prodInkCostGroupHistory)-1;
											}else if(isset($store_master_data[0]['sleeve_ink_colors']) && !empty($store_master_data[0]['sleeve_ink_colors'])){
												$sleevecost = common::ADD_COST_BACK_SIDE_INK_COLOR + intval($store_master_data[0]['sleeve_ink_colors'])-1;
											}
											$flashSalePrice = $flashSalePrice - $sleevecost;
											$sopvm_update_data = [
												'status' 							=> '1',
												'front_side_ink_colors_group' 		=> $front_side_ink_colors_group,
												'back_side_ink_colors_group'  		=> $back_side_ink_colors_group,
												'sleeve_ink_color_group'  		    => '0',
												'is_back_enable_group'  	  		=> $is_back_enable_group,
												'price'                             => $flashSalePrice,
												'price_on_demand'                   => $ondemandPrice,
											];
										}else{
											$sopvm_update_data = [
												'status' 							=> '1',
												'front_side_ink_colors_group' 		=> $front_side_ink_colors_group,
												'back_side_ink_colors_group'  		=> $back_side_ink_colors_group,
												'sleeve_ink_color_group'  		    => $sleeve_ink_color_group,
												'is_back_enable_group'  	  		=> $is_back_enable_group,
												'price'                             => $flashSalePrice,
												'price_on_demand'                   => $ondemandPrice,
											];
										}
										
										parent::updateTable_f_mdl('store_owner_product_variant_master',$sopvm_update_data,'store_product_variant_master_id="'.$var_data['id'].'"');
									}else{
										if($is_eligible_sleeve_print=='0'){
											if(isset($prodInkCostGroupHistory) && !empty($prodInkCostGroupHistory)){
												$sleevecost = common::ADD_COST_BACK_SIDE_INK_COLOR + intval($prodInkCostGroupHistory)-1;
											}else if(isset($store_master_data[0]['sleeve_ink_colors']) && !empty($store_master_data[0]['sleeve_ink_colors'])){
												$sleevecost = common::ADD_COST_BACK_SIDE_INK_COLOR + intval($store_master_data[0]['sleeve_ink_colors'])-1;
											}
											$flashSalePrice = $flashSalePrice - $sleevecost;

											$sopvm_insert_data = [
												'store_owner_product_master_id'     => $sopm_id,
												'store_product_variant_master_id'   => $var_data['id'],
												'store_organization_type_master_id' => $var_data['store_organization_type_master_id'],
												'price'                             => $flashSalePrice,
												'price_on_demand'                   => $ondemandPrice,
												'fundraising_price'                 => "0",
												'color'     		                => $var_data['color'],
												'size'      		                => $var_data['size'],
												'image'                             => $var_data['image'],
												'original_image'                    => $var_data['feature_image'],
												'sku' 				                => $var_data['sku'],
												'weight' 			                => $var_data['weight'],
												'front_side_ink_colors_group' 		=> $front_side_ink_colors_group,
												'back_side_ink_colors_group'  		=> $back_side_ink_colors_group,
												'sleeve_ink_color_group'  		    => '0',
												'is_back_enable_group'  	  		=> $is_back_enable_group,
												'status' 			                => '1',
												'created_on' 		                => @date('Y-m-d H:i:s'),
												'created_on_ts' 	                => time()
											];
										}else{
											$sopvm_insert_data = [
												'store_owner_product_master_id'     => $sopm_id,
												'store_product_variant_master_id'   => $var_data['id'],
												'store_organization_type_master_id' => $var_data['store_organization_type_master_id'],
												'price'                             => $flashSalePrice,
												'price_on_demand'                   => $ondemandPrice,
												'fundraising_price'                 => "0",
												'color'     		                => $var_data['color'],
												'size'      		                => $var_data['size'],
												'image'                             => $var_data['image'],
												'original_image'                    => $var_data['feature_image'],
												'sku' 				                => $var_data['sku'],
												'weight' 			                => $var_data['weight'],
												'front_side_ink_colors_group' 		=> $front_side_ink_colors_group,
												'back_side_ink_colors_group'  		=> $back_side_ink_colors_group,
												'sleeve_ink_color_group'  		    => $sleeve_ink_color_group,
												'is_back_enable_group'  	  		=> $is_back_enable_group,
												'status' 			                => '1',
												'created_on' 		                => @date('Y-m-d H:i:s'),
												'created_on_ts' 	                => time()
											];

										}
										parent::insertTable_f_mdl('store_owner_product_variant_master', $sopvm_insert_data);
										$newVarCount++;
									}
									
								}
							}

							if($newVarCount>0){
								$sopm_update_data = [
									'is_product_synced_to_collect' => '0'
								];
								parent::updateTable_f_mdl('store_owner_product_master',$sopm_update_data,'id="'.$sopm_id.'"');
							}

							//now open store to sync in shopify
							$sm_update_data = [
								'is_products_synced' => '0',
								'is_manual_store_sync' => '1'
							];
							parent::updateTable_f_mdl('store_master',$sm_update_data,'id="'.$store_master_id.'"');
						}
					}
				}
			}else{
				$res['SUCCESS'] = 'FALSE';
				$res['MESSAGE'] = 'Template not found.';
			}
			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = 'Products added successfully.';
			
		}
		// echo json_encode($res,1);
		common::sendJson($res);
	}

	public function getStoreptoduct_group($store_master_id) {
	    $res = [];
	    global $s3Obj;
	    if (!empty($store_master_id)) {
	        // Retrieve product group data
	        $sql = "SELECT sopm.id,sopm.product_title, sopm.group_name,mgp.id as group_id,sopvm.image FROM store_owner_product_master as sopm
                INNER JOIN `store_owner_product_variant_master` as sopvm ON sopvm.store_owner_product_master_id = sopm.id
                LEFT JOIN minimum_group_product as mgp ON mgp.product_group=sopm.group_name 
	            WHERE store_master_id = '" . $store_master_id . "' AND sopm.is_soft_deleted = '0' GROUP BY sopm.group_name ORDER BY sopm.group_name ASC";
	        $groupData = parent::selectTable_f_mdl($sql);
	        
	        if (!empty($groupData)) {
	            foreach ($groupData as $single_group) {
	                $product_id = $single_group['id'];
	                $group_id = $single_group['group_id'];
	                $group_name = $single_group['group_name'];
	                
	                // Retrieve products for this group

	                $sql = "SELECT sopm.id,sopm.product_title, sopm.is_personalization,sopm.is_required,sopm.is_item_personalization,sopm.is_item_required ,sopm.group_name,mgp.id as group_id,sopvm.image
	                        FROM store_owner_product_master as sopm
                            INNER JOIN `store_owner_product_variant_master` as sopvm ON sopvm.store_owner_product_master_id = sopm.id
                            LEFT JOIN minimum_group_product as mgp ON mgp.product_group=sopm.group_name
	                        WHERE sopm.group_name = '".$group_name."' AND sopm.is_soft_deleted = '0' AND sopm.store_master_id ='".$store_master_id."' group by sopm.id ";
	                $group_products = parent::selectTable_f_mdl($sql);
	                
	                // Prepare the group product data
	                $group_prod = [];
	                if (!empty($group_products)) {
	                    foreach ($group_products as $product) {
	                    	$image = '<img class="" src="' . $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH . $product['image']) . '" width="70" alt="" style="height:auto;">';

	                        $group_prod[] = [
	                            'prod_id' => $product['id'],
	                            'prod_name' => $product['product_title'],
	                            'prod_image' => $image,
	                            'is_personalization' => $product['is_personalization'],
	                            'is_required' => $product['is_required'],
								'is_item_personalization' => $product['is_item_personalization'],
	                            'is_item_required' => $product['is_item_required'],
	                            'group' => $product['group_name'],
	                            'group_id' => $product['group_id'],
	                        ];
	                    }
	                }

	                // Add the group and its products to the result array
	                $res[] = [
	                    'group' => $group_id,
	                    'prod_group' => $group_name,
	                    'product_id' => $product_id,
	                    'group_prod' => $group_prod
	                ];
	            }
	        }
	    }
	     
	    return $res;
	}

	public function update_persionalization_details_post(){
		global $s3Obj;
		global $login_user_email;
        $s3Obj = new Aws3;
		$res = [];

		if(!empty(parent::getVal("action")) && parent::getVal("action") == "update_persionalization_details_post"){
			
			$store_master_id= trim(parent::getVal('store_master_id'));
			$persionalization_arr=[];
			if(isset($_POST['persionalization_arr']) && !empty($_POST['persionalization_arr'])){
				$persionalization_arr=$_POST['persionalization_arr'];
			}

			foreach($persionalization_arr as $singleprod){
				$pro_id    = $singleprod['product_id'];
				$status    = $singleprod['status'];
				$isrequire = $singleprod['isrequire'];
				$itemstatus    = $singleprod['itemstatus'];
				$isitemrequire = $singleprod['isitemrequire'];
				
				if($status=='true'){
					$is_persionalization ='1';
				}else{
					$is_persionalization ='0';
				}

				if($isrequire=='true'){
					$is_require ='1';
				}else{
					$is_require ='0';
				}

				if($itemstatus=='true'){
					$is_item_personalization ='1';
				}else{
					$is_item_personalization ='0';
				}

				if($isitemrequire=='true'){
					$is_item_required ='1';
				}else{
					$is_item_required ='0';
				}
				
				$sopm_update_data = [
					'is_personalization' => $is_persionalization,
					'is_required'		 => $is_require,
					'is_item_personalization' => $is_item_personalization,
					'is_item_required'		 => $is_item_required
				];
				
				parent::updateTable_f_mdl('store_owner_product_master',$sopm_update_data,'id="'.$pro_id.'"');
			}
			
			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = 'Setting updated successfully.';
			
		}
		// echo json_encode($res,1);
		common::sendJson($res);
	}

	public function getProductAdditionalColorGroup()
	{
		global $s3Obj;
		$response = array();
		
		if (!empty($_POST['groupname'])) {
			$store_product_master_ids='';
			$strownSql     = 'SELECT group_concat(store_product_master_id) as store_product_master_id FROM store_owner_product_master WHERE group_name ="'.$_POST['groupname'].'" AND store_master_id ="'.$_POST['store_master_id'].'" ';
			$getStrProduct = parent::selectTable_f_mdl($strownSql);
			if (!empty($getStrProduct)) {
				$storeProductId 			= explode(',', $getStrProduct[0]['store_product_master_id']);
				$JsonproductArray 			= json_encode(array_values($storeProductId));
				$store_product_master_ids   = str_replace (array('[', ']'), '' , $JsonproductArray);
			}
			
			$sql = 'SELECT `store_product_variant_master`.color, store_product_colors_master.product_color,store_product_colors_master.product_color_name,store_color_family_master.color_image
				FROM `store_product_variant_master`
				LEFT JOIN `store_product_master` ON `store_product_master`.id = `store_product_variant_master`.store_product_master_id
				LEFT JOIN store_product_colors_master ON store_product_colors_master.product_color = store_product_variant_master.color
				LEFT JOIN store_color_family_master ON store_color_family_master.color_family_name = store_product_colors_master.color_family
				WHERE `store_product_variant_master`.status = "1" AND `store_product_variant_master`.store_product_master_id IN ('.$store_product_master_ids.') 
				AND `store_product_master`.status = "1" GROUP BY `store_product_variant_master`.color';
			$getColor = parent::selectTable_f_mdl($sql);

			$htmlBody = '';
			if (!empty($getColor)) {
				foreach ($getColor as $single_color) {
					$clrfamilyName = $single_color['product_color_name'];
					$clrfamilycode = $single_color['product_color'];
					$colorImage = $single_color['color_image'];

					$htmlBody .= '<div class="checkbox-custom checkbox-primary">';
					$htmlBody .= '<input type="checkbox" class="additional_color_product_group" value="' . $clrfamilyName . '">';
					if($clrfamilyName == 'Tie-Dye' || $clrfamilyName == 'Tie-Dye Mask'){
						if(!empty($colorImage)){
							$htmlBody .= '<label class="family_color" style=""><img style="width: 18px;height: 18px;border-radius: 50%;border: 1px solid transparent;position: relative;box-shadow: 0 0px 2px #000000;margin-left: 1px;margin-bottom:2px" src="'.$s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$colorImage).'">'.'<span style="margin-left: 4px;">'.$clrfamilyName.'</span>';
						}
						else{
							$htmlBody .= '<label for=""><span class="color_group_span" style="background-color:'.$clrfamilycode.'">&nbsp;&nbsp;&nbsp;&nbsp;</span>'.$clrfamilyName;
						}
					}else{
							$htmlBody .= '<label for=""><span class="color_group_span" style="background-color:' . $clrfamilycode . '">&nbsp;&nbsp;&nbsp;&nbsp;</span>' . $clrfamilyName;
					}
					$htmlBody .= '</label>';
					$htmlBody .= '</div>';
				}
				$response['htmlBody'] = $htmlBody;
			} else {
				$response['htmlBody'] = '';
			}
		} else {
			$response['htmlBody'] = '';
		}
		echo json_encode($response);
		die();
	}

	public function update_product_color_group()
	{
		$res =  array();
		$priceAlert='no';
		$store_master_id   = $_POST['store_master_id'];
		$product_groupname = $_POST['product_groupname'];
		$store_organization_type_master_id = $_POST['store_organization_type_master_id'];
		$color_arr = $_POST['color_arr'];
		$sql = 'SELECT * FROM `store_master` WHERE id="' . $store_master_id . '"';
		$store_master_data =  parent::selectTable_f_mdl($sql);
		
		if (!empty($product_groupname)) {
			$sql = 'SELECT * FROM `store_owner_product_master` WHERE store_master_id="' . $store_master_id . '" AND group_name = "'.$product_groupname.'" ';
			$pro_list =  parent::selectTable_f_mdl($sql);
			if (!empty($pro_list)) {
				foreach ($pro_list as $single_pro) {
					$storeowner_product_master_id = $single_pro['id'];
					$funddata= 'SELECT fundraising_price FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id ='.$storeowner_product_master_id.' ';
					$fundres =  parent::selectTable_f_mdl($funddata);
					$fundraising_price = 0;
					if(!empty($fundres)){
						$fundraising_price = $fundres[0]['fundraising_price'];
					}

					$sql = 'SELECT color FROM store_owner_product_variant_master where store_owner_product_master_id = ' . $storeowner_product_master_id . ' group by color';
					$getColor = parent::selectTable_f_mdl($sql);
					foreach ($getColor as $valueColor) {
						$colorarr_get[] = $valueColor['color'];
					}

					$productColors = '';
					$productCode = array();
					if (sizeof($color_arr) > 0) {
						foreach ($color_arr as $value) {
							$sql1      = 'SELECT product_color FROM store_product_colors_master where product_color_name = "' . $value . '" ';
							$colorName = parent::selectTable_f_mdl($sql1);
							if (!empty($colorName)) {
								foreach ($colorName as $valueC) {
									$productCode[] = $valueC['product_color'];
								}
							}
						}
					}

					$productCode = array_diff($productCode, $colorarr_get);
					$JsonproductArray = json_encode(array_values($productCode));
					$colorCodeValues  = str_replace(array('[', ']'), '', $JsonproductArray);
				
					if (!empty($colorCodeValues)) {
						$existSql = 'SELECT * FROM `store_product_variant_master` WHERE store_product_master_id="' . $single_pro['store_product_master_id'] . '" AND color IN(' . $colorCodeValues . ') and store_organization_type_master_id = ' . $_POST["store_organization_type_master_id"] . ' AND is_ver_deleted = "0" ';
					} else {
						$existSql = 'SELECT * FROM `store_product_variant_master` WHERE store_product_master_id="' . $single_pro['store_product_master_id'] . '" and store_organization_type_master_id = ' . $_POST["store_organization_type_master_id"] . ' AND is_ver_deleted = "0"';
					}
					$existData =  parent::selectTable_f_mdl($existSql);

					if (!empty($existData)) {

						$sqlmasterprod = 'SELECT id,is_eligible_sleeve_print from store_product_master where id="'.$single_pro['store_product_master_id'].'" AND is_deleted="0" ';
						$productMasterdata = parent::selectTable_f_mdl($sqlmasterprod);
						$is_eligible_sleeve_print='0';
						if(!empty($productMasterdata)){
							$is_eligible_sleeve_print=$productMasterdata[0]['is_eligible_sleeve_print'];
						}
						//insert product details
						if (isset($storeowner_product_master_id)) {

							$sql = 'SELECT sopvm.front_side_ink_colors_group,sopvm.back_side_ink_colors_group,sopvm.sleeve_ink_color_group,sopvm.is_back_enable_group FROM store_owner_product_master as sopm INNER JOIN store_owner_product_variant_master as sopvm ON sopm.id=sopvm.store_owner_product_master_id WHERE sopm.store_master_id="'.$store_master_id.'" AND sopm.group_name="'.$single_pro['group_name'].'" LIMIT 1';
							$prodInkCostGroup =  parent::selectTable_f_mdl($sql);

							$sqlGroupHistory = 'SELECT changed_sleeve_ink_group_price FROM group_ink_price_history WHERE store_master_id="'.$store_master_id.'" AND  group_name="'.$single_pro['group_name'].'" ORDER BY id DESC LIMIT 1';
							$prodInkCostGroupHistoryData =  parent::selectTable_f_mdl($sqlGroupHistory);
							$prodInkCostGroupHistory='0';
							if(!empty($prodInkCostGroupHistoryData)){
								$prodInkCostGroupHistory = $prodInkCostGroupHistoryData[0]['changed_sleeve_ink_group_price'];
							}


							$sql = 'SELECT * FROM `store_product_variant_master` WHERE store_product_master_id="' . $single_pro['store_product_master_id'] . '" AND color IN(' . $colorCodeValues . ') AND store_organization_type_master_id = ' . $_POST["store_organization_type_master_id"] . ' AND is_ver_deleted = "0" ';
							// print_r($sql);die;
							$var_list =  parent::selectTable_f_mdl($sql);
							if (!empty($var_list)) {
								foreach ($var_list as $var_data) {
									$image = $var_data['image'];

									// Task 42 start
									$sql = 'SELECT price,price_on_demand from store_product_variant_master where id="' . $var_data['id'] . '" AND is_ver_deleted = "0"';
									$storeProductVariantMaster = parent::selectTable_f_mdl($sql);
									
									/*
									* Front-side and back-side price only added with on-demand store
									* Add front-side as per color catridge price into base price
									*/
									$add_cost = 0;
									if(isset($prodInkCostGroup[0]['front_side_ink_colors_group']) && !empty($prodInkCostGroup[0]['front_side_ink_colors_group'])){
										$add_cost += intval($prodInkCostGroup[0]['front_side_ink_colors_group'])-1;
									}else if(isset($store_master_data[0]['front_side_ink_colors']) && !empty($store_master_data[0]['front_side_ink_colors'])){
										$add_cost += intval($store_master_data[0]['front_side_ink_colors'])-1;
									}

									$add_on_cost = 0;
									if(isset($prodInkCostGroup[0]['back_side_ink_colors_group']) && !empty($prodInkCostGroup[0]['back_side_ink_colors_group'])){
										$add_cost   += common::ADD_COST_BACK_SIDE_INK_COLOR+intval($prodInkCostGroup[0]['back_side_ink_colors_group'])-1;
										$add_on_cost = common::ADD_COST_BACK_SIDE_INK_COLOR;
									}else if(isset($store_master_data[0]['back_side_ink_colors']) && !empty($store_master_data[0]['back_side_ink_colors'])){
										$add_cost   += common::ADD_COST_BACK_SIDE_INK_COLOR+intval($store_master_data[0]['back_side_ink_colors'])-1;
										$add_on_cost = common::ADD_COST_BACK_SIDE_INK_COLOR;
									}

									if(isset($prodInkCostGroupHistory) && !empty($prodInkCostGroupHistory)){
										$add_cost += common::ADD_COST_BACK_SIDE_INK_COLOR + intval($prodInkCostGroupHistory)-1;
									}else if(isset($store_master_data[0]['sleeve_ink_colors']) && !empty($store_master_data[0]['sleeve_ink_colors'])){
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
									if (isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type'] == 'SHIP_1_LOCATION_SORT') {
										$fullfilment_type_price = $fullfilment_gold_price;
										//$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_2;
									}else if (isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type'] == 'SHIP_EACH_FAMILY_HOME') {
										$fullfilment_type_price = $fullfilment_platinum_price;
										//$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_3;
									}else if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=='SHIP_1_LOCATION_NOT_SORT'){
										$fullfilment_type_price = $fullfilment_silver_price;
									}
									
									//To do add bussiness login for fullfilmemnt type & fundrising
									$ondemandPrice  = 0;
									$flashSalePrice = 0;
									if (isset($storeProductVariantMaster[0]['price']) && $storeProductVariantMaster[0]['price_on_demand']) {
										$ondemandPrice  = (floatval($storeProductVariantMaster[0]['price_on_demand']) + $add_on_cost);
										$flashSalePrice = $storeProductVariantMaster[0]['price'] + $add_cost + $fullfilment_type_price;
									} else {
										$ondemandPrice  = (floatval($storeProductVariantMaster[0]['price_on_demand']) + $add_on_cost);
										$flashSalePrice = $storeProductVariantMaster[0]['price'] + $add_cost + $fullfilment_type_price;
										//$ondemandPrice  = $var_data['price_on_demand'];
										//$flashSalePrice = $var_data['price'];
									}

									if(!empty($prodInkCostGroup)){
										$front_side_ink_colors_group=trim($prodInkCostGroup[0]['front_side_ink_colors_group']);
										$back_side_ink_colors_group=trim($prodInkCostGroup[0]['back_side_ink_colors_group']);
										$sleeve_ink_color_group=trim($prodInkCostGroup[0]['sleeve_ink_color_group']);
										$is_back_enable_group=trim($prodInkCostGroup[0]['is_back_enable_group']);
									}else{
										$front_side_ink_colors_group=trim($store_master_data[0]['front_side_ink_colors']);
										$back_side_ink_colors_group=trim($store_master_data[0]['back_side_ink_colors']);
										$sleeve_ink_color_group=trim($store_master_data[0]['sleeve_ink_colors']);
										$is_back_enable_group=trim($store_master_data[0]['is_back_enable']);
									}

									$sql = 'SELECT id,price,price_on_demand,store_owner_product_master_id FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id="'.$storeowner_product_master_id.'" AND store_organization_type_master_id ='.$_POST["store_organization_type_master_id"].' LIMIT 1 ';								
									$StoreVerData =  parent::selectTable_f_mdl($sql);
									if(!empty($StoreVerData)){
										if($StoreVerData[0]['price']==$flashSalePrice &&  $StoreVerData[0]['price_on_demand']==$ondemandPrice){
											$priceAlert='no';
										}else{
											$priceAlert='yes';
										}
									}

									if($is_eligible_sleeve_print=='0'){
										if(isset($prodInkCostGroupHistory) && !empty($prodInkCostGroupHistory)){
											$sleevecost = common::ADD_COST_BACK_SIDE_INK_COLOR + intval($prodInkCostGroupHistory)-1;
										}else if(isset($store_master_data[0]['sleeve_ink_colors']) && !empty($store_master_data[0]['sleeve_ink_colors'])){
											$sleevecost = common::ADD_COST_BACK_SIDE_INK_COLOR + intval($store_master_data[0]['sleeve_ink_colors'])-1;
										}
										$flashSalePrice = $flashSalePrice - $sleevecost;

										$sopvm_insert_data = [
											'store_owner_product_master_id'     => $storeowner_product_master_id,
											'store_product_variant_master_id'   => $var_data['id'],
											'store_organization_type_master_id' => $var_data['store_organization_type_master_id'],
											'price'                             => $flashSalePrice,
											'price_on_demand'                   => $ondemandPrice,
											'fundraising_price'					=> $fundraising_price,
											'color'     		                => $var_data['color'],
											'size'      		                => $var_data['size'],
											'image'                             => $var_data['image'],
											'original_image'                    => $var_data['feature_image'],
											'sku' 				                => $var_data['sku'],
											'weight' 			                => $var_data['weight'],
											'front_side_ink_colors_group' 		=> $front_side_ink_colors_group,
											'back_side_ink_colors_group' 		=> $back_side_ink_colors_group,
											'sleeve_ink_color_group' 		    => '0',
											'is_back_enable_group'  	 		=> $is_back_enable_group,
											'status' 			                => '1',
											'created_on' 		                => @date('Y-m-d H:i:s'),
											'created_on_ts' 	                => time()
										];
									}else{
										$sopvm_insert_data = [
											'store_owner_product_master_id'     => $storeowner_product_master_id,
											'store_product_variant_master_id'   => $var_data['id'],
											'store_organization_type_master_id' => $var_data['store_organization_type_master_id'],
											'price'                             => $flashSalePrice,
											'price_on_demand'                   => $ondemandPrice,
											'fundraising_price'					=> $fundraising_price,
											'color'     		                => $var_data['color'],
											'size'      		                => $var_data['size'],
											'image'                             => $var_data['image'],
											'original_image'                    => $var_data['feature_image'],
											'sku' 				                => $var_data['sku'],
											'weight' 			                => $var_data['weight'],
											'front_side_ink_colors_group' 		=> $front_side_ink_colors_group,
											'back_side_ink_colors_group' 		=> $back_side_ink_colors_group,
											'sleeve_ink_color_group' 		    => $sleeve_ink_color_group,
											'is_back_enable_group'  	 		=> $is_back_enable_group,
											'status' 			                => '1',
											'created_on' 		                => @date('Y-m-d H:i:s'),
											'created_on_ts' 	                => time()
										];

									}
									$ger = parent::insertTable_f_mdl('store_owner_product_variant_master', $sopvm_insert_data);
									// print_r($ger);die;
								}
								$sopm_update_data = [
									'is_product_synced_to_collect' => '0'
								];
								parent::updateTable_f_mdl('store_owner_product_master',$sopm_update_data,'id="'.$storeowner_product_master_id.'"');
							}
							//now open store to sync in shopify
							$sm_update_data = [
								'is_products_synced' => '0',
								'is_manual_store_sync' => '1'
							];
							parent::updateTable_f_mdl('store_master', $sm_update_data, 'id="' . $store_master_id . '"');
						}
						$res['SUCCESS'] = 'TRUE';
						$res['priceAlert']=$priceAlert;
						$res['MESSAGE'] = 'Color added successfully.';
					} else {
						$res['SUCCESS'] = 'TRUE';
						$res['priceAlert']=$priceAlert;
						$res['MESSAGE'] = 'Color added successfully.';
					}
				}
				$varSql ="SELECT count(*) as totalVariant FROM store_owner_product_master as sopm LEFT JOIN store_owner_product_variant_master as sopvm ON sopvm.store_owner_product_master_id = sopm.id where sopm.store_master_id = '$store_master_id' and sopvm.id!='' ";
				$varInfo = parent::selectTable_f_mdl($varSql);
				$totalVariant = 0;
				if(!empty($varInfo)){
					$varData = $varInfo[0];
					$totalVariant = $varData['totalVariant'];
				}
				$updatVariantCount = [
					"total_variants_count"=>$totalVariant
				];
				parent::updateTable_f_mdl('store_master',$updatVariantCount,'id="'.$store_master_id.'"');
			} else {
				$res['SUCCESS'] = 'FALSE';
				$res['priceAlert']=$priceAlert;
				$res['MESSAGE'] = 'Error while inserting addtional color. Please check and try again after some time.';
			}
			
			echo json_encode($res);
		}
		die;
	}

	public function add_identifier_group_products()
	{
		$res =  array();
		if(!empty($_POST['store_master_id'])){
			$store_master_id = trim($_POST['store_master_id']);
			$groupname       =trim($_POST['groupname']);
			$group_identifier_keyword=trim($_POST['group_identifier_keyword']);
			$sql = 'SELECT * FROM `store_master` WHERE id="' . $store_master_id . '"';
			$store_master_data =  parent::selectTable_f_mdl($sql);
			$sql='SELECT product_title FROM store_owner_product_master WHERE store_master_id="'.$store_master_id. '"';
			$pro_data_new = parent::selectTable_f_mdl($sql);
			foreach ($pro_data_new as $single_product) {
				$product_title=trim($single_product['product_title']);
				$unique_keyget = substr($product_title, strpos($product_title, "_") + 1); 
				if($unique_keyget==$group_identifier_keyword){
					$res['SUCCESS'] = 'FALSE';
					$res['MESSAGE'] = 'This keyword is already used in this store.';
					echo json_encode($res);die;
				}
			}

			$sql = 'SELECT store_owner_product_master.id,store_owner_product_master.product_title,store_owner_product_master.shop_product_id, store_master.store_name FROM `store_owner_product_master`
				LEFT JOIN store_master ON store_master.id = store_owner_product_master.store_master_id
				WHERE store_owner_product_master.store_master_id="'.$store_master_id.'" AND store_owner_product_master.group_name = "'.$groupname.'" AND store_owner_product_master.is_soft_deleted="0"
			';
			$pro_data = parent::selectTable_f_mdl($sql);
			if(!empty($pro_data)){
				foreach ($pro_data as $single_pro) {
					$store_owner_product_master_id = $single_pro['id'];

					$sopm_update_data = [
						'product_title' => $single_pro['product_title']."_".$group_identifier_keyword,
					];
					parent::updateTable_f_mdl('store_owner_product_master',$sopm_update_data,'id="'.$store_owner_product_master_id.'"');

					if(isset($single_pro['shop_product_id']) && !empty($single_pro['shop_product_id'])){
						//if we have shopify product id, then we also update title in shopify
						$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
						if(!empty($shop_data)){
							global $path;
							require_once($path.'lib/class_graphql.php');

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
							  	"id":"gid://shopify/Product/'.$single_pro['shop_product_id'].'",
							  	"title":"'.$single_pro['store_name'].'-'.trim($single_pro['product_title']."_".$group_identifier_keyword).'"
							  }
							}';
							$graphql->runByMutation($mutationData,$inputData);
						}
					}
				}
				$res['SUCCESS'] = 'TRUE';
				$res['MESSAGE'] = 'Product identifier added successfully.';
			}else{
				$res['SUCCESS'] = 'FALSE';
				$res['MESSAGE'] = 'Error while updating details. Please check and try again after some time.';
			}
			echo json_encode($res);
		}
		die;
	}

	public function check_product_prefix()
	{
		$res =  array();
		if(!empty($_POST['store_master_id'])){
			$store_master_id = trim($_POST['store_master_id']);
			$product_name_prefix=trim($_POST['product_name_prefix']);
			
			$sql='SELECT product_title FROM store_owner_product_master WHERE store_master_id="'.$store_master_id. '"';
			$pro_data_new = parent::selectTable_f_mdl($sql);
			foreach ($pro_data_new as $single_product) {
				$product_title=trim($single_product['product_title']);
				$unique_keyget = substr($product_title, strpos($product_title, "_") + 1); 
				if($unique_keyget==$product_name_prefix){
					$res['SUCCESS'] = 'FALSE';
					$res['MESSAGE'] = 'This keyword is already used in this store.';
					echo json_encode($res);die;
				}
			}
			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = 'This keyword is not used in this store.';
		}else{
			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = 'This keyword is not used in this store.';
		}
		echo json_encode($res);die;
	}

	public function getProductListMoreThenHundred($store_master_id){
		$sql = 'SELECT sopm.id AS product_id,sopm.product_title AS product_title,sopvm.image,COUNT(sopvm.id) AS variant_count,spm.vendor_product_id FROM 
			store_owner_product_master sopm JOIN store_owner_product_variant_master sopvm ON sopm.id = sopvm.store_owner_product_master_id
			INNER JOIN store_product_master as spm ON spm.id = sopm.store_product_master_id
			WHERE sopm.store_master_id ="'.$store_master_id.'" AND sopm.is_soft_deleted="0"  GROUP BY sopm.id, sopm.product_title
			HAVING COUNT(sopvm.id) > 100 ORDER BY variant_count DESC
		';
		return parent::selectTable_f_mdl($sql);
	}

	public function save_personalization_label()
	{
		$res =  array();
		if(!empty($_POST['store_master_id'])){
			$store_master_id 			= trim($_POST['store_master_id']);
			$personalization_item_label = trim($_POST['personalization_item_label']);

			$sopm_update_data = [
				'personalization_item_label' => $personalization_item_label
			];
			parent::updateTable_f_mdl('store_owner_product_master',$sopm_update_data,'store_master_id="'.$store_master_id.'"');
			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = 'Label updated successfully.';

		}else{
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'Error while updating details. Please check and try again after some time.';
		}
		echo json_encode($res);die;
	}

	public function change_group_ink_price(){
		global $login_user_email;
		$res =  array();
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
		if(!empty($_POST['store_master_id']) && !empty($_POST['group_name'])){
			$store_master_id 				= $_POST['store_master_id'];
			$group_name      				= $_POST['group_name'];
			$store_sale_type_master_id 		= $_POST['store_sale_type_master_id'];
			$changed_front_ink_group_price  = $_POST['changed_front_ink_group_price'];
			$changed_back_ink_group_price   = $_POST['changed_back_ink_group_price'];
			$changed_sleeve_ink_color_group = $_POST['changed_sleeve_ink_color_group'];
			$back_side_ink_ondemand   		= $_POST['back_side_ink_ondemand'];
			
			$sql = 'SELECT * FROM `store_master` WHERE id="' . $store_master_id . '"';
			$store_master_data =  parent::selectTable_f_mdl($sql);

			$sql = 'SELECT id FROM `store_owner_product_master` WHERE store_master_id="' . $store_master_id . '" AND group_name = "'.$group_name.'" AND is_soft_deleted="0" ';
			$pro_list =  parent::selectTable_f_mdl($sql);
			$store_owner_product_master_id = [];
			if (!empty($pro_list)) {
				foreach ($pro_list as $single_pro) {
					$store_owner_product_master_id[] = $single_pro['id'];
				}						
			}

			$sql = 'SELECT * FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id IN('.implode(",",$store_owner_product_master_id).') ';
			$var_list =  parent::selectTable_f_mdl($sql);
			if (!empty($var_list)) {
				foreach ($var_list as $Varvalue) {
					$input_price = 0;
					
					$sql = 'SELECT price,price_on_demand from store_product_variant_master where id="'.$Varvalue['store_product_variant_master_id'].'"';
					$storeProductVariantMaster = parent::selectTable_f_mdl($sql);

					//flash sale
					$add_cost = 0;
					if(isset($changed_front_ink_group_price) && !empty($changed_front_ink_group_price)){
						$add_cost += intval($changed_front_ink_group_price)-1;
					}

					if(isset($changed_back_ink_group_price ) && !empty($changed_back_ink_group_price )){
						$add_cost   += common::ADD_COST_BACK_SIDE_INK_COLOR+intval($changed_back_ink_group_price )-1;
					}

					if(isset($changed_sleeve_ink_color_group ) && !empty($changed_sleeve_ink_color_group )){
						$add_cost   += common::ADD_COST_BACK_SIDE_INK_COLOR + intval($changed_sleeve_ink_color_group )-1;
					}
					//on demand
					$add_on_cost = 0;
					if(isset($back_side_ink_ondemand ) && $back_side_ink_ondemand=='Yes'){
						$add_on_cost = common::ADD_COST_BACK_SIDE_INK_COLOR;
					}

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
					}else if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
						$fullfilment_type_price = $fullfilment_platinum_price;
						//$fullfilment_type_price = common::ADD_COST_STORE_FULFILLMENT_TYPE_3;
					}else if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=='SHIP_1_LOCATION_NOT_SORT'){
						$fullfilment_type_price = $fullfilment_silver_price;
					}

					$sqlmaster_group = 'SELECT id,group_name from store_owner_product_master where id="'.$Varvalue['store_owner_product_master_id'].'" AND is_soft_deleted="0" ';
					$storeProductGroup = parent::selectTable_f_mdl($sqlmaster_group);
					$group_name='';
					if(!empty($storeProductGroup)){
						$group_name=$storeProductGroup[0]['group_name'];
					}


					$ondemandPrice  = 0;
					$flashSalePrice = 0;
					if(isset($storeProductVariantMaster[0]['price']) && $storeProductVariantMaster[0]['price_on_demand']){
						if($group_name=='Yard Signs'){
							$ondemandPrice = (floatval($storeProductVariantMaster[0]['price_on_demand']));
							$flashSalePrice     = $storeProductVariantMaster[0]['price'];
						}else{
							$ondemandPrice = (floatval($storeProductVariantMaster[0]['price_on_demand'])+$add_on_cost);
							$flashSalePrice     = $storeProductVariantMaster[0]['price']+$add_cost+$fullfilment_type_price;
						}	
					}else{
						$ondemandPrice = $var_data['price_on_demand'];
						$flashSalePrice     = $var_data['price'];
					}

					$sopvm_update_data = [
						'price'             			=> $flashSalePrice,
						'price_on_demand'   			=> $ondemandPrice,
						'is_back_enable_group' 			=>trim($back_side_ink_ondemand),
						'front_side_ink_colors_group' 	=> $changed_front_ink_group_price,
						'back_side_ink_colors_group'  	=> $changed_back_ink_group_price,
						'sleeve_ink_color_group'		=> $changed_sleeve_ink_color_group
					];

					if(isset($store_sale_type_master_id) && $store_sale_type_master_id==2){
						$input_price = $ondemandPrice + floatval(trim($Varvalue['fundraising_price']));
					}else{
						
						$input_price = $flashSalePrice + floatval(trim($Varvalue['fundraising_price']));// add new variable add_price and price_update_setting_change task 58
					}

					if(!empty($Varvalue['shop_variant_id'])){
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
									"id":"gid://shopify/ProductVariant/'.$Varvalue['shop_variant_id'].'",
									"price":"'.( $input_price ).'"
									}
								}';
						$graphql->runByMutation($mutationData,$inputData);
					}

					parent::updateTable_f_mdl('store_owner_product_variant_master',$sopvm_update_data,'id="'.$Varvalue['id'].'"');
				}

				$storeType = '';
				if($store_sale_type_master_id==1){
					$storeType = "Flash Sale";
				}else{
					$storeType = "On-Demand";
				}
				$changeGroupInkPriceData = [
					'store_master_id' 				=>$store_master_id,
					'group_name'      				=>$group_name,
					'store_type'      				=>$storeType,
					'changed_front_ink_group_price' =>$changed_front_ink_group_price,
					'changed_back_ink_group_price'  =>$changed_back_ink_group_price,
					'changed_sleeve_ink_group_price'=>$changed_sleeve_ink_color_group,
					'back_side_ink_ondemand'   		=>$back_side_ink_ondemand,
					'created_on'      				=>date('Y-m-d H:i:s'),
					"updated_by"      				=> "Super Admin <br>(".$login_user_email.")",
				];
				parent::insertTable_f_mdl('group_ink_price_history',$changeGroupInkPriceData);

				$res['SUCCESS'] = 'TRUE';
				$res['MESSAGE'] = 'Price changed successfully.';
			}else{
				$res['SUCCESS'] = 'FALSE';
				$res['MESSAGE'] = 'Product variant not found.';
			}
		}else{
			$res['SUCCESS'] = 'FALSE';
			$res['MESSAGE'] = 'Error while inserting store details. Please check and try again after some time.';
		}
		echo json_encode($res);die();	
	}

	public function getChangeGroupInkPriceHistory(){	
		$html = '';
		if(!empty($_POST['store_master_id']) && !empty($_POST['group_name'])){
			$store_master_id = $_POST['store_master_id'];
			$group_name      = $_POST['group_name'];
			$sql = 'SELECT * FROM group_ink_price_history WHERE store_master_id="'.$store_master_id.'" AND group_name ="'.$group_name.'" order by id desc ';
			$inkpriceData = parent::selectTable_f_mdl($sql);
			if(!empty($inkpriceData)){ 
				foreach ($inkpriceData as $value){
					$source      = $value['created_on'];
					$date        = new DateTime($source);
					$createdDate = $date->format("m/d/Y h:i A");
					$html.="<tr>";
					if($value['store_type']=='Flash Sale'){
					$html.="<td>".$value['changed_front_ink_group_price']."</td>
					<td>".$value['changed_back_ink_group_price']."</td>
					<td>".$value['changed_sleeve_ink_group_price']."</td>";
					$html.="<td></td>";
					$html.="<td></td>";
					}else{
					$html.="<td></td>";
					$html.="<td></td>";
					$html.="<td></td>";
					$html.="<td>".$value['back_side_ink_ondemand']."</td>";
					$html.="<td></td>";
					}
					$html.="<td>".$value['store_type']."</td>
					<td>".$createdDate."</td>
					<td>".$value['updated_by']."</td>
					</tr>";   
				}
			} 
		}
		echo $html;

	}

	public function getGroupInkCost(){	
		$inkCostData = [];
		if(!empty($_POST['store_master_id']) && !empty($_POST['group_name'])){
			$store_master_id = $_POST['store_master_id'];
			$group_name      = $_POST['group_name'];
			$sql = 'SELECT sopm.id,sopm.store_master_id,sopm.store_product_master_id,sopm.group_name,sopvm.id as store_owner_product_variant_id,sopvm.front_side_ink_colors_group,sopvm.back_side_ink_colors_group,sopvm.sleeve_ink_color_group,sopvm.is_back_enable_group FROM store_owner_product_master as sopm INNER JOIN store_owner_product_variant_master as sopvm ON sopm.id=sopvm.store_owner_product_master_id WHERE sopm.store_master_id="'.$store_master_id.'" AND sopm.group_name="'.$group_name.'" AND sopm.is_soft_deleted="0" Group by sopm.group_name ';
			$inkCostData = parent::selectTable_f_mdl($sql);
		}
		echo json_encode($inkCostData);die();
	}

}
