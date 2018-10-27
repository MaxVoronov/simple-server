<?php declare(strict_types=1);

namespace App\Tests;

use App\Uri;
use PHPUnit\Framework\TestCase;

class UriTest extends TestCase
{
    public function testParsingStringInConstructor(): void
    {
        $uri = new Uri('https://user:pass@local.example.com:8080/path?param=value#hash');

        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('user:pass', $uri->getUserInfo());
        $this->assertEquals('local.example.com', $uri->getHost());
        $this->assertEquals('8080', $uri->getPort());
        $this->assertEquals('user:pass@local.example.com:8080', $uri->getAuthority());
        $this->assertEquals('/path', $uri->getPath());
        $this->assertEquals('param=value', $uri->getQuery());
        $this->assertEquals('hash', $uri->getFragment());
    }

    public function testConversionUriToString(): void
    {
        $url = 'https://user:pass@local.example.com:8080/path?param=value#hash';
        $uri = new Uri($url);

        $this->assertEquals($url, $uri->__toString());
    }
}
