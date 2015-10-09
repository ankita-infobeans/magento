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
 * @package     Mage_Sales
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Sales order history block
 *
 * @category Mage
 * @package Mage_Sales
 * @author Magento Core Team <core@magentocommerce.com>
 */
class Gorilla_Greatplains_Block_Offlineorder_History extends Mage_Core_Block_Template {

    public function __construct() {

        parent::__construct();
        // $this->setTemplate('sales/order/history.phtml');
        $this->setTemplate('greatplains/offlineorder/history.phtml');
        $orders = Mage::getResourceModel('sales/order_collection')
                        ->addFieldToSelect('*')
                        ->addFieldToFilter('customer_id', Mage::getSingleton('customer/session')->getCustomer()->getId())
                        ->addFieldToFilter('state', array('in' => Mage::getSingleton('sales/order_config')->getVisibleOnFrontStates()))
                        ->setOrder('created_at', 'desc');
        $this->setOrders($orders);
        Mage::app()->getFrontController()
                    ->getAction()
                    ->getLayout()
                    ->getBlock('root')
                    ->setHeaderTitle(Mage::helper('sales')->__('My Orders'));
    }

    public function getOfflineOrders() {
        $offlineordermodel = Mage::getModel("greatplains/offlineorder");
        $offlineorders = $offlineordermodel->getOfflineOrder();
        return $offlineorders;
    }

    protected function _prepareLayout() {
        parent::_prepareLayout();
        // return $this;

        $pager = $this->getLayout()->createBlock('page/html_pager', 'sales.order.history.pager')->setCollection($this->getOrders());
        $this->setChild('pager', $pager);
        $this->getOrders()->load();
        return $this;
    }

    public function getPagerHtml() {
        return $this;
        return $this->getChildHtml('pager');
    }

    public function getViewUrl($offlineorderid) {
        $offlineorderid = trim($offlineorderid);
        return $this->getUrl('greatplains/offlineorder/view', array('offlineorder_id' => $offlineorderid));
    }

    public function getBackUrl() {
        return $this;
        return $this->getUrl('customer/account/');
    }

}