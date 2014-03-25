<?php
/**
 * @name            :  Super Deals
 * @version         :  1.1
 * @author          :  Apptha - http://www.apptha.com
 * @copyright       :  Copyright (C) 2013 Powered by Apptha
 * @license         :  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @Creation Month  :  April 2013
 *
 * */

class Apptha_Superdeals_Block_Mostviewedproduct extends Mage_Catalog_Block_Product_Abstract
{   
   public function getProductsLimit()    {

                $count = Mage::helper('superdeals')->getMostviewedSidebar();
		if($count)
			return $count;
		return 1;
    }
	public function getProductCollectionInitial()
    {    	
            $storeId    	=   Mage::app()->getStore()->getId();
            $todayDate      =   Mage::app()->getLocale()->date()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
            $tomorrow       =   mktime(0, 0, 0, date('m'), date('d') + 1, date('y'));
            $dateTomorrow   =   date('m/d/y', $tomorrow);
						
			$products = Mage::getResourceModel('reports/product_collection')
									->addAttributeToSelect('*')
									->setStoreId($storeId)
									->addStoreFilter($storeId)
									->addViewsCount('desc')
					                ->addAttributeToFilter('special_price', array('neq'=>''))
					                ->addAttributeToFilter('special_from_date', array('date' => true, 'to' => $todayDate))
					                ->addAttributeToFilter('special_to_date', array('or' => array(0 => array('date' => true, 'from' => $dateTomorrow), 1 => array('is' => new Zend_Db_Expr('null')))), 'left')                
									->addAttributeToFilter('status','1');
		
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($products);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($products);

		return $products; 				
    }
     public function getProductCollectionRandom()
    {
            $storeId    	= Mage::app()->getStore()->getId();
            $todayDate      = Mage::app()->getLocale()->date()->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
            $tomorrow       = mktime(0, 0, 0, date('m'), date('d') + 1, date('y'));
            $dateTomorrow   = date('m/d/y', $tomorrow);
            $products 		= Mage::getModel('catalog/product')
			                        ->getCollection()
			                        ->addAttributeToFilter('special_price', array('neq'=>''))
			                        ->addAttributeToFilter('special_from_date', array('date' => true, 'to' => $todayDate))
			                        ->addAttributeToFilter('special_to_date', array('or' => array(0 => array('date' => true, 'from' => $dateTomorrow), 1 => array('is' => new Zend_Db_Expr('null')))), 'left');

        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($products);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($products);

		return $products;
    }

	public function getProductCollection()
    {
		$storeId = Mage::app()->getStore()->getId();
		$productCollection = $this->getProductCollectionInitial();
                $count = count($productCollection);
                if($count<=0){
                     $productCollection = $this->getProductCollectionRandom();                
                }
		$sameProduct = array();
 		$checkedProducts = new Varien_Data_Collection();
		foreach($productCollection as $key => $prod) {

			$parentId = $this->getParentId($prod);

			if($parentId == '') {
				continue;
			}

			$product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($this->getParentId($prod));

			// if the product is not visible or is disabled
			if(!$product->isVisibleInCatalog()) {
				continue;
			}

			// if two or more simple products of the same configurable product are ordered
			if(in_array($product->getId(),$sameProduct)) {
				continue;
			}

			$sameProduct[] = $product->getId();

			if (!$checkedProducts->getItemById($parentId)) {
				$checkedProducts->addItem($product);
			}

			if(count($checkedProducts) >= $this->getProductsLimit()) {
				break;
			}
		}
		$productCollection = $checkedProducts;
		return $productCollection;
	}

	public function getParentId($product)
	{
		$parentId = '';

		if($product->getVisibility() != '1') {
			$parentId = $product->getId();

		}
		else {
			// get parent id if the product is not visible
			// this means that the product is associated with a configurable product
			$parentIdArray = $product->loadParentProductIds()->getData('parent_product_ids');

			if(!empty($parentIdArray)) {
				$parentId = $parentIdArray[0];
			}
		}
		return $parentId;
	}
         
}