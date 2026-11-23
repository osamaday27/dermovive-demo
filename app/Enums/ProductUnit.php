<?php

namespace App\Enums;

enum ProductUnit: string
{
    use HasLabel;

    case Box = 'box';
    case Bottle = 'bottle';
    case Tube = 'tube';
    case Carton = 'carton';
    case Piece = 'piece';
}
