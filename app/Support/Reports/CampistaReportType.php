<?php

namespace App\Support\Reports;

enum CampistaReportType: string
{
    case RegistrationFichas = 'registration_fichas';
    case TribeQuadrant = 'tribe_quadrant';
    case SensitiveHealth = 'sensitive_health';
    case MissionContacts = 'mission_contacts';

    public function label(): string
    {
        return match ($this) {
            self::RegistrationFichas => 'Fichas de inscrição',
            self::TribeQuadrant => 'Quadrante por tribo',
            self::SensitiveHealth => 'Lista médica da enfermaria',
            self::MissionContacts => 'Contatos e endereços',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::RegistrationFichas => 'Fichas de inscrição',
            self::TribeQuadrant => 'Quadrante das inscrições por tribo',
            self::SensitiveHealth => 'Lista médica da enfermaria',
            self::MissionContacts => 'Contatos e endereços para missão',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::RegistrationFichas => 'Ficha consolidada de cada campista para impressão operacional.',
            self::TribeQuadrant => 'Distribuição dos campistas agrupada por tribo.',
            self::SensitiveHealth => 'Dados restritos para triagem e cuidados da enfermaria.',
            self::MissionContacts => 'Responsáveis, telefones e endereços para visitas missionárias.',
        };
    }

    public function permission(): string
    {
        return match ($this) {
            self::RegistrationFichas => 'print_registration_fichas_report',
            self::TribeQuadrant => 'print_tribe_quadrant_report',
            self::SensitiveHealth => 'print_sensitive_health_report',
            self::MissionContacts => 'print_mission_contacts_report',
        };
    }

    public function isSensitive(): bool
    {
        return $this === self::SensitiveHealth;
    }
}
