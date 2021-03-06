<?php

declare(strict_types=1);

namespace Baraja\CurrencyExchangeRate;


final class ExchangeRate
{
	public function __construct(
		private string $country,
		private string $currency,
		private string $code,
		private float $rate
	) {
	}


	public function getCountry(): string
	{
		return $this->country;
	}


	public function getCurrency(): string
	{
		return $this->currency;
	}


	public function getCode(): string
	{
		return $this->code;
	}


	public function getRate(): float
	{
		return $this->rate;
	}
}
