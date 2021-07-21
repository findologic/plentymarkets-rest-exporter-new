<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\PropertyGroup;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Entity;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Translatable;

class Name extends Entity implements Translatable
{
    /** @var int */
    private $propertyGroupId;

    /** @var string|null */
    private $lang;

    /** @var string|null */
    private $name;

    /** @var string|null */
    private $description;

    public function __construct(array $data)
    {
        $this->propertyGroupId = (int)$data['propertyGroupId'];
        $this->lang = $this->getStringProperty('lang', $data);
        $this->name = $this->getStringProperty('name', $data);
        $this->description = $this->getStringProperty('description', $data);
    }

    public function getData(): array
    {
        return [
            'propertyGroupId' => $this->propertyGroupId,
            'lang' => $this->lang,
            'name' => $this->name,
            'description' => $this->description
        ];
    }

    public function getPropertyGroupId(): int
    {
        return $this->propertyGroupId;
    }

    public function getLang(): string
    {
        return (string)$this->lang;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
