<?php
use PHPShopify\Product;

include_once 'model/cron_send_email_after_seven_days_reports_mdl.php';
include_once $path . '/libraries/Aws3.php';
class cron_send_email_after_seven_days_reports_ctl extends cron_send_email_after_seven_days_reports_mdl
{
    function __construct()
    {
        $this->send_email_index();
    }

    function send_email_index()
    {
        $storeList                   =  parent::getStoreList_f_mdl();
        
        if (!empty($storeList)) {
            $sendOnEmail = [];
            $failOnEmail = [];
            $emailData = '';
            foreach ($storeList as $store_data) {
                $store_master_id  = $store_data['id'];
                $store_name       = $store_data['store_name'];
                $first_name       = $store_data['first_name'];
                $email            = $store_data['email'];
                $store_owner_id            = $store_data['store_owner_id'];
                    
                $this->sendMinimumProductSummeryEmail($store_master_id,$store_name,$first_name,$email,$store_owner_id);
            }
        }

        $store_list_not_lunched_store = parent::getNotLaunched1dayStoreList_f_mdl();
        if (!empty($store_list_not_lunched_store)) {
            $sendOnEmail = [];
            $failOnEmail = [];
            $emailData = '';
            $email_temp_id='32';
            foreach ($store_list_not_lunched_store as $storedata) {
                $store_master_id  = $storedata['id'];
                $store_name       = $storedata['store_name'];
                $first_name       = $storedata['first_name'];
                $email            = $storedata['email'];
                $store_owner_id   = $storedata['store_owner_id'];
                    
                $this->sendFriendlyReminderEmail($store_master_id,$store_name,$first_name,$email,$store_owner_id,$email_temp_id);
            }
        }

        $store_list_not_lunched_store = parent::getNotLaunched3daysStoreList_f_mdl();
        if (!empty($store_list_not_lunched_store)) {
            $sendOnEmail = [];
            $failOnEmail = [];
            $emailData = '';
            $email_temp_id='33';
            foreach ($store_list_not_lunched_store as $storedata) {
                $store_master_id  = $storedata['id'];
                $store_name       = $storedata['store_name'];
                $first_name       = $storedata['first_name'];
                $email            = $storedata['email'];
                $store_owner_id   = $storedata['store_owner_id'];
                    
                $this->sendFriendlyReminderEmail($store_master_id,$store_name,$first_name,$email,$store_owner_id,$email_temp_id);
            }
        }

    }

