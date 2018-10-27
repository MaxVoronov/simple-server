<?php declare(strict_types=1);

namespace App;

use App\Exception\InvalidStreamResourceException;
use App\Exception\StreamRuntimeException;
use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    /** @var bool|resource */
    protected $resource;

    /**
     * Stream constructor
     * @param string $stream
     * @param string $mode
     */
    public function __construct($stream = 'php://memory', string $mode = 'wb+')
    {
        if (\is_resource($stream)) {
            $this->resource = $stream;
        } elseif (\is_string($stream)) {
            set_error_handler(function ($errno, $errstr) {
                throw new InvalidStreamResourceException(
                    'Invalid file provided for stream; must be a valid path with valid permissions'
                );
            }, E_WARNING);
            $this->resource = fopen($stream, $mode);
            restore_error_handler();
        }
    }

    /**
     * @inheritdoc
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;

        return $resource;
    }

    /**
     * @inheritdoc
     */
    public function getSize()
    {
        if ($this->resource === null) {
            return null;
        }
        $stats = fstat($this->resource);

        return $stats['size'];
    }

    /**
     * @inheritdoc
     */
    public function close(): void
    {
        if (!$this->resource) {
            return;
        }

        $resource = $this->detach();
        fclose($resource);
    }

    /**
     * @inheritdoc
     */
    public function tell(): int
    {
        if (!$this->resource) {
            throw new StreamRuntimeException('No resource available; cannot tell position');
        }

        $result = ftell($this->resource);
        if (!\is_int($result)) {
            throw new StreamRuntimeException('Error occurred during tell operation');
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function eof(): bool
    {
        if (!$this->resource) {
            return true;
        }

        return feof($this->resource);
    }

    /**
     * @inheritdoc
     */
    public function isSeekable(): bool
    {
        if (!$this->resource) {
            return false;
        }

        return (bool) $this->getMetadata('seekable');
    }

    /**
     * @inheritdoc
     */
    public function seek($offset, $whence = SEEK_SET): bool
    {
        if (!$this->resource) {
            throw new StreamRuntimeException('No resource available; cannot seek position');
        }

        if (!$this->isSeekable()) {
            throw new StreamRuntimeException('Stream is not seekable');
        }

        $result = fseek($this->resource, $offset, $whence);
        if ($result !== 0) {
            throw new StreamRuntimeException('Error seeking within stream');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function rewind(): self
    {
        rewind($this->resource);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isWritable(): bool
    {
        if (!$this->resource) {
            return false;
        }

        $uri = $this->getMetadata('uri');

        return is_writable($uri);
    }

    /**
     * @inheritdoc
     */
    public function write($string): int
    {
        if (!$this->resource) {
            throw new StreamRuntimeException('No resource available; cannot write');
        }

        $result = fwrite($this->resource, $string);
        if (false === $result) {
            throw new StreamRuntimeException('Error writing to stream');
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function isReadable(): bool
    {
        if (!$this->resource) {
            return false;
        }

        $mode = $this->getMetadata('mode');

        return (strpos($mode, 'r') !== false || strpos($mode, '+') !== false);
    }

    /**
     * @inheritdoc
     */
    public function read($length): string
    {
        if (!$this->resource) {
            throw new StreamRuntimeException('No resource available; cannot read');
        }

        if (!$this->isReadable()) {
            throw new StreamRuntimeException('Stream is not readable');
        }

        $result = fread($this->resource, $length);
        if ($result === false) {
            throw new StreamRuntimeException('Error reading stream');
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getContents(): string
    {
        if (!$this->isReadable()) {
            return '';
        }

        $result = stream_get_contents($this->resource);
        if ($result === false) {
            throw new StreamRuntimeException('Error reading from stream');
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getMetadata($key = null)
    {
        if ($key === null) {
            return stream_get_meta_data($this->resource);
        }

        $metadata = stream_get_meta_data($this->resource);
        if (!array_key_exists($key, $metadata)) {
            return null;
        }

        return $metadata[$key];
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        if (!$this->isReadable()) {
            return '';
        }

        try {
            $this->rewind();
            return $this->getContents();
        } catch (StreamRuntimeException $e) {
            return '';
        }
    }
}
