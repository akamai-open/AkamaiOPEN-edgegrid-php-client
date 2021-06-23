<?php
/**
 * Akamai {OPEN} EdgeGrid Auth Client
 *
 * @author Davey Shafik <dshafik@akamai.com>
 * @copyright Copyright 2016 Akamai Technologies, Inc. All rights reserved.
 * @license Apache 2.0
 * @link https://github.com/akamai-open/AkamaiOPEN-edgegrid-php-client
 * @link https://developer.akamai.com
 * @link https://developer.akamai.com/introduction/Client_Auth.html
 */
namespace Akamai\Open\EdgeGrid;

use Akamai\Open\EdgeGrid\Handler\Authentication as AuthenticationHandler;
use Akamai\Open\EdgeGrid\Handler\Debug as DebugHandler;
use Akamai\Open\EdgeGrid\Handler\Verbose as VerboseHandler;

/**
 * Akamai {OPEN} EdgeGrid Client for PHP
 *
 * Akamai\Open\EdgeGrid\Client wraps GuzzleHttp\Client
 * providing request authentication/signing for Akamai
 * {OPEN} APIs.
 *
 * This client works _identically_ to GuzzleHttp\Client
 *
 * However, if you try to call an Akamai {OPEN} API you *must*
 * first call {@see Akamai\Open\EdgeGrid\Client->setAuth()}.
 *
 * @package Akamai\Open\EdgeGrid\Client
 */
class Client extends \GuzzleHttp\Client implements \Psr\Log\LoggerAwareInterface
{
    const VERSION = '2.0.0';

    /**
     * @const int Default Timeout in seconds
     */
    const DEFAULT_REQUEST_TIMEOUT = 300;

    /**
     * @var bool|array|resource Whether verbose mode is enabled
     *
     * - true - Use STDERR
     * - array - output/error streams (different)
     * - resource - output/error stream (same)
     */
    protected static $staticVerbose = false;

    /**
     * @var bool|resource Whether debug mode is enabled
     */
    protected static $staticDebug = false;

    /**
     * @var \Akamai\Open\EdgeGrid\Authentication
     */
    protected $authentication;

    /**
     * @var \Akamai\Open\EdgeGrid\Handler\Verbose
     */
    protected $verboseHandler;

    /**
     * @var \Akamai\Open\EdgeGrid\Handler\Debug
     */
    protected $debugHandler;

    /**
     * @var bool|array|resource Whether verbose mode is enabled
     *
     * - true - Use STDOUT
     * - array - output/error streams (different)
     * - resource - output/error stream (same)
     */
    protected $verbose = false;

    /**
     * @var bool|resource Whether debugging is enabled
     */
    protected $debug = false;

    /**
     * @var bool Whether to override the static verbose setting
     */
    protected $verboseOverride = false;

    /**
     * @var bool Whether to override the static debug setting
     */
    protected $debugOverride = false;

    /**
     * @var callable Logging Handler
     */
    protected $logger;

    /**
     * \GuzzleHttp\Client-compatible constructor
     *
     * @param array $config Config options array
     * @param Authentication|null $authentication
     */
    public function __construct(
        $config = [],
        Authentication $authentication = null
    ) {
        $config = $this->setAuthenticationHandler($config, $authentication);
        $config = $this->setBasicOptions($config);
        $config['headers']['User-Agent'] = 'Akamai-Open-Edgegrid-PHP/' .
            self::VERSION . ' ' . \GuzzleHttp\default_user_agent();

        parent::__construct($config);
    }

    /**
     * Make an Asynchronous request
     *
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function requestAsync($method, $uri = null, array $options = []): \GuzzleHttp\Promise\PromiseInterface
    {
        $options = $this->setRequestOptions($options);

        $query = parse_url($uri, PHP_URL_QUERY);
        if (!empty($query)) {
            $uri = substr($uri, 0, (strlen($query)+1) * -1);
            parse_str($query, $options['query']);
        }

        return parent::requestAsync($method, $uri, $options);
    }

    /**
     * Send an Asynchronous HTTP request
     *
     * @param \Psr\Http\Message\RequestInterface $request The HTTP request
     * @param array $options Request options
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function sendAsync(\Psr\Http\Message\RequestInterface $request, array $options = []): \GuzzleHttp\Promise\PromiseInterface
    {
        $options = $this->setRequestOptions($options);

        return parent::sendAsync($request, $options);
    }

    /**
     * Set Akamai {OPEN} Authentication Credentials
     *
     * @param string $client_token
     * @param string $client_secret
     * @param string $access_token
     * @return $this
     */
    public function setAuth($client_token, $client_secret, $access_token)
    {
        $this->authentication->setAuth($client_token, $client_secret, $access_token);

        return $this;
    }

