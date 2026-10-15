<?php

/**
 * ID / competence cards: CR80 (85.6 × 54 mm) plastic card shown on site, issued either to a TUV EAST inspector
 * (holder_type inspector, linked to the user account) or to an assessed client worker — rigger, scaffolding
 * erector, operator … (holder_type worker, no login).
 * Front = photo / name / Iqama / company / ID code / dates; back = job ref, certification, competence and a QR
 * that opens the public verification page (/verify/card/{token}).
 * Life cycle: created (printed, "pending") → handed over (valid; inspectors also see it in their portal)
 * → expired / revoked. Handing over a card revokes the holder's previous active card (same account, or same Iqama).
 */
final class InspectorCardService
{
    public const W_MM = 85.6;
    public const H_MM = 54;

    public static function nextCardNo(): string
    {
        $y = date('Y');
        return sprintf('TE-IC-%s-%04d', $y, Numbering::next('id_card', $y));
    }

    /** Create or edit a card. A new card starts as pending until it is physically handed over. */
    public static function save(?int $id, array $in, ?array $photo): int
    {
        $worker = ($in['holder_type'] ?? 'inspector') === 'worker';
        $user = null;
        $clientId = null;
        if ($worker) {
            if (trim((string) ($in['id_number'] ?? '')) === '') throw new DomainException('Enter the worker\'s Iqama / ID number.');
            if (!empty($in['client_id'])) {
                $clientId = (int) DB::value('SELECT id FROM clients WHERE id = ?', [(int) $in['client_id']]) ?: null;
            }
        } else {
            $user = DB::one("SELECT id, role FROM users WHERE id = ? AND role IN ('inspector','qc','admin')", [(int) ($in['user_id'] ?? 0)]);
            if (!$user) throw new DomainException('Select the inspector the card is issued to.');
        }
        if ($in['expiry_date'] < $in['issue_date']) throw new DomainException('The expiry date must be after the issue date.');
        $cardNo = strtoupper(trim((string) ($in['card_no'] ?? '')));
        if ($cardNo !== '' && DB::value('SELECT id FROM inspector_cards WHERE card_no = ? AND id <> ?', [$cardNo, $id ?? 0])) {
            throw new DomainException("ID code $cardNo is already used by another card.");
        }
        $data = [
            'holder_type' => $worker ? 'worker' : 'inspector',
            'user_id' => $user ? (int) $user['id'] : null,
            'client_id' => $clientId,
            'holder_name' => mb_strtoupper(trim($in['holder_name'])),
            'id_number' => nullable($in['id_number'] ?? null),
            'company' => mb_strtoupper(trim($in['company'])),
            'title' => mb_strtoupper(trim($in['title'])),
            'job_ref' => nullable($in['job_ref'] ?? null),
            'certification' => nullable($in['certification'] ?? null),
            'assessor' => nullable($in['assessor'] ?? null),
            'competence' => nullable($in['competence'] ?? null),
            'issue_date' => $in['issue_date'],
            'expiry_date' => $in['expiry_date'],
        ];
        if ($photo) {                                       // admin upload also becomes the inspector's profile photo
            $data['photo_path'] = $photo['path'];
            if ($user) self::setProfilePhoto($data['user_id'], $photo['path']);
        } elseif (!$id || (!empty($in['use_profile_photo']) && $user)) {
            // inspectors: the photo on their profile; replacement cards: the photo of the card being replaced
            $data['photo_path'] = $user ? self::profilePhoto($data['user_id']) : null;
            if (!$data['photo_path'] && !$id && !empty($in['renew_from'])) {
                $data['photo_path'] = $user
                    ? DB::value('SELECT photo_path FROM inspector_cards WHERE id = ? AND user_id = ?', [(int) $in['renew_from'], $data['user_id']])
                    : DB::value("SELECT photo_path FROM inspector_cards WHERE id = ? AND holder_type = 'worker'", [(int) $in['renew_from']]);
            }
            if (!$data['photo_path']) unset($data['photo_path']);
        }

        return DB::tx(function () use ($id, $data, $cardNo) {
            if ($id) {
                if ($cardNo !== '') $data['card_no'] = $cardNo;
                DB::update('inspector_cards', $data, 'id = ?', [$id]);
                Audit::log('id_card.updated', 'inspector_card', $id, ['card_no' => $cardNo]);
                return $id;
            }
            $data['card_no'] = $cardNo !== '' ? $cardNo : self::nextCardNo();
            $data['verify_token'] = bin2hex(random_bytes(16));
            $data['created_by'] = Auth::id();
            $newId = DB::insert('inspector_cards', $data);
            Audit::log('id_card.created', 'inspector_card', $newId, ['card_no' => $data['card_no'], 'holder' => $data['holder_type'], 'user_id' => $data['user_id']]);
            return $newId;
        });
    }

