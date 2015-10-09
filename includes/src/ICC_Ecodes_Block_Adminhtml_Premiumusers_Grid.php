<?php

class ICC_Ecodes_Block_Adminhtml_Premiumusers_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();

        $this->setId('premiumusersGrid');
        $this->setDefaultSort('user');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('premiumusers_filter');

    }

    /**
     * @return Mmm_BusinessModel_Block_Adminhtml_InstallerZipCode_Grid
     */
    protected function _prepareCollection()
    {
        $premiumusers = Mage::getModel('ecodes/premiumusers')->getCollection()
                ->addFieldToSelect('id')
                ->addFieldToSelect('firstname')
                ->addFieldToSelect('lastname')
                ->addFieldToSelect('user')
                ->addFieldToSelect('pass')
                ->addFieldToSelect('email')
                ->addFieldToSelect('created_at')
                ->addFieldToSelect('updated_at');
        
        $this->setCollection($premiumusers);
        parent::_prepareCollection();
        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('id',
            array(
                'header'=> Mage::helper('ecodes')->__('Id'),
                'index' => 'id',
        ));
        
        $this->addColumn('user',
            array(
                'header'=> Mage::helper('ecodes')->__('Username'),
                'index' => 'user',
        ));
        
        $this->addColumn('pass',
            array(
                'header'=> Mage::helper('ecodes')->__('Password'),
                'index' => 'pass',
                'renderer' => 'ecodes/adminhtml_premiumusers_grid_render_password'
        ));
        
        $this->addColumn('firstname',
            array(
                'header'=> Mage::helper('ecodes')->__('First Name'),
                'index' => 'firstname',
        ));

        $this->addColumn('lastname',
            array(
                'header'=> Mage::helper('ecodes')->__('Last Name'),
                'index' => 'lastname',
        ));

        $this->addColumn('email',
            array(
                'header'=> Mage::helper('ecodes')->__('Email'),
                'index' => 'email',
        ));

        $this->addColumn('created_at',
            array(
                'header'=> Mage::helper('ecodes')->__('Created'),
                'index' => 'created_at',
        ));

        $this->addColumn('updated_at',
            array(
                'header'=> Mage::helper('ecodes')->__('Updated'),
                'index' => 'updated_at',
        ));

        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

}



