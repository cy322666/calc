<?php

namespace App\Filament\App\Resources\Categories;

//use App\Filament\Clusters\Products\ProductsCluster;
//use App\Filament\Clusters\Products\Resources\Categories\Pages\CreateCategory;
//use App\Filament\Clusters\Products\Resources\Categories\Pages\EditCategory;
//use App\Filament\Clusters\Products\Resources\Categories\Pages\ListCategories;
//use App\Filament\Clusters\Products\Resources\Categories\RelationManagers\ProductsRelationManager;
//use App\Filament\Clusters\Products\Resources\Categories\Schemas\CategoryForm;
//use App\Filament\Clusters\Products\Resources\Categories\Tables\CategoriesTable;
use App\Filament\App\Resources\Categories\Pages\CreateCategory;
use App\Filament\App\Resources\Categories\Pages\EditCategory;
use App\Filament\App\Resources\Categories\Pages\ListCategories;
use App\Filament\App\Resources\Categories\Schemas\CategoryForm;
use App\Filament\App\Resources\Categories\Tables\CategoriesTable;
use App\Models\Calculator\Category;
use BackedEnum;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

//    protected static ?string $cluster = ProductsCluster::class;

    protected static ?string $recordTitleAttribute = 'name';

//    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-tag';

//    protected static ?string $navigationParentItem = 'Products';

//    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Категории';

    public static function form(Form $schema): Form
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
//            ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
