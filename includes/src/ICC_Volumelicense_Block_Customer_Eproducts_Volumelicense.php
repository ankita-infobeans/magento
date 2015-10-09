<?php
class ICC_Volumelicense_Block_Customer_Eproducts_Volumelicense extends Mage_Core_Block_Template {

	public function getCustomer() {
		return Mage::getSingleton('customer/session')->getCustomer();
	}
        public function __construct()
        {
            // we have to load only unique orders by current customer
            /*$products = Mage::getModel('volumelicense/volumelicense')->getCollection();
            $products->addFieldToFilter('max_register',array('gt' => 0));
            $products->setOrder ( 'created_at', 'desc' );
            $products->getByUserId($this->getCustomer()->getId());
            $this->setVolumeProducts($products);*/
            Mage::getSingleton('customer/session')->isLoggedIn();
            $loginCustomerId = Mage::getSingleton('customer/session')->getCustomerId();
            
            
            $products = Mage::getModel('sales/order_item')->getCollection();
            $sales_order_table= Mage::getSingleton('core/resource')->getTableName('sales_flat_order'); 
            $products->getSelect()->joinLeft(array('sales_order'=>$sales_order_table),'`main_table`.`order_id` = `sales_order`.`entity_id`'
            
            );
            $products->addFieldToFilter('sales_order.customer_id',array('eq'=>$loginCustomerId));
            $products->addFieldToFilter('sales_order.parent_order_id',array('null'=>true));
	    $products->addFieldToFilter('sales_order.volume_license',array('eq'=>1));
	    $products->addFieldToFilter('main_table.volume_license',array('eq'=>1));
	    $products->addFieldToFilter('main_table.qty_invoiced',array('gt'=>0));
	    
	   $products->setOrder ( 'sales_order.increment_id', 'desc' );
	   //echo $products->getSelect();
	   // echo "<pre>"; print_r($products->getData());die;
	  // echo $products->getSelect();die;
	    
            //echo "<pre>";print_r($order->getData());die;
            $this->setVolumeProducts($products);
        }

        protected function _prepareLayout()
        {
            $pager = $this->getLayout()->createBlock('page/html_pager', 'volumelicense.customer.products.pager')
                ->setCollection($this->getVolumeProducts());
            $this->setChild('volumelicense_pager', $pager);
            $this->getVolumeProducts()->load();
            //collect current order's ids
            return $this;
        }

        public function getPagerHtml()
        {
            return $this->getChildHtml('volumelicense_pager');
        }
        
         public function getVolumeselfUsers() 
        {
                return  Mage::getModel('volumelicense/volumelicense')->getCollection()->getByUserId($this->getCustomer()->getId());
        }
        
        public function getChildOrderCount($order_id,$order_item_id)
        {
	      //$order_id=232652;
        
	      $products = Mage::getModel('sales/order')->getCollection();
	      $products->addFieldToFilter('parent_order_id',array('eq'=>$order_id));
	      $products->addFieldToFilter('volume_license',array('eq'=>1));
	      $products->addFieldToFilter('parent_order_item_id',array('eq'=>$order_item_id));
	      return count($products);
        }

        
}
