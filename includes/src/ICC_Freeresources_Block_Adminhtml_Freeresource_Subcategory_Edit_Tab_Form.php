<?php
/**
 * Free Resource category edit form tab
 *
 * @category    ICC
 * @package     ICC_Freeresources
  */
class ICC_Freeresources_Block_Adminhtml_Freeresource_Subcategory_Edit_Tab_Form
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
        
        $fieldset->addField('subcategory_id', 'hidden', array(
            'name'  => 'subcategory_id',
            'label' => 'Sub Category Id',
            'required'  => false,
        ));
        
       $event = $fieldset->addField('freeresource_id', 'select', array(
            'name'  => 'freeresource_id',
            'label'     => 'Free Resource',
            'values'    => Mage::getModel('icc_freeresources/freeresource_category')->getAllOptions(),
            'onchange'  => 'checkSelectedItem(this)',
            'required'  => true,
        ));
        
        $event->setAfterElementHtml("<script type=\"text/javascript\">
        function checkSelectedItem(selectElement) {
            var category_subcategory_id = document.getElementById('category_subcategory_id').value;
            var reloadurl = '". $this->getUrl('icc_freeresources/freeresource')."id/' + selectElement.value+'/subcatid/' + category_subcategory_id;
            new Ajax.Request(reloadurl, {
                method: 'get',
                onLoading: function (transport) {
                    $('category_category_id').update('Searching...');
                },
                onComplete: function(transport) {
                        $('category_category_id').update(transport.responseText);
                        disableSelectedItem();
                }
            });
            
        }
        checkSelectedItem(document.getElementById('category_freeresource_id'));
</script>");
        
        $fieldset->addField('category_id', 'select', array(
           'name'  => 'category_id',
           'label'     => 'Category',
           'values'    => Mage::getModel('icc_freeresources/freeresource_subcategory')->getAllOptions(),
           'required'  => true,
        ));
        
        $fieldset->addField('title', 'text', array(
            'label' => Mage::helper('icc_freeresources')->__('Title'),
            'name'  => 'title',
            'required'  => true,
            'class' => 'required-entry',
        ));
        
        $fieldset->addField('description', 'editor', array (
            'name'      => 'description',
            'label'     => Mage::helper('icc_freeresources')->__('Description'),
            'title'     => Mage::helper('icc_freeresources')->__('Description'),
            'style'     => 'height:15em; width:50em;',
            'config'    => Mage::getSingleton('cms/wysiwyg_config')->getConfig(),
            'wysiwyg'   => true,
            'required'  => false,
        ));
        
        $fieldset->addField('product_title', 'text', array(
            'label' => Mage::helper('icc_freeresources')->__('Product Title'),
            'name'  => 'product_title',
            'required'  => FALSE,
        ));
        
        $fieldset->addField('product_link', 'text', array(
            'label' => Mage::helper('icc_freeresources')->__('Product Link'),
            'name'  => 'product_link',
            'note'    => Mage::helper('icc_freeresources')->__('Relative to Website Base URL')
        ));
        
         $fieldset->addField('image_url', 'text', array(
            'label' => Mage::helper('icc_freeresources')->__('Image URL'),
            'name'  => 'image_url',
            'required'  => false,
        ));
        
        $fieldset->addField('image_url_link', 'text', array(
            'label' => Mage::helper('icc_freeresources')->__('Image Url Link'),
            'name'  => 'image_url_link',
            'note'    => Mage::helper('icc_freeresources')->__('Relative to Website Base URL')
        ));
        
        $fieldset->addField('link_to_content', 'editor', array (
            'name'      => 'link_to_content',
            'label'     => Mage::helper('icc_freeresources')->__('Link To Content'),
            'title'     => Mage::helper('icc_freeresources')->__('Link To Content'),
            'style'     => 'height:15em; width:50em;',
            'config'    => Mage::getSingleton('cms/wysiwyg_config')->getConfig(),
            'wysiwyg'   => true,
            'required'  => false,
        ));
        
          $fieldset->addField('download_url', 'text', array(
            'label' => Mage::helper('icc_freeresources')->__('Download Url'),
            'name'  => 'download_url',
            'required'  => FALSE,
        ));
        
        $hideShow = $fieldset->addField('download_text', 'text', array(
            'label' => Mage::helper('icc_freeresources')->__('Download Text'),
            'name'  => 'download_text',
            'note'    => Mage::helper('icc_freeresources')->__('Relative to Website Base URL')
        ));
        
        $hideShow->setAfterElementHtml("<script type=\"text/javascript\">
            function disableSelectedItem() {
                var index = document.getElementById('category_freeresource_id').selectedIndex;
                var value = document.getElementById('category_freeresource_id').options[index].text;
                if (value == 'Free Codes') {
                    document.getElementById('category_product_title').disabled = false;
                    document.getElementById('category_product_link').disabled = false;

                    document.getElementById('category_download_url').disabled = true;
                    document.getElementById('category_download_text').disabled = true;
                } else {
                    document.getElementById('category_product_title').disabled = true;
                    document.getElementById('category_product_link').disabled = true;

                    document.getElementById('category_download_url').disabled = false;
                    document.getElementById('category_download_text').disabled = false;
                }
            }
</script>"); 
        
        $fieldset->addField('status', 'select', array(
            'label' => Mage::helper('icc_freeresources')->__('Status'),
            'name'  => 'status',
            'values'=> array(
                array(
                    'value' => 1,
                    'label' => Mage::helper('icc_freeresources')->__('Enabled'),
                ),
                array(
                    'value' => 0,
                    'label' => Mage::helper('icc_freeresources')->__('Disabled'),
                ),
            ),
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
