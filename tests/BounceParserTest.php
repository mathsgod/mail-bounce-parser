<?php

declare(strict_types=1);

namespace Mail;

use PHPUnit\Framework\TestCase;

final class BounceParserTest extends TestCase
{
    public function testParseDeliveryStatus(): void
    {
        $report = <<<'EOT'
Final-Recipient: rfc822; recipient@example.com
Action: failed
Status: 5.1.1
Diagnostic-Code: smtp; 550 5.1.1 User unknown
Remote-MTA: dns; mx.example.com
EOT;

        $result = BounceParser::parseDeliveryStatus($report);

        self::assertSame('rfc822; recipient@example.com', $result['final-recipient']);
        self::assertSame('failed', $result['action']);
        self::assertSame('5.1.1', $result['status']);
        self::assertSame('smtp; 550 5.1.1 User unknown', $result['diagnostic-code']);
        self::assertSame('dns; mx.example.com', $result['remote-mta']);
    }

    public function testParseDeliveryStatusWithContinuationLine(): void
    {
        $report = <<<'EOT'
Diagnostic-Code: smtp; 550 5.1.1 User unknown
    because the mailbox is full
EOT;

        $result = BounceParser::parseDeliveryStatus($report);

        self::assertSame('smtp; 550 5.1.1 User unknown because the mailbox is full', $result['diagnostic-code']);
    }

    public function testParseFullBounceMessage(): void
    {
        $message = $this->buildMultipartBounce();

        $result = BounceParser::parse($message);

        self::assertSame('rfc822; recipient@example.com', $result['final-recipient']);
        self::assertSame('failed', $result['action']);
        self::assertSame('5.1.1', $result['status']);
        self::assertSame('smtp; 550 5.1.1 User unknown', $result['diagnostic-code']);
    }

    public function testParseFallsBackToRegexWhenMimeParsingFails(): void
    {
        $message = <<<'EOT'
From: mailer-daemon@example.com
To: sender@example.com
Subject: bounce

Final-Recipient: rfc822; fallback@example.com
Action: failed
Status: 4.2.2
Diagnostic-Code: smtp; 452 4.2.2 Mailbox full
EOT;

        $result = BounceParser::parse($message);

        self::assertSame('rfc822; fallback@example.com', $result['final-recipient']);
        self::assertSame('failed', $result['action']);
        self::assertSame('4.2.2', $result['status']);
        self::assertSame('452 4.2.2 Mailbox full', $result['diagnostic-code']);
    }

    private function buildMultipartBounce(): string
    {
        return <<<'EOT'
From: mailer-daemon@example.com
To: sender@example.com
Subject: Delivery Status Notification (Failure)
Content-Type: multipart/report; report-type=delivery-status; boundary="bounce-boundary"

--bounce-boundary
Content-Type: text/plain

This message was created automatically by mail delivery software.

--bounce-boundary
Content-Type: message/delivery-status

Final-Recipient: rfc822; recipient@example.com
Action: failed
Status: 5.1.1
Diagnostic-Code: smtp; 550 5.1.1 User unknown

--bounce-boundary--
EOT;
    }
}
