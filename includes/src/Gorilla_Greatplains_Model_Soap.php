<?php

class Gorilla_Greatplains_Model_Soap extends Mage_Core_Model_Abstract
{

    public $_response;
    private $_type;
    private $_soap;
    protected $_data;
    protected $_errors = null;

    const TYPEGETPRODUCTBYSKU = "getproductbysku";
    const TYPEGETPRODUCTLIST = "getproductlist";
    const TYPECREATENEWORDER = "createneworder";
    const TYPEGETORDERDETAIL = "getorderdetail";
    const TYPEGETOFFLINEORDERSUMMARY = "getofflineordersummary";
    const TYPEUPDATEORDERRESPONSE = "updateorderresponse";

    /* 	public function getData() {

      }
      public function getData
     */

    function __construct()
    {
        try {
            $this->_soap = new SoapClient($this->getWsdlUrl(), array('connection_timeout' => 10, 'exceptions'         => true,
                'trace'              => (Mage::helper('greatplains')->isDebug()) ? 1 : 0, 'compression'        => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP));
        } catch (SoapFault $sf) {
            // Log SOAP fault from connection
            //Mage::helper ( 'greatplains' )->Log ( $sf->__toString (), Zend_Log::CRIT );
            return false;
        } catch (Exception $e) {
            // Log Exception from Connection
            //Mage::helper ( 'greatplains' )->Log ( $e->__toString (), Zend_Log::CRIT );
            return false;
        }
        return $this;
    }

    public function getProductBySku($sku)
    {
        //Mage::helper ( 'greatplains' )->Log ("Starting getProductBySku ".$sku);

        $param = new Gorilla_Greatplains_Model_Source_GetProductBySKU();

        $param->sku = $sku;
        $response = $param->Process($this->doCall('GetProductBySKU', $param));
        $errors = $response->getErrors();

        if ($errors) {
            $this->_errors = $response->getErrors();
            //Mage::helper ( 'greatplains' )->Log ("error : ".$this->_errors);
            return false;
        }
        return $response->getData();
    }

    public function getProductList($data)
    {

        $param = new Gorilla_Greatplains_Model_Source_GetProductList($data);

        $data = $param->getData();

        $response = $param->Process($this->doCall('GetProductList', $data));

        $errors = $response->getErrors();

        // print_r($errors);
        if ($errors) {
            $this->_errors = $response->getErrors();
            //Mage::helper ( 'greatplains' )->Log ("error : ".$this->_errors);
            return false;
        }
        return $response->getData();
    }

    public function getOrderDetail($ordernumber)
    {
        // echo $ordernumber."<br><br>";
        $param = new Gorilla_Greatplains_Model_Source_GetOrderDetail($ordernumber);
        //	echo "<pre>";
        //print_r($param);

        $re = $this->doCall('GetOrderDetail', $param);

        //print_r($re);
        //       echo "</pre>";
        // die;
        $response = $param->Process($re);

        $errors = $response->getErrors();

        if ($errors) {
            $this->_errors = $response->getErrors();
            Mage::helper('greatplains')->Log("error : " . $this->_errors);
            return false;
        }
        return $response;
    }

    public function getOfflineOrderSummary($indno = "", $orgno = "", $lastname = "")
    {

        $param = new Gorilla_Greatplains_Model_Source_GetOfflineOrderSummary($indno, $orgno, $lastname);

        $response = $param->Process($this->doCall('GetOfflineOrderSummary', $param));

        $errors = $response->getErrors();

        if ($errors) {
            $this->_errors = $response->getErrors();
            //Mage::helper ( 'greatplains' )->Log ("error : ".$this->_errors);
            return false;
        }
        return $response;
    }

    public function updateOrder($data)
    {

        $param = new Gorilla_Greatplains_Model_Source_UpdateOrder($data);

        $response = $param->Process($this->doCall('UpdateOrder', $param));

        $errors = $response->getErrors();

        if ($errors) {
            $this->_errors = $response->getErrors();
            return $this;
        }

        return $response;
    }

