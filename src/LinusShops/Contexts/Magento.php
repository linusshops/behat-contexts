<?php
/**
 *
 *
 * @author Sam Schmidt <samuel@dersam.net>
 * @since 2016-03-15
 */

namespace LinusShops\Contexts;

trait Magento
{
    /**
     * @Given /^I should be able to add the product to my cart$/
     */
    public function assertCanAddProductToCart()
    {
        throw new \Behat\Behat\Tester\Exception\PendingException();
    }
}
