<?php
include_once 'model/sa_stores_mdl.php';
include_once('helpers/createStoreHelper.php');
include_once('helpers/storeHelper.php');
include_once 'model/sa_order_view_mdl.php';
$login_user_email="";
if(isset($_SESSION['user_email']) && $_SESSION['user_email'] != "") {
	$login_user_email=trim($_SESSION['user_email']);
}
class sa_place_orders_ctl extends sa_stores_mdl
{
	public $TempSession = "";

	function __construct()
	{
		if (parent::isGET() || parent::isPOST()) {
			if(parent::getVal("method")){
				$this->checkRequestProcess(parent::getVal("method"));
			}else{
				$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			}
			//$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}

		common::CheckLoginSession();
	}
	
	function checkRequestProcess($requestFor){
        if($requestFor != ""){
            switch($requestFor){
        		case "getDataFromSanmar":
					$this->getDataFromSanmar();
                break;
                case "checkProductInfo":
					$this->checkProductInfo();
                break;
				case "getcheckAvailabity":
					$this->getcheckAvailabity();
                break;
				case "placeOrder":
					$this->getcheckAvailabity();
                break;
				case "getSubstituteSku":
					$this->getSubstituteSku();
                break;
				case "getColorBySku":
					$this->getColorBySku();
                break;
				case "getSizeBySkuColor":
					$this->getSizeBySkuColor();
                break;
				case "removeStoreSanmar":
					$this->removeStoreSanmar();
				break;
				case "bulk_delete_store_sanmar":
					$this->bulk_delete_store_sanmar();
				break;
				case "remove_mask_as_ordered_manually":
					$this->remove_mask_as_ordered_manually();
				break;
				case "remove_mask_as_ordered_bulk":
					$this->remove_mask_as_ordered_bulk();
				break;
          	}
        }
    }
	
	function storePagination()
	{
		
		if (parent::isPOST()) {
			if (parent::getVal("hdn_method") == "store_pagination") {
				$record_count = 0;
				$page = 0;
				$current_page = 1;
				$rows = '10';
				$keyword = '';

				if ((isset($_REQUEST['rows'])) && (!empty($_REQUEST['rows']))) {
					$rows = $_REQUEST['rows'];
				}
				if ((isset($_REQUEST['keyword'])) && (!empty($_REQUEST['keyword']))) {
					$keyword = $_REQUEST['keyword'];
				}
				if ((isset($_REQUEST['current_page'])) && (!empty($_REQUEST['current_page']))) {
					$current_page = $_REQUEST['current_page'];
				}
				$start = ($current_page - 1) * $rows;
				$end = $rows;
				$sort_field = '';
				if (isset($_POST['sort_field']) && !empty($_POST['sort_field'])) {
					$sort_field = $_POST['sort_field'];
				}
				$sort_type = '';
				if (isset($_POST['sort_type']) && !empty($_POST['sort_type'])) {
					$sort_type = $_POST['sort_type'];
				}
				

				$cond_keyword = '';

				$cond_status = '';
				if (isset($_POST['store_status'])) {
					$cond_status = 'AND sm.status = "' . $_POST['store_status'] . '"';
				}

				$cond_order = 'ORDER BY id DESC';
				if (!empty($sort_field)) {
					$cond_order = 'ORDER BY ' . $sort_field . ' ' . $sort_type;
				}
				$sale_type='';
				$store_status="0";
				if((isset($_POST['store_type'])) && (!empty($_POST['store_type']))) {
					$store_type=trim($_POST['store_type']);
					if($store_type=='2'){
						$store_status="1";
					}
				}

				$sql = "SELECT sm.id, sm.store_name, sm.store_close_date,sm.store_sale_type_master_id,
				(SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 AND oim.buyStatus = '0' and oim.store_master_id = sm.id AND om.`order_tags` NOT LIKE '%Return_Prime%') as totalItem_sold,
				(SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0 AND `order_tags` NOT LIKE '%Return_Prime%') as total_order
				FROM `store_orders_master` as som 
				INNER JOIN store_master sm ON som.store_master_id = sm.id
				WHERE sm.status = '".$store_status."' AND som.is_order_cancel = '0' AND som.`order_tags` NOT LIKE '%Return_Prime%' AND sm.store_sale_type_master_id ='".$store_type."' AND som.store_master_id IN (SELECT om.store_master_id FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 AND oim.buyStatus = '0' AND om.`order_tags` NOT LIKE '%Return_Prime%' )
				GROUP BY sm.id ORDER BY som.store_master_id  DESC";
				$all_list = parent::selectTable_f_mdl($sql);
				
				if ((isset($all_count[0]['count'])) && (!empty($all_count[0]['count']))) {
					$record_count = $all_count[0]['count'];
					$page = $record_count / $rows;
					$page = ceil($page);
				}
				$sr_start = 1;
				if ($record_count >= 1) {
					$sr_start = (($current_page - 1) * $rows) + 1;
				}
				$sr_end = ($current_page) * $rows;
				if ($record_count <= $sr_end) {
					$sr_end = $record_count;
				}

				if (isset($_POST['pagination_export']) && $_POST['pagination_export'] == 'Y') {
					//
				} else {
					$html = '';
					$html .= '<div class="row">';
					$html .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
					$html .= '<div class="table-responsive dropdown-active">';// Task 54 19/10/2021 Add new class dropdown-active
					$html .= '<table class="table table-bordered table-hover">';

					$html .= '<thead>';
					$html .= '<tr>';
					$html .= '<th><input type="checkbox" id="ckbCheckAll"></th>';
					$html .= '<th>#</th>';
					$html .= '<th>Stores Name</th>';
					if($_POST['store_type']=='1'){
						$html .= '<th># Orders</th>';
					}
					$html .= '<th># Items</th>';
					if($_POST['store_type']=='1'){
						$html .= '<th class="sort_th" data-sort_field="store_close_date">Store Last Date</th>';
					}
					
					$html .= '<th>Action</th>';
					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					if (!empty($all_list)) {
						$sr = $sr_start;
						foreach ($all_list as $single) {
							$html .= '<tr>';
							$html .= '<td><input type="checkbox" value='.$single["id"].' class="checkBoxClassStore storeIdClass" name="storeId[]"></td>';
							$html .= '<td>' . $sr . '</td>';
							$html .= '<td>' . $single["store_name"] . '</td>';
							if($_POST['store_type']=='1'){
								$html .= '<td>'.$single["total_order"].'</td>';
							}
							$html .= '<td>' . $single["totalItem_sold"] . '</td>';
							if($_POST['store_type']=='1'){
								if (!empty($single['store_close_date'])) {
									$html .= '<td>' . date('m/d/Y h:i A', $single["store_close_date"]) . '</td>';
								} else {
									$html .= '<td></td>';
								}
							}
							
							$html .= '<td><button class="btn btn-danger btn-round btn-sm storeRemoveSanmar" data-id="'.$single["id"].'" data-toggle="tooltip" data-placement="top" data-trigger="hover" data-original-title="Remove Store" title="" aria-describedby="tooltip197422"><i class="fa fa-trash"></i></button></td>';
							$html .= '</tr>';
							$sr++;
						}
					} else {
						$html .= '<tr>';
						if($_POST['store_type']=='1'){
							$html .= '<td colspan="7" align="center">No Record Found</td>';
						}else{
							$html .= '<td colspan="5" align="center">No Record Found</td>';
						}
						$html .= '</tr>';
					}

					$html .= '</tbody>';
					$html .= '</table>';
					$html .= '</div>';
					$html .= '</div>';
					$html .= '</div>';

					
					$addressSql = "SELECT id, ship_to FROM `address_master`  ORDER BY id DESC";
					$addressList = parent::selectTable_f_mdl($addressSql);

					$res['DATA'] = $html;
					$res['address'] = $addressList;
					echo json_encode($res, 1);
					exit;
					die;
				}
			}
			die;
		}
	}

	function fetchStoreTokenInfo()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "fetch-stkn") {
				$masterStoreId = parent::getVal("sid");
				parent::fetchStoreTokenInfo_f_mdl($masterStoreId);
			}
		}
	}

	public function getDataFromSanmar()
	{
		if (parent::isPOST() && parent::getVal("method") == "getDataFromSanmar") {
			$post = $_POST;
		
			if(!empty($post['storeId'])){
				$storeId = implode(',', $post['storeId']);
				$sql = "SELECT IFNULL(SUM(soim.quantity),0) as sold_items, soim.totalBuyProduct, sopvm.sku, soim.store_owner_product_variant_master_id, sopm.product_title, sopvm.color,spcm.product_color_name,spvm.sanmar_size as size
						FROM store_order_items_master as soim
						INNER JOIN store_owner_product_variant_master as sopvm on sopvm.id = soim.store_owner_product_variant_master_id
						INNER JOIN store_owner_product_master as sopm on sopm.id = soim.store_owner_product_master_id
						INNER JOIN store_product_colors_master as spcm ON spcm.product_color = sopvm.color
						INNER JOIN store_product_variant_master as spvm ON spvm.id=sopvm.store_product_variant_master_id
						INNER JOIN store_orders_master as som ON som.store_master_id = soim.store_master_id AND som.id = soim.store_orders_master_id
						WHERE soim.store_master_id IN ($storeId) AND soim.buyStatus = '0' AND soim.is_deleted = 0 AND som.is_order_cancel = '0'
						group by soim.store_owner_product_variant_master_id
						order by soim.store_owner_product_variant_master_id
					   ";
				$all_list = parent::selectTable_f_mdl($sql);

				$store_sql="SELECT id,store_name FROM store_master WHERE id IN ($storeId) ";
				$all_storelist = parent::selectTable_f_mdl($store_sql);

				$order_sql="SELECT DISTINCT sm.id,sm.store_name,sm.store_sale_type_master_id,om.id as ordermasterid,
					CASE
						WHEN om.order_type = 1 THEN om.shop_order_number
						WHEN om.order_type = 2 THEN CONCAT_WS('-', 'MO', om.manual_order_number)
						ELSE CONCAT_WS('-', 'QB', om.quickbuy_order_number)
				END AS shop_order_number FROM store_order_items_master AS soim INNER JOIN store_orders_master AS om ON soim.store_orders_master_id = om.id INNER JOIN store_master AS sm ON sm.id = soim.store_master_id WHERE soim.store_master_id IN ($storeId) AND soim.buyStatus = '0' AND soim.is_deleted = 0 AND om.is_order_cancel = 0 ORDER BY shop_order_number
				";

				$allorderlist = parent::selectTable_f_mdl($order_sql);

				$data = $this->getProdctinfo($all_list);
				
				$stores_names=$storename_history=$storeid_history='';
				if(!empty($all_storelist)){
					foreach ($all_storelist as $storedata) {
						$stores_names .= '<span>'.$storedata['store_name'].'</span>';
						$storename_history .= $storedata['store_name'].',';
						$storeid_history .= $storedata['id'].',';
					}
				}
				$shop_order_number=$shoporder_history=$ordermasterid='';
				if(!empty($allorderlist)){
					foreach ($allorderlist as $orderdata) {
						$shop_order_number .= '<div class="sanmar-order-num-sec">
                                            <input type="checkbox" id="shopordernumber_'.$orderdata['shop_order_number'].'" value="'.$orderdata['ordermasterid'].'" class=" bulkMarkAsOrderd">
                                            <label for="shopordernumber_'.$orderdata['shop_order_number'].'" class="label">#'.$orderdata['shop_order_number'].'</label>
                                        </div>';
						$shoporder_history .= '#'.$orderdata['shop_order_number'].',';
						$ordermasterid .= '#'.$orderdata['ordermasterid'].',';
					}
				}
				$data['store_list'] = $stores_names;
				$data['shop_order_number'] = $shop_order_number;
				$data['storename_history'] = $storename_history;
				$data['storeid_history'] = $storeid_history;
				$data['shoporder_history'] = $shoporder_history;
				$data['store_order_ids'] = $ordermasterid;
				echo json_encode($data, 1);
				die;
			}else{
				return false;
			}
			die;
		}
	}

	public function getcheckAvailabity(){

			$post = $_POST;
			$arr = [];
			$PONumber = $post['po_number'];
			$attention = $post['attention'];
			$selected_storename_json = rtrim($post['selected_storename_json'], ',');
			$selected_ordernumber_json = rtrim($post['selected_ordernumber_json'], ',');
			if(!empty($post['style'])){
				$color_code = '';

				foreach (json_decode($post['style'], true) as $key => $value) {

					$getcolorcodeSql = "SELECT store_product_variant_master_id,spvm.sanmar_color_code,sopvm.sku,sopvm.size,spvm.color,spcm.product_color_name FROM store_owner_product_variant_master as sopvm INNER JOIN  store_product_variant_master as spvm ON spvm.id=sopvm.store_product_variant_master_id AND spvm.sku=sopvm.sku INNER JOIN store_product_colors_master as spcm ON spcm.product_color=spvm.color WHERE  spvm.sanmar_size='".trim(json_decode($post['size'], true)[$key])."' AND sopvm.sku='".$value."' AND  spcm.product_color_name='".trim(json_decode($post['color'], true)[$key])."'";
					$sanmarcolorcode_List = parent::selectTable_f_mdl($getcolorcodeSql);
					if(!empty($sanmarcolorcode_List[0]['sanmar_color_code'])){
						$color_code= $sanmarcolorcode_List[0]['sanmar_color_code'];
					}else{
						$sanmarcodeSql = "SELECT catelog_color FROM sanmar_products_master where style='".$value."' AND size='".trim(json_decode($post['size'], true)[$key])."' AND color_name='".trim(json_decode($post['color'], true)[$key])."' ";
						$sanmarcolorcodeData = parent::selectTable_f_mdl($sanmarcodeSql);
						if(!empty($sanmarcolorcodeData)){
							$color_code= $sanmarcolorcodeData[0]['catelog_color'];
						}
					}

					array_push($arr,[
						//'color' => !empty($color[trim($post['color'])[$key]])?$color[trim($post['color'])[$key]]:trim($post['color'])[$key],
						'errorOccured'=>'',
						'message' =>'',
						'poId'=>'',
						'style' => $value,
						'color' => $color_code,
						'size' =>json_decode($post['size'], true)[$key],
						'quantity' =>json_decode($post['qty'], true)[$key],
						'sizeIndex' => '',
						'inventoryKey' =>''
						//'sizeIndex' => $post['sizeIndex'][$key],
						//'inventoryKey' =>$post['inventoryKey'][$key],
						
					]);
				}
			}

			$sanmarResponse = [];
			$res = [];
			$id = $post['addressShiping'];
			$addressSql = "SELECT * FROM `address_master` WHERE id = '".$id."' ORDER BY id DESC";
			$addressList = parent::selectTable_f_mdl($addressSql);
			if(count($addressList) > 0)	{
				if (parent::isPOST() && parent::getVal("method") == "getcheckAvailabity" && $PONumber != ''){
					$sanmarResponse = [];

					$response = $this->getcheckAvailabitySanmar($arr, $PONumber, $attention, $addressList);

					if(!empty($response->return->response->webServicePoDetailList)){
						if(count($arr)>1){
							$sanmarResponse = $response->return->response->webServicePoDetailList;
						}else{
							$sanmarResponse[0] = $response->return->response->webServicePoDetailList;
						}
						$res['STATUS'] = TRUE;
					}else{
						$sanmarResponse[0] = $response->return->message;
						$res['STATUS'] 		= FALSE;
					}
				}
				elseif(parent::isPOST() && parent::getVal("method") == "placeOrder" && $PONumber != ''){
					$response = $this->submitOrder($arr, $PONumber, $attention, $addressList,$selected_storename_json,$selected_ordernumber_json);
					$sanmarResponse = $response->return->message;
					if(empty($response->return->errorOccurred)){
						$res['STATUS'] = TRUE;
						$this->updateProductStatus($post);
					}else{
						$sanmarResponse  	= $response->return->message;
						$res['STATUS'] 		= FALSE;
					}
				}
			}else{
				$sanmarResponse  			= "Address has been required.";
				$res['STATUS'] 				= FALSE;
			}
				
			$res['sanmarResponse']  = $sanmarResponse;
			echo json_encode($res);die();
	}

	public function getSubstituteSku(){
		$res 	= [];
		$result = [];
		$status = false;
		if (parent::isPOST() && parent::getVal("method") == "getSubstituteSku"){
			$post = $_POST;
			
			$sql = 'SELECT vasm.id, vasm.sku, spm.product_title FROM varient_sku_master as vsm INNER JOIN store_product_master as spm ON vsm.product_id = spm.id INNER JOIN varient_alternate_sku_master as vasm ON vsm.id = vasm.varient_sku_master_id WHERE vsm.sku = "'.$post['style'].'"';
			$data = parent::selectTable_f_mdl($sql);
			if(count($data) > 0){
				$result = $data;
				$status = true;
			}
		}
		$res['result'] = $result;
		$res['status'] = $status;
		echo json_encode($res);die();
	}

	public function getColorBySku(){
		if (parent::isPOST() && parent::getVal("method") == "getColorBySku") {
			$res = [];

			$post = $_POST;
			$color =  [];
			$resultSan = $this->getProductInfoInSanmar($post);
			
			if(empty($resultSan['errorOccured'])){
				$sanmarResponce = [];
				if(!empty($resultSan['listResponse']['productBasicInfo'])){
					$sanmarResponce = [];
				}else{
					if(!empty($resultSan['listResponse'])){
						$sanmarResponce = $resultSan['listResponse']; 
					}	
				}

				if(!empty($sanmarResponce)){
					foreach($sanmarResponce AS $key => $value){
						if(!in_array($value['productBasicInfo']['color'], $color)){
							$color[] = $value['productBasicInfo']['color'];
						}	
					}
				}else{
					if(!empty($sanmarResponce['productBasicInfo']['color'])){
						$color[] = $sanmarResponce['productBasicInfo']['color'];
					}
				}
			}

			$res['result'] = $color;
			$res['status'] = !empty($color)?true:false;
			echo json_encode($res);die();
		}
	}

	public function getSizeBySkuColor(){
		if (parent::isPOST() && parent::getVal("method") == "getSizeBySkuColor") {
			$res = [];

			$post = $_POST;
			$size =  [];
			$resultSan = $this->getProductInfoInSanmar($post);
			
			if(empty($resultSan['errorOccured'])){
				$sanmarResponce = [];
				if(!empty($resultSan['listResponse']['productBasicInfo'])){
					$sanmarResponce = [];
				}else{
					if(!empty($resultSan['listResponse'])){
						$sanmarResponce = $resultSan['listResponse']; 
					}	
				}

				if(!empty($sanmarResponce)){
					foreach($sanmarResponce AS $key => $value){
						if(!in_array($value['productBasicInfo']['size'], $size)){
							$size[] = $value['productBasicInfo']['size'];
						}	
					}
				}else{
					if(!empty($sanmarResponce['productBasicInfo']['size'])){
						$size[] = $sanmarResponce['productBasicInfo']['size'];
					}
				}
			}

			$res['result'] = $size;
			$res['status'] = !empty($size)?true:false;
			echo json_encode($res);die();
		}
	}

	private function getProdctinfo($data){
		$html = '';
		$totalQTY = 0;
		
		$html .= '<div class="row">';
		$html .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
		$html .= '<div class="table-responsive dropdown-active">';// Task 54 19/10/2021 Add new class dropdown-active
		$html .= '<table class="table table-bordered table-hover">';

		$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<th><input type="checkbox" class="product-items-checkbox" id="ckbCheckAllProduct" disabled></th>';
		$html .= '<th>#</th>';
		$html .= '<th>Product Name</th>';
		$html .= '<th>SKU</th>';
		$html .= '<th>Color</th>';
		$html .= '<th>Size</th>';
		$html .= '<th>Quantity Needed</th>';
		$html .= '<th>Quantity To Be Order</th>';
		$html .= '<th>Cost</th>';
		$html .= '<th>Total Cost</th>';
		$html .= '<th>Availability</th>';
		$html .= '<th>Manually Order</th>';

		$html .= '</tr>';
		$html .= '</thead>';

		$html .= '<tbody>';
		if(!empty($data)){
			$sr="1";
			$arr = [];
			$casePrice = '$0';
			$totalPrice = '$0';
			
			foreach ($data as $key => $single) {
				$totalQTY = $totalQTY + $single["sold_items"];
				$color = $single['product_color_name'];
				$vid = $single["store_owner_product_variant_master_id"];
				$html .= '<button style="display:none;" type="button" data-id="'.$vid.'" onclick="checkProductInfo('.$vid.')" id="checkId_'.$vid.'" class="btn btn-info checkProductInfo">Check Info</button>';
				$html .= '<tr id="rowId_'.$single["store_owner_product_variant_master_id"].'">';
				$html .= '<td><input disabled type="checkbox" data-style="'.$single["sku"].'"
								data-inventorykey=""
								data-caseprice=""
								data-substitute="0"
								data-color="'.$single["product_color_name"].'"
								data-sizeindex=""
								data-size="'.$single["size"].'"
								data-qty="'.$single["sold_items"].'"
							class="checkBoxClass vidClass" value="'.$vid.'" id="checkboxId_'.$vid.'" name="vid[]"></td>';
				$html .= '<td>' . $sr. '</td>';
				$html .= '<td>' . $single["product_title"] . '</td>';
				//$html .= '<td id="productSku_'.$vid.'" class="products-sku-substitute-sec">' . $single["sku"] . '</td>';
				$html .= '<td id="productSku_'.$vid.'" class="products-sku-substitute-sec"><input type="text" class="editable-variant" id="subStyle_'.$vid.'" value="'.$single["sku"] .'" onchange="updateDataArribute('.$vid.')" ></td>';
				//$html .= '<td id="productColorName_'.$vid.'">' . $single["product_color_name"] . '</td>';
				$html .= '<td id="productColorName_'.$vid.'"><input type="text" class="editable-variant" id="subColor_'.$vid.'" value="'.$single["product_color_name"].'" onchange="updateDataArribute('.$vid.')"></td>';
				//$html .= '<td id="productSize_'.$vid.'">' . $single["size"] . '</td>';
				$html .= '<td id="productSize_'.$vid.'"><input type="text" class="editable-variant " id="subSize_'.$vid.'" value="'.$single["size"].'" onchange="updateDataArribute('.$vid.')"></td>';
				$html .= '<td>' . $single["sold_items"] . '<input type="hidden" value="'.$single["sold_items"].'" id="sold_items_'.$vid.'" name="sold_items[]"></td>';
				$html .= '<td><input type="number" id="qty_'.$vid.'" onchange="calcuteAmount('.$vid.')" value='.$single["sold_items"].' min="'.$single["sold_items"].'" class="form-control quantity_ordered" name="quantity_ordered[]"></td>';
				$html .= '<td><span id="case_price_'.$vid.'" class="loader-icon">'.$casePrice.'</span></td>';
				$html .= '<td><span id="total_price_'.$vid.'" class="loader-icon priceTotal">'.$totalPrice.'</span></td>';
				$html .= '<td id="available_'.$vid.'"><span class="btn btn-default" id="cheke_available_'.$vid.'"><i class="fa fa-question"></i></span></td>';
				$html .= '<td><input type="checkbox" data-style="'.$single["sku"].'" data-color="'.$single["product_color_name"].'" data-size="'.$single["size"].'" data-qty="'.$single["sold_items"].'"
							class="checkbox_markasorderd" value="'.$vid.'" id="markasorderd_'.$vid.'"></td>';
				$html .= '</tr>';
				$sr++;
			}
		}else {
			$html .= '<tr>';
			$html .= '<td colspan="15" align="center">No Record Found</td>';
			$html .= '</tr>';
		}

		$html .= '</tbody>';
		$html .= '<tfoot>';
		$html .= '<tr>';
		$html .= '<th>Total</th>';
		$html .= '<th></th>';
		$html .= '<th></th>';
		$html .= '<th></th>';
		$html .= '<th></th>';
		$html .= '<th></th>';
		$html .= '<th><span id="selectedQty">0</span> out of '.$totalQTY.'</th>';
		$html .= '<th id="totalProduct">'.$totalQTY.'</th>';
		$html .= '<th></th>';
		$html .= '<th id="priceTotal">$0.00</th>';
		$html .= '<th></th>';
		$html .= '<th></th>';
		
		$html .= '</tr>';
		$html .= '</tfoot>';
		$html .= '</table>';
		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';

		$res['DATA'] = $html;
		return $res;
		exit;
		die;
	}

	private function getSize($size){
		switch (true) {
				
			case strpos($size,'Youth XS') !== false:
				return 'XS';
				break;
			case strpos($size,'Youth S') !== false:
				return 'S';
				break;
			case strpos($size,'Youth M') !== false:
				return 'M';
				break;
			case strpos($size,'Youth L') !== false:
				return 'L';
				break;
			case strpos($size,'Youth XL') !== false:
				return 'XL';
				break;
			case strpos($size,'Adult XS') !== false:
				return 'XS';
				break;
			case strpos($size,'Adult S') !== false:
				return 'S';
				break;
			case strpos($size,'Adult M') !== false:
				return 'M';
				break;
			case strpos($size,'Adult L') !== false:
				return 'L';
				break;
			case strpos($size,'Adult XL') !== false:
				return 'XL';
				break;
			case strpos($size,'Adult 2XL') !== false:
				return '2XL';
				break;
			case strpos($size,'Adult 3XL') !== false:
				return '3XL';
				break;
			case strpos($size,'Adult 4XL') !== false:
				return '4XL';
				break;
			default:
				return $size;
				break;
			
		}		
	}

	public function checkProductInfo()
	{
		if (parent::isPOST() && parent::getVal("method") == "checkProductInfo"){

			$localhostWsdlUrl = common::PRODUCT_INFO;
			
			$client= new SoapClient($localhostWsdlUrl, array(
				'trace'=>true,
				'exceptions'=>true
			));
			
			$webServiceUser =array(
				'sanMarCustomerNumber' => common::sanMarCustomerNumber,
				'sanMarUserName' => common::sanMarUserName,
				'sanMarUserPassword' => common::sanMarUserPassword
			);
			
			// $verient_id= ($_POST['ver_id'])?$_POST['ver_id']:'';
			// $verient_id= str_replace('checkboxId_','',$verient_id);
			$style = ($_POST['style'])?trim($_POST['style']):'';
			$size = '';
			$color_code='';

			$getcolorcodeSql = "SELECT store_product_variant_master_id,spvm.sanmar_color_code,sopvm.sku,sopvm.size,spvm.color,spvm.sanmar_size,spcm.product_color_name FROM store_owner_product_variant_master as sopvm INNER JOIN  store_product_variant_master as spvm ON spvm.id=sopvm.store_product_variant_master_id AND spvm.sku=sopvm.sku INNER JOIN store_product_colors_master as spcm ON spcm.product_color=spvm.color WHERE  spvm.sanmar_size='".trim($_POST['size'])."' AND sopvm.sku='".$style."' AND  spcm.product_color_name='".trim($_POST['color'])."'";
			$sanmarcolorcode_List = parent::selectTable_f_mdl($getcolorcodeSql);
			if(!empty($sanmarcolorcode_List)){
				$color_code= $sanmarcolorcode_List[0]['sanmar_color_code'];
				$size= $sanmarcolorcode_List[0]['sanmar_size'];
			}

			$arr = [
				'style' => trim($style),
				'color' => trim($color_code),
				'size' => trim($size)
			];
			$getProductInfoByStyleColorSize= array('arg0' =>$arr,'arg1' =>$webServiceUser );

			$logFileOpen = fopen("sanmar_logs.txt", "a+") or die("Unable to open file!");
			$errorText = "</br></br></br>";
			$errorText .= "----------------------------------------------------------------------------";
			$errorText .= "Get Product Price SANMAR ".date("m/d/Y h:i A");
			$errorText .= "----------------------------------------------------------------------------";
			$errorText .= "</br></br></br>";
			$errorText = "Price get data = ".print_r($getProductInfoByStyleColorSize, true);
			fwrite($logFileOpen, $errorText);
			unset($errorText);

			$result=$client->__soapCall('getProductInfoByStyleColorSize',array('getProductInfoByStyleColorSize' => $getProductInfoByStyleColorSize) );
			$array = json_decode(json_encode($result), true);
			$errorText = "Price response data = ".print_r($array, true);
			fwrite($logFileOpen, $errorText);
			unset($errorText);
			$casePrice = 0;
			if(!empty($array['return']['listResponse']['productPriceInfo'])){
				if(!empty($array['return']['listResponse']['productPriceInfo']['caseSalePrice'])){
					$casePrice = $array['return']['listResponse']['productPriceInfo']['caseSalePrice'];
				}else{
					$casePrice = $array['return']['listResponse']['productPriceInfo']['casePrice'];
				}
			}else{
				if(!empty($array['return']['listResponse'][0]['productPriceInfo']['caseSalePrice'])){
					$casePrice = $array['return']['listResponse'][0]['productPriceInfo']['caseSalePrice'];
				}else{
					if(!empty($array['return']['listResponse'][0]['productPriceInfo']['casePrice'])){
						$casePrice = $array['return']['listResponse'][0]['productPriceInfo']['casePrice'];
					}
				}	
			}

			$sanmarResponce = [];

			if(!empty($array['return']['listResponse']['productBasicInfo'])){
				$sanmarResponce = $array['return']['listResponse']['productBasicInfo'];
			}else{
				if(!empty($array['return']['listResponse'][0]['productBasicInfo'])){
					$sanmarResponce = $array['return']['listResponse'][0]['productBasicInfo']; 
				}	
			}

			$color = '';
			$inventoryKey = '';
			$sizeIndex = '';
			$size = '';
			$style = '';

			if(!empty($sanmarResponce)){
				$inventoryKey = $sanmarResponce['inventoryKey'];
				$color = $sanmarResponce['color'];
				$sizeIndex = $sanmarResponce['sizeIndex'];
				$size = $sanmarResponce['size'];
				$style = $sanmarResponce['style'];
			}

			$totalPrice= $casePrice*$_POST["qntity"];

			$res = [];
			$res['STATUS'] = "TRUE";
			$res['casePrice']  = $casePrice;
			$res['totalPrice'] = $totalPrice;
			$res['inventorykey']  = $inventoryKey;
			$res['sizeindex'] = $sizeIndex;
			if(isset($array['return']['errorOccured']) && $array['return']['errorOccured']==1){
				$res['STATUS'] = "FALSE";
				$res['casePrice']  = 0;
				$res['totalPrice'] = 0;
			}
			echo json_encode($res);die();
		}
	}

	private function getProductInfoInSanmar($post)
	{
		
		$localhostWsdlUrl = common::PRODUCT_INFO;
		$client= new SoapClient($localhostWsdlUrl, array(
			'trace'=>true,
			'exceptions'=>true
		));
		$webServiceUser = array(
			'sanMarCustomerNumber' => common::sanMarCustomerNumber,
			'sanMarUserName' => common::sanMarUserName,
			'sanMarUserPassword' => common::sanMarUserPassword
		);

		$verient_id= ($post['ver_id'])?$post['ver_id']:'';
		$style = ($post['style'])?trim($post['style']):'';
		$size = '';
		$color_code='';
		$getcolorcodeSql = "SELECT store_product_variant_master_id,spvm.sanmar_color_code,sopvm.sku,sopvm.size,spvm.color,spvm.sanmar_size,spcm.product_color_name FROM store_owner_product_variant_master as sopvm INNER JOIN  store_product_variant_master as spvm ON spvm.id=sopvm.store_product_variant_master_id AND spvm.sku=sopvm.sku INNER JOIN store_product_colors_master as spcm ON spcm.product_color=spvm.color WHERE  sopvm.size='".trim($post['size'])."' AND sopvm.sku='".$style."' AND  spcm.product_color_name='".trim($post['color'])."'";
		$sanmarcolorcode_List = parent::selectTable_f_mdl($getcolorcodeSql);
		
		if(!empty($sanmarcolorcode_List)){
			$color_code= $sanmarcolorcode_List[0]['sanmar_color_code'];
			$size= $sanmarcolorcode_List[0]['sanmar_size'];
		}
		
		$arr = [
			'style' => trim($style),
			'color' => trim($color_code),
			'size' => trim($size)
		];

		$getProductInfoByStyleColorSize= array('arg0' => $arr,'arg1' => $webServiceUser);
		$logFileOpen = fopen("logs.txt", "a+") or die("Unable to open file!");
		$errorText = "</br></br></br>";
		$errorText .= "----------------------------------------------------------------------------";
		$errorText .= "Get Product Info SANMAR ".date("m/d/Y h:i A");
		$errorText .= "----------------------------------------------------------------------------";
		$errorText .= "</br></br></br>";
		$errorText = "getProductInfoByStyleColorSize data = ".print_r($getProductInfoByStyleColorSize, true);
		fwrite($logFileOpen, $errorText);
		unset($errorText);
		
		$result=$client->__soapCall('getProductInfoByStyleColorSize',array('getProductInfoByStyleColorSize' => $getProductInfoByStyleColorSize) );
		$array = json_decode(json_encode($result), true);

		$errorText = "product info data response = ".print_r($array, true);
		fwrite($logFileOpen, $errorText);
		unset($errorText);
		
		$result = $array['return'];
		return $result;
		die();
		
	}

	private function getcheckAvailabitySanmar($data = '', $PONumber, $attention='', $addressList){
		try{
			
			$localhostWsdlUrl = common::SANMAR_PO_SERVICE_POST;
			
			$client= new SoapClient($localhostWsdlUrl, [
			  'trace'=>true,
			  'exceptions'=>true
			]);
			
			//web service credentail 
			$webServiceUser = [
				'sanMarCustomerNumber' => common::sanMarCustomerNumber,
				'sanMarUserName' => common::sanMarUserName,
				'sanMarUserPassword' => common::sanMarUserPassword
			];
						
			$webServicePO = [ 
			  'attention' => $attention,
			  'internalMessage'=> '',
			  'notes'=>'',
			  'poNum' => $PONumber,
			  'shipTo' =>$addressList[0]['ship_to'],
			  'shipAddress1' => $addressList[0]['address_line_1'],
			  'shipAddress2' => $addressList[0]['address_line_2'],
			  'shipCity' => $addressList[0]['city'],
			  'shipState' => $addressList[0]['state'],
			  'shipZip' => $addressList[0]['zip_code'],
			  'shipMethod' => $addressList[0]['shipping_method'],
			  'shipEmail' => !empty($addressList[0]['email'])?$addressList[0]['email']:'',
			  'residence' => $addressList[0]['residence'],
			  'department'=> '',
			  'webServicePoDetailList' => $data
			];

			$getPreSubmitInfo=['arg0' => $webServicePO , 'arg1'=> $webServiceUser ];

			$logFileOpen = fopen("sanmar_logs.txt", "a+") or die("Unable to open file!");
			$errorText = "</br></br></br>";
			$errorText .= "----------------------------------------------------------------------------";
			$errorText .= "check availability start time ".date("m/d/Y h:i A");
			$errorText .= "----------------------------------------------------------------------------";
			$errorText .= "order data = ".print_r($getPreSubmitInfo, true);
			fwrite($logFileOpen, $errorText);
			unset($errorText);
			
			 //calling the submitPO method.
			$result=$client->__soapCall('getPreSubmitInfo',['getPreSubmitInfo' => $getPreSubmitInfo]);
			$errorText = "check availability response = ".print_r($result, true);
			fwrite($logFileOpen, $errorText);
			unset($errorText);
			fclose($logFileOpen);
			return $result;
			 
		} catch(SoapFault $e){
			// return var_dump($e);
		}
			
	}

	private function submitOrder($data, $PONumber, $attention='', $addressList,$selected_storename_json,$selected_ordernumber_json){
		global $login_user_email;
		try{

			// $isProduction = true;
			// if($isProduction == true)
			// {
			// 	$localhostWsdlUrl="https://ws.sanmar.com:8080/SanMarWebService/SanMarPOServicePort?wsdl";

			// }else{
			// 	$localhostWsdlUrl="https://edev-ws.sanmar.com:8080/SanMarWebService/SanMarPOServicePort?wsdl";
			// }

			//echo $localhostWsdlUrl;die;

			$localhostWsdlUrl = common::SANMAR_PO_SERVICE_POST;
			
			$client= new SoapClient($localhostWsdlUrl, [
			  'trace'=>true,
			  'exceptions'=>true
			]);
			
			//web service credentail 
			// $webServiceUser = array(
			// 	'sanMarCustomerNumber' => '208001',
			// 	'sanMarUserName' => 'spiritwearhero',
			// 	'sanMarUserPassword' => $isProduction == true?'Tesla123##':'12341234'
			// );
			$webServiceUser =[
				'sanMarCustomerNumber' => common::sanMarCustomerNumber,
				'sanMarUserName' => common::sanMarUserName,
				'sanMarUserPassword' => common::sanMarUserPassword
			];
			
			// $webServicePODetail = $data;

			$webServicePO = [ 
				'attention' => $attention,
				'internalMessage'=> '',
				'notes'=>'',
				'poNum' => $PONumber,
				'shipTo' =>$addressList[0]['ship_to'],
				'shipAddress1' => $addressList[0]['address_line_1'],
				'shipAddress2' => $addressList[0]['address_line_2'],
				'shipCity' => $addressList[0]['city'],
				'shipState' => $addressList[0]['state'],
				'shipZip' => $addressList[0]['zip_code'],
				'shipMethod' => $addressList[0]['shipping_method'],
				'shipEmail' => !empty($addressList[0]['email'])?$addressList[0]['email']:'',
				'residence' => $addressList[0]['residence'],
				'department'=> '',
				'webServicePoDetailList' => $data
			];

			if(!empty($data)){
				$data_get=self::GenerateUniqIdPlaceOrder();
				if(!empty($data_get)){
					$place_order_number_sa = 'SA-'.$data_get;
				}else{
					$place_order_number_sa = 'SA-1';
				}

				$spodh_insert_data = [
					'place_order_number_sa' => $place_order_number_sa,
					'po_number' 			=> $PONumber,
					'store_names' 			=> $selected_storename_json,
					'order_numbers' 		=> $selected_ordernumber_json,
					'create_on' 			=> @date('Y-m-d H:i:s'),
					'update_on' 			=> @date('Y-m-d H:i:s'),
					'order_placed_by'		=> "Super Admin <br>(".$login_user_email.")"
				];
				parent::insertTable_f_mdl('sanmar_place_order_details_history',$spodh_insert_data);

				foreach($data as $items){
					$po_insert_data = [
						'place_order_number_sa' => $place_order_number_sa,
						'attention' 			=> $attention,
						'po_number' 			=> $PONumber,
						'ship_to' 				=> $addressList[0]['ship_to'],
						'ship_email' 			=> !empty($addressList[0]['email'])?$addressList[0]['email']:'',
						'sku' 					=> $items['style'],
						'color' 				=> $items['color'],
						'size'  				=> $items['size'],
						'quantity' 				=> $items['quantity'],
						'create_on' 			=> @date('Y-m-d H:i:s'),
						'update_on' 			=> @date('Y-m-d H:i:s'),
						'order_placed_by'		=> "Super Admin <br>(".$login_user_email.")"
					];
					parent::insertTable_f_mdl('sanmar_place_order_history',$po_insert_data);
				}
			}
			$getPreSubmitInfo=['arg0' => $webServicePO , 'arg1'=> $webServiceUser ];
			 //calling the submitPO method.
			$logFileOpen = fopen("sanmar_logs.txt", "a+") or die("Unable to open file!");
			$errorText = "</br></br></br>";
			$errorText .= "----------------------------------------------------------------------------";
			$errorText .= "place order sanmar ".date("m/d/Y h:i A");
			$errorText .= "----------------------------------------------------------------------------";
			$errorText .= "</br></br></br>";
			$errorText .= "submitPO data = ".print_r($getPreSubmitInfo, true);
			fwrite($logFileOpen, $errorText);
			unset($errorText);
			$result=$client->__soapCall('submitPO',['submitPO' => $getPreSubmitInfo]);

			$errorText = "Submit order response = ".print_r($result, true);
			fwrite($logFileOpen, $errorText);
			unset($errorText);
			fclose($logFileOpen);

			return $result;
			 
		} catch(SoapFault $e){
			// return var_dump($e);
		}
	}

	private function updateProductStatus($data){
		foreach (json_decode($data['style'], true) as $key => $value) {
			$sql = "SELECT id, soim.totalBuyProduct FROM store_order_items_master as soim 
					WHERE store_owner_product_variant_master_id = '".json_decode($data['vid'], true)[$key]."' AND buyStatus = '0' ";
			$all_list = parent::selectTable_f_mdl($sql);
			if(count($all_list) > 0){
				$buyData = json_decode($data['qty'], true)[$key] + $all_list[0]['totalBuyProduct'];
				$itemInfo = [
					'buyStatus' => '1',
					'updated_at' => date("Y-m-d h:i:s"),
					'totalBuyProduct' 	=> $buyData
				];
				$substituteId = $this->checkUniqId(json_decode($data['vid'], true)[$key]);
				
				if(json_decode($data['substitute'], true)[$key] == '1'){
					$itemInfo['substituteStatus'] = '1';
					$itemInfo['substituteId'] = $substituteId;
				}
				// print_r($itemInfo);die;
				$where = 'store_owner_product_variant_master_id = "'.json_decode($data['vid'], true)[$key].'" AND buyStatus = "0"';
				$updateinfo = parent::updateTable_f_mdl('store_order_items_master',$itemInfo,$where);
				if($updateinfo){
					$arrInsert = [
						'sku' => json_decode($data['style'], true)[$key],
						'color' => json_decode($data['color'], true)[$key],
						'inventoryKey' => json_decode($data['inventoryKey'], true)[$key],
						'qty' => json_decode($data['qty'], true)[$key],
						'size' => json_decode($data['size'], true)[$key],
						'sizeIndex' => json_decode($data['sizeIndex'], true)[$key],
						'substituteId' => $substituteId,
					];
					parent::insertTable_f_mdl('substituteData',$arrInsert);
				}	
			}
		}	
	}


	private function checkUniqId($vid){
		$substituteId =	uniqid();
		$sql = "SELECT id, soim.totalBuyProduct FROM store_order_items_master as soim WHERE store_owner_product_variant_master_id = '".$vid."' AND substituteId = '".$substituteId."' ";
		$all_list = parent::selectTable_f_mdl($sql);
		if(count($all_list) > 0){
			$this->checkUniqId($vid);
		}
		return $substituteId;
	}

	function removeStoreSanmar()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "removeStoreSanmar") {

				$storeMasterId = parent::getVal("store_master_id");
				$items_ids='';
				$itemsArr=array();
				$sql = "SELECT sm.id, 
				(SELECT GROUP_CONCAT(oim.id) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 AND oim.buyStatus = '0' and oim.store_master_id = sm.id AND om.`order_tags` NOT LIKE '%Return_Prime%') as total_items,
				(SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0 AND `order_tags` NOT LIKE '%Return_Prime%') as total_order
				FROM `store_orders_master` as som 
				INNER JOIN store_master sm ON som.store_master_id = sm.id
				WHERE sm.id='".$storeMasterId."'  AND som.is_order_cancel = '0' AND som.`order_tags` NOT LIKE '%Return_Prime%' AND som.store_master_id IN (SELECT om.store_master_id FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 AND oim.buyStatus = '0'AND om.`order_tags` NOT LIKE '%Return_Prime%' )
				GROUP BY sm.id ORDER BY som.store_master_id  DESC";
				$all_list = parent::selectTable_f_mdl($sql);
				
				$items_ids=$all_list[0]['total_items'];
				$itemsArr=explode(",",$items_ids);

				foreach ($itemsArr as $values) {

					$updateStoreData = [
				        'buyStatus'=>'1'
					];
					parent::updateTable_f_mdl('store_order_items_master', $updateStoreData, 'id="' . $values . '"');
				}

				$resultArray = array();
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Store data delete successfully.";

				common::sendJson($resultArray);
			}
		}
	}

	function bulk_delete_store_sanmar()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "bulk_delete_store_sanmar") {

				$storeMasterId = parent::getVal("store_master_ids");

				foreach ($storeMasterId as $values) {
					$items_ids='';
					$itemsArr=array();
					$sql = "SELECT sm.id, 
					(SELECT GROUP_CONCAT(oim.id) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 AND oim.buyStatus = '0' and oim.store_master_id = sm.id AND om.`order_tags` NOT LIKE '%Return_Prime%') as total_items,
					(SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0 AND `order_tags` NOT LIKE '%Return_Prime%') as total_order
					FROM `store_orders_master` as som 
					INNER JOIN store_master sm ON som.store_master_id = sm.id
					WHERE sm.id='".$values."' AND som.is_order_cancel = '0' AND som.`order_tags` NOT LIKE '%Return_Prime%' AND som.store_master_id IN (SELECT om.store_master_id FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 AND oim.buyStatus = '0'AND om.`order_tags` NOT LIKE '%Return_Prime%' )
					GROUP BY sm.id ORDER BY som.store_master_id  DESC";
					$all_list = parent::selectTable_f_mdl($sql);
					
					$items_ids=$all_list[0]['total_items'];
					$itemsArr=explode(",",$items_ids);
					

					foreach ($itemsArr as $values) {

						$updateStoreData = [
							'buyStatus'=>'1'
						];
						parent::updateTable_f_mdl('store_order_items_master', $updateStoreData, 'id="' . $values . '"');
					}
				}
				
				$resultArray = array();
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Store data delete successfully.";

				common::sendJson($resultArray);
			}
		}
	}

	public function GenerateUniqIdPlaceOrder(){
		$digits = 3;
		$random = str_pad(rand(0, pow(10, $digits)-1), $digits, '0', STR_PAD_LEFT);
		$time = time();
		$mystring = substr($time,1,3);
		$uniq_id = $mystring.$random;
		$sql_id_get = "SELECT place_order_number_sa FROM sanmar_place_order_history WHERE place_order_number_sa=$uniq_id ";
		$data = parent::selectTable_f_mdl($sql_id_get);
		if(count($data)> 0){
			self::GenerateUniqIdPlaceOrder();
		}else{
			return $uniq_id;
		}
	}

	public function remove_mask_as_ordered_manually()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "remove_mask_as_ordered_manually") {

				$maskasordered = parent::getVal("maskasordered");
				$selected_storeid_json = rtrim(parent::getVal("selected_storeid_json"), ",");
				$selected_orderids_json = rtrim(parent::getVal("selected_orderids_json"), ",");
				
				$res=[];
				foreach ($maskasordered as $values) {
					$items_ids='';
					$itemsArr=array();
					$sku=$values['style'];
					$color=$values['color'];
					$size=$values['size'];

					$res=parent::updateMarkAsOrderd_f_mdl($selected_storeid_json, $selected_orderids_json,$sku,$color,$size);

				}
				$resultArray = array();
				$resultArray["maskasordered"] = $maskasordered;
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Items Marked As Manually Ordered.";

				common::sendJson($resultArray);
			}
		}
	}

	public function remove_mask_as_ordered_bulk()
	{
		if (parent::isPOST()) {
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "remove_mask_as_ordered_bulk") {

				$maskasordered_id = parent::getVal("maskasordered_id");
				$selected_storeid_json = rtrim(parent::getVal("selected_storeid_json"), ",");
				$selected_orderids_json = rtrim(parent::getVal("selected_orderids_json"), ",");
				
				$res=[];
				foreach ($maskasordered_id as $values) {
					$store_order_master_id='';
					$store_order_master_id=$values['store_order_master_id'];
					
					$res=parent::updateMarkAsOrderdBulk_f_mdl($selected_storeid_json,$store_order_master_id);

				}
				$resultArray = array();
				$resultArray["isSuccess"] = "TRUE";
				$resultArray["msg"] = "Order Marked As Manually Ordered.";

				common::sendJson($resultArray);
			}
		}
	}
	
}