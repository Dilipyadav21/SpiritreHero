<?php
//https://app.spirithero.com/webhooks.php?action=order_create

include_once 'model/webhooks_mdl.php';
$path  = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';
$s3Obj = new Aws3;
class webhooks_ctl extends webhooks_mdl
{
    function __construct(){
        if(isset($_REQUEST['action'])){
            $action = $_REQUEST['action'];
            if($action=='order_create'){
                $this->order_create();
                exit;
            }else if($action=='order-shipped'){
                $this->orderShipped();
                exit;
            }else if($action=='order_shipped_fulfillengine'){
                $this->orderShippedFulfillEngine();
                exit;
            }
        }
    }

    public function order_create(){
        global $s3Obj;
        require_once(common::EMAIL_REQUIRE_URL);
        if(strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp')!==false){
            $objAWS = new aws_ses_smtp();
        }else if(strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail')!==false){
            $objAWS = new sendGridEmail();
        }else{
            $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
        }

        $data = file_get_contents('php://input');

        //$varStoreName = $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'];
        $varStoreName = 'spirithero1.myshopify.com';

        //$data = '{"id":2636954009649,"email":"deepshikhabhartia@gmail.com","closed_at":null,"created_at":"2021-02-04T07:59:01-08:00","updated_at":"2021-02-04T07:59:02-08:00","number":22356,"note":null,"token":"086e65cfec5b1bb209d6c0391ed7c9a0","test":false,"total_price":"21.45","subtotal_price":"20.00","total_weight":170,"total_tax":"1.45","taxes_included":false,"currency":"USD","financial_status":"paid","confirmed":true,"total_discounts":"0.00","total_line_items_price":"20.00","cart_token":"4418ea71e382e908c3b0fa250c1790c6","buyer_accepts_marketing":false,"name":"#23356","referring_site":"http:\/\/m.facebook.com\/","landing_site":"\/collections\/weibel","cancelled_at":null,"cancel_reason":null,"checkout_token":"2465b8c987dee3bd570425e963ea3dfd","reference":null,"user_id":null,"location_id":null,"source_identifier":null,"source_url":null,"processed_at":"2021-02-04T07:59:00-08:00","device_id":null,"phone":null,"customer_locale":"en","app_id":580111,"browser_ip":"2601:641:c100:40:c10c:c9e:751e:cce8","client_details":{"accept_language":"en-US,en;q=0.9","browser_height":678,"browser_ip":"2601:641:c100:40:c10c:c9e:751e:cce8","browser_width":360,"session_hash":null,"user_agent":"Mozilla\/5.0 (Linux; Android 11; SM-G991U Build\/RP1A.200720.012; wv) AppleWebKit\/537.36 (KHTML, like Gecko) Version\/4.0 Chrome\/88.0.4324.141 Mobile Safari\/537.36 [FB_IAB\/FB4A;FBAV\/303.0.0.30.122;]"},"landing_site_ref":null,"order_number":23356,"discount_applications":[],"discount_codes":[],"note_attributes":[],"payment_gateway_names":["payflow"],"processing_method":"direct","checkout_id":17306970062897,"source_name":"web","fulfillment_status":null,"tax_lines":[{"price":"1.45","rate":0.0725,"title":"California State Tax","price_set":{"shop_money":{"amount":"1.45","currency_code":"USD"},"presentment_money":{"amount":"1.45","currency_code":"USD"}}}],"tags":"","contact_email":"deepshikhabhartia@gmail.com","order_status_url":"https:\/\/spirithero.com\/19515059\/orders\/086e65cfec5b1bb209d6c0391ed7c9a0\/authenticate?key=baf641a68a8153412c60daf173ae8846","presentment_currency":"USD","total_line_items_price_set":{"shop_money":{"amount":"20.00","currency_code":"USD"},"presentment_money":{"amount":"20.00","currency_code":"USD"}},"total_discounts_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}},"total_shipping_price_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}},"subtotal_price_set":{"shop_money":{"amount":"20.00","currency_code":"USD"},"presentment_money":{"amount":"20.00","currency_code":"USD"}},"total_price_set":{"shop_money":{"amount":"21.45","currency_code":"USD"},"presentment_money":{"amount":"21.45","currency_code":"USD"}},"total_tax_set":{"shop_money":{"amount":"1.45","currency_code":"USD"},"presentment_money":{"amount":"1.45","currency_code":"USD"}},"line_items":[{"id":5687875665969,"variant_id":32582715801649,"title":"Weibel Spirit Wear-Unisex Dry-Fit Shirt","quantity":1,"sku":"YST350","variant_title":"Youth S (size 6\/8) \/ Maroon","vendor":"SanMar","product_id":4816434233393,"requires_shipping":true,"taxable":true,"gift_card":false,"name":"Weibel Spirit Wear-Unisex Dry-Fit Shirt - Youth S (size 6\/8) \/ Maroon","variant_inventory_management":null,"properties":[{"name":"_is_spiritapp_product","value":""}],"product_exists":true,"fulfillable_quantity":1,"grams":170,"price":"20.00","total_discount":"0.00","fulfillment_status":null,"price_set":{"shop_money":{"amount":"20.00","currency_code":"USD"},"presentment_money":{"amount":"20.00","currency_code":"USD"}},"total_discount_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}},"discount_allocations":[],"tax_lines":[{"title":"California State Tax","price":"1.45","rate":0.0725,"price_set":{"shop_money":{"amount":"1.45","currency_code":"USD"},"presentment_money":{"amount":"1.45","currency_code":"USD"}}}]}],"fulfillments":[],"refunds":[],"total_tip_received":"0.0","shipping_lines":[{"id":2197472870449,"title":"Free Shipping","price":"0.00","code":"free-shipping","source":"Advanced Shipping Rules","phone":null,"requested_fulfillment_service_id":null,"carrier_identifier":"1db2e5576ce75de8b6f398fd04fe123e","discounted_price":"0.00","price_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}},"discounted_price_set":{"shop_money":{"amount":"0.00","currency_code":"USD"},"presentment_money":{"amount":"0.00","currency_code":"USD"}},"discount_allocations":[],"tax_lines":[]}],"billing_address":{"first_name":"Deepshikha ","address1":"46434 Briar Place","phone":"(408) 398-3142","city":"Fremont","zip":"94539","province":"California","country":"United States","last_name":"Khaitan ","address2":"","company":"","latitude":37.4948711,"longitude":-121.9196217,"name":"Deepshikha  Khaitan ","country_code":"US","province_code":"CA"},"shipping_address":{"first_name":"Deepshikha ","address1":"46434 Briar Place","phone":"(408) 398-3142","city":"Fremont","zip":"94539","province":"California","country":"United States","last_name":"Khaitan ","address2":"","company":"","latitude":37.4948711,"longitude":-121.9196217,"name":"Deepshikha  Khaitan ","country_code":"US","province_code":"CA"},"customer":{"id":3641562791985,"email":"deepshikhabhartia@gmail.com","accepts_marketing":false,"created_at":"2021-02-04T07:53:35-08:00","updated_at":"2021-02-04T07:59:02-08:00","first_name":"Deepshikha ","last_name":"Khaitan ","state":"disabled","note":null,"verified_email":true,"multipass_identifier":null,"tax_exempt":false,"phone":null,"tags":"","currency":"USD","accepts_marketing_updated_at":"2021-02-04T07:53:35-08:00","marketing_opt_in_level":null,"default_address":{"id":3921244684337,"customer_id":3641562791985,"first_name":"Deepshikha ","last_name":"Khaitan ","company":"","address1":"46434 Briar Place","address2":"","city":"Fremont","province":"California","country":"United States","zip":"94539","phone":"(408) 398-3142","name":"Deepshikha  Khaitan ","province_code":"CA","country_code":"US","country_name":"United States","default":true}}}';

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
                    SELECT `store_owner_product_master`.store_master_id, `store_sale_type_master`.sale_type,`store_owner_product_master`.is_product_fundraising FROM `store_owner_product_master`
                    LEFT JOIN `store_master` ON `store_master`.id = `store_owner_product_master`.store_master_id
                    LEFT JOIN `store_sale_type_master` ON `store_sale_type_master`.id = `store_master`.store_sale_type_master_id
                    WHERE `store_owner_product_master`.shop_product_id = "'.$result['line_items'][0]['product_id'].'"
                ';
                
                $store_id_arr = parent::selectTable_f_mdl($sql);
                $is_product_fundraising='No';
                if(isset($store_id_arr[0]['is_product_fundraising']) && !empty($store_id_arr[0]['is_product_fundraising'])){
                    $is_product_fundraising = $store_id_arr[0]['is_product_fundraising'];
                }
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

                    $discount_code = $discount_code_amount = $discount_code_type='';
                    foreach($result['discount_codes'] as $objcodes){
                        if(isset($objcodes['code']) && !empty($objcodes['code'])){
                            $discount_code        = $objcodes['code'];
                            $discount_code_amount = $objcodes['amount'];
                            $discount_code_type   = $objcodes['type'];
                        }
                    }

                    $sql='SELECT id FROM `coupon_code_master` WHERE discount_code="'.$discount_code.'" ';
                    $discountData = parent::selectTable_f_mdl($sql);
                    $discount_code_id=$discount_code_series_id='';
                    if(empty($discountData)){
                        $sql='SELECT coupon_code_master_id from coupon_code_series_master  where coupon_code="'.$discount_code.'" ';
                        $discountSeriesData = parent::selectTable_f_mdl($sql);
                        if(!empty($discountSeriesData)){
                            $discount_code_id=$discountSeriesData[0]['coupon_code_master_id'];
                            $discount_code_series_id=$discountSeriesData[0]['id'];
                        }
                    }else{
                        $discount_code_id=$discountData[0]['id'];
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
                        'order_tags' =>$result['tags'],
                        'discount_code_id' =>$discount_code_id,
                        'discount_code_series_id' =>$discount_code_series_id,
                        'discount_code' =>$discount_code,
                        'discount_code_amount' =>$discount_code_amount,
                        'discount_code_type' =>$discount_code_type,
                        'created_on' => @date('Y-m-d H:i:s'),
                        'created_on_ts' => time(),
                    ];
                    $som_arr = parent::insertTable_f_mdl('store_orders_master',$som_insert_data);

                    $logFileOpen = fopen("order_logs.txt", "a+") or die("Unable to open file!");
                    $errorText  = "</br></br></br>";
                    $errorText .= "----------------------------------------------------------------------------";
                    $errorText .= "order webhoock call =>".date("m-d-Y h:i:s");
                    $errorText .= "----------------------------------------------------------------------------";
                    $errorText .= "</br></br></br>";
                    $errorText .= " get order data = ".print_r($som_insert_data, true);
                    fwrite($logFileOpen, $errorText);
                    unset($errorText);
                    $mailSendStatus = $objAWS->sendEmail(['dilipyadav@bitcot.com'],'Order Webhook Data > '.$store_master_id.' > '.$result['order_number'],'store_master_id='.$store_master_id.'==shop_order_id='.$shop_order_id.'==shop_order_number='.$result['order_number'].'==created_on='.@date('Y-m-d H:i:s').'==sa_order_id='.$som_arr['insert_id'],'store_master_id='.$store_master_id.'==shop_order_id='.$shop_order_id.'==shop_order_number='.$result['order_number'].'==created_on='.@date('Y-m-d H:i:s').'==sa_order_id='.$som_arr['insert_id']);
                    $mailSendStatus = $objAWS->sendEmail(['preetamdhanotiya@bitcot.com'],'Order Webhook Data > '.$store_master_id.' > '.$result['order_number'],'store_master_id='.$store_master_id.'==shop_order_id='.$shop_order_id.'==shop_order_number='.$result['order_number'].'==created_on='.@date('Y-m-d H:i:s').'==sa_order_id='.$som_arr['insert_id'],'store_master_id='.$store_master_id.'==shop_order_id='.$shop_order_id.'==shop_order_number='.$result['order_number'].'==created_on='.@date('Y-m-d H:i:s').'==sa_order_id='.$som_arr['insert_id']);

                    if(isset($som_arr['insert_id']) && !empty($som_arr['insert_id'])){
                        $total_fundraising_amount = 0;
                        $fundraising_amount = 0.00;
                        $items = [];
                        $fe_key = 0;
                        $customcat_key=0;
                        $errorText = "check order line_items = ".print_r($result['line_items'], true);
                        fwrite($logFileOpen, $errorText);
                        unset($errorText);
                        //now insert item data
                        foreach($result['line_items'] as $key => $single_item){
                            $sql='
                                SELECT id, store_owner_product_master_id, fundraising_price,color,size,image,assign_logo_heightinch,assign_logo_widthinch,associate_with_logo_id FROM `store_owner_product_variant_master`
                                WHERE shop_product_id = "'.$single_item['product_id'].'"
                                AND shop_variant_id = "'.$single_item['variant_id'].'"
                                LIMIT 1
                            ';
                            $store_var_data = parent::selectTable_f_mdl($sql);
                            $var_image=$assign_logo_heightinch=$assign_logo_widthinch=$associate_with_logo_id='';
                            if(!empty($store_var_data)){
                                $assign_logo_heightinch = $store_var_data[0]['assign_logo_heightinch'];
                                $assign_logo_widthinch = $store_var_data[0]['assign_logo_widthinch'];

                                $var_image = $store_var_data[0]['image'];
                                $associate_with_logo_id = $store_var_data[0]['associate_with_logo_id'];
                                $productTags = $this->getProductTags($varStoreName,$single_item['product_id']);
                                $return_prime='no';
                                if (preg_match('/\b' . 'Return_Prime' . '\b/', $result['tags'])) { 
                                   $return_prime='yes';
                                }
                                if($result['total_price']=='0.00' && $return_prime == 'yes'){
                                    $total_fundraising_amount='0.00';
                                }else{
                                    $total_fundraising_amount += floatval($store_var_data[0]['fundraising_price'])*$single_item['quantity'];
                                }

                                $fundraising_amount=floatval($store_var_data[0]['fundraising_price'])*$single_item['quantity'];
                                $personalizationName =$personalizationItemName= '';
                                foreach($single_item['properties'] as $propertiesValues){
                                    if(isset($propertiesValues['name']) && !empty($propertiesValues['name'])){
                                        if($propertiesValues['name'] == 'Personalization Name'){
                                            $personalizationName =$propertiesValues['value'];
                                        }else {
                                            $personalizationItemName =$propertiesValues['value'];
                                        } 
                                    }
                                }
                                
                                /* Task start assigned logo to order 25/08/2022*/
                                $logoSql = 'SELECT sopm.shop_product_id,sdlm.logo_image,sopvm.associate_with_logo_id,sdlm.print_size,sdlm.print_location,prnt.print_location as printLocation,prnt.preset_id,prnt.default_title,sopm.store_product_master_id FROM store_owner_product_master as sopm 
                                INNER JOIN store_owner_product_variant_master as sopvm ON sopm.id = sopvm.store_owner_product_master_id
                                LEFT JOIN store_design_logo_master as sdlm ON sdlm.id = sopvm.associate_with_logo_id
                                LEFT JOIN print_locations as prnt ON prnt.id = sdlm.print_location
                                WHERE sopm.shop_product_id ="'.$single_item['product_id'].'" AND sopvm.shop_variant_id ="'.$single_item['variant_id'].'"  AND sdlm.is_deleted = "0" ';
                                $logoData = parent::selectTable_f_mdl($logoSql);

                                $store_product_master_id = (!empty($logoData[0]['store_product_master_id']))?$logoData[0]['store_product_master_id']:'';

                                $fulfillengineSql = "SELECT * FROM store_product_master  WHERE id = '".$store_product_master_id."' ";
                                $FulfillData = parent::selectTable_f_mdl($fulfillengineSql);
                                $vendor_product_id='';
                                if(!empty($FulfillData)){
                                    $vendor_product_id=$FulfillData[0]['vendor_product_id'];
                                }

                                $print_size='';
                                if(!empty($logoData[0]['print_size'])){
                                    $print_size=$logoData[0]['print_size']; 
                                }
                                $logo_image =$logo_width=$logo_height= '';
                                $print_location = '';
                                $presets = 0;
                                if(!empty($logoData)){
                                    $logo_image = (!empty($logoData[0]['logo_image']))?$logoData[0]['logo_image']:'';
                                    $print_location = (!empty($logoData[0]['printLocation']))?$logoData[0]['printLocation']:'';
                                    $presets = (!empty($logoData[0]['preset_id']))?$logoData[0]['preset_id']:'';
                                    
                                    $logocoordinates='SELECT * FROM `logo_coordinates` WHERE store_product_master_id = "'.$store_product_master_id.'" AND print_location_id="'.$print_location.'" ';
                                    $logocoordinates_data = parent::selectTable_f_mdl($logocoordinates);

                                    $assignLogoSql='SELECT * FROM `store_owner_product_master` WHERE id = "'.$store_var_data[0]['store_owner_product_master_id'].'" ';
                                    $assignLogoSql_data = parent::selectTable_f_mdl($assignLogoSql);
                                    if(!empty($assignLogoSql_data)){
                                        if($assignLogoSql_data[0]['assign_logo_width']=='' && $assignLogoSql_data[0]['assign_logo_height']==''){
                                            if(!empty($logocoordinates_data)){
                                                $dpi = 96;
                                                $logo_width=$logocoordinates_data[0]['logo_width'] / $dpi;
                                                $logo_height=$logocoordinates_data[0]['logo_height'] / $dpi;
                                            }
                                        }else{
                                            $dpi = 96;
                                            $logo_width =$assignLogoSql_data[0]['assign_logo_width'] / $dpi;
                                            $logo_height =$assignLogoSql_data[0]['assign_logo_height'] / $dpi;
                                        }
                                    }

                                    $fulfillMasterSql = "SELECT * FROM fulfillengine_products_master  WHERE catalog_product_id = '".$vendor_product_id."'  LIMIT 1";
                                    $FulfillMasterData = parent::selectTable_f_mdl($fulfillMasterSql);
                                    $fa_productcategory='';
                                    $fa_printLocation ='';
                                    $fa_heightInches ='';
                                    $fa_widthInches ='';
                                    if(!empty($FulfillMasterData)){
                                        $fa_productcategory=$FulfillMasterData[0]['printing_methods'];
                                        $fa_productcategoryArr = explode (",", $fa_productcategory);  
                                        $fa_printLocation=$FulfillMasterData[0]['print_locations'];
                                        $fa_printLocationArr = explode (",", $fa_printLocation); 
                                        $catalog_product_id=$FulfillMasterData[0]['catalog_product_id'];
                                        $fa_productcategory=$fa_productcategoryArr[0];
                                        foreach ($fa_printLocationArr as $location) {
                                            if($location==$print_location){
                                                $fa_printLocation = $location;
                                            } 
                                        }
                                    }

                                    switch ($fa_printLocation) {
                                        case "front":
                                            $fa_widthInches= $FulfillMasterData[0]['front_width'];
                                            $fa_heightInches= $FulfillMasterData[0]['front_height'];
                                            break;
                                        case "back":
                                            $fa_widthInches= $FulfillMasterData[0]['back_width'];
                                            $fa_heightInches= $FulfillMasterData[0]['back_height'];
                                            break;
                                        case "left_chest":
                                            $fa_widthInches= $FulfillMasterData[0]['left_chest_width'];
                                            $fa_heightInches= $FulfillMasterData[0]['left_chest_height'];
                                            break;
                                        case "right_chest":
                                            $fa_widthInches= $FulfillMasterData[0]['right_chest_width'];
                                            $fa_heightInches= $FulfillMasterData[0]['right_chest_height'];
                                            break;
                                        case "left_sleeve_short":
                                            $fa_widthInches= $FulfillMasterData[0]['left_sleeve_short_width'];
                                            $fa_heightInches= $FulfillMasterData[0]['left_sleeve_short_height'];
                                            break;
                                        case "right_sleeve_short":
                                            $fa_widthInches= $FulfillMasterData[0]['right_sleeve_short_width'];
                                            $fa_heightInches= $FulfillMasterData[0]['right_sleeve_short_height'];
                                            break;
                                        case "left_sleeve_long":
                                            $fa_widthInches= $FulfillMasterData[0]['left_sleeve_long_width'];
                                            $fa_heightInches= $FulfillMasterData[0]['left_sleeve_long_height'];
                                            break;
                                        case "right_sleeve_long":
                                            $fa_widthInches= $FulfillMasterData[0]['right_sleeve_long_width'];
                                            $fa_heightInches= $FulfillMasterData[0]['right_sleeve_long_height'];
                                            break;
                                        case "cap_front":
                                            $fa_widthInches= $FulfillMasterData[0]['cap_front_width'];
                                            $fa_heightInches= $FulfillMasterData[0]['cap_front_height'];
                                            break;
                                        case "cap_front_left":
                                            $fa_widthInches= $FulfillMasterData[0]['cap_front_left_width'];
                                            $fa_heightInches= $FulfillMasterData[0]['cap_front_left_height'];
                                            break;
                                        case "cap_front_right":
                                            $fa_widthInches= $FulfillMasterData[0]['cap_front_right_width'];
                                            $fa_heightInches= $FulfillMasterData[0]['cap_front_right_height'];
                                            break;
                                        default:
                                            $fa_widthInches= '';
                                            $fa_heightInches= '';
                                            break;
                                    }

                                }
                                /* Task end assigned logo to order 25/08/2022*/
                                if($result['total_price']=='0.00' && $return_prime == 'yes'){
                                    $fundraising_amount = '0.00';
                                }

                                $store_fundsql='SELECT id, is_fundraising, ct_fundraising_price FROM `store_master` WHERE id = "'.$store_master_id.'"';
                                $store_fund_data = parent::selectTable_f_mdl($store_fundsql);
                                
                                $store_fund_status='';
                                $store_fund_amount='';
                                if(!empty($store_fund_data)){
                                    $store_fund_status=$store_fund_data[0]['is_fundraising'];
                                    $store_fund_amount=$store_fund_data[0]['ct_fundraising_price'];
                                }
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
                                    'fundraising_status' => $is_product_fundraising,
                                    'fundraising_amount' => $fundraising_amount,
                                    'store_fund_status' => $store_fund_status,
                                    'store_fund_amount' => $store_fund_amount,
                                    'sku' => $single_item['sku'], /* Task assigned logo 25/08/2022 */
                                    'vendor' => $single_item['vendor'],
                                    'variant_title' => $single_item['variant_title'],
                                    'tags' => $productTags,
                                    'personalization_name' =>$personalizationName,
                                    'personalization_item_name' =>$personalizationItemName,
                                    'logo_image'=>$logo_image,
                                    'print_location'=>$print_location,
                                    'created_on' => @date('Y-m-d H:i:s'),
                                    'created_on_ts' => time(),
                                ];
                                $oim_res=parent::insertTable_f_mdl('store_order_items_master',$soim_insert_data);

                                $errorText  = "order items insert data= ".print_r($soim_insert_data, true);
                                $errorText .= "store_order_items_master insert response= ".print_r($oim_res, true);
                                fwrite($logFileOpen, $errorText);
                                unset($errorText);
                                
                                $mailSendStatus = $objAWS->sendEmail(['preetamdhanotiya@bitcot.com'],'Order Items Data  > '.$store_master_id.' > '.$result['order_number'],'store_master_id='.$store_master_id.'==shop_product_id='.$single_item['product_id'].'==shop_variant_id='.$single_item['variant_id'].'==title='.$single_item['title'].'==quantity='.$single_item['quantity'].'==sku='.$single_item['sku'].'==variant_title='.$single_item['variant_title'].'==fundraising_status='.$is_product_fundraising.'==fundraising_amount='.$fundraising_amount.'==tags='.$productTags.'==created_on='.@date('Y-m-d H:i:s'),'store_master_id='.$store_master_id.'==shop_product_id='.$single_item['product_id'].'==shop_variant_id='.$single_item['variant_id'].'==title='.$single_item['title'].'==quantity='.$single_item['quantity'].'==sku='.$single_item['sku'].'==variant_title='.$single_item['variant_title'].'==fundraising_status='.$is_product_fundraising.'==fundraising_amount='.$fundraising_amount.'==tags='.$productTags.'==created_on='.@date('Y-m-d H:i:s'));
                                $mailSendStatus = $objAWS->sendEmail(['dilipyadav@bitcot.com'],'Order Items Data  > '.$store_master_id.' > '.$result['order_number'],'store_master_id='.$store_master_id.'==shop_product_id='.$single_item['product_id'].'==shop_variant_id='.$single_item['variant_id'].'==title='.$single_item['title'].'==quantity='.$single_item['quantity'].'==sku='.$single_item['sku'].'==variant_title='.$single_item['variant_title'].'==fundraising_status='.$is_product_fundraising.'==fundraising_amount='.$fundraising_amount.'==tags='.$productTags.'==created_on='.@date('Y-m-d H:i:s'),'store_master_id='.$store_master_id.'==shop_product_id='.$single_item['product_id'].'==shop_variant_id='.$single_item['variant_id'].'==title='.$single_item['title'].'==quantity='.$single_item['quantity'].'==sku='.$single_item['sku'].'==variant_title='.$single_item['variant_title'].'==fundraising_status='.$is_product_fundraising.'==fundraising_amount='.$fundraising_amount.'==tags='.$productTags.'==created_on='.@date('Y-m-d H:i:s'));

                                $variant_color=$store_var_data[0]['color'];
                                $variant_size=$store_var_data[0]['size'];
                                $customcatSkuSql = "SELECT spvm.customcat_sku FROM store_product_variant_master as spvm INNER JOIN store_owner_product_variant_master as sopvm ON sopvm.store_product_variant_master_id = spvm.id  WHERE sopvm.id ='".$store_var_data[0]['id']."' AND spvm.color ='".$variant_color."' AND spvm.size ='".$variant_size."' ";
                                $customcatSkuData = parent::selectTable_f_mdl($customcatSkuSql);
                                $items[$key] =[
                                    "catalog_sku"=>$customcatSkuData[0]['customcat_sku'],
                                    "design_url"=>(!empty($logo_image))?$s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$logo_image):'',
                                    "quantity"=>$single_item['quantity'],
                                    "preset_id"=>$presets
                                ];

                                if($single_item['vendor']=='CustomCat'){
                                    $logFileOpen = fopen("customcat_order_logs.txt", "a+") or die("Unable to open file!");
                                    $errorText  = "</br></br></br>";
                                    $errorText .= "----------------------------------------------------------------------------";
                                    $errorText .= "order customcat_order_logs call =>".date("m/d/Y h:i A");
                                    $errorText .= "----------------------------------------------------------------------------";
                                    fwrite($logFileOpen, $errorText);
                                    unset($errorText);

                                    $CustomCatItems[$customcat_key]= [
                                        "catalog_sku"=>$customcatSkuData[0]['customcat_sku'],
                                        "design_url"=>(!empty($logo_image))?$s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$logo_image):'',
                                        "quantity"=>$single_item['quantity'],
                                        "preset_id"=>$presets
                                    ];

                                    $errorText  = "customcat order items data= ".print_r($CustomCatItems, true);
                                    fwrite($logFileOpen, $errorText);
                                    unset($errorText);
                                    $customcat_key++;
                                }


                                
                                $logFileOpen = fopen("fulfill_order_logs.txt", "a+") or die("Unable to open file!");
                                $errorText  = "</br></br></br>";
                                $errorText .= "----------------------------------------------------------------------------";
                                $errorText .= "order fulfill_order_logs call =>".date("m/d/Y h:i A");
                                $errorText .= "----------------------------------------------------------------------------";
                                fwrite($logFileOpen, $errorText);
                                unset($errorText);


                                $storeLogomockupsql = "SELECT image FROM `store_logo_mockups_master` WHERE (image!='' or image IS NOT NULL) and store_owner_product_variant_master_id =".$store_var_data[0]['id']." ";
		                        $logoMockupData =  parent::selectTable_f_mdl($storeLogomockupsql);

                                if(!empty($logoMockupData)){
                                    $pro_image_url = $s3Obj->getAwsUrl(common::LOGO_MOCKUP_UPLOAD_S3_PATH.$store_master_id.'/'.$logoMockupData[0]['image']);
                                }else{
                                    $pro_image_url=(!empty($var_image))?$s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$var_image):'';
                                }
                                
