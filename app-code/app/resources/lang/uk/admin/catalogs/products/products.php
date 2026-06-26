<?php

declare(strict_types=1);

return [
    // Navigation
    'navigation_label' => 'Товари',

    // Labels
    'labels' => [
        'model' => 'Товар',
        'plural_model' => 'Товари',
        'variant_model' => 'Варіація товару',
        'variant_plural_model' => 'Варіації товару',
        'add_discount' => 'Додати знижку',
        'add_attribute' => 'Додати атрибут',
        'delete_discount' => 'Видалити знижку',
        'delete_attribute' => 'Видалити атрибут',
        'size_guide_title' => 'Назва блоку / popup',
        'size_guide_short_description' => 'Короткий опис',
        'size_guide_table' => 'Таблиця розмірів',
        'size_guide_table_helper' => 'Встав таблицю у форматі TSV (tab) або CSV (використовуйте роздільник - ;). Кожен новий рядок — новий рядок таблиці.',
        'size_guide_table_preview' => 'Попередній перегляд таблиці',
        'size_guide_table_preview_empty' => 'Почни вводити TSV/CSV, і тут зʼявиться попередній перегляд.',
        'size_guide_image' => 'Зображення',
        'size_guide_full_description_title' => 'Назва блоку з повним описом',
        'size_guide_full_description' => 'Повний опис',
        'composition_title' => 'Назва блоку "Склад"',
        'composition_items' => 'Пункти списку "Склад"',
        'composition_item_value' => 'Пункт складу',
        'care_title' => 'Назва блоку "Догляд"',
        'care_items' => 'Пункти списку "Догляд"',
        'care_item_value' => 'Пункт догляду',
    ],

    // Sections
    'sections' => [
        'size_guide' => 'Довідник розмірів',
        'composition_and_care' => 'Склад і догляд',
        'composition_block' => 'Блок "Склад"',
        'care_block' => 'Блок "Догляд"',
    ],

    // Tabs
    'tabs' => [
        'size_guide' => 'Довідник розмірів',
        'composition_and_care' => 'Склад і догляд',
    ],

    // Pages
    'pages' => [
        'create' => 'Створити товар',
        'edit' => 'Редагувати товар',
        'variants' => 'Варіації товару',
        'create_variant' => 'Створити варіацію товару',
        'edit_variant' => 'Редагувати варіацію товару',
    ],

    'actions' => [
        'manage_variants' => 'Керувати варіаціями',
        'create_variant' => 'Створити варіацію',
        'back_to_product' => 'Назад до товару',
    ],

    // Errors
    'errors' => [
        'duplicate_attribute_language' => 'Комбінація атрибуту та мови повинна бути унікальною!',
        'validation_size_guide_table_required' => 'Таблиця розмірів обовʼязкова, якщо вказано зображення або розпочато заповнення таблиці.',
        'duplicate_variant_slug_language' => 'Для варіанта товару мова slug не може дублюватися.',
        'duplicate_variant_slug_value' => 'Такий SEO slug для цієї мови вже використовується іншим варіантом товару.',
        'duplicate_product_or_variant_slug_value' => 'Такий SEO slug уже використовується іншим товаром або варіантом товару.',
        'duplicate_variant_attributes_combination' => 'Варіант із таким самим набором атрибутів і значень уже існує для цього товару.',
    ],
];
