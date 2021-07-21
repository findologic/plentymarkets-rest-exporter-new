<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Property\Amazon;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Property\Group;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Property\Name;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Property\Option;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Property\Selection;

class Property extends Entity
{
    /** @var int  */
    private $id;

    /** @var string  */
    private $cast;

    /** @var string  */
    private $typeIdentifier;

    /** @var int  */
    private $position;

    /** @var string  */
    private $createdAt;

    /** @var string  */
    private $updatedAt;

    /** @var string  */
    private $propertyId;

    /** @var string  */
    private $propertyGroupId;

    /** @var Group[] */
    private $groups = [];

    /** @var array */
    private $availabilities = [];

    /** @var Name[] */
    private $names = [];

    /** @var Option[] */
    private $options = [];

    /** @var array */
    private $markets = [];

    /** @var Selection[] */
    private $selections = [];

    /** @var Amazon[] */
    private $amazons = [];

    public function __construct(array $data)
    {
        // The documentation completely differs from what is actually received
        $this->id = (int)$data['id'];
        $this->cast = (string)$data['cast'];
        $this->typeIdentifier = (string)$data['typeIdentifier'];
        $this->position = (int)$data['position'];
        $this->createdAt = (string)$data['createdAt'];
        $this->updatedAt = (string)$data['updatedAt'];
        $this->propertyId = (string)$data['propertyId'];
        $this->propertyGroupId = (string)$data['propertyGroupId'];

        if (isset($data['availabilities'])) {
            $this->availabilities = $data['availabilities']; // Unknown structure - undocumented, got only empty arrays.
        }

        if (isset($data['markets'])) {
            $this->markets = $data['markets']; // Unknown structure - undocumented, got only empty arrays.
        }

        if (!empty($data['groups'])) {
            foreach ($data['groups'] as $group) {
                $this->groups[] = new Group($group);
            }
        }

        if (!empty($data['names'])) {
            foreach ($data['names'] as $name) {
                $this->names[] = new Name($name);
            }
        }

        if (!empty($data['options'])) {
            foreach ($data['options'] as $option) {
                $this->options[] = new Option($option);
            }
        }

        if (!empty($data['selections'])) {
            foreach ($data['selections'] as $selection) {
                $this->selections[] = new Selection($selection);
            }
        }

        if (!empty($data['amazons'])) {
            foreach ($data['amazons'] as $amazon) {
                $this->amazons[] = new Amazon($amazon);
            }
        }
    }

    public function getData(): array
    {
        $groups = [];
        foreach ($this->groups as $group) {
            $groups[] = $group->getData();
        }

        $names = [];
        foreach ($this->names as $name) {
            $names[] = $name->getData();
        }

        $options = [];
        foreach ($this->options as $option) {
            $options[] = $option->getData();
        }

        $selections = [];
        foreach ($this->selections as $selection) {
            $selections[] = $selection->getData();
        }

        $amazons = [];
        foreach ($this->amazons as $amazon) {
            $amazons[] = $amazon->getData();
        }

        return [
            'id' => $this->id,
            'cast' => $this->cast,
            'typeIdentifier' => $this->typeIdentifier,
            'position' => $this->position,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'propertyId' => $this->propertyId,
            'propertyGroupId' => $this->propertyGroupId,
            'availabilities' => $this->availabilities,
            'markets' => $this->markets,
            'groups' => $groups,
            'names' => $names,
            'options' => $options,
            'selections' => $selections,
            'amazons' => $amazons
        ];
    }

    public function getId(): int
    {
        // Undocumented
        return $this->id;
    }

    public function getCast(): string
    {
        // Undocumented
        return $this->cast;
    }

    public function getTypeIdentifier(): string
    {
        // Undocumented
        return $this->typeIdentifier;
    }

    public function getPosition(): int
    {
        // Undocumented
        return $this->position;
    }

    public function getCreatedAt(): string
    {
        // Undocumented
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        // Undocumented
        return $this->updatedAt;
    }

    public function getPropertyId(): string
    {
        // Undocumented
        return $this->propertyId;
    }

    public function getPropertyGroupId(): string
    {
        // Undocumented
        return $this->propertyGroupId;
    }

    /**
     * @return Group[]
     */
    public function getGroups(): array
    {
        // Undocumented - the properties may not match the received data exactly
        return $this->groups;
    }

    public function getAvailabilities(): array
    {
        // Undocumented
        return $this->availabilities;
    }

    /**
     * @return Name[]
     */
    public function getNames(): array
    {
        // Undocumented - the properties may not match the received data exactly
        return $this->names;
    }

    /**
     * @return Option[]
     */
    public function getOptions(): array
    {
        // Undocumented - the properties may not match the received data exactly
        return $this->options;
    }

    public function getMarkets(): array
    {
        // Undocumented
        return $this->markets;
    }

    /**
     * @return Selection[]
     */
    public function getSelections(): array
    {
        // Undocumented - the properties may not match the received data exactly
        return $this->selections;
    }

    /**
     * @return Amazon[]
     */
    public function getAmazons(): array
    {
        // Undocumented - the properties may not match the received data exactly
        return $this->amazons;
    }
}
