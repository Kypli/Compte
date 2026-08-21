<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MoneyFormatExtension extends AbstractExtension
{
    private const ALLOWED_FORMATS = ['dot', 'comma', 'euro_cents', 'german'];
    private const CURRENCIES = [
        'EUR' => ['symbol' => "\xE2\x82\xAC", 'prefix' => '', 'suffix' => ' ', 'fractionDigits' => 2],
        'USD' => ['symbol' => '$', 'prefix' => '$', 'suffix' => '', 'fractionDigits' => 2],
        'GBP' => ['symbol' => "\xC2\xA3", 'prefix' => "\xC2\xA3", 'suffix' => '', 'fractionDigits' => 2],
        'CHF' => ['symbol' => 'CHF', 'prefix' => 'CHF ', 'suffix' => '', 'fractionDigits' => 2],
        'JPY' => ['symbol' => "\xC2\xA5", 'prefix' => "\xC2\xA5", 'suffix' => '', 'fractionDigits' => 0],
        'CAD' => ['symbol' => 'CA$', 'prefix' => 'CA$', 'suffix' => '', 'fractionDigits' => 2],
    ];

    public function getFilters(): array
    {
        return [
            new TwigFilter('money_display', [$this, 'format']),
            new TwigFilter('money_symbol', [$this, 'symbol']),
        ];
    }

    public function symbol(?string $currency = 'EUR'): string
    {
        $currency = is_string($currency) ? strtoupper($currency) : 'EUR';

        return self::CURRENCIES[$currency]['symbol'] ?? self::CURRENCIES['EUR']['symbol'];
    }

    public function format(float|int|string|null $amount, ?string $format = 'comma', string|bool|null $currency = 'EUR', bool $trimZeros = false, bool $showZeroDecimals = true): string
    {
        if (is_bool($currency)) {
            $showZeroDecimals = $trimZeros;
            $trimZeros = $currency;
            $currency = 'EUR';
        }

        $value = is_numeric($amount) ? (float) $amount : 0.0;
        $trimZeros = $trimZeros || in_array($format, ['one_decimal', 'comma_one_decimal', 'euro_one_decimal'], true);
        $currency = is_string($currency) ? strtoupper($currency) : 'EUR';
        $currencyConfig = self::CURRENCIES[$currency] ?? self::CURRENCIES['EUR'];

        $legacyCurrencyByFormat = [
            'us_dollar' => 'USD',
            'uk_pound' => 'GBP',
            'swiss_franc' => 'CHF',
            'german_euro' => 'EUR',
        ];
        if (isset($legacyCurrencyByFormat[$format]) && 'EUR' === $currency) {
            $currencyConfig = self::CURRENCIES[$legacyCurrencyByFormat[$format]];
        }

        $format = match ($format) {
            'one_decimal' => 'dot',
            'comma_one_decimal' => 'comma',
            'euro_one_decimal' => 'euro_cents',
            'us_dollar', 'uk_pound', 'swiss_franc' => 'dot',
            'german_euro' => 'german',
            default => $format,
        };
        $format = in_array($format, self::ALLOWED_FORMATS, true) ? $format : 'comma';

        return match ($format) {
            'dot' => $this->formatFixed($value, '.', ' ', $currencyConfig, $trimZeros, $showZeroDecimals),
            'euro_cents' => $this->formatCurrencyCents($value, $currencyConfig, $trimZeros, $showZeroDecimals),
            'german' => $this->formatFixed($value, ',', '.', $currencyConfig, $trimZeros, $showZeroDecimals),
            default => $this->formatFixed($value, ',', ' ', $currencyConfig, $trimZeros, $showZeroDecimals),
        };
    }

    private function formatFixed(float $value, string $decimalSeparator, string $thousandsSeparator, array $currency, bool $trimZeros, bool $showZeroDecimals): string
    {
        $fractionDigits = $currency['fractionDigits'];
        $roundedValue = 0 === $fractionDigits ? round($value) : $value;
        $sign = $roundedValue < 0 ? '-' : '';
        $formatted = number_format(abs($roundedValue), $fractionDigits, $decimalSeparator, $thousandsSeparator);

        if (0 < $fractionDigits && !$trimZeros && !$showZeroDecimals && round($value, $fractionDigits) === 0.0) {
            $formatted = '0';
        } elseif (0 < $fractionDigits && $trimZeros) {
            $formatted = rtrim(rtrim($formatted, '0'), $decimalSeparator);
        }

        return $sign.$formatted;
    }

    private function formatCurrencyCents(float $value, array $currency, bool $trimZeros, bool $showZeroDecimals): string
    {
        $fractionDigits = $currency['fractionDigits'];
        $sign = $value < 0 ? '-' : '';
        $symbol = $currency['symbol'];

        if (0 === $fractionDigits) {
            $whole = number_format((int) round(abs($value)), 0, '', ' ');

            return sprintf('%s%s%s', $sign, $whole, $symbol);
        }

        $rounded = round(abs($value), 2);
        $whole = (int) floor($rounded);
        $cents = (int) round(($rounded - $whole) * 100);

        if ($cents === 100) {
            ++$whole;
            $cents = 0;
        }

        $formattedWhole = number_format($whole, 0, '', ' ');

        if (!$trimZeros && !$showZeroDecimals && $whole === 0 && $cents === 0) {
            return sprintf('%s0%s', $sign, $symbol);
        }
        if ($trimZeros && $cents === 0) {
            return sprintf('%s%s%s', $sign, $formattedWhole, $symbol);
        }
        if ($trimZeros && $cents % 10 === 0) {
            return sprintf('%s%s%s%d', $sign, $formattedWhole, $symbol, $cents / 10);
        }

        return sprintf('%s%s%s%02d', $sign, $formattedWhole, $symbol, $cents);
    }

}