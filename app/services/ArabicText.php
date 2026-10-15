<?php

/**
 * Arabic text for printed documents. Dompdf (and a plain LTR box) cannot join Arabic letters or reorder
 * right-to-left text, so the text is shaped here into Arabic Presentation Forms and put in visual order.
 * Output is meant to be shown left-to-right: html() wraps it in a bidi-override span so browsers and Dompdf
 * both draw it exactly as laid out. Static labels and user-entered Arabic (owner, address ...) go through the same code.
 */
final class ArabicText
{
    /** base letter => [isolated, final, initial, medial] (generated from the Unicode presentation-form tables) */
    private const FORMS = [
        0x0621 => [0xFE80, null, null, null], 0x0622 => [0xFE81, 0xFE82, null, null], 0x0623 => [0xFE83, 0xFE84, null, null],
        0x0624 => [0xFE85, 0xFE86, null, null], 0x0625 => [0xFE87, 0xFE88, null, null], 0x0626 => [0xFE89, 0xFE8A, 0xFE8B, 0xFE8C],
        0x0627 => [0xFE8D, 0xFE8E, null, null], 0x0628 => [0xFE8F, 0xFE90, 0xFE91, 0xFE92], 0x0629 => [0xFE93, 0xFE94, null, null],
        0x062A => [0xFE95, 0xFE96, 0xFE97, 0xFE98], 0x062B => [0xFE99, 0xFE9A, 0xFE9B, 0xFE9C], 0x062C => [0xFE9D, 0xFE9E, 0xFE9F, 0xFEA0],
        0x062D => [0xFEA1, 0xFEA2, 0xFEA3, 0xFEA4], 0x062E => [0xFEA5, 0xFEA6, 0xFEA7, 0xFEA8], 0x062F => [0xFEA9, 0xFEAA, null, null],
        0x0630 => [0xFEAB, 0xFEAC, null, null], 0x0631 => [0xFEAD, 0xFEAE, null, null], 0x0632 => [0xFEAF, 0xFEB0, null, null],
        0x0633 => [0xFEB1, 0xFEB2, 0xFEB3, 0xFEB4], 0x0634 => [0xFEB5, 0xFEB6, 0xFEB7, 0xFEB8], 0x0635 => [0xFEB9, 0xFEBA, 0xFEBB, 0xFEBC],
        0x0636 => [0xFEBD, 0xFEBE, 0xFEBF, 0xFEC0], 0x0637 => [0xFEC1, 0xFEC2, 0xFEC3, 0xFEC4], 0x0638 => [0xFEC5, 0xFEC6, 0xFEC7, 0xFEC8],
        0x0639 => [0xFEC9, 0xFECA, 0xFECB, 0xFECC], 0x063A => [0xFECD, 0xFECE, 0xFECF, 0xFED0], 0x0641 => [0xFED1, 0xFED2, 0xFED3, 0xFED4],
        0x0642 => [0xFED5, 0xFED6, 0xFED7, 0xFED8], 0x0643 => [0xFED9, 0xFEDA, 0xFEDB, 0xFEDC], 0x0644 => [0xFEDD, 0xFEDE, 0xFEDF, 0xFEE0],
        0x0645 => [0xFEE1, 0xFEE2, 0xFEE3, 0xFEE4], 0x0646 => [0xFEE5, 0xFEE6, 0xFEE7, 0xFEE8], 0x0647 => [0xFEE9, 0xFEEA, 0xFEEB, 0xFEEC],
        0x0648 => [0xFEED, 0xFEEE, null, null], 0x0649 => [0xFEEF, 0xFEF0, 0xFEF3, 0xFEF4], 0x064A => [0xFEF1, 0xFEF2, 0xFEF3, 0xFEF4],
    ];

    /** lam + alef variant => [isolated, final] ligature */
    private const LIG = [0x0622 => [0xFEF5, 0xFEF6], 0x0623 => [0xFEF7, 0xFEF8], 0x0625 => [0xFEF9, 0xFEFA], 0x0627 => [0xFEFB, 0xFEFC]];

    private const MIRROR = ['(' => ')', ')' => '(', '[' => ']', ']' => '[', '{' => '}', '}' => '{', '<' => '>', '>' => '<'];

