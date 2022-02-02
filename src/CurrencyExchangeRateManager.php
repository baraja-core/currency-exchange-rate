<?php

declare(strict_types=1);

namespace Baraja\CurrencyExchangeRate;


use Nette\Caching\Cache;
use Nette\Caching\Storage;

/**
 * Original implementation for currency conversion.
 *
 * @deprecated since 2022-01-22, use ExchangeRateConvertor from baraja-core/shop-currency
 */
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
		static $return;

		if ($return === null) {
			$return = [];
			foreach (explode("\n", $this->loadApi()) as $line) {
				if ((bool) preg_match('/^\d$/', $line[0]) === false && strtoupper($line[0]) === $line[0]) {
					[$country, $currency, $quantity, $code, $rate] = explode('|', $line);
					$return[$code] = new ExchangeRate($country, $currency, $code, (float) ((float) str_replace(',', '.', $rate) / (int) $quantity));
				}
			}
		}

		return $return;
	}


	public function getRate(string $code): float
	{
		$list = $this->getList();
		$code = strtoupper($code);
		if (isset($list[$code]) === true) {
			return $list[$code]->getRate();
		}

		throw new \InvalidArgumentException(sprintf(
			'Currency rate for code "%s" does not exist. Did you mean "%s"?',
			$code,
			implode('", "', array_keys($list)),
		));
	}


	public function getPrice(
		float|string $price,
		string $expectedCurrency,
		?string $currentCurrency = null,
		?bool $preferParsedCurrency = null,
	): float {
		if (is_string($price)) { // price can contain numeric-string or value with currency like "10 EUR"
			$price = (string) str_replace(',', '.', strtoupper(trim($price)));
			if (preg_match('/^([\d.]+)(\s*[A-Z]{3})?$/', $price, $priceParser) === 1) {
				$pricePart = ($priceParser[1] ?? throw new \RuntimeException('Price must exist.'));
				$price = $pricePart === '' ? '1' : $pricePart;
				$currencyPart = trim($priceParser[2] ?? '');
				if ($currencyPart === '') { // numeric-string
					if ($currentCurrency === null) {
						throw new \InvalidArgumentException('Current currency is mandatory.');
					}
				} elseif ($currentCurrency === null) {
					$currentCurrency = $currencyPart;
				} elseif ($currencyPart !== $currentCurrency) {
					if ($preferParsedCurrency === null) {
						throw new \InvalidArgumentException(sprintf(
							'The input currency is ambiguous. The parameter states that the input is in "%s", but the price is in "%s".',
							$currentCurrency,
							$currencyPart,
						));
					}
					if ($preferParsedCurrency === true) {
						$currentCurrency = $currencyPart;
					}
				}
			} else {
				throw new \InvalidArgumentException(sprintf('Invalid price format, because haystack "%s" given. Did you mean format like "10.3EUR"?', $price));
			}
		}
		if ($currentCurrency === null) {
			$currentCurrency = 'CZK';
		}

		$baseRate = $currentCurrency === 'CZK'
			? 1
			: $this->getRate($currentCurrency);
		$rate = $expectedCurrency === 'CZK'
			? 1
			: $this->getRate($expectedCurrency);

		return $price * $baseRate / $rate;
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
		$alpha = "a-z\x80-\xFF";
		$isUrl = (bool) preg_match(<<<XX
					(^
						https?://(
							(([-_0-9$alpha]+\\.)*                       # subdomain
								[0-9$alpha]([-0-9$alpha]{0,61}[0-9$alpha])?\\.)?  # domain
								[$alpha]([-0-9$alpha]{0,17}[$alpha])?   # top domain
							|\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}  # IPv4
							|\\[[0-9a-f:]{3,39}\\]                      # IPv6
						)(:\\d{1,5})?                                   # port
						(/\\S*)?                                        # path
						(\\?\\S*)?                                      # query
						(\\#\\S*)?                                      # fragment
					$)Dix
			XX
			, $apiUrl);

		if ($isUrl === false) {
			throw new \InvalidArgumentException('Haystack "' . $apiUrl . '" is not valid URL.');
		}

		$this->apiUrl = $apiUrl;

		return $this;
	}


	private function loadApi(): string
	{
		static $cache;
		if ($cache === null) {
			if ($this->cache !== null) {
				try {
					$cache = $this->cache->load('api');

					return $cache;
				} catch (\Throwable) {
				}
			}
			if (strncmp($this->apiUrl, 'https://', 8) !== 0) {
				throw new \RuntimeException('API URL must be secured. URL "' . $this->apiUrl . '" given.');
			}
			$cache = trim($this->normalize((string) file_get_contents($this->apiUrl)));
			if ($cache === '') {
				throw new \RuntimeException('API response is empty. Is URL "' . $this->apiUrl . '" callable?');
			}
			if ($this->cache !== null) {
				try {
					$this->cache->save('api', $cache, [
						Cache::EXPIRATION => '4 hours',
					]);
				} catch (\Throwable) {
				}
			}
		}

		return $cache;
	}


	private function normalize(string $s): string
	{
		// convert to compressed normal form (NFC)
		if (class_exists('Normalizer', false) && ($n = \Normalizer::normalize($s, \Normalizer::FORM_C)) !== false) {
			$s = (string) $n;
		}

		$s = str_replace(["\r\n", "\r"], "\n", $s);

		// remove control characters; leave \t + \n
		$s = (string) preg_replace('#[\x00-\x08\x0B-\x1F\x7F-\x9F]+#u', '', $s);

		// right trim
		$s = (string) preg_replace('#[\t ]+$#m', '', $s);

		// leading and trailing blank lines
		$s = trim($s, "\n");

		return $s;
	}
}
