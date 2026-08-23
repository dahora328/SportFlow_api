<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfCnpjRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $c = preg_replace('/\D/', '', $value);

        if (strlen($c) === 11) {
            // Validate CPF
            if (preg_match('/(\d)\1{10}/', $c)) {
                $fail('O campo :attribute não é um CPF válido.');
                return;
            }
            for ($t = 9; $t < 11; $t++) {
                for ($d = 0, $c_p = 0; $c_p < $t; $c_p++) {
                    $d += $c[$c_p] * (($t + 1) - $c_p);
                }
                $d = ((10 * $d) % 11) % 10;
                if ($c[$c_p] != $d) {
                    $fail('O campo :attribute não é um CPF válido.');
                    return;
                }
            }
        } elseif (strlen($c) === 14) {
            // Validate CNPJ
            if (preg_match('/(\d)\1{13}/', $c)) {
                $fail('O campo :attribute não é um CNPJ válido.');
                return;
            }
            $b = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
            for ($i = 0, $n = 0; $i < 12; $n += $c[$i] * $b[++$i]);
            if ($c[12] != ((($n %= 11) < 2) ? 0 : 11 - $n)) {
                $fail('O campo :attribute não é um CNPJ válido.');
                return;
            }
            for ($i = 0, $n = 0; $i <= 12; $n += $c[$i] * $b[$i++]);
            if ($c[13] != ((($n %= 11) < 2) ? 0 : 11 - $n)) {
                $fail('O campo :attribute não é um CNPJ válido.');
                return;
            }
        } else {
            $fail('O campo :attribute deve ser um CPF ou CNPJ válido.');
        }
    }
}
