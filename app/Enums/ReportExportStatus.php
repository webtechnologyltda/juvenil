<?php

namespace App\Enums;

enum ReportExportStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Na fila',
            self::Processing => 'Gerando relatório',
            self::Ready => 'Pronto',
            self::Failed => 'Falhou',
        };
    }
}
