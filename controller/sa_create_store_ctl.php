<?php
include_once 'model/sa_addedit_superadmin_mdl.php';
$path = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';
$s3Obj = new Aws3;
$login_user_email="";
if(isset($_SESSION['user_email']) && $_SESSION['user_email'] != "") {
	$login_user_email=trim($_SESSION['user_email']);
}
class sa_create_store_ctl extends sa_addedit_superadmin_mdl
{
	public $TempSession = "";
	function __construct()
	{
		if (parent::isGET() || parent::isPOST()) {
			if(parent::getVal("method")){
				$this->checkRequestProcess(parent::getVal("method"));
			}else{
				$this->SITE_ACCESS_KEY = parent::getVal("stkn");
			}
		}
		common::CheckLoginSession();
	}

    function checkRequestProcess($requestFor){
        if($requestFor != ""){
            switch($requestFor){
				case "create_store_from_sa":
					$this->create_store_from_sa();
                break;
				case "get_all_template_products_colors":
					$this->get_all_template_products_colors();
                break;

			}
        }
    }

	public function getGeneralSettingDetails(){
		$sql = 'SELECT fullfilment_type_second,fullfilment_type_third,is_enable_in_home,is_enable_in_bagged,is_enable_in_basic FROM general_settings_master WHERE id = 1 LIMIT 1';
		return parent::selectTable_f_mdl($sql);
	}
	
