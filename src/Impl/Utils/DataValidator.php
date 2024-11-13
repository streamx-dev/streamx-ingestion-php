<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl\Utils;

class DataValidator
{
    private object $object;

    private function __construct(object $object)
    {
        $this->object = $object;
    }

    public static function for(object $object): DataValidator
    {
        return new self($object);
    }

    public function require(string $property)
    {
        if (property_exists($this->object, $property)) {
            return $this->requireNonNull($this->object->{$property}, $property);
        }
        throw new DataValidationException("Property [$property] is required");
    }

    public function retrieveNullable(string $property)
    {
        if (property_exists($this->object, $property)) {
            return $this->object->{$property};
        }
        return null;
    }

    private function requireNonNull($value, string $name)
    {
        if ($value == null) {
            throw new DataValidationException("Property [$name] is required");
        }
        return $value;
    }
}