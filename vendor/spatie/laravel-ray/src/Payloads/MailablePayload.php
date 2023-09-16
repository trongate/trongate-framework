<?php

namespace Spatie\LaravelRay\Payloads;

use Illuminate\Mail\Mailable;
use Spatie\Ray\Payloads\Payload;
use Throwable;

class MailablePayload extends Payload
{
    /** @var string */
    protected $html = '';

    /** @var \Illuminate\Mail\Mailable|null */
    protected $mailable = null;

    public static function forMailable(Mailable $mailable)
    {
        return new self(self::renderMailable($mailable), $mailable);
    }

    public function __construct(string $html, Mailable $mailable = null)
    {
        $this->html = $html;

        $this->mailable = $mailable;
    }

    public function getType(): string
    {
        return 'mailable';
    }

    public function getContent(): array
    {
        $content = [
            'html' => $this->html,
            'from' => [],
            'to' => [],
            'cc' => [],
            'bcc' => [],
        ];

        if ($this->mailable) {
            $content = array_merge($content, [
                'mailable_class' => get_class($this->mailable),
                'from' => $this->convertToPersons($this->mailable->from),
                'subject' => $this->mailable->subject,
                'to' => $this->convertToPersons($this->mailable->to),
                'cc' => $this->convertToPersons($this->mailable->cc),
                'bcc' => $this->convertToPersons($this->mailable->bcc),
            ]);
        }

        return $content;
    }

    protected static function renderMailable(Mailable $mailable): string
    {
        try {
            return $mailable->render();
        } catch (Throwable $exception) {
            return "Mailable could not be rendered because {$exception->getMessage()}";
        }
    }

    protected function convertToPersons(array $persons): array
    {
        return collect($persons)
            ->map(function (array $person) {
                return [
                    'email' => $person['address'],
                    'name' => $person['name'] ?? '',
                ];
            })
            ->toArray();
    }
}
