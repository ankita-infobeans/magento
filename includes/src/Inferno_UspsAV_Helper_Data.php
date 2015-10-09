<?php
/**
 * @category    Inferno
 * @package     Inferno_UspsAV
 * @license     https://mageinferno.com/eula/
 */
class Inferno_UspsAV_Helper_Data
    extends Mage_Core_Helper_Abstract
{
    /**
     * This always returns XML, either a cleaned USPS address or an error response.
     *
     * @param $address
     * @return mixed|string
     */
    public function uspsSubmitRequest($address)
    {
        $xml = Mage::helper('inferno_uspsav')->uspsToXml($address);
        
        $ch = curl_init(Mage::getStoreConfig('inferno_uspsav/usps/gateway_url'));
        curl_setopt($ch, CURLOPT_POST, 1); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, "API=Verify&XML=$xml");
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        
        $result = curl_exec($ch);
        $error = curl_error($ch);
        
        if ($error) {
            $result = "<AddressValidateRequest><Error><![CDATA[$error]]></Error></AddressValidateRequest>";
        }
        
        return $result;
    }
    
    public function uspsToXml($address)
    {
        $account = Mage::getStoreConfig('inferno_uspsav/usps/account_number');
        
        $xml  = "<AddressValidateRequest USERID=\"$account\">"
            . "<Address ID=\"1\">"
            . "<Address1>{$address->address1}</Address1>"
            . "<Address2>{$address->address2}</Address2>"
            . "<City>{$address->city}</City>"
            . "<State>{$address->state}</State>"
            . "<Zip5>{$address->zip}</Zip5>"
            . "<Zip4></Zip4>"
            . "</Address>";
        
        if (isset($address->ship_address2)) {
            $xml .= "<Address ID=\"2\">"
                . "<Address1>{$address->ship_address1}</Address1>"
                . "<Address2>{$address->ship_address2}</Address2>"
                . "<City>{$address->ship_city}</City>"
                . "<State>{$address->ship_state}</State>"
                . "<Zip5>{$address->ship_zip}</Zip5>"
                . "<Zip4></Zip4>"
                . "</Address>";
        }
        
        $xml .= "</AddressValidateRequest>";
        
        return $xml;
    }
    
    public function checkXmlForErrors($xml)
    {
        $error = array();
        
        // Check XML response for general error
        if (isset($xml->Error)) {
            $error = array(
                'error' => -1,
                'message' => Mage::helper('inferno_uspsav')->__('USPS Error: ' . $xml->Error),
                'error_uspsav' => 1,
                'allow_bypass' => Mage::getStoreConfig('inferno_uspsav/general/allow_bypass'),
            );
        }
        
        // Check XML response for errors and store as error message
        if (isset($xml->Address[0]->Error)) {
            $error = array(
                'error' => -1,
                'message' => Mage::helper('inferno_uspsav')
                    ->__('Line 1 of your street address did not pass validation. Please check your address and try again.'),
                'error_uspsav' => 1,
                'allow_bypass' => Mage::getStoreConfig('inferno_uspsav/general/allow_bypass'),
            );
        }
        
        if (isset($xml->Address[1]->Error)) {
            $error = array(
                'error' => -1,
                'message' => Mage::helper('inferno_uspsav')
                    ->__('Line 2 of your street address did not pass validation. Please check your address and try again.'),
                'error_uspsav' => 1,
                'allow_bypass' => Mage::getStoreConfig('inferno_uspsav/general/allow_bypass'),
            );
        }
        
        if (isset($xml->Address->ReturnText)) {
            $xml->Address->ReturnText = str_replace('Default address: ', '', $xml->Address->ReturnText);
            $error = array(
                'error' => -1,
                'message' => Mage::helper('inferno_uspsav')->__((string)$xml->Address->ReturnText),
                'error_uspsav' => 1,
                'allow_bypass' => Mage::getStoreConfig('inferno_uspsav/general/allow_bypass'),
            );
        }
        
        return $error;
    }

    /**
     * Should we sanitize/cleanse this address with USPS?
     *
     * @param $address
     * @return bool
     */
    public function shouldCleanseAddress($address)
    {
        $response = false;

        if (Mage::getStoreConfig('inferno_uspsav/general/enabled')
            && isset($address['country_id'])
            && $address['country_id'] == 'US'
            && empty($address['uspsav_bypass'])
        ) {
            $response = true;
        }

        return $response;
    }

    /**
     * Get the current posted address as an object.
     *
     * @param $address
     * @return object
     */
    public function getAddressAsObject($address)
    {
        // Get region name by id
        $regionModel = Mage::getModel('directory/region')->load($address['region_id']);
        $regionCode = $regionModel->getCode();

        // Store address object to pass to USPS
        $response = (object) array();
        $response->address1  = isset($address['street'][1]) ? str_replace("&"," ",$address['street'][1])     : '';
        $response->address2  = isset($address['street'][0]) ? str_replace("&"," ",$address['street'][0])     : '';
        $response->city      = isset($address['city'])      ? $address['city']          : '';
        $response->state     = isset($regionCode)           ? $regionCode               : '';
        $response->zip       = isset($address['postcode'])  ? $address['postcode']      : '';
        return $response;
    }

    /**
     * Update the post object to the cleansed XML response.
     *
     * @param $cleansedXml
     * @param null $formPrefix
     */
    public function setPostedAddressToCleansedXml($cleansedXml, $formPrefix = null)
    {
        $correctedRegion = Mage::getModel('directory/region')->loadByCode($cleansedXml->Address[0]->State, 'US');

        // Set post to the corrected response from USPS
        $post = Mage::app()->getRequest()->getPost($formPrefix);
        $post['street'][0]  = $cleansedXml->Address[0]->Address2;
        $post['street'][1]  = $cleansedXml->Address[0]->Address1;
        $post['city']       = $cleansedXml->Address[0]->City;
        $post['region_id']  = $correctedRegion->getId();
        $post['postcode']   = $cleansedXml->Address[0]->Zip5;
        $post['postcode']  .= $cleansedXml->Address[0]->Zip4 == '' ? '' : '-' . $cleansedXml->Address[0]->Zip4;

        if ($formPrefix) {
            Mage::app()->getRequest()->setPost($formPrefix, $post);
        } else {
            Mage::app()->getRequest()->setPost($post);
        }
    }
}
