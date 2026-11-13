<?php
declare(strict_types=1);
/* API de "Mon standard" — tourne sur votre hébergement PHP.
   Identifiants StandardFacile : uniquement dans .env (jamais envoyés au navigateur, jamais journalisés). */

const SFBASE = 'https://www.monstandardfacile.com/';
const MAX_ATTEMPTS = 5;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; frame-ancestors 'none'");

function out(int $code, array $o): never { http_response_code($code); echo json_encode($o, JSON_UNESCAPED_UNICODE); exit; }

function env(): array {
    static $e = null; if ($e !== null) return $e;
    $e = []; $f = __DIR__ . '/.env';
    if (!is_file($f)) return $e;
    foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
        if ($l[0] === '#' || !str_contains($l, '=')) continue;
        [$k, $v] = explode('=', $l, 2); $v = trim($v);
        if (strlen($v) >= 2 && $v[0] === $v[-1] && ($v[0] === '"' || $v[0] === "'")) $v = substr($v, 1, -1);
        $e[trim($k)] = $v;
    }
    return $e;
}

$path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
session_name('pcstd');
session_set_cookie_params(['lifetime' => 43200, 'path' => $path, 'secure' => !empty($_SERVER['HTTPS']), 'httponly' => true, 'samesite' => 'Strict']);
session_start();

$cfg = env();
if (($cfg['SF_NUMERO'] ?? '') === '' || ($cfg['SF_PASSWORD'] ?? '') === '' || ($cfg['APP_PASSWORD_HASH'] ?? '') === '') out(503, ['error' => 'Configuration incomplète : renseignez le fichier .env (voir LISEZ-MOI).']);

$route = $_GET['r'] ?? '';
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($isPost) {
    $o = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($o !== '' && parse_url($o, PHP_URL_HOST) !== parse_url('http://' . $_SERVER['HTTP_HOST'], PHP_URL_HOST)) out(403, ['error' => 'Origine refusée.']);
}
$in = $isPost ? (json_decode(file_get_contents('php://input') ?: '{}', true) ?: []) : [];

/* ---------- petit stockage (historique, essais de connexion) ---------- */
function store(string $name, ?array $set = null): array {
    $f = __DIR__ . "/data/$name.json";
    if ($set !== null) { file_put_contents($f, json_encode($set, JSON_UNESCAPED_UNICODE), LOCK_EX); @chmod($f, 0600); return $set; }
    return is_file($f) ? (json_decode(file_get_contents($f), true) ?: []) : [];
}
function addHistory(array $e): void { store('history', array_slice(array_merge([$e], store('history')), 0, 200)); }

/* ---------- authentification de l'application ---------- */
if ($route === 'login' && $isPost) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '?'; $all = store('attempts'); $now = time();
    $mine = array_values(array_filter($all[$ip] ?? [], fn($t) => $t > $now - 900));
    $global = array_values(array_filter($all['*'] ?? [], fn($t) => $t > $now - 900));   // plafond tous visiteurs confondus
    if (count($mine) >= MAX_ATTEMPTS || count($global) >= 20) out(429, ['error' => 'Trop d\'essais. Réessayez dans 15 minutes.']);
    $pw = $in['password'] ?? '';
    if (!is_string($pw) || !password_verify($pw, $cfg['APP_PASSWORD_HASH'])) {
        $mine[] = $now; $all[$ip] = $mine; $global[] = $now; $all['*'] = $global; store('attempts', $all);
        usleep(400000); out(401, ['error' => 'Mot de passe incorrect.']);
    }
    session_regenerate_id(true); $_SESSION['ok'] = true; $_SESSION['until'] = $now + 43200;
    out(200, ['ok' => true]);
}
$authed = !empty($_SESSION['ok']) && ($_SESSION['until'] ?? 0) > time();
if ($route === 'ping') out(200, ['live' => true, 'authed' => $authed]);
if (!$authed) out(401, ['error' => 'Connexion requise.']);
if ($route === 'logout') { session_destroy(); out(200, ['ok' => true]); }
if ($route === 'history') out(200, store('history'));

