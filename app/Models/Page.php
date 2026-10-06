<?php

namespace App\Models;

use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use League\CommonMark\Extension\Attributes\AttributesExtension;

#[Fillable(['title', 'body_markdown'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    private const MARKDOWN_OPTIONS = [
        'html_input' => 'escape',
        'allow_unsafe_links' => false,
        'max_nesting_level' => 50,
        'attributes' => ['allow' => ['class']],
    ];

    public const SORTS = [
        'updated' => 'Last updated',
        'created' => 'Newest created',
        'title' => 'Title A–Z',
    ];

    public const PER_PAGE_OPTIONS = [20, 50, 100, 200];

    public const DEFAULT_PER_PAGE = 50;

    public const RELEVANCE = 'relevance';

    public static function sortKey(mixed $requested, bool $searching = false): string
    {
        if (is_string($requested) && (isset(self::SORTS[$requested]) || ($searching && $requested === self::RELEVANCE))) {
            return $requested;
        }

        return $searching ? self::RELEVANCE : 'updated';
    }

    public static function perPage(mixed $requested): int
    {
        $requested = is_string($requested) ? (int) $requested : 0;

        return in_array($requested, self::PER_PAGE_OPTIONS, true) ? $requested : self::DEFAULT_PER_PAGE;
    }

    public static function searchTerm(mixed $requested): string
    {
        $term = is_string($requested) ? Str::limit(trim($requested), 200, '') : '';

        return self::tokens($term) === [] ? '' : $term;
    }

    /** @return list<string> */
    private static function tokens(string $term): array
    {
        return preg_split('/[^\p{L}\p{N}]+/u', $term, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Match every word of the term (prefix match) against title and body.
     * Uses the pages_fts index on SQLite and falls back to LIKE elsewhere.
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $tokens = self::tokens($term);

        if ($tokens === []) {
            return;
        }

        if (DB::getDriverName() === 'sqlite') {
            $match = implode(' ', array_map(fn (string $t) => '"'.$t.'"*', $tokens));

            $query->select('pages.*')
                ->join('pages_fts', 'pages_fts.rowid', '=', 'pages.id')
                ->whereRaw('pages_fts MATCH ?', [$match]);

            return;
        }

        foreach ($tokens as $token) {
            $like = '%'.$token.'%';
            $query->where(fn (Builder $q) => $q->where('pages.title', 'like', $like)->orWhere('pages.body_markdown', 'like', $like));
        }
    }

    #[Scope]
    protected function sorted(Builder $query, string $sort): void
    {
        if ($sort === self::RELEVANCE && DB::getDriverName() === 'sqlite') {
            $query->orderByRaw('bm25(pages_fts, 10.0, 1.0)');
            $sort = 'updated';
        }

        match ($sort) {
            'created' => $query->latest('pages.created_at'),
            'title' => $query->orderByRaw('lower(pages.title)'),
            default => $query->latest('pages.updated_at'),
        };

        $query->latest('pages.id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function renderedBody(): string
    {
        return Str::markdown($this->body_markdown, self::MARKDOWN_OPTIONS, [new AttributesExtension()]);
    }
}
