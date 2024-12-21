<?php
include_once 'model/index_mdl.php';

class index_ctl extends index_mdl
{
	public $TempSession = "";

	function index_ctl(){
		$this->TempSession = $this->setSiteAccessKey();
		
		common::CheckLoginSession();	
	}
	
	function setSiteAccessKey(){
		
		$ReturnSessionName = "";

		#region - GET STORE ACCESS KEY
		if(parent::isGET() || parent::isPOST()){
			if(parent::getVal("shop") != ""){
				$this->shop_name = parent::getVal("shop");
				$StoreDetail = parent::getShopDetail_f_mdl();
				
				if(count($StoreDetail) > 0){
					$ReturnSessionName = common::STORE_LOGIN_SESSION_FIRST_KEY.$StoreDetail[0]["site_access_key"];
					$this->SITE_ACCESS_KEY = $StoreDetail[0]["site_access_key"];
				}
			}
		}
		#endregion
		
		return $ReturnSessionName;
	}

	function redirect_to_dashboard()
	{
		if(!empty($_SESSION['role_id']) && !empty($_SESSION['user_id']))
		{
			if($_SESSION['role_id'] == 1){
				header("location: ".common::SITE_URL."sa-stores.php?stkn=");
			}else{
				header("location: ".common::SITE_URL."sa-vendor-orders.php?stkn=");
			}
		} else { 
			header("location: ".common::SITE_URL."superadmin-login.php?stkn=".$this->SITE_ACCESS_KEY);
		}
		exit;
	}

	public function loginCheck()
	{	
		$res = [];
		if(parent::isPOST()){
			if (!empty(parent::getVal("method")) && parent::getVal("method") == "loginCheck") {
				if(isset($_SESSION['token']) && isset($_SESSION['shop'])){
					$this->updateStoreData($_SESSION['token'], $_SESSION['shop']);
					unset($_SESSION['token']);
					unset($_SESSION['shop']);
				}
				
				$signin_email    = parent::getVal("signin_email");
				$signin_password = md5(trim(parent::getVal("signin_password")));
				$stkn = parent::getVal("stkn");

				$sql = 'SELECT * FROM users WHERE email="'.$signin_email.'" AND password ="'.$signin_password.'" ';
				$store_data = parent::selectTable_f_mdl($sql);

				$store_email = 'SELECT * FROM users WHERE email="'.$signin_email.'" ';
				$store_email_data = parent::selectTable_f_mdl($store_email);
				if(empty($store_email_data)){
					$res['siteUrl'] = "";
					$res['status']  = false;
					$res['message']  = "We are sorry but there is no account with this email address in our system. Please try a different email address or contact Spirit Hero for assistance.";
					echo json_encode($res);die();	
				}
				if(isset($store_data[0]['role_id']) && $store_data[0]['role_id']=='1'){
					$sql1 = 'SELECT * FROM users WHERE email="'.$signin_email.'" AND password ="'.$signin_password.'" AND status ="1" ';
					$users = parent::selectTable_f_mdl($sql1);
					if(!empty($users)){
						if ($users[0]['email'] === $signin_email && $users[0]['password'] === $signin_password) {
							$_SESSION['role_id'] = $store_data[0]['role_id'];
							$_SESSION['user_id'] = $store_data[0]['id'];
							$_SESSION['user_name'] = $store_data[0]['first_name'];
							$_SESSION['user_email'] = $store_data[0]['email'];
							$_SESSION['stkn']    = $stkn;
							$siteUrl = '';
							if($store_data[0]['role_id'] == 1){
								$siteUrl = common::SITE_URL."sa-stores.php?stkn=".$this->SITE_ACCESS_KEY;
							}
							$res['siteUrl'] = $siteUrl;
							$res['status']  = true;
							$res['message']  = "You have loggedin successfully";
						}else{
							$res['siteUrl'] = "";
							$res['status']  = false;
							$res['message']  = "Incorrect email and/or password";
						}

					}else{
						$res['siteUrl'] = "";
						$res['status']  = false;
						$res['message']  = "We are sorry but there is no account with this email address in our system. Please try a different email address or contact Spirit Hero for assistance.";
					}
				}else if (count($store_data) > 0) {
					$vendor_id = $store_data[0]['vendor_id'];
					$vndrSql = 'SELECT `status` FROM store_vendors_master WHERE id="'.$vendor_id.'"';
					$vendorData = parent::selectTable_f_mdl($vndrSql);
					$status = 1;
					if($vendor_id > 0){
						$status = 0;
						if(!empty($vendorData)){
							$status = $vendorData[0]['status'];
						}
					}
					if($status == 1){
						if ($store_data[0]['email'] === $signin_email && $store_data[0]['password'] === $signin_password) {
							$_SESSION['role_id'] = $store_data[0]['role_id'];
							$_SESSION['user_id'] = $store_data[0]['id'];
							$_SESSION['user_name'] = $store_data[0]['first_name'];
							$_SESSION['user_email'] = $store_data[0]['email'];
							$_SESSION['stkn']    = $stkn;
							$siteUrl = '';
							if($store_data[0]['role_id'] == 1){
								$siteUrl = common::SITE_URL."sa-stores.php?stkn=".$this->SITE_ACCESS_KEY;
							}
							else{
								$_SESSION['vendor_id'] = $store_data[0]['vendor_id'];
								$siteUrl = common::SITE_URL."sa-vendor-orders.php?stkn=".$this->SITE_ACCESS_KEY;
							}
							$res['siteUrl'] = $siteUrl;
							$res['status']  = true;
							$res['message']  = "You have loggedin successfully";
						}else{
							$res['siteUrl'] = "";
							$res['status']  = false;
							$res['message']  = "Incorrect email and/or password";
						}
					}else{
						$res['siteUrl'] = "";
						$res['status']  = false;
						$res['message']  = "We are sorry but there is no account with this email address in our system. Please try a different email address or contact Spirit Hero for assistance.";
					}	
				}else{
	               $res['siteUrl'] = "";
	               $res['status']  = false;
	               $res['message']  = "Incorrect email and/or password";
	            }
			}
			echo json_encode($res);die();	
		}	
	}

