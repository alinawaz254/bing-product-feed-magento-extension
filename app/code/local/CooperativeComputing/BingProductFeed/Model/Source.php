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

class CooperativeComputing_BingProductFeed_Model_Source
{
    public function toOptionArray()
    {
        $store          = Mage::getModel('core/store')->load(Mage_Core_Model_App::DISTRO_STORE_ID);
        $rootCategoryId = $store->getRootCategoryId();
        $array          = [];
        $cat['value']   = '';
        $cat['label']   = '-- All Category Products --';
        $array[]        = $cat;

        return $this->getTreeCategories($rootCategoryId, false, $array, 0);
    }

    public function getTreeCategories($parentId, $isChild, $array, $level){
        $allCats = Mage::getModel('catalog/category')->getCollection()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter('is_active','1')
                    // ->addAttributeToFilter('include_in_menu','1')
                    ->addAttributeToFilter('parent_id',array('eq' => $parentId))
                    ->addAttributeToSort('position', 'asc');

        foreach($allCats as $category)
        {
            $cat['value'] = $category->getId();
            $cat['label'] = $category->getName() . ' (' . $category->getProductCount() . ')';

            if ($level > 0) {
                $beforeLabel = '';
                for ($x=1; $x<=$level; $x++) {
                    $beforeLabel .= ' --- ';
                }
                $beforeLabel = rtrim($beforeLabel);
                $beforeLabel .= ' > ';
            }
            $cat['label'] = $beforeLabel . $cat['label'];

            $array[] = $cat;

            $subcats = $category->getChildren();
            if($subcats != ''){
                // $array = array_merge($array, $this->getTreeCategories($category->getId(), true));
                // $level++;
                $array = $this->getTreeCategories($category->getId(), true, $array, $level+1);
            }
        }
        return $array;
    }
}
