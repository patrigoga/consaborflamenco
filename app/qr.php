<?php
declare(strict_types=1);

function csf_qr_svg(string $data, int $scale = 5, int $border = 4): string
{
    $matrix = csf_qr_matrix($data);
    $moduleCount = count($matrix);
    $viewSize = $moduleCount + ($border * 2);
    $pixelSize = $viewSize * $scale;
    $path = [];

    foreach ($matrix as $y => $row) {
        foreach ($row as $x => $dark) {
            if ($dark) {
                $path[] = 'M' . ($x + $border) . ' ' . ($y + $border) . 'h1v1h-1z';
            }
        }
    }

    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $viewSize . ' ' . $viewSize . '" width="' . $pixelSize . '" height="' . $pixelSize . '" role="img" aria-label="Codigo QR">'
        . '<rect width="100%" height="100%" fill="#fff"/>'
        . '<path fill="#111114" d="' . implode('', $path) . '"/>'
        . '</svg>';
}

function csf_qr_matrix(string $data): array
{
    $version = 7;
    $size = 21 + (($version - 1) * 4);
    $dataCodewords = 156;
    $blockDataCodewords = 78;
    $eccCodewords = 20;
    $mask = 0;
    $bytes = array_values(unpack('C*', $data) ?: []);

    if ($data === '' || count($bytes) > $dataCodewords - 2) {
        throw new InvalidArgumentException('El texto del QR esta vacio o es demasiado largo.');
    }

    $bits = [];
    csf_qr_append_bits($bits, 0b0100, 4);
    csf_qr_append_bits($bits, count($bytes), 8);
    foreach ($bytes as $byte) {
        csf_qr_append_bits($bits, $byte, 8);
    }

    $capacityBits = $dataCodewords * 8;
    csf_qr_append_bits($bits, 0, min(4, $capacityBits - count($bits)));
    while ((count($bits) % 8) !== 0) {
        $bits[] = 0;
    }

    $codewords = [];
    foreach (array_chunk($bits, 8) as $byteBits) {
        $value = 0;
        foreach ($byteBits as $bit) {
            $value = ($value << 1) | $bit;
        }
        $codewords[] = $value;
    }

    $padBytes = [0xEC, 0x11];
    $padIndex = 0;
    while (count($codewords) < $dataCodewords) {
        $codewords[] = $padBytes[$padIndex % 2];
        $padIndex++;
    }

    $blocks = [
        array_slice($codewords, 0, $blockDataCodewords),
        array_slice($codewords, $blockDataCodewords, $blockDataCodewords),
    ];
    $eccBlocks = [
        csf_qr_rs_remainder($blocks[0], $eccCodewords),
        csf_qr_rs_remainder($blocks[1], $eccCodewords),
    ];
    $finalCodewords = [];

    for ($i = 0; $i < $blockDataCodewords; $i++) {
        $finalCodewords[] = $blocks[0][$i];
        $finalCodewords[] = $blocks[1][$i];
    }
    for ($i = 0; $i < $eccCodewords; $i++) {
        $finalCodewords[] = $eccBlocks[0][$i];
        $finalCodewords[] = $eccBlocks[1][$i];
    }

    $matrix = array_fill(0, $size, array_fill(0, $size, false));
    $function = array_fill(0, $size, array_fill(0, $size, false));
    csf_qr_draw_function_patterns($matrix, $function, $version);
    csf_qr_draw_format_bits($matrix, $function, $mask);

    $dataBits = [];
    foreach ($finalCodewords as $codeword) {
        csf_qr_append_bits($dataBits, $codeword, 8);
    }
    csf_qr_draw_data_bits($matrix, $function, $dataBits, $mask);

    return $matrix;
}

function csf_qr_append_bits(array &$bits, int $value, int $length): void
{
    for ($i = $length - 1; $i >= 0; $i--) {
        $bits[] = ($value >> $i) & 1;
    }
}

function csf_qr_draw_function_patterns(array &$matrix, array &$function, int $version): void
{
    $size = count($matrix);
    csf_qr_draw_finder($matrix, $function, 0, 0);
    csf_qr_draw_finder($matrix, $function, $size - 7, 0);
    csf_qr_draw_finder($matrix, $function, 0, $size - 7);

    for ($i = 8; $i < $size - 8; $i++) {
        $dark = ($i % 2) === 0;
        csf_qr_set_function($matrix, $function, $i, 6, $dark);
        csf_qr_set_function($matrix, $function, 6, $i, $dark);
    }

    foreach ([6, 22, 38] as $x) {
        foreach ([6, 22, 38] as $y) {
            if (($x === 6 && $y === 6) || ($x === 38 && $y === 6) || ($x === 6 && $y === 38)) {
                continue;
            }
            csf_qr_draw_alignment($matrix, $function, $x, $y);
        }
    }

    csf_qr_set_function($matrix, $function, 8, (4 * $version) + 9, true);
    csf_qr_draw_version_bits($matrix, $function, $version);
}

function csf_qr_draw_finder(array &$matrix, array &$function, int $left, int $top): void
{
    $size = count($matrix);

    for ($dy = -1; $dy <= 7; $dy++) {
        for ($dx = -1; $dx <= 7; $dx++) {
            $x = $left + $dx;
            $y = $top + $dy;
            if ($x < 0 || $x >= $size || $y < 0 || $y >= $size) {
                continue;
            }

            $inside = $dx >= 0 && $dx <= 6 && $dy >= 0 && $dy <= 6;
            $dark = $inside && (
                $dx === 0 || $dx === 6 || $dy === 0 || $dy === 6 ||
                ($dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4)
            );
            csf_qr_set_function($matrix, $function, $x, $y, $dark);
        }
    }
}

