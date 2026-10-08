<?php

declare(strict_types=1);

namespace UEBERBIT\Shorturls\Backend;

use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Core\Imaging\Icon;

/**
 * Doc header button showing the full short URL together with a copy-to-clipboard
 * button, styled like the read-only short_url field of EXT:redirects.
 */
final class ShortUrlCopyButton implements ButtonInterface
{
    private string $url = '';
    private string $label = '';
    private string $title = '';
    private ?Icon $copyIcon = null;

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Text shown in the input group; falls back to the URL if empty.
     */
    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setCopyIcon(?Icon $copyIcon): self
    {
        $this->copyIcon = $copyIcon;
        return $this;
    }

    public function isValid(): bool
    {
        return $this->url !== '' && $this->copyIcon !== null;
    }

    public function getType(): string
    {
        return self::class;
    }

    public function render(): string
    {
        $url = htmlspecialchars($this->url);
        $label = htmlspecialchars($this->label !== '' ? $this->label : $this->url);
        $title = htmlspecialchars($this->title);

        $html = [];
        $html[] = '<div class="input-group input-group-sm w-auto flex-nowrap">';
        $html[] = '    <span class="input-group-text">' . $label . '</span>';
        $html[] = '    <typo3-copy-to-clipboard text="' . $url . '" class="btn btn-default" title="' . $title . '">';
        $html[] = '        ' . $this->copyIcon->render();
        $html[] = '    </typo3-copy-to-clipboard>';
        $html[] = '</div>';

        return implode("\n", $html);
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
