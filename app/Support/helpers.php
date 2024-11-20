<?php


function brazilianMoneyFloatToString(float|string $value): string {
    $value = str_replace(',', '.', str_replace('.', '', $value));
    return number_format($value, 2, ',', '.');
}

function brazilianMoneyStringToFloat(string $value) {
    return floatval(str_replace(',', '.', str_replace('.', '', $value)));
}
