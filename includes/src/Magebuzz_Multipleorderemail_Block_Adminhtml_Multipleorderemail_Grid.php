<?php
/**
 * @category    Magebuzz
 * @package     Magebuzz_Multipleorderemail
 */
class Magebuzz_Multipleorderemail_Block_Adminhtml_Multipleorderemail_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('multipleorderemailGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('rule_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $model = Mage::getModel('multipleorderemail/multipleorderemailrule');
        $collection = $model->getCollection();
        $this->setCollection($collection);    
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('rule_id', array(
            'header'    => Mage::helper('multipleorderemail')->__('ID'),
            'align'     =>'right',
            'width'     => '50px',
            'index'     => 'rule_id',
        ));

        $this->addColumn('title', array(
            'header'    => Mage::helper('multipleorderemail')->__('Title'),
            'align'     =>'left',
            'width'     => '150px',
            'index'     => 'title',
        ));

        $this->addColumn('description', array(
            'header'    => Mage::helper('multipleorderemail')->__('Description'),
            'align'     =>'left',
            'width'     => '200px',
            'index'     => 'description',
        ));    

        $this->addColumn('status', array(
            'header'    => Mage::helper('multipleorderemail')->__('Status'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'status',
            'type'      => 'options',
            'options'   => array (
                1 => 'Enabled',
                2 => 'Disabled',
            ),
        ));

        $this->addColumn('action',
            array (
                'header'    =>  Mage::helper('multipleorderemail')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array (
                    array (
                        'caption'   => Mage::helper('multipleorderemail')->__('Edit'),
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('multipleorderemail')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('multipleorderemail')->__('XML'));
        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('multipleorderemail_id');
        $this->getMassactionBlock()->setFormFieldName('multipleorderemail');

        $this->getMassactionBlock()->addItem('delete', array (
            'label'    => Mage::helper('multipleorderemail')->__('Delete'),
            'url'      => $this->getUrl('*/*/massDelete'),
            'confirm'  => Mage::helper('multipleorderemail')->__('Are you sure?')
        ));

        $statuses = Mage::getSingleton('multipleorderemail/status')->getOptionArray();
        $this->getMassactionBlock()->addItem('status', array (
            'label'=> Mage::helper('multipleorderemail')->__('Change status'),
            'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
            'additional' => array (
                'visibility' => array (
                    'name' => 'status',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => Mage::helper('multipleorderemail')->__('Status'),
                    'values' => $statuses
                )
            )
        ));
        return $this;
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
    
    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=> true));
    }  
}