    /**
     * Specify the headers to include when signing the request
     *
     * This is specified by the API, currently no APIs use this
     * feature.
     *
     * @param array $headers
     * @return $this
     */
    public function setHeadersToSign(array $headers)
    {
        $this->authentication->setHeadersToSign($headers);

        return $this;
    }

    /**
     * Set the max body size
     *
     * @param int $max_body_size
     * @return $this
     */
    public function setMaxBodySize($max_body_size)
    {
        $this->authentication->setMaxBodySize($max_body_size);

        return $this;
    }

    /**
     * Set Request Host
     *
     * @param string $host
     * @return $this
     */
    public function setHost($host)
    {
        if (substr($host, -1) === '/') {
            $host = substr($host, 0, -1);
        }

        $headers = $this->getConfig('headers');
        $headers['Host'] = $host;
        $this->setConfigOption('headers', $headers);

        if (strpos('/', $host) === false) {
            $host = 'https://' . $host;
        }
        $this->setConfigOption('base_uri', $host);

        return $this;
    }

    /**
     * Set the HTTP request timeout
     *
     * @param int $timeout_in_seconds
     * @return $this
     */
    public function setTimeout($timeout_in_seconds)
    {
        $this->setConfigOption('timeout', $timeout_in_seconds);

        return $this;
    }

    /**
     * Print formatted JSON responses to output
     *
     * @param bool|resource $enable
     * @return $this
     */
    public function setInstanceVerbose($enable)
    {
        $this->verboseOverride = true;
        $this->verbose = $enable;
        return $this;
    }

    /**
     * Print HTTP requests/responses to output
     *
     * @param bool|resource $enable
     * @return $this
     */
    public function setInstanceDebug($enable)
    {
        $this->debugOverride = true;
        $this->debug = $enable;
        return $this;
    }

    /**
     * Set a PSR-3 compatible logger (or use monolog by default)
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param string $messageFormat Message format
     * @return $this
     */
    public function setLogger(
        \Psr\Log\LoggerInterface $logger = null,
        $messageFormat = \GuzzleHttp\MessageFormatter::CLF
    ) {
        if ($logger === null) {
            $handler = new \Monolog\Handler\ErrorLogHandler(\Monolog\Handler\ErrorLogHandler::SAPI);
            $handler->setFormatter(new \Monolog\Formatter\LineFormatter('%message%'));
            $logger = new \Monolog\Logger('HTTP Log', [$handler]);
        }

        $formatter = new \GuzzleHttp\MessageFormatter($messageFormat);

        $handler = \GuzzleHttp\Middleware::log($logger, $formatter);
        $this->logger = $handler;

        $handlerStack = $this->getConfig('handler');
        $this->setLogHandler($handlerStack, $handler);

        return $this;
    }

    /**
     * Add logger using a given filename/format
     *
     * @param string $filename
     * @param string $format
     * @return \Akamai\Open\EdgeGrid\Client|bool
     */
    public function setSimpleLog($filename, $format = '{code}')
    {
        if ($this->logger && !($this->logger instanceof \Monolog\Logger)) {
            return false;
        }

        $handler = new \Monolog\Handler\StreamHandler($filename);
        $handler->setFormatter(new \Monolog\Formatter\LineFormatter('%message%'));
        $log = new \Monolog\Logger('HTTP Log', [$handler]);

        return $this->setLogger($log, $format);
    }

    /**
     * Create instance using environment (preferred) or .edgerc file (fallback) automatically.
     *
     * @param string $section
     * @param null $path
     * @return Client
     * @throws \Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException
     */
    public static function createInstance($section = 'default', $path = null, array $config = [])
    {
        $auth = \Akamai\Open\EdgeGrid\Authentication::createInstance($section, $path);

        if ($host = $auth->getHost()) {
            $config['base_uri'] = 'https://' .$host;
        }

        return new static($config, $auth);
    }

    public static function createFromEnv($section = 'default', array $config = [])
    {
        $auth = \Akamai\Open\EdgeGrid\Authentication::createFromEnv($section);

        if ($host = $auth->getHost()) {
            $config['base_uri'] = 'https://' . $host;
        }

        return new static($config, $auth);
    }

