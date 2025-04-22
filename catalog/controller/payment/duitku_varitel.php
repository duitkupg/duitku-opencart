<?php

require_once(DIR_SYSTEM . 'library/duitku-php/Duitku.php');

class ControllerPaymentDuitkuVaritel extends Controller {

  public function index() {

    $this->data['errors'] = array();
    $this->data['button_confirm'] = $this->language->get('button_confirm');
    
    $this->data['text_loading'] = $this->language->get('text_loading');

    $this->data['process_order'] = $this->url->link('payment/duitku_varitel/process_order');

    
      // CODE HERE IF LOWER
      if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/duitku.tpl')) {        
        $this->template = $this->config->get('config_template') . '/template/payment/duitku.tpl';
      } else {
        $this->template = 'default/template/payment/duitku.tpl';
      }            
      
      $this->render();    
  }

  /**
   * Called when a customer checkouts.
   * If it runs successfully, it will redirect to Duitku payment page.
   */
  public function process_order() {    
    $this->load->model('payment/duitku_varitel');
    $this->load->model('checkout/order');
    $this->load->model('total/shipping');
    $this->load->language('payment/duitku_varitel');

    $this->data['errors'] = array();

    $this->data['button_confirm'] = $this->language->get('button_confirm');

    $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

    //generate Signature
    $merchant_code = $this->config->get('duitku_varitel_merchant');
    $api_key = $this->config->get('duitku_varitel_api_key');
    $order_id = $this->session->data['order_id'];
    $def_curr = $this->config->get('config_currency');
    $order_total = $def_curr == 'IDR' ? $order_info['total'] : $this->currency->convert($order_info['total'], $order_info['currency_code'], 'IDR');
    $signature = md5($merchant_code . $order_id . intval($order_total) . $api_key);    

    // Prepare Parameters
    $params = array(
          'merchantCode' => $merchant_code, // API Key Merchant /
          'paymentAmount' => intval($order_total), //transform order into integer
          'paymentMethod' => "FT",
          'merchantOrderId' => $order_id,
          'productDetails' => $this->config->get('config_name') . ' Order : #' . $order_id,
          'additionalParam' => '',
          'merchantUserInfo' => $order_info['payment_firstname'] . " " . $order_info['payment_lastname'],
          'signature' => $signature,          
          'returnUrl' => $this->url->link('payment/duitku_varitel/landing_redir'),
          'callbackUrl' => $this->url->link('payment/duitku_varitel/payment_notification'),
    );           

   /* Duitku_Config::$isProduction =
        $this->config->get('duitku_environment') == 'production'
        ? true : false;   */

    try {     
      $redirUrl = Duitku_VtWeb::getRedirectionUrl($this->config->get('duitku_varitel_endpoint'), $params);       
       $this->redirect($redirUrl);
    }
    catch (Exception $e) {
      $this->data['errors'][] = $e->getMessage();
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
        
    $redirUrl = $this->url->link('checkout/cart');

    if (isset($_GET['resultCode']) && isset($_GET['merchantOrderId']) && isset($_GET['reference']) && $_GET['resultCode'] == '00') {
      //if capture or pending or challenge or settlement, redirect to order received page
      $this->cart->clear();
      $redirUrl = $this->url->link('checkout/success&');
      $this->response->redirect($redirUrl);

    }else if( isset($_GET['resultCode']) && isset($_GET['merchantOrderId']) && isset($_GET['reference']) && $_GET['resultCode'] != '00') {
      //if deny, redirect to order checkout page again
      // $redirUrl = $this->url->link('checkout/cart');
      $redirUrl = $this->url->link('payment/duitku_varitel/failure');
      $this->response->redirect($redirUrl);

    }else if( isset($_GET['order_id']) && !isset($_GET['resultCode'])){
      // if customer click "back" button, redirect to checkout page again
      $redirUrl = $this->url->link('checkout/cart');
      $this->response->redirect($redirUrl);
    }
    $this->response->redirect($redirUrl);
  }

  /*
  * redirect to payment failure using template & language (text template)
  */
  public function failure() {
    $this->load->language('payment/duitku_varitel');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->data['heading_title'] = $this->language->get('heading_title');
    $this->data['text_failure'] = $this->language->get('text_failure');

    $this->children = array(
      'common/column_left',
      'common/column_right',
      'common/content_top',
      'common/content_bottom',
      'common/footer',
      'common/header'
    );

    $this->data['checkout_url'] = $this->url->link('checkout/cart');    

    if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/duitku_checkout_failure.tpl')) {      
       $this->template = $this->config->get('config_template') . '/template/payment/duitku_checkout_failure.tpl';
    } else {      
      $this->template = 'default/template/payment/duitku_checkout_failure.tpl';
    }

    $this->response->setOutput($this->render(true));

  }

    /**
   * Called when Duitku server sends notification to this server.
   * It will change order status according to transaction status and fraud
   * status sent by Duitku server.
   */
  public function payment_notification() {    
    header("HTTP/1.1 200 OK");

    $this->load->model('checkout/order');
    $this->load->model('payment/duitku_varitel');

    
    if (empty($_REQUEST['resultCode']) || empty($_REQUEST['merchantOrderId']) || empty($_REQUEST['reference'])) {
          throw new Exception(__('wrong query string please contact admin.', 'duitku_varitel'));
    }    

    $order_id = stripslashes($_REQUEST['merchantOrderId']);
    $status = stripslashes($_REQUEST['resultCode']);
    $reference = stripslashes($_REQUEST['reference']);
    $api_key = $this->config->get('duitku_varitel_api_key');
    $merchant_code = $this->config->get('duitku_varitel_merchant');    
    $endpoint = $this->config->get('duitku_varitel_endpoint');

    $order_info = $this->model_checkout_order->getOrder($order_id);        

    //check if order id is in the database
    if ($order_info) {        
        if ($status == '00' && Duitku_VtWeb::validateTransaction($endpoint, $merchant_code, $order_id, $reference, $api_key)) {
          $order_status_id = $this->config->get('duitku_varitel_success_mapping');          
        } else {
          $order_status_id = $this->config->get('duitku_varitel_failure_mapping');       
        }     

        if (!$order_info['order_status_id']) {
          $this->model_checkout_order->confirm($order_id, $order_status_id);
        } else {
          $this->model_checkout_order->update($order_id, $order_status_id);
        }
    }

        
  }
}
