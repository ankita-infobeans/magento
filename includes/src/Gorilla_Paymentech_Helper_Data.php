<?php

class Gorilla_Paymentech_Helper_Data extends Mage_Paygate_Helper_Data {

    public function isDebug() {
        return $this->_getConfig('debug');
    }

    public function Log($message, $level = Zend_Log::DEBUG) {
        
        if ($this->isDebug() || $level < 5) {// log only if debug is enabled or is a warn or below error
            Mage::Log($message, $level, 'paymentech-log.log');
        }
    }

    public function getSoap() {

        return new Gorilla_Paymentech_Model_Profile_Soap();
    }

    /**
     * Get the URL to the Auth.net WSDL
     * 
     * @return string $wsdl_url
     */
    public function getWsdlUrl() {
        if ($this->isTestMode()) {
            return $this->_getConfig('test_gateway_wsdl');
        } else {
            return $this->_getConfig('gateway_wsdl');
        }
    }

    /**
     * Get the URL to the Auth.net Gateway API
     * 
     * @return string $gateway_url
     */
    public function getGatewayUrl() {
        if ($this->isTestMode()) {
            return $this->_getConfig('test_gateway_url');
        } else {
            return $this->_getConfig('gateway_url');
        }
    }

    /**
     * Get the transaction key
     * 
     * @return string $transaction_key
     */
    public function getTransactionKey() {
        if ($this->isTestMode()) {
            return $this->_getConfig('test_trans_key');
        } else {
            return $this->_getConfig('trans_key');
        }
    }

    public function getApiPassword() {
        if ($this->isTestMode()) {
            return $this->_getConfig('test_password');
        } else {
            return $this->_getConfig('password');
        }
    }

    /**
     * Get the API login
     * 
     * @return string $api_login
     */
    public function getApiLogin() {
        if ($this->isTestMode()) {
            return $this->_getConfig('test_login');
        } else {
            return $this->_getConfig('login');
        }
    }

    public function getMerchantId() {
        if ($this->isTestMode()) {
            return $this->_getConfig('test_merchant_id');
        } else {
            return $this->_getConfig('merchant_id');
        }
    }

    /**
     * Are we in test mode?
     * 
     * @return bool
     */
    public function isTestMode() {
        return $this->_getConfig('test');
    }
    
    public function useCents() {
    	$cents = $this->_getConfig('cents');
    	if($cents == 0)
    	{
    		return true;
    	}
    	return false;
    	//Mage::Log("cents is ".$cents);
    	////rn $cents; 
    	
    }

    /**
     * Get config settings for the gateway
     * 
     * @param string $key
     * @return mixed
     */
    protected function _getConfig($key) {
        return Mage::getStoreConfig('payment/paymentech/' . $key); // Mage::getSingleton('paymentech/gateway')->getConfigData($key);
    }

    public function getCustomerAddCreditCardUrl() {
        return $this->_getUrl('paymentech/account/add');
    }

    function id_to_twoletter($id) {
        $state = array();
        $state[1] = 'AL';
        $state[2] = 'AK';
        $state[3] = 'AS';
        $state[4] = 'AZ';
        $state[5] = 'AR';

        $state[12] = 'CA';
        $state[13] = 'CO';
        $state[14] = 'CT';
        $state[15] = 'DE';
        $state[16] = 'DC';
        $state[17] = 'FM';
        $state[18] = 'FL';
        $state[19] = 'GA';
        $state[20] = 'GU';
        $state[21] = 'HI';
        $state[22] = 'ID';
        $state[23] = 'IL';
        $state[24] = 'IN';
        $state[25] = 'IA';
        $state[26] = 'KS';
        $state[27] = 'KY';
        $state[28] = 'LA';
        $state[29] = 'ME';
        $state[30] = 'MH';
        $state[31] = 'MD';
        $state[32] = 'MA';
        $state[33] = 'MI';
        $state[34] = 'MN';
        $state[35] = 'MS';
        $state[36] = 'MO';
        $state[37] = 'MT';
        $state[38] = 'NE';
        $state[39] = 'NV';
        $state[40] = 'NH';
        $state[41] = 'NJ';
        $state[42] = 'NM';
        $state[43] = 'NY';
        $state[44] = 'NC';
        $state[45] = 'ND';
        $state[46] = 'MP';
        $state[47] = 'OH';
        $state[48] = 'OK';
        $state[49] = 'OR';
        $state[50] = 'PW';
        $state[51] = 'PA';
        $state[52] = 'PR';
        $state[53] = 'RI';
        $state[54] = 'SC';
        $state[55] = 'SD';
        $state[56] = 'TN';
        $state[57] = 'TX';
        $state[58] = 'UT';
        $state[59] = 'VT';
        $state[60] = 'VI';
        $state[61] = 'VA';
        $state[62] = 'WA';
        $state[63] = 'WV';
        $state[64] = 'WI';
        $state[65] = 'WY';

        // Canadian Provinces
        // edited 12-5-07
        $state[66] = 'AB';
        $state[67] = 'BC';
        $state[68] = 'MB';
        $state[70] = 'NB';
        $state[69] = 'NL';

        $state[72] = 'NT';
        $state[71] = 'NS';
        $state[73] = 'NU';
        $state[74] = 'ON';
        $state[75] = 'PE';
        $state[76] = 'QC';
        $state[77] = 'SK';
        $state[78] = 'YT';

        if(isset($state[$id]))
            return $state[$id];
        else
            return $id;
        
    }

