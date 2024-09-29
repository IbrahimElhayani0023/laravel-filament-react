<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('General')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(callable $set, ?string $state) => $set('slug', Str::slug($state)))
                                    ->required(),
                                Forms\Components\MarkdownEditor::make('description')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('price')
                                    ->required()
                                    ->numeric()
                                    ->prefix('$'),
                            ])
                    ])->columnSpan(2),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('SEO')
                            ->schema([
                                Forms\Components\FileUpload::make('images')
                                    ->columnSpanFull()
                                    ->image()
                                    ->multiple(),
                                Forms\Components\Select::make('category')
                                    ->required(),
                                Forms\Components\Select::make('brand')
                                    ->required(),
                                Forms\Components\TextInput::make('slug')
                                    ->unique(Product::class, 'slug', ignoreRecord: true)
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                            ]),
                        Forms\Components\Section::make('Status')
                            ->schema([
                                Forms\Components\TextInput::make('stock')
                                    ->required()
                                    ->numeric()
                                    ->default(1),
                                Forms\Components\Toggle::make('published')
                                    ->required(),
                                Forms\Components\Toggle::make('is_featured')
                                    ->required(),
                            ])
                    ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('published')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function (Product $record) {
                        if ($record->galery) {
                            foreach ($record->galery as $ph) Storage::disk('public')->delete($ph);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
