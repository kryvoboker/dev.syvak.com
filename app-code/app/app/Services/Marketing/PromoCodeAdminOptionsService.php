<?php

declare(strict_types=1);

namespace App\Services\Marketing;

use App\Models\ApplicationSettings\Currency;
use App\Models\ApplicationSettings\Language;
use App\Models\Catalogs\Categories\Category;
use App\Models\Catalogs\Products\Product;
use App\Models\Users\User;
use App\Models\Users\UserGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class PromoCodeAdminOptionsService
{
    /**
     * @return array<string, string>
     */
    public function currencyOptions(): array
    {
        return Currency::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->toBase()
            ->mapWithKeys(fn (Currency $currency): array => [
                $this->stringValue($currency->getKey()) => sprintf(
                    '%s — %s%s',
                    $currency->code,
                    $currency->name,
                    $currency->is_default ? ' (' . __('admin/marketing/promo_codes.labels.default_currency') . ')' : '',
                ),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    /**
     * @param array<int, int> $excluded_ids
     * @return array<string, string>
     */
    public function userOptions(string $search = '', array $excluded_ids = []): array
    {
        $search = Str::trim($search);

        return User::query()
            ->where('is_active', true)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nested_query) use ($search): void {
                    $nested_query
                        ->whereLike('name', "%{$search}%")
                        ->orWhereLike('lastname', "%{$search}%")
                        ->orWhereLike('email', "%{$search}%")
                        ->orWhereLike('telephone', "%{$search}%");
                });
            })
            ->when($excluded_ids !== [], fn (Builder $query): Builder => $query->whereNotIn('id', $excluded_ids))
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (User $user): array => [
                $this->stringValue($user->getKey()) => Str::squish(sprintf('%s %s — %s', $user->name, $user->lastname, $user->email)),
            ])
            ->all();
    }

    public function userLabelById(int|string|null $id): ?string
    {
        if (! is_numeric($id)) {
            return null;
        }

        $user = User::query()->find($this->integerValue($id));

        return $user instanceof User
            ? Str::squish(sprintf('%s (%s)', $user->name, $user->email ?? ''))
            : null;
    }

    /**
     * @return array<string, string>
     */
    public function userGroupOptions(): array
    {
        return UserGroup::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->mapWithKeys(fn (mixed $name, mixed $id): array => [$this->stringValue($id) => $this->stringValue($name)])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    /**
     * @param array<int, int> $excluded_ids
     * @return array<string, string>
     */
    public function userGroupSearchOptions(string $search, array $excluded_ids = []): array
    {
        return UserGroup::query()
            ->where('is_active', true)
            ->whereLike('name', "%{$search}%")
            ->when($excluded_ids !== [], fn (Builder $query): Builder => $query->whereNotIn('id', $excluded_ids))
            ->orderBy('name')
            ->limit(50)
            ->pluck('name', 'id')
            ->mapWithKeys(fn (mixed $name, mixed $id): array => [$this->stringValue($id) => $this->stringValue($name)])
            ->all();
    }

    public function userGroupLabelById(int|string|null $id): ?string
    {
        if (! is_numeric($id)) {
            return null;
        }

        $name = UserGroup::query()->whereKey((int) $id)->value('name');

        return $name === null ? null : $this->stringValue($name);
    }

    public function defaultCurrencyId(): ?int
    {
        $currency = (new Currency())->getDefaultActiveCurrency();

        return $currency === null ? null : $this->integerValue($currency->getKey());
    }

    /**
     * @return array<string, string>
     */
    /**
     * @param array<int, int> $excluded_ids
     * @return array<string, string>
     */
    public function productSearchOptions(string $search, array $excluded_ids = []): array
    {
        $language_id = $this->integerValue(resolve_language_by_locale(app()->getLocale())?->id);
        $active_language_ids = Language::query()->where('is_active', true)->pluck('id');

        return Product::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($search, $active_language_ids): void {
                $query
                    ->whereHas('productDescription', fn (Builder $description_query): Builder => $description_query
                        ->whereIn('language_id', $active_language_ids)
                        ->whereLike('name', "%{$search}%"))
                    ->orWhereLike('sku', "%{$search}%")
                    ->orWhereLike('model', "%{$search}%")
                    ->orWhereLike('ean', "%{$search}%");
            })
            ->when($excluded_ids !== [], fn (Builder $query): Builder => $query->whereNotIn('id', $excluded_ids))
            ->with(['productDescription' => function (\Illuminate\Database\Eloquent\Relations\Relation $query) use ($language_id): void {
                $query->where('language_id', $language_id);
            }])
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Product $product): array => [$this->stringValue($product->getKey()) => $this->productLabel($product)])
            ->all();
    }

    public function productLabel(Product $product): string
    {
        $name = Str::trim($this->stringValue($product->productDescription->first()?->name));
        $identifiers = collect([$product->sku, $product->model, $product->ean])
            ->filter(fn (mixed $value): bool => filled($value))
            ->implode(' / ');

        return Str::squish(implode(' — ', array_filter([$name, $identifiers])));
    }

    /**
     * @return array<string, string>
     */
    /**
     * @param array<int, int> $excluded_ids
     * @return array<string, string>
     */
    public function categorySearchOptions(string $search, array $excluded_ids = []): array
    {
        $language_id = $this->integerValue(resolve_language_by_locale(app()->getLocale())?->id);
        $active_language_ids = Language::query()->where('is_active', true)->pluck('id');

        return Category::query()
            ->where('is_active', true)
            ->whereHas('categoryDescription', fn (Builder $query): Builder => $query
                ->whereIn('language_id', $active_language_ids)
                ->whereLike('name', "%{$search}%"))
            ->when($excluded_ids !== [], fn (Builder $query): Builder => $query->whereNotIn('id', $excluded_ids))
            ->with(['categoryDescription' => function (\Illuminate\Database\Eloquent\Relations\Relation $query) use ($language_id): void {
                $query->where('language_id', $language_id);
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (Category $category): array => [
                $this->stringValue($category->getKey()) => Str::trim($this->stringValue($category->categoryDescription->first()?->name)),
            ])
            ->all();
    }

    public function productLabelById(int|string|null $id): ?string
    {
        if (! is_numeric($id)) {
            return null;
        }

        $product = Product::query()
            ->with(['productDescription' => function (\Illuminate\Database\Eloquent\Relations\Relation $query): void {
                $query->where('language_id', resolve_language_by_locale(app()->getLocale())?->id);
            }])
            ->find($this->integerValue($id));

        return $product instanceof Product ? $this->productLabel($product) : null;
    }

    public function categoryLabelById(int|string|null $id): ?string
    {
        if (! is_numeric($id)) {
            return null;
        }

        $category = Category::query()
            ->with(['categoryDescription' => function (\Illuminate\Database\Eloquent\Relations\Relation $query): void {
                $query->where('language_id', resolve_language_by_locale(app()->getLocale())?->id);
            }])
            ->find($this->integerValue($id));

        return $category instanceof Category
            ? Str::trim($this->stringValue($category->categoryDescription->first()?->name))
            : null;
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private function integerValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
