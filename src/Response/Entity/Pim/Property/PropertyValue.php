<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Pim\Property;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Entity;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Translatable;

class PropertyValue extends Entity implements Translatable
{
    /** @var int|null This id is nullable */
    private $id;

    /** @var string */
    private $lang;

    /** @var string */
    private $value;

    /** @var string|null */
    private $description;

    public function __construct(array $data)
    {
        $this->id = $this->getIntProperty('id', $data);
        $this->lang = $this->getStringProperty('lang', $data);
        $this->value = $this->getStringProperty('value', $data);
        $this->description = $this->getStringProperty('description', $data);
    }

    public function getData(): array
    {
        return [
            'id' => $this->id,
            'lang' => $this->lang,
            'value' => $this->value,
            'description' => $this->description,
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
