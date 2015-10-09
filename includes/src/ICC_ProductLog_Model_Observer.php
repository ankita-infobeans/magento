<?php
class ICC_ProductLog_Model_Observer
{
    /** observer for tracking changes of product tax class id */

    public function afterProductSave(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        $taxClassOrig = $product->getOrigData('tax_class_id');
        $taxClassNew = $product->getData('tax_class_id');

        $taxClass = array(
            '0' => 'None',
            '2' => 'Taxable Goods',
            '4' => 'Shipping',
            '6' => 'Downloadable'
        );

        if($taxClassNew != $taxClassOrig) {
            $logStatement = 'Product '.$product->getName().' ('.$product->getSku().') Tax Class changed from '.$taxClass[$taxClassOrig].' to '.$taxClass[$taxClassNew];
            if(Mage::app()->getStore()->isAdmin() && Mage::getDesign()->getArea() == 'adminhtml')
            {
                if($admin_session = Mage::getSingleton('admin/session')) {
                    $admin_user = $admin_session->getUser()->getUsername();
                    Mage::log($logStatement.' by admin user: '.$admin_user, null, 'tax_class_changes.log');
                }
                else {
                    Mage::log($logStatement, null, 'tax_class_changes.log');
                }
            }   
            else {
                Mage::log($logStatement, null, 'tax_class_changes.log');
            }
        }

        return $this;
    }
    
    public function beforeProductSave(Varien_Event_Observer $observer)
    {
        if($observer->getEvent()->getProduct()){
            $Product=$observer->getEvent()->getProduct();
            if($Product->getData('url_key') == ''):
            $url = Mage::getModel('catalog/product')->load($Product->getData('entity_id'))->getUrlKey();
                if($url !=''):
                    $Product->setData('url_key',$url);
                endif;
            endif;  
        }
    }

}
