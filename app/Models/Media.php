<?php

namespace App\Models;

use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

// user_id is deliberately not fillable: it always comes from the $user->media() relation.
#[Fillable(['uuid', 'original_name', 'mime_type', 'size'])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    public const DISK = 'local';

    protected $table = 'media';

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hasBlockedExtension(string $name): bool
    {
        // Strip every kind of space and invisible formatting character, not just ASCII
        // whitespace: "payload.exe\u{00A0}" must be blocked exactly like "payload.exe ".
        $parts = array_map(
            fn (string $part) => preg_replace('/[\s\p{Z}\p{Cf}]+/u', '', $part) ?? $part,
            explode('.', strtolower(basename(str_replace('\\', '/', $name)))),
        );
        array_shift($parts);

        return array_intersect($parts, config('swag.media.blocked_extensions')) !== [];
    }

    public function path(): string
    {
        return 'media/'.$this->uuid;
    }

    public function isInline(): bool
    {
        return in_array($this->mime_type, config('swag.media.inline_mimes'), true);
    }

    public function isImage(): bool
    {
        return $this->isInline() && str_starts_with($this->mime_type, 'image/');
    }

    /** A URL-safe file name, cosmetic only: lookup is by uuid. */
    public function urlName(): string
    {
        $extension = preg_replace('/[^a-z0-9]/', '', strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION)));
        $stem = Str::slug(pathinfo($this->original_name, PATHINFO_FILENAME));
        $stem = $stem !== '' ? $stem : 'file';

        return $extension !== '' ? $stem.'.'.$extension : $stem;
    }

    public function url(): string
    {
        return route('media.show', ['media' => $this->uuid, 'name' => $this->urlName()]);
    }

    public function markdown(): string
    {
        $label = str_replace(['\\', '[', ']'], ['\\\\', '\\[', '\\]'], $this->original_name);
        $link = '['.$label.']('.$this->url().')';

        return $this->isImage() ? '!'.$link : $link;
    }
}
