<?php
/**
 * @copyright  Amasty (http://www.amasty.com)
 */   
class Amasty_Acart_Block_Adminhtml_Blist extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_blist';
        $this->_blockGroup = 'amacart';
        $this->_headerText     = Mage::helper('amacart')->__('Black List');
        parent::__construct();
    }
}