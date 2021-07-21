<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Attribute;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Entity;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Translatable;

class Name extends Entity implements Translatable
{
    /** @var int */
    private $attributeId;

    /** @var string */
    private $lang;

    /** @var string */
    private $name;

    public function __construct(array $data)
    {
        // Undocumented - the properties may not match the received data exactly
        $this->attributeId = (int)$data['attributeId'];
        $this->lang = (string)$data['lang'];
        $this->name = (string)$data['name'];
    }

    public function getData(): array
    {
        return [
            'attributeId' => $this->attributeId,
            'lang' => $this->lang,
            'name' => $this->name
        ];
    }

    public function getAttributeId(): int
    {
        return $this->attributeId;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
