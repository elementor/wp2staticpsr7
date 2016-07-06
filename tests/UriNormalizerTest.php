<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriNormalizer;

/**
 * @covers GuzzleHttp\Psr7\UriNormalizer
 */
class UriNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getEmptyPathTestCases
     */
    public function testReplaceEmptyPath($uri, $expected)
    {
        $normalizedUri = UriNormalizer::normalize(new Uri($uri), UriNormalizer::REPLACE_EMPTY_PATH);

        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $normalizedUri);
        $this->assertSame($expected, (string) $normalizedUri);
    }

    public function getEmptyPathTestCases()
    {
        return [
            ['http://example.org', 'http://example.org/'],
            ['https://example.org', 'https://example.org/'],
            ['urn://example.org', 'urn://example.org'],
        ];
    }

    public function testRemoveDefaultPort()
    {
        $uri = $this->getMock('Psr\Http\Message\UriInterface');
        $uri->expects($this->any())->method('getScheme')->will($this->returnValue('http'));
        $uri->expects($this->any())->method('getPort')->will($this->returnValue(80));
        $uri->expects($this->once())->method('withPort')->with(null)->will($this->returnValue(new Uri('http://example.org')));

        $normalizedUri = UriNormalizer::normalize($uri, UriNormalizer::REMOVE_DEFAULT_PORT);

        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $normalizedUri);
        $this->assertNull($normalizedUri->getPort());
    }

    public function testRemoveDotSegments()
    {
        $uri = new Uri('http://example.org/../a/b/../c/./d.html');
        $normalizedUri = UriNormalizer::normalize($uri, UriNormalizer::REMOVE_DOT_SEGMENTS);

        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $normalizedUri);
        $this->assertSame('http://example.org/a/c/d.html', (string) $normalizedUri);
    }

    public function testRemoveDotSegmentsOfAbsolutePathReference()
    {
        $uri = new Uri('/../a/b/../c/./d.html');
        $normalizedUri = UriNormalizer::normalize($uri, UriNormalizer::REMOVE_DOT_SEGMENTS);

        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $normalizedUri);
        $this->assertSame('/a/c/d.html', (string) $normalizedUri);
    }

    public function testRemoveDotSegmentsOfRelativePathReference()
    {
        $uri = new Uri('../c/./d.html');
        $normalizedUri = UriNormalizer::normalize($uri, UriNormalizer::REMOVE_DOT_SEGMENTS);

        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $normalizedUri);
        $this->assertSame('../c/./d.html', (string) $normalizedUri);
    }

    public function testRemoveDuplicateSlashes()
    {
        $uri = new Uri('http://example.org//foo///bar/bam.html');
        $normalizedUri = UriNormalizer::normalize($uri, UriNormalizer::REMOVE_DUPLICATE_SLASHES);

        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $normalizedUri);
        $this->assertSame('http://example.org/foo/bar/bam.html', (string) $normalizedUri);
    }

    public function testSortQueryParameters()
    {
        $uri = new Uri('?lang=en&article=fred');
        $normalizedUri = UriNormalizer::normalize($uri, UriNormalizer::SORT_QUERY_PARAMETERS);

        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $normalizedUri);
        $this->assertSame('?article=fred&lang=en', (string) $normalizedUri);
    }

    public function testSortQueryParametersWithSameKeys()
    {
        $uri = new Uri('?a=b&b=c&a=a&a&b=a&b=b&a=d&a=c');
        $normalizedUri = UriNormalizer::normalize($uri, UriNormalizer::SORT_QUERY_PARAMETERS);

        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $normalizedUri);
        $this->assertSame('?a&a=a&a=b&a=c&a=d&b=a&b=b&b=c', (string) $normalizedUri);
    }
}
