<?php
// Heading
$_['heading_title']      = 'Duitku Va Mandiri (Deprecated)';

// Text
$_['text_payment']       = 'Payment';
$_['text_success']       = 'Success: You have modified Duitku account details!';
$_['text_duitku_vamandiri']= '<img src="view/image/payment/vamandiri.png" width="60" height = "25" alt="Duitku Va Mandiri (Deprecated)" title="Duitku" style="border: 1px solid #EEEEEE;" />';
$_['text_live']          = 'Production';
$_['text_successful']    = 'Always Successful';
$_['text_fail']          = 'Always Fail';

$_['entry_environment']  = 'Environment'; // v2 API only
$_['entry_endpoint']     = 'URL Endpoint'; 
$_['entry_merchant']     = 'Merchant ID:'; // v1 API only
$_['entry_api_key']      = 'Merchant API Key'; // v1 API only
$_['entry_expired_period']	= 'Expired Period';
$_['entry_test']         = 'Test Mode:';
$_['entry_total']        = 'Total:<br /><span class="help">The checkout total the order must reach before this payment method becomes active.</span>';
$_['entry_order_status'] = 'Order Status:';
$_['entry_geo_zone']     = 'Geo Zone:';
$_['entry_status']       = 'Status:';
$_['entry_sort_order']   = 'Sort Order:';
$_['entry_duitku_vamandiri_success_mapping'] = 'Map Payment Success Status to Order Status:';
$_['entry_duitku_vamandiri_failure_mapping'] = 'Map Payment Failure Status to Order Status:';
$_['entry_display_name'] = 'Display name:';

// Error
$_['error_permission']   = 'Warning: You do not have permission to modify the Duitku Payment!';
$_['error_merchant']     = 'Merchant ID is required!';
$_['error_client_key']   = 'Client Key is required!';
$_['error_server_key']   = 'Merchant Code is required!';
$_['error_expired_period']   = 'Expired Period is required! 1 - 1440 ( minute )';
$_['error_currency_conversion'] = 'Currency conversion rate is required when IDR currency is not installed in the system!';
$_['error_display_name'] = 'Please specify a name for this payment method!';
$_['error_endpoint']   = 'URL Endpoint is required!';
?>