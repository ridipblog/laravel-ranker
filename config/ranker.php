<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Order Column Name
    |--------------------------------------------------------------------------
    |
    | The default database column used to store the sequential sort order index.
    | You can override this per-model inside the model's $sortable property.
    |
    */
    'order_column_name' => 'order_column',

    /*
    |--------------------------------------------------------------------------
    | Starting Sort Index
    |--------------------------------------------------------------------------
    |
    | The initial integer index assigned to the first item (typically 1 or 0).
    |
    */
    'start_order' => 1,

    /*
    |--------------------------------------------------------------------------
    | Sort When Creating
    |--------------------------------------------------------------------------
    |
    | Automatically calculate and assign the highest order number when a new
    | record is created without an explicit order value.
    |
    */
    'sort_when_creating' => true,

    /*
    |--------------------------------------------------------------------------
    | Allowed Models for Global Reorder API Route
    |--------------------------------------------------------------------------
    |
    | If you use the built-in universal RankerController route, list all models
    | allowed to be reordered via HTTP requests for security.
    |
    */
    'allowed_models' => [
        // \App\Models\Project::class,
        // \App\Models\Task::class,
    ],
];