    public function sendMinimumProductSummeryEmail($store_master_id,$store_name,$first_name,$email,$store_owner_id)
    {   
        $s3Obj = new Aws3;
        require_once(common::EMAIL_REQUIRE_URL);
        if (strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp') !== false) {
            $objAWS = new aws_ses_smtp();
        } else if (strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail') !== false) {
            $objAWS = new sendGridEmail();
        } else {
            $objAWS = new Aws(common::AWS_ACCESS_KEY, common::AWS_SECRET_KEY, common::AWS_REGION);
        }

        $flashSaleData = array();
        $onDemandData = array();

        $soldSql = 'SELECT IFNULL(SUM(oim.quantity),0) as sold_items,om.store_master_id,oim.store_owner_product_master_id ,oim.title,om.id as store_order_master_id FROM `store_orders_master` as om INNER JOIN store_order_items_master as oim on om.id = oim.store_orders_master_id WHERE om.is_order_cancel = 0 AND oim.is_deleted = 0 AND oim.store_master_id = "'.$store_master_id.'"';
        $qtySold = parent::selectTable_f_mdl($soldSql);

  
        $soldItems = '';
        if (!empty($qtySold)) {
            $soldItems = $qtySold[0]['sold_items']; 
        }

                
        $sql = 'SELECT subject,body,recipients FROM `email_templates_master` WHERE id=17';
        $et_data = parent::selectTable_f_mdl($sql);
        $sql = 'SELECT * FROM store_master WHERE id="'.$store_master_id.'"';
        $store_data = parent::selectTable_f_mdl($sql);
        if($store_data[0]['store_sale_type_master_id']=='1'){

            $store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
            $store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;
        
            $logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
            $logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
            $dashboardLink = '<a href="'.common::CUSTOMER_PORTAL_SITE_URL.'index.php?do=stores">Click here</a>';
            $front_store_url ="https://" . common::PARENT_STORE_NAME . "/collections/" .$store_data[0]["shop_collection_handle"];
            $body ='';
            $subject ='';
            $to_email ='';

            if (!empty($et_data)) {
                $body    = $et_data[0]['body'];
                $subject = $et_data[0]['subject'];
                $ccMails = '';
                if($et_data[0]['recipients']){
                    $recipients = $et_data[0]['recipients'];
                    $recipients = str_replace(' ', '', $recipients);
                    $ccMails    = explode(',', $recipients);
                }
            }

            $body  = str_replace('{{FIRST_NAME}}', $first_name, $body);
            $body  = str_replace('{{STORE_NAME}}', $store_name, $body);
            $body  = str_replace('{{TOTAL_ITEM}}', $soldItems, $body);
            $body  = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
            $body  = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
            $body  = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
            $body  = str_replace('{{DASHBOARD_LINK}}', $dashboardLink, $body);
            $body  = str_replace('{{FRONT_STORE_URL}}', trim($front_store_url), $body);
            $html  = $body;

            $to_email = $email;
            $mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $html, $html,$ccMails);      
            /*send mail store manager */
            $store_owner_details_master_id = $store_owner_id;
            $sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_details_master_id . '"';
            $smm_data =  parent::selectTable_f_mdl($sql_managerData);
            if(!empty($smm_data)){
                foreach ($smm_data as $managerData) {
                    $body       = $et_data[0]['body'];
                    $to_email   = $managerData['email'];
                    $firstname  = $managerData['first_name'];
                    $body       = str_replace('{{FIRST_NAME}}', $firstname, $body);
                    $body       = str_replace('{{STORE_NAME}}', $store_name, $body);
                    $body 		= str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
                    $body       = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
                    $body       = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
                    $body       = str_replace('{{DASHBOARD_LINK}}', $dashboardLink, $body);
                    $body       = str_replace('{{FRONT_STORE_URL}}', trim($front_store_url), $body);
                    $body       = str_replace('{{TOTAL_ITEM}}', $soldItems, $body);

                    $mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body);
                }
            }
            /*send mail store manager */
        }
    }

    public function sendFriendlyReminderEmail($store_master_id,$store_name,$first_name,$email,$store_owner_id,$email_temp_id)
    {   
        $s3Obj = new Aws3;
        require_once(common::EMAIL_REQUIRE_URL);
        if (strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp') !== false) {
            $objAWS = new aws_ses_smtp();
        } else if (strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail') !== false) {
            $objAWS = new sendGridEmail();
        } else {
            $objAWS = new Aws(common::AWS_ACCESS_KEY, common::AWS_SECRET_KEY, common::AWS_REGION);
        }

        $flashSaleData = array();
        $onDemandData = array();
      
        $sql = 'SELECT subject,body,recipients FROM `email_templates_master` WHERE id="'.$email_temp_id.'"';
        $et_data = parent::selectTable_f_mdl($sql);

        $sql = 'SELECT * FROM store_master WHERE id="'.$store_master_id.'"';
        $store_data = parent::selectTable_f_mdl($sql);

        $store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
        $store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;
        
        
        $logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
        $logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
        $body ='';
        $subject ='';
        $to_email ='';

        if (!empty($et_data)) {
            $body    = $et_data[0]['body'];
            $subject = $et_data[0]['subject'];
            $ccMails = '';
            if($et_data[0]['recipients']){
                $recipients = $et_data[0]['recipients'];
                $recipients = str_replace(' ', '', $recipients);
                $ccMails    = explode(',', $recipients);
            }
        }    

        $body  = str_replace('{{FIRST_NAME}}', $first_name, $body);
        $body  = str_replace('{{STORE_NAME}}', $store_name, $body);
        $body  = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
        $body  = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
        $body  = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);

        $html  = $body;

        $to_email = $email;
        $mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $html, $html,$ccMails); 

        /*send mail store manager */
        $store_owner_details_master_id = $store_owner_id;
        $sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_details_master_id . '"';
        $smm_data =  parent::selectTable_f_mdl($sql_managerData);
        if(!empty($smm_data)){
            foreach ($smm_data as $managerData) {
                $body       = $et_data[0]['body'];
                $to_email   = $managerData['email'];
                $firstname  = $managerData['first_name'];
                $body       = str_replace('{{FIRST_NAME}}', $firstname, $body);
                $body       = str_replace('{{STORE_NAME}}', $store_name, $body);
                $body 		= str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
                $body       = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
                $body       = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
                $mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body);
            }
        }
        /*send mail store manager */
    }
}