<?php
#Example Format Config
return [
    'return_statement'=> 'return response()->json($data);',

    'Modules' => [
        'Blog' => [
            'Models' => [
                'Post' => [
                    'Fields' => [
                        'Description' => ['type' => 'string', 'options' => ['default' => '1', 'index']],
                        'Title' => ['type' => 'string'],
                    ],
                    'Relations' => [
                        'BelongsToMany' => [
                            'Blog::Category',
                        ],
                        'morphTo',
                    ],
                    'CRUD' => [
                        'Admin' => ['CRUD'],
                        'Post' => ['CUD'],
                        'User' => ['CRD'],
                    ],
                    'Requests' =>[
                        'admin' => [
                            'store' =>[
                                'title' => ['required' , 'in:2,3']
                            ],
                            'update' =>[
                                'description' => ['required', 'in:5,2']
                            ]
                        ]

                    ]
                ],
                'Category' => [
                    'Fields' => [
                        'Title' => ['type' => 'string']
                    ],
                    'Relations' => [
                        'HasMany' => [
                            'Blog::Post'
                        ]
                    ],
                    'CRUD' => [
                        'Admin' => ['CRUD'],
                        'User' => ['R'],
                    ]
                ],
            ]
        ]
    ],
];
