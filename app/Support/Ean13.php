<?php

namespace App\Support;

/** Minimal EAN-13 generator / SVG renderer (for printable barcode labels). */
class Ean13
{
    private const L = ['0001101','0011001','0010011','0111101','0100011','0110001','0101111','0111011','0110111','0001011'];
    private const G = ['0100111','0110011','0011011','0100001','0011101','0111001','0000101','0010001','0001001','0010111'];
    private const R = ['1110010','1100110','1101100','1000010','1011100','1001110','1010000','1000100','1001000','1110100'];
    private const P = ['LLLLLL','LLGLGG','LLGGLG','LLGGGL','LGLLGG','LGGLLG','LGGGLL','LGLGLG','LGLGGL','LGGLGL'];

    public static function check(string $d12): int
    {
        $s = 0;
        for ($i = 0; $i < 12; $i++) { $s += (int) $d12[$i] * ($i % 2 === 0 ? 1 : 3); }
        return (10 - $s % 10) % 10;
    }

    public static function make(string $d12): string { return $d12.self::check($d12); }

    public static function valid(?string $c): bool
    {
        return $c !== null && preg_match('/^\d{13}$/', $c) === 1 && self::check(substr($c, 0, 12)) === (int) $c[12];
    }

    public static function svg(?string $code, int $height = 56, int $module = 2): string
    {
        if (! self::valid($code)) { return ''; }
        $par = self::P[(int) $code[0]];
        $bits = '101';
        for ($i = 1; $i <= 6; $i++) {
            $t = $par[$i - 1] === 'L' ? self::L : self::G;
            $bits .= $t[(int) $code[$i]];
        }
        $bits .= '01010';
        for ($i = 7; $i <= 12; $i++) { $bits .= self::R[(int) $code[$i]]; }
        $bits .= '101';

        $w = strlen($bits) * $module;
        $bars = '';
        for ($i = 0, $n = strlen($bits); $i < $n; $i++) {
            if ($bits[$i] === '1') {
                $bars .= '<rect x="'.($i * $module).'" y="0" width="'.$module.'" height="'.$height.'" fill="#0F172A"/>';
            }
        }
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$w.' '.$height.'" width="'.$w.'" height="'.$height.'" role="img" aria-label="'.e($code).'">'.$bars.'</svg>';
    }
}
