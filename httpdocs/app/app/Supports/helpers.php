<?php

declare(strict_types=1);

use App\Data\AppSettingsData;
use App\Services\AppSettingsService;
use App\Services\Images\ImageUrlBuilderService;

if (!function_exists('clear_telephone')) {
    /**
     * @param string|null $telephone
     * @param bool        $is_delete_first_nums
     *
     * @return string
     */
    function clear_telephone(?string $telephone, bool $is_delete_first_nums = false): string
    {
        if (!isset($telephone)) {
            return '';
        }

        if ($is_delete_first_nums) {
            return (string)(preg_replace(['/\D+/', '/^38/'], '', $telephone) ?: $telephone);
        }

        return (string)(preg_replace('/\D+/', '', $telephone) ?: $telephone);
    }
}

if (!function_exists('parse_telephone')) {
    /**
     * @param string $telephone
     *
     * @return string
     */
    function parse_telephone(string $telephone): string
    {
        $telephone = clear_telephone($telephone, true);

        $mask         = '+38 (___) ___-__-__';
        $phone_length = \Illuminate\Support\Str::length($telephone);

        for ($index_number = 0; $index_number < $phone_length; $index_number++) {
            $mask = preg_replace('/_/', $telephone[$index_number], $mask, 1);
        }

        return $mask;
    }
}

if (!function_exists('trim_strs_in_arr')) {
    /**
     * @param array $arr
     *
     * @return array
     */
    function trim_strs_in_arr(array $arr): array
    {
        return array_map(function ($item) {
            if (is_string($item)) {
                return trim($item);
            }

            return $item;
        }, $arr);
    }
}

if (!function_exists('convert_img_and_get_url')) {
    /**
     * @param string|null $path
     * @param int         $width
     * @param int|null    $height
     * @param bool        $is_square
     * @param string      $bg_color HEX or transparent color
     *
     * @return string
     */
    function convert_img_and_get_url(?string $path, int $width, ?int $height = null, bool $is_square = true, string $bg_color = '000000'): string
    {
        return app(ImageUrlBuilderService::class)->url($path, $width, $height, $is_square, $bg_color);
    }
}

if (!function_exists('multiple_convert_img_and_get_url')) {
    /**
     * @param string|null $path
     * @param int         $width
     * @param int|null    $height
     * @param bool        $is_square
     * @param string      $bg_color HEX or transparent color
     *
     * @return array
     */
    function multiple_convert_img_and_get_url(?string $path, int $width, ?int $height = null, bool $is_square = true, string $bg_color = '000000'): array
    {
        return app(ImageUrlBuilderService::class)->multipleUrl($path, $width, $height, $is_square, $bg_color);
    }
}

if (!function_exists('get_app_settings')) {
    /**
     * @return AppSettingsData|null
     */
    function get_app_settings(): ?AppSettingsData
    {
        return app(AppSettingsService::class)->getSettings();
    }
}

if (!function_exists('breadcrumb')) {
    /**
     * @param string      $title
     * @param string|null $url
     *
     * @return array
     */
    function breadcrumb(string $title, ?string $url = null): array
    {
        return ['title' => $title, 'url' => $url];
    }
}
