<?php

namespace App\Enums;

enum CustomerCategory: string
{
    use HasLabel;

    case Pharmacy = 'pharmacy';
    case BeautyCenter = 'beauty_center';
    case Wholesale = 'wholesale';
    case Retail = 'retail';
    case Distributor = 'distributor';
}
