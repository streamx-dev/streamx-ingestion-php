<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl\Utils;

class DataValidator
{
    private PropertyRetriever $propertyRetriever;

    private function __construct(object $object)
    {
        $this->propertyRetriever = PropertyRetriever::for($object);
    }

    public static function for(object $object): self
    {
        return new self($object);
    }

    public function require(string $property)
    {
        $value = $this->propertyRetriever->retrieveNullable($property);
        if ($value) {
            return $value;
        }
        throw new DataValidationException("Property [$property] is required");
    }
}