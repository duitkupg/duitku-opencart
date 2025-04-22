<?php

require_once(DIR_SYSTEM . 'library/duitku-php/Duitku.php');

class ControllerPaymentDuitkuPosPay extends Controller {

  public function index() {

    $this->data['errors'] = array();
    $this->data['button_confirm'] = $this->language->get('button_confirm');
    
    $this->data['text_loading'] = $this->language->get('text_loading');

    $this->data['process_order'] = $this->url->link('payment/duitku_pospay/process_order');

    
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
    $this->load->model('payment/duitku_pospay');
    $this->load->model('checkout/order');
    $this->load->model('total/shipping');
    $this->load->language('payment/duitku_pospay');

    $this->data['errors'] = array();

    $this->data['button_confirm'] = $this->language->get('button_confirm');

    $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

    //generate Signature
    $merchant_code = $this->config->get('duitku_pospay_merchant');
    $api_key = $this->config->get('duitku_pospay_api_key');
	$expired = $this->config->get('duitku_pospay_expired') != null ? $this->config->get('duitku_pospay_expired') : 1440;
    $order_id = $this->session->data['order_id'];
    $def_curr = $this->config->get('config_currency');
    $order_total = $def_curr == 'IDR' ? $order_info['total'] : $this->currency->convert($order_info['total'], $order_info['currency_code'], 'IDR');
    
	//itemDetails
	$products = $this->cart->getProducts();
	  
	$item_details = array();

    foreach ($products as $product) {
      $item = array(
        'price'    => (int)$product['price']*(int)$product['quantity'],
        'quantity' => (int)$product['quantity'],
        'name'     => substr($product['name'], 0, 49)
      );
      $item_details[] = $item;
    }

    if ($this->cart->hasShipping()) {
      $shipping_data = $this->session->data['shipping_method'];
      if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
        $shipping_data['cost'] = $this->tax->calculate(
          $shipping_data['cost'],
          $shipping_data['tax_class_id'],
          $this->config->get('config_tax'));
        }

        $shipping_item = array(
          'price' => (int)$shipping_data['cost'],
          'quantity' => 1,
          'name' => 'Shipping Fee'
        );
        $item_details[] = $shipping_item;
	}

	$amount_price = 0;
	foreach ($item_details as $item) {
		$amount_price += $item['price'];
	}

	if ($amount_price != $order_total) {
		$coupon_item = array(
			'price'    => (int)$order_total - (int)$amount_price,
			'quantity' => 1,
			'name'     => 'Coupon'
		  );
		$item_details[] = $coupon_item;
	}
	
	$billing_address = array(
	  'firstName' => $order_info['payment_firstname'],
	  'lastName' => $order_info['payment_lastname'],
	  'address' => $order_info['payment_address_1'],
	  'city' => $order_info['payment_city'],
	  'postalCode' => $order_info['payment_postcode'],
	  'phone' => $order_info['telephone'],
	  'countryCode' => "ID"
	);
	
	$customerDetails = array(
		'firstName' => $order_info['payment_firstname'],
		'lastName' => $order_info['payment_lastname'],
		'email' => $order_info['email'],
		'phoneNumber' => $order_info['telephone'],
		'billingAddress' => $billing_address,
		'shippingAddress' => $billing_address
	);
	
	$signature = md5($merchant_code . $order_id . intval($order_total) . $api_key);    

    // Prepare Parameters
    $params = array(
          'merchantCode' => $merchant_code, // API Key Merchant /
          'paymentAmount' => intval($order_total), //transform order into integer
          'paymentMethod' => "A2",
          'merchantOrderId' => $order_id,
          'productDetails' => $this->config->get('config_name') . ' Order : #' . $order_id,
          'additionalParam' => $order_info['payment_firstname'] . " " . $order_info['payment_lastname'],
          'merchantUserInfo' => $order_info['email'],
		  'customerVaName' => $order_info['email'],
		  'email' => $order_info['email'],
          'phoneNumber' => $order_info['telephone'],
          'signature' => $signature,
		  'expiryPeriod' => $expired,       
          'returnUrl' => $this->url->link('payment/duitku_pospay/landing_redir'),
          'callbackUrl' => $this->url->link('payment/duitku_pospay/payment_notification'),
		  'customerDetail' => $customerDetails,
		  'itemDetails' => $item_details,
    );          

   /* Duitku_Config::$isProduction =
        $this->config->get('duitku_environment') == 'production'
        ? true : false;   */

    try {     
      $redirUrl = DuitkuCore_Web::getRedirectionUrl($this->config->get('duitku_pospay_endpoint'), $params);       
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
   * http://[your shop’s homepage]/index.php?route=payment/duitku_pospay/payment_notification
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
      $redirUrl = $this->url->link('payment/duitku_pospay/failure');
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
    $this->load->language('payment/duitku_pospay');

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
    $this->load->model('payment/duitku_pospay');

    
    if (empty($_REQUEST['resultCode']) || empty($_REQUEST['merchantOrderId']) || empty($_REQUEST['reference'])) {
          throw new Exception(__('wrong query string please contact admin.', 'duitku_pospay'));
    }    

    $order_id = stripslashes($_REQUEST['merchantOrderId']);
    $status = stripslashes($_REQUEST['resultCode']);
    $reference = stripslashes($_REQUEST['reference']);
    $api_key = $this->config->get('duitku_pospay_api_key');
    $merchant_code = $this->config->get('duitku_pospay_merchant');    
    $endpoint = $this->config->get('duitku_pospay_endpoint');

    $order_info = $this->model_checkout_order->getOrder($order_id);        

    //check if order id is in the database
    if ($order_info) {        
        if ($status == '00' && DuitkuCore_Web::validateTransaction($endpoint, $merchant_code, $order_id, $reference, $api_key)) {
          $order_status_id = $this->config->get('duitku_pospay_success_mapping');          
        } else {
          $order_status_id = $this->config->get('duitku_pospay_failure_mapping');       
        }     

        if (!$order_info['order_status_id']) {
          $this->model_checkout_order->confirm($order_id, $order_status_id);
        } else {
          $this->model_checkout_order->update($order_id, $order_status_id);
        }
    }

        
  }
}
