<?php
/**
 * Provides generic context functionality. Items in this trait should be
 * generally useful in any context, web or otherwise.
 *
 * @author Sam Schmidt <samuel@dersam.net>
 * @since 2016-03-15
 */

namespace LinusShops\Prophet\Context;

use Behat\Mink\Exception\ExpectationException;

trait Generic
{
    /**
     * Attempt the given function a configurable number of times, waiting X seconds
     * between each attempt. If the step does not succeed in any of the attempts,
     * success being defined as the lambda returning true, then throw an exception.
     *
     * @param callable $lambda - some action to attempt. Will be reattempted until
     * successful or the specified number of attempts is reached. Only a return
     * value of true is considered successful, anything else will be reattempted.
     * @param int $attempts - the number of times to run the lambda before giving up.
     * @param int $waitInterval - how long to wait between attempts
     * @throws \Exception - thrown if the lambda is never successful.
     */
    public function waitFor(callable $lambda, $attempts = 10, $waitInterval = 1)
    {
        for ($i = 0; $i < $attempts; $i++) {
            try {
                if ($lambda($this) === true) {
                    return;
                }
            } catch (\Exception $e) {
                //Do nothing and pass on to next iteration
            }

            $this->wait($waitInterval);
        }

        throw new \Exception(
            "Step did not succeed after {$attempts} attempts."
        );
    }

    /**
     * Define a step that waits for a specified number of seconds before
     * continuing. In most cases, it is better to define a waitFor, as this
     * will check for a specific action to complete, but sometimes it is unavoidable.
     *
     * @Given /^I wait "([^"]*)" seconds$/
     *
     * @param $seconds
     */
    public function wait($seconds)
    {
        sleep($seconds);
    }

    /**
     * Throw an exception if condition is false.
     *
     * @param boolean $condition
     * @param string $failureMessage
     * @throws \Exception
     */
    public function assert($condition, $failureMessage)
    {
        if (!$condition) {
            throw new \Exception($failureMessage);
        }
    }
}
