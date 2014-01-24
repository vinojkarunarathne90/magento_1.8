<?php
/**
 * User: Wisepricer
 * Date: 04/12/12
 * Time: 12:52
 * To change this template use File | Settings | File Templates.
 */
class Autocompleteplus_Autosuggest_ProductsController extends Mage_Core_Controller_Front_Action
{

    private $imageField='';

    private $standardImageFields=array();

    public function sendAction(){

        set_time_limit (1800);

        $post = $this->getRequest()->getParams();

        $enabled= Mage::getStoreConfig('autocompleteplus/config/enabled');
        if($enabled=='0'){
            die('The user has disabled autocompleteplus.');
        }

        $imageField=Mage::getStoreConfig('autocompleteplus/config/imagefield');
        if(!$imageField){
            $imageField='thumbnail';
        }

        $useAttributes= Mage::getStoreConfig('autocompleteplus/config/attributes');

        $currency=Mage::app()->getStore()->getCurrentCurrencyCode();

        $standardImageFields=array('image','small_image','thumbnail');

        $startInd     = $post['offset'];
        if(!$startInd){
            $startInd=0;
        }

        $count        = $post['count'];

        //maxim products on one page is 200
        if(!$count||$count>10000){
            $count=10000;
        }
        //retrieving page number
        $pageNum=floor(($startInd/$count));

        //retrieving products collection to check if the offset is not bigger that the product count
        $collection=Mage::getModel('catalog/product')->getCollection();
        
        if(isset($post['store'])&&$post['store']!=''){
            $collection->addStoreFilter($post['store']);
        }


        /* since the retreiving of product count will load the entire collection of products,
         *  we need to annul it in order to get the specified page only
         */
        unset($collection);

        $mage=Mage::getVersion();
        $ext=(string) Mage::getConfig()->getNode()->modules->Autocompleteplus_Autosuggest->version;

        $xml='<?xml version="1.0"?>';
        $xml.='<catalog version="'.$ext.'" magento="'.$mage.'">';


        $productScheme = Mage::getModel('catalog/product');

        if($useAttributes!='0'){
            $attributes = Mage::getResourceModel('eav/entity_attribute_collection')
                ->setEntityTypeFilter($productScheme->getResource()->getTypeId())
                ->addFieldToFilter('is_user_defined', '1') // This can be changed to any attribute code
                ->load(false);
        }

        $collection=Mage::getModel('catalog/product')->getCollection();
        if(isset($post['store'])&&$post['store']!=''){
            $collection->addStoreFilter($post['store']);
        }


        //setting page+products on the page
        $collection->getSelect()->limit($count,$startInd);//->limitPage($pageNum, $count);//setPage($pageNum, $count)->load();

        $collection->load();

        foreach ($collection as $product) {

            $productCollData=$product->getData();
            $productModel=Mage::getModel('catalog/product')->load($productCollData['entity_id']);

            $categoriesNames='';

            $categories = $productModel->getCategoryCollection()
                ->addAttributeToSelect('name');

            foreach($categories as $category) {
                $categoriesNames.=$category->getName().':'.$category->getId().';';
            }

            $price       =$this->getPrice($productModel);
            $sku         =$productModel->getSku();

            $status      =$productModel->isInStock();
            $stockItem   = $productModel->getStockItem();

            if($stockItem->getIsInStock()&&$status)
            {
                $sell=1;
            }else{
                $sell=0;
            }

            $productUrl       =Mage::helper('catalog/product')->getProductUrl($productModel->getId());
            $prodDesc         =$productModel->getDescription();
            $prodShortDesc    =$productModel->getShortDescription();
            $prodName         =$productModel->getName();

            $visibility       =$productModel->getVisibility();

            try{

                if(in_array($imageField,$standardImageFields)){
                    $prodImage   =Mage::helper('catalog/image')->init($productModel, $imageField);
                }else{
                    $function='get'.$imageField;
                    $prodImage  =$productModel->$function();
                }

            }catch(Exception $e){
                $prodImage='';
            }


            $row='<product currency="'.$currency.'" visibility="'.$visibility.'" price="'.$price.'" url="'.$productUrl.'"  thumbs="'.$prodImage.'" selleable="'.$sell.'" action="insert" >';
            $row.='<description><![CDATA['.$prodDesc.']]></description>';
            $row.='<short><![CDATA['.$prodShortDesc.']]></short>';
            $row.='<name><![CDATA['.$prodName.']]></name>';
            $row.='<sku><![CDATA['.$sku.']]></sku>';
//die($useAttributes);
            if($useAttributes!='0'){
                foreach($attributes as $attr){

                    $action=$attr->getAttributeCode();

                    if($attr->getfrontend_input()=='select'){

                        if($productModel->getData($action)){
                            $row.='<attribute name="'.$attr->getAttributeCode().'"><![CDATA['.$productModel->getAttributeText($action).']]></attribute>';
                        }

                    }elseif($attr->getfrontend_input()=='textarea'){

                        if($productModel->getData($action)){
                            $row.='<attribute name="'.$attr->getAttributeCode().'"><![CDATA['.$productModel->getData($action).']]></attribute>';
                        }
                    }elseif($attr->getfrontend_input()=='price'){

                        if($productModel->getData($action)){
                            $row.='<attribute name="'.$attr->getAttributeCode().'"><![CDATA['.$productModel->getData($action).']]></attribute>';
                        }
                    }elseif($attr->getfrontend_input()=='text'){

                        if($productModel->getData($action)){
                            $row.='<attribute name="'.$attr->getAttributeCode().'"><![CDATA['.$productModel->getData($action).']]></attribute>';
                        }
                    }


                }

            }

            $row.='<categories><![CDATA['.$categoriesNames.']]></categories>';

            $row.='</product>';
            $xml.=$row;
        }

        $xml.='</catalog>';
        header('Content-type: text/xml');
        echo $xml;
        die;

    }

