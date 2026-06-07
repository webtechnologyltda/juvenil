<?php

namespace App\Support;

class AtendenteWhatsapp
{
    public const DEFAULT_MESSAGE = 'Olá tenho uma dúvida sobre o Acampamento Juvenil, consegue me ajudar?';

    public static function url(?string $phone, string $message = self::DEFAULT_MESSAGE): ?string
    {
        $number = self::number($phone);

        if ($number === null) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }

    public static function number(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        return str_starts_with($digits, '55') ? $digits : '55'.$digits;
    }
}
