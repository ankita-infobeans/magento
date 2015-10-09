<?php

class ICC_Ecodes_Block_Customer_Eproducts_Downloadable extends Mage_Downloadable_Block_Customer_Products_List
{
    const ECODES_DOWNLOADABLE_DATETIME_FORMAT  = 'MM/dd/yy';

    public function __construct()
    {
        // we have to load only unique orders by current customer
        $products = Mage::getResourceModel('ecodes/downloadable_collection')
        ->getDownloadableHistory($this->getCustomerId());

        $products->getSelect()->where('order.volume_users is null and order.parent_order_id is null')->group('order.increment_id');
        $this->setPurchasedProducts($products);
        
         // we have to load only unique orders by current customer
        $vproducts = Mage::getResourceModel('ecodes/downloadable_collection')
        ->getDownloadableHistory($this->getCustomerId());
        $vproducts->addFieldToselect('order_id');
        $vproducts->getSelect()->where('order.parent_order_id is not null and order.future_email is null')->group('order.increment_id');
        //echo $vproducts->getSelect();
        $this->setVpurchasedProducts($vproducts);
        
        
        
    }

    protected function _prepareLayout()
    {
        $pager = $this->getLayout()->createBlock('page/html_pager', 'downloadable.customer.products.pager')
            ->setCollection($this->getPurchasedProducts());
        $this->setChild('downloadable_pager', $pager);
        $this->getPurchasedProducts()->load();

        //collect current order's ids
        $ordersIds = array();
        foreach ($this->getPurchasedProducts() as $_product){
            $ordersIds[] = $_product->getIncrementId();
        }

        if (empty($ordersIds)){
            $ordersIds = array(null);
        }

        //load purchased Links collection by order's Ids
        $purchasedLinks = Mage::getResourceModel('ecodes/downloadable_collection')
            ->getDownloadableHistory($this->getCustomerId());

        $purchasedLinks->addFieldToFilter('order.increment_id' , array('in' => $ordersIds));
        $this->setPurchasedLinks($purchasedLinks);
        
        
        
        $vpager = $this->getLayout()->createBlock('page/html_pager', 'downloadable.customer.vproducts.pager')
            ->setCollection($this->getVpurchasedProducts());
        $this->setChild('vdownloadable_pager', $vpager);
        $this->getVpurchasedProducts()->load();

        //collect current order's ids
        $vordersIds = array();
        foreach ($this->getVpurchasedProducts() as $_product){
            $vordersIds[] = $_product->getIncrementId();
        }

        if (empty($vordersIds)){
            $vordersIds = array(null);
        }

        //load purchased Links collection by order's Ids
        $vpurchasedLinks = Mage::getResourceModel('ecodes/downloadable_collection')
            ->getDownloadableHistory($this->getCustomerId());

        $vpurchasedLinks->addFieldToFilter('order.increment_id' , array('in' => $vordersIds));
        $this->setVpurchasedLinks($vpurchasedLinks);
        

        return $this;
    }

    public function getPagerHtml()
    {
        return $this->getChildHtml('downloadable_pager');
    }
    public function getPagerHtmlv()
    {
        return $this->getChildHtml('vdownloadable_pager');
    }
    

    /**
     * @return ICC_Ecodes_Model_Mysql4_Downloadable_Collection|object
     */
    public function getDownloadableHistory()
    {
        $downloadableHistory = Mage::getResourceModel('ecodes/downloadable_collection')
            ->getDownloadableHistory($this->getCustomerId());

        return $downloadableHistory;
    }

    /**
     * @return mixed
     */
    public function getCustomerId()
    {
        if(!$this->getData('customer_id'))
        {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            if($customerId = $customer->getId())
            {
                $this->setData('customer_id', $customerId);
            }
        }

        return $this->getData('customer_id');
    }

    /**
     * @param $datetime
     * @return mixed
     */
    public function convertDateTime($datetime)
    {
        if(!$datetime) return false;
        $datetime = $this->getLocale()
            ->date(strtotime($datetime), null, null, false)->toString(self::ECODES_DOWNLOADABLE_DATETIME_FORMAT);
        return $datetime;
    }

    /**
     * Return url to download link
     *
     * @param ICC_Ecodes_Model_Downloadable|Varien_Object $item
     * @return string
     */
    public function getDownloadUrl($item)
    {
        return $this->getUrl('downloadable/download/link', array('id' => $item->getLinkHash(), '_secure' => true));
    }

    /**
     * @return mixed
     */
    public function getLocale()
    {
        if(!$this->getData('locale'))
        {
            $this->setData('locale', Mage::app()->getLocale());
        }

        return $this->getData('locale');
    }
}


/*
 * Queryy for all link in one grid.
 * 
SELECT `ecodes`.`serial`, `dlpi`.`number_of_downloads_bought`, `dlpi`.`number_of_downloads_used`,
        `dlpi`.`link_hash`, `dlpi`.`link_id`, `dlpi`.`status`, `dlpi`.`link_title`, `dlpi`.`product_id`, 
        `order_item`.`created_at`, `order_item`.`name`, `order`.`increment_id` FROM `downloadable_link_purchased` AS `main_table` 
        LEFT JOIN `ecodes_downloadable` AS `ecodes` ON main_table.order_item_id = ecodes.order_item_id LEFT JOIN `downloadable_link_purchased_item` AS `dlpi` 
        ON main_table.purchased_id = dlpi.purchased_id LEFT JOIN `sales_flat_order_item` AS `order_item` ON `main_table`.`order_item_id` = `order_item`.`item_id` 
        LEFT JOIN `sales_flat_order` AS `order` ON `order_item`.`order_id` = `order`.`entity_id` WHERE (main_table.customer_id = '162852') 
        AND (ecodes.enabled = 1 OR ecodes.enabled is null) AND (dlpi.status NOT IN('pending_payment', 'payment_review', 'refunded', 'deleted')) 
        AND (order.volume_users is null) 
        AND ( (order.parent_order_id is not null AND order.future_email is null )OR(order.parent_order_id is null) ) GROUP BY `order`.`increment_id` 
 * 
 * 
 */