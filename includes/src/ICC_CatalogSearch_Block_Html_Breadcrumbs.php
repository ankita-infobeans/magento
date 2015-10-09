<?php

class ICC_Catalogsearch_Block_Html_Breadcrumbs extends Mage_Page_Block_Html_Breadcrumbs
{
    /**
     * Array of breadcrumbs
     *
     * array(
     *  [$index] => array(
     *                  ['label']
     *                  ['title']
     *                  ['link']
     *                  ['first']
     *                  ['last']
     *              )
     * )
     *
     * @var array
     */
    protected $_crumbs = null;

    /**
     * Cache key info
     *
     * @var null|array
     */
    protected $_cacheKeyInfo = null;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('page/html/breadcrumbs.phtml');
    }

    protected function _toHtml() {

        if ($this->getRequest()->getParam('cat', false)) {
            $cat_id = $this->getRequest()->getParam('cat', false);
            $category = Mage::getModel('catalog/category')->load($cat_id);
            $cat_name = $category->getName();
            $cat_url = $this->getBaseUrl() . $category->getUrlPath();
            $parents = Mage::getModel('catalog/category')->load($cat_id)->getParentCategories();
         }

        if (is_array($this->_crumbs)) {
            reset($this->_crumbs);
            $this->_crumbs[key($this->_crumbs)]['first'] = true;
            end($this->_crumbs);
            $this->_crumbs[key($this->_crumbs)]['last'] = true;
        }

        if ($cat_id) {
            if($parents){
                foreach($parents as $p){
                    $cat = Mage::getModel('catalog/category')->load($p->getId());
                    $this->_crumbs['category' . $cat->getId()] = array('label' => $cat->getName(), 'title' => $cat->getName(), 'link' => $cat->getUrlPath(), 'first' => '', 'last' => '', 'readonly' => '');
                }
                
            }
           $home = $this->_crumbs['home'];
            unset($this->_crumbs['home']);
            array_unshift($this->_crumbs, $home);
            //move search to the end
            $search = $this->_crumbs['search'];
            unset($this->_crumbs['search']);
            $this->_crumbs['search'] = $search;
        }
        $this->assign('crumbs', $this->_crumbs);
        return parent::_toHtml();
    }

}
