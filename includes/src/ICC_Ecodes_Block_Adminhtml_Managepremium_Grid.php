<?php

class ICC_Ecodes_Block_Adminhtml_Managepremium_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function _construct()
    {   //Mage::log('grid block called', null, 'premium-grid-block.log');
        parent::_construct();
        $this->setId('ManagepremiumGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('main_table.id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        
    }
    protected function _prepareCollection()
    {
        $sub_id = (int)$this->getRequest()->getParam('id');
        $collection = Mage::getModel('ecodes/premiumsubusers')->getCollection();
        $collection->addFieldToFilter('subs_id', $sub_id);
        $collection->getSelect()->group('main_table.user_id');  // Avoid problem with duplicate subs_id,user_id rows
        $collection->getSelect()
                ->joinLeft(
                        array(
                            'users' => 'ecodes_premium_users'
                        ), 
                        'main_table.user_id = users.id',
                        array('users.*')
                    );
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    protected function _prepareColumns()
    {
        
        $this->addColumn('firstname', array(
            'header'    => $this->__('First Name'),
            'width'     => '50px',
            'sortable' => true,
            'index'     => 'firstname',
            'type'  => 'string',
        ));
        
        $this->addColumn('lastname', array(
            'header'    => $this->__('Last Name'),
            'width'     => '50px',
            'sortable' => true,
            'index'     => 'lastname',
            'type'  => 'string',
        ));
        
        $this->addColumn('email', array(
            'header'    => $this->__('Email Address'),
            'width'     => '50px',
            'sortable' => true,
            'index'     => 'email',
            'type'  => 'string',
        ));
        
        $this->addColumn('user', array(
            'header'    => $this->__('Username'),
            'width'     => '50px',
            'sortable' => true,
            'index'     => 'user',
            'type'  => 'string',
        ));
        
        
        $sub_id = (int)$this->getRequest()->getParam('id');
        $this->addColumn('action',
            array(
                'header'    =>  $this->__('Action'),
                'width'     => '100px',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => $this->__('Edit'),
                        'url'       => array('base' => '*/*/edit/subscription_id/' . $sub_id),
                        'field'     => 'id',
                       // 'confirm'   => 'Are you sure you would like to process this queue item?',
                    ),
//                    array(
//                        'caption'   => $this->__('Add New'),
//                        'url'       =>  array('base' => '*/managepremium/add'),
//                        'field'     => 'id',
//                       // 'confirm'   => 'Are you sure you would like to manage the users this order item?',
//                    ),
//                    array(
//                        'caption' => $this->__('Delete'),
//                        'url' => array('base' => '*/premium/delete'),
//                        'field' => 'id',
//                        'confirm' => 'Are you sure you would like to PERMANENTLY DELETE this user? This action can not be undone.',
//                    ),
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));
        
        
    }
    
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=> true));
    }
    /* */
    // /*
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id'=>$row->getId(), 'subscription_id' =>(int)$this->getRequest()->getParam('id')) );
    }
    
}