<?php

namespace DoubleThreeDigital\SimpleCommerce\Seeders;

use DoubleThreeDigital\SimpleCommerce\Models\Currency;
use Illuminate\Database\Seeder;
use Statamic\Stache\Stache;

class CurrencySeeder extends Seeder
{
    public function run()
    {
        $currencies = [
            ['iso' =>'AFN', 'name' => 'Afghani', 'symbol' => '؋'],
            ['iso' => 'ALL', 'name' => 'Lek', 'symbol' => 'Lek'],
            ['iso' => 'ANG', 'name' => 'Netherlands Antillian Guilder', 'symbol' => 'ƒ'],
            ['iso' => 'ARS', 'name' => 'Argentine Peso', 'symbol' => '$'],
            ['iso' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => '$'],
            ['iso' => 'AWG', 'name' => 'Aruban Guilder', 'symbol' => 'ƒ'],
            ['iso' => 'AZN', 'name' => 'Azerbaijanian Manat', 'symbol' => 'ман'],
            ['iso' => 'BAM', 'name' => 'Convertible Marks', 'symbol' => 'KM'],
            ['iso' => 'BBD', 'name' => 'Barbados Dollar', 'symbol' => '$'],
            ['iso' => 'BGN', 'name' => 'Bulgarian Lev', 'symbol' => 'лв'],
            ['iso' => 'BMD', 'name' => 'Bermudian Dollar', 'symbol' => '$'],
            ['iso' => 'BND', 'name' => 'Brunei Dollar', 'symbol' => '$'],
            ['iso' => 'BOB', 'name' => 'BOV Boliviano Mvdol', 'symbol' => '$b'],
            ['iso' => 'BRL', 'name' => 'Brazilian Real', 'symbol' => 'R$'],
            ['iso' => 'BSD', 'name' => 'Bahamian Dollar', 'symbol' => '$'],
            ['iso' => 'BWP', 'name' => 'Pula', 'symbol' => 'P'],
            ['iso' => 'BYR', 'name' => 'Belarussian Ruble', 'symbol' => 'p.'],
            ['iso' => 'BZD', 'name' => 'Belize Dollar', 'symbol' => 'BZ$'],
            ['iso' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => '$'],
            ['iso' => 'CHF', 'name' => 'Swiss Franc', 'symbol' => 'CHF'],
            ['iso' => 'CLP', 'name' => 'CLF Chilean Peso Unidades de fomento', 'symbol' => '$'],
            ['iso' => 'CNY', 'name' => 'Yuan Renminbi', 'symbol' => '¥'],
            ['iso' => 'COP', 'name' => 'COU Colombian Peso Unidad de Valor Real', 'symbol' => '$'],
            ['iso' => 'CRC', 'name' => 'Costa Rican Colon', 'symbol' => '₡'],
            ['iso' => 'CUP', 'name' => 'CUC Cuban Peso Peso Convertible', 'symbol' => '₱'],
            ['iso' => 'CZK', 'name' => 'Czech Koruna', 'symbol' => 'Kč'],
            ['iso' => 'DKK', 'name' => 'Danish Krone', 'symbol' => 'kr'],
            ['iso' => 'DOP', 'name' => 'Dominican Peso', 'symbol' => 'RD$'],
            ['iso' => 'EGP', 'name' => 'Egyptian Pound', 'symbol' => '£'],
            ['iso' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
            ['iso' => 'FJD', 'name' => 'Fiji Dollar', 'symbol' => '$'],
            ['iso' => 'FKP', 'name' => 'Falkland Islands Pound', 'symbol' => '£'],
            ['iso' => 'GBP', 'name' => 'Pound Sterling', 'symbol' => '£'],
            ['iso' => 'GIP', 'name' => 'Gibraltar Pound', 'symbol' => '£'],
            ['iso' => 'GTQ', 'name' => 'Quetzal', 'symbol' => 'Q'],
            ['iso' => 'GYD', 'name' => 'Guyana Dollar', 'symbol' => '$'],
            ['iso' => 'HKD', 'name' => 'Hong Kong Dollar', 'symbol' => '$'],
            ['iso' => 'HNL', 'name' => 'Lempira', 'symbol' => 'L'],
            ['iso' => 'HRK', 'name' => 'Croatian Kuna', 'symbol' => 'kn'],
            ['iso' => 'HUF', 'name' => 'Forint', 'symbol' => 'Ft'],
            ['iso' => 'IDR', 'name' => 'Rupiah', 'symbol' => 'Rp'],
            ['iso' => 'ILS', 'name' => 'New Israeli Sheqel', 'symbol' => '₪'],
            ['iso' => 'IRR', 'name' => 'Iranian Rial', 'symbol' => '﷼'],
            ['iso' => 'ISK', 'name' => 'Iceland Krona', 'symbol' => 'kr'],
            ['iso' => 'JMD', 'name' => 'Jamaican Dollar', 'symbol' => 'J$'],
            ['iso' => 'JPY', 'name' => 'Yen', 'symbol' => '¥'],
            ['iso' => 'KGS', 'name' => 'Som', 'symbol' => 'лв'],
            ['iso' => 'KHR', 'name' => 'Riel', 'symbol' => '៛'],
            ['iso' => 'KPW', 'name' => 'North Korean Won', 'symbol' => '₩'],
            ['iso' => 'KRW', 'name' => 'Won', 'symbol' => '₩'],
            ['iso' => 'KYD', 'name' => 'Cayman Islands Dollar', 'symbol' => '$'],
            ['iso' => 'KZT', 'name' => 'Tenge', 'symbol' => 'лв'],
            ['iso' => 'LAK', 'name' => 'Kip', 'symbol' => '₭'],
            ['iso' => 'LBP', 'name' => 'Lebanese Pound', 'symbol' => '£'],
            ['iso' => 'LKR', 'name' => 'Sri Lanka Rupee', 'symbol' => '₨'],
            ['iso' => 'LRD', 'name' => 'Liberian Dollar', 'symbol' => '$'],
            ['iso' => 'LTL', 'name' => 'Lithuanian Litas', 'symbol' => 'Lt'],
            ['iso' => 'LVL', 'name' => 'Latvian Lats', 'symbol' => 'Ls'],
            ['iso' => 'MKD', 'name' => 'Denar', 'symbol' => 'ден'],
            ['iso' => 'MNT', 'name' => 'Tugrik', 'symbol' => '₮'],
            ['iso' => 'MUR', 'name' => 'Mauritius Rupee', 'symbol' => '₨'],
            ['iso' => 'MXN', 'name' => 'MXV Mexican Peso Mexican Unidad de Inversion (UDI)', 'symbol' => '$'],
            ['iso' => 'MYR', 'name' => 'Malaysian Ringgit', 'symbol' => 'RM'],
            ['iso' => 'MZN', 'name' => 'Metical', 'symbol' => 'MT'],
            ['iso' => 'NGN', 'name' => 'Naira', 'symbol' => '₦'],
            ['iso' => 'NIO', 'name' => 'Cordoba Oro', 'symbol' => 'C$'],
            ['iso' => 'NOK', 'name' => 'Norwegian Krone', 'symbol' => 'kr'],
            ['iso' => 'NPR', 'name' => 'Nepalese Rupee', 'symbol' => '₨'],
            ['iso' => 'NZD', 'name' => 'New Zealand Dollar', 'symbol' => '$'],
            ['iso' => 'OMR', 'name' => 'Rial Omani', 'symbol' => '﷼'],
            ['iso' => 'PAB', 'name' => 'USD Balboa US Dollar', 'symbol' => 'B/.'],
            ['iso' => 'PEN', 'name' => 'Nuevo Sol', 'symbol' => 'S/.'],
            ['iso' => 'PHP', 'name' => 'Philippine Peso', 'symbol' => 'Php'],
            ['iso' => 'PKR', 'name' => 'Pakistan Rupee', 'symbol' => '₨'],
            ['iso' => 'PLN', 'name' => 'Zloty', 'symbol' => 'zł'],
            ['iso' => 'PYG', 'name' => 'Guarani', 'symbol' => 'Gs'],
            ['iso' => 'QAR', 'name' => 'Qatari Rial', 'symbol' => '﷼'],
            ['iso' => 'RON', 'name' => 'New Leu', 'symbol' => 'lei'],
            ['iso' => 'RSD', 'name' => 'Serbian Dinar', 'symbol' => 'Дин.'],
            ['iso' => 'RUB', 'name' => 'Russian Ruble', 'symbol' => 'руб'],
            ['iso' => 'SAR', 'name' => 'Saudi Riyal', 'symbol' => '﷼'],
            ['iso' => 'SBD', 'name' => 'Solomon Islands Dollar', 'symbol' => '$'],
            ['iso' => 'SCR', 'name' => 'Seychelles Rupee', 'symbol' => '₨'],
            ['iso' => 'SEK', 'name' => 'Swedish Krona', 'symbol' => 'kr'],
            ['iso' => 'SGD', 'name' => 'Singapore Dollar', 'symbol' => '$'],
            ['iso' => 'SHP', 'name' => 'Saint Helena Pound', 'symbol' => '£'],
            ['iso' => 'SOS', 'name' => 'Somali Shilling', 'symbol' => 'S'],
            ['iso' => 'SRD', 'name' => 'Surinam Dollar', 'symbol' => '$'],
            ['iso' => 'SVC', 'name' => 'USD El Salvador Colon US Dollar', 'symbol' => '$'],
            ['iso' => 'SYP', 'name' => 'Syrian Pound', 'symbol' => '£'],
            ['iso' => 'THB', 'name' => 'Baht', 'symbol' => '฿'],
            ['iso' => 'TRY', 'name' => 'Turkish Lira', 'symbol' => 'TL'],
            ['iso' => 'TTD', 'name' => 'Trinidad and Tobago Dollar', 'symbol' => 'TT$'],
            ['iso' => 'TWD', 'name' => 'New Taiwan Dollar', 'symbol' => 'NT$'],
            ['iso' => 'UAH', 'name' => 'Hryvnia', 'symbol' => '₴'],
            ['iso' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
            ['iso' => 'UYU', 'name' => 'UYI Peso Uruguayo Uruguay Peso en Unidades Indexadas', 'symbol' => '$U'],
            ['iso' => 'UZS', 'name' => 'Uzbekistan Sum', 'symbol' => 'лв'],
            ['iso' => 'VEF', 'name' => 'Bolivar Fuerte', 'symbol' => 'Bs'],
            ['iso' => 'VND', 'name' => 'Dong', 'symbol' => '₫'],
            ['iso' => 'XCD', 'name' => 'East Caribbean Dollar', 'symbol' => '$'],
            ['iso' => 'YER', 'name' => 'Yemeni Rial', 'symbol' => '﷼'],
            ['iso' => 'ZAR', 'name' => 'Rand', 'symbol' => 'R'],
        ];

        foreach ($currencies as $currency) {
            Currency::create([
                'uuid'      => (new Stache())->generateId(),
                'iso'       => $currency['iso'],
                'name'      => $currency['name'],
                'symbol'    => $currency['symbol'],
            ]);
        }
    }
}