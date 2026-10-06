<?php

namespace App\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\ExtensionInterface;

/**
 * Links to this site's uploaded files open in a new tab.
 *
 * Must be registered AFTER AttributesExtension: that extension's listener replaces a node's
 * whole `attributes` array and filters it against the allow list, so anything set here before
 * it runs would be discarded. See the ordering test in MediaLinksTest.
 */
class MediaLinkExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, function (DocumentParsedEvent $event): void {
            foreach ($event->getDocument()->iterator() as $node) {
                if ($node instanceof Link && $this->isMediaUrl($node->getUrl())) {
                    $node->data->set('attributes/target', '_blank');
                    $node->data->set('attributes/rel', 'noopener');
                }
            }
        });
    }

    private function isMediaUrl(string $url): bool
    {
        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['path']) || ! str_starts_with($parts['path'], '/media/')) {
            return false;
        }

        if (! isset($parts['host'])) {
            return true;
        }

        // An absolute URL is baked into the page body when the page is created, so it can carry
        // a different host from the one the reader is on. The configured app URL counts as ours
        // as well as the current request's root, otherwise the owner reaching their own site by
        // its other hostname would lose the new tab and navigate away from the page. Compared
        // with the port, so a different service on the same host is not ours.
        $origin = $this->origin($parts);

        return in_array($origin, [
            $this->origin(parse_url((string) config('app.url')) ?: []),
            $this->origin(parse_url(url('/')) ?: []),
        ], true);
    }

    /** @param  array<string, mixed>  $parts */
    private function origin(array $parts): string
    {
        $host = strtolower((string) ($parts['host'] ?? ''));
        $scheme = strtolower((string) ($parts['scheme'] ?? 'http'));
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);

        return $host.':'.$port;
    }
}
