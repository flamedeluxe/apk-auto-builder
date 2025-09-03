<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BuildResource\Pages;
use App\Models\Build;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;

class BuildResource extends Resource
{
    protected static ?string $model = Build::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Сборки';

    protected static ?string $modelLabel = 'Сборка';

    protected static ?string $pluralModelLabel = 'Сборки';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('project_id')
                    ->label('Проект')
                    ->relationship('project', 'name')
                    ->required(),
                Forms\Components\TextInput::make('build_id')
                    ->label('Build ID')
                    ->required(),
                Forms\Components\TextInput::make('workflow_name')
                    ->label('Workflow')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Статус')
                    ->options([
                        'started' => 'Началась',
                        'debug_started' => 'Debug началась',
                        'finished' => 'Завершена',
                        'published' => 'Опубликована',
                        'debug_published' => 'Debug опубликована',
                        'promoted_to_production' => 'Продвинута в Production',
                        'failed' => 'Ошибка',
                        'error' => 'Ошибка',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('artifact_url')
                    ->label('Ссылка на артефакт')
                    ->url(),
                Forms\Components\TextInput::make('track')
                    ->label('Трек'),
                Forms\Components\DateTimePicker::make('started_at')
                    ->label('Начало'),
                Forms\Components\DateTimePicker::make('finished_at')
                    ->label('Завершение'),
                Forms\Components\Textarea::make('error_message')
                    ->label('Сообщение об ошибке')
                    ->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')
                    ->label('Проект')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('workflow_name')
                    ->label('Workflow')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'build-release-and-publish-beta' => 'success',
                        'build-debug' => 'warning',
                        'beta-to-release' => 'info',
                        default => 'gray',
                    }),
                BadgeColumn::make('status')
                    ->label('Статус')
                    ->getStateUsing(fn (Build $record) => $record->status_text)
                    ->color(fn (Build $record) => $record->status_color),
                TextColumn::make('track')
                    ->label('Трек')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'production' => 'success',
                        'beta' => 'warning',
                        'alpha' => 'info',
                        'debug' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('started_at')
                    ->label('Начало')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('duration')
                    ->label('Длительность')
                    ->getStateUsing(fn (Build $record) => $record->duration ? $record->duration . ' мин' : '-'),
                TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Проект')
                    ->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'started' => 'Началась',
                        'debug_started' => 'Debug началась',
                        'finished' => 'Завершена',
                        'published' => 'Опубликована',
                        'debug_published' => 'Debug опубликована',
                        'promoted_to_production' => 'Продвинута в Production',
                        'failed' => 'Ошибка',
                        'error' => 'Ошибка',
                    ]),
                Tables\Filters\SelectFilter::make('track')
                    ->label('Трек')
                    ->options([
                        'alpha' => 'Alpha',
                        'beta' => 'Beta',
                        'production' => 'Production',
                        'debug' => 'Debug',
                    ]),
            ])
            ->actions([
                Action::make('download')
                    ->label('Скачать')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Build $record) => $record->artifact_url)
                    ->openUrlInNewTab()
                    ->visible(fn (Build $record) => !empty($record->artifact_url)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBuilds::route('/'),
            'create' => Pages\CreateBuild::route('/create'),
            'edit' => Pages\EditBuild::route('/{record}/edit'),
        ];
    }
}
