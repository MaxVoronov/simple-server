<?php declare(strict_types=1);

namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface
{
    use MessageTrait;

    public const STATUS_OK = 200;
    public const STATUS_MOVED_PERMANENTLY = 301;
    public const STATUS_NOT_FOUND = 404;
    public const STATUS_I_AM_A_TEAPOT = 418;
    public const STATUS_INTERNAL_SERVER_ERROR = 500;

    public static $statusTexts = [
        self::STATUS_OK => 'OK',
        self::STATUS_MOVED_PERMANENTLY => 'Moved Permanently',
        self::STATUS_NOT_FOUND => 'Not Found',
        self::STATUS_I_AM_A_TEAPOT => 'I’m a teapot',
        self::STATUS_INTERNAL_SERVER_ERROR => 'Internal Server Error',
    ];

    /** @var int */
    protected $statusCode;

    /** @var string */
    protected $reasonPhrase;

    /** @var bool */
    protected $isHeadersSent = false;

    /**
     * Response constructor
     * @param StreamInterface $body
     * @param int $statusCode
     * @param array $headers
     */
    public function __construct(StreamInterface $body, int $statusCode = self::STATUS_OK, array $headers = [])
    {
        $this->body = $body;
        $this->headers = $headers;
        $this->statusCode = $statusCode;
        $this->reasonPhrase = '';

        if (\array_key_exists($statusCode, self::$statusTexts)) {
            $this->reasonPhrase = self::$statusTexts[$statusCode];
        }
    }

    /**
     * Init instance of response
     * @param StreamInterface $body
     * @param int $statusCode
     * @param array $headers
     * @return Response
     */
    public static function init(StreamInterface $body, int $statusCode = self::STATUS_OK, array $headers = []): self
    {
        return new static($body, $statusCode, $headers);
    }

    /**
     * @inheritdoc
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @inheritdoc
     */
    public function withStatus($code, $reasonPhrase = ''): self
    {
        $response = clone $this;
        $response->statusCode = $code;

        if ($reasonPhrase === '' && \array_key_exists($code, self::$statusTexts)) {
            $reasonPhrase = self::$statusTexts[$code];
        }
        $response->reasonPhrase = $reasonPhrase;

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * Prepare and send headers to stream
     * @return Response
     */
    public function sendHeaders(): self
    {
        if ($this->isHeadersSent) {
            return $this;
        }

        $this->getBody()->write(sprintf(
            "HTTP/%s %s %s\r\n",
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        ));
        foreach ($this->headers as $name => $value) {
            $this->getBody()->write(sprintf("%s: %s\r\n", $name, $value));
        }
        $this->getBody()->write("\r\n");
        $this->isHeadersSent = true;

        return $this;
    }
}
