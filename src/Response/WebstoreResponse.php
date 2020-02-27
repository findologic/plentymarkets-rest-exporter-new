<?php

namespace FINDOLOGIC\PlentyMarketsRestExporter\Response;

class WebstoreResponse extends Response
{
    /** @var int */
    private $id;

    /** @var string */
    private $type;

    /** @var string */
    private $storeIdentifier;

    /** @var string */
    private $name;

    /** @var int */
    private $pluginSetId;

    /** @var array */
    private $configuration;

    public function __construct(array $data)
    {
        $this->id = (int)$data['id'];
        $this->type = (string)$data['type'];
        $this->storeIdentifier = (int)$data['storeIdentifier'];
        $this->name = (string)$data['name'];
        $this->pluginSetId = (int)$data['pluginSetId'];
        $this->configuration = (array)$data['configuration'];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getStoreIdentifier(): string
    {
        return $this->storeIdentifier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPluginSetId(): int
    {
        return $this->pluginSetId;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }
}
