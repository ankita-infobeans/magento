<?php

class Intersec_Orderimportexport_Block_Adminhtml_System_Convert_Profile_Grid extends Mage_Adminhtml_Block_System_Convert_Profile_Grid
{
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('dataflow/profile_collection')
            ->addFieldToFilter('entity_type', array('null'=>''))
            ->addFieldToFilter('is_intersec', 0);

        $this->setCollection($collection);
    }
}

