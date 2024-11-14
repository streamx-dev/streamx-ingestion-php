<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Tests\Unit\Impl\Utils;

use PHPUnit\Framework\TestCase;
use Streamx\Clients\Ingestion\Impl\Utils\DataValidationException;
use Streamx\Clients\Ingestion\Impl\Utils\DataValidator;

class DataValidatorTest extends TestCase
{

    /** @test */
    public function shouldReturnRequiredProperty()
    {
        $object = json_decode('{"name":"value"}');

        $value = DataValidator::for($object)->require('name');

        $this->assertEquals('value', $value);
    }

    /** @test */
    public function shouldThrowExceptionWhenRequiredPropertyIsAbsent()
    {
        $object = json_decode('{"not-name":"value"}');

        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('Property [name] is required');

        DataValidator::for($object)->require('name');
    }

    /** @test */
    public function shouldThrowExceptionWhenRequiredPropertyIsNull()
    {
        $object = json_decode('{"name":null}');

        $this->expectException(DataValidationException::class);
        $this->expectExceptionMessage('Property [name] is required');

        DataValidator::for($object)->require('name');
    }
}