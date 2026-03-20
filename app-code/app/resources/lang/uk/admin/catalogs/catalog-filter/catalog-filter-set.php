<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Механізм фільтрації',

    'labels' => [
        'model'                           => 'Механізм фільтрації',
        'plural_model'                    => 'Механізм фільтрації',
        'code'                            => 'Код',
        'context_type'                    => 'Тип контексту',
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
    ],

    'sections' => [
        'general'    => 'Загальні налаштування',
        'strategy'   => 'Стратегія фільтрації',
        'rebuild'    => 'Налаштування rebuild',
        'index_meta' => 'Статус виконання індексації',
    ],

    'actions' => [
        'refresh_index_status' => 'Оновити статус індексу',
        'sync_groups'          => 'Синхронізувати групи',
        'sync_values'          => 'Синхронізувати значення',
        'sync_all'             => 'Синхронізувати групи та значення',
    ],

    'notifications' => [
        'index_status_refreshed' => 'Статус індексації оновлено.',
        'groups_synced'          => 'Групи синхронізовано. Створено: :created, Оновлено: :updated, Всього: :total.',
        'values_synced'          => 'Значення синхронізовано. Створено: :created, Оновлено: :updated, Видалено: :removed, Всього: :total.',
        'all_synced'             => 'Механізм фільтрації синхронізовано. Груп: :groups_total, Значень: :values_total.',
    ],
];
