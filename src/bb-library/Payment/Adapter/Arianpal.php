<?php
/**
* BoxBilling
*
* LICENSE
*
* This source file is subject to the license that is bundled
* with this package in the file LICENSE.txt
* It is also available through the world-wide-web at this URL:
* http://www.boxbilling.com/LICENSE.txt
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@boxbilling.com so we can send you a copy immediately.
*
* @copyright Copyright (c) 2010-2012 BoxBilling (http://www.boxbilling.com)
* @license   http://www.boxbilling.com/LICENSE.txt
* @version   1.1
* Dev By www . arianpal . com
*/
class Payment_Adapter_arianpal extends Payment_AdapterAbstract {
  public function _send($url, $api, $amount, $redirect) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "api=$api&amount=$amount&redirect=$redirect");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
  }
  public function _get($url, $api, $trans_id, $id_get) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "api=$api&id_get=$id_get&trans_id=$trans_id");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
  }
  public $go_url;
  public function init() {
    if (!$this->getParam('merchantID') || !$this->getParam('password')) {
      throw new Payment_Exception('Payment gateway "arianpal" is not configured properly. Please update configuration parameter "API Key Code" at "Configuration -> Payments".');
    }
  }
  public static function getConfig() {
    return array('supports_one_time_payments' => true, 'supports_subscriptions' => false, 'description' => 'Clients will be redirected to arianpal.ir to make payment.<br />', 'form' => array('merchantID' => array('text', array('label' => 'MerchantID', 'description' => '????? ????? ??????? ?? ????? ???? ??? ', 'validators' => array('notempty'),),), 'password' => array('text', array('label' => 'Password', 'description' => '????? ????? ??????? ?? ????? ???? ??? ', 'validators' => array('notempty'),),), 'currencie' => array('radio', array('multiOptions' => array('1' => 'Toman', '0' => 'Rial'), 'label' => 'Select Currencies',),),),);
  }
/**
* Return payment gateway type
* @return string
*/
  public function getType() {
    return Payment_AdapterAbstract :: TYPE_FORM;
  }
/**
* Return payment gateway type
* @return string
*/
  public function getServiceUrl() {

    return $this->go_url;
  }
/**
* Init call to webservice or return form params
* @param Payment_Invoice $invoice
*/
  public function singlePayment(Payment_Invoice $invoice) {
    require_once ('./bb-includes/nusoapp.php');
    $url = '';
    $MerchantID = $this->getParam('merchantID');
    $Password = $this->getParam('password');
    $amount = (int) $invoice->getTotalWithTax();
    $redirect = $this->getParam('redirect_url');
    if ($this->getParam('currencie') == '0')
      $amount = $amount / 10;
    $ResNumber = $invoice->getId();
    $Description = $invoice->getTitle();
    $client = $invoice->getBuyer();
    $Paymenter = $client->getFirstName() . ' ' . $client->getLastName();
    $Paymenter = urlencode($Paymenter);
    $Email = $client->getEmail();
    $Mobile = $client->getPhone();
    $client = new nusoap_client('http://merchant.arianpal.com/WebService.asmx?wsdl', 'wsdl');
    $err = $client->getError();
    if ($err) {
      echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
      die();
    }
    $parameters = array("MerchantID" => $MerchantID, "Password" => $Password, "Price" => $amount, "ReturnPath" => $redirect, "ResNumber" => $ResNumber, "Description" => $Description, "Paymenter" => $Paymenter, "Email" => $Email, "Mobile" => $Mobile);
// Call the SOAP method
    $result = $client->call('RequestPayment', array($parameters));
// Check for a fault
    if ($client->fault) {
      throw new Exception('<h2>Fault</h2><pre>' . $result . '</pre>');
    }
    else {
// Check for errors
      $resultStr = $result;
      $err = $client->getError();
      $PayPath = $result['RequestPaymentResult']['PaymentPath'];
      $Status = $result['RequestPaymentResult']['ResultStatus'];
      if ($err) {
// Display the error
        throw new Exception('<h2>Fault</h2><pre>' . $err . '</pre>');
      }
      else {
        $PayPath = $result['RequestPaymentResult']['PaymentPath'];
        $Status = $result['RequestPaymentResult']['ResultStatus'];
        if ($Status == 'Succeed') {
          $this->go_url = $PayPath;
        }
        else {
          throw new Exception('arianpal error : ' . $result.' - '.$Status);
        }
      }
    }
    return $data;
  }
/**
* Perform recurent payment
*/
  public function recurrentPayment(Payment_Invoice $invoice) {
    throw new Payment_Exception('Not implemented yet');
  }
/**
* Handle IPN and return response object
* @return Payment_Transaction
*/
  public function getTransaction($data, Payment_Invoice $invoice) {
          $ipn = $data['post'];
          require_once ('./bb-includes/nusoapp.php');
          $MerchantID = $this->getParam('merchantID');
          $Password = $this->getParam('password');
          $amount = (int) $invoice->getTotalWithTax();
          if ($this->getParam('currencie') == '0')
                $amount = $amount / 10;
          $Pay_Status = 'Failed';
          $VerifyAnswer = '';
          $Refnumber = $ipn['refnumber'];
          if (isset ($ipn['status']) && $ipn['status'] == 100) {
                //arianpal successed payments condition
                $Status = $ipn['status'];
                $Resnumber = $ipn['resnumber'];
                $client = new nusoap_client('http://merchant.arianpal.com/WebService.asmx?wsdl', 'wsdl');
                $err = $client->getError();
                if ($err) {
                    throw new Exception('<h2>Fault</h2><pre>' . $result . '</pre>');
                }
                else
                {
                      $parameters = array("MerchantID" => $MerchantID, "Password" => $Password, "Price" => $amount, "RefNum" => $Refnumber);
                      $result = $client->call('verifyPayment', $parameters);

                      if ($client->fault) {
                        throw new Exception('<h2>Fault</h2><pre>' . $result . '</pre>');
                      }
                      else
                      {
                            $resultStr = $result;
                            $err = $client->getError();
                            if ($err) {
                                    throw new Exception('<h2>Fault</h2><pre>' . $err . '</pre>');
                            }
                            else
                            {
                                    $Status = $result['verifyPaymentResult']['ResultStatus'];
                                    error_log('Verify answer:' . $Status);
                                    if ($Status == 'Success') {
                                          $Pay_Status = 'OK'  ;
                                    }
                                    else {
                                          $Pay_Status = 'Verifyed';
                                    }
                            }
                      }
                }
          }
          else
          {
                error_log('Verify answer:' . $ipn['status']);
          }
          if ($Pay_Status != 'OK') {
            throw new Payment_Exception('arianpal Verification Failed: ' . $Pay_Status);
          }
          $response = new Payment_Transaction();
          $response->setType(Payment_Transaction :: TXTYPE_PAYMENT);
          $response->setId($Refnumber);
          $response->setAmount($invoice->getTotalWithTax());
          $response->setCurrency($invoice->getCurrency());
          $response->setStatus(Payment_Transaction :: STATUS_COMPLETE);
          return $response;
  }
/**
* Check if Ipn is valid
*/
  public function isIpnValid($data, Payment_Invoice $invoice) {
    $ipn = $data['post'];
    return true;
  }
}