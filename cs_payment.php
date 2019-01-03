<?php
/**
* Plugin Name: Ninja Payment Processing 
* Plugin URI: http://codesolution.biz/
* Description: SSLCommerz Payment processing with ninja form.
* Version: 1.0
* Author: Code Solution
* Author URI: http://codesolution.biz/
**/

require_once 'SSLCommerz.php';




add_action('init', 'cs_ninja_sslcommerz_payment_process');
function cs_ninja_sslcommerz_payment_process() {
    session_start();
    define('BASE_URL', 'http://bsccm.test/');
	global $wpdb;
    if (stripos($_SERVER['REQUEST_URI'], 'payment-process') !== false) {
		$id = $_GET['id'];
		$post = $wpdb->get_results("SELECT post_id FROM $wpdb->postmeta WHERE meta_value = '". $id ."'");
        $info = get_metadata('post', $post[0]->post_id);//$wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id = '". $post[0]->post_id ."'");
        $paymentAmount = unserialize($info['calculations'][0]);
        $total = (int) filter_var($paymentAmount['TotalPay']['value'], FILTER_SANITIZE_NUMBER_INT);
		if(!empty($info)) {
			$sslCommerz = new SSLCommerz();
			$data['store_id'] = SSLCZ_STORE_ID;
			$data['store_passwd'] = SSLCZ_STORE_PASSWD;
			$data['total_amount'] = abs($total) / 100;
			$data['currency'] = $info[_field_70][0];;
			$data['tran_id'] = $post[0]->post_id;
			$data['cus_name'] = $info[_field_37][0] . ' ' . $info[_field_38][0];
			$data['cus_email'] = $info[_field_39][0];
			$data['cus_phone'] = $info[_field_40][0];
			$data['emi_option'] = 0;
			$data['fail_url'] = BASE_URL . 'payment-fail';
			$data['success_url'] = BASE_URL . 'payment-success';
            $data['cancel_url'] = BASE_URL . 'event-and-workshop-registration';
            unset($_SESSION['payment_info']);
            $_SESSION['payment_info'] = $data;

        	$sslCommerz->initiate($data);
		}		
	}
	
	else if (stripos($_SERVER['REQUEST_URI'], 'payment-success') !== false) {
        $tran_id = $_POST['tran_id'];
        $info = get_metadata('post', $tran_id);
        $status = $info['_field_69'][0];
        
        if($status == 'Pending' && array_key_exists('payment_info', $_SESSION)) {
            $sslCommerz = new SSLCommerz();
		    $amount = $_SESSION['payment_info']['total_amount'];
			$currency = $_SESSION['payment_info']['currency'];
            $validation = $sslCommerz->orderValidate($tran_id, $amount, $currency, $_POST);
            $tran_id = (string)$tran_id;

            if ($validation) {
                $payment = $wpdb->update( 
                    'wp_postmeta', 
                    array( 
                        'meta_value' => 'Success',
                    ), 
                    array( 
                        'post_id' => $tran_id , 
                        'meta_key' => '_field_69'
                    )
                );
                if ($payment == 1) {                    
                    $_SESSION['success'] = True;
                    return "Payment Record Updated Successfully";
                }
                elseif ($payment == FALSE) {
                    return "Error in Payment Record!!";
                }

            }
        }
        elseif ($status == 'Success') {
            return "Payment already done!!";
        }
        else {
            return "Something going wrong!!";
        }
	}

	else if (stripos($_SERVER['REQUEST_URI'], 'payment-fail') !== false) {
		$_SESSION['success'] = FALSE;
	}
}

function cs_payment_success()
{
    $output = '';
    if ($_SESSION['success'] && array_key_exists('payment_info', $_SESSION)) {
        $data = $_SESSION['payment_info'];
        $output .= "<div class='panel panel-default payment_success'>";
        $output .= "<div class='panel-heading'>CRITICON BANGLADESH 2019,Event and Workshop Registration</div>";
        $output .= "<div class='panel-body'><p>Your payment has been accepted successfully. Please bring this receipt in the conference.</p></div>";
        $output .= "<table class='table'>";
        $output .= '<tr><td>Name:</td><td>'.$data['cus_name'].'</td></tr>';
        $output .= '<tr><td>Mobile:</td><td>'.$data['cus_phone'].'</td></tr>';
        $output .= '<tr><td>Payment Date:</td><td>'.date('Y-m-d').'</td></tr>';
        $output .= '<tr><td>Transaction ID:</td><td>'.$data['tran_id'].'</td></tr>';
        $output .= '<tr><td>Payment Amount:</td><td>'.$data['total_amount'].' '.$data['currency'].'</td></tr>';
        $output .= '</table></div>';
    }
    elseif (!$_SESSION['success']) {
        $output .= '<h2>Event and Workshop Registration</h2>';
        $output .= '<h3>Something is wrong!! We are unable to accept your payment!!</h3><br>';
    } 
    //wp_mail($data['cus_email'], 'Event and Workshop Registration', $output . '<br><br> - BSCCM Team', array('Content-Type: text/html; charset=UTF-8'));
    return $output;
}

add_shortcode('cs_payment', 'cs_payment_success');

