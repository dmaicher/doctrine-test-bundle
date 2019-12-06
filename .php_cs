<?php

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'psr0' => false,
        'yoda_style' => false,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'general_phpdoc_annotation_remove' => [
           'annotations' => ['author'],
        ],
        'no_useless_return' => true,
        'phpdoc_to_comment' => false,
        'multiline_whitespace_before_semicolons' => [
            'strategy' => 'new_line_for_chained_calls',
        ],
        'no_superfluous_phpdoc_tags' => true,
        'phpdoc_var_annotation_correct_order' => true,
        'void_return' => true,
    ])
;
