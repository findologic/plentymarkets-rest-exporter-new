<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Pim\Property;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Entity;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Translatable;

class CharacteristicText extends Entity implements Translatable
{
    /** @var int */
    private $id;

    /** @var string */
    private $value;

    /** @var string */
    private $lang;

    public function __construct(array $data)
    {
        $this->id = $this->getIntProperty('valueId', $data);
        $this->value = $this->getStringProperty('value', $data);
        $this->lang = $this->getStringProperty('lang', $data);
    }

    public function getData(): array
    {
        return [
            'valueId' => $this->id,
            'value' => $this->value,
            'lang' => $this->lang,
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLang(): string
    {
        return $this->lang;
    }
}
