<?php
include_once 'model/login_mdl.php';

class login_ctl extends login_mdl
{
	public $authURL = "";
	public $storeURL = "";
	public $isValidThemeProcess = true;
	
	function __construct(){
		$TempStore = $this->setSiteAccessKey();
		
		$this->loginPage = true;

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
	
	function CheckLoginRequest(){
		if (common::isGET()) {
			require_once('lib/shopify.php');
			
			if (common::getVal('code') != "") { 
				// if the code param has been sent to this page... we are in Step 2
				// Step 2: do a form POST to get the access token
				$shopifyClient = new ShopifyClient(common::getVal('shop'), "", common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
				unset($_SESSION["shop"]);
				unset($_SESSION["token"]);
				
				// Now, request the token and store it in your session.
				$_SESSION['token'] = $shopifyClient->getAccessToken(common::getVal('code'));
				
				if ($_SESSION['token'] != '') {
					$_SESSION['shop'] = strtolower(common::getVal('shop'));
					
					$this->shop_name = $_SESSION['shop'];
					$result = parent::getShopDetail_f_mdl();
					
					$shopifySiteAdmin = $_SESSION['shop'].'/admin';
					$shopifySiteUrlAppUrl = $_SESSION['shop'].common::APP_SUB_FOLDER;
					$shopifySiteUrlAppUrlPass = "";
					$isHTTP = false;
					if(strpos($shopifySiteUrlAppUrl, 'http://') !== false) {
						$isHTTP = true;
					}
					
					$shopifyWebSiteUrl = $_SESSION['shop'];
					if(count($result) > 0) {

						parent::updateShopDetail_f_mdl();

						#region - App Webhooks
						/*$this->addOrderCreateWebhook($_SESSION['token'], $_SESSION['shop']);
						$this->addProductAddWebhook($_SESSION['token'], $_SESSION['shop']);
						$this->addProductUpdateWebhook($_SESSION['token'], $_SESSION['shop']);
						$this->addProductDeleteWebhook($_SESSION['token'], $_SESSION['shop']);
						$this->addCustomerAddWebhook($_SESSION['token'], $_SESSION['shop']);
						$this->addCustomerUpdateWebhook($_SESSION['token'], $_SESSION['shop']);
						$this->addCustomerDeleteWebhook($_SESSION['token'], $_SESSION['shop']);*/
						#endregion
						
						if($isHTTP) {
							$shopifySiteUrlAppUrlPass = str_replace("http://", "https://", $shopifySiteUrlAppUrl);
						}
						else {
							$shopifySiteUrlAppUrlPass = "https://".$shopifySiteUrlAppUrl;
						}

						$this->updateStoreCurrency($_SESSION['token'], $_SESSION['shop']);
					}
				}
			}
			else if(common::getVal('shop') != "") {
				// Step 1: get the shopname from the user and redirect the user to the
				// shopify authorization page where they can choose to authorize this app
				
				$shop = common::getVal('shop');
				
				if(!(stristr($shop, '.myshopify.com'))) {
					$shop .= '.myshopify.com';
				}
				$shopifyClient = new ShopifyClient($shop, "", common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
				
				// get the URL to the current page
				$pageURL = 'http';

				if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") { $pageURL .= "s"; }

				$pageURL .= "://";
				if ($_SERVER["SERVER_PORT"] != "80") {
					$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].strtok($_SERVER["REQUEST_URI"],'?');
				} else {
					$pageURL .= $_SERVER["SERVER_NAME"].strtok($_SERVER["REQUEST_URI"],'?');
				}
				// redirect to authorize url
				/* header("Location: " . $shopifyClient->getAuthorizeUrl(common::SHOPIFY_SCOPE, $pageURL));
				exit; */
				
				$this->authURL = $shopifyClient->getAuthorizeUrl(common::SHOPIFY_SCOPE, $pageURL);
				$this->storeURL = $shop;
				
				if(strpos($this->authURL, 'https://') === false) {
					$this->authURL = str_replace('http://','https://',$this->authURL);
				}
				
				if(strpos($this->authURL, 'https%3A%2F%2F') === false) {
					$this->authURL = str_replace('http%3A%2F%2F','https%3A%2F%2F',$this->authURL);
				}
			}
		}
		// if they posted the form with the shop name
		else if (common::isPOST()) {
			/*echo "In post";
			exit;*/
			require_once('lib/shopify.php');
			
			if (common::getVal('shop') != "") {
				// Step 1: get the shopname from the user and redirect the user to the
				// shopify authorization page where they can choose to authorize this app
				$shop = (common::getVal('shop') != "") ? common::getVal('shop') : common::getVal('shop');
				if(!(stristr($shop, '.myshopify.com'))) {
					$shop .= '.myshopify.com';
				}
				$shopifyClient = new ShopifyClient($shop, "", common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			
				// get the URL to the current page
				$pageURL = 'http';
				if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") { $pageURL .= "s"; }
				$pageURL .= "://";
				if ($_SERVER["SERVER_PORT"] != "80") {
					$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].strtok($_SERVER["REQUEST_URI"],'?');
				} else {
					$pageURL .= $_SERVER["SERVER_NAME"].strtok($_SERVER["REQUEST_URI"],'?');
				}
				// redirect to authorize url
				/* header("Location: " . $shopifyClient->getAuthorizeUrl(common::SHOPIFY_SCOPE, $pageURL));
				exit; */
				$this->authURL = $shopifyClient->getAuthorizeUrl(common::SHOPIFY_SCOPE, $pageURL);
				$this->storeURL = $shop;
				
				if(strpos($this->authURL, 'https://') === false) {
					$this->authURL = str_replace('http://','https://',$this->authURL);
				}
				
				if(strpos($this->authURL, 'https%3A%2F%2F') === false) {
					$this->authURL = str_replace('http%3A%2F%2F','https%3A%2F%2F',$this->authURL);
				}
			}
		}
		
		if(common::isLogin())
		{
			if(isset($_SESSION['shop'])) {
				
				parent::updateShopDetail_f_mdl();
				
				$this->shop_name = $_SESSION['shop'];
				$result = parent::getShopDetail_f_mdl();
				
				$LoginSessionName = $this->setSiteAccessKey();
				$IndexShopName = $_SESSION['shop'];
				
				if(count($result) > 0) {
					$saveInfo = array();
					$saveInfo["SHOP_ID"] = $result[0]["id"];
					$saveInfo["TOKEN"] = $result[0]["token"];
					$saveInfo["SHOPIFY_LOGIN_URL"] = $result[0]["shopify_site_admin"];
					$saveInfo["SHOPIFY_SITE"] = $result[0]["shopify_site_admin"];
					$saveInfo["SHOPIFY_SITE_URL"] = $result[0]["shopify_site_url_app_url"];
					$saveInfo["SHOPIFY_WEB_SITE_URL"] = $result[0]["shopify_web_site_url"];
					$saveInfo["SITE_ACCESS_KEY"] = $result[0]["site_access_key"];
					$saveInfo["EMAIL"] = $result[0]["email"];
					$saveInfo["SHOP_REAL_NAME"] = $result[0]["shop_real_name"];

					common::setSession($LoginSessionName, json_encode($saveInfo));
				}
				header("location:".common::SITE_URL."?shop=".$IndexShopName);
				exit;
			}
			else {
				header("location:/");
				exit;
			}
		}
	}

	#region - Webhooks Methods
	function addOrderCreateWebHook($token, $shop){
		$this->shop_name = $shop;
		$ShopIdResult = parent::getShopDetail_f_mdl();

		$columnName = "order_create_webhook_id";

		require_once('lib/shopify.php');
		
		if(count($ShopIdResult) > 0) {
			$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			$allWebHook = $sc->call('GET', '/admin/webhooks.json');
			$allWebHookJson = json_encode($allWebHook);
			$allWebHookJson = json_decode($allWebHookJson);
			
			$isWebhookFound = false;
			foreach($allWebHookJson as $obj) 
			{
				if($obj->id == $ShopIdResult[0]["order_create_webhook_id"]) {
					$isWebhookFound = true;
				}
			}
			if(!$isWebhookFound) {
				$webhook_array = json_decode('{"webhook":{"topic":"orders/create","address":"'.common::SHOPIFY_ORDER_CREATE_WEBHOOK.'","format":"json"}}',true);
				
				$allWebHook = $sc->call('POST', '/admin/webhooks.json', $webhook_array);
				
				if($allWebHook["id"] != "") {
					$this->shop_name = $shop;
					$resultStatus = parent::updateShopStatus_f_mdl();					
					
					parent::updateWebhooksIds_f_mdl($ShopIdResult[0]["id"], $allWebHook["id"], $columnName);
				}
			}
		}
		else {
			$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			$webhook_array = json_decode('{"webhook":{"topic":"orders/create","address":"'.common::SHOPIFY_ORDER_CREATE_WEBHOOK.'","format":"json"}}',true);
			
			$allWebHook = $sc->call('POST', '/admin/webhooks.json', $webhook_array);
			
			if($allWebHook["id"] != "") {
				$this->shop_name = $shop;
				$resultStatus = parent::updateShopStatus_f_mdl();					
				
				parent::updateWebhooksIds_f_mdl($ShopIdResult[0]["id"], $allWebHook["id"], $columnName);
			}
		}
	}

	function addProductAddWebhook($token, $shop){
		$this->shop_name = $shop;
		$ShopIdResult = parent::getShopDetail_f_mdl();

		$columnName = "product_add_webhook_id";

		require_once('lib/shopify.php');
		
		if(count($ShopIdResult) > 0) {
			$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			$allWebHook = $sc->call('GET', '/admin/webhooks.json');
			$allWebHookJson = json_encode($allWebHook);
			$allWebHookJson = json_decode($allWebHookJson);
			
			$isWebhookFound = false;
			foreach($allWebHookJson as $obj) 
			{
				if($obj->id == $ShopIdResult[0]["product_add_webhook_id"]) {
					$isWebhookFound = true;
				}
			}
			if(!$isWebhookFound) {
				$webhook_array = json_decode('{"webhook":{"topic":"products/create","address":"'.common::SHOPIFY_PRODUCT_ADD_WEBHOOK.'","format":"json"}}',true);
				
				$allWebHook = $sc->call('POST', '/admin/webhooks.json', $webhook_array);
				
				if($allWebHook["id"] != "") {
					$this->shop_name = $shop;
					$resultStatus = parent::updateShopStatus_f_mdl();					
					
					parent::updateWebhooksIds_f_mdl($ShopIdResult[0]["id"], $allWebHook["id"], $columnName);
				}
			}
		}
		else {
			$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			$webhook_array = json_decode('{"webhook":{"topic":"products/create","address":"'.common::SHOPIFY_PRODUCT_ADD_WEBHOOK.'","format":"json"}}',true);
			
			$allWebHook = $sc->call('POST', '/admin/webhooks.json', $webhook_array);
			
			if($allWebHook["id"] != "") {
				$this->shop_name = $shop;
				$resultStatus = parent::updateShopStatus_f_mdl();					
				
				parent::updateWebhooksIds_f_mdl($ShopIdResult[0]["id"], $allWebHook["id"], $columnName);
			}
		}
	}

	function addProductUpdateWebhook($token, $shop){
		$this->shop_name = $shop;
		$ShopIdResult = parent::getShopDetail_f_mdl();

		$columnName = "product_update_webhook_id";

		require_once('lib/shopify.php');
		
		if(count($ShopIdResult) > 0) {
			$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			$allWebHook = $sc->call('GET', '/admin/webhooks.json');
			$allWebHookJson = json_encode($allWebHook);
			$allWebHookJson = json_decode($allWebHookJson);
			
			$isWebhookFound = false;
			foreach($allWebHookJson as $obj) 
			{
				if($obj->id == $ShopIdResult[0]["product_update_webhook_id"]) {
					$isWebhookFound = true;
				}
			}
			if(!$isWebhookFound) {
				$webhook_array = json_decode('{"webhook":{"topic":"products/update","address":"'.common::SHOPIFY_PRODUCT_UPDATE_WEBHOOK.'","format":"json"}}',true);
				
				$allWebHook = $sc->call('POST', '/admin/webhooks.json', $webhook_array);
				
				if($allWebHook["id"] != "") {
					$this->shop_name = $shop;
					$resultStatus = parent::updateShopStatus_f_mdl();					
					
					parent::updateWebhooksIds_f_mdl($ShopIdResult[0]["id"], $allWebHook["id"], $columnName);
				}
			}
		}
		else {
			$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			$webhook_array = json_decode('{"webhook":{"topic":"products/update","address":"'.common::SHOPIFY_PRODUCT_UPDATE_WEBHOOK.'","format":"json"}}',true);
			
			$allWebHook = $sc->call('POST', '/admin/webhooks.json', $webhook_array);
			
			if($allWebHook["id"] != "") {
				$this->shop_name = $shop;
				$resultStatus = parent::updateShopStatus_f_mdl();					
				
				parent::updateWebhooksIds_f_mdl($ShopIdResult[0]["id"], $allWebHook["id"], $columnName);
			}
		}
	}

	function addProductDeleteWebhook($token, $shop){
		$this->shop_name = $shop;
		$ShopIdResult = parent::getShopDetail_f_mdl();

		$columnName = "product_delete_webhook_id";

		require_once('lib/shopify.php');
		
		if(count($ShopIdResult) > 0) {
			$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			$allWebHook = $sc->call('GET', '/admin/webhooks.json');
			$allWebHookJson = json_encode($allWebHook);
			$allWebHookJson = json_decode($allWebHookJson);
			
			$isWebhookFound = false;
			foreach($allWebHookJson as $obj) 
			{
				if($obj->id == $ShopIdResult[0]["product_delete_webhook_id"]) {
					$isWebhookFound = true;
				}
			}
			if(!$isWebhookFound) {
				$webhook_array = json_decode('{"webhook":{"topic":"products/delete","address":"'.common::SHOPIFY_PRODUCT_DELETE_WEBHOOK.'","format":"json"}}',true);
				
				$allWebHook = $sc->call('POST', '/admin/webhooks.json', $webhook_array);
				
				if($allWebHook["id"] != "") {
					$this->shop_name = $shop;
					$resultStatus = parent::updateShopStatus_f_mdl();					
					
					parent::updateWebhooksIds_f_mdl($ShopIdResult[0]["id"], $allWebHook["id"], $columnName);
				}
			}
		}
		else {
			$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			$webhook_array = json_decode('{"webhook":{"topic":"products/delete","address":"'.common::SHOPIFY_PRODUCT_DELETE_WEBHOOK.'","format":"json"}}',true);
			
			$allWebHook = $sc->call('POST', '/admin/webhooks.json', $webhook_array);
			
			if($allWebHook["id"] != "") {
				$this->shop_name = $shop;
				$resultStatus = parent::updateShopStatus_f_mdl();					
				
				parent::updateWebhooksIds_f_mdl($ShopIdResult[0]["id"], $allWebHook["id"], $columnName);
			}
		}
	}

	function addCustomerAddWebhook($token, $shop){
		$this->shop_name = $shop;
		$ShopIdResult = parent::getShopDetail_f_mdl();

		$columnName = "customer_add_webhook_id";

		require_once('lib/shopify.php');
		
		if(count($ShopIdResult) > 0) {
			$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			$allWebHook = $sc->call('GET', '/admin/webhooks.json');
			$allWebHookJson = json_encode($allWebHook);
			$allWebHookJson = json_decode($allWebHookJson);
			
			$isWebhookFound = false;
			foreach($allWebHookJson as $obj) 
			{
				if($obj->id == $ShopIdResult[0]["customer_add_webhook_id"]) {
					$isWebhookFound = true;
				}
			}
			if(!$isWebhookFound) {
				$webhook_array = json_decode('{"webhook":{"topic":"customers/create","address":"'.common::SHOPIFY_CUSTOMER_ADD_WEBHOOK.'","format":"json"}}',true);
				
				$allWebHook = $sc->call('POST', '/admin/webhooks.json', $webhook_array);
				
				if($allWebHook["id"] != "") {
					$this->shop_name = $shop;
					$resultStatus = parent::updateShopStatus_f_mdl();					
					
					parent::updateWebhooksIds_f_mdl($ShopIdResult[0]["id"], $allWebHook["id"], $columnName);
				}
			}
		}
		else {
			$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			$webhook_array = json_decode('{"webhook":{"topic":"customers/create","address":"'.common::SHOPIFY_CUSTOMER_ADD_WEBHOOK.'","format":"json"}}',true);
			
			$allWebHook = $sc->call('POST', '/admin/webhooks.json', $webhook_array);
			
			if($allWebHook["id"] != "") {
				$this->shop_name = $shop;
				$resultStatus = parent::updateShopStatus_f_mdl();					
				
				parent::updateWebhooksIds_f_mdl($ShopIdResult[0]["id"], $allWebHook["id"], $columnName);
			}
		}
	}

	function addCustomerUpdateWebhook($token, $shop){
		$this->shop_name = $shop;
		$ShopIdResult = parent::getShopDetail_f_mdl();

		$columnName = "customer_update_webhook_id";

		require_once('lib/shopify.php');
		
		if(count($ShopIdResult) > 0) {
			$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			$allWebHook = $sc->call('GET', '/admin/webhooks.json');
			$allWebHookJson = json_encode($allWebHook);
			$allWebHookJson = json_decode($allWebHookJson);
			
			$isWebhookFound = false;
			foreach($allWebHookJson as $obj) 
			{
				if($obj->id == $ShopIdResult[0]["customer_update_webhook_id"]) {
					$isWebhookFound = true;
				}
			}
			if(!$isWebhookFound) {
				$webhook_array = json_decode('{"webhook":{"topic":"customers/update","address":"'.common::SHOPIFY_CUSTOMER_UPDATE_WEBHOOK.'","format":"json"}}',true);
				
				$allWebHook = $sc->call('POST', '/admin/webhooks.json', $webhook_array);
				
				if($allWebHook["id"] != "") {
					$this->shop_name = $shop;
					$resultStatus = parent::updateShopStatus_f_mdl();					
					
					parent::updateWebhooksIds_f_mdl($ShopIdResult[0]["id"], $allWebHook["id"], $columnName);
				}
			}
		}
		else {
			$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			$webhook_array = json_decode('{"webhook":{"topic":"customers/update","address":"'.common::SHOPIFY_CUSTOMER_UPDATE_WEBHOOK.'","format":"json"}}',true);
			
			$allWebHook = $sc->call('POST', '/admin/webhooks.json', $webhook_array);
			
			if($allWebHook["id"] != "") {
				$this->shop_name = $shop;
				$resultStatus = parent::updateShopStatus_f_mdl();					
				
				parent::updateWebhooksIds_f_mdl($ShopIdResult[0]["id"], $allWebHook["id"], $columnName);
			}
		}
	}

	function addCustomerDeleteWebhook($token, $shop){
		$this->shop_name = $shop;
		$ShopIdResult = parent::getShopDetail_f_mdl();

		$columnName = "customer_delete_webhook_id";

		require_once('lib/shopify.php');
		
		if(count($ShopIdResult) > 0) {
			$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			$allWebHook = $sc->call('GET', '/admin/webhooks.json');
			$allWebHookJson = json_encode($allWebHook);
			$allWebHookJson = json_decode($allWebHookJson);
			
			$isWebhookFound = false;
			foreach($allWebHookJson as $obj) 
			{
				if($obj->id == $ShopIdResult[0]["customer_delete_webhook_id"]) {
					$isWebhookFound = true;
				}
			}
			if(!$isWebhookFound) {
				$webhook_array = json_decode('{"webhook":{"topic":"customers/delete","address":"'.common::SHOPIFY_CUSTOMER_DELETE_WEBHOOK.'","format":"json"}}',true);
				
				$allWebHook = $sc->call('POST', '/admin/webhooks.json', $webhook_array);
				
				if($allWebHook["id"] != "") {
					$this->shop_name = $shop;
					$resultStatus = parent::updateShopStatus_f_mdl();					
					
					parent::updateWebhooksIds_f_mdl($ShopIdResult[0]["id"], $allWebHook["id"], $columnName);
				}
			}
		}
		else {
			$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);
			$webhook_array = json_decode('{"webhook":{"topic":"customers/delete","address":"'.common::SHOPIFY_CUSTOMER_DELETE_WEBHOOK.'","format":"json"}}',true);
			
			$allWebHook = $sc->call('POST', '/admin/webhooks.json', $webhook_array);
			
			if($allWebHook["id"] != "") {
				$this->shop_name = $shop;
				$resultStatus = parent::updateShopStatus_f_mdl();					
				
				parent::updateWebhooksIds_f_mdl($ShopIdResult[0]["id"], $allWebHook["id"], $columnName);
			}
		}
	}
	#endregion

	#region - Other Operational Methods
	function updateStoreCurrency($token, $shop){
		$this->shop_name = $shop;
		$result = parent::getShopDetail_f_mdl();
		
		if(count($result) > 0) {
			$sc = new ShopifyClient($shop, $token, common::SHOPIFY_API_KEY, common::SHOPIFY_SECRET);

			$shopDetails = $sc->call('GET', '/admin/shop.json');

			$shopDetailsJson = json_encode($shopDetails);
			$shopDetailsJsonNew = json_decode($shopDetailsJson);
			
			if($shopDetailsJsonNew->id != "") {
				parent::updateStoreCurrency_f_mdl($shopDetailsJsonNew->name, $shopDetailsJsonNew->email, $shopDetailsJsonNew->customer_email, $shopDetailsJsonNew->currency, $shopDetailsJsonNew->money_format, $shopDetailsJsonNew->money_with_currency_format,  $shopDetailsJsonNew->timezone, $shopDetailsJsonNew->iana_timezone, $shopDetailsJsonNew->phone, $result[0]["id"]);
			}
		}
	}
	#endregion
}
?>