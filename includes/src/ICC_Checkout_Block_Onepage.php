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
 * @package     Mage_Checkout
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Onepage checkout block
 *
 * @category   Mage
 * @package    Mage_Checkout
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class ICC_Checkout_Block_Onepage extends Mage_Checkout_Block_Onepage {

    public function getSteps() {
        $steps = array();
        if (!$this->isCustomerLoggedIn()) {
            $steps['login'] = $this->getCheckout()->getStepData('login');
        }
        $premiumAccessStep = false;
        $option_ids = Mage::helper('icc_premiumaccess')->getPremiumAccessType();
        foreach ($this->getQuote()->getAllItems() as $item) {
            if (in_array(Mage::getModel('catalog/product')->load($item->getProductId())->getData('item_type'), $option_ids)) {
                $premiumAccessStep = true; /* Change it to True */
                break;
            }
        }
        $requiedVolumeStep = false;
        foreach ($this->getQuote()->getAllItems() as $item) {
            if ($item->getProductType() == 'bundle') {
                $allBundleItem[$item->getId()] = $item->getQty();
            } else {

                if ($item->getParentItemId() != null) {
                    if (array_key_exists($item->getParentItemId(), $allBundleItem)) {
                        $iterateCounter = $allBundleItem[$item->getParentItemId()] * $item->getQty();
                    }
                } else {
                    $iterateCounter = $item->getQty();
                }
            }
            if (Mage::getModel('catalog/product')->load($item->getProductId())->getData('volume_license') && Mage::getModel('catalog/product')->load($item->getProductId())->getVolumeLicense() == 1 && $iterateCounter > 1) {
                $requiedVolumeStep = true;
                break;
            }
        }

        if ($premiumAccessStep && $requiedVolumeStep) {
            $stepCodes = array('billing', 'shipping', 'shipping_method', 'premiumaccess', 'volumelicense', 'payment', 'review');
            $this->getCheckout()->setData('has_premiumaccess_step', true);
            $this->getCheckout()->setData('has_volumelicense_step', true);
        } elseif ($premiumAccessStep && !$requiedVolumeStep) {
            $stepCodes = array('billing', 'shipping', 'shipping_method', 'premiumaccess', 'payment', 'review');
            $this->getCheckout()->setData('has_premiumaccess_step', true);
            $this->getCheckout()->setData('has_volumelicense_step', false);
        } elseif (!$premiumAccessStep && $requiedVolumeStep) {
            // echo "33";
            $stepCodes = array('billing', 'shipping', 'shipping_method', 'volumelicense', 'payment', 'review');
            $this->getCheckout()->setData('has_premiumaccess_step', false);
            $this->getCheckout()->setData('has_volumelicense_step', true);
        } else {
            $stepCodes = array('billing', 'shipping', 'shipping_method', 'payment', 'review');
            $this->getCheckout()->setData('has_premiumaccess_step', false);
            $this->getCheckout()->setData('has_volumelicense_step', false);
        }
        //echo "<pre>";print_r($stepCodes);
        foreach ($stepCodes as $step) {

            $steps[$step] = $this->getCheckout()->getStepData($step);
        }

        return $steps;
    }

}
