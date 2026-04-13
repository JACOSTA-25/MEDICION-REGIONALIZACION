<?php

namespace App\Http\Controllers\Encuesta;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class CodigoQrEncuestaController extends Controller
{
    private const SURVEY_URL = 'https://medicion.desarrollougmaicao.com/encuesta';

    public function show(): View
    {
        $surveyUrl = self::SURVEY_URL;
        $subject = 'Encuesta institucional de medicion de servicios';
        $body = 'Comparto el acceso directo a la encuesta institucional:'."\n\n".$surveyUrl;

        return view('encuesta.qr', [
            'gmailShareUrl' => 'https://mail.google.com/mail/?view=cm&fs=1&su='.urlencode($subject).'&body='.urlencode($body),
            'surveyUrl' => $surveyUrl,
            'qrImageUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=480x480&margin=16&data='.urlencode($surveyUrl),
            'whatsAppShareUrl' => 'https://wa.me/?text='.urlencode('Comparto el acceso directo a la encuesta institucional: '.$surveyUrl),
        ]);
    }
}
