<?php

namespace App\Jobs;

use App\Models\Membre;
use App\Models\SmsEnvoi;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EnvoyerSmsEnvoiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SmsEnvoi $envoi
    ) {}

    public function handle(SmsService $sms): void
    {
        $envoi = $this->envoi;
        if ($envoi->statut !== 'programme') {
            return;
        }

        $envoi->update(['statut' => 'en_cours']);
        $query = Membre::where('fd_id', $envoi->fd_id)
            ->where('actif', true)
            ->whereNotNull('telephone')
            ->where('telephone', '!=', '');

        if (!empty($envoi->membre_ids)) {
            $query->whereIn('id', $envoi->membre_ids);
        }

        $membres = $query->get(['id', 'prenom', 'nom', 'telephone']);
        $sent = 0;
        foreach ($membres as $m) {
            $result = $sms->send($m->telephone, $envoi->message);
            if ($result['success']) {
                $sent++;
            }
        }

        $envoi->update([
            'statut' => 'envoye',
            'envoye_at' => now(),
        ]);
    }
}
