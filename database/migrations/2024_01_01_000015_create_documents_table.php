<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // uploaded by
            $table->string('entity_type'); // App\Models\Product, App\Models\PurchaseOrder, etc.
            $table->unsignedBigInteger('entity_id');
            $table->string('file_name');
            $table->string('original_name');
            $table->string('mime_type');
            $table->integer('file_size'); // in bytes
            $table->string('disk')->default('local'); // local, s3, minio
            $table->string('path');
            $table->enum('type', ['image', 'spec_sheet', 'invoice', 'delivery_note', 'stock_take_report', 'other']);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['entity_type', 'entity_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
