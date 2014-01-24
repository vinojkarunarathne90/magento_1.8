<?php

$helper=Mage::helper('autosuggest');

    //getting site owner email
    $storeMail=$helper->getConfigDataByFullPath('trans_email/ident_general/email');
          Mage::log($storeMail,null,'autocomplete.log');
    Mage::getModel('core/config')->saveConfig('autocompleteplus/config/store_email', $storeMail );

    Mage::getModel('core/config')->saveConfig('autocompleteplus/config/enabled', 1 );


?>