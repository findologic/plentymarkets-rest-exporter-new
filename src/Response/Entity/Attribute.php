<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Attribute\Name;

class Attribute extends Entity
{
    /** @var int */
    private $id;

    /** @var string */
    private $backendName;

    /** @var int */
    private $position;

    /** @var bool */
    private $isSurchargePercental;

    /** @var bool */
    private $isLinkableToImage;

    /** @var string */
    private $amazonAttribute;

    /** @var string */
    private $fruugoAttribute;

    /** @var int */
    private $pixmaniaAttribute;

    /** @var string */
    private $ottoAttribute;

    /** @var string */
    private $googleShoppingAttribute;

    /** @var int */
    private $neckermannAtEpAttribute;

    /** @var string */
    private $typeOfSelectionInOnlineStore;

    /** @var int */
    private $laRedouteAttribute;

    /** @var bool */
    private $isGroupable;

    /** @var string */
    private $updatedAt;

    /** @var Name[] */
    private $names = [];

    public function __construct(array $data)
    {
        $this->id = (int)$data['id'];
        $this->backendName = (string)$data['backendName'];
        $this->position = (int)$data['position'];
        $this->isSurchargePercental = (bool)$data['isSurchargePercental'];
        $this->isLinkableToImage = (bool)$data['isLinkableToImage'];
        $this->amazonAttribute = (string)$data['amazonAttribute'];
        $this->fruugoAttribute = (string)$data['fruugoAttribute'];
        $this->pixmaniaAttribute = (int)$data['pixmaniaAttribute'];
        $this->ottoAttribute = (string)$data['ottoAttribute'];
        $this->googleShoppingAttribute = (string)$data['googleShoppingAttribute'];
        $this->neckermannAtEpAttribute = (int)$data['neckermannAtEpAttribute'];
        $this->typeOfSelectionInOnlineStore = (string)$data['typeOfSelectionInOnlineStore'];
        $this->laRedouteAttribute = (int)$data['laRedouteAttribute'];
        $this->isGroupable = (bool)$data['isGroupable'];
        $this->updatedAt = (string)$data['updatedAt'];

        if (!empty($data['attributeNames'])) {
            foreach ($data['attributeNames'] as $name) {
                $this->names[] = new Name($name);
            }
        }
    }

    public function getData(): array
    {
        $names = [];
        foreach ($this->names as $name) {
            $names[] = $name->getData();
        }

        return [
            'id' => $this->id,
            'backendName' => $this->backendName,
            'position' => $this->position,
            'isSurchargePercental' => $this->isSurchargePercental,
            'isLinkableToImage' => $this->isLinkableToImage,
            'amazonAttribute' => $this->amazonAttribute,
            'fruugoAttribute' => $this->fruugoAttribute,
            'pixmaniaAttribute' => $this->pixmaniaAttribute,
            'ottoAttribute' => $this->ottoAttribute,
            'googleShoppingAttribute' => $this->googleShoppingAttribute,
            'neckermannAtEpAttribute' => $this->neckermannAtEpAttribute,
            'typeOfSelectionInOnlineStore' => $this->typeOfSelectionInOnlineStore,
            'laRedouteAttribute' => $this->laRedouteAttribute,
            'isGroupable' => $this->isGroupable,
            'updatedAt' => $this->updatedAt,
            'attributeNames' => $names
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getBackendName(): string
    {
        return $this->backendName;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function isSurchargePercental(): bool
    {
        return $this->isSurchargePercental;
    }

    public function isLinkableToImage(): bool
    {
        return $this->isLinkableToImage;
    }

    public function getAmazonAttribute(): string
    {
        return $this->amazonAttribute;
    }

    public function getFruugoAttribute(): string
    {
        return $this->fruugoAttribute;
    }

    public function getPixmaniaAttribute(): int
    {
        return $this->pixmaniaAttribute;
    }

    public function getOttoAttribute(): string
    {
        return $this->ottoAttribute;
    }

    public function getGoogleShoppingAttribute(): string
    {
        return $this->googleShoppingAttribute;
    }

    public function getNeckermannAtEpAttribute(): int
    {
        return $this->neckermannAtEpAttribute;
    }

    public function getTypeOfSelectionInOnlineStore(): string
    {
        return $this->typeOfSelectionInOnlineStore;
    }

    public function getLaRedouteAttribute(): int
    {
        return $this->laRedouteAttribute;
    }

    public function isGroupable(): bool
    {
        return $this->isGroupable;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    /**
     * @return Name[]
     */
    public function getNames(): array
    {
        // Undocumented - the properties may not match the received data exactly
        return $this->names;
    }
}
