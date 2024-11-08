<?php declare(strict_types=1);

namespace Streamx\Clients\Ingestion\Impl;

use AvroSchema;
use Streamx\Clients\Ingestion\Exceptions\StreamxClientException;
use Streamx\Clients\Ingestion\Publisher\JsonProvider;
use Streamx\Clients\Ingestion\Publisher\Message;

class DefaultJsonProvider implements JsonProvider
{

    public function getJson(Message $message, string $schema): string
    {
        $avroSchema = AvroSchema::parse($schema);

        if ($message->action == Message::PUBLISH_ACTION) {
            $this->wrapPayloadWithTypeName($message, $avroSchema);
        }

        $avroArray = $this->convertObjectToAvroArray($message, $avroSchema);

        $messageAsJson = json_encode($avroArray);
        if (json_last_error() == JSON_ERROR_NONE) {
            return $messageAsJson;
        }
        throw new StreamxClientException('JSON encoding error: ' . json_last_error_msg());
    }

    private function wrapPayloadWithTypeName(Message $message, AvroSchema $avroSchema): void
    {
        $schemaQualifiedName = $avroSchema->qualified_name();
        $payloadTypeName = preg_replace('/IngestionMessage$/', '', $schemaQualifiedName);

        $payload = $message->payload;
        $payload = array($payloadTypeName => $payload);
        $message->payload = $payload;
    }

    private function convertObjectToAvroArray($object, $schema): array
    {
        $avroData = [];
        foreach ($schema->fields() as $field) {
            $fieldName = $field->name();
            $avroData[$fieldName] = $object->$fieldName;
        }
        return $avroData;
    }
}