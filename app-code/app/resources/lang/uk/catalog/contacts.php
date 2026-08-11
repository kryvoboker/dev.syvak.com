<?php

declare(strict_types=1);

return [
    'fields' => [
        'name' => 'Ім’я',
        'email' => 'Email',
        'phone' => 'Телефон',
        'text' => 'Повідомлення',
        'file' => 'Файл',
    ],
    'placeholders' => [
        'name' => 'Ваше ім’я',
        'email' => 'your@email.com',
        'phone' => '+38 000 000 00 00',
        'text' => 'Ваше повідомлення',
    ],
    'labels' => [
        'map' => 'Мапа',
        'images' => 'Зображення сторінки контактів',
        'contact_information' => 'Контактна інформація',
        'contact_details' => 'Контактні дані',
    ],
    'buttons' => [
        'submit' => 'Надіслати повідомлення',
        'open_map' => 'Відкрити мапу',
        'open_address' => 'Відкрити адресу',
        'close_error' => 'Закрити помилку',
    ],
    'validation' => [
        'invalid' => 'Вкажіть коректне значення.',
        'invalid_file' => 'Виберіть коректний файл.',
    ],
    'messages' => [
        'success' => 'Ваше повідомлення успішно надіслано.',
    ],
    'errors' => [
        'delivery_failed' => 'Не вдалося надіслати повідомлення. Спробуйте пізніше.',
        'page_not_found' => 'Сторінку контактів не знайдено.',
    ],
    'fallbacks' => [
        'title' => 'Контакти',
        'working_hours_title' => 'Графік роботи',
    ],
    'email' => [
        'response_subject' => 'Відповідь на ваше звернення',
    ],
];
