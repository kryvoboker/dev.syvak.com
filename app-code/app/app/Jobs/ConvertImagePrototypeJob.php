<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AvifEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Throwable;

class ConvertImagePrototypeJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The time (seconds) during which a job is considered unique.
     * Adjust this to suit your needs (e.g., 10–30 minutes).
     */
    public int $uniqueFor = 900;

    public function __construct(
        public readonly string $original_relative_path,
        public readonly string $prototype_relative_path,
        public readonly int $width,
        public readonly int $height,
    ) {}

    /**
     * A unique task key.
     * The prototype + dimensions are sufficient (since they determine the output files).
     */
    public function uniqueId(): string
    {
        return sha1(
            $this->prototype_relative_path . '|' .
            $this->width . 'x' . $this->height,
        );
    }

    public function handle(): void
    {
        if (! Storage::fileExists($this->prototype_relative_path)) {
            return;
        }

        // Convert to webp and avif (if available in environment)
        $this->convertTo('webp', Storage::path($this->prototype_relative_path));
        $this->convertTo('avif', Storage::path($this->prototype_relative_path));
    }

    private function convertTo(string $format, string $prototype_abs): void
    {
        $format = Str::lower($format);

        if (! in_array($format, ['webp', 'avif'], true)) {
            return;
        }

        $target_abs = $this->buildTargetPath($format);

        // Idempotency: if file already exists - skip conversion
        if (Storage::fileExists($target_abs)) {
            return;
        }

        $this->ensureDirectoryExists($target_abs);
        $this->performConversion($format, $prototype_abs, $target_abs);
    }

    private function buildTargetPath(string $format): string
    {
        // Target path: cache/(webp|avif)/[original path] + name_w_h.format
        $original_rel = Str::ltrim($this->original_relative_path, '/');
        $dir          = Str::after(
            Str::trim(dirname($original_rel), '.'),
            'images/',
        );

        if ($dir == 'images' || $dir == '.') {
            $dir = '';
        }

        $name = pathinfo($original_rel, PATHINFO_FILENAME);
        $file = sprintf('%s_%d_%d.%s', $name, $this->width, $this->height, $format);

        return Str::trim("images/cache/$format/$dir/$file", '/');
    }

    private function ensureDirectoryExists(string $target_abs): void
    {
        $target_dir = dirname($target_abs);

        if (! Storage::directoryExists($target_dir)) {
            Storage::makeDirectory($target_dir);
        }
    }

    private function performConversion(string $format, string $prototype_abs, string $target_abs): void
    {
        try {
            $manager      = new ImageManager(new Driver());
            $img          = $manager->decodePath($prototype_abs);
            $target_abs   = Storage::path($target_abs);
            $webp_quality = max(
                1,
                (int) data_get(
                    get_app_settings(),
                    'system_settings.images.webp_quality',
                    (int) config('app.images.webp_quality', 80),
                ),
            );
            $avif_quality = max(
                1,
                (int) data_get(
                    get_app_settings(),
                    'system_settings.images.avif_quality',
                    (int) config('app.images.avif_quality', 50),
                ),
            );

            if ($format === 'webp') {
                $img->encode(new WebpEncoder(quality: $webp_quality))->save($target_abs);

                return;
            }

            // AVIF: support depends on driver/build (GD/Imagick + libavif)
            // If not supported - exit without error
            $img->encode(new AvifEncoder(quality: $avif_quality))->save($target_abs);
        } catch (Throwable $e) {
            Log::channel('stack')->warning('AVIF conversion not supported on this server.', [
                'error' => $e->getMessage(),
                'file'  => $prototype_abs,
            ]);
        }
    }
}
