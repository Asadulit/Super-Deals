<?php
/**
 * Apptha
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.apptha.com/LICENSE.txt
 *
 * ==============================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * ==============================================================
 * This package designed for Magento COMMUNITY edition
 * Apptha does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * Apptha does not provide extension support in case of
 * incorrect edition usage.
 * ==============================================================
 *
 * @category    Apptha
 * @package     Apptha_SuperDeals
 * @version    0.1.2
 * @author      Apptha Team <developers@contus.in>
 * @copyright   Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 *
 */

class Apptha_Superdeals_Block_Banner extends Mage_Catalog_Block_Product_Abstract {
	public function getProductsLimit()    {
		$count = Mage::helper('superdeals')->getOfferSlider();
		if($count)
		return $count;
		return 5;
	}
	public function getProductCollectionHigher()
	{
		$storeId    =	Mage::app()->getStore()->getId();
		$product    = 	Mage::getModel('catalog/product');
		$todayDate  = 	$product->getResource()->formatDate(time());
		$products   = 	$product->setStoreId($storeId)->getCollection()
								->addAttributeToSelect(array('name', 'price', 'small_image'), 'left')
								->addAttributeToSelect(array('special_price', 'special_from_date', 'special_to_date'), 'left')
								->addAttributeToSelect('status')
								->addAttributeToSort('special_price','asc')
								->addAttributeToFilter('special_to_date', array('date'=>true, 'from'=> $todayDate));
		Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($products);
		Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($products);
		return $products;
	}
	
	public function getProductCollectionLower()
	{
		$storeId    =	Mage::app()->getStore()->getId();
		$product    = 	Mage::getModel('catalog/product');
		$todayDate  = 	$product->getResource()->formatDate(time());
		$products   = 	$product->setStoreId($storeId)->getCollection()
								->addAttributeToSelect(array('name', 'price', 'small_image'), 'left')
								->addAttributeToSelect(array('special_price', 'special_from_date', 'special_to_date'), 'left')
								->addAttributeToSelect('status')
								->addAttributeToSort('special_price','desc')
								->addAttributeToFilter('special_to_date', array('date'=>true, 'from'=> $todayDate));
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
		$products->getSelect()->order('rand()');
		return $products;
	}

	public function getProductCollection()
	{
		$storeId = Mage::app()->getStore()->getId();
		$count = count($productCollection);
		$order = (int)Mage::getStoreConfig('superdeals/slider/slide_order');
		if($order == 1){
			$productCollection = $this->getProductCollectionHigher();

		}
		elseif($order == 2){
			$productCollection = $this->getProductCollectionLower();
						
		}
		elseif($order == 3){
			$productCollection = $this->getProductCollectionRandom();
		}
		else {
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