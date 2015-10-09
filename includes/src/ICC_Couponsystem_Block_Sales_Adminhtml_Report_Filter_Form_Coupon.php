<?php
class ICC_Couponsystem_Block_Sales_Adminhtml_Report_Filter_Form_Coupon extends Mage_Sales_Block_Adminhtml_Report_Filter_Form
{ 
    protected function _prepareForm()
    {
        parent::_prepareForm();

        $form = $this->getForm();
        $htmlIdPrefix = $form->getHtmlIdPrefix();

        /** @var Varien_Data_Form_Element_Fieldset $fieldset */
        $fieldset = $this->getForm()->getElement('base_fieldset');
        
        if (is_object($fieldset) && $fieldset instanceof Varien_Data_Form_Element_Fieldset) {

            $fieldset->addField('price_rule_type', 'select', array(
                'name'    => 'price_rule_type',
                'options' => array(
                    Mage::helper('reports')->__('Any'),
                    Mage::helper('reports')->__('Specified')
                ),
                'label'   => Mage::helper('reports')->__('Rule Name'),
            ));

            $rulesList = Mage::helper('icc_couponsystem')->getUniqRulesNamesList();

            $rulesListOptions = array();

            foreach ($rulesList as $key => $ruleName) {
                $rulesListOptions[] = array(
                    'label' => $ruleName,
                    'value' => $key,
                    'title' => $ruleName
                );
            }

            $fieldset->addField('rules_list', 'multiselect', array(
                'name'      => 'rules_list',
                'values'    => $rulesListOptions,
                'display'   => 'none'
            ), 'price_rule_type');

            $this->setChild('form_after', $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence')
                ->addFieldMap($htmlIdPrefix . 'price_rule_type', 'price_rule_type')
                ->addFieldMap($htmlIdPrefix . 'rules_list', 'rules_list')
                ->addFieldDependence('rules_list', 'price_rule_type', '1')
            );
        }
        
          /*updated 05-05*/
        $fieldset->addField('coupon_codes', 'text', array(
                'name'    => 'coupon_codes',
                'label'   => Mage::helper('reports')->__('Coupon Code'),
        ));
        $dateFormatIso = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        $fieldset->addField('f_report_date', 'date', array(
            'name'      => 'f_report_date',
            'image'     => $this->getSkinUrl('images/grid-cal.gif'),
            'label'     => Mage::helper('reports')->__('Start Date'),
            'title'     => Mage::helper('reports')->__('Start Date'),
            'format'    => $dateFormatIso,
        ));

        $fieldset->addField('t_report_date', 'date', array(
            'name'      => 't_report_date',
            'format'    => $dateFormatIso,
            'image'     => $this->getSkinUrl('images/grid-cal.gif'),
            'label'     => Mage::helper('reports')->__('Date Expired'),
            'title'     => Mage::helper('reports')->__('Date Expired'),
        ));

        return $this;
    }
}
			