/* ---------- client StandardFacile ---------- */
final class SF {
    private array $cookies;
    public function __construct(private string $num, private string $pw) { $this->cookies = $_SESSION['sf'] ?? []; }
    private function req(string $path, ?array $body = null): string {
        $hdr = ['X-Requested-With: XMLHttpRequest', 'Referer: ' . SFBASE, 'Origin: https://www.monstandardfacile.com',
                'Cookie: ' . implode('; ', array_map(fn($k, $v) => "$k=$v", array_keys($this->cookies), $this->cookies))];
        $ch = curl_init(SFBASE . $path);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (PizzaClub standard)',
            CURLOPT_HEADERFUNCTION => function ($c, $h) {
                if (stripos($h, 'set-cookie:') === 0) { $kv = trim(explode(';', substr($h, 11))[0]); $p = strpos($kv, '='); if ($p) $this->cookies[substr($kv, 0, $p)] = substr($kv, $p + 1); }
                return strlen($h);
            }]);
        if ($body !== null) {
            $hdr[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['typeofchargement' => 'ajax'] + $body));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $hdr);
        $r = curl_exec($ch);
        if ($r === false) { $m = curl_error($ch); curl_close($ch); throw new RuntimeException('StandardFacile injoignable : ' . $m); }
        curl_close($ch); $_SESSION['sf'] = $this->cookies;
        return (string)$r;
    }
    public function login(): void {
        $this->cookies = []; $this->req('');
        $this->req('', ['numero_connexion' => $this->num, 'password_connexion' => $this->pw]);
        if (!str_contains($this->req(''), 'id="deconnexion"'))
            throw new RuntimeException('Connexion refusée par StandardFacile (identifiants, ou vérification supplémentaire demandée). Aucune tentative de contournement.');
    }
    private function page(string $path, array $body = []): string {
        if (!$this->cookies) $this->login();
        $h = $this->req($path, $body);
        if ($h === '' || str_contains($h, 'id="btn_connexion"')) { $this->login(); $h = $this->req($path, $body); }
        return $h;
    }
    private static function dom(string $html): DOMXPath {
        $d = new DOMDocument(); libxml_use_internal_errors(true);
        $d->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>'); libxml_clear_errors();
        return new DOMXPath($d);
    }
    private static function cls(string $c): string { return "contains(concat(' ',normalize-space(@class),' '),' $c ')"; }

    public function status(): string {
        if (!$this->cookies) $this->login();
        $x = self::dom($this->req('')); $n = $x->query('//*[@id="status"]/@class')->item(0);
        $c = $n ? $n->nodeValue : ''; return str_contains($c, 'pause') ? 'pause' : (str_contains($c, 'open') ? 'open' : 'close');
    }
    public function phones(): array {
        $x = self::dom($this->page('configuration/telephones/', ['affiche' => '1', 'profil' => '1'])); $r = [];
        foreach ($x->query('//li[@data-id]') as $li) {
            if ($li->getAttribute('data-new') === '1') continue;
            $i = $x->query('.//input[@data-typedonnee="phone"]', $li)->item(0);
            $r[] = ['id' => $li->getAttribute('data-id'), 'value' => $i ? trim($i->getAttribute('value')) : ''];
        }
        return $r;
    }
    public function planning(): array {
        // Le site stocke le planning dans des champs cachés inputPlan1..7 : "début-fin-actif-profil|" (minutes depuis minuit).
        $x = self::dom($this->page('configuration/planning/')); $days = [];
        for ($d = 1; $d <= 7; $d++) {
            $n = $x->query("//*[@id='inputPlan$d']")->item(0);
            if (!$n) throw new RuntimeException('Lecture du planning impossible : champ du jour ' . $d . ' introuvable.');
            $ranges = []; $prof = []; $flags = [];
            foreach (array_filter(explode('|', trim($n->getAttribute('value')))) as $seg) {
                if (!preg_match('/^(\d+)-(\d+)-(\d+)-(\d+)$/', $seg, $m)) throw new RuntimeException('Lecture du planning impossible (format inattendu).');
                $ranges[] = [(int)$m[1], (int)$m[2]]; $flags[] = (int)$m[3]; $prof[] = (int)$m[4];
            }
            $days[$d] = ['on' => count($ranges) > 0 && !in_array(0, $flags, true), 'ranges' => $ranges, 'prof' => $prof];
        }
        return $days;
    }
    public function announce(string $page): array {
        $x = self::dom($this->page("configuration/$page/"));
        $el = fn(string $id) => $x->query("//*[@id='$id']")->item(0);
        $val = function (string $id) use ($el, $x): string {
            $e = $el($id); if (!$e) return '';
            if ($e->hasAttribute('value')) return $e->getAttribute('value');
            $o = $x->query('.//option[@selected]', $e)->item(0) ?? $x->query('.//option', $e)->item(0);
            return $o ? $o->getAttribute('value') : '';
        };
        $gl = function (string $id) use ($el) { $e = $el($id); return $e ? (string)((int)$e->getAttribute('data-value') + (int)$e->getAttribute('data-diff')) : ''; };
        $t = $x->query('//*[@data-typeannonce]/@data-typeannonce')->item(0);
        // Le site lit ces réglages avec innerHTML (ddh) ou .value (ddv) : on fait pareil.
        $f = []; foreach (['profil','is_texte','is_musique','type_texte','type_musique','choix_musique'] as $k) { $e = $el($k); $f[$k] = $e ? trim($e->textContent) : ''; }
        foreach (['extension','speakrine'] as $k) $f[$k] = $val($k);
        $g = []; foreach (['param_mixvol','param_debut','param_timeout','synthese_volume','synthese_debit','synthese_timbre'] as $k) $g[$k] = $gl($k);
        return ['text' => $el('synthese_texte')?->textContent ?? '', 'type' => $t ? $t->nodeValue : '', 'f' => $f, 'g' => $g];
    }
    private static function refused(string $r, string $what): void { if (str_starts_with($r, '0|')) throw new RuntimeException("StandardFacile a refusé $what : " . mb_substr(substr($r, 2), 0, 140)); }
    public function setPhone(string $id, string $value): void {
        self::refused($this->page('configuration/telephones/', ['change_redirection' => '1', 'typedonnee' => 'phone', 'value' => $value, 'id' => $id, 'profil' => '1']), 'le numéro');
    }
    public function setPlanning(array $days): void {
        $cur = $this->planning(); $b = ['save_planning' => '1'];
        for ($d = 1; $d <= 7; $d++) {
            $on = $days[$d]['on']; $parts = [];
            // jour fermé : les créneaux sont conservés avec le drapeau 0 (comme l'interrupteur du site).
            $ranges = $days[$d]['ranges'];
            foreach ($ranges as $i => $r) $parts[] = "{$r[0]}-{$r[1]}-" . ($on ? 1 : 0) . '-' . ($cur[$d]['prof'][$i] ?? 1) . '|';
            $b["planning$d"] = implode('', $parts);
        }
        self::refused($this->page('configuration/planning/', $b), 'le planning');
    }
    public function setAnnounce(string $page, string $text): void {
        $a = $this->announce($page);
        if ($a['type'] === '') throw new RuntimeException('Type d\'annonce introuvable sur la page : écriture annulée.');
        if ($a['f']['is_texte'] !== '1' || $a['f']['type_texte'] !== 'synthese') throw new RuntimeException('Cette annonce n\'est pas en mode « texte lu par synthèse » : écriture annulée pour ne rien écraser.');
        $c = ['type' => $a['type'], 'profil' => $a['f']['profil'], 'extension' => $a['f']['extension'], 'is_texte' => '1', 'is_musique' => $a['f']['is_musique'],
              'type_texte' => 'synthese', 'texte' => $text, 'speakrine' => $a['f']['speakrine'], 'volume' => $a['g']['synthese_volume'], 'debit' => $a['g']['synthese_debit'], 'timbre' => $a['g']['synthese_timbre']];
        if ($a['f']['is_musique'] === '1') $c += ['type_musique' => $a['f']['type_musique'], 'choix_musique' => $a['f']['choix_musique']];
        if ($a['type'] === 'ACCUEIL') $c += ['mixvol' => $a['g']['param_mixvol'], 'debut' => $a['g']['param_debut'], 'timeout' => $a['g']['param_timeout']];
        self::refused($this->page('configuration/message-accueil/', ['creer_annonce' => '1'] + $c), 'la création de l\'annonce');
        self::refused($this->page('configuration/message-accueil/', ['enregistrer_annonce' => '1'] + $c), 'l\'enregistrement de l\'annonce');
    }
}

