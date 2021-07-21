<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Wrapper;

use FINDOLOGIC\Export\Data\Attribute;
use FINDOLOGIC\PlentyMarketsRestExporter\Config;
use FINDOLOGIC\PlentyMarketsRestExporter\Definition\CastType;
use FINDOLOGIC\PlentyMarketsRestExporter\RegistryService;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\ItemVariation\Property as ItemVariationProperty;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Pim\Property\Property as PimProperty;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Pim\Property\PropertyRelationValue;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Pim\Property\PropertyValue;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Pim\Variation as VariationEntity;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Property\Name;
use FINDOLOGIC\PlentyMarketsRestExporter\Translator;
use FINDOLOGIC\PlentyMarketsRestExporter\Utils;

/**
 * @property VariationEntity $variationEntity
 * @property RegistryService $registryService
 * @property Config $config
 * @property Attribute[] $attributes
 */
trait PropertyAware
{
    public function processProperties(): void
    {
        foreach ($this->variationEntity->getProperties() as $property) {
            $propertyDetails = $this->registryService->getProperty($property->getId());
            if (!$propertyDetails) {
                continue;
            }

            if ($propertyDetails->getTypeIdentifier() !== ItemVariationProperty::PROPERTY_TYPE_ITEM) {
                continue;
            }

            $propertyName = null;
            /** @var Name|null $name */
            $name = Translator::translate($propertyDetails->getNames(), $this->config->getLanguage());
            if ($name) {
                $propertyName = $name->getName();
            }

            $value = $this->getPropertyValue($property);

            if (Utils::isEmpty($property) || Utils::isEmpty($value)) {
                continue;
            }

            // Convert $value to an array in case it only contains a single value.
            $this->attributes[] = new Attribute((string)$propertyName, (array)$value);
        }
    }

    /**
     * @param PimProperty $property
     * @return string[]|string|null
     */
    protected function getPropertyValue(PimProperty $property)
    {
        switch ($property->getPropertyData()->getCast()) {
            case CastType::EMPTY:
                return null;
            case CastType::SHORT_TEXT:
            case CastType::LONG_TEXT:
                /** @var PropertyValue|null $propertyValue */
                $propertyValue = Translator::translate($property->getValues(), $this->config->getLanguage());
                if ($propertyValue) {
                    return $propertyValue->getValue();
                }

                return null;
            case CastType::SELECTION:
                if (!$property->getValues()) {
                    return null;
                }

                foreach ($property->getPropertyData()->getSelections() as $selection) {
                    if ($property->getValues()[0]->getValue() != $selection->getId()) {
                        continue;
                    }

                    /** @var PropertyRelationValue|null $relationValue */
                    $relationValue = Translator::translate(
                        $selection->getRelation()->getValues(),
                        $this->config->getLanguage()
                    );
                    if ($relationValue) {
                        return $relationValue->getValue();
                    }
                }

                return null;
            case CastType::MULTI_SELECTION:
                if (!$this->registryService->getPropertySelections()) {
                    return null;
                }

                return $this->registryService->getPropertySelections()->getPropertySelectionValues(
                    $property->getId(),
                    $property->getValues(),
                    $this->config->getLanguage()
                );
            default:
                return isset($property->getValues()[0]) ? $property->getValues()[0]->getValue() : null;
        }
    }
}
