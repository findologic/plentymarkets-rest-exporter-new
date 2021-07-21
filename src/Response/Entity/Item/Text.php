<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Item;

use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Entity;
use FINDOLOGIC\PlentyMarketsRestExporter\Response\Entity\Translatable;

class Text extends Entity implements Translatable
{
    /** @var string */
    private $lang;

    /** @var string */
    private $name1;

    /** @var string */
    private $name2;

    /** @var string */
    private $name3;

    /** @var string */
    private $shortDescription;

    /** @var string */
    private $metaDescription;

    /** @var string */
    private $description;

    /** @var string */
    private $technicalData;

    /** @var string */
    private $urlPath;

    /** @var string */
    private $keywords;

    public function __construct(array $data)
    {
        // Undocumented - the properties may not match the received data exactly
        $this->lang = (string)$data['lang'];
        $this->name1 = (string)$data['name1'];
        $this->name2 = (string)$data['name2'];
        $this->name3 = (string)$data['name3'];
        $this->shortDescription = (string)$data['shortDescription'];
        $this->metaDescription = (string)$data['metaDescription'];
        $this->description = (string)$data['description'];
        $this->technicalData = (string)$data['technicalData'];
        $this->urlPath = (string)$data['urlPath'];
        $this->keywords = (string)$data['keywords'];
    }

    public function getData(): array
    {
        return [
            'lang' => $this->lang,
            'name1' => $this->name1,
            'name2' => $this->name2,
            'name3' => $this->name3,
            'shortDescription' => $this->shortDescription,
            'metaDescription' => $this->metaDescription,
            'description' => $this->description,
            'technicalData' => $this->technicalData,
            'urlPath' => $this->urlPath,
            'keywords' => $this->keywords
        ];
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function getName1(): string
    {
        return $this->name1;
    }

    public function getName2(): string
    {
        return $this->name2;
    }

    public function getName3(): string
    {
        return $this->name3;
    }

    public function getShortDescription(): string
    {
        return $this->shortDescription;
    }

    public function getMetaDescription(): string
    {
        return $this->metaDescription;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTechnicalData(): string
    {
        return $this->technicalData;
    }

    public function getUrlPath(): string
    {
        return $this->urlPath;
    }

    public function getKeywords(): string
    {
        return $this->keywords;
    }
}
