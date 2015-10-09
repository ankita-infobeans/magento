<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition End User License Agreement
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magento.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Checkout
 * @copyright Copyright (c) 2006-2014 X.commerce, Inc. (http://www.magento.com)
 * @license http://www.magento.com/license/enterprise-edition
 */

/**
 * Onepage checkout block
 *
 * @category   Mage
 * @package    Mage_Checkout
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class ICC_Volumelicense_Block_Checkout_Onepage extends Mage_Checkout_Block_Onepage_Abstract
{
    /**
     * Get 'one step checkout' step data
     *
     * @return array
     */
    public function getSteps()
    {
        $steps = array();
        $stepCodes = $this->_getStepCodes();
	$login_flag = 0;
        if ($this->isCustomerLoggedIn()) {
            //$stepCodes = array_diff($stepCodes, array('login'));
            $login_flag = 1;
        }
        /* Add Custom Code Start*/
       
        $requiedVolumeStep = false;
        foreach ($this->getQuote()->getAllVisibleItems() as $item) {
            if ($item->getProduct()->getData('volume_license') && $item->getProduct()->getData('volume_license') == 1 && $item->getQty() > 1) {
                $requiedVolumeStep = true;
                break;
            }
        }
        if ($requiedVolumeStep) {
	    if($flag == 1) {
            $stepCodes = array('login','billing', 'shipping', 'shipping_method', 'volumelicense', 'payment', 'review');
            }
            else {
            $stepCodes = array('billing', 'shipping', 'shipping_method', 'volumelicense', 'payment', 'review');
            }
            $this->getCheckout()->setData('has_volumelicense_step', true);			
        }			
        else {
            //$stepCodes = array('billing', 'shipping', 'shipping_method', 'payment', 'review');
            //$this->getCheckout()->setData('has_volumelicense_step', false);			
        }
        //echo "<pre>";print_r($stepCodes);die;
        /* Add Custom Code End*/
        foreach ($stepCodes as $step) {
            $steps[$step] = $this->getCheckout()->getStepData($step);
        }
        return $steps;
    }

    /**
     * Get active step
     *
     * @return string
     */
    public function getActiveStep()
    {
        return $this->isCustomerLoggedIn() ? 'billing' : 'login';
    }
}
