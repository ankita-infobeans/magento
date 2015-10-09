<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
 
class ICC_Premiumaccess_Block_Adminhtml_Premiumuser_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
      
      parent::__construct();
      $this->setId('premiumuserGrid');
      $this->setDefaultSort('id');
      $this->setDefaultDir('ASC');
      $this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection()
  {
      
      $_session_premium_access_id = Mage::getSingleton("adminhtml/session")->getData('premium_access_id'); 
      
      $collection = Mage::getModel('icc_premiumaccess/registry')->getCollection();      
      //$collection->addFieldToSelect(array('IF( main_table.parent_customer_id> 1 ,"NO", "YES") as parent_customer_id'));
      $collection->addExpressionFieldToSelect('parent_customer_id', 'IF( main_table.parent_customer_id> 0 ,"NO", "YES")');
      $customercollection = Mage::getResourceModel('customer/customer_collection')
                ->addNameToSelect()
                ->addAttributeToSelect('email');      
        
      $collection->getSelect()->joinLeft(
              array('epa' => $customercollection->getSelect()), 'main_table.assign_customer_id=epa.entity_id', array('name','email') // added 'e.status' in stead of 'status'
      );        
      
      $collection->addFieldToFilter(
                            'main_table.subscription_id',
                            array(
                                'eq'=> $_session_premium_access_id
                            )
                        );  
        $collection->addFieldToFilter(
                            'main_table.status',
                            array(
                                'neq'=> 4
                            )
                        );  
      $this->setCollection($collection);
      return parent::_prepareCollection();
  }

  protected function _prepareColumns()
  {
      $this->addColumn('id', array(
          'header'    => Mage::helper('ecodes')->__('ID'),
          'align'     =>'right',
          'width'     => '50px',
          'index'     => 'id',
      ));

      $this->addColumn('name', array(
          'header'    => Mage::helper('ecodes')->__('Customer Name'),          
          'index'     => 'name',
          'width'     => '100px',
          'type'      => 'string' 
      ));
      
      
      $this->addColumn('assign_customer_email', array(
          'header'    => Mage::helper('ecodes')->__('Email'),          
          'index'     => 'assign_customer_email',
          'type'      => 'string'  
      ));      
      
      
      $this->addColumn('status', array(
          'header'    => Mage::helper('ecodes')->__('Status'),
          'align'     => 'left',
          'width'     => '80px',
          'index'     => 'status',
          'type'      => 'options',
          'options'   => array(
              1 => 'Enabled',
              0 => 'Disabled',
              2 => 'Expiry',
              3 => 'Refund',
          ),
      ));
      
          
      $this->addColumn('parent_customer_id', array(
          'header'    => Mage::helper('ecodes')->__('Ower Flag Status'),
          'align'     => 'center',
          'width'     => '150px',
          'index'     => 'parent_customer_id',
          'filter'    => false,
          'type'      => 'string'           
      ));
      return parent::_prepareColumns();
  }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('registry');     
        
        
        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('ecodes')->__('Delete'),
             'url'      => $this->getUrl('*/*/massDelete'),
             'confirm'  => Mage::helper('ecodes')->__('Are you sure?')
        ));
        
        return $this;
    }

  public function getRowUrl($row)
  {
      //return $this->getUrl('*/*/edit', array('id' => $row->getId()));
  }

}
