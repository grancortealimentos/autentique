<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateApiClient extends Command
{
    protected $signature = 'api-client:create {name : Nome da aplicação consumidora} {url : URL base da aplicação}';

    protected $description = 'Provisiona uma nova aplicação cliente (ApiClient) e gera a X-API-KEY dela';

    public function handle(): int
    {
        $plainTextKey = Str::random(64);

        $apiClient = new ApiClient([
            'name' => $this->argument('name'),
            'url' => $this->argument('url'),
            'is_active' => true,
        ]);
        // api_key_hash é intencionalmente não-fillable (nunca deve vir de request HTTP).
        $apiClient->forceFill(['api_key_hash' => hash('sha256', $plainTextKey)]);
        $apiClient->save();

        $this->components->info("Aplicação \"{$apiClient->name}\" criada (id: {$apiClient->id}).");

        $this->newLine();
        $this->line('  X-API-KEY: <fg=yellow;options=bold>' . $plainTextKey . '</>');
        $this->newLine();

        $this->components->warn('Essa chave não é recuperável. Guarde-a agora — ela não será exibida novamente.');

        return self::SUCCESS;
    }
}
