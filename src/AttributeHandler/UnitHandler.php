<?php

declare(strict_types=1);

namespace Webgriffe\SyliusAkeneoPlugin\AttributeHandler;

final class UnitHandler implements UnitHandlerInterface
{
    private const UNIT_MAPPING = [
        // area
        'square_millimeter' => 'mm²',
        'square_centimeter' => 'cm²',
        'square_decimeter'  => 'dm²',
        'square_meter'      => 'm²',
        'centiare'          => 'ca',
        'square_dekameter'  => 'dam²',
        'are'               => 'a',
        'square_hectometer' => 'hm²',
        'hectare'           => 'ha',
        'square_kilometer'  => 'km²',
        'square_mil'        => 'sq mil',
        'square_inch'       => 'in²',
        'square_foot'       => 'ft²',
        'square_yard'       => 'yd²',
        'arpent'            => 'arpent',
        'acre'              => 'a',
        'square_furlong'    => 'fur²',
        'square_mile'       => 'mi²',

        // binary
        'bit'      => 'b',
        'byte'     => 'B',
        'kilobyte' => 'kB',
        'megabyte' => 'MB',
        'gigabyte' => 'GB',
        'terabyte' => 'TB',

        // decibel
        'decibel' => 'd',

        // frequency
        'hertz'     => 'Hz',
        'kilohertz' => 'kHz',
        'megahertz' => 'MHz',
        'gigahertz' => 'GHz',
        'terahertz' => 'THz',

        // length
        'millimeter' => 'mm',
        'centimeter' => 'cm',
        'decimeter'  => 'dm',
        'meter'      => 'm',
        'dekameter'  => 'dam',
        'hectometer' => 'hm',
        'kilometer'  => 'km',
        'mil'        => 'mil',
        'inch'       => 'in',
        'feet'       => 'ft',
        'yard'       => 'yd',
        'chain'      => 'ch',
        'furlong'    => 'fur',
        'mile'       => 'mi',

        // power
        'watt'     => 'W',
        'kilowatt' => 'kW',
        'megawatt' => 'MW',
        'gigawatt' => 'GW',
        'terawatt' => 'TW',

        // voltage
        'millivolt' => 'mV',
        'centivolt' => 'cV',
        'decivolt'  => 'dV',
        'volt'      => 'V',
        'dekavolt'  => 'daV',
        'hectovolt' => 'hV',
        'kilovolt'  => 'kV',

        // intensity
        'milliampere' => 'mA',
        'centiampere' => 'cA',
        'deciampere'  => 'dA',
        'ampere'      => 'A',
        'dekampere'   => 'daA',
        'hectoampere' => 'hA',
        'kiloampere'  => 'kA',

        // resistance
        'milliohm' => 'mO',
        'centiohm' => 'cO',
        'deciohm'  => 'dO',
        'ohm'      => 'O',
        'dekaohm'  => 'daO',
        'hectohm'  => 'hO',
        'kilohm'   => 'kO',
        'megohm'   => 'mO',

        // speed
        'meter per second'   => 'mdivs',
        'meter per minute'   => 'mdivm',
        'meter per hour'     => 'mdivh',
        'kilometer per hour' => 'kmdivh',
        'foot per second'    => 'ftdivs',
        'foot per hour'      => 'ftdivh',
        'yard per hour'      => 'yddivh',
        'mile per hour'      => 'midivh',

        // electric charge
        'milliamperehour' => 'mAh',
        'amperehour'      => 'Ah',
        'millicoulomb'    => 'mC',
        'centioulomb'     => 'cC',
        'decicoulomb'     => 'dC',
        'coulomb'         => 'C',
        'dekacoulomb'     => 'daC',
        'hectocoulomb'    => 'hC',
        'kilocoulomb'     => 'kC',

        // duration
        'millisecond' => 'ms',
        'second'      => 's',
        'minute'      => 'm',
        'hour'        => 'h',
        'day'         => 'd',

        // temperature
        'celsius'    => '°C',
        'fahrenheit' => '°F',
        'kelvin'     => '°K',
        'rankine'    => '°R',
        'reaumur'    => '°r',

        // volume
        'cubic_millimeter' => 'mm³',
        'cubic_centimeter' => 'cm³',
        'milliliter'       => 'ml',
        'centiliter'       => 'cl',
        'deciliter'        => 'dl',
        'cubic_decimeter'  => 'dm³',
        'liter'            => 'l',
        'cubic_meter'      => 'm³',
        'pint'             => 'pt',
        'barrel'           => 'bbl',
        'gallon'           => 'gal',
        'cubic_foot'       => 'ft³',
        'cubic_inch'       => 'in³',
        'cubic_yard'       => 'yd³',

        // weight
        'ounce'     => 'oz',
        'milligram' => 'mg',
        'gram'      => 'g',
        'kilogram'  => 'kg',
        'ton'       => 't',
        'grain'     => 'gr',
        'denier'    => 'denier',
        'once'      => 'once',
        'marc'      => 'marc',
        'livre'     => 'livre',
        'pound'     => 'lb',
    ];

    private array $customMapping;

    /**
     * @param array $customMapping An a array of Akeneo unit values (case
     *                             insensitive) to be mapped to a string.
     */
    public function __construct(array $customMapping = [])
    {
        $this->customMapping = $customMapping;
    }

    /**
     * @param array $value
     *
     * @return string
     */
    public function getUnit($attributeValue): string
    {
        if (!array_key_exists('unit', $attributeValue)) {
            return '';
        }

        $unit = strtolower($attributeValue['unit']);

        return ' ' . (
            $this->customMapping[$unit]
            ?? self::UNIT_MAPPING[$unit]
            ?? $unit
        );
    }
}
