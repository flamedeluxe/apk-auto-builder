<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Проекты';

    protected static ?string $modelLabel = 'Проект';

    protected static ?string $pluralModelLabel = 'Проекты';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Название проекта')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('application_name')
                            ->label('Название приложения')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('package_name')
                            ->label('Package Name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Например: com.example.myapp'),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3),
                    ])->columns(2),

                Forms\Components\Section::make('Настройки сборки')
                    ->schema([
                        Forms\Components\TextInput::make('gitverse_repo_url')
                            ->label('Gitverse Repository URL')
                            ->required()
                            ->url()
                            ->helperText('SSH URL репозитория в Gitverse'),
                        Forms\Components\TextInput::make('codemagic_app_id')
                            ->label('Codemagic App ID')
                            ->maxLength(255)
                            ->helperText('ID приложения в Codemagic (опционально)'),
                        Forms\Components\Select::make('build_type')
                            ->label('Тип сборки')
                            ->options([
                                'debug' => 'Debug',
                                'release' => 'Release',
                            ])
                            ->default('release')
                            ->required(),
                        Forms\Components\TextInput::make('gradle_task')
                            ->label('Gradle Task')
                            ->default('bundleRelease')
                            ->required()
                            ->helperText('Например: assembleDebug, bundleRelease'),
                        Forms\Components\Select::make('google_play_track')
                            ->label('Google Play Track')
                            ->options([
                                'alpha' => 'Alpha',
                                'beta' => 'Beta',
                                'production' => 'Production',
                            ])
                            ->default('beta')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Уведомления')
                    ->schema([
                        Forms\Components\TagsInput::make('email_recipients')
                            ->label('Email получатели')
                            ->helperText('Список email адресов для уведомлений'),
                        Forms\Components\TextInput::make('telegram_chat_id')
                            ->label('Telegram Chat ID')
                            ->helperText('ID чата для Telegram уведомлений'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активный проект')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('application_name')
                    ->label('Приложение')
                    ->searchable(),
                TextColumn::make('package_name')
                    ->label('Package')
                    ->searchable()
                    ->copyable(),
                BadgeColumn::make('build_status')
                    ->label('Статус сборки')
                    ->getStateUsing(fn (Project $record) => $record->build_status)
                    ->colors([
                        'success' => 'finished',
                        'warning' => 'started',
                        'danger' => 'failed',
                        'secondary' => 'never_built',
                    ]),
                TextColumn::make('latest_build.created_at')
                    ->label('Последняя сборка')
                    ->dateTime()
                    ->sortable(),
                BadgeColumn::make('is_active')
                    ->label('Статус')
                    ->getStateUsing(fn (Project $record) => $record->is_active ? 'Активен' : 'Неактивен')
                    ->colors([
                        'success' => 'Активен',
                        'danger' => 'Неактивен',
                    ]),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные проекты'),
            ])
            ->actions([
                Action::make('build_release')
                    ->label('Собрать Release')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(function (Project $record) {
                        app(\App\Services\CodemagicService::class)->triggerBuild($record, 'build-release-and-publish-beta');
                        
                        Notification::make()
                            ->title('Сборка запущена')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Project $record) => $record->is_active),
                
                Action::make('build_debug')
                    ->label('Собрать Debug')
                    ->icon('heroicon-o-bug-ant')
                    ->color('warning')
                    ->action(function (Project $record) {
                        app(\App\Services\CodemagicService::class)->triggerBuild($record, 'build-debug');
                        
                        Notification::make()
                            ->title('Debug сборка запущена')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Project $record) => $record->is_active),

                Action::make('promote_to_production')
                    ->label('В Production')
                    ->icon('heroicon-o-arrow-up')
                    ->color('info')
                    ->action(function (Project $record) {
                        app(\App\Services\CodemagicService::class)->triggerBuild($record, 'beta-to-release');
                        
                        Notification::make()
                            ->title('Продвижение в Production запущено')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Project $record) => $record->is_active),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
