<?php

class ICC_Ecodes_Block_Adminhtml_Managepremium_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    protected function _construct()
    {
        parent::_construct();
        $this->_objectId = 'id';
        $this->_blockGroup = 'ecodes'; // this is the xml handle
        $this->_controller = 'adminhtml_managepremium'; // path underneath "block" folder
        $this->_mode = 'edit';
    }
    
    protected function _prepareLayout() 
    {
        parent::_prepareLayout();
        $this->_updateButton('save', 'label', $this->__('Save Changes'));
        $this->removeButton('delete');
//        $this->removeButton('back');
        $request = $this->getRequest();
        $this->_updateButton('back', 'onclick', 'setLocation(\'' . $this->getUrl('*/managepremium/list', array('id' => $request->getParam('id'), 'subscription_id' => $request->getParam('subscription_id')) ) . '\')');
//        $this->_addButton('save_and_continue', array(
  //          'label' => $this->__('Save and Continue Edit'),
            //'onclick' => 'saveAndContinueEdit()',
    //        'class' => 'save',
      //  ), 100);
        //$this->_formScripts[] = "
       //     function saveAndContinueEdit () {
        //        editForm.submit($('edit_form').action ='back/edit';
        //    }
       // ";
  //      Mage::log('in block premium edit and preparing the layout ', null, 'admin-premium-edit.log');
        return $this;
    }
    
    public function getHeaderText()
    {
        //$id = (int)$this->getRequest()->getParam('id');
        //$user = Mage::getModel('ecodes/premiumusers')->load($id);
        return 'Premium Subscription User'; //$user->getName();
    }
}