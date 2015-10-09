<?php
require_once 'Mage/Customer/controllers/AccountController.php';
class Gorilla_Greatplains_OfflineorderController extends Mage_Customer_AccountController 
{
    public $offlineid;
    private $gp;

    public function viewAction() {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->getLayout()->getBlock('head')->setTitle($this->__('My Orders'));
        if ($block = $this->getLayout()->getBlock('customer.account.link.back')) {
            $block->setRefererUrl($this->_getRefererUrl());
        }
        $this->renderLayout();
/*
        $this -> loadLayout();
        $this -> _initLayoutMessages('catalog/session');

        /*$block = $this -> 
        getLayout() -> 
        createBlock('Gorilla_Greatplains_Block_Offlineorder_View', 'offlineorder_view', 
        array('template' => 'greatplains/offlineorder/info.phtml'));

        $this -> getLayout() -> getBlock('content') -> append($block);
*/
        //$this -> renderLayout();

    }

    public function historyAction() {

        $this -> loadLayout();
        $this -> _initLayoutMessages('customer/session');
        $this->getLayout()->getBlock('customer_account_navigation')->setActive('sales/order/history');
        $this->getLayout()->getBlock('head')->setTitle($this->__('My Orders'));
        $this -> renderLayout();

    }

    public function indexAction() {

        $this -> loadLayout();
        $this -> _initLayoutMessages('catalog/session');
        $this -> renderLayout();

    }

    public function infoAction() {

        $this -> loadLayout();
        $this -> _initLayoutMessages('catalog/session');
        $this -> renderLayout();

    }

}