    function state_to_twoletter($state_name) {

        $state = array();
        $state['ALABAMA'] = 'AL';
        $state['ALASKA'] = 'AK';
        $state['AMERICAN SAMOA'] = 'AS';
        $state['ARIZONA'] = 'AZ';
        $state['ARKANSAS'] = 'AR';
        $state['CALIFORNIA'] = 'CA';
        $state['COLORADO'] = 'CO';
        $state['CONNECTICUT'] = 'CT';
        $state['DELAWARE'] = 'DE';
        $state['DISTRICT OF COLUMBIA'] = 'DC';
        $state['FEDERATED STATES OF MICRONESIA'] = 'FM';
        $state['FLORIDA'] = 'FL';
        $state['GEORGIA'] = 'GA';
        $state['GUAM'] = 'GU';
        $state['HAWAII'] = 'HI';
        $state['IDAHO'] = 'ID';
        $state['ILLINOIS'] = 'IL';
        $state['INDIANA'] = 'IN';
        $state['IOWA'] = 'IA';
        $state['KANSAS'] = 'KS';
        $state['KENTUCKY'] = 'KY';
        $state['LOUISIANA'] = 'LA';
        $state['MAINE'] = 'ME';
        $state['MARSHALL ISLANDS'] = 'MH';
        $state['MARYLAND'] = 'MD';
        $state['MASSACHUSETTS'] = 'MA';
        $state['MICHIGAN'] = 'MI';
        $state['MINNESOTA'] = 'MN';
        $state['MISSISSIPPI'] = 'MS';
        $state['MISSOURI'] = 'MO';
        $state['MONTANA'] = 'MT';
        $state['NEBRASKA'] = 'NE';
        $state['NEVADA'] = 'NV';
        $state['NEW HAMPSHIRE'] = 'NH';
        $state['NEW JERSEY'] = 'NJ';
        $state['NEW MEXICO'] = 'NM';
        $state['NEW YORK'] = 'NY';
        $state['NORTH CAROLINA'] = 'NC';
        $state['NORTH DAKOTA'] = 'ND';
        $state['NORTHERN MARIANA ISLANDS'] = 'MP';
        $state['OHIO'] = 'OH';
        $state['OKLAHOMA'] = 'OK';
        $state['OREGON'] = 'OR';
        $state['PALAU'] = 'PW';
        $state['PENNSYLVANIA'] = 'PA';
        $state['PUERTO RICO'] = 'PR';
        $state['RHODE ISLAND'] = 'RI';
        $state['SOUTH CAROLINA'] = 'SC';
        $state['SOUTH DAKOTA'] = 'SD';
        $state['TENNESSEE'] = 'TN';
        $state['TEXAS'] = 'TX';
        $state['UTAH'] = 'UT';
        $state['VERMONT'] = 'VT';
        $state['VIRGIN ISLANDS'] = 'VI';
        $state['VIRGINIA'] = 'VA';
        $state['WASHINGTON'] = 'WA';
        $state['WEST VIRGINIA'] = 'WV';
        $state['WISCONSIN'] = 'WI';
        $state['WYOMING'] = 'WY';

        // Canadian Provinces
        // edited 12-5-07
        $state['ALBERTA'] = 'AB';
        $state['BRITISH COLUMBIA'] = 'BC';
        $state['MANITOBA'] = 'MB';
        $state['NEW BRUNSWICK'] = 'NB';
        $state['LABRADOR'] = 'NL';
        $state['NEWFOUNDLAND'] = 'NL';
        $state['NORTHWEST TERRITORIES'] = 'NT';
        $state['NOVA SCOTIA'] = 'NS';
        $state['NUNAVUT'] = 'NU';
        $state['ONTARIO'] = 'ON';
        $state['PRINCE EDWARD ISLAND'] = 'PE';
        $state['QUEBEC'] = 'QC';
        $state['SASKATCHEWAN'] = 'SK';
        $state['YUKON'] = 'YT';
        
        if(isset($state[strtoupper($state_name)]))
            return $state[strtoupper($state_name)];
        else
            return $state_name;
    }

}