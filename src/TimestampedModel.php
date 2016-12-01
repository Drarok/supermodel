<?php

namespace Zerifas\Supermodel;

use DateTime;

use Zerifas\Supermodel\Transformers\DateTimeTransformer;
use Zerifas\Supermodel\Metadata\MetadataCache;

abstract class TimestampedModel extends Model
{
    public static function getValueTransformers(): array
    {
        return [
            'createdAt' => DateTimeTransformer::class,
            'updatedAt' => DateTimeTransformer::class,
        ];
    }

    public function toArray(MetadataCache $metadata): array
    {
        $table = $metadata->getTableName(static::class);
        $data = parent::toArray($metadata);

        $now = DateTimeTransformer::toArray(new DateTime());

        $key = "${table}.createdAt";
        if (empty($data[$key])) {
            $data[$key] = $now;
        }

        $data["${table}.updatedAt"] = $now;

        return $data;
    }
}
