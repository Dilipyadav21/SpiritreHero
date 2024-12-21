<?php
include_once 'model/sa_order_view_mdl.php';

$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';
$login_user_email="";
if(isset($_SESSION['user_email']) && $_SESSION['user_email'] != "") {
	$login_user_email=trim($_SESSION['user_email']);
}
class sa_order_view_ctl extends sa_order_view_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}

		if(isset($_REQUEST['action'])){
			$action = $_REQUEST['action'];
			if($action=='update_order_cust_email'){
				$this->update_order_cust_email();exit;
			}elseif($action=='update_order_cust_name'){
				$this->update_order_cust_name();exit;
			}elseif($action=='mark_as_manually_orderd'){
				$this->mark_as_manually_orderd();exit;
			}elseif($action=='mark_as_manually_orderd_bulk'){
				$this->mark_as_manually_orderd_bulk();exit;
			}
		}
		
		common::CheckLoginSession();
	}
	
	function getOrderViewInfo(){
		if(!empty(parent::getVal("oid")))
		{	
			$this->orderId = parent::getVal("oid");
			
			$orderData = $this->get_order_details($this->orderId);
			
			return $orderData;die();
		}
	}
	// 84
	// function getstoreProductMaster(){
	// 	return parent::getstoreProductMaster_f_mdl();
	// }
	
	function updateOrderInfo(){
		if(!empty(parent::getVal("method")) && parent::getVal("method") == "update_order_product"){
			$variant_size = parent::getVal("variant_size");
			$select_color = parent::getVal("select_color");
			$new_qty = parent::getVal("new_qty");
			$new_title = parent::getVal("new_title");
			$new_color_code = parent::getVal("color_code");
			$prod_edit_store_owner_product_master_id =  parent::getVal("prod_edit_store_owner_product_master_id");
			$shop_variant_id =  parent::getVal("shop_variant_id");
			$sopm_id =  parent::getVal("sopm_id");
			$variant_title = $variant_size.' / '.$select_color;
			$fundraising_price=0;
			$ver_price=0;
			$store_owner_product_master_id = parent::getVal("store_order_items_master_id");

			$sql = 'SELECT shop_variant_id,id,sku,price,price_on_demand,fundraising_price
					FROM store_owner_product_variant_master
					WHERE color = "'.$new_color_code.'" AND size = "'.$variant_size.'" AND store_owner_product_master_id = "'.$sopm_id.'"';

			$newProductVariantData = parent::selectTable_f_mdl($sql);

			$sqlclone = 'Select * from store_order_items_master where id = "'.$store_owner_product_master_id.'" ';
			$queryclone = parent::selectTable_f_mdl($sqlclone);
			$store_master_id=$queryclone[0]['store_master_id'];

			$previous_array = [ 'title' => $queryclone[0]['title'], 'quantity' =>  $queryclone[0]['quantity'], 'variant_title' => $queryclone[0]['variant_title'] ];
			$previous_status = json_encode($previous_array);

			$new_array = [ 'title' => $new_title, 'quantity'=> $new_qty, 'variant_title' => $variant_title ,'color' => $new_color_code];
			$new_status = json_encode($new_array);

			$new_variant_id = '';
			$store_owner_product_variant_master_id = '';
			if(!empty($newProductVariantData)){
				$new_variant_id = $newProductVariantData[0]['shop_variant_id'];
				$store_owner_product_variant_master_id = $newProductVariantData[0]['id'];
				$new_sku = $newProductVariantData[0]['sku'];
			}
			$storeSql='SELECT store_organization_type_master_id,store_sale_type_master_id FROM store_master where id="'.$store_master_id.'" ';
			$StoreData = parent::selectTable_f_mdl($storeSql);
			if(!empty($StoreData)){
				$fundraising_price	= $newProductVariantData[0]['fundraising_price'];
				$new_prodfundraising_price=$fundraising_price*$new_qty;
				if($StoreData[0]['store_sale_type_master_id'] =='1'){
					$ver_price			= $newProductVariantData[0]['price']+$fundraising_price;
				}else{
					$ver_price= $newProductVariantData[0]['price_on_demand']+$fundraising_price;
				}
			}
			if( $shop_variant_id == $new_variant_id) {
				/* $preTitleName = '';
				if( isset( $queryclone[0]['title'] ) && $queryclone[0]['title'] == $new_title ) {
					
					$queryclone[0]['title']
				}
				if( isset( $queryclone[0]['quantity'] ) && $queryclone[0]['quantity'] == $new_qty ) {
					$changeFieldName .= 'Quantity ,';
				}

				if( isset( $queryclone[0]['variant_title'] ) && $queryclone[0]['variant_title'] == $variant_title ) {
					$changeFieldName .= 'Variant Title ,';
				} */

				

				$order_items_master_id = $store_owner_product_master_id;
				$orders_master_id = $queryclone[0]['store_orders_master_id'];
				$user_action = 'Edit';

				$this->setNoticesLogs($order_items_master_id,$orders_master_id,$user_action,$previous_status,$new_status);
				parent::updateOrdersDetail_f_mdl($variant_title,$ver_price,$fundraising_price,$new_prodfundraising_price,$store_owner_product_master_id,$new_qty,$new_title,$shop_variant_id,$prod_edit_store_owner_product_master_id);
				 
				$neworderItems = 'SELECT quantity,price,fundraising_amount,is_deleted FROM store_order_items_master WHERE store_orders_master_id = "'.$orders_master_id.'" ';
				$neworderItemsData = parent::selectTable_f_mdl($neworderItems);
				if(!empty($neworderItemsData)){
					$total_fund_order="0.00";
					$total_price="0.00";
					foreach($neworderItemsData as $single_item){
						if((int)$single_item['is_deleted'] != 1 ){
							$total_fund_order += $single_item['fundraising_amount'];
							$total_price  += $single_item['price'] * $single_item['quantity'];
						}
					}
				}

				$om_update_data = [
                    'total_fundraising_amount' => $total_fund_order,
                    'total_price' => $total_price
                ];
                parent::updateTable_f_mdl('store_orders_master',$om_update_data,'id="'.$orders_master_id.'"');
                $resultArray["isSuccess"] = true ;
                common::sendJson($resultArray);
			
			}else {
				//parent::updateOrdersDetail_f_mdl($variant_title,$store_owner_product_master_id,$new_qty,$new_title,$shop_variant_id);
				//parent::updateOrderslineItems_f_mdl($store_owner_product_master_id);

				parent::updateOrderslineItems_f_mdl($store_owner_product_master_id);				
				
				unset($queryclone[0]['id']);
				unset($queryclone[0]['is_deleted']);
				
				if( $new_variant_id != '' ) {
					$queryclone[0]['shop_variant_id'] = $new_variant_id;
				}
				$queryclone[0]['store_owner_product_variant_master_id'] = $store_owner_product_variant_master_id;
				$queryclone[0]['variant_title'] = $variant_title;
				$queryclone[0]['store_owner_product_master_id'] = $sopm_id;
				$queryclone[0]['quantity'] = $new_qty;
				$queryclone[0]['title'] = $new_title;
				$queryclone[0]['sku'] = $new_sku;
				$queryclone[0]['price'] = $ver_price;
				$queryclone[0]['fundraising_amount'] = $new_prodfundraising_price;
				$queryclone[0]['is_deleted'] = 0;
				$queryclone[0]['item_update_status'] = 'Updated';


				parent::insertTable_f_mdl('store_order_items_master',$queryclone[0]);

				$order_items_master_id = $store_owner_product_master_id;
				$orders_master_id = $queryclone[0]['store_orders_master_id'];

				$neworderItems = 'SELECT quantity,price,fundraising_amount,is_deleted FROM store_order_items_master WHERE store_orders_master_id = "'.$orders_master_id.'" ';
				$neworderItemsData = parent::selectTable_f_mdl($neworderItems);
				if(!empty($neworderItemsData)){
					$total_fund_order="0.00";
					$total_price="0.00";
					foreach($neworderItemsData as $single_item){
						if((int)$single_item['is_deleted'] != 1 ){
							$total_fund_order += $single_item['fundraising_amount'];
							$total_price  += $single_item['price'] * $single_item['quantity'];
						}
					}
				}

				$om_update_data = [
                    'total_fundraising_amount' => $total_fund_order,
                    'total_price' => $total_price
                ];
                parent::updateTable_f_mdl('store_orders_master',$om_update_data,'id="'.$orders_master_id.'"');

				$user_action = 'Edit';
				$this->setNoticesLogs($order_items_master_id,$orders_master_id,$user_action,$previous_status,$new_status);
				
				$resultArray["isSuccess"] = true ;
				common::sendJson($resultArray);
			}
			
		}
	}
	
	function updateOrderSortListInfo(){
		if(!empty(parent::getVal("method")) && parent::getVal("method") == "update_sort_list_info"){

			$student_name = parent::getVal("student_name");
			$select_sort_list = parent::getVal("select_sort_list");
			$store_order_id = parent::getVal("oid");

			parent::updateOrderSortListInfo_f_mdl($store_order_id,$select_sort_list,$student_name);
		}
	}

	function get_order_details($store_orders_master_id){
		$sql = 'SELECT `store_orders_master`.* FROM `store_orders_master`
		LEFT JOIN `store_master` ON `store_master`.id = `store_orders_master`.store_master_id
		WHERE `store_orders_master`.id="'.$store_orders_master_id.'"
		';
		$list_data = parent::selectTable_f_mdl($sql);
		if(!empty($list_data)){
			$sql = 'SELECT `store_order_items_master`.store_owner_product_variant_master_id,`store_order_items_master`.id, store_order_items_master. personalization_name,store_order_items_master. personalization_item_name,store_order_items_master.shop_product_id, `store_order_items_master`.store_owner_product_master_id,`store_order_items_master`.shop_variant_id, `store_order_items_master`.title, `store_order_items_master`.quantity, `store_order_items_master`.price, `store_order_items_master`.sku,`store_order_items_master`.variant_title,store_owner_product_variant_master.image
			,store_order_items_master.is_deleted,store_order_items_master.fundraising_amount,store_vendors_master.vendor_name,store_orders_master.is_sent_customcat,store_orders_master.fe_order_id,store_order_items_master.mark_as_manually_ordered_vendor,store_order_items_master.mark_as_manually_ordered_by,shipengine_shipping_rates_master.tracking_status,shipengine_shipping_rates_master.ship_date,fe_webhook_master.fe_tracker_number,fe_webhook_master.fe_tracking_url,store_orders_master.created_on FROM `store_order_items_master`
			LEFT JOIN store_owner_product_variant_master ON store_owner_product_variant_master.id = `store_order_items_master`.store_owner_product_variant_master_id
			LEFT JOIN store_owner_product_master ON store_owner_product_master.id=store_order_items_master.store_owner_product_master_id
            LEFT JOIN store_product_master ON store_product_master.id=store_owner_product_master.store_product_master_id
            LEFT JOIN store_vendors_master ON store_vendors_master.id=store_product_master.vendor_id
            LEFT JOIN store_orders_master ON store_orders_master.id = store_order_items_master.store_orders_master_id
			LEFT JOIN fe_webhook_master ON fe_webhook_master.fe_order_id=store_orders_master.fe_order_id
			LEFT JOIN shipengine_shipping_rates_master  ON shipengine_shipping_rates_master.tracking_number=fe_webhook_master.fe_tracker_number
			WHERE `store_order_items_master`.store_orders_master_id = "'.$store_orders_master_id.'"
			';/* Task 67 add personalization_name*/

			$var_data = parent::selectTable_f_mdl($sql);

			$list_data[0]['var_data'] = $var_data;
			return $list_data;
		}else{
			header('location:index.php');
		}
	}

	function get_total_price( $orders_master_id , $total_price) {
		parent::updateTotalPrice_f_mdl($orders_master_id,$total_price);
	}
	
	public function getRequestedInfo($requestCurrent_product,$sortlist_info){
		$respArray = parent::getRequestedInfo_f_mdl($requestCurrent_product);
		
		if(!empty($respArray)){
			// is store has platinum fulfilment, then no need to show sorting list
			$divHtml = '';
			if($respArray[0]['store_fulfillment_type']!='SHIP_EACH_FAMILY_HOME'){
				$store_master_id = $respArray[0]['store_master_id'];
				$divHtml = parent::getSortListNameInfo_f_mdl($store_master_id,$sortlist_info);
			}
			$res['SUCCESS'] = 'TRUE';
			$res['divHtml'] = $divHtml;
			$res['sale_type'] = $respArray[0]['sale_type'];;
			$res['store_fulfillment_type'] = $respArray[0]['store_fulfillment_type'];
		}else{
			$res['SUCCESS'] = 'FALSE';
		}
		
		return $res;
	}
	
	public function get_all_variants_by_product(){
		$is_empty = 1;

		if(!empty(parent::getVal("method")) && parent::getVal("method") == "get_all_variants_by_product"){

			/*Task 61 start 01/11/2021*/
			// $part = explode("/", $_POST['variant_title']);
			// $num = (count($part) - 1);


			// $variant_size = implode('/', explode('/', $_POST['variant_title'], -1));
			// $variant_color = $part[$num];

			/* Task 61 start 01/11/2021*/
			$array =  explode(" / ", $_POST['variant_title'], 2);
			$variant_color     = $array[1];
			$variant_size      = $array[0];
			
			/* Task 61 end 01/11/2021*/
			$getcolorcodesql='SELECT product_color_name,product_color FROM store_product_colors_master WHERE product_color_name="'.$variant_color.'" AND status="1" ';
			$getcolorcode_data = parent::selectTable_f_mdl($getcolorcodesql);
			$procolor_code='';
			if(!empty($getcolorcode_data)){
				$procolor_code=$getcolorcode_data[0]['product_color'];
			}

			$sql = 'SELECT color ,store_product_colors_master.product_color_name
				FROM `store_owner_product_variant_master`
				LEFT JOIN store_product_colors_master ON store_product_colors_master.product_color = `store_owner_product_variant_master`.color
				WHERE store_owner_product_variant_master.store_owner_product_master_id = "'.$_POST['store_owner_product_master_id'].'" AND store_product_colors_master.status="1" GROUP BY color
			';$colour_data = parent::selectTable_f_mdl($sql);
			
			
			
			$sql = 'SELECT id, store_product_variant_master_id, color, size, image, original_image, sku
				FROM `store_owner_product_variant_master`
				WHERE store_owner_product_master_id = "'.$_POST['store_owner_product_master_id'].'" AND color="'.$procolor_code.'" GROUP BY size
			';
			
			$size_data = parent::selectTable_f_mdl($sql);
			
			$sql = 'SELECT store_master_id,title,quantity,shop_variant_id 
				FROM `store_order_items_master`
				WHERE id = "'.$_POST['store_order_items_master_id'].'"
			';
			$order_data = parent::selectTable_f_mdl($sql);

			$sql = 'SELECT soim.id,soim.product_title,sm.store_name,sm.product_name_identifier
				FROM `store_owner_product_master` as soim INNER JOIN store_master as sm ON soim.store_master_id=sm.id
				WHERE soim.store_master_id = "'.$order_data[0]['store_master_id'].'" AND soim.is_soft_deleted="0"
			';
			$pro_data = parent::selectTable_f_mdl($sql);
			
			$divHtml = '';
			if(isset($colour_data) && !empty($colour_data) && isset($size_data) && !empty($size_data)){
				$divHtml .= '<div class="row">';
				$divHtml .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
				
				$sizeHtml = '<option value="">--Please choose size--</option>';
				$colorHtml = '<option value="">--Please choose color--</option>';
				$productHtml = '<option value="">--Please choose title--</option>';
				
				foreach($colour_data as $colour) {
					if(trim($colour['product_color_name']) == trim($variant_color)) { 
						$select_colour_attribute = 'selected'; 
					}
					else{
						$select_colour_attribute = '';
					}
							
					$colorHtml.= '<option data-color_code="'.$colour['color'].'" '.$select_colour_attribute.' value ="'.$colour['product_color_name'].'">'.$colour['product_color_name'].'</option>';
				}
				
				foreach($size_data as $size) {
					if(trim($size['size']) == trim($variant_size)) { 
						$select_size_attribute = 'selected'; 
					}
					else{
						$select_size_attribute = '';
					}
					
					$sizeHtml.= '<option '.$select_size_attribute.' value ="'.$size['size'].'">'.$size['size'].'</option>';
				}

				foreach($pro_data as $product) {
					if($product['product_name_identifier']."-".$product['product_title'] == $order_data[0]['title']) { 
						$select_title_attribute = 'selected'; 
					}
					else{
						$select_title_attribute = '';
					}
					
					$productHtml.= '<option '.$select_title_attribute.' value ="'.$product['product_name_identifier']."-".$product['product_title'].'" data-sopm_id="'.$product['id'].'">'.$product['product_name_identifier'].'-'.$product['product_title'].'</option>';
				}
				
				$divHtml .= '<input type="hidden" name ="store_order_items_master_id" class="form-control" id="store_order_items_master_id" value="'.$_POST['store_order_items_master_id'].'"/>';

				$divHtml .= '<input type="hidden" name ="prod_edit_store_owner_product_master_id" class="form-control" id="prod_edit_store_owner_product_master_id" value="'.$_POST['store_owner_product_master_id'].'"/>';
				
				
				$divHtml .= '<div class="form-row">';

				// $divHtml .= '<div class="form-group col-md-6">
				// 				<label>Title</label>
				// 				<input type="text" class="form-control" id="new_title" value="'.$order_data[0]['title'].'" required/>';
				// $divHtml .= '</div>';

				$divHtml .= '<div class="form-group col-md-6">
								<label>Select Title</label>
								<select name="select_title" id="select_title" class="form-control" required>';
								
								$divHtml .= $productHtml;
								
								$divHtml .= '</select>';
				$divHtml .= '</div>';

				$divHtml .= '<div class="form-group col-md-6">
								<label>Select Color</label>
								<select name="select_color" id="select_color" value="select_color" class="form-control" required>';
								
								$divHtml .= $colorHtml;
								
								$divHtml .= '</select>';
				$divHtml .= '</div>';

				$divHtml .= '<div class="form-group col-md-6">
								<label>Select Size</label>
								<select name="select_size" id="select_size" value="select_size" class="form-control" required>';
								
								$divHtml .= $sizeHtml;
								
								$divHtml .= '</select>';
				$divHtml .= '</div>';

				$divHtml .= '<div class="form-group col-md-6">
								<label>Qty</label>
								<input type="number" class="form-control" id="new_qty" min="1" value="'.$order_data[0]['quantity'].'" required/>
								<input type="hidden" class="form-control" id="shop_variant_id" value="'.$order_data[0]['shop_variant_id'].'"/>';
				$divHtml .= '</div>';
				
				$divHtml .= '</div>';
				
				$divHtml .= '</div>';
				$divHtml .= '</div>';
				$is_empty = 0;
			}else{
				$divHtml .= '<div style="text-align: center"> <h1> No data available or missing master information </h1> </div>';
				$is_empty = 1;
			}

			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = '';
			$res['divHtml'] = $divHtml;
			$res['is_empty'] = $is_empty;
			
			common::sendJson($res);
		}
	}

	public function get_student_sort_list_info(){
		if(!empty(parent::getVal("method")) && parent::getVal("method") == "get_student_sort_list_info"){
			
			$store_master_id = $_POST['store_master_id'];
			$sortlist_info = $_POST['sort_list_info'];
			$student_name = $_POST['student_name'];

			$respArray = parent::getSortListNameInfo_f_mdl($store_master_id,$sortlist_info);
				
			$divHtml = '';
			if(isset($respArray) && !empty($respArray)){
				$divHtml .= '<div class="row">';
				$divHtml .= '<div class="form-row col-md-12">';
				$divHtml .= '<div class="form-group col-md-6">
								<label>Select Sort List Name</label>
								<select name="select_sort_list" id="select_sort_list" value="select_sort_list" class="form-control">';
								
								$divHtml .= $respArray;
								
								$divHtml .= '</select>';
				$divHtml .= '</div>';
				$divHtml .= '<div class="form-group col-md-6">
								<label>Student Name</label>
								<input type="text" name ="student_name" class="form-control" id="student_name" value="'.$_POST['student_name'].'"/>';
				$divHtml .= '</div>';
				$divHtml .= '</div>';
				
				$divHtml .= '</div>';
				$divHtml .= '</div>';
			}else{
				$divHtml .= '<div style="text-align: center">No data available.</div>';
			}

			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = '';
			$res['divHtml'] = $divHtml;
			
			common::sendJson($res);
		}
	}

	function getStoreVariant(){
		if(!empty(parent::getVal("action")) && parent::getVal("action") == "get_all_variants_by_product"){
			$s3Obj = new Aws3;
			$store_master_id = parent::getVal("store_master_id");
			$order_id = parent::getVal("order_id");

			$sql = 'SELECT store_sale_type_master_id
					FROM store_master
					WHERE id = "'.$store_master_id.'"';
		
			$store_data = parent::selectTable_f_mdl($sql);

			$sql = 'SELECT
					sopm.id as store_owner_product_master_id,
					sopvm.id as store_product_variant_master_id,
					sopvm.shop_product_id,
					sopvm.shop_variant_id,
					sopm.product_title,
					sopvm.price,
					sopvm.price_on_demand,
					sopvm.sku,
					svm.vendor_name,
					sopvm.color,
					sopvm.size,
					sopvm.image,
					spcm.product_color_name
				
				FROM store_owner_product_variant_master  as sopvm
				
				INNER JOIN store_owner_product_master as sopm
				ON sopvm.store_owner_product_master_id = sopm.id
				
				INNER JOIN store_product_master as spm
				ON sopm.store_product_master_id = spm.id
				
				INNER JOIN store_vendors_master as svm
				ON svm.id = spm.vendor_id
				
				INNER JOIN store_product_colors_master as spcm
				ON spcm.product_color = sopvm.color
				
				WHERE sopm.store_master_id = "'.$store_master_id.'"';
		
			$variant_list = parent::selectTable_f_mdl($sql);

			if(!empty($variant_list)){
				$htmlBody = '';

				$htmlBody .= '<table class="table"><tbody><tr><th>#</th><th>Image</th><th>Color</th><th>Size</th><th>SKU</th><th>Price</th></tr>';

				foreach($variant_list as $objData){
					$pro_price = 0;
					if(isset($store_data[0]['store_sale_type_master_id']) && $store_data[0]['store_sale_type_master_id']==2){
						// for on-depand
						$pro_price = $objData['price_on_demand'];
					}else{
						// for flash
						$pro_price = $objData['price'];
					}

					$htmlBody .= '<tr id="var_div_'.$objData['store_product_variant_master_id'].'">';

					$htmlBody .= '<td><input type="checkbox" class="choose-pro" value="'.$objData['store_product_variant_master_id'].'"></td>';

					// $htmlBody .= '<td class="text-center"><img id="varedit_image_18569" src="https://spirithero.aibitz.com/image_uploads/'.$objData['image'].'" style="width: 100px;"></td>';
					$htmlBody .= '<td class="text-center"><img id="varedit_image_18569" src="'.$s3Obj->getAwsUrl('/image_uploads/'.$objData['image']).'" style="width: 100px;"></td>';

					$htmlBody .= '<td><span>'.$objData['product_color_name'].'</span></td>';

					$htmlBody .= '<td><input type="hidden" class="form-control">'.$objData['size'].'</td>';

					$htmlBody .= '<td><input type="hidden" class="form-control">'.$objData['sku'].'</td>';

					$htmlBody .= '<td><span class="flase_sale_price" style="white-space: nowrap;"><span>$'.number_format($pro_price,'2','.','').'</span></span>';

					$htmlBody .= '</td>';
					$htmlBody .= '</tr>';
				}
				$htmlBody .= '</table>';

				$res['SUCCESS'] = true;
				$res['MESSAGE'] = '';
				$res['htmlBody'] = $htmlBody;
			}else{
				$res['SUCCESS'] = false;
				$res['MESSAGE'] = 'No records found';
			}
			common::sendJson($res);
		}
	}

	function add_product_into_order(){
		if(!empty(parent::getVal("action")) && parent::getVal("action") == "add_product_into_order"){
			$store_master_id = parent::getVal("store_master_id");
			$order_id = parent::getVal("order_id");
			$variant_id_string = parent::getVal("variant_id_string");

			$sql = 'SELECT store_sale_type_master_id
					FROM store_master
					WHERE id = "'.$store_master_id.'"';
		
			$store_data = parent::selectTable_f_mdl($sql);

			$sql = 'SELECT
					sopm.id as store_owner_product_master_id,
					sopvm.id as store_product_variant_master_id,
					sopvm.shop_product_id,
					sopvm.shop_variant_id,
					sopm.product_title,
					sopvm.price,
					sopvm.price_on_demand,
					sopvm.sku,
					svm.vendor_name,
					sopvm.color,
					sopvm.size,
					sopvm.image,
					spcm.product_color_name
				
				FROM store_owner_product_variant_master  as sopvm
				
				INNER JOIN store_owner_product_master as sopm
				ON sopvm.store_owner_product_master_id = sopm.id
				
				INNER JOIN store_product_master as spm
				ON sopm.store_product_master_id = spm.id
				
				INNER JOIN store_vendors_master as svm
				ON svm.id = spm.vendor_id
				
				INNER JOIN store_product_colors_master as spcm
				ON spcm.product_color = sopvm.color
				
				WHERE sopm.store_master_id = "'.$store_master_id.'" AND sopvm.id IN ('.$variant_id_string.')';
		
			$variant_list = parent::selectTable_f_mdl($sql);

			if(!empty($variant_list)){
				foreach($variant_list as $objData){
					$pro_price = 0;
					if(isset($store_data[0]['store_sale_type_master_id']) && $store_data[0]['store_sale_type_master_id']==2){
						// for on-depand
						$pro_price = $objData['price_on_demand'];
					}else{
						// for flash
						$pro_price = $objData['price'];
					}

					$soim_insert_data = [
						'store_master_id' => $store_master_id,
						'store_owner_product_master_id' => $objData['store_owner_product_master_id'],
						'store_owner_product_variant_master_id' => $objData['store_product_variant_master_id'],
						'store_orders_master_id' => $order_id,
						'shop_product_id' => $objData['shop_product_id'],
						'shop_variant_id' => $objData['shop_variant_id'],
						'title' => $objData['product_title'],
						'quantity' => 1,
						'price' => floatval($pro_price),
						'sku' => $objData['sku'],
						'vendor' => $objData['vendor_name'],
						'variant_title' => $objData['size'].' / '.$objData['product_color_name'],
						
						'created_on' => @date('Y-m-d H:i:s'),
						'created_on_ts' => time(),
					];
					parent::insertTable_f_mdl('store_order_items_master',$soim_insert_data);
				}

				$res['SUCCESS'] = 'TRUE';
				$res['MESSAGE'] = 'Product added successfully';
			}else{
				$res['SUCCESS'] = 'FALSE';
				$res['MESSAGE'] = 'No records found';
			}
			common::sendJson($res);
		}
	}

	function delete_order_product(){
		if(!empty(parent::getVal("method")) && parent::getVal("method") == "delete_order_product"){
			$store_master_id = parent::getVal("store_master_id");
			$order_id = parent::getVal("order_id");
			$store_order_items_master_id = parent::getVal("store_order_items_master_id");

			#parent::deleteTable_f_mdl('store_order_items_master','id ='.$store_order_items_master_id.' AND store_master_id = '.$store_master_id.' AND store_orders_master_id = '.$order_id);
			

			$res = parent::updateOrderslineItems_f_mdl($store_order_items_master_id);

			$sql = 'SELECT soim.id,soim.quantity,soim.fundraising_amount,soim.price,som.total_fundraising_amount,som.total_price FROM store_order_items_master as soim INNER JOIN store_orders_master as som ON soim.store_orders_master_id= som.id WHERE soim.id  ="'.$store_order_items_master_id.'"';
			$sql_orderdata= parent::selectTable_f_mdl($sql);
			if(!empty($sql_orderdata)){
				$quantity=$sql_orderdata[0]['quantity'];
				$fundraising_amount=$sql_orderdata[0]['fundraising_amount'];
				$price=$sql_orderdata[0]['price'];
				$total_fundraising_amount=$sql_orderdata[0]['total_fundraising_amount'];
				$total_price=$sql_orderdata[0]['total_price'];
			}
			$newtotalFund=$total_fundraising_amount-$fundraising_amount;
			$newtotalprice=$total_price-($price*$quantity);

			$om_update_data = [
				'total_fundraising_amount' => $newtotalFund,
				'total_price' => $newtotalprice
			];
			parent::updateTable_f_mdl('store_orders_master',$om_update_data,'id="'.$order_id.'"');

			if( $res['isSuccess'] ) {
				$res['SUCCESS'] = true;
				$res['MESSAGE'] = 'Product delete successfully';

				$order_items_master_id = $store_order_items_master_id;
				$orders_master_id = $order_id;
				$user_action = 'Deleted';
				$previous_status = 'Try to Delete line item';
				$new_status = 'Successful deleted line item';
				$this->setNoticesLogs($order_items_master_id,$orders_master_id,$user_action,$previous_status,$new_status);

			}else {
				$order_items_master_id = $store_order_items_master_id;
				$orders_master_id = $order_id;
				$user_action = 'Deleted';
				$previous_status = 'Try to Delete line item';
				$new_status = 'Fail deleted process';
				$this->setNoticesLogs($order_items_master_id,$orders_master_id,$user_action,$previous_status,$new_status);

				$res['SUCCESS'] =  false;
				$res['MESSAGE'] = 'Sorry for technical problem, Please try sometime';
			}

			

			common::sendJson($res);
		}
	}

	/**
	 * set log details by admin
	 * 
	 * @param  mixed $store_master_id
	 * @return void
	 */
	protected function setNoticesLogs($order_items_master_id,$orders_master_id,$user_action,$previous_status,$new_status){
		global $login_user_email;
		$soim_insert_data = [
			'order_items_master_id' => $order_items_master_id,
			'orders_master_id' => $orders_master_id,
			'user_action' => $user_action,
			'previous_status' => $previous_status,			
			'new_status' => $new_status,
			'created_at' => @date('Y-m-d H:i:s'),
			"updated_by" => "Super Admin <br>(".$login_user_email.")",
		];
		
		parent::insertTable_f_mdl('order_warning_notes',$soim_insert_data);
	}

	/**
	 * get_notices_details
	 * Task 45 get store details
	 * @param  mixed $store_master_id
	 * @return void
	 */
	function get_notices_details($store_master_id=0)
	{
		$sql = 'select n.user_action, n.previous_status, n.new_status, oitem.title, oitem.variant_title, n.created_at,n.updated_by from order_warning_notes as n left join store_order_items_master as oitem on oitem.id = n.order_items_master_id where n.orders_master_id =  "'.$store_master_id.'"';
		return parent::selectTable_f_mdl($sql);
	}

	/**
	 * get_store_owner_product_master_id
	 * Task 67 add new function
	 * @param  mixed $store_master_id
	 * @return void
	 */
	function get_store_owner_product_master_id($store_owner_product_master_id=0)
	{
		$sql = 'SELECT store_product_master_id  from store_owner_product_master WHERE id  =  "'.$store_owner_product_master_id.'"';
		return parent::selectTable_f_mdl($sql);
	}

	/**
	 * 
	 * 
	 * get_store_details
	 * Task 45 get store details
	 * @param  mixed $store_master_id
	 * @return void
	 */
	function get_store_details($store_master_id=0)
	{
		$sql = 'SELECT store_sale_type_master_id
		FROM store_master
		WHERE id = "'.$store_master_id.'"';
		return parent::selectTable_f_mdl($sql);
	}

	/**
	 * is check variation is exist the order item table if not exist the order item item and then insert the variation
	 * Task 45 get store details
	 * @param  mixed $store_master_id
	 * @return void
	 */
	function is_check_variant_exist_and_insert_if_not( $store_master_id = 0,$order_number = 0,$variant_id = 0 , $order_data = '') {

		if( $store_master_id > 0 && $order_number > 0 && $variant_id > 0) {
			$order_arr = json_decode($order_data[0]['json_data'],1);
			$items = $order_arr['line_items'];
			$varStoreName = 'spirithero1.myshopify.com';

			$getExistingVariantImage = array_filter($items, function($item) use ($variant_id){
				if($item['variant_id'] == $variant_id){
					return $item;
				}
			});

			if( isset( $getExistingVariantImage )  && $getExistingVariantImage != '' ) {
				$sql = 'Select * from store_order_items_master where store_master_id = "'.$store_master_id.'" and store_orders_master_id = "'.$order_number.'" and shop_variant_id = "'.$variant_id.'" ';
				$query = parent::selectTable_f_mdl($sql);
	
				if(empty($query)) {
					
					foreach($getExistingVariantImage as $single_item ) {
						$productTags = $this->getProductTags($varStoreName,$single_item['product_id']);

						$store_var_data = $this->getProductMasterID($single_item['product_id'],$single_item['variant_id']);

						$product_master_id = (isset($store_var_data[0]['store_owner_product_master_id']) && $store_var_data[0]['store_owner_product_master_id'] != '' ) ? $store_var_data[0]['store_owner_product_master_id'] : '';
						$product_variant_master_id = (isset($store_var_data[0]['id']) && $store_var_data[0]['id'] != '' ) ? $store_var_data[0]['id'] : '';

						$soim_insert_data = [
							'store_master_id' => $store_master_id,
							'store_owner_product_master_id' => $product_master_id,
							'store_owner_product_variant_master_id' => $product_variant_master_id,
							'store_orders_master_id' => $order_number,
							'shop_product_id' => $single_item['product_id'],
							'shop_variant_id' => $single_item['variant_id'],
							'title' => $single_item['title'],
							'quantity' => $single_item['quantity'],
							'price' => $single_item['price'],
							'sku' => $single_item['sku'],
							'vendor' => $single_item['vendor'],
							'variant_title' => $single_item['variant_title'],
							'tags' => $productTags,
							'created_on' => @date('Y-m-d H:i:s'),
							'created_on_ts' => time(),
						];
						
						parent::insertTable_f_mdl('store_order_items_master',$soim_insert_data);
					}			

				}
			}		
			
		}
	}

	public function getProductTags($storeName,$productId){
        require_once('lib/shopify.php');
		$productTags = '';
		try {

			$sql = "SELECT id, shop_name, token FROM `shop_management` WHERE shop_name = 'spirithero1.myshopify.com' LIMIT 1";
			$shop_data = parent::selectTable_f_mdl($sql);

			$shop_id = isset( $shop_data[0]['id'] ) ? $shop_data[0]['id'] : 0;
			$shop    = isset( $shop_data[0]['shop_name'] ) ? $shop_data[0]['shop_name'] : 'spirithero1.myshopify.com';
			$token   = isset( $shop_data[0]['token'] ) ? $shop_data[0]['shop_name'] : '';
			
			if( $shop_id > 0 && $token != '' ) {
				$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);		
		
           		$productJson = $sc->call('GET', '/admin/api/2023-04/products/'.$productId.'.json?fields=tags');	

            	$productTags = $productJson['tags'];
			}
		} catch (ShopifyApiException $e){
		} catch (ShopifyCurlException $e) {
        }
        
        return $productTags;
    }

	public function getProductMasterID($product_id, $variant_id) {
		$sql='
				SELECT id, store_owner_product_master_id, fundraising_price FROM `store_owner_product_variant_master`
				WHERE shop_product_id = "'.$product_id.'"
				AND shop_variant_id = "'.$variant_id.'"
				LIMIT 1
			';
        return $store_var_data = parent::selectTable_f_mdl($sql);
	}

	public function isCheckProductMasterID($product_id) {
		$sql='
				SELECT id, store_owner_product_master_id FROM `store_owner_product_variant_master`
				WHERE store_owner_product_master_id = "'.$product_id.'" LIMIT 1
			';
        return $store_var_data = parent::selectTable_f_mdl($sql);die();
	}

	/* Task 102 start */
	function updatePersonalizationName()
	{	
		if(!empty(parent::getVal("method")) && parent::getVal("method") == "update_personalization_name"){
			$resultArray = array();
			$personalization_name       = (!empty(parent::getVal('personalization_name')))?parent::getVal('personalization_name'):'';
			$personalization_item_name  = (!empty(parent::getVal('personalization_item_name')))?parent::getVal('personalization_item_name'):'';
    		$store_order_item_master_id = parent::getVal('store_order_item_master_id');

    		$itemInfo = [
    			'personalization_name' => $personalization_name,
    			'personalization_item_name' => $personalization_item_name
    		];
			$updateinfo = parent::updateTable_f_mdl('store_order_items_master',$itemInfo,'id="'.$store_order_item_master_id.'"');
			if ($updateinfo['isSuccess'] == "1") {
				$resultArray["isSuccess"] = "1";
				$resultArray["msg"]       = "Changes saved successfully.";
			}
			else{
				$resultArray["isSuccess"] = "0";
				$resultArray["msg"]       = "Oops! you haven't update anything.";
			}
			echo json_encode($resultArray);die();
		}
	}
	/* Task 102 end */
	
	public function get_product_varient_data(){
		
		if(!empty(parent::getVal("method")) && parent::getVal("method") == "get_product_varient_data"){

			$sopm_id=parent::getVal("sopm_id");
			$prod_var_title=parent::getVal("prod_var_title");
			$variant_color     = '';
			$variant_size      = '';
			
			$sql = 'SELECT color ,store_product_colors_master.product_color_name
				FROM `store_owner_product_variant_master`
				LEFT JOIN store_product_colors_master ON store_product_colors_master.product_color = `store_owner_product_variant_master`.color
				WHERE store_owner_product_master_id = "'.$sopm_id.'" GROUP BY color
			';
			$colour_data = parent::selectTable_f_mdl($sql);
			
			$sql = 'SELECT id, store_product_variant_master_id, color, size, image, original_image, sku
				FROM `store_owner_product_variant_master`
				WHERE store_owner_product_master_id = "'.$sopm_id.'" GROUP BY size
			';
			$size_data = parent::selectTable_f_mdl($sql);
			
			$colorHtml = '';
			$sizeHtml = '';
			if(isset($colour_data) && !empty($colour_data) && isset($size_data) && !empty($size_data)){
				
				$sizeHtml = '<option value="">--Please choose size--</option>';
				$colorHtml = '<option value="">--Please choose color--</option>';
				
				foreach($colour_data as $colour) {
					if(trim($colour['product_color_name']) == trim($variant_color)) { 
						$select_colour_attribute = 'selected'; 
					}
					else{
						$select_colour_attribute = '';
					}
							
					$colorHtml.= '<option data-color_code="'.$colour['color'].'" '.$select_colour_attribute.' value ="'.$colour['product_color_name'].'">'.$colour['product_color_name'].'</option>';
				}
				
				foreach($size_data as $size) {
					if(trim($size['size']) == trim($variant_size)) { 
						$select_size_attribute = 'selected'; 
					}
					else{
						$select_size_attribute = '';
					}
					
					$sizeHtml.= '<option '.$select_size_attribute.' value ="'.$size['size'].'">'.$size['size'].'</option>';
				}

			}

			$res['SUCCESS'] = 'TRUE';
			$res['MESSAGE'] = '';
			$res['sizeHtml'] = $sizeHtml;
			$res['colorHtml'] = $colorHtml;
			
			common::sendJson($res);
		}
	}

	public function get_product_varient_size(){
		
		if(!empty(parent::getVal("method")) && parent::getVal("method") == "get_product_varient_size"){

			$sopm_id=parent::getVal("sopm_id");
			$prod_var_color=parent::getVal("prod_var_color");
			$prod_var_title=parent::getVal("prod_var_title");
			$select_size=parent::getVal("select_size");
			$variant_color     = '';
			$variant_size      = '';
			
			$sql = 'SELECT product_color ,product_color_name FROM store_product_colors_master WHERE  product_color_name= "'.$prod_var_color.'" GROUP BY product_color_name
			';
			
			$colour_data = parent::selectTable_f_mdl($sql);
			$product_color='';
			if(!empty($colour_data)){
				$product_color=$colour_data[0]['product_color'];
			}
			
			
			$sql = 'SELECT size FROM `store_owner_product_variant_master`
				WHERE store_owner_product_master_id = "'.$sopm_id.'" AND color="'.$product_color.'" GROUP BY size
			';
			$size_data = parent::selectTable_f_mdl($sql);
			
			$sizeHtml = '';
			$sizeHtml = '<option value="">--Please choose size--</option>';
			if(isset($size_data) && !empty($size_data)){

				foreach($size_data as $size) {
					if(trim($size['size']) == trim($select_size)) { 
						$select_size_attribute = 'selected'; 
					}
					else{
						$select_size_attribute = '';
					}
					
					$sizeHtml.= '<option '.$select_size_attribute.' value ="'.$size['size'].'">'.$size['size'].'</option>';
				}

			}
			if(!empty($sizeHtml)){

				$res['SUCCESS'] = 'TRUE';
				$res['MESSAGE'] = '';
				$res['sizeHtml'] = $sizeHtml;
			}else{
				$res['SUCCESS'] = 'FALSE';
				$res['MESSAGE'] = 'Sizes not available';
				$res['sizeHtml'] = $sizeHtml;
			}
			
			common::sendJson($res);
		}
	}

	public function get_product_varient_color(){
		
		if(!empty(parent::getVal("method")) && parent::getVal("method") == "get_product_varient_color"){

			$sopm_id=parent::getVal("sopm_id");
			$prod_var_size=parent::getVal("prod_var_size");
			$prod_var_title=parent::getVal("prod_var_title");
			$variant_color     = '';
			$variant_size      = '';
			
			$sql = 'SELECT color ,store_product_colors_master.product_color_name
				FROM `store_owner_product_variant_master`
				LEFT JOIN store_product_colors_master ON store_product_colors_master.product_color = `store_owner_product_variant_master`.color
				WHERE store_owner_product_master_id = "'.$sopm_id.'" AND  store_owner_product_variant_master.size="'.$prod_var_size.'" GROUP BY color
			';
			$colour_data = parent::selectTable_f_mdl($sql);

			
			$colorHtml = '';
			$colorHtml = '<option value="">--Please choose color--</option>';
				
			foreach($colour_data as $colour) {
				if(trim($colour['product_color_name']) == trim($variant_color)) { 
					$select_colour_attribute = 'selected'; 
				}
				else{
					$select_colour_attribute = '';
				}
						
				$colorHtml.= '<option data-color_code="'.$colour['color'].'" '.$select_colour_attribute.' value ="'.$colour['product_color_name'].'">'.$colour['product_color_name'].'</option>';
			}
			if(!empty($colorHtml)){

				$res['SUCCESS'] = 'TRUE';
				$res['MESSAGE'] = '';
				$res['colorHtml'] = $colorHtml;
			}else{
				$res['SUCCESS'] = 'FALSE';
				$res['MESSAGE'] = 'Coloes not available';
				$res['colorHtml'] = $colorHtml;
			}
			
			common::sendJson($res);
		}
	}

	function update_order_cust_email(){
		if(!empty(parent::getVal("action")) && parent::getVal("action") == "update_order_cust_email"){
			$customer_email = parent::getVal("customer_email");
			$order_id = parent::getVal("order_id");
			$resultArray = array();

			$orderemailInfo = [
    			'cust_email' => $customer_email
    		];
			$updateinfo = parent::updateTable_f_mdl('store_orders_master',$orderemailInfo,'id="'.$order_id.'"');
			$resultArray["isSuccess"] = "1";
			$resultArray["msg"]       = "Changes saved successfully.";
			// echo json_encode($resultArray);die();
			common::sendJson($resultArray);die;
		}
	}

	function update_order_cust_name(){
		if(!empty(parent::getVal("action")) && parent::getVal("action") == "update_order_cust_name"){
			$customer_name = parent::getVal("customer_name");
			$order_id = parent::getVal("order_id");
			$resultArray = array();

			$orderemailInfo = [
    			'cust_name' => $customer_name
    		];
			$updateinfo = parent::updateTable_f_mdl('store_orders_master',$orderemailInfo,'id="'.$order_id.'"');
			$resultArray["isSuccess"] = "1";
			$resultArray["msg"]       = "Changes saved successfully.";
			// echo json_encode($resultArray);die();
			common::sendJson($resultArray);die;
		}
	}

	public function commonMockups($sopvmid)
	{
		$sql = "SELECT image FROM `store_logo_mockups_master` WHERE (image!='' or image IS NOT NULL) and store_owner_product_variant_master_id =".$sopvmid." ";
		return $data =  parent::selectTable_f_mdl($sql);
	}

	public function mark_as_manually_orderd(){
		global $login_user_email;
		if(!empty(parent::getVal("action")) && parent::getVal("action") == "mark_as_manually_orderd"){
			$customer_name = parent::getVal("customer_name");
			$store_order_items_master_id = parent::getVal("store_order_items_master_id");
			$resultArray = array();

			$orderitemsData = [
    			'mark_as_manually_ordered_vendor' => '1',
    			"mark_as_manually_ordered_by" => $login_user_email
    		];
			$updateinfo = parent::updateTable_f_mdl('store_order_items_master',$orderitemsData,'id="'.$store_order_items_master_id.'"');
			$resultArray["isSuccess"] = "1";
			$resultArray["msg"]       = "Status updated successfully.";
			// echo json_encode($resultArray);die();
			common::sendJson($resultArray);die;
		}
	}

	public function mark_as_manually_orderd_bulk(){
		global $login_user_email;
		if(!empty(parent::getVal("action")) && parent::getVal("action") == "mark_as_manually_orderd_bulk"){
			$bulk_order_items_ids = parent::getVal("bulk_order_items_ids");
			$deleteItemsIdArray = explode(",",$bulk_order_items_ids);
			$resultArray = array();
			$orderitemsData = [
    			'mark_as_manually_ordered_vendor' => '1',
				'buyStatus' => '1',
    			"mark_as_manually_ordered_by" => $login_user_email
    		];
    		foreach($deleteItemsIdArray as $single_item){
				$updateinfo = parent::updateTable_f_mdl('store_order_items_master',$orderitemsData,'id ="'.$single_item.'"');
			}
			
			$resultArray["isSuccess"] = "1";
			$resultArray["msg"]       = "Status updated successfully.";
			// echo json_encode($resultArray);die();
			common::sendJson($resultArray);die;
		}
	}

	function checkAllOrdermarkasManuallyOrderd(){
		if(!empty(parent::getVal("oid")))
		{	
			$orderId = parent::getVal("oid");
			$sql = "SELECT id,quantity,buyStatus,vendor,is_deleted,mark_as_manually_ordered_vendor FROM `store_order_items_master` WHERE store_orders_master_id ='".$orderId."' AND buyStatus='0' AND is_deleted ='0' ";
			$manuallyOrderData=parent::selectTable_f_mdl($sql);
			return $manuallyOrderData;die();
		}
	}
}