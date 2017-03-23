<?php

namespace ILIAS\HTTP\Cookies;

use Dflydev\FigCookies\SetCookies;
use Psr\Http\Message\ResponseInterface;

/**
 * Class CookieJarFacade
 *
 * Wrapper class for the FigCookies SetCookies class.
 *
 * @author  Nicolas Schäfli <ns@studer-raimann.ch>
 * @package ILIAS\HTTP\Cookies
 * @since   5.2
 * @version 1.0.0
 */
class CookieJarWrapper implements CookieJar {

	/**
	 * @var SetCookies $cookies
	 */
	private $cookies;


	/**
	 * CookieJarWrapper constructor.
	 *
	 * @param SetCookies $cookies
	 */
	private function __construct(SetCookies $cookies)
	{
		$this->cookies = $cookies;
	}


	/**
	 * @inheritDoc
	 */
	public function has($name)
	{
		return $this->cookies->has($name);
	}


	/**
	 * @inheritDoc
	 */
	public function get($name)
	{
		$cookie = $this->cookies->get($name);

		return (is_null($cookie)) ? null : new CookieWrapper($cookie);
	}


	/**
	 * @inheritDoc
	 */
	public function getAll()
	{
		$wrappedCookies = [];
		foreach ($this->cookies->getAll() as $cookie) {
			$wrappedCookies[] = new CookieWrapper($cookie);
		}

		return $wrappedCookies;
	}


	/**
	 * @inheritDoc
	 */
	public function with(Cookie $setCookie)
	{
		/**
		 * @var CookieWrapper $wrapper
		 */
		$wrapper = $setCookie;
		$internalCookie = $wrapper->getImplementation();
		$this->cookies = $this->cookies->with($internalCookie);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function without($name)
	{
		$this->cookies = $this->cookies->without($name);

		return $this;
	}


	/**
	 * @inheritDoc
	 */
	public function renderIntoResponseHeader(ResponseInterface $response)
	{
		$response = $this->cookies->renderIntoSetCookieHeader($response);

		return $response;
	}


	/**
	 * @inheritDoc
	 */
	public static function fromCookieStrings($cookieStrings)
	{
		return new self(SetCookies::fromSetCookieStrings($cookieStrings));
	}


	/**
	 * @inheritDoc
	 */
	public static function fromResponse(ResponseInterface $response)
	{
		return new self(SetCookies::fromResponse($response));
	}
}