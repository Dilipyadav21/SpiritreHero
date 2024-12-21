<?php
use PHPShopify\Product;

include_once 'model/weekly_report_to_superadmin_mdl.php';
include_once $path . '/libraries/Aws3.php';
$s3Obj = new Aws3;
class weekly_report_to_superadmin_ctl extends weekly_report_to_superadmin_mdl
{
    function __construct()
    {
        $this->send_weekly_report_mail();
    }

    function send_weekly_report_mail()
    {   
        global $s3Obj;
        require_once(common::EMAIL_REQUIRE_URL);
        if (strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp') !== false) {
            $objAWS = new aws_ses_smtp();
        } else if (strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail') !== false) {
            $objAWS = new sendGridEmail();
        } else {
            $objAWS = new Aws(common::AWS_ACCESS_KEY, common::AWS_SECRET_KEY, common::AWS_REGION);
        }

        $storeList                   =  parent::getStoreList_f_mdl();
        if (!empty($storeList)) {
            $flashSaleData = array();
            $onDemandData  = array();
            foreach ($storeList as $store_data) {
                $store_master_id           = $store_data['id'];
                $store_name                = $store_data['store_name'];
                $store_sale_type_master_id = $store_data['store_sale_type_master_id'];

                $created_on = '';
                if (!empty($store_data['created_on'])) {
                    $splitDate                 = explode(" ",$store_data['created_on']);
                    $created_on                = $splitDate[0];
                }
                else{
                    $created_on                = date('y-m-d');
                }
                

                $cond_start_date = ' AND om.created_on_ts>="'.strtotime($created_on.' 0:0').'"';
                $cond_end_date = ' AND om.created_on_ts<="'.strtotime(date('y-m-d').' 23:59').'"';

                $sql = 'SELECT IFNULL(SUM(oim.quantity),0) as totalQantity FROM `store_orders_master` as om INNER JOIN `store_order_items_master` as oim on om.id = oim.store_orders_master_id WHERE 1 AND om.is_order_cancel = 0 AND oim.is_deleted = 0 and oim.store_master_id = '.$store_master_id.'
                '.$cond_start_date.'
                '.$cond_end_date.'
                ';

                $qtyData = parent::selectTable_f_mdl($sql);
                $totalQantity = $qtyData[0]['totalQantity'];
             
                $sql = 'SELECT subject,body,recipients FROM `email_templates_master` WHERE id=16';
                $et_data = parent::selectTable_f_mdl($sql);

                $sql = 'SELECT * FROM store_master WHERE id="'.$store_master_id.'"';
                $store_data = parent::selectTable_f_mdl($sql);

                $store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
                $store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;
                
                $logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
                $logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
                $body ='';
                $subject ='';
                $to_email ='';
                
                if (!empty($et_data)) {
                    $body    = $et_data[0]['body'];
                    $subject = $et_data[0]['subject'];
                    $ccMails = '';
                    if($et_data[0]['recipients']){
                        $recipients = $et_data[0]['recipients'];
                        $recipients = str_replace(' ', '', $recipients);
                        $ccMails    = explode(',', $recipients);
                    }
                }    

                $body       = str_replace('{{STORE_NAME}}', $store_name, $body);
                $body       = str_replace('{{SOLD_ITEMS}}', $totalQantity, $body);
                $body 		= str_replace('{{SPIRITHERO_LOGO}}', $logo, $body);
                $body       = str_replace('{{STORE_OPEN_DATE}}', $store_open_date, $body);
                $body       = str_replace('{{STORE_LAST_DATE}}', $store_last_date, $body);

 
                if ($store_sale_type_master_id == 1 ) {
                    $flashSaleData[] = $body;
                }else{
                    $onDemandData[]  = $body;
                }
            }

            $htmlValues1='';
            foreach ($flashSaleData as $value1) {
                $htmlValues1.=$value1;
            }

            $htmlValues2='';
            foreach ($onDemandData as $value2) {
                $htmlValues2.=$value2;
            }

            
            $html ='
            <b>#FALSH SALES STORES:</b><br><br>
            '.$htmlValues1.'
            <br>
            <b>#ON-DEMAND STORES:</b><br><br>
            '.$htmlValues2.'';

            $to_email = common::SUPER_ADMIN_EMAIL;
            $mailSendStatus = $objAWS->sendEmail([$to_email], $subject, $html, $html,$ccMails); 
        }
    }
}
