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
}
