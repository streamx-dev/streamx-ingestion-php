# StreamX PHP Ingestion Client

StreamX PHP Ingestion Client enables publishing and unpublishing data to and from StreamX via its
REST Ingestion Service.

Main entry points are:

- [StreamxClientBuilders](src/Builders/StreamxClientBuilders.php) - with which it's possible to
  create default or customized clients,
- [StreamxClient](src/StreamxClient.php) - with which it's possible to create publishers
- [Publisher](src/Publisher/Publisher.php) - with which it's possible to make actual publications

An example usage:

```php
// Create some test content, either as an associative array or an object
$data = ['content' => ['bytes' => 'Hello, StreamX!']]

// Create the client and a publisher dedicated to a specific 'channel'
$ingestionClient = StreamxClientBuilders::create('http://localhost:8080')->build();
$pagesPublisher = $ingestionClient->newPublisher("pages");

// Publish and unpublish data
$pagesPublisher->publish('index.html', $data);
$pagesPublisher->unpublish('index.html');

```

## Installation

The recommended way to install the client is through
[Composer](https://getcomposer.org/).

```bash
composer require streamx/ingestion-client
```