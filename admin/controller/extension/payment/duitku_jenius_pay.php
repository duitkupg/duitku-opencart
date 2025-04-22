<?php
class ControllerExtensionPaymentDuitkuJeniusPay extends Controller {

  private $error = array();

  public function index() {
    $this->load->language('extension/payment/duitku_jenius_pay');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('setting/setting');
    $this->load->model('localisation/order_status');
    $this->config->get('currency');


    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $this->model_setting_setting->editSetting('duitku_jenius_pay', $this->request->post);

      $this->session->data['success'] = $this->language->get('text_success');

      $this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'], 'SSL'));
    }

    $language_entries = array(
      'heading_title',
      'text_enabled',
      'text_disabled',
      'text_yes',      
      'text_live',
      'text_successful',
      'text_fail',
      'text_all_zones',
      'text_edit',
      'entry_merchant',
      'entry_api_key',          
      'entry_expired_period',
      'entry_test',
      'entry_total',
      'entry_order_status',
      'entry_geo_zone',
      'entry_status',
      'entry_sort_order',                              
      'entry_duitku_jenius_pay_success_mapping',
	  'entry_duitku_jenius_pay_pending_mapping',
      'entry_duitku_jenius_pay_failure_mapping',      
      'entry_display_name',
      'entry_environment',
      'entry_endpoint',
      'button_save',
      'button_cancel'
      );

    foreach ($language_entries as $language_entry) {
      $data[$language_entry] = $this->language->get($language_entry);
    }

    if (isset($this->error)) {
      $data['error'] = $this->error;
    } else {
      $data['error'] = array();
    }

    $data['breadcrumbs'] = array();

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
      'separator' => false
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_payment'),
      'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'], 'SSL'),
      'separator' => ' :: '
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('extension/payment/duitku_jenius_pay', 'token=' . $this->session->data['token'], 'SSL'),
      'separator' => ' :: '
    );

    $data['action'] = $this->url->link('extension/payment/duitku_jenius_pay', 'token=' . $this->session->data['token'], 'SSL');

    $data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'], 'SSL');

    $inputs = array(            
      'duitku_jenius_pay_merchant',
      'duitku_jenius_pay_environment',
      'duitku_jenius_pay_api_key',
      'duitku_jenius_pay_expired',
      'duitku_jenius_pay_endpoint',      
      'duitku_jenius_pay_debug',
      'duitku_jenius_pay_total',
      'duitku_jenius_pay_order_status_id',
      'duitku_jenius_pay_geo_zone_id',
      'duitku_jenius_pay_sort_order',              
      'duitku_jenius_pay_status',      
      'duitku_jenius_pay_success_mapping',
	  'duitku_jenius_pay_pending_mapping',
      'duitku_jenius_pay_failure_mapping',
      'duitku_jenius_pay_challenge_mapping',
      'duitku_jenius_pay_display_name',      
      'duitku_jenius_pay_sanitization',      
    );

    foreach ($inputs as $input) {
      if (isset($this->request->post[$input])) {
        $data[$input] = $this->request->post[$input];
      } else {
        $data[$input] = $this->config->get($input);
      }
    }

    $this->load->model('localisation/order_status');

    $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

    $this->load->model('localisation/geo_zone');

    $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
    
  $data['column_left'] = $this->load->controller('common/column_left');
  $data['header'] = $this->load->controller('common/header');
  $data['footer'] = $this->load->controller('common/footer');
  
  
  if(!$this->currency->has('IDR'))
  {
    $data['curr'] = true;
  }
  else
  {
    $data['curr'] = false;
  }
  $this->response->setOutput($this->load->view('extension/payment/duitku_jenius_pay.tpl',$data));
  
  }

  protected function validate() {
      

    if (!$this->user->hasPermission('modify', 'extension/payment/duitku_jenius_pay')) {
      $this->error['warning'] = $this->language->get('error_permission');
    }

    // check for empty values
    if (!$this->request->post['duitku_jenius_pay_display_name']) {
      $this->error['display_name'] = $this->language->get('error_display_name');
    }
        

	// check for empty values
	if (!$this->request->post['duitku_jenius_pay_api_key']) {
		$this->error['client_key_v2'] = $this->language->get('error_client_key');
	}

	if (!$this->request->post['duitku_jenius_pay_merchant']) {
		$this->error['server_key_v2'] = $this->language->get('error_server_key');
	} 

	if (!$this->request->post['duitku_jenius_pay_expired'] OR $this->request->post['duitku_jenius_pay_expired'] > 1440 ) {
		$this->error['expired_period'] = $this->language->get('error_expired_period');
	}        

	if (!$this->request->post['duitku_jenius_pay_endpoint']) {
		$this->error['endpoint'] = $this->language->get('error_endpoint');
	}        

    if (!$this->error) {
      return true;
    } else {
      return false;
    }
  }
}
?>
