<?php
/**
 * Free Resource category edit form tab
 *
 * @category    ICC
 * @package     ICC_Freeresources
 */
class ICC_Freeresources_Block_Adminhtml_Freeresource_Category_Edit_Tab_Form
    extends Mage_Adminhtml_Block_Widget_Form {
    /**
     * prepare the form
     * @access protected
     * @return Freeresources_Freeresource_Block_Adminhtml_Freeresource_Category_Edit_Tab_Form
     */
    protected function _prepareForm(){
        $freeresource = Mage::registry('current_freeresource');
        $category    = Mage::registry('current_category');
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('category_');
        $form->setFieldNameSuffix('category');
        $this->setForm($form);
        $fieldset = $form->addFieldset('category_form', array('legend'=>Mage::helper('icc_freeresources')->__('Category')));

        $fieldset->addField('freeresource_id', 'select', array(
            'name'  => 'freeresource_id',
            'label'     => 'Free Resource',
            'values'    => Mage::getModel('icc_freeresources/freeresource_category')->getAllOptions(),
         ));
        $fieldset->addField('title', 'text', array(
            'label' => Mage::helper('icc_freeresources')->__('Title'),
            'name'  => 'title',
            'required'  => true,
            'class' => 'required-entry',
        ));
  

        $form->addValues($this->getCategory()->getData());
        return parent::_prepareForm();
    }
    /**
     * get the current category
     * @access public
     * @return ICC_Freeresources_Model_Freeresource_Category
     */
    public function getCategory(){
        return Mage::registry('current_category');
    }
}