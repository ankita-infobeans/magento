<?php
/**
 * Free Resource categorys admin grid block
 *
 * @category    ICC
 * @package     ICC_Freeresources
  */
class ICC_Freeresources_Block_Adminhtml_Freeresource_Subcategory_Grid
    extends Mage_Adminhtml_Block_Widget_Grid {
    /**
     * constructor
     * @access public

     */
    public function __construct(){
        parent::__construct();
        $this->setId('freeresourceCategoryGrid');
        $this->setDefaultSort('ct_subcategory_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }
    /**
     * prepare collection
     * @access protected
     * @return ICC_Freeresources_Block_Adminhtml_Freeresource_Category_Grid

     */
    protected function _prepareCollection(){
        $collection = Mage::getResourceModel('icc_freeresources/freeresource_subcategory_freeresource_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    /**
     * prepare grid collection
     * @access protected
     * @return ICC_Freeresources_Block_Adminhtml_Freeresource_Category_Grid

     */
    protected function _prepareColumns(){
        $this->addColumn('subcategory_id', array(
            'header'        => Mage::helper('icc_freeresources')->__('Id'),
            'index'         => 'subcategory_id',
            'type'          => 'number',
            'filter_index'  => 'subcategory_id',
        ));
        $this->addColumn('fr_title', array(
            'header'        => Mage::helper('icc_freeresources')->__('Free Resource Title'),
            'index'         => 'fr_title',
            'filter_index'  => 'fr.title',
        ));
        $this->addColumn('ct_title', array(
            'header'        => Mage::helper('icc_freeresources')->__('Category Title'),
            'index'         => 'ct_title',
            'filter_index'  => 'ct.title',
        ));
        $this->addColumn('title', array(
            'header'        => Mage::helper('icc_freeresources')->__('Sub Category Title'),
            'index'         => 'title',
            'filter_index'  => 'main_table.title',
        ));
        $this->addColumn('status', array(
            'header'    => Mage::helper('icc_freeresources')->__('Status'),
            'index'        => 'status',
            'type'        => 'options',
            'options'    => array(
                '1' => Mage::helper('icc_freeresources')->__('Enabled'),
                '0' => Mage::helper('icc_freeresources')->__('Disabled'),
            )
        ));
        $this->addColumn('action',
            array(
                'header'=>  Mage::helper('icc_freeresources')->__('Action'),
                'width' => '100',
                'type'  => 'action',
                'getter'=> 'getSubcategoryId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('icc_freeresources')->__('Edit'),
                        'url'   => array('base'=> '*/*/edit'),
                        'field' => 'id'
                    )
                ),
                'filter'=> false,
                'is_system'    => true,
                'sortable'  => false,
        ));
        $this->addExportType('*/*/exportCsv', Mage::helper('icc_freeresources')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('icc_freeresources')->__('Excel'));
        $this->addExportType('*/*/exportXml', Mage::helper('icc_freeresources')->__('XML'));
        return parent::_prepareColumns();
    }
    /**
     * prepare mass action
     * @access protected
     * @return ICC_Freeresources_Block_Adminhtml_Freeresource_Grid

     */
    protected function _prepareMassaction() {
        $this->setMassactionIdField('subcategory');
        $this->getMassactionBlock()->setFormFieldName('subcategory');
        $this->getMassactionBlock()->addItem('delete', array(
            'label'=> Mage::helper('icc_freeresources')->__('Delete'),
            'url'  => $this->getUrl('*/*/massDelete'),
            'confirm'  => Mage::helper('icc_freeresources')->__('Are you sure?')
        ));
        $this->getMassactionBlock()->addItem('status', array(
            'label'=> Mage::helper('icc_freeresources')->__('Change status'),
            'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
            'additional' => array(
                'status' => array(
                        'name' => 'status',
                        'type' => 'select',
                        'class' => 'required-entry',
                        'label' => Mage::helper('icc_freeresources')->__('Status'),
                        'values' => array(
                                '1' => Mage::helper('icc_freeresources')->__('Enabled'),
                                '0' => Mage::helper('icc_freeresources')->__('Disabled'),
                        )
                )
            )
        ));
       return $this;
    }
    /**
     * get the row url
     * @access public
     * @param ICC_Freeresources_Model_Freeresource_Category
     * @return string

     */
    public function getRowUrl($row){
        return $this->getUrl('*/*/edit', array('id' => $row->getSubcategoryId()));
    }
    /**
     * get the grid url
     * @access public
     * @return string

     */
    public function getGridUrl(){
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }
}
