<?php
include_once 'model/cron_match_price_mdl.php';

class cron_match_price_ctl extends cron_match_price_mdl
{

    /* function index() {

        

        $response = array();
        require_once('lib/shopify.php');
        

		$shop_data = parent::getStoreInfo_f_mdl();

		$shop_id = $shop_data[0]['id'];
		$shop = $shop_data[0]['shop_name'];
		$token = $shop_data[0]['token'];
		
		$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);

		try {
            $productIdArr = parent::getAllProductID_f_mdl();
            $List = implode(',', $productIdArr);
            $res = $sc->call('GET', '/admin/api/2023-04/products.json?fields=id,variants&ids='.$List.',7327097159854&limit=250');	
            $count = count($res);
           // $response[] = $res;
            for($i=0;$i<$count;$i++){

                if( is_array( $res[$i]['variants'] ) ) {

                    foreach( $res[$i]['variants'] as $value ) {
                        $variant_id = $value['id'];
                        $variant_price = $value['price'];
                        $response[] = $variant_id ."-".$variant_price;
                    }
                }
                //$variant_id = $res[$i]['variants']['id'];
                //$variant_price = $res[$i]['variants']['price'];

               // $response[] = $response ."-".$variant_price;
            }

           
		} catch (ShopifyApiException $e){
		} catch (ShopifyCurlException $e) {
        }
        return $response; 
    } */


    function sync_order_process_old() {

        $order_id = $_REQUEST['id']; 
        $orders = [$order_id];

        $response = array();
        require_once('lib/shopify.php');
        
        $varStoreName = 'spirithero1.myshopify.com';

		$shop_data = parent::getStoreInfo_f_mdl();

		$shop_id = $shop_data[0]['id'];
		$shop = $shop_data[0]['shop_name'];
		$token = $shop_data[0]['token'];
		
        print_r( $shop_data ); 

		$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET); 
        echo "<br>".$List = implode(',', $orders);
        $res = $sc->call('GET', '/admin/api/2023-04/orders.json?ids='.$List.'&limit=250');
        echo "<pre>";print_r($res);
        $count = count($res);
        $response[] = $res;

        if( !isset( $res ) ) {
            $response[] =  "Not fetch Shopfy api";
        }
        
