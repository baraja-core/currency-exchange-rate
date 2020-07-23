<?php

declare(strict_types=1);

namespace Baraja\CurrencyExchangeRate;


use Nette\Utils\Strings;
use Nette\Utils\Validators;

final class CurrencyExchangeRateManager
{

	/** @var string */
	private $apiUrl = 'https://www.cnb.cz/cs/financni_trhy/devizovy_trh/kurzy_devizoveho_trhu/denni_kurz.txt';


	/**
	 * @return ExchangeRate[]
	 */
	public function getList(): array
	{
		$return = [];
		foreach (explode("\n", $this->loadApi()) as $line) {
			if ((bool) preg_match('/^\d$/', $line[0]) === false && Strings::upper($line[0]) === $line[0]) {
				[$country, $currency, $quantity, $code, $rate] = explode('|', $line);
				$return[$code] = new ExchangeRate($country, $currency, $code, (float) ((float) str_replace(',', '.', $rate) / (int) $quantity));
			}
		}

		return $return;
	}


	public function getRate(string $code): float
	{
		if (isset(($list = $this->getList())[$code = strtoupper($code)]) === true) {
			return (float) $list[$code]['rate'];
		}

		throw new \InvalidArgumentException(
			'Currency rate for code "' . $code . '" does not exist. '
			. 'Did you mean "' . implode('", "', array_keys($list)) . '"?'
		);
	}


	/**
	 * @internal
	 * @param string $apiUrl
	 * @return self
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
		if (strncmp($this->apiUrl, 'https://', 8) !== 0) {
			throw new \RuntimeException('API URL must be secured. URL "' . $this->apiUrl . '" given.');
		}
		if (($response = trim(Strings::normalize((string) file_get_contents($this->apiUrl)))) === '') {
			throw new \RuntimeException('API response is empty. Is URL "' . $this->apiUrl . '" callable?');
		}

		return $response;
	}
}
