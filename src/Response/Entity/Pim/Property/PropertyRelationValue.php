<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Pim\Property;

use DateTimeInterface;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Entity;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Translatable;

class PropertyRelationValue extends Entity implements Translatable
{
    /** @var int */
    private $id;

    /** @var int */
    private $relationId;

    /** @var string */
    private $value;

    /** @var string */
    private $lang;

    /** @var string|null */
    private $description;

    /** @var DateTimeInterface */
    private $createdAt;

    /** @var DateTimeInterface */
    private $updatedAt;

    public function __construct(array $data)
    {
        $this->id = $this->getIntProperty('id', $data);
        $this->relationId = $this->getIntProperty('propertyRelationId', $data);
        $this->value = $this->getStringProperty('value', $data);
        $this->lang = $this->getStringProperty('lang', $data);
        $this->description = $this->getStringProperty('description', $data);
        $this->createdAt = $this->getDateTimeProperty('createdAt', $data);
        $this->updatedAt = $this->getDateTimeProperty('updatedAt', $data);
    }

    public function getData(): array
    {
        return [
            'id' => $this->id,
            'propertyRelationId' => $this->relationId,
            'value' => $this->value,
            'lang' => $this->lang,
            'description' => $this->description,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getRelationId(): int
    {
        return $this->relationId;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }
}
