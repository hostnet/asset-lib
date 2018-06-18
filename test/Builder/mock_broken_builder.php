<?php
// Fake an error
error_log('FOO');

echo 'BAR';

exit(1);