        for($i=0;$i<$count;$i++){

            if( is_array( $res[$i]['line_items'] ) ) {
                $shop_order_id = $res[$i]['id'] ;
                $sql ='SELECT id,shop_order_id,shop_order_number,store_master_id FROM `store_orders_master` WHERE shop_order_id = "'.$shop_order_id.'"';
                $order_exist = parent::selectTable_f_mdl($sql);

                if (empty($order_exist)) {
                    foreach ($res[$i]['line_items'] as $single_item) {
                        $orderData  = $sc->call('GET', '/admin/api/2023-04/variants/'.$single_item['variant_id'].'.json');
                        echo "<pre>";print_r($orderData);
                        $size        = $orderData['option1'];
                        $sku         = $orderData['sku'];
                        $title       = $single_item['title'];
                        $arr         = explode('-', $title);
                        $store_name  = $arr[0];
                        echo "<br>".$productName = str_replace($arr[0]."-","",$title);
                        $storeSql    = 'SELECT * FROM `store_master` where store_name = "'.$store_name.'" LIMIT 1';
                        $store_master_id='';
                        $store_owner_product_master_id = '';
                        $store_sale_type_master_id = '';
                        $storeData = parent::selectTable_f_mdl($storeSql);
                        if (!empty($storeData)) {
                            echo "<br>".$store_master_id           = $storeData[0]['id'];
                            echo "<br>".$storeName                 = $storeData[0]['store_name'];
                            echo "<br>".$store_sale_type_master_id = $storeData[0]['store_sale_type_master_id'];
                        }
                        
                        echo $checksql  = 'SELECT * FROM `store_owner_product_master` where store_master_id = "'.$store_master_id.'" AND product_title like "%'.$productName.'%"';
                        // $checkData = parent::selectTable_f_mdl($checksql);

                        // $store_owner_product_variant_master_id = '';
                        // if (!empty($checkData)) {
                      
                        //     $store_owner_sql  = 'SELECT * FROM store_owner_product_master where product_title = "'.$productName.'" AND store_master_id ="'.$store_master_id.'" ';
                        //     $store_owner_data = parent::selectTable_f_mdl($store_owner_sql);

                        //     $store_owner_product_master_id = $store_owner_data[0]['id'];

                        //     $sopm_update_data = [];
                        //     $sopm_update_data = [
                        //         'shop_product_id' =>$single_item['product_id']
                        //     ];

                        //     parent::updateTable_f_mdl('store_owner_product_master',$sopm_update_data,'id="'.$store_owner_product_master_id.'"');

                        //     $store_ownerpdv_sql  = 'SELECT * FROM store_owner_product_variant_master where size = "'.$size.'" AND sku = "'.$sku.'" AND  store_owner_product_master_id = "'.$store_owner_product_master_id.'" LIMIT 1';
                        //     $store_ownerpdv      = parent::selectTable_f_mdl($store_ownerpdv_sql);
                        //     $store_owner_product_variant_master_id = $store_ownerpdv[0]['id'];
                        //     $sovm_update_data    = [];
                        //     foreach ($store_ownerpdv as $value) {
                        //         $sovm_update_data = [
                        //             'shop_product_id' =>$single_item['product_id'],
                        //             'shop_variant_id' =>$single_item['variant_id']
                        //         ];

                        //         parent::updateTable_f_mdl('store_owner_product_variant_master',$sovm_update_data,'id="'.$value['id'].'"');
                        //     }
                        // }

                        // // echo "Sawan Test</br>";

                        // // echo "<pre>";print_r($orderData);

                        // $shop_product_id = $single_item['product_id'];

                        // $shop_variant_id = $single_item['variant_id'];
                        // $cust_phone = '';
                        // if(isset($res[$i]['customer']['phone']) && !empty($res[$i]['customer']['phone'])){
                        //     $cust_phone = $res[$i]['customer']['phone'];
                        // }else if(isset($res[$i]['customer']['default_address']['phone']) && !empty($res[$i]['customer']['default_address']['phone'])){
                        //     $cust_phone = $res[$i]['customer']['default_address']['phone'];
                        // }else if(isset($res[$i]['shipping_address']['phone']) && !empty($res[$i]['shipping_address']['phone'])){
                        //     $cust_phone = $res[$i]['shipping_address']['phone'];
                        // }else if(isset($res[$i]['billing_address']['phone']) && !empty($res[$i]['billing_address']['phone'])){
                        //     $cust_phone = $res[$i]['billing_address']['phone'];
                        // }
    
                        // $studentName = $sortListName = '';
                        // foreach($res[$i]['note_attributes'] as $objProperties){
                        //     if(isset($objProperties['name']) && !empty($objProperties['name'])){
                        //         if($objProperties['name'] == 'student_name'){
                        //             $studentName =$objProperties['value'];
                        //         }
                        //         if($objProperties['name'] == 'sort_list_name'){
                        //             $sortListName =$objProperties['value'];
                        //         }
                        //     }
                        // }
                                
                        // $som_insert_data = [
                        //     'store_master_id' => $store_master_id,
                        //     'store_sale_type' => $store_sale_type_master_id,
                        //     'shop_order_id' => $shop_order_id,
                        //     'shop_order_number' => $res[$i]['order_number'],
                        //     'total_price' => $res[$i]['total_price'],
                        //     'total_fundraising_amount' => "0.00",
                        //     'shop_cust_id' => $res[$i]['customer']['id'],
                        //     'cust_email' => $res[$i]['customer']['email'],
                        //     'cust_name' => $res[$i]['customer']['first_name'].' '.$res[$i]['customer']['last_name'],
                        //     'cust_phone' => $cust_phone,
                        //     'json_data' => json_encode($res[$i]),
                        //     'sortlist_info' => $sortListName,
                        //     'student_name' => $studentName,
                        //     'created_on' => @date('Y-m-d H:i:s'),
                        //     'created_on_ts' => time(),
                        // ];
                        // $som_arr = parent::insertTable_f_mdl('store_orders_master',$som_insert_data);
                        // if(isset($som_arr['insert_id']) && !empty($som_arr['insert_id'])){
                        //     $total_fundraising_amount = 0;
                        //     $productTags = '';

                        //     try {
                        //         $productJson = $sc->call('GET', '/admin/api/2023-04/products/'.$single_item['product_id'].'.json?fields=tags'); 
                    
                        //         $productTags = $productJson['tags'];
                        //     } catch (ShopifyApiException $e){
                        //     } catch (ShopifyCurlException $e) {
                        //     }



                        //     $total_fundraising_amount += floatval($store_var_data[0]['fundraising_price'])*$single_item['quantity'];

                        //     $soim_insert_data = [
                        //         'store_master_id' => $store_master_id,
                        //         'store_owner_product_master_id' => $store_owner_product_master_id,
                        //         'store_owner_product_variant_master_id' => $store_owner_product_variant_master_id,
                        //         'store_orders_master_id' => $som_arr['insert_id'],
                        //         'shop_product_id' => $single_item['product_id'],
                        //         'shop_variant_id' => $single_item['variant_id'],
                        //         'title' => $single_item['title'],
                        //         'quantity' => $single_item['quantity'],
                        //         'price' => $single_item['price'],
                        //         'sku' => $single_item['sku'],
                        //         'vendor' => $single_item['vendor'],
                        //         'variant_title' => $single_item['variant_title'],
                        //         'tags' => $productTags,
                        //         'created_on' => @date('Y-m-d H:i:s'),
                        //         'created_on_ts' => time(),
                        //     ];
                        //     parent::insertTable_f_mdl('store_order_items_master',$soim_insert_data);
                        //     //update total_fundraising_amount in main order
                        //     $som_update_data = [
                        //         'total_fundraising_amount' => $total_fundraising_amount
                        //     ];
                        //     parent::updateTable_f_mdl('store_orders_master',$som_update_data,'id="'.$som_arr['insert_id'].'"');
                        //     echo "Update order ";
                        // }
                    } 
                    die();   
                } 
            }
        }
        die('njn');
    }

        function sync_order_process() {

        $order_id = $_REQUEST['id']; 
        $orders = [$order_id];

        $response = array();
        require_once('lib/shopify.php');
        
        $varStoreName = 'spirithero1.myshopify.com';

        $shop_data = parent::getStoreInfo_f_mdl();

        $shop_id = $shop_data[0]['id'];
        $shop = $shop_data[0]['shop_name'];
        $token = $shop_data[0]['token'];
        
        print_r( $shop_data ); 

        $sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);

       try { 
            $List = implode(',', $orders);
            $res = $sc->call('GET', '/admin/api/2023-04/orders.json?ids='.$List.'&limit=250');  
            $count = count($res);
            $response[] = $res;

            if( !isset( $res ) ) {
                $response[] =  "Not fetch Shopfy api";
            }
            
            for($i=0;$i<$count;$i++){

                if( is_array( $res[$i]['line_items'] ) ) {
                    $shop_order_id = $res[$i]['id'] ;
                   echo $sql ='
                        SELECT id,shop_order_id,shop_order_number,store_master_id FROM `store_orders_master`
                        WHERE shop_order_id = "'.$shop_order_id.'"
                    ';
                
                    $order_exist = parent::selectTable_f_mdl($sql);
                
                    if(empty($order_exist)){
                        //if order is not existed in db, then insert it
                        //now check order is from which store
                        $store_master_id = '';
                        foreach( $res[$i]['line_items'] as $lineitem ) {
                            $sql='
                                SELECT `store_owner_product_master`.store_master_id, `store_sale_type_master`.sale_type FROM `store_owner_product_master`
                                LEFT JOIN `store_master` ON `store_master`.id = `store_owner_product_master`.store_master_id
                                LEFT JOIN `store_sale_type_master` ON `store_sale_type_master`.id = `store_master`.store_sale_type_master_id
                                WHERE `store_owner_product_master`.shop_product_id = "'.$lineitem['product_id'].'"
                            ';
                        
                            $store_id_arr = parent::selectTable_f_mdl($sql);

                            if(isset($store_id_arr[0]['store_master_id']) && $store_id_arr[0]['store_master_id'] != '') {
                                $store_master_id = $store_id_arr[0]['store_master_id'];
                            }
                        }
                        
        
                        if(isset($store_master_id) && !empty($store_master_id)){
                            //$store_master_id = $store_id_arr[0]['store_master_id'];
        
                            $cust_phone = '';
                            if(isset($res[$i]['customer']['phone']) && !empty($res[$i]['customer']['phone'])){
                                $cust_phone = $res[$i]['customer']['phone'];
                            }else if(isset($res[$i]['customer']['default_address']['phone']) && !empty($res[$i]['customer']['default_address']['phone'])){
                                $cust_phone = $res[$i]['customer']['default_address']['phone'];
                            }else if(isset($res[$i]['shipping_address']['phone']) && !empty($res[$i]['shipping_address']['phone'])){
                                $cust_phone = $res[$i]['shipping_address']['phone'];
                            }else if(isset($res[$i]['billing_address']['phone']) && !empty($res[$i]['billing_address']['phone'])){
                                $cust_phone = $res[$i]['billing_address']['phone'];
                            }
        
                            $studentName = $sortListName = '';
                            foreach($res[$i]['note_attributes'] as $objProperties){
                                if(isset($objProperties['name']) && !empty($objProperties['name'])){
                                    if($objProperties['name'] == 'student_name'){
                                        $studentName =$objProperties['value'];
                                    }
                                    if($objProperties['name'] == 'sort_list_name'){
                                        $sortListName =$objProperties['value'];
                                    }
                                }
                            }
                                    
                            $som_insert_data = [
                                'store_master_id' => $store_master_id,
                                'store_sale_type' => $store_id_arr[0]['sale_type'],
                                'shop_order_id' => $shop_order_id,
                                'shop_order_number' => $res[$i]['order_number'],
                                'total_price' => $res[$i]['total_price'],
                                'total_fundraising_amount' => "0.00",
                                'shop_cust_id' => $res[$i]['customer']['id'],
                                'cust_email' => $res[$i]['customer']['email'],
                                'cust_name' => $res[$i]['customer']['first_name'].' '.$res[$i]['customer']['last_name'],
                                'cust_phone' => $cust_phone,
                                'json_data' => json_encode($res[$i]),
                                'sortlist_info' => $sortListName,
                                'student_name' => $studentName,
                                'created_on' => @date('Y-m-d H:i:s'),
                                'created_on_ts' => time(),
                            ];

                            $som_arr = parent::insertTable_f_mdl('store_orders_master',$som_insert_data);
                            if(isset($som_arr['insert_id']) && !empty($som_arr['insert_id'])){
                                $total_fundraising_amount = 0;
        
                                //now insert item data
                                foreach($res[$i]['line_items'] as $single_item){
                                    $sql='
                                        SELECT id, store_owner_product_master_id, fundraising_price FROM `store_owner_product_variant_master`
                                        WHERE shop_product_id = "'.$single_item['product_id'].'"
                                        AND shop_variant_id = "'.$single_item['variant_id'].'"
                                        LIMIT 1
                                    ';
                                    $store_var_data = parent::selectTable_f_mdl($sql);
                                    
                                    if(!empty($store_var_data)){
        
                                        $productTags = '';

                                        try {
                                            $productJson = $sc->call('GET', '/admin/api/2023-04/products/'.$single_item['product_id'].'.json?fields=tags'); 
                                
                                            $productTags = $productJson['tags'];
                                        } catch (ShopifyApiException $e){
                                        } catch (ShopifyCurlException $e) {
                                        }


        
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
                                $response[] = "Update order ";
                            }
                        }
                    }else {
                        $store_orders_master_id = $order_exist[0]['id'];
                        $shop_order_number = $order_exist[0]['shop_order_number'];
                        $store_master_id = $order_exist[0]['store_master_id'];
                        $total_fundraising_amount = 0;
                        if( isset( $store_orders_master_id ) && $store_orders_master_id != '' ) {
                            //now insert item data
                            foreach($res[$i]['line_items'] as $single_item){

                                echo $sql ='
                                    SELECT id FROM `store_order_items_master`
                                    WHERE shop_product_id = "'.$single_item['product_id'].'"
                                    AND shop_variant_id = "'.$single_item['variant_id'].'" AND  store_orders_master_id = "'.$store_orders_master_id.'"
                                    LIMIT 1
                                ';
                        
                                $order_line_exist = parent::selectTable_f_mdl($sql);
                        
                                if(empty($order_line_exist)){
                                                              
                                    echo $sql='
                                        SELECT id, store_owner_product_master_id, fundraising_price FROM `store_owner_product_variant_master`
                                        WHERE shop_product_id = "'.$single_item['product_id'].'"
                                        AND shop_variant_id = "'.$single_item['variant_id'].'"
                                        LIMIT 1
                                    ';
                                    $store_var_data = parent::selectTable_f_mdl($sql);
                            
                                    if(!empty($store_var_data)){

                                        $productTags = '';

                                        try {
                                            $productJson = $sc->call('GET', '/admin/api/2023-04/products/'.$single_item['product_id'].'.json?fields=tags'); 
                                
                                            $productTags = $productJson['tags'];
                                        } catch (ShopifyApiException $e){
                                        } catch (ShopifyCurlException $e) {
                                        }
                                    
                                       
                                        $total_fundraising_amount += floatval($store_var_data[0]['fundraising_price'])*$single_item['quantity'];

                                        $soim_insert_data = [
                                            'store_master_id' => $store_master_id,
                                            'store_owner_product_master_id' => $store_var_data[0]['store_owner_product_master_id'],
                                            'store_owner_product_variant_master_id' => $store_var_data[0]['id'],
                                            'store_orders_master_id' => $store_orders_master_id,
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
                                        print_r($soim_insert_data);
                                        parent::insertTable_f_mdl('store_order_items_master',$soim_insert_data);
                                        $response[] = "Created order items";
                                    }else {
                                        $response[] = "Missing master product id";
                                    }
                                }else {
                                    $response[] = "Missing master product id";
                                }
                                
                            } 
                        }else {
                            $response[] = "Missing Master id";
                        }

                        
                    }
                    /* foreach( $res[$i]['line_items'] as $value ) {
                        $response[] = $value['id'];
                        //$variant_price = $value['price'];
                        //$response[] = $variant_id ."-".$variant_price;
                    } */
                }
                //$variant_id = $res[$i]['variants']['id'];
                //$variant_price = $res[$i]['variants']['price'];

               // $response[] = $response ."-".$variant_price;
            }

           
         } catch (ShopifyApiException $e){
            $response[] = $e;
        } catch (ShopifyCurlException $e) {
            $response[] = $e;
        } 
        return $response; 

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