<?php

namespace App\Support\Reports;

use App\Models\User;

enum CampistaReportType: string
{
    case RegistrationFichas = 'registration_fichas';
    case TribeQuadrant = 'tribe_quadrant';
    case SensitiveHealth = 'sensitive_health';
    case MissionContacts = 'mission_contacts';
    case RegistrationPayments = 'registration_payments';

    public const PAGE_PERMISSION = 'page_reports_page';

    public function label(): string
    {
        return match ($this) {
            self::RegistrationFichas => 'Fichas de inscrição',
            self::TribeQuadrant => 'Quadrante por tribo',
            self::SensitiveHealth => 'Lista médica da enfermaria',
            self::MissionContacts => 'Contatos e endereços',
            self::RegistrationPayments => 'Pagamentos de inscrições',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::RegistrationFichas => 'Fichas de inscrição',
            self::TribeQuadrant => 'Quadrante das inscrições por tribo',
            self::SensitiveHealth => 'Lista médica da enfermaria',
            self::MissionContacts => 'Contatos e endereços para missão',
            self::RegistrationPayments => 'Pagamentos de campistas e equipe de trabalho',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::RegistrationFichas => 'Ficha consolidada de cada campista para impressão operacional.',
            self::TribeQuadrant => 'Distribuição dos campistas agrupada por tribo.',
            self::SensitiveHealth => 'Dados restritos para triagem e cuidados da enfermaria.',
            self::MissionContacts => 'Responsáveis, telefones e endereços para visitas missionárias.',
            self::RegistrationPayments => 'Situação dos lançamentos vinculados a campistas e integrantes da equipe de trabalho.',
        };
    }

    public function permission(): string
    {
        return match ($this) {
            self::RegistrationFichas => 'print_registration_fichas_report',
            self::TribeQuadrant => 'print_tribe_quadrant_report',
            self::SensitiveHealth => 'print_sensitive_health_report',
            self::MissionContacts => 'print_mission_contacts_report',
            self::RegistrationPayments => 'print_registration_payments_report',
        };
    }

    public function isSensitive(): bool
    {
        return $this === self::SensitiveHealth;
    }

    public function isFinancial(): bool
    {
        return $this === self::RegistrationPayments;
    }

    public function canBeAccessedBy(User $user): bool
    {
        if ($this->isSensitive() || $this->isFinancial()) {
            return $user->can($this->permission());
        }

        return $user->can(self::PAGE_PERMISSION);
    }
}