	function create_store_from_sa(){
		global $s3Obj;
		global $login_user_email;
        $s3Obj = new Aws3;
		$res = [];

		if(!empty(parent::getVal("method")) && parent::getVal("method") == "create_store_from_sa"){
			
			$organization_name= trim(parent::getVal('organization_name'));
			$first_name= trim(parent::getVal('first_name'));
			$last_name= trim(parent::getVal('last_name'));
			$email= trim(parent::getVal('email'));
			$mobile= trim(parent::getVal('mobile'));
			$store_name= trim(parent::getVal('store_name'));
			$sale_type= trim(parent::getVal('sale_type'));
			$fulfillment_type= trim(parent::getVal('fulfillment_type'));
			$password = md5(trim(parent::getVal("password")));
			$store_organization_type_master_id="1";
			$notes=trim(parent::getVal("notes"));
			$address_line_1=trim(parent::getVal("address_line_1"));
			$state=trim(parent::getVal("state"));
			$city=trim(parent::getVal("city"));
			$zip_code=trim(parent::getVal("zip_code"));
			$fund_amount=trim(parent::getVal("fund_amount"));
			if($fund_amount <= '0'){
				$fund_status='No';
			}else{
				$fund_status='Yes';
			}
			$store_open_date 	= parent::getVal("store_open_date")!=''?strtotime(parent::getVal("store_open_date").' 0:0'):'';
			$store_close_date 	= parent::getVal("store_close_date")!=''?strtotime(parent::getVal("store_close_date").' 23:59:59'):'';
			if($store_open_date==''){
				$store_open_date=time();
			}

			$product_template_id=trim(parent::getVal("product_template"));
			$product_colors=[];
			if(isset($_POST['product_colors']) && !empty($_POST['product_colors'])){
				$product_colors=$_POST['product_colors'];
			}

			$sql = 'SELECT id,token FROM `store_owner_details_master` WHERE email="'.$email.'"';
			$owner_exist =  parent::selectTable_f_mdl($sql);
			
			if(!empty($owner_exist)){
				$store_owner_details_master_id = $owner_exist[0]['id'];
				$store_owner_details_master_token = $owner_exist[0]['token'];
			}else{
				$sql = 'SELECT id FROM `store_manager_master` WHERE email="'.$email.'"';
				$manager_email_exist =  parent::selectTable_f_mdl($sql);
				if(!empty($manager_email_exist)){
					$res['SUCCESS'] = 'FALSE';
					$res['MESSAGE'] = 'Email already exists as a store manager.';
					// echo json_encode($res,1);exit;
					common::sendJson($res);exit;
				}else{
					$sodm_store_owner_roles_master_id = '1';
					$user_token = time().rand(100000,999999);
					$sodm_insert_data = [
						'first_name' => trim($first_name),
						'last_name' => trim($last_name),
						'email' => trim($email),
						'phone' => trim($mobile),
						'organization_name' => trim($organization_name),
						'store_owner_roles_master_id' => trim($sodm_store_owner_roles_master_id),
						'password' => $password,
						'token' => $user_token,
						'status' => '1',
						'created_on' => @date('Y-m-d H:i:s'),
						'created_on_ts' => time(),
					];
					$store_owner_details_master_arr = parent::insertTable_f_mdl('store_owner_details_master',$sodm_insert_data);
					if(isset($store_owner_details_master_arr['insert_id'])){
						$store_owner_details_master_id = $store_owner_details_master_arr['insert_id'];
						$store_owner_details_master_token = $user_token;
					}
				}
			}

			$sqlgensetting = 'SELECT * FROM `general_settings_master` LIMIT 1';
			$generalSettingData =  parent::selectTable_f_mdl($sqlgensetting);

			if($sale_type == "2"){
	            //On-Demand 
	            $store_description = trim($generalSettingData[0]['fullfilment_ondemand']);
	        }else{
	            if(trim($fulfillment_type=='SHIP_1_LOCATION_NOT_SORT')){
	                //Silver
	                $store_description = trim($generalSettingData[0]['fullfilment_silver']);
	            }else if($fulfillment_type=='SHIP_1_LOCATION_SORT'){
                    //Gold
                    $store_description = trim($generalSettingData[0]['fullfilment_gold']);
                }else if($fulfillment_type=='SHIP_EACH_FAMILY_HOME'){
                    //Platinum
                    $store_description = trim($generalSettingData[0]['fullfilment_platinum']);
                }
	        }

			
			if(!empty($store_owner_details_master_id) && !empty($store_owner_details_master_token)){
				$sm_insert_data = [
					'store_owner_details_master_id' 	=> $store_owner_details_master_id,
					'store_organization_type_master_id' => trim($store_organization_type_master_id),
					'front_side_ink_colors' 			=> "1",
					'back_side_ink_colors' 				=> "",
					'store_fulfillment_type' 			=> $fulfillment_type,
					'store_sale_type_master_id' 		=> $sale_type,
					'actual_store_sale_type_master_id'	=> $sale_type,
					'is_fundraising' 					=> $fund_status,
					'ct_fundraising_price' 				=> $fund_amount,
					'store_name' 						=> $store_name,
					'product_name_identifier' 			=> $store_name,
					'store_description' 				=> $store_description,
					'store_open_date' 					=> $store_open_date,
					'store_close_date' 					=> $store_close_date,
					'status' 							=> '1',
					'is_ach_check' 						=>'',
					'notes'								=>$notes,
					'created_on' 						=> @date('Y-m-d H:i:s'),
					'updated_on' 						=> @date('Y-m-d H:i:s'),
					'created_on_ts' 					=> time(),
				];
				$store_master_arr = parent::insertTable_f_mdl('store_master',$sm_insert_data);
				if(isset($store_master_arr['insert_id'])){

					$storeStatusData =[
						'store_master_id' => $store_master_arr['insert_id'],
						'status'          => '10',
						'created_on'      => date('Y-m-d H:i:s'),
						'updated_by'	  =>"Super Admin <br>(".$login_user_email.")"
					];
					
					$store_status_history = parent::insertTable_f_mdl('store_status_history', $storeStatusData);
					
					$sql_manager="SELECT GROUP_CONCAT(id) as store_master_id FROM store_master WHERE store_owner_details_master_id=".$store_owner_details_master_id." ";
					$storeManagerDataGet =  parent::selectTable_f_mdl($sql_manager);
					
					$store_ids=$storeManagerDataGet[0]['store_master_id'];
					$storeManagerData =[
						'associate_store_ids' => $store_ids,
					];
					$store_manager_stores=parent::updateTable_f_mdl('store_manager_master',$storeManagerData,'store_owner_id="'.$store_owner_details_master_id.'"');

					//insert owner address details
					$store_master_id = $store_master_arr['insert_id'];
					$soam_insert_data = [
						'store_owner_details_master_id' => $store_owner_details_master_id,
						'store_master_id' 				=> $store_master_id,
						'check_payable_to_name' 		=> "",
						'address_line_1' 				=> $address_line_1,
						'address_line_2' 				=> "",
						'country' 						=> "US",
						'city' 							=> $city,
						'state' 						=> $state,
						'zip_code' 						=> $zip_code,
						'status' 						=> '1',
						'created_on' 					=> @date('Y-m-d H:i:s'),
						'created_on_ts' 				=> time(),
					];
					parent::insertTable_f_mdl('store_owner_address_master',$soam_insert_data);

					//insert owner silver delivery address details
					$sosdam_insert_data = [
						'store_owner_details_master_id' => $store_owner_details_master_id,
						'store_master_id' 				=> $store_master_id,
						'first_name' 					=> $first_name,
						'last_name' 					=> $last_name,
						'company_name' 					=> $organization_name,
						'address_line_1' 				=> $address_line_1,
						'address_line_2' 				=> "",
						'country' 						=> "US",
						'city' 							=> $city,
						'state' 						=> $state,
						'zip_code' 						=> $zip_code,
						'status' 						=> '1',
						'created_on' 					=> @date('Y-m-d H:i:s'),
						'created_on_ts' 				=> time(),
					];
					parent::insertTable_f_mdl('store_owner_silver_delivery_address_master',$sosdam_insert_data);
					
					//=======Add sort list===========
					$sslm_insert_data = [
						'store_owner_details_master_id' => $store_owner_details_master_id,
						'store_master_id' => $store_master_id,
						'sort_list_name' => "Test",
						'sort_list_index' => "1",
						'status' => '1',
						'created_on' => @date('Y-m-d H:i:s'),
						'created_on_ts' => time(),
					];
					parent::insertTable_f_mdl('store_sort_list_master',$sslm_insert_data);
					
					//send welcome mail to shop owner
					require_once(common::EMAIL_REQUIRE_URL);
					if(strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp')!==false){
						$objAWS = new aws_ses_smtp();
					}else if(strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail')!==false){
						$objAWS = new sendGridEmail();
					}else{
						$objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
					}

					$emailData = parent::getEmailTemplateInfo(37);
					$sql = 'SELECT * FROM store_master WHERE id="'.$store_master_id.'"';
					$store_data = parent::selectTable_f_mdl($sql);
					$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
					$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
					$subject = $emailData[0]['subject'];
					$to_email = $email;
					$body=$emailData[0]['body'];
					$from_email = common::AWS_ADMIN_EMAIL;
					$attachment = [];
					$storeUrl = '<a target="_blank" href="https://'.$_SERVER['HTTP_HOST'].'/store-owners/login.php">https://'.$_SERVER['HTTP_HOST'].'/store-owners/login.php</a>';

					if(!empty($owner_exist)){
						$body = str_replace('{{PASSWORD}}', 'You already have an account on SpiritHero. So please use that password with the above email to login to your account.', $body);
					}else{
						$body = str_replace('{{PASSWORD}}', trim(parent::getVal("password")), $body);
					}
					$body = str_replace('{{STORE_NAME}}',$store_name,$body);
					$body = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
					$body = str_replace('{{FIRST_NAME}}', $first_name, $body);
					$body = str_replace('{{EMAIL}}', $email, $body);
					$body = str_replace('{{DASHBOARD_LINK}}', $storeUrl, $body);

					$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body);
					/*send mail store manager */
					$store_owner_details_master_id = $store_data[0]['store_owner_details_master_id'];
					$sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_details_master_id . '"';
					$smm_data =  parent::selectTable_f_mdl($sql_managerData);
					if(!empty($smm_data)){
						foreach ($smm_data as $managerData) {
							
							$body       =$emailData[0]['body'];
							$to_email   = $managerData['email'];
							$firstname  = $managerData['first_name'];
							$body       = str_replace('{{FIRST_NAME}}', $firstname, $body);
							$body       = str_replace('{{STORE_NAME}}', $store_name, $body);
							$body       = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
							$body 		= str_replace('{{EMAIL}}', $email, $body);
							$body 		= str_replace('{{DASHBOARD_LINK}}', $storeUrl, $body);
							if(!empty($owner_exist)){
								$body = str_replace('{{PASSWORD}}', 'You already have an account on SpiritHero. So please use that password with the above email to login to your account.', $body);
							}else{
								$body = str_replace('{{PASSWORD}}', trim(parent::getVal("password")), $body);
							}
							$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body);
						}
					}
					/*send mail store manager */

					//send mail to super admin
					$sql = 'SELECT subject,body,recipients FROM `email_templates_master` WHERE id='.common::NEW_STORE_CREATED_TO_SUPER_ADMIN;//Task 11
					$et_data = parent::selectTable_f_mdl($sql);

					$sql = 'SELECT email, first_name, organization_name FROM `store_owner_details_master` WHERE id="'.$store_owner_details_master_id.'"';
					$sodm_data =  parent::selectTable_f_mdl($sql);

					$logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
					$logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
					$store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
					$store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;

					if(!empty($et_data)){
						$subject = $et_data[0]['subject'];
						$body = $et_data[0]['body'];

						$ccMails = '';
						if($et_data[0]['recipients']){
							$recipients = $et_data[0]['recipients'];
							$recipients = str_replace(' ', '', $recipients);
							$ccMails = explode(',', $recipients);
						}

						$to_email   = common::SUPER_ADMIN_EMAIL;
						$from_email = common::AWS_ADMIN_EMAIL;
						$attachment = [];

						$body = str_replace('{{ORGANIZATION_NAME}}',$sodm_data[0]['organization_name'],$body);
						$body = str_replace('{{STORE_NAME}}',$store_name, $body);
						$body = str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
						$body = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
						$body = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);
						
						$mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $body, $body,$ccMails);
						
					}

