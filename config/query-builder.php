<?php

return [

    /*
     * By default the package will use the `page`, `per-page`, `include`, `filter`, `sort`
     * and `fields` query parameters as described in the readme.
     *
     * You can customize these query string parameters here.
     */
    'parameters' => [
        'include' => 'include',

        'filter' => 'filter',

        'sort' => 'sort',

        'fields' => 'fields',

        'append' => 'append',

        'page' => 'page',

        'per-page' => 'per-page',
    ],

    /*
     * Related model counts are included using the relationship name suffixed with this string.
     * For example: GET /users?include=postsCount
     */
    'count_suffix' => 'Count',

    /*
     * Pagination default settings
     */
    'pagination' => [
        'per-page' => [
            'default' => 15,
            'min' => 1,
            'max' => 50,
        ],
    ],
];
