<?php
declare(strict_types=1);

/**
 * @copyright 2018 Hostnet B.V.
 */

// Fake an error
error_log('FOO');

echo 'BAR';

exit(1);
