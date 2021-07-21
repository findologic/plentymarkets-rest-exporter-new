<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\ItemProperty;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Entity;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Translatable;

class Name extends Entity implements Translatable
{
    /** @var int */
    private $propertyId;

    /** @var string */
    private $lang;

    /** @var string */
    private $name;

    /** @var string|null */
    private $description;

    public function __construct(array $data)
    {
        $this->propertyId = $this->getIntProperty('propertyId', $data);
        $this->lang = $this->getStringProperty('lang', $data);
        $this->name = $this->getStringProperty('name', $data);
        $this->description = $this->getStringProperty('description', $data);
    }

    public function getData(): array
    {
        return [
            'propertyId' => $this->propertyId,
            'lang' => $this->lang,
            'name' => $this->name,
            'description' => $this->description
        ];
    }

    public function getPropertyId(): int
    {
        return $this->propertyId;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
