<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Wrapper;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTime;
use FINDOLOGIC\Export\Data\Attribute;
use FINDOLOGIC\Export\Data\Item;
use FINDOLOGIC\Export\Data\Keyword;
use FINDOLOGIC\Export\Data\Ordernumber;
use FINDOLOGIC\Export\Data\Property;
use FINDOLOGIC\Export\Exporter;
use FINDOLOGIC\PlentyMarketsRestExporter\Config;
use FINDOLOGIC\PlentyMarketsRestExporter\RegistryService;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Item as ProductEntity;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Item\Text;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Pim\Variation as PimVariation;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\WebStore\Configuration as StoreConfiguration;
use FINDOLOGIC\PlentyMarketsRestExporter\Translator;
use FINDOLOGIC\PlentyMarketsRestExporter\Utils;

class Product
{
    public const WRAP_MODE_DEFAULT = 0;

    public const WRAP_MODE_SEPARATE_VARIATIONS = 1;

    /** @var Item */
    private $item;

    /** @var Config */
    private $config;

    /** @var RegistryService */
    private $registryService;

    /** @var ProductEntity */
    private $productEntity;

    /** @var PimVariation[] */
    private $variationEntities;

    /** @var string|null */
    private $reason = null;

    /** @var StoreConfiguration  */
    private $storeConfiguration;

    /** @var Exporter */
    private $exporter;

    private int $wrapMode;

    /**
     * @param PimVariation[] $variationEntities
     */
    public function __construct(
        Exporter $exporter,
        Config $config,
        StoreConfiguration $storeConfiguration,
        RegistryService $registryService,
        ProductEntity $productEntity,
        array $variationEntities,
        int $wrapMode = self::WRAP_MODE_DEFAULT
    ) {
        $this->exporter = $exporter;
        $this->item = $exporter->createItem($productEntity->getId());
        $this->config = $config;
        $this->productEntity = $productEntity;
        $this->registryService = $registryService;
        $this->variationEntities = $variationEntities;
        $this->storeConfiguration = $storeConfiguration;
        $this->wrapMode = $wrapMode;
    }

    /**
     * Returns a libflexport-consumable Item.
     * Returns null if
     *   * The data can not be parsed properly.
     *   * The products are not available for the current store.
     *   * Settings do not allow the product to be exported.
     *   * Product has no variants.
     *
     * @see Product::getReason() To get the reason why the product could not be exported.
     *
     * @return Item|null
     */
    public function processProductData(): ?Item
    {
        $this->item->addPrice(0);

        if (count($this->variationEntities) === 0) {
            $this->reason = 'Product has no variations.';

            return null;
        }

        $variationCount = $this->processVariations();
        if ($variationCount === 0 && $this->config->isExportUnavailableVariations()) {
            $variationCount = $this->processVariations(false);
        }
        if ($variationCount === 0) {
            $this->reason =
                'All assigned variations are not exportable (inactive, no longer available, no categories etc.)';

            return null;
        }

        $this->setTexts();

        $this->item->addDateAdded(new DateTime($this->productEntity->getCreatedAt()));
        $this->addManufacturer();
        $this->addFreeTextFields();

        $variationIdProperty = new Property('variation_id');
        $variationIdProperty->addValue((string)$this->productEntity->getMainVariationId());
        $this->item->addProperty($variationIdProperty);

        $priceId = $this->registryService->getPriceId();
        $priceIdProperty = new Property('price_id');
        $priceIdProperty->addValue((string)$priceId);
        $this->item->addProperty($priceIdProperty);

        return $this->item;
    }

