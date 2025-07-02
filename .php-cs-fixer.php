<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'vendor',
        'node_modules',
        'storage',
        'bootstrap/cache',
        'public',
        'database/migrations', // On exclut souvent les migrations
    ])
    ->notPath([
        'server.php',
        'artisan',
        'nova', // Si vous utilisez Laravel Nova
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        // Base PSR-12 avec ajustements Laravel
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        '@PHP80Migration' => true,

        // Règles spécifiques Laravel
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public',
                'method_protected',
                'method_private',
            ],
            'sort_algorithm' => 'alpha',
        ],

        // Formatage des tableaux
        'array_syntax' => ['syntax' => 'short'],
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters', 'match'],
        ],

        // Espacement
        'binary_operator_spaces' => [
            'default' => 'single_space',
            'operators' => [
                '=>' => 'align_single_space_minimal',
                '=' => 'align_single_space_minimal',
            ],
        ],

        // Imports
        'fully_qualified_strict_types' => true,
        'global_namespace_import' => [
            'import_classes' => false,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],

        // PHPDoc
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_to_comment' => false, // Important pour Laravel
        'phpdoc_summary' => false,

        // Règles de code
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'remove_inheritdoc' => false,
        ],
        'declare_strict_types' => false, // Désactivé pour compatibilité Laravel
        'strict_comparison' => true,

        // Classes
        'self_accessor' => true,
        'visibility_required' => [
            'elements' => ['property', 'method', 'const'],
        ],

        // Strings
        'single_quote' => true,
        'escape_implicit_backslashes' => true,

        // Control structures
        'no_alternative_syntax' => true,
        'no_unneeded_control_parentheses' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/.php-cs-fixer.cache'); // Améliore les performances
