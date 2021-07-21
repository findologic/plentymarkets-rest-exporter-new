<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response\Collection;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Entity;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Pim\Property\PropertyValue;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Property\Selection;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Property\Selection\Relation\RelationValue;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\PropertySelection;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\IterableResponse;
use FINDOLOGIC\PlentyMarketsRestExporter\Translator;

class PropertySelectionResponse extends IterableResponse implements CollectionInterface, IterableResponseInterface
{
    use EntityCollection;

    /** @var Selection[] */
    private $selections;

    /**
     * @param Selection[] $selections
     */
    public function __construct(
        int $page,
        int $totalsCount,
        bool $isLastPage,
        array $selections,
        int $lastPageNumber = 1,
        int $firstOnPage = 1,
        int $lastOnPage = 1,
        int $itemsPerPage = 100
    ) {
        $this->page = $page;
        $this->totalsCount = $totalsCount;
        $this->isLastPage = $isLastPage;
        $this->selections = $selections;
        $this->lastPageNumber = $lastPageNumber;
        $this->firstOnPage = $firstOnPage;
        $this->lastOnPage = $lastOnPage;
        $this->itemsPerPage = $itemsPerPage;
    }

    /**
     * @return Selection|null
     */
    public function first(): ?Entity
    {
        return $this->getFirstEntity($this->selections);
    }

    /**
     * @return Selection[]
     */
    public function all(): array
    {
        return $this->selections;
    }

    /**
     * @param array $criteria
     * @return Selection|null
     */
    public function findOne(array $criteria): ?Entity
    {
        return $this->findOneEntityByCriteria($this->selections, $criteria);
    }

    /**
     * @param array $criteria
     * @return Selection[]
     */
    public function find(array $criteria): array
    {
        return $this->findEntitiesByCriteria($this->selections, $criteria);
    }

    /**
     * @param PropertyValue[] $selections
     * @return string[]
     */
    public function getPropertySelectionValues(int $propertyId, array $selections, string $lang): array
    {
        $propertySelections = [];
        foreach ($selections as $selection) {
            $propertySelections[] = $this->findOne(
                [
                    'propertyId' => $propertyId,
                    'relation' => [
                        'selectionRelationId' => (int)$selection->getValue()
                    ]
                ]
            );
        }

        /** @var Selection[] $propertySelections */
        $propertySelections = array_filter($propertySelections);

        $values = [];
        foreach ($propertySelections as $propertySelection) {
            /** @var RelationValue|null $relationValue */
            $relationValue = Translator::translate($propertySelection->getRelation()->getRelationValues(), $lang);
            if ($relationValue) {
                $values[$propertySelection->getId()] = $relationValue->getValue();
            }
        }

        return $values;
    }
}