    /** The printed card was given to its holder: it becomes valid (and appears in the inspector portal). */
    public static function handOver(int $id): void
    {
        $card = self::find('c.id = ?', [$id]);
        if (!$card || $card['status'] !== 'active') throw new DomainException('Only an active card can be handed over.');
        if ($card['handed_over_at']) throw new DomainException('This card was already handed over on ' . fmt_datetime($card['handed_over_at']) . '.');
        DB::tx(function () use ($card) {
            DB::update('inspector_cards', ['handed_over_at' => now(), 'handed_over_by' => Auth::id()], 'id = ?', [$card['id']]);
            [$same, $p] = self::sameHolder($card);
            DB::run(
                "UPDATE inspector_cards SET status = 'revoked', revoked_reason = ? WHERE $same AND status = 'active' AND id <> ?",
                array_merge(['Replaced by card ' . $card['card_no']], $p, [$card['id']])
            );
            Audit::log('id_card.handed_over', 'inspector_card', (int) $card['id'], ['card_no' => $card['card_no'], 'user_id' => $card['user_id']]);
            if ($card['user_id']) {
                Notify::user((int) $card['user_id'], 'ID card issued', 'Your inspector ID card ' . $card['card_no'] . ' has been issued to you.', 'inspector/card');
            }
        });
    }

    /** SQL condition (+ params) matching every card of the same person: same account, or for workers the same Iqama. */
    public static function sameHolder(array $card): array
    {
        if ($card['user_id']) return ['user_id = ?', [$card['user_id']]];
        if ($card['id_number']) return ["holder_type = 'worker' AND id_number = ?", [$card['id_number']]];
        return ['id = ?', [$card['id']]];
    }

    public static function profilePhoto(int $userId): ?string
    {
        return DB::value('SELECT photo_path FROM inspector_profiles WHERE user_id = ?', [$userId]) ?: null;
    }

    public static function setProfilePhoto(int $userId, string $path): void
    {
        DB::run('INSERT INTO inspector_profiles (user_id, photo_path) VALUES (?, ?) ON DUPLICATE KEY UPDATE photo_path = VALUES(photo_path)', [$userId, $path]);
    }