    /**
     * Factory method to create a client using credentials from `.edgerc`
     *
     * Automatically checks your HOME directory, and the current working
     * directory for credentials, if no path is supplied.
     *
     * @param string $section Credential section to use
     * @param string $path Path to .edgerc credentials file
     * @param array $config Options to pass to the constructor/guzzle
     * @return \Akamai\Open\EdgeGrid\Client
     */
    public static function createFromEdgeRcFile($section = 'default', $path = null, array $config = [])
    {
        $auth = \Akamai\Open\EdgeGrid\Authentication::createFromEdgeRcFile($section, $path);

        if ($host = $auth->getHost()) {
            $config['base_uri'] = 'https://' . $host;
        }

        return new static($config, $auth);
    }

    /**
     * Print HTTP requests/responses to STDOUT
     *
     * @param bool|resource $enable
     */
    public static function setDebug($enable)
    {
        self::$staticDebug = $enable;
    }

    /**
     * Print formatted JSON responses to STDOUT
     *
     * @param bool|resource|array $enable
     */
    public static function setVerbose($enable)
    {
        self::$staticVerbose = $enable;
    }

    /**
     * Handle debug option
     *
     * @param array $config
     * @return bool|resource
     */
    protected function getDebugOption(array $config)
    {
        if (isset($config['debug'])) {
            return ($config['debug'] === true) ? fopen('php://stderr', 'ab') : $config['debug'];
        }

        if ($this->debugOverride && $this->debug) {
            return ($this->debug === true) ? fopen('php://stderr', 'ab') : $this->debug;
        } elseif (!$this->debugOverride && static::$staticDebug) {
            return (static::$staticDebug === true) ? fopen('php://stderr', 'ab') : static::$staticDebug;
        }

        return false;
    }

    /**
     * Debugging status for the current request
     *
     * @return bool|resource
     */
    protected function isDebug()
    {
        if (($this->debugOverride && !$this->debug) || (!$this->debugOverride && !static::$staticDebug)) {
            return false;
        }

        if ($this->debugOverride && $this->debug) {
            return $this->debug;
        }

        return static::$staticDebug;
    }

    /**
     * Verbose status for the current request
     *
     * @return array|bool|resource
     */
    protected function isVerbose()
    {
        if (($this->verboseOverride && !$this->verbose) || (!$this->verboseOverride && !static::$staticVerbose)) {
            return false;
        }

        if ($this->verboseOverride && $this->verbose) {
            return $this->verbose;
        }

        return static::$staticVerbose;
    }

    /**
     * Set the Authentication instance
     *
     * @param array $config
     * @param Authentication|null $authentication
     */
    protected function setAuthentication(array $config, Authentication $authentication = null)
    {
        $this->authentication = $authentication;
        if ($authentication === null) {
            $this->authentication = new Authentication();
        }

        if (isset($config['timestamp'])) {
            $this->authentication->setTimestamp($config['timestamp']);
        }

        if (isset($config['nonce'])) {
            $this->authentication->setNonce($config['nonce']);
        }
    }

    /**
     * Set the Authentication Handler
     *
     * @param array $config
     * @param Authentication|null $authentication
     * @return array
     */
    protected function setAuthenticationHandler(array $config, Authentication $authentication = null)
    {
        $this->setAuthentication($config, $authentication);

        $authenticationHandler = new AuthenticationHandler();
        $authenticationHandler->setSigner($this->authentication);
        if (!isset($config['handler'])) {
            $config['handler'] = \GuzzleHttp\HandlerStack::create();
        }
        try {
            if (!($config['handler'] instanceof \GuzzleHttp\HandlerStack)) {
                $config['handler'] = \GuzzleHttp\HandlerStack::create($config['handler']);
            }
            $config['handler']->before('history', $authenticationHandler, 'authentication');
        } catch (\InvalidArgumentException $e) {
            // history middleware not added yet
            $config['handler']->push($authenticationHandler, 'authentication');
        }
        return $config;
    }

    /**
     * Set timeout and base_uri options
     *
     * @param array $config
     * @return mixed
     */
    protected function setBasicOptions(array $config)
    {
        if (!isset($config['timeout'])) {
            $config['timeout'] = static::DEFAULT_REQUEST_TIMEOUT;
        }

        if (isset($config['base_uri']) && strpos($config['base_uri'], 'http') === false) {
            $config['base_uri'] = 'https://' . $config['base_uri'];
            return $config;
        }
        return $config;
    }

