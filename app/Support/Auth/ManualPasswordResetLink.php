<?php

namespace App\Support\Auth;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use RuntimeException;

class ManualPasswordResetLink
{
    public function generate(User $user): string
    {
        $panel = Filament::getPanel('admin');

        if (! $panel->hasPasswordReset()) {
            throw new RuntimeException('O fluxo de redefinição de senha não está habilitado no painel administrativo.');
        }

        $token = Password::broker($panel->getAuthPasswordBroker())->createToken($user);

        Log::notice('Link manual de redefinição de senha gerado.', [
            'actor_id' => auth()->id(),
            'target_user_id' => $user->getKey(),
        ]);

        return $panel->getResetPasswordUrl($token, $user);
    }

    public function expirationMinutes(): int
    {
        $broker = Filament::getPanel('admin')->getAuthPasswordBroker()
            ?? config('auth.defaults.passwords');

        return (int) config("auth.passwords.{$broker}.expire", 60);
    }
}
