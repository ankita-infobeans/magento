<?php
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class ICC_MultipleCoupons_Model_Resource_Coupon extends Mage_SalesRule_Model_Resource_Coupon
{
    /**
     * Constructor adds unique fields
     */
    protected function _construct()
    {
        $this->_init('salesrule/coupon', 'coupon_id');
       /* $this->addUniqueField(array(
            'field' => 'code',
            'title' => Mage::helper('salesrule')->__('Coupon with the same code')
        ));*/
    }

    
}
