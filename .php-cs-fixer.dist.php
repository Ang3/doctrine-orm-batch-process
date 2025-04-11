<?php

declare(strict_types=1);

$header = <<<'EOF'
    This file is part of package ang3/doctrine-orm-batch-process
    
    This source file is subject to the MIT license that is bundled
    with this source code in the file LICENSE.
    EOF;

$finder = PhpCsFixer\Finder::create()
    ->ignoreDotFiles(false)
    ->ignoreVCSIgnored(true)
    ->exclude('tests/Fixtures')
    ->in(__DIR__)
    ->append([
        __DIR__.'/dev-tools/doc.php',
        // __DIR__.'/php-cs-fixer', disabled, as we want to be able to run bootstrap file even on lower PHP version, to show nice message
    ])
;

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP81Migration' => true,
        '@PHP80Migration:risky' => true,
        '@PHP74Migration' => true,
        '@PHP74Migration:risky' => true,
        '@PHPUnit100Migration:risky' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@Symfony' => true,
        'general_phpdoc_annotation_remove' => ['annotations' => ['expectedDeprecation']], // one should use PHPUnit built-in method instead
        'header_comment' => ['header' => $header],
        'heredoc_indentation' => false, // TODO switch on when # of PR's is lower
        'modernize_strpos' => true, // needs PHP 8+ or polyfill
        'no_useless_concat_operator' => false, // TODO switch back on when the `src/Console/Application.php` no longer needs the concat
        'use_arrow_functions' => false, // TODO switch on when # of PR's is lower
    ])
    ->setFinder($finder)
;

return $config;
