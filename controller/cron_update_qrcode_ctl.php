<?php 
include_once 'model/index_mdl.php';
$path  = preg_replace('/controller(?!.*controller).*/','',__DIR__);
include $path.'/lib/generate_qr_code.php';
$qrcodeObj = new generate_qr_code();

class cron_update_qrcode_ctl extends index_mdl{
    public function updateQrcode($store_master_id)
    {
        global $qrcodeObj;
        $sql = 'SELECT id,store_name,shop_collection_handle FROM store_master WHERE id='.$store_master_id.'';
		$list_data = parent::selectTable_f_mdl($sql);
        if(!empty($list_data)){
            $storeMasterId = $list_data[0]['id'];
            $storeName = $list_data[0]['store_name'];
            $shopCollectionHandle = $list_data[0]['shop_collection_handle'];
            if(!empty($shopCollectionHandle)){
                $qrcodeObj->generateQrCode($storeName,$shopCollectionHandle,$storeMasterId);
            }
        }
    }
}
?>