<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ICC_Premiumaccess_Block_Adminhtml_Premiumreport extends Mage_Adminhtml_Block_Widget_Grid_Container
{
  public function __construct()
  {     
    
    $this->_blockGroup      = 'icc_premiumaccess';
    $this->_controller      = 'adminhtml_premiumreport';
    $this->_headerText      = Mage::helper('icc_premiumaccess')->__('premiumACCESS Report');
    //$this->_addButtonLabel  = Mage::helper('ecodes')->__('Add Item');
    parent::__construct();
    $this->_removeButton('add');
  }
}