    private function getPrice($product){

        $helper=Mage::helper('autosuggest');
        if ($product->getTypeId()=='grouped'){

            $helper->prepareGroupedProductPrice($product);
            $_minimalPriceValue = $product->getPrice();

            if($_minimalPriceValue){
                $price=$_minimalPriceValue;
            }

        }elseif($product->getTypeId()=='bundle'){

            if(!$product->getFinalPrice()){
                $price=$helper->getBundlePrice($product);
            }else{
                $price=$product->getFinalPrice();
            }

        }else{
            $price       =$product->getFinalPrice();
        }

        if(!$price){
            $price=0;
        }
        return $price;
    }

    public function sendupdatedAction(){

        date_default_timezone_set('Asia/Jerusalem');

        set_time_limit (1800);

        $post = $this->getRequest()->getParams();

        $enabled= Mage::getStoreConfig('autocompleteplus/config/enabled');

        if($enabled=='0'){
            die('The user has disabled autocompleteplus.');
        }

        $this->imageField=Mage::getStoreConfig('autocompleteplus/config/imagefield');
        if(!$this->imageField){
            $this->imageField='thumbnail';
        }

        $this->standardImageFields=array('image','small_image','thumbnail');

        $useAttributes= Mage::getStoreConfig('autocompleteplus/config/attributes');

        $count        = $post['count'];

        $from = $post['from'];
        if(!isset($post['from'])){
            $returnArr=array(
                'status'=>'failure',
                'error_code'=>'767',
                'error_details'=>'The "from" parameter is mandatory'
            );
            echo json_encode($returnArr);
            die;
        }


        if(isset($post['to'])){
            $to   = $post['to'];
        }else{
            $to   = strtotime('now');
        }

        //$fromMysqldate = date( 'Y-m-d h:m:s', $from );
        //$toMysqldate   = date( 'Y-m-d h:m:s', $to );

        $storeQ='';

        if(isset($post['store_id'])){
            $storeQ   = 'AND store_id='.$post['store_id'];

        }


        $read = Mage::getSingleton('core/resource')->getConnection('core_read');

        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $_tableprefix = (string)Mage::getConfig()->getTablePrefix();

        $sql='SELECT * FROM `'.$_tableprefix.'autocompleteplus_batches` WHERE update_date BETWEEN ? AND ? '.$storeQ.' LIMIT '.$count;

        $updates=$read->fetchAll($sql,array($from,$to));

        $productScheme=Mage::getModel('catalog/product');

        if($useAttributes!='0'){
            $attributes = Mage::getResourceModel('eav/entity_attribute_collection')
                ->setEntityTypeFilter($productScheme->getResource()->getTypeId())
                ->addFieldToFilter('is_user_defined', '1') // This can be changed to any attribute code
                ->load(false);
        }else{

            $attributes=null;
        }

        $mage=Mage::getVersion();
        $ext=(string) Mage::getConfig()->getNode()->modules->Autocompleteplus_Autosuggest->version;

        $xml='<?xml version="1.0"?>';
        $xml.='<catalog fromdatetime="'.$from.'" version="'.$ext.'" magento="'.$mage.'">';


        foreach ($updates as $batch) {

          if($batch['action']=='update'){

              $xml.=$this->_makeUpdateRow($batch,$attributes);

          }else{
              $xml.=$this->_makeRemoveRow($batch);
          }


        }

        $xml.='</catalog>';
        header('Content-type: text/xml');
        echo $xml;
        die;

    }

