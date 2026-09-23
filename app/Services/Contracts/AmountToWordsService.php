<?php

namespace App\Services\Contracts;

use InvalidArgumentException;

class AmountToWordsService
{
    public function convert(string|int|float $amount): string
    {
        if (!is_numeric($amount) || (float) $amount < 0 || (float) $amount > 999999999.99) {
            throw new InvalidArgumentException('El monto debe estar entre 0 y 999999999.99 soles.');
        }
        $cents = (int) round((float) $amount * 100);
        $whole = intdiv($cents, 100);
        $words = $this->integer($whole);
        $words = preg_replace('/uno$/u', 'un', $words);
        $words = preg_replace('/veintiun$/u', 'veintiún', $words);

        return mb_convert_case($words, MB_CASE_TITLE, 'UTF-8').' con '.sprintf('%02d', $cents % 100).'/100 Soles';
    }

    private function integer(int $n): string
    {
        $small = ['cero', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte', 'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve'];
        if ($n < 30) {
            return $small[$n];
        }
        if ($n < 100) {
            return [3 => 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'][intdiv($n, 10)].($n % 10 ? ' y '.$small[$n % 10] : '');
        }
        if ($n === 100) {
            return 'cien';
        }
        if ($n < 1000) {
            return [1 => 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'][intdiv($n, 100)].($n % 100 ? ' '.$this->integer($n % 100) : '');
        }
        $base = $n < 1000000 ? 1000 : 1000000;
        $count = intdiv($n, $base);
        $prefix = $count === 1 ? ($base === 1000 ? 'mil' : 'un millón') : $this->apocopate($this->integer($count)).($base === 1000 ? ' mil' : ' millones');

        return $prefix.($n % $base ? ' '.$this->integer($n % $base) : '');
    }

    private function apocopate(string $value): string
    {
        return preg_replace('/veintiun$/u', 'veintiún', preg_replace('/uno$/u', 'un', $value));
    }
}
