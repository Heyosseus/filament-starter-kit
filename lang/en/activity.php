<?php

declare(strict_types=1);

return [
    'model' => [
        'singular' => 'Activity',
        'plural' => 'Activity',
    ],

    'nav' => [
        'label' => 'Activity log',
    ],

    'system' => 'System',

    'fields' => [
        'when' => 'When',
        'event' => 'Event',
        'subject' => 'Subject',
        'by' => 'By',
        'area' => 'Area',
        'batch' => 'Batch',
        'field' => 'Field',
        'old' => 'Was',
        'new' => 'Now',
    ],

    'events' => [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
    ],

    'sections' => [
        'summary' => 'Summary',
        'changes' => 'What changed',
        'properties' => 'Recorded properties',
    ],

    'filters' => [
        'from' => 'From',
        'until' => 'Until',
    ],

    'table' => [
        'empty_heading' => 'Nothing recorded yet',
        'empty_description' => 'Changes to users and roles will appear here.',
    ],

    'relation' => [
        'title' => 'History',
        'empty' => 'No changes recorded for this record.',
        'open' => 'Open',
    ],
];
