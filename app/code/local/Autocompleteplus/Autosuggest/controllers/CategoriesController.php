<?php
class Autocompleteplus_Autosuggest_CategoriesController extends Mage_Core_Controller_Front_Action
{
    public function sendAction(){

        $categories=$this->load_tree();

        echo json_encode($categories);
    }

    private function nodeToArray(Varien_Data_Tree_Node $node , $mediaUrl, $baseUrl)
    {
        $result = array();

        $thumbnail='';

        try{

            $thumbImg=$node->getThumbnail();

           if($thumbImg!=null){

              $thumbnail=$mediaUrl.'catalog/category/'.$node->getThumbnail();
           }
        }catch(Exception $e){

        }

        $result['category_id'] = $node->getId();
        $result['image'] = $mediaUrl.'catalog/category/'.$node->getImage();
        $result['thumbnail'] = $thumbnail;
        $result['description'] = $node->getDescription();
        $result['parent_id'] = $node->getParentId();
        $result['name'] = $node->getName();
        $result['is_active'] = $node->getIsActive();
        $result['url_path'] = $baseUrl.$node->getData('url_path');
        $result['children'] = array();

        foreach ($node->getChildren() as $child) {
            $result['children'][] = $this->nodeToArray($child,$mediaUrl,$baseUrl);
        }

        return $result;
    }

    private function load_tree() {

        $tree = Mage::getResourceSingleton('catalog/category_tree')
        ->load();

        $post = $this->getRequest()->getParams();

        $store = $post['store'];;
        $parentId =  Mage::app()->getStore($store)->getRootCategoryId();

        $tree = Mage::getResourceSingleton('catalog/category_tree')
        ->load();

        $root = $tree->getNodeById($parentId);

        if($root && $root->getId() == 1) {
            $root->setName(Mage::helper('catalog')->__('Root'));
        }

        $collection = Mage::getModel('catalog/category')->getCollection()
                    ->setStoreId($store)
                    ->addAttributeToSelect('name')
                    ->addAttributeToSelect('url_path')
                    ->addAttributeToSelect('image')
                    ->addAttributeToSelect('thumbnail')
                    ->addAttributeToSelect('description')
            ->addAttributeToFilter('is_active',array('eq'=>true));

        $tree->addCollectionData($collection, true);

        $mediaUrl= Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $baseUrl= Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);

        return $this->nodeToArray($root,$mediaUrl,$baseUrl);

    }

}