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
- [Publisher](src/Publisher/Publisher.php) - with which it's possible to make actual ingestions (publishing and unpublishing)

# Example usage:

```php
// Check current schema for the channel where you want to publish/unpublish, using appropriate StreamX endpoint.
// Save the fully qualified name of the channel schema to a variable. Example:
$channelSchemaName = 'dev.streamx.blueprints.data.PageIngestionMessage';

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

// Create the client and a publisher dedicated to a specific channel:
$ingestionClient = StreamxClientBuilders::create('http://localhost:8080')->build();
$pagesPublisher = $ingestionClient->newPublisher("pages", $channelSchemaName);

// Publish data
$pagesPublisher->publish('index.html', $pageData);

// Unpublish data (payload is not needed)
$pagesPublisher->unpublish('index.html');

// To pass customized event time and properties, use the send(Message) method:
$message = (Message::newPublishMessage('index.html', $pageData))
    ->withEventTime(1731498686)
    ->withProperties(['prop-1' => 'value-1', 'prop-2' => 'value-2'])
    ->build();
$pagesPublisher->send($message);

// The Publisher enables you to retrieve the channel schema (as Json String) by invoking the following method:
$pagesPublisher->fetchSchema();

// You can also check the availability of the Ingestion Service, by calling the below method that returns true or false:
$pagesPublisher->isIngestionServiceAvailable();
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