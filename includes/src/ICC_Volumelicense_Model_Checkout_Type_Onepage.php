<?php
/**
 * @category   ICC
 * @package    ICC_Volumelicense
 */
class ICC_Volumelicense_Model_Checkout_Type_Onepage extends ICC_Checkout_Model_Type_Onepage
{
    public function saveVolumelicense($data) {
        
        $session = Mage::getSingleton('checkout/session');
        $quoteId = $session->getQuoteId();
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $connection->beginTransaction();
        $__fields = array();
        $__fields['volume_users'] = json_encode($data);
        $__where = $connection->quoteInto('entity_id =?', $quoteId);
        $connection->update('sales_flat_quote', $__fields, $__where);
        $connection->commit();
        $this->getCheckout()
                ->setStepData('volumelicense', 'allow', true)
                ->setStepData('volumelicense', 'complete', true)
                ->setStepData('payment', 'allow', true);

        return array();
    }

    public function savePremiumaccess($data) {
        /* if (empty($data)) {
          return array('error' => -1, 'message' => $this->_helper->__('Invalid data.'));
          } */
        /* foreach ($data as $itemId => $value){
          $QuoteItem = Mage::getModel('sales/quote_item')->load($itemId);
          $QuoteItem->setPremiumAccess(true);
          } */
        $session = Mage::getSingleton('checkout/session');
        $quoteId = $session->getQuoteId();
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $connection->beginTransaction();
        $__fields = array();
        $__fields['premium_access'] = 1;
        $__fields['premium_users'] = serialize($data);
        $__where = $connection->quoteInto('entity_id =?', $quoteId);
        $connection->update('sales_flat_quote', $__fields, $__where);
        $connection->commit();
        $this->getCheckout()
                ->setStepData('premiumaccess', 'allow', true)
                ->setStepData('premiumaccess', 'complete', true)
                ->setStepData('payment', 'allow', true);
        return array();
    }

}
