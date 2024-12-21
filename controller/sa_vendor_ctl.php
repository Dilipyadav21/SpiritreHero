<?php 
include_once 'model/sa_orders_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';
class sa_vendor_ctl extends sa_orders_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		if(isset($_REQUEST['method'])){
			$action = $_REQUEST['method'];
			if($action=='createZip'){
				$this->createZip();exit;
			}
		}
		common::CheckLoginSession();
	}

	function getStoreDropdownInfo(){
		return parent::getStoreDropdownInfo_f_mdl();die();
	}

	function get_store_owner_product_master_id($store_owner_product_master_id=0)
	{
		$sql = 'SELECT store_product_master_id  from store_owner_product_master WHERE id  =  "'.$store_owner_product_master_id.'"';
		return parent::selectTable_f_mdl($sql);
	}

	function get_order_list_post(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "orders_pagination")
			{	
				$vendor_id = (isset($_SESSION['vendor_id']))?$_SESSION['vendor_id']:0;
				$sql='SELECT * FROM store_vendors_master WHERE id = '.$vendor_id.' ';
				$getVendor = parent::selectTable_f_mdl($sql);
				$vendor_name ='';
				if(!empty($getVendor)){
					$vendor_name = (!empty($getVendor[0]['vendor_name']))?$getVendor[0]['vendor_name']:'';
				}

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
				//end fixed, no change for any module

				$cond_keyword = '';
				if(isset($keyword) && !empty($keyword)){
					$cond_keyword = "AND (
							store_orders_master.shop_order_id = '$keyword' OR
							store_orders_master.shop_order_number = '$keyword' OR
							store_orders_master.cust_email LIKE '%$keyword%' OR
							store_orders_master.cust_phone LIKE '%$keyword%' OR
							store_orders_master.cust_name LIKE '%$keyword%'
						)";
				}
				$cond_order = 'ORDER BY store_orders_master.id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}

				$selectedStore = "";
				if( (isset($_REQUEST['select_store']))&&(!empty($_REQUEST['select_store'])) ){
					$selectedStore = 'AND store_master.id = "'.$_REQUEST['select_store'].'"';
				}
				
				
				$sql="
						SELECT count(store_orders_master.id) as count
						FROM `store_orders_master`
						LEFT JOIN `store_master` ON store_master.id = store_orders_master.store_master_id INNER JOIN `store_order_items_master` ON store_order_items_master.store_orders_master_id = store_orders_master.id AND store_order_items_master.vendor='".$vendor_name."'
						WHERE 1 AND store_orders_master.order_type = 1
						$cond_keyword
						$selectedStore
					";
				$all_count = parent::selectTable_f_mdl($sql);



				$sql1="
						SELECT DISTINCT 
							store_master.store_name,
							store_orders_master.id, 
							store_orders_master.store_master_id,
							store_orders_master.shop_order_id, store_orders_master.shop_order_number, store_orders_master.total_price, 
							store_orders_master.cust_email, 
							store_orders_master.cust_name,
							store_orders_master.is_order_cancel,
							store_orders_master.created_on_ts
						FROM `store_orders_master`
						LEFT JOIN `store_master` ON store_master.id = store_orders_master.store_master_id INNER JOIN `store_order_items_master` ON store_order_items_master.store_orders_master_id = store_orders_master.id AND store_order_items_master.vendor='".$vendor_name."'
						WHERE 1 AND store_orders_master.order_type = 1 AND store_sale_type = 'On-Demand'
						$cond_keyword
						$selectedStore

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
				}else{
					$html = '';
					$html .= '<div class="row">';
					$html .= '<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';
					$html .= '<div class="table-responsive">';
					$html .= '<table class="table table-bordered table-hover">';

					$html .= '<thead>';
					$html .= '<tr>';
					$html .= '<th><input type="checkbox" id="ckbCheckAll"></td></th>';
					$html .= '<th>#</th>';
					$html .= '<th>Store Name</th>';
					$html .= '<th>Order Number</th>';
					$html .= '<th>Price</th>';
					$html .= '<th>Customer Name</th>';
					$html .= '<th>Email</th>';
					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					if(!empty($all_list)){
						$sr = $sr_start;
						foreach($all_list as $single){
							$html .= '<tr>';
							$html .= '<td><input type="checkbox" value='.$single["id"].' class="checkBoxClass"></td>';
							$html .= '<td>'.$sr.'</td>';
							$html .= '<td>'.$single['store_name'].'</td>';
							
							$html .= '<td><a href="sa-vendor-order-details.php?stkn='.parent::getVal("stkn").'&oid='.$single['id'].'">'.$single['shop_order_number'].'</a></td>';
							
							$html .= '<td>$'.number_format((double)$single['total_price'],2).'</td>';
							$html .= '<td>'.$single['cust_name'].'</td>';
							$html .= '<td>'.$single['cust_email'].'</td>';
							
							$html .= '</tr>';
							$sr++;
						}
					}else{
						$html .= '<tr>';
						$html .= '<td colspan="7" align="center">No Record Found</td>';
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
			die();
		}
	}

	function getOrderViewInfo(){
		if(!empty(parent::getVal("oid")))
		{	
			$this->orderId = parent::getVal("oid");
			
			$orderData = $this->get_order_details($this->orderId);
			
			return $orderData;die();
		}
	}

	function get_order_details($store_orders_master_id){
		$vendor_id = (isset($_SESSION['vendor_id']))?$_SESSION['vendor_id']:0;
		$sql='SELECT * FROM store_vendors_master WHERE id = '.$vendor_id.' ';
		$getVendor = parent::selectTable_f_mdl($sql);
		$vendor_name ='';
		if(!empty($getVendor)){
			$vendor_name = (!empty($getVendor[0]['vendor_name']))?$getVendor[0]['vendor_name']:'';
		}

		$sql = 'SELECT `store_orders_master`.* FROM `store_orders_master`
		LEFT JOIN `store_master` ON `store_master`.id = `store_orders_master`.store_master_id
		WHERE `store_orders_master`.id="'.$store_orders_master_id.'" AND store_sale_type = "On-Demand"
		';
		$list_data = parent::selectTable_f_mdl($sql);
		if(!empty($list_data)){
			$sql = 'SELECT `store_order_items_master`.store_owner_product_variant_master_id,`store_order_items_master`.id, store_order_items_master. personalization_name,store_order_items_master.shop_product_id, `store_order_items_master`.store_owner_product_master_id,`store_order_items_master`.shop_variant_id, `store_order_items_master`.title, `store_order_items_master`.quantity, `store_order_items_master`.price, `store_order_items_master`.sku,`store_order_items_master`.variant_title,store_owner_product_variant_master.image
			,store_order_items_master.is_deleted,store_order_items_master.logo_image FROM `store_order_items_master`
			LEFT JOIN store_owner_product_variant_master ON store_owner_product_variant_master.id = `store_order_items_master`.store_owner_product_variant_master_id
			WHERE `store_order_items_master`.store_orders_master_id = "'.$store_orders_master_id.'" AND store_order_items_master.vendor="'.$vendor_name.'"
			';/* Task 67 add personalization_name*/

			$var_data = parent::selectTable_f_mdl($sql);

			$list_data[0]['var_data'] = $var_data;
			return $list_data;
		}else{
			header('location:index.php');
		}
	}

	function get_notices_details($store_master_id=0)
	{
		$sql = 'select n.user_action, n.previous_status, n.new_status, oitem.title, oitem.variant_title, n.created_at from order_warning_notes as n left join store_order_items_master as oitem on oitem.id = n.order_items_master_id where n.orders_master_id =  "'.$store_master_id.'"';
		return parent::selectTable_f_mdl($sql);die();
	}

	function getProfileDetails()
	{
		$user_id = $_SESSION['user_id'];
		$sql="SELECT email,password,first_name,last_name,id FROM users WHERE id = '".$user_id."' ";
		$getData = parent::selectTable_f_mdl($sql);
		return $getData;die();
	}

	function updateProfile(){
		if(parent::isPOST()){
			$res = [];
			if(parent::getVal("method") == "updateProfile")
			{
				$login_user_id = $_SESSION['user_id'];
				$updateData = [
					'first_name' => parent::getVal("first_name"),
					'last_name' => parent::getVal("last_name")
				];
				parent::updateTable_f_mdl('users',$updateData,'id="'.$login_user_id.'"');
				$res['SUCCESS'] = true;
				$res['MESSAGE'] = 'Profile update successfully.';
			}else{
				$res['SUCCESS'] = false;
				$res['MESSAGE'] = '!Something went wrong.';
			}
			echo json_encode($res);die();
		}	
	}

	function createZip(){
		$s3Obj = new Aws3;
		if(parent::isPOST()){
			$res = [];
			if(parent::getVal("method") == "createZip")
			{
				$vendor_id = (isset($_SESSION['vendor_id']))?$_SESSION['vendor_id']:0;
				$sql='SELECT * FROM store_vendors_master WHERE id = '.$vendor_id.' ';
				$getVendor = parent::selectTable_f_mdl($sql);
				$vendor_name ='';
				if(!empty($getVendor)){
					$vendor_name = (!empty($getVendor[0]['vendor_name']))?$getVendor[0]['vendor_name']:'';
				}

				$orderIds = implode(',', parent::getVal("orderIds"));
				$sql = 'SELECT store_owner_product_master_id,logo_image,vendor FROM store_order_items_master where store_orders_master_id IN('.$orderIds.') AND vendor = "'.$vendor_name.'" group by store_owner_product_master_id order by store_orders_master_id desc';
				$data = parent::selectTable_f_mdl($sql);
				if(!empty($data)){
					$storeOwnerProductData = [];
					$rand = "vendor_".rand();
					$folderName = common::IMAGE_UPLOAD_S3_PATH.$rand;
					mkdir($folderName);

					foreach ($data as $value) {
						$file_name = $value['logo_image'];
						$filename = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$value['logo_image']);
						@file_put_contents($folderName."/".$file_name, @file_get_contents($filename));
					}
					$dData   = (count(glob("$folderName/*")) === 0) ? 0 : 1;
    
					if ($dData!=0){
						$zip = new ZipArchive();
					    $filename = $rand.".zip";
					    if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
					        exit("cannot open <$filename>\n");
					    }
					 	$dir = Common::IMAGE_UPLOAD_S3_PATH.$rand."/";

					    // Create zip
					    $this->createZipFile($zip,$dir);

					    $zip->close();
					    $encode = urlencode(base64_encode($orderIds));
					    $res['filename'] = $filename;
					    $res['status'] = "TRUE";
					    $res['pdf_url'] = Common::SITE_URL."generate-pdf.php?stkn&ids=".$encode."";
					    $files = glob($folderName.'/*');
					    foreach($files as $file){
						    if(is_file($file))
						    unlink($file); //delete file
						}
					}else{
						$res['MESSAGE'] = "Data not found";
						$res['status'] = "FALSE";
					}	
				    rmdir($folderName);
				}
			}else{
				$res['MESSAGE'] = "Data not found";
				$res['status'] = "FALSE";
			}
			// $this->createPdf($orderIds);
			echo json_encode($res);die();
		}	
	}

	function createZipFile($zip,$dir){
	    if (is_dir($dir)){
	        if ($dh = opendir($dir)){
	            while (($file = readdir($dh)) !== false){
	                // If file
	                if (is_file($dir.$file)) {
	                    if($file != '' && $file != '.' && $file != '..'){
	                        //$zip->addFile($dir.$file);
	                        $zip->addFromString($file, file_get_contents($dir.$file));
	                    }
	                }else{
	                    // If directory
	                    if(is_dir($dir.$file) ){

	                        if($file != '' && $file != '.' && $file != '..'){

	                            // Add empty directory
	                            $zip->addEmptyDir($dir.$file);

	                            $folder = $dir.$file.'/';
	                            
	                            // Read data of the folder
	                            createZip($zip,$folder);
	                        }
	                    }
	                    
	                }      
	            }
	            closedir($dh);
	        }
	    }
	}

	function createPdf($decode){
		$s3Obj = new Aws3;
		$vendor_id = (isset($_SESSION['vendor_id']))?$_SESSION['vendor_id']:0;
		$sql='SELECT * FROM store_vendors_master WHERE id = '.$vendor_id.' ';
		$getVendor = parent::selectTable_f_mdl($sql);
		$vendor_name ='';
		if(!empty($getVendor)){
			$vendor_name = (!empty($getVendor[0]['vendor_name']))?$getVendor[0]['vendor_name']:'';
		}
		$sql = 'SELECT soim.store_owner_product_master_id,soim.store_owner_product_variant_master_id,soim.logo_image,soim.vendor,soim.title,soim.variant_title,soim.store_orders_master_id,soim.print_location,som.shop_order_number,sopvm.image as product_image FROM store_order_items_master as soim LEFT JOIN store_orders_master as som ON som.id = soim.store_orders_master_id LEFT JOIN store_owner_product_variant_master as sopvm ON sopvm.id = soim.store_owner_product_variant_master_id where soim.store_orders_master_id IN('.$decode.') AND soim.vendor = "'.$vendor_name.'" order by soim.store_orders_master_id desc';	
		$pdfData = parent::selectTable_f_mdl($sql);
		// echo "<pre>";print_r($pdfData);die();
		$bindHtml = '';
		if(!empty($pdfData)){
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
										  padding:10px;
										}
										td{
											padding:10px;
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

					          <th scope="col">Order Number</th>
					          <th scope="col">Product Image</th>
					          <th scope="col">Name</th>
					          <th scope="col">Size/Color</th>
					          <th scope="col">Logo Image</th>
							  <th scope="col">Print Location</th>
					        </tr>
				      	</thead>';
				    $bindHtml.='<tbody>';
	            
					foreach ($pdfData as $value) {
						$logoUrl = '';
						if(!empty($value['logo_image'])){
							$logoImage    = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$value['logo_image']);
							// $logoUrl = '<a href="'.$logoImage.'">Click on logo image</a>';
							$logoUrl = '<img src="'.$logoImage.'" alt="logo" width="80" height="80">';
						}

						$productUrl = '';
						if(!empty($value['product_image'])){
							$productImage = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$value['product_image']);
							// $productUrl = '<a href="'.$productImage.'">Click on product image</a>';
							$productUrl = '<img src="'.$productImage.'" alt="Girl in a jacket" width="80" height="80">';
						}
						// print_r($productUrl);die;

						$bindHtml.="<tr>
						<td>".$value['shop_order_number']."</td>
						<td>".$productUrl."</td>
						<td>".$value['title']."</td>
						<td>".$value['variant_title']."</td>
						<td>".$logoUrl."</td>
						<td>".$value['print_location']."</td>
						</tr>";   
					}
					$bindHtml.='<tbody>';
		   			$bindHtml .='</table>
						    </div>
						</div>
						</div>
						</body>
						</html>';
			require_once("html-templates/custom/dompdf/vendor/autoload.php");			
			
			$options = new \Dompdf\Options();
			$options->setIsRemoteEnabled(true);
			$dompdf = new \Dompdf\Dompdf($options);
			$dompdf->loadHtml($bindHtml);
			$dompdf->setPaper('A4', 'landscape');
			$dompdf->render();
			$dompdf->stream('vendor_'.time().'.pdf',array("Attachment" => 1));
			exit(0);
		}
	}
}
?>
