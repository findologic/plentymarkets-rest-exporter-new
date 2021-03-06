<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity;

use DateTimeInterface;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\PropertyGroup\Name;

/**
 * Note - this class is not redundant. The names in this class differ from the ones in Property\Group\Name
 */
class PropertyGroup extends Entity
{
    /** @var int */
    private $id;

    /** @var string|null */
    private $backendName;

    /** @var string|null */
    private $orderPropertyGroupingType;

    /** @var bool */
    private $isSurchargePercental;

    /** @var int|null */
    private $ottoComponent;

    /** @var DateTimeInterface|null */
    private $updatedAt;

    /** @var Name[] */
    private $names = [];

    public function __construct(array $data)
    {
        // The documentation completely differs from what is actually received
        $this->id = (int)$data['id'];
        $this->backendName = $this->getStringProperty('backendName', $data);
        $this->orderPropertyGroupingType = $this->getStringProperty('orderPropertyGroupingType', $data);
        $this->isSurchargePercental = $this->getBoolProperty('isSurchargePercental', $data, false);
        $this->ottoComponent = $this->getIntProperty('ottoComponent', $data);
        $this->updatedAt = $this->getDateTimeProperty('updatedAt', $data);

        if (!empty($data['names'])) {
            foreach ($data['names'] as $name) {
                $this->names[] = new Name($name);
            }
        }
    }

    public function getData(): array
    {
        $data = [];

        $names = [];
        foreach ($this->names as $name) {
            $names[] = $name->getData();
        }

        if (!empty($names)) {
            $data['names'] = $names;
        }

        return array_merge($data, [
            'id' => $this->id,
            'backendName' => $this->backendName,
            'orderPropertyGroupingType' => $this->orderPropertyGroupingType,
            'isSurchargePercental' => $this->isSurchargePercental,
            'ottoComponent' => $this->ottoComponent,
            'updatedAt' => $this->updatedAt->format(DATE_ATOM),
        ]);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBackendName(): ?string
    {
        return $this->backendName;
    }

    public function getOrderPropertyGroupingType(): ?string
    {
        return $this->orderPropertyGroupingType;
    }

    public function isSurchargePercental(): bool
    {
        return $this->isSurchargePercental;
    }

    public function getOttoComponent(): ?int
    {
        return $this->ottoComponent;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @return Name[]
     */
    public function getNames(): array
    {
        return $this->names;
    }
}
