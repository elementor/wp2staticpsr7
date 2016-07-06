<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\UriInterface;

/**
 * Provides methods to normalize and compare URIs.
 *
 * @author Tobias Schultze
 *
 * @link https://tools.ietf.org/html/rfc3986#section-6
 */
final class UriNormalizer
{
    /**
     * Default normalizations which only include the ones that preserve semantics.
     *
     * self::CAPITALIZE_PERCENT_ENCODING | self::DECODE_UNRESERVED_CHARACTERS | self::REPLACE_EMPTY_PATH |
     * self::REMOVE_DEFAULT_PORT | self::REMOVE_DOT_SEGMENTS
     */
    const PRESERVING_NORMALIZATIONS = 31;

    /**
     * All letters within a percent-encoding triplet (e.g., "%3A") are case-insensitive, and should be capitalized.
     *
     * Example: http://example.org/a%c2%b1b → http://example.org/a%C2%B1b
     */
    const CAPITALIZE_PERCENT_ENCODING = 1;

    /**
     * Decodes percent-encoded octets of unreserved characters.
     *
     * For consistency, percent-encoded octets in the ranges of ALPHA (%41–%5A and %61–%7A), DIGIT (%30–%39),
     * hyphen (%2D), period (%2E), underscore (%5F), or tilde (%7E) should not be created by URI producers and,
     * when found in a URI, should be decoded to their corresponding unreserved characters by URI normalizers.
     *
     * Example: http://example.org/%7Eusern%61me/ → http://example.org/~username/
     */
    const DECODE_UNRESERVED_CHARACTERS = 2;

    /**
     * Converts the empty path to "/" for http and https URIs.
     *
     * Example: http://example.org → http://example.org/
     */
    const REPLACE_EMPTY_PATH = 4;

    /**
     * Removes the default port of the given URI scheme from the URI.
     *
     * Example: http://example.org:80/ → http://example.org/
     */
    const REMOVE_DEFAULT_PORT = 8;

    /**
     * Removes unnecessary dot-segments.
     *
     * Dot-segments in relative-path references are not removed as it would
     * change the semantics of the URI reference.
     *
     * Example: http://example.org/../a/b/../c/./d.html → http://example.org/a/c/d.html
     */
    const REMOVE_DOT_SEGMENTS = 16;

    /**
     * Paths which include two or more adjacent slashes are converted to one.
     *
     * Webservers usually ignore duplicate slashes and treat those URIS equivalent.
     * But in theory those URIs do not need to be equivalent. So this normalization
     * may change the semantics. Encoded slashes (%2F) are not removed.
     *
     * Example: http://example.org//foo///bar.html → http://example.org/foo/bar.html
     */
    const REMOVE_DUPLICATE_SLASHES = 32;

    /**
     * Sort query parameters with their values in alphabetical order.
     *
     * However, the order of parameters in a URI may be significant (this is not defined by the standard).
     * So this normalization is not safe and may change the semantics of the URI.
     *
     * Example: ?lang=en&article=fred → ?article=fred&lang=en4
     *
     * Note: The sorting is neither locale nor Unicode aware (the URI query does not get decoded at all) as the
     * purpose is to be able to compare URIs in a reproducible way, not to have the params sorted perfectly.
     */
    const SORT_QUERY_PARAMETERS = 64;

    /**
     * Returns a normalized URI.
     *
     * The scheme and host component are already normalized to lowercase per PSR-7 UriInterface.
     * This methods adds additional normalizations that can be configured with the $flags parameter.
     *
     * @param UriInterface $uri   The URI to normalize
     * @param int          $flags A bitmask of normalizations to apply, see constants
     *
     * @return UriInterface The normalized URI
     * @link https://tools.ietf.org/html/rfc3986#section-6.2
     */
    public static function normalize(UriInterface $uri, $flags = self::PRESERVING_NORMALIZATIONS)
    {
        if ($flags & self::CAPITALIZE_PERCENT_ENCODING) {
            $uri = $uri->withPath();
        }

        if ($flags & self::DECODE_UNRESERVED_CHARACTERS) {
            $uri = $uri->withPath();
        }

        if ($flags & self::REPLACE_EMPTY_PATH && $uri->getPath() === '' &&
            ($uri->getScheme() === 'http' || $uri->getScheme() === 'https')
        ) {
            $uri = $uri->withPath('/');
        }

        if ($flags & self::REMOVE_DEFAULT_PORT && Uri::isDefaultPort($uri)) {
            $uri = $uri->withPort(null);
        }

        if ($flags & self::REMOVE_DOT_SEGMENTS && !Uri::isRelativePathReference($uri)) {
            $uri = $uri->withPath(UriResolver::removeDotSegments($uri->getPath()));
        }

        if ($flags & self::REMOVE_DUPLICATE_SLASHES) {
            $uri = $uri->withPath(preg_replace('#//++#', '/', $uri->getPath()));
        }

        if ($flags & self::SORT_QUERY_PARAMETERS && $uri->getQuery() !== '') {
            $queryKeyValues = explode('&', $uri->getQuery());
            sort($queryKeyValues);
            $uri = $uri->withQuery(implode('&', $queryKeyValues));
        }

        return $uri;
    }

    /**
     * Whether two URIs can be considered equivalent.
     *
     * Both URIs are normalized automatically before comparison with the given $normalizations bitmask. The method also
     * accepts relative URI references and returns true when they are equivalent. This of course assumes they will be
     * resolved against the same base URI. If this is not the case, determination of equivalence or difference of
     * relative references does not mean anything.
     *
     * @param UriInterface $uri1           An URI to compare
     * @param UriInterface $uri2           An URI to compare
     * @param int          $normalizations A bitmask of normalizations to apply, see constants
     *
     * @return bool
     * @link https://tools.ietf.org/html/rfc3986#section-6.1
     */
    public static function isEquivalent(UriInterface $uri1, UriInterface $uri2, $normalizations = self::PRESERVING_NORMALIZATIONS)
    {
        return (string) self::normalize($uri1, $normalizations) === (string) self::normalize($uri2, $normalizations);
    }

    private function __construct()
    {
        // cannot be instantiated
    }
}
