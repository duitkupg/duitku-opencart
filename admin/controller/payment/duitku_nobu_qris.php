<?php
class ControllerPaymentDuitkuNobuQris extends Controller {

  private $error = array();

  public function index() {
    $this->load->language('payment/duitku_nobu_qris');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('setting/setting');
    $this->load->model('localisation/order_status');
    $this->config->get('currency');


    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $this->model_setting_setting->editSetting('duitku_nobu_qris', $this->request->post);

      $this->session->data['success'] = $this->language->get('text_success');

      $this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
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
      'entry_duitku_nobu_qris_success_mapping',
	  'entry_duitku_nobu_qris_pending_mapping',
      'entry_duitku_nobu_qris_failure_mapping',      
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
      'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
      'separator' => ' :: '
    );

    $data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('payment/duitku_nobu_qris', 'token=' . $this->session->data['token'], 'SSL'),
      'separator' => ' :: '
    );

    $data['action'] = $this->url->link('payment/duitku_nobu_qris', 'token=' . $this->session->data['token'], 'SSL');

    $data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

    $inputs = array(            
      'duitku_nobu_qris_merchant',
      'duitku_nobu_qris_environment',
      'duitku_nobu_qris_api_key',
      'duitku_nobu_qris_expired',
      'duitku_nobu_qris_endpoint',      
      'duitku_nobu_qris_debug',
      'duitku_nobu_qris_total',
      'duitku_nobu_qris_order_status_id',
      'duitku_nobu_qris_geo_zone_id',
      'duitku_nobu_qris_sort_order',              
      'duitku_nobu_qris_status',      
      'duitku_nobu_qris_success_mapping',
	  'duitku_nobu_qris_pending_mapping',
      'duitku_nobu_qris_failure_mapping',
      'duitku_nobu_qris_challenge_mapping',
      'duitku_nobu_qris_display_name',      
      'duitku_nobu_qris_sanitization',      
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
  $this->response->setOutput($this->load->view('payment/duitku_nobu_qris.tpl',$data));
  
  }

  protected function validate() {
      

    if (!$this->user->hasPermission('modify', 'payment/duitku_nobu_qris')) {
      $this->error['warning'] = $this->language->get('error_permission');
    }

    // check for empty values
    if (!$this->request->post['duitku_nobu_qris_display_name']) {
      $this->error['display_name'] = $this->language->get('error_display_name');
    }
        

	// check for empty values
	if (!$this->request->post['duitku_nobu_qris_api_key']) {
		$this->error['client_key_v2'] = $this->language->get('error_client_key');
	}

	if (!$this->request->post['duitku_nobu_qris_merchant']) {
		$this->error['server_key_v2'] = $this->language->get('error_server_key');
	}        

	if (!$this->request->post['duitku_nobu_qris_expired'] OR $this->request->post['duitku_nobu_qris_expired'] > 60 ) {
		$this->error['expired_period'] = $this->language->get('error_expired_period');
	}        


	if (!$this->request->post['duitku_nobu_qris_endpoint']) {
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
