# Modules: Registration and Loading Guide

Этот документ описывает, как в проекте регистрируются модульные провайдеры (`nwidart/laravel-modules`), какие есть стратегии загрузки и какие правила нужно соблюдать при добавлении нового модуля.

## 1. Кто управляет загрузкой модулей

Основная цепочка:

1. `App\Providers\ModuleProvidersServiceProvider`
2. `App\Services\Modules\ModuleProviderResolverService`
3. `App\Services\Modules\ModuleProviderRegistrarService`

Ключевая идея: **модульный `ServiceProvider` не должен сам решать, загружаться ему или нет**. Решение принимается централизованно в `ModuleProviderResolverService`.

## 2. Где берётся список модулей

Источник данных:

- БД: таблица `module_definitions` (активность модуля, `nwidart_name`, путь и мета)
- БД: таблица `module_instances` (настройки экземпляров, `is_enabled`, `placement`, `settings.shared.page_types`)
- Конфиг модуля: `Modules/<ModuleName>/config/config.php`

`ModuleProviderResolverService` берёт только активные модули (`enabled()`) и фильтрует их по текущему request-контексту.

## 3. Стратегии загрузки провайдеров

Разрешённые стратегии задаются в `config/modules-runtime.php`:

- `eager`
- `route_matched`
- `middleware_after_session`

### `eager`

Загружается сразу в `ModuleProvidersServiceProvider::boot()`.

Когда использовать:
- модуль нужен рано в lifecycle;
- модуль не зависит от `request()->route()` и session.

### `route_matched`

Загружается по событию `Illuminate\Routing\Events\RouteMatched`.

Когда использовать:
- модуль зависит от route name/page type;
- нужно, чтобы маршрут уже был сопоставлен.

### `middleware_after_session`

Загружается middleware `App\Http\Middleware\Modules\RegisterModuleProvidersAfterSession`.

Когда использовать:
- модуль зависит от данных сессии/пользовательских предпочтений;
- модуль должен стартовать после `StartSession`.

## 4. Как resolver выбирает конкретный провайдер

Для каждого релевантного модуля resolver строит FQCN по шаблону:

`Modules\\<NwidartName>\\Providers\\<NwidartName>ServiceProvider`

Пример:

- `nwidart_name = ProductsCarousel`
- провайдер: `Modules\\ProductsCarousel\\Providers\\ProductsCarouselServiceProvider`

Если класс не существует, провайдер не регистрируется, а в `stack` пишется warning.

## 5. Правила для модульного ServiceProvider

1. В `boot()` не дублировать lazy-логику (не делать второй resolver внутри провайдера).
2. Если провайдер зарегистрирован — он должен корректно завершать `registerViews()`, `registerConfig()` и т.д.
3. Не использовать хардкод чужого namespace.
4. `name` и `nameLower` должны соответствовать модулю.

## 6. Правила добавления нового модуля

Минимальный checklist:

1. Создать модуль в `Modules/<ModuleName>/`.
2. Проверить `module.json`:
- `name` = `<ModuleName>`
- `providers` содержит корректный FQCN именно этого модуля.
3. Проверить `composer.json` модуля:
- PSR-4: `"Modules\\<ModuleName>\\": "app/"`
4. Добавить стратегию в `Modules/<ModuleName>/config/config.php`:

```php
'runtime' => [
    'provider_loading_strategy' => 'route_matched',
],
```

5. Синхронизировать модульные определения (через существующий механизм sync в проекте).
6. Убедиться, что модуль активен в `module_definitions` и нужные `module_instances` включены.
7. Очистить кэш после изменений провайдеров/конфигов:

```bash
php artisan optimize:clear
```

## 7. Частые ошибки

1. Неверный namespace провайдера в `module.json`.
2. Дублирование lazy-условий в модульном провайдере и в resolver.
3. Ожидание `page_types`, когда поле пустое/null, без fallback-условий.
4. Неочищенный кэш после смены provider/config.

## 8. Быстрая диагностика

1. Проверить, что класс провайдера существует по PSR-4 пути.
2. Проверить стратегию в `config/config.php` модуля.
3. Проверить запись модуля в БД (`module_definitions.is_enabled`, `is_installed`, `is_enabled_in_filesystem`).
4. Проверить релевантные `module_instances` (`is_enabled`, `placement`, `settings.shared.page_types`).
5. Проверить логи `stack` и выполнить `php artisan optimize:clear`.

---

Если модуль должен работать так же, как `Carousel` или `ProductsCarousel`, ориентируйся на текущую связку:
- `ModuleProvidersServiceProvider`
- `ModuleProviderResolverService`
- `ModuleProviderRegistrarService`
и не добавляй вторую независимую систему lazy-загрузки внутри модуля.
