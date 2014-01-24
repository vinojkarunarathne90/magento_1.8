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
class Autocompleteplus_Autosuggest_Block_Adminhtml_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
     /*
     * Set template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('autocompleteplus/system/config/button.phtml');
    }
 
    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }
    
    public function getUUID(){

        try{
            $helper=Mage::helper('autosuggest');

            $_tableprefix = (string)Mage::getConfig()->getTablePrefix();

            $write = Mage::getSingleton('core/resource')->getConnection('core_write');

            $tblExist=$write->showTableStatus($_tableprefix.'autocompleteplus_config');

            if(!$tblExist){return 'no_uuid';}

            $sqlFetch    ='SELECT * FROM '. $_tableprefix.'autocompleteplus_config WHERE id = 1';

            $config=$write->fetchAll($sqlFetch);


            if(isset($config[0]['licensekey'])){

                $licensekey=$config[0]['licensekey'];

            }else{
                $licensekey='no_uuid';
            }

            return $licensekey;

        }catch(Exception $e){
            Mage::log($e->getMessage(),null,'autocompleteplus.log');
        }
    }
 
    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxCheckUrl()
    {
        return Mage::helper('adminhtml')->getUrl('autocompleteplus/products/updateemail');    }
 
    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
            'id'        => 'autocompleteplus_button',
            'label'     => $this->helper('adminhtml')->__('Update'),
            'onclick'   => 'javascript:updateautocomplete(); return false;'
        ));
 
        return $button->toHtml();
    }

}