/* ---------- conversions ---------- */
function hm(int $m): string { return sprintf('%02d:%02d', intdiv($m, 60), $m % 60); }
function tmin(string $t): int { [$h, $m] = array_map('intval', explode(':', $t)); return $h * 60 + $m; }
function fromSF(array $days): array { $o = []; foreach ([0,1,2,3,4,5,6] as $d) { $x = $days[$d === 0 ? 7 : $d]; $o[$d] = array_map(fn($r) => [hm($r[0]), hm($r[1])], $x['ranges']); } return $o; }
function offFromSF(array $days): array { $o = []; foreach ([0,1,2,3,4,5,6] as $d) $o[$d] = !$days[$d === 0 ? 7 : $d]['on']; return $o; }
function toSF(array $h, array $off): array { $o = []; foreach ([1,2,3,4,5,6,7] as $d) { $k = $d === 7 ? 0 : $d; $r = array_map(fn($p) => [tmin($p[0]), tmin($p[1])], $h[$k] ?? []); $o[$d] = ['on' => count($r) > 0 && empty($off[$k]), 'ranges' => $r]; } return $o; }
function norm(string $s): string { return trim(preg_replace('/\s+/u', ' ', $s)); }
function digits(string $s): string { return preg_replace('/\D/', '', $s); }

