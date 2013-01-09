<?php

namespace Scrutinizer\Workflow\Client\Activity;

interface CallbackInterface
{
    /**
     * This is called once before the worker starts consuming messages.
     *
     * @return void
     */
    public function initialize();

    /**
     * Processes the input, and returns the desired output.
     *
     * @param string $input
     *
     * @return string
     */
    public function handle($input);

    public function cleanUp();
}