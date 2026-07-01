<?php

namespace App\Support\Financeiro;

use App\Enums\StatusInscricao;
use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Enums\TipoEquipeTrabalho;
use App\Models\Campista;
use App\Models\EquipeTrabalho;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

final class LancamentoRegistrationCard
{
    public static function forCampista(Campista $registration): HtmlString
    {
        $registration->loadMissing('tribo');

        $status = $registration->status instanceof StatusInscricao
            ? $registration->status
            : StatusInscricao::tryFrom((int) $registration->status);

        $name = (string) ($registration->nome ?: 'Inscrição removida');
        $tribe = (string) ($registration->tribo?->cor ?: 'Sem tribo');
        $photoUrl = self::photoUrl($registration->avatar_url);
        $initials = self::initials($name);
        $statusColor = self::statusColor($status?->getColor());

        return self::render(
            id: 'Campista #'.((string) $registration->getKey()),
            name: $name,
            status: $status?->getLabel() ?? 'Sem status',
            statusColor: $statusColor,
            photoUrl: $photoUrl,
            initials: $initials,
            meta: [$tribe],
        );
    }

    public static function forTeam(EquipeTrabalho $registration): HtmlString
    {
        $registration->loadMissing('tribo');

        $status = $registration->status instanceof StatusInscricaoEquipeTrabalho
            ? $registration->status
            : StatusInscricaoEquipeTrabalho::tryFrom((int) $registration->status);

        $teamType = $registration->tipo_equipe instanceof TipoEquipeTrabalho
            ? $registration->tipo_equipe
            : TipoEquipeTrabalho::tryFrom((int) $registration->tipo_equipe);

        $name = (string) ($registration->nome ?: 'Inscrição removida');
        $team = (string) ($registration->descricao ?: $registration->tribo?->cor ?: 'Sem equipe');
        $photoUrl = self::photoUrl($registration->avatar_url);
        $initials = self::initials($name);
        $statusColor = self::statusColor($status?->getColor());
        $typeLabel = $teamType?->getLabel() ?? 'Tipo não definido';

        return self::render(
            id: 'Equipe #'.((string) $registration->getKey()),
            name: $name,
            status: $status?->getLabel() ?? 'Sem status',
            statusColor: $statusColor,
            photoUrl: $photoUrl,
            initials: $initials,
            meta: [$team, $typeLabel],
        );
    }

    /**
     * @param  array<int, string>  $meta
     */
    private static function render(string $id, string $name, string $status, string $statusColor, ?string $photoUrl, string $initials, array $meta): HtmlString
    {
        $photo = $photoUrl
            ? '<img src="'.e($photoUrl).'" alt="Foto de '.e($name).'" onerror="this.onerror=null;this.hidden=true;this.nextElementSibling.hidden=false;">'
            : '';
        $metaHtml = collect($meta)
            ->filter(fn (string $item): bool => filled($item))
            ->map(fn (string $item): string => '<span>'.e($item).'</span>')
            ->implode('');

        return new HtmlString(
            '<div class="juvenil-launch-registration-card">'
                .'<div class="juvenil-launch-registration-card__photo">'
                    .$photo
                    .'<span '.($photoUrl ? 'hidden' : '').'>'.e($initials).'</span>'
                .'</div>'
                .'<div class="juvenil-launch-registration-card__body">'
                    .'<div class="juvenil-launch-registration-card__topline">'
                        .'<span class="juvenil-launch-registration-card__id">'.e($id).'</span>'
                        .'<span class="juvenil-launch-registration-card__status juvenil-launch-registration-card__status--'.e($statusColor).'">'.e($status).'</span>'
                    .'</div>'
                    .'<strong class="juvenil-launch-registration-card__name">'.e($name).'</strong>'
                    .'<div class="juvenil-launch-registration-card__meta">'
                        .$metaHtml
                    .'</div>'
                .'</div>'
            .'</div>',
        );
    }

    private static function photoUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    private static function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];
        $initials = collect($words)
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : '?';
    }

    private static function statusColor(string|array|null $color): string
    {
        return is_string($color) && in_array($color, ['success', 'warning', 'danger'], true)
            ? $color
            : 'neutral';
    }
}
