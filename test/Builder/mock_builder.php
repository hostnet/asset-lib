<?php
@mkdir(__DIR__ . '/out', 0777, true);

file_put_contents(__DIR__ . '/out/args.json', json_encode(array_slice($argv, 1), JSON_PRETTY_PRINT));
file_put_contents(
    __DIR__ . '/out/stdin.json',
    json_encode(json_decode(file_get_contents('php://stdin')), JSON_PRETTY_PRINT)
);

// Fake an error
error_log('FOOBAR');

// Fake the output
echo json_encode(['action' => 'WRITE', 'file' => 'a.js', 'metadata' => []]) . "\n";
