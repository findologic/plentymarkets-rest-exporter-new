<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\VatConfiguration;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Entity;

class VatRate extends Entity
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var float */
    private $vatRate;

    public function __construct(array $data)
    {
        $this->id = (int)$data['id'];
        $this->name = (string) $data['name'];
        $this->vatRate = (float)$data['vatRate'];
    }

    public function getData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'vatRate' => $this->vatRate
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVatRate(): float
    {
        return $this->vatRate;
    }
}