    private function _makeUpdateRow($batch,$attributes){

        $productId =         $batch['product_id'];
        $sku =               $batch['sku'];
        $storeId =           $batch['store_id'];
        $updatedate =        $batch['update_date'];
        $action =            $batch['action'];

        $currency=Mage::app()->getStore($storeId)->getCurrentCurrencyCode();

        if($productId!=null){

            $productModel=Mage::getModel('catalog/product')
                ->setStoreId($storeId)
                ->load($productId);

        }else{

            $productModel=Mage::getModel('catalog/product')
                ->setStoreId($storeId)
                ->loadByAttribute('sku', $sku);

        }

        if($productModel==null){
            return '';
        }
        
        $price       =$this->getPrice($productModel);
        $sku         =$productModel->getSku();

        $status      =$productModel->isInStock();
        $stockItem   = $productModel->getStockItem();

        $categoriesNames='';

        $categories = $productModel->getCategoryCollection()
            ->addAttributeToSelect('name');

        foreach($categories as $category) {
            $categoriesNames.=$category->getName().':'.$category->getId().';';
        }

        if($stockItem->getIsInStock()&&$status)
        {
            $sell=1;
        }else{
            $sell=0;
        }

        $productUrl       =Mage::helper('catalog/product')->getProductUrl($productModel->getId());

        $prodDesc         =$productModel->getDescription();
        $prodShortDesc    =$productModel->getShortDescription();
        $prodName         =$productModel->getName();

        $visibility       =$productModel->getVisibility();

        try{

            if(in_array($this->imageField,$this->standardImageFields)){
                $prodImage   =Mage::helper('catalog/image')->init($productModel, $this->imageField);
            }else{
                $function='get'.$this->imageField;
                $prodImage  =$productModel->$function();
            }

        }catch(Exception $e){
            $prodImage='';
        }

        $row='<product updatedate="'.$updatedate.'" currency="'.$currency.'" storeid="'.$storeId.'" visibility="'.$visibility.'" price="'.$price.'" url="'.$productUrl.'"  thumbs="'.$prodImage.'" selleable="'.$sell.'" action="'.$action.'" >';
        $row.='<description><![CDATA['.$prodDesc.']]></description>';
        $row.='<short><![CDATA['.$prodShortDesc.']]></short>';
        $row.='<name><![CDATA['.$prodName.']]></name>';
        $row.='<sku><![CDATA['.$sku.']]></sku>';

        if($attributes!=null){
            foreach($attributes as $attr){

                $action=$attr->getAttributeCode();

                if($attr->getfrontend_input()=='select'){

                    if($productModel->getData($action)){
                        $row.='<attribute name="'.$attr->getAttributeCode().'"><![CDATA['.$productModel->getAttributeText($action).']]></attribute>';
                    }

                }elseif($attr->getfrontend_input()=='textarea'){

                    if($productModel->getData($action)){
                        $row.='<attribute name="'.$attr->getAttributeCode().'"><![CDATA['.$productModel->getData($action).']]></attribute>';
                    }
                }elseif($attr->getfrontend_input()=='price'){

                    if($productModel->getData($action)){
                        $row.='<attribute name="'.$attr->getAttributeCode().'"><![CDATA['.$productModel->getData($action).']]></attribute>';
                    }
                }elseif($attr->getfrontend_input()=='text'){

                    if($productModel->getData($action)){
                        $row.='<attribute name="'.$attr->getAttributeCode().'"><![CDATA['.$productModel->getData($action).']]></attribute>';
                    }
                }


            }
        }

        $row.='<categories><![CDATA['.$categoriesNames.']]></categories>';
        $row.='</product>';

        return $row;
    }

    private function _makeRemoveRow($batch){

        $updatedate=        $batch['update_date'];
        $action=            $batch['action'];
        $sku=               $batch['sku'];



        $row='<product updatedate="'.$updatedate.'" action="'.$action.'" >';
        $row.='<sku><![CDATA['.$sku.']]></sku>';
        $row.='</product>';

        return $row;
    }

