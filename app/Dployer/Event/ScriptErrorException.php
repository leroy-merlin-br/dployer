<?php

namespace Dployer\Event;

use RuntimeException;

/**
 * Exception to throw when a script returns with error and command should abort.
 */
class ScriptErrorException extends RuntimeException
{
}
