<?php

class ICC_TEC_Block_Adminhtml_Event_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function _construct()
    {   //die( 'grid widget');
        parent::_construct();
        $this->setId('EventGrid');
        $this->setUseAjax(true); 
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
    }
    
    protected function _prepareCollection()
    {
       
        $attribute_collection = Mage::getModel('eav/entity_attribute_set')
                        ->getCollection()
                        ->addFieldToFilter('attribute_set_name', 'Event');
        $attribute_set = $attribute_collection->getFirstItem();

        $product_collection = Mage::getModel('catalog/product')
                                ->getCollection()
                                ->addAttributeToSelect('*')
                                ->addFieldToFilter('attribute_set_id', $attribute_set->getId())
                                        ;
                $this->setCollection($product_collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
//        $this->addColumn('entity_id', array(
//            'header'    => $this->__('Product ID'),
//            'width'     => '50px',
//            'sortable' => true,
//            'index'     => 'entity_id',
//            'type'  => 'number',
//        ));
        
        $this->addColumn('gp_sku', array(
            'header'    => $this->__('GP SKU'),
            'width'     => '50px',
            'sortable' => true,
            'index'     => 'gp_sku',
            'type'  => 'string',
        ));
        
        $this->addColumn('name', array(
            'header'    => $this->__('Product Name'),
            'width'     => '50px',
            'index'     => 'name',
            'type'  => 'string',
            'sortable' => true,
        ));
        
        $this->addColumn('event_date', array(
            'header'    => $this->__('Event Date'),
            'width'     => '50px',
            'index'     => 'event_date',
            'type'  => 'date',
            'sortable' => true,
        ));
        
        $this->addColumn('event_end_date', array(
            'header'    => $this->__('Event End Date'),
            'width'     => '50px',
            'index'     => 'event_end_date',
            'type'  => 'date',
            'sortable' => true,
        ));
        
        $this->addColumn('event_location', array(
            'header'    => $this->__('Event Location'),
            'width'     => '50px',
            'index'     => 'event_location',
            'type'  => 'string',
            'sortable' => true,
        ));
        
        $eventProvderInfo = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'event_provider')->getSource()->getAllOptions(false);
        $eventProviderOptions = array();
        foreach($eventProvderInfo as $option)
        {
            $eventProviderOptions[$option['value']] = $option['label'];
        }
        
        $this->addColumn('event_provider', array(
            'header'    => $this->__('Event Provider'),
            'width'     => '50px',
            'index'     => 'event_provider',
            'type'  => 'options',
            'options' => $eventProviderOptions,
            'sortable' => true,
        )); 
        
        $this->addColumn('event_sponsor', array(
            'header'    => $this->__('Event Sponsor'),
            'width'     => '50px',
            'index'     => 'event_sponsor',
            'type'  => 'string',
            'sortable' => true,
        )); 
        
        $eventInstructorInfo = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'event_instructors')->getSource()->getAllOptions(false);
        $eventInstructorOptions = array();
        foreach($eventInstructorInfo as $option)
        {
            $eventInstructorOptions[$option['value']] = $option['label'];
        }

        $this->addColumn('event_instructors', array(
            'header'    => $this->__('Event Instructor'),
            'width'     => '50px',
            'index'     => 'event_instructors',
            'type'      => 'options',
            'options'   => $eventInstructorOptions,
            'sortable'  => true,
            'filter_condition_callback' => array($this, '_filterEventInstructorsCondition'),
        )); 
        
        $this->addColumn('event_chapter_title', array(
            'header'    => $this->__('Event Chapter Title'),
            'width'     => '50px',
            'index'     => 'event_chapter_title',
            'type'  => 'string',
            'sortable' => true,
        )); 
        
        $eventSeminarCoordInfo = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'event_seminar_coordinator')->getSource()->getAllOptions(false);
        $eventSeminarCoordOptions = array();
        foreach($eventSeminarCoordInfo as $option)
        {
            $eventSeminarCoordOptions[$option['value']] = $option['label'];
        }
        $this->addColumn('event_seminar_coordinator', array(
            'header'    => $this->__('Event Seminar Coordinator'),
            'width'     => '50px',
            'index'     => 'event_seminar_coordinator',
            'type'      => 'options',
            'options'   => $eventSeminarCoordOptions,
            'sortable'  => true,
        )); 
        
        $this->addColumn('event_admin_notes', array(
            'header'    => $this->__('Administrative Notes'),
            'width'     => '50px',
            'index'     => 'event_admin_notes',
            'type'  => 'string',
            'sortable' => true,
        )); 
        
        $eventStateInfo = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'event_state')->getSource()->getAllOptions(false);
        $eventStateOptions = array();
        foreach($eventStateInfo as $option)
        {
            $eventStateOptions[$option['value']] = $option['label'];
        }
        $this->addColumn('event_state', array(
            'header'    => $this->__('State'),
            'width'     => '50px',
            'index'     => 'event_state',
            'type'  => 'options',
            'options' => $eventStateOptions,
            'sortable' => true,
        )); 
                
        $this->addExportType('*/*/exportEventProductCsv', Mage::helper('reports')->__('CSV'));
        $this->addExportType('*/*/exportEventProductExcel', Mage::helper('reports')->__('Excel XML'));
    }
    
    
    protected function _filterEventInstructorsCondition($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ( ! $value ) {
            return;
        }
        $this->getCollection()->addFieldToFilter('event_instructors', array('finset' => $value));
    }
    
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=> true));
    }
    
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/roster', array('id'=>$row->getId()));
    }    
}