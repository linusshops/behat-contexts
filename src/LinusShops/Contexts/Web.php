<?php
/**
 * Provide additional functionality for MinkContext.
 *
 * Some functionality will only work when using Selenium2- these items are noted
 * in the docblocks.
 *
 * @author Sam Schmidt <samuel@dersam.net>
 * @since 2016-03-15
 */

namespace LinusShops\Contexts;

use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\ResponseTextException;
use Behat\MinkExtension\Context\MinkContext;

class Web extends MinkContext
{
    use Generic;

    /**
     * If using the Selenium2Driver, will automatically screenshot any failed
     * steps and save them in the current directory.
     *
     * Screenshots are named as "step-<step-line-number>-<timestamp>.png".
     *
     * @AfterStep
     * @param AfterStepScope $event
     */
    public function takeScreenshotAfterFailedStep(AfterStepScope $event)
    {
        if (!$event->getTestResult()->isPassed()) {
            if ($this->getSession()->getDriver() instanceof Selenium2Driver) {
                $stepLine = $event->getStep()->getLine();
                $time = time();
                $fileName = "./step-{$stepLine}-{$time}.png";
                if (is_writable('.')) {
                    $screenshot = $this->getSession()->getDriver()->getScreenshot();
                    $stepText = $event->getStep()->getText();
                    if (file_put_contents($fileName, $screenshot)) {
                        echo "Screenshot for '{$stepText}' placed in {$fileName}".PHP_EOL;
                    } else {
                        echo "Screenshot failed: {$fileName} is not writable.";
                    }
                }
            }
        }
    }

    /**
     * Set the size of the viewport in pixels. Useful for testing visibility
     *
     * @Given /^the viewport has width "(?P<width>[^"]+)" and height "(?P<height>[^"]+)"$/
     * @When /^the viewport changes to width "(?P<width>[^"]+)" and height "(?P<height>[^"]+)"$/
     *
     * @param $width - width in pixels
     * @param $height - height in pixels
     */
    public function setViewportSize($width, $height)
    {
        $this->getSession()->getDriver()->resizeWindow($width, $height);
    }

    /**
     * Runs a wait for until an cssSelector exists on the page. This will pass
     * even if the cssSelector is not visible on the page. It only needs to exist
     * in the DOM.
     *
     * @Given /^selector "([^"]*)" exists$/
     *
     * @param $cssSelector - The css selector to search for on the page.
     * @param int $attempts
     * @param int $waitInterval
     * @throws \Exception
     */
    public function waitForSelectorExistence($cssSelector, $attempts = 10, $waitInterval = 1)
    {
        $this->waitFor(function ($context) use ($cssSelector) {
            /** @var Web $context */
            try {
                $context->assertElementExists($cssSelector);
            } catch (ElementNotFoundException $e) {
                return false;
            }

            return true;
        }, $attempts, $waitInterval);
    }

    /**
     * Passes when the given css selector is visible on the page.
     *
     * @Given /^selector matching "([^"]*)" is visible$/
     */
    public function waitForSelectorVisibility($cssSelector, $attempts = 10, $waitInterval = 1)
    {
        $this->waitFor(function ($context) use ($cssSelector) {
            /** @var Web $context */
            try {
                $context->assertIsVisible($cssSelector);
            } catch (ExpectationException $e) {
                return false;
            }

            return true;
        }, $attempts, $waitInterval);
    }

    /**
     * Passes when at least one element with the given selector is visible on the page.
     *
     * Use when there are multiple potential matches, but not all of them may
     * be visible on the page.
     *
     * @Given /^at least one selector matching "([^"]*)" is visible$/
     *
     * @param $selectorString
     * @throws \Exception
     */
    public function waitForAtLeastOneVisibleElementOfType($selectorString)
    {
        $this->waitFor(function ($context) use ($selectorString) {
            /** @var Web $context */
            $page = $context->getSession()->getPage();
            /** @var NodeElement[] $nodes */
            $nodes = $page->findAll('css', $selectorString);
            /** @var NodeElement $node */
            foreach ($nodes as $node) {
                if ($node->isVisible()) {
                    return true;
                }
            }
            return false;
        });
    }

    public function waitForElementText($selector, $text)
    {
        $this->waitFor(function ($context) use ($selector, $text) {
            try {
                /** @var Web $context */
                $context->assertElementContainsText($selector, $text);
            } catch (ExpectationException $e) {

            }
        });
    }

    public function waitForVisibleText($text)
    {
        $this->waitFor(function ($context) use ($text) {
            try {
                /** @var Web $context */
                $context->assertPageContainsText($text);
                return true;
            } catch (ResponseTextException $e) {

            }
        });
    }

