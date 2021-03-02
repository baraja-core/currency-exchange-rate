<?php

declare(strict_types=1);

namespace Baraja\CurrencyExchangeRate;


use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

final class CurrencyExchangeRateManager
{
	private string $apiUrl = 'https://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt';

	private ?Cache $cache = null;


	public function __construct(?Storage $storage = null)
	{
		if ($storage !== null) {
			$this->cache = new Cache($storage, 'baraja-currency-exchange-rate');
		}
	}


	/**
	 * @return ExchangeRate[]
	 */
	public function getList(): array
	{
		$return = [];
		foreach (explode("\n", $this->loadApi()) as $line) {
			if ((bool) preg_match('/^\d$/', $line[0]) === false && strtoupper($line[0]) === $line[0]) {
				[$country, $currency, $quantity, $code, $rate] = explode('|', $line);
				$return[$code] = new ExchangeRate($country, $currency, $code, (float) ((float) str_replace(',', '.', $rate) / (int) $quantity));
			}
		}

		return $return;
	}


	public function getRate(string $code): float
	{
		if (isset(($list = $this->getList())[$code = strtoupper($code)]) === true) {
			return $list[$code]->getRate();
		}

		throw new \InvalidArgumentException(
			'Currency rate for code "' . $code . '" does not exist. '
			. 'Did you mean "' . implode('", "', array_keys($list)) . '"?',
		);
	}


	public function isCurrencySupported(string $code): bool
	{
		if ($code === 'CZK') {
			return true;
		}
		try {
			if ($this->getRate($code) > 0) {
				return true;
			}
		} catch (\Throwable $e) {
		}

		return false;
	}


	/**
	 * @internal
	 */
	public function setApiUrl(string $apiUrl): self
	{
		if (Validators::isUrl($apiUrl) === false) {
			throw new \InvalidArgumentException('Haystack "' . $apiUrl . '" is not valid URL.');
		}

		$this->apiUrl = $apiUrl;

		return $this;
	}


	private function loadApi(): string
	{
		static $cache;
		if ($this->cache !== null) {
			try {
				$cache = $this->cache->load('api');
			} catch (\Throwable) {
			}
		}
		if ($cache === null) {
			if (strncmp($this->apiUrl, 'https://', 8) !== 0) {
				throw new \RuntimeException('API URL must be secured. URL "' . $this->apiUrl . '" given.');
			}
			$cache = trim(Strings::normalize((string) file_get_contents($this->apiUrl)));
			if ($cache === '') {
				throw new \RuntimeException('API response is empty. Is URL "' . $this->apiUrl . '" callable?');
			}
			if ($this->cache !== null) {
				try {
					$cache = $this->cache->save('api', $cache, [
						Cache::EXPIRATION => '4 hours',
					]);
				} catch (\Throwable) {
				}
			}
		}

		return $cache;
	}
}
