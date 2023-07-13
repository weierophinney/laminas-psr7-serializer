<?php

declare(strict_types=1);

namespace LaminasTest\Psr7\Serializer;

use Laminas\Diactoros\Stream;
use Laminas\Psr7\Serializer\RelativeStream;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use const SEEK_SET;

/**
 * @covers \Laminas\Psr7\Serializer\RelativeStream
 */
class RelativeStreamTest extends TestCase
{
    public function testToString(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->any())->method('isSeekable')->willReturn(true);
        $decorated->expects($this->any())->method('tell')->willReturn(100);
        $decorated->expects($this->any())->method('seek')->with(100, SEEK_SET);
        $decorated->expects($this->once())->method('getContents')->willReturn('foobarbaz');

        $stream = new RelativeStream($decorated, 100);
        $ret    = $stream->__toString();
        $this->assertSame('foobarbaz', $ret);
    }

    public function testClose(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->once())->method('close');
        $stream = new RelativeStream($decorated, 100);
        $stream->close();
    }

    public function testDetach(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->once())->method('detach')->willReturn(250);
        $stream = new RelativeStream($decorated, 100);
        $ret    = $stream->detach();
        $this->assertSame(250, $ret);
    }

    public function testGetSize(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->once())->method('getSize')->willReturn(250);
        $stream = new RelativeStream($decorated, 100);
        $ret    = $stream->getSize();
        $this->assertSame(150, $ret);
    }

    public function testTell(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->once())->method('tell')->willReturn(188);
        $stream = new RelativeStream($decorated, 100);
        $ret    = $stream->tell();
        $this->assertSame(88, $ret);
    }

    public function testIsSeekable(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->once())->method('isSeekable')->willReturn(true);
        $stream = new RelativeStream($decorated, 100);
        $ret    = $stream->isSeekable();
        $this->assertSame(true, $ret);
    }

    public function testIsWritable(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->once())->method('isWritable')->willReturn(true);
        $stream = new RelativeStream($decorated, 100);
        $ret    = $stream->isWritable();
        $this->assertSame(true, $ret);
    }

    public function testIsReadable(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->once())->method('isReadable')->willReturn(false);
        $stream = new RelativeStream($decorated, 100);
        $ret    = $stream->isReadable();
        $this->assertSame(false, $ret);
    }

    public function testSeek(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->once())->method('seek')->with(126, SEEK_SET);
        $stream = new RelativeStream($decorated, 100);
        $this->assertNull($stream->seek(26));
    }

    public function testRewind(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->once())->method('seek')->with(100, SEEK_SET);
        $stream = new RelativeStream($decorated, 100);
        $this->assertNull($stream->rewind());
    }

    public function testWrite(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->once())->method('tell')->willReturn(100);
        $decorated->expects($this->once())->method('write')->with('foobaz')->willReturn(6);
        $stream = new RelativeStream($decorated, 100);
        $ret    = $stream->write("foobaz");
        $this->assertSame(6, $ret);
    }

    public function testRead(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->once())->method('tell')->willReturn(100);
        $decorated->expects($this->once())->method('read')->with(3)->willReturn('foo');
        $stream = new RelativeStream($decorated, 100);
        $ret    = $stream->read(3);
        $this->assertSame("foo", $ret);
    }

    public function testGetContents(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->once())->method('tell')->willReturn(100);
        $decorated->expects($this->once())->method('getContents')->willReturn('foo');
        $stream = new RelativeStream($decorated, 100);
        $ret    = $stream->getContents();
        $this->assertSame("foo", $ret);
    }

    public function testGetMetadata(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->once())->method('getMetadata')->with('bar')->willReturn('foo');
        $stream = new RelativeStream($decorated, 100);
        $ret    = $stream->getMetadata("bar");
        $this->assertSame("foo", $ret);
    }

    public function testWriteRaisesExceptionWhenPointerIsBehindOffset(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->once())->method('tell')->willReturn(0);
        $decorated->expects($this->never())->method('write')->with('foobaz');
        $stream = new RelativeStream($decorated, 100);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid pointer position');

        $stream->write("foobaz");
    }

    public function testReadRaisesExceptionWhenPointerIsBehindOffset(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->once())->method('tell')->willReturn(0);
        $decorated->expects($this->never())->method('read')->with(3);
        $stream = new RelativeStream($decorated, 100);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid pointer position');

        $stream->read(3);
    }

    public function testGetContentsRaisesExceptionWhenPointerIsBehindOffset(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->once())->method('tell')->willReturn(0);
        $decorated->expects($this->never())->method('getContents');
        $stream = new RelativeStream($decorated, 100);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid pointer position');

        $stream->getContents();
    }

    public function testCanReadContentFromNotSeekableResource(): void
    {
        /** @var Stream&MockObject $decorated */
        $decorated = $this->createMock(Stream::class);
        $decorated->expects($this->any())->method('isSeekable')->willReturn(false);
        $decorated->expects($this->never())->method('seek');
        $decorated->expects($this->once())->method('tell')->willReturn(3);
        $decorated->expects($this->once())->method('getContents')->willReturn('CONTENTS');

        $stream = new RelativeStream($decorated, 3);
        $this->assertSame('CONTENTS', $stream->__toString());
    }
}
