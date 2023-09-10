<?php

class Email
{
    protected $toAddress;
    protected $fromName;
    protected $fromAddress;
    protected $subject;
    protected $body;
    protected $replyTo;
    protected $headers = [];
    protected $contentType;
    protected $charset = 'UTF-8';
    protected $mimeBoundary;
    protected $multipart = false;
    protected $parts = [];

    public function __construct(string $toAddress, $multipart = false)
    {
        if (!static::validateAddress($toAddress)) {
            throw new InvalidArgumentException('Invalid email address "' . $toAddress . '".');
        }

        $this->toAddress = $toAddress;
        $this->multipart = $multipart;
        if ($multipart) {
            $this->mimeBoundary = '--------' . uniqid('mb', true);
        }

        $this->addHeader('MIME-Version', '1.0');
        $this->addHeader('Date', date('r (T)'));
        $this->addHeader('Content-Transfer-Encoding', '8bit');
    }

    public function subject(string $subject): void
    {
        $subject = mb_encode_mimeheader($subject, 'UTF-8');

        $this->subject = $subject;
    }

    public function from(string $name, string $address): void
    {
        if (!static::validateAddress($address)) {
            throw new InvalidArgumentException('Invalid email address "' . $address . '".');
        }

        $this->fromName = $name;
        $this->fromAddress = $address;
        $this->addHeader('From', $this->fromName . ' <' . $this->fromAddress . '>');
    }

    public function body(string $content, string $contentType = 'text/plain')
    {
        if ($this->multipart) {
            throw new InvalidArgumentException('This is a multipart message. Please use addPart() instead of body().');
        }

        $this->body = $content;
        $this->addHeader('Content-Type', $contentType . ";charset=" . $this->charset);
    }

    /**
     * Assigns a reply to address
     *
     * @param string $address
     * @throws InvalidEmailAddress if the given address is invalid
     */
    public function replyTo(string $address)
    {
        if (!static::validateAddress($address)) {
            throw new InvalidEmailAddress('Invalid email address "' . $address . '".');
        }

        $this->replyTo = $address;
        $this->addHeader('Reply-To', $address);
    }

    public function addPart(string $content, string $contentType = 'text/plain', ?array $partHeaders = null): void
    {
        $part = '';
        if (!$this->multipart) {
            throw new InvalidArgumentException('This is not a multipart message. Please use body() instead of addPart().');
        }

        $this->addHeader('Content-Type',
            'multipart/alternative;boundary=' . $this->mimeBoundary . ';charset=' . $this->charset);

        if (empty($this->parts)) {
            $part .= 'This is a multi-part message in MIME format.';
        }

        $part .= "\r\n\r\n--" . $this->mimeBoundary . "\r\n";
        $part .= "Content-Type: " . $contentType . ";charset=" . $this->charset . "\r\n";

        if ($partHeaders) {
            foreach ($partHeaders as $name => $value) {
                $part .= $name . ': ' . $value . "\r\n";
            }
        }
        $part .= "\r\n" . $content;

        $this->parts[] = $part;
    }

    public function addHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    public function send(): bool
    {

        if ($this->multipart) {
            $body = implode("\r\n", $this->parts);
            $body .= "\r\n\r\n--" . $this->mimeBoundary . '--';
        } else {
            $body = $this->body;
        }

        $headers = '';
        foreach ($this->headers as $name => $value) {
            $headers .= $name . ': ' . $value . "\r\n";
        }

        // Additional sendmail parameters
        $parameters = '-f ' . $this->fromAddress;

        // Send mail
        $mailSent = mail($this->toAddress, $this->subject, $body, $headers, $parameters);

        return $mailSent;
    }

    public function htmlToPlainText(string $content): string
    {
        $content = trim(strip_tags($content));
        $content = str_replace(["\t", "\r"], "", $content);
        $content = explode("\n", $content);

        // Trim each text row
        foreach ($content as &$row) {
            $row = trim($row);
        }

        $content = implode("\r\n", $content);

        return $content;
    }

    public static function validateAddress(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}