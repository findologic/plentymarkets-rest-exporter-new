<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Wrapper;

use FINDOLOGIC\Export\Data\Item;
use FINDOLOGIC\Export\Exporter;
use FINDOLOGIC\PlentyMarketsRestExporter\Config;
use FINDOLOGIC\PlentyMarketsRestExporter\Logger\DummyLogger;
use FINDOLOGIC\PlentyMarketsRestExporter\RegistryService;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Collection\ItemResponse;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Collection\PimVariationResponse;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Item as ProductEntity;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Item as ProductResponseItem;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Pim\Variation;
use Psr\Log\LoggerInterface;

class CsvWrapper extends Wrapper
{
    public const VARIANT_MODE_ALL = 'all';

    /** @var string */
    protected $exportPath;

    protected ?string $fileNamePrefix;

    /** @var Exporter */
    private $exporter;

    /** @var Config */
    private $config;

    /** @var RegistryService */
    private $registryService;

    /** @var LoggerInterface */
    private $internalLogger;

    /** @var LoggerInterface */
    private $customerLogger;

    public function __construct(
        string $path,
        ?string $fileNamePrefix,
        Exporter $exporter,
        Config $config,
        RegistryService $registryService,
        ?LoggerInterface $internalLogger,
        ?LoggerInterface $customerLogger
    ) {
        $this->exportPath = $path;
        $this->fileNamePrefix = $fileNamePrefix;
        $this->exporter = $exporter;
        $this->config = $config;
        $this->registryService = $registryService;
        $this->internalLogger = $internalLogger ?? new DummyLogger();
        $this->customerLogger = $customerLogger ?? new DummyLogger();
    }

    /**
     * @inheritDoc
     */
    public function wrap(
        int $start,
        int $total,
        ItemResponse $products,
        PimVariationResponse $variations
    ): void {
        /** @var Item[] $items */
        $items = [];
        foreach ($products->all() as $product) {
            if (!$this->shouldExportProduct($product, $variations)) {
                $this->customerLogger->notice(
                    sprintf(
                        'Product with id %d was skipped, as it contains the tag "findologic-exclude"',
                        $product->getId()
                    )
                );

                continue;
            }

            $productVariations = $variations->find([
                'base' => [
                    'itemId' => $product->getId()
                ]
            ]);

            list($groupedVariations, $separateVariations) = $this->splitVariationsByGroupability($productVariations);

            foreach ($separateVariations as $separateVariation) {
                if ($item = $this->wrapItem($product, [$separateVariation], Product::WRAP_MODE_SEPARATE_VARIATIONS)) {
                    $items[] = $item;
                }
            }

            if ($item = $this->wrapItem($product, $groupedVariations, Product::WRAP_MODE_DEFAULT)) {
                $items[] = $item;
            }
        }

        if ($this->fileNamePrefix) {
            $this->exporter->setFileNamePrefix($this->fileNamePrefix);
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

    /**
     * @param Variation[] $productVariations
     */
    private function wrapItem(
        ProductEntity $product,
        array $productVariations,
        int $wrapMode
    ): ?Item {
        $productWrapper = new Product(
            $this->exporter,
            $this->config,
            $this->registryService->getWebStore()->getConfiguration(),
            $this->registryService,
            $product,
            $productVariations,
            $wrapMode
        );
        $item = $productWrapper->processProductData();

        if (!$item) {
            $this->customerLogger->warning(sprintf(
                'Product with id %d could not be exported. Reason: %s',
                $product->getId(),
                $productWrapper->getReason()
            ));

            return null;
        }

        return $item;
    }

    /**
     * Splits product variations into those that should be grouped into a single item and those that should be
     * exported as separate items. Product variations that have groupable attributes are exported separately if the
     * "Item view" > "Show variations by type" Ceres config is set to "All".
     *
     * @param Variation[] $productVariations
     */
    private function splitVariationsByGroupability(array $productVariations): array
    {
        if (!$this->shouldExportGroupableAttributeVariantsSeparately()) {
            return [$productVariations, []];
        }

        $groupedVariations = [];
        $separateVariations = [];

        foreach ($productVariations as $variation) {
            if ($this->hasVariationGroupableAttributes($variation)) {
                $separateVariations[] = $variation;
                continue;
            }

            $groupedVariations[] = $variation;
        }

        return [$groupedVariations, $separateVariations];
    }

    private function hasVariationGroupableAttributes(Variation $variation): bool
    {
        $attributeValues = $variation->getAttributeValues();

        foreach ($attributeValues as $attributeValue) {
            $attribute = $this->registryService->getAttribute($attributeValue->getId());

            if ($attribute && $attribute->isGroupable()) {
                return true;
            }
        }

        return false;
    }

    private function shouldExportGroupableAttributeVariantsSeparately(): bool
    {
        $config = $this->registryService->getPluginConfigurations('Ceres');

        if (!isset($config['item.variation_show_type'])) {
            return true;
        }

        return $config['item.variation_show_type'] === self::VARIANT_MODE_ALL;
    }

    private function shouldExportProduct(ProductResponseItem $product, PimVariationResponse $variations): bool
    {
        $mainVariation = $variations->findOne([
            'base' => [
                'itemId' => $product->getId(),
                'isMain' => true
            ]
        ]);

        if ($mainVariation && $mainVariation->hasExportExclusionTag($this->config->getLanguage())) {
            return false;
        }

        return true;
    }
}
