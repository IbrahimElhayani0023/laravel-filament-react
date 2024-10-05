<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->relationship('user', 'name')
                                    ->required()
                                    ->preload()
                                    ->searchable(),
                                Forms\Components\ToggleButtons::make('order_status')
                                    ->options([
                                        'new' => 'New',
                                        'processing' => 'Processing',
                                        'shipped' => 'Shipped',
                                        'delivered' => 'Delivered',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->colors([
                                        'new' => 'info',
                                        'processing' => 'warning',
                                        'shipped' => 'success',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger'
                                    ])->icons([
                                        'new' => 'heroicon-o-plus',
                                        'processing' => 'heroicon-o-arrow-path-rounded-square',
                                        'shipped' => 'heroicon-o-truck',
                                        'delivered' => 'heroicon-o-check-badge',
                                        'cancelled' => 'heroicon-o-x-mark',
                                    ])
                                    ->inline()
                                    ->default('new')
                                    ->required(),
                                Forms\Components\Select::make('payment_method')
                                    ->options([
                                        'stripe' => 'Stripe',
                                        'cod' => 'Cash On Delivery',
                                    ]),
                                Forms\Components\Select::make('payment_status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'paid' => 'Paid',
                                        'failed' => 'Failed'
                                    ]),
                                Forms\Components\Select::make('currency')
                                    ->options([
                                        'usd' => 'USD',
                                        'mad' => 'MAD',
                                        'eur' => 'EUR',
                                    ]),
                                Forms\Components\Select::make('shipping_method')
                                    ->options([
                                        'flat_rate' => 'Flat Rate',
                                        'local_pickup' => 'Local Pickup',
                                    ]),
                                Forms\Components\MarkdownEditor::make('note')
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Forms\Components\Section::make('Order Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->required()
                                    ->columnSpan(4)
                                    ->reactive()
                                    ->afterStateUpdated(fn(Set $set, ?string $state) => $set('unit_amount', $state ? Product::find($state)->price : 0))
                                    ->afterStateUpdated(fn(Set $set, ?string $state) => $set('total_amount', $state ? Product::find($state)->price : 0)),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn(Set $set, ?string $state, Get $get) => $set('total_amount', $state * $get('unit_amount')))
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('unit_amount')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->default(0)
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('total_amount')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->default(0)
                                    ->columnSpan(3),
                            ])->columns(12),
                        Forms\Components\Placeholder::make('grand_total_placeholder')
                            ->label('Grand Total')
                            ->content(function (Get $get, Set $set) {
                                $total = 0;
                                if (!$repeaters = $get('items')) {
                                    return $total;
                                }

                                foreach ($repeaters as $key => $repeater) {
                                    $total += $get("items.{$key}.total_amount");
                                }
                                $set('total_price', $total);
                                return Number::currency($total, 'USD');
                            }),
                        Forms\Components\Hidden::make('total_price')

                            ->default(0),
                        Forms\Components\Hidden::make('shipping_amount')
                            ->default(1),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_status')
                    ->searchable(),
                Tables\Columns\SelectColumn::make('order_status')
                    ->options([
                        'new' => 'New',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled'
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('currency')
                    ->searchable(),
                Tables\Columns\TextColumn::make('shipping_method')
                    ->searchable(),
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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                ]),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
