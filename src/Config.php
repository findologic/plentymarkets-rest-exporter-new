<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter;

use Exception;

/**
 * Holds Plentymarkets-relevant configuration from the customer-login.
 */
class Config
{
    /** @var string */
    private $domain;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var string */
    private $language;

    /** @var int|null */
    private $multiShopId;

    /** @var int|null */
    private $availabilityId;

    /** @var int|null */
    private $priceId;

    /** @var int|null */
    private $rrpId;

    /** @var string  */
    private $protocol = Client::PROTOCOL_HTTPS;

    /** @var bool */
    private $debug = false;

    /** @var bool  */
    private $exportUnavailableVariations = false;

    public function __construct(array $rawConfig = [])
    {
        foreach ($rawConfig as $configKey => $configValue) {
            $setter = 'set' . ucfirst($configKey);

            if (!method_exists($this, $setter)) {
                continue;
            }

            $this->{$setter}($configValue);
        }
    }

    public static function fromArray(array $data, bool $debug = false): self
    {
        $shop = array_values($data)[0] ?? null;
        if (!$shop || !isset($shop['plentymarkets'])) {
            throw new Exception('Something went wrong while tying to fetch the importer data');
        }

        $plentyConfig = $shop['plentymarkets'];
        return new Config([
            'domain' => $shop['url'],
            'username' => $shop['export_username'],
            'password' => $shop['export_password'],
            'language' => $shop['language'],
            'multiShopId' => $plentyConfig['multishop_id'],
            'availabilityId' => $plentyConfig['availability_id'],
            'priceId' => $plentyConfig['price_id'],
            'rrpId' => $plentyConfig['rrp_id'],
            'exportUnavailableVariations' => $plentyConfig['export_unavailable_variants'],
            'debug' => $debug
        ]);
    }

    public static function fromEnvironment(): Config
    {
        return new Config([
            'domain' => Utils::env('EXPORT_DOMAIN'),
            'username' => Utils::env('EXPORT_USERNAME'),
            'password' => Utils::env('EXPORT_PASSWORD'),
            'language' => Utils::env('EXPORT_LANGUAGE'),
            'multiShopId' => (int)Utils::env('EXPORT_MULTISHOP_ID'),
            'availabilityId' => (int)Utils::env('EXPORT_AVAILABILITY_ID'),
            'priceId' => (int)Utils::env('EXPORT_PRICE_ID'),
            'rrpId' => (int)Utils::env('EXPORT_RRP_ID'),
            'exportUnavailableVariations' => (bool)Utils::env('EXPORT_UNAVAILABLE_VARIATIONS'),
            'debug' => (bool)Utils::env('DEBUG')
        ]);
    }

    /**
     * A domain or any URI can be submitted to this method. The configuration may only store the domain name itself.
     *
     * @param string $uri
     * @return $this
     */
    public function setDomain(string $uri): self
    {
        // TODO: Only fetch the domain name out of the given URI.
        $this->domain = $uri;

        return $this;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setLanguage(string $language): self
    {
        // TODO: Get the language table out of the old plenty-rest exporter and store it here.
        $this->language = $language;

        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }


    public function setMultiShopId(?int $multiShopId): self
    {
        $this->multiShopId = $multiShopId;

        return $this;
    }

    public function getMultiShopId(): ?int
    {
        return $this->multiShopId;
    }

    public function setAvailabilityId(?int $availabilityId): self
    {
        $this->availabilityId = $availabilityId;

        return $this;
    }

    public function getAvailabilityId(): ?int
    {
        return $this->availabilityId;
    }

    public function setPriceId(?int $priceId): self
    {
        $this->priceId = $priceId;

        return $this;
    }

    public function getPriceId(): ?int
    {
        return $this->priceId;
    }

    public function setRrpId(?int $rrpId): self
    {
        $this->rrpId = $rrpId;

        return $this;
    }

    public function getRrpId(): ?int
    {
        return $this->rrpId;
    }

    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;

        return $this;
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function setProtocol(string $protocol): void
    {
        $this->protocol = $protocol;
    }

    public function getProtocol(): string
    {
        return $this->protocol;
    }

    public function isExportUnavailableVariations(): bool
    {
        return $this->exportUnavailableVariations;
    }

    public function setExportUnavailableVariations(bool $exportUnavailableVariations): void
    {
        $this->exportUnavailableVariations = $exportUnavailableVariations;
    }
}
