<?php
include_once 'model/sa_add_payout_mdl.php';
include_once $path . '/libraries/Aws3.php';
class sa_add_payout_ctl extends sa_add_payout_mdl
{
	public $TempSession = "";

	function __construct(){	
		if(parent::isGET() || parent::isPOST()){
			$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			
			$this->passedId = parent::getVal("pid");
		}
	
		common::CheckLoginSession();
		
	}
	
	function getPayoutInfo(){
		return parent::getPayoutInfo_f_mdl($this->passedId);
	}
	
	function getStoreDropdownInfo(){
		return parent::getStoreDropdownInfo_f_mdl();
	}
	
	function addPayout(){
		if(parent::isPOST()){
			if(!empty(parent::getVal("hdn_method")) && parent::getVal("hdn_method") == "add-payout"){
				
				$this->id = parent::getVal("hdn_id");
				$this->payout_amount = parent::getVal("payout_amount");
				$this->store_master_id = parent::getVal("select_store");
				$this->notes = parent::getVal("payout_notes");
				$payout_date = parent::getVal("payout_date");			
				$this->payout_date = $payout_date!=''?date("Y-m-d h:i:s", strtotime($payout_date)):'';				
				if($this->id > 0){
					$resArray = parent::updatePayoutInfo_f_mdl();
					
					if($resArray['isSuccess'] == '1'){
						$this->sendPayoutEmail($this->store_master_id,$this->payout_amount);
					}
				}
				else{
					$resArray = parent::addPayout_f_mdl();
					
					if($resArray['isSuccess'] == '1'){
						$this->sendPayoutEmail($this->store_master_id,$this->payout_amount);
					}
				}
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
		
		//$mailSendStatus = $objAWS::sendEmail($from_email, $to_email, $subject, str_replace(["{{PAYOUT_AMOUNT}}","{{FIRST_NAME}}","{{DASHBOARD_LINK}}"],[$paidAmoount,$ownerEmail['0']['first_name'],common::CUSTOMER_ADMIN_DASHBOARD_URL],$emailData[0]['body']), $attachment);

		$sql = 'SELECT * FROM store_master WHERE id="'.$storeMasterId.'"';
        $store_data = parent::selectTable_f_mdl($sql);

		$store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
		$store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;
		$mailSendStatus = 1;
		if ($emailData[0]['status'] == 1) {
			$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, str_replace(["{{PAYOUT_AMOUNT}}","{{FIRST_NAME}}","{{STORE_NAME}}","{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$paidAmoount,$ownerEmail['0']['first_name'],$store_data[0]['store_name'],common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date],$emailData[0]['body']), str_replace(["{{PAYOUT_AMOUNT}}","{{FIRST_NAME}}","{{STORE_NAME}}","{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$paidAmoount,$ownerEmail['0']['first_name'],$store_data[0]['store_name'],common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date],$emailData[0]['body']));
		}
		/*send mail store manager */
		$store_owner_details_master_id = $ownerEmail['0']['store_owner_id'];
		$sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_details_master_id . '"';
		$smm_data =  parent::selectTable_f_mdl($sql_managerData);
		if(!empty($smm_data)){
			foreach ($smm_data as $managerData) {
				$to_email   = $managerData['email'];
				$from_email = common::AWS_ADMIN_EMAIL;
				$attachment = [];
				if ($emailData[0]['status'] == 1) {
					$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, str_replace(["{{PAYOUT_AMOUNT}}","{{FIRST_NAME}}","{{STORE_NAME}}","{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$paidAmoount,$managerData['first_name'],$store_data[0]['store_name'],common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date],$emailData[0]['body']), str_replace(["{{PAYOUT_AMOUNT}}","{{FIRST_NAME}}","{{STORE_NAME}}","{{DASHBOARD_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$paidAmoount,$managerData['first_name'],$store_data[0]['store_name'],common::CUSTOMER_ADMIN_DASHBOARD_URL,$logo,$store_open_date,$store_last_date],$emailData[0]['body']));
				}	
			}
		}
		/*send mail store manager */
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