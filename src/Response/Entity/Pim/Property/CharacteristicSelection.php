<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Pim\Property;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Entity;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Translatable;

class CharacteristicSelection extends Entity implements Translatable
{
    /** @var int */
    private $id;

    /** @var int */
    private $propertyId;

    /** @var string */
    private $name;

    /** @var string */
    private $lang;

    /** @var string|null */
    private $description;

    public function __construct(array $data)
    {
        $this->id = $this->getIntProperty('id', $data);
        $this->propertyId = $this->getIntProperty('propertyId', $data);
        $this->name = $this->getStringProperty('name', $data);
        $this->lang = $this->getStringProperty('lang', $data);
        $this->description = $this->getStringProperty('description', $data);
    }

    public function getData(): array
    {
        return [
            'id' => $this->id,
            'propertyId' => $this->propertyId,
            'name' => $this->name,
            'lang' => $this->lang,
            'description' => $this->description,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
