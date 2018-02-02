<?php

class Divvit_Divvit_Block_Checker extends Mage_Adminhtml_Block_System_Config_Form_Field
{

   /**
    * Returns html part of the setting
    *
    * @param Varien_Data_Form_Element_Abstract $element
    * @return string
    */
   protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
   {
       $helper = Mage::helper('divvit_divvit');
       if (!$helper->tableChecker()) {
         $html = '<div style="background: #ddd;padding: 3px 10px;color: red;font-weight: bold;">Divvit Module Error!</div><div style="background: #f1f1f1;padding: 10px;text-align: center;color: red;">Installation incomplete. Table `divvit_order` not found.</div>';
       } else {
          $html = $element->getElementHtml();
       }
      return $html;
   }

}