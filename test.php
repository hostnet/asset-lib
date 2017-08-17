<?php
require __DIR__ . '/vendor/autoload.php';

$logger = new \Symfony\Component\Console\Logger\ConsoleLogger(
    $output = new \Symfony\Component\Console\Output\BufferedOutput(\Symfony\Component\Console\Output\OutputInterface::VERBOSITY_DEBUG)
);

try {
    \Hostnet\Component\Resolver\Packer::pack(__DIR__ . '/test/fixtures', $logger);
} catch (\Exception $e) {
    var_dump($e);
}

echo $output->fetch();
