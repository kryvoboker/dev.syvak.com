<?php

declare(strict_types=1);

return [
    'navigation_label' => 'Сторінка невдалої оплати',
    'tabs' => [
        'images' => 'Зображення',
        'seo_url' => 'SEO URL',
        'buttons_and_payments' => 'Кнопки та оплата',
        'support_contacts' => 'Контакти підтримки',
        'general_settings' => 'Загальні налаштування',
    ],
    'sections' => [
        'localized_content' => 'Локалізований контент',
        'retry_button' => 'Кнопка «Спробувати ще раз»',
        'alternative_payment_button' => 'Кнопка альтернативного способу оплати',
        'available_payment_methods' => 'Доступні активні способи оплати',
        'working_hours' => 'Графік роботи',
        'phones' => 'Номери телефонів',
        'emails' => 'Email-адреси',
    ],
    'labels' => [
        'model' => 'Налаштування сторінки невдалої оплати',
        'plural_model' => 'Налаштування сторінки невдалої оплати',
        'title' => 'Заголовок сторінки',
        'description' => 'Опис сторінки',
        'images' => 'Зображення сторінки невдалої оплати',
        'image' => 'Зображення',
        'width' => 'Ширина',
        'height' => 'Висота',
        'is_square' => 'Квадратне зображення',
        'background' => 'Фон конвертованого зображення',
        'custom_css_classes' => 'Власні CSS-класи',
        'enabled' => 'Активно',
        'button_text' => 'Текст кнопки',
        'use_contacts_data' => 'Використовувати дані зі сторінки «Контакти»',
        'working_hours' => 'Графік роботи',
        'phones' => 'Номери телефонів',
        'phone' => 'Номер телефону',
        'phone_type' => 'Тип телефону',
        'emails' => 'Email-адреси',
        'email' => 'Email-адреса',
    ],
    'helpers' => [
        'image' => 'Необов’язкове зображення. Підтримуються JPEG, PNG та SVG.',
        'background' => 'Вкажіть transparent або HEX-колір із шести символів, наприклад #FFFFFF.',
        'custom_css_classes' => 'Вкажіть CSS-класи через пробіл.',
        'use_contacts_working_hours' => 'Якщо активовано, буде показано графік роботи зі сторінки «Контакти».',
    ],
    'options' => [
        'mobile' => 'Мобільний',
        'landline' => 'Стаціонарний',
    ],
    'defaults' => [
        'title' => 'Щось пішло не так...',
        'description' => 'На жаль, ми не змогли обробити платіж. Кошти з вашої картки не списані.',
        'retry_button' => 'Спробувати ще раз',
        'alternative_payment_button' => 'Обрати інший спосіб оплати',
    ],
    'errors' => [
        'duplicate_slug' => 'Цей SEO URL вже використовується для вибраної мови.',
    ],
];
