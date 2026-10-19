<?php

/*
 * The OpesCare blog is file-based: one Markdown file per post per locale,
 * under content/blog/{en,fr}/*.md, with YAML front matter. Nothing about a
 * post lives in the database; only reader comments do (blog_comments).
 */
return [
    // Where posts live. Tests point this at a fixture directory.
    'path' => env('BLOG_CONTENT_PATH', base_path('content/blog')),

    // Where the FAQ page reads its questions (content/faq/{locale}.yaml).
    'faq_path' => env('FAQ_CONTENT_PATH', base_path('content/faq')),

    // Generated 1200x630 hero / Open Graph cards: public/{images_dir}/{slug}.png
    'images_dir' => 'blog-images',

    // Optional TTF for the cards (GD cannot read woff2).
    'image_font' => env('BLOG_IMAGE_FONT'),

    'per_page' => 9,

    // Parsed posts are cached as plain arrays (never objects), keyed by a
    // fingerprint of the files, so an edited post invalidates itself.
    'cache_ttl' => 86400,

    'comments' => [
        'max_length'      => 2000,
        'min_seconds'     => 3,        // time-trap: faster than this is a bot
        'max_age_seconds' => 86400,    // a form older than a day is stale
        'per_minute'      => 3,
        'per_day'         => 20,
    ],
];
