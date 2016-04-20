<?php
/**
 * Provides some Magento specific actions, with verification against the
 * Magento DB.
 *
 * This trait expects to be applied to a class that inherits from LinusShops\Contexts\Web.
 *
 * @author Sam Schmidt <samuel@dersam.net>
 * @since 2016-03-15
 */

namespace LinusShops\Contexts;

trait Magento
{
    public function viewConfigurableProduct($productId, $autoselect = [])
    {
        $product = \Mage::getModel('catalog/product')->load($productId);
        $url = $product->getProductUrl();
        $hash = '#';
        foreach ($autoselect as $key => $value) {
            if ($hash != '#') {
                $hash .= '&';
            }

            $hash .= "{$key}={$value}";
        }

        $this->visit($url.$hash);

        $this->waitForVisibleText($product->getName());

        return $product;
    }
}
