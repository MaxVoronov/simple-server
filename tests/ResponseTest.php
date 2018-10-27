<?php declare(strict_types=1);

namespace App\Tests;

use App\Response;
use App\Stream;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testCorrectInitInstances(): void
    {
        $this->assertInstanceOf(Response::class, new Response(new Stream));
        $this->assertInstanceOf(Response::class, Response::init(new Stream));
    }

    public function testReadAndUpdateBody(): void
    {
        $bodyContent = new Stream('php://memory');
        $response = Response::init(new Stream);
        $response = $response->withBody($bodyContent);

        $this->assertEquals($bodyContent, $response->getBody());
    }

    public function testReadAndUpdateStatus(): void
    {
        $response = Response::init(new Stream);
        $response = $response->withStatus(Response::STATUS_I_AM_A_TEAPOT);

        $this->assertEquals(Response::STATUS_I_AM_A_TEAPOT, $response->getStatusCode());
        $this->assertEquals(Response::$statusTexts[Response::STATUS_I_AM_A_TEAPOT], $response->getReasonPhrase());
    }

    public function testReadAndUpdateHeaders(): void
    {
        $defaultHeaders = ['Server' => 'PHPUnit'];
        $response = Response::init(new Stream, Response::STATUS_OK, $defaultHeaders);
        $response = $response->withHeader('Content-Language', 'ru');

        $this->assertTrue($response->hasHeader('Server'));
        $this->assertTrue($response->hasHeader('Content-Language'));
        $this->assertFalse($response->hasHeader('Fake-Header'));
        $this->assertEquals([$defaultHeaders['Server']], $response->getHeader('Server'));
        $this->assertEquals('ru', $response->getHeaderLine('Content-Language'));
    }

    public function testReadAndUpdateProtocolVersion(): void
    {
        $response = Response::init(new Stream);
        $this->assertEquals('1.0', $response->getProtocolVersion());

        $protocolVersion = '1.1';
        $updatedResponse = $response->withProtocolVersion($protocolVersion);
        $this->assertEquals($protocolVersion, $updatedResponse->getProtocolVersion());
        $this->assertNotSame($response, $updatedResponse);
    }
}
