<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('type'); // 'expense' / 'income'
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::table('fixed_cost_templates', function (Blueprint $table) {
            $table->unsignedBigInteger('new_category_id')->nullable()->after('category_id');
        });

        Schema::table('fixed_cost_occurrences', function (Blueprint $table) {
            $table->unsignedBigInteger('new_category_id')->nullable()->after('category_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('new_category_id')->nullable()->after('category_id');
        });

        // mapping old category schema to new unified category table
        $systemCategoryMap = [];
        $customCategoryMap = [];

        $oldSystemCategories = DB::table('system_categories')->get();
        foreach ($oldSystemCategories as $category) {
            $newId = DB::table('categories')->insertGetId([
                'user_id' => null,
                'name' => $category->name,
                'icon' => $category->icon ?? null,
                'type' => $category->type ?? 'expense',
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $systemCategoryMap[$category->id] = $newId;
        }

        $oldCustomCategories = DB::table('custom_categories')->get();
        foreach ($oldCustomCategories as $category) {
            $newId = DB::table('categories')->insertGetId([
                'user_id' => $category->user_id,
                'name' => $category->name,
                'icon' => $category->icon ?? null,
                'type' => $category->type ?? 'expense',
                'is_system' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $customCategoryMap[$category->id] = $newId;
        }

        $updateTableCategory = function ($tableName) use ($systemCategoryMap, $customCategoryMap) {
            DB::table($tableName)->orderBy('id')->chunk(1000, function ($rows) use ($tableName, $systemCategoryMap, $customCategoryMap) {
                foreach ($rows as $row) {
                    $newCategoryId = null;
                    if (str_contains($row->category_type, 'SystemCategory')) {
                        $newCategoryId = $systemCategoryMap[$row->category_id] ?? null;
                    } elseif (str_contains($row->category_type, 'CustomCategory')) {
                        $newCategoryId = $customCategoryMap[$row->category_id] ?? null;
                    }

                    if ($newCategoryId) {
                        DB::table($tableName)->where('id', $row->id)->update(['new_category_id' => $newCategoryId]);
                    }
                }
            });
        };

        $updateTableCategory('fixed_cost_templates');
        $updateTableCategory('fixed_cost_occurrences');
        $updateTableCategory('transactions');

        $tablesWithIndex = ['fixed_cost_templates', 'transactions'];
        foreach ($tablesWithIndex as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['category_type', 'category_id']);
            });
        }

        $tablesToContract = ['fixed_cost_templates', 'fixed_cost_occurrences', 'transactions'];
        foreach ($tablesToContract as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('category_type');
                $table->dropColumn('category_id');
                $table->renameColumn('new_category_id', 'category_id');
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
            });
        }

        Schema::dropIfExists('system_categories');
        Schema::dropIfExists('custom_categories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
