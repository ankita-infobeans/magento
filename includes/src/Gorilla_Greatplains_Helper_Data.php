<?php

class Gorilla_Greatplains_Helper_Data extends Mage_Core_Helper_Abstract {

    const XML_PATH_GREATPLAINS_NEWORDERS_MAX_SEND_ATTEMPTS = 'greatplains/new_orders/max_send_attempts';

    public function getNewOrderMaxSendAttempts() {
        return (int) Mage::getStoreConfig(self::XML_PATH_GREATPLAINS_NEWORDERS_MAX_SEND_ATTEMPTS);
    }

    public function isDebug() {
        return true;
        return $this->_getConfig('debug');
    }

    public function Log($message, $logname = "greatplains", $level = Zend_Log::DEBUG) {

        if ($this->isDebug() || $level < 5) { // log only if debug is enabled or
            // is a warn or below error
            Mage::Log(date("m-d-y G:i:s :: ", time()) . " : " . $message, $level, 'greatplains.' . $logname . '.log');
            //  echo date ( "m-d-y G:i:s :: ", time () ) ." : ".$message."\n";;
        }
    }

    public function getSoap() {

        return Mage::getSingleton('greatplains/soap');

        // return new Gorilla_Paymentech_Model_Profile_Soap ();
    }

    /**
     * Get the URL to the Auth.net WSDL
     *
     * @return string $wsdl_url
     */
    public function getWsdlUrl() {

        if ($this->isTestMode()) {
            return Mage::getStoreConfig('greatplains/general/wsdl_test');
        }
        return Mage::getStoreConfig('greatplains/general/wsdl');

        // $wsdl = "http://iccdev.t3infosystems.com:8080/ICC.GP.MagentoIntegrationService/MagentoIntegration.svc?wsdl";
        //  $wsdl = "http://icc.t3infosystems.com:8080/ICC.GP.MagentoIntegrationService/MagentoIntegration.svc?wsdl";



        return $wsdl;
    }

    /**
     * Are we in test mode?
     *
     * @return bool
     */
    public function isTestMode() {
        //return true;
        return Mage::getStoreConfig('greatplains/general/use_test_wsdl');
    }

    /**
     * Translate Magento shipping method name to GP value and return it.
     *
     * @param string $mageShipMethod - $order->getShippingMethod();
     * @return string
     */
    public function getShippingMethod($mageShipMethod) {
        $lookup = array(
            'tablerate_bestway' => 'FEDEX',
            'fedex_FEDEX_2_DAY' => 'FEDEX 2DAY',
            'fedex_STANDARD_OVERNIGHT' => 'FEDEXSTDOVER',
            'fedex_PRIORITY_OVERNIGHT' => 'FEDEXPRIOR',
            'fedex_INTERNATIONAL_ECONOMY' => 'FEDEX INTL ECON',
            'fedex_INTERNATIONAL_PRIORITY' => 'FEDEX INTL PRI'
        );
        $mageShipMethod = (empty($mageShipMethod)) ? null : trim($mageShipMethod);
        if (!empty($mageShipMethod) && isset($lookup[$mageShipMethod])) {
            return $lookup[$mageShipMethod];
        }
        /*jinal khakharia changed on 5 august 2015 for IMS-67 : free method name changed*/
        return 'FEDEX NOCHG';
        /*end*/

    }

}
