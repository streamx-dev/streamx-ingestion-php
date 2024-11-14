<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl\Utils;

use Exception;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;

final class HttpUtils
{
    private const EM_MALFORMED_URI = 'Ingestion endpoint URI: %s is malformed. %s';

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * @throws StreamxClientException
     */
    public static function buildUri(string $uriString): UriInterface
    {
        try {
            $uri = new Uri($uriString);
        } catch (Exception $e) {
            throw new StreamxClientException(sprintf(self::EM_MALFORMED_URI,
                $uriString, $e->getMessage()), $e);
        }
        return $uri;
    }

    /**
     * @throws StreamxClientException
     */
    public static function buildAbsoluteUri(string $uriString): UriInterface
    {
        $uri = self::buildUri($uriString);
        self::validateAbsoluteUri($uri, $uriString);
        return $uri;
    }

    /**
     * @throws StreamxClientException
     */
    private static function validateAbsoluteUri(URIInterface $uri, string $uriSource): void
    {
        if (empty($uri->getScheme())) {
            throw new StreamxClientException(sprintf(self::EM_MALFORMED_URI,
                $uriSource, 'Relative URI is not supported.'));
        }
    }
}