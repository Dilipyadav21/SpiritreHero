<?php
include_once 'model/sa_store_edit_request_mdl.php';
include_once $path . '/libraries/Aws3.php';
class sa_store_edit_request_ctl extends sa_store_edit_request_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
		}
		
		common::CheckLoginSession();
	}
	
	
	function editStoreListPagination(){
		if(parent::isPOST()){
			if(parent::getVal("hdn_method") == "store_edit_pagination")
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
							sm.store_name LIKE '%".trim($keyword)."%'
						)";
				}
				$cond_order = 'ORDER BY sorc.id DESC';
				if(!empty($sort_field)){
					$cond_order = 'ORDER BY '.$sort_field.' '.$sort_type;
				}
				$sql="
						SELECT count(sorc.id) as count,sorc.id, sm.store_name,sorc.request_body,sorc.expire_ts,sorc.status,sorc.created_on,sorc.created_on_ts,request_date FROM store_owner_request_to_changes sorc INNER JOIN store_master sm ON sorc.store_master_id = sm.id WHERE 1
						$cond_keyword
					";
				$all_count = parent::selectTable_f_mdl($sql);
				
				
				$sql1="
						SELECT sorc.id, sorc.store_master_id, sm.store_name,sorc.request_body,sorc.expire_ts,sorc.status,sorc.created_on,sorc.created_on_ts,request_date FROM store_owner_request_to_changes sorc INNER JOIN store_master sm ON sorc.store_master_id = sm.id WHERE 1
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
					$html .= '<th>Stores Name</th>';
					$html .= '<th>Request Description</th>';
					$html .= '<th>Request Date</th>';
					$html .= '<th>Action</th>';
					
					$html .= '</tr>';
					$html .= '</thead>';

					$html .= '<tbody>';

					
					if(!empty($all_list))
					{
						$sr = $sr_start;
						foreach($all_list as $single){
							$request_date =$single["request_date"];
							if(!empty($single["request_date"])){
								$request_date = date('m/d/Y h:i A', strtotime($request_date));
							}
							
							$html .= '<tr>';
							$html .= '<td>'.$sr.'</td>';	
							$html .= '<td>'.$single["store_name"].'</td>';	
							$html .= '<td>'.$single["request_body"].'</td>';
							$html .= '<td>'.$request_date.'</td>';
							
							if($single["status"] == '0')
							{
								$html .= '<td><button data-href="sa-store-edit-request.php?stkn='.parent::getVal("stkn").'&eid='.$single["id"].'" data-id = "'.$single["id"].'" data-store_master_id = "'.$single["store_master_id"].'" type="button" class="btn btn-info btn-edit-store-req">Approve</button></td>';
							}
							else if($single["status"] == '1')
							{
								$html .= '<td>Approved</td>';
							}
							else{
								$html .= '<td>Expired</td>';
							}
							
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
	
	function updatePermissionRequest(){
		$s3Obj = new Aws3;
		if(parent::isPOST()){
			if(!empty(parent::getVal("method")) && parent::getVal("method") == "storeRequest") {
				$this->id = parent::getVal("id");
				$this->storeId = parent::getVal("store_master_id");

				//send email to store-owner
				$sql = 'SELECT `store_master`.id,`store_master`.email_notification, `store_master`.store_name,`store_master`.store_open_date,`store_master`.store_close_date,`store_master`.shop_collection_handle, `store_owner_details_master`.email, `store_owner_details_master`.first_name,`store_owner_details_master`.id as store_owner_details_master_id FROM `store_master`
				LEFT JOIN `store_owner_details_master` ON `store_owner_details_master`.id = `store_master`.store_owner_details_master_id
				WHERE `store_master`.id="'.$this->storeId.'"
				';
						
				$sm_data = parent::selectTable_f_mdl($sql);
				if(!empty($sm_data)){
					$sql = 'SELECT subject,body FROM `email_templates_master` WHERE id='.common::TO_CUSTOMER_ADMIN_APPROVE_REQUEST_ACCESS_EDIT_MODE;
					$et_data = parent::selectTable_f_mdl($sql);
					$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
					$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
					$front_store_url ="https://" . common::PARENT_STORE_NAME . "/collections/" .$sm_data[0]["shop_collection_handle"];
					$store_open_date=!empty($sm_data[0]["store_open_date"]) ? date('m/d/Y', $sm_data[0]["store_open_date"]) : '' ;
					$store_last_date=!empty($sm_data[0]["store_close_date"]) ? date('m/d/Y', $sm_data[0]["store_close_date"]) : '' ;
					if(!empty($et_data)){
						require_once(common::EMAIL_REQUIRE_URL);
				        if(strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp')!==false){
				            $objAWS = new aws_ses_smtp();
				        }else if(strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail')!==false){
                            $objAWS = new sendGridEmail();
                        }else{
				            $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
				        }
						
						
						$subject = $et_data[0]['subject'];
						$body = $et_data[0]['body'];
						$to_email = $sm_data[0]['email'];
						$attachment = [];
						$from_email = common::AWS_ADMIN_EMAIL;

						$body = str_replace('{{FIRST_NAME}}',$sm_data[0]['first_name'],$body);
						$body = str_replace('{{STORE_NAME}}',$sm_data[0]['store_name'],$body);
						$body = str_replace('{{DASHBOARD_LINK}}',common::CUSTOMER_ADMIN_DASHBOARD_URL,$body);
						$body = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
						$body = str_replace('{{FRONT_STORE_URL}}', trim($front_store_url), $body);
						$body = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
						$body = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
						//$objAWS::sendEmail($from_email, $to_email, $subject, $body, $attachment);

						$sql = 'SELECT * 
								FROM store_master 
								WHERE id="'.$this->storeId.'"';
						$store_data = parent::selectTable_f_mdl($sql);

						//if($store_data[0]['email_notification'] == '1'){
							$objAWS->sendEmail([$to_email], $subject, $body, $body);
						//}
						/*send mail store manager*/
						$store_owner_details_master_id=$sm_data[0]['store_owner_details_master_id'];
						$sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_details_master_id . '"';
						$smm_data =  parent::selectTable_f_mdl($sql_managerData);
						if(!empty($smm_data)){
							foreach ($smm_data as $managerData) {
								$to_email   = $managerData['email'];
								$body = $et_data[0]['body'];
								$body = str_replace('{{FIRST_NAME}}',$managerData['first_name'],$body);
								$body = str_replace('{{STORE_NAME}}',$sm_data[0]['store_name'],$body);
								$body = str_replace('{{DASHBOARD_LINK}}',common::CUSTOMER_ADMIN_DASHBOARD_URL,$body);
								$body = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
								$body = str_replace('{{FRONT_STORE_URL}}', trim($front_store_url), $body);
								$body = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
								$body = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
								$objAWS->sendEmail([$to_email], $subject, $body, $body);
							}
						}
						/*send mail store manager end*/
					}
				}

				$status = '1';
				$updtReq =  parent::updatePermissionRequest_f_mdl($this->id,$status);

				return $updtReq;
			}
		}
	}
	
	function sendPayoutEmail($storeMasterId,$paidAmoount){
		$s3Obj = new Aws3;
		$ownerEmail = parent::getEmailInfo_f_mdl($storeMasterId);
		
		#region - Send Mail To Store Admin
		require_once(common::EMAIL_REQUIRE_URL);
        if(strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp')!==false){
            $objAWS = new aws_ses_smtp();
        }else if(strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail')!==false){
            $objAWS = new sendGridEmail();
        }else{
            $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
        }
		
		$emailData = parent::getEmailTemplateInfo(common::PAYOUT_EMAIL_ID);
		$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
		$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
		$subject = $emailData[0]['subject'];
		$to_email = $ownerEmail['0']['email'];
		$from_email = common::AWS_ADMIN_EMAIL;
		$attachment = [];
		
		//$mailSendStatus = $objAWS::sendEmail($from_email, $to_email, $subject, str_replace(["{{PAYOUT_AMOUNT}}","{{FIRST_NAME}}"],[$paidAmoount,$ownerEmail['0']['first_name']],$emailData[0]['body']), $attachment);

		$sql = 'SELECT * FROM store_master WHERE id="'.$storeMasterId.'"';
		$store_data = parent::selectTable_f_mdl($sql);

		$store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
		$store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;
		
		$mailSendStatus = 1;
		if ($emailData[0]['status'] == 1) {	
			$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, str_replace(["{{PAYOUT_AMOUNT}}","{{FIRST_NAME}}","{{STORE_NAME}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$paidAmoount,$ownerEmail['0']['first_name'],$store_data[0]['store_name'],$logo,$store_open_date,$store_last_date],$emailData[0]['body']), str_replace(["{{PAYOUT_AMOUNT}}","{{FIRST_NAME}}","{{STORE_NAME}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$paidAmoount,$ownerEmail['0']['first_name'],$store_data[0]['store_name'],$logo,$store_open_date,$store_last_date],$emailData[0]['body']));
		}
		
		#endregion
		
		$resultArray = array();
		 
		if($mailSendStatus){
			$resultArray["isSuccess"] = "1";
			$resultArray["msg"] = "Changes saved successfully.";
		}
		else{
			$resultArray["isSuccess"] = "0";
			$resultArray["msg"] = "Oops! there is some issue during insert. Please try again.";
		}
		common::sendJson($resultArray);
	}
}
?>