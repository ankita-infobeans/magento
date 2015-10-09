<?php

class ICC_TEC_Block_Adminhtml_Exam_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function _construct()
    {
        parent::_construct();
        $this->setId('ExamGrid');
        $this->setUseAjax(false); // set to false because of troubles removing the root node
        $this->setDefaultSort('main_table.entity_id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {

        $roster_collection = Mage::getModel('icc_tec/roster')
                ->getCollection()
                ->addFieldToFilter('prod_info.attribute_set_id', '12')
                ->addFieldToSelect(array(
//            "fullname",
            "date",
            "location",
            "code_cycle"
                ))
        ;
        $roster_collection->getSelect()
                ->joinLeft(
                        array(
                    'ord_item' => 'sales_flat_order_item' // $this->getTable('sales/order_item')
                        ), 'main_table.order_item_id = ord_item.item_id', array('ord_item.product_type')
                )
                ->joinLeft(
                        array(
                    'order' => 'sales_flat_order' // $this->getTable('sales/order_item')
                        )
                        , 'main_table.order_id = order.entity_id'
//                        , 'ord_item.order_id = order.entity_id'
                        , array(
//                    'order.increment_id AS order_increment_id',
                    'order.entity_id AS order_entity_id',
//                    'order.total_paid AS order_total_paid',
//                    "email" => "order.customer_email",
                    ""
                        )
                )
                ->joinLeft(
                        array('order_address' => 'sales_flat_order_address'), 'order.billing_address_id = order_address.entity_id  AND order_address.address_type = \'billing\' '
                        , array(
                    'order_address.telephone AS order_telephone',
                    'REPLACE(REPLACE(CONCAT_WS( \' \', order_address.street, order_address.city, order_address.region, order_address.postcode ),"\n"," "),"  "," ") AS my_order_address'
                        )
                )
                ->joinLeft(
                        array(
                    'customer_entity'
                        ), 'order.customer_id = customer_entity.entity_id'
                        , array("")
                )
                ->joinLeft(
                        array(
                    'prod_info' => 'catalog_product_entity'
                        ), 'ord_item.product_id = prod_info.entity_id'
                        , array('prod_info.attribute_set_id')
                )
                ->joinLeft(
                        array('product_varchar_title' => 'catalog_product_entity_varchar'), 'prod_info.entity_id = product_varchar_title.entity_id AND attribute_id = (SELECT attribute_id FROM `eav_attribute` WHERE `attribute_code` = \'name\' AND `entity_type_id` = (SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = \'catalog_product\')    ) '
                        , array('product_varchar_title.value AS prod_title')
                )
                ->columns(array(
                    "email"    => new Zend_Db_Expr("IF(customer_entity.email is null,order.customer_email,customer_entity.email)"),
                    "fullname" => new Zend_Db_Expr("TRIM(REPLACE(REPLACE(REPLACE(main_table.fullname, '\n', ' '), '\r', ' '), '\t', ' '))"),
                ))
        ;
        $roster_collection->getSelect()->where('( ord_item.qty_canceled + ord_item.qty_refunded ) < ord_item.qty_ordered');
        $this->setCollection($roster_collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        $this->addColumn('entity_id', array(
            'header'       => $this->__('Roster ID'),
            'width'        => '50px',
            'sortable'     => true,
            'index'        => 'entity_id',
            'filter_index' => 'main_table.entity_id',
            'type'         => 'number',
        ));


        $this->addColumn('fullname', array(
            'header'       => $this->__('Registrant Name'),
            'width'        => '50px',
            'index'        => 'fullname',
            'filter_index' => 'fullname',
            'type'         => 'string',
            'sortable'     => true,
        ));

        $this->addColumn('email', array(
            'header'       => $this->__('Customer Email'),
            'width'        => '50px',
            'index'        => 'email',
            'filter_index' => 'order.customer_email',
            'type'         => 'string',
            'sortable'     => true,
        ));


        $this->addColumn('order_telephone', array(
            'header'       => $this->__('Billing Telephone'),
            'width'        => '50px',
            'index'        => 'order_telephone',
            'filter_index' => 'order_address.telephone',
            'type'         => 'string',
            'sortable'     => true,
        ));

        $this->addColumn('my_order_address', array(
            'header'       => $this->__('Billing Address'),
            'width'        => '50px',
            'index'        => 'my_order_address',
            'filter_index' => 'REPLACE(REPLACE(CONCAT_WS( \' \', order_address.street, order_address.city, order_address.region, order_address.postcode ),"\n"," "),"  "," ")',
            'type'         => 'string',
            'sortable'     => true,
        ));

        $this->addColumn('prod_title', array(
            'header'       => $this->__('Exam Name'),
            'width'        => '50px',
            'index'        => 'prod_title',
            'filter_index' => 'product_varchar_title.value',
            'type'         => 'string',
            'sortable'     => true,
        ));

        $this->addColumn('code_cycle', array(
            'header'       => $this->__('Code Cycle'),
            'width'        => '50px',
            'index'        => 'code_cycle',
            'filter_index' => 'main_table.code_cycle',
            'type'         => 'string',
            'sortable'     => true,
        ));

        $this->addColumn('date', array(
            'header'       => $this->__('Date'),
            'width'        => '50px',
            'index'        => 'date',
            'filter_index' => 'main_table.date',
            'type'         => 'string',
            'sortable'     => true,
        ));

        $this->addColumn('location', array(
            'header'       => $this->__('Location'),
            'width'        => '50px',
            'index'        => 'location',
            'filter_index' => 'main_table.location',
            'type'         => 'string',
            'sortable'     => true,
        ));

        $this->addExportType('*/*/exportSearchCsv', Mage::helper('reports')->__('CSV'));
        $this->addExportType('*/*/exportSearchExcel', Mage::helper('reports')->__('Excel XML'));
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/list', array('_current' => true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/sales_order/view', array('order_id' => $row->getOrderEntityId()));
    }

//    protected function _afterLoadCollection()
//    {
//        parent::_afterLoadCollection();
//        Mage::log($this->getCollection()->getSelect()->assemble());
//        return $this;
//    }

}
