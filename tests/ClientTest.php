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
namespace Akamai\Open\EdgeGrid\Tests;

use Akamai\Open\EdgeGrid\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

/**
 * Test for Akamai\Open\EdgeGrid\Client
 *
 * @author Davey Shafik <dshafik@akamai.com>
 * @since PHP 5.6
 * @version 1.0
 * @requires PHP 5.5
 */
class ClientTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        Client::setVerbose(false);
        Client::setDebug(false);
    }

    /**
     * @param $name
     * @param $options
     * @param $request
     * @param $result
     * @dataProvider makeAuthHeaderProvider
     */
    public function testMakeAuthHeader($name, $options, $request, $result)
    {
        //$this->setName($name);

        // Mock the response, we don't care about it
        $container = [];
        $handler = $this->getMockHandler([new Response(200)], $container);

        $timestamp = $this->prophesize(\Akamai\Open\EdgeGrid\Authentication\Timestamp::class);
        $timestamp->__toString()->willReturn($options['timestamp']);
        $timestamp->isValid()->willReturn(true);
        $nonce = $this->prophesize(\Akamai\Open\EdgeGrid\Authentication\Nonce::class);
        $nonce->__toString()->willReturn($options['nonce']);

        $client = new Client(
            array_merge($options, [
                'base_uri' => $options['base_url'],
                'handler' => $handler,
                'timestamp' => $timestamp->reveal(),
                'nonce' => $nonce->reveal()
            ])
        );

        $client->setAuth($options['client_token'], $options['client_secret'], $options['access_token']);
        $client->setMaxBodySize($options['max_body']);

        if (isset($options['headers_to_sign'])) {
            $client->setHeadersToSign($options['headers_to_sign']);
        }

        $headers = array();
        if (isset($request['headers'])) {
            array_walk_recursive($request['headers'], function ($value, $key) use (&$headers) {
                $headers[$key] = $value;
            });
        }

        $client->request(
            $request['method'],
            $request['path'],
            [
                'headers' => $headers,
                'body' => $request['data'],
            ]
        );

        $this->assertCount(1, $container);
        $request = $container[0]['request'];
        $headers = $request->getHeaders();

        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertCount(1, $headers['Authorization']);
        $this->assertEquals($result, $headers['Authorization'][0]);
    }

    /**
     * @param $name
     * @param $options
     * @param $request
     * @param $result
     * @dataProvider makeAuthHeaderProvider
     */
    public function testMakeAuthHeaderPsr7($name, $options, $request, $result)
    {
        //$this->setName($name);

        // Mock the response, we don't care about it
        $container = [];
        $handler = $this->getMockHandler([new Response(200)], $container);

        $timestamp = $this->prophesize(\Akamai\Open\EdgeGrid\Authentication\Timestamp::class);
        $timestamp->__toString()->willReturn($options['timestamp']);
        $timestamp->isValid()->willReturn(true);
        $nonce = $this->prophesize(\Akamai\Open\EdgeGrid\Authentication\Nonce::class);
        $nonce->__toString()->willReturn($options['nonce']);

        $client = new Client(
            array_merge($options, [
                'base_uri' => $options['base_url'],
                'handler' => $handler,
                'timestamp' => $timestamp->reveal(),
                'nonce' => $nonce->reveal()
            ])
        );

        $client->setAuth($options['client_token'], $options['client_secret'], $options['access_token']);
        $client->setMaxBodySize($options['max_body']);

        if (isset($options['headers_to_sign'])) {
            $client->setHeadersToSign($options['headers_to_sign']);
        }

        $headers = array();
        if (isset($request['headers'])) {
            array_walk_recursive($request['headers'], function ($value, $key) use (&$headers) {
                $headers[$key] = $value;
            });
        }

        $request = new \GuzzleHttp\Psr7\Request(
            $request['method'],
            $request['path'],
            $headers,
            $request['data']
        );

        $client->send($request);

        $this->assertCount(1, $container);
        $request = $container[0]['request'];
        $headers = $request->getHeaders();

        $this->assertArrayHasKey('Authorization', $headers);
        $this->assertCount(1, $headers['Authorization']);
        $this->assertEquals($result, $headers['Authorization'][0]);
    }

    /**
     * @backupGlobals enabled
     * @dataProvider createFromEdgeRcProvider
     * @param $section
     * @param $file
     */
    public function testCreateFromEdgeRcDefault($section, $file)
    {
        $_SERVER['HOME'] = __DIR__ . '/edgerc';
        $client = \Akamai\Open\EdgeGrid\Client::createFromEdgeRcFile($section, $file);
        $authentication = \PHPUnit\Framework\Assert::readAttribute($client, 'authentication');

        $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Client::class, $client);
        $this->assertEquals(
            [
                'client_token' => 'akab-client-token-xxx-xxxxxxxxxxxxxxxx',
                'client_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=',
                'access_token' => 'akab-access-token-xxx-xxxxxxxxxxxxxxxx'
            ],
            \PHPUnit\Framework\Assert::readAttribute($authentication, 'auth')
        );
        $this->assertEquals(
            'https://akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net',
            $client->getConfig('base_uri')
        );
        $this->assertEquals(2048, \PHPUnit\Framework\Assert::readAttribute($authentication, 'max_body_size'));
    }

    /**
     * @backupGlobals enabled
     */
    public function testCreateFromEnvNoSection()
    {
        $_ENV['AKAMAI_HOST'] = 'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net';
        $_ENV['AKAMAI_CLIENT_TOKEN'] = 'akab-client-token-xxx-xxxxxxxxxxxxxxxx';
        $_ENV['AKAMAI_CLIENT_SECRET'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=';
        $_ENV['AKAMAI_ACCESS_TOKEN'] = 'akab-access-token-xxx-xxxxxxxxxxxxxxxx';
        $_ENV['AKAMAI_MAX_SIZE'] = 2048;

        $client = \Akamai\Open\EdgeGrid\Client::createFromEnv();
        $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Client::class, $client);

        $authentication = \PHPUnit\Framework\Assert::readAttribute($client, 'authentication');
        $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Authentication::class, $authentication);

        $this->assertEquals(
            array(
                'client_token' => 'akab-client-token-xxx-xxxxxxxxxxxxxxxx',
                'client_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=',
                'access_token' => 'akab-access-token-xxx-xxxxxxxxxxxxxxxx'
            ),
            \PHPUnit\Framework\Assert::readAttribute($authentication, 'auth')
        );

        /** @var \GuzzleHttp\Psr7\Uri $base_uri */
        $base_uri = $client->getConfig('base_uri');

        $this->assertEquals(
            'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net',
            $base_uri->getHost()
        );
        $this->assertEquals(2048, \PHPUnit\Framework\Assert::readAttribute($authentication, 'max_body_size'));
    }

    /**
     * @backupGlobals enabled
     */
    public function testCreateFromEnvDefaultSection()
    {
        $_ENV['AKAMAI_DEFAULT_HOST'] = 'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net';
        $_ENV['AKAMAI_DEFAULT_CLIENT_TOKEN'] = 'akab-client-token-xxx-xxxxxxxxxxxxxxxx';
        $_ENV['AKAMAI_DEFAULT_CLIENT_SECRET'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=';
        $_ENV['AKAMAI_DEFAULT_ACCESS_TOKEN'] = 'akab-access-token-xxx-xxxxxxxxxxxxxxxx';
        $_ENV['AKAMAI_DEFAULT_MAX_SIZE'] = 2048;

        $client = \Akamai\Open\EdgeGrid\Client::createFromEnv();
        $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Client::class, $client);

        $authentication = \PHPUnit\Framework\Assert::readAttribute($client, 'authentication');
        $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Authentication::class, $authentication);

        $this->assertEquals(
            array(
                'client_token' => 'akab-client-token-xxx-xxxxxxxxxxxxxxxx',
                'client_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=',
                'access_token' => 'akab-access-token-xxx-xxxxxxxxxxxxxxxx'
            ),
            \PHPUnit\Framework\Assert::readAttribute($authentication, 'auth')
        );

        /** @var \GuzzleHttp\Psr7\Uri $base_uri */
        $base_uri = $client->getConfig('base_uri');

        $this->assertEquals(
            'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net',
            $base_uri->getHost()
        );
        $this->assertEquals(2048, \PHPUnit\Framework\Assert::readAttribute($authentication, 'max_body_size'));
    }

    /**
     * @backupGlobals enabled
     */
    public function testCreateFromEnvPreferSection()
    {
        $_ENV['AKAMAI_HOST'] = false;
        $_ENV['AKAMAI_CLIENT_TOKEN'] = false;
        $_ENV['AKAMAI_CLIENT_SECRET'] = false;
        $_ENV['AKAMAI_ACCESS_TOKEN'] = false;
        $_ENV['AKAMAI_MAX_SIZE'] = 0;

        $_ENV['AKAMAI_TESTING_HOST'] = 'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net';
        $_ENV['AKAMAI_TESTING_CLIENT_TOKEN'] = 'akab-client-token-xxx-xxxxxxxxxxxxxxxx';
        $_ENV['AKAMAI_TESTING_CLIENT_SECRET'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=';
        $_ENV['AKAMAI_TESTING_ACCESS_TOKEN'] = 'akab-access-token-xxx-xxxxxxxxxxxxxxxx';
        $_ENV['AKAMAI_TESTING_MAX_SIZE'] = 2048;

        $client = \Akamai\Open\EdgeGrid\Client::createFromEnv('testing');
        $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Client::class, $client);

        $authentication = \PHPUnit\Framework\Assert::readAttribute($client, 'authentication');
        $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Authentication::class, $authentication);

        $this->assertEquals(
            array(
                'client_token' => 'akab-client-token-xxx-xxxxxxxxxxxxxxxx',
                'client_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=',
                'access_token' => 'akab-access-token-xxx-xxxxxxxxxxxxxxxx'
            ),
            \PHPUnit\Framework\Assert::readAttribute($authentication, 'auth')
        );

        /** @var \GuzzleHttp\Psr7\Uri $base_uri */
        $base_uri = $client->getConfig('base_uri');

        $this->assertEquals(
            'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net',
            $base_uri->getHost()
        );
        $this->assertEquals(2048, \PHPUnit\Framework\Assert::readAttribute($authentication, 'max_body_size'));
    }

    /**
     * @backupGlobals enabled
     */
    public function testCreateFromEnvNoMaxSize()
    {
        $_ENV['AKAMAI_HOST'] = 'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net';
        $_ENV['AKAMAI_CLIENT_TOKEN'] = 'akab-client-token-xxx-xxxxxxxxxxxxxxxx';
        $_ENV['AKAMAI_CLIENT_SECRET'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=';
        $_ENV['AKAMAI_ACCESS_TOKEN'] = 'akab-access-token-xxx-xxxxxxxxxxxxxxxx';

        $client = \Akamai\Open\EdgeGrid\Client::createFromEnv('testing');
        $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Client::class, $client);

        $authentication = \PHPUnit\Framework\Assert::readAttribute($client, 'authentication');
        $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Authentication::class, $authentication);

        $this->assertEquals(
            array(
                'client_token' => 'akab-client-token-xxx-xxxxxxxxxxxxxxxx',
                'client_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=',
                'access_token' => 'akab-access-token-xxx-xxxxxxxxxxxxxxxx'
            ),
            \PHPUnit\Framework\Assert::readAttribute($authentication, 'auth')
        );
        $this->assertEquals(
            'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net',
            $authentication->getHost()
        );
        $this->assertEquals(131072, \PHPUnit\Framework\Assert::readAttribute($authentication, 'max_body_size'));
    }

    /**
     * @expectedException \Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException
     * @expectedExceptionMessage Environment variables AKAMAI_HOST or AKAMAI_DEFAULT_HOST do not exist
     */
    public function testCreateFromEnvInvalid()
    {
        $client = \Akamai\Open\EdgeGrid\Client::createFromEnv();
    }

    /**
     * @expectedException \Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException
     * @expectedExceptionMessage Environment variable AKAMAI_TESTING_HOST does not exist
     */
    public function testCreateFromEnvInvalidSection()
    {
        $client = \Akamai\Open\EdgeGrid\Client::createFromEnv('testing');
    }

    /**
     * @backupGlobals enabled
     */
    public function testCreateInstancePreferEnv()
    {
        $_ENV['AKAMAI_HOST'] = 'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net';
        $_ENV['AKAMAI_CLIENT_TOKEN'] = 'akab-client-token-xxx-xxxxxxxxxxxxxxxx';
        $_ENV['AKAMAI_CLIENT_SECRET'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=';
        $_ENV['AKAMAI_ACCESS_TOKEN'] = 'akab-access-token-xxx-xxxxxxxxxxxxxxxx';
        $_ENV['AKAMAI_MAX_SIZE'] = 2048;

        $client = \Akamai\Open\EdgeGrid\Client::createInstance(
            'default',
            __DIR__ . '/edgerc/.edgerc.default-testing'
        );
        $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Client::class, $client);

        $authentication = \PHPUnit\Framework\Assert::readAttribute($client, 'authentication');
        $this->assertInstanceOf(\Akamai\Open\EdgeGrid\Authentication::class, $authentication);

        $this->assertEquals(
            array(
                'client_token' => 'akab-client-token-xxx-xxxxxxxxxxxxxxxx',
                'client_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=',
                'access_token' => 'akab-access-token-xxx-xxxxxxxxxxxxxxxx'
            ),
            \PHPUnit\Framework\Assert::readAttribute($authentication, 'auth')
        );
        $this->assertEquals(
            'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net',
            $authentication->getHost()
        );
        $this->assertEquals(2048, \PHPUnit\Framework\Assert::readAttribute($authentication, 'max_body_size'));
    }

    public function testCreateInstanceFallbackEdgeRc()
    {
        $authentication = \Akamai\Open\EdgeGrid\Authentication::createInstance('default', __DIR__ . '/edgerc/.edgerc');

        $this->assertInstanceOf('\Akamai\Open\EdgeGrid\Authentication', $authentication);
        $this->assertEquals(
            array(
                'client_token' => 'akab-client-token-xxx-xxxxxxxxxxxxxxxx',
                'client_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=',
                'access_token' => 'akab-access-token-xxx-xxxxxxxxxxxxxxxx'
            ),
            \PHPUnit\Framework\Assert::readAttribute($authentication, 'auth')
        );
        $this->assertEquals(
            'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net',
            $authentication->getHost()
        );
        $this->assertEquals(2048, \PHPUnit\Framework\Assert::readAttribute($authentication, 'max_body_size'));
    }

    /**
     * @backupGlobals enabled
     */
    public function testCreateInstanceSection()
    {
        $_ENV['AKAMAI_TESTING_HOST'] = 'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net';
        $_ENV['AKAMAI_TESTING_CLIENT_TOKEN'] = 'akab-client-token-xxx-xxxxxxxxxxxxxxxx';
        $_ENV['AKAMAI_TESTING_CLIENT_SECRET'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=';
        $_ENV['AKAMAI_TESTING_ACCESS_TOKEN'] = 'akab-access-token-xxx-xxxxxxxxxxxxxxxx';
        $_ENV['AKAMAI_TESTING_MAX_SIZE'] = 2048;

        $authentication = \Akamai\Open\EdgeGrid\Authentication::createInstance('testing', __DIR__ . '/edgerc/.edgerc');

        $this->assertInstanceOf('\Akamai\Open\EdgeGrid\Authentication', $authentication);
        $this->assertEquals(
            array(
                'client_token' => 'akab-client-token-xxx-xxxxxxxxxxxxxxxx',
                'client_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=',
                'access_token' => 'akab-access-token-xxx-xxxxxxxxxxxxxxxx'
            ),
            \PHPUnit\Framework\Assert::readAttribute($authentication, 'auth')
        );
        $this->assertEquals(
            'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net',
            $authentication->getHost()
        );
        $this->assertEquals(2048, \PHPUnit\Framework\Assert::readAttribute($authentication, 'max_body_size'));
    }

    /**
     * @backupGlobals enabled
     */
    public function testCreateInstanceSectionFallback()
    {
        $_ENV['AKAMAI_HOST'] = false;
        $_ENV['AKAMAI_CLIENT_TOKEN'] = false;
        $_ENV['AKAMAI_CLIENT_SECRET'] = false;
        $_ENV['AKAMAI_ACCESS_TOKEN'] = false;
        $_ENV['AKAMAI_MAX_SIZE'] = 0;

        $authentication = \Akamai\Open\EdgeGrid\Authentication::createInstance(
            'testing',
            __DIR__ . '/edgerc/.edgerc.testing'
        );

        $this->assertInstanceOf('\Akamai\Open\EdgeGrid\Authentication', $authentication);
        $this->assertEquals(
            array(
                'client_token' => 'akab-client-token-xxx-xxxxxxxxxxxxxxxx',
                'client_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=',
                'access_token' => 'akab-access-token-xxx-xxxxxxxxxxxxxxxx'
            ),
            \PHPUnit\Framework\Assert::readAttribute($authentication, 'auth')
        );
        $this->assertEquals(
            'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net',
            $authentication->getHost()
        );
        $this->assertEquals(2048, \PHPUnit\Framework\Assert::readAttribute($authentication, 'max_body_size'));
    }

    /**
     * @backupGlobals enabled
     */
    public function testCreateInstanceSectionFallbackEnv()
    {
        $_ENV['AKAMAI_HOST'] = 'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net';
        $_ENV['AKAMAI_CLIENT_TOKEN'] = 'akab-client-token-xxx-xxxxxxxxxxxxxxxx';
        $_ENV['AKAMAI_CLIENT_SECRET'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=';
        $_ENV['AKAMAI_ACCESS_TOKEN'] = 'akab-access-token-xxx-xxxxxxxxxxxxxxxx';
        $_ENV['AKAMAI_MAX_SIZE'] = 2048;

        $authentication = \Akamai\Open\EdgeGrid\Authentication::createInstance('testing', __DIR__ . '/edgerc/.edgerc');

        $this->assertInstanceOf('\Akamai\Open\EdgeGrid\Authentication', $authentication);
        $this->assertEquals(
            array(
                'client_token' => 'akab-client-token-xxx-xxxxxxxxxxxxxxxx',
                'client_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=',
                'access_token' => 'akab-access-token-xxx-xxxxxxxxxxxxxxxx'
            ),
            \PHPUnit\Framework\Assert::readAttribute($authentication, 'auth')
        );
        $this->assertEquals(
            'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net',
            $authentication->getHost()
        );
        $this->assertEquals(2048, \PHPUnit\Framework\Assert::readAttribute($authentication, 'max_body_size'));
    }

    /**
     * @backupGlobals enabled
     */
    public function testCreateInstanceSectionFallbackInvalidEdgerc()
    {
        $_ENV['AKAMAI_HOST'] = 'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net';
        $_ENV['AKAMAI_CLIENT_TOKEN'] = 'akab-client-token-xxx-xxxxxxxxxxxxxxxx';
        $_ENV['AKAMAI_CLIENT_SECRET'] = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=';
        $_ENV['AKAMAI_ACCESS_TOKEN'] = 'akab-access-token-xxx-xxxxxxxxxxxxxxxx';
        $_ENV['AKAMAI_MAX_SIZE'] = 2048;

        $authentication = \Akamai\Open\EdgeGrid\Authentication::createInstance(
            'testing',
            __DIR__ . '/edgerc/.edgerc.invalid'
        );

        $this->assertInstanceOf('\Akamai\Open\EdgeGrid\Authentication', $authentication);
        $this->assertEquals(
            array(
                'client_token' => 'akab-client-token-xxx-xxxxxxxxxxxxxxxx',
                'client_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=',
                'access_token' => 'akab-access-token-xxx-xxxxxxxxxxxxxxxx'
            ),
            \PHPUnit\Framework\Assert::readAttribute($authentication, 'auth')
        );
        $this->assertEquals(
            'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net',
            $authentication->getHost()
        );
        $this->assertEquals(2048, \PHPUnit\Framework\Assert::readAttribute($authentication, 'max_body_size'));
    }

    /**
     * @expectedException \Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException
     * @expectedExceptionMessage Unable to create instance using environment or .edgerc file
     */
    public function testCreateInstanceSectionFallbackInvalidEdgercNoEnv()
    {
        try {
            $client = \Akamai\Open\EdgeGrid\Client::createInstance(
                'testing',
                __DIR__ . '/edgerc/.edgerc.invalid'
            );
        } catch (\Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException $e) {
            $this->assertInstanceOf(
                '\Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException',
                $e->getPrevious()
            );

            $this->assertEquals("Section \"testing\" does not exist!", $e->getPrevious()->getMessage());

            throw $e;
        }
    }

    /**
     * @expectedException \Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException
     * @expectedExceptionMessage Unable to create instance using environment or .edgerc file
     * @backupGlobals enabled
     */
    public function testCreateInstanceInvalidEdgercInvalidEnv()
    {
        $_ENV['AKAMAI_TESTING_HOST'] = 'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net';

        try {
            $client = \Akamai\Open\EdgeGrid\Client::createInstance("testing", __DIR__ . '/edgerc/.edgerc');
        } catch (\Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException $e) {
        }

        $this->assertTrue(isset($e), 'Exception not thrown');

        $this->assertInstanceOf(
            \Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException::class,
            $e->getPrevious()
        );

        $this->assertEquals('Section "testing" does not exist!', $e->getPrevious()->getMessage());

        throw $e;
    }

    /**
     * @expectedException \Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException
     * @expectedExceptionMessage Unable to create instance using environment or .edgerc file
     * @backupGlobals enabled
     */
    public function testCreateInstanceInvalidEdgercInvalidEnvSection()
    {
        $_ENV['AKAMAI_TESTING_HOST'] = 'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net';

        try {
            $authentication = \Akamai\Open\EdgeGrid\Client::createInstance(
                'testing',
                __DIR__ . '/edgerc/.edgerc'
            );
        } catch (\Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException $e) {
            $this->assertInstanceOf(
                '\Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException',
                $e->getPrevious()
            );

            $this->assertEquals(
                'Section "testing" does not exist!',
                $e->getPrevious()->getMessage()
            );

            throw $e;
        }
    }

    /**
     * @expectedException \Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException
     * @expectedExceptionMessage Unable to create instance using environment or .edgerc file
     * @backupGlobals enabled
     */
    public function testCreateInstanceInvalidEdgercInvalidEnvSectionInvalidDefaultEnv()
    {
        $_ENV['AKAMAI_HOST'] = 'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net';

        try {
            $authentication = \Akamai\Open\EdgeGrid\Client::createInstance(
                'testing',
                __DIR__ . '/edgerc/.edgerc'
            );
        } catch (\Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException $e) {
            $this->assertInstanceOf(
                '\Akamai\Open\EdgeGrid\Authentication\Exception\ConfigException',
                $e->getPrevious()
            );

            $this->assertEquals(
                'Environment variables AKAMAI_CLIENT_TOKEN or AKAMAI_DEFAULT_CLIENT_TOKEN do not exist',
                $e->getPrevious()->getMessage()
            );

            throw $e;
        }
    }

    public function testHostnameWithTrailingSlash()
    {
        $client = new \Akamai\Open\EdgeGrid\Client();
        $client->setHost('akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net/');

        $this->assertEquals(
            'akaa-baseurl-xxxxxxxxxxx-xxxxxxxxxxxxx.luna.akamaiapis.net',
            $client->getConfig('headers')['Host']
        );
    }

    public function testDefaultTimeout()
    {
        // Mock the response, we don't care about it
        $container = [];
        $handler = $this->getMockHandler([new Response(200)], $container);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $this->assertArrayHasKey('timeout', $client->getConfig());
        $this->assertEquals(Client::DEFAULT_REQUEST_TIMEOUT, $client->getConfig('timeout'));

        $client->setAuth('test', 'test', 'test');

        $client->get('/test');
        $this->assertEquals(Client::DEFAULT_REQUEST_TIMEOUT, $container[0]['options']['timeout']);
    }

    public function testTimeoutOption()
    {
        // Mock the response, we don't care about it
        $container = [];
        $handler = $this->getMockHandler([new Response(200), new Response(200)], $container);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
                'timeout' => 2
            ]
        );

        $this->assertArrayHasKey('timeout', $client->getConfig());
        $this->assertEquals(2, $client->getConfig('timeout'));

        $client->setAuth('test', 'test', 'test');

        $client->get('/test');
        $this->assertEquals(2, end($container)['options']['timeout']);

        $client->get('/test', ['timeout' => 5]);
        $this->assertEquals(5, end($container)['options']['timeout']);
    }

    public function testSetTimeout()
    {
        // Mock the response, we don't care about it
        $container = [];
        $handler = $this->getMockHandler([new Response(200), new Response(200), new Response(200)], $container);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $this->assertArrayHasKey('timeout', $client->getConfig());
        $this->assertEquals(Client::DEFAULT_REQUEST_TIMEOUT, $client->getConfig('timeout'));

        $client->get('/test', ['timeout' => 2]);
        $this->assertEquals(2, end($container)['options']['timeout']);
        $this->assertEquals(Client::DEFAULT_REQUEST_TIMEOUT, $client->getConfig('timeout'));

        $client->setTimeout(5);
        $client->get('/test');

        $this->assertEquals(5, $client->getConfig('timeout'));
        $this->assertEquals(5, end($container)['options']['timeout']);
    }

    public function testStaticDebugSingle()
    {
        $container = [];
        $handler = $this->getMockHandler([new Response(200)], $container);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setDebug(true);

        $client->get('/test');
        $this->assertEquals(true, is_resource(end($container)['options']['debug']));
    }

    public function testInstanceDebugSingle()
    {
        $container = [];
        $handler = $this->getMockHandler([new Response(200)], $container);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        $client->setInstanceDebug(true);
        $client->get('/test');
        $this->assertEquals(true, is_resource(end($container)['options']['debug']));
    }

    public function testDebugOverrideSingle()
    {
        $container = [];
        $handler = $this->getMockHandler([new Response(200), new Response(200)], $container);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        Client::setDebug(true);
        $client->setInstanceDebug(false);

        $client->get('/test');
        $this->assertEquals(false, is_resource(end($container)['options']['debug']));
    }

    public function testInstanceDebugOptionSingle()
    {
        $container = [];
        $handler = $this->getMockHandler([new Response(200), new Response(200), new Response(200)], $container);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        $client->get('/test', ['debug' => true]);
        $this->assertEquals(true, is_resource(end($container)['options']['debug']));

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');
        $client->setInstanceDebug(true);
        $client->get('/test', ['debug' => false]);
        $this->assertEquals(false, is_resource(end($container)['options']['debug']));
        $this->assertFalse(end($container)['options']['debug']);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');
        Client::setDebug(true);
        $client->get('/test', ['debug' => false]);
        $this->assertEquals(false, is_resource(end($container)['options']['debug']));
        $this->assertFalse(end($container)['options']['debug']);
    }

    public function testNonApiCall()
    {
        $container = [];
        $handler = $this->getMockHandler([new Response(200), new Response(200), new Response(200)], $container);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $response = $client->get('/test');
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Response::class, $response);
        $this->assertEquals('http', end($container)['request']->getUri()->getScheme());
        $this->assertEquals('example.org', end($container)['request']->getUri()->getHost());
        $this->assertArrayNotHasKey('Authentication', end($container)['request']->getHeaders());

        $response = $client->get('http://example.com/test');
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Response::class, $response);
        $this->assertEquals('http', end($container)['request']->getUri()->getScheme());
        $this->assertEquals('example.com', end($container)['request']->getUri()->getHost());
        $this->assertArrayNotHasKey('Authentication', end($container)['request']->getHeaders());

        $response = $client->get('https://example.net/test');
        $this->assertInstanceOf(\GuzzleHttp\Psr7\Response::class, $response);
        $this->assertEquals('https', end($container)['request']->getUri()->getScheme());
        $this->assertEquals('example.net', end($container)['request']->getUri()->getHost());
        $this->assertArrayNotHasKey('Authentication', end($container)['request']->getHeaders());
    }

    public function testForceHttps()
    {
        $client = new Client(
            [
                'base_uri' => 'example.org'
            ]
        );

        $uri = $client->getConfig('base_uri');
        $this->assertEquals('https', parse_url($uri, PHP_URL_SCHEME));
    }

    /**
     * @param $method
     * @param $responses
     * @param $path
     * @param $expected
     * @internal param $response
     * @dataProvider loggingProvider
     */
    public function testLogging($method, $responses, $path, $expected)
    {
        $handler = $this->getMockHandler($responses);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $logHandler = $this->setLogger($client);

        try {
            $client->{$method}($path);
        } catch (\Exception $e) {
        }

        $records = [];
        foreach ($logHandler->getRecords() as $record) {
            $records[] = $record['message'];
        }
        $this->assertEquals($expected, $records);
    }

    public function testLoggingRedirect()
    {
        $handler = $this->getMockHandler([
            new Response(301, ['Location' => '/redirected']),
            new Response(200, ['Content-Type' => 'application/json'])
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $logHandler = $this->setLogger($client);

        try {
            $client->get('/redirect');
        } catch (\Exception $e) {
        }

        $records = [];
        foreach ($logHandler->getRecords() as $record) {
            $records[] = $record['message'];
        }
        $this->assertEquals(['GET /redirect 301 ', 'GET /redirected 200 application/json'], $records);
    }

    public function testLoggingDefault()
    {
        $client = new Client();
        $client->setLogger();

        $logger = \PHPUnit\Framework\Assert::readAttribute($client, 'logger');
        $this->assertInstanceOf(
            \Closure::class,
            $logger
        );

        $reflection = new \ReflectionFunction($logger);
        $args = $reflection->getParameters();
        $this->assertTrue(array_shift($args)->isCallable());
    }

    public function testLoggingRequestHandler()
    {
        $handler = $this->getMockHandler([
            new Response(200, ['Content-Type' => 'application/json'])
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
            ]
        );
        $logHandler = $this->setLogger($client);

        $client->get('/test', ['handler' => $handler]);
        $records = [];
        foreach ($logHandler->getRecords() as $record) {
            $records[] = $record['message'];
        }
        $this->assertEquals(['GET /test 200 application/json'], $records);
    }

    public function testSetSimpleLog()
    {
        $handler = $this->getMockHandler([
            new Response(200, ['Content-Type' => 'application/json'])
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $fp = fopen('php://memory', 'wb+');
        $client->setSimpleLog($fp, '{method} {target} {code}');
        $client->get('/test');

        fseek($fp, 0);
        $this->assertEquals('GET /test 200', fgets($fp));
    }

    public function testSetSimpleLogInvalid()
    {
        $logger = new SimpleLog();
        $client = new Client();
        $client->setLogger($logger);

        $this->assertFalse($client->setSimpleLog('test'));
    }

    public function makeAuthHeaderProvider()
    {
        $testdata = json_decode(file_get_contents(__DIR__ . '/testdata.json'), true);
        $tests = $testdata['tests'];
        unset($testdata['tests']);

        foreach ($tests as $test) {
            yield [
                'name' => $test['testName'],
                'options' => array_merge($testdata, $test['request']),
                'request' => array_merge(['data' => ''], $test['request']),
                'result' => $test['expectedAuthorization'],
            ];
        }
    }

    public function createFromEdgeRcProvider()
    {
        return [
            [
                'section' => null,
                'file' => null,
            ],
            [
                'section' => 'default',
                'file' => null,
            ],
            [
                'section' => 'testing',
                'file' => __DIR__ . '/edgerc/.edgerc.testing',
            ],
            [
                'section' => 'testing',
                'file' => __DIR__ . '/edgerc/.edgerc.default-testing',
            ]
        ];
    }

    public function loggingProvider()
    {
        return [
            [
                'get',
                [new Response(200)],
                '/test',
                [
                    'GET /test 200 '
                ]
            ],
            [
                'get',
                [new Response(404)],
                '/error',
                [
                    'GET /error 404 '
                ]
            ],
            [
                'post',
                [new Response(500)],
                '/error',
                [
                    'POST /error 500 '
                ]
            ],
            [
                'put',
                [new Response(200, ['Content-Type' => 'application/json'])],
                '/test',
                [
                    'PUT /test 200 application/json'
                ]
            ],
            [
                'options',
                [new Response(405, ['Content-Type' => 'application/json'])],
                '/error',
                [
                    'OPTIONS /error 405 application/json'
                ]
            ],
            [
                'get',
                [new Response(503, ['Content-Type' => 'text/csv'])],
                '/error',
                [
                    'GET /error 503 text/csv'
                ]
            ],
            [
                'get',
                [
                    new Response(301, ['Location' => '/redirect']),
                    new Response(200)
                ],
                '/notthere',
                [
                    'GET /notthere 301 ',
                    'GET /redirect 200 ',
                ]
            ]
        ];
    }

    public function getMockHandler($requests, array &$container = null)
    {
        $mock = new MockHandler($requests);
        $container = [];
        $history = Middleware::history($container);

        $handler = HandlerStack::create($mock);
        $handler->push($history, 'history');

        return $handler;
    }

    /**
     * @param \Akamai\Open\EdgeGrid\Client $client
     * @return \Monolog\Handler\TestHandler
     */
    protected function setLogger(Client $client)
    {
        $handler = new \Monolog\Handler\TestHandler();
        $logger = new \Monolog\Logger('Test Logger', [$handler]);
        $client->setLogger($logger, '{method} {target} {code} {res_header_content-type}');

        return $handler;
    }
}
