<?php
use PHPShopify\Product;

include_once 'model/cron_check_order_delivery_status_mdl.php';
include_once $path . '/libraries/Aws3.php';
class cron_check_order_delivery_status_ctl extends cron_check_order_delivery_status_mdl
{
    function __construct()
    {
        $this->check_sopify_order_delivery_status();
    }

    function check_sopify_order_delivery_status()
    {
        $OrderSql = 'SELECT om.store_master_id,om.store_sale_type,om.shop_order_id,om.shop_order_number,om.cust_email,om.cust_name,om.id as store_order_master_id FROM `store_orders_master` as om LEFT JOIN store_master as sm ON sm.id=om.store_master_id WHERE om.shop_order_id != "" AND om.is_order_cancel = 0 AND om.store_sale_type = "On-Demand" AND om.is_sent_delivery_mail="0" AND om.created_on > "2023-12-31 11:59:59" ';
        $OrderData = parent::selectTable_f_mdl($OrderSql);
        if (!empty($OrderData)) {
            foreach ($OrderData as $order_data) {
                $order_master_id  = $order_data['store_order_master_id'];
                $store_master_id  = $order_data['store_master_id'];
                $shop_order_id       = $order_data['shop_order_id'];
                $shop_order_number       = $order_data['shop_order_number'];
                $cust_email       = $order_data['cust_email'];
                $cust_name       = $order_data['cust_name'];
                
                $this->getSopifyOrderDeliveryStatus($store_master_id,$order_master_id,$shop_order_id,$shop_order_number,$cust_email,$cust_name);
            }
        }

        //send email id=41 flash sale only when order is less then 10 after store launch after 7 days
        $store_sql = "SELECT sodm.id as store_owner_id,sodm.first_name,sodm.last_name,sm.id,sodm.email,sm.store_name,sm.store_close_date,sm.shop_collection_handle FROM store_owner_details_master as sodm LEFT JOIN store_master as sm ON sodm.id = sm.store_owner_details_master_id WHERE sm.status = '1' AND sm.lessthenten_orderemai_sent='0' AND sm.store_sale_type_master_id='1' AND sm.verification_status = '1' AND DATEDIFF(CURDATE(), sm.approved_date) = 7";	
        $storeData = parent::selectTable_f_mdl($store_sql);
        if(!empty($storeData)){
            foreach($storeData as $store_data){
                $store_owner_id     = $store_data['store_owner_id'];
                $first_name         = $store_data['first_name'];
                $last_name          = $store_data['last_name'];
                $email              = $store_data['email'];
                $store_name         = $store_data['store_name'];
                $store_close_date   = $store_data['store_close_date'];
                $store_master_id    = $store_data['id'];
                $shop_collection_handle = $store_data['shop_collection_handle'];
                $this->sendEmailAfterTenDaysLaunchOrderLessThenTen($store_owner_id,$first_name,$last_name,$email,$store_name,$store_close_date,$store_master_id,$shop_collection_handle);
            }
        }
    }

    public function getSopifyOrderDeliveryStatus($store_master_id,$order_master_id,$shop_order_id,$shop_order_number,$cust_email,$cust_name)
    {   
        $res = [];
        $s3Obj = new Aws3;
        require_once('lib/shopify.php');
        require_once(common::EMAIL_REQUIRE_URL);
        if (strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp') !== false) {
            $objAWS = new aws_ses_smtp();
        } else if (strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail') !== false) {
            $objAWS = new sendGridEmail();
        } else {
            $objAWS = new Aws(common::AWS_ACCESS_KEY, common::AWS_SECRET_KEY, common::AWS_REGION);
        }

        $shop = common::PARENT_STORE_NAME;
		$sql = "SELECT id, shop_name, token FROM `shop_management` WHERE shop_name = '".$shop."' LIMIT 1";
		$shop_data = parent::selectTable_f_mdl($sql);
		if(!empty($shop_data)) {
			$shop = $shop_data[0]['shop_name'];
			$token = $shop_data[0]['token'];

            try{

                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://'.$shop.'/admin/api/2023-10/orders/'.$shop_order_id.'.json',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'X-Shopify-Access-Token: '.$token.''
                    ),
                ));
    
                $response = curl_exec($curl);
    
