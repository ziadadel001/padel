<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Colors\Color;

class SendNotification extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string $view = 'filament.pages.send-notification';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Send Notification';

    protected ?string $heading = 'Send Notification to Staff';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Radio::make('recipient_type')
                            ->label('Recipient Type')
                            ->options([
                                'all' => 'All Staff',
                                'specific' => 'Specific Staff',
                            ])
                            ->default('all')
                            ->reactive()
                            ->required(),

                        Select::make('staff_ids')
                            ->label('Select Staff Members')
                            ->multiple()
                            ->options(User::role('staff')->pluck('name', 'id'))
                            ->visible(fn($get) => $get('recipient_type') === 'specific')
                            ->required(fn($get) => $get('recipient_type') === 'specific')
                            ->searchable(),

                        TextInput::make('title')
                            ->label('Notification Title')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('message')
                            ->label('Notification Message')
                            ->required()
                            ->rows(3),

                        Select::make('type')
                            ->label('Notification Type')
                            ->options([
                                'success' => 'Success',
                                'info' => 'Info',
                                'warning' => 'Warning',
                                'danger' => 'Danger',
                            ])
                            ->default('info')
                            ->required(),
                    ])
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $data = $this->form->getState();

        if ($data['recipient_type'] === 'all') {
            $recipients = User::role('staff')->get();
        } else {
            $recipients = User::whereIn('id', $data['staff_ids'])->get();
        }

        if ($recipients->isEmpty()) {
            Notification::make()
                ->title('No recipients found')
                ->danger()
                ->send();
            return;
        }

        $notification = Notification::make()
            ->title($data['title'])
            ->body($data['message']);

        // Set color based on type
        $notification = match ($data['type']) {
            'success' => $notification->success(),
            'warning' => $notification->warning(),
            'danger' => $notification->danger(),
            default => $notification->info(),
        };

        // Force 'sync' queue to ensure database notifications are saved immediately
        $oldQueue = config('queue.default');
        config(['queue.default' => 'sync']);

        foreach ($recipients as $recipient) {
            $notification->sendToDatabase($recipient);
        }

        config(['queue.default' => $oldQueue]);

        Notification::make()
            ->title('Notification sent successfully')
            ->success()
            ->send();

        $this->form->fill();
    }
}
