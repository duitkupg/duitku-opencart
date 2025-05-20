<?php

class DuitkuCore_Web {

  public static function getRedirectionUrl($baseUrl, $params, $log)
  {
    //$payloads = array();
    //$payloads = array_replace_recursive($payloads, $params);    
    if ($params['paymentMethod'] == 'MG') {
      $url = $baseUrl . '/api/merchant/creditcard/inquiry';   
    } else {
      $url = $baseUrl . '/api/merchant/v2/inquiry';   
    }
    $log->write("URL : " . $url);
    $log->write("Request : " . json_encode($params, JSON_PRETTY_PRINT) );		
    $result = Duitku_ApiRequestor::post($url,$params);
    $log->write("Response : " . json_encode($result, JSON_PRETTY_PRINT));

    // var_dump($result);
    // die();
    return $result->paymentUrl;
  }
  
  public static function validateTransaction($baseUrl, $merchantCode, $order_id, $reference, $apikey, $log)
  {

        $url = $baseUrl . '/api/merchant/transactionStatus';                        
        $log->write("URL Check transaction: " . $url);
        //generate Signature
        $signature = md5($merchantCode . $order_id . $apikey);

        // Prepare Parameters
        $params = array(
          'merchantCode' => $merchantCode, // API Key Merchant /
          'merchantOrderId' => $order_id,
          'signature' => $signature,
          'reference' => $reference,
        );

        $log->write("Request : " . json_encode($params, JSON_PRETTY_PRINT));

        //throw error if failed
        $result = Duitku_ApiRequestor::post($url,$params);    
        $log->write("Response : " . json_encode($result, JSON_PRETTY_PRINT));
		
		if ($result->statusCode == "00")			
			return true;
		else
			return false;		       
  }
}