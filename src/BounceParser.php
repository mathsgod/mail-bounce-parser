<?php

namespace Mail;

use ZBateson\MailMimeParser\Message;
use ZBateson\MailMimeParser\Message\IMessagePart;

class BounceParser
{


    static function parseDeliveryStatus(string $report)
    {
        $lines = explode("\n", $report);

        //remove empty lines
        $lines = array_filter($lines, function ($line) {
            return $line != "";
        });


        $data = [];
        $data[] = array_shift($lines);
        foreach ($lines as $line) {

            if ($line[0] == " " || $line[0] == "\t") { //append to last line
                $data[count($data) - 1] .= " " . trim($line);
                continue;
            } else {
                $data[] = $line;
            }
        }

        foreach ($data as $line) {
            if (strpos($line, ":") !== false) {
                $parts = explode(":", $line, 2);

                $name = strtolower($parts[0]);
                $value = trim($parts[1]);

                $result[$name] = $value;
            }
        }

        return $result;
    }


    static function normalizeLineEndings(string $message)
    {
        $rawMessage = $message; // 您收到的原始退信字符串

        // 獲取 Boundary ID
        preg_match('/boundary="([^"]+)"/', $rawMessage, $matches);
        if (isset($matches[1])) {
            $boundary = $matches[1];
            $endBoundary = "--" . $boundary . "--";

            // 如果內容中沒有結尾邊界，手動補上
            if (strpos($rawMessage, $endBoundary) === false) {
                $rawMessage = rtrim($rawMessage) . "\n\n" . $endBoundary . "\n";
            }
        }


        return $rawMessage;
    }

    static function parse(string $message)
    {
        $message = self::normalizeLineEndings($message);

        try {
            $msg = Message::from($message, false);

            $part = $msg->getPart(0, function (IMessagePart $part) {
                return stripos($part->getContentType(), 'delivery-status') !== false;
            });

            if ($part !== null) {
                $body = $part->getContent();
                if ($body) {
                    return self::parseDeliveryStatus($body);
                }
            }
        } catch (\Exception $e) {
        }

        return self::parseWithRegex($message);
    }

    static function parseWithRegex(string $message)
    {
        //用Regex 找出 Final-Recipient, Action, Status, Diagnostic-Code 等字段
        $result = [];

        // 提取 Final-Recipient
        if (preg_match('/Final-Recipient:\s*(.+?)(?:\r?\n|$)/i', $message, $matches)) {
            $result['final-recipient'] = trim($matches[1]);
        }

        // 提取 Action
        if (preg_match('/Action:\s*(.+?)(?:\r?\n|$)/i', $message, $matches)) {
            $result['action'] = trim($matches[1]);
        }

        // 提取 Status
        if (preg_match('/Status:\s*(\d+\.\d+\.\d+)/i', $message, $matches)) {
            $result['status'] = trim($matches[1]);
        }

        // 提取 Diagnostic-Code
        if (preg_match('/Diagnostic-Code:\s*(?:smtp;)?\s*(.+?)(?=\r?\n[A-Z]|\r?\n\r?\n|$)/is', $message, $matches)) {
            $result['diagnostic-code'] = trim($matches[1]);
        }

        // 提取 Remote-MTA (如果有的話)
        if (preg_match('/Remote-MTA:\s*(?:dns;)?\s*(.+?)(?:\r?\n|$)/i', $message, $matches)) {
            $result['remote-mta'] = trim($matches[1]);
        }

        return $result;
    }
}
