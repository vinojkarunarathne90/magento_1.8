<?php

class Autocompleteplus_Autosuggest_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getConfigDataByFullPath($path){

        if (!$row = Mage::getSingleton('core/config_data')->getCollection()->getItemByColumnValue('path', $path)) {
            $conf = Mage::getSingleton('core/config')->init()->getXpath('/config/default/'.$path);
            if(is_array($conf)){
                $value = array_shift($conf);
            }else{
                return '';
            }

        } else {
            $value = $row->getValue();
        }

        return $value;

    }

    public function getConfigMultiDataByFullPath($path){

        if (!$rows = Mage::getSingleton('core/config_data')->getCollection()->getItemsByColumnValue('path', $path)) {
            $conf = Mage::getSingleton('core/config')->init()->getXpath('/config/default/'.$path);
            $value = array_shift($conf);
        } else {
            $values=array();
            foreach($rows as $row){
                $values[$row->getScopeId()]=$row->getValue();
            }
        }

        return $values;

    }

    public function sendCurl($command){

        if(isset($ch)) unset($ch);

        if(function_exists('curl_setopt')){
            $ch              = curl_init();
            curl_setopt($ch, CURLOPT_URL, $command);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
            $str=curl_exec($ch);
        }else{
            $str='failed';
        }

        return $str;


    }

    public function getKey(){

        $read = Mage::getSingleton('core/resource')->getConnection('core_read');

        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $_tableprefix = (string)Mage::getConfig()->getTablePrefix();

        $tblExist=$write->showTableStatus($_tableprefix.'autocompleteplus_config');

        if(!$tblExist){return;}

        $sql='SELECT * FROM `'.$_tableprefix.'autocompleteplus_config` WHERE `id` =1';

        $licenseData=$read->fetchAll($sql);

        $key=$licenseData[0]['licensekey'];

        return $key;
    }


    public static function sendPostCurl($command, $data=array(),$cookie_file='genCookie.txt') {

        if(isset($ch)) unset($ch);

        if(function_exists('curl_setopt')){

            $ch              = curl_init();
            curl_setopt($ch, CURLOPT_URL, $command);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:21.0) Gecko/20100101 Firefox/21.0');
            //curl_setopt($ch,CURLOPT_POST,0);
            if(!empty($data)){
                curl_setopt_array($ch, array(
                    CURLOPT_POSTFIELDS => $data,
                ));
            }


            //  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            //      'Connection: Keep-Alive',
            //      'Keep-Alive: 800'
            //  ));

            $str=curl_exec($ch);

        }else{
            $str='failed';
        }

        return $str;
    }

    public function prepareGroupedProductPrice($groupedProduct)
    {
        $aProductIds = $groupedProduct->getTypeInstance()->getChildrenIds($groupedProduct->getId());

        $prices = array();
        foreach ($aProductIds as $ids) {
            foreach ($ids as $id) {
                $aProduct = Mage::getModel('catalog/product')->load($id);
                $prices[] = $aProduct->getPriceModel()->getPrice($aProduct);
            }
        }

        krsort($prices);
        $groupedProduct->setPrice($prices[0]);

        // or you can return price
    }

    public function getBundlePrice($product) {

        $optionCol= $product->getTypeInstance(true)
            ->getOptionsCollection($product);
        $selectionCol= $product->getTypeInstance(true)
            ->getSelectionsCollection(
                $product->getTypeInstance(true)->getOptionsIds($product),
                $product
            );
        $optionCol->appendSelections($selectionCol);
        $price = $product->getPrice();

        foreach ($optionCol as $option) {
            if($option->required) {
                $selections = $option->getSelections();
                $selPricesArr=array();

                foreach($selections as $s){
                    $selPricesArr[]=$s->price;
                }

                $minPrice = min($selPricesArr);

                if($product->getSpecialPrice() > 0) {
                    $minPrice *= $product->getSpecialPrice()/100;
                }

                $price += round($minPrice,2);
            }
        }
        return $price;

    }

    public function getMultiStoreDataJson(){

        $websites=Mage::getModel('core/website')->getCollection();

        $multistoreData=array();
        $multistoreJson='';
        $useStoreCode=$this->getConfigDataByFullPath('web/url/use_store');
        $mage=Mage::getVersion();
        $ext=(string) Mage::getConfig()->getNode()->modules->Autocompleteplus_Autosuggest->version;
        $version=array('mage'=>$mage,'ext'=>$ext);

        //getting site url
        $url=$this->getConfigDataByFullPath('web/unsecure/base_url');

        //getting site owner email
        $storeMail=$this->getConfigDataByFullPath('autocompleteplus/config/store_email');

        if(!$storeMail){

            $storeMail=$this->getConfigDataByFullPath('trans_email/ident_general/email');
        }

        $collection=Mage::getModel('catalog/product')->getCollection();
        //$productCount=$collection->count();


        $storesArr=array();
        foreach($websites as $website){
            $code=$website->getCode();
            $stores=$website->getStores();
            foreach($stores as $store){
                $storesArr[$store->getStoreId()]=$store->getData();
            }
        }

        if(count($storesArr)==1){

            $dataArr=array(
                'stores'=>$multistoreData,
                'version'=>$version
            );

            $dataArr['site']=$url;
            $dataArr['email']=$storeMail;

            $multistoreJson=json_encode($dataArr);

        }else{

            $storeUrls=$this->getConfigMultiDataByFullPath('web/unsecure/base_url');
            $locales=$this->getConfigMultiDataByFullPath('general/locale/code');
            $storeComplete=array();

            foreach($storesArr as $key=>$value){

                if(!$value['is_active']){
                    continue;
                }

                $storeComplete=$value;
                if(array_key_exists($key,$locales)){
                    $storeComplete['lang']=$locales[$key];
                }else{
                    $storeComplete['lang']=$locales[0];
                }

                if(array_key_exists($key,$storeUrls)){
                    $storeComplete['url']=$storeUrls[$key];
                }else{
                    $storeComplete['url']=$storeUrls[0];
                }

                if($useStoreCode){
                    $storeComplete['url']=$storeUrls[0].$value['code'];
                }

                $multistoreData[]=$storeComplete;
            }

            $dataArr=array(
                'stores'=>$multistoreData,
                'version'=>$version
            );

            $dataArr['site']=$url;
            $dataArr['email']=$storeMail;
            //$dataArr['product_count']=$productCount;

            $multistoreJson=json_encode($dataArr);

        }
        Mage::log($multistoreJson,null,'autocomplete.log');

        return $multistoreJson;
    }



}