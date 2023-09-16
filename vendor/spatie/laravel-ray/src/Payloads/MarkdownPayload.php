<?php

namespace Spatie\LaravelRay\Payloads;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use Spatie\Ray\Payloads\Payload;

class MarkdownPayload extends Payload
{
    /** @var string */
    protected $markdown;

    public function __construct(string $markdown)
    {
        $this->markdown = $markdown;
    }

    public function getType(): string
    {
        return 'custom';
    }

    public function getContent(): array
    {
        return [
            'content' => $this->markdownToHtml($this->markdown),
            'label' => 'Markdown',
        ];
    }

    protected function markdownToHtml(string $markdown): string
    {
        $converter = new GithubFlavoredMarkdownConverter([
            'renderer' => [
                'block_separator' => "<br>\n",
            ],
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        $html = $converter->convertToHtml($markdown);
        $html = $this->processCodeBlocks($html);
        $html = $this->processHeaderTags($html);
        $css = $this->getCustomStyles();

        return trim("{$css}{$html}");
    }

    protected function getCustomStyles(): string
    {
        // render links as underlined
        return '<style>a { text-decoration:underline!important; }</style>';
    }

    protected function processCodeBlocks($html): string
    {
        // format code blocks background color, padding, and display width; the background
        // color changes based on light or dark app theme.
        return str_replace('<pre><code', '<pre class="w-100 bg-gray-200 dark:bg-gray-800 p-5"><code', $html);
    }

    protected function processHeaderTags($html): string
    {
        // render headers with the correct format and size as divs
        $html = str_replace(['<h1>', '<h2>', '<h3>', '<h4>'], [
            '<div class="w-100 block" style="font-size:2.1em!important;">',
            '<div class="w-100 block" style="font-size:1.8em!important;">',
            '<div class="w-100 block" style="font-size:1.5em!important;">',
            '<div class="w-100 block" style="font-size:1.2em!important;">',
        ], $html);

        // replace closing header tags with closing div tags
        return preg_replace('~</h[1-4]>~', '</div>', $html);
    }
}