    /**
     * Get the first element that matches the given css selector.
     *
     * @param $cssSelector
     * @return NodeElement|mixed|null
     */
    public function getElementByCssSelector($cssSelector)
    {
        $element = $this->getSession()
            ->getPage()
            ->find("css", $cssSelector);

        return $element;
    }

    /**
     * Get all elements matching the given css selector.
     *
     * @param $cssSelector
     * @return \Behat\Mink\Element\NodeElement[]
     */
    public function getElementsByCssSelector($cssSelector)
    {
        $elements = $this->getSession()
            ->getPage()
            ->findAll("css", $cssSelector);

        return $elements;
    }

    /**
     * Click the first element matching the given css selector.
     *
     * @When /^I click the element matching "([^"]*)"$/
     * @When /^I click on "([^"]*)"$/
     *
     * @param $cssSelector
     * @throws ExpectationException - if no matching elements found
     */
    public function click($cssSelector)
    {
        $element = $this->getElementByCssSelector($cssSelector);

        $this->assert($element != null, "{$cssSelector} not found on the page");

        $element->click();
    }

    /**
     * Doubleclick the first element matching the given css selector.
     *
     * @When /^I doubleclick the element matching "([^"]*)"$/
     *
     * @param $cssSelector
     * @throws ExpectationException - if no matching elements found
     */
    public function doubleclick($cssSelector)
    {
        $element = $this->getElementByCssSelector($cssSelector);

        $this->assert($element != null, "{$cssSelector} not found on the page");

        $element->doubleClick();
    }

    /**
     * Click the first visible element matching the css selector.
     *
     * @When /^I click the first visible element matching "([^"]*)"$/
     *
     * @param $selectorString
     * @throws ExpectationException
     */
    public function clickFirstVisibleElementOfType($selectorString)
    {
        $page = $this->getSession()->getPage();
        /** @var NodeElement[] $nodes */
        $nodes = $page->findAll('css', $selectorString);
        foreach ($nodes as $node) {
            if ($node->isVisible()) {
                $node->click();
                return;
            }
        }

        throw new ExpectationException(
            "No visible {$selectorString} element found.",
            $this->getSession()->getDriver()
        );
    }

    /**
     * Determine if the specified css selector is visible on the page.
     *
     * @param string $cssSelector
     * @return bool
     */
    public function isVisible($cssSelector)
    {
        $session = $this->getSession();
        $page = $session->getPage();

        $pageElement = $page->find('css', $cssSelector);

        return $pageElement == null ? false : $pageElement->isVisible();
    }

    /**
     * Throw an exception if condition is false.
     *
     * @param boolean $condition
     * @param string $failureMessage
     * @throws ExpectationException
     */
    public function assert($condition, $failureMessage)
    {
        if (!$condition) {
            throw new ExpectationException($failureMessage, $this->getSession()->getDriver());
        }
    }

    /**
     * Assert that an element matching the given selector exists on the page.
     *
     * @param $cssSelector
     * @throws ElementNotFoundException
     */
    public function assertElementExists($cssSelector)
    {
        $this->assertSession()->elementExists(
            'css',
            $cssSelector,
            $this->getSession()->getPage()
        );
    }

    /**
     * Assert the given selector is visible on the page.
     *
     * @param string $cssSelector
     * @throws ExpectationException
     */
    public function assertIsVisible($cssSelector)
    {
        $this->assert($this->isVisible($cssSelector), $cssSelector.' is not visible on page');
    }

    /**
     * Assert the given selector is NOT visible on the page.
     *
     * @param string $cssSelector
     * @throws ExpectationException
     */
    public function assertIsNotVisible($cssSelector)
    {
        $this->assert(!$this->isVisible($cssSelector), $cssSelector.' is visible on page');
    }

    /**
     * @param $parameterName
     * @param $expectedValue
     * @throws ExpectationException
     */
    public function assertQueryStringParameterValue($parameterName, $expectedValue)
    {
        $matches = array();
        $matched = preg_match(
            "/{$parameterName}=([^&#]*)/",
            $this->getSession()->getCurrentUrl(),
            $matches
        );

        $this->assert($matched, 'Parameter '.$parameterName.' does not exist in querystring');
        $this->assert(
            $matches[1] == $expectedValue,
            "{$matches[1]} does not match expected {$expectedValue}"
        );
    }

    /**
     * Call a function on all elements matching the given selector.
     *
     * Useful to apply an assertion to every element that matches a given selector.
     *
     * @param $cssSelector
     * @param callable $function
     * @return array
     */
    public function mapElements($cssSelector, callable $function)
    {
        $elements = $this->getElementByCssSelector($cssSelector);
        $result = array();
        if (!empty($elements)) {
            $result = $this->map($elements, $function);
        }

        return $result;
    }
}
