<?php
class Gorilla_Catalog_Model_Observer
{

    public function getAllAttributes(Varien_Event_Observer $observer)
    {
        $event = $observer->getEvent();
        $collection = $event->getCollection();
        $collection->addAttributeToSelect('*');

        return $this;
    }

}