<?php

namespace Spatie\LaravelRay\Payloads;

use Spatie\Ray\Payloads\Payload;
use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\Header\HeaderConsts;
use ZBateson\MailMimeParser\Header\Part\AddressPart;
use ZBateson\MailMimeParser\MailMimeParser;

class LoggedMailPayload extends Payload
{
    /** @var string */
    protected $html = '';

    /** @var array */
    protected $from;

    /** @var string|null */
    protected $subject;

    /** @var array */
    protected $to;

    /** @var array */
    protected $cc;

    /** @var array */
    protected $bcc;

    public static function forLoggedMail(string $loggedMail): self
    {
        $parser = new MailMimeParser();

        $message = $parser->parse($loggedMail, true);

        $content = $message->getContent() ?? $message->getHtmlContent() ?? '';

        return new self(
            $content,
            self::convertHeaderToPersons($message->getHeader(HeaderConsts::FROM)),
            $message->getHeaderValue(HeaderConsts::SUBJECT),
            self::convertHeaderToPersons($message->getHeader(HeaderConsts::TO)),
            self::convertHeaderToPersons($message->getHeader(HeaderConsts::CC)),
            self::convertHeaderToPersons($message->getHeader(HeaderConsts::BCC)),
        );
    }

    public function __construct(
        string $html,
        array $from = [],
        ?string $subject = null,
        array $to = [],
        array $cc = [],
        array $bcc = []
    ) {
        $this->html = $html;
        $this->from = $from;
        $this->subject = $subject;
        $this->to = $to;
        $this->cc = $cc;
        $this->bcc = $bcc;
    }

    public function getType(): string
    {
        return 'mailable';
    }

    public function getContent(): array
    {
        return [
            'html' => $this->sanitizeHtml($this->html),
            'subject' => $this->subject,
            'from' => $this->from,
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
        ];
    }

    protected function sanitizeHtml(string $html): string
    {
        $needle = 'Content-Type: text/html; charset=utf-8 Content-Transfer-Encoding: quoted-printable';

        if (strpos($html, $needle) !== false) {
            $html = substr($html, strpos($html, $needle));
        }

        return $html;
    }

    protected static function convertHeaderToPersons(?AddressHeader $header): array
    {
        if ($header === null) {
            return [];
        }

        return array_map(
            function (AddressPart $address) {
                return [
                    'name' => $address->getName(),
                    'email' => $address->getEmail(),
                ];
            },
            $header->getAddresses()
        );
    }
}
