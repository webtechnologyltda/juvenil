<?php

namespace App\Filament\Widgets\Concerns;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Facades\Filament;

trait SupportsWidgetShield
{
    protected static ?string $widgetPermissionKey = null;

    public static function canView(): bool
    {
        $permission = static::getWidgetPermission();
        $user = Filament::auth()?->user();

        return $permission && $user
            ? $user->can($permission)
            : parent::canView();
    }

    protected static function getWidgetPermission(): ?string
    {
        if (static::$widgetPermissionKey === null) {
            $widget = FilamentShield::getWidgets()[static::class] ?? null;
            static::$widgetPermissionKey = $widget ? array_key_first($widget['permissions']) : null;
        }

        return static::$widgetPermissionKey;
    }
}