    public static function revoke(int $id, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '') throw new DomainException('Give a reason for revoking the card.');
        $n = DB::update('inspector_cards', ['status' => 'revoked', 'revoked_reason' => mb_substr($reason, 0, 255)], "id = ? AND status = 'active'", [$id]);
        if (!$n) throw new DomainException('This card is already revoked.');
        Audit::log('id_card.revoked', 'inspector_card', $id, ['reason' => $reason]);
    }

    /** pending (not handed over yet) | valid | expired | revoked | inactive (holder's account deactivated). */
    public static function state(array $card): string
    {
        if ($card['status'] === 'revoked') return 'revoked';
        if (!empty($card['user_id']) && ($card['user_status'] ?? 'active') !== 'active') return 'inactive';
        if (empty($card['handed_over_at'])) return 'pending';
        if ($card['expiry_date'] < date('Y-m-d')) return 'expired';
        return 'valid';
    }

    public static function find(string $where, array $p): ?array
    {
        return DB::one("SELECT c.*, u.status user_status, u.email user_email, u.role user_role, cl.name client_name
                        FROM inspector_cards c LEFT JOIN users u ON u.id = c.user_id LEFT JOIN clients cl ON cl.id = c.client_id
                        WHERE $where LIMIT 1", $p);
    }

    /** Badge for a card state (admin screens). */
    public static function badge(string $state): string
    {
        return [
            'valid' => badge('active', 'Valid'), 'pending' => badge('reserved', 'Awaiting hand-over'), 'expired' => badge('draft', 'Expired'),
            'revoked' => badge('revoked'), 'inactive' => badge('inactive', 'Holder inactive'),
        ][$state];
    }

    /** Look a card up from a scanned QR (full verification URL), a bare token, or the printed ID code. */
    public static function lookup(string $q): ?array
    {
        $q = trim($q);
        if ($q === '') return null;
        if (preg_match('#([a-f0-9]{32})/?(?:[?\#].*)?$#i', $q, $m)) {
            $card = self::find('c.verify_token = ?', [strtolower($m[1])]);
            if ($card) return $card;
        }
        return self::find('c.card_no = ?', [strtoupper($q)]);
    }

    /** Everything the card faces need, with images as data URIs so browser and PDF render the same. */
    public static function model(array $card): array
    {
        $verifyUrl = abs_url('verify/card/' . $card['verify_token']);
        return [
            'card' => $card,
            'photo' => upload_data_uri($card['photo_path']),
            'qr' => DocumentService::qr($verifyUrl),
            'verify_url' => $verifyUrl,
            'logo' => self::brandCrop('tuv'),
            'logos' => setting('card_logos_path') ? [upload_data_uri(setting('card_logos_path'))] : self::accreditationLogos(),
            'emails' => setting('card_emails', 'info@tuv-east.com - operations@tuv-east.com'),
            'address' => setting('card_address', 'Main Office:  Eastern Province - Dhahran, King Abdulaziz Rd – Al Waleedi Business Tower, Saudi Arabia'),
            'state' => self::state($card),
        ];
    }

    /** Default marks under the photo, as on the printed cards: UAF + LEEA Full Member (Saudi Accreditation as fallback). */
    private static function accreditationLogos(): array
    {
        $img = fn ($f) => is_file(ROOT_PATH . '/assets/img/' . $f)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents(ROOT_PATH . '/assets/img/' . $f)) : null;
        $uaf = $img('sticker-uaf.png');
        $leea = $img('sticker-leea.png');
        return $uaf && $leea ? [$uaf, $leea] : array_filter([self::brandCrop('accreditation')]);
    }

    /** The TUV EAST mark and the Saudi Accreditation mark are both in assets/img/logo.png — cut them apart once. */
    private static function brandCrop(string $part): ?string
    {
        static $cache = [];
        if (array_key_exists($part, $cache)) return $cache[$part];
        $src = ROOT_PATH . '/assets/img/logo.png';
        if (!function_exists('imagecreatefrompng') || !is_file($src)) return $cache[$part] = null;
        $im = imagecreatefrompng($src);
        $rect = $part === 'tuv' ? ['x' => 0, 'y' => 40, 'width' => 440, 'height' => 210] : ['x' => 455, 'y' => 0, 'width' => 325, 'height' => 250];
        $crop = imagecrop($im, $rect);
        imagesavealpha($crop, true);
        ob_start();
        imagepng($crop);
        $png = ob_get_clean();
        return $cache[$part] = 'data:image/png;base64,' . base64_encode($png);
    }

    /**
     * Fit the "Certificate of Competence" lines into the box on the back: the largest font (5.4 → 3 pt) at which
     * every line shows without wrapping, in one column — or two columns when that allows a clearly larger font.
     * Only if nothing fits unwrapped at 3 pt are long lines allowed to wrap.
     * Returns ['pt' => float, 'cols' => [['left' => mm, 'width' => mm, 'lines' => string[]], …]].
     * Mirrored in JS for the live preview on the admin card form (keep both in sync).
     */
    public static function fitCompetence(?string $text): array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', mb_strtoupper((string) $text))), 'strlen'));
        $height = 15.4;                                              // mm below the box title
        $layouts = [[['left' => 2.2, 'width' => 56.6, 'lines' => $lines]]];
        if (count($lines) > 1) {
            [$a, $b] = array_chunk($lines, (int) ceil(count($lines) / 2));
            $layouts[] = [['left' => 2.2, 'width' => 27.3, 'lines' => $a], ['left' => 31.5, 'width' => 27.3, 'lines' => $b]];
        }
        // rows a column needs at $pt (capitals ≈ 0.64 em wide), or null when $wrap is off and a line is too long
        $rows = function (array $col, float $pt, bool $wrap): ?int {
            $perLine = max(1, (int) floor($col['width'] / ($pt * 0.3528 * 0.64)));
            $n = 0;
            foreach ($col['lines'] as $l) {
                if (!$wrap && mb_strlen($l) > $perLine) return null;
                $n += (int) ceil(mb_strlen($l) / $perLine);
            }
            return $n;
        };
        foreach ([false, true] as $wrap) {
            $best = null;
            foreach ($layouts as $cols) {
                for ($pt = 5.4; $pt >= 3.0; $pt = round($pt - 0.1, 1)) {
                    $need = array_map(fn ($c) => $rows($c, $pt, $wrap), $cols);
                    if (!in_array(null, $need, true) && max($need ?: [0]) * $pt * 0.3528 * 1.3 <= $height) break;
                }
                if ($pt < 3.0) continue;
                if (!$best || $pt >= $best['pt'] + 0.5) $best = ['pt' => $pt, 'cols' => $cols];
            }
            if ($best) break;
        }
        $best ??= ['pt' => 3.0, 'cols' => end($layouts)];
        // each line gets its own top (mm): Dompdf does not honour line-height for these tiny absolutely-placed blocks
        $lh = $best['pt'] * 0.3528 * 1.3;
        foreach ($best['cols'] as &$col) {
            $row = 0;
            $col['tops'] = [];
            foreach ($col['lines'] as $l) {
                $col['tops'][] = round(5.2 + $row * $lh, 2);
                $row += $rows(['width' => $col['width'], 'lines' => [$l]], $best['pt'], true);
            }
        }
        unset($col);
        return $best;
    }

    /** Iqama shown on the public page with the middle digits hidden. */
    public static function maskId(?string $id): string
    {
        $id = (string) $id;
        $n = strlen($id);
        return $n <= 4 ? $id : substr($id, 0, 2) . str_repeat('•', $n - 4) . substr($id, -2);
    }
}
