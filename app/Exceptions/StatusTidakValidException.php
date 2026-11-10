<?php

namespace App\Exceptions;

use DomainException;

/** Dilempar saat sebuah pengajuan tidak berada pada status yang diizinkan untuk aksi tertentu. */
class StatusTidakValidException extends DomainException
{
}
