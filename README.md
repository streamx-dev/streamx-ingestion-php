# StreamX PHP Ingestion Client

StreamX PHP Ingestion Client enables publishing and unpublishing data to and from StreamX via its
REST Ingestion Service.

# Requirements
The minimal version of PHP to use the PHP Ingestion Client is 8.1

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
class Page {
    public function __construct(public Content $content) { }
}
class Content {
    public function __construct(public string $bytes) { }
}
$pageData = new Page(new Content('Hello, StreamX!'));

// Create the client and a publisher dedicated to a specific 'channel'
$ingestionClient = StreamxClientBuilders::create('http://localhost:8080')->build();
$pagesPublisher = $ingestionClient->newPublisher("pages", $pagesSchemaJson);

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