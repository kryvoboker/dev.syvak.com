<?php

declare(strict_types=1);

namespace App\Supports\Services\Images;

use App\Jobs\ConvertImagePrototypeJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use RuntimeException;

final readonly class ImageUrlBuilderService
{
    public function __construct(
        private Request $request,
    ) {}

    /**
     * Generate URL for image with specified dimensions.
     *
     * @param string   $path   Original path (relative to public), e.g: "images/products/2025/12/ABC123.jpg"
     * @param int      $width  Required width
     * @param int|null $height Required height (defaults to width)
     */
    public function url(string $path, int $width, ?int $height = null): string
    {
        $height ??= $width;

        $path = Str::ltrim($path, '/');
        $this->validateArgs($path, $width, $height);

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
            $this->createPrototype($path, $prototype_rel, $width, $height);
        }

        // Queue conversion job (idempotent inside job)
        ConvertImagePrototypeJob::dispatch(
            original_relative_path : $path,
            prototype_relative_path: $prototype_rel,
            width                : $width,
            height               : $height,
        )->onQueue('images');

        // 4) Return prototype
        return $this->assetVersioned($prototype_rel);
    }

    /**
     * @param string $path
     * @param int    $width
     * @param int    $height
     *
     * @return void
     */
    private function validateArgs(string $path, int $width, int $height): void
    {
        if ($width < 1 || $height < 1) {
            throw new InvalidArgumentException('Width/height must be >= 1.');
        }

        // Basic protection against path traversal
        if (Str::contains($path, ['../', '..\\'])) {
            throw new InvalidArgumentException('Invalid image path.');
        }

        // Original file must exist
        $abs = Storage::path($path);

        if (!Storage::fileExists($abs)) {
            throw new RuntimeException("Original image not found: $path");
        }
    }

    /**
     * @param string $mime
     *
     * @return bool
     */
    private function clientSupports(string $mime): bool
    {
        $accept = (string)$this->request->header('Accept', '');

        return Str::contains($accept, $mime);
    }

    /**
     * @param string $public_relative
     *
     * @return string
     */
    private function assetVersioned(string $public_relative): string
    {
        $v   = (string)config('app.images.image_version');
        $url = asset($public_relative);

        // Add version to query string
        $sep = Str::contains($url, '?') ? '&' : '?';

        return $url . $sep . 'v=' . rawurlencode($v);
    }

    /**
     * @param string $public_relative
     *
     * @return bool
     */
    private function publicFileExists(string $public_relative): bool
    {
        return Storage::fileExists($public_relative);
    }

    /**
     * @param string $public_relative_file
     *
     * @return void
     */
    private function ensureDirFor(string $public_relative_file): void
    {
        $dir = dirname($public_relative_file);

        if (!Storage::directoryExists($dir)) {
            Storage::makeDirectory($dir);
        }
    }

    /**
     * @param string $path
     *
     * @return array
     */
    private function splitPath(string $path): array
    {
        // "images/products/2025/12/ABC.jpg" -> ["images/products/2025/12", "ABC", "jpg"]
        $dir  = Str::trim(dirname($path), '.');
        $name = pathinfo($path, PATHINFO_FILENAME);
        $ext  = Str::lower(pathinfo($path, PATHINFO_EXTENSION));

        return [$dir === '.' ? '' : $dir, $name, $ext];
    }

    /**
     * @param string $original_path
     * @param int    $w
     * @param int    $h
     *
     * @return string
     */
    private function prototypeRelativePath(string $original_path, int $w, int $h): string
    {
        [$dir, $name, $ext] = $this->splitPath($original_path);

        $file = sprintf('%s_%d_%d.%s', $name, $w, $h, $ext);

        return Str::trim("cache/prototype/$dir/$file", '/');
    }

    /**
     * @param string $format
     * @param string $original_path
     * @param int    $w
     * @param int    $h
     *
     * @return string
     */
    private function webRelativePath(string $format, string $original_path, int $w, int $h): string
    {
        [$dir, $name] = $this->splitPath($original_path);
        $file = sprintf('%s_%d_%d.%s', $name, $w, $h, $format);

        return Str::trim("cache/$format/$dir/$file", '/');
    }

    /**
     * Create prototype of original format with required dimensions.
     * Implementation uses Intervention Image.
     *
     * @param string $original_rel
     * @param string $prototype_rel
     * @param int    $w
     * @param int    $h
     *
     * @return void
     */
    private function createPrototype(string $original_rel, string $prototype_rel, int $w, int $h): void
    {
        $this->ensureDirFor($prototype_rel);

        $src = Storage::path($original_rel);
        $dst = Storage::path($prototype_rel);

        // Requires: intervention/image v3 (Laravel-friendly)
        $manager = new ImageManager(new Driver());

        $image = $manager->read($src);

        // Prototype: fit to dimensions (center), no upscaling can be enabled if needed
        $image = $image->cover($w, $h);

        // Save in original format (extension already in filename)
        $image->save($dst);
    }
}
