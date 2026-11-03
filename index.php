<?php
declare(strict_types=1);

const DATA_FILE = __DIR__ . '/data/lista-presente.presente';

function jsonResponse(int $status, array $body): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header('Cache-Control: no-store');
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}
function getList(): array {
    if (!file_exists(DATA_FILE)) return [];
    $list = json_decode((string) file_get_contents(DATA_FILE), true);
    return is_array($list) ? $list : [];
}
function saveList(array $list): void {
    if (!is_dir(dirname(DATA_FILE))) mkdir(dirname(DATA_FILE), 0700, true);
    file_put_contents(DATA_FILE, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    chmod(DATA_FILE, 0600);
}
function input(): array { return json_decode((string) file_get_contents('php://input'), true) ?: []; }
function clean(mixed $value, int $max): string { return mb_substr(trim((string) $value), 0, $max); }

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path === '/api/wishes') {
    $list = getList();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $public = array_map(fn($i) => ['id' => $i['id'], 'name' => $i['name'], 'wish' => $i['wish'], 'updatedAt' => $i['updatedAt']], $list);
        jsonResponse(200, $public);
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = input(); $name = clean($data['name'] ?? '', 60); $wish = clean($data['wish'] ?? '', 500); $password = (string)($data['password'] ?? '');
        if (!$name || !$wish || mb_strlen($password) < 4) jsonResponse(400, ['error' => 'Informe nome, desejo e uma senha de pelo menos 4 caracteres.']);
        foreach ($list as $item) if (mb_strtolower($item['name']) === mb_strtolower($name)) jsonResponse(409, ['error' => 'Este nome já existe. Use a opção de editar.']);
        $list[] = ['id' => bin2hex(random_bytes(16)), 'name' => $name, 'wish' => $wish, 'passwordHash' => password_hash($password, PASSWORD_DEFAULT), 'updatedAt' => gmdate('c')];
        saveList($list); jsonResponse(201, ['ok' => true]);
    }
}
if (preg_match('#^/api/wishes/([a-f0-9]{32})$#', (string)$path, $matches) && $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = input(); $list = getList();
    foreach ($list as &$item) {
        if ($item['id'] !== $matches[1]) continue;
        if (!password_verify((string)($data['password'] ?? ''), $item['passwordHash'])) jsonResponse(403, ['error' => 'Senha incorreta.']);
        $wish = clean($data['wish'] ?? '', 500);
        if (!$wish) jsonResponse(400, ['error' => 'Informe o que você gostaria de ganhar.']);
        $item['wish'] = $wish; $item['updatedAt'] = gmdate('c'); saveList($list); jsonResponse(200, ['ok' => true]);
    }
    jsonResponse(404, ['error' => 'Cadastro não encontrado.']);
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow,noarchive"><title>Amigo Secreto</title><link rel="stylesheet" href="/public/styles.css"></head>
<body><div class="snow snow-one">✦</div><div class="snow snow-two">✦</div><div class="snow snow-three">✦</div><main>
<header class="hero"><div class="garland" aria-hidden="true"><i>●</i><i>●</i><i>●</i><i>●</i><i>●</i><i>●</i><i>●</i></div><p class="eyebrow">✦ CELEBRAÇÃO EM FAMÍLIA ✦</p><h1>Amigo<br><em>Secreto</em></h1><p>Uma lista de desejos para deixar cada presente ainda mais especial.</p><div class="hero-mark" aria-hidden="true">🎁</div></header>
<section class="card wishes-card"><div class="section-heading"><div><p class="label">JÁ ESTÁ NA LISTA</p><h2>Desejos da família</h2></div><span class="mistletoe">❋</span></div><div id="list" class="wish-list" aria-live="polite"></div></section>
<section class="card form-card"><div class="section-heading"><div><p class="label">É A SUA VEZ</p><h2 id="form-title">Adicionar meu desejo</h2></div><span class="mistletoe">✷</span></div><p class="hint" id="form-hint">Escolha uma senha para poder alterar seu desejo depois.</p><form id="wish-form"><input type="hidden" id="id"><label>Seu nome<input id="name" maxlength="60" required autocomplete="name" placeholder="Ex.: Carol"></label><label>O que você gostaria de ganhar?<textarea id="wish" maxlength="500" required placeholder="Ex.: Um livro de ficção, tamanho..."></textarea></label><label>Sua senha<input id="password" type="password" minlength="4" required autocomplete="new-password" placeholder="Mínimo de 4 caracteres"></label><button type="submit">Salvar desejo</button><button type="button" id="cancel" class="secondary" hidden>Cancelar edição</button><p id="feedback" role="status"></p></form></section>
</main><script src="https://code.jquery.com/jquery-3.7.1.min.js"></script><script src="/public/app.js"></script></body></html>
