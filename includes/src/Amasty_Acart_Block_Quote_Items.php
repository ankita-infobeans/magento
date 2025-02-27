<?php
    class Amasty_Acart_Block_Quote_Items extends Mage_Catalog_Block_Product_Abstract
    {
        
        protected $_params = array(
            'mode' => array(
                'default' => 'table',
                'available' => array(
                    'list', 'table'
                )
            ),
            'image' => array(
                'default' => 'yes',
                'available' => array(
                    'yes', 'no'
                )
            ),
            'price' => array(
                'default' => 'yes',
                'available' => array(
                    'yes', 'no'
                )
            ),
            'priceFormat' => array(
                'default' => 'exculdeTax',
                'available' => array(
                    'exculdeTax', 'includeTax'
                )
            ),
            'descriptionFormat' => array(
                'default' => 'short',
                'available' => array(
                    'short', 'full'
                )
            ),
            'discount' => array(
                'default' => 'yes',
                'available' => array(
                    'yes', 'no'
                )
            ),
        );
        
        public function __construct()
            {
            parent::__construct();

            $this->setTemplate('amacart/items.phtml');
            }

        protected function _getLayoutParam($key){
            return in_array($this->$key, $this->_params[$key]['available']) ? $this->$key : $this->_params[$key]['default'];
        }
        
        public function getMode(){
            return $this->_getLayoutParam('mode');
        }
        
        public function showImage(){
            return $this->_getLayoutParam('image') == 'yes';
        }

        public function showPrice(){
            return $this->_getLayoutParam('price') == 'yes';
        }
        
        public function showShortDescription(){
            return $this->_getLayoutParam('descriptionFormat') == 'short';
        }
        
        public function showPriceIncTax(){
            return $this->_getLayoutParam('priceFormat') == 'includeTax';
        }
        
        public function showDiscount(){
            return $this->_getLayoutParam('discount') == 'yes';
        }

        public function getDiscountPrice($price){
            $discountPrice = $price;

            $sceduleId = $this->getHistory()->getScheduleId();
            $schedule = Mage::getModel('amacart/schedule')->load($sceduleId);
                    
            switch($schedule->getCouponType()){
                case "by_percent":
            
                        $discountPrice -= $discountPrice * $schedule->getDiscountAmount() / 100;
                    break;
                case "by_fixed":
                        $discountPrice -= $schedule->getDiscountAmount();
                    break;
            }
            
            return $discountPrice;
        }
    }
?>


