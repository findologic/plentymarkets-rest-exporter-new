<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Property;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Property\Selection\Relation;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Entity;

class Selection extends Entity
{
    /** @var int */
    private $id;

    /** @var int */
    private $propertyId;

    /** @var int */
    private $position;

    /** @var string */
    private $createdAt;

    /** @var string */
    private $updatedAt;

    /** @var Relation */
    private $relation;

    public function __construct(array $data)
    {
        //Undocumented - the properties may not match the received data exactly
        $this->id = (int)$data['id'];
        $this->propertyId = (int)$data['propertyId'];
        $this->position = (int)$data['position'];
        $this->createdAt = (string)$data['createdAt'];
        $this->updatedAt = (string)$data['updatedAt'];

        if (!empty($data['relation'])) {
            $this->relation = new Relation($data['relation']);
        }
    }

    public function getData(): array
    {
        return [
            'id' => $this->id,
            'propertyId' => $this->propertyId,
            'position' => $this->position,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'relation' => $this->relation->getData()
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPropertyId(): int
    {
        return $this->propertyId;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function getRelation(): ?Relation
    {
        return $this->relation;
    }
}