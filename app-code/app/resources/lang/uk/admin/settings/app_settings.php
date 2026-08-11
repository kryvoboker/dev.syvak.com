<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation_label' => 'Налаштування застосунку',

    // Labels
    'labels' => [
        'model' => 'Параметр застосунку',
        'plural_model' => 'Параметри застосунку',
        'titles' => 'Заголовки сайту',
        'meta_titles' => 'Мета-заголовки',
        'meta_descriptions' => 'Мета-описи',
        'meta_keywords' => 'Мета-ключові слова',
        'socials' => 'Соціальні мережі',
        'timezone' => 'Часовий пояс',
        'image_sizes' => 'Розміри зображень',
        'logo_image_width' => 'Ширина логотипа',
        'logo_image_height' => 'Висота логотипа',
        'ai_api_model' => 'AI модель',
        'ai_api_temperature' => 'AI температура',
        'ai_api_max_tokens' => 'AI макс. токенів',
        'ai_system_prompt' => 'AI системний промпт',
        'ai_api_wait_time_seconds' => 'AI пауза (сек)',
        'ai_api_max_calls' => 'AI макс. викликів',
        'ai_api_max_retries' => 'AI макс. повторів',
        'ai_api_max_retry_wait_time_seconds' => 'AI пауза повтору (сек)',
        'user_upload_max_size_mb' => 'Макс. розмір аватара (МБ)',
        'user_image_path' => 'Директорія завантаження аватарів',
        'user_no_image' => 'Шлях fallback-аватара',
        'user_preview_list_width' => 'Ширина превʼю в списку користувачів',
        'user_preview_list_height' => 'Висота превʼю в списку користувачів',
        'user_preview_page_width' => 'Ширина превʼю на сторінці користувача',
        'user_preview_page_height' => 'Висота превʼю на сторінці користувача',
        'max_viewport_width' => 'Максимальна ширина viewport',
        'path_to_logo' => 'Шлях до логотипа',
        'default_no_image' => 'Глобальний fallback no-image',
        'prototype_quality' => 'Якість прототипу',
        'webp_quality' => 'Якість WEBP',
        'avif_quality' => 'Якість AVIF',
        'total_sizes_for_generate' => 'Кількість варіантів масштабу',
        'max_image_width_for_convert' => 'Макс. ширина для конвертації',
        'max_image_height_for_convert' => 'Макс. висота для конвертації',
        'social_type' => 'Тип соціальної мережі',
    ],

    // Tabs
    'tabs' => [
        'seo' => 'SEO',
        'user' => 'Користувач',
        'socials' => 'Соціальні мережі',
        'system' => 'Система',
        'ai' => 'AI',
    ],

    // Helpers
    'helpers' => [
        'titles' => 'Заголовки сайту для різних сторінок',
        'meta_titles' => 'SEO мета-заголовки для сторінок',
        'meta_descriptions' => 'SEO мета-описи для сторінок',
        'meta_keywords' => 'SEO ключові слова для сторінок',
        'socials' => 'Посилання на соціальні мережі',
        'timezone' => 'Часовий пояс застосунку',
        'image_sizes' => 'Налаштування розмірів зображень',
        'logo_image_width' => 'Відображувана ширина логотипа в пікселях',
        'logo_image_height' => 'Відображувана висота логотипа в пікселях',
        'ai_api_model' => 'Назва OpenAI моделі (наприклад: gpt-5-mini)',
        'ai_api_temperature' => 'Значення temperature від 0 до 2',
        'ai_api_max_tokens' => 'Максимальна кількість токенів у відповіді',
        'ai_system_prompt' => 'Системний промпт для запитів перекладу',
        'ai_api_wait_time_seconds' => 'Затримка між API викликами в секундах',
        'ai_api_max_calls' => 'Максимум API викликів за вікно лімітера',
        'ai_api_max_retries' => 'Максимальна кількість повторів при помилках',
        'ai_api_max_retry_wait_time_seconds' => 'Максимальна пауза перед повтором у секундах',
        'user_upload_max_size_mb' => 'Максимально дозволений розмір завантаження аватара в МБ',
        'user_image_path' => 'Відносна директорія, де зберігаються аватари користувачів. Підтримує плейсхолдери {year} і {month}.',
        'user_no_image' => 'Шлях до зображення аватара за замовчуванням',
        'user_preview_list_width' => 'Ширина аватара в превʼю списку користувачів (адмінка)',
        'user_preview_list_height' => 'Висота аватара в превʼю списку користувачів (адмінка)',
        'user_preview_page_width' => 'Ширина аватара в редакторі на сторінці користувача',
        'user_preview_page_height' => 'Висота аватара в редакторі на сторінці користувача',
        'max_viewport_width' => 'Максимальна ширина viewport фронтенду в пікселях',
        'path_to_logo' => 'Відносний шлях до зображення логотипа сайту',
        'default_no_image' => 'Глобальний fallback-шлях для відсутніх зображень',
        'prototype_quality' => 'Якість JPEG/PNG для згенерованих прототипів (1-100)',
        'webp_quality' => 'Якість конвертації WEBP (1-100)',
        'avif_quality' => 'Якість конвертації AVIF (1-100)',
        'total_sizes_for_generate' => 'Скільки масштабованих варіантів зображення генерувати (1x..Nx)',
        'max_image_width_for_convert' => 'Максимальна ширина зображення для конвертації',
        'max_image_height_for_convert' => 'Максимальна висота зображення для конвертації',
    ],

    // Columns
    'columns' => [
        'timezone' => 'Часовий пояс',
    ],

    // Placeholders
    'placeholders' => [
    ],
];
