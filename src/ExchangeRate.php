<?php

declare(strict_types=1);

namespace Baraja\CurrencyExchangeRate;


final class ExchangeRate
{
	private string $country;

	private string $currency;

	private string $code;

	private float $rate;


	public function __construct(string $country, string $currency, string $code, float $rate)
	{
		$this->country = $country;
		$this->currency = $currency;
		$this->code = $code;
		$this->rate = $rate;
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
