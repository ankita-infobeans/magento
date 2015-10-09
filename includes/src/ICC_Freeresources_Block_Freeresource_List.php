<?php
/**
 * Free Resource list block
 *
 * @category    ICC
 * @package     ICC_Freeresources
  */
class ICC_Freeresources_Block_Freeresource_List
    extends Mage_Core_Block_Template {
    /**
     * initialize
     * @access public
     */
     public function __construct(){
        parent::__construct();
        $freeresources = Mage::getResourceModel('icc_freeresources/freeresource_subcategory_freeresource_collection');
        $this->setFreeresources($freeresources);
    }
    
    /**
     * prepare the layout
     * @access protected
     * @return ICC_Freeresources_Block_Freeresource_List
     */
    protected function _prepareLayout(){
        parent::_prepareLayout();
        $pager = $this->getLayout()->createBlock('page/html_pager', 'icc_freeresources.freeresource.html.pager')
            ->setCollection($this->getFreeresources());
        $this->setChild('pager', $pager);
        $this->getFreeresources()->load();
        return $this;
    }

    /**
     * get the pager html
     * @access public
     * @return string
     */
    public function getPagerHtml(){
        return $this->getChildHtml('pager');
    }
    
    public function getSubCategoryList($categoryId)
    {
        $dir = $this->getRequest()->getParam('dir');
        $freeresources = Mage::getResourceModel('icc_freeresources/freeresource_subcategory_freeresource_collection')->addFieldToFilter('status',1);
        $array = array();
	$array['dir'] = ($dir == 'asc') ? 'desc' : 'asc';
        foreach ($freeresources as $subcategory_freeresource) {
            $array['val'][$subcategory_freeresource->getFreeresourceId()]['title'] = $subcategory_freeresource->getFrTitle();
            $array['val'][$subcategory_freeresource->getFreeresourceId()]['category'][$subcategory_freeresource->getCategoryId()] = $subcategory_freeresource->getCtTitle();
            $array['val'][$subcategory_freeresource->getFreeresourceId()][$subcategory_freeresource->getCategoryId()]['subcategory'][$subcategory_freeresource->getTitle()]['description'] = $subcategory_freeresource->getDescription();
            $array['val'][$subcategory_freeresource->getFreeresourceId()][$subcategory_freeresource->getCategoryId()]['subcategory'][$subcategory_freeresource->getTitle()]['product_title'] = $subcategory_freeresource->getProductTitle();
            $array['val'][$subcategory_freeresource->getFreeresourceId()][$subcategory_freeresource->getCategoryId()]['subcategory'][$subcategory_freeresource->getTitle()]['product_link'] = $subcategory_freeresource->getProductLink();
            $array['val'][$subcategory_freeresource->getFreeresourceId()][$subcategory_freeresource->getCategoryId()]['subcategory'][$subcategory_freeresource->getTitle()]['image_url'] = $subcategory_freeresource->getImageUrl();
            $array['val'][$subcategory_freeresource->getFreeresourceId()][$subcategory_freeresource->getCategoryId()]['subcategory'][$subcategory_freeresource->getTitle()]['image_url_link'] = $subcategory_freeresource->getImageUrlLink();
            $array['val'][$subcategory_freeresource->getFreeresourceId()][$subcategory_freeresource->getCategoryId()]['subcategory'][$subcategory_freeresource->getTitle()]['link_to_content'] = $subcategory_freeresource->getLinkToContent();
            $array['val'][$subcategory_freeresource->getFreeresourceId()][$subcategory_freeresource->getCategoryId()]['subcategory'][$subcategory_freeresource->getTitle()]['download_url'] = $subcategory_freeresource->getDownloadUrl();
            $array['val'][$subcategory_freeresource->getFreeresourceId()][$subcategory_freeresource->getCategoryId()]['subcategory'][$subcategory_freeresource->getTitle()]['download_text'] = $subcategory_freeresource->getDownloadText();
          }
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            foreach ($array['val'][$id]['category'] as $key => $category) {
                if ($dir == 'desc') {
                    krsort($array['val'][$id][$key]['subcategory']);
                } else { 
                    ksort($array['val'][$id][$key]['subcategory']);
                }
            }
        }
        return $array;
         
    }
}
