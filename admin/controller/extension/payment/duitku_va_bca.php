<?php
class ControllerExtensionPaymentDuitkuVABca extends Controller {

  private $error = array();

  public function index() {
    $this->load->language('extension/payment/duitku_va_bca');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('setting/setting');
    $this->load->model('localisation/order_status');
    $this->config->get('currency');


    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $this->model_setting_setting->editSetting('duitku_va_bca', $this->request->post);

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
      'entry_duitku_va_bca_success_mapping',
	  'entry_duitku_va_bca_pending_mapping',
      'entry_duitku_va_bca_failure_mapping',      
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
      'href' => $this->url->link('extension/payment/duitku_va_bca', 'token=' . $this->session->data['token'], 'SSL'),
      'separator' => ' :: '
    );

    $data['action'] = $this->url->link('extension/payment/duitku_va_bca', 'token=' . $this->session->data['token'], 'SSL');

    $data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'], 'SSL');

    $inputs = array(            
      'duitku_va_bca_merchant',
      'duitku_va_bca_environment',
      'duitku_va_bca_api_key',
      'duitku_va_bca_expired',
      'duitku_va_bca_endpoint',      
      'duitku_va_bca_debug',
      'duitku_va_bca_total',
      'duitku_va_bca_order_status_id',
      'duitku_va_bca_geo_zone_id',
      'duitku_va_bca_sort_order',              
      'duitku_va_bca_status',      
      'duitku_va_bca_success_mapping',
	  'duitku_va_bca_pending_mapping',
      'duitku_va_bca_failure_mapping',
      'duitku_va_bca_challenge_mapping',
      'duitku_va_bca_display_name',      
      'duitku_va_bca_sanitization',      
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
  $this->response->setOutput($this->load->view('extension/payment/duitku_va_bca.tpl',$data));
  
  }

  protected function validate() {
      

    if (!$this->user->hasPermission('modify', 'extension/payment/duitku_va_bca')) {
      $this->error['warning'] = $this->language->get('error_permission');
    }

    // check for empty values
    if (!$this->request->post['duitku_va_bca_display_name']) {
      $this->error['display_name'] = $this->language->get('error_display_name');
    }
        

	// check for empty values
	if (!$this->request->post['duitku_va_bca_api_key']) {
		$this->error['client_key_v2'] = $this->language->get('error_client_key');
	}

	if (!$this->request->post['duitku_va_bca_merchant']) {
		$this->error['server_key_v2'] = $this->language->get('error_server_key');
	} 

	if (!$this->request->post['duitku_va_bca_expired'] OR $this->request->post['duitku_va_bca_expired'] > 1440 ) {
		$this->error['expired_period'] = $this->language->get('error_expired_period');
	}        

	if (!$this->request->post['duitku_va_bca_endpoint']) {
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
