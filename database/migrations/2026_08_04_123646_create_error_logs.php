<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_resolved')->default(false);
            $table->string('source');
            $table->string('level');
            $table->string('exception_class')->nullable();
            $table->text('message');
            $table->string('file')->nullable();
            $table->string('line')->nullable();
            $table->text('stack_trace')->nullable();
            $table->string('http_method')->nullable();
            $table->string('url')->nullable();
            $table->string('route_name')->nullable();
            $table->text('query_params')->nullable();
            $table->jsonb('request_payload')->nullable();
            $table->jsonb('request_headers')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('app_module')->nullable();
            $table->string('job_name')->nullable();
            $table->string('correlation_id')->nullable();
            $table->jsonb('extra_data')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('level');
            $table->index('is_resolved');
            $table->index('correlation_id');
            $table->index('created_at');
            $table->index('exception_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
