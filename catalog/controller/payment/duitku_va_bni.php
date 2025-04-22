<?php

require_once(DIR_SYSTEM . 'library/duitku-php/Duitku.php');

class ControllerPaymentDuitkuVABni extends Controller {

  public function index() {

    $data['errors'] = array();
    $data['button_confirm'] = $this->language->get('button_confirm');
    
    $data['text_loading'] = $this->language->get('text_loading');

    $data['process_order'] = 'payment/duitku_va_bni/process_order';

    if(version_compare(VERSION, '2.2.0.0') < 0) {
      // CODE HERE IF LOWER
      if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/duitku.tpl')) {
        return $this->load->view($this->config->get('config_template') . '/template/payment/duitku.tpl', $data);
      } else {
        return $this->load->view('default/template/payment/duitku.tpl', $data);
      }
    } else {
      // CODE HERE IF HIGHER OR EQUAL
      return $this->load->view('payment/duitku', $data);
    }  

  }

  /**
   * Called when a customer checkouts.
   * If it runs successfully, it will redirect to Duitku payment page.
   */
  public function process_order() {    
    $this->load->model('payment/duitku_va_bni');
    $this->load->model('checkout/order');
    $this->load->model('total/shipping');
    $this->load->language('payment/duitku_va_bni');

    $data['errors'] = array();

    $data['button_confirm'] = $this->language->get('button_confirm');

    $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

    //generate Signature
    $merchant_code = $this->config->get('duitku_va_bni_merchant');
    $api_key = $this->config->get('duitku_va_bni_api_key');
    $order_id = $this->session->data['order_id'];
    $def_curr = $this->config->get('config_currency');
    $order_total = $def_curr == 'IDR' ? $order_info['total'] : $this->currency->convert($order_info['total'], $def_curr, 'IDR');
    $signature = md5($merchant_code . $order_id . intval($order_total) . $api_key);    

    // Prepare Parameters
    $params = array(
          'merchantCode' => $merchant_code, // API Key Merchant /
          'paymentAmount' => intval($order_total), //transform order into integer
          'paymentMethod' => "I1",
          'merchantOrderId' => $order_id,
          'productDetails' => $this->config->get('config_name') . ' Order : #' . $order_id,
          'additionalParam' => $order_info['payment_firstname'] . " " . $order_info['payment_lastname'],
          'merchantUserInfo' => $order_info['email'],
		  'email' => $order_info['email'],
          'signature' => $signature,          
          'returnUrl' => $this->url->link('payment/duitku_va_bni/landing_redir'),
          'callbackUrl' => $this->url->link('payment/duitku_va_bni/payment_notification'),
    );         

	//for va cart is automatically clear before redirection
	//$this->cart->clear();
	
    try {     	  
      $redirUrl = Duitku_VtWeb::getRedirectionUrl($this->config->get('duitku_va_bni_endpoint'), $params);
      $this->response->setOutput($redirUrl);	  
    }
    catch (Exception $e) {
      $data['errors'][] = $e->getMessage();
      error_log($e->getMessage());
      echo $e->getMessage();
    }
  }

  /**
   * Landing page when payment is finished or failure or customer pressed "back" button
   * The Cart is cleared here, so make sure customer reach this page to ensure the cart is emptied when payment succeed
   * payment finish/unfinish/error url :
   * http://[your shop’s homepage]/index.php?route=payment/veritrans/payment_notification
   */
  public function landing_redir() {    
    $this->load->model('checkout/order');
    $this->load->model('payment/duitku_va_bni');    
    $redirUrl = $this->url->link('checkout/cart');

    if (isset($_GET['resultCode']) && isset($_GET['merchantOrderId']) && isset($_GET['reference']) && $_GET['resultCode'] == '01') {
      //if capture or pending or challenge or settlement, redirect to order received page
      /* $this->cart->clear();
      $redirUrl = $this->url->link('checkout/success&');
      $this->response->redirect($redirUrl); */
	  
	  $order_id = stripslashes($_GET['merchantOrderId']);
	  $this->cart->clear();
	  $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('duitku_va_bni_pending_mapping'), 'Duitku payment pending.');
      $redirUrl = $this->url->link('payment/duitku_va_bni/failure');
      $this->response->redirect($redirUrl);

    }else if( isset($_GET['resultCode']) && isset($_GET['merchantOrderId']) && isset($_GET['reference']) && $_GET['resultCode'] != '00') {
      //if deny, redirect to order checkout page again
	  
      $order_id = stripslashes($_GET['merchantOrderId']);
	  $this->cart->clear();
	  $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('duitku_va_bni_failure_mapping'), 'Duitku payment failed.');
      $redirUrl = $this->url->link('payment/duitku_va_bni/failure');
      $this->response->redirect($redirUrl);

    }else if( isset($_GET['order_id']) && !isset($_GET['resultCode'])){
      // if customer click "back" button, redirect to checkout page again
      $redirUrl = $this->url->link('checkout/cart');
      $this->response->redirect($redirUrl);
    }
    $this->response->redirect($redirUrl);
  }

  /*
  * assume there is no failure in bank transfer but waiting for transfer
  */
  public function failure() {
    $this->load->language('payment/duitku_va_bni');

    $this->document->setTitle($this->language->get('heading_title'));

    $data['heading_title'] = $this->language->get('heading_title');
    $data['text_failure'] = $this->language->get('text_failure');

    $data['column_left'] = $this->load->controller('common/column_left');
    $data['column_right'] = $this->load->controller('common/column_right');
    $data['content_top'] = $this->load->controller('common/content_top');
    $data['content_bottom'] = $this->load->controller('common/content_bottom');
    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');
    //$data['checkout_url'] = $this->url->link('checkout/cart');

     if(version_compare(VERSION, '2.2.0.0') < 0) {
      // CODE HERE IF LOWER
      if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/duitku_checkout_va.tpl')) {
        $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/payment/duitku_checkout_va.tpl', $data));
      } else {
        $this->response->setOutput($this->load->view('default/template/payment/duitku_checkout_va', $data));
      }
    } else {
      // CODE HERE IF HIGHER OR EQUAL
      $this->response->setOutput($this->load->view('payment/duitku_checkout_va', $data));
    }        
  }

   /**
   * Called when Duitku server sends notification to this server.
   * It will change order status according to transaction status and fraud
   * status sent by Duitku server.
   */
  public function payment_notification() {
    header("HTTP/1.1 200 OK");

    $this->load->model('checkout/order');
    $this->load->model('payment/duitku_va_bni');

    if (empty($_REQUEST['resultCode']) || empty($_REQUEST['merchantOrderId']) || empty($_REQUEST['reference'])) {
          throw new Exception(__('wrong query string please contact admin.', 'duitku_va_bni'));
    }    

    $order_id = stripslashes($_REQUEST['merchantOrderId']);
    $status = stripslashes($_REQUEST['resultCode']);
    $reference = stripslashes($_REQUEST['reference']);
    $api_key = $this->config->get('duitku_va_bni_api_key');
    $merchant_code = $this->config->get('duitku_va_bni_merchant');    
    $endpoint = $this->config->get('duitku_va_bni_endpoint');

    $order_info = $this->model_checkout_order->getOrder($order_id);

    //check if order id is in the database
    if ($order_info) {
        $this->log->write("perform validation");
        if ($status == '00' && Duitku_VtWeb::validateTransaction($endpoint, $merchant_code, $order_id, $reference, $api_key)) {
            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('duitku_va_bni_success_mapping'), 'Duitku payment successful.');    
        } else {
            $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('duitku_va_bni_failure_mapping'), 'Duitku payment failed.');
        }     
    }

    echo "success";
  }
}