	public function changePassword(){
		if(parent::isPOST()){
			$res = [];
			if(parent::getVal("method") == "changePassword")
			{	
				$login_user_id = $_SESSION['user_id'];
				$changePasswordData = [
					'password' => md5(trim(parent::getVal("password")))
				];
				parent::updateTable_f_mdl('users',$changePasswordData,'id="'.$login_user_id.'"');
				$res['SUCCESS'] = true;
				$res['MESSAGE'] = 'Password changed successfully.';
			}else{
				$res['SUCCESS'] = false;
				$res['MESSAGE'] = '!Something went wrong.';
			}
			echo json_encode($res);die();
		}
	}

	public function updateStoreData($token, $shop){
		require_once('lib/shopify.php');
		$this->shop_name = $shop;
		$result = parent::getShopDetail_f_mdl();
		if(empty($result)) {
			$sql = "SELECT * FROM `shop_management` LIMIT 1";
			$result = parent::selectTable_f_mdl($sql);
		}
		if(!empty($result)) {
			$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			$shopDetails = $sc->call('GET', '/admin/shop.json');

			$shopDetailsJson = json_encode($shopDetails);
			$shopDetailsJsonNew = json_decode($shopDetailsJson);
			
			if($shopDetailsJsonNew->id != "") {
				parent::updateShopDetails_f_mdl($shopDetailsJsonNew->name, $shopDetailsJsonNew->email, $shopDetailsJsonNew->customer_email, $shopDetailsJsonNew->currency, $shopDetailsJsonNew->money_format, $shopDetailsJsonNew->money_with_currency_format,  $shopDetailsJsonNew->timezone, $shopDetailsJsonNew->iana_timezone, $shopDetailsJsonNew->phone, $result[0]["id"]);
			}
		}
	}
}
?>