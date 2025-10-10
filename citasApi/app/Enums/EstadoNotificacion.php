<?php

namespace App\Enums;

enum EstadoNotificacion: string
{
    case PENDIENTE = 'pendiente';
    case APROBADA = 'aprobada';
    case RECHAZADA = 'rechazada';
}
