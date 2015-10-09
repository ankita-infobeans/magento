<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ICC_Premiumaccess_Block_Adminhtml_Customer_Edit_Tabs extends Mage_Adminhtml_Block_Customer_Edit_Tabs
{
    public function __construct()
    {
        parent::__construct();
    }
    
    protected function _beforeToHtml()
    {
      
       if (Mage::registry('current_customer')->getId()) {
         $this->addTabAfter('purchase_premium_access', array(
                        'label'     => Mage::helper('customer')->__('Purchase PremiumACCESS'),
                        'content'   => $this->getLayout()->createBlock('icc_premiumaccess/adminhtml_customer_edit_tab_purchase')->toHtml(),              
         ), 'wishlist');  
           
         $this->addTabAfter('gift_shared_premium_access', array(
                        'label'     => Mage::helper('customer')->__('Gift PremiumACCESS'),
                        'content'   => $this->getLayout()->createBlock('icc_premiumaccess/adminhtml_customer_edit_tab_gift')->toHtml(),
         ), 'purchase_premium_access');
       }
       
        /*
        $this->addTab('primumgift', array(
                        'label'     => Mage::helper('customer')->__('Payment Mode'),
                        'content'   => $this->getLayout()->createBlock('icc_premiumaccess/adminhtml_customer_edit_tab_gift')->toHtml(),
                     ));
         */
         parent::_beforeToHtml();
        
    }
    
}