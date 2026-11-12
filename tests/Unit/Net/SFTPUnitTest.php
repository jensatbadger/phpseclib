<?php

/**
 * @author    phpseclib contributors
 * @copyright 2026 phpseclib contributors
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 */

declare(strict_types=1);

namespace phpseclib4\Tests\Unit\Net;

use phpseclib4\Exception\UnexpectedValueException;
use phpseclib4\Net\SFTP;
use phpseclib4\Tests\PhpseclibTestCase;

class SFTPUnitTest extends PhpseclibTestCase
{
    public function testTruncatedRequestIdPacketIsRejectedWithoutBuffering(): void
    {
        $sftp = (new \ReflectionClass(SFTP::class))->newInstanceWithoutConstructor();
        $setProperty = static function (string $name, mixed $value) use ($sftp): void {
            $property = new \ReflectionProperty(SFTP::class, $name);
            $property->setValue($sftp, $value);
        };
        $setProperty('use_request_id', true);
        $setProperty('packet_buffer', "\0\0\0\x04\x65\x01\x02\x03");

        $method = new \ReflectionMethod(SFTP::class, 'get_sftp_packet');
        $warnings = [];
        set_error_handler(static function (int $severity, string $message) use (&$warnings): bool {
            $warnings[] = $message;
            return true;
        });

        try {
            try {
                $method->invoke($sftp, 7);
                $this->fail('Expected a truncated packet to be rejected');
            } catch (UnexpectedValueException $e) {
                $this->assertSame('Packet is too small', $e->getMessage());
            }
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
        $this->assertSame('', (new \ReflectionProperty(SFTP::class, 'packet_buffer'))->getValue($sftp));
        $this->assertSame(-1, (new \ReflectionProperty(SFTP::class, 'packet_type'))->getValue($sftp));
        $this->assertSame([], (new \ReflectionProperty(SFTP::class, 'requestBuffer'))->getValue($sftp));
    }
}
