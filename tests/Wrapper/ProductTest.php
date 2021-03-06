<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Tests\Wrapper;

use Carbon\Carbon;
use DateTime;
use FINDOLOGIC\Export\Exporter;
use FINDOLOGIC\PlentyMarketsRestExporter\Config;
use FINDOLOGIC\PlentyMarketsRestExporter\Parser\AttributeParser;
use FINDOLOGIC\PlentyMarketsRestExporter\Parser\CategoryParser;
use FINDOLOGIC\PlentyMarketsRestExporter\Parser\ManufacturerParser;
use FINDOLOGIC\PlentyMarketsRestExporter\Parser\PimVariationsParser;
use FINDOLOGIC\PlentyMarketsRestExporter\Parser\UnitParser;
use FINDOLOGIC\PlentyMarketsRestExporter\Parser\VatParser;
use FINDOLOGIC\PlentyMarketsRestExporter\Parser\WebStoreParser;
use FINDOLOGIC\PlentyMarketsRestExporter\RegistryService;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Item;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Item\Text;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Pim\Property\Base;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Pim\Variation;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\WebStore;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\WebStore\Configuration;
use FINDOLOGIC\PlentyMarketsRestExporter\Tests\Helper\ConfigHelper;
use FINDOLOGIC\PlentyMarketsRestExporter\Tests\Helper\ResponseHelper;
use FINDOLOGIC\PlentyMarketsRestExporter\Wrapper\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    use ConfigHelper;
    use ResponseHelper;

    private const AVAILABLE_PROPERTIES = ['price_id', 'variation_id', 'base_unit', 'package_size'];

    /** @var Exporter|MockObject */
    private $exporterMock;

    /** @var Config */
    private $config;

    /** @var Item|MockObject */
    private $itemMock;

    /** @var Configuration|MockObject */
    private $storeConfigurationMock;

    /** @var RegistryService|MockObject */
    private $registryServiceMock;

    /** @var Variation[]|MockObject[] */
    private $variationEntityMocks = [];

    protected function setUp(): void
    {
        $this->exporterMock = $this->getMockBuilder(Exporter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->getDefaultConfig();
        $this->itemMock = $this->getMockBuilder(Item::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeConfigurationMock = $this->getMockBuilder(Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->registryServiceMock = $this->getMockBuilder(RegistryService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $standardVatResponse = $this->getMockResponse('VatResponse/standard_vat.json');
        $standardVat = VatParser::parseSingleEntityResponse($standardVatResponse);
        $this->registryServiceMock->expects($this->any())->method('getStandardVat')->willReturn($standardVat);

        $categoryResponse = $this->getMockResponse('CategoryResponse/one.json');
        $parsedCategoryResponse = CategoryParser::parse($categoryResponse);

        $this->registryServiceMock->expects($this->any())
            ->method('getCategory')
            ->willReturn($parsedCategoryResponse->first());
    }

    public function testProductWithoutVariationsIsNotExported(): void
    {
        $product = $this->getProduct();
        $item = $product->processProductData();

        $this->assertNull($item);
        $this->assertSame('Product has no variations.', $product->getReason());
    }

    public function testProductWithOnlyInactiveVariationsIsNotExported(): void
    {
        $variationMock = $this->getMockBuilder(Variation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $baseMock = $this->getMockBuilder(Base::class)
            ->disableOriginalConstructor()
            ->getMock();

        $variationMock->expects($this->once())->method('getBase')->willReturn($baseMock);
        $baseMock->expects($this->once())->method('isActive')->willReturn(false);

        $this->variationEntityMocks[] = $variationMock;

        $product = $this->getProduct();
        $item = $product->processProductData();

        $this->assertNull($item);
        $this->assertSame(
            'All assigned variations are not exportable (inactive, no longer available, no categories etc.)',
            $product->getReason()
        );
    }

    public function testProductWithInvisibleVariationsIsNotExported(): void
    {
        $variationMock = $this->getMockBuilder(Variation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $baseMock = $this->getMockBuilder(Base::class)
            ->disableOriginalConstructor()
            ->getMock();

        $variationMock->expects($this->exactly(2))->method('getBase')->willReturn($baseMock);
        $baseMock->expects($this->once())->method('isActive')->willReturn(true);
        $baseMock->expects($this->once())->method('getAutomaticListVisibility')
            ->willReturn(0);

        $this->variationEntityMocks[] = $variationMock;

        $product = $this->getProduct();
        $item = $product->processProductData();

        $this->assertNull($item);
        $this->assertSame(
            'All assigned variations are not exportable (inactive, no longer available, no categories etc.)',
            $product->getReason()
        );
    }

    public function testProductWithNoLongerAvailableVariationsIsNotExported(): void
    {
        $variationMock = $this->getMockBuilder(Variation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $baseMock = $this->getMockBuilder(Base::class)
            ->disableOriginalConstructor()
            ->getMock();

        $variationMock->expects($this->exactly(3))->method('getBase')->willReturn($baseMock);
        $baseMock->expects($this->once())->method('isActive')
            ->willReturn(true);
        $baseMock->expects($this->once())->method('getAutomaticListVisibility')
            ->willReturn(3);
        $baseMock->expects($this->once())->method('getAvailableUntil')
            ->willReturn(Carbon::now()->subSeconds(3));

        $this->variationEntityMocks[] = $variationMock;

        $product = $this->getProduct();
        $item = $product->processProductData();

        $this->assertNull($item);
        $this->assertSame(
            'All assigned variations are not exportable (inactive, no longer available, no categories etc.)',
            $product->getReason()
        );
    }

    public function testProductWithAllVariationsMatchingConfigurationAvailabilityIdAreNotExported()
    {
        $this->exporterMock = $this->getExporter();

        $this->config->setAvailabilityId(5);

        $rawVariations = $this->getMockResponse('Pim/Variations/variations_with_5_for_availability_id.json');
        $variations = PimVariationsParser::parse($rawVariations);
        $this->variationEntityMocks = $variations->all();

        $product = $this->getProduct();
        $item = $product->processProductData();

        $this->assertNull($item);
        $this->assertSame(
            'All assigned variations are not exportable (inactive, no longer available, no categories etc.)',
            $product->getReason()
        );
    }

    public function testProductWithAllVariationsMatchingConfigurationAvailabilityAreExportedIfConfigured()
    {
        $this->exporterMock = $this->getExporter();

        $this->config->setAvailabilityId(5);
        $this->config->setExportUnavailableVariations(true);

        $rawVariations = $this->getMockResponse('Pim/Variations/variations_with_5_for_availability_id.json');
        $variations = PimVariationsParser::parse($rawVariations);
        $this->variationEntityMocks = $variations->all();

        $product = $this->getProduct();
        $item = $product->processProductData();

        $this->assertNotNull($item);
        // TODO: check item's orderNumbers directly once order numbers getter is implemented
        $line = $item->getCsvFragment();
        $columnValues = explode("\t", $line);
        $this->assertEquals('S-000813-C|modeeeel|1004|106|3213213213213|101|1005|107', $columnValues[1]);
    }

    public function testMatchingAvailabilityExportSettingDoesNotOverrideOtherVariationExportabilityChecks()
    {
        $this->exporterMock = $this->getExporter();

        $this->config->setAvailabilityId(5);
        $this->config->setExportUnavailableVariations(true);

        $rawVariations = $this->getMockResponse(
            'Pim/Variations/variations_with_5_for_availability_id_and_mixed_status.json'
        );
        $variations = PimVariationsParser::parse($rawVariations);
        $this->variationEntityMocks = $variations->all();

        $product = $this->getProduct();
        $item = $product->processProductData();

        $this->assertNotNull($item);
        // TODO: check item's orderNumbers directly once order numbers getter is implemented
        $line = $item->getCsvFragment();
        $columnValues = explode("\t", $line);
        $this->assertEquals('101|1005|107', $columnValues[1]);
    }

    public function testProductIsSuccessfullyWrapped(): void
    {
        $expectedName = 'Pretty awesome name!';
        $expectedSummary = 'Easy, transparent, sexy';
        $expectedDescription = 'That is the best item, and I am a bit longer text.';
        $expectedUrlPath = 'awesome-url-path/somewhere-in-the-store';
        $expectedPriceId = 11;
        $expectedMainVariationId = 20;
        $expectedBaseUnit = 'Stück';
        $expectedPackageSize = '1000';

        $this->exporterMock = $this->getExporter();

        $rawVariation = $this->getMockResponse('Pim/Variations/response.json');
        $variations = PimVariationsParser::parse($rawVariation);

        $rawWebStores = $this->getMockResponse('WebStoreResponse/response.json');
        $webStores = WebStoreParser::parse($rawWebStores);

        $rawCategories = $this->getMockResponse('CategoryResponse/one.json');
        $categories = CategoryParser::parse($rawCategories);

        $rawManufacturers = $this->getMockResponse('ManufacturerResponse/one.json');
        $manufacturers = ManufacturerParser::parse($rawManufacturers);

        $rawUnits = $this->getMockResponse('UnitResponse/one.json');
        $units = UnitParser::parse($rawUnits);

        $this->storeConfigurationMock->expects($this->exactly(2))
            ->method('getDisplayItemName')
            ->willReturn(1);

        $this->storeConfigurationMock->expects($this->once())->method('getDefaultLanguage')
            ->willReturn('de');

        $this->registryServiceMock->expects($this->once())->method('getAllWebStores')->willReturn($webStores);
        $this->registryServiceMock->expects($this->once())->method('getCategory')
            ->willReturn($categories->first());

        $this->registryServiceMock->expects($this->once())
            ->method('getManufacturer')
            ->willReturn($manufacturers->first());

        $this->registryServiceMock->expects($this->once())
            ->method('getUnit')
            ->willReturn($units->first());

        $this->registryServiceMock->expects($this->once())
            ->method('getPluginConfigurations')
            ->with('Ceres')
            ->willReturn(
                [
                    'global.enableOldUrlPattern' => false
                ]
            );

        $this->registryServiceMock->method('getPriceId')->willReturn($expectedPriceId);

        $text = new Text([
            'lang' => 'de',
            'name1' => $expectedName,
            'name2' => 'wrong',
            'name3' => 'wrong',
            'shortDescription' => $expectedSummary,
            'metaDescription' => 'my father gave me a small loan of a million dollar.',
            'description' => $expectedDescription,
            'technicalData' => 'Interesting technical information.',
            'urlPath' => $expectedUrlPath,
            'keywords' => 'get me out',
        ]);

        $this->itemMock->expects($this->once())
            ->method('getTexts')
            ->willReturn([$text]);

        $this->itemMock->expects($this->once())
            ->method('getManufacturerId')
            ->willReturn(1);

        $this->itemMock->method('getId')->willReturn(10);
        $this->itemMock->method('getMainVariationId')->willReturn($expectedMainVariationId);

        $this->variationEntityMocks[] = $variations->findOne(['id' => 1004]);

        $product = $this->getProduct();
        $item = $product->processProductData();

        $this->assertSame($expectedName, $item->getName()->getValues()['']);
        $this->assertSame($expectedSummary, $item->getSummary()->getValues()['']);
        $this->assertSame($expectedDescription, $item->getDescription()->getValues()['']);
        $this->assertSame(
            'https://plenty-testshop.de/' . $expectedUrlPath . '_10_20',
            $item->getUrl()->getValues()['']
        );

        $line = $item->getCsvFragment(self::AVAILABLE_PROPERTIES);
        $line = trim($line, "\n");
        $columnValues = explode("\t", $line);
        $this->assertSame((string)$expectedPriceId, $columnValues[18]);
        $this->assertSame((string)$expectedMainVariationId, $columnValues[19]);
        $this->assertSame($expectedBaseUnit, $columnValues[20]);
        $this->assertSame($expectedPackageSize, $columnValues[21]);

        $this->assertTrue(
            DateTime::createFromFormat(DateTime::ISO8601, $item->getDateAdded()->getValues()['']) !== false
        );
    }

    public function testLanguagePrefixIsAddedToUrlInCaseLanguageIsAvailableButNotDefaultLanguage(): void
    {
        $expectedUrlPath = 'awesome-url-path/somewhere-in-the-store';
        $expectedLanguagePrefix = 'de';

        $this->exporterMock = $this->getExporter();

        $rawVariation = $this->getMockResponse('Pim/Variations/response.json');
        $variations = PimVariationsParser::parse($rawVariation);

        $rawWebStores = $this->getMockResponse('WebStoreResponse/response.json');
        $webStores = WebStoreParser::parse($rawWebStores);

        $this->storeConfigurationMock->expects($this->exactly(2))
            ->method('getDisplayItemName')
            ->willReturn(1);

        $this->storeConfigurationMock->expects($this->once())->method('getDefaultLanguage')
            ->willReturn('en');
        $this->storeConfigurationMock->expects($this->once())->method('getLanguageList')
            ->willReturn(['de', 'en']);

        $this->registryServiceMock->expects($this->once())->method('getAllWebStores')->willReturn($webStores);

        $this->registryServiceMock->expects($this->once())
            ->method('getPluginConfigurations')
            ->with('Ceres')
            ->willReturn(['global.enableOldUrlPattern' => false]);

        $text = new Text([
            'lang' => $expectedLanguagePrefix,
            'name1' => 'Pretty awesome name!',
            'name2' => 'wrong',
            'name3' => 'wrong',
            'shortDescription' => 'Easy, transparent, sexy',
            'metaDescription' => 'my father gave me a small loan of a million dollar.',
            'description' => 'That is the best item, and I am a bit longer text.',
            'technicalData' => 'Interesting technical information.',
            'urlPath' => $expectedUrlPath,
            'keywords' => 'get me out',
        ]);

        $anotherText = new Text([
            'lang' => 'en',
            'name1' => 'Pretty awesome name!',
            'name2' => 'wrong',
            'name3' => 'wrong',
            'shortDescription' => 'Easy, transparent, sexy',
            'metaDescription' => 'my father gave me a small loan of a million dollar.',
            'description' => 'That is the best item, and I am a bit longer text.',
            'technicalData' => 'Interesting technical information.',
            'urlPath' => $expectedUrlPath,
            'keywords' => 'get me out',
        ]);

        $this->itemMock->expects($this->once())
            ->method('getTexts')
            ->willReturn([$text, $anotherText]);

        $this->variationEntityMocks[] = $variations->first();

        $product = $this->getProduct();
        $item = $product->processProductData();

        $this->assertSame(
            'https://plenty-testshop.de/' . $expectedLanguagePrefix . '/' . $expectedUrlPath . '_0_0',
            $item->getUrl()->getValues()['']
        );
    }

    public function testSortIsSetByTheMainVariation(): void
    {
        $this->exporterMock = $this->getExporter();

        $variationResponse = $this->getMockResponse('Pim/Variations/response_for_sort_test.json');
        $variations = PimVariationsParser::parse($variationResponse);
        $this->variationEntityMocks = $variations->all();

        $rawWebStores = $this->getMockResponse('WebStoreResponse/response.json');
        $webStores = WebStoreParser::parse($rawWebStores);
        $this->registryServiceMock->expects($this->any())->method('getAllWebStores')->willReturn($webStores);

        $product = $this->getProduct();
        $item = $product->processProductData();

        $this->assertEquals($item->getSort()->getValues(), ['' => 2]);
    }

    public function testKeywordsAreSetFromAllVariations(): void
    {
        $this->exporterMock = $this->getExporter();

        $variationResponse = $this->getMockResponse('Pim/Variations/variations_with_tags.json');
        $variations = PimVariationsParser::parse($variationResponse);
        $this->variationEntityMocks = $variations->all();

        $text = new Text([
            'lang' => 'de',
            'name1' => 'Pretty awesome name!',
            'name2' => 'wrong',
            'name3' => 'wrong',
            'shortDescription' => 'Easy, transparent, sexy',
            'metaDescription' => 'my father gave me a small loan of a million dollar.',
            'description' => 'That is the best item, and I am a bit longer text.',
            'technicalData' => 'Interesting technical information.',
            'urlPath' => 'urlPath',
            'keywords' => 'keywords from product'
        ]);

        $this->itemMock->expects($this->once())
            ->method('getTexts')
            ->willReturn([$text]);

        $webStoreMock = $this->getMockBuilder(WebStore::class)
            ->disableOriginalConstructor()
            ->getMock();
        $webStoreMock->method('getStoreIdentifier')->willReturn(34185);
        $this->registryServiceMock->method('getWebStore')->willReturn($webStoreMock);

        $rawWebStores = $this->getMockResponse('WebStoreResponse/response.json');
        $webStores = WebStoreParser::parse($rawWebStores);
        $this->registryServiceMock->expects($this->any())->method('getAllWebStores')->willReturn($webStores);

        $this->storeConfigurationMock->expects($this->any())
            ->method('getDisplayItemName')
            ->willReturn(1);

        $this->storeConfigurationMock->expects($this->any())
            ->method('getDefaultLanguage')
            ->willReturn('de');

        $product = $this->getProduct();
        $item = $product->processProductData();

        // TODO: check item's keyword property directly once keywords getter is implemented
        $line = $item->getCsvFragment();
        $columnValues = explode("\t", $line);
        $this->assertEquals('de tag 1,de tag 2,de tag 3,keywords from product', $columnValues[12]);
    }

    public function testPriceAndInsteadPriceIsSetByLowestValues(): void
    {
        $this->exporterMock = $this->getExporter();

        $variationResponse = $this->getMockResponse('Pim/Variations/response_for_lowest_price_test.json');
        $variations = PimVariationsParser::parse($variationResponse);
        $this->variationEntityMocks = $variations->all();

        $rawWebStores = $this->getMockResponse('WebStoreResponse/response.json');
        $webStores = WebStoreParser::parse($rawWebStores);
        $this->registryServiceMock->expects($this->any())->method('getAllWebStores')->willReturn($webStores);

        $this->registryServiceMock->expects($this->any())->method('getRrpId')->willReturn(1);

        $product = $this->getProduct();
        $item = $product->processProductData();

        $this->assertEquals($item->getPrice()->getValues(), ['' => 50]);
        $this->assertEquals($item->getInsteadPrice(), 100);
    }

    public function testImageOfFirstVariationIsUsed(): void
    {
        $this->exporterMock = $this->getExporter();

        $variationResponse = $this->getMockResponse('Pim/Variations/response_for_image_test.json');
        $variations = PimVariationsParser::parse($variationResponse);
        $this->variationEntityMocks = $variations->all();

        $product = $this->getProduct();
        $item = $product->processProductData();

        // TODO: check item's images property directly once images getter is implemented
        $line = $item->getCsvFragment();
        $columnValues = explode("\t", $line);
        $this->assertEquals('FirstAvailableImage.jpg', $columnValues[10]);
    }

    public function testGroupsAreSetFromAllVariations()
    {
        $this->exporterMock = $this->getExporter();

        $variationResponse = $this->getMockResponse('Pim/Variations/variations_with_different_clients.json');
        $variations = PimVariationsParser::parse($variationResponse);
        $this->variationEntityMocks = $variations->all();

        $rawWebStores = $this->getMockResponse('WebStoreResponse/response.json');
        $webStores = WebStoreParser::parse($rawWebStores);
        $this->registryServiceMock->expects($this->any())->method('getAllWebStores')->willReturn($webStores);

        $product = $this->getProduct();
        $item = $product->processProductData();

        // TODO: check item's groups property directly once groups getter is implemented
        $line = $item->getCsvFragment();
        $columnValues = explode("\t", $line);
        $this->assertEquals('0_,1_', $columnValues[13]);
    }

    public function testOrdernumbersAreSetFromAllVariations()
    {
        $this->exporterMock = $this->getExporter();

        $variationResponse = $this->getMockResponse('Pim/Variations/response_for_ordernumber_test.json');
        $variations = PimVariationsParser::parse($variationResponse);
        $this->variationEntityMocks = $variations->all();

        $rawWebStores = $this->getMockResponse('WebStoreResponse/response.json');
        $webStores = WebStoreParser::parse($rawWebStores);
        $this->registryServiceMock->expects($this->any())->method('getAllWebStores')->willReturn($webStores);

        $product = $this->getProduct();
        $item = $product->processProductData();

        // TODO: check item's order numbers property directly once order numbers getter is implemented
        $line = $item->getCsvFragment();
        $columnValues = explode("\t", $line);
        $this->assertEquals('1|11|1111|111|11111|111111|2|22|2222|222|22222|222222', $columnValues[1]);
    }

    public function testAttributesAreSetFromAllVariations()
    {
        $this->exporterMock = $this->getExporter();

        $variationResponse = $this->getMockResponse('Pim/Variations/variations_with_attribute_values.json');
        $variations = PimVariationsParser::parse($variationResponse);
        $this->variationEntityMocks = $variations->all();

        $attributeResponse = $this->getMockResponse('AttributeResponse/response.json');
        $attributes = AttributeParser::parse($attributeResponse);
        $attributes = $attributes->all();
        array_unshift($attributes, $attributes[0]);

        $this->registryServiceMock->expects($this->exactly(3))
            ->method('getAttribute')
            ->withConsecutive([1], [2], [1])
            ->willReturnOnConsecutiveCalls(...$attributes);

        $product = $this->getProduct();
        $item = $product->processProductData();

        // TODO: check item's attributes property directly once attributes getter is implemented
        $line = $item->getCsvFragment();
        $columnValues = explode("\t", $line);
        $this->assertEquals(
            'cat=Sessel+%26+Hocker&cat_url=%2Fwohnzimmer%2Fsessel-hocker%2F&' .
            'couch+color+de=lila&couch+color+de=valueeeee&test+de=some+test+attribute+value+in+German',
            $columnValues[11]
        );
    }

    public function testSetsSalesFrequencyAsZeroIfSortBySalesIsNotConfigured()
    {
        $this->exporterMock = $this->getExporter();

        $variationResponse = $this->getMockResponse('Pim/Variations/variations_with_attribute_values.json');
        $variations = PimVariationsParser::parse($variationResponse);
        $this->variationEntityMocks = $variations->all();

        $this->storeConfigurationMock->expects($this->once())->method('getItemSortByMonthlySales')->willReturn(0);

        $product = $this->getProduct();
        $item = $product->processProductData();

        $this->assertEquals(['' => 0], $item->getSalesFrequency()->getValues());
    }

    public function testSetSalesFrequencyByPositionIfSortBySalesIsConfigured()
    {
        $this->exporterMock = $this->getExporter();

        $variationResponse = $this->getMockResponse('Pim/Variations/variations_with_different_positions.json');
        $variations = PimVariationsParser::parse($variationResponse);
        $this->variationEntityMocks = $variations->all();

        $this->storeConfigurationMock->expects($this->once())->method('getItemSortByMonthlySales')->willReturn(1);

        $product = $this->getProduct();
        $item = $product->processProductData();

        $this->assertEquals(['' => 5], $item->getSalesFrequency()->getValues());
    }

    /**
     * For this test the first item in the response is a non-main variation with the higher position
     * The second variation is main with lower position.
     * This test makes sure that the highest position is used and not from simply from the last or main variation
     */
    public function testUsesHighestPositionForSalesFrequency()
    {
        $this->exporterMock = $this->getExporter();

        $variationResponse = $this->getMockResponse('Pim/Variations/variations_with_different_positions.json');
        $variations = PimVariationsParser::parse($variationResponse);
        $this->variationEntityMocks = array_slice($variations->all(), 0, 2);

        $this->storeConfigurationMock->expects($this->once())->method('getItemSortByMonthlySales')->willReturn(1);

        $product = $this->getProduct();
        $item = $product->processProductData();

        $this->assertEquals(['' => 1], $item->getSalesFrequency()->getValues());
    }

    public function testCallistoUrlFormatIsUsedWhenCeresConfigCouldNotBeFetched(): void
    {
        $expectedUrlPath = 'awesome-url-path/somewhere-in-the-store';

        $this->exporterMock = $this->getExporter();

        $rawVariation = $this->getMockResponse('Pim/Variations/response.json');
        $variations = PimVariationsParser::parse($rawVariation);

        $rawWebStores = $this->getMockResponse('WebStoreResponse/response.json');
        $webStores = WebStoreParser::parse($rawWebStores);

        $this->storeConfigurationMock->expects($this->exactly(2))
            ->method('getDisplayItemName')
            ->willReturn(1);

        $this->storeConfigurationMock->expects($this->once())->method('getDefaultLanguage')
            ->willReturn('de');

        $this->registryServiceMock->expects($this->once())->method('getAllWebStores')->willReturn($webStores);
        $this->registryServiceMock->expects($this->once())->method('getPluginConfigurations')->willReturn([]);

        $text = new Text([
            'lang' => 'de',
            'name1' => 'Pretty awesome name!',
            'name2' => 'wrong',
            'name3' => 'wrong',
            'shortDescription' => 'Easy, transparent, sexy',
            'metaDescription' => 'my father gave me a small loan of a million dollar.',
            'description' => 'That is the best item, and I am a bit longer text.',
            'technicalData' => 'Interesting technical information.',
            'urlPath' => $expectedUrlPath,
            'keywords' => 'get me out',
        ]);

        $this->itemMock->expects($this->once())
            ->method('getTexts')
            ->willReturn([$text]);

        $this->variationEntityMocks[] = $variations->first();

        $product = $this->getProduct();
        $item = $product->processProductData();

        $this->assertSame(
            'https://plenty-testshop.de/' . $expectedUrlPath . '/a-0',
            $item->getUrl()->getValues()['']
        );
    }

    public function testCallistoUrlFormatIsUsedWhenConfigured(): void
    {
        $expectedUrlPath = 'awesome-url-path/somewhere-in-the-store';

        $this->exporterMock = $this->getExporter();

        $rawVariation = $this->getMockResponse('Pim/Variations/response.json');
        $variations = PimVariationsParser::parse($rawVariation);

        $rawWebStores = $this->getMockResponse('WebStoreResponse/response.json');
        $webStores = WebStoreParser::parse($rawWebStores);

        $this->storeConfigurationMock->expects($this->exactly(2))
            ->method('getDisplayItemName')
            ->willReturn(1);

        $this->storeConfigurationMock->expects($this->once())->method('getDefaultLanguage')
            ->willReturn('de');

        $this->registryServiceMock->expects($this->once())->method('getAllWebStores')->willReturn($webStores);
        $this->registryServiceMock->expects($this->once())
            ->method('getPluginConfigurations')
            ->with('Ceres')
            ->willReturn(['global.enableOldUrlPattern' => true]);

        $text = new Text([
            'lang' => 'de',
            'name1' => 'Pretty awesome name!',
            'name2' => 'wrong',
            'name3' => 'wrong',
            'shortDescription' => 'Easy, transparent, sexy',
            'metaDescription' => 'my father gave me a small loan of a million dollar.',
            'description' => 'That is the best item, and I am a bit longer text.',
            'technicalData' => 'Interesting technical information.',
            'urlPath' => $expectedUrlPath,
            'keywords' => 'get me out',
        ]);

        $this->itemMock->expects($this->once())
            ->method('getTexts')
            ->willReturn([$text]);

        $this->variationEntityMocks[] = $variations->first();

        $product = $this->getProduct();
        $item = $product->processProductData();

        $this->assertSame(
            'https://plenty-testshop.de/' . $expectedUrlPath . '/a-0',
            $item->getUrl()->getValues()['']
        );
    }

    private function getProduct(): Product
    {
        return new Product(
            $this->exporterMock,
            $this->config,
            $this->storeConfigurationMock,
            $this->registryServiceMock,
            $this->itemMock,
            $this->variationEntityMocks,
            Product::WRAP_MODE_DEFAULT
        );
    }

    private function getExporter(): Exporter
    {
        return Exporter::create(Exporter::TYPE_CSV, 100, self::AVAILABLE_PROPERTIES);
    }
}
