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
namespace Akamai\Open\EdgeGrid\Tests\Handler;

use GuzzleHttp\Psr7\Response;
use Akamai\Open\EdgeGrid\Client;

/**
 * @requires PHP 5.5
 */
class VerboseTest extends \PHPUnit\Framework\TestCase
{
    public function teardown(): void
    {
        Client::setVerbose(false);
    }

    public function testInstanceVerboseSingle()
    {
        $handler = $this->getMockHandler([new Response(200, [], json_encode(['test' => 'data']))]);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        $client->setInstanceVerbose(true);

        $expectedOutput = hex2bin(
            '1b5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333' .
            'b30316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a1b5b' .
            '33363b30316d3d3d3d3e205b564552424f53455d20526573706f6e73653a200a1b5b33333b3' .
            '0316d7b0a202020202274657374223a202264617461220a7d1b5b33393b34393b30306d0a'
        );
        $this->expectOutputString($expectedOutput);

        $client->get('/test');
    }

    public function testInstanceVerboseMultiple()
    {
        $handler = $this->getMockHandler([
            new Response(200, [], json_encode(['test' => 'data'])),
            new Response(200, [], json_encode(['test' => 'data2', ['foo', 'bar'], false, null, 123, 0.123]))
        ]);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );
        $client->setAuth('test', 'test', 'test');

        $client->setInstanceVerbose(true);

        $expectedOutput = hex2bin(
            '1b5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333' .
            'b30316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a1b5b' .
            '33363b30316d3d3d3d3e205b564552424f53455d20526573706f6e73653a200a1b5b33333b3' .
            '0316d7b0a202020202274657374223a202264617461220a7d1b5b33393b34393b30306d0a1b' .
            '5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333b3' .
            '0316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a1b5b33' .
            '363b30316d3d3d3d3e205b564552424f53455d20526573706f6e73653a200a1b5b33333b303' .
            '16d7b0a202020202274657374223a20226461746132222c0a202020202230223a205b0a2020' .
            '20202020202022666f6f222c0a202020202020202022626172220a202020205d2c0a2020202' .
            '02231223a2066616c73652c0a202020202232223a206e756c6c2c0a202020202233223a2031' .
            '32332c0a202020202234223a20302e3132330a7d1b5b33393b34393b30306d0a'
        );
        $this->expectOutputString($expectedOutput);

