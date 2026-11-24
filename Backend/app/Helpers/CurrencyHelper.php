<?php

if (! function_exists('format_indian_currency')) {
    function format_indian_currency($number, $currencyCode = 'INR')
    {
        $symbol = $currencyCode === 'INR' ? '₹' : $currencyCode . ' ';
        
        $isNegative = $number < 0;
        $number = abs($number);

        if ($number >= 10000000) {
            $formatted = rtrim(rtrim(number_format($number / 10000000, 2), '0'), '.') . 'Cr';
        } elseif ($number >= 100000) {
            $formatted = rtrim(rtrim(number_format($number / 100000, 2), '0'), '.') . 'L';
        } elseif ($number >= 1000) {
            $formatted = rtrim(rtrim(number_format($number / 1000, 2), '0'), '.') . 'K';
        } else {
            $formatted = number_format($number, 2);
        }

        return ($isNegative ? '-' : '') . $symbol . $formatted;
    }
}

if (! function_exists('format_indian_currency_tooltip')) {
    function format_indian_currency_tooltip($number, $currencyCode = 'INR')
    {
        $symbol = $currencyCode === 'INR' ? '₹' : $currencyCode . ' ';
        
        $isNegative = $number < 0;
        $number = abs($number);

        $decimals = number_format(fmod($number, 1), 2);
        $decimalsStr = substr($decimals, 1);
        
        $integerPart = floor($number);
        $integerStr = (string)$integerPart;
        
        if (strlen($integerStr) > 3) {
            $lastThree = substr($integerStr, -3);
            $rest = substr($integerStr, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $integerStr = $rest . ',' . $lastThree;
        }

        return ($isNegative ? '-' : '') . $symbol . $integerStr . $decimalsStr;
    }
}
