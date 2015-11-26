<?php
/**
 * Bing Product Feed Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to ali.nawaz@cooperativecomputing.com so we can send you a copy immediately.
 *
 * @category    Cooperative Computing
 * @package     CooperativeComputing_BingProductFeed
 * @author      Ali Nawaz <ali.nawaz@cooperativecomputing.com>
 * @copyright   Copyright 2015 Cooperative Computing (http://www.cooperativecomputing.com)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

set_time_limit(0);
require_once 'app/Mage.php';
// Mage::app('default');
Mage::app(Mage::app()->getStore()->getCode());

$categories = isset($_GET['categories']) && count($_GET['categories']) > 0 ? $_GET['categories'] : [];
$categories = array_filter($categories);

try {
    $handle    = fopen('bingshopping.txt', 'w');
    $heading   = array('MerchantProductID','Title','Brand','SKU','ProductURL','Price','Availability','Description','ImageURL','MerchantCategory','ShippingWeight');
    $feed_line = implode("\t", $heading)."\r\n";
    fwrite($handle, $feed_line);

    $products = Mage::getModel('catalog/product')->getCollection();
    if (count($categories) > 0) {
        $products
            ->joinField(
                'category_id', 'catalog/category_product', 'category_id', 
                'product_id = entity_id', null, 'left'
            )
            ->addAttributeToFilter('category_id', array(
                    array('in' => $categories),
            ));
    }
    $products->addAttributeToFilter('status', 1);//enabled
    $products->addAttributeToFilter('visibility', 4); //catalog, search
    $products->addAttributeToSelect('*');
    $prodIds = $products->getAllIds();
    $product = Mage::getModel('catalog/product');

    foreach ($prodIds as $productId) {
        $product->load($productId);
        $productUrl = $product->getProductUrl();
        $productUrl = str_replace('/' . basename($_SERVER['PHP_SELF']), '/index.php', $productUrl); // include index.php in url
        // $productUrl = str_replace('/' . basename($_SERVER['PHP_SELF']), '', $productUrl); // do not included index.php in url

        $product_data = array();
        $product_data['MerchantProductID'] = $product->getId(); 
        $product_data['Title']             = $product->getName(); 
        $product_data['Brand']             = $product->getResource()->getAttribute('manufacturer')->getFrontend()->getValue($product);
        $product_data['SKU']               = $product->getSku();
        $product_data['ProductURL']        = $productUrl;

        if ($product->getSpecialPrice()) {
            $product_data['Price'] = $product->getSpecialPrice();
        } else {
            $product_data['Price'] = $product->getPrice();
        }

        $product_data['Availability']     = $product->getIsInStock();
        $product_data['Description']      = strip_tags($product->getDescription());
        $product_data['ImageURL']         = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
        $product_data['MerchantCategory'] = '';

        foreach ($product->getCategoryIds() as $_categoryId) {
            $category = Mage::getModel('catalog/category')->load($_categoryId);
            $product_data['MerchantCategory'] .= $category->getName().', ';
        }

        $product_data['MerchantCategory'] = rtrim($product_data['MerchantCategory'],', ');       
        $product_data['ShippingWeight']   = $product->getWeight();
         
        //sanitize data
        foreach ($product_data as $k => $val) {
            $bad              = array('"', "\r\n", "\n", "\r", "\t");
            $good             = array("", " ", " ", " ", "");
            $product_data[$k] = '' . str_replace($bad,$good,$val) . '';
        }

        $feed_line = implode("\t", $product_data) . "\r\n";
        fwrite($handle, $feed_line);
        fflush($handle);
    }

    fclose($handle);
    echo 'success';
     
} catch(Exception $e) {
    die($e->getMessage());
}
?>