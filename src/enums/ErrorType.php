<?php

declare(strict_types=1);

enum ErrorType: string
{
    case NOT_FOUND = 'NOT_FOUND';
    case FORBIDDEN = 'FORBIDDEN';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case SERVER_ERROR = 'SERVER_ERROR';
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case BAD_REQUEST = 'BAD_REQUEST';

    public function label(): string
    {
        return match ($this) {
            self::NOT_FOUND => 'Stranica nije pronađena',
            self::FORBIDDEN => 'Pristup odbijen',
            self::UNAUTHORIZED => 'Niste prijavljeni',
            self::SERVER_ERROR => 'Interna greška servera',
            self::VALIDATION_ERROR => 'Greška u validaciji',
            self::BAD_REQUEST => 'Neispravan zahtev',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::NOT_FOUND => 'Tražena stranica ne postoji ili je uklonjena.',
            self::FORBIDDEN => 'Nemate dozvolu za pristup ovoj stranici.',
            self::UNAUTHORIZED => 'Morate biti prijavljeni da biste pristupili ovoj stranici.',
            self::SERVER_ERROR => 'Došlo je do neočekivane greške. Molimo pokušajte ponovo.',
            self::VALIDATION_ERROR => 'Podaci koje ste poslali nisu ispravni.',
            self::BAD_REQUEST => 'Zahtev nije mogao biti obrađen zbog neispravnih parametara.',
        };
    }

    public function httpCode(): int
    {
        return match ($this) {
            self::NOT_FOUND => 404,
            self::FORBIDDEN => 403,
            self::UNAUTHORIZED => 401,
            self::SERVER_ERROR => 500,
            self::VALIDATION_ERROR => 422,
            self::BAD_REQUEST => 400,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::NOT_FOUND => 'fa-map-signs',
            self::FORBIDDEN => 'fa-lock',
            self::UNAUTHORIZED => 'fa-user-lock',
            self::SERVER_ERROR => 'fa-gear',
            self::VALIDATION_ERROR => 'fa-circle-exclamation',
            self::BAD_REQUEST => 'fa-triangle-exclamation',
        };
    }
}
