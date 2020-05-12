<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response\Collection;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Entity;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\PropertySelection;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\IterableResponse;

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
}
