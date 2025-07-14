<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class CreateUserStorageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    /**
     * Crée une nouvelle instance du job.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Exécute le job.
     */
    public function handle(): void
    {
        // Exemple : storage/app/users/{id}/
        $path = 'users/' . $this->user->id;

        // Crée le répertoire s'il n'existe pas
        if (!Storage::exists($path)) {
            Storage::makeDirectory($path);
        }
    }
}
