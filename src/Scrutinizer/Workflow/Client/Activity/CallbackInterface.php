<?php

namespace Scrutinizer\Workflow\Client\Activity;

interface CallbackInterface
{
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