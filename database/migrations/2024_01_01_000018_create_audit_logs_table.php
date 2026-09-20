<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entity_type'); // App\Models\Product, etc.
            $table->unsignedBigInteger('entity_id');
            $table->enum('action', ['created', 'updated', 'deleted', 'restored', 'login', 'logout', 'failed_login', 'permission_changed', 'exported']);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('event_type')->nullable(); // custom event categorization
            $table->text('description')->nullable();
            $table->timestamps(); // created_at is the timestamp of the action

            $table->index(['entity_type', 'entity_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
            $table->index('created_at');

            // Note: Audit logs are immutable - no updates or deletes allowed via model
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
