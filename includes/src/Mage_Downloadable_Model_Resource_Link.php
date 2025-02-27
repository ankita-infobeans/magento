<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition End User License Agreement
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magento.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Downloadable
 * @copyright Copyright (c) 2006-2014 X.commerce, Inc. (http://www.magento.com)
 * @license http://www.magento.com/license/enterprise-edition
 */


/**
 * Downloadable Product  Samples resource model
 *
 * @category    Mage
 * @package     Mage_Downloadable
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Downloadable_Model_Resource_Link extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Initialize connection and define resource
     *
     */
    protected function _construct()
    {
        $this->_init('downloadable/link', 'link_id');
    }

    /**
     * Save title and price of link item
     *
     * @param Mage_Downloadable_Model_Link $linkObject
     * @return Mage_Downloadable_Model_Resource_Link
     */
    public function saveItemTitleAndPrice($linkObject)
    {

        $writeAdapter   = $this->_getWriteAdapter();
        $linkTitleTable = $this->getTable('downloadable/link_title');
        $linkPriceTable = $this->getTable('downloadable/link_price');

        $select = $writeAdapter->select()
            ->from($this->getTable('downloadable/link_title'))
            ->where('link_id=:link_id AND store_id=:store_id');
        $bind = array(
            ':link_id'   => $linkObject->getId(),
            ':store_id'  => (int)$linkObject->getStoreId()
        );

        if ($writeAdapter->fetchOne($select, $bind)) {
            $where = array(
                'link_id = ?'  => $linkObject->getId(),
                'store_id = ?' => (int)$linkObject->getStoreId()
            );
            if ($linkObject->getUseDefaultTitle()) {
                $writeAdapter->delete(
                    $linkTitleTable, $where);
            } else {
                $insertData = array('title' => $linkObject->getTitle());
                $writeAdapter->update(
                    $linkTitleTable,
                    $insertData,
                    $where);
            }
        } else {
            if (!$linkObject->getUseDefaultTitle()) {
                $writeAdapter->insert(
                    $linkTitleTable,
                    array(
                        'link_id'   => $linkObject->getId(),
                        'store_id'  => (int)$linkObject->getStoreId(),
                        'title'     => $linkObject->getTitle(),
                    ));
            }
        }

        $select = $writeAdapter->select()
            ->from($linkPriceTable)
            ->where('link_id=:link_id AND website_id=:website_id');
        $bind = array(
            ':link_id'       => $linkObject->getId(),
            ':website_id'    => (int)$linkObject->getWebsiteId(),
        );
        if ($writeAdapter->fetchOne($select, $bind)) {
            $where = array(
                'link_id = ?'    => $linkObject->getId(),
                'website_id = ?' => $linkObject->getWebsiteId()
            );
            if ($linkObject->getUseDefaultPrice()) {
                $writeAdapter->delete(
                    $linkPriceTable, $where);
            } else {
                $writeAdapter->update(
                    $linkPriceTable,
                    array('price' => $linkObject->getPrice()),
                    $where);
            }
        } else {
            if (!$linkObject->getUseDefaultPrice()) {
                $dataToInsert[] = array(
                    'link_id'    => $linkObject->getId(),
                    'website_id' => (int)$linkObject->getWebsiteId(),
                    'price'      => (float)$linkObject->getPrice()
                );
                if ($linkObject->getOrigData('link_id') != $linkObject->getLinkId()) {
                    $_isNew = true;
                } else {
                    $_isNew = false;
                }
                if ($linkObject->getWebsiteId() == 0 && $_isNew && !Mage::helper('catalog')->isPriceGlobal()) {
                    $websiteIds = $linkObject->getProductWebsiteIds();
                    foreach ($websiteIds as $websiteId) {
                        $baseCurrency = Mage::app()->getBaseCurrencyCode();
                        $websiteCurrency = Mage::app()->getWebsite($websiteId)->getBaseCurrencyCode();
                        if ($websiteCurrency == $baseCurrency) {
                            continue;
                        }
                        $rate = Mage::getModel('directory/currency')->load($baseCurrency)->getRate($websiteCurrency);
                        if (!$rate) {
                            $rate = 1;
                        }
                        $newPrice = $linkObject->getPrice() * $rate;
                        $dataToInsert[] = array(
                            'link_id'       => $linkObject->getId(),
                            'website_id'    => (int)$websiteId,
                            'price'         => $newPrice
                        );
                    }
                }
                $writeAdapter->insertMultiple($linkPriceTable, $dataToInsert);
            }
        }
        return $this;
    }

    /**
     * Delete data by item(s)
     *
     * @param Mage_Downloadable_Model_Link|array|int $items
     * @return Mage_Downloadable_Model_Resource_Link
     */
    public function deleteItems($items)
    {
        $writeAdapter   = $this->_getWriteAdapter();
        $where = array();
        if ($items instanceof Mage_Downloadable_Model_Link) {
            $where = array('link_id = ?'    => $items->getId());
        } elseif (is_array($items)) {
            $where = array('link_id in (?)' => $items);
        } else {
            $where = array('sample_id = ?'  => $items);
        }
        if ($where) {            
            $writeAdapter->delete(
                $this->getMainTable(), $where);
            $writeAdapter->delete(
                $this->getTable('downloadable/link_title'), $where);
            $writeAdapter->delete(
                $this->getTable('downloadable/link_price'), $where);
            
        }
        $this->detetePurchasedItems($items);
        return $this;
    }
    
    
    protected function detetePurchasedItems($items){
        
        if ($items instanceof Mage_Downloadable_Model_Link) {             
             $download_purchased = Mage::getModel('downloadable/link_purchased_item')->getCollection();
             $download_purchased->addFieldToFilter('link_id', array('eq'=>$items->getId()));
             foreach($download_purchased as $purchased_item){
                   $data = array(
                           'status' => 'deleted'
                           );
                   $model = Mage::getModel('downloadable/link_purchased_item')->load($purchased_item->getItemId());		
                   $model->setstatus('deleted');                                       
                   $model->save();
                   //$this->deleteVolumeLicenseItem($purchased_item->getItemId());         
             }     
             
        } elseif (is_array($items)) {
            foreach($items as $item){
                $download_purchased = Mage::getModel('downloadable/link_purchased_item')->getCollection();
                $download_purchased->addFieldToFilter('link_id', array('eq'=>$item));
                foreach($download_purchased as $purchased_item){
                      $data = array(
                              'status' => 'deleted'
                              );
                      $model = Mage::getModel('downloadable/link_purchased_item')->load($purchased_item->getItemId());		
                      $model->setstatus('deleted');                                       
                      $model->save();
                      //$this->deleteVolumeLicenseItem($purchased_item->getItemId());    
                } 
            }
        }
    }
    
    
    protected function deleteVolumeLicenseItem($itemId){
        if(isset($itemId) && $itemId>0){
            $volumneLinks = Mage::getModel('volumelicense/links')->getCollection()
                              ->addFieldToFilter('pur_item_id', array('eq'=>$itemId));
            foreach ($volumneLinks as $links){
                 try{
                  $model = Mage::getModel("volumelicense/links")->load($links->getId());                  
                  $model->delete();
                 }catch (Exception $msg){
                     //SKIP THE EXCEPTION
                 } 
            }
        }
        
    }

    /**
     * Retrieve links searchable data
     *
     * @param int $productId
     * @param int $storeId
     * @return array
     */
    public function getSearchableData($productId, $storeId)
    {
        $adapter    = $this->_getReadAdapter();
        $ifNullDefaultTitle = $adapter->getIfNullSql('st.title', 's.title');
        $select = $adapter->select()
            ->from(array('m' => $this->getMainTable()), null)
            ->join(
                array('s' => $this->getTable('downloadable/link_title')),
                's.link_id=m.link_id AND s.store_id=0',
                array())
            ->joinLeft(
                array('st' => $this->getTable('downloadable/link_title')),
                'st.link_id=m.link_id AND st.store_id=:store_id',
                array('title' => $ifNullDefaultTitle))
            ->where('m.product_id=:product_id');
        $bind = array(
            ':store_id'   => (int)$storeId,
            ':product_id' => $productId
        );

        return $adapter->fetchCol($select, $bind);
    }
}
