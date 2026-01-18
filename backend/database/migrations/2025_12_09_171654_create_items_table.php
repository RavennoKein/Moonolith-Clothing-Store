<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->string('bahan', 100)->nullable();
            $table->enum('tingkat_ketebalan', ['tipis', 'sedang', 'tebal'])->nullable();
            $table->enum('status_produk', ['aktif', 'non_aktif', 'terbatas'])->default('aktif');
            $table->enum('gender', ['men', 'women', 'kids', 'unisex'])->default('unisex');
            $table->string('image_url')->nullable();
            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};