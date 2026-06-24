<?php

declare(strict_types=1);

namespace App\Supports\Services\Images;

use App\Jobs\ConvertImagePrototypeJob;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use InvalidArgumentException;

final readonly class ImageUrlBuilderService
{
    public function __construct(
        private Request $request,
    ) {}

    /**
     * @param string $bg_color HEX or transparent color
     *
     * @return string[]
     */
    public function multipleUrl(?string $path, int $width, ?int $height = null, bool $is_square = true, string $bg_color = 'ffffff'): array
    {
        $total_sizes_for_generate = max(
            1,
            (int)data_get(
                get_app_settings(),
                'system_settings.images.total_sizes_for_generate',
                (int)config('app.images.total_sizes_for_generate', 4),
            ),
        );
        $path                     = (string)$path;
        $height                   ??= $width;

        $this->checkSourceImage($path);

        $urls = [
            'original_thumb' => $this->assetVersioned($path),
        ];

        for ($scale = 1; $scale <= $total_sizes_for_generate; $scale++) {
            $urls["thumb_{$scale}x"] = $this->url($path, $width * $scale, $height * $scale, $is_square, $bg_color);
        }

        return $urls;
    }

    /**
     * Generate URL for image with specified dimensions.
     *
     * @param string|null $path     Original path (relative to public), e.g: "images/products/2025/12/ABC123.jpg"
     * @param int         $width    Required width
     * @param int|null    $height   Required height (defaults to width)
     * @param string      $bg_color HEX or transparent color
     */
    public function url(?string $path, int $width, ?int $height = null, bool $is_square = true, string $bg_color = 'ffffff'): string
    {
        $path   = (string)$path;
        $height ??= $width;
        $path   = Str::ltrim($path, '/');

        $this->validateArgs($path, $width, $height);

        if (
            Storage::fileExists($path) === false ||
            $width > (int)data_get(get_app_settings(), 'system_settings.images.max_image_width_for_convert', (int)config('app.images.max_image_width_for_convert', 2500)) ||
            $height > (int)data_get(get_app_settings(), 'system_settings.images.max_image_height_for_convert', (int)config('app.images.max_image_height_for_convert', 2500))
        ) {
            return $this->assetVersioned($path);
        }

        $image_size = getimagesize(Storage::path($path));

        // If original image is smaller or equal than requested size - return original
        if ($image_size !== false) {
            [$original_width, $original_height] = $image_size;

            if ($original_width < $width || $original_height < $height) {
                $width  = $original_width;
                $height = $original_height;
            }

            if ($is_square === false) {
                $this->calculateAspectionSizes($original_width, $original_height, $width, $height);
            } else {
                $width  = max($width, $height);
                $height = $width;
            }
        }

        // 1) If browser supports AVIF - try to return AVIF (if exists)
        if ($this->clientSupports('image/avif')) {
            $avif_rel = $this->webRelativePath('avif', $path, $width, $height);

            if ($this->publicFileExists($avif_rel)) {
                return $this->assetVersioned($avif_rel);
            }
        }

        // 2) If browser supports WEBP - try to return WEBP (if exists)
        if ($this->clientSupports('image/webp')) {
            $webp_rel = $this->webRelativePath('webp', $path, $width, $height);

            if ($this->publicFileExists($webp_rel)) {
                return $this->assetVersioned($webp_rel);
            }
        }

        // 3) No web versions available (or not supported) - create/check prototype and queue conversion task
        $prototype_rel = $this->prototypeRelativePath($path, $width, $height);

        // If prototype not created yet - create it
        if (!$this->publicFileExists($prototype_rel)) {
            $this->createPrototype($path, $prototype_rel, $width, $height, $is_square, $bg_color);
        }

        // Queue conversion job (idempotent inside job)
        ConvertImagePrototypeJob::dispatch(
            original_relative_path : $path,
            prototype_relative_path: $prototype_rel,
            width                  : $width,
            height                 : $height,
        )->onQueue('images');

        // 4) Return prototype
        return $this->assetVersioned($prototype_rel);
    }

    private function calculateAspectionSizes(int $original_width, int $original_height, int &$target_width, int &$target_height): void
    {
        if ($original_width == $original_height) {
            $target_width  = $original_width;
            $target_height = $original_height;

            return;
        }

        if ($original_width > $original_height) {
            $k             = min($original_width, $target_width) / max($original_width, $target_width);
            $target_height = (int)round($original_height * $k);
        } else {
            $k            = min($original_height, $target_height) / max($original_height, $target_height);
            $target_width = (int)round($original_width * $k);
        }
    }

    private function validateArgs(string &$path, int $width, int $height): void
    {
        if ($width < 1 || $height < 1) {
            throw new InvalidArgumentException('Width/height must be >= 1!');
        }

        // Basic protection against path traversal
        if (Str::contains($path, ['../', '..\\'])) {
            throw new InvalidArgumentException('Invalid image path!');
        }

        $this->checkSourceImage($path);
    }

    private function checkSourceImage(string &$path): void
    {
        if (Storage::fileExists($path) === false) {
            $path = (string)data_get(
                get_app_settings(),
                'system_settings.images.default_no_image',
                (string)config('app.images.default_no_image', 'images/no-image.png'),
            );
        }
    }

    private function clientSupports(string $mime): bool
    {
        $supported_formats = $this->request->header('X-Supported-Image-Formats', '');
        $formats_array     = explode(',', $supported_formats);
        $accept            = (string)$this->request->header('Accept', '');

        return Str::contains($accept, $mime) || Arr::some($formats_array, function (string $format) use ($mime) {
                return Str::contains($mime, $format);
            });
    }

    private function assetVersioned(string $public_relative): string
    {
        $v   = (string)config('app.images.image_version');
        $url = asset("storage/$public_relative");

        // Add version to query string
        $sep = Str::contains($url, '?') ? '&' : '?';

        return $url . $sep . 'v=' . rawurlencode($v);
    }

    private function publicFileExists(string $public_relative): bool
    {
        return Storage::fileExists($public_relative);
    }

    private function ensureDirFor(string $public_relative_file): void
    {
        $dir = dirname($public_relative_file);

        if (!Storage::directoryExists($dir)) {
            Storage::makeDirectory($dir);
        }
    }

    private function splitPath(string $path): array
    {
        // "images/products/2025/12/ABC.jpg" -> ["images/products/2025/12", "ABC", "jpg"]
        $dir  = Str::after(Str::trim(dirname($path), '.'), 'images/');
        $name = pathinfo($path, PATHINFO_FILENAME);
        $ext  = Str::lower(pathinfo($path, PATHINFO_EXTENSION));

        if ($dir == 'images') {
            $dir = '';
        }

        return [$dir === '.' ? '' : $dir, $name, $ext];
    }

    private function prototypeRelativePath(string $original_path, int $w, int $h): string
    {
        [$dir, $name, $ext] = $this->splitPath($original_path);

        $file = sprintf('%s_%d_%d.%s', $name, $w, $h, $ext);

        if (empty($dir)) {
            return Str::trim("images/cache/prototype/$file", '/');
        }

        return Str::trim("images/cache/prototype/$dir/$file", '/');
    }

    private function webRelativePath(string $format, string $original_path, int $w, int $h): string
    {
        [$dir, $name] = $this->splitPath($original_path);
        $file = sprintf('%s_%d_%d.%s', $name, $w, $h, $format);

        if (empty($dir)) {
            return Str::trim("images/cache/$format/$file", '/');
        }

        return Str::trim("images/cache/$format/$dir/$file", '/');
    }

    /**
     * Create prototype of original format with required dimensions.
     * Implementation uses Intervention Image.
     *
     * @param string $bg_color HEX or transparent color
     */
    private function createPrototype(string $original_rel, string $prototype_rel, int $w, int $h, bool $is_square, string $bg_color = '000000'): void
    {
        $this->ensureDirFor($prototype_rel);

        $src = Storage::path($original_rel);
        $dst = Storage::path($prototype_rel);

        try {
            // Requires: intervention/image v3 (Laravel-friendly)
            $image_manager = new ImageManager(new Driver());

            $image_obj = $image_manager->decodePath($src);

            if ($is_square === true) {
                // Scale image to fit into target dimensions while preserving aspect ratio
                // This prevents upscaling and cropping of content
                $image_obj->scaleDown($w, $h);

                // Get actual dimensions after scaling
                $actual_width  = $image_obj->width();
                $actual_height = $image_obj->height();

                // If image is smaller than target size, we need to add padding
                if ($actual_width < $w || $actual_height < $h) {
                    // Place image on canvas with transparent/white background
                    $image_obj->resizeCanvas($w, $h, $bg_color);
                }

                $image_obj->save($dst, (int)data_get(get_app_settings(), 'system_settings.images.prototype_quality', (int)config('app.images.prototype_quality', 100)));

                return;
            }

            // Non-square: use cover to fill the dimensions
            $image_obj
                ->cover($w, $h)
                ->save($dst, (int)data_get(get_app_settings(), 'system_settings.images.prototype_quality', (int)config('app.images.prototype_quality', 100)));
        } catch (Exception $e) {
            Log::channel('images')->error($e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'code' => $e->getCode(),
            ]);
        }
    }
}
