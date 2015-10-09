<?php

class Gorilla_Queue_Block_Adminhtml_Queue_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('QueueGrid');
        $this->setUseAjax(true);
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('gorilla_queue/queue')->getCollection();
        $collection->addFieldToSelect('*');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('queue_id', array(
            'header'   => $this->__('Queue ID'),
            'width'    => '50px',
            'sortable' => true,
            'index'    => 'queue_id',
            'type'     => 'number',
        ));

        $this->addColumn('status', array(
            'header'         => $this->__('Queue Status'),
            'width'          => '50px',
            'index'          => 'status',
            'type'           => 'options',
            'sortable'       => true,
            'options'        => Mage::getModel('gorilla_queue/queue')->getStatusesOptions(),
            'frame_callback' => array($this, 'decorateStatus')
        ));

        $this->addColumn('short_description', array(
            'header'   => $this->__('Queue Description'),
            'width'    => '50px',
            'index'    => 'short_description',
            'type'     => 'string',
            'sortable' => true,
        ));

        $this->addColumn('number_attempts', array(
            'header' => $this->__('Number of Times Attempted'),
            'width'  => '50px',
            'index'  => 'number_attempts',
            'type'   => 'number',
        ));
        $this->addColumn('last_attempt', array(
            'header'    => $this->__('Last Time Processed'),
            'width'     => '50px',
            'type'      => 'datetime',
            'index'     => 'last_attempt',
            'gmtoffset' => true,
        ));
        $this->addColumn('created_at', array(
            'header'    => $this->__('Creation Date'),
            'width'     => '50px',
            'type'      => 'datetime',
            'index'     => 'created_at',
            'gmtoffset' => true,
        ));
        $this->addColumn('error_message', array(
            'header'     => $this->__('Error Message'),
            'width'      => '*',
            'type'       => 'text',
            'index'      => 'error_message',
            'filterable' => false,
            'sortable'   => false
        ));

        $this->addColumn('action', array(
            'header'    => $this->__('Action'),
            'width'     => '100px',
            'type'      => 'action',
            'getter'    => 'getQueueId',
            'actions'   => array(
                array(
                    'caption' => $this->__('Process'),
                    'url'     => array('base' => '*/*/process'),
                    'field'   => 'id',
                    'confirm' => 'Are you sure you would like to process this queue item?',
                ),
                array(
                    'caption' => $this->__('Reset'),
                    'url'     => array('base' => '*/*/reset'),
                    'field'   => 'id',
                    'confirm' => 'Are you sure you would like to reset this queue item?',
                ),
                array(
                    'caption' => $this->__('Remove'),
                    'url'     => array('base' => '*/*/remove'),
                    'field'   => 'id',
                    'confirm' => 'Are you sure you would like to PERMANENTLY REMOVE this queue item? This action can not be undone.',
                ),
            ),
            'filter'    => false,
            'sortable'  => false,
            'index'     => 'stores',
            'is_system' => true,
        ));
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    /**
     * Decorate status column values
     *
     * @param $value
     * @param $row
     * @param $column
     * @param $isExport
     * @return string
     */
    public function decorateStatus($value, $row, $column, $isExport)
    {
        $class = '';
        switch ($row->getStatus()) {
            case Gorilla_Queue_Model_Queue::STATUS_SUCCESS:
                $class = 'grid-severity-notice';
                break;
            case Gorilla_Queue_Model_Queue::STATUS_OPEN:
            case Gorilla_Queue_Model_Queue::STATUS_PROCESSING:
            case Gorilla_Queue_Model_Queue::STATUS_ABORTED_NOTIFIED:
                $class = 'grid-severity-major';
                break;
            case Gorilla_Queue_Model_Queue::STATUS_ABORTED:
                $class = 'grid-severity-critical';
                break;
        }
        return '<span class="' . $class . '"><span>' . $value . '</span></span>';
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

}
