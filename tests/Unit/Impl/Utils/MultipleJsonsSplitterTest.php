<?php

namespace Streamx\Clients\Ingestion\Tests\Unit\Impl\Utils;

use PHPUnit\Framework\TestCase;
use Streamx\Clients\Ingestion\Impl\Utils\MultipleJsonsSplitter;

class MultipleJsonsSplitterTest extends TestCase
{

    /** @test */
    public function shouldSplitMultipleJsonsString() {
        // given
        $input = '
            { "aaa": "bbb" }
            {
                "ccc": "ddd",
                "eee": "fff"
            }{ "ggg": "{0} = }" }{ "hhh": "{" }

            {}

            { "key": "value with a \" (escaped quote)" }{ "key": "\"\"\"" }

            {"invalid}": "data"
        ';

        // when
        $singleJsons = MultipleJsonsSplitter::splitToSingleJsons($input);

        // then
        $this->assertIsArray($singleJsons);
        $this->assertEquals([
            '{ "aaa": "bbb" }',
            '{
                "ccc": "ddd",
                "eee": "fff"
            }',
            '{ "ggg": "{0} = }" }',
            '{ "hhh": "{" }',
            '{}',
            '{ "key": "value with a \" (escaped quote)" }',
            '{ "key": "\"\"\"" }',
            '{"invalid}": "data"'
        ], $singleJsons);
    }

    /** @test */
    public function shouldReturnSingleJsonStringForSingleJsonInput() {
        // given
        $input = '{ "aaa": "bbb" }';

        // when
        $singleJsons = MultipleJsonsSplitter::splitToSingleJsons($input);

        // then
        $this->assertIsArray($singleJsons);
        $this->assertCount(1, $singleJsons);
        $this->assertEquals('{ "aaa": "bbb" }', $singleJsons[0]);
    }

    /** @test */
    public function shouldReturnOriginalInputForInvalidSingleJsonInput() {
        // given
        $input = '{"invalid}": "data"';

        // when
        $singleJsons = MultipleJsonsSplitter::splitToSingleJsons($input);

        // then
        $this->assertIsArray($singleJsons);
        $this->assertCount(1, $singleJsons);
        $this->assertEquals($input, $singleJsons[0]);
    }

}
