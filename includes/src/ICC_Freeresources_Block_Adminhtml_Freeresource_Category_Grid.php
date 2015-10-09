<?php
/**
 * Free Resource category admin grid block
 *
 * @category    ICC
 * @package     ICC_Freeresources
 */
class ICC_Freeresources_Block_Adminhtml_Freeresource_Category_Grid
    extends Mage_Adminhtml_Block_Widget_Grid {
    /**
     * constructor
     * @access public
     */
    public function __construct(){
        parent::__construct();
        $this->setId('freeresourceCategoryGrid');
        $this->setDefaultSort('ct_category_id');
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
        $collection = Mage::getResourceModel('icc_freeresources/freeresource_category_freeresource_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    /**
     * prepare grid collection
     * @access protected
     * @return ICC_Freeresources_Block_Adminhtml_Freeresource_Category_Grid
     */
    protected function _prepareColumns(){
        $this->addColumn('category_id', array(
            'header'        => Mage::helper('icc_freeresources')->__('Id'),
            'index'         => 'ct_category_id',
            'type'          => 'number',
            'filter_index'  => 'ct.category_id',
        ));
        $this->addColumn('free_resource', array(
            'header'        => Mage::helper('icc_freeresources')->__('Free Resource'),
            'index'         => 'main_table_title',
            'filter_index'  => 'main_table.title',
        ));
        $this->addColumn('ct_title', array(
            'header'        => Mage::helper('icc_freeresources')->__('Category Title'),
            'index'         => 'ct_title',
            'filter_index'  => 'ct.title',
        ));
        $this->addColumn('action',
            array(
                'header'=>  Mage::helper('icc_freeresources')->__('Action'),
                'width' => '100',
                'type'  => 'action',
                'getter'=> 'getCtCategoryId',
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
    protected function _prepareMassaction(){
        $this->setMassactionIdField('ct_category_id');
        $this->setMassactionIdFilter('ct.category_id');
        $this->setMassactionIdFieldOnlyIndexValue(true);
        $this->getMassactionBlock()->setFormFieldName('category');
        $this->getMassactionBlock()->addItem('delete', array(
            'label'=> Mage::helper('icc_freeresources')->__('Delete'),
            'url'  => $this->getUrl('*/*/massDelete'),
            'confirm'  => Mage::helper('icc_freeresources')->__('Are you sure?')
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
        return $this->getUrl('*/*/edit', array('id' => $row->getCtCategoryId()));
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