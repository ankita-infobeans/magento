<?php

class ICC_NewBundleProduct_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_NODE_NEW_BUNDLE_PRODUCT_TYPE      = 'global/catalog/product/type/bundle';

    /**
     * Retrieve array of allowed product types for bundle selection product
     *
     * @return array
     */
    public function getAllowedSelectionTypes()
    {
        $config = Mage::getConfig()->getNode(self::XML_NODE_NEW_BUNDLE_PRODUCT_TYPE);
        return array_keys($config->allowed_selection_types->asArray());
    }
}