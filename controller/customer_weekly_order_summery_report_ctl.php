<?php
use PHPShopify\Product;

include_once 'model/customer_weekly_order_summery_report_mdl.php';
include_once $path . '/libraries/Aws3.php';
class customer_weekly_order_summery_report_ctl extends customer_weekly_order_summery_report_mdl
{
    function __construct()
    {
        $this->sendEmailWeeklySummaryToCustomer();
    }

    function sendEmailWeeklySummaryToCustomer()
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
                $this->sendSummaryEmail($store_master_id,$store_name,$first_name,$email,$store_owner_id);
                parent::updateSendMailDate_f_mdl($store_master_id);
            }
        }
    }

    public function sendSummaryEmail($store_master_id,$store_name,$first_name,$email,$store_owner_id)
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

        $lastweeksoldSql = 'SELECT IFNULL(SUM(oim.quantity),0) as sold_items,om.store_master_id,oim.store_owner_product_master_id ,oim.title,om.id as store_order_master_id FROM `store_orders_master` as om INNER JOIN store_order_items_master as oim on om.id = oim.store_orders_master_id WHERE om.is_order_cancel = 0 AND oim.is_deleted = 0 AND oim.store_master_id = "'.$store_master_id.'" AND om.created_on > now() - interval 1 week';
        $lastweekqtySold = parent::selectTable_f_mdl($lastweeksoldSql);
        if (!empty($lastweekqtySold)) {
            $lastWeeksoldItems = $lastweekqtySold[0]['sold_items']; 
        }

        $store_sql = 'SELECT store_sale_type_master_id,verification_status,shop_collection_handle,store_open_date,store_close_date FROM `store_master` WHERE id="'.$store_master_id.'"';
        $stor_data = parent::selectTable_f_mdl($store_sql);

        $front_store_url ="https://" . common::PARENT_STORE_NAME . "/collections/" .$stor_data[0]["shop_collection_handle"];
        if($stor_data[0]['store_sale_type_master_id']=='1'){
            $sql_ondemand_mail = 'SELECT subject,body,recipients FROM `email_templates_master` WHERE id=30';
            $et_data_ondemand = parent::selectTable_f_mdl($sql_ondemand_mail);

            $store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
            $store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;

            $logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
            $logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
            $body ='';
            $subject ='';
            $to_email =$email;
            if (!empty($et_data_ondemand)) {
                $body    = $et_data_ondemand[0]['body'];
                $subject = $et_data_ondemand[0]['subject'];
            }    
            $storeUrl = '<a target="_blank" href="https://'.$_SERVER['HTTP_HOST'].'/store-owners/login.php">https://'.$_SERVER['HTTP_HOST'].'/store-owners/login.php</a>';
            $body     = str_replace('{{FIRST_NAME}}', $first_name, $body);
            $body     = str_replace('{{STORE_NAME}}', $store_name, $body);
            $body     = str_replace('{{LOGIN_URL}}', $storeUrl, $body);
            $body     = str_replace('{{TOTAL_ITEM}}', $soldItems, $body);
            $body     = str_replace('{{LAST_7_DAYS_ITEMS_SOLD}}', $lastWeeksoldItems, $body);
            $body 	  = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
            $body     = str_replace('{{FRONT_STORE_URL}}', trim($front_store_url), $body);
            $body     = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
            $body     = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);

            $html     = $body;
            if($stor_data[0]['verification_status'] == 1){
                $to_email = $email;
                $mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $html, $html);
                /*send mail store manager */
                $store_owner_details_master_id = $store_owner_id;
                $sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_details_master_id . '"';
                $smm_data =  parent::selectTable_f_mdl($sql_managerData);
                if(!empty($smm_data)){
                    foreach ($smm_data as $managerData) {
                        $body     = $et_data_ondemand[0]['body'];
                        $to_email = $managerData['email'];

                        $body     = str_replace('{{FIRST_NAME}}', $managerData['first_name'], $body);
                        $body     = str_replace('{{STORE_NAME}}', $store_name, $body);
                        $body     = str_replace('{{LOGIN_URL}}', $storeUrl, $body);
                        $body     = str_replace('{{TOTAL_ITEM}}', $soldItems, $body);
                        $body     = str_replace('{{LAST_7_DAYS_ITEMS_SOLD}}', $lastWeeksoldItems, $body);
                        $body 	  = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
                        $body     = str_replace('{{FRONT_STORE_URL}}', trim($front_store_url), $body);
                        $body     = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
                        $body     = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);

                        $html     = $body;
                        $mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $html, $html);
                    }
                }
                /*send mail store manager */
            }
        }else{
            $sql = 'SELECT subject,body,recipients FROM `email_templates_master` WHERE id=18';
            $et_data = parent::selectTable_f_mdl($sql);

            $store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
            $store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;

            $logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
            $logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
            $body ='';
            $subject ='';
            $to_email =$email;
            if (!empty($et_data)) {
                $body    = $et_data[0]['body'];
                $subject = $et_data[0]['subject'];
            }    
            $storeUrl = '<a target="_blank" href="https://'.$_SERVER['HTTP_HOST'].'/store-owners/login.php">https://'.$_SERVER['HTTP_HOST'].'/store-owners/login.php</a>';
            $body     = str_replace('{{FIRST_NAME}}', $first_name, $body);
            $body     = str_replace('{{STORE_NAME}}', $store_name, $body);
            $body     = str_replace('{{LOGIN_URL}}', $storeUrl, $body);
            $body     = str_replace('{{TOTAL_ITEM}}', $soldItems, $body);  
            $body     = str_replace('{{LAST_7_DAYS_ITEMS_SOLD}}', $lastWeeksoldItems, $body);
            $body 	  = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body); 
            $body     = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
            $body     = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
            $body     = str_replace('{{FRONT_STORE_URL}}', trim($front_store_url), $body);

            $html     = $body;
            if($stor_data[0]['verification_status'] == 1){
                $to_email = $email;
                $mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $html, $html);
                /*send mail store manager */
                $store_owner_details_master_id = $store_owner_id;
                $sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_details_master_id . '"';
                $smm_data =  parent::selectTable_f_mdl($sql_managerData);
                if(!empty($smm_data)){
                    foreach ($smm_data as $managerData) {
                        $body     = $et_data[0]['body'];
                        $to_email = $managerData['email'];

                        $body     = str_replace('{{FIRST_NAME}}', $managerData['first_name'], $body);
                        $body     = str_replace('{{STORE_NAME}}', $store_name, $body);
                        $body     = str_replace('{{LOGIN_URL}}', $storeUrl, $body);
                        $body     = str_replace('{{TOTAL_ITEM}}', $soldItems, $body);
                        $body     = str_replace('{{LAST_7_DAYS_ITEMS_SOLD}}', $lastWeeksoldItems, $body);
                        $body 	  = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
                        $body     = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
                        $body     = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
                        $body     = str_replace('{{FRONT_STORE_URL}}', trim($front_store_url), $body);
                        $html     = $body;
                        $mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $html, $html);
                    }
                }
                /*send mail store manager */
            }
        }
    }
}