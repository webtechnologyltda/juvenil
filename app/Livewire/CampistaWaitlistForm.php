<?php

namespace App\Livewire;

use App\Settings\GeneralSettings;
use App\Support\CampistaRegistrationAvailability;
use App\Support\Campistas\WaitlistManager;
use App\Support\RegistrationAgeLimits;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CampistaWaitlistForm extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public ?array $data = [];

    public ?string $sex = null;

    public ?int $generalPosition = null;

    public ?int $sexPosition = null;

    public function render()
    {
        return view('livewire.campista-waitlist-form');
    }

    public function mount(?string $sex = null): void
    {
        $this->sex = in_array($sex, ['M', 'F'], true) ? $sex : null;
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components($this->waitlistSchema());
    }

    public function joinWaitlistAction(): Action
    {
        return Action::make('joinWaitlist')
            ->label('Entrar na fila de espera')
            ->icon('heroicon-o-queue-list')
            ->color('warning')
            ->modalHeading('Entrar na fila de espera')
            ->modalDescription('Deixe seu contato para ser chamado caso uma vaga compatível seja liberada por desistência.')
            ->modalSubmitActionLabel('Cadastrar na fila')
            ->extraModalWindowAttributes(['class' => 'waitlist-entry-modal'], merge: true)
            ->fillForm(fn (): array => $this->sex === null ? [] : ['sexo' => $this->sex])
            ->schema($this->waitlistSchema())
            ->action(fn (array $data): mixed => $this->createWaitlistEntry($data));
    }

    public function submit(): void
    {
        try {
            $this->createWaitlistEntry($this->form->getState());
            $this->form->fill();
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())
                    ->mapWithKeys(fn (array $messages, string $field): array => ['data.'.$field => $messages])
                    ->all(),
            );
        }
    }

    /**
     * @return array<int, mixed>
     */
    private function waitlistSchema(): array
    {
        return [
            Grid::make([
                'default' => 1,
                'md' => 2,
            ])
                ->schema([
                    TextInput::make('nome')
                        ->label('Nome completo')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('telefone')
                        ->label('WhatsApp')
                        ->required()
                        ->mask('(99) 9 9999-9999')
                        ->rules([
                            fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                $sex = $this->sex ?? $get('sexo');
                                $normalizedPhone = app(WaitlistManager::class)->normalizePhone(is_string($value) ? $value : null);

                                if ($normalizedPhone !== null && is_string($sex) && app(WaitlistManager::class)->hasActiveDuplicate($normalizedPhone, $sex)) {
                                    $fail('Este telefone já está na fila de espera para o sexo selecionado.');
                                }
                            },
                        ])
                        ->maxLength(32),

                    TextInput::make('email')
                        ->label('E-mail')
                        ->email()
                        ->maxLength(255),

                    TextInput::make('data_nascimento')
                        ->label('Data de nascimento')
                        ->mask('99/99/9999')
                        ->rules([
                            fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                $message = RegistrationAgeLimits::fromSettings(app(GeneralSettings::class))
                                    ->violationMessage(is_string($value) ? $value : null);

                                if ($message !== null) {
                                    $fail($message);
                                }
                            },
                        ])
                        ->required(),

                    ToggleButtons::make('sexo')
                        ->label('Sexo da vaga')
                        ->required()
                        ->inline()
                        ->options([
                            'M' => 'Masculino',
                            'F' => 'Feminino',
                        ])
                        ->colors([
                            'M' => Color::Blue,
                            'F' => Color::Pink,
                        ])
                        ->icons([
                            'M' => 'eos-male',
                            'F' => 'eos-female',
                        ])
                        ->default($this->sex)
                        ->disableOptionWhen(fn (string $value): bool => $this->sex !== null && $value !== $this->sex)
                        ->columnSpanFull(),

                    Textarea::make('observacao')
                        ->label('Observação')
                        ->placeholder('Opcional')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),

                    Checkbox::make('aceitar_politica_privacidade')
                        ->label('Eu aceito a Política de Privacidade')
                        ->accepted()
                        ->required()
                        ->columnSpanFull(),
                ]),
        ];
    }

    private function createWaitlistEntry(array $data): null
    {
        if ($this->sex !== null) {
            $data['sexo'] = $this->sex;
        }

        $availability = CampistaRegistrationAvailability::fromSettings(app(GeneralSettings::class));

        if ($availability->sexHasVacancy($data['sexo'] ?? null)) {
            Notification::make()
                ->title('Ainda há vaga disponível')
                ->body('Você pode preencher a inscrição completa normalmente.')
                ->warning()
                ->send();

            return null;
        }

        $ageLimitMessage = RegistrationAgeLimits::fromSettings(app(GeneralSettings::class))
            ->violationMessage($data['data_nascimento'] ?? null);

        if ($ageLimitMessage !== null) {
            throw ValidationException::withMessages([
                'data_nascimento' => $ageLimitMessage,
            ]);
        }

        $entry = app(WaitlistManager::class)->createPublicEntry($data);

        $this->generalPosition = app(WaitlistManager::class)->generalPosition($entry);
        $this->sexPosition = app(WaitlistManager::class)->sexPosition($entry);

        $this->form->fill();

        Notification::make()
            ->title('Você entrou na fila de espera')
            ->body(sprintf(
                'Sua posição geral é %s e sua posição por sexo é %s.',
                $this->generalPosition,
                $this->sexPosition,
            ))
            ->success()
            ->send();

        return null;
    }
}
