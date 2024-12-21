<?php
include_once 'model/sa_bulk_product_mdl.php';

$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';
$login_user_email="";
if(isset($_SESSION['user_email']) && $_SESSION['user_email'] != "") {
	$login_user_email=trim($_SESSION['user_email']);
}
class sa_bulk_product_ctl extends sa_bulk_product_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		
		common::CheckLoginSession();
	}
	
	function bulkProductPagination(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "bulkproduct_pagination")
			{
				$s3Obj = new Aws3;
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
						csv_file LIKE '%".trim($keyword)."%' OR
						uploaded_by LIKE '%".trim($keyword)."%'
					)";
				}
				$cond_order = 'ORDER BY id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}
				/*$cond_start_end = '';
				if(isset($this->start_date) && !empty($this->start_date) && isset($this->end_date) && !empty($this->end_date) ){
					$cond_start_end = "AND add_date BETWEEN ".$this->start_date." AND ".$this->end_date."";
				}*/
				$sql="
						SELECT count(*) as count
						FROM bulk_product_csv
						WHERE status = 1
						$cond_keyword
					";
				$all_count = parent::selectTable_f_mdl($sql);
				
				$sql1="
						SELECT id, csv_file, status, created_on, created_on_ts,uploaded_by FROM bulk_product_csv WHERE status = '1'
						$cond_keyword
						
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
					$html .= '<table class="table table-bordered table-hover">';

					$html .= '<thead>';
					$html .= '<tr>';
			
					$html .= '<th>#</th>';
					$html .= '<th>File Name</th>';
					$html .= '<th>Uploaded By</th>';
					$html .= '<th>Date</th>';
					$html .= '<th>Download</th>';
					
					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					if(!empty($all_list))
					{
						$sr = $sr_start;
						foreach($all_list as $single){
							
							$fileUrl = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$single["csv_file"]);
							if(!$s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$single["csv_file"])){
								$fileUrl = $single["csv_file"];
							}
							
							$html .= '<tr>';
							$html .= '<td>'.$sr.'</td>';
							$html .= '<td>'.$single["csv_file"].'</td>';
							$html .= '<td>'.$single["uploaded_by"].'</td>';
							$html .= '<td>'.date('m/d/Y h:i A',$single["created_on_ts"]).'</td>';
							$html .= '<td>						   
										<a href="'.$fileUrl.'" target="_blank" class="btn btn-info waves-effect waves-classic" download>Download</a>
									  </td>';
							
							$html .= '</tr>';	
							$sr++;
						}
					}
					else{
						$html .= '<tr>';
						$html .= '<td colspan="4" align="center">No Record Found</td>';
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
	
	function addBulkProduct(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("hdn_csv_method")) && parent::getVal("hdn_csv_method") == "add_csvFile"){
				$csvFile = "";
				$s3Obj = new Aws3;
				global $login_user_email;
				require_once(common::EMAIL_REQUIRE_URL);
				if (strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp') !== false) {
					$objAWS = new aws_ses_smtp();
				} else if (strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail') !== false) {
					$objAWS = new sendGridEmail();
				} else {
					$objAWS = new Aws(common::AWS_ACCESS_KEY, common::AWS_SECRET_KEY, common::AWS_REGION);
				}
				#region - Check Image Posted Or Not
				if($_FILES['upload_file']){
					
					$fileUploadPath = "image_uploads/";
					$tmpFileExtention = ".csv";
					
					$mimeType = $_FILES['upload_file']['type'];
					$fileName = $_FILES['upload_file']['name'];
					$tempfileName = $_FILES['upload_file']['tmp_name'];
					
					#region - Get Uploaded File's Extension
					$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
					#endregion

					$fileName = explode('.', $fileName);/* Task 103 add new line for fileName */
					#region - Can Upload Same Image Using Rand Function
					// $tempFinalfileName = time().rand(1000,1000000).$tmpFileExtention;
					$tempFinalfileName = $fileName[0].'_'.time().rand(1000,1000000).$tmpFileExtention;/* Task 103 add file name */
					#endregion
					
					$validImgExtArray = explode(",", common::VALID_CSV_EXTENSIONS);
					
					#region - Check's Valid Format
					if(in_array($fileExt, $validImgExtArray)){ 
						$fileUploadPath = $fileUploadPath.strtolower($tempFinalfileName);
					}
					#endregion
					
					#region - Upload File To Dir
					// if(move_uploaded_file($tempfileName, $fileUploadPath))
					if($s3Obj->uploadFile($fileUploadPath,$tempfileName,$mimeType))
					{
						$csvFile = $tempFinalfileName;
					}
					#endregion
				}
				#endregion
				
				$this->csvFile = $csvFile;
				
				$getInsertedInfo = parent::addBulkProduct_f_mdl($login_user_email);
				
				sleep(1);
				
				$getInsertedId = $getInsertedInfo['master_id'];
				
				$getFileInfo = parent::getFileInfo_f_mdl($getInsertedId);
				
				$fileName = $getFileInfo[0]['csv_file'];
				
				$filePath = 'image_uploads/'.$fileName;
				$filePath = $s3Obj->getAwsUrl($filePath);
				$file = fopen($filePath, "r");
				
				$count = 0;
				$variantCount = 0;
				
				$typreOneOrgTypeMasterId = 1;
				$typreTwoOrgTypeMasterId = 2;
				
				$isAnyErrorFound = '0';
				
				while (($csvData = fgetcsv($file, 10000, ",")) !== FALSE)
				{	
					if ($count > 0) {
						if($csvData[0] != ''){
							$vendorMasterId = 0;
							$vendorMasterId = parent::getVendorInfo_f_mdl(trim($csvData[3]));

							if(empty($vendorMasterId[0]['id'])){
								$respArray = parent::addVendor_f_mdl(trim($csvData[3]));

								if($respArray['vendor_id'] > 0){
									$vendorMasterId = $respArray['vendor_id'];
								}
							}else{
								$vendorMasterId = $vendorMasterId[0]['id'];
							}

							$getInsertProductInfo = parent::addBulkCsvData_f_mdl(trim($csvData[0]),trim($csvData[1]),trim($csvData[2]),$vendorMasterId);

							$insertedProductId = $getInsertProductInfo['master_id'];

							//sent email to admin
							$subject = 'SpitiHero - Products CSV Imported';
							$to_email = 'preetamdhanotiya@bitcot.com';
							$from_email = common::AWS_ADMIN_EMAIL;
							$attachment = [];
							$ccMails = ['dilipyadav@bitcot.com'];
							$body = 'Hi Preetam,<br>The master products have been added successfully through products csv <br>Please update Default sizes,Default price directely from Database.<br><br>Master Product Id = '. $insertedProductId . '<br><br>Thanks<br>SpitiHero Team';
							$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body,$ccMails);	
							//sent email to admin end  

							$variantCount = 0;
						}

						// client want to ALL variant data, that why we comment below condition
						//if ($variantCount <= 99){
							if(isset($insertedProductId) && !empty($insertedProductId)){
								
								$colorCode = parent::getColorCodeInfo_f_mdl(strtolower(trim($csvData[8])));
								if(empty($colorCode)){
									$colorCode[0]['product_color'] = "";
								}
								$imgUploadPath = common::SHOPIFY_DIRECTORY_PATH."/image_uploads/";
								$image = $feature_image = '';
								if(!empty($csvData[10])){
									
									$filename = $csvData[10];
									$image_ext = pathinfo($filename, PATHINFO_EXTENSION);

									$image = time().rand(100000,999999).rand(100000,999999).'.'.$image_ext;
									//	file_put_contents($imgUploadPath.$image, file_get_contents($csvData[10]));
									$s3Obj->putObject(common::IMAGE_UPLOAD_S3_PATH.$image, file_get_contents($csvData[10]));
								}
								if(!empty($csvData[12])){

									$filename = $csvData[12];
									$image_ext = pathinfo($filename, PATHINFO_EXTENSION);

									$feature_image = time().rand(100000,999999).rand(100000,999999).'.'.$image_ext;
									//	file_put_contents($imgUploadPath.$feature_image, file_get_contents($csvData[12]));
									$s3Obj->putObject(common::IMAGE_UPLOAD_S3_PATH.$feature_image, file_get_contents($csvData[12]));
								}

								$spirithero_sku=self::generateSpiritHeroSKU(trim($csvData[11]),trim($csvData[8]),trim($csvData[9]));

								parent::addBulkVariant_f_mdl($insertedProductId,$typreOneOrgTypeMasterId,trim($csvData[4]),trim($csvData[6]),$colorCode[0]['product_color'],trim($csvData[9]),$image,trim($csvData[11]),$spirithero_sku,$feature_image,trim($csvData[13]),trim($csvData[14]),trim($csvData[15]),trim($csvData[16]));
								//parent::addBulkVariant_f_mdl($insertedProductId,$typreTwoOrgTypeMasterId,trim($csvData[5]),trim($csvData[7]),$colorCode[0]['product_color'],trim($csvData[9]),$image,trim($csvData[11]),$spirithero_sku,$feature_image,trim($csvData[13]),trim($csvData[14]));


								$insertedVariantStatus = $getInsertProductInfo['isSuccess'];
								
								$variantCount++;
								
								if($insertedVariantStatus == '0'){
									parent::maintainLog_f_mdl($insertedProductId,$csvData[4],$csvData[5],$csvData[6],$csvData[7],$csvData[8],$csvData[9],$csvData[10],$csvData[11],$csvData[12]);
									
									$isAnyErrorFound = '1';
								}
							}else{
								$isAnyErrorFound = '1';
							}
						//}
					}
					$count++;
				}
				
				fclose($file);
				
				$resultResp = array();
				
				if($isAnyErrorFound == '0'){
					$resultResp["isSuccess"] = "1";
					$resultResp["msg"] = "Changes saved successfully.";
				}
				else{
					$resultResp["isSuccess"] = "0";
					$resultResp["msg"] = "Oops! there is some issue during insert. Please try again.";
				}
				
				common::sendJson($resultResp);
				
			}
		}
	}
	
	function generateSpiritHeroSKU($sku,$color,$size){
		
		$color= strtolower($color);
		$color=preg_replace('/[^A-Za-z0-9\-]/', ' ', $color);
		$color=str_replace(" ","_",$color);
		$size= strtolower($size);
		if(strpos($size,'(')){
			$size_sort=strstr($size, ' (',true);
		}else{
		$size_sort=$size;
		}
		$size_sort=preg_replace('/[^A-Za-z0-9\-]/', ' ', $size_sort);
		$size=str_replace(" ","_",$size_sort);
		$new_sku=$sku.'-'.$color.'-'.$size;
		return $new_sku;
	}
}
?>