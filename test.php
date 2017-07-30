<?php
require __DIR__ . '/vendor/autoload.php';

$logger = new \Symfony\Component\Console\Logger\ConsoleLogger(
    $output = new \Symfony\Component\Console\Output\BufferedOutput(\Symfony\Component\Console\Output\OutputInterface::VERBOSITY_DEBUG)
);

\Hostnet\Component\Resolver\Packer::pack($logger);

echo $output->fetch();
