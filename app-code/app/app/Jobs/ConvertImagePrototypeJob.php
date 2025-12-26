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
use Intervention\Image\ImageManager;
use Throwable;

class ConvertImagePrototypeJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The time (seconds) during which a job is considered unique.
     * Adjust this to suit your needs (e.g., 10–30 minutes).
     */
    public int $uniqueFor = 900;

    public function __construct(
        public readonly string $original_relative_path,
        public readonly string $prototype_relative_path,
        public readonly int    $width,
        public readonly int    $height,
    ) {}

    /**
     * A unique task key.
     * The prototype + dimensions are sufficient (since they determine the output files).
     */
    public function uniqueId(): string
    {
        return sha1(
            $this->prototype_relative_path . '|' .
            $this->width . 'x' . $this->height
        );
    }

    /**
     * @return void
     */
    public function handle(): void
    {
        if (!Storage::fileExists($this->prototype_relative_path)) {
            return;
        }

        // Convert to webp and avif (if available in environment)
        $this->convertTo('webp', Storage::path($this->prototype_relative_path));
        $this->convertTo('avif', Storage::path($this->prototype_relative_path));
    }

    /**
     * @param string $format
     * @param string $prototype_abs
     *
     * @return void
     */
    private function convertTo(string $format, string $prototype_abs): void
    {
        $format = Str::lower($format);

        if (!in_array($format, ['webp', 'avif'], true)) {
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

    /**
     * @param string $format
     *
     * @return string
     */
    private function buildTargetPath(string $format): string
    {
        // Target path: cache/(webp|avif)/[original path] + name_w_h.format
        $original_rel = Str::ltrim($this->original_relative_path, '/');
        $dir          = Str::after(Str::trim(dirname($original_rel), '.'), 'images/');

        if ($dir == 'images' || $dir == '.') {
            $dir = '';
        }

        $name = pathinfo($original_rel, PATHINFO_FILENAME);
        $file = sprintf('%s_%d_%d.%s', $name, $this->width, $this->height, $format);

        return Str::trim("images/cache/$format/$dir/$file", '/');
    }

    /**
     * @param string $target_abs
     *
     * @return void
     */
    private function ensureDirectoryExists(string $target_abs): void
    {
        $target_dir = dirname($target_abs);

        if (!Storage::directoryExists($target_dir)) {
            Storage::makeDirectory($target_dir);
        }
    }

    /**
     * @param string $format
     * @param string $prototype_abs
     * @param string $target_abs
     *
     * @return void
     */
    private function performConversion(string $format, string $prototype_abs, string $target_abs): void
    {
        $manager    = new ImageManager(new Driver());
        $img        = $manager->read($prototype_abs);
        $target_abs = Storage::path($target_abs);

        if ($format === 'webp') {
            $img->toWebp(quality: (int)config('app.images.webp_quality'))->save($target_abs);

            return;
        }

        // AVIF: support depends on driver/build (GD/Imagick + libavif)
        // If not supported - exit without error
        try {
            $img->toAvif(quality: (int)config('app.images.avif_quality'))->save($target_abs);
        } catch (Throwable $e) {
            Log::channel('stack')->warning('AVIF conversion not supported on this server.', [
                'error' => $e->getMessage(),
                'file'  => $prototype_abs,
            ]);
        }
    }
}
