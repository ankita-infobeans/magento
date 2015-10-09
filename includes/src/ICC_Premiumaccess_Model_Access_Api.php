<?php
/**
 * @category       ICC
 * @package        ICC_Premiumaccess
 */
class ICC_Premiumaccess_Model_Access_Api  extends Mage_Api_Model_Resource_Abstract
{
    /**
     * Function to get all premiumAccess product information 
     */
    public function items($email = null)
    {
        $customer = Mage::getModel("customer/customer");
        $customer->setWebsiteId(1);
        $customer->loadByEmail($email);
        if($customer):
        $childproducts = Mage::getModel('sales/order_item')->getCollection();
        $sales_order_tables= Mage::getSingleton('core/resource')->getTableName('sales_flat_order'); 
        $childproducts->getSelect()->joinLeft(array('sales_order'=>$sales_order_tables),'`main_table`.`order_id` = `sales_order`.`entity_id`');
        $childproducts->addFieldToFilter('sales_order.customer_id',array('eq'=>$customer->getId()));
        $childproducts->addFieldToFilter('sales_order.parent_order_id',array('notnull'=>true));
        $childproducts->addFieldToFilter('sales_order.premium_access',array('eq'=>1));
        //$childproducts->addFieldToFilter('main_table.premium_access',array('eq'=>1));
        $childproducts->addFieldToFilter('sales_order.status',array('neq'=>'canceled'));
	$childproducts->addFieldToFilter('sales_order.status',array('neq'=>'pending'));
       // $childproducts->getSelect()->where('main_table.expirydate >= "' . date('Y-m-d H:i:s').'"');
        Mage::log('api'.$childproducts->getSelect(), null, 'premiumaccessapi.log');

        $result=array();
        $bundle = array();
        $i = 0;
        $date = date('Y-m-d H:i:s');
        foreach ($childproducts as $collection) {
            if ($collection->getProductType() == 'bundle') {
                $bundle[$collection->getItemId()] = $collection->getExpirydate();
            } else                
            {
                if((array_key_exists($collection->getParentItemId(), $bundle)) && ($collection->getProductType() == 'virtual')) {
		if ((strtotime($bundle[$collection->getParentItemId()])) > time()) {
		        $result[$i]['sku'] = $collection->getSku(); 
		        $result[$i]['expiration'] = $bundle[$collection->getParentItemId()];
		        $i++;
                    }
                } else {
                    if (($collection->getPremiumAccess() == '1') && ($collection->getProductType() == 'virtual')) {
                        if ((strtotime($collection->getExpirydate())) > time()) {
                            $result[$i]['sku'] = $collection->getSku(); 
                            $result[$i]['expiration'] = $collection->getExpirydate();
                            $i++;
                            }
                    }
                }
            }
        }

        return $result;
        else:
            return 0;
        endif;
        
    }
}
