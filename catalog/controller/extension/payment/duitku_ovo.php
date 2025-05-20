<?php

require_once(DIR_SYSTEM . 'library/duitku-php/Duitku.php');

class ControllerExtensionPaymentDuitkuOvo extends Controller {

  public function index() {

    $data['errors'] = array();
    $data['button_confirm'] = $this->language->get('button_confirm');
    
    $data['text_loading'] = $this->language->get('text_loading');

    $data['process_order'] = 'extension/payment/duitku_ovo/process_order';

    if(version_compare(VERSION, '3.0.0.0') < 0) {
      // CODE HERE IF LOWER
      if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/duitkutpl')) {
        return $this->load->view($this->config->get('config_template') . '/template/extension/payment/duitkutpl', $data);
      } else {
        return $this->load->view('default/template/extension/payment/duitkutpl', $data);
      }
    } else {
      // CODE HERE IF HIGHER OR EQUAL
      return $this->load->view('extension/payment/duitkutpl', $data);
    }  

  }

  /**
   * Called when a customer checkouts.
   * If it runs successfully, it will redirect to Duitku payment page.
   */
  public function process_order() {    
    $this->load->model('extension/payment/duitku_ovo');
    $this->load->model('checkout/order');
    //$this->load->model('total/shipping');
    $this->load->language('extension/payment/duitku_ovo');

    $data['errors'] = array();

    $data['button_confirm'] = $this->language->get('button_confirm');

    $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

    //generate Signature
    $merchant_code = $this->config->get('payment_duitku_ovo_merchant');
    $api_key = $this->config->get('payment_duitku_ovo_api_key');
    $order_id = $this->session->data['order_id'];
    $def_curr = $this->config->get('config_currency');
    $order_total = trim($this->currency->format($order_info['total'], 'IDR', '', false));
    
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
          'paymentMethod' => "OV",
          'merchantOrderId' => $order_id,
          'productDetails' => $this->config->get('config_name') . ' Order : #' . $order_id,
          'additionalParam' => $order_info['payment_firstname'] . " " . $order_info['payment_lastname'],
          'merchantUserInfo' => $order_info['email'],
		  'customerVaName' => $order_info['email'],
		  'email' => $order_info['email'],
		  'phoneNumber' => $order_info['telephone'],
          'signature' => $signature,
		  'expiryPeriod' => 1440,       
          'returnUrl' => $this->url->link('extension/payment/duitku_ovo/landing_redir'),
          'callbackUrl' => $this->url->link('extension/payment/duitku_ovo/payment_notification'),
		  'customerDetail' => $customerDetails,
		  'itemDetails' => $item_details,
    );        

	//for va cart is automatically clear before redirection
	//$this->cart->clear();
	
    try {  

      //Solution IF Error mail function
      //Disable mail function => Dashboard => Extensions => Events => disable 'mail_order_add'
      $this->cart->clear();
	  $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_duitku_ovo_pending_mapping'), 'Duitku payment pending.');
	
      $redirUrl = DuitkuCore_Web::getRedirectionUrl($this->config->get('payment_duitku_ovo_endpoint'), $params,  $this->log);
      $this->response->setOutput($redirUrl);  
    }
    catch (Exception $e) {
      $this->log->write('Error : ' . $e->getMessage());
      $redirUrl = $this->url->link('extension/payment/duitku_ovo/failure');
      $this->response->setOutput($redirUrl);
    }
  }

  /**
   * Landing page when payment is finished or failure or customer pressed "back" button
   * The Cart is cleared here, so make sure customer reach this page to ensure the cart is emptied when payment succeed
   * payment finish/unfinish/error url :
   * http://[your shop’s homepage]/index.php?route=payment/duitku_ovo/payment_notification
   */
  public function landing_redir() {    
    $this->load->model('checkout/order');
    $this->load->model('extension/payment/duitku_ovo');    
    $redirUrl = $this->url->link('checkout/success');

    if (isset($_GET['resultCode']) && isset($_GET['merchantOrderId']) && isset($_GET['reference']) && $_GET['resultCode'] == '01') {
      //if capture or pending or challenge or settlement, redirect to order received page
      /* $this->cart->clear();
      $redirUrl = $this->url->link('checkout/success&');
      $this->response->redirect($redirUrl); */
	  
	  $order_id = stripslashes($_GET['merchantOrderId']);
	  // $this->cart->clear();
	  // $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_duitku_ovo_pending_mapping'), 'Duitku payment pending.');
      $redirUrl = $this->url->link('payment/duitku_ovo/failure');
      $this->response->redirect($redirUrl);

    }else if( isset($_GET['resultCode']) && isset($_GET['merchantOrderId']) && isset($_GET['reference']) && $_GET['resultCode'] != '00') {
      //if deny, redirect to order checkout page again
	  
      $order_id = stripslashes($_GET['merchantOrderId']);
	  $this->cart->clear();
	  $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_duitku_ovo_failure_mapping'), 'Duitku payment failed.');
      $redirUrl = $this->url->link('extension/payment/duitku_ovo/failure');
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
    $this->load->language('extension/payment/duitku_ovo');

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

     if(version_compare(VERSION, '3.0.0.0') < 0) {
      // CODE HERE IF LOWER
      if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/extension/payment/duitku_checkout_va')) {
        $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/extension/payment/duitku_checkout_va', $data));
      } else {
        $this->response->setOutput($this->load->view('default/template/extension/payment/duitku_checkout_va', $data));
      }
    } else {
      // CODE HERE IF HIGHER OR EQUAL
      $this->response->setOutput($this->load->view('extension/payment/duitku_checkout_va', $data));
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
    $this->load->model('extension/payment/duitku_ovo');

    if (empty($_REQUEST['resultCode']) || empty($_REQUEST['merchantOrderId']) || empty($_REQUEST['reference'])) {
      header("HTTP/1.1 404 Not Found");
      echo "wrong query string please contact admin.";
      die;
    }    

    $order_id = stripslashes($_REQUEST['merchantOrderId']);
    $status = stripslashes($_REQUEST['resultCode']);
    $reference = stripslashes($_REQUEST['reference']);
    $api_key = $this->config->get('payment_duitku_ovo_api_key');
    $merchant_code = $this->config->get('payment_duitku_ovo_merchant');    
    $endpoint = $this->config->get('payment_duitku_ovo_endpoint');

    $signatureCheck = md5($merchant_code . intval($_REQUEST['amount']) . $_REQUEST['merchantOrderId'] . $api_key);

    $order_info = $this->model_checkout_order->getOrder($order_id);
    $current_status_id = $order_info['order_status_id'];

    if ($current_status_id == $this->config->get('payment_duitku_ovo_success_mapping')){
      header("HTTP/1.1 200");
      echo "Order Already Completed";
      die;
    }

    if ($_REQUEST['signature'] != $signatureCheck){
      header("HTTP/1.1 401 Unauthorized");
      echo "Wrong Signature";
      die;
    }

    $order_info = $this->model_checkout_order->getOrder($order_id);
    $this->log->write("Callback Recieved : " . json_encode($_REQUEST, JSON_PRETTY_PRINT));
    //check if order id is in the database
    if ($order_info) {
      try {
        if ($status == '00' && DuitkuCore_Web::validateTransaction($endpoint, $merchant_code, $order_id, $reference, $api_key, $this->log)) {
          $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_duitku_ovo_success_mapping'), 'Duitku payment successful.');    
        } else {
          $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_duitku_ovo_failure_mapping'), 'Duitku payment failed.');
        } 
        echo "Callback Recieved";
      } 
      catch (Exception $e) {
        $this->log->write('Error : ' . $e->getMessage());
        echo "Validation Error";
      }
    }
  }
}
