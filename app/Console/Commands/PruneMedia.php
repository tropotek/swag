<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\Page;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class PruneMedia extends Command
{
    protected $signature = 'swag:prune-media
        {--force : Actually delete. Without it the command only reports.}
        {--hours=24 : Leave anything uploaded within this many hours alone.}';

    protected $description = 'Report or delete uploaded files that no page references';

    public function handle(): int
    {
        $hours = max(0, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours);
        $disk = Storage::disk(Media::DISK);

        $orphans = $this->orphanedMedia($cutoff);
        $strays = $this->strayFiles($disk, $cutoff);

        if ($orphans->isEmpty() && $strays === []) {
            $this->info('Nothing to prune.');

            return self::SUCCESS;
        }

        $bytes = 0;

        foreach ($orphans as $media) {
            $bytes += (int) $media->size;
            $this->line(sprintf('  %s  %s  (%s)', $media->uuid, $media->original_name, $this->size($media->size)));
        }

        foreach ($strays as $path) {
            $bytes += $size = (int) $disk->size($path);
            $this->line(sprintf('  %s  no database row  (%s)', basename($path), $this->size($size)));
        }

        $count = $orphans->count() + count($strays);

        if (! $this->option('force')) {
            $this->newLine();
            $this->info(sprintf('%d unreferenced file%s, %s. Nothing deleted — re-run with --force.',
                $count, $count === 1 ? '' : 's', $this->size($bytes)));

            return self::SUCCESS;
        }

        foreach ($orphans as $media) {
            $disk->delete($media->path());
            $media->delete();
        }

        $disk->delete($strays);

        $this->newLine();
        $this->info(sprintf('Deleted %d file%s, %s reclaimed.', $count, $count === 1 ? '' : 's', $this->size($bytes)));

        return self::SUCCESS;
    }

    /**
     * Media whose uuid appears in no page body. Matched across every user's pages, not just
     * the owner's: a false positive here deletes a file someone is still using.
     *
     * @return \Illuminate\Support\Collection<int, Media>
     */
    private function orphanedMedia(\Illuminate\Support\Carbon $cutoff): \Illuminate\Support\Collection
    {
        return Media::where('created_at', '<', $cutoff)
            ->get()
            ->reject(fn (Media $media) => Page::where('body_markdown', 'like', '%'.$media->uuid.'%')->exists())
            ->values();
    }

    /**
     * Files on the disk with no media row at all — what an upload that died between writing
     * the bytes and recording the row leaves behind.
     *
     * @return list<string>
     */
    private function strayFiles(Filesystem $disk, \Illuminate\Support\Carbon $cutoff): array
    {
        $known = Media::pluck('uuid')->all();

        return collect($disk->files('media'))
            ->reject(fn (string $path) => in_array(basename($path), $known, true))
            ->filter(fn (string $path) => $disk->lastModified($path) < $cutoff->getTimestamp())
            ->values()
            ->all();
    }

    private function size(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 1).' MB'
            : max(1, (int) round($bytes / 1024)).' KB';
    }
}
