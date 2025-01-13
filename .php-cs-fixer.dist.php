<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/config')
    ->in(__DIR__ . '/migrations')
    ->in(__DIR__ . '/public')
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
;

return Retailcrm\PhpCsFixer\Defaults::rules()
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/var/.php_cs.cache')
;
