<?php
/**
 * OVERRIDDEN IN LOCAL CODE POOL, ADDING LOGGING TO getLinks() per ticket 2014062010000282
 */
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
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
 * @package     Mage_Downloadable
 * @copyright   Copyright (c) 2011 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Downloadable product type model
 *
 * @category    Mage
 * @package     Mage_Downloadable
 * @author      Magento Core Team <core@magentocommerce.com>
 */

//include 'Mage_Downloadable_Model_Product_Type';
class ICC_Downloadable_Model_Product_Type extends Mage_Downloadable_Model_Product_Type
{
        /**
     * Get downloadable product links
     *
     * Customized by Gorilla to wrap collection with try/catch so we can be sure it is logged.
     * Also log case where no links are returned in the collection.
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getLinks($product = null)
    {
        $product = $this->getProduct($product);
        /* @var Mage_Catalog_Model_Product $product */
        if (is_null($product->getDownloadableLinks())) {
            try {
            $_linkCollection = Mage::getModel('downloadable/link')->getCollection()
                ->addProductToFilter($product->getId())
                ->addTitleToResult($product->getStoreId())
                ->addPriceToResult($product->getStore()->getWebsiteId());

            // Gorilla customization
            $sql = (string)$_linkCollection->getSelect();
            // End Gorilla customization

            $linksCollectionById = array();
            foreach ($_linkCollection as $link) {
                /* @var Mage_Downloadable_Model_Link $link */

                $link->setProduct($product);
                $linksCollectionById[$link->getId()] = $link;
            }

            // Gorilla customization - if there are no links returned in the collection, either there was a problem with
            // the query or the product doesn't have any links. In either case, we'll log the query.
            if(count($linksCollectionById) == 0) { // Custom log: ticket#2014062010000282
                //Mage::log(__METHOD__.':'.__LINE__.": No links returned for product " . $product->getId() . ". SQL = $sql", null, 'downloadable_missing_links.log');
            }
            // End Gorilla customization

            $product->setDownloadableLinks($linksCollectionById);
            } catch(Exception $e) { // Gorilla customization: catch and log exception, if any
                Mage::log(__METHOD__.':'.__LINE__.": Exception getting links for product " . $product->getId() . ". SQL = $sql. Exception: " . $e->__toString() . '. ' . $e->getTraceAsString(), Zend_Log::ERR, 'downloadable_missing_links.log');
                throw $e; // Throw exception so that it's handled as usual
            }
        }
        return $product->getDownloadableLinks();
    }

    /**
     * Prepare additional options/information for order item which will be
     * created from this product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return array
     */
    public function getOrderOptions($product = null)
    {
    	$options = parent::getOrderOptions($product);
    	if ($linkIds = $this->getProduct($product)->getCustomOption('downloadable_link_ids')) {
    		$linkOptions = array();
    		$links = $this->getLinks($product);
    		foreach (explode(',', $linkIds->getValue()) as $linkId) {
    			if (isset($links[$linkId])) {
    				$linkOptions[] = $linkId;
    			}
    		}
    		$options = array_merge($options, array('links' => $linkOptions));
    		if(count($linkOptions) == 0) { // Gorilla Custom log: ticket#2014062010000282
    			Mage::log(__METHOD__.':'.__LINE__.": No links in order options for " . $product->getId() . ". Product links: " . implode(', ', array_keys($links)), null, 'downloadable_missing_links.log');
    		}
    	}
    	else { // Gorilla Custom log: ticket#2014062010000282
    		Mage::log(__METHOD__.':'.__LINE__.": No link id for downloadable_link_ids found for " . $product->getId(), null, 'downloadable_missing_links.log');
    	}
    	$options = array_merge($options, array(
    			'is_downloadable' => true,
    			'real_product_type' => self::TYPE_DOWNLOADABLE
    	));
    	return $options;
    }

}
