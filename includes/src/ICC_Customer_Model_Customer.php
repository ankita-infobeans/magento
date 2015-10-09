<?php

class ICC_Customer_Model_Customer extends Mage_Customer_Model_Customer
{

    const XML_PATH_CUSTOMER_TAX_CLASS_SELECTION_METHOD = 'tax/avectra/customer_tax_class_selection_method';

    const CUSTOMER_TAX_CLASS_BY_CUSTOMER_GROUP = 'USE_CUSTOMER_GROUP';
    const CUSTOMER_TAX_CLASS_BY_AVECTRA_EXEMPT = 'USE_AVECTRA_EXEMPT';

    const AVECTRA_TAX_EXEMPT_STATUS_NO = 0;
    const AVECTRA_TAX_EXEMPT_STATUS_YES = 1;

    // These shouldn't be hard-coded, but there's no better way. (Something will have to be hard-coded);
    const CUSTOMER_TAX_CLASS_RETAIL = 3;
    const CUSTOMER_TAX_CLASS_EXEMPT = 5;



    /*
 * Override for password validation
 * Avectra does all the validation so no need for this
 *
 */
    public function validate()
    {
        return true;
    }


    public function createEcodesMasterAccount($login, $password, $confirmPassword = null, $newUser = false)
    {
        Mage::log("In createEcodesMasterAccount");
        $helper = Mage::helper('ecodes');



        $error = $helper->validateLogin($login);
        if (!$error) {
            $error = $helper->validatePassword($password, $login, $this->getFirstname(), $this->getLastname());
            if (!$error) {
                if ($newUser) { //new account
                    //make sure passwords match
                    if ($password != $confirmPassword) {
                        $error = 'Please make sure your passwords match. ERR 902';
                    }
                    if (!$error) {
                        //try to create ICC Connect master user account
                        $icc_connect = Mage::getModel('ecodes/api');
                        if ($icc_connect->hasConnection()) {
                            /*** start. artem. gorilla. ticket 2013012410000204 ***/
                            $result = $icc_connect->createUser($login, $password, $login, $password, $this->getFirstname(), $this->getLastname(), $this->getEmail());
                            if (!$result['success']) 
                            {
                                $error = $result['message'];
                            } 
                            else 
                            {
                                $this->setEcodesMasterUser($login);
                                $this->setEcodesMasterPass($helper->encryptPassword($password));
                                $this->save();
                                $this->processQueuedSubscriptions();
                                return array('success' => true, 'message' => 'Your account has been created successfully');
                            }
                            /*** end. artem. gorilla. ticket 2013012410000204 ***/
                        } else {
                            // queue but let them move on
                            $q = Mage::getModel('gorilla_queue/queue');
                            $q->addToQueue(
                                'ecodes/apiQueue',
                                'processMayHaveMasterUserQueueItem',
                                array('customer_id' => $this->getId(),
                                    'login' => $login,
                                    'password' => $password,
                                    'first' => $this->getFirstname(),
                                    'last' => $this->getLastname(),
                                    'email' => $this->getEmail(),
                                ),
                                'customer_id_' . $this->getId()
                            )
                                ->setShortDescription('Icc Connect server could not be contacted.')->save();

                            return array('success' => true, 'message' => 'We were unable to create your master account. Please continue with your purchase and we will email you at a later time with further instructions.');
                        }
                    }
                } else { //existing account
                    $icc_connect = Mage::getModel('ecodes/api');
                    if ($icc_connect->hasConnection()) {
                        $result = Mage::getModel('ecodes/api')->checkMasterCredentials($login, $password);
                        if (!$result['success']) {
                            $error = $result['message'];
                            return array('success' => false, 'message' => $error);
                        } else {
                            $this->setEcodesMasterUser($login);
                            $this->setEcodesMasterPass($helper->encryptPassword($password));
                            $this->save();
                            $this->processQueuedSubscriptions();
                            return array('success' => true, 'message' => 'Your account has been linked successfully');
                        }
                    } else {

                        $q = Mage::getModel('gorilla_queue/queue');
                        $q->addToQueue(
                            'ecodes/apiQueue',
                            'processMayHaveMasterUserQueueItem',
                            array('customer_id' => $this->getId(),
                                'login' => $login,
                                'password' => $password,
                                'first' => $this->getFirstname(),
                                'last' => $this->getLastname(),
                                'email' => $this->getEmail(),
                            ),
                            'customer_id_' . $this->getId()
                        )
                            ->setShortDescription('Icc Connect server could not be contacted to confirm Master User account.')->save();

                        return array('success' => true, 'message' => 'We were unable to create your master account. Please continue with your purchase and we will email you at a later time with further instructions.');

                    }
                }
            } else {
                // queue but let them move on
                $q = Mage::getModel('gorilla_queue/queue');
                $q->addToQueue(
                    'ecodes/apiQueue',
                    'processMayHaveMasterUserQueueItem',
                    array('customer_id' => $this->getId(),
                        'login' => $login,
                        'password' => $password,
                        'first' => $this->getFirstname(),
                        'last' => $this->getLastname(),
                        'email' => $this->getEmail(),
                    ),
                    'customer_id_' . $this->getId()
                )
                    ->setShortDescription('Icc Connect server could not be contacted.')->save();
            }
        }
        return array('success' => false, 'message' => $error);
    }

