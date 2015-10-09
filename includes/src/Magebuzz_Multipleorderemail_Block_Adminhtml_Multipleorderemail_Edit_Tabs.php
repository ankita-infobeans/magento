<?php
/**
 * @category    Magebuzz
 * @package     Magebuzz_Multipleorderemail
 */
class Magebuzz_Multipleorderemail_Block_Adminhtml_Multipleorderemail_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('multipleorderemail_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('multipleorderemail')->__('Order Email Block Information'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array (
            'label'     => Mage::helper('multipleorderemail')->__('Information'),
            'title'     => Mage::helper('multipleorderemail')->__('Information'),
            'content'   => $this->getLayout()->createBlock('multipleorderemail/adminhtml_multipleorderemail_edit_tab_main')->toHtml(),
        ));  

        $this->addTab('coditions', array (
            'label'     => Mage::helper('multipleorderemail')->__('Conditions'),
            'title'     => Mage::helper('multipleorderemail')->__('Conditions'),
            'content'   => $this->getLayout()->createBlock('multipleorderemail/adminhtml_multipleorderemail_edit_tab_categories')->toHtml(),
        ));

        $this->addTab('email_template', array (
            'label'     => Mage::helper('multipleorderemail')->__('Order Email Block'),
            'title'     => Mage::helper('multipleorderemail')->__('Order Email Block'),
            'content'   => $this->getLayout()->createBlock('multipleorderemail/adminhtml_multipleorderemail_edit_tab_template')->toHtml(),
        ));
        return parent::_beforeToHtml();
    }
}