                                if($single_item['vendor']=='FulfillEngine'){

                                    $fulfillengineLineItems[$fe_key]= [
                                        "countryOfOrigin" => "US", 
                                        "customsDescription" => "My First Test Order", 
                                        "declaredValue" => 0, 
                                        "designId" => "", 
                                        "designData" => [
                                            "artwork" => [
                                                [
                                                    "mockups" => [
                                                        [
                                                            "mockupUrl" => $pro_image_url, 
                                                        ] 
                                                    ], 
                                                    "originalFileUrl" => (!empty($logo_image))?$s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$logo_image):'', 
                                                    "physicalPlacement"=> [
                                                        "horizontalPlacementFromCenterInInches"=> 0,
                                                        "verticalPlacementInInches"=> 0
                                                    ],
                                                    "physicalSize"=> [
                                                    // "widthInches"=> $fa_widthInches,
                                                    //"widthInches"=> $logo_width,
                                                        "widthInches"=> $assign_logo_widthinch,
                                                    // "heightInches"=> $fa_heightInches
                                                    //"heightInches"=> $logo_height
                                                        "heightInches"=> $assign_logo_heightinch
                                                    ], 
                                                    "printingMethod" =>  $fa_productcategory, 
                                                    "printLocation" => $fa_printLocation 
                                                ] 
                                            ] 
                                        ], 
                                        "gtin" => "", 
                                        "htsCode" => "", 
                                        "id" => '', //vendor_product_id
                                        "quantity" => $single_item['quantity'], 
                                        "sku" => $single_item['sku'], 
                                        "vendorSKU" => "" 
                                        
                                    ];

