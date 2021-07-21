<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Unit;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Entity;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Translatable;

class Name extends Entity implements Translatable
{
    /** @var int */
    private $unitId;

    /** @var string */
    private $lang;

    /** @var string */
    private $name;

    public function __construct(array $data)
    {
        // Undocumented - the properties may not match the received data exactly
        $this->unitId = (int)$data['unitId'];
        $this->lang = (string)$data['lang'];
        $this->name = (string)$data['name'];
    }

    public function getData(): array
    {
        return [
            'unitId' => $this->unitId,
            'lang' => $this->lang,
            'name' => $this->name
        ];
    }

    public function getUnitId(): int
    {
        return $this->unitId;
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