    public static function has(string $s): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $s);
    }

    /** Logical (typed) text -> shaped glyphs in visual left-to-right order. Text without Arabic is returned unchanged. */
    public static function visual(string $s): string
    {
        if (!self::has($s)) return $s;
        $s = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{200E}\x{200F}\x{202A}-\x{202E}]/u', '', $s);
        $cps = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);

        // tokens: [char, type R|L|N] after shaping
        $tok = [];
        $n = count($cps);
        for ($i = 0; $i < $n; $i++) {
            $cp = mb_ord($cps[$i], 'UTF-8');
            if ($cp === 0x0644 && $i + 1 < $n && isset(self::LIG[mb_ord($cps[$i + 1], 'UTF-8')])) {
                $tok[] = ['cp' => 0x0644, 'lig' => mb_ord($cps[$i + 1], 'UTF-8'), 'ch' => null];
                $i++;
            } else {
                $tok[] = ['cp' => $cp, 'lig' => 0, 'ch' => $cps[$i]];
            }
        }
        $dual = fn ($t) => $t && !$t['lig'] && isset(self::FORMS[$t['cp']]) && self::FORMS[$t['cp']][2] !== null;
        $takes = fn ($t) => $t && ($t['lig'] || (isset(self::FORMS[$t['cp']]) && self::FORMS[$t['cp']][1] !== null) || $t['cp'] === 0x0640);
        $chars = [];
        foreach ($tok as $k => $t) {
            $prev = $tok[$k - 1] ?? null;
            $next = $tok[$k + 1] ?? null;
            $pj = $dual($prev) || ($prev && $prev['cp'] === 0x0640);
            $nj = $takes($next);
            if ($t['lig']) {
                $g = self::LIG[$t['lig']][$pj ? 1 : 0];
                $chars[] = [mb_chr($g, 'UTF-8'), 'R'];
            } elseif (isset(self::FORMS[$t['cp']])) {
                $f = self::FORMS[$t['cp']];
                if ($f[2] !== null) $g = $pj && $nj ? $f[3] : ($pj ? $f[1] : ($nj ? $f[2] : $f[0]));
                else $g = $pj && $f[1] !== null ? $f[1] : $f[0];
                $chars[] = [mb_chr($g, 'UTF-8'), 'R'];
            } elseif (($t['cp'] >= 0x0600 && $t['cp'] <= 0x06FF && !($t['cp'] >= 0x0660 && $t['cp'] <= 0x0669)) || $t['cp'] === 0x0640) {
                $chars[] = [$t['ch'], 'R'];                           // Arabic punctuation, tatweel, other letters
            } elseif (preg_match('/[A-Za-z0-9\x{0660}-\x{0669}]/u', $t['ch'])) {
                $chars[] = [$t['ch'], 'L'];
            } else {
                $chars[] = [$t['ch'], 'N'];
            }
        }

        // neutrals between two L chars stay L; every other neutral takes the right-to-left paragraph direction
        $m = count($chars);
        for ($i = 0; $i < $m; $i++) {
            if ($chars[$i][1] !== 'N') continue;
            $j = $i;
            while ($j < $m && $chars[$j][1] === 'N') $j++;
            $before = $i > 0 ? $chars[$i - 1][1] : 'R';
            $after = $j < $m ? $chars[$j][1] : 'R';
            $dir = ($before === 'L' && $after === 'L') ? 'L' : 'R';
            for ($k = $i; $k < $j; $k++) $chars[$k][1] = $dir;
            $i = $j - 1;
        }

        // runs of equal direction; run order reversed, R runs reversed internally
        $runs = [];
        foreach ($chars as [$ch, $d]) {
            if ($runs && end($runs)['d'] === $d) $runs[count($runs) - 1]['s'][] = $ch;
            else $runs[] = ['d' => $d, 's' => [$ch]];
        }
        $out = '';
        foreach (array_reverse($runs) as $r) {
            if ($r['d'] === 'R') {
                foreach (array_reverse($r['s']) as $ch) $out .= self::MIRROR[$ch] ?? $ch;
            } else {
                $out .= implode('', $r['s']);
            }
        }
        return $out;
    }

    /** Width in mm of the shaped text (DejaVu Sans, the font Dompdf draws Arabic with). */
    public static function widthMm(string $logical, float $pt, bool $bold): float
    {
        $font = ROOT_PATH . '/vendor/dompdf/dompdf/lib/fonts/DejaVuSans' . ($bold ? '-Bold' : '') . '.ttf';
        $v = self::visual($logical);
        if (function_exists('imagettfbbox') && is_file($font)) {
            $b = imagettfbbox($pt, 0, $font, $v);
            return ($b[2] - $b[0]) * 25.4 / 96 * 1.3;   // GD sizes in 72-dpi points: x1.3 matches what Dompdf draws (measured)
        }
        return mb_strlen($v) * $pt * 0.19;
    }

    /** Word-wrap logical text into lines no wider than $widthMm. Explicit newlines are kept. */
    public static function wrap(string $text, float $widthMm, float $pt, bool $bold): array
    {
        $lines = [];
        foreach (preg_split('/\R/u', trim($text)) as $para) {
            $cur = '';
            foreach (preg_split('/\s+/u', trim($para), -1, PREG_SPLIT_NO_EMPTY) as $w) {
                $try = $cur === '' ? $w : $cur . ' ' . $w;
                if ($cur !== '' && self::widthMm($try, $pt, $bold) > $widthMm) { $lines[] = $cur; $cur = $w; } else { $cur = $try; }
            }
            $lines[] = $cur;
        }
        return $lines;
    }

    /**
     * HTML for Arabic (or mixed) text: each wrapped line is shaped separately and wrapped in an LTR bidi-override span.
     * $widthMm = available text width, so long lines are broken the way a right-to-left reader expects.
     */
    public static function html(string $text, float $widthMm = 0, float $pt = 10, bool $bold = true): string
    {
        $text = trim($text);
        if ($text === '') return '';
        if (!self::has($text)) return e($text);
        $lines = $widthMm > 0 ? self::wrap($text, $widthMm * 0.97, $pt, $bold) : [$text];   // safety margin: Dompdf measures slightly wider than GD
        return implode('<br>', array_map(fn ($l) => '<span class="ar">' . e(self::visual($l)) . '</span>', $lines));
    }
}