/* ---------- actions (une seule à la fois) ---------- */
$lock = fopen(__DIR__ . '/data/.lock', 'c'); flock($lock, LOCK_EX);
$sf = new SF($cfg['SF_NUMERO'], $cfg['SF_PASSWORD']);
$line = (int)($cfg['SF_LINE_INDEX'] ?? 0);
try {
    if ($route === 'state') {
        $p = $sf->phones()[$line] ?? throw new RuntimeException('Ligne de téléphone introuvable.');
        try { $st = $sf->status(); } catch (Throwable) { $st = ''; }
        out(200, ['live' => true, 'forward' => $p['value'], 'forwardId' => $p['id'], 'greeting' => norm($sf->announce('message-accueil')['text']),
                  'closed' => norm($sf->announce('message-repondeur')['text']), $pl = null, 'hours' => fromSF($pl = $sf->planning()), 'off' => offFromSF($pl), 'status' => $st]);
    }
    if ($route === 'apply' && $isPost) {
        $key = $in['key'] ?? ''; $value = $in['value'] ?? null;
        try {
            if ($key === 'forward') {
                $label = 'Numéro de transfert'; if (!is_string($value) || !preg_match('/^(\+|00)?\d{9,15}$/', preg_replace('/[\s.\-()]/', '', $value))) throw new RuntimeException('Numéro invalide.');
                $p = $sf->phones()[$line] ?? throw new RuntimeException('Ligne de téléphone introuvable.'); $before = $p['value'];
                $sf->setPhone($p['id'], $value); $after = $sf->phones()[$line]['value'] ?? '';
                if (digits($after) !== digits($value)) throw new RuntimeException("Relecture différente : StandardFacile affiche « $after ».");
            } elseif ($key === 'greeting' || $key === 'closed') {
                $label = $key === 'greeting' ? 'Annonce d\'accueil' : 'Annonce de fermeture'; $page = $key === 'greeting' ? 'message-accueil' : 'message-repondeur';
                if (!is_string($value) || norm($value) === '' || mb_strlen($value) > 1500) throw new RuntimeException('Texte vide ou trop long.');
                $before = norm($sf->announce($page)['text']); $sf->setAnnounce($page, $value); $after = norm($sf->announce($page)['text']);
                if ($after !== norm($value)) throw new RuntimeException('Relecture différente : le texte enregistré chez StandardFacile n\'est pas celui envoyé.');
            } elseif ($key === 'hours') {
                $label = 'Horaires d\'ouverture'; $h = $value['hours'] ?? null;
                if (!is_array($h)) throw new RuntimeException('Horaires invalides.');
                $off = is_array($value['off'] ?? null) ? $value['off'] : [];
                $pl = $sf->planning(); $before = ['hours' => fromSF($pl), 'off' => offFromSF($pl)];
                $sf->setPlanning(toSF($h, $off)); $pl = $sf->planning(); $after = ['hours' => fromSF($pl), 'off' => offFromSF($pl)];
                $want = toSF($h, $off);
                if (json_encode($after) !== json_encode(['hours' => fromSF($want), 'off' => offFromSF($want)])) throw new RuntimeException('Relecture différente : le planning chez StandardFacile n\'est pas celui envoyé.');
            } else throw new RuntimeException('Réglage inconnu.');
            addHistory(['at' => time() * 1000, 'label' => $label, 'status' => 'applied', 'before' => $before, 'after' => $after]);
            out(200, ['ok' => true, 'verified' => true, 'label' => $label]);
        } catch (RuntimeException $e) {
            addHistory(['at' => time() * 1000, 'label' => (string)$key, 'status' => 'failed', 'error' => $e->getMessage()]);
            out(502, ['ok' => false, 'error' => $e->getMessage()]);
        }
    }
    out(404, ['error' => 'Introuvable.']);
} catch (RuntimeException $e) { out(502, ['error' => $e->getMessage()]); }
catch (Throwable) { out(500, ['error' => 'Erreur serveur.']); }