                curl_close($curl);
            }catch(Exception $e) {
                $response= $e;
            }
            
        }else{
			$res['MESSAGE'] = "Invalid token";
			$res['STATUS'] = true;
		}

        if (!empty($response)) {
            $resultData = json_decode($response, true);
        }

        /*sent mail after 10 days order fulfilled start*/
        if(!empty($resultData['order']['fulfillments'])){
            $fulfillment_status=$resultData['order']['fulfillments'][0]['line_items'][0]['fulfillment_status'];
            $fulfillment_created_at=$resultData['order']['fulfillments'][0]['created_at'];
            // $date = new DateTime($fulfillment_created_at);
            // $delivery_datetime = $date->format('Y-m-d H:i:s');

            $updateorder_delivery = [
				"delivery_status"=>$fulfillment_status,
				"delivery_datetime"=>$fulfillment_created_at
			];	
			$resPonseData = parent::updateTable_f_mdl('store_orders_master',$updateorder_delivery,'id="'.$order_master_id.'"');	

            $fulfillment_created = new DateTime($fulfillment_created_at);
            $current_date = new DateTime();
            $interval = $current_date->diff($fulfillment_created);
            $days_difference = $interval->days;
            if ($days_difference > 10) {

                $sql = 'SELECT subject,body FROM `email_templates_master` WHERE id=40';
                $et_data = parent::selectTable_f_mdl($sql);
                $sql_store = 'SELECT * FROM store_master WHERE id="'.$store_master_id.'"';
                $store_data = parent::selectTable_f_mdl($sql_store);
                $store_name='';
                if(!empty($store_data)){
                    $store_name=$store_data[0]['store_name'];
                }
                
                $logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
                $logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
                $body ='';
                $subject ='';
                $to_email =$cust_email;
                if (!empty($et_data)) {
                    $body    = $et_data[0]['body'];
                    $subject = $et_data[0]['subject'];
                    $ccMails = '';
                }

                $subject = str_replace('{{ORDER_NUMBER}}',$shop_order_number,$subject);
                $subject = str_replace('{{STORE_NAME}}',$store_name,$subject);

                $body  = str_replace('{{CUSTOMER_FIRST_NAME}}', $cust_name, $body);
                $body  = str_replace('{{STORE_NAME}}', $store_name, $body);
                $body  = str_replace('{{ORDER_NUMBER}}',$shop_order_number,$body);
                $body  = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
                $html  = $body;

                $mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $html, $html,$ccMails);   
                if($mailSendStatus=='1'){
                    $update_delivery_mail = [
                        "is_sent_delivery_mail"=>'1'
                    ];	
                    $resPonseData = parent::updateTable_f_mdl('store_orders_master',$update_delivery_mail,'id="'.$order_master_id.'"');	

                } 
            }  
        } 
        /*sent mail after 10 days order fulfilled end*/

        // if(!empty($resultData['order']['fulfillments'][0]['shipment_status'])){
        //     $delivery_status=$resultData['order']['fulfillments'][0]['shipment_status'];
        //     $updated_at=$resultData['order']['fulfillments'][0]['updated_at'];
        //     $date = new DateTime($updated_at);
        //     $delivery_datetime = $date->format('Y-m-d H:i:s');

        //     $updateorder_delivery = [
		// 		"delivery_status"=>$delivery_status,
		// 		"delivery_datetime"=>$delivery_datetime
		// 	];	
		// 	$resPonseData = parent::updateTable_f_mdl('store_orders_master',$updateorder_delivery,'id="'.$order_master_id.'"');	

        //     $delivery_date = new DateTime($delivery_datetime);
        //     $current_date = new DateTime();
        //     $interval = $current_date->diff($delivery_date);
        //     $days_difference = $interval->days;
        //     if ($days_difference > 2) {

        //         $sql = 'SELECT subject,body FROM `email_templates_master` WHERE id=40';
        //         $et_data = parent::selectTable_f_mdl($sql);
        //         $sql_store = 'SELECT * FROM store_master WHERE id="'.$store_master_id.'"';
        //         $store_data = parent::selectTable_f_mdl($sql_store);
        //         $store_name='';
        //         if(!empty($store_data)){
        //             $store_name=$store_data[0]['store_name'];
        //         }
                
        //         $logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
        //         $logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
        //         $body ='';
        //         $subject ='';
        //         $to_email =$cust_email;
        //         if (!empty($et_data)) {
        //             $body    = $et_data[0]['body'];
        //             $subject = $et_data[0]['subject'];
        //             $ccMails = '';
        //         }

        //         $subject = str_replace('{{ORDER_NUMBER}}',$shop_order_number,$subject);
        //         $subject = str_replace('{{STORE_NAME}}',$store_name,$subject);

        //         $body  = str_replace('{{CUSTOMER_FIRST_NAME}}', $cust_name, $body);
        //         $body  = str_replace('{{STORE_NAME}}', $store_name, $body);
        //         $body  = str_replace('{{ORDER_NUMBER}}',$shop_order_number,$body);
        //         $body  = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
        //         $html  = $body;

        //         $mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $html, $html,$ccMails);   
        //         if($mailSendStatus=='1'){
        //             $update_delivery_mail = [
        //                 "is_sent_delivery_mail"=>'1'
        //             ];	
        //             $resPonseData = parent::updateTable_f_mdl('store_orders_master',$update_delivery_mail,'id="'.$order_master_id.'"');	

        //         } 
        //     }  
        // } 

        $resultResp = array();
		$resultResp["isSuccess"] = "1";
		$resultResp["msg"] = "Delivery email sent successfully.";
    }

    public function sendEmailAfterTenDaysLaunchOrderLessThenTen($store_owner_id,$first_name,$last_name,$email,$store_name,$store_close_date,$store_master_id,$shop_collection_handle)
    {   
        $res = [];
        $s3Obj = new Aws3;
        require_once('lib/shopify.php');
        require_once(common::EMAIL_REQUIRE_URL);
        if (strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp') !== false) {
            $objAWS = new aws_ses_smtp();
        } else if (strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail') !== false) {
            $objAWS = new sendGridEmail();
        } else {
            $objAWS = new Aws(common::AWS_ACCESS_KEY, common::AWS_SECRET_KEY, common::AWS_REGION);
        }
        $storeurl   = common::SITE_URL."sa-store-view.php?stkn=&id=".$store_master_id;
        $front_store_url ="https://" . common::PARENT_STORE_NAME . "/collections/" .$shop_collection_handle;
       
        $logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
        $logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
        $totalordersql = "SELECT count(id) as total_order FROM store_orders_master where store_master_id='".$store_master_id."' AND order_type='1' ";
        $totalOrderData = parent::selectTable_f_mdl($totalordersql);
        if(!empty($totalOrderData)){
            $total_order = $totalOrderData[0]['total_order'];
        }else{
            $total_order = 0;
        }

        if($total_order<=10){

            $ordersql = "SELECT created_on FROM store_orders_master WHERE store_master_id='".$store_master_id."' ORDER BY id DESC LIMIT 1 ";
            $ordersqlData = parent::selectTable_f_mdl($ordersql);
            $order_last_date = !empty($ordersqlData[0]["created_on"]) ? date('m/d/Y', strtotime($ordersqlData[0]["created_on"])) : '';
            /*sent mail after 10 days order less then 10 start*/
            $sql = 'SELECT subject,body FROM `email_templates_master` WHERE id=42';
            $et_data = parent::selectTable_f_mdl($sql);
            $body = $subject ='';
            $to_email =$email;
            if (!empty($et_data)) {
                $body    = $et_data[0]['body'];
                $subject = $et_data[0]['subject'];
                $ccMails = '';
            }

            $subject = str_replace('{{STORE_NAME}}',$store_name,$subject);
            $body  = str_replace('{{FIRST_NAME}}', $first_name, $body);
            $body  = str_replace('{{STORE_NAME}}', $store_name, $body);
            $body  = str_replace('{{STORE_URL}}', $storeurl, $body);
            $body  = str_replace('{{TOTAL_ORDERS}}', $total_order, $body);
            $body  = str_replace('{{FRONT_STORE_URL}}', trim($front_store_url), $body);
            $body  = str_replace('{{LAST_ORDER_DATE}}', $order_last_date, $body);
            $body  = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
            $html  = $body;
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
                    $subject = str_replace('{{STORE_NAME}}',$store_name,$subject);
                    $body  = str_replace('{{FIRST_NAME}}', $first_name, $body);
                    $body  = str_replace('{{STORE_NAME}}', $store_name, $body);
                    $body  = str_replace('{{STORE_URL}}', $storeurl, $body);
                    $body  = str_replace('{{TOTAL_ORDERS}}', $total_order, $body);
                    $body  = str_replace('{{FRONT_STORE_URL}}', trim($front_store_url), $body);
                    $body  = str_replace('{{LAST_ORDER_DATE}}', $order_last_date, $body);
                    $body  = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
                    $mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body);
                }
            }
            /*send mail store manager */
            $emailHistoryData = [
                "store_master_id"            => $store_master_id,
                "email_template_id"            => '41',
                "store_name"   				 => $store_name,
                "email"   				     => $to_email,
                "subject"   				 => $subject,
                "update_on"                 => date('Y-m-d H:i:s')
            ];
            parent::insertTable_f_mdl('less_then_ten_order_email_history',$emailHistoryData);
            $updateData = [
                "lessthenten_orderemai_sent" => '1'
            ];
            $resPonseData = parent::updateTable_f_mdl('store_master',$updateData,'id="'.$store_master_id.'"');	
            /*sent mail after 10 days order fulfilled end*/
        }
        $resultResp = array();
		$resultResp["isSuccess"] = "1";
		$resultResp["msg"] = "Delivery email sent successfully.";
    }

}