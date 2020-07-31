Currency exchange rate
======================

![Integrity check](https://github.com/baraja-core/currency-exchange-rate/workflows/Integrity%20check/badge.svg)

A small simple PHP library for finding current exchange rates.

We draw the exchange rate list from the website of the Czech National Bank, which updates it every hour.

📦 Installation & Basic Usage
-----------------------------

This package can be installed using [Package Manager](https://github.com/baraja-core/package-manager) which is also part of the Baraja [Sandbox](https://github.com/baraja-core/sandbox). If you are not using it, you have to install the package manually following this guide.

A model configuration can be found in the `common.neon` file inside the root of the package.

To manually install the package call Composer and execute the following command:

```shell
composer require baraja-core/currency-exchange-rate
```

📄 License
-----------

`baraja-core/currency-exchange-rate` is licensed under the MIT license. See the [LICENSE](https://github.com/baraja-core/currency-exchange-rate/blob/master/LICENSE) file for more details.