    private function __makeSafeString($str){
        $str=strip_tags($str);
        $str=str_replace('"','',$str);
        $str=str_replace("'",'',$str);
        $str=str_replace('/','',$str);
        $str=str_replace('<','',$str);
        $str=str_replace('>','',$str);
        $str=str_replace('\\','',$str);
        return $str;
    }

    private function __checkAccess(){

        $post = $this->getRequest()->getParams();

        $key=Mage::getModel('autocompleteplus_autosuggest/observer')->getKey();

        if(isset($post['key'])&&$post['key']==$key){
            return true;
        }else{
            return false;
        }

    }

    public function checkinstallAction(){

        $read = Mage::getSingleton('core/resource')->getConnection('core_read');

        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $_tableprefix = (string)Mage::getConfig()->getTablePrefix();

        $sql='SELECT * FROM `'.$_tableprefix.'autocompleteplus_config` WHERE `id` =1';

        $licenseData=$read->fetchAll($sql);

        $key=$licenseData[0]['licensekey'];

        if(strlen($key)>0&&$key!='failed'){
          echo 'the key exists';
        }else{
            echo 'no key inside';
        }

    }

    public function versAction(){
        $mage=Mage::getVersion();
        $ext=(string) Mage::getConfig()->getNode()->modules->Autocompleteplus_Autosuggest->version;
        $result=array('mage'=>$mage,'ext'=>$ext);
        echo json_encode($result);die;
    }

    public function getstoresAction(){

        $helper=Mage::helper('autosuggest');

        echo $helper->getMultiStoreDataJson();
        die;
    }

    public function updateemailAction(){

        $data = $this->getRequest()->getPost();

        $email=$data['email'];
        $uuid=$this->_getUUID();
        
        Mage::getModel('core/config')->saveConfig('autocompleteplus/config/store_email',$email);

        $params=array(
            'uuid'=>$uuid,
            'email'=>$email
        );

        $helper=Mage::helper('autosuggest');

        $command="http://magento.autocompleteplus.com/ext_update_email";

        $res=$helper->sendPostCurl($command,$params);

        $result=json_decode($res);

        if($result->status=='OK'){
            echo 'Your email address was updated!';
        }
    }