    /**
     * Set values on the private \GuzzleHttp\Client->config
     *
     * This is a terrible hack, and illustrates why making
     * anything private makes it difficult to extend, and impossible
     * when there is no setter.
     *
     * @param string $what Config option to set
     * @param mixed $value Value to set the option to
     * @return void
     */
    protected function setConfigOption($what, $value)
    {
        $closure = function () use ($what, $value) {
            /* @var $this \GuzzleHttp\Client */
            $this->config[$what] = $value;
        };

        $closure = $closure->bindTo($this, \GuzzleHttp\Client::class);
        $closure();
    }

    /**
     * Add the Debug handler to the HandlerStack
     *
     * @param array $options Guzzle Options
     * @param bool|resource|null $fp Stream to write to
     * @return array
     */
    protected function setDebugHandler($options, $fp = null)
    {
        try {
            if (is_bool($fp)) {
                $fp = null;
            }

            $handler = $this->getConfig('handler');
            // if we have a default handler, and we've already created a DebugHandler
            // we can bail out now (or we will add another one to the stack)
            if ($handler && $this->debugHandler) {
                return $options;
            }

            if (isset($options['handler'])) {
                $handler = $options['handler'];
            }

            if ($handler === null) {
                $handler = \GuzzleHttp\HandlerStack::create();
            }

            if (!$this->debugHandler) {
                $this->debugHandler = new DebugHandler($fp);
            }

            $handler->after('allow_redirects', $this->debugHandler, 'debug');
        } catch (\InvalidArgumentException $e) {
            $handler->push($this->debugHandler, 'debug');
        }

        $options['handler'] = $handler;

        return $options;
    }

    /**
     * Add the Log handler to the HandlerStack
     *
     * @param \GuzzleHttp\HandlerStack $handlerStack
     * @param callable $logHandler
     * @return $this
     */
    protected function setLogHandler(\GuzzleHttp\HandlerStack $handlerStack, callable $logHandler)
    {
        try {
            $handlerStack->after('history', $logHandler, 'logger');
        } catch (\InvalidArgumentException $e) {
            try {
                $handlerStack->before('allow_redirects', $logHandler, 'logger');
            } catch (\InvalidArgumentException $e) {
                $handlerStack->push($logHandler, 'logger');
            }
        }

        return $this;
    }

    /**
     * Add the Verbose handler to the HandlerStack
     *
     * @param array $options Guzzle Options
     * @param bool|resource|array|null $fp Stream to write to
     * @return array
     */
    protected function setVerboseHandler($options, $fp = null)
    {
        try {
            if (is_bool($fp) || $fp === null) {
                $fp = ['outputStream' => null, 'errorStream' => null];
            } elseif (!is_array($fp)) {
                $fp = ['outputStream' => $fp, 'errorStream' => $fp];
            }

            $handler = $this->getConfig('handler');
            // if we have a default handler, and we've already created a VerboseHandler
            // we can bail out now (or we will add another one to the stack)
            if ($handler && $this->verboseHandler) {
                return $options;
            }

            if (isset($options['handler'])) {
                $handler = $options['handler'];
            }

            if ($handler === null) {
                $handler = \GuzzleHttp\HandlerStack::create();
            }

            if (!$this->verboseHandler) {
                $this->verboseHandler = new VerboseHandler(array_shift($fp), array_shift($fp));
            }

            $handler->after('allow_redirects', $this->verboseHandler, 'verbose');
        } catch (\InvalidArgumentException $e) {
            $handler->push($this->verboseHandler, 'verbose');
        }

        $options['handler'] = $handler;

        return $options;
    }

    /**
     * Set request specific options
     *
     * @param array $options
     * @return array
     */
    protected function setRequestOptions(array $options)
    {
        if (isset($options['timestamp'])) {
            $this->authentication->setTimestamp($options['timestamp']);
        } elseif (!$this->getConfig('timestamp')) {
            $this->authentication->setTimestamp();
        }

        if (isset($options['nonce'])) {
            $this->authentication->setNonce($options['nonce']);
        }

        if (isset($options['handler'])) {
            $options = $this->setAuthenticationHandler($options, $this->authentication);
        }

        if ($fp = $this->isVerbose()) {
            $options = $this->setVerboseHandler($options, $fp);
        }

        $options['debug'] = $this->getDebugOption($options);
        if ($fp = $this->isDebug()) {
            $options = $this->setDebugHandler($options, $fp);
        }

        if ($this->logger && isset($options['handler'])) {
            $this->setLogHandler($options['handler'], $this->logger);
            return $options;
        }

        return $options;
    }
}