        $client->get('/test1');
        $client->get('/test2');
    }

    public function testStaticVerboseSingle()
    {
        $handler = $this->getMockHandler([new Response(200, [], json_encode(['test' => 'data']))]);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setVerbose(true);

        $expectedOutput = hex2bin(
            '1b5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333' .
            'b30316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a1b5b' .
            '33363b30316d3d3d3d3e205b564552424f53455d20526573706f6e73653a200a1b5b33333b3' .
            '0316d7b0a202020202274657374223a202264617461220a7d1b5b33393b34393b30306d0a'
        );
        $this->expectOutputString($expectedOutput);

        $client->get('/test');
    }

    public function testStaticVerboseMultiple()
    {
        $handler = $this->getMockHandler([
            new Response(200, [], json_encode(['test' => 'data'])),
            new Response(200, [], json_encode(['test' => 'data2', ['foo', 'bar'], false, null, 123, 0.123]))
        ]);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setVerbose(true);

        $expectedOutput = hex2bin(
            '1b5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333' .
            'b30316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a1b5b' .
            '33363b30316d3d3d3d3e205b564552424f53455d20526573706f6e73653a200a1b5b33333b3' .
            '0316d7b0a202020202274657374223a202264617461220a7d1b5b33393b34393b30306d0a1b' .
            '5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333b3' .
            '0316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a1b5b33' .
            '363b30316d3d3d3d3e205b564552424f53455d20526573706f6e73653a200a1b5b33333b303' .
            '16d7b0a202020202274657374223a20226461746132222c0a202020202230223a205b0a2020' .
            '20202020202022666f6f222c0a202020202020202022626172220a202020205d2c0a2020202' .
            '02231223a2066616c73652c0a202020202232223a206e756c6c2c0a202020202233223a2031' .
            '32332c0a202020202234223a20302e3132330a7d1b5b33393b34393b30306d0a'
        );
        $this->expectOutputString($expectedOutput);

        $client->get('/test');
        $client->get('/test2');
    }

    public function testVerboseOverrideSingle()
    {
        $handler = $this->getMockHandler([new Response(200, [], json_encode(['test' => 'data']))]);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setVerbose(true);
        $client->setInstanceVerbose(false);

        $this->expectOutputString('');

        $client->get('/test');
    }

    public function testVerboseOverrideMultiple()
    {
        $handler = $this->getMockHandler([
            new Response(200, [], json_encode(['test' => 'data'])),
            new Response(200, [], json_encode(['test' => 'data2', ['foo', 'bar'], false, null, 123, 0.123]))
        ]);
        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setVerbose(true);
        $client->setInstanceVerbose(false);

        $this->expectOutputString('');

        $client->get('/test');
        $client->get('/test2');
    }

    public function testVerboseRedirect()
    {
        $handler = $this->getMockHandler([
            new Response(301, ['Location' => 'http://example.org/redirected'], json_encode(['test' => 'data'])),
            new Response(200, [], json_encode(['test' => 'data2', ['foo', 'bar'], false, null, 123, 0.123]))
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setVerbose(true);

        $expectedOutput = hex2bin(
            '1b5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333' .
            'b30316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a1b5b' .
            '33363b30316d3d3d3d3e205b564552424f53455d20526564697265637465643a20687474703' .
            'a2f2f6578616d706c652e6f72672f726564697265637465641b5b33393b34393b30306d0a1b' .
            '5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333b3' .
            '0316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a1b5b33' .
            '363b30316d3d3d3d3e205b564552424f53455d20526573706f6e73653a200a1b5b33333b303' .
            '16d7b0a202020202274657374223a20226461746132222c0a202020202230223a205b0a2020' .
            '20202020202022666f6f222c0a202020202020202022626172220a202020205d2c0a2020202' .
            '02231223a2066616c73652c0a202020202232223a206e756c6c2c0a202020202233223a2031' .
            '32332c0a202020202234223a20302e3132330a7d1b5b33393b34393b30306d0a'
        );
        $this->expectOutputString($expectedOutput);

        $client->get('/redirect');
    }

    public function testVerboseNonJson()
    {
        $handler = $this->getMockHandler([
            new Response(200, [], 'String body')
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setVerbose(true);

        $expectedOutput = hex2bin(
            '1b5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333' .
            'b30316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a1b5b' .
            '33363b30316d3d3d3d3e205b564552424f53455d20526573706f6e73653a200a1b5b33333b3' .
            '0316d537472696e6720626f64791b5b33393b34393b30306d0a'
        );
        $this->expectOutputString($expectedOutput);

        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\ClientException $e) {
        }
    }

    public function testVerboseRequestHandler()
    {
        $handler = $this->getMockHandler([
            new Response(200, [], 'String body')
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
            ]
        );

        Client::setVerbose(true);

        $expectedOutput = hex2bin(
            '1b5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333' .
            'b30316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a1b5b' .
            '33363b30316d3d3d3d3e205b564552424f53455d20526573706f6e73653a200a1b5b33333b3' .
            '0316d537472696e6720626f64791b5b33393b34393b30306d0a'
        );
        $this->expectOutputString($expectedOutput);

        try {
            $client->get('/error', ['handler' => $handler]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
        }
    }

    public function testVerboseNoResponse()
    {
        $handler = $this->getMockHandler([
            new Response(101)
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setVerbose(true);

        $expectedOutput = hex2bin(
            '1b5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333' .
            'b30316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a1b5b' .
            '33363b30316d3d3d3d3e205b564552424f53455d20526573706f6e73653a200a1b5b33333b3' .
            '0316d4e6f20726573706f6e736520626f64792072657475726e65641b5b33393b34393b3030' .
            '6d0a'
        );
        $this->expectOutputString($expectedOutput);


        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\ClientException $e) {
        }
    }

    public function testVerboseError()
    {
        $handler = $this->getMockHandler([
            new Response(404, [], json_encode(['test' => 'data2', ['foo', 'bar'], false, null, 123, 0.123]))
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $fp = fopen('php://memory', 'ab+');
        Client::setVerbose([$fp, $fp]);

        $this->expectOutputString('');
        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\ClientException $e) {
        }

        fseek($fp, 0);
        $output = '';
        do {
            $output .= fgets($fp);
        } while (!feof($fp));

        $expectedOutput = hex2bin(
            '1b5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333b30316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a1b5b33313b30316d3d3d3d3e205b4552524f525d20416e206572726f72206f636375727265643a200a1b5b33333b30316d7b0a202020202274657374223a20226461746132222c0a202020202230223a205b0a202020202020202022666f6f222c0a202020202020202022626172220a202020205d2c0a202020202231223a2066616c73652c0a202020202232223a206e756c6c2c0a202020202233223a203132332c0a202020202234223a20302e3132330a7d1b5b33393b34393b30306d0a'
        );

        $this->assertEquals($expectedOutput, $output);
    }

    public function testVerboseErrorNonJson()
    {
        $handler = $this->getMockHandler([
            new Response(404, [], 'String body')
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $fp = fopen('php://memory', 'ab+');
        Client::setVerbose($fp);

        $this->expectOutputString('');
        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\ClientException $e) {
        }

        fseek($fp, 0);
        $output = '';
        do {
            $output .= fgets($fp);
        } while (!feof($fp));

        $expectedOutput = hex2bin(
            '1b5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333b30316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a1b5b33313b30316d3d3d3d3e205b4552524f525d20416e206572726f72206f636375727265643a200a1b5b33333b30316d537472696e6720626f64791b5b33393b34393b30306d0a'
        );

        $this->assertEquals($expectedOutput, $output);
    }

    public function testVerboseMixed()
    {
        $handler = $this->getMockHandler([
            new Response(200, [], json_encode(['test' => 'data'])),
            new Response(404, [], json_encode(['test' => 'data2', ['foo', 'bar'], false, null, 123, 0.123]))
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        $fp = fopen('php://memory', 'ab+');
        Client::setVerbose(['php://output', $fp]);


        $expectedOutput = hex2bin(
            '1b5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333b30316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a1b5b33363b30316d3d3d3d3e205b564552424f53455d20526573706f6e73653a200a1b5b33333b30316d7b0a202020202274657374223a202264617461220a7d1b5b33393b34393b30306d0a1b5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333b30316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a'
        );
        $this->expectOutputString($expectedOutput);

        try {
            $client->get('/success');
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\ClientException $e) {
        }

        fseek($fp, 0);
        $output = '';
        do {
            $output .= fgets($fp);
        } while (!feof($fp));

        $expectedError = hex2bin(
            '1b5b33313b30316d3d3d3d3e205b4552524f525d20416e206572726f72206f6363757272656' .
            '43a200a1b5b33333b30316d7b0a202020202274657374223a20226461746132222c0a202020' .
            '202230223a205b0a202020202020202022666f6f222c0a202020202020202022626172220a2' .
            '02020205d2c0a202020202231223a2066616c73652c0a202020202232223a206e756c6c2c0a' .
            '202020202233223a203132332c0a202020202234223a20302e3132330a7d1b5b33393b34393' .
            'b30306d0a'
        );

        $this->assertEquals($expectedError, $output);
    }

    public function testVerboseResponseExceptionNoCode()
    {
        $handler = $this->getMockHandler([
            new \GuzzleHttp\Exception\RequestException('Error message', new \GuzzleHttp\Psr7\Request('GET', '/test'))
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setVerbose(true);

        $expectedOutput = hex2bin(
            '1b5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333' .
            'b30316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a1b5b' .
            '33313b30316d3d3d3d3e205b4552524f525d20416e206572726f72206f636375727265643a2' .
            '00a1b5b33333b30316d4572726f72206d6573736167651b5b33393b34393b30306d0a'
        );
        $this->expectOutputString($expectedOutput);

        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\RequestException $e) {
        }
    }

    public function testVerboseResponseExceptionWithCode()
    {
        $handler = $this->getMockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'Error message',
                new \GuzzleHttp\Psr7\Request('GET', '/test'),
                new Response(500)
            )
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setVerbose(true);

        $expectedOutput = hex2bin(
            '1b5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333' .
            'b30316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a1b5b' .
            '33313b30316d3d3d3d3e205b4552524f525d20416e206572726f72206f636375727265643a2' .
            '00a1b5b33333b30316d3530303a204572726f72206d6573736167651b5b33393b34393b3030' .
            '6d0a'
        );
        $this->expectOutputString($expectedOutput);

        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\RequestException $e) {
        }
    }

    public function testVerboseResponseExceptionWithBody()
    {
        $handler = $this->getMockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'Error message',
                new \GuzzleHttp\Psr7\Request('GET', '/test'),
                new Response(500, [], json_encode(['errorString' => 'An error']))
            )
        ]);

        $client = new Client(
            [
                'base_uri' => 'http://example.org',
                'handler' => $handler,
            ]
        );

        Client::setVerbose(true);

        $expectedOutput = hex2bin(
            '1b5b33363b30316d3d3d3d3e205b564552424f53455d20526571756573743a200a1b5b33333' .
            'b30316d4e6f207265717565737420626f64792073656e741b5b33393b34393b30306d0a1b5b' .
            '33313b30316d3d3d3d3e205b4552524f525d20416e206572726f72206f636375727265643a2' .
            '00a1b5b33333b30316d3530303a204572726f72206d6573736167650a1b5b33333b30316d7b' .
            '226572726f72537472696e67223a22416e206572726f72227d1b5b33393b34393b30306d0a'
        );
        $this->expectOutputString($expectedOutput);

        try {
            $client->get('/error');
        } catch (\GuzzleHttp\Exception\RequestException $e) {
        }
    }

    public function testVerboseSingleStreamString()
    {
        $verbose = new \Akamai\Open\EdgeGrid\Handler\Verbose('php://memory');

        $fp = \PHPUnit\Framework\Assert::readAttribute($verbose, 'outputStream');
        $fp2 = \PHPUnit\Framework\Assert::readAttribute($verbose, 'errorStream');

        $this->assertSame($fp, $fp2);
        $this->assertEquals(stream_get_meta_data($fp)['uri'], 'php://memory');
    }

    /**
     * @expectedException \Akamai\Open\EdgeGrid\Exception\HandlerException\IOException
     * @expectedExceptionMessage Unable to use output stream: error://stream
     */
    public function testVerboseSingleStreamStringInvalid()
    {
        $verbose = new \Akamai\Open\EdgeGrid\Handler\Verbose('error://stream');
    }

    public function testVerboseDualStreamString()
    {
        $verbose = new \Akamai\Open\EdgeGrid\Handler\Verbose('php://memory', 'php://temp');

        $fp = \PHPUnit\Framework\Assert::readAttribute($verbose, 'outputStream');
        $fp2 = \PHPUnit\Framework\Assert::readAttribute($verbose, 'errorStream');

        $this->assertNotSame($fp, $fp2);
        $this->assertEquals(stream_get_meta_data($fp)['uri'], 'php://memory');
        $this->assertEquals(stream_get_meta_data($fp2)['uri'], 'php://temp');
    }

    /**
     * @expectedException \Akamai\Open\EdgeGrid\Exception\HandlerException\IOException
     * @expectedExceptionMessage Unable to use error stream: error://stream
     */
    public function testVerboseDualStreamStringErrorInvalid()
    {
        $verbose = new \Akamai\Open\EdgeGrid\Handler\Verbose('php://input', 'error://stream');
    }

    /**
     * @expectedException \Akamai\Open\EdgeGrid\Exception\HandlerException\IOException
     * @expectedExceptionMessage Unable to use output stream: error://stream
     */
    public function testVerboseDualStreamStringInvalid()
    {
        $verbose = new \Akamai\Open\EdgeGrid\Handler\Verbose('error://stream', 'error://stream2');
    }

    public function getMockHandler($request, array &$container = null)
    {
        $client = new \Akamai\Open\EdgeGrid\Tests\ClientTest();
        return $client->getMockHandler($request, $container);
    }
}