    /**
     * May return the reason why a product wasn't able to get exported.
     *
     * @codeCoverageIgnore
     *
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    protected function setTexts(): void
    {
        if (!$this->storeConfiguration->getDisplayItemName()) {
            return;
        }

        $textGetter = 'getName' . $this->storeConfiguration->getDisplayItemName();

        /** @var Text[] $texts */
        $texts = Translator::translateMultiple($this->productEntity->getTexts(), $this->config->getLanguage());
        foreach ($texts as $text) {
            $name = $text->$textGetter();
            if (trim($name) !== '') {
                $this->item->addName($name);
            }

            if (trim($text->getShortDescription()) !== '') {
                $this->item->addSummary($text->getShortDescription());
            }
            if (trim($text->getDescription()) !== '') {
                $this->item->addDescription($text->getDescription());
            }
            if (trim($text->getKeywords()) !== '') {
                $this->item->addKeyword(new Keyword($text->getKeywords()));
            }

            $this->item->addUrl($this->buildProductUrl($text->getUrlPath()));
        }
    }

    protected function processVariations(bool $checkAvailability = true): int
    {
        $hasImage = false;
        $hasCategories = false;
        $variationsProcessed = 0;
        $prices = [];
        $insteadPrices = [];
        $ordernumbers = [];
        $highestPosition = 0;
        $baseUnit = null;
        $packageSize = null;

        foreach ($this->variationEntities as $variationEntity) {
            if (!$this->shouldExportVariation($variationEntity, $checkAvailability)) {
                continue;
            }

            $variation = new Variation($this->config, $this->registryService, $variationEntity);
            $variation->processData();

            if (!$hasImage && $variation->getImage()) {
                $this->item->addImage($variation->getImage());
                $hasImage = true;
            }

            foreach ($variation->getGroups() as $group) {
                $this->item->addUsergroup($group);
            }

            foreach ($variation->getTags() as $tag) {
                $this->item->addKeyword($tag);
            }

            if (!$packageSize) {
                $packageSize = $variation->getPackageSize();
            }

            if (!$baseUnit) {
                $baseUnit = $variation->getBaseUnit();
            }

            $position = $variation->getPosition();
            if ($variation->isMain() || !$this->item->getSort()->getValues()) {
                // Only add sort in case the variation has a position.
                if ($variation->getPosition()) {
                    $this->item->addSort($variation->getPosition());
                }
            }
            $highestPosition = $position > $highestPosition ? $position : $highestPosition;

            $ordernumbers = array_merge($ordernumbers, $this->getVariationOrdernumbers($variation));

            foreach ($variation->getAttributes() as $attribute) {
                $this->item->addMergedAttribute($attribute);
            }

            $prices[] = $variation->getPrice();
            $insteadPrices[] = $variation->getInsteadPrice();

            if ($variation->hasCategories()) {
                $hasCategories = true;
            }

            $variationsProcessed++;
        }

        // If no children have categories, we're skipping this product.
        if (!$hasCategories) {
            return 0;
        }

        // VatRate should be set from the last variation, therefore this code outside the foreach loop
        if (isset($variation) && $variation->getVatRate() !== null) {
            $this->item->setTaxRate($variation->getVatRate());
        }

        if ($prices) {
            $this->item->addPrice(min($prices));
        }

        if ($insteadPrices) {
            $this->item->setInsteadPrice(min($insteadPrices));
        }

        $ordernumbers = array_unique($ordernumbers);
        foreach ($ordernumbers as $ordernumber) {
            $this->addOrdernumber($ordernumber);
        }

        $salesFrequency = $this->storeConfiguration->getItemSortByMonthlySales() ? $highestPosition : 0;
        $this->item->addSalesFrequency($salesFrequency);

        if ($baseUnit) {
            $baseUnitProperty = new Property('base_unit');
            $baseUnitProperty->addValue((string)$baseUnit);
            $this->item->addProperty($baseUnitProperty);
        }

        if ($packageSize) {
            $packageSizeProperty = new Property('package_size');
            $packageSizeProperty->addValue((string)$packageSize);
            $this->item->addProperty($packageSizeProperty);
        }

        return $variationsProcessed;
    }

    protected function shouldExportVariation(PimVariation $variation, bool $checkAvailability = true): bool
    {
        if (!$variation->getBase()->isActive()) {
            return false;
        }

        if ($variation->getBase()->getAutomaticListVisibility() < 1) {
            return false;
        }

        /** @var CarbonInterface|null $availableUntil */
        $availableUntil = $variation->getBase()->getAvailableUntil();
        if ($availableUntil !== null && $availableUntil->lessThan(Carbon::now())) {
            return false;
        }

        if ($checkAvailability && $this->config->getAvailabilityId() !== null) {
            if ($variation->getBase()->getAvailability() === $this->config->getAvailabilityId()) {
                return false;
            }
        }

        if ($variation->hasExportExclusionTag($this->config->getLanguage())) {
            return false;
        }

        return true;
    }

    protected function addManufacturer(): void
    {
        $manufacturerId = $this->productEntity->getManufacturerId();
        if (!Utils::isEmpty($manufacturerId)) {
            $manufacturer = $this->registryService->getManufacturer($manufacturerId);

            if (Utils::isEmpty($manufacturer->getName())) {
                return;
            }

            $vendorAttribute = new Attribute('vendor', [$manufacturer->getName()]);
            $this->item->addMergedAttribute($vendorAttribute);
        }
    }

    protected function addFreeTextFields(): void
    {
        foreach (range(1, 20) as $field) {
            $fieldName = 'free' . (string)$field;
            $getter = 'getFree' . (string)$field;

            $value = (string)$this->productEntity->{$getter}();
            if (trim($value) === '') {
                continue;
            }

            $this->item->addMergedAttribute(new Attribute($fieldName, [$value]));
        }
    }

    private function buildProductUrl(string $urlPath): string
    {
        if ($this->shouldUseCallistoUrl()) {
            return sprintf(
                '%s://%s%s/%s/a-%s',
                $this->config->getProtocol(),
                $this->config->getDomain(),
                $this->getLanguageUrlPrefix(),
                trim($urlPath, '/'),
                $this->productEntity->getId()
            );
        }

        return sprintf(
            '%s://%s%s/%s_%s_%s',
            $this->config->getProtocol(),
            $this->config->getDomain(),
            $this->getLanguageUrlPrefix(),
            trim($urlPath, '/'),
            $this->productEntity->getId(),
            $this->wrapMode ? $this->variationEntities[0]->getId() : $this->productEntity->getMainVariationId()
        );
    }

    private function shouldUseCallistoUrl(): bool
    {
        $config = $this->registryService->getPluginConfigurations('Ceres');

        if (!isset($config['global.enableOldUrlPattern'])) {
            return true;
        }

        return filter_var($config['global.enableOldUrlPattern'], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Returns the language URL prefix. This may be relevant for multiple channels.
     * An empty string may be returned if the default store language is already the exported language.
     *
     * @return string
     */
    private function getLanguageUrlPrefix(): ?string
    {
        if ($this->isDefaultLanguage() || !$this->isLanguageAvailable()) {
            return '';
        }

        return '/' . strtolower($this->config->getLanguage());
    }

    /**
     * Returns true if the language of the webStore is the default language. Otherwise false may be returned.
     *
     * @return bool
     */
    private function isDefaultLanguage(): bool
    {
        $defaultStoreLanguage = $this->storeConfiguration->getDefaultLanguage();

        return strtoupper($defaultStoreLanguage) === strtoupper($this->config->getLanguage());
    }

    private function isLanguageAvailable(): bool
    {
        $availableLanguages = array_map('strtoupper', $this->storeConfiguration->getLanguageList());

        return (in_array(strtoupper($this->config->getLanguage()), $availableLanguages));
    }

    private function getVariationOrdernumbers(Variation $variation): array
    {
        $ordernumberGetters = ['number', 'model', 'id', 'itemId'];
        $ordernumbers = [];

        foreach ($ordernumberGetters as $field) {
            $getter = 'get' . ucfirst($field);
            $ordernumbers[] = (string)$variation->{$getter}();
        }

        foreach ($variation->getBarcodes() as $barcode) {
            $ordernumbers[] = $barcode;
        }

        return $ordernumbers;
    }

    private function addOrdernumber(string $ordernumber)
    {
        if (trim($ordernumber) === '') {
            return;
        }

        $this->item->addOrdernumber(new Ordernumber($ordernumber));
    }
}
