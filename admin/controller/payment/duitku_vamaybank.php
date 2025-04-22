<?php
class ControllerPaymentDuitkuVamaybank extends Controller {

  private $error = array();

  public function index() {
    $this->load->language('payment/duitku_vamaybank');

    $this->document->setTitle($this->language->get('heading_title'));

    $this->load->model('setting/setting');
    $this->load->model('localisation/order_status');
    $this->config->get('curency');


    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $this->model_setting_setting->editSetting('duitku_vamaybank', $this->request->post);

      $this->session->data['success'] = $this->language->get('text_success');

        $this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
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
      'entry_test',
      'entry_total',
      'entry_order_status',
      'entry_geo_zone',
      'entry_status',
      'entry_sort_order',                              
      'entry_duitku_vamaybank_success_mapping',
      'entry_duitku_vamaybank_failure_mapping',      
      'entry_display_name',
      'entry_environment',
      'entry_endpoint',
      'button_save',
      'button_cancel'
      );

    foreach ($language_entries as $language_entry) {
      $this->data[$language_entry] = $this->language->get($language_entry);
    }

    if (isset($this->error)) {
      $this->data['error'] = $this->error;
    } else {
      $this->data['error'] = array();
    }

    $this->data['breadcrumbs'] = array();

    $this->data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_home'),
      'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
      'separator' => false
    );

    $this->data['breadcrumbs'][] = array(
      'text' => $this->language->get('text_payment'),
      'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
      'separator' => ' :: '
    );

    $this->data['breadcrumbs'][] = array(
      'text' => $this->language->get('heading_title'),
      'href' => $this->url->link('payment/duitku_vamaybank', 'token=' . $this->session->data['token'], 'SSL'),
      'separator' => ' :: '
    );

    $this->data['action'] = $this->url->link('payment/duitku_vamaybank', 'token=' . $this->session->data['token'], 'SSL');

    $this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

    $inputs = array(            
      'duitku_vamaybank_merchant',
      'duitku_vamaybank_environment',
      'duitku_vamaybank_api_key',
      'duitku_vamaybank_endpoint',      
      'duitku_vamaybank_debug',
      'duitku_vamaybank_total',
      'duitku_vamaybank_order_status_id',
      'duitku_vamaybank_geo_zone_id',
      'duitku_vamaybank_sort_order',              
      'duitku_vamaybank_status',      
      'duitku_vamaybank_success_mapping',
      'duitku_vamaybank_failure_mapping',
      'duitku_vamaybank_challenge_mapping',
      'duitku_vamaybank_display_name',      
      'duitku_vamaybank_sanitization',      
    );

    foreach ($inputs as $input) {
      if (isset($this->request->post[$input])) {
        $this->data[$input] = $this->request->post[$input];
      } else {
        $this->data[$input] = $this->config->get($input);
      }
    }

    $this->load->model('localisation/order_status');

    $this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

    $this->load->model('localisation/geo_zone');

    $this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

    $this->template = 'payment/duitku_vamaybank.tpl';
    $this->children = array(
      'common/header',
      'common/footer'
    );
  
  
  if(!$this->currency->has('IDR'))
  {
    $this->data['curr'] = true;
  }
  else
  {
    $this->data['curr'] = false;
  }
  $this->response->setOutput($this->render());
  
  }

  protected function validate() {
      

    if (!$this->user->hasPermission('modify', 'payment/duitku_vamaybank')) {
      $this->error['warning'] = $this->language->get('error_permission');
    }

    // check for empty values
    if (!$this->request->post['duitku_vamaybank_display_name']) {
      $this->error['display_name'] = $this->language->get('error_display_name');
    }       

    // check for empty values
    if (!$this->request->post['duitku_vamaybank_api_key']) {
      $this->error['client_key_v2'] = $this->language->get('error_client_key');
    }

    if (!$this->request->post['duitku_vamaybank_merchant']) {
      $this->error['server_key_v2'] = $this->language->get('error_server_key');
    }        

    if (!$this->request->post['duitku_vamaybank_endpoint']) {
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
