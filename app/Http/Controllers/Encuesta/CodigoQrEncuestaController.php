<?php

namespace App\Http\Controllers\Encuesta;

use App\Http\Controllers\Controller;
use App\Models\Sede;
use App\Services\Sedes\ServicioSedes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CodigoQrEncuestaController extends Controller
{
    public function __construct(
        private readonly ServicioSedes $sedeService,
    ) {}

    public function show(Request $request): View
    {
        $selectedSedeId = $this->sedeService->resolveForRequest(
            $request->user(),
            $request,
            'id_sede',
            false,
            true
        );
        $selectedSede = Sede::query()->findOrFail($selectedSedeId ?? Sede::ID_MAICAO);
        $surveyUrl = route('survey.create', ['sede' => $selectedSede->slug]);
        $subject = 'Encuesta institucional de medicion de servicios';
        $body = 'Comparto el acceso directo a la encuesta institucional:'."\n\n".$surveyUrl;

        return view('encuesta.qr', [
            'canSelectSede' => $request->user()?->hasGlobalSedeAccess() ?? false,
            'gmailShareUrl' => 'https://mail.google.com/mail/?view=cm&fs=1&su='.urlencode($subject).'&body='.urlencode($body),
            'selectedSede' => $selectedSede,
            'selectedSedeId' => (int) $selectedSede->id_sede,
            'sedes' => $this->sedeService->visibleTo($request->user()),
            'surveyUrl' => $surveyUrl,
            'qrImageUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=480x480&margin=16&data='.urlencode($surveyUrl),
            'whatsAppShareUrl' => 'https://wa.me/?text='.urlencode('Comparto el acceso directo a la encuesta institucional: '.$surveyUrl),
        ]);
    }
}
