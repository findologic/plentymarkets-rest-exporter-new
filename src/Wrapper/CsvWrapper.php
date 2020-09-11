<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Wrapper;

use FINDOLOGIC\Export\Data\Item;
use FINDOLOGIC\Export\Exporter;
use FINDOLOGIC\PlentyMarketsRestExporter\Config;
use FINDOLOGIC\PlentyMarketsRestExporter\Registry;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Collection\ItemResponse;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Collection\ItemVariationResponse;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\WebStore;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\WebStore\Configuration as StoreConfiguration;

class CsvWrapper extends Wrapper
{
    /** @var string */
    protected $exportPath;

    /** @var Exporter */
    private $exporter;

    /** @var Config */
    private $config;

    /** @var Registry */
    private $registry;

    /** @var StoreConfiguration */
    private $storeConfiguration;

    public function __construct(string $path, Exporter $exporter, Config $config, Registry $registry)
    {
        $this->exportPath = $path;
        $this->exporter = $exporter;
        $this->config = $config;
        $this->registry = $registry;
    }

    /**
     * @inheritDoc
     */
    public function wrap(
        int $start,
        int $total,
        ItemResponse $products,
        ItemVariationResponse $variations
    ): void {
        /** @var Item[] $items */
        $items = [];
        foreach ($products->all() as $product) {
            $productVariations = $variations->find(['itemId' => $product->getId()]);

            $productWrapper = new Product(
                $this->exporter,
                $this->config,
                $this->getStoreConfiguration(),
                $this->registry,
                $product,
                $productVariations
            );
            $item = $productWrapper->processProductData();

            if (!$item) {
                continue;
            }

            $items[] = $item;
        }

        $this->exporter->serializeItemsToFile($this->exportPath, $items, $start, count($items), $total);
    }

    /**
     * @codeCoverageIgnore
     */
    public function setExportPath(string $path): self
    {
        $this->exportPath = $path;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getExportPath(): string
    {
        return $this->exportPath;
    }

    private function getStoreConfiguration(): StoreConfiguration
    {
        if (!$this->storeConfiguration) {
            /** @var WebStore $webStore */
            $webStore = $this->registry->get('webStore');
            $this->storeConfiguration = $webStore->getConfiguration();
        }

        return $this->storeConfiguration;
    }
}
