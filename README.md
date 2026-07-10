# Mail Bounce Parser

A PHP library for parsing email bounce messages and extracting delivery status notifications.

## Features

- Parse bounce emails in EML format
- Extract delivery status information
- Identify recipient addresses and bounce reasons
- Support for standard DSN (Delivery Status Notification) format
- Built on top of `zbateson/mail-mime-parser`

## Requirements

- PHP 8.1 or higher
- Composer

## Installation

Install via Composer:

```bash
composer require mathsgod/mail-bounce-parser
```

## Usage

### Basic Example

```php
<?php
require 'vendor/autoload.php';

// Load the bounce email content
$eml = file_get_contents('bounce.eml');

// Parse the bounce message
$report = \Mail\BounceParser::parse($eml);

// Display the parsed results
print_r($report);
```

### Handling Results

The parser returns an array containing the delivery status information, including:

- Recipient email addresses
- Bounce status codes
- Diagnostic messages
- Action taken by the mail server
- Original message details

## License

This project is open source and available under the terms specified in the LICENSE file.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

If you encounter any issues or have questions, please open an issue on the GitHub repository.    
