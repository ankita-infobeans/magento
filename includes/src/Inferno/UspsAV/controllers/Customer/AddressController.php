<?php
/**
 * @category    Inferno
 * @package     Inferno_UspsAV
 * @license     https://mageinferno.com/eula/
 */
require_once Mage::getModuleDir('controllers', 'Mage_Customer') . DS . 'AddressController.php';

class Inferno_UspsAV_Customer_AddressController
    extends Mage_Customer_AddressController
{
    public function formPostAction()
    {
        if (! $this->_validateFormKey()) {
            return $this->_redirect('*/*/');
        }
        
        // Save data
        if ($this->getRequest()->isPost()) {
            $customer = $this->_getSession()->getCustomer();
            $customerAddress = Mage::getModel('customer/address');
            
            if ($customerAddressId = $this->getRequest()->getParam('id')) {
                $existingAddress = $customer->getAddressById($customerAddressId);

                if ($existingAddress->getId() && $existingAddress->getCustomerId() == $customer->getId()) {
                    $customerAddress->setId($existingAddress->getId());
                }
            }
            
            $post = $this->getRequest()->getPost();

            // Check to see if we should cleanse this address
            if (Mage::helper('inferno_uspsav')->shouldCleanseAddress($post)) {
                $address = Mage::helper('inferno_uspsav')->getAddressAsObject($post);
                
                // Pass the address to USPS to verify and store the XML response
                if ($result = Mage::helper('inferno_uspsav')->uspsSubmitRequest($address)) {
                    $cleansedXml = new SimpleXMLElement($result);
                    $error = Mage::helper('inferno_uspsav')->checkXmlForErrors($cleansedXml);

                    if (isset($cleansedXml->Address)) {
                        $correctedRegion =
                            Mage::getModel('directory/region')->loadByCode($cleansedXml->Address[0]->State, 'US');

                        if (! $correctedRegion) {
                            $error = array(
                                'error' => -1,
                                'message' => Mage::helper('inferno_uspsav')
                                    ->__('USPS Error: The corrected region doesn\'t exist in store configuration.'),
                                'error_uspsav' => 1,
                                'allow_bypass' => Mage::getStoreConfig('inferno_uspsav/general/allow_bypass'),
                            );
                        } else {
                            Mage::helper('inferno_uspsav')->setPostedAddressToCleansedXml($cleansedXml);
                        }
                    }
                }

                // Exit this method, and pass back (alert) error to user
                if (isset($error['error'])) {
                    $this->_getSession()->addError($error['message']);
                    $post['uspsav_failed'] = 1;
                    $this->_getSession()->setAddressFormData($post);

                    if ($customerAddress->getId()) {
                        return $this->_redirectError(Mage::getUrl('*/*/edit', array('id' =>
                            $customerAddress->getId())));
                    } else {
                        return $this->_redirect('*/*/new/');
                    }
                }
            }
        }

        return parent::formPostAction();
    }
}
