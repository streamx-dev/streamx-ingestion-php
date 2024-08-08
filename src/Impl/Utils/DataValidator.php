<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl\Utils;

class DataValidator
{

    private function __construct(private readonly object $object)
    {
    }

    public static function for(object $object): DataValidator
    {
        return new self($object);
    }

    public function require(string $property): mixed
    {
        if (property_exists($this->object, $property)) {
            return $this->requireNonNull($this->object->{$property}, $property);
        }
        throw new DataValidationException("Property [$property] is required");
    }

    private function requireNonNull(mixed $value, string $name): mixed
    {
        if ($value == null) {
            throw new DataValidationException("Property [$name] is required");
        }
        return $value;
    }
}