<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation_label' => 'Application Settings',

    // Labels
    'labels' => [
        'model' => 'Application Setting',
        'plural_model' => 'Application Settings',
        'titles' => 'Site Titles',
        'meta_titles' => 'Meta Titles',
        'meta_descriptions' => 'Meta Descriptions',
        'meta_keywords' => 'Meta Keywords',
        'socials' => 'Social Networks',
        'timezone' => 'Timezone',
        'image_sizes' => 'Image Sizes',
        'logo_image_width' => 'Logo Width',
        'logo_image_height' => 'Logo Height',
        'ai_api_model' => 'AI Model',
        'ai_api_temperature' => 'AI Temperature',
        'ai_api_max_tokens' => 'AI Max Tokens',
        'ai_system_prompt' => 'AI System Prompt',
        'ai_api_wait_time_seconds' => 'AI Wait Time (sec)',
        'ai_api_max_calls' => 'AI Max Calls',
        'ai_api_max_retries' => 'AI Max Retries',
        'ai_api_max_retry_wait_time_seconds' => 'AI Retry Wait (sec)',
        'user_upload_max_size_mb' => 'User Avatar Max Size (MB)',
        'user_image_path' => 'User Avatar Upload Directory',
        'user_no_image' => 'User Fallback Avatar Path',
        'user_preview_list_width' => 'User List Preview Width',
        'user_preview_list_height' => 'User List Preview Height',
        'user_preview_page_width' => 'User Page Preview Width',
        'user_preview_page_height' => 'User Page Preview Height',
        'max_viewport_width' => 'Max Viewport Width',
        'path_to_logo' => 'Logo Path',
        'default_no_image' => 'Global Fallback No-Image',
        'prototype_quality' => 'Prototype Quality',
        'webp_quality' => 'WEBP Quality',
        'avif_quality' => 'AVIF Quality',
        'total_sizes_for_generate' => 'Image Scale Variants Count',
        'max_image_width_for_convert' => 'Max Convert Image Width',
        'max_image_height_for_convert' => 'Max Convert Image Height',
        'social_type' => 'Social Type',
    ],

    // Tabs
    'tabs' => [
        'seo' => 'SEO',
        'user' => 'User',
        'socials' => 'Socials',
        'system' => 'System',
        'ai' => 'AI',
    ],

    // Helpers
    'helpers' => [
        'titles' => 'Site titles for different pages',
        'meta_titles' => 'SEO meta titles for pages',
        'meta_descriptions' => 'SEO meta descriptions for pages',
        'meta_keywords' => 'SEO keywords for pages',
        'socials' => 'Social network links',
        'timezone' => 'Application timezone',
        'image_sizes' => 'Image size configurations',
        'logo_image_width' => 'Displayed logo width in pixels',
        'logo_image_height' => 'Displayed logo height in pixels',
        'ai_api_model' => 'OpenAI model name (for example: gpt-5-mini)',
        'ai_api_temperature' => 'OpenAI temperature value from 0 to 2',
        'ai_api_max_tokens' => 'Maximum number of generated tokens per request',
        'ai_system_prompt' => 'System prompt for translation requests',
        'ai_api_wait_time_seconds' => 'Delay between API calls in seconds',
        'ai_api_max_calls' => 'Maximum API calls per limiter window',
        'ai_api_max_retries' => 'Maximum retries for failed requests',
        'ai_api_max_retry_wait_time_seconds' => 'Maximum wait before retry in seconds',
        'user_upload_max_size_mb' => 'Maximum allowed avatar upload size in MB',
        'user_image_path' => 'Relative directory where user avatars are stored. Supports {year} and {month} placeholders.',
        'user_no_image' => 'Default avatar image path for users without uploaded image',
        'user_preview_list_width' => 'Avatar width in users list preview (admin)',
        'user_preview_list_height' => 'Avatar height in users list preview (admin)',
        'user_preview_page_width' => 'Avatar width in user edit page image editor',
        'user_preview_page_height' => 'Avatar height in user edit page image editor',
        'max_viewport_width' => 'Maximum frontend viewport width in pixels',
        'path_to_logo' => 'Relative path to site logo image',
        'default_no_image' => 'Global fallback image path when source image is missing',
        'prototype_quality' => 'JPEG/PNG quality for generated prototype images (1-100)',
        'webp_quality' => 'WEBP conversion quality (1-100)',
        'avif_quality' => 'AVIF conversion quality (1-100)',
        'total_sizes_for_generate' => 'How many scaled image variants to generate (1x..Nx)',
        'max_image_width_for_convert' => 'Maximum image width allowed for conversion',
        'max_image_height_for_convert' => 'Maximum image height allowed for conversion',
    ],

    // Columns
    'columns' => [
        'timezone' => 'Timezone',
    ],

    // Placeholders
    'placeholders' => [
    ],
];
