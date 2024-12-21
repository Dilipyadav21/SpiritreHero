<?php
// https://app.spirithero.com/cron-send-reminder-email-before-store-close.php
include_once 'model/cron_send_reminder_email_before_store_close_mdl.php';
include_once $path . '/libraries/Aws3.php';
class cron_send_reminder_email_before_store_close_ctl extends cron_send_reminder_email_before_store_close_mdl
{
    function __construct(){
        $this->send_reminder_email();
    }

    function send_reminder_email(){
        $s3Obj = new Aws3;
        require_once(common::EMAIL_REQUIRE_URL);
        //Check email only testing purpose
        /*$objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
        $mailSendStatus = $objAWS->sendEmail(["sanjay@bitcot.com"], "send_reminder_email", "Message: send_reminder_email", "Message: send_reminder_email");*/
        //end Check email..

        $second_of_3_days = 3*24*3600;
        $sql = 'SELECT store_master.id,store_name,(store_close_date-'.time().') as diff_with_close_date, first_name, email,store_owner_details_master.id as store_owner_id,store_master.verification_status
        FROM store_master
        LEFT JOIN store_owner_details_master ON store_owner_details_master.id = store_master.store_owner_details_master_id
        WHERE is_store_close_reminder_email_sent="0" AND store_close_date!=""
        HAVING diff_with_close_date < '.$second_of_3_days.' AND diff_with_close_date > 0
        ;';

        $store_data = parent::selectTable_f_mdl($sql);
        if(!empty($store_data)){
            foreach($store_data as $single_store){
                if(strpos(common::EMAIL_REQUIRE_URL, 'aws_ses_smtp')!==false){
                    $objAWS = new aws_ses_smtp();
                }else if(strpos(common::EMAIL_REQUIRE_URL, 'sendGridEmail')!==false){
                    $objAWS = new sendGridEmail();
                }else{
                    $objAWS = new Aws(common::AWS_ACCESS_KEY,common::AWS_SECRET_KEY,common::AWS_REGION);
                }

                $sql = 'SELECT id,title,subject,body,variables FROM `email_templates_master` WHERE id = "'.common::EMAIL_TO_CUSTOMER_ADMIN_FOR_REMINDER_OF_STORE_CLOSE.'"';
                $emailData = parent::selectTable_f_mdl($sql);
                $logo_image = $s3Obj->getAwsUrl(common::IMAGE_UPLOAD_S3_PATH.'email-logo.png');
                $logo ='<img class="navbar-brand-logo" src="'.$logo_image.'">';
                $subject = $emailData[0]['subject'];
                $to_email = $single_store['email'];
                $from_email = common::AWS_ADMIN_EMAIL;
                $attachment = [];

                $store_link = common::CUSTOMER_PORTAL_URL.'?do=store-edit&id='.$single_store['id'];

                //$objAWS::sendEmail($from_email, $to_email, $subject, str_replace(["{{FIRST_NAME}}","{{STORE_LINK}}"],[$single_store['first_name'],$store_link],$emailData[0]['body']), $attachment);

                $sql = 'SELECT * FROM store_master WHERE id="'.$single_store['id'].'"';
                $store_data = parent::selectTable_f_mdl($sql);

                $store_open_date=!empty($store_data[0]["store_open_date"]) ? date('m/d/Y', $store_data[0]["store_open_date"]) : '' ;
                $store_last_date=!empty($store_data[0]["store_close_date"]) ? date('m/d/Y', $store_data[0]["store_close_date"]) : '' ;



                if($single_store['verification_status'] == 1){

                    //if($store_data[0]['email_notification'] == '1'){
                        $objAWS->sendEmail([$to_email], $subject, str_replace(["{{FIRST_NAME}}","{{STORE_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$single_store['first_name'],$store_link,$logo,$store_open_date,$store_last_date],$emailData[0]['body']), str_replace(["{{FIRST_NAME}}","{{STORE_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$single_store['first_name'],$store_link,$logo,$store_open_date,$store_last_date],$emailData[0]['body']));
                    //}
                    /*send mail store manager */
                    $store_owner_details_master_id=$single_store['store_owner_id'];
                    $sql_managerData = 'SELECT email,first_name FROM `store_manager_master` WHERE status="0" AND store_owner_id="' . $store_owner_details_master_id . '"';
                    $smm_data =  parent::selectTable_f_mdl($sql_managerData);
                    if(!empty($smm_data)){
                        foreach ($smm_data as $managerData) {
                            $to_email   = $managerData['email'];
                            
                            $objAWS->sendEmail([$to_email], $subject, str_replace(["{{FIRST_NAME}}","{{STORE_NAME}}","{{STORE_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$managerData['first_name'],$store_data[0]['store_name'],$store_link,$logo,$store_open_date,$store_last_date],$emailData[0]['body']), str_replace(["{{FIRST_NAME}}","{{STORE_NAME}}","{{STORE_LINK}}","{{SPIRITHERO_LOGO}}","{{STORE_OPEN_DATE}}","{{STORE_LAST_DATE}}"],[$managerData['first_name'],$store_data[0]['store_name'],$store_link,$logo,$store_open_date,$store_last_date],$emailData[0]['body']));
                        }
                    }
                    /*send mail store manager */
                }
                parent::updateTable_f_mdl('store_master',['is_store_close_reminder_email_sent'=>'1'],'id="'.$single_store['id'].'"');
            }
        }
    }


}
?>