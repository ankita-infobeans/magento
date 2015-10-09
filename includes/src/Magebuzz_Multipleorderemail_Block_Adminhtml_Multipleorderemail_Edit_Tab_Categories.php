<?php
/**
 * @category    Magebuzz
 * @package     Magebuzz_Multipleorderemail
 */
class Magebuzz_Multipleorderemail_Block_Adminhtml_Multipleorderemail_Edit_Tab_Categories extends Mage_Adminhtml_Block_Widget_Form 
{
    protected function _prepareForm()
    {
        $data = Mage::registry('multipleorderemail_data')->getData();        
        $model = Mage::getModel('multipleorderemail/multipleorderemailrule')->load($data['rule_id']);
        $model->getActions()->setJsFormObject('rule_actions_fieldset');      
        $model->setData($data);
        $data['customer_group_ids'] = unserialize($model->getUserGroup());
        
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('rule_');
        $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
            ->setTemplate('promo/fieldset.phtml')
            ->setNewChildUrl($this->getUrl('adminhtml/promo_quote/newActionHtml/form/rule_actions_fieldset'));
        
        $fieldset = $form->addFieldset('actions_fieldset', array(
            'legend'=>Mage::helper('multipleorderemail')->__('Apply the rule only to cart items matching the following conditions (leave blank for all items)')
        ))->setRenderer($renderer);

        $fieldset->addField('actions', 'text', array(
            'name' => 'actions',
            'label' => Mage::helper('multipleorderemail')->__('Apply To'),
            'title' => Mage::helper('multipleorderemail')->__('Apply To'),
            'required' => true,
        ))->setRule($model)->setRenderer(Mage::getBlockSingleton('rule/actions'));

        $customerGroups = Mage::getResourceModel('customer/group_collection')->load()->toOptionArray();
        $found = false;

        foreach ($customerGroups as $group) {
            if ($group['value'] == 0) {
                $found = true;
            }
        }
        if (!$found) {
             array_unshift($customerGroups, array(
                'value' => 0,
                'label' => Mage::helper('multipleorderemail')->__('NOT LOGGED IN'))
            );
        }

        $form->setValues($data);
        $this->setForm($form);
        return $this;
    }
}