                                    $fulfillengineShippingLineItems[$fe_key]= [
                                        "orderItemGroupId" => $vendor_product_id, 
                                        "quantity" => $single_item['quantity']    
                                    ];
                                    $fe_key++;
                                }
                                
                            }

                            $array =  explode(" /", $single_item['variant_title'], 2);
                            $variant_color     = !empty($array[1])?$array[1]:'';
                            $variant_size      = !empty($array[0])?$array[0]:'';

                            $sanmar_total_apparel_cast='0';
                            $apprialCast='0';
                            if($single_item['vendor']=='SanMar'){
                                $sanmar_total_apparel_cast =self::checkProductApparelCast($single_item['sku'],$variant_color,$variant_size,$single_item['quantity'],$store_var_data[0]['id']);
                            }

                            $apprialCastSql = 'SELECT sanmar_apprial_cost FROM store_master WHERE id="'.$store_master_id.'" ';
                            $apprialCastData = parent::selectTable_f_mdl($apprialCastSql);

                            $apprialCast =$apprialCastData[0]['sanmar_apprial_cost'];
                            $total_apprialCast= $apprialCast + $sanmar_total_apparel_cast;
                            $total_apprialCast = str_replace(",","",$total_apprialCast);
                            $totalApperalData = [
                                'sanmar_apprial_cost'  => $total_apprialCast
                            ];
                            parent::updateTable_f_mdl('store_master',$totalApperalData,'id="'.$store_master_id.'" ');
                        }



                        //add total total fund order
                        $itemfundsql='SELECT SUM(fundraising_amount) as totalfundAmount  FROM store_order_items_master WHERE store_master_id="'.$store_master_id.'" AND store_orders_master_id="'.$som_arr['insert_id'].'" AND is_deleted="0" ';
                        $itemfundsql_data = parent::selectTable_f_mdl($itemfundsql);

                        $som_update_data = [
                            'total_fundraising_amount' => $itemfundsql_data[0]['totalfundAmount']
                        ];
                        parent::updateTable_f_mdl('store_orders_master',$som_update_data,'id="'.$som_arr['insert_id'].'"');

                        //update total_fundraising_amount in main order
                        if($store_id_arr[0]['sale_type']=="On-Demand"){
                            if(!empty($CustomCatItems)){
                                self::sendOrderInCustomCat($shop_order_id,$result,$items,$CustomCatItems);
                            }
                            if(!empty($fulfillengineShippingLineItems)){
                                //send otder to fulfillengine
                                self::sendOrderInFullfillEngine($shop_order_id,$result,$fulfillengineLineItems,$fulfillengineShippingLineItems);
                            }
                               
                        }
                      /*=========Dilip======profit margin auto update===============*/
                      $total_sale         = 0.00;
                      $fundraising_amount = 0.00;
                      if (isset($store_master_id) && !empty($store_master_id)) {
                          $saleSql = 'SELECT soim.id,soim.store_master_id,sm.store_name AS title,soim.is_deleted,SUM(soim.quantity * soim.price) AS total_sale,som.store_sale_type FROM store_order_items_master AS soim INNER JOIN store_orders_master AS som ON soim.store_orders_master_id = som.id INNER JOIN store_master AS sm ON soim.store_master_id = sm.id WHERE 1 AND soim.is_deleted = 0 AND som.is_order_cancel = "0"  AND som.order_type="1" AND som.store_master_id = '.$store_master_id.' ';
                          $saleData = parent::selectTable_f_mdl($saleSql);

                          $saleSql2 = 'SELECT soim.id,soim.store_master_id,sm.store_name AS title,soim.is_deleted,SUM(soim.quantity * soim.price) AS total_sale,som.store_sale_type FROM store_order_items_master AS soim INNER JOIN store_orders_master AS som ON soim.store_orders_master_id = som.id INNER JOIN store_master AS sm ON soim.store_master_id = sm.id WHERE 1 AND soim.is_deleted = 0 AND som.is_order_cancel = "0" AND som.order_type="2" AND som.store_master_id = '.$_POST['store_master_id'].' ';
                          $saleData2 = parent::selectTable_f_mdl($saleSql2);
                          $total_sale_manual='';
                          if(!empty($saleData2)){
                            $total_sale_manual = $saleData2[0]['total_sale'];
                          }

                          $saleSql3 = 'SELECT soim.id,soim.store_master_id,sm.store_name AS title,soim.is_deleted,SUM(soim.quantity * soim.price) AS total_sale,som.store_sale_type FROM store_order_items_master AS soim INNER JOIN store_orders_master AS som ON soim.store_orders_master_id = som.id INNER JOIN store_master AS sm ON soim.store_master_id = sm.id WHERE 1 AND soim.is_deleted = 0 AND som.is_order_cancel = "0" AND som.order_type="3" AND som.store_master_id = '.$_POST['store_master_id'].' ';
                          $saleData3 = parent::selectTable_f_mdl($saleSql3);
                          $total_sale_quickbuy='';
                          if(!empty($saleData3)){
                            $total_sale_quickbuy = $saleData3[0]['total_sale'];
                          }

                          if(!empty($saleData)){
                              $total_sale = $saleData[0]['total_sale'];
                              $fundSql = 'SELECT IFNULL(SUM(total_fundraising_amount),0) as total_fundraising_amount FROM `store_orders_master` WHERE `store_orders_master`.store_master_id = '.$store_master_id.' and `store_orders_master`.is_order_cancel = "0"';
                              $fundData = parent::selectTable_f_mdl($fundSql);
                              $fundraising_amount = $fundData[0]['total_fundraising_amount'];
                              $total_sale         =number_format((float)$total_sale, 2);
                              $total_sale = str_replace(",","",$total_sale);

                              $total_sale_manual = number_format((float)$total_sale_manual, 2);
                              $total_sale_manual = str_replace(",","",$total_sale_manual);

                              $total_sale_quickbuy = number_format((float)$total_sale_quickbuy, 2);
                              $total_sale_quickbuy = str_replace(",","",$total_sale_quickbuy);

                              $total_gross_sale = $total_sale+$total_sale_manual+$total_sale_quickbuy;
                              $total_gross_sale = number_format((float)$total_gross_sale, 2);
                              $total_gross_sale = str_replace(",","",$total_gross_sale);

                              $fundraising_amount =number_format((float)$fundraising_amount, 2);
                              $fundraising_amount = str_replace(",","",$fundraising_amount);

                          }
                      }

                      $profitSql  = 'SELECT *, "0" AS profit_value FROM `profit_cost_details` ';
                      $profitDataAll = parent::selectTable_f_mdl($profitSql);

                      $item_sql="
                          SELECT sm.ct_fundraising_price,sm.id,sm.store_name,sm.is_fundraising,
                          (SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = sm.id) as totalItem_sold,
                          (SELECT IFNULL(SUM(oim.quantity),0) FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = sm.id and om.order_type=1) as actual_orderItem_sold,
                          (SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0) as total_order, 
                          (SELECT count(id) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0 AND order_type=1) as total_actual_order,
                          (SELECT IFNULL(SUM(total_fundraising_amount),0) FROM store_orders_master WHERE store_master_id = sm.id AND is_order_cancel = 0) as total_fund_amount
                          FROM store_master sm INNER JOIN store_owner_details_master sodm ON sm.store_owner_details_master_id = sodm.id INNER JOIN store_sale_type_master sstm ON sm.store_sale_type_master_id = sstm.id 
                          LEFT JOIN store_organization_type_master as org ON sm.store_organization_type_master_id = org.id WHERE sm.id = ".$store_master_id."
                      ";
                      $item_sql_data = parent::selectTable_f_mdl($item_sql);
                      $label_values = 0.00;
                      $printCost=0.00;
                      $checked_lable_cost=0.00;
                      $unchecked_lable_cost=0.00;
                      if(!empty($profitDataAll))
                      {
                        $craditcardfee=0.00;
                          foreach($profitDataAll as $value)
                          {
                              $profitSql  = 'SELECT store_profit.profit_value,profit_cost_details.cost_label,profit_cost_details.id,profit_cost_details.cost_slug,profit_cost_details.is_checked FROM store_profit LEFT JOIN profit_cost_details ON store_profit.profit_label_id = profit_cost_details.id where store_profit.store_master_id = "'.$store_master_id.'" AND profit_cost_details.id =  "'.$value['id'].'" ';
                              $profitData = parent::selectTable_f_mdl($profitSql);
                              if(!empty($profitData))
                              {
                                  $label_values += str_replace(",","",$profitData[0]['profit_value']);
                                  $label_values = str_replace(",","",$label_values);
                                  $cost_slug=$profitData[0]['cost_slug'];
                                  $is_checked=$profitData[0]['is_checked'];
                                  $totalItem_sold=$item_sql_data[0]['totalItem_sold'];
                                  $actual_orderItem_sold=$item_sql_data[0]['actual_orderItem_sold'];
                                  $profit_id=$profitData[0]['id'];
                                  if($is_checked=='1'){
                                      $printCostLabel =str_replace(",","",$profitData[0]['profit_value']) * $totalItem_sold;
                                      $printCostLabel = str_replace(",","",$printCostLabel);
                                      $printCost = number_format((float)($printCostLabel-str_replace(",","",$profitData[0]['profit_value'])), 2);
                                      $checked_lable_cost +=$printCostLabel;
                                      $checked_lable_cost = str_replace(",","",$checked_lable_cost);

                                  }else{ 
                                      $unchecked_lable_cost += str_replace(",","",$profitData[0]['profit_value']);
                                      $unchecked_lable_cost = str_replace(",","",$unchecked_lable_cost);

                                      $total_card_fee=0.00;
                                      if($profit_id=='12'){
                                          $total_order_amount = str_replace(",","",$total_gross_sale);
                                          $total_order_get = str_replace(",","",$item_sql_data[0]['total_order']);
                                          $card_fee= $total_order_amount*2.9/100;
                                          $card_fee = str_replace(",","",$card_fee);
                                          $no_of_order_fee=$total_order_get * 0.30;
                                          $total_card_fee=$card_fee + $no_of_order_fee;
                                          $total_card_fee=number_format((float)$total_card_fee, 2);
                                          $total_card_fee = str_replace(",","",$total_card_fee);
                                          $craditcardfee=$total_card_fee;
                                      }
                                  }
                              }
                              else{
                                  $label_values += str_replace(",","",$value['lable_profit']);
                                  $label_values = str_replace(",","",$label_values);

                                  $cost_slug=$value['cost_slug'];
                                  $is_checked=$value['is_checked'];
                                  $totalItem_sold=str_replace(",","",$item_sql_data[0]['totalItem_sold']);
                                  $actual_orderItem_sold=str_replace(",","",$item_sql_data[0]['actual_orderItem_sold']);
                                  $profit_id=$value['id'];

                                  if($is_checked=='1'){
                                      $printCostLabel =str_replace(",","",$value['lable_profit']) * $totalItem_sold;
                                      $printCostLabel = str_replace(",","",$printCostLabel);
                                      $printCost = number_format((float)($printCostLabel-str_replace(",","",$value['lable_profit'])), 2);
                                      $checked_lable_cost +=$printCostLabel;
                                      $checked_lable_cost = str_replace(",","",$checked_lable_cost);
                                  }else{ 
                                      $unchecked_lable_cost += str_replace(",","",$value['lable_profit']);
                                      $unchecked_lable_cost = str_replace(",","",$unchecked_lable_cost);

                                      $total_card_fee=0.00;
                                      if($profit_id=='12'){
                                          $total_order_amount = str_replace(",","",$total_gross_sale);
                                          $total_order_get = str_replace(",","",$item_sql_data[0]['total_order']);
                                          $card_fee= $total_order_amount*2.9/100;
                                          $card_fee = str_replace(",","",$card_fee);
                                          $no_of_order_fee=$total_order_get * 0.30;
                                          $total_card_fee=$card_fee + $no_of_order_fee;
                                          $total_card_fee=number_format((float)$total_card_fee, 2);
                                          $total_card_fee = str_replace(",","",$total_card_fee);
                                          $craditcardfee=$total_card_fee;
                                      }
                                  }
                              }
                          }
                      }
                      $total_lable_price = ($checked_lable_cost+$unchecked_lable_cost + $fundraising_amount +$craditcardfee+$sanmar_total_apparel_cast);
                       $lablrprice   = number_format( (float)str_replace(",","",$total_lable_price), 2, '.', '');
                       $total_profit= (float)$total_gross_sale-str_replace(",","",$lablrprice);

                       $totalProfit  = (float)$total_profit;
                       $total_profit = str_replace(",","",$total_profit);

                       $gross_sale=$total_sale-str_replace(",","",$fundraising_amount);
                       $gross_sale = str_replace(",","",$gross_sale);

                       $profitmargin= ($total_profit/$gross_sale)*100;
                       $profitmargin  = number_format((float)$profitmargin, 2);
                       $profitmargin = str_replace(",","",$profitmargin);
                       
                       $newProfit = $totalProfit;
                      
                       $totalProData = [
                          'total_profit'  => $newProfit,
                          'profit_margin' => $profitmargin
                       ];
                      parent::updateTable_f_mdl('store_master',$totalProData,'id="'.$store_master_id.'"');
                      
                      /*==========Dilip========================*/

                        //send mail to store owner
                        $sql = 'SELECT `store_owner_details_master`.id,`store_owner_details_master`.first_name, `store_owner_details_master`.email, `store_owner_details_master`.email_notification,`store_master`.store_name,`store_master`.store_open_date,`store_master`.store_close_date
                        FROM `store_master`
                        LEFT JOIN `store_owner_details_master` ON `store_owner_details_master`.id = `store_master`.store_owner_details_master_id
                        WHERE `store_master`.id = "'.$store_master_id.'"
                        ';
                        $store_owner_arr = parent::selectTable_f_mdl($sql);
                        if(!empty($store_owner_arr) && $store_owner_arr[0]['email_notification']=="1"){
                            //if user has enabled email-notification, then he get email
                            require_once(common::EMAIL_REQUIRE_URL);
                            if(strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp')!==false){
                                $objAWS = new aws_ses_smtp();
                            }else if(strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail')!==false){
                                $objAWS = new sendGridEmail();
                            }else{
                                $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
                            }
                            $sql = 'SELECT subject,body FROM `email_templates_master` WHERE id='.common::NEW_ORDER_TO_CUSTOMER_ADMIN;
                            $et_data = parent::selectTable_f_mdl($sql);
                            $logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
                            $logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
                            $store_open_date=!empty($store_owner_arr[0]["store_open_date"]) ? date('m/d/Y', $store_owner_arr[0]["store_open_date"]) : '' ;
                            $store_last_date=!empty($store_owner_arr[0]["store_close_date"]) ? date('m/d/Y', $store_owner_arr[0]["store_close_date"]) : '' ;


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
                                $body = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
                                $body = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
                                //$mailSendStatus = $objAWS::sendEmail($from_email, $to_email, $subject, $body, $attachment);

                                $sql = 'SELECT * FROM store_master WHERE id="'.$store_master_id.'"';
                                $store_data = parent::selectTable_f_mdl($sql);

                                $mailSendStatus = 1;
                                //if($store_data[0]['email_notification'] == '1'){
                                    $mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body);
                                    /* send mail store owner */
                                    $store_owner_details_master_id=$store_owner_arr[0]['id'];
                                    $sql_managerData = 'SELECT * FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_details_master_id . '"';
                                    $smm_data =  parent::selectTable_f_mdl($sql_managerData);
                                    $logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
                                    $logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
                                    $store_open_date=!empty($store_owner_arr[0]["store_open_date"]) ? date('m/d/Y', $store_owner_arr[0]["store_open_date"]) : '' ;
                                    $store_last_date=!empty($store_owner_arr[0]["store_close_date"]) ? date('m/d/Y', $store_owner_arr[0]["store_close_date"]) : '' ;
                                    if(!empty($smm_data)){
                                        foreach ($smm_data as $managerData) {
                                            $email_notification   = $managerData['email_notification'];
                                            if($email_notification=="1"){
                                                $to_email   = $managerData['email'];
                                                $from_email = common::AWS_ADMIN_EMAIL;
                                                $attachment = [];
                                                $body = str_replace('{{CUSTOMER_NAME}}',$customer_name,$body);
                                                $body = str_replace('{{STORE_NAME}}', $store_owner_arr[0]['store_name'], $body);
                                                $body = str_replace('{{DASHBOARD_LINK}}',common::CUSTOMER_ADMIN_DASHBOARD_URL,$body);
                                                $body = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
                                                $body = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
                                                $body = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
                                                $mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body);
                                            } 
                                        }
                                    }
                                    /* send mail store owner */
                                //}
                            }
                        }
                    }

                    fclose($logFileOpen);
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

    public function sendOrderInCustomCat($shop_order_id,$result,$items,$CustomCatItems)
    {   
        $logFileOpen = fopen("customcat_order_logs.txt", "a+") or die("Unable to open file!");
        $errorText  = "</br></br></br>";
        $errorText .= "----------------------------------------------------------------------------";
        $errorText .= "order customcat_order_logs call =>".date("m-d-Y h:i:s");
        $errorText .= "----------------------------------------------------------------------------";
        fwrite($logFileOpen, $errorText);
        unset($errorText);
        $api_key=common::CUSTOMCAT_API_KEY;
        $api_endpoint=COMMON::CUSTOMCAT_API_ENDPOINT;
        $data = [
            "shipping_first_name"=>(!empty($result['shipping_address']['first_name']))?$result['shipping_address']['first_name']:'',
            "shipping_last_name"=>(!empty($result['shipping_address']['last_name']))?$result['shipping_address']['last_name']:'',
            "shipping_address1"=>(!empty($result['shipping_address']['address1']))?$result['shipping_address']['address1']:'',
            "shipping_address2"=>(!empty($result['shipping_address']['address2']))?$result['shipping_address']['address2']:'',
            "shipping_city"=>(!empty($result['shipping_address']['city']))?$result['shipping_address']['city']:'',
            "shipping_state"=>(!empty($result['shipping_address']['province']))?$result['shipping_address']['province']:'',
            "shipping_zip"=>(!empty($result['shipping_address']['zip']))?$result['shipping_address']['zip']:'',
            "shipping_country"=>(!empty($result['shipping_address']['country_code']))?$result['shipping_address']['country_code']:'',
            "shipping_email"=>(!empty($result['contact_email']))?$result['contact_email']:'',
            "shipping_phone"=>(!empty($result['shipping_address']['phone']))?$result['shipping_address']['phone']:'',
            "shipping_method"=>"Economy",
            "items"=>$CustomCatItems,
            "sandbox"=> "0",
            "api_key"=> $api_key
        ];
        $jsonData = json_encode($data);
        $errorText = "----------------------------------------------------------------------------";
        $errorText .= " get order data = ".print_r($jsonData, true);
        $errorText .= "----------------------------------------------------------------------------";
        fwrite($logFileOpen, $errorText);
        unset($errorText);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $api_endpoint.$shop_order_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>$jsonData,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $isSentCustomCat = 0;
        $customcat_order_id = '';
        $res = [];
        if (!empty($response)) {
            $resultData = json_decode($response, true);
        }
        $errorText = "----------------------------------------------------------------------------";
        $errorText .= " get custoncat order response data = ".print_r($resultData, true);
        $errorText .= "----------------------------------------------------------------------------";
        fwrite($logFileOpen, $errorText);
        unset($errorText);
        if(isset($resultData['ORDER_ID']) && !empty($resultData['ORDER_ID'])){
            $shop_order_id = $resultData['ORDER_ID'];
            $customcat_order_id = $resultData['CUSTOMCAT_ORDER_ID'];
            $isSentCustomCat = 2;
        }
        $dataCustomCat = [
            "is_sent_customcat"=>$isSentCustomCat,
            "customcat_order_id"=>$customcat_order_id
        ];
        parent::updateTable_f_mdl('store_orders_master',$dataCustomCat,'shop_order_id="'.$shop_order_id.'"');

        $getorderSql = "SELECT * FROM store_orders_master  WHERE shop_order_id='".trim($shop_order_id)."' ";
        $order_List = parent::selectTable_f_mdl($getorderSql);
        if(!empty($order_List)){
            $store_order_id = $order_List[0]['id'];
        }else{
            $store_order_id ='';
        }
        if(!empty($customcat_order_id)){
            $soim_update_data = [
                'buyStatus' => '1'
            ];
            parent::updateTable_f_mdl('store_order_items_master',$soim_update_data,'store_orders_master_id="'.$store_order_id.'" AND vendor="CustomCat" ');
        }
        $cust_webhook_endpoint =common::cust_webhook_endpoint;
        $webhook_url=common::webhook_url;
        $api_key=common::CUSTOMCAT_API_KEY;

        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => $cust_webhook_endpoint,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => array("topic"=>"order-shipped","api_key"=> $api_key,"url"=>$webhook_url),
        ));

        $web_response = curl_exec($curl);
        curl_close($curl);


        if(!empty($web_response)){
            $web_res_Data = json_decode($web_response, true);
        }
        if(isset($web_res_Data['WEBHOOK_ID']) && !empty($web_res_Data['WEBHOOK_ID'])){
            $webhook_id = $web_res_Data['WEBHOOK_ID'];
        }

        $webhoock_id_data = [
            "customcat_webhook_id"=>$webhook_id
        ];
        parent::updateTable_f_mdl('store_orders_master',$webhoock_id_data,'shop_order_id="'.$shop_order_id.'"');

        require_once(common::EMAIL_REQUIRE_URL);
        if(strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp')!==false){
            $objAWS = new aws_ses_smtp();
        }else if(strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail')!==false){
            $objAWS = new sendGridEmail();
        }else{
            $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
        }
        $mailSendStatus = $objAWS->sendEmail(['dilipyadav@bitcot.com'], 'Custom cat ', $jsonData, $jsonData);
        $mailSendStatus = $objAWS->sendEmail(['dilipyadav@bitcot.com'], 'Custom cat res ', $response, $response);
        $mailSendStatus = $objAWS->sendEmail(['dilipyadav@bitcot.com'], 'Custom cat webhoock res ', $web_response, $web_response);
    }

    public function sendOrderInFullfillEngine($shop_order_id,$result,$fulfillengineLineItems,$fulfillengineShippingLineItems)
    {   
        $fullfill_order_endpoint =common::FULFILLENGINE_ORDER_ENDPOINTS;
        $api_key                 =common::FULFILLENGINE_API_KEY;
        $logFileOpen = fopen("fulfill_order_logs.txt", "a+") or die("Unable to open file!");
        $errorText = "----------------------------------------------------------------------------";
        $errorText .= "send order fulfillengine call =>".date("m/d/Y h:i A");
        $errorText .= "----------------------------------------------------------------------------";
        fwrite($logFileOpen, $errorText);
        unset($errorText);

        $cust_phone = '';
        // if(isset($result['customer']['phone']) && !empty($result['customer']['phone'])){
        //     $cust_phone = $result['customer']['phone'];
        // }else if(isset($result['customer']['default_address']['phone']) && !empty($result['customer']['default_address']['phone'])){
        //     $cust_phone = $result['customer']['default_address']['phone'];
        // }else if(isset($result['shipping_address']['phone']) && !empty($result['shipping_address']['phone'])){
        //     $cust_phone = $result['shipping_address']['phone'];
        // }else if(isset($result['billing_address']['phone']) && !empty($result['billing_address']['phone'])){
        //     $cust_phone = $result['billing_address']['phone'];
        // }

        $super_admin_email=$cust_email=$cust_first_name=$cust_last_name=$cust_address1=$cust_address2=$cust_city=$cust_zip=$cust_country_code=$cust_province = $cust_province_code= '';

        if(isset($result['customer']['email']) && !empty($result['customer']['email'])){
            //$cust_email = $result['customer']['email'];
            $cust_email ='';
        }
        if(isset($result['customer']['default_address']['first_name']) && !empty($result['customer']['default_address']['first_name'])){
            $cust_first_name = $result['customer']['default_address']['first_name'];
        }
        if(isset($result['customer']['default_address']['last_name']) && !empty($result['customer']['default_address']['last_name'])){
            $cust_last_name = $result['customer']['default_address']['last_name'];
        }
        if(isset($result['customer']['default_address']['address1']) && !empty($result['customer']['default_address']['address1'])){
            $cust_address1 = $result['customer']['default_address']['address1'];
        }
        if(isset($result['customer']['default_address']['address2']) && !empty($result['customer']['default_address']['address2'])){
            $cust_address2 = $result['customer']['default_address']['address2'];
        }
        if(isset($result['customer']['default_address']['city']) && !empty($result['customer']['default_address']['city'])){
            $cust_city= $result['customer']['default_address']['city'];
        }
        if(isset($result['customer']['default_address']['zip']) && !empty($result['customer']['default_address']['zip'])){
            $cust_zip = $result['customer']['default_address']['zip'];
        }
        if(isset($result['customer']['default_address']['country_code']) && !empty($result['customer']['default_address']['country_code'])){
            $cust_country_code = $result['customer']['default_address']['country_code'];
        }
        if(isset($result['customer']['default_address']['zip']) && !empty($result['customer']['default_address']['zip'])){
            $cust_zip = $result['customer']['default_address']['zip'];
        }
        if(isset($result['customer']['default_address']['province']) && !empty($result['customer']['default_address']['province'])){
            $cust_province = $result['customer']['default_address']['province'];
        }
        if(isset($result['customer']['default_address']['province_code']) && !empty($result['customer']['default_address']['province_code'])){
            $cust_province_code = $result['customer']['default_address']['province_code'];
        }
        
        //shipping_address
        if(isset($result['customer']['shipping_address']['first_name']) && !empty($result['customer']['shipping_address']['first_name'])){
            $shipping_first_name = $result['customer']['shipping_address']['first_name'];
        }
        if(isset($result['customer']['shipping_address']['last_name']) && !empty($result['customer']['shipping_address']['last_name'])){
            $shipping_last_name = $result['customer']['shipping_address']['last_name'];
        }
        if(isset($result['customer']['shipping_address']['address1']) && !empty($result['customer']['shipping_address']['address1'])){
            $shipping_address1 = $result['customer']['shipping_address']['address1'];
        }
        if(isset($result['customer']['shipping_address']['address2']) && !empty($result['customer']['shipping_address']['address2'])){
            $shipping_address2 = $result['customer']['shipping_address']['address2'];
        }
        if(isset($result['customer']['shipping_address']['phone']) && !empty($result['customer']['shipping_address']['phone'])){
            $shipping_phone = $result['customer']['shipping_address']['phone'];
        }
        if(isset($result['customer']['shipping_address']['city']) && !empty($result['customer']['shipping_address']['city'])){
            $shipping_city = $result['customer']['shipping_address']['city'];
        }
        if(isset($result['customer']['shipping_address']['zip']) && !empty($result['customer']['shipping_address']['zip'])){
            $shipping_zip = $result['customer']['shipping_address']['zip'];
        }
        if(isset($result['customer']['shipping_address']['province']) && !empty($result['customer']['shipping_address']['province'])){
            $shipping_state = $result['customer']['shipping_address']['province'];
        }
        if(isset($result['customer']['shipping_address']['country']) && !empty($result['customer']['shipping_address']['country'])){
            $shipping_country = $result['customer']['shipping_address']['country'];
        }
        if(isset($result['customer']['shipping_address']['country_code']) && !empty($result['customer']['shipping_address']['country_code'])){
            $shipping_country_code = $result['customer']['shipping_address']['country_code'];
        }
        if(isset($result['customer']['shipping_address']['province_code']) && !empty($result['customer']['shipping_address']['province_code'])){
            $shipping_state_code = $result['customer']['shipping_address']['province_code'];
        }
        $super_admin_email = common::SUPER_ADMIN_EMAIL;

        $fulfillEnginrOrderdata = [
            "campaignId" => "", 
            "confirmationEmailAddress" => $super_admin_email, 
            "customerAddress" => [
                "name" => $cust_first_name.' '.$cust_last_name, 
                "nameLine2" => '', 
                "addressLine1" => $cust_address1, 
                "addressLine2" => $cust_address2, 
                "city" => $cust_city, 
                "state" => $cust_province, 
                "postalCode" => $cust_zip, 
                "country" => $cust_country_code, 
                "phone" => $cust_phone 
            ],
            "customerEmailAddress" => $cust_email, 
            "customId" => "", 
            "holdFulfillmentUntilDate" => "", 
            // "isTest" => true, 
            "orderItemGroups"=>$fulfillengineLineItems,
            "shipments" => [
                [
                   "confirmationEmailAddress" => $cust_email, 
                   "customId" => "", 
                   "customPackingSlipUrl" => "", 
                   "giftMessage" => "", 
                   //"items" => $fulfillengineShippingLineItems, 
                   "returnToAddress" => [
                            "name" => "SpiritHero.com", 
                            "nameLine2" => "", 
                            "addressLine1" => "2641 Crow Canyon Rd", 
                            "addressLine2" => "STE 3", 
                            "city" => "San Ramon", 
                            "state" => "CA", 
                            "postalCode" => "94583", 
                            "country" => "US", 
                            "phone" => "800-239-9948"
                         ], 
                   "shippingAddress" => [
                        "name"          => $cust_first_name.' '.$cust_last_name, 
                        "nameLine2"     => '', 
                        "addressLine1"  => $cust_address1, 
                        "addressLine2"  => $cust_address2, 
                        "city"          => $cust_city, 
                        "state"         => $cust_province, 
                        "postalCode"    => $cust_zip, 
                        "country"       => $cust_country_code, 
                        "phone"         => $cust_phone 

                        // "name" => $shipping_first_name, 
                        // "nameLine2" => $shipping_last_name, 
                        // "addressLine1" => $shipping_address1, 
                        // "addressLine2" => $shipping_address2, 
                        // "city" => $shipping_city, 
                        // "state" => $shipping_state_code, 
                        // "postalCode" => $shipping_zip, 
                        // "country" => $shipping_country_code, 
                        // "phone" => $shipping_phone
                    ], 
                   "shippingTier" => "economy", 
                   "shippingCarrier" => "", 
                   "shippingService" => "" 
                ] 
            ]
        ];
        $jsonData = json_encode($fulfillEnginrOrderdata);

        $errorText .= "----------------------------------------------------------------------------";
        $errorText .= " get order data = ".print_r($jsonData, true);
        $errorText .= "----------------------------------------------------------------------------";
        fwrite($logFileOpen, $errorText);
        unset($errorText);

        try {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $fullfill_order_endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>$jsonData,
                CURLOPT_HTTPHEADER => array(
                    'accept: application/json',
                    'X-API-KEY: '.$api_key,
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            //echo $response;
            $fe_res_Data=[];
            if(!empty($response)){
                $fe_res_Data = json_decode($response, true);
                if (isset($fe_res_Data["orderId"])) {
                    $fe_orderId = $fe_res_Data["orderId"];
                    $feOrder_id_data = [
                        "fe_order_id"=>$fe_orderId
                    ];
                    parent::updateTable_f_mdl('store_orders_master',$feOrder_id_data,'shop_order_id="'.$shop_order_id.'"');

                    $getorderSql = "SELECT * FROM store_orders_master  WHERE shop_order_id='".trim($shop_order_id)."' ";
		            $order_List = parent::selectTable_f_mdl($getorderSql);
                    if(!empty($order_List)){
                        $store_order_id = $order_List[0]['id'];
                    }else{
                        $store_order_id ='';
                    }

                    $soim_update_data = [
                        'buyStatus' => '1'
                    ];
                    parent::updateTable_f_mdl('store_order_items_master',$soim_update_data,'store_orders_master_id="'.$store_order_id.'" AND vendor="FulfillEngine" ');
                }
            }
            
            $errorText .= "----------------------------------------------------------------------------";
            $errorText .= " get order response data = ".print_r($response, true);
            $errorText .= "----------------------------------------------------------------------------";
            fwrite($logFileOpen, $errorText);
            unset($errorText);

        }catch (Exception $e){
            $errorText .= "----------------------------------------------------------------------------";
            $errorText .= " get order error = ".print_r($e, true);
            $errorText .= "----------------------------------------------------------------------------";
            fwrite($logFileOpen, $errorText);
            unset($errorText);
            //echo "<pre>";print_r($e);
        }

    }

    public function orderShipped(){
        global $s3Obj;
        global $path;
        require_once($path.'lib/shopify.php');
        require_once($path.'lib/functions.php');
        require_once($path.'lib/class_graphql.php');
        
        $jsondata = file_get_contents('php://input');
        $order_id=$tracker_number=$tracking_url=$customcat_order_id='';
        $result = [];
        if (!empty($jsondata)) {
            $getres = json_decode($jsondata, true);
        }

        $order_id =$getres['order_id'];
        $tracker_number =$getres['tracker_number'];
        $tracking_url =$getres['tracking_url'];
        $customcat_order_id =$getres['customcat_order_id'];

        $customcat_webhoock_data = [
            "customcat_order_id"       => $customcat_order_id,
            "customcat_tracker_number" => $tracker_number,
            "customcat_tracking_url"   => $tracking_url
        ];
        parent::updateTable_f_mdl('store_orders_master',$customcat_webhoock_data,'shop_order_id="'.$order_id.'"');

        // $order_id ='5320632336642';
        // $tracker_number ='14785';
        // $tracking_url ='https://tools.usps.com/go/TrackConfirmAction.action?tRef=fullpage&tLc=1&text28777=&tLabels=9261290278835162776496';
        // $customcat_order_id ='';

        require_once(common::EMAIL_REQUIRE_URL);
        if(strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp')!==false){
            $objAWS = new aws_ses_smtp();
        }else if(strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail')!==false){
            $objAWS = new sendGridEmail();
        }else{
            $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
        }
        $mailSendStatus = $objAWS->sendEmail(['dilipyadav@bitcot.com'], 'Custom cat order2', $jsondata, $jsondata);
        $mailSendStatus = $objAWS->sendEmail(['dilipyadav@bitcot.com'], 'Custom cat order1', $getres, $getres);

        $shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
        //$shop_id = $shop_data[0]['id'];
        $shop  = $shop_data[0]['shop_name'];
        $token = $shop_data[0]['token'];

        $headers = array(
            'X-Shopify-Access-Token' => $token
        );
        $graphql = new Graphql($shop, $headers);
        
        $fulfillmentOrdersData =[];
        $sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);

        try {
            $fulfillmentOrdersData = $sc->call('GET', '/admin/api/2023-04/orders/'.$order_id.'/fulfillment_orders.json');
        } catch (ShopifyApiException $e){

        }

        if (!empty($fulfillmentOrdersData)) {
            $fulfillment_order_id = $fulfillmentOrdersData[0]['id'];
            $itemsArray = [];
            foreach ($fulfillmentOrdersData[0]['line_items'] as $item) {
                $newItem = [
                    "id" => "gid://shopify/FulfillmentOrderLineItem/".$item['id'],
                    "quantity" => $item["quantity"]
                ];
                $itemsArray[] = $newItem;
            }
            $itemsdata = json_encode($itemsArray);

            $mutationData ='mutation MarkAsFulfilledSubmit($fulfillment: FulfillmentV2Input!, $message: String) {
                fulfillmentCreateV2(fulfillment: $fulfillment, message: $message) {
                  fulfillment {
                    id
                    __typename
                  }
                  userErrors {
                    field
                    message
                    __typename
                  }
                  __typename
                }
            }';

            $inputData = array(
                "fulfillment" => array(
                    "trackingInfo" => array(
                        "numbers" => array($tracker_number),
                        "urls" => null,
                        "company" => "UPS"
                    ),
                    "notifyCustomer" => true,
                    "lineItemsByFulfillmentOrder" => array(
                        array(
                            "fulfillmentOrderId" => "gid://shopify/FulfillmentOrder/".$fulfillment_order_id,
                            "fulfillmentOrderLineItems" => json_decode($itemsdata, true)
                        )
                    )
                )
            );

            $inputDataJson = json_encode($inputData);
            $res=$graphql->runByMutation($mutationData,$inputDataJson);
        }

        echo "OK";
        http_response_code(200);
        return;
        exit;

    }

    /*========Get Apparel Cast Senmar==========*/
    public function checkProductApparelCast($style,$color,$size,$quantity,$verient_id)
    {
        require_once(common::EMAIL_REQUIRE_URL);
        if(strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp')!==false){
            $objAWS = new aws_ses_smtp();
        }else if(strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail')!==false){
            $objAWS = new sendGridEmail();
        }else{
            $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
        }
       
        $localhostWsdlUrl = common::PRODUCT_INFO;
        $client= new SoapClient($localhostWsdlUrl, array(
            'trace'=>true,
            'exceptions'=>true
        ));
        $webServiceUser =array(
            'sanMarCustomerNumber' => common::sanMarCustomerNumber,
            'sanMarUserName' => common::sanMarUserName,
            'sanMarUserPassword' => common::sanMarUserPassword
        );
       
        $verient_id= ($verient_id)?trim($verient_id):'';
       
        $style = ($style)?trim($style):'';
        $sizeget = $this->getSize(trim($size));
        $color_code='';

        $getcolorcodeSql = "SELECT store_product_variant_master_id,spvm.sanmar_color_code,sopvm.sku,sopvm.size,spvm.color,spcm.product_color_name FROM store_owner_product_variant_master as sopvm INNER JOIN  store_product_variant_master as spvm ON spvm.id=sopvm.store_product_variant_master_id INNER JOIN store_product_colors_master as spcm ON spcm.product_color=spvm.color WHERE  sopvm.size='".trim($size)."' AND sopvm.sku='".$style."' AND  spcm.product_color_name='".trim($color)."'";
		$sanmarcolorcode_List = parent::selectTable_f_mdl($getcolorcodeSql);

        if(!empty($sanmarcolorcode_List)){
            $color_code= $sanmarcolorcode_List[0]['sanmar_color_code'];
        }
        $arr = [
            'style' => trim($style),
            'color' => trim($color_code),
            'size' => trim($sizeget)
        ];
        $getProductInfoByStyleColorSize= array('arg0' =>$arr,'arg1' =>$webServiceUser );

        $result=$client->__soapCall('getProductInfoByStyleColorSize',array('getProductInfoByStyleColorSize' => $getProductInfoByStyleColorSize) );
        $json=json_encode($result);
        $array = json_decode($json, true);
        $casePrice = 0;
        $sanmar_apparel_cast=0;
        $totalPrice=0;
        if(!empty($array['return']['listResponse']['productPriceInfo'])){
            if(!empty($array['return']['listResponse']['productPriceInfo']['caseSalePrice'])){
                $casePrice = $array['return']['listResponse']['productPriceInfo']['caseSalePrice'];
            }else{
                $casePrice = $array['return']['listResponse']['productPriceInfo']['casePrice'];
            }
        }else{
            if(!empty($array['return']['listResponse'][0]['productPriceInfo']['caseSalePrice'])){
                $casePrice = $array['return']['listResponse'][0]['productPriceInfo']['caseSalePrice'];
            }else{
                if(!empty($array['return']['listResponse'][0]['productPriceInfo']['casePrice'])){
                    $casePrice = $array['return']['listResponse'][0]['productPriceInfo']['casePrice'];
                }
            }   
        }
        $casePrice=json_encode($casePrice);
        $sanmarResponce = [];
        if(!empty($array['return']['listResponse']['productBasicInfo'])){
            $sanmarResponce = $array['return']['listResponse']['productBasicInfo'];
        }else{
            if(!empty($array['return']['listResponse'][0]['productBasicInfo'])){
                $sanmarResponce = $array['return']['listResponse'][0]['productBasicInfo']; 
            }   
        }
        $sanmar_apparel_cast += str_replace(",","",$casePrice);
        $sanmar_apparel_cast = str_replace(",","",$sanmar_apparel_cast);
        $totalPrice += str_replace(",","",$casePrice) * $quantity;
        $sanmar_total_apparel_cast = str_replace(",","",$totalPrice);

        return $sanmar_total_apparel_cast;
    }
    /*========Get Apparel Cast Senmar==========*/

    public function getSize($size){
        switch (true) {
                
            case strpos($size,'Youth XS') !== false:
                return 'XS';
                break;
            case strpos($size,'Youth S') !== false:
                return 'S';
                break;
            case strpos($size,'Youth M') !== false:
                return 'M';
                break;
            case strpos($size,'Youth L') !== false:
                return 'L';
                break;
            case strpos($size,'Youth XL') !== false:
                return 'XL';
                break;
            case strpos($size,'Adult XS') !== false:
                return 'XS';
                break;
            case strpos($size,'Adult S') !== false:
                return 'S';
                break;
            case strpos($size,'Adult M') !== false:
                return 'M';
                break;
            case strpos($size,'Adult L') !== false:
                return 'L';
                break;
            case strpos($size,'Adult XL') !== false:
                return 'XL';
                break;
            case strpos($size,'Adult 2XL') !== false:
                return '2XL';
                break;
            case strpos($size,'Adult 3XL') !== false:
                return '3XL';
                break;
            case strpos($size,'Adult 4XL') !== false:
                return '4XL';
                break;
            default:
                return $size;
                break;
            
        }       
    }

    public function orderShippedFulfillEngine(){
        global $s3Obj;
        global $path;
        require_once($path.'lib/shopify.php');
        require_once($path.'lib/functions.php');
        require_once($path.'lib/class_graphql.php');

        require_once(common::EMAIL_REQUIRE_URL);
        if(strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp')!==false){
            $objAWS = new aws_ses_smtp();
        }else if(strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail')!==false){
            $objAWS = new sendGridEmail();
        }else{
            $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
        }
        
        $jsondatafe = file_get_contents('php://input');
        $fe_order_id=$tracker_number=$tracking_url=$shipment_id=$campaign_id='';
        $result = [];
        if (!empty($jsondatafe)) {
            $getferes = json_decode($jsondatafe, true);
            $fe_order_id    =$getferes['orderId'];
            $shipment_id    = $getferes['shipmentId'];
            $campaign_id    = $getferes['campaignId'];
            $tracker_number = $getferes['trackingNumber'];
            $tracking_url   = $getferes['trackingUrl'];
            $shippingCarrier   = $getferes['shippingCarrier'];
        }

        $fe_webhoock_data = [
            "fe_order_id"       => $fe_order_id,
            "fe_shipment_id"    => $shipment_id,
            "fe_campaign_id"    => $campaign_id,
            "fe_tracker_number" => $tracker_number,
            "fe_tracking_url"   => $tracking_url,
            "created_on"        => @date('Y-m-d H:i:s')
        ];
        parent::insertTable_f_mdl('fe_webhook_master',$fe_webhoock_data);
        $shop_order_id = '';
        $ordersql = "SELECT shop_order_id,fe_order_id FROM store_orders_master WHERE fe_order_id = '".$fe_order_id."' ";
        $shop_order_data = parent::selectTable_f_mdl($ordersql);
        if(!empty($shop_order_data)){
            $shop_order_id = $shop_order_data[0]['shop_order_id'];
        }

        $shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
		if(!empty($shop_data)) {
			require_once('lib/class_graphql.php');

			$shop = $shop_data[0]['shop_name'];
			$token = $shop_data[0]['token'];

			$headers = array(
				'X-Shopify-Access-Token' => $token
			);
			$graphql = new Graphql($shop, $headers);
		}
        if(!empty($shop_order_data)){
		
            $gql_query = '{
            order(id: "gid://shopify/Order/'.$shop_order_id.'") {
                fulfillmentOrders(first: 10) {
                edges {
                    node {
                    id
                    status
                    }
                }
                }
            }
            }';
            $response1 = $graphql->runByQuery($gql_query);
            $fulfillmentOrderId = $response1['data']['order']['fulfillmentOrders']['edges'][0]['node']['id'];
            // $fulfillmentOrderStatus = $response1['data']['order']['fulfillmentOrders']['edges'][0]['node']['status'];
            // echo $fulfillmentOrderId;die;
            //$fulfillmentOrderId='gid://shopify/FulfillmentOrder/6785101431042';
            
            $mutationData = 'mutation MarkAsFulfilledSubmit($fulfillment: FulfillmentV2Input!, $message: String) {
            fulfillmentCreateV2(fulfillment: $fulfillment, message: $message) {
                fulfillment {
                id
                __typename
                }
                userErrors {
                field
                message
                __typename
                }
                __typename
            }
            }';

            // Assuming $tracing_no, $USPS, and $fulfillmentOrderId are already defined
            $inputData = json_encode([
            'fulfillment' => [
                'trackingInfo' => [
                'numbers' => [$tracker_number],
                'urls' => null,
                'company' => $shippingCarrier
                ],
                'notifyCustomer' => true,
                'lineItemsByFulfillmentOrder' => [
                [
                    'fulfillmentOrderId' => $fulfillmentOrderId
                ]
                ]
            ],
            'message' => 'Fulfilled By API'
            ]);

            $graphql->runByMutation($mutationData, $inputData);
        }
		
        $mailSendStatus = $objAWS->sendEmail(['dilipyadav@bitcot.com'], 'fulfillengine order webhook', $jsondatafe, $jsondatafe);

        echo "OK";
        http_response_code(200);
        return;
        exit;

    }

    // public function getPrintLocationDataFulfillengine($vendor_product_id){
    //     $catalog_products_endpoint ='https://fulfillengine-api.azurewebsites.net/api/accounts/act-8330840/catalogproducts';
    //     $api_key='sCfhzA2340fNfMCJmqvpzvopBy3yBFuK-stpHFdmtNAPoUgV85EASFweojTp9JX_RZr4wH4TDsIds_ugJbjDCQ';

    //     try {

    //         $curl = curl_init();

    //         curl_setopt_array($curl, array(
    //             CURLOPT_URL => $catalog_products_endpoint,
    //             CURLOPT_RETURNTRANSFER => true,
    //             CURLOPT_ENCODING => '',
    //             CURLOPT_MAXREDIRS => 10,
    //             CURLOPT_TIMEOUT => 0,
    //             CURLOPT_FOLLOWLOCATION => true,
    //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //             CURLOPT_CUSTOMREQUEST => 'POST',
    //             CURLOPT_POSTFIELDS =>'{
    //                 "catalogProductIds": [
    //                 "'.$vendor_product_id.'"
    //                 ]
    //             }',
    //             CURLOPT_HTTPHEADER => array(
    //             'accept: text/plain',
    //             'X-API-KEY: '.$api_key,
    //             'Content-Type: application/json',
    //             //'Cookie: ARRAffinity=b1633e0e24eb358f6ad73d240f6693706fe7b6a1916a7cd60c898ba804a95116; ARRAffinitySameSite=b1633e0e24eb358f6ad73d240f6693706fe7b6a1916a7cd60c898ba804a95116'
    //             ),
    //         ));

    //         $response = curl_exec($curl);
    //         curl_close($curl);
    //     }catch (Exception $e){
    //         $response=$e;
    //         //echo "<pre>";print_r($e);
    //     }

    //     $resData = [];
    //     $id = '';
    //     $name = '';
    //     $eligiblePrintingMethods = [];
    //     $printLocations = [];
    //     if (!empty($response)) {
    //         $resultData = json_decode($response, true);
    //     }
    //     if(isset($resultData[0]['id']) && !empty($resultData[0]['id'])){
    //         $id = $resultData[0]['id'];
    //         $name = $resultData[0]['name'];
    //         $eligiblePrintingMethods = $resultData[0]['eligiblePrintingMethods'];
    //         $printLocations = $resultData[0]['printLocations'];
    //     }
    //     $resData['id']=$id;
    //     $resData['name']=$name;
    //     $resData['eligiblePrintingMethods']=$eligiblePrintingMethods;
    //     $resData['printLocations']=$printLocations;

    //     return $resData;
    // }
}