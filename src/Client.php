<?php

declare(strict_types=1);

namespace FINDOLOGIC\PlentyMarketsRestExporter;

use FINDOLOGIC\PlentyMarketsRestExporter\Debug\Debugger;
use FINDOLOGIC\PlentyMarketsRestExporter\Debug\DebuggerInterface;
use FINDOLOGIC\PlentyMarketsRestExporter\Debug\DummyDebugger;
use FINDOLOGIC\PlentyMarketsRestExporter\Exception\AuthorizationException;
use FINDOLOGIC\PlentyMarketsRestExporter\Exception\CriticalException;
use FINDOLOGIC\PlentyMarketsRestExporter\Exception\CustomerException;
use FINDOLOGIC\PlentyMarketsRestExporter\Exception\PermissionException;
use FINDOLOGIC\PlentyMarketsRestExporter\Exception\Retry\EmptyResponseException;
use FINDOLOGIC\PlentyMarketsRestExporter\Exception\ThrottlingException;
use FINDOLOGIC\PlentyMarketsRestExporter\Logger\DummyLogger;
use FINDOLOGIC\PlentyMarketsRestExporter\Request\Request;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class Client
{
    public const
        PROTOCOL_HTTP = 'http',
        PROTOCOL_HTTPS = 'https';

    public const
        METHOD_GET = 'GET',
        METHOD_POST = 'POST';

    private const
        PLENTY_SHORT_PERIOD_CALLS_HEADER = 'X-Plenty-Global-Short-Period-Calls-Left',
        PLENTY_SHORT_PERIOD_DECAY_HEADER = 'X-Plenty-Global-Short-Period-Decay';

    private const
        REST_PATH = 'rest';

    /** @var GuzzleClient */
    private $client;

    /** @var Config */
    private $config;

    /** @var LoggerInterface */
    private $internalLogger;

    /** @var LoggerInterface */
    private $customerLogger;

    /** @var DebuggerInterface */
    private $debugger;

    /** @var ResponseInterface Used for rate limiting. */
    private $lastResponse;

    /** @var string */
    private $accessToken;

    /** @var string */
    private $refreshToken;

    public function __construct(
        GuzzleClient $httpClient,
        Config $config,
        ?LoggerInterface $internalLogger = null,
        ?LoggerInterface $customerLogger = null,
        ?DebuggerInterface $debugger = null
    ) {
        $this->client = $httpClient;
        $this->config = $config;
        $this->internalLogger = $internalLogger ?? new DummyLogger();
        $this->customerLogger = $customerLogger ?? new DummyLogger();
        $this->debugger = $debugger ?? new DummyDebugger();

        if ($this->config->isDebug()) {
            $this->debugger = new Debugger();
        }
    }

    public function send(Request $request): ResponseInterface
    {
        $this->handleRateLimit();
        $this->handleLogin();

        $request = $request->withUri($this->buildRequestUri($request->getUri()->__toString()));
        $response = $this->sendRequest($request, $request->getParams());
        $this->handleResponse($request, $response);

        return $response;
    }

    private function sendRequest(GuzzleRequest $request, array $params = null): ResponseInterface
    {
        $uri = $request->getUri()->__toString();
        if ($request->getMethod() === self::METHOD_GET && !empty($params)) {
            $request = $request->withUri($request->getUri()->withQuery(''));

            $uri .= sprintf('?%s', $this->getRequestOptions($request, $params)[RequestOptions::QUERY]);
        }

        $this->customerLogger->debug(sprintf('Getting data from: %s', $uri));

        if ($this->accessToken) {
            $request = $request->withAddedHeader('Authorization', 'Bearer ' . $this->accessToken);
        }

        $response = $this->client->send(
            $request,
            $this->getRequestOptions($request, $params)
        );
        $this->lastResponse = $response;

        return $response;
    }

    private function handleResponse(RequestInterface $request, ResponseInterface $response): void
    {
        $this->debugger->save($request, $response);

        switch ($response->getStatusCode()) {
            case 401:
                throw new AuthorizationException('The REST client is not logged in.');
            case 403:
                throw new PermissionException(sprintf(
                    'The REST client does not have access rights for method with URI "%s"',
                    $request->getUri()->__toString()
                ));
            case 429:
                throw new ThrottlingException('Throttling limit reached.');
            case 200:
                if (empty($response->getBody()->__toString())) {
                    throw new EmptyResponseException($request->getUri()->__toString());
                }
                break;
            default:
                throw new CustomerException(sprintf(
                    'Could not reach API method with URI "%s". Status code was %d.',
                    $request->getUri()->__toString(),
                    $response->getStatusCode()
                ));
        }
    }

    private function handleLogin(): void
    {
        if (!$this->refreshToken) {
            $this->doLogin();
        }
    }

    private function doLogin(): void
    {
        $this->customerLogger->debug('Trying to log into the Plentymarkets REST API...');

        $request = new GuzzleRequest(
            self::METHOD_POST,
            $this->buildRequestUri('login'),
        );
        $params = [
            'username' => $this->config->getUsername(),
            'password' => $this->config->getPassword()
        ];

        $response = $this->sendRequest($request, $params);
        if ($response->getStatusCode() >= 301 && $response->getStatusCode() <= 404) {
            $this->config->setProtocol(self::PROTOCOL_HTTP);
            $request = $request->withUri($this->buildRequestUri('login'));

            $response = $this->sendRequest($request, $params);
        }

        $this->handleLoginResponse($request, $response);
    }

    private function handleLoginResponse(RequestInterface $request, ResponseInterface $response): void
    {
        if ($response->getStatusCode() !== 200) {
            throw new CriticalException(sprintf(
                'Unable to connect to the REST API via "%s".',
                $request->getUri()->__toString()
            ));
        }

        $data = json_decode($response->getBody()->__toString());
        if (!$data || !property_exists($data, 'accessToken')) {
            throw new CriticalException(
                'Wrong username or password. The response does not contain an access token.'
            );
        }

        $this->customerLogger->info('Login to the REST API was successful!');
        $this->accessToken = $data->accessToken;
        $this->refreshToken = $data->refreshToken;
    }

    public function getAccessToken(): string
    {
        if (!$this->accessToken) {
            throw new \Exception('Login before you can get the accessToken.');
        }

        return $this->accessToken;
    }

    /**
     * @codeCoverageIgnore Not yet implemented.
     */
    private function refreshLogin(): void
    {
        // TODO: Login sessions are typically 24 hours, but we want to refresh the login session anyway, if
        //  the login session time is exceeded.
    }

    private function handleRateLimit(): void
    {
        if (!$this->lastResponse || !$this->isRateLimited()) {
            return;
        }

        $waitTimeInSeconds = $this->getRateLimitWaitTimeInSeconds();
        $this->customerLogger->info(sprintf(
            'Waiting for %d seconds, due to rate limiting...',
            $waitTimeInSeconds
        ));

        sleep($waitTimeInSeconds);
    }

    private function isRateLimited(): bool
    {
        $requestsLeft = (int)$this->lastResponse->getHeaderLine(self::PLENTY_SHORT_PERIOD_CALLS_HEADER);

        return $requestsLeft <= 1;
    }

    private function getRateLimitWaitTimeInSeconds(): int
    {
        return (int)$this->lastResponse->getHeaderLine(self::PLENTY_SHORT_PERIOD_DECAY_HEADER);
    }

    private function buildRequestUri(string $endpoint): Uri
    {
        return new Uri(sprintf(
            '%s://%s/%s/%s',
            $this->config->getProtocol(),
            $this->config->getDomain(),
            self::REST_PATH,
            $endpoint
        ));
    }

    private function sanitizeQueryParams(array $params): string
    {
        $sanitized = [];
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                // Plentymarkets REST API separates array parameters with ",".
                $sanitized[$key] = $key . '=' . implode(',', $value);

                continue;
            }

            if ($value === null) {
                continue;
            }

            $sanitized[$key] = $key . '=' . $value;
        }

        return implode('&', $sanitized);
    }

    private function getRequestOptions(GuzzleRequest $request, array $params): array
    {
        $options = [
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::ALLOW_REDIRECTS => true,
        ];

        switch ($request->getMethod()) {
            case self::METHOD_POST:
                $options[RequestOptions::FORM_PARAMS] = $params;
                break;
            case self::METHOD_GET:
                $options[RequestOptions::QUERY] = $this->sanitizeQueryParams($params);
                break;
        }

        return $options;
    }
}
