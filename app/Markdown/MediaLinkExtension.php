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

        $host = $parts['host'] ?? null;

        return $host === null || strcasecmp($host, (string) parse_url(url('/'), PHP_URL_HOST)) === 0;
    }
}
