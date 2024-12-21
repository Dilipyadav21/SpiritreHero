<?php
//https://app.spirithero.com/webhooks.php?action=order_create

include_once 'model/webhooks_mdl.php';
include_once $path . '/libraries/Aws3.php';
$s3Obj = new Aws3;
class webhooks_ctl_test extends webhooks_mdl
{
    function __construct(){
        if(isset($_REQUEST['action'])){
            $action = $_REQUEST['action'];
            if($action=='order_create'){
                $this->order_create();
                exit;
            }
        }
    }

    public function order_create(){
        global $s3Obj;
        //$data = file_get_contents('php://input');

        //$varStoreName = $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'];
        $varStoreName = 'spirithero1.myshopify.com';

        $data = '{"id":2628173987889,"admin_graphql_api_id":"gid:\/\/shopify\/Order\/2628173987889","app_id":580111,"browser_ip":"67.135.192.141","buyer_accepts_marketing":false,"cancel_reason":null,"cancelled_at":null,"cart_token":"ea9d740fe64c730e1ff275e30483eda3","checkout_id":17218203058225,"checkout_token":"43d39b33eabc591b8f84d046bc26381a","client_details":{"accept_language":"en-US,en;q=0.9","browser_height":585,"browser_ip":"67.135.192.141","browser_width":339,"session_hash":null,"user_agent":"Mozilla\/5.0 (Linux; Android 11; SAMSUNG SM-G981U1) AppleWebKit\/537.36 (KHTML, like Gecko) SamsungBrowser\/13.2 Chrome\/83.0.4103.106 Mobile Safari\/537.36"},"closed_at":"2021-02-17T14:19:34-08:00","confirmed":true,"contact_email":"balagasrinivasrao@gmail.com","created_at":"2021-01-27T19:26:33-08:00","currency":"USD","current_subtotal_price":"50.00","current_subtotal_price_set":{"shop_money":{"amount":"50.00","currency_code":"USD"},"presentment_money":{"amount":"50.00","currency_code":"USD"}},"current_total_discounts":"0.00","current_total_discounts_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}},"current_total_duties_set":null,"current_total_price":"53.63","current_total_price_set":{"shop_money":{"amount":"53.63","currency_code":"USD"},"presentment_money":{"amount":"53.63","currency_code":"USD"}},"current_total_tax":"3.63","current_total_tax_set":{"shop_money":{"amount":"3.63","currency_code":"USD"},"presentment_money":{"amount":"3.63","currency_code":"USD"}},"customer_locale":"en","device_id":null,"discount_codes":[],"email":"balagasrinivasrao@gmail.com","financial_status":"paid","fulfillment_status":"fulfilled","landing_site":"\/collections\/patterson","landing_site_ref":null,"location_id":null,"name":"#23301","note":null,"note_attributes":[],"number":22301,"order_number":23301,"order_status_url":"https:\/\/spirithero.com\/19515059\/orders\/6778c2296cb8153c7363e2deb6e479cb\/authenticate?key=6b0cfddc43c2790096c2895fa040ab12","original_total_duties_set":null,"payment_gateway_names":["payflow"],"phone":null,"presentment_currency":"USD","processed_at":"2021-01-27T19:26:30-08:00","processing_method":"direct","reference":null,"referring_site":"","source_identifier":null,"source_name":"web","source_url":null,"subtotal_price":"50.00","subtotal_price_set":{"shop_money":{"amount":"50.00","currency_code":"USD"},"presentment_money":{"amount":"50.00","currency_code":"USD"}},"tags":"","tax_lines":[{"price":"3.63","rate":0.0725,"title":"California State Tax","price_set":{"shop_money":{"amount":"3.63","currency_code":"USD"},"presentment_money":{"amount":"3.63","currency_code":"USD"}}}],"taxes_included":false,"test":false,"token":"6778c2296cb8153c7363e2deb6e479cb","total_discounts":"0.00","total_discounts_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}},"total_line_items_price":"50.00","total_line_items_price_set":{"shop_money":{"amount":"50.00","currency_code":"USD"},"presentment_money":{"amount":"50.00","currency_code":"USD"}},"total_outstanding":"0.00","total_price":"53.63","total_price_set":{"shop_money":{"amount":"53.63","currency_code":"USD"},"presentment_money":{"amount":"53.63","currency_code":"USD"}},"total_shipping_price_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}},"total_tax":"3.63","total_tax_set":{"shop_money":{"amount":"3.63","currency_code":"USD"},"presentment_money":{"amount":"3.63","currency_code":"USD"}},"total_tip_received":"0.00","total_weight":0,"updated_at":"2021-02-22T14:47:22-08:00","user_id":null,"billing_address":{"first_name":"Srinivasrao ","address1":"3795 southampton terrace","phone":"2485508421","city":"Fremont","zip":"94555","province":"California","country":"United States","last_name":"Balaga","address2":"Fremont","company":"","latitude":37.577571,"longitude":-122.042814,"name":"Srinivasrao  Balaga","country_code":"US","province_code":"CA"},"customer":{"id":3633178509361,"email":"balagasrinivasrao@gmail.com","accepts_marketing":false,"created_at":"2021-01-27T19:25:16-08:00","updated_at":"2021-01-27T19:26:34-08:00","first_name":"Srinivasrao ","last_name":"Balaga","state":"disabled","note":null,"verified_email":true,"multipass_identifier":null,"tax_exempt":false,"phone":null,"tags":"","currency":"USD","accepts_marketing_updated_at":"2021-01-27T19:25:16-08:00","marketing_opt_in_level":null,"admin_graphql_api_id":"gid:\/\/shopify\/Customer\/3633178509361","default_address":{"id":3912159428657,"customer_id":3633178509361,"first_name":"Srinivasrao ","last_name":"Balaga","company":"","address1":"3795 southampton terrace","address2":"Fremont","city":"Fremont","province":"California","country":"United States","zip":"94555","phone":"2485508421","name":"Srinivasrao  Balaga","province_code":"CA","country_code":"US","country_name":"United States","default":true}},"discount_applications":[],"fulfillments":[{"id":2509059358769,"admin_graphql_api_id":"gid:\/\/shopify\/Fulfillment\/2509059358769","created_at":"2021-02-17T14:19:33-08:00","location_id":41754254,"name":"#23301.1","order_id":2628173987889,"receipt":{},"service":"manual","shipment_status":"delivered","status":"success","tracking_company":"USPS","tracking_number":"9405511298370124989733","tracking_numbers":["9405511298370124989733"],"tracking_url":"https:\/\/tools.usps.com\/go\/TrackConfirmAction.action?tLabels=9405511298370124989733","tracking_urls":["https:\/\/tools.usps.com\/go\/TrackConfirmAction.action?tLabels=9405511298370124989733"],"updated_at":"2021-02-22T14:47:22-08:00","line_items":[{"id":5668979966001,"admin_graphql_api_id":"gid:\/\/shopify\/LineItem\/5668979966001","fulfillable_quantity":0,"fulfillment_status":"fulfilled","gift_card":false,"grams":0,"name":"Patterson Elementary School-Unisex Hoodie - Youth M (size 10\/12) \/ Navy","price":"28.00","price_set":{"shop_money":{"amount":"28.00","currency_code":"USD"},"presentment_money":{"amount":"28.00","currency_code":"USD"}},"product_exists":true,"product_id":4806450217009,"properties":[{"name":"_is_spiritapp_product","value":""}],"quantity":1,"requires_shipping":true,"sku":"18500B","taxable":true,"title":"Patterson Elementary School-Unisex Hoodie","total_discount":"0.00","total_discount_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}},"variant_id":32556228804657,"variant_inventory_management":null,"variant_title":"Youth M (size 10\/12) \/ Navy","vendor":"SanMar","tax_lines":[{"price":"2.03","price_set":{"shop_money":{"amount":"2.03","currency_code":"USD"},"presentment_money":{"amount":"2.03","currency_code":"USD"}},"rate":0.0725,"title":"California State Tax"}],"duties":[],"discount_allocations":[]},{"id":5668979998769,"admin_graphql_api_id":"gid:\/\/shopify\/LineItem\/5668979998769","fulfillable_quantity":0,"fulfillment_status":"fulfilled","gift_card":false,"grams":0,"name":"Patterson Elementary School-Unisex T-Shirt - Youth M (size 10\/12) \/ Deep Navy","price":"11.00","price_set":{"shop_money":{"amount":"11.00","currency_code":"USD"},"presentment_money":{"amount":"11.00","currency_code":"USD"}},"product_exists":true,"product_id":4806449037361,"properties":[{"name":"_is_spiritapp_product","value":""}],"quantity":2,"requires_shipping":true,"sku":"5000B","taxable":true,"title":"Patterson Elementary School-Unisex T-Shirt","total_discount":"0.00","total_discount_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}},"variant_id":32556225757233,"variant_inventory_management":null,"variant_title":"Youth M (size 10\/12) \/ Deep Navy","vendor":"SanMar","tax_lines":[{"price":"1.60","price_set":{"shop_money":{"amount":"1.60","currency_code":"USD"},"presentment_money":{"amount":"1.60","currency_code":"USD"}},"rate":0.0725,"title":"California State Tax"}],"duties":[],"discount_allocations":[]}]}],"line_items":[{"id":5668979966001,"admin_graphql_api_id":"gid:\/\/shopify\/LineItem\/5668979966001","fulfillable_quantity":0,"fulfillment_status":"fulfilled","gift_card":false,"grams":0,"name":"Patterson Elementary School-Unisex Hoodie - Youth M (size 10\/12) \/ Navy","price":"28.00","price_set":{"shop_money":{"amount":"28.00","currency_code":"USD"},"presentment_money":{"amount":"28.00","currency_code":"USD"}},"product_exists":true,"product_id":4806450217009,"properties":[{"name":"_is_spiritapp_product","value":""}],"quantity":1,"requires_shipping":true,"sku":"18500B","taxable":true,"title":"Patterson Elementary School-Unisex Hoodie","total_discount":"0.00","total_discount_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}},"variant_id":32556228804657,"variant_inventory_management":null,"variant_title":"Youth M (size 10\/12) \/ Navy","vendor":"SanMar","tax_lines":[{"price":"2.03","price_set":{"shop_money":{"amount":"2.03","currency_code":"USD"},"presentment_money":{"amount":"2.03","currency_code":"USD"}},"rate":0.0725,"title":"California State Tax"}],"duties":[],"discount_allocations":[]},{"id":5668979998769,"admin_graphql_api_id":"gid:\/\/shopify\/LineItem\/5668979998769","fulfillable_quantity":0,"fulfillment_status":"fulfilled","gift_card":false,"grams":0,"name":"Patterson Elementary School-Unisex T-Shirt - Youth M (size 10\/12) \/ Deep Navy","price":"11.00","price_set":{"shop_money":{"amount":"11.00","currency_code":"USD"},"presentment_money":{"amount":"11.00","currency_code":"USD"}},"product_exists":true,"product_id":4806449037361,"properties":[{"name":"_is_spiritapp_product","value":""}],"quantity":2,"requires_shipping":true,"sku":"5000B","taxable":true,"title":"Patterson Elementary School-Unisex T-Shirt","total_discount":"0.00","total_discount_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}},"variant_id":32556225757233,"variant_inventory_management":null,"variant_title":"Youth M (size 10\/12) \/ Deep Navy","vendor":"SanMar","tax_lines":[{"price":"1.60","price_set":{"shop_money":{"amount":"1.60","currency_code":"USD"},"presentment_money":{"amount":"1.60","currency_code":"USD"}},"rate":0.0725,"title":"California State Tax"}],"duties":[],"discount_allocations":[]}],"refunds":[],"shipping_address":{"first_name":"Srinivasrao ","address1":"3795 southampton terrace","phone":"2485508421","city":"Fremont","zip":"94555","province":"California","country":"United States","last_name":"Balaga","address2":"Fremont","company":"","latitude":37.577571,"longitude":-122.042814,"name":"Srinivasrao  Balaga","country_code":"US","province_code":"CA"},"shipping_lines":[{"id":2189640335409,"carrier_identifier":"1db2e5576ce75de8b6f398fd04fe123e","code":"free-shipping","discounted_price":"0.00","discounted_price_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}},"phone":null,"price":"0.00","price_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}},"requested_fulfillment_service_id":null,"source":"Advanced Shipping Rules","title":"Free Shipping","tax_lines":[],"discount_allocations":[]}]}';

        $result = [];
        if (!empty($data)) {
            $result = json_decode($data, true);
        }
							
        if(isset($result['id']) && !empty($result['id'])){
            $shop_order_id = $result['id'];
            $sql='
                SELECT shop_order_id FROM `store_orders_master`
                WHERE shop_order_id = "'.$shop_order_id.'"
            ';
			
            $order_exist = parent::selectTable_f_mdl($sql);
			
            if(empty($order_exist)){
                //if order is not existed in db, then insert it
                //now check order is from which store
                $sql='
                    SELECT `store_owner_product_master`.store_master_id, `store_sale_type_master`.sale_type FROM `store_owner_product_master`
                    LEFT JOIN `store_master` ON `store_master`.id = `store_owner_product_master`.store_master_id
                    LEFT JOIN `store_sale_type_master` ON `store_sale_type_master`.id = `store_master`.store_sale_type_master_id
                    WHERE `store_owner_product_master`.shop_product_id = "'.$result['line_items'][0]['product_id'].'"
                ';
                $store_id_arr = parent::selectTable_f_mdl($sql);

                if(isset($store_id_arr[0]['store_master_id']) && !empty($store_id_arr[0]['store_master_id'])){
                    $store_master_id = $store_id_arr[0]['store_master_id'];

                    $cust_phone = '';
                    if(isset($result['customer']['phone']) && !empty($result['customer']['phone'])){
                        $cust_phone = $result['customer']['phone'];
                    }else if(isset($result['customer']['default_address']['phone']) && !empty($result['customer']['default_address']['phone'])){
                        $cust_phone = $result['customer']['default_address']['phone'];
                    }else if(isset($result['shipping_address']['phone']) && !empty($result['shipping_address']['phone'])){
                        $cust_phone = $result['shipping_address']['phone'];
                    }else if(isset($result['billing_address']['phone']) && !empty($result['billing_address']['phone'])){
                        $cust_phone = $result['billing_address']['phone'];
                    }

                    $studentName = $sortListName = '';
                    foreach($result['note_attributes'] as $objProperties){
                        if(isset($objProperties['name']) && !empty($objProperties['name'])){
                            if($objProperties['name'] == 'student_name'){
                                $studentName =$objProperties['value'];
                            }
                            if($objProperties['name'] == 'sort_list_name'){
                                $sortListName =$objProperties['value'];
                            }
                        }
                    }
                    $customer_name= $result['customer']['first_name'].' '.$result['customer']['last_name'];      
                    $som_insert_data = [
                        'store_master_id' => $store_master_id,
                        'store_sale_type' => $store_id_arr[0]['sale_type'],
                        'shop_order_id' => $shop_order_id,
                        'shop_order_number' => $result['order_number'],
                        'total_price' => $result['total_price'],
                        'total_fundraising_amount' => "0.00",
                        'shop_cust_id' => $result['customer']['id'],
                        'cust_email' => $result['customer']['email'],
                        'cust_name' => $result['customer']['first_name'].' '.$result['customer']['last_name'],
                        'cust_phone' => $cust_phone,
                        'json_data' => $data,
                        'sortlist_info' => $sortListName,
                        'student_name' => $studentName,
                        'created_on' => @date('Y-m-d H:i:s'),
                        'created_on_ts' => time(),
                    ];

                    $som_arr = parent::insertTable_f_mdl('store_orders_master',$som_insert_data);

                    if(isset($som_arr['insert_id']) && !empty($som_arr['insert_id'])){
                        $total_fundraising_amount = 0;

                        //now insert item data
                        foreach($result['line_items'] as $single_item){
                            $sql='
                                SELECT id, store_owner_product_master_id, fundraising_price FROM `store_owner_product_variant_master`
                                WHERE shop_product_id = "'.$single_item['product_id'].'"
                                AND shop_variant_id = "'.$single_item['variant_id'].'"
                                LIMIT 1
                            ';
                            $store_var_data = parent::selectTable_f_mdl($sql);
							
                            if(!empty($store_var_data)){

                                $productTags = $this->getProductTags($varStoreName,$single_item['product_id']);

                                $total_fundraising_amount += floatval($store_var_data[0]['fundraising_price'])*$single_item['quantity'];

                                $soim_insert_data = [
                                    'store_master_id' => $store_master_id,
                                    'store_owner_product_master_id' => $store_var_data[0]['store_owner_product_master_id'],
                                    'store_owner_product_variant_master_id' => $store_var_data[0]['id'],
                                    'store_orders_master_id' => $som_arr['insert_id'],
                                    'shop_product_id' => $single_item['product_id'],
                                    'shop_variant_id' => $single_item['variant_id'],
                                    'title' => $single_item['title'],
                                    'quantity' => $single_item['quantity'],
                                    'price' => $single_item['price'],
                                    'sku' => $single_item['sku'],
                                    'vendor' => $single_item['vendor'],
									'variant_title' => $single_item['variant_title'],
									'tags' => $productTags,
                                    'created_on' => @date('Y-m-d H:i:s'),
                                    'created_on_ts' => time(),
                                ];
                                parent::insertTable_f_mdl('store_order_items_master',$soim_insert_data);
                            }
                        }

                        //update total_fundraising_amount in main order
                        $som_update_data = [
                            'total_fundraising_amount' => $total_fundraising_amount
                        ];
                        parent::updateTable_f_mdl('store_orders_master',$som_update_data,'id="'.$som_arr['insert_id'].'"');

                        //send mail to store owner
                        $sql = 'SELECT `store_owner_details_master`.first_name, `store_owner_details_master`.email, `store_owner_details_master`.email_notification,`store_master`.store_name
                        FROM `store_master`
                        LEFT JOIN `store_owner_details_master` ON `store_owner_details_master`.id = `store_master`.store_owner_details_master_id
                        WHERE `store_master`.id = "'.$store_master_id.'"
                        ';
                        $store_owner_arr = parent::selectTable_f_mdl($sql);
                        if(!empty($store_owner_arr) && $store_owner_arr[0]['email_notification']=="1"){
                            //if user has enabled email-notification, then he get email
                            require_once("lib/class_aws.php");
                            $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
                            $sql = 'SELECT subject,body FROM `email_templates_master` WHERE id='.common::NEW_ORDER_TO_CUSTOMER_ADMIN;
                            $et_data = parent::selectTable_f_mdl($sql);
                            $logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
                            $logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
                            if(!empty($et_data)){
                                $subject = $et_data[0]['subject'];
                                $body = $et_data[0]['body'];
                                $to_email = $store_owner_arr[0]['email'];
                                $from_email = common::AWS_ADMIN_EMAIL;
                                $attachment = [];

                                $body = str_replace('{{CUSTOMER_NAME}}',$customer_name,$body);
                                $body = str_replace('{{STORE_NAME}}', $store_owner_arr[0]['store_name'], $body);
								$body = str_replace('{{DASHBOARD_LINK}}',common::CUSTOMER_ADMIN_DASHBOARD_URL,$body);
                                $body = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
                                //$mailSendStatus = $objAWS::sendEmail($from_email, $to_email, $subject, $body, $attachment);

                                $sql = 'SELECT * FROM store_master WHERE id="'.$store_master_id.'"';
                                $store_data = parent::selectTable_f_mdl($sql);

                                $mailSendStatus = 1;
                                //if($store_data[0]['email_notification'] == '1'){
                                    //$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body);
                                //}
                            }
                        }
                    }
                }
            }
			
        }
		
		echo "OK";
		http_response_code(200);
		return;
		exit;

    }

    public function getProductTags($storeName,$productId){
        require_once('lib/shopify.php');

		$sql = "SELECT id, shop_name, token FROM `shop_management` WHERE shop_name = '".$storeName."' LIMIT 1";

		$shop_data = parent::selectTable_f_mdl($sql);

		$shop_id = $shop_data[0]['id'];
		$shop = $shop_data[0]['shop_name'];
		$token = $shop_data[0]['token'];
		
		$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);

        $productTags = '';
		try {
            $productJson = $sc->call('GET', '/admin/api/2023-04/products/'.$productId.'.json?fields=tags');	

            $productTags = $productJson['tags'];
		} catch (ShopifyApiException $e){
		} catch (ShopifyCurlException $e) {
        }
        
        return $productTags;
    }
}