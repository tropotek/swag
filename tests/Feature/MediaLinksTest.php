<?php

use App\Models\Page;

function renderBody(string $markdown): string
{
    return Page::factory()->make(['body_markdown' => $markdown])->renderedBody();
}

it('opens an absolute link to this site\'s media in a new tab', function () {
    $html = renderBody('[kit.zip]('.url('/media/abc/kit.zip').')');

    expect($html)->toContain('href="'.url('/media/abc/kit.zip').'"')
        ->and($html)->toContain('target="_blank"')
        ->and($html)->toContain('rel="noopener"');
});

it('opens a relative media link in a new tab', function () {
    expect(renderBody('[kit](/media/abc/kit.zip)'))->toContain('target="_blank"')->toContain('rel="noopener"');
});

it('leaves images as plain images', function () {
    $html = renderBody('![alt]('.url('/media/abc/p.png').')');

    expect($html)->toContain('<img src="'.url('/media/abc/p.png').'"')->not->toContain('target=');
});

it('does not touch ordinary links', function () {
    expect(renderBody('[home](/pages/1) and [out](https://example.com/page)'))->not->toContain('target=');
});

it('does not treat a /media/ path on another host as ours', function () {
    expect(renderBody('[x](https://evil.example/media/abc/x.zip)'))->not->toContain('target=');
});

/**
 * An absolute media URL is baked into the page body when the API creates the page, so it can
 * carry a different host from the one the reader is on. The configured app URL counts as ours
 * too, or the owner opening their own page by the other hostname the app answers on would lose
 * the new tab and navigate away from the page they were reading.
 */
it('treats the configured app url as ours even when the request host differs', function () {
    config(['app.url' => 'https://swag.example']);

    expect(renderBody('[kit](https://swag.example/media/abc/kit.zip)'))->toContain('target="_blank"');
});

// Built from config so it varies only the port: a bare different host would pass either way.
it('does not treat our host on a different port as ours', function () {
    $host = parse_url(config('app.url'), PHP_URL_HOST);

    expect(renderBody('[x](http://'.$host.':9999/media/abc/x.zip)'))->not->toContain('target=');
});

it('does not match look-alike paths', function () {
    expect(renderBody('[x](/mediax/abc) [y](/pages/media/abc)'))->not->toContain('target=');
});

it('still blocks unsafe links and still escapes raw html', function () {
    $html = renderBody("[bad](javascript:alert(1))\n\n<script>alert(1)</script>");

    expect($html)->not->toContain('href="javascript:')->and($html)->toContain('&lt;script&gt;');
});

it('does not let a user add their own target through attribute syntax', function () {
    expect(renderBody('[x](https://example.com){target=_blank}'))->not->toContain('target=');
});

/**
 * Pins extension order. AttributesListener ends with
 * `$entry['node']->data->set('attributes', self::assemble($entry))`, replacing the whole
 * attributes array and filtering it against the `allow` list (`['class']`). Our target and
 * rel survive only because MediaLinkExtension is registered after it and so runs second.
 * Swap the order in renderedBody() and this test goes red.
 */
it('keeps the new tab attributes on a media link that also carries an attribute block', function () {
    $html = renderBody('[kit](/media/abc/kit.zip){.btn}');

    expect($html)->toContain('target="_blank"')
        ->and($html)->toContain('rel="noopener"')
        ->and($html)->toContain('class="btn"');
});
