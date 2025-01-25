<?php

namespace mauricerenck\IndieConnector;

use Kirby\Toolkit\Str;

class ExternalPostSender extends Sender
{
    public function __construct(
        public ?callable $pageText = null,
        public ?callable $pageImage = null,
        public ?callable $pageLink = null,
        public ?array $textfields = null,
        public ?string $imagefield = null,
        public ?string $prefereLanguage = null,
        public ?bool $usePermalinkUrl = null,
        private ?int $maxPostLength = null,
        public ?UrlChecks $urlChecks = null,
        public ?PageChecks $pageChecks = null
    ) {
        parent::__construct();

        $this->pageText = $pageText ?? option('mauricerenck.indieConnector.post.pageText', null);
        $this->pageImage = $pageImage ?? option('mauricerenck.indieConnector.post.pageImage', null);
        $this->$pageLink = $pageLink ?? option('mauricerenck.indieConnector.post.$pageLink', null);

        $this->textfields = $textfields ?? option('mauricerenck.indieConnector.post.textfields', ['description']);
        $this->imagefield = $imagefield ?? option('mauricerenck.indieConnector.post.imagefield', false);
        $this->prefereLanguage = $prefereLanguage ?? option('mauricerenck.indieConnector.post.prefereLanguage', false);
        $this->usePermalinkUrl = $usePermalinkUrl ?? option('mauricerenck.indieConnector.post.usePermalinkUrl', false);
        $this->maxPostLength = $maxPostLength ?? 300;

        $this->urlChecks = $urlChecks ?? new UrlChecks();
        $this->pageChecks = $pageChecks ?? new PageChecks();

        // backwards compatibility
        $singleTextfield = option('mauricerenck.indieConnector.post.textfield', false);
        if (!$textfields && $singleTextfield) {
            $this->textfields = [$singleTextfield];
        }

        if (!$textfields && $singleTextfield) {
            $this->textfields = [$singleTextfield];
        }

        if (!$maxPostLength && option('mauricerenck.indieConnector.mastodon-text-length', false)) {
            $this->maxPostLength = option('mauricerenck.indieConnector.mastodon-text-length');
        }
    }

    public function getTextFieldContent($page, $trimTextPosition)
    {
        $pageOfLanguage = !$this->prefereLanguage ? null : $page->translation($this->prefereLanguage);
        $content = !is_null($pageOfLanguage) ? $pageOfLanguage->content() : $page->content()->toArray();

        if (is_callable($this->$pageText)) {
            return Str::short($this->$pageText($page), $trimTextPosition);
        }

        if (is_array($this->textfields)) {
            foreach ($this->textfields as $field) {
                if (isset($content[$field]) && !empty($content[$field])) {
                    return Str::short($content[$field], $trimTextPosition);
                }
            }
        }

        $field = $this->textfields;
        if (!is_array($this->textfields) && isset($content[$field]) && !empty($content[$field])) {
            return Str::short($content[$field], $trimTextPosition);
        }

        return Str::short($content['title'], $trimTextPosition);
    }

    public function getPostUrl($page)
    {
        $url = $page->url($this->prefereLanguage);

        if ($this->usePermalinkUrl) {
            $url = $page->permalink();
        }

        return $url;
    }

    public function calculatePostTextLength(string $url)
    {
        $urlLength = Str::length($url);
        return $this->maxPostLength - $urlLength - 2;
    }

    public function updatePosts($url, $statusCode, $page, $target)
    {
        return $this->updateExternalPosts($url, $statusCode, $target, $page);
    }
}
