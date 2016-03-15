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

namespace LinusShops\Prophet\Context;

use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
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
     * @When /^selector "([^"]*)" exists$/
     * @Then /^selector "([^"]*)" exists$/
     *
     * @param $cssSelector - The css selector to search for on the page.
     * @param int $attempts
     * @param int $waitInterval
     * @throws \Exception
     */
    public function waitForSelectorExistence($cssSelector, $attempts = 10, $waitInterval = 1)
    {
        $this->waitFor(function ($context) use ($cssSelector) {
            /** @var MinkContext $context */
            try {
                $context->assertSession()->elementExists(
                    'css',
                    $cssSelector,
                    $context->getSession()->getPage()
                );
            } catch (ElementNotFoundException $e) {
                return false;
            }

            return true;
        }, $attempts, $waitInterval);
    }

    /**
     * @Given /^selector "([^"]*)" is visible$/
     * @When /^selector "([^"]*)" is visible$/
     * @Then /^selector "([^"]*)" is visible$/
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

    public function waitForAtLeastOneVisibleElementOfType($selectorString)
    {
        $this->waitFor(function ($context) use ($selectorString) {
            /** @var $context Web */
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

    public function getElementByCssSelector($cssSelector)
    {
        $element = $this->getSession()
            ->getPage()
            ->find("css", $cssSelector);

        $this->assert($element != null, "{$cssSelector} not found on the page");

        return $element;
    }

    public function click($cssSelector)
    {
        $element = $this->getElementByCssSelector($cssSelector);

        $element->click();
    }

    public function doubleclick($cssSelector)
    {
        $element = $this->getElementByCssSelector($cssSelector);

        $element->doubleClick();
    }

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
     * @param string $cssSelector
     * @throws ExpectationException
     */
    public function assertIsVisible($cssSelector)
    {
        $this->assert($this->isVisible($cssSelector), $cssSelector.' is not visible on page');
    }
}
