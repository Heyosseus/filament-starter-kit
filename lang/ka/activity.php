<?php

declare(strict_types=1);

return [
    'model' => [
        'singular' => 'აქტივობა',
        'plural' => 'აქტივობა',
    ],

    'nav' => [
        'label' => 'აქტივობის ჟურნალი',
    ],

    'system' => 'სისტემა',

    'fields' => [
        'when' => 'როდის',
        'event' => 'მოქმედება',
        'subject' => 'ობიექტი',
        'by' => 'ვინ',
        'area' => 'სფერო',
        'batch' => 'პაკეტი',
        'field' => 'ველი',
        'old' => 'იყო',
        'new' => 'გახდა',
    ],

    'events' => [
        'created' => 'შეიქმნა',
        'updated' => 'განახლდა',
        'deleted' => 'წაიშალა',
    ],

    'sections' => [
        'summary' => 'მიმოხილვა',
        'changes' => 'რა შეიცვალა',
        'properties' => 'ჩაწერილი მონაცემები',
    ],

    'filters' => [
        'from' => 'დან',
        'until' => 'მდე',
    ],

    'table' => [
        'empty_heading' => 'ჩანაწერები ჯერ არ არის',
        'empty_description' => 'მომხმარებლებსა და როლებში შეტანილი ცვლილებები აქ გამოჩნდება.',
    ],

    'relation' => [
        'title' => 'ისტორია',
        'empty' => 'ამ ჩანაწერზე ცვლილებები არ დაფიქსირებულა.',
        'open' => 'გახსნა',
    ],
];