    public function processQueuedSubscriptions()
    {
        $q = Mage::getModel('ecodes/apiQueue');
        $q->processNewMasterUser($this->getId());
    }

    /**
     * Interrupt core getTaxClassId().
     * The core method uses the customer's group to determine which tax class to use.
     * This method uses the Avectra Tax Exempt Status attribute to select which Tax Class Id to return.
     *
     * This allows us to decouple tax class from customer group. (If they were coupled, we would have N*M customer
     * groups, where N is the number of tax classes and M is the number of customer groups in use (for non-tax-class
     * purposes)
     *
     * @return int|mixed - tax class id
     */
    public function getTaxClassId()
    {
        $useAvectraTaxExemptStatus = $this->useAvectraTaxExemptStatus();
        if ($useAvectraTaxExemptStatus) {

            // Set to current value if there is one. Just a safety net.
            $taxClassId = ($this->hasData('tax_class_id')) ? $this->getData('tax_class_id') : null;

            /* Use customer's Avectra tax exempt flag to calculate taxes.
             * Since a value is not required for this attribute, we default to using Magento's standard calculation.
             */
            $customerAvectraTaxExemptStatus = $this->getData('tax_exempt_status');
            switch ($customerAvectraTaxExemptStatus) {
                case self::AVECTRA_TAX_EXEMPT_STATUS_NO:
                    $taxClassId = self::CUSTOMER_TAX_CLASS_RETAIL;
                    break;
                case self::AVECTRA_TAX_EXEMPT_STATUS_YES:
                    $taxClassId = self::CUSTOMER_TAX_CLASS_EXEMPT;
                    break;
                default:
                    return parent::getTaxClassId();
            }

            /* Manually set the value for this model and the group model
             * which is called instead of this one in some places.
             */
            $this->setTaxClassId($taxClassId);
            Mage::getModel('customer/group')->setTaxClassId($taxClassId);
            return $taxClassId;
        }

        // Shouldn't reach this unless if statement above is false.
        return parent::getTaxClassId();
    }

    /**
     * Determines whether we should use Avectra's tax calculation based on System>Configuration settings.
     * Independent function since we need to use it from other models.
     *
     * @return bool
     */
    public function useAvectraTaxExemptStatus()
    {
        $taxClassSelectionMethod = Mage::getConfig()->getStoresConfigByPath(self::XML_PATH_CUSTOMER_TAX_CLASS_SELECTION_METHOD);
        $taxClassSelectionMethod = reset($taxClassSelectionMethod); // need first value in array
        return $taxClassSelectionMethod == self::CUSTOMER_TAX_CLASS_BY_AVECTRA_EXEMPT;
    }
}
