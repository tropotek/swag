<?php

namespace App\Models;

use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['title', 'body_markdown'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    private const MARKDOWN_OPTIONS = [
        'html_input' => 'escape',
        'allow_unsafe_links' => false,
        'max_nesting_level' => 50,
    ];

    public const SORTS = [
        'updated' => 'Last updated',
        'created' => 'Newest created',
        'title' => 'Title A–Z',
    ];

    public static function sortKey(mixed $requested): string
    {
        return is_string($requested) && isset(self::SORTS[$requested]) ? $requested : 'updated';
    }

    #[Scope]
    protected function sorted(Builder $query, string $sort): void
    {
        match ($sort) {
            'created' => $query->latest('created_at'),
            'title' => $query->orderByRaw('lower(title)'),
            default => $query->latest('updated_at'),
        };

        $query->latest('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function renderedBody(): string
    {
        return Str::markdown($this->body_markdown, self::MARKDOWN_OPTIONS);
    }
}