    public function createNewOrder($order)
    {

        $param = new Gorilla_Greatplains_Model_Source_CreateNewOrder($order);
        Mage::log($param, null, 'gp_soap_requests.log', true);
        $response = $param->Process($this->doCall('CreateNewOrder', $param));
        Mage::log($response, null, 'gp_soap_requests.log', true);
        Mage::log('-------------------------------', null, 'gp_soap_requests.log', true);
        $errors = $response->getErrors();
        if ($errors) {
            $this->_errors = $response->getErrors();
            return $this;
        }
        return $response;
    }

    private function doCall($transaction, $data)
    {
        $object = json_decode(json_encode($data), TRUE);
        Mage::helper('greatplains')->Log("Request array: " . print_r($object, true));
        //Mage::helper('greatplains')->Log("Request XML: " . print_r($object, true));
        $response = null;
        // $this->_response->return->error = array();
        try {

            $client = $this->getSoapClient();
            if (!$client) return false;

            $response = $client->$transaction($object);
        } Catch (SoapFault $sf) {
            if ($response) {
                $response->return->error = $sf->getMessage();
            }
            else
            {
                $response = $sf->getMessage();
            }
            Mage::helper('greatplains')->Log("error : " . $sf->getMessage());
        } Catch (Exception $e) {
            if ($response) {
                $response->return->error = $e->getMessage();
            }
            else
            {
                $response = $sf->getMessage();
            }
            Mage::helper('greatplains')->Log("error : " . $e->getMessage());
        }

        $request = $client->__getLastRequest();

        try {
            Mage::helper('greatplains')->Log("Request : " . print_r($request, true));
        } catch (Exception $e) {
            MAGE::Log("Error printing request to log");
        }
        try {
            Mage::helper('greatplains')->Log("Response : " . print_r($response, true));
        } catch (Exception $e) {
            MAGE::Log("Error printing response to log");
        }

        return $response;
    }

    /**
     *
     * @return boolean|SoapClient
     */
    public function getSoapClient()
    {

        if (!Mage::registry('gp_soap_client')) {

            try {

                $this->_soap = new SoapClient($this->getWsdlUrl(), array('connection_timeout' => 20, 'exceptions'         => true,
                    'trace'              => (Mage::helper('greatplains')->isDebug()) ? 1 : 0, 'compression'        => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP));
            } catch (SoapFault $sf) {
                // Log SOAP fault from connection
               // Mage::helper ( 'greatplains' )->Log ( "Soap Fault " . $sf->__toString (), Zend_Log::ERR );
             //  print_r($sf->__toString ());
               // die();
                return false;
            } catch (Exception $e) {
                //Mage::helper ( 'greatplains' )->Log ( "Execption " . $e->__toString (), Zend_Log::ERR );
              //  print_r( $e->__toString ());
               // die();
                // Log Exception from Connection
                return false;
            }
          //  var_dump($this->_soap); die();
            Mage::register('gp_soap_client', $this->_soap);
        }
        return Mage::registry('gp_soap_client');
        //return $this->_soap;
    }

    public function getWsdlUrl()
    {
        return Mage::helper('greatplains')->getWsdlUrl();
    }

    public function getErrors()
    {
        //Mage::helper ( 'greatplains' )->Log ( "getting errors" . print_r ( $this->_error, true ) );
        return $this->_error;
    }

    public function getErrorMessages()
    {
        return $this->_errors;
    }

    private function addError($error)
    {
        if (is_array($this->_error)) {
            return $this->_error[] = $error;
        }
        return $this->_error = array($error);
    }
    
    public static function generateValidXmlFromObj(stdClass $obj, $node_block='nodes', $node_name='node') {
        $arr = get_object_vars($obj);
        return self::generateValidXmlFromArray($arr, $node_block, $node_name);
    }

    public static function generateValidXmlFromArray($array, $node_block='nodes', $node_name='node') {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>';

        $xml .= '<' . $node_block . '>';
        $xml .= self::generateXmlFromArray($array, $node_name);
        $xml .= '</' . $node_block . '>';

        return $xml;
    }

    private static function generateXmlFromArray($array, $node_name) {
        $xml = '';

        if (is_array($array) || is_object($array)) {
            foreach ($array as $key=>$value) {
                if (is_numeric($key)) {
                    $key = $node_name;
                }

                $xml .= '<' . $key . '>' . self::generateXmlFromArray($value, $node_name) . '</' . $key . '>';
            }
        } else {
            $xml = htmlspecialchars($array, ENT_QUOTES);
        }

        return $xml;
    }    
    

}
