<?php

class ICC_Premiumaccess_Block_Adminhtml_Premiumcustomers_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
  public function __construct()
  {
      
      parent::__construct();
      $this->setId('premiumcustomersGrid');
      $this->setDefaultSort('id');
      $this->setDefaultDir('ASC');
      $this->setSaveParametersInSession(true);
  }

  protected function _prepareCollection()
  {
      
      $_premiumaccess_id = (int)$this->getRequest()->getParam('id'); 
      
      $collection = Mage::getModel('icc_premiumaccess/premiumaccess')->getCollection();
      $collection->addFieldToSelect(array('product_name', 'sku', 'expiration', 'seats_total', 'registered_count' ,'notes', 'status'));
      $customercollection = Mage::getResourceModel('customer/customer_collection')
                ->addNameToSelect()
                ->addAttributeToSelect('email');      
        
      $collection->getSelect()->join(
              array('epa' => $customercollection->getSelect()), 'main_table.customer_id=epa.entity_id', array('name','email') // added 'e.status' in stead of 'status'
      );        
      
      if(isset($_premiumaccess_id) && $_premiumaccess_id>0){
           $collection->addFieldToFilter(
                            'id',
                            array(
                                'eq'=>$_premiumaccess_id
                            )
                        );
      }
      
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
          'align'     =>'left',
          'index'     => 'name',
          'width'     => '50px',
      ));
      
      
      $this->addColumn('email', array(
          'header'    => Mage::helper('ecodes')->__('Email'),
          'align'     =>'left',
          'index'     => 'email',
          'width'     => '50px',
      ));
      
      $this->addColumn('product_name', array(
            'header'    => $this->__('Product Name'),           
            'sortable'  => true,
            'index'     => 'product_name',
            'type'      => 'string',
        ));
      
      
       $this->addColumn('seats_total', array(
            'header'    => $this->__('Total Qty'),
            'width'     => '30px',
            'sortable' => true,
            'index'     => 'seats_total',
            'type'  => 'string',
        ));
        $this->addColumn('registered_count', array(
            'header'    => $this->__('Gift Share Qty'),
            'width'     => '30px',
            'sortable' => true,
            'index'     => 'registered_count',
            'type'  => 'string',
        ));
        
        $this->addColumn('expiration', array(
            'header'    => $this->__('Expiration Date'),
            'width'     => '50px',
            'sortable'  => true,
            'index'     => 'expiration',
            'type'      => 'date',
        ));
      
      
      

	  /*
      $this->addColumn('content', array(
			'header'    => Mage::helper('ecodes')->__('Item Content'),
			'width'     => '150px',
			'index'     => 'content',
      ));
	  */

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
          ),
      ));
	  
        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('ecodes')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('ecodes')->__('Manage User(s)'),
                        'url'       => array('base'=> '*/premiumuser/index'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));
		
		//$this->addExportType('*/*/exportCsv', Mage::helper('ecodes')->__('CSV'));
		//$this->addExportType('*/*/exportXml', Mage::helper('ecodes')->__('XML'));
	  
      return parent::_prepareColumns();
  }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('subscription_id');
        $this->getMassactionBlock()->setFormFieldName('subscription');       

        //$statuses = Mage::getSingleton('subscription/status')->getOptionArray();

        array_unshift($statuses, array('label'=>'', 'value'=>''));
        $this->getMassactionBlock()->addItem('status', array(
             'label'=> Mage::helper('ecodes')->__('Change status'),
             'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
             'additional' => array(
                    'visibility' => array(
                         'name' => 'status',
                         'type' => 'select',
                         'class' => 'required-entry',
                         'label' => Mage::helper('ecodes')->__('Status'),
                         'values' => array(
                                        1 => 'Enabled',
                                        0 => 'Disabled',
                                        2 => 'Expiry',
                                    )
                     )
             )
        ));
        return $this;
    }

  public function getRowUrl($row)
  {
      //return $this->getUrl('*/*/edit', array('id' => $row->getId()));
  }

}
