<?php

namespace GuzzleHttp\Psr7;

final class Rfc7230
{
	/**
	 * Header related regular expressions (copied from amphp/http package)
	 * (Note: once we require PHP 7.x we could just depend on the upstream package)
	 *
	 * @link https://github.com/amphp/http/blob/16e465fa82555104d1cff98cb8e412295a380214/src/Rfc7230.php#L12-L15
	 * @license https://github.com/amphp/http/blob/16e465fa82555104d1cff98cb8e412295a380214/LICENSE
	 */
	const HEADER_REGEX = "(^([^()<>@,;:\\\"/[\]?={}\x01-\x20\x7F]++):[ \t]*+((?:[ \t]*+[\x21-\x7E\x80-\xFF]++)*+)[ \t]*+\r\n)m";
	const HEADER_FOLD_REGEX = "(\r\n[ \t]++)";
}
