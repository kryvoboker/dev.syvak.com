<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation_label' => 'Глобальні конфіги',

    // Labels
    'labels' => [
        'global_configs' => 'Глобальні конфіги',
        'selected' => 'Вибрано',
        'key' => 'Ключ',
        'value' => 'Значення',
        'is_active' => 'Активний',
    ],

    // Sections
    'sections' => [
        'global_configs' => 'Глобальні конфіги',
    ],

    // Helpers
    'helpers' => [
        'global_configs' => 'Порожні рядки пропускаються під час збереження. Значення може містити звичайний текст, числа, JSON, серіалізовані дані або null.',
    ],

    // Placeholders
    'placeholders' => [
        'key' => 'site.header.title',
        'value' => 'Будь-яке текстове значення або JSON',
        'search' => 'Пошук за ключем або значенням',
    ],

    // Actions
    'actions' => [
        'create' => 'Створити конфіг',
        'add' => 'Додати конфіг',
        'delete_all' => 'Видалити всі конфіги',
        'disable_selected' => 'Вимкнути вибрані конфіги',
        'delete_selected' => 'Видалити вибрані конфіги',
    ],

    // Filters
    'filters' => [
        'status' => 'Статус',
        'key' => 'Назва',
        'value' => 'Значення',
    ],

    // Notifications
    'notifications' => [
        'saved_single' => 'Глобальний конфіг збережено.',
        'saved' => 'Глобальні конфіги збережено. Створено: :created_count, оновлено: :updated_count, видалено: :deleted_count.',
        'deleted_all' => 'Видалено глобальних конфігів: :deleted_count.',
        'disabled_selected' => 'Вимкнено вибраних конфігів: :updated_count.',
        'deleted_selected' => 'Видалено вибраних конфігів: :deleted_count.',
    ],
];
