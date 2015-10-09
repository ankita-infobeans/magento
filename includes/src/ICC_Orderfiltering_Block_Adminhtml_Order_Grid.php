<?php

class ICC_Orderfiltering_Block_Adminhtml_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid {
    const STORE_ID = 0;
    const ATTRIBUTE_ID = 250;

    public function __construct() {
        parent::__construct();
        $this->setId('sales_order_grid');
        $this->setUseAjax(true);
        $this->setDefaultSort('real_order_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        
    }


    protected function _prepareCollection() {
        $collection = Mage::getResourceModel($this->_getCollectionClass());

        $filter = $this->getParam('filter');

        $filter_data = Mage::helper('adminhtml')->prepareFilterString($filter);

        $collection->getSelect()
                ->join('sales_flat_order_item'
                        , '`sales_flat_order_item`.`order_id`=`main_table`.`entity_id`'
                        , array(
                            'name'                          => 'name',
                            'product_id'                    => 'sales_flat_order_item.product_id',
                            // the rest are required to explicitly be aliased 
                            'main_table.increment_id'       => 'main_table.increment_id',
                            'main_table.created_at'         => 'main_table.created_at',
                            'main_table.billing_name'       => 'main_table.billing_name',
                            'main_table.shipping_name'      => 'main_table.shipping_name',
                            'main_table.base_grand_total'   => 'main_table.base_grand_total',
                            'main_table.grand_total'        => 'main_table.grand_total',
                            'main_table.status'             => 'main_table.status'
                        )
        );
        
        /**
         * Old Order ID filters
         */
        $oldOrderIdFilters = "";
        if (isset($filter_data['old_order_id_a'])) {
            $oldOrderIdFilters .= ' AND `sales_flat_order`.`old_order_id_a` LIKE "%' . $filter_data['old_order_id_a'] . '%"';
        }
        if (isset($filter_data['old_order_id_b'])) {
            $oldOrderIdFilters .= ' AND `sales_flat_order`.`old_order_id_b` LIKE "%' . $filter_data['old_order_id_b'] . '%"';
        }   
        $collection->getSelect()->join(
                                'sales_flat_order', 
                                '`sales_flat_order`.`entity_id`=`main_table`.`entity_id`'.$oldOrderIdFilters, 
                                array(
                                    'old_order_id_a' => 'sales_flat_order.old_order_id_a',
                                    'old_order_id_b' => 'sales_flat_order.old_order_id_b'
                                ));

        /**
         * Product Type filter
         */
        $collection->getSelect()->joinLeft(
                        'catalog_product_entity',
                        '`catalog_product_entity`.`entity_id` = `sales_flat_order_item`.`product_id`',
                        array('cpe_entity_id' => 'catalog_product_entity.entity_id')
                        );
        $collection->getSelect()->joinLeft(
                        'catalog_product_entity_int',
                        '`catalog_product_entity_int`.`entity_id` = `catalog_product_entity`.`entity_id` 
                            AND 
                            `catalog_product_entity_int`.`attribute_id` = ' . self::ATTRIBUTE_ID,
                        array('cpei_value' => 'catalog_product_entity_int.value')
                        );
        $productTypeFilter = "";
        if (!empty($filter_data['Product_Types']) && $filter_data['Product_Types']) {
            $productTypeFilter .= ' AND  `eav_attribute_option_value`.`value` = "' . $filter_data['Product_Types'] . '"';
        }

        $collection->getSelect()->joinLeft(
                        'eav_attribute_option_value', 
                        '`eav_attribute_option_value`.`option_id` = `catalog_product_entity_int`.`value` 
                            AND 
                            `eav_attribute_option_value`.`store_id` = ' . self::STORE_ID . $productTypeFilter,
                        array('item_type_values' => new Zend_Db_Expr('group_concat(DISTINCT `eav_attribute_option_value`.`value` SEPARATOR ",")')));
        $collection->getSelect()->group('main_table.entity_id');
        //$collection->getSelect()->joinLeft(
          //   array('vlr' => Mage::getResourceModel('customer/customer_collection')
        //     ->addNameToSelect()->getSelect()), 'main_table.customer_id=vlr.entity_id', array('name'));
        
        // can't call parent::_prepareCollection since it reset's $collection.
        // so we call the grandparent's function directly, instead
        $this->setCollection($collection);
        $grid = Mage::getBlockSingleton('adminhtml/widget_grid');
        return $grid::_prepareCollection();
    }

    protected function _prepareColumns() 
    {
        /**
         * Prepare parent collection.
         * Then update the indexes of each column to explicitly use the main_table
         * Otherwise we get ambiguous SQL.
         * Ugly, but it works.
         */
        parent::_prepareColumns();
        $mainTableColumns = array(
                                'real_order_id'     => true,
                                'store_id'          => true,
                                'created_at'        => true,
                                'billing_name'      => true,
                                'shipping_name'     => true,
                                'base_grand_total'  => true,
                                'grand_total'       => true,
                                'status'            => true
                            );
        foreach ($this->_columns as $id => $column) {
            if (isset($mainTableColumns[$id])) {
                $column->setIndex('main_table.'.$column->getIndex());
            }
        }

        $filter = $this->getParam('filter');
        $filter_data = Mage::helper('adminhtml')->prepareFilterString($filter);
        $this->addColumnAfter('name', array(
            'header' => Mage::helper('sales')->__('Customer Name'),
            
            'renderer' => 'ICC_Orderfiltering_Block_Adminhtml_Renderer_Customername',
            'filter_condition_callback' => array($this, '_searchCustomerName')
         ), 'created_at');
        
        $this->addColumn('Producttypes', array(
                        'header' => Mage::helper('sales')->__('Product Types'), 
                        'index' => 'item_type_values', 
                        'type' => 'options', 
                        'width' => '150px', 
                        'options' => Mage::helper('icc_orderfiltering')->getTypes(), 
                        'renderer' => 'ICC_Orderfiltering_Block_Adminhtml_Renderer_Producttype', 
                        'filter_index' => 'eav_attribute_option_value.value'
                        ));

        $this->addColumn('old_order_id_a', array(
                        'header' => 'Old Order Id A', 
                        'index' => 'old_order_id_a', 
                        'type' => 'text', 
                        'width' => '150px', 
                        'value' => (isset($filter_data['old_order_id_a'])) ? $filter_data['old_order_id_a'] : ''
                        ));
        
        $this->addColumn('old_order_id_b', array(
                        'header' => 'Old Order Id B', 
                        'index' => 'old_order_id_b', 
                        'type' => 'text', 
                        'width' => '150px', 
                        'value' => (isset($filter_data['old_order_id_b'])) ? $filter_data['old_order_id_b'] : ''
                        ));

        // this is called in the parent, but we needed to call the parent above
        // so we call this again here after adding our columns
        $this->sortColumnsByOrder();
        return $this;
    }
     protected function _searchCustomerName($collection, $column){
          $condition = $column->getFilter()->getCondition();
          $custom =  Mage::getModel('sales/order')->getCollection();//->addExpressionFieldToSelect('full_name','CONCAT(main_table.customer_firstname," ", main_table.customer_lastname)');
          // $custom->getSelect()->joinLeft(
         //    array('vlr' => Mage::getResourceModel('customer/customer_collection')
        //     ->addNameToSelect()->getSelect()), 'main_table.customer_id=vlr.entity_id', array('name'));
          $custom->addFieldToFilter('CONCAT(main_table.customer_firstname," ", main_table.customer_lastname)' , $condition)->addFieldToSelect('entity_id');
          $entity_ids = array_column($custom->getData(), 'entity_id');
          $collection->addFieldToFilter('entity_id', array('in' => $entity_ids));
          return $this;
      }

}
