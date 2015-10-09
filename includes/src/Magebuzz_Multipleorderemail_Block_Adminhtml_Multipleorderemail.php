<?php  
/**
 * @category    Magebuzz
 * @package     Magebuzz_Multipleorderemail
 */
class Magebuzz_Multipleorderemail_Block_Adminhtml_Multipleorderemail extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_multipleorderemail';
        $this->_blockGroup = 'multipleorderemail';
        $this->_headerText = Mage::helper('multipleorderemail')->__('Order Email Block Manager');
        $this->_addButtonLabel = Mage::helper('multipleorderemail')->__('Add Order Email Block');
        parent::__construct();
    }
}