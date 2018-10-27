<?php declare(strict_types=1);

namespace App;

use Psr\Http\Message\StreamInterface;

trait MessageTrait
{
    /** @var array */
    protected $headers;

    /** @var StreamInterface */
    protected $body;

    /** @var string */
    protected $protocolVersion = '1.0';

    /**
     * @inheritdoc
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * @inheritdoc
     */
    public function withProtocolVersion($version): self
    {
        $message = clone $this;
        $message->protocolVersion = $version;

        return $message;
    }

    /**
     * @inheritdoc
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @inheritdoc
     */
    public function hasHeader($name): bool
    {
        return \array_key_exists($name, $this->headers);
    }

    /**
     * @inheritdoc
     */
    public function getHeader($name): array
    {
        if (!$this->hasHeader($name)) {
            return [];
        }

        $value = $this->headers[$name];
        $value = \is_array($value) ? $value : [$value];

        return $value;
    }

    /**
     * @inheritdoc
     */
    public function getHeaderLine($name): string
    {
        if (!$this->hasHeader($name)) {
            return '';
        }

        return implode(',', $this->getHeader($name));
    }

    /**
     * @inheritdoc
     */
    public function withHeader($name, $value): self
    {
        $message = clone $this;
        $value = \is_array($value) ? $value : [$value];
        $message->headers[$name] = $value;

        return $message;
    }

    /**
     * @inheritdoc
     */
    public function withAddedHeader($name, $value): self
    {
        $message = clone $this;
        $value = \is_array($value) ? $value : [$value];

        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }

        $message->headers[] = array_merge($this->headers[$name], $value);

        return $message;
    }

    /**
     * @inheritdoc
     */
    public function withoutHeader($name): self
    {
        $message = clone $this;
        if (!$this->hasHeader($name)) {
            return $message;
        }

        unset($message->headers[$name]);

        return $message;
    }

    /**
     * @inheritdoc
     */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * @inheritdoc
     */
    public function withBody(StreamInterface $body): self
    {
        $message = clone $this;
        $message->body = $body;

        return $message;
    }
}
