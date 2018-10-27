<?php declare(strict_types=1);

namespace App\Tests;

use App\Exception\InvalidStreamResourceException;
use App\Stream;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    public function testCanInitStream(): void
    {
        $this->assertInstanceOf(Stream::class, new Stream('php://memory'));
    }

    public function testPassInvalidStreamResourceException(): void
    {
        $this->expectException(InvalidStreamResourceException::class);
        new Stream('[ WRONG RESOURCE ]', 'rb');
    }

    public function testIsReadableStream(): void
    {
        $stream = new Stream('php://memory', 'r');
        $this->assertTrue($stream->isReadable());
    }

    public function testIsWritableStream(): void
    {
        $stream = new Stream('php://memory', 'r');
        $this->assertFalse($stream->isWritable());

        $file = tempnam(sys_get_temp_dir(), 'test');
        $stream = new Stream($file, 'w');
        $this->assertTrue($stream->isWritable());
    }

    public function testIsSeekableStream(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'stream_');
        file_put_contents($file, 'Test stream content');
        $stream = new Stream($file, 'wb+');

        $this->assertTrue($stream->isSeekable());
    }

    public function testReturnFullContentOnConversionToString(): void
    {
        $content = 'Test stream content';
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($content);

        $this->assertEquals($content, $stream->__toString());
    }

    public function testCheckStreamMetadata(): void
    {
        $wrapperType = 'php';
        $streamType = 'memory';
        $mode = 'rb';

        $stream = new Stream(sprintf('%s://%s', $wrapperType, $streamType), $mode);

        $this->assertInternalType('array', $stream->getMetadata());
        $this->assertEquals($mode, strtolower($stream->getMetadata('mode')));
        $this->assertEquals($wrapperType, strtolower($stream->getMetadata('wrapper_type')));
        $this->assertEquals($streamType, strtolower($stream->getMetadata('stream_type')));
    }

    public function testDetachReturnsResource(): void
    {
        $resource = fopen('php://memory', 'wb+');
        $stream = new Stream($resource);

        $this->assertSame($resource, $stream->detach());
    }

    public function testReturnCorrectContentSize(): void
    {
        $content = 'Test stream content';
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($content);

        $this->assertEquals(\strlen($content), $stream->getSize());
    }
}
