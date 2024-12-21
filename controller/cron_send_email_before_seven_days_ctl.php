<?php
use PHPShopify\Product;

include_once 'model/cron_send_email_before_seven_days_mdl.php';
include_once $path . '/libraries/Aws3.php';

class cron_send_email_before_seven_days_ctl extends cron_send_email_before_seven_days_mdl
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
                $is_extend_store = $store_data['is_extend_store'];
                if (!empty($store_data['store_close_date'])) {
                    $store_close_date = date('Y-m-d', $store_data['store_close_date']);
                    $back7Days = date('Y-m-d', strtotime('-6 days', strtotime($store_close_date)));
                    $back3Days = date('Y-m-d', strtotime('-2 days', strtotime($store_close_date)));
                    if (date('Y-m-d') == $back7Days) {
                        $this->sendMinimumProductSummeryEmail($store_master_id,$store_name,$first_name,$email,19,$store_owner_id);
                    }elseif(date('Y-m-d') ==$back3Days){
                        $this->sendMinimumProductSummeryEmail($store_master_id,$store_name,$first_name,$email,23,$store_owner_id);
                    }elseif (date('Y-m-d') > $store_close_date) {
                        if($is_extend_store=='1'){
                            $this->sendMinimumProductSummeryEmail($store_master_id,$store_name,$first_name,$email,25,$store_owner_id);
                        }
                    }
                }
            }
        }
    }

    public function sendMinimumProductSummeryEmail($store_master_id,$store_name,$first_name,$email,$templateId,$store_owner_id)
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

        $product_group_name = parent::getGroupName_f_mdl($store_master_id);
        $emailData = array();
        $minimumMetAarray = array();
        foreach ($product_group_name as $group_name) {
            $store_owner_group_name        = $group_name['group_name'];
            $groupItemSql = 'SELECT id FROM store_owner_product_master WHERE group_name="'.$store_owner_group_name.'" and store_master_id = "'.$store_master_id.'" ';
            $groupItemDetails = parent::selectTable_f_mdl($groupItemSql);

            $store_sql = 'SELECT verification_status,shop_collection_handle FROM `store_master` WHERE id="'.$store_master_id.'"';
            $stor_data = parent::selectTable_f_mdl($store_sql);
            

            

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
            $minimum_group_value = parent::get_minimums_f_mdl($store_owner_group_name);

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

            $emailData[]         = "<br>" . "Product Group Name: " . $group_name['group_name'] . "<br>" . "Minimum for this product group:$group_value " . "<br>" . "# of items sold: $soldItems " . "<br>" . "# of items needed to meet minimum: $minimums"."<br>";
        }

        $htmlValues='';
        foreach ($emailData as $key => $value) {
           $htmlValues.=$value;
        }
        
        $sql            = 'SELECT subject,body,recipients FROM `email_templates_master` WHERE id = '.$templateId.'';
        $minimums_data  = parent::selectTable_f_mdl($sql);

        $sql = 'SELECT * FROM store_master WHERE id="'.$store_master_id.'"';
        $store_data = parent::selectTable_f_mdl($sql);

		$store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
		$store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;

        $logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
        $logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
        $front_store_url ="https://" . common::PARENT_STORE_NAME . "/collections/" .$stor_data[0]["shop_collection_handle"];

        $body           = '';
        $subject        = '';
        if (!empty($minimums_data)) {
            $body    = $minimums_data[0]['body'];
            $subject = $minimums_data[0]['subject'];
        }


        $manualOrderLink = '<a href="'.common::CUSTOMER_PORTAL_SITE_URL.'closeStoreList.php?do=closeStoreList">Click here</a>';
        $dashboardLink   = '<a href="'.common::CUSTOMER_PORTAL_SITE_URL.'index.php?do=stores">Click here</a>';
        if($templateId=='23'){
            $body     = str_replace('{{ORDER_LINK}}', $manualOrderLink, $body);
            $body     = str_replace('{{STORE_NAME}}', $store_name, $body);
            $body     = str_replace('{{DASHBOARD_LINK}}', $dashboardLink, $body);
            $body     = str_replace('{{FRONT_STORE_URL}}', trim($front_store_url), $body);
            $body     = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
            $body     = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
        }elseif ($templateId=='25') {
            $body     = str_replace('{{ORDER_LINK}}', $manualOrderLink, $body);
            $body     = str_replace('{{STORE_NAME}}', $store_name, $body);
            $body     = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
            $body     = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
            $body     = str_replace('{{FRONT_STORE_URL}}', trim($front_store_url), $body);
        }elseif ($templateId=='19'){
            $body     = str_replace('{{DASHBOARD_LINK}}', $dashboardLink, $body);
            $body     = str_replace('{{FRONT_STORE_URL}}', trim($front_store_url), $body);
        }

        $body     = str_replace('{{FIRST_NAME}}', $first_name, $body);
        $body     = str_replace('{{STORE_NAME}}', $store_name, $body);
        $body     = str_replace('{{SOLD_ITEMS}}', $soldItems,  $body);
        $body     = str_replace('{{PRODUCTS_SUMMERY}}', $htmlValues, $body);
        $body 	  = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
        $body     = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
        $body     = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
        $html     = $body;
        $to_email = $email;
        if($stor_data[0]['verification_status'] == 1){
        
            if (in_array("1", $minimumMetAarray)){
                $res = $objAWS->sendEmail([$to_email], $subject, $html,'');
                /*send mail store manager */
                $store_owner_details_master_id = $store_owner_id;
                $sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_details_master_id . '"';
                $smm_data =  parent::selectTable_f_mdl($sql_managerData);
                if(!empty($smm_data)){
                    foreach ($smm_data as $managerData) {
                        $body    = $minimums_data[0]['body'];
                        $to_email   = $managerData['email'];
                        $body       = str_replace('{{FIRST_NAME}}', $managerData['first_name'], $body);
                        $body       = str_replace('{{STORE_NAME}}', $store_name, $body);
                        $body 		= str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
                        $body       = str_replace('{{DASHBOARD_LINK}}', $dashboardLink, $body);
                        $body       = str_replace('{{FRONT_STORE_URL}}', trim($front_store_url), $body);
                        $body       = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
                        $body       = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
                        $body       = str_replace('{{SOLD_ITEMS}}', $soldItems,  $body);
                        $body       = str_replace('{{PRODUCTS_SUMMERY}}', $htmlValues, $body);
                        $body       = str_replace('{{ORDER_LINK}}', $manualOrderLink, $body);
                        $html       = $body;
                        $res = $objAWS->sendEmail([$to_email], $subject, $html,'');
                    }
                }
                /*send mail store manager */
            }
            else{
                $sqlmail  = 'SELECT subject,body,recipients FROM `email_templates_master` WHERE id ="26"';
                $minimums_data_get  = parent::selectTable_f_mdl($sqlmail);
                $store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
		        $store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;
                $logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
                $logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
                $body           = '';
                $subject        = '';
                if (!empty($minimums_data_get)) {
                    $body    = $minimums_data_get[0]['body'];
                    $subject = $minimums_data_get[0]['subject'];
                }

                $body     = str_replace('{{FIRST_NAME}}', $first_name, $body);
                $body     = str_replace('{{STORE_NAME}}', $store_name, $body);
                $subject  = str_replace('{{STORE_NAME}}', $store_name, $subject);
                $body 	  = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
                $body     = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
                $body     = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
                $body       = str_replace('{{FRONT_STORE_URL}}', trim($front_store_url), $body);

                $html     = $body;
                $to_email = $email;

                $res1 = $objAWS->sendEmail([$to_email], $subject, $html,'');
                /*send mail store manager */
                $store_owner_details_master_id = $store_owner_id;
                $sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_details_master_id . '"';
                $smm_data =  parent::selectTable_f_mdl($sql_managerData);
                if(!empty($smm_data)){
                    foreach ($smm_data as $managerData) {
                        $body       = $minimums_data_get[0]['body'];
                        $to_email   = $managerData['email'];
                        $body       = str_replace('{{FIRST_NAME}}', $managerData['first_name'], $body);
                        $subject    = str_replace('{{STORE_NAME}}', $store_name, $subject);
                        $body       = str_replace('{{STORE_NAME}}', $store_name, $body);
                        $body 	    = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
                        $body       = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
                        $body       = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
                        $html       = $body;
                        $res1 = $objAWS->sendEmail([$to_email], $subject, $html,'');
                    }
                }
                /*send mail store manager */  
            }  
        }
    }
}
?>