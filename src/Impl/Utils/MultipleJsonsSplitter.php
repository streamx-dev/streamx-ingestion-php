<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl\Utils;

class MultipleJsonsSplitter {

    private const ESCAPED_QUOTE = '\"';
    private const ESCAPED_QUOTE_TEMPORARY_REPLACEMENT = '^kj-N2hpS.';

    /**
     * @param $jsonsString string containing one or more JSON strings. They can be separated with any or no separator
     * @return array of separated JSON strings
     */
    static function splitToSingleJsons(string $jsonsString): array {
        $resultJsons = [];
        $openedBracesCounter = 0;
        $currentJsonStartIndex = 0;
        $insideQuotes = false;

        $escapedJsonsString = self::maskEscapedQuotes($jsonsString);
        $stringLength = strlen($escapedJsonsString);

        $endIndexOfLastValidSingleJson = -1;
        for ($i = 0; $i < $stringLength; $i++) {
            $char = $escapedJsonsString[$i];
            if ($char == '"') {
                $insideQuotes = !$insideQuotes;
            }
            if ($insideQuotes) {
                continue;
            }
            if ($char === '{') {
                $openedBracesCounter++;
                if ($openedBracesCounter === 1) {
                    $currentJsonStartIndex = $i;
                }
            } elseif ($char === '}') {
                $openedBracesCounter--;
                if ($openedBracesCounter === 0) {
                    $currentJsonLength = $i - $currentJsonStartIndex + 1;
                    $resultJson = substr($escapedJsonsString, $currentJsonStartIndex, $currentJsonLength);
                    $resultJsons[] = self::unmaskEscapedQuotes($resultJson);
                    $endIndexOfLastValidSingleJson = $i;
                }
            }
        }

        $remainingString = trim(substr($escapedJsonsString, 1 + $endIndexOfLastValidSingleJson));
        if (!empty($remainingString)) {
            $resultJsons[] = self::unmaskEscapedQuotes($remainingString);
        }

        return $resultJsons;
    }

    private static function maskEscapedQuotes(string $string): string {
        return str_replace(self::ESCAPED_QUOTE, self::ESCAPED_QUOTE_TEMPORARY_REPLACEMENT, $string);
    }

    private static function unmaskEscapedQuotes(string $string): string {
        return str_replace(self::ESCAPED_QUOTE_TEMPORARY_REPLACEMENT, self::ESCAPED_QUOTE, $string);
    }
}