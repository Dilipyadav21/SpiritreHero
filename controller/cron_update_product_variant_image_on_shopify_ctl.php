<?php
//http://app.spirithero.com/cron_update_product_variant_image_on_shopify.php
include_once 'model/cron_update_product_variant_image_on_shopify_mdl.php';
$path  = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include_once $path . '/libraries/Aws3.php';
$s3Obj = new Aws3;
/* Update product varient images */
class cron_update_product_variant_image_on_shopify_ctl extends cron_update_product_variant_image_on_shopify_mdl
{
	function __construct(){
		$this->updateProductVariantShopify();
	}
	
	public function updateProductVariantShopify(){
		global $path;
		global $s3Obj;
		$shop_data = parent::getShopCredentials_f_mdl(common::PARENT_STORE_NAME,true);
		if(!empty($shop_data)) {
			require_once( $path.'lib/class_graphql.php');
			$shop  = $shop_data[0]['shop_name'];
			$token = $shop_data[0]['token'];
			$headers = array(
				'X-Shopify-Access-Token' => $token
			);
			$graphql = new Graphql($shop, $headers);
		}

		if(isset($_GET['product_id']))
		{
			$sql = 'SELECT id, shop_product_id, shop_variant_id, `image` FROM `store_owner_product_variant_master` WHERE store_owner_product_master_id="'.$_GET['product_id'].'"';
			$Productvar_data = parent::selectTable_f_mdl($sql);
			if($Productvar_data){
				foreach ($Productvar_data as $key => $proValue) {
					$shop_product_id = $proValue['shop_product_id'];
					$shop_variant_id = $proValue['shop_variant_id'];
					$merged_image = $image = $proValue['image'];

					$gql_query = '{
						productVariant(id:"gid://shopify/ProductVariant/'.$shop_variant_id.'"){
						image{
							id
						}
						}
					}';
					$var_img_data = $graphql->runByQuery($gql_query);

					//add new image in main product
					$mutationData = 'mutation productAppendImages($input: ProductAppendImagesInput!) {
						productAppendImages(input: $input) {
						newImages {
							id
						}
						userErrors {
							field
							message
						}
						}
					}';
					//Task 59
					$inputData = '{
						"input": {
						"id": "gid://shopify/Product/'.$shop_product_id.'",
						"images": [
							{
							"src": "'.$s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.$merged_image).'"
							}
						]
						}
					}';
					sleep(0.4);
					$gqlNewImgData = $graphql->runByMutation($mutationData,$inputData);
					
					if(isset($gqlNewImgData['data']['productAppendImages']['newImages'][0]['id']) && !empty($gqlNewImgData['data']['productAppendImages']['newImages'][0]['id'])){
						$newImgId = $gqlNewImgData['data']['productAppendImages']['newImages'][0]['id'];

						//delete old image from variant
						if(isset($var_img_data['data']['productVariant']['image']['id']) && !empty($var_img_data['data']['productVariant']['image']['id'])){
							$ImgIdForDelete = $var_img_data['data']['productVariant']['image']['id'];
							$mutationData = 'mutation productDeleteImages($id: ID!, $imageIds: [ID!]!) {
								productDeleteImages(id: $id, imageIds: $imageIds) {
								deletedImageIds
								product {
									id
								}
								userErrors {
									field
									message
								}
								}
							}';
							$inputData = '{
								"id": "gid://shopify/Product/'.$shop_product_id.'",
								"imageIds": ["'.$ImgIdForDelete.'"]
							}';
							sleep(0.4);
							$graphql->runByMutation($mutationData,$inputData);
						}

						//assign new image in variant
						$mutationData = 'mutation productVariantUpdate($input: ProductVariantInput!) {
							productVariantUpdate(input: $input) {
							productVariant {
								id
							}
							userErrors {
								field
								message
							}
							}
						}';
						$inputData = '{
							"input": {
							"id": "gid://shopify/ProductVariant/'.$shop_variant_id.'",
							"imageId": "'.$newImgId.'"
							}
						}';
						sleep(0.4);
						$graphql->runByMutation($mutationData,$inputData);

					}

				}
			}
		}
	}
}
?>