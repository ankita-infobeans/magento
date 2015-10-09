<?php
/**
 * @category    Inferno
 * @package     Inferno_UspsAV
 * @license     https://mageinferno.com/eula/
 */
require_once Mage::getModuleDir('controllers', 'Mage_Checkout') . DS . 'OnepageController.php';

class Inferno_UspsAV_Checkout_OnepageController
    extends Mage_Checkout_OnepageController
{
    protected $_addressType;
    protected $_error = array();

    /**
     * Perform address validation on checkout billing address
     */
    public function saveBillingAction()
    {
        $this->_addressType = 'billing';

        if ($this->shouldCleansePostedAddress()) {
            $this->cleanseAddress();
        }

        if ($this->_error) {
            // Return response in JSON
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($this->_error));
        } else {
            parent::saveBillingAction();
        }
    }

    /**
     * Perform address validation on checkout shipping address
     */
    public function saveShippingAction()
    {
        $this->_addressType = 'shipping';

        if ($this->shouldCleansePostedAddress()) {
            $this->cleanseAddress();
        }

        if ($this->_error) {
            // Return response in JSON
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($this->_error));
        } else {
            parent::saveShippingAction();
        }
    }

    /**
     * Here's where we cleanse the address.
     */
    public function cleanseAddress()
    {
        $this->preprocessIfExistingAddress();
        $result = Mage::helper('inferno_uspsav')->uspsSubmitRequest($this->getPostedAddressAsObject());
        $cleansedXml = new SimpleXMLElement($result); // Ensure this is a proper SimpleXMLElement
        $this->_error = Mage::helper('inferno_uspsav')->checkXmlForErrors($cleansedXml);

        if (! is_object($cleansedXml) || isset($this->_error['error'])) {
            return;
        }

        // Look up the state/region by abbreviation (always U.S.)
        if (isset($cleansedXml->Address)) {
            $correctedRegion = Mage::getModel('directory/region')->loadByCode($cleansedXml->Address[0]->State, 'US');

            if (! $correctedRegion) {
                $this->_error = array(
                    'error' => -1,
                    'message' => Mage::helper('inferno_uspsav')
                        ->__('USPS Error: The corrected region doesn\'t exist in store configuration.'),
                    'error_uspsav' => 1
                );
            } else {
                Mage::helper('inferno_uspsav')->setPostedAddressToCleansedXml($cleansedXml, $this->_addressType);
                $this->postprocessIfExistingAddress();
            }
        } else {
            Mage::log('USPS Error: The API key is not setup for production access.', Zend_Log::ERR);
            $this->_error = array(
                'error' => -1,
                'message' => Mage::helper('inferno_uspsav')
                    ->__('USPS Error: The API key is not setup for production access.'),
                'error_uspsav' => 1
            );
        }
    }

    /**
     * Should we sanitize/cleanse this address with USPS?
     *
     * @return bool
     */
    public function shouldCleansePostedAddress()
    {
        $response = false;

        $this->preprocessIfExistingAddress();

        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost($this->_addressType, array());
            $response = Mage::helper('inferno_uspsav')->shouldCleanseAddress($post);
        }

        return $response;
    }

    /**
     * Get the current posted address as an object.
     *
     * @return object
     */
    private function getPostedAddressAsObject()
    {
        $response = false;

        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost($this->_addressType, array());
            $response = Mage::helper('inferno_uspsav')->getAddressAsObject($post);
        }

        return $response;
    }

    /**
     * An existing address in the database has been cleansed. Let's save the address book record and update post data.
     */
    public function preprocessIfExistingAddress()
    {
        $post = $this->getRequest()->getPost($this->_addressType, array());
        $customer = Mage::getModel('customer/session')->getCustomer();

        // Check if an existing address has been posted (from user's address book)
        if ($existingAddressId = $this->getRequest()->getPost($this->_addressType . '_address_id')) {
            $existingAddress = $customer->getAddressById($existingAddressId);

            // Address book record match against current customer
            if ($existingAddress->getId() && $existingAddress->getCustomerId() == $customer->getId()) {
                // Update post object, otherwise address will not be updated

                $post['street']     = $existingAddress->getStreet();
                $post['city']       = $existingAddress->getCity();
                $post['region_id']  = $existingAddress->getRegionId();
                $post['postcode']   = $existingAddress->getPostcode();
                $post['country_id'] = $existingAddress->getCountryId();
            }
        }

        $this->getRequest()->setPost($this->_addressType, $post);
    }

    /**
     * An existing address in the database has been cleansed. Let's save the address book record and update post data.
     */
    public function postprocessIfExistingAddress()
    {
        $post = $this->getRequest()->getPost($this->_addressType, array());
        $customer = Mage::getModel('customer/session')->getCustomer();

        // Check if an existing address has been posted (from user's address book)
        if ($existingAddressId = $this->getRequest()->getPost($this->_addressType . '_address_id')) {
            $existingAddress = $customer->getAddressById($existingAddressId);

            // Address book record match against current customer
            if ($existingAddress->getId() && $existingAddress->getCustomerId() == $customer->getId()) {
                // Set existing address to cleansed data from USPS and save
                $existingAddress->setId($existingAddress->getId())
                    ->setStreet($post['street'])
                    ->setCity($post['city'])
                    ->setRegionId($post['region_id'])
                    ->setPostcode($post['postcode']);

                $existingAddress->save();
            }
        }
    }
}

