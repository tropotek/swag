<?php

namespace App\Models;

use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function renderedBody(): string
    {
        return Str::markdown($this->body_markdown, self::MARKDOWN_OPTIONS);
    }
}
