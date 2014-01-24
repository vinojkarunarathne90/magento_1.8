<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Used in creating options for Yes|No config value selection
 *
 */
class Autocompleteplus_Autosuggest_Model_Adminhtml_Attributes
{
    public $fields = array();
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $this->fields=$this->getOptions();
        return $this->fields;
    }

    public function getOptions()
    {
        $entityType = Mage::getModel('catalog/product')->getResource()->getEntityType();
        $entityTypeId=$entityType->getId();
        $attributeInfo = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setEntityTypeFilter($entityTypeId)
            ->getData();
        $result=array();
        $result[]=array('value'=>'','label'=>'Choose an attribute');
        foreach($attributeInfo as $_key=>$_value)
        {
            if($_value['is_global'] != "1"  || $_value['is_visible']!="1"){
                // continue;
            }
            if(isset($_value['frontend_label'])&&($_value['frontend_label']!='')){
                $result[]=array('value'=>$_value['attribute_code'],'label' => $_value['frontend_label']);
            }else{
                $result[]=array('value'=>$_value['attribute_code'],'label' => $_value['attribute_code']);
            }

        }
        return $result;
    }

}