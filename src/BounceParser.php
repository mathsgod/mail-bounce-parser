<?php

namespace Mail;

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


    static function parse(string $message)
    {
        $value = "";
        $msg = \Laminas\Mime\Message::createFromMessage($message);

        $headers_array = $msg->getParts()[0]->getHeadersArray();
        foreach ($headers_array as $header) {
            if ($header[0] == "Content-Type") {
                $value = $header[1];

                //join back to string
                $value = explode(";", $value);
                //trim all from lines
                $value = array_map(function ($line) {
                    return trim($line);
                }, $value);

                $value = array_map(function ($line) {
                    $parts = explode("=", $line, 2);
                    $name = $parts[0];
                    $value = $parts[1] ?? "";
                    return [$name, $value];
                }, $value);

                //find boundary
                foreach ($value as $i => $line) {
                    if ($line[0] == "boundary") {
                        $value = $line[1];
                        //remove quotes
                        $value = trim($value, '"');
                        break;
                    }
                }
                continue;
            }
        }

        $boundary =  $value;

        $msg = \Laminas\Mime\Message::createFromMessage($message, $boundary);

        $body = null;
        foreach ($msg->getParts() as $part) {
            if ($part->getType() == "message/delivery-status") {
                $body = $part->getContent();
                break;
            }
        }

        if (!$body) {
            return [];
        }

        return self::parseDeliveryStatus($body);
    }
}
