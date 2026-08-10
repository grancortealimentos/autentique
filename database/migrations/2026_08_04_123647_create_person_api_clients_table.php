<?php

use App\Models\ApiClient;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_api_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Person::class, 'person_id')->constrained('persons')->cascadeOnDelete();
            $table->foreignIdFor(ApiClient::class, 'api_client_id')->constrained('api_clients')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->foreignIdFor(User::class, 'granted_by')->constrained('users');
            $table->foreignIdFor(User::class, 'revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();

            $table->unique(['person_id', 'api_client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_api_clients');
    }
};
