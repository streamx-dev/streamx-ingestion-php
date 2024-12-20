<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl\Utils;

class PropertyRetriever
{
    private object $object;

    private function __construct(object $object)
    {
        $this->object = $object;
    }

    public static function for(object $object): self
    {
        return new self($object);
    }

    /**
     * @param string $property
     * @return mixed value of the property, or null if no such property
     */
    public function retrieveNullable(string $property)
    {
        if (property_exists($this->object, $property)) {
            return $this->object->{$property};
        }
        return null;
    }
}