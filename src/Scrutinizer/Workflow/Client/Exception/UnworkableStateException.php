<?php

namespace Scrutinizer\Workflow\Client\Exception;

/**
 * This exception may be thrown from within workers if they realize that they are in an unworkable state.
 *
 * As a consequence, the worker will automatically terminate after processing the current activity.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class UnworkableStateException extends \RuntimeException
{
}