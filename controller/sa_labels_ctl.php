<?php
include_once 'model/sa_labels_mdl.php';

$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . 'libraries/Aws3.php';
$s3Obj = new Aws3;

class sa_labels_ctl extends sa_labels_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		
		common::CheckLoginSession();
	}
	
	function getStoreDropdownInfo(){
		return parent::getStoreDropdownInfo_f_mdl();
	}
	
	function getvendorDropdownInfo(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "getVendor"){
				$storeMasterId = parent::getVal("store_master_id");
				$resultArray = array();

				$vendorRespArray = parent::getvendorDropdownInfo_f_mdl($storeMasterId);

				$dropdownHtml = '<option value="">All</option>';
				foreach($vendorRespArray as $vendor){
					$dropdownHtml .= '<option value="'.$vendor['vendor_name'].'">'.$vendor['vendor_name'].'</option>';
				}

				$resultArray['dropdown_html']=$dropdownHtml;

				common::sendJson($resultArray);
			}
		}
	}
	
	function getPoSettings(){
		return parent::getPoSettings_f_mdl();
	}
	
	function getDates(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "getDates"){
				
				$storeMasterId = parent::getVal("store_master_id");
				
				parent::getDates_f_mdl($storeMasterId);
			}
		}
	}
	
	function exportPdf(){
		ini_set("pcre.backtrack_limit", "10000000");
		require_once ('html-templates/custom/mpdf/vendor/autoload.php');
		if(parent::isGET()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "exportPdf"){

				$storeMasterId      = parent::getVal("store_master_id");
				$lableSize          = parent::getVal("lableSize");
				$store_sale_type_id = parent::getVal("store_sale_type_id");
				
				$lableStartDate_ts = $lableEndDate_ts = '';
				
				$lableStartDate = parent::getVal("lable_start_date");
				
				$lableOrderBy = parent::getVal("order_by");
				
				if(!empty($lableStartDate)){
					$lableStartDate_ts = strtotime($lableStartDate.' 0:0');
				}
				$lableEndDate = parent::getVal("lable_end_date");
				if(!empty($lableEndDate)){
					$lableEndDate_ts = strtotime($lableEndDate.' 23:59');
				}
				
				if($lableSize == '2'){
					$resArray = parent::get_labels_info_f_mdl($storeMasterId,$lableStartDate_ts,$lableEndDate_ts,$lableOrderBy,$store_sale_type_id);
					
					if(empty($resArray)) {
						$_SESSION['SUCCESS'] = 'FALSE';
						$_SESSION['MESSAGE'] = 'No record found.';
						header("Location: sa-labels.php?stkn=".parent::getVal("stkn"));
						exit;
					}
					
					$orderWiseArray = [];
					foreach($resArray as $objData){
						$orderWiseArray[$objData['shop_order_number']][] = $objData;
					}
					$main_data_arr = [];
					$orderLineItems = [];
					
					foreach($orderWiseArray as $obj_k=>$obj_v){
						$tmp_arr = [];$item_arr = [];

						$total_item_count = 0;
						
						$quantity = array();
						foreach ($obj_v as $key => $row)
						{
							$quantity[$key] = $row['quantity'];
						}
						array_multisort($quantity, SORT_ASC, $obj_v);
						$size_arr=array();
						$item_no=0;
						foreach($obj_v as $lineItemObj){
							$total_item_count = $total_item_count + $lineItemObj['quantity'];
							$item_name= '';
							$shop_variant_id=$lineItemObj['shop_variant_id'];
							if(!empty($shop_variant_id)){
								$sql = 'SELECT sopvm.store_owner_product_master_id,sopm.product_title FROM store_owner_product_variant_master as sopvm INNER JOIN  store_owner_product_master as sopm ON sopvm.store_owner_product_master_id=sopm.id WHERE sopvm.shop_variant_id='.$shop_variant_id.' ';
								$prodname_arr = parent::selectTable_f_mdl($sql);
							}
							if(!empty($prodname_arr)){
								$item_name = trim($prodname_arr[0]['product_title'],'-')." ".$lineItemObj['variant_title']." X ".$lineItemObj['quantity'];
								$item_name1 =trim($prodname_arr[0]['product_title'],'-');

							}else{
								$item_name = trim(str_replace($resArray[0]['store_name'],"",$lineItemObj['title']),'-')." ".$lineItemObj['variant_title']." X ".$lineItemObj['quantity'];
								$item_name1 =trim(str_replace($resArray[0]['store_name'],"",$lineItemObj['title']),'-');
							}
							
							$size_arr=explode("/",$lineItemObj['variant_title']);

							$array =  explode(" /", $lineItemObj['variant_title'], 2);
							$variant_color     = !empty($array[1])?$array[1]:'';
							$variant_size      = !empty($array[0])?$array[0]:'';
							$item_no++;
							$t = [];
							$t['item_no'] = $item_no;
							$t['item'] = $item_name;
							$t['item_name'] = $item_name1;
							$t['size'] = $variant_size;
							$t['color'] = $variant_color;
							$t['quantity'] = $lineItemObj['quantity'];
							
							array_push($item_arr,$t);
						}

						$tmp_arr['order_text'] = 'Order No:';
						$tmp_arr['order_no'] = '#'.$obj_v[0]['shop_order_number'];
						$tmp_arr['student_name'] = $obj_v[0]['student_name'];
						$tmp_arr['sort_list_name'] = $obj_v[0]['sortlist_info'];

						$tmp_arr['totalItem'] = $total_item_count;

						$tmp_arr['line_items'] = $item_arr;
						array_push($orderLineItems,$tmp_arr);
					}

					$main_data_arr['store_name'] = $resArray[0]['store_name'];
					$main_data_arr['orders'] = $orderLineItems;
					$res = $this->createdPdfFunc($main_data_arr);

					$mpdf = new \Mpdf\Mpdf(['format' => [101.6, 152.4]]);
					$mpdf = new \Mpdf\Mpdf([
		                'format' => [101.6, 152.4],
		                'margin_left' => 3,
		                'margin_right' => 3,
		                'margin_top' => 2,
		                'margin_bottom' => 10,
		                'margin_footer' => 5 // Add footer margin
		            ]);
					$mpdf->defaultfooterline = 0;
					$mpdf->SetFooter('{PAGENO}');
					$mpdf->defaultheaderline = 0;
					
					$mpdf->WriteHTML($res);
					$mpdf->SetDisplayMode('fullpage');
					$mpdf->Output('label_pdf.pdf', 'I');	
					exit(0);
					
				}
			}
		}
	}

	function createdPdfFunc($data){
		$divHtml = '<html>
		<head>
			<style>
			@import url("https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@200;400;600;700;800;900&display=swap");
	
			body, html, iframe {
				color: #000;
				font-family: "Nunito Sans", sans-serif !important;
				font-weight: 400;
				font-size: 14px;
				background-color: #fff;
				margin: 7px 10px;
				padding: 7px 10px;
			}
	
			*, *::before, *::after {
				box-sizing: border-box;
				margin: 0; 
				padding: 0; 
			}
			.order_tbl {
				max-width: 100%;
				width:100%; 
			}
	
			.order_logo { 
				text-align: center;
				width:320px;
				margin:0 auto;
			}
			.order_logo img {
				width:100%;
			}
	
			.order_venue {
				text-align: center;
				background-color: #000;
				color: #fff;
				padding:2px 10px;
				word-wrap:break-word;
				font-size:12px;
				font-weight: bold;
			}
	
			.order_venue p {
				margin: 0;
			}
	
			.order_venue a {
				color: #fff;
				text-decoration: none;
			}
	
			.order_id {
				text-align: center;
				border-collapse: collapse;
				border-spacing: 0;
				border: 2px solid #000;
				width:100%;  
			}
	
			.order_date {
				text-align: center;
				font-size: 14px; 
				font-weight: bold;
				margin: 0 0;
				white-space: break-spaces;
			}
	
			.order_id thead tr td {
				border: 2px solid #000;
				padding: 5px;
				font-size: 13px;
				font-weight: 600;
			}
	
			.items_bold {
				font-weight: bold;
				vertical-align: top;
				width: 60px;
			}
	
			.items_area .quantity_items {
				font-weight: bold; 
				font-size: 18px;
				text-align: center;
				width:88px;
			}
	
			.items_area .quantity_items span {
				display: block;
				font-size: 17px;
				width: 100%;
			}
	
			.customer_name {
				display: block;
				margin-top: 4px;
			}
			.items_area .itme_number {
				font-weight: bold;
				font-size: 16px;
				padding-bottom: 10px;
				white-space: nowrap;
			}
	
			.items_area {
				border-bottom: 2px solid #000;
				padding: 0px 0px;
			}
			.items_area td {
				padding: 0 4px; 
				font-size: 14px; 
			}
	
			.customer_name {
				display: block;
			}
			.order_block {
				max-width:437px;
				margin: 0 auto; 
			}
			@media(max-width:575px) {
			 .order_block {
				max-width: fit-content;
				}
			}
			</style>
		</head>
		<body>
		<main> 
		';
		$dataCount = count($data['orders']);
		$a = 1;

		foreach($data['orders'] as $value){
			if($a === $dataCount){
				$divHtml .= '<section class="order_block" style="page-break-after: never;">';
			}else{
				$divHtml .= '<section class="order_block" style="page-break-after: always;">';
			}
			$divHtml .= '
			
				<table class="order_tbl">
	
				<tr>
								<td>
									<div class="order_logo">
									<img src="img/pdfLogo.png">
									</div>
								</td>
							</tr>
							<tr>
								<td class="order_venue">
										<p class="order_issues">HAVE AN ORDER ISSUE?</p>
										<p class="order_issues">Call 800-239-9948 X 1 or Email <a
												href="#0" style="color:#fff;text-decoration:none;">support@spirithero.com</a></p>
								</td>
							</tr>
							<tr>
								<td class="order_date">'.$data['store_name'].'</td>
							</tr>
							<tr>
								<td>
									<table class="order_tbl order_id">
										<thead>
											<tr>
												<td>Order : '.$value['order_no'].'</td>
												<td>Total # of Items : '.$value['totalItem'].'</td>
											</tr>
											<tr>
												<td>
													Customer Name:
													<span class="customer_name">'.$value['student_name'].'</span>
												</td>
												<td>Teacher Name:
													<span class="customer_name">'.$value['sort_list_name'].'</span>
												</td>
											</tr>
										</thead>
									</table>
								</td>
							</tr>
				
			  
			';
			foreach($value['line_items'] as $item){
				$divHtml .= ' 
							<tr>
								<td class="items_area">
									<table class="order_tbl items_group">
										<tbody>
											<tr>

												<td>
													<table class="order_tbl items_group">
													<tbody>
												 	<tr>
												 	<td class="items_bold">Name:</td>
												 	<td>'.$item['item_name'].'</td>
												 	</tr>      
													</tbody>
													</table>
												</td>

					
												<td rowspan="4" class="quantity_items">Quantity
													<span style="font-size:30px;">'.$item['quantity'].'</span>
												</td>
											</tr>
											<tr>

												<td>
												<table class="order_tbl items_group">
												<tbody>
												 <tr>
												 <td class="items_bold">Color:</td>
												 <td>
												 '.$item['color'].'
												 </td>
												 </tr>      
												</tbody>
												</table>
												</td>

											</tr>
											<tr>

											<td>
											<table class="order_tbl items_group">
											<tbody>
											 <tr>
											 <td class="items_bold">Size:</td>
											 <td>
											 '.$item['size'].'
											 </td>
											 </tr>      
											</tbody>
											</table>
											</td>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>						
				';
			}
			$divHtml .=  ' </table>
			</section>
		
			';
			$a++;
		}
		$divHtml .= '</main>
		</body>
	</html>';
	return $divHtml;
	
	}
	
	// function generatePoPdf_Old(){
	// 	if(parent::isGET()){
	// 		if(!empty(parent::getVal("method")) && parent::getVal("method") == "generatePo"){
	// 			$storeMasterId = parent::getVal("store_master_id");
	// 			$select_vendor = parent::getVal("select_vendor");
	// 			$store_sale_type_id = parent::getVal("store_sale_type_id");
	// 			$lableStartDate_ts = $lableEndDate_ts = '';
				
	// 			$lableStartDate = parent::getVal("lable_start_date");
	// 			$resultArray = array();
				
	// 			if(!empty($lableStartDate)){
	// 				$lableStartDate_ts = strtotime($lableStartDate.' 0:0');
	// 			}
	// 			$lableEndDate = parent::getVal("lable_end_date");
	// 			if(!empty($lableEndDate)){
	// 				$lableEndDate_ts = strtotime($lableEndDate.' 23:59');
	// 			}
				
	// 			$poResArray = parent::getPurchaseOrderLineItem($storeMasterId,$lableStartDate_ts,$lableEndDate_ts,$select_vendor,$store_sale_type_id);

	// 			$settingArray = parent::getPoSettings_f_mdl();
				
	// 			$po_number = parent::getVal("po_number");
	// 			$account_number = parent::getVal("account_number");
	// 			$po_notes = parent::getVal("po_notes");
	// 			$po_bill_to = parent::getVal("po_bill_to");
	// 			$po_ship_to = parent::getVal("po_ship_to");

	// 			if(!empty($poResArray)){
	// 				$main_data_arr = [];
	// 				$line_data_arr = [];
	// 				$totalQty = 0;

	// 				foreach($poResArray as $objData){					
	// 					$tmpTagArray = explode(",",$objData['tags']);
						
	// 					$brand = '';
	// 					foreach($tmpTagArray as $objTag){
	// 						if (strpos($objTag, 'brand_') !== false) {
	// 							$brand = str_replace("brand_","",$objTag);
	// 						}
	// 					}
						
	// 					$tmp_arr = [];
	// 					$tmp_arr['vendor'] = $objData['vendor'];
	// 					$tmp_arr['sku'] = $objData['sku'];
	// 					$tmp_arr['qty'] = $objData['quantity'];
	// 					$tmp_arr['name'] = trim(str_replace($objData['store_name'],"",$objData['title']),'-');
	// 					$tmp_arr['color'] = trim(substr($objData['variant_title'], strrpos($objData['variant_title'], '/') + 1));
	// 					$tmp_arr['size'] = trim(implode('/', explode('/', $objData['variant_title'], -1)));
	// 					$tmp_arr['brand'] = $brand;

	// 					$totalQty += $objData['quantity'];

	// 					array_push($line_data_arr,$tmp_arr);
	// 				}
					
	// 				$main_data_arr['po_number'] = $po_number;
	// 				$main_data_arr['account_number'] = $account_number;
	// 				$main_data_arr['notes'] = $po_notes;
	// 				$main_data_arr['ship_to'] = $po_ship_to;
	// 				$main_data_arr['bill_to'] = $po_bill_to;
	// 				$main_data_arr['Line'] = $line_data_arr;
	// 				$main_data_arr['TotalQty'] = $totalQty;
					
	// 				$documentData = json_encode($main_data_arr);
					
	// 				$api_path = '';				

	// 				$template_id = common::PDF_GENERATE_API_PO_ID;
					
	// 				$pdf_res = parent::get_pdf_by_api(common::PDF_GENERATE_API_KEY,common::PDF_GENERATE_API_SECRET,common::PDF_GENERATE_API_WORKSPACE,$template_id,$documentData,$api_path);
					
	// 				if(isset($pdf_res['pdf_url']) && !empty($pdf_res['pdf_url'])){
	// 					//header('Location:  '.$pdf_res['pdf_url']);
	// 					//echo '<script type="text/javascript">window.open(\''.$pdf_res['pdf_url'].'\', \'_blank\');</script>';
	// 					$resultArray["SUCCESS"] = 'TRUE';
	// 					$resultArray["pdf_url"] = $pdf_res['pdf_url'];
	// 				}else{
	// 					$resultArray["SUCCESS"] = 'FALSE';
	// 					$resultArray['MESSAGE'] = 'Your account has exceeded the monthly document generation limit.';
	// 				}		
	// 			}else{
	// 				$resultArray["SUCCESS"] = 'FALSE';
	// 				$resultArray['MESSAGE'] = 'No record found.';
	// 			}
	// 			common::sendJson($resultArray);
	// 		}
	// 	}
	// }

	// function generatePoPdf1(){
	// 	if(parent::isGET()){
	// 		if(!empty(parent::getVal("method")) && parent::getVal("method") == "generatePo"){
	// 			$storeMasterId = parent::getVal("store_master_id");
	// 			$select_vendor = parent::getVal("select_vendor");
	// 			$store_sale_type_id = parent::getVal("store_sale_type_id");
	// 			$lableStartDate_ts = $lableEndDate_ts = '';
				
	// 			$lableStartDate = parent::getVal("lable_start_date");
	// 			$resultArray = array();
				
	// 			if(!empty($lableStartDate)){
	// 				$lableStartDate_ts = strtotime($lableStartDate.' 0:0');
	// 			}
	// 			$lableEndDate = parent::getVal("lable_end_date");
	// 			if(!empty($lableEndDate)){
	// 				$lableEndDate_ts = strtotime($lableEndDate.' 23:59');
	// 			}
				
	// 			$poResArray = parent::getPurchaseOrderLineItem($storeMasterId,$lableStartDate_ts,$lableEndDate_ts,$select_vendor,$store_sale_type_id);
	// 			$settingArray = parent::getPoSettings_f_mdl();
				
	// 			$po_number = parent::getVal("po_number");
	// 			$account_number = parent::getVal("account_number");
	// 			$po_notes = parent::getVal("po_notes");
	// 			$po_bill_to = parent::getVal("po_bill_to");
	// 			$po_ship_to = parent::getVal("po_ship_to");
	// 			if(!empty($poResArray)){
	// 				$main_data_arr = [];
	// 				$line_data_arr = [];
	// 				$totalQty = 0;
	// 				foreach($poResArray as $key=>$objData){
	// 					$tmpProData = array();
	// 					foreach($poResArray as $keys=>$objDatas){
	// 						if($objData['title']==$objDatas['title']){
	// 							$tmpProData[] = $objDatas;
	// 						}
	// 					}
	// 					$productData[$objData['title']] = $tmpProData;
	// 					$data = array();
	// 					$ttl = 0;
	// 					$data['b1']=0;
	// 					$data['b2']=0;
	// 					$data['b3']=0;
	// 					$data['b4']=0;
	// 					$data['b5']=0;
	// 					$data['b6']=0;
	// 					$data['b7']=0;
	// 					$data['b8']=0;
	// 					$data['b9']=0;
	// 					$data['b10']=0;
	// 					$data['b11']=0;
	// 					$data['b12']=0;
	// 					$data['b13']=0;
	// 					$data['b14']=0;
						
	// 					foreach ($productData[$objData['title']] as $value) {
	// 						$sizes = trim(implode('/', explode('/', $value['variant_title'], -1)));
	// 						if(strpos($sizes,' (')!==false){
	// 							$val = explode(' (',$sizes);
	// 							$size = $val[0];
	// 						}
	// 						else{
	// 							$size = $sizes;
	// 						}
	// 						$titleSql = 'SELECT id,product_title FROM `store_owner_product_master` WHERE id ="'.$value['store_owner_product_master_id'].'" ';			
	// 						$titleDetails = parent::selectTable_f_mdl($titleSql);

	// 						if (!empty($titleDetails)) {
	// 							$data['name']   = trim($titleDetails[0]['product_title']);
	// 						}
	// 						else{
	// 							$data['name']   = trim(str_replace($value['store_name'],"",$value['title']),'-');
	// 						}
	// 						$data['vendor'] = (isset($value['vendor']))?$value['vendor']:'-';
	// 						$data['sku']    = (isset($value['sku']))?$value['sku']:'-';
	// 						$data['color']  = trim(substr($value['variant_title'], strrpos($value['variant_title'], '/') + 1));
	// 						$tmpTagArray    = explode(",",$value['tags']);
	// 						$brand = '';
	// 						foreach($tmpTagArray as $objTag){
	// 							if (strpos($objTag, 'brand_') !== false) {
	// 								$brand = str_replace("brand_","",$objTag);
	// 							}
	// 						}
	// 						$data['brand'] = $brand;
	// 						$qty = 0;
							
	// 						switch (true) {
	// 							case strpos($size,'Youth XS') !== false:
	// 								$data['b1'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Youth S') !== false:
	// 								$data['b2'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Youth M') !== false:
	// 								$data['b3'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Youth L') !== false:
	// 								$data['b4'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Youth XL') !== false:
	// 								$data['b5'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Adult XS') !== false:
	// 								$data['b6'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Adult S') !== false:
	// 								$data['b7'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Adult M') !== false:
	// 								$data['b8'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Adult L') !== false:
	// 								$data['b9'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Adult XL') !== false:
	// 								$data['b10'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Adult 2XL') !== false:
	// 								$data['b11'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Adult 3XL') !== false:
	// 								$data['b12'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Adult 4XL') !== false:
	// 								$data['b13'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'XS') !== false:
	// 								$data['b6'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'S') !== false:
	// 								$data['b7'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'M') !== false:
	// 								$data['b8'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'L') !== false:
	// 								$data['b9'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'XL') !== false:
	// 								$data['b10'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'2XL') !== false:
	// 								$data['b11'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'3XL') !== false:
	// 								$data['b12'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'4XL') !== false:
	// 								$data['b13'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							default:
	// 							    $data['b14'] += (isset($value['quantity']))?$value['quantity']:0;
	// 						}
	// 						$ttl= (int)$data['b1']+$data['b2']+$data['b3']+$data['b4']+$data['b5']+$data['b6']+$data['b7']+$data['b8']+$data['b9']+$data['b10']+$data['b11']+$data['b12']+$data['b13']+$data['b14'];
	// 						$data['b15']=$ttl;
	// 					}
	// 					array_push($line_data_arr,$data);
	// 				}
	// 				$tempArr                         = array_unique(array_column($line_data_arr, 'name'));
	// 				$line_data                       = array_intersect_key($line_data_arr, $tempArr);
	// 				$item_data                       = array_values($line_data);
	// 				$main_data_arr['po_number']      = $po_number;
	// 				$main_data_arr['store_name']     = $poResArray[0]['store_name'];
	// 				$main_data_arr['account_number'] = $account_number;
	// 				$main_data_arr['notes']          = $po_notes;
	// 				$main_data_arr['ship_to']        = $po_ship_to;
	// 				$main_data_arr['bill_to']        = $po_bill_to;
	// 				$main_data_arr['Line']           = $item_data;
	// 				$main_data_arr['TotalQty']       = array_sum(array_column($item_data, 'b15'));
	// 				$documentData                    = json_encode($main_data_arr);
	// 				$api_path = '';		
		
	// 				$template_id = common::PDF_GENERATE_API_PO_ID;
					
	// 				$pdf_res = parent::get_pdf_by_api(common::PDF_GENERATE_API_KEY,common::PDF_GENERATE_API_SECRET,common::PDF_GENERATE_API_WORKSPACE,$template_id,$documentData,$api_path);
					
	// 				if(isset($pdf_res['pdf_url']) && !empty($pdf_res['pdf_url'])){
	// 					//header('Location:  '.$pdf_res['pdf_url']);
	// 					//echo '<script type="text/javascript">window.open(\''.$pdf_res['pdf_url'].'\', \'_blank\');</script>';
	// 					$resultArray["SUCCESS"] = 'TRUE';
	// 					$resultArray["pdf_url"] = $pdf_res['pdf_url'];
	// 				}else{
	// 					$resultArray["SUCCESS"] = 'FALSE';
	// 					$resultArray['MESSAGE'] = 'Your account has exceeded the monthly document generation limit.';
	// 				}		
	// 			}else{
	// 				$resultArray["SUCCESS"] = 'FALSE';
	// 				$resultArray['MESSAGE'] = 'No record found.';
	// 			}
	// 			common::sendJson($resultArray);
	// 		}
	// 	}
	// }

	// function generatePoPdf(){
	// 	if(parent::isGET()){
	// 		if(!empty(parent::getVal("method")) && parent::getVal("method") == "generatePo"){
	// 			$storeMasterId = parent::getVal("store_master_id");
	// 			$select_vendor = parent::getVal("select_vendor");
	// 			$store_sale_type_id = parent::getVal("store_sale_type_id");
	// 			$lableStartDate_ts = $lableEndDate_ts = '';
				
	// 			$lableStartDate = parent::getVal("lable_start_date");
	// 			$resultArray = array();
				
	// 			if(!empty($lableStartDate)){
	// 				$lableStartDate_ts = strtotime($lableStartDate.' 0:0');
	// 			}
	// 			$lableEndDate = parent::getVal("lable_end_date");
	// 			if(!empty($lableEndDate)){
	// 				$lableEndDate_ts = strtotime($lableEndDate.' 23:59');
	// 			}
				
	// 			$poResArray = parent::getPurchaseOrderLineItem($storeMasterId,$lableStartDate_ts,$lableEndDate_ts,$select_vendor,$store_sale_type_id);
	// 			$settingArray = parent::getPoSettings_f_mdl();
				
	// 			$po_number = parent::getVal("po_number");
	// 			$account_number = parent::getVal("account_number");
	// 			$po_notes = parent::getVal("po_notes");
	// 			$po_bill_to = parent::getVal("po_bill_to");
	// 			$po_ship_to = parent::getVal("po_ship_to");
	// 			if(!empty($poResArray)){
	// 				$main_data_arr = [];
	// 				$line_data_arr = [];
	// 				$totalQty = 0;
	// 				foreach($poResArray as $key=>$objData){
	// 					$tmpProData = array();
	// 					$color1 = trim(substr($objData['variant_title'], strrpos($objData['variant_title'], '/') + 1));
	// 					foreach($poResArray as $keys=>$objDatas){
	// 						$color2 = trim(substr($objDatas['variant_title'], strrpos($objDatas['variant_title'], '/') + 1));
	// 						if($objData['title']==$objDatas['title']){
	// 							$tmpProData[$color2][] = $objDatas;
	// 						}
	// 					}
	// 					// echo "<pre>";print_r($productData[$objData['title']]);die();
	// 					$productData[$objData['title']] = $tmpProData;
	// 					// echo "<pre>";print_r($productData[$objData['title']][$color1]);die();
	// 					$data = array();
	// 					$ttl = 0;
	// 					$data['b1']=0;
	// 					$data['b2']=0;
	// 					$data['b3']=0;
	// 					$data['b4']=0;
	// 					$data['b5']=0;
	// 					$data['b6']=0;
	// 					$data['b7']=0;
	// 					$data['b8']=0;
	// 					$data['b9']=0;
	// 					$data['b10']=0;
	// 					$data['b11']=0;
	// 					$data['b12']=0;
	// 					$data['b13']=0;
	// 					$data['b14']=0;
						
	// 					foreach ($productData[$objData['title']][$color1] as $value) {
	// 						$sizes = trim(implode('/', explode('/', $value['variant_title'], -1)));
	// 						if(strpos($sizes,' (')!==false){
	// 							$val = explode(' (',$sizes);
	// 							$size = $val[0];
	// 						}
	// 						else{
	// 							$size = $sizes;
	// 						}
	// 						$titleSql = 'SELECT id,product_title FROM `store_owner_product_master` WHERE id ="'.$value['store_owner_product_master_id'].'" ';			
	// 						$titleDetails = parent::selectTable_f_mdl($titleSql);

	// 						if (!empty($titleDetails)) {
	// 							$data['name']   = trim($titleDetails[0]['product_title']);
	// 						}
	// 						else{
	// 							$data['name']   = trim(str_replace($value['store_name'],"",$value['title']),'-');
	// 						}
	// 						$data['vendor'] = (isset($value['vendor']))?$value['vendor']:'-';
	// 						$data['sku']    = (isset($value['sku']))?$value['sku']:'-';
	// 						$data['color']  = trim(substr($value['variant_title'], strrpos($value['variant_title'], '/') + 1));
	// 						$tmpTagArray    = explode(",",$value['tags']);
	// 						$brand = '';
	// 						foreach($tmpTagArray as $objTag){
	// 							if (strpos($objTag, 'brand_') !== false) {
	// 								$brand = str_replace("brand_","",$objTag);
	// 							}
	// 						}
	// 						$data['brand'] = $brand;
	// 						$qty = 0;
							
	// 						switch (true) {
	// 							case strpos($size,'Youth XS') !== false:
	// 								$data['b1'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Youth S') !== false:
	// 								$data['b2'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Youth M') !== false:
	// 								$data['b3'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Youth L') !== false:
	// 								$data['b4'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Youth XL') !== false:
	// 								$data['b5'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Adult XS') !== false:
	// 								$data['b6'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Adult S') !== false:
	// 								$data['b7'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Adult M') !== false:
	// 								$data['b8'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Adult L') !== false:
	// 								$data['b9'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Adult XL') !== false:
	// 								$data['b10'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Adult 2XL') !== false:
	// 								$data['b11'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Adult 3XL') !== false:
	// 								$data['b12'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Adult 4XL') !== false:
	// 								$data['b13'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'Adult 5XL') !== false:
	// 								$data['b14'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'XS') !== false:
	// 								$data['b6'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'S') !== false:
	// 								$data['b7'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'M') !== false:
	// 								$data['b8'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'L') !== false:
	// 								$data['b9'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'XL') !== false:
	// 								$data['b10'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'2XL') !== false:
	// 								$data['b11'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'3XL') !== false:
	// 								$data['b12'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'4XL') !== false:
	// 								$data['b13'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 							case strpos($size,'5XL') !== false:
	// 								$data['b14'] += (isset($value['quantity']))?$value['quantity']:0;
	// 							break;
	// 						}
	// 						$ttl= (int)$data['b1']+$data['b2']+$data['b3']+$data['b4']+$data['b5']+$data['b6']+$data['b7']+$data['b8']+$data['b9']+$data['b10']+$data['b11']+$data['b12']+$data['b13']+$data['b14'];
	// 						$data['b15']=$ttl;
	// 					}
	// 					array_push($line_data_arr,$data);
	// 				}

	// 				$item_data                       = $this->CheckCustomArrayUnique($line_data_arr);
	// 				$main_data_arr['po_number']      = $po_number;
	// 				$main_data_arr['store_name']     = $poResArray[0]['store_name'];
	// 				$main_data_arr['account_number'] = $account_number;
	// 				$main_data_arr['notes']          = $po_notes;
	// 				$main_data_arr['ship_to']        = $po_ship_to;
	// 				$main_data_arr['bill_to']        = $po_bill_to;
	// 				$main_data_arr['Line']           = $item_data;
	// 				$main_data_arr['TotalQty']       = array_sum(array_column($item_data, 'b15'));
	// 				// echo "<pre>";print_r($main_data_arr);die();
	// 				$documentData                    = json_encode($main_data_arr);
	// 				$api_path = '';		
		
	// 				$template_id = common::PDF_GENERATE_API_PO_ID;
					
	// 				$pdf_res = parent::get_pdf_by_api(common::PDF_GENERATE_API_KEY,common::PDF_GENERATE_API_SECRET,common::PDF_GENERATE_API_WORKSPACE,$template_id,$documentData,$api_path);
					
	// 				if(isset($pdf_res['pdf_url']) && !empty($pdf_res['pdf_url'])){
	// 					//header('Location:  '.$pdf_res['pdf_url']);
	// 					//echo '<script type="text/javascript">window.open(\''.$pdf_res['pdf_url'].'\', \'_blank\');</script>';
	// 					$resultArray["SUCCESS"] = 'TRUE';
	// 					$resultArray["pdf_url"] = $pdf_res['pdf_url'];
	// 				}else{
	// 					$resultArray["SUCCESS"] = 'FALSE';
	// 					$resultArray['MESSAGE'] = 'Your account has exceeded the monthly document generation limit.';
	// 				}		
	// 			}else{
	// 				$resultArray["SUCCESS"] = 'FALSE';
	// 				$resultArray['MESSAGE'] = 'No record found.';
	// 			}
	// 			common::sendJson($resultArray);
	// 		}
	// 	}
	// }

	public function CheckCustomArrayUnique($line_data_arr)
	{
		$result      = array();
		$tmpResult      = array();
		/*foreach ($line_data_arr as $keys => $values){
			foreach ($line_data_arr as $key => $val) {
				if($val['name']!=$values['name'] && $val['color']!=$values['color']){
					$tmpResult[] = $values;	
				}
			}
		}*/

		foreach ($line_data_arr as $keys => $values) {
			if(!empty($tmpResult)){
				$status = true;
				foreach ($tmpResult as $key => $val) {
					// echo "<pre>";print_r($tmpResult);
					if($val['name']==$values['name'] && $val['color']==$values['color']){
						// $tmpResult[] = $values;	
						$status = false;
					}
				}
				if($status){
					$tmpResult[] = $values;	
				}
			}else{
				$tmpResult[] = $values;
			}
		}
		return $tmpResult;
	}

	// function generateProfitOrder(){
	// 	if(parent::isGET() || parent::isPOST()){
	// 		if(!empty(parent::getVal("method")) && parent::getVal("method") == "generateProfitOrder"){
	// 			$storeMasterId = parent::getVal("store_master_id");
	// 			$shipping_cost = parent::getVal("shipping_cost");
	// 			$apparel_cost = parent::getVal("apparel_cost");
	// 			$printing_cost = parent::getVal("printing_cost");
	// 			$misc_expense = parent::getVal("misc_expense");
	// 			$store_sale_type_id = parent::getVal("store_sale_type_id");
	// 			$lableStartDate_ts = $lableEndDate_ts = $store_title = '';
	// 			$lableStartDate = parent::getVal("lable_start_date");
	// 			$resultArray = array();
				
	// 			if(!empty($lableStartDate)){
	// 				$lableStartDate_ts = strtotime($lableStartDate.' 0:0');
	// 			}
	// 			$lableEndDate = parent::getVal("lable_end_date");
	// 			if(!empty($lableEndDate)){
	// 				$lableEndDate_ts = strtotime($lableEndDate.' 23:59');
	// 			}
				
	// 			$respArray = parent::getTotalCell($storeMasterId,$lableStartDate_ts,$lableEndDate_ts,$store_sale_type_id);
				
	// 			if(!empty($respArray[0]['total_sell']))
	// 			{
	// 				$total_profit = (float)$respArray[0]['total_sell'] - ((float)$shipping_cost + (float)$apparel_cost+(float)$printing_cost+(float)$misc_expense);
				
	// 				if($storeMasterId == 'all'){
	// 					$store_title = 'ALL';
	// 				}else{
	// 					$store_title = $respArray[0]['title'];
	// 				}
					
	// 				$main_data_arr = [];
	// 				$main_data_arr['store_name'] = $store_title;
	// 				$main_data_arr['total_sell'] = number_format((float)$respArray[0]['total_sell'], 2);
	// 				$main_data_arr['shipping_cost'] = number_format((float)$shipping_cost, 2);
	// 				$main_data_arr['apparel_cost'] = number_format((float)$apparel_cost, 2);
	// 				$main_data_arr['printing_cost'] = number_format((float)$printing_cost, 2);
	// 				$main_data_arr['misc_expense'] = number_format((float)$misc_expense, 2);
					
	// 				$main_data_arr['total_profit'] = number_format((float)$total_profit, 2);
					
	// 				$documentData = json_encode($main_data_arr);
					
	// 				$api_path = '';				

	// 				$template_id = common::PDF_GENERATE_API_PROFIT_REPORT;
					
	// 				$pdf_res = parent::get_pdf_by_api(common::PDF_GENERATE_API_KEY,common::PDF_GENERATE_API_SECRET,common::PDF_GENERATE_API_WORKSPACE,$template_id,$documentData,$api_path);
					
	// 				if(isset($pdf_res['pdf_url']) && !empty($pdf_res['pdf_url'])){
	// 					//header('Location:  '.$pdf_res['pdf_url']);
	// 					//echo '<script type="text/javascript">window.open(\''.$pdf_res['pdf_url'].'\', \'_blank\');</script>';
	// 					$resultArray["SUCCESS"] = 'TRUE';
	// 					$resultArray["pdf_url"] = $pdf_res['pdf_url'];
	// 				}else{
	// 					$resultArray["SUCCESS"] = 'FALSE';
	// 					$resultArray['MESSAGE'] = 'Your account has exceeded the monthly document generation limit';
	// 				}
	// 			}else{
	// 				$resultArray["SUCCESS"] = 'FALSE';
	// 				$resultArray['MESSAGE'] = 'No record found.';
	// 			}
				
	// 			common::sendJson($resultArray);
	// 		}
	// 	}
	// }

	//Teacher PDF Before 12-09-2024 text cut issue 
	function exportTeacherPdfOld(){
		if(parent::isGET()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "exportTeacherPdf"){
				$bindHtml = '';
				$storeMasterId = parent::getVal("store_master_id");
				$store_sale_type_id = parent::getVal("store_sale_type_id");
				$lableStartDate_ts = $lableEndDate_ts = '';
				$lableStartDate = parent::getVal("lable_start_date");
				if(!empty($lableStartDate)){
					$lableStartDate_ts = strtotime($lableStartDate.' 0:0');
				}
				$lableEndDate = parent::getVal("lable_end_date");
				if(!empty($lableEndDate)){
					$lableEndDate_ts = strtotime($lableEndDate.' 23:59');
				}

				$resArray = parent::get_orders_f_mdl($storeMasterId,$lableStartDate_ts,$lableEndDate_ts,$store_sale_type_id);
				$store_name=$resArray[0]['store_name'];
				$store_name=str_replace("/"," ",$store_name);
				if(empty($resArray)) {
					$_SESSION['SUCCESS'] = 'FALSE';
					$_SESSION['MESSAGE'] = 'No record found.';
					header("Location: sa-labels.php?stkn=".parent::getVal("stkn"));
					exit;
				}

				$teacher_arr = [];
				foreach($resArray as $single_order){
					if(isset($single_order['sortlist_info']) && !empty($single_order['sortlist_info'])){
						array_push($teacher_arr,$single_order['sortlist_info']);
					}
				}

				$teacher_arr = array_unique($teacher_arr);

				$bindHtml.= "
							<html>
								<head>
									<style type = 'text/css'>
										@import url('https://fonts.googleapis.com/css?family=Oswald&display=swap');
										@page {
										  margin: 0 ;
										}

										@media print {
										  @page {
										   size: 215.8mm 297.4mm;
										  height: 279.4mm;
										  width: 215.8mm;
										  }
										}
										.teacher-tbl-td{
											font-size:60px;
											width: 100%;
											padding-top:80px;
											padding-bottom:80px;
											text-align: center;
											vertical-align: middle;
											font-family: 'Oswald', sans-serif;
										}
									</style>
								</head>
							<body>";
				$bindHtml.="<table style='width:100%;'>";
				//$bindHtml.="<tr><td class='teacher-tbl-td'>Teacher Name</td></tr>";
				
				foreach($teacher_arr as $single_teacher){
					$bindHtml.="<tr>";
					$bindHtml.="<td class='teacher-tbl-td'>".$single_teacher."</td>";
					$bindHtml.="</tr>";
				}
				$bindHtml.="</table>
							</body>
						</html>";

				require_once("html-templates/custom/dompdf/vendor/autoload.php");

				$dompdf = new \Dompdf\Dompdf();
				$dompdf->loadHtml($bindHtml);
				// $dompdf->setPaper('A4', 'portrait');
				// Set the custom size for 4x6 inches
	            $customPaper = array(0, 0, 288, 432); // 4x6 inches in points (72 points per inch)
	            $dompdf->setPaper($customPaper);
				$dompdf->render();
				$dompdf->stream($store_name.' Teacher PDF.pdf',array("Attachment" => false));
				exit(0);
			}
		}
	}

	//Teacher PDF After 12-09-2024 text cut issue resolved
	function exportTeacherPdf(){
	    if(parent::isGET()){
	        if(!empty(parent::getVal("method")) && parent::getVal("method") == "exportTeacherPdf"){
	            $bindHtml = '';
	            $storeMasterId = parent::getVal("store_master_id");
	            $store_sale_type_id = parent::getVal("store_sale_type_id");
	            $lableStartDate_ts = $lableEndDate_ts = '';
	            $lableStartDate = parent::getVal("lable_start_date");
	            if(!empty($lableStartDate)){
	                $lableStartDate_ts = strtotime($lableStartDate.' 0:0');
	            }
	            $lableEndDate = parent::getVal("lable_end_date");
	            if(!empty($lableEndDate)){
	                $lableEndDate_ts = strtotime($lableEndDate.' 23:59');
	            }

	            $resArray = parent::get_orders_f_mdl($storeMasterId,$lableStartDate_ts,$lableEndDate_ts,$store_sale_type_id);
	            $store_name = $resArray[0]['store_name'];
	            $store_name = str_replace("/"," ",$store_name);
	            if(empty($resArray)) {
	                $_SESSION['SUCCESS'] = 'FALSE';
	                $_SESSION['MESSAGE'] = 'No record found.';
	                header("Location: sa-labels.php?stkn=".parent::getVal("stkn"));
	                exit;
	            }

	            // Modified to store order count with teacher info
	            $teacher_arr = [];
	            foreach($resArray as $single_order){
	                if(isset($single_order['sortlist_info']) && !empty($single_order['sortlist_info'])){
	                    $teacher_arr[$single_order['sortlist_info']] = isset($single_order['order_count']) ? $single_order['order_count'] : 1;
	                }
	            }

	            $bindHtml .= "
	                <html>
	                    <head>
	                        <style type = 'text/css'>
	                            @import url('https://fonts.googleapis.com/css?family=Oswald&display=swap');
	                            @page {
	                                margin: 0;
	                            }
	                            @media print {
	                                @page {
	                                    size: 215.8mm 297.4mm;
	                                }
	                            }
	                            .teacher-tbl-td {
	                                font-size: 60px;
	                                width: 100%;
	                                padding-top: 80px;
	                                padding-bottom: 0px;
	                                text-align: center;
	                                vertical-align: middle;
	                                word-wrap: break-word;
	                                font-family: 'Oswald', sans-serif;
	                            }
	                            .multiple-orders {
	                                font-size: 20px;  /* Smaller font for the multiple orders text */
	                                text-align: center;
	                                padding-bottom: 40px;
	                                font-family: 'Oswald', sans-serif;
	                            }
	                            .page-break {
	                                page-break-after: always;
	                            }
	                        </style>
	                    </head>
	                <body>";

	            foreach($teacher_arr as $teacher_name => $order_count){
	                $bindHtml .= "<table style='width:100%;'>";
	                $bindHtml .= "<tr><td class='teacher-tbl-td'>".$teacher_name."</td></tr>";
	                if($order_count > 1){
	                    $bindHtml .= "<tr><td class='multiple-orders'>Multiple Orders Enclosed<br>Open Before Distribution</td></tr>";
	                }
	                $bindHtml .= "</table>";
	                $bindHtml .= "<div class='page-break'></div>";
	            }

	            $bindHtml .= "
	                </body>
	            </html>";

	            require_once("html-templates/custom/dompdf/vendor/autoload.php");
	            $dompdf = new \Dompdf\Dompdf();
	            $dompdf->loadHtml($bindHtml);
	            $customPaper = array(0, 0, 288, 432); // 4x6 inches in points
	            $dompdf->setPaper($customPaper);
	            $dompdf->render();
	            $dompdf->stream($store_name.' Teacher PDF.pdf', array("Attachment" => false));
	            exit(0);
	        }
	    }
	}
	
	/* Task 88 start*/
	function generateChecklistReport(){
		if(parent::isGET()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "generateChecklistReport"){
				$bindHtml           = '';
				$store_master_id    = parent::getVal("store_master_id");
				$store_sale_type_id = parent::getVal("store_sale_type_id");
				
				$store_sale_type = '';
				if($store_sale_type_id==1){
					$store_sale_type = 'Flash Sale';
				}
				else{
					$store_sale_type = 'On-Demand';
				}

				$lable_start_date = parent::getVal("lable_start_date");
				$lable_end_date   = parent::getVal("lable_end_date");

				$cond_start_date     = '';
				if(isset($lable_start_date) && !empty($lable_start_date)){
					$cond_start_date = ' AND store_orders_master.created_on_ts>="'.strtotime($lable_start_date.' 0:0').'"';
				}
				$cond_end_date       = '';
				if(isset($lable_end_date) && !empty($lable_end_date)){
					$cond_end_date   = ' AND store_orders_master.created_on_ts<="'.strtotime($lable_end_date.' 23:59').'"';
				}

				$storeCondition      = '';
				if($store_master_id == 'All'){					
					$storeCondition = "AND store_orders_master.store_sale_type='".$store_sale_type."'";
				}

				if($store_master_id > 0){					
					$storeCondition = " AND store_orders_master.store_master_id = '".$store_master_id."'";
				}

				/* Task 100 Add new field personlization in sql*/
				$sql = 'SELECT store_orders_master.*, 
				CASE
					WHEN store_orders_master.order_type = 1 THEN store_orders_master.shop_order_number
					WHEN store_orders_master.order_type = 2 THEN CONCAT_WS("-","MO",store_orders_master.manual_order_number)
					ELSE CONCAT_WS("-","QB",store_orders_master.quickbuy_order_number)
				END AS shop_order_number, store_master.store_fulfillment_type,soim.store_orders_master_id,soim.is_deleted,soim.title,soim.quantity,soim.variant_title,soim.personalization_name,soim.personalization_item_name FROM `store_orders_master` INNER JOIN store_master ON store_orders_master.store_master_id = store_master.id INNER JOIN store_order_items_master as soim ON store_orders_master.id = soim.store_orders_master_id WHERE 1 
				AND is_order_cancel = 0
				AND soim.is_deleted = 0
				'.$cond_start_date.'
				'.$cond_end_date.'
				'.$storeCondition.'
				ORDER BY student_name ASC;
				';
				$list_data = parent::selectTable_f_mdl($sql);

				$store_name_get='SELECT store_name FROM store_master WHERE id='.$store_master_id.' ';
				$store_name_data = parent::selectTable_f_mdl($store_name_get);
				$store_name=$store_name_data[0]['store_name'];
				$store_name=str_replace("/"," ",$store_name);

				if(empty($list_data)) {
					$_SESSION['SUCCESS'] = 'FALSE';
					$_SESSION['MESSAGE'] = 'No record found.';
					header("Location: sa-labels.php?stkn=".parent::getVal("stkn"));
					exit;
				}
				
				$bindHtml .="<html>
								<head>
									<style type = 'text/css'>
										@import url('https://fonts.googleapis.com/css?family=Oswald&display=swap');
										table, td, th {
										  border: 1px solid black;
										}

										table {
										  border-collapse: collapse;
										  width: 100%;
										  text-align:center;
										}

										th {
										  text-align:center;
										}
									</style>
								</head>
							<body>";
				$bindHtml .='<div class="row">
								<div class="col-lg-12">
								 <div class="table-responsive">
        		<table class=" table table-bordered table-hover">
			      	<thead>
				        <tr>
				          <th scope="col">Order#</th>
				          <th scope="col">Item Name</th>
				          <th scope="col">Size</th>
				          <th scope="col">Color</th>
				          <th scope="col">Quantity</th>
				          <th scope="col">Teacher Name</th>
				          <th scope="col">Student Name</th>
				          <th scope="col">Personalization</th>
				          <th scope="col">Custom Field</th>
				          <th scope="col">Received?</th>
				        </tr>
			      	</thead>';/* Task 100 Add new th personlization*/
			    $bindHtml.='<tbody>';
				if(!empty($list_data)){
					foreach ($list_data as $value) {
						$array         =  explode(" / ", $value['variant_title'], 2);
						$variant_color = !empty($array[1])?$array[1] : '';
						$variant_size  = !empty($array[0])?$array[0] : '';
				        $bindHtml
						.='<tr>
						  <td scope="row">'.$value['shop_order_number'].'</td>
						  <td>'.$value['title'].'</td>
						  <td>'.$variant_size.'</td>
						  <td>'.$variant_color.'</td>
						  <td>'.$value['quantity'].'</td>
						  <td>'.$value['sortlist_info'].'</td>
						  <td>'.$value['student_name'].'</td>
						  <td>'.$value['personalization_name'].'</td>
						  <td>'.$value['personalization_item_name'].'</td>
						  <td></td>
						</tr>';/* Task 100 Add new td personlization*/
				    }
				$bindHtml.='<tbody>';
   			$bindHtml .='</table>
				    </div>
						</div>
						</div>
						</body>
						</html>';
				
					require_once("html-templates/custom/dompdf/vendor/autoload.php");

					$dompdf = new \Dompdf\Dompdf();
					$dompdf->loadHtml($bindHtml);
					$dompdf->setPaper('A4', 'landscape');
					$dompdf->render();
					$dompdf->stream($store_name.' Checklist Report.pdf',array("Attachment" => false));
					exit(0);
				}
			}
		}
	}
	/* Task 88 end*/

	/*
	function exportPdf1(){
		if(parent::isGET()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "exportPdf"){
				$bindHtml = '';
				$storeMasterId = parent::getVal("store_master_id");

				$lableStartDate_ts = $lableEndDate_ts = '';
				$lableStartDate = parent::getVal("lable_start_date");
				if(!empty($lableStartDate)){
					$lableStartDate_ts = strtotime($lableStartDate.' 0:0');
				}
				$lableEndDate = parent::getVal("lable_end_date");
				if(!empty($lableEndDate)){
					$lableEndDate_ts = strtotime($lableEndDate.' 23:59');
				}
				
				$resArray = parent::get_orders_f_mdl($storeMasterId,$lableStartDate_ts,$lableEndDate_ts);
				if(empty($resArray)) {
					$_SESSION['SUCCESS'] = 'FALSE';
					$_SESSION['MESSAGE'] = 'No record found.';
					header("Location: sa-labels.php?stkn=".parent::getVal("stkn"));
					exit;
				}
				
				$chunked_arr = array_chunk($resArray,3);
				
				$bindHtml.= "
							<html>
								<head>
									<style type = 'text/css'>
										@page {
										  margin: 15.6mm 4.6mm ;
										}

										@media print {
										  @page {
										   size: 215.8mm 297.4mm;
										  height: 279.4mm;
										  width: 215.8mm;
										  }
										}
										td {
										 width: 60.1mm;
										 height:20.3mm;
										 font-size:9pt;
										}
										.lable_header_text{
											 font-weight: bold
										}
									</style>
								</head>
							<body>";
				$bindHtml.="<table style='width:100%;'>";
				foreach($chunked_arr as $single_chunk){
					$bindHtml.="<tr>";

					if(isset($single_chunk[0]['json_data']) && !empty($single_chunk[0]['json_data'])){
						$order_arr = json_decode($single_chunk[0]['json_data'],1);
						$sort_list_name = $student_name = '';

						$item_name  = $order_arr['line_items'][0]['title'];
						$item_quantity = $order_arr['line_items'][0]['quantity'];

						if(!empty($order_arr['line_items'][0]['properties'])){
							foreach($order_arr['line_items'][0]['properties'] as $single_prop) {
								if($single_prop['name']=='sort-list-name') {
									$sort_list_name = $single_prop['value'];
								} else if($single_prop['name']=='student-name') {
									$student_name = $single_prop['value'];
								}
							}
						}

						if($item_quantity > 1){
							$style_tag = 'color:red;';
						} else{
							$style_tag = '';
						}

						$bindHtml.="<td style='padding: 1.5mm 2.1mm 1.5mm 1.5mm;".$style_tag."'>
										<span class='lable_header_text' >Name:</span><span> ".$sort_list_name."</span><br>
										<span class='lable_header_text'>Teacher:</span><span> ".$student_name."</span><br>
										<span class='lable_header_text'>Item Name:</span><span> ".$item_name."</span><br>
										<span class='lable_header_text'>Item Quantity:</span><span> ".$item_quantity."</span>
									</td>";
					}else{
						$bindHtml.="<td style='padding: 1.5mm 2.1mm 1.5mm 1.5mm;'><span class='lable_header_text'>&nbsp;</span></td>";
					}
					if(isset($single_chunk[1]['json_data']) && !empty($single_chunk[1]['json_data'])){
						$order_arr = json_decode($single_chunk[1]['json_data'],1);
						$sort_list_name = $student_name = '';

						$item_name  = $order_arr['line_items'][0]['title'];
						$item_quantity = $order_arr['line_items'][0]['quantity'];

						if(!empty($order_arr['line_items'][0]['properties'])){
							foreach($order_arr['line_items'][0]['properties'] as $single_prop) {
								if($single_prop['name']=='sort-list-name') {
									$sort_list_name = $single_prop['value'];
								} else if($single_prop['name']=='student-name') {
									$student_name = $single_prop['value'];
								}
							}
						}

						if($item_quantity > 1){
							$style_tag = 'color:red;';
						} else{
							$style_tag = '';
						}

						$bindHtml.="<td style='padding: 1.5mm 2.1mm 1.5mm 3.6mm;".$style_tag."'>
										<span class='lable_header_text' >Name:</span><span> ".$sort_list_name."</span><br>
										<span class='lable_header_text'>Teacher:</span><span> ".$student_name."</span><br>
										<span class='lable_header_text'>Item Name:</span><span> ".$item_name."</span><br>
										<span class='lable_header_text'>Item Quantity:</span><span> ".$item_quantity."</span>
									</td>";
					}else{
						$bindHtml.="<td style='padding: 1.5mm 2.1mm 1.5mm 1.5mm;'><span class='lable_header_text'>&nbsp;</span></td>";
					}
					if(isset($single_chunk[2]['json_data']) && !empty($single_chunk[2]['json_data'])){
						$order_arr = json_decode($single_chunk[2]['json_data'],1);
						$sort_list_name = $student_name = '';

						$item_name  = $order_arr['line_items'][0]['title'];
						$item_quantity = $order_arr['line_items'][0]['quantity'];

						if(!empty($order_arr['line_items'][0]['properties'])){
							foreach($order_arr['line_items'][0]['properties'] as $single_prop) {
								if($single_prop['name']=='sort-list-name') {
									$sort_list_name = $single_prop['value'];
								} else if($single_prop['name']=='student-name') {
									$student_name = $single_prop['value'];
								}
							}
						}

						if($item_quantity > 1){
							$style_tag = 'color:red;';
						} else{
							$style_tag = '';
						}

						$bindHtml.="<td style='padding: 1.5mm 2.1mm 1.5mm 4.4mm;".$style_tag."'>
										<span class='lable_header_text' >Name:</span><span> ".$sort_list_name."</span><br>
										<span class='lable_header_text'>Teacher:</span><span> ".$student_name."</span><br>
										<span class='lable_header_text'>Item Name:</span><span> ".$item_name."</span><br>
										<span class='lable_header_text'>Item Quantity:</span><span> ".$item_quantity."</span>
									</td>";
					}else{
						$bindHtml.="<td style='padding: 1.5mm 2.1mm 1.5mm 1.5mm;'><span class='lable_header_text'>&nbsp;</span></td>";
					}
					$bindHtml.="</tr>";
				}

				$bindHtml.="</table>
							</body>
						</html>";
				
				require_once("html-templates/custom/dompdf/vendor/autoload.php");
				
				$paper_size = array(0,0,612.00,792.00);
				
				$dompdf = new \Dompdf\Dompdf();
				$dompdf->loadHtml($bindHtml);
				$dompdf->setPaper($paper_size);
				$dompdf->render();
				$dompdf->stream('spirithero-label-'.time().'.pdf');
			}
		}
	}
	*/
	public function export_order_by_search(){
		global $s3Obj;//Task 59
		$status = false;
		if(parent::isGET()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "export_order_by_search"){
				$cond_start_date = '';
				if(isset($_POST['lable_start_date']) && !empty($_POST['lable_start_date'])){
					$cond_start_date = ' AND store_orders_master.created_on_ts>="'.strtotime($_POST['lable_start_date'].' 0:0').'"';
				}
				$cond_end_date = '';
				if(isset($_POST['lable_end_date']) && !empty($_POST['lable_end_date'])){
					$cond_end_date = ' AND store_orders_master.created_on_ts<="'.strtotime($_POST['lable_end_date'].' 23:59').'"';
				}
				$store_sale_type_id = parent::getVal("store_sale_type_id");
				
				$store_sale_type = '';
				if($store_sale_type_id==1){
					$store_sale_type = 'Flash Sale';
				}
				else{
					$store_sale_type = 'On-Demand';
				}

				$storeCondition = '';
				if($store_master_id = 'All'){					
					$storeCondition = "AND soim.store_sale_type='".$store_sale_type."'";
				}
				$store_master_id = $_POST['store_master_id'];
				if($store_master_id > 0){					
					$storeCondition = " AND soim.store_master_id = '".$store_master_id."'";
				}
				
				$groupSql = 'SELECT store_owner_product_master.group_name , soim.id, soim.store_master_id,soim.is_deleted,soim.item_update_status,store_orders_master.is_order_cancel FROM `store_order_items_master` as soim INNER JOIN store_owner_product_master ON store_owner_product_master.id = soim.store_owner_product_master_id INNER JOIN store_orders_master ON store_orders_master.id = soim.store_orders_master_id WHERE is_deleted = 0  
				
				'.$storeCondition.' Group By store_owner_product_master.group_name ORDER BY store_owner_product_master.group_name ASC
				';
				$groupData = parent::selectTable_f_mdl($groupSql);
				$resultArray = array();

				$store_name_get='SELECT store_name FROM store_master WHERE id='.$store_master_id.' ';
				$store_name_data = parent::selectTable_f_mdl($store_name_get);
				$store_name=$store_name_data[0]['store_name'];
				$store_name=str_replace("/"," ",$store_name);
				// $export_file = time() . '-export.csv';
				$export_file = $store_name . ' Order Report.csv';
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
				// print_r($groupData);die;
				if(!empty($groupData)){
					foreach($groupData as $groupName){
							$gname = $groupName['group_name'];
							$list_data = array();
							$order_arr = array();
							
						$sql = 'SELECT store_orders_master.*, 
						CASE
							WHEN store_orders_master.order_type = 1 THEN store_orders_master.shop_order_number
							WHEN store_orders_master.order_type = 2 THEN CONCAT_WS("-","MO",store_orders_master.manual_order_number)
							ELSE CONCAT_WS("-","QB",store_orders_master.quickbuy_order_number)
						END AS shop_order_number, soim.id as itemID, store_master.store_fulfillment_type,store_owner_product_master.group_name FROM `store_orders_master` INNER JOIN store_master ON store_orders_master.store_master_id = store_master.id INNER JOIN store_order_items_master as soim ON store_orders_master.id = soim.store_orders_master_id INNER JOIN store_owner_product_master ON store_owner_product_master.id = soim.store_owner_product_master_id WHERE 1 AND store_owner_product_master.group_name = "'.$gname.'" AND is_deleted = 0 
								'.$cond_start_date.'
								'.$cond_end_date.'
								'.$storeCondition.' ORDER BY store_orders_master.shop_order_number DESC
								';

							$list_data = parent::selectTable_f_mdl($sql);

							if(!empty($list_data)){
								fputcsv($file_for_export_data,
									['Group Name',$gname]
								);
								fputcsv($file_for_export_data,
									['Order #','Order Date','Item Name','Size','Color','Quantity','SKU','Teacher name','Student Name','Purchaser Email','Purchaser Name','Store Type' ,'Fulfillment Type','Personalization Name','Custom Field','Fundraising Status','Fundraising Amount','Order Status','Item Status']
								);

								foreach($list_data as $single_order){
									//Task 57 20/10/2021 add new condition is_deleted = 0
									$sql = 'SELECT * FROM `store_order_items_master`
											WHERE  is_deleted = 0 AND id = "'.$single_order['itemID'].'" AND store_orders_master_id =
											'.$single_order['id'].'';
									
									$order_arr = parent::selectTable_f_mdl($sql);

									$sort_list_name = $student_name ='';

									$sort_list_name = $single_order['sortlist_info'];
									$student_name = $single_order['student_name'];

									$order_date = $single_order['created_on'];// New 25/09/2021
									$date       = new DateTime($order_date);
									$order_date = $date->format("m/d/Y h:i A");

									$order_status='';
									if($single_order['is_order_cancel']=='1'){
										$order_status="Cancelled";
									}

									/* Task 34 start */
									$store_fulfillment_type = '';
									if($single_order['store_fulfillment_type'] == 'SHIP_1_LOCATION_NOT_SORT'){
										$store_fulfillment_type = 'Silver';
									}
									else if($single_order["store_fulfillment_type"] == 'SHIP_1_LOCATION_SORT'){
										$store_fulfillment_type = 'Gold';
									}
									else{
										$store_fulfillment_type = 'Platinum';
									}

									$getVariantsIDArr = array();
									if(count($order_arr) > 0){
										
										foreach($order_arr as $single_item){

											/* Task 61 start 01/11/2021*/
											$array =  explode(" / ", $single_item['variant_title'], 2);
											$variant_color     = !empty($array[1])?$array[1]:'';
											$variant_size      = !empty($array[0])?$array[0]:'';
											/* Task 61 end 01/11/2021*/
											$fundraising_amount = number_format((float)$single_item['fundraising_amount'], 2);
											$store_fund_amount = number_format((float)$single_item['store_fund_amount'], 2);
											$item_status='';
											if($single_item['is_deleted']=='1'){
												$item_status='Deleted';
												$fundraising_amount='0.00';
											}

											if($single_order['is_order_cancel']=='1'){
												$fundraising_amount='0.00';
											}

											
											//here we skip first record, because it is already include above
											$getVariantsIDArr[] = $single_item['shop_variant_id'];
											fputcsv($file_for_export_data,
												[
													trim($single_order['shop_order_number']),
													trim($order_date),// New 25/09/2021
													trim($single_item['title']),
													trim($variant_size),
													trim($variant_color),
													// Task 61 end
													trim($single_item['quantity']),
													trim($single_item['sku']),
													trim($sort_list_name),
													trim($student_name),
													trim($single_order['cust_email']),
													trim($single_order['cust_name']),
													trim(explode(':',$single_order['store_sale_type'])[0]),
													trim($store_fulfillment_type),//Task 34
													trim($single_item['personalization_name']),
													trim($single_item['personalization_item_name']),
													trim($single_item['fundraising_status']),
													trim("$".$fundraising_amount),
													trim($order_status),
													trim($single_item['item_update_status'])
													
												]
											);
										}

										
									}
									
								}

								fputcsv($file_for_export_data,
												['']
										);
							}
					}

					$status = true;
				}

				
				/* DELETE PRODUCT SECTION START*/

					$sqlDelete = 'SELECT store_orders_master.*, 
					CASE
						WHEN store_orders_master.order_type = 1 THEN store_orders_master.shop_order_number
						WHEN store_orders_master.order_type = 2 THEN CONCAT_WS("-","MO",store_orders_master.manual_order_number)
						ELSE CONCAT_WS("-","QB",store_orders_master.quickbuy_order_number)
					END AS shop_order_number, soim.id as itemID, store_master.store_fulfillment_type 
						FROM `store_orders_master` 
						INNER JOIN store_master ON store_orders_master.store_master_id = store_master.id 
						INNER JOIN store_order_items_master as soim ON store_orders_master.id = soim.store_orders_master_id 
						WHERE 1 AND soim.store_owner_product_master_id NOT IN (SELECT store_owner_product_master.id FROM store_owner_product_master WHERE store_master_id = "'.$store_master_id.'" ) AND is_deleted = 0 
                        '.$cond_start_date.'
                        '.$cond_end_date.'
                        '.$storeCondition.' ORDER BY store_orders_master.shop_order_number DESC
                        ';
					$dataDelete = parent::selectTable_f_mdl($sqlDelete);

					if(!empty($dataDelete)){
						fputcsv($file_for_export_data,
                    		['Deleted Product Orders','']
						);
						fputcsv($file_for_export_data,
							['Order #','Order Date','Item Name','Size','Color','Quantity','SKU','Teacher name','Student Name','Purchaser Email','Purchaser Name','Store Type' ,'Fulfillment Type','Personalization Name','Custom Field','Fundraising Status','Fundraising Amount','Order Status','Item Status']
						);

						foreach($dataDelete as $single_order){
							//Task 57 20/10/2021 add new condition is_deleted = 0
							$sql = 'SELECT * FROM `store_order_items_master`
									WHERE is_deleted = 0 AND id = "'.$single_order['itemID'].'" AND store_orders_master_id =
									'.$single_order['id'].'';
							
							$order_arr = parent::selectTable_f_mdl($sql);
							$sort_list_name = $student_name ='';
							$sort_list_name = $single_order['sortlist_info'];
							$student_name = $single_order['student_name'];
							$order_date = $single_order['created_on'];// New 25/09/2021
							$date       = new DateTime($order_date);
							$order_date = $date->format("m/d/Y h:i A");

							$order_status='';
							if($single_order['is_order_cancel']=='1'){
								$order_status="Cancelled";
							}
		
							/* Task 34 start */
							$store_fulfillment_type = '';
							if($single_order['store_fulfillment_type'] == 'SHIP_1_LOCATION_NOT_SORT'){
								$store_fulfillment_type = 'Silver';
							}
							else if($single_order["store_fulfillment_type"] == 'SHIP_1_LOCATION_SORT'){
								$store_fulfillment_type = 'Gold';
							}
							else{
								$store_fulfillment_type = 'Platinum';
							}
		
							$getVariantsIDArr = array();
							if(count($order_arr) > 0){
								foreach($order_arr as $single_item){
		
									/* Task 61 start 01/11/2021*/
									$array =  explode(" / ", $single_item['variant_title'], 2);
									$variant_color     = !empty($array[1])?$array[1]:'';
									$variant_size      = !empty($array[0])?$array[0]:'';
									/* Task 61 end 01/11/2021*/
									$fundraising_amount = number_format((float)$single_item['fundraising_amount'], 2);
									$store_fund_amount = number_format((float)$single_item['store_fund_amount'], 2);
									//here we skip first record, because it is already include above
									$getVariantsIDArr[] = $single_item['shop_variant_id'];
									$item_status='';
									if($single_item['is_deleted']=='1'){
										$item_status='Deleted';
										$fundraising_amount='0.00';
									}

									if($single_order['is_order_cancel']=='1'){
										$fundraising_amount='0.00';
									}

									fputcsv($file_for_export_data,
										[
											trim($single_order['shop_order_number']),
											trim($order_date),// New 25/09/2021
											trim($single_item['title']),
											trim($variant_size),
											trim($variant_color),
											// Task 61 end
											trim($single_item['quantity']),
											trim($single_item['sku']),
											trim($sort_list_name),
											trim($student_name),
											trim($single_order['cust_email']),
											trim($single_order['cust_name']),
											trim(explode(':',$single_order['store_sale_type'])[0]),
											trim($store_fulfillment_type),//Task 34
											trim($single_item['personalization_name']),
											trim($single_item['personalization_item_name']),
											trim($single_item['fundraising_status']),
											trim("$".$fundraising_amount),
											trim($order_status),
											trim($single_item['item_update_status'])
										]
									);
								}	
							}	
						}
						fputcsv($file_for_export_data,
                                ['']
                        );
						$status = true;
					}
					
				/* DELETE PRODUCT SECTION END*/

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

	public function exportFundReport(){
		global $s3Obj;//Task 59
		$status = false;
		if(parent::isGET()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "export_fund_report"){
				$cond_start_date = '';
				if(isset($_POST['lable_start_date']) && !empty($_POST['lable_start_date'])){
					$cond_start_date = ' AND store_orders_master.created_on_ts>="'.strtotime($_POST['lable_start_date'].' 0:0').'"';
				}
				$cond_end_date = '';
				if(isset($_POST['lable_end_date']) && !empty($_POST['lable_end_date'])){
					$cond_end_date = ' AND store_orders_master.created_on_ts<="'.strtotime($_POST['lable_end_date'].' 23:59').'"';
				}
				$store_sale_type_id = parent::getVal("store_sale_type_id");
				
				$store_sale_type = '';
				if($store_sale_type_id==1){
					$store_sale_type = 'Flash Sale';
				}
				else{
					$store_sale_type = 'On-Demand';
				}

				$storeCondition = '';
				if($store_master_id = 'All'){					
					$storeCondition = "AND soim.store_sale_type='".$store_sale_type."'";
				}
				$store_master_id = $_POST['store_master_id'];
				if($store_master_id > 0){					
					$storeCondition = " AND soim.store_master_id = '".$store_master_id."'";
				}
				
				$resultArray = array();

				$store_name_get='SELECT store_name FROM store_master WHERE id='.$store_master_id.' ';
				$store_name_data = parent::selectTable_f_mdl($store_name_get);

				$store_name=$store_name_data[0]['store_name'];
				$store_name=str_replace("/"," ",$store_name);
				
				$export_file = $store_name . ' Fundraising Report.csv';
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
				// print_r($groupData);die;
				
				$list_data = array();
				$order_arr = array();
							
				$sql = 'SELECT store_orders_master.*, 
					CASE
						WHEN store_orders_master.order_type = 1 THEN store_orders_master.shop_order_number
						WHEN store_orders_master.order_type = 2 THEN CONCAT_WS("-","MO",store_orders_master.manual_order_number)
						ELSE CONCAT_WS("-","QB",store_orders_master.quickbuy_order_number)
					END AS shop_order_number, soim.id as itemID,soim.fundraising_status,soim.fundraising_amount,soim.is_deleted,soim.item_update_status FROM `store_orders_master` INNER JOIN store_master ON store_orders_master.store_master_id = store_master.id INNER JOIN store_order_items_master as soim ON store_orders_master.id = soim.store_orders_master_id WHERE 1 AND is_deleted = 0
				'.$cond_start_date.'
				'.$cond_end_date.'
				'.$storeCondition.' ORDER BY store_orders_master.shop_order_number DESC
				';

				$list_data = parent::selectTable_f_mdl($sql);

				if(!empty($list_data)){
					
					fputcsv($file_for_export_data,
						['Order #','Order Date','Fundraising Status','Fundraising Amount','Order Status','Item Status']
					);
					$total_amount="0.00";
					foreach($list_data as $single_order){
						//Task 57 20/10/2021 add new condition is_deleted = 0
						$order_date = $single_order['created_on'];// New 25/09/2021
						$date       = new DateTime($order_date);
							$order_date = $date->format("m/d/Y h:i A");
						$order_status='';
						if($single_order['is_order_cancel']=='1'){
							$order_status="Cancelled";
						}
						$item_status='';
						if($single_order['is_deleted']=='1'){
							$item_status='Deleted';
						}
						
						$fundraising_amount = number_format((float)$single_order['fundraising_amount'], 2);
						$fundraising_amount = str_replace(",","",$fundraising_amount);
						if($single_order['is_order_cancel']=='1' || $single_order['is_deleted']=='1'){
							$fundraising_amount='0.00';
						}
						$total_amount += $fundraising_amount;		
						fputcsv($file_for_export_data,
							[
								trim($single_order['shop_order_number']),
								trim($order_date),
								trim($single_order['fundraising_status']),
								trim("$".$fundraising_amount),
								trim($order_status),
								trim($single_order['item_update_status'])
							]
						);	
					}
					$total_fundamount = number_format((float)$total_amount, 2);
					$total_amount = str_replace(",","",$total_fundamount);

					fputcsv($file_for_export_data,
						['','','Total Amount',trim("$".$total_amount)]
					);

					fputcsv($file_for_export_data,
						['']
					);

					$status = true;
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

	function get_stores(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "get_stores"){
				$storeSaleType = parent::getVal("store_sale_type");
				$resultArray = array();

				$storeRespArray = parent::getStores($storeSaleType);
				
				$dropdownHtml = '';
				$dropdownHtml .= '<option value="">All</option>';
				foreach($storeRespArray as $store){
					$dropdownHtml .= '<option value="'.$store['id'].'">'.$store['store_name'].'</option>';
				}
				echo $dropdownHtml;die;
			}
		} 
	}
	/* Task 105 start */
	public function generatePersonalizationReport(){
		global $s3Obj;
		if(parent::isGET()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "generatePersonalizationReport"){
								$store_master_id    = parent::getVal("store_master_id");
				$store_sale_type_id = parent::getVal("store_sale_type_id");
				
				$store_sale_type = '';
				if($store_sale_type_id==1){
					$store_sale_type = 'Flash Sale';
				}
				else{
					$store_sale_type = 'On-Demand';
				}

				$lable_start_date = parent::getVal("lable_start_date");
				$lable_end_date   = parent::getVal("lable_end_date");

				$cond_start_date     = '';
				if(isset($lable_start_date) && !empty($lable_start_date)){
					$cond_start_date = ' AND store_orders_master.created_on_ts>="'.strtotime($lable_start_date.' 0:0').'"';
				}
				$cond_end_date       = '';
				if(isset($lable_end_date) && !empty($lable_end_date)){
					$cond_end_date   = ' AND store_orders_master.created_on_ts<="'.strtotime($lable_end_date.' 23:59').'"';
				}

				$storeCondition      = '';
				if($store_master_id == 'All'){					
					$storeCondition = "AND store_orders_master.store_sale_type='".$store_sale_type."'";
				}

				if($store_master_id > 0){					
					$storeCondition = " AND store_orders_master.store_master_id = '".$store_master_id."'";
				}

				/* Task 100 Add new field personlization in sql*/
				$sql = 'SELECT store_orders_master.*, 
				CASE
					WHEN store_orders_master.order_type = 1 THEN store_orders_master.shop_order_number
					WHEN store_orders_master.order_type = 2 THEN CONCAT_WS("-","MO",store_orders_master.manual_order_number)
					ELSE CONCAT_WS("-","QB",store_orders_master.quickbuy_order_number)
				END AS shop_order_number, soim.quantity,store_master.store_fulfillment_type,soim.store_orders_master_id,soim.is_deleted,soim.title,soim.variant_title,soim.personalization_name,soim.personalization_item_name,soim.sku FROM `store_orders_master` INNER JOIN store_master ON store_orders_master.store_master_id = store_master.id INNER JOIN store_order_items_master as soim ON store_orders_master.id = soim.store_orders_master_id WHERE 1 
				AND (soim.personalization_name!="" OR soim.personalization_item_name IS NOT NULL)
				AND is_order_cancel = 0
				AND soim.is_deleted = 0
				'.$cond_start_date.'
				'.$cond_end_date.'
				'.$storeCondition.'';
				$list_data = parent::selectTable_f_mdl($sql);

				$store_name_get='SELECT store_name FROM store_master WHERE id='.$store_master_id.' ';
				$store_name_data = parent::selectTable_f_mdl($store_name_get);
				$store_name=$store_name_data[0]['store_name'];
				$store_name=str_replace("/"," ",$store_name);

				$resultArray = array();
				
				if(!empty($list_data)){
					$export_file = $store_name . ' Personalization Report.csv';
					$export_file_path = 'image_uploads/_export/' . $export_file;
					$export_file_url = common::IMAGE_UPLOAD_URL.'_export/' . $export_file;
				
					// $aws_path = common::IMAGE_UPLOAD_S3_PATH.'_export/' . $export_file;//Task 59
					
					header('Content-Encoding: UTF-8');
					header('Content-type: text/csv; charset=UTF-8');
					header('Content-Transfer-Encoding: binary');
					header('Content-type: application/csv');
					header('Content-Disposition: attachment; filename='.$export_file_url);
					$file_for_export_data = fopen($export_file_path,"w");
					fputcsv($file_for_export_data,
						['Order #','Personalization Name','Custom Field']
					);

					foreach($list_data as $single_order){
						for ($x = 1; $x <= $single_order['quantity']; $x++) {
							$sort_list_name = $student_name ='';

							$sort_list_name = $single_order['sortlist_info'];
							$student_name = $single_order['student_name'];

							$order_date = $single_order['created_on'];// New 25/09/2021
							$date       = new DateTime($order_date);
							$order_date = $date->format("m/d/Y h:i A");

							/* Task 34 start */
							$store_fulfillment_type = '';
							if($single_order['store_fulfillment_type'] == 'SHIP_1_LOCATION_NOT_SORT'){
								$store_fulfillment_type = 'Silver';
							}
							else if($single_order["store_fulfillment_type"] == 'SHIP_1_LOCATION_SORT'){
								$store_fulfillment_type = 'Gold';
							}
							else{
								$store_fulfillment_type = 'Platinum';
							}

							
							$array =  explode(" / ", $single_order['variant_title'], 2);
							$variant_color     = !empty($array[1])?$array[1]:'';
							$variant_size      = !empty($array[0])?$array[0]:'';

							fputcsv($file_for_export_data,
								[
									$single_order['shop_order_number'],
									htmlentities($single_order['personalization_name'], ENT_QUOTES,  'UTF-8'),
									htmlentities($single_order['personalization_item_name'], ENT_QUOTES,  'UTF-8')
								]
							);
						}
					}
					fclose($file_for_export_data);
					
					$documentURL = $export_file_url;
					
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
	/* Task 105 end */

	public function exportDiscountReportBySearch(){
		global $s3Obj;//Task 59
		$status = false;
		if(parent::isGET()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "export_discount_report_by_search"){
				$cond_start_date = '';
				if(isset($_POST['lable_start_date']) && !empty($_POST['lable_start_date'])){
					$cond_start_date = ' AND created_on_ts>="'.strtotime($_POST['lable_start_date'].' 0:0').'"';
				}
				$cond_end_date = '';
				if(isset($_POST['lable_end_date']) && !empty($_POST['lable_end_date'])){
					$cond_end_date = ' AND created_on_ts<="'.strtotime($_POST['lable_end_date'].' 23:59').'"';
				}
				$store_sale_type_id = parent::getVal("store_sale_type_id");
				
				$store_sale_type = '';
				if($store_sale_type_id==1){
					$store_sale_type = 'Flash Sale';
				}
				else{
					$store_sale_type = 'On-Demand';
				}

				
				$store_master_id = $_POST['store_master_id'];
				if($store_master_id > 0){					
					$storeCondition = " AND store_master_id = '".$store_master_id."'";
				}
				
				$resultArray = array();

				$store_name_get='SELECT store_name FROM store_master WHERE id='.$store_master_id.' ';
				$store_name_data = parent::selectTable_f_mdl($store_name_get);

				$store_name=$store_name_data[0]['store_name'];
				$store_name=str_replace("/"," ",$store_name);
				
				$export_file = $store_name . ' Discount Report.csv';
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
				// print_r($groupData);die;
				
				$list_data = array();
				$order_arr = array();
							
				$sql = 'SELECT * FROM store_orders_master WHERE order_type = 1 
					'.$cond_start_date.'
					'.$cond_end_date.'
					'.$storeCondition.' ORDER BY shop_order_number DESC
				';
				$list_data = parent::selectTable_f_mdl($sql);

				if(!empty($list_data)){
					
					fputcsv($file_for_export_data,
						['Order #','Order Date','Discount Code','Discount Amount']
					);
					$total_discount_amount="0.00";
					foreach($list_data as $single_order){
						$order_date = $single_order['created_on'];
						$date       = new DateTime($order_date);
						$order_date = $date->format("m/d/Y h:i A");
						$discount_code='';
						if(!empty($single_order['discount_code'])){
							$discount_code= $single_order['discount_code'];
						}
						
						$discount_code_amount = number_format((float)$single_order['discount_code_amount'], 2);
						$discount_code_amount = str_replace(",","",$discount_code_amount);
						$total_discount_amount += $discount_code_amount;	
							
						fputcsv($file_for_export_data,
							[
								trim($single_order['shop_order_number']),
								trim($order_date),
								trim($discount_code),
								trim("$".$discount_code_amount)
							]
						);	
					}
					$total_discount = number_format((float)$total_discount_amount, 2);
					$total_discount = str_replace(",","",$total_discount);

					fputcsv($file_for_export_data,
						['','','Total Discount Amount',trim("$".$total_discount)]
					);

					fputcsv($file_for_export_data,
						['']
					);

					$status = true;
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
}
?>
