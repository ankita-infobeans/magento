<?php
/**
* @author Amasty Team
* @copyright Amasty
* @package Amasty_Acart
*/
class Amasty_Acart_Model_Observer 
{
    function onSalesOrderPlaceAfter($observer){
        
        $order = $observer->getOrder();
        $quote = $order->getQuote();
        if ($quote){
//          
            Mage::getModel('amacart/schedule')->buyQuote($quote);
        }
    }
    
    public function handleBlockOutput($observer)
    {
        
        $block = $observer->getBlock();
        
        $transport = $observer->getTransport();
        $html = $transport->getHtml();
//        var_dump(get_class($block));
        if ($block instanceof Mage_Checkout_Block_Onepage
                || $block instanceof TM_FireCheckout_Block_Checkout
                || $block instanceof Magestore_Onestepcheckout_Block_Onestepcheckout
                ) {
            $html = $this->_prepareOnepageHtml($transport);
        }
        
        $transport->setHtml($html);
    }
    
    protected function _prepareOnepageHtml($transport){
        $html = $transport->getHtml();
        $js = array('<script>');
        
        $js[] = "
            
            
                if (typeof(amacartEventsHandled) == 'undefined'){
                function amcartValidateEmail(email) { 
                    var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                    return re.test(email);
                }

                var amcarttimers = null;

                function amcartAjaxCall(value){
                    new Ajax.Request('" . Mage::getUrl('amacartfront/main/email', array('_secure'=>true)) . "', {
                        parameters: {
                            value: value
                        },
                        onSuccess: function(response) {

                        }
                  });
                }

                function amcartHandleEmailKeyUp(e, input){
                    var value = $(input).value;
                    if (amcartValidateEmail(value)){

                        if (amcarttimers != null){
                            clearTimeout(amcarttimers);
                        }

                        amcarttimers = setTimeout(function(){
                            amcartAjaxCall(value)
                        }, 500);
                    }
                }
                $(document).on('keyup', '[id=\"login-email\"]', amcartHandleEmailKeyUp);
                $(document).on('keyup', '[name=\"billing[email]\"]', amcartHandleEmailKeyUp);
            }
            amacartEventsHandled = true;
            
        ";
        
        $js[] = '</script>';
        
        return $html.implode('', $js);
    }
    
    function clearCoupons(){
        $allCouponsCollection = Mage::getModel('salesrule/rule')->getCollection();
        
        $allCouponsCollection->join(

            array('history' => 'amacart/history'),
            'main_table.rule_id = history.sales_rule_id', 
            array('history.history_id')
        );
        
        $allCouponsCollection->getSelect()->where(
            'main_table.to_date < "'.date('Y-m-d', time()).'"'
        );
        
        foreach ($allCouponsCollection->getItems() as $aCoupon) {
            $aCoupon->delete();
        }
    }
    
    function refreshHistory(){
        Mage::getModel('amacart/schedule')->run();
    }
    
    /**
     * Append rule product attributes to select by quote item collection
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_SalesRule_Model_Observer
     */
    public function addProductAttributes(Varien_Event_Observer $observer)
    {
        // @var Varien_Object
        $attributesTransfer = $observer->getEvent()->getAttributes();

        $attributes = Mage::getResourceModel('amacart/rule')->getAttributes();
        
        $result = array();
        foreach ($attributes as $code) {
            $result[$code] = true;
        }
        
        $attributesTransfer->addData($result);
        
        return $this;
    } 
}
?>