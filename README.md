# mail-bounce-parser

## Installation

```bash
composer require mathsgod/mail-bounce-parser
```

## Usage

```php

$eml=file_get_contents('bounce.eml');

$report = \Mail\BounceParser::parse($eml);
print_r($report);

```    