					//add template products
					if(!empty($product_template_id)){
						$prodsql="SELECT store_product_master_id FROM product_templates_master_details WHERE product_templates_master_id='".$product_template_id."' ";
						$prodsql_data =  parent::selectTable_f_mdl($prodsql);
						foreach($prodsql_data as $singleprod){
							$sql="SELECT * FROM store_product_master WHERE id='".$singleprod['store_product_master_id']."' AND is_deleted='0' ";
							$master_proddata =  parent::selectTable_f_mdl($sql);
							if(!empty($master_proddata)){

								$sql="SELECT product_group FROM minimum_group_product WHERE id='".$master_proddata[0]['group_id']."' ";
								$groupdata =  parent::selectTable_f_mdl($sql);

								if(!empty($master_proddata)){
									if($master_proddata[0]['id']=='789' || $master_proddata[0]['id']=='169'){
										$is_persionalization = '1';
										$is_require = '1';
									}else{
										$is_persionalization = '0';
										$is_require = '0';
									}

									$sopm_insert_data = [
										'store_master_id' 			=> $store_master_id,
										'is_product_fundraising'	=> $fund_status,
										'store_product_master_id' 	=> $master_proddata[0]['id'],
										'product_title'       		=> $master_proddata[0]['product_title'],
										'product_description' 		=> $master_proddata[0]['product_description'],
										'tags'         				=> $master_proddata[0]['tags'],
										'group_name'   				=> $groupdata[0]['product_group'],
										'status'       				=> '1',
										'is_personalization' 		=> $is_persionalization,
										'is_required'		 		=> $is_require,
										'created_on'   				=> @date('Y-m-d H:i:s'),
										'created_on_ts'				=> time()
									];
									$sopm_arr    = parent::insertTable_f_mdl('store_owner_product_master',$sopm_insert_data);
									if (isset($sopm_arr['insert_id'])) {
										$sopm_id  = $sopm_arr['insert_id'];

										$JsonproductArray = json_encode(array_values($product_colors));
										$colorCodeValues  = str_replace (array('[', ']'), '' , $JsonproductArray);
										$sql = 'SELECT * FROM `store_master` WHERE id="' . $store_master_id . '"';
										$store_master_data =  parent::selectTable_f_mdl($sql);
										$sql = 'SELECT * FROM `store_product_variant_master` WHERE store_product_master_id="'.$master_proddata[0]['id'].'" AND color IN('.$colorCodeValues.') AND store_organization_type_master_id = '.$store_organization_type_master_id.' AND is_ver_deleted="0" ';
										$var_list =  parent::selectTable_f_mdl($sql);

										if (!empty($var_list)) {
											foreach ($var_list as $var_data) {
												$image = $var_data['image'];
												
												// Task 42 start
												$sql = 'SELECT price,price_on_demand from store_product_variant_master where id="'.$var_data['id'].'" AND is_ver_deleted="0" ';
												$storeProductVariantMaster = parent::selectTable_f_mdl($sql);
												
												$add_cost = 0;
												if(isset($store_master_data[0]['front_side_ink_colors']) && !empty($store_master_data[0]['front_side_ink_colors'])){
													$add_cost += intval($store_master_data[0]['front_side_ink_colors'])-1;
												}

												$add_on_cost = 0;
												if(isset($store_master_data[0]['back_side_ink_colors']) && !empty($store_master_data[0]['back_side_ink_colors'])){
													$add_cost   += common::ADD_COST_BACK_SIDE_INK_COLOR+intval($store_master_data[0]['back_side_ink_colors'])-1;
													$add_on_cost = common::ADD_COST_BACK_SIDE_INK_COLOR;
												}
												
												$fullfillmentsql = 'SELECT * FROM `general_settings_master` WHERE id=1';
												$getFullfilmentPrice= parent::selectTable_f_mdl($fullfillmentsql);
												$fullfilment_silver_price='0';
												$fullfilment_gold_price='0';
												$fullfilment_platinum_price='0';
												if(!empty($getFullfilmentPrice)){
													$fullfilment_silver_price   = $getFullfilmentPrice[0]['fullfilment_silver_price'];
													$fullfilment_gold_price   = $getFullfilmentPrice[0]['fullfilment_gold_price'];
													$fullfilment_platinum_price   = $getFullfilmentPrice[0]['fullfilment_platinum_price'];
												}
												$fullfilment_type_price = 0;
												if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=='SHIP_1_LOCATION_SORT'){
													$fullfilment_type_price = $fullfilment_gold_price;
												}else if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=='SHIP_EACH_FAMILY_HOME'){
													$fullfilment_type_price = $fullfilment_platinum_price;
												}else if(isset($store_master_data[0]['store_fulfillment_type']) && $store_master_data[0]['store_fulfillment_type']=='SHIP_1_LOCATION_NOT_SORT'){
													$fullfilment_type_price = $fullfilment_silver_price;
												}
												
												$sqlmaster_group = 'SELECT id,group_id from store_product_master where id="'.$master_proddata[0]['id'].'" AND is_deleted="0" ';
												$storeProductMasterGroup = parent::selectTable_f_mdl($sqlmaster_group);
												$group_id='';
												if(!empty($storeProductMasterGroup)){
													$group_id=$storeProductMasterGroup[0]['group_id'];
												}

												//To do add bussiness login for fullfilmemnt type & fundrising
												$ondemandPrice  = 0;
												$flashSalePrice = 0;
												if(isset($storeProductVariantMaster[0]['price']) && $storeProductVariantMaster[0]['price_on_demand'])
												{
													if($group_id=='9'){
														$ondemandPrice  = (floatval($storeProductVariantMaster[0]['price_on_demand']));
														$flashSalePrice = $storeProductVariantMaster[0]['price'];
													}else{
														$ondemandPrice  = (floatval($storeProductVariantMaster[0]['price_on_demand'])+$add_on_cost);
														$flashSalePrice = $storeProductVariantMaster[0]['price']+$add_cost+$fullfilment_type_price;
													}
												}
												else
												{
													$ondemandPrice  = $var_data['price_on_demand'];
													$flashSalePrice = $var_data['price'];
												}
												
												$sopvm_insert_data = [
													'store_owner_product_master_id'     => $sopm_id,
													'store_product_variant_master_id'   => $var_data['id'],
													'store_organization_type_master_id' => $var_data['store_organization_type_master_id'],
													'price'                             => $flashSalePrice,
													'price_on_demand'                   => $ondemandPrice,
													'fundraising_price'                 => $fund_amount,
													'color'     		                => $var_data['color'],
													'size'      		                => $var_data['size'],
													'image'                             => $var_data['image'],
													'original_image'                    => $var_data['feature_image'],
													'sku' 				                => $var_data['sku'],
													'weight' 			                => $var_data['weight'],
													'status' 			                => '1',
													'created_on' 		                => @date('Y-m-d H:i:s'),
													'created_on_ts' 	                => time()
												];
												parent::insertTable_f_mdl('store_owner_product_variant_master', $sopvm_insert_data);
											}
										}
									}
								}
							}
						}
					}
					$res['SUCCESS'] = 'TRUE';
					$res['MESSAGE'] = 'Store created successfully.';
					$res['token'] = $store_owner_details_master_token;
				}else{
					$res['SUCCESS'] = 'FALSE';
					$res['MESSAGE'] = 'Error while inserting store details. Please check and try again after some time.';
				}
			}else{
				$res['SUCCESS'] = 'FALSE';
				$res['MESSAGE'] = 'Error found in owner details. Please check and try again after some time.';
			}
		}
		// echo json_encode($res,1);
		common::sendJson($res);
	}

	public function getProductTemplateList(){
		$sql = 'SELECT id, template_name FROM `product_template_master` ORDER BY template_name ASC';
		return parent::selectTable_f_mdl($sql);
	}

	function get_all_template_products_colors(){
		global $s3Obj;
		global $login_user_email;
        $s3Obj = new Aws3;
		$res = [];

		if(!empty(parent::getVal("method")) && parent::getVal("method") == "get_all_template_products_colors"){
			
			$template_id= trim(parent::getVal('template_id'));
			$template_name= trim(parent::getVal('template_name'));
			$prodcolorFamily=$prodcolor='';
			// Retrieve product color data
			$sql = "SELECT spvm.id,spvm.color,spcm.product_color_name,scfm.color_family_name,scfm.color_family_color,scfm.color_image,scfm.id as color_mamily_id FROM product_templates_master_details as ptmd INNER JOIN store_product_master as spm ON spm.id=ptmd.store_product_master_id AND spm.is_deleted='0' INNER JOIN store_product_variant_master as spvm ON spvm.store_product_master_id=spm.id AND spvm.is_ver_deleted='0' LEFT JOIN store_product_colors_master as spcm ON spcm.product_color=spvm.color LEFT JOIN store_color_family_master as scfm ON scfm.color_family_name = spcm.color_family  WHERE ptmd.product_templates_master_id='".$template_id."' group by scfm.color_family_color order by scfm.color_family_color ";
	        $prodColorFamilyData = parent::selectTable_f_mdl($sql);
			if(!empty($prodColorFamilyData)){
				$prodcolorFamily .='<ul class="new_pro_family_list_ul">';
				foreach ($prodColorFamilyData as $single_colorfamily) {
					$clrfamilyName = $single_colorfamily['color_family_name'];
					$clrfamilycode = $single_colorfamily['color_family_color'];
					$color_mamily_id = $single_colorfamily['color_mamily_id'];
					if(!empty($clrfamilycode)){

						$sql = "SELECT spvm.id,spvm.color,spcm.product_color_name,spcm.id as product_color_id FROM product_templates_master_details as ptmd INNER JOIN store_product_master as spm ON spm.id=ptmd.store_product_master_id AND spm.is_deleted='0' INNER JOIN store_product_variant_master as spvm ON spvm.store_product_master_id=spm.id AND spvm.is_ver_deleted='0' LEFT JOIN store_product_colors_master as spcm ON spcm.product_color=spvm.color LEFT JOIN store_color_family_master as scfm ON scfm.color_family_name = spcm.color_family  WHERE ptmd.product_templates_master_id='".$template_id."' AND spcm.color_family='".$clrfamilyName."'  group by spcm.product_color_name order by spcm.product_color_name ";
		        		$prodColorData = parent::selectTable_f_mdl($sql);
						$prodcolor = '';
						if(!empty($prodColorData)){
							$prodcolor .= '<ul class="product-color-ul" id="colorList_'.$color_mamily_id.'" style="display:none;">';
							foreach ($prodColorData as $single_color) {
								$checkboxValue = $single_color['color'];
								$prodcolor .= '<li class="logo-prod-color-sec"><div>
									<input type="checkbox" id="logocolor_variantid_'.$single_color['id'].'" value="'.$checkboxValue.'" class="checkBoxClass logo_color_for colorfamily_'.$color_mamily_id.'" product_color_name="'.$single_color['product_color_name'].'" colorfamily_id="'.$color_mamily_id.'">
									<label for="logocolor_variantid_'.$single_color['id'].'" class="logocolor"> <span class="color_group_span" style="background-color:'.$checkboxValue.'">&nbsp;&nbsp;&nbsp;&nbsp;</span>'.$single_color['product_color_name'].'</label>
								</div></li>';
							}
							$prodcolor .= '</ul>';
						}else{
							$prodcolor .= '<div class="logo-prod-color-sec" style="color:red;">
								<label for="logocolor_variantid_0" class="logocolor">No product found in selected template</label>
							</div>';
						}


						if($clrfamilyName == 'Tie-Dye'){
							if(!empty($single_colorfamily['color_image'])){
								$prodcolorFamily .= '<li><div class="logo-prod-color-sec">
									<span class="toggleColors" data-target="#colorList_'.$color_mamily_id.'"><i class="fa fa-plus"></i></span>
									<input type="checkbox" id="logocolorfamily_variantid_'.$single_colorfamily['id'].'" value="'.$clrfamilycode.'" class="checkBoxClassfamily color_family_'.$color_mamily_id.'  temp-prod-color-family" product_color_family_name="'.$clrfamilyName.'" colorfamily_id="'.$color_mamily_id.'">
									<label for="logocolorfamily_variantid_'.$single_colorfamily['id'].'" class="logocolorfamily"><img style="width: 18px;height: 18px;border-radius: 50%;border: 1px solid transparent;position: relative;box-shadow: 0 0px 2px #000000;margin-left: 1px;margin-bottom:2px" src="'.$s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$single_colorfamily['color_image']).'"> <span class="color_family_group_span" style="margin-left: 4px;">&nbsp;&nbsp;&nbsp;&nbsp;</span>'.$clrfamilyName.'</label>
								</div>';
								$prodcolorFamily .=$prodcolor;
								$prodcolorFamily .='</li>';
							}
							else{
								$prodcolorFamily .= '<li><div class="logo-prod-color-sec">
									<span class="toggleColors" data-target="#colorList_'.$color_mamily_id.'"><i class="fa fa-plus"></i></span>
									<input type="checkbox" id="logocolorfamily_variantid_'.$single_colorfamily['id'].'" value="'.$clrfamilycode.'" class="checkBoxClassfamily color_family_'.$color_mamily_id.' temp-prod-color-family" product_color_family_name="'.$clrfamilyName.'" colorfamily_id="'.$color_mamily_id.'">
									<label for="logocolorfamily_variantid_'.$single_colorfamily['id'].'" class="logocolorfamily"> <span class="color_family_group_span" style="background-color:'.$clrfamilycode.'">&nbsp;&nbsp;&nbsp;&nbsp;</span>'.$clrfamilyName.'</label>
								</div>';
								$prodcolorFamily .=$prodcolor;
								$prodcolorFamily .='</li>';
							}
						}else{
							$prodcolorFamily .= '<li><div class="logo-prod-color-sec">
								<span class="toggleColors" data-target="#colorList_'.$color_mamily_id.'"><i class="fa fa-plus"></i></span>
								<input type="checkbox" id="logocolorfamily_variantid_'.$single_colorfamily['id'].'" value="'.$clrfamilycode.'" class="checkBoxClassfamily color_family_'.$color_mamily_id.' temp-prod-color-family" product_color_family_name="'.$clrfamilyName.'" colorfamily_id="'.$color_mamily_id.'">
								<label for="logocolorfamily_variantid_'.$single_colorfamily['id'].'" class="logocolorfamily"><span class="color_family_group_span" style="background-color:'.$clrfamilycode.'">&nbsp;&nbsp;&nbsp;&nbsp;</span>'.$clrfamilyName.' (Color Family)</label>
							</div>';
							$prodcolorFamily .=$prodcolor;
							$prodcolorFamily .='</li>';
						}
					}
				}
				$prodcolorFamily .='</ul">';
			}else{
				$prodcolorFamily .= '<div class="logo-prod-color-family-sec" style="color:red;">
							<label for="logocolorfamily_variantid_0" class="logocolorfamily">No product found in selected template</label>
						</div>';
			}

	        $res['product_colors'] = $prodcolorFamily;
			
		}
		// echo json_encode($res,1);
		common::sendJson($res);
	}

	public function getallStoreList(){
		$sql = 'SELECT sodm.id as store_owner_id,sodm.organization_name,sodm.first_name,sodm.last_name,sodm.email,sodm.phone,soam.address_line_1,soam.address_line_2,soam.country,soam.state,soam.city,soam.zip_code, sm.* FROM `store_master` as sm LEFT JOIN store_owner_details_master as sodm ON sodm.id=sm.store_owner_details_master_id LEFT JOIN store_owner_address_master as soam on soam.store_master_id=sm.id GROUP BY sodm.id ORDER BY sodm.organization_name ASC';
		return parent::selectTable_f_mdl($sql);
	}

}
?>