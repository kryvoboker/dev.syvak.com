<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Фільтр товарів',

    'tabs' => [
        'core'           => 'Основні налаштування',
        'filter_options' => 'Опції фільтрів',
    ],

    'labels' => [
        'model'                           => 'Фільтр товарів',
        'plural_model'                    => 'Фільтр товарів',
        'code'                            => 'Код',
        'context_type'                    => 'На якій сторінці застосовувати фільтр',
        'is_enabled'                      => 'Увімкнено',
        'is_price_filter_enabled'         => 'Фільтр за ціною увімкнено',
        'is_attribute_filtering_enabled'  => 'Фільтрація за атрибутами увімкнена',
        'price_source_mode'               => 'Режим джерела ціни',
        'discount_only_policy'            => 'Політика discount_only',
        'facet_strategy'                  => 'Стратегія підрахунку facet',
        'min_stock_quantity'              => 'Мінімальний залишок товару',
        'rebuild_chunk_size'              => 'Розмір чанку rebuild',
        'rebuild_lock_timeout_seconds'    => 'Таймаут lock rebuild (сек.)',
        'max_selected_values_per_group'   => 'Макс. обраних значень у групі',
        'base_currency_indexing_required' => 'Індексація тільки у базовій валюті',
        'active_index_version'            => 'Активна версія індексу',
        'building_index_version'          => 'Версія індексу в побудові',
        'last_status'                     => 'Останній статус',
        'last_run_mode'                   => 'Останній режим запуску',
        'last_progress_percent'           => 'Останній прогрес (%)',
        'index_rows_total'                => 'Кількість рядків індексу',
        'last_full_rebuild_at'            => 'Останній повний rebuild',
        'last_incremental_sync_at'        => 'Останній incremental sync',
        'rebuild_lock_key'                => 'Ключ lock rebuild',
        'rebuild_lock_acquired_at'        => 'Час захоплення lock rebuild',
        'filter_items'                    => 'Опції фільтрів',
        'source_type'                     => 'Тип джерела',
        'source_id'                       => 'ID джерела',
        'sort_order'                      => 'Порядок сортування',
        'get_key'                         => 'GET ключ',
        'get_value'                       => 'GET значення',
        'get_extra'                       => 'GET додаткові параметри',
        'key'                             => 'Ключ',
        'value'                           => 'Значення',
        'filter_mode'                     => 'Режим фільтра',
        'filter_mode_hint'                => 'Дозволені значення беруться з config/catalog-filter.php',
        'filter_mode_description'         => 'Опис режиму фільтра',
        'min_price'                       => 'Мінімальна ціна',
        'max_price'                       => 'Максимальна ціна',
        'step'                            => 'Крок ціни',
        'option_labels'                   => 'Переклади назви опції',
        'option_label_value'              => 'Назва',
        'price'                           => 'Ціна',
    ],

    'helpers' => [
        'see_wiki' => 'Дивись сторінку "Wiki".',
    ],

    'sections' => [
        'general'           => 'Загальні налаштування',
        'strategy_by_price' => 'Стратегія фільтрації по цінам',
        'strategy_by_stock' => 'Стратегія фільтрації по залишкам',
        'rebuild'           => 'Налаштування rebuild',
        'index_meta'        => 'Статус виконання індексації',
        'filter_options'    => 'Точкове налаштування опцій фільтрів',
    ],

    'actions' => [
        'refresh_index_status' => 'Оновити статус індексу',
        'sync_groups'          => 'Синхронізувати групи',
        'sync_values'          => 'Синхронізувати значення',
        'sync_all'             => 'Синхронізувати групи та значення',
    ],

    'notifications' => [
        'index_status_refreshed' => 'Індекс оновлено. Рядків: :rows_total, Версія: :index_version, Статус: :status.',
        'groups_synced'          => 'Групи синхронізовано. Створено: :created, Оновлено: :updated, Всього: :total.',
        'values_synced'          => 'Значення синхронізовано. Створено: :created, Оновлено: :updated, Видалено: :removed, Всього: :total.',
        'all_synced'             => 'Фільтр товарів синхронізовано. Груп: :groups_total, Значень: :values_total.',
    ],

    'filter_mode_options' => [
        'range'    => 'Діапазон',
        'boolean'  => 'Так / Ні',
        'multiple' => 'Множинний вибір',
    ],

    'filter_mode_descriptions' => [
        'range'    => 'Використовуйте для числових значень і діапазонів (наприклад, ціна від/до).',
        'boolean'  => 'Використовуйте для бінарних станів (наприклад, у наявності).',
        'multiple' => 'Використовуйте, коли можна обирати одне або декілька значень зі списку.',
    ],
];
