<?php
/**
 * ICC_Freeresources extension
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 * 
 * @category       ICC
 * @package        ICC_Freeresources
 * @copyright      Copyright (c) 2015
 * @license        http://opensource.org/licenses/mit-license.php MIT License
 */
/**
 * Free Resource front contrller
 *
 * @category    ICC
 * @package     ICC_Freeresources
 * @author      Ultimate Module Creator
 */
class ICC_Freeresources_FreeresourceController
    extends Mage_Core_Controller_Front_Action {
    /**
      * default action
      * @access public
      * @return void
      * @author Ultimate Module Creator
      */
    public function indexAction() {
        $freeresourceId  = (int) $this->getRequest()->getParam('id');
        $subCategoryId = (int) $this->getRequest()->getParam('subcatid', 0);
        $collections = Mage::getModel('icc_freeresources/freeresource_category')->getCollection()->addFieldToFilter('freeresource_id',$freeresourceId);
        $values = '<select class=" select" name="category[category_id]" id="category_category_id">';
        if ($subCategoryId != 0) {
            $subCategoryCollection = Mage::getModel('icc_freeresources/freeresource_subcategory')->load($subCategoryId);
        }
        foreach ($collections as $key => $collection) {
            if ($subCategoryId != 0 && $subCategoryCollection->getCategoryId() != 0 && $collection->getCategoryId() == $subCategoryCollection->getCategoryId() ) {
                $values .= '<option value="'.$collection->getCategoryId().'" selected>'.$collection->getTitle().'</option>';
            } else {
                $values .= '<option value="'.$collection->getCategoryId().'">'.$collection->getTitle().'</option>';
            }
        }
        echo $values .= '</select>';
    }
}
