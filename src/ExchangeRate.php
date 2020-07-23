<?php

declare(strict_types=1);

namespace Baraja\CurrencyExchangeRate;


final class ExchangeRate
{

	/** @var string */
	private $country;

	/** @var string */
	private $currency;

	/** @var string */
	private $code;

	/** @var float */
	private $rate;


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
