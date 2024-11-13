# StreamX PHP Ingestion Client

StreamX PHP Ingestion Client enables publishing and unpublishing data to and from StreamX via its
REST Ingestion Service.

# Requirements
PHP 7.4 or higher

# Compatibility
As of 2024-11-07, the supported version of StreamX is 0.0.45.

# Main entry points:

- [StreamxClientBuilders](src/Builders/StreamxClientBuilders.php) - with which it's possible to
  create default or customized clients,
- [StreamxClient](src/StreamxClient.php) - with which it's possible to create publishers
- [Publisher](src/Publisher/Publisher.php) - with which it's possible to make actual publications

# Example usage:

```php
// Download schema for the channel from appropriate StreamX endpoint, then save it to a local file. Load it into a string:
$pagesSchemaJson = file_get_contents('your-schema-files-directory/pages-schema.avsc');

// Create some test content that matches the channel schema. It can be created as an associative array:
$pageData = ['content' => ['bytes' => 'Hello, StreamX!']];

// It can also be created as a PHP object that follows the same schema:
class Page
{
  public $content;

  public function __construct(Content $content) {
    $this->content = $content;
  }
}

class Content
{
  public $bytes;

  public function __construct(string $bytes) {
    $this->bytes = $bytes;
  }
}
$pageData = new Page(new Content('Hello, StreamX!'));

// Create the client and a publisher dedicated to a specific channel. The channel schema name can be retrieved from your StreamX instance
$ingestionClient = StreamxClientBuilders::create('http://localhost:8080')->build();
$pagesPublisher = $ingestionClient->newPublisher("pages", $channelSchemaName);

// Publish data
$pagesPublisher->publish('index.html', $pageData);

// Unpublish data (payload is not needed)
$pagesPublisher->unpublish('index.html');

```

# Installation

The recommended way to install the client is through
[Composer](https://getcomposer.org/).

```bash
composer require streamx/ingestion-client
```

# Run tests with coverage

1. Install xdebug (with version that supports PHP 7.4):
```bash
pecl install xdebug-3.1.5
```

2. Configure xdebug mode:
```bash
export XDEBUG_MODE=coverage
```

3. Run tests with coverage and open results in web browser:
```bash
./vendor/bin/phpunit --coverage-text --coverage-html target/coverage-report
open target/coverage-report/index.html
```