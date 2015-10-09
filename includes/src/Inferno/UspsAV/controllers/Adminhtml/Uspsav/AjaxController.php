<?php
/**
 * @category    Inferno
 * @package     Inferno_UspsAV
 * @license     https://mageinferno.com/eula/
 */
class Inferno_UspsAV_Adminhtml_Uspsav_AjaxController
    extends Mage_Adminhtml_Controller_Action
{
    public function indexAction(){
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/');
        }

        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getParams();

            // Only make this work if USPS Address Verification isn't bypassed
            // and only for addresses within the United States
            if (isset($params['country_id']) && $params['country_id'] == 'US') {
                // Get region name from id
                $regionModel = Mage::getModel('directory/region')->load($params['region_id']);
                $regionCode = $regionModel->getCode();

                // Store address object to pass to USPS
                $address = (object) array();
                $address->address1  = isset($params['street1'])     ? $params['street1']    : '';
                $address->address2  = isset($params['street0'])     ? $params['street0']    : '';
                $address->city      = isset($params['city'])        ? $params['city']       : '';
                $address->state     = isset($regionCode)            ? $regionCode           : '';
                $address->zip       = isset($params['postcode'])    ? $params['postcode']   : '';

                // Pass the address to USPS to verify and store the XML response
                if ($result = Mage::helper('inferno_uspsav')->uspsSubmitRequest($address)) {
                    $xml = new SimpleXMLElement($result);
                    $error = Mage::helper('inferno_uspsav')->checkXmlForErrors($xml);
                }

                // Exit this method, and pass back (alert) error to user
                if (isset($error['error'])) {
                    $response['message'] = $error['message'];
                    $response['error'] = 1;
                }

                // Set post to the USPS XML response
                if (isset($xml)) {
                    $correctedRegionModel = Mage::getModel('directory/region')->loadByCode($xml->Address[0]->State, $params['country_id']);
                    $regionId = $correctedRegionModel->getId();

                    $response['street0']    = $xml->Address[0]->Address2;
                    $response['street1']    = $xml->Address[0]->Address1;
                    $response['city']       = $xml->Address[0]->City;
                    $response['region_id']  = $regionId;
                    $response['postcode']   = $xml->Address[0]->Zip5;
                    $response['postcode']  .= $xml->Address[0]->Zip4 == '' ? '' : '-' . $xml->Address[0]->Zip4;
                    $response['country_id'] = $params['country_id'];
                }
            } else {
                $response['message'] = 'Address verification only functions in the United States.';
                $response['error'] = 1;
            }
        } else {
            $response['message'] = 'Invalid post.';
            $response['error'] = 1;
        }

        if (!isset($response['error'])) {
            $response['message'] = 'Address verified and updated.';
        }

        // Return response in JSON
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }
}
