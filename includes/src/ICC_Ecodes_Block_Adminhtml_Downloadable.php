<?php

class ICC_Ecodes_Block_Adminhtml_Downloadable extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {

        $this->_blockGroup      = 'ecodes';
        $this->_controller      = 'adminhtml_downloadable';
        $this->_headerText      = Mage::helper('ecodes')->__('Manage Serial Numbers for Downloadable eCodes');

        parent::__construct();

        /* $this->_removeButton('add');
        $this->addButton('new', array(
        'label'     => Mage::helper('ecodes')->__('Add New Serial Numbers'),
        'onclick'   => 'setLocation(\'' . $this->getNewUrl() .'\')',
        'class'     => 'add',
        )); */

//        $this->_addButtonLabel = Mage::helper('ecodes')->__('Add New Serial Numbers');
//        $this->_addButtonLabel = Mage::helper('ecodes')->__('');
        $this->_removeButton('add');
        $this->addButton('new', array(
            'label'     => Mage::helper('ecodes')->__('Upload CSV of New Serial Numbers'),
            'onclick'   => 'setLocation(\'' . $this->getUploadCsvUrl() .'\')',
            'class'     => 'add',
        ));

    }

    public function getNewUrl()
    {
        return $this->getUrl('*/*/new');
    }
    
    public function getUploadCsvUrl()
    {
        return $this->getUrl('*/*/uploadcsv');
    }
    
}