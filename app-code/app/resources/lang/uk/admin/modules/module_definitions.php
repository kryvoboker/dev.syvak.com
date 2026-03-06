<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Модулі',
    'labels'           => [
        'model'                    => 'Модуль',
        'plural_model'             => 'Модулі',
        'name'                     => 'Назва',
        'slug'                     => 'Slug',
        'nwidart_name'             => 'Кодовий модуль',
        'module_path'              => 'Шлях',
        'description'              => 'Опис',
        'is_installed'             => 'Встановлений',
        'is_enabled'               => 'Глобально увімкнений',
        'is_enabled_in_filesystem' => 'Увімкнений у файловій системі',
        'sort_order'               => 'Порядок сортування',
        'settings_schema'          => 'Схема налаштувань',
        'meta'                     => 'Мета-дані',
    ],
    'columns' => [
        'name'                     => 'Назва',
        'slug'                     => 'Slug',
        'nwidart_name'             => 'Кодовий модуль',
        'instances_count'          => 'Екземпляри',
        'is_enabled'               => 'Глобально',
        'is_installed'             => 'Встановлений',
        'is_enabled_in_filesystem' => 'Файлова система',
        'module_path'              => 'Шлях',
    ],
    'filters' => [
        'name'                     => 'Модуль',
        'is_enabled'               => 'Глобальний стан',
        'enabled_only'             => 'Тільки увімкнені',
        'disabled_only'            => 'Тільки вимкнені',
        'is_installed'             => 'Стан встановлення',
        'installed_only'           => 'Тільки встановлені',
        'not_installed_only'       => 'Тільки не встановлені',
        'is_enabled_in_filesystem' => 'Стан файлової системи',
        'filesystem_enabled_only'  => 'Тільки увімкнені у файловій системі',
        'filesystem_disabled_only' => 'Тільки вимкнені у файловій системі',
    ],
    'sections' => [
        'definition'      => 'Опис модуля',
        'state'           => 'Стан',
        'settings_schema' => 'Схема налаштувань',
        'meta'            => 'Мета-дані',
    ],
    'actions' => [
        'sync'         => 'Синхронізувати модулі',
        'enable'       => 'Увімкнути',
        'disable'      => 'Вимкнути',
        'add_instance' => 'Додати екземпляр',
    ],
    'notifications' => [
        'enabled'  => 'Модуль глобально увімкнено.',
        'disabled' => 'Модуль глобально вимкнено.',
        'synced'   => 'Синхронізацію модулів завершено. Створено: :created, оновлено: :updated, відсутні: :missing.',
    ],
];
