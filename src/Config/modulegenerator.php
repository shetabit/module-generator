<?php

return [
    "Modules" => [
        "Blog" => [
            "Models" => [
                "Post" => [
                    "Fields" => [
                        "Title" => ["type" => "string", "required" => true],
                        "Description" => ["type" => "string", "required" => true],
                    ],
                    "Relations" => [
                        "BelongsTo" => [
                            "Blog::Category" => true
                        ]
                    ],
                    "CRUD" => [
                        "Admin" => ["CRUD"],
                        "User" => ["R"],
                    ]
                ],
                "Category" => [
                    "Fields" => [
                        "Title" => ["type" => "string", "required" => true]
                    ],
                    "Relations" => [
                        "HasMany" => [
                            "Blog::Post" => true
                        ]
                    ],
                    "CRUD" => [
                        "Admin" => ["CRUD"],
                        "User" => ["R"],
                    ]
                ],
            ]
        ]
    ],

    "Auth" => [
        "Admin" => ["guard" => "admin-api"],
        "User" => ["guard" => "user-api"],
        "All" => ["guard" => null]
    ]

];
