<?php

declare(strict_types=1);

namespace App\Filament\Pages\Wiki;

use App\Filament\Navigation\AdminNavigationGroupEnum;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Lang;
use UnitEnum;

abstract class BaseWikiPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::BookOpen;

    protected static string|null|UnitEnum $navigationGroup = AdminNavigationGroupEnum::Wiki;

    protected string $view = 'filament.pages.wiki.page';

    abstract protected static function getWikiTranslationPath(): string;

    abstract protected static function getWikiScreenshotDirectory(): string;

    public function getTitle(): string
    {
        return (string) __(static::getWikiTranslationPath() . '.title');
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function getSubheading(): ?string
    {
        $description_key = static::getWikiTranslationPath() . '.description';

        if (! Lang::has($description_key)) {
            return null;
        }

        return (string) __($description_key);
    }

    public static function getNavigationLabel(): string
    {
        return (string) __(static::getWikiTranslationPath() . '.navigation_label');
    }

    public function getBreadcrumbs(): array
    {
        return [
            static::getUrl() => static::getNavigationLabel(),
        ];
    }

    public function getIntroTitle(): string
    {
        return (string) __(static::getWikiTranslationPath() . '.intro_title');
    }

    public function getIntroDescription(): string
    {
        return (string) __(static::getWikiTranslationPath() . '.intro_description');
    }

    /**
     * @return array<int, string>
     */
    public function getPracticalExamples(): array
    {
        $examples = __(static::getWikiTranslationPath() . '.examples');

        if (! is_array($examples)) {
            return [];
        }

        return array_values(array_filter($examples, fn (mixed $value): bool => is_string($value) && filled($value)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getWikiSections(): array
    {
        $sections = __(static::getWikiTranslationPath() . '.sections');

        if (! is_array($sections)) {
            return [];
        }

        return array_map(function (mixed $section): array {
            if (! is_array($section)) {
                return [];
            }

            $screenshot_name          = (string) ($section['screenshot'] ?? '');
            $screenshot_relative_path = filled($screenshot_name)
                ? 'images/wiki/' . static::getWikiScreenshotDirectory() . '/' . $screenshot_name
                : '';

            return [
                'title'                  => (string) ($section['title'] ?? ''),
                'description'            => (string) ($section['description'] ?? ''),
                'items'                  => is_array($section['items'] ?? null) ? $section['items'] : [],
                'screenshot_relative'    => $screenshot_relative_path,
                'screenshot_url'         => $this->resolveScreenshotUrl($screenshot_relative_path),
                'screenshot_description' => (string) ($section['screenshot_description'] ?? ''),
            ];
        }, $sections);
    }

    private function resolveScreenshotUrl(string $screenshot_relative_path): ?string
    {
        if (blank($screenshot_relative_path)) {
            return null;
        }

        if (! file_exists(public_path($screenshot_relative_path))) {
            return null;
        }

        return asset($screenshot_relative_path);
    }
}
