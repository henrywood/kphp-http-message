<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/10/21
 * Time: 下午1:18
 */

namespace PhpPkg\Http\Message\Traits;

/**
 * Trait CookiesTrait
 *
 * @package PhpPkg\Http\Message\Traits
 */
trait CookiesTrait
{
	/**
	 * @var Cookies|null
	 */
	private ?\PhpPkg\Http\Message\Cookies $cookies = null;

	/*******************************************************************************
	 * Cookies
	 ******************************************************************************/

	/**
	 * @return array
	 */
	public function getCookieParams(): array
	{
		return $this->cookies->all();
	}

	/**
	 * @param string $key
	 * @param null   $default
	 * @return mixed
	 */
	public function getCookieParam(string $key, $default = null): mixed
	{
		return $this->cookies->get($key, $default);
	}

	/**
	 * @param array $cookies
	 * @return static
	 */
	public function withCookieParams(array $cookies): static
	{
		$clone = clone $this;

		$clone->cookies = new \PhpPkg\Http\Message\Cookies($cookies);

		return $clone;
	}

	/**
	 * @param string       $name
	 * @param array|string $value
	 *
	 * @return static
	 */
	public function setCookie(string $name, array|string $value): static
	{
		$this->cookies->set($name, $value);
		return $this;
	}

	/**
	 * @return \PhpPkg\Http\Message\Cookies
	 */
	public function getCookies(): \PhpPkg\Http\Message\Cookies
	{
		return $this->cookies;
	}

	/**
	 * @param array|\PhpPkg\Http\Message\Cookies $cookies
	 *
	 * @return static
	 */
	public function setCookies(array|\PhpPkg\Http\Message\Cookies $cookies): static
	{
		if (is_array($cookies)) {
			return $this->setCookiesFromArray($cookies);
		}

		$this->cookies = $cookies;
		return $this;
	}

	/**
	 * @param array $cookies
	 * @return static
	 */
	public function setCookiesFromArray(array $cookies): static
	{
		if (!$this->cookies) {
			$this->cookies = new \PhpPkg\Http\Message\Cookies($cookies);
		} else {
			$this->cookies->sets($cookies);
		}

		return $this;
	}
}
