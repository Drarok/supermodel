<?php

namespace Zerifas\Supermodel;

use DateTime;

use Zerifas\Supermodel\Transformers\DateTimeTransformer;
use Zerifas\Supermodel\Metadata\MetadataCache;

abstract class TimestampedModel extends Model
{
    /**
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @var DateTime
     */
    protected $updatedAt;

    public static function getValueTransformers(): array
    {
        return [
            'createdAt' => DateTimeTransformer::class,
            'updatedAt' => DateTimeTransformer::class,
        ];
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function toArray(MetadataCache $metadata): array
    {
        $table = $metadata->getTableName(static::class);
        $data = parent::toArray($metadata);

        $now = new DateTime();
        $nowString = DateTimeTransformer::toArray($now);

        $key = "${table}.createdAt";
        if (empty($data[$key])) {
            $this->setCreatedAt($now);
            $data[$key] = $nowString;
        }

        $this->setUpdatedAt($now);
        $data["${table}.updatedAt"] = $nowString;

        return $data;
    }
}
