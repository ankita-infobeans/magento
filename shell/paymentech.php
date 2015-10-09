<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Shell
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

require_once 'abstract.php';

/**
 * Magento Compiler Shell Script
 *
 * @category    Mage
 * @package     Mage_Shell
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Shell_Compiler extends Mage_Shell_Abstract
{
    /**
     * Run script
     *
     */
    public function run()
    {
        if (!$this->getArg('amount') && !$this->getArg('void')) {
            echo $this->usageHelp();
            return;
        }

        // Build data object to send for transaction
        $data = array(
            'amount' => $this->getArg('amount'),
            'cc_number' => '4788250000028291',      // VI          // null     6011210307939594
            //'cc_number' => '5111005111051128',      // MC
            //'cc_number' => '6011000995500000',      // DI     6011016011016011
            //'cc_number' => '371144371144376',       // AX    4 digit cvv
            'cc_exp_year' => '2016',
            'cc_exp_month' => '04',
            'cc_type' => 'VI',
            'cc_cid' => '123',
            //'chase_paymentech_customer_ref_num' => 'REF NUMBER HERE',
            'order' => new Varien_Object(array(
                'increment_id' =>  'TESTORDERNO_VI_08',
                'billing_address' => new Varien_Object(array(
                    'firstname' => 'Collin',
                    'lastname' => 'Bourdage',
                    'street' => array('123 Faked Street'),
                    'city' => 'Chicago',
                    'region' => 'Illinois',
                    'postcode' => '60607',
                    'country_id' => 'US',
                    'telephone' => '1231231234',
                ))
            ))
        );

        $payment = new Varien_Object();
        $payment->setData($data);

        $transId = '4F870B03AF8F90782B2F5C68B92354B338D8538E';
//die('dead');
        if ($this->getArg('auth')) {
            $payment->setData('chase_paymentech_trans_type', 'A');
            Mage::getModel('chasepaymentech/profile')->createOrderTransaction($payment, false);
        } else if ($this->getArg('auth-capture')) {
            $payment->setData('chase_paymentech_trans_type', 'AC');
            Mage::getModel('chasepaymentech/profile')->createOrderTransaction($payment, false);
        } else if ($this->getArg('capture')) {
            $payment->setData('trans_id', $transId);
            Mage::getModel('chasepaymentech/profile')->createMarkForCaptureTransaction($payment, false);
        } else if ($this->getArg('refund')) {
            $payment->setData('chase_paymentech_trans_type', 'R');
            $payment->setData('trans_id', $transId);
            Mage::getModel('chasepaymentech/profile')->createOrderTransaction($payment, false);
        } else if ($this->getArg('void')) {
            $payment->setData('trans_id', '2');
            $payment->setData('parent_transaction_id', $transId);
            Mage::getModel('chasepaymentech/profile')->createReversalTransaction($payment, false);
        } else {
            echo $this->usageHelp();
            return;
        }

        Mage::log(Mage::app()->getLocale()->date(now())->toString('YYYY-MM-dd hh:mm:ss') . "\n");
    }

    /**
     * Retrieve Usage Help Message
     *
     * @return string
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f orderItemCapture.php -- [options]

  --auth --amount <amount>              Processes as authorization only
  --auth-capture --amount <amount>      Processes as authorization and capture
  --capture --amount <amount>           Processes as capture only
  --refund                              Processes as capture only
  --void                                Processes as capture only

  help              This help

USAGE;
    }
}

$shell = new Mage_Shell_Compiler();
$shell->run();
