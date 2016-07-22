<?php

namespace Zerifas\Supermodel;

use DateTime;

trait TimestampColumns
{
    protected $createdAt;
    protected $updatedAt;

    public function setCreatedAt(DateTime $value)
    {
        $this->createdAt = $value;
        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(DateTime $value)
    {
        $this->updatedAt = $value;
        return $this;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function toArray()
    {
        if ($this->createdAt === null) {
            $this->createdAt = new DateTime();
        }

        $this->updatedAt = new DateTime();

        return parent::toArray();
    }
}