    public function updateAction(){

        set_time_limit (1800);

        $post = $this->getRequest()->getParams();

        $enabled= Mage::getStoreConfig('autocompleteplus/config/enabled');

        if($enabled=='0'){
            die('The user has disabled autocompleteplus.');
        }

        $imageField=Mage::getStoreConfig('autocompleteplus/config/imagefield');
        if(!$imageField){
            $imageField='thumbnail';
        }

        $currency=Mage::app()->getStore()->getCurrentCurrencyCode();

        $standardImageFields=array('image','small_image','thumbnail');

        $useAttributes= Mage::getStoreConfig('autocompleteplus/config/attributes');

        $startInd     = $post['offset'];
        if(!$startInd){
            $startInd=0;
        }

        $count        = $post['count'];

        //maxim products on one page is 200
        if(!$count||$count>10000){
            $count=10000;
        }
        //retrieving page number
        $pageNum=($startInd/$count)+1;

        $mage=Mage::getVersion();
        $ext=(string) Mage::getConfig()->getNode()->modules->Autocompleteplus_Autosuggest->version;

        $xml='<?xml version="1.0"?>';
        $xml.='<catalog version="'.$ext.'" magento="'.$mage.'">';


        $collection=Mage::getModel('catalog/product')->getCollection();

        if(isset($post['store'])&&$post['store']!=''){
            $collection->addStoreFilter($post['store']);
        }

        $productScheme=Mage::getModel('catalog/product');

        if($useAttributes!='0'){

            $attributes = Mage::getResourceModel('eav/entity_attribute_collection')
                ->setEntityTypeFilter($productScheme->getResource()->getTypeId())
                ->addFieldToFilter('is_user_defined', '1') // This can be changed to any attribute code
                ->load(false);

        }

        //setting page+products on the page
        $collection->getSelect()->limit($count,$startInd);//->limitPage($pageNum, $count);//setPage($pageNum, $count)->load();

        $collection->load();

        $xml='<?xml version="1.0"?>';
        $xml.='<catalog>';

        foreach ($collection as $product) {

            $productCollData=$product->getData();
            $productModel=Mage::getModel('catalog/product')->load($productCollData['entity_id']);

            $categoriesNames='';

            $categories = $productModel->getCategoryCollection()
                ->addAttributeToSelect('name');

            foreach($categories as $category) {
                $categoriesNames.=$category->getName().':'.$category->getId().';';
            }

            $price       =$this->getPrice($productModel);
            $sku         =$productModel->getSku();

            $status      =$productModel->isInStock();
            $stockItem   = $productModel->getStockItem();

            if($stockItem->getIsInStock()&&$status)
            {
                $sell=1;
            }else{
                $sell=0;
            }

            $productUrl       =Mage::helper('catalog/product')->getProductUrl($productModel->getId());

            $prodDesc         =$productModel->getDescription();
            $prodShortDesc    =$productModel->getShortDescription();
            $prodName         =$productModel->getName();

            $visibility       =$productModel->getVisibility();

            try{

                if(in_array($imageField,$standardImageFields)){
                    $prodImage   =Mage::helper('catalog/image')->init($productModel, $imageField);
                }else{
                    $function='get'.$imageField;
                    $prodImage  =$productModel->$function();
                }

            }catch(Exception $e){
                $prodImage='';
            }

            $row='<product currency="'.$currency.'" visibility="'.$visibility.'" price="'.$price.'" url="'.$productUrl.'"  thumbs="'.$prodImage.'" selleable="'.$sell.'" action="update" >';
            $row.='<description><![CDATA['.$prodDesc.']]></description>';
            $row.='<short><![CDATA['.$prodShortDesc.']]></short>';
            $row.='<name><![CDATA['.$prodName.']]></name>';
            $row.='<sku><![CDATA['.$sku.']]></sku>';

            if($useAttributes!='0'){

                foreach($attributes as $attr){

                    $action=$attr->getAttributeCode();

                      if($attr->getfrontend_input()=='select'){

                            if($productModel->getData($action)){
                                $row.='<attribute name="'.$attr->getAttributeCode().'"><![CDATA['.$productModel->getAttributeText($action).']]></attribute>';
                            }

                        }elseif($attr->getfrontend_input()=='textarea'){

                            if($productModel->getData($action)){
                                $row.='<attribute name="'.$attr->getAttributeCode().'"><![CDATA['.$productModel->getData($action).']]></attribute>';
                            }
                        }elseif($attr->getfrontend_input()=='price'){

                            if($productModel->getData($action)){
                                $row.='<attribute name="'.$attr->getAttributeCode().'"><![CDATA['.$productModel->getData($action).']]></attribute>';
                            }
                        }elseif($attr->getfrontend_input()=='text'){

                            if($productModel->getData($action)){
                                $row.='<attribute name="'.$attr->getAttributeCode().'"><![CDATA['.$productModel->getData($action).']]></attribute>';
                            }
                        }


                }

            }
            $row.='<categories><![CDATA['.$categoriesNames.']]></categories>';

            $row.='</product>';
            $xml.=$row;
        }

        $xml.='</catalog>';
        header('Content-type: text/xml');
        echo $xml;
        die;

    }


    protected function _getUUID(){

        $read = Mage::getSingleton('core/resource')->getConnection('core_read');

        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $_tableprefix = (string)Mage::getConfig()->getTablePrefix();

        $tblExist=$write->showTableStatus($_tableprefix.'autocompleteplus_config');

        if(!$tblExist){return '';}

        $sql='SELECT * FROM `'.$_tableprefix.'autocompleteplus_config` WHERE `id` =1';

        $licenseData=$read->fetchAll($sql);

        $key=$licenseData[0]['licensekey'];

        return $key;

    }

    protected function _setUUID($key){

        try{

            $_tableprefix = (string)Mage::getConfig()->getTablePrefix();

            $read = Mage::getSingleton('core/resource')->getConnection('core_read');

            $write = Mage::getSingleton('core/resource')->getConnection('core_write');

            $tblExist=$write->showTableStatus($_tableprefix.'autocompleteplus_config');

            if(!$tblExist){return;}

            $sqlFetch    ='SELECT * FROM '. $_tableprefix.'autocompleteplus_config WHERE id = 1';

            $updates=$write->fetchAll($sqlFetch);

            if($updates&&count($updates)!=0){

                $sql='UPDATE '. $_tableprefix.'autocompleteplus_config  SET licensekey=? WHERE id = 1';

                $write->query($sql, array($key));

            }else{

                $sql='INSERT INTO '. $_tableprefix.'autocompleteplus_config  (licensekey) VALUES (?)';

                $write->query($sql, array($key));

            }


        }catch(Exception $e){
            Mage::log($e->getMessage(),null,'autocompleteplus.log');
        }

    }
}
