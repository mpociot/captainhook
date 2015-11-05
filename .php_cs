<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__);

$fixers = array(
    '-psr0',
    'newline_after_open_tag',
    'ordered_use',
    'short_array_syntax',
    'single_blank_line_before_namespace',
    'single_quote',
    'unalign_double_arrow',
    'unalign_equals',
    'unused_use',
);

return Symfony\CS\Config\Config::create()
    ->finder($finder)
    ->fixers($fixers)
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->setUsingCache(true);
