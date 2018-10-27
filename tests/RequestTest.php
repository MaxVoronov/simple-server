<?php declare(strict_types=1);

namespace App\Tests;

use App\Exception\InvalidHttpRequestException;
use App\Request;
use App\Stream;
use App\Uri;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    /** @var Request */
    protected $request;

    public function setUp(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'req_');
        file_put_contents(
            $file,
            "GET /test HTTP/1.0\r\n" .
            "Host: localhost:8080\r\n" .
            "Accept-Encoding: gzip, deflate, br\r\n" .
            "Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7,he;q=0.6\r\n" .
            "Connection: close\r\n"
        );

        $stream = new Stream($file, 'rw');
        $this->request = Request::fromStream($stream);
    }

    public function testThrowExceptionOnEmptyHttpRequest(): void
    {
        $this->expectException(InvalidHttpRequestException::class);
        $stream = new Stream('php://memory', 'rw');
        Request::fromStream($stream);
    }

    public function testReadAndUpdateProtocolVersion(): void
    {
        $protocolVersion = '1.1';
        $updatedRequest = $this->request->withProtocolVersion($protocolVersion);
        $this->assertEquals('1.0', $this->request->getProtocolVersion());
        $this->assertEquals($protocolVersion, $updatedRequest->getProtocolVersion());
        $this->assertNotSame($this->request, $updatedRequest);
    }

    public function testReadAndUpdateUri(): void
    {
        $uri = new Uri('');
        $updatedRequest = $this->request->withUri($uri);
        $this->assertEquals('localhost', $this->request->getUri()->getHost());
        $this->assertEquals(8080, $this->request->getUri()->getPort());
        $this->assertSame($uri, $updatedRequest->getUri());
    }

    public function testReadAndUpdateBody(): void
    {
        $stream = new Stream('php://memory');
        $updatedRequest = $this->request->withBody($stream);
        $this->assertEquals($stream, $updatedRequest->getBody());
        $this->assertNotEquals($stream, $this->request->getBody());
    }

    public function testCorrectHeadersParsing(): void
    {
        $headers = $this->request->getHeaders();
        $this->assertCount(4, $headers);
        $this->assertEquals('gzip, deflate, br', $this->request->getHeaderLine('Accept-Encoding'));

        $updatedRequest = $this->request->withAddedHeader('Cache-Control', 'no-cache');
        $this->assertEquals('no-cache', $updatedRequest->getHeaderLine('Cache-Control'));
        $this->assertNotEquals('no-cache', $this->request->getHeaderLine('Cache-Control'));
    }
}
