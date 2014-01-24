<?php

$installer = $this;

$installer->startSetup();

$res=$installer->run("

DROP TABLE IF EXISTS {$this->getTable('autocompleteplus_config')};

CREATE TABLE IF NOT EXISTS {$this->getTable('autocompleteplus_config')} (

  `id` int(11) NOT NULL auto_increment,

  `licensekey` varchar(255) character set utf8 NOT NULL,
  
  `site_url` varchar(255) character set utf8 NOT NULL,

   PRIMARY KEY  (`id`)

) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;


DROP TABLE IF EXISTS {$this->getTable('autocompleteplus_batches')};

CREATE TABLE IF NOT EXISTS {$this->getTable('autocompleteplus_batches')} (

  `id` int(11) NOT NULL auto_increment,

   `product_id` INT NULL,

   `store_id` INT NOT NULL,

   `update_date` INT DEFAULT NULL,

   `action` VARCHAR( 255 ) NOT NULL,

   `sku` VARCHAR( 255 ) NOT NULL,

   PRIMARY KEY  (`id`)

) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

");


$installer->endSetup();

$helper=Mage::helper('autosuggest');

//getting site url
$url=$helper->getConfigDataByFullPath('web/unsecure/base_url');

//getting site owner email
$storeMail=$helper->getConfigDataByFullPath('autocompleteplus/config/store_email');

//getting site design theme package name
$package=$helper->getConfigDataByFullPath('design/package/name');



$collection=Mage::getModel('catalog/product')->getCollection();
$productCount=$collection->count();


$multistoreJson=$helper->getMultiStoreDataJson();

try{

    $commandOrig="http://magento.autocompleteplus.com/install";

    $data=array();
    $data['multistore']=$multistoreJson;

    $key=$helper->sendPostCurl($commandOrig,$data);

    if(strlen($key)>50){
        $key='failed';
    }

    Mage::log(print_r($key,true),null,'autocomplete.log');

    $errMsg='';
    if($key=='failed'){
        $errMsg.='Could not get license string.';
    }

    if($package=='base'){
        $errMsg.= ';The Admin needs to move autocomplete template files to his template folder';
    }

    if($errMsg!=''){

        $command="http://magento.autocompleteplus.com/install_error";
        $data=array();
        $data['site']=$url;
        $data['msg']=$errMsg;
        $data['email']=$storeMail;
        $data['product_count']=$productCount;
        $data['multistore']=$multistoreJson;
        $res=$helper->sendPostCurl($command,$data);
    }

}catch(Exception $e){

    $key='failed';
    $errMsg=$e->getMessage();

    Mage::log('Install failed with a message: '.$errMsg,null,'autocomplete.log');

    $command="http://magento.autocompleteplus.com/install_error";

    $data=array();
    $data['site']=$url;
    $data['msg']=$errMsg;
    $data['original_install_URL']=$commandOrig;
    $res=$helper->sendPostCurl($command,$data);
}

$installer->startSetup();

$res=$installer->run("INSERT INTO {$this->getTable('autocompleteplus_config')} (licensekey,site_url) VALUES('".$key."','".$url."');");

$installer->endSetup();

?>