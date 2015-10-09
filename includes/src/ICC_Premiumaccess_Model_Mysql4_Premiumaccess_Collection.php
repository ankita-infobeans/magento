<?php
    class ICC_Premiumaccess_Model_Mysql4_Premiumaccess_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
    {

		public function _construct(){
			$this->_init("icc_premiumaccess/premiumaccess","id");
		}

                /**
                 * This method returns all premium access collction.
                 * @return \ICC_Premiumaccess_Model_Mysql4_Premiumaccess_Collection
                 */
                public function getAll() {
                        $this->getSelect();		
                        return $this;
                }

                /**
                 * This method returns premium access collection by order item id  
                 * @param type $id
                 * @return \ICC_Premiumaccess_Model_Mysql4_Premiumaccess_Collection
                 */
                public function getByOrderLineItemId($id) {
                        $this->getSelect()->where('order_item_id = ' . (int)$id);
                        return $this;
                }

                /**
                 * This method return collection of premium access by customer id 
                 * @param type $id
                 * @return \ICC_Premiumaccess_Model_Mysql4_Premiumaccess_Collection
                 */
                public function getRegisteredByCustomerId($id) {
                        $this->getSelect()->where('customer_id = ' . (int)$id .' and '.' status = "1"');
                        return $this;
                }

                /**
                 * This method return premium access collection by sku base
                 * @param type $sku
                 * @return \ICC_Premiumaccess_Model_Mysql4_Premiumaccess_Collection
                 */
                public function getBySku($sku) {
                        $this->getSelect()->where('sku = "' . $sku.'"');
                        return $this;
                }

                /**
                 * this method used to return premium access collection of having expriry date greater than current time
                 * @return \ICC_Premiumaccess_Model_Mysql4_Premiumaccess_Collection
                 */
                public function getByExpiration() {
                        $this->getSelect()->where('expiration >= "' . date('Y-m-d H:i:s').'"');
                        return $this;
                }
                /**
                 * This method returns collction of premium access having not completely shared premium access 
                 * @return \ICC_Premiumaccess_Model_Mysql4_Premiumaccess_Collection
                 */
                public function getNotifySubscription(){
                    $this->getSelect()->where('seats_total > registered_count');
                    return $this;
                }
    }
	 
