<?php

class ICC_Ecodes_Helper_Downloadable extends Mage_Core_Helper_Abstract
{
    const SERIALS_HISTORY_HEADER = "SERIAL NUMBER ASSIGNEMT: \n\n";
    const SERIALS_ASSIGNED_HISTORY_MESSAGE = "Serial numbers %s were added to the order. \n";
    const SERIALS_NOT_ASSIGNED_HISTORY_MESSAGE = 'There were %s serial numbers added to the Queue';
    const DEFAULT_MISSING_SERIAL_MESSAGE = 'There are currently no serial numbers available for this product, please check your account soon.';
    const LINK_STATUS_REFUNDED   = 'refunded';
    const LINK_STATUS_DELETED   = 'deleted';
    const SERIALS_VOLUME_LICENSE_PARENT_ORDER_MESSAGE = "Serial Key will be assign to child orders only.";

    /**
     * @param array $serialsAssigned
     * @param int $numSerialsNotAssigned
     * @param bool $globalMessageFlag
     * @return string
     */
    public function generateSerialsAssignedHistoryComment(array $serialsAssigned, $numSerialsNotAssigned,
                                                          $globalMessageFlag = false
    )
    {
        $historyMessage = false;
        if(!$globalMessageFlag)
        {
            $historyMessage = self::SERIALS_HISTORY_HEADER;
        }
        if($serialsAssigned)
        {
            $historyMessage .= sprintf(self::SERIALS_ASSIGNED_HISTORY_MESSAGE, implode(', ', $serialsAssigned));
        }
        if($numSerialsNotAssigned)
        {
            $historyMessage .= sprintf(self::SERIALS_NOT_ASSIGNED_HISTORY_MESSAGE, $numSerialsNotAssigned);
        }
        return $historyMessage;
    }

    public function renderSerialOptionOnOrderItem(Mage_Sales_Model_Order_Item $orderItem, $noSerialsAvailableMessage = null, $checkIfSerialRequired = false)
    {
        
        if ($checkIfSerialRequired) {
            $product = Mage::getModel('catalog/product')->load($orderItem->getProductId());
            if (!$product->getSerialRequired()) {
                return $this;
            }
        }
        
        if (is_null($noSerialsAvailableMessage)) {
            $noSerialsAvailableMessage = self::DEFAULT_MISSING_SERIAL_MESSAGE;
        }
        
        $downloadableCollection = Mage::getModel('ecodes/downloadable')->getCollection();
        $assignedSerials = $downloadableCollection->getAssignedSerials($orderItem);
        $order=Mage::getModel('sales/order')->load($orderItem->getOrderId());
        if((($order->getVolumeLicense()!=0)&&($order->getParentOrderId()==NULL))){
            $noSerialsAvailableMessage = self::SERIALS_VOLUME_LICENSE_PARENT_ORDER_MESSAGE;
        }
        $orderItemProductOptions = $orderItem->getProductOptions();
        $value = $assignedSerials ? implode(',', $assignedSerials) : $noSerialsAvailableMessage;
        $orderItemProductOptions['options']['ecodes_downloadable_serials']['label'] = 'Assigned Serials';
        $orderItemProductOptions['options']['ecodes_downloadable_serials']['value'] = $value;
        $orderItem->setProductOptions($orderItemProductOptions);

        return $this;
    }
}