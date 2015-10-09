<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ICC_Premiumaccess_Block_Adminhtml_Premiumuser extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {     
    
    $this->_blockGroup       = 'icc_premiumaccess';
    $this->_controller       = 'adminhtml_premiumuser';
    $this->_headerText       = Mage::helper('icc_premiumaccess')->__('Customer User PremiumACCESS Manager');
    $this->_addButtonLabel   = Mage::helper('icc_premiumaccess')->__('Gift PremiumACCESS To Customer ');
    $this->_backButtonLabel  = Mage::helper('icc_premiumaccess')->__('Back');
    parent::__construct();
    $this->removeButton('add');
    $this->_addBackButton();   
    $this->_addNewButton();    
  }
  
  protected function _addBackButton()
  {
      
        $url_back_to_premium_access = $this->getUrl('*/premiumcustomers/index'); 
        $this->_addButton('back', array(
            'label'     => $this->getBackButtonLabel(),
            'onclick'   => 'setLocation(\'' . $url_back_to_premium_access .'\')',
            'class'     => 'back',
        ));
  }
  
  protected  function _addNewButton(){
       $this->_addButton('add', array(
            'label'     => $this->getAddButtonLabel(),
            'onclick'   => 'setLocation(\'' . $this->getCreateUrl() .'\')',
            'class'     => 'add',
        ));
  }
  
}