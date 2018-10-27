<?php declare(strict_types=1);

namespace App;

use App\Exception\InvalidHttpMethodException;
use App\Exception\InvalidHttpRequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request implements RequestInterface
{
    use MessageTrait;

    public const METHOD_CONNECT = 'CONNECT';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_GET = 'GET';
    public const METHOD_HEAD = 'HEAD';
    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_TRACE = 'TRACE';

    protected static $validMethods = [
        self::METHOD_CONNECT,
        self::METHOD_DELETE,
        self::METHOD_GET,
        self::METHOD_HEAD,
        self::METHOD_OPTIONS,
        self::METHOD_PATCH,
        self::METHOD_POST,
        self::METHOD_PUT,
        self::METHOD_TRACE,
    ];

    /** @var Uri */
    protected $uri;

    /** @var string */
    protected $method;

    /** @var string */
    protected $requestTarget;

    /**
     * Request constructor
     * @param StreamInterface $body
     * @param Uri $uri
     * @param string $method
     * @param array $headers
     */
    public function __construct(StreamInterface $body, Uri $uri, string $method = self::METHOD_GET, array $headers = [])
    {
        $this->uri = $uri;
        $this->body = $body;
        $this->headers = $headers;

        $method = strtoupper($method);
        $this->validateMethod($method);
        $this->method = $method;
    }

    /**
     * Create request instance from stream
     * @param StreamInterface $stream
     * @return Request
     */
    public static function fromStream(StreamInterface $stream): self
    {
        $rawQueryData = trim($stream->read(4096));
        if (empty($rawQueryData)) {
            throw new InvalidHttpRequestException('Http request can not be empty');
        }

        return self::parseRawQuery($stream, $rawQueryData);
    }

    /**
     * @inheritdoc
     */
    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        if (!$this->uri) {
            return '/';
        }

        $target = $this->uri->getPath();
        if ($this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        if (empty($target)) {
            $target = '/';
        }

        return $target;
    }

    /**
     * @inheritdoc
     */
    public function withRequestTarget($requestTarget): self
    {
        $request = clone $this;
        $request->requestTarget = $requestTarget;

        return $request;
    }

    /**
     * @inheritdoc
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @inheritdoc
     */
    public function withMethod($method): self
    {
        $message = clone $this;
        $message->method = $method;

        return $message;
    }

    /**
     * @inheritdoc
     */
    public function getUri(): Uri
    {
        return $this->uri;
    }

    /**
     * @inheritdoc
     */
    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        $request = clone $this;
        $request->uri = $uri;

        if ($preserveHost) {
            return $request;
        }

        if (!$uri->getHost()) {
            return $request;
        }

        $host = $uri->getHost();
        if ($uri->getPort()) {
            $host .= ':' . $uri->getPort();
        }
        $request->headers['Host'] = $host;

        return $request;
    }

    /**
     * Parse raw HTTP query
     * @param StreamInterface $stream
     * @param string $rawQuery
     * @return Request
     */
    protected static function parseRawQuery(StreamInterface $stream, string $rawQuery): Request
    {
        $headerLines = explode("\n", trim($rawQuery));
        list($method, $path, $protocolVersion) = explode(' ', array_shift($headerLines));
        $protocolVersion = str_replace('HTTP/', '', trim($protocolVersion));

        $request = new static($stream, new Uri($path));
        $request = $request->withMethod($method)
            ->withProtocolVersion($protocolVersion);

        foreach ($headerLines as $header) {
            list($name, $value) = explode(':', trim($header), 2);
            $request = $request->withAddedHeader(trim($name), trim($value));
        }

        if ($hostHeader = $request->getHeaderLine('Host')) {
            list($host, $port) = explode(':', $hostHeader);
            $uri = $request->getUri()
                ->withHost($host)
                ->withPort((int)$port);
            $request = $request->withUri($uri);
        }

        return $request;
    }

    /**
     * Validate HTTP method
     * @param string $method
     * @return Request
     */
    protected function validateMethod(string $method): self
    {
        if (!\in_array($method, self::$validMethods, true)) {
            throw new InvalidHttpMethodException('Invalid HTTP method');
        }

        return $this;
    }
}
