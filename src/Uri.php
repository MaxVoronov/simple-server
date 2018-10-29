<?php declare(strict_types=1);

namespace App;

use App\Exception\InvalidUrlException;
use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    /** @var string */
    protected $scheme;

    /** @var string */
    protected $user;

    /** @var string */
    protected $password;

    /** @var string */
    protected $host;

    /** @var int|null */
    protected $port;

    /** @var string */
    protected $path;

    /** @var string */
    protected $query;

    /** @var string */
    protected $fragment;

    /**
     * Uri constructor
     * @param string $uri
     */
    public function __construct(string $uri)
    {
        $this->parseUri($uri);
    }

    /**
     * @inheritdoc
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @inheritdoc
     */
    public function getAuthority(): string
    {
        $authority = $this->host;

        $userInfo = $this->getUserInfo();
        if (!empty($userInfo)) {
            $authority = $this->getUserInfo() . '@' . $authority;
        }

        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * @inheritdoc
     */
    public function getUserInfo(): string
    {
        if (!empty($this->user)) {
            return $this->user . (!empty($this->password) ? ':' . $this->password : '');
        }

        return '';
    }

    /**
     * @inheritdoc
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @inheritdoc
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * @inheritdoc
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @inheritdoc
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @inheritdoc
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * @inheritdoc
     */
    public function withScheme($scheme): self
    {
        $uri = clone $this;
        $uri->scheme = $scheme;

        return $uri;
    }

    /**
     * @inheritdoc
     */
    public function withUserInfo($user, $password = null): self
    {
        $uri = clone $this;
        $uri->user = $user;
        $uri->password = $password;

        return $uri;
    }

    /**
     * @inheritdoc
     */
    public function withHost($host): self
    {
        $uri = clone $this;
        $uri->host = $host;

        return $uri;
    }

    /**
     * @inheritdoc
     */
    public function withPort($port): self
    {
        $uri = clone $this;
        $uri->port = $port;

        return $uri;
    }

    /**
     * @inheritdoc
     */
    public function withPath($path): self
    {
        $uri = clone $this;
        $uri->path = $path;

        return $uri;
    }

    /**
     * @inheritdoc
     */
    public function withQuery($query): self
    {
        $uri = clone $this;
        $uri->query = $query;

        return $uri;
    }

    /**
     * @inheritdoc
     */
    public function withFragment($fragment): self
    {
        $uri = clone $this;
        $uri->fragment = $fragment;

        return $uri;
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        $uri = sprintf('%s://%s%s', $this->getScheme(), $this->getAuthority(), $this->getPath());

        $query = $this->getQuery();
        if (!empty($query)) {
            $uri .= '?' . $query;
        }

        $fragment = $this->getFragment();
        if (!empty($fragment)) {
            $uri .= '#' . $fragment;
        }

        return $uri;
    }

    /**
     * Parse query and return array of params
     * @return array
     */
    public function getQueryParams(): array
    {
        parse_str($this->getQuery(), $params);

        return $params;
    }

    /**
     * Parse string and fill Uri fields
     * @param string $url
     * @return Uri
     */
    protected function parseUri(string $url): self
    {
        $parsedUrl = parse_url($url);
        if ($parsedUrl === false) {
            throw new InvalidUrlException('Can not parse URL');
        }

        $this->scheme = $parsedUrl['scheme'] ?? 'http';
        $this->host = $parsedUrl['host'] ?? '';
        $this->port = !empty($parsedUrl['port']) ? ((int)$parsedUrl['port']) : null;
        $this->user = $parsedUrl['user'] ?? '';
        $this->password = $parsedUrl['pass'] ?? '';
        $this->path = $parsedUrl['path'] ?? '';
        $this->query = $parsedUrl['query'] ?? '';
        $this->fragment = $parsedUrl['fragment'] ?? '';

        return $this;
    }
}