function csf_qr_draw_alignment(array &$matrix, array &$function, int $centerX, int $centerY): void
{
    for ($dy = -2; $dy <= 2; $dy++) {
        for ($dx = -2; $dx <= 2; $dx++) {
            $distance = max(abs($dx), abs($dy));
            csf_qr_set_function($matrix, $function, $centerX + $dx, $centerY + $dy, $distance !== 1);
        }
    }
}

function csf_qr_draw_format_bits(array &$matrix, array &$function, int $mask): void
{
    $size = count($matrix);
    $data = (0b01 << 3) | $mask;
    $bits = (($data << 10) | csf_qr_bch_remainder($data, 0x537, 10)) ^ 0x5412;

    for ($i = 0; $i <= 5; $i++) {
        csf_qr_set_function($matrix, $function, 8, $i, csf_qr_get_bit($bits, $i));
    }
    csf_qr_set_function($matrix, $function, 8, 7, csf_qr_get_bit($bits, 6));
    csf_qr_set_function($matrix, $function, 8, 8, csf_qr_get_bit($bits, 7));
    csf_qr_set_function($matrix, $function, 7, 8, csf_qr_get_bit($bits, 8));
    for ($i = 9; $i < 15; $i++) {
        csf_qr_set_function($matrix, $function, 14 - $i, 8, csf_qr_get_bit($bits, $i));
    }

    for ($i = 0; $i < 8; $i++) {
        csf_qr_set_function($matrix, $function, $size - 1 - $i, 8, csf_qr_get_bit($bits, $i));
    }
    for ($i = 8; $i < 15; $i++) {
        csf_qr_set_function($matrix, $function, 8, $size - 15 + $i, csf_qr_get_bit($bits, $i));
    }
    csf_qr_set_function($matrix, $function, 8, $size - 8, true);
}

function csf_qr_draw_version_bits(array &$matrix, array &$function, int $version): void
{
    $size = count($matrix);
    $bits = ($version << 12) | csf_qr_bch_remainder($version, 0x1F25, 12);

    for ($i = 0; $i < 18; $i++) {
        $dark = csf_qr_get_bit($bits, $i);
        $x = $size - 11 + ($i % 3);
        $y = intdiv($i, 3);
        csf_qr_set_function($matrix, $function, $x, $y, $dark);
        csf_qr_set_function($matrix, $function, $y, $x, $dark);
    }
}

function csf_qr_draw_data_bits(array &$matrix, array $function, array $bits, int $mask): void
{
    $size = count($matrix);
    $bitIndex = 0;
    $upward = true;

    for ($right = $size - 1; $right >= 1; $right -= 2) {
        if ($right === 6) {
            $right--;
        }

        for ($vertical = 0; $vertical < $size; $vertical++) {
            $y = $upward ? $size - 1 - $vertical : $vertical;
            for ($columnOffset = 0; $columnOffset < 2; $columnOffset++) {
                $x = $right - $columnOffset;
                if ($function[$y][$x]) {
                    continue;
                }

                $dark = ($bitIndex < count($bits) && $bits[$bitIndex] === 1);
                if ((($x + $y) % 2) === 0) {
                    $dark = !$dark;
                }
                $matrix[$y][$x] = $dark;
                $bitIndex++;
            }
        }

        $upward = !$upward;
    }
}

function csf_qr_set_function(array &$matrix, array &$function, int $x, int $y, bool $dark): void
{
    $matrix[$y][$x] = $dark;
    $function[$y][$x] = true;
}

function csf_qr_get_bit(int $value, int $index): bool
{
    return (($value >> $index) & 1) !== 0;
}

function csf_qr_bch_remainder(int $value, int $poly, int $degree): int
{
    $value <<= $degree;

    for ($i = csf_qr_bit_length($value) - 1; $i >= $degree; $i--) {
        if ((($value >> $i) & 1) !== 0) {
            $value ^= $poly << ($i - $degree);
        }
    }

    return $value & ((1 << $degree) - 1);
}

function csf_qr_bit_length(int $value): int
{
    $length = 0;
    while ($value > 0) {
        $length++;
        $value >>= 1;
    }

    return $length;
}

function csf_qr_rs_remainder(array $data, int $degree): array
{
    $divisor = csf_qr_rs_divisor($degree);
    $result = array_fill(0, $degree, 0);

    foreach ($data as $byte) {
        $factor = $byte ^ $result[0];
        array_shift($result);
        $result[] = 0;
        for ($i = 0; $i < $degree; $i++) {
            $result[$i] ^= csf_qr_gf_multiply($divisor[$i], $factor);
        }
    }

    return $result;
}

function csf_qr_rs_divisor(int $degree): array
{
    $result = array_fill(0, $degree, 0);
    $result[$degree - 1] = 1;
    $root = 1;

    for ($i = 0; $i < $degree; $i++) {
        for ($j = 0; $j < $degree; $j++) {
            $result[$j] = csf_qr_gf_multiply($result[$j], $root);
            if ($j + 1 < $degree) {
                $result[$j] ^= $result[$j + 1];
            }
        }
        $root = csf_qr_gf_multiply($root, 0x02);
    }

    return $result;
}

function csf_qr_gf_multiply(int $x, int $y): int
{
    static $exp = null;
    static $log = null;

    if ($exp === null || $log === null) {
        $exp = array_fill(0, 512, 0);
        $log = array_fill(0, 256, 0);
        $value = 1;
        for ($i = 0; $i < 255; $i++) {
            $exp[$i] = $value;
            $log[$value] = $i;
            $value <<= 1;
            if (($value & 0x100) !== 0) {
                $value ^= 0x11D;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            $exp[$i] = $exp[$i - 255];
        }
    }

    if ($x === 0 || $y === 0) {
        return 0;
    }

    return $exp[$log[$x] + $log[$y]];
}
