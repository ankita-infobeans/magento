<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ICC_Premiumaccess_Block_Adminhtml_Customer_Edit_Tab_Gift extends Mage_Adminhtml_Block_Widget_Form
{
    public function __construct()
    {
        parent::__construct();     
        $this->setTemplate('premiumaccess/gift.phtml');
    }
        
    
    protected function _prepareLayout()
    {
        $this->setChild('grid',
            $this->getLayout()->createBlock('icc_premiumaccess/adminhtml_customer_edit_tab_gift_grid','gift.grid')
        );
        return parent::_prepareLayout();
    }
}