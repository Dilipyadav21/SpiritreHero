<?php
include_once 'model/index_mdl.php';

class forgot_password_ctl extends index_mdl
{
	function __construct(){
		if(isset($_REQUEST['action'])){
			$action = $_REQUEST['action'];
			if($action=='customer_forgot_pass'){
				$this->customer_forgot_pass();exit;
			}else if($action=='set_new_pass'){
				$this->set_new_pass();exit;
			}else if($action=='resend_otp'){
				$this->resend_otp();exit;
			}

		}
	}

	public function customer_forgot_pass(){
		if(isset($_POST['signin_email']) && !empty($_POST['signin_email'])){
			$sql = 'SELECT * FROM `users`
					WHERE email="'.$_POST['signin_email'].'"';
			$login_user_data = parent::selectTable_f_mdl($sql);

			$sql = 'SELECT * FROM `store_owner_otp_master`
					WHERE status=1
					AND email="'.$_POST['signin_email'].'"';
			$otp_data = parent::selectTable_f_mdl($sql);

			$emailResp = 0;
			$email_otp = '';

			$isEmailValid = false;
			if(!empty($login_user_data)){
				$isEmailValid = true;
			}

			if($isEmailValid){
				if(!empty($otp_data)){
					$email_otp = $otp_data[0]['otp'];
				}else{
					$otp_from_DB = $this->generate_otp_and_insert_intoDB($_POST['signin_email']);
					$email_otp = $otp_from_DB;
				}
				$subject = 'Forgot Password Instructions';
				$url = "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']."?otp=".$email_otp;
				$content = '
							<tr>
                                <td style="padding: 10px; text-align: left; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">
									You are just one step away from changing your password. Please click on the below link Reset Password to set your new password. If you have any questions, please email support@spirithero.com or contact your sales rep.
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 10px; text-align: left; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">
                                    <h1><a href="'.$url.'">Reset Password</a></h1>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 10px; text-align: left; font-family: sans-serif; font-size: 15px; mso-height-rule: exactly; line-height: 20px; color: #555555;">
                                    <p>Best,</p>
                                    <p>Spirit Hero</p>
                                    <p>800-239-9948</p>
                                </td>
                            </tr>';
				$emailResp = parent::sendOtpForForgotPassword($_POST['signin_email'],$subject,$content);

				if($emailResp){
					$_SESSION['SUCCESS'] = 'TRUE';					
					$_SESSION['FORGOT_EMAIL'] = $_POST['signin_email'];
					// $_SESSION['MESSAGE'] = 'Please check your mail. We have sent you the reset password link in your mail.';
					header('location:forgot-password.php?msg=msg');
				}
			}else{
				$_SESSION['SUCCESS'] = 'FALSE';
				$_SESSION['MESSAGE'] = 'Email is wrong.';
				unset($_SESSION['FORGOT_EMAIL']);
				header('location:forgot-password.php');
			}
		}else{
			$_SESSION['SUCCESS'] = 'FALSE';
			$_SESSION['MESSAGE'] = 'Invalid request.';
			unset($_SESSION['FORGOT_EMAIL']);
			header('location:forgot-password.php');
		}
	}
	
	public function set_new_pass(){
		if(isset($_POST['email_otp']) && !empty($_POST['email_otp']) && isset($_SESSION['FORGOT_EMAIL']) && !empty($_SESSION['FORGOT_EMAIL']) && isset($_POST['new_password']) && !empty($_POST['new_password'])){
			$sql = 'SELECT * FROM `store_owner_otp_master`
					WHERE status=1
					AND email="'.$_SESSION['FORGOT_EMAIL'].'"
					AND otp="'.$_POST['email_otp'].'"';
			$otp_data = parent::selectTable_f_mdl($sql);

			if(!empty($otp_data)){
				$new_pass = $_POST['new_password'];

				$sql = 'SELECT * FROM `users`
						WHERE email="'.$_SESSION['FORGOT_EMAIL'].'"';
				$login_user_data = parent::selectTable_f_mdl($sql);

				if(!empty($login_user_data)){
					$update_data = [
						'password' => md5($new_pass),
					];
					parent::updateTable_f_mdl('users',$update_data,'email="'.$_SESSION['FORGOT_EMAIL'].'"');

					$update_data = [
						'status' => '0',
					];
					parent::updateTable_f_mdl('store_owner_otp_master',$update_data,'email="'.$_SESSION['FORGOT_EMAIL'].'"');

					$_SESSION['SUCCESS'] = 'TRUE';
					$_SESSION['MESSAGE'] = 'Password updated successfully.';
					unset($_SESSION['FORGOT_EMAIL']);
					header("location: ".common::SITE_URL."superadmin-login.php?stkn=");
				}
			}else{
				$_SESSION['SUCCESS'] = 'FALSE';
				$_SESSION['MESSAGE'] = 'OTP is wrong.';
				header('location:forgot-password.php');
			}
		}else{
			$_SESSION['SUCCESS'] = 'FALSE';
			$_SESSION['MESSAGE'] = 'Invalid request.';
			unset($_SESSION['FORGOT_EMAIL']);
			header('location:forgot-password.php');
		}
	}
	
	public function resend_otp(){
		if(isset($_POST['signin_email']) && !empty($_POST['signin_email']) && isset($_SESSION['FORGOT_EMAIL']) && !empty($_SESSION['FORGOT_EMAIL'])){

			$this->customer_forgot_pass();
		}else{
			$_SESSION['SUCCESS'] = 'FALSE';
			$_SESSION['MESSAGE'] = 'Invalid request.';
			unset($_SESSION['FORGOT_EMAIL']);
			header('location:forgot-password.php');
		}
	}

	public function generate_otp_and_insert_intoDB($email){
		$otp = rand(100000,999999);
		$soom_insert_data = [
			'email' => trim($email),
			'otp' => trim($otp),
			'status' => '1',
			'add_date' => time(),
		];
		parent::insertTable_f_mdl('store_owner_otp_master',$soom_insert_data);

		return $otp;
	}
}