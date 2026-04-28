<?php
require_once __DIR__ . '/config.php';

$uri     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method  = $_SERVER['REQUEST_METHOD'];
$path    = trim(preg_replace('#^.*?/api#', '', $uri), '/');
$parts   = explode('/', $path);
$resource = $parts[0] ?? '';
$id       = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : null;

switch ($resource) {
  case 'config':
    if ($method === 'GET') { getFullConfig(); break; }
    break;
  case 'auth':
    $action = $parts[1] ?? '';
    if ($action === 'login'  && $method === 'POST') { handleLogin(); break; }
    if ($action === 'logout' && $method === 'POST') { handleLogout(); break; }
    break;
  case 'station':
    if ($method === 'GET') { getStation(); break; }
    if ($method === 'PUT') { requireAuth(); updateStation(); break; }
    break;
  case 'hero':
    if ($method === 'GET') { getHero(); break; }
    if ($method === 'PUT') { requireAuth(); updateHero(); break; }
    break;
  case 'colors':
    if ($method === 'GET') { getColors(); break; }
    if ($method === 'PUT') { requireAuth(); updateColors(); break; }
    break;
  case 'djs':
    if ($method === 'GET')    { getDJs(); break; }
    if ($method === 'POST')   { requireAuth(); createDJ(); break; }
    if ($method === 'PUT')    { requireAuth(); updateDJ($id); break; }
    if ($method === 'DELETE') { requireAuth(); deleteDJ($id); break; }
    break;
  case 'schedule':
    if ($method === 'GET')    { getSchedule(); break; }
    if ($method === 'POST')   { requireAuth(); createShow(); break; }
    if ($method === 'PUT')    { requireAuth(); updateShow($id); break; }
    if ($method === 'DELETE') { requireAuth(); deleteShow($id); break; }
    break;
  case 'downloads':
    if ($method === 'GET')    { getDownloads(); break; }
    if ($method === 'POST')   { requireAuth(); createDownload(); break; }
    if ($method === 'PUT')    { requireAuth(); updateDownload($id); break; }
    if ($method === 'DELETE') { requireAuth(); deleteDownload($id); break; }
    break;
  case 'socials':
    if ($method === 'GET')    { getSocials(); break; }
    if ($method === 'POST')   { requireAuth(); createSocial(); break; }
    if ($method === 'PUT')    { requireAuth(); updateSocial($id); break; }
    if ($method === 'DELETE') { requireAuth(); deleteSocial($id); break; }
    break;
  case 'flags':
    if ($method === 'GET')    { getFlags(); break; }
    if ($method === 'POST')   { requireAuth(); createFlag(); break; }
    if ($method === 'PUT')    { requireAuth(); updateFlag($id); break; }
    if ($method === 'DELETE') { requireAuth(); deleteFlag($id); break; }
    break;
  case 'upload':
    if ($method === 'POST') { requireAuth(); handleUpload(); break; }
    break;
  default:
    jsonResponse(['error' => 'Not found'], 404);
}

/* ══ FULL CONFIG ══════════════════════════════ */
function getFullConfig() {
  $db = getDB();
  $station   = $db->query('SELECT * FROM station LIMIT 1')->fetch();
  $hero      = $db->query('SELECT * FROM hero LIMIT 1')->fetch();
  $colors    = $db->query('SELECT * FROM colors LIMIT 1')->fetch();
  $djs       = $db->query('SELECT * FROM djs ORDER BY sort_order,id')->fetchAll();
  $schedule  = $db->query('SELECT * FROM schedule ORDER BY sort_order,id')->fetchAll();
  $downloads = $db->query('SELECT * FROM downloads ORDER BY sort_order,id')->fetchAll();
  $socials   = $db->query('SELECT * FROM socials ORDER BY sort_order,id')->fetchAll();
  $flags     = $db->query('SELECT * FROM flags ORDER BY sort_order,id')->fetchAll();

  jsonResponse([
    'station'   => $station   ? formatStation($station)                   : defaultStation(),
    'hero'      => $hero      ? formatHero($hero)                         : defaultHero(),
    'colors'    => $colors    ? formatColors($colors)                     : defaultColors(),
    'djs'       => array_map('formatDJ', $djs),
    'schedule'  => array_map('formatShow', $schedule),
    'downloads' => array_map('formatDownload', $downloads),
    'socials'   => array_map('formatSocial', $socials),
    'flags'     => array_map('formatFlag', $flags),
  ]);
}

/* ══ FORMAT FUNCTIONS ════════════════════════ */
function formatStation($r) {
  return [
    'name'        => $r['name']         ?? 'Caribbean Gospel Radio HD',
    'streamUrl'   => $r['stream_url']   ?? '',
    'fallbackUrl' => $r['fallback_url'] ?? '',
    'metadataUrl' => $r['metadata_url'] ?? '',
    'bitrate'     => $r['bitrate']      ?? '',
    'timezone'    => $r['timezone']     ?? 'America/New_York',
    'logo'        => $r['logo_data']    ?: ($r['logo_url'] ?? ''),
    'cboxId'      => $r['cbox_id']      ?? '',
    'cboxTag'     => $r['cbox_tag']     ?? '',
    'cboxEmbed'   => $r['cbox_embed']   ?? '',
  ];
}
function formatHero($r) {
  return [
    'type'           => $r['bg_type']          ?: 'gradient',
    'src'            => $r['bg_data']           ?: ($r['bg_src'] ?? ''),
    'overlayOpacity' => (float)($r['overlay_opacity'] ?? 0.55),
    'headline1'      => $r['headline1']         ?: 'Praise',
    'headline2'      => $r['headline2']         ?: 'Worship',
    'headline3'      => $r['headline3']         ?: 'Word',
    'tagline'        => $r['tagline']           ?: 'Broadcasting 24/7 Across the Caribbean & Beyond',
    'subline'        => $r['subline']           ?: 'The Sound of the Islands · Gospel · Worship · Inspiration',
  ];
}
function formatColors($r) {
  return [
    'primary'   => $r['primary_col'] ?? '#FFB700',
    'accent'    => $r['accent_col']  ?? '#CC0000',
    'dark'      => $r['dark_col']    ?? '#0a0a0a',
    'navBg'     => $r['nav_bg']      ?? 'rgba(0,0,0,0.92)',
    'playerBg'  => $r['player_bg']   ?? '#111111',
    'sectionBg' => $r['section_bg']  ?? '#111111',
    'footerBg'  => $r['footer_bg']   ?? '#0d0d0d',
    'text'      => $r['text_col']    ?? '#ffffff',
  ];
}
function formatDJ($r) {
  return [
    'id'    => (int)$r['id'],
    'name'  => $r['name'] ?? '',
    'bio'   => $r['bio']  ?? '',
    'photo' => $r['photo_data'] ?: ($r['photo_url'] ?? ''),
  ];
}
function formatShow($r) {
  $start = $r['time_start'] ?? '';
  $end   = $r['time_end']   ?? '';
  return [
    'id'           => (int)$r['id'],
    'show'         => $r['show_name']    ?? '',
    'day'          => $r['day_pattern']  ?? '',
    'time'         => $start . ' – ' . $end,
    'timeStart'    => $start,
    'timeEnd'      => $end,
    'djId'         => isset($r['dj_id']) && $r['dj_id'] ? (int)$r['dj_id'] : null,
    'liveOverride' => (bool)($r['live_override'] ?? false),
    'live'         => false,
  ];
}
function formatDownload($r) {
  return [
    'id'       => (int)$r['id'],
    'platform' => $r['platform']  ?? '',
    'sub'      => $r['sub_label'] ?? '',
    'url'      => $r['url']       ?? '#',
    'icon'     => $r['icon_key']  ?? '',
    'color'    => $r['icon_color']?? '#222',
    'img'      => $r['img_data']  ?: ($r['img_url'] ?? ''),
  ];
}
function formatSocial($r) {
  return [
    'id'       => (int)$r['id'],
    'platform' => $r['platform']  ?? '',
    'url'      => $r['url']       ?? '#',
    'icon'     => $r['icon_key']  ?? '',
    'color'    => $r['icon_color']?? '#333',
    'img'      => $r['img_data']  ?: ($r['img_url'] ?? ''),
  ];
}
function formatFlag($r) {
  return [
    'id'   => (int)$r['id'],  // id needed for delete
    'name' => $r['name'] ?? '',
    'code' => $r['code'] ?? '',
  ];
}

/* ══ DEFAULTS ════════════════════════════════ */
function defaultStation() {
  return ['name'=>'Caribbean Gospel Radio HD','streamUrl'=>'','timezone'=>'America/New_York','logo'=>'','cboxId'=>'','cboxTag'=>'','cboxEmbed'=>'','fallbackUrl'=>'','metadataUrl'=>'','bitrate'=>''];
}
function defaultHero() {
  return ['type'=>'gradient','src'=>'','overlayOpacity'=>0.55,'headline1'=>'Praise','headline2'=>'Worship','headline3'=>'Word','tagline'=>'Broadcasting 24/7 Across the Caribbean & Beyond','subline'=>'The Sound of the Islands · Gospel · Worship · Inspiration'];
}
function defaultColors() {
  return ['primary'=>'#FFB700','accent'=>'#CC0000','dark'=>'#0a0a0a','navBg'=>'rgba(0,0,0,0.92)','playerBg'=>'#111111','sectionBg'=>'#111111','footerBg'=>'#0d0d0d','text'=>'#ffffff'];
}

/* ══ AUTH ════════════════════════════════════ */
function handleLogin() {
  $in  = getInput();
  $db  = getDB();
  $stmt = $db->prepare('SELECT * FROM admin_users WHERE username = ? LIMIT 1');
  $stmt->execute([$in['username'] ?? '']);
  $row = $stmt->fetch();
  if (!$row || !password_verify($in['password'] ?? '', $row['password_hash'])) {
    jsonResponse(['error' => 'Invalid credentials'], 401);
  }
  $token = generateToken($row['id']);
  jsonResponse(['token' => $token, 'username' => $row['username']]);
}
function handleLogout() {
  jsonResponse(['ok' => true]);
}

/* ══ STATION ═════════════════════════════════ */
function getStation() {
  $r = getDB()->query('SELECT * FROM station LIMIT 1')->fetch();
  jsonResponse($r ? formatStation($r) : defaultStation());
}
function updateStation() {
  $in = getInput(); $db = getDB();
  $logoData = null; $logoUrl = '';
  if (!empty($in['logo'])) {
    if (strpos($in['logo'], 'data:') === 0) $logoData = $in['logo'];
    else $logoUrl = $in['logo'];
  }
  $db->prepare('UPDATE station SET name=?,stream_url=?,fallback_url=?,metadata_url=?,bitrate=?,timezone=?,logo_url=?,logo_data=?,cbox_id=?,cbox_tag=?,cbox_embed=? WHERE id=1')
     ->execute([
       $in['name']        ?? 'Caribbean Gospel Radio HD',
       $in['streamUrl']   ?? '',
       $in['fallbackUrl'] ?? '',
       $in['metadataUrl'] ?? '',
       $in['bitrate']     ?? '',
       $in['timezone']    ?? 'America/New_York',
       $logoUrl, $logoData,
       $in['cboxId']      ?? '',
       $in['cboxTag']     ?? '',
       $in['cboxEmbed']   ?? '',
     ]);
  jsonResponse(['ok' => true]);
}

/* ══ HERO ════════════════════════════════════ */
function getHero() {
  $r = getDB()->query('SELECT * FROM hero LIMIT 1')->fetch();
  jsonResponse($r ? formatHero($r) : defaultHero());
}
function updateHero() {
  $in = getInput(); $db = getDB();
  $bgData = null; $bgSrc = '';
  if (!empty($in['src'])) {
    if (strpos($in['src'], 'data:') === 0) $bgData = $in['src'];
    else $bgSrc = $in['src'];
  }
  $db->prepare('UPDATE hero SET bg_type=?,bg_src=?,bg_data=?,overlay_opacity=?,headline1=?,headline2=?,headline3=?,tagline=?,subline=? WHERE id=1')
     ->execute([
       $in['type']           ?? 'gradient',
       $bgSrc, $bgData,
       $in['overlayOpacity'] ?? 0.55,
       $in['headline1']      ?? '',
       $in['headline2']      ?? '',
       $in['headline3']      ?? '',
       $in['tagline']        ?? '',
       $in['subline']        ?? '',
     ]);
  jsonResponse(['ok' => true]);
}

/* ══ COLORS ══════════════════════════════════ */
function getColors() {
  $r = getDB()->query('SELECT * FROM colors LIMIT 1')->fetch();
  jsonResponse($r ? formatColors($r) : defaultColors());
}
function updateColors() {
  $in = getInput(); $db = getDB();
  $db->prepare('UPDATE colors SET primary_col=?,accent_col=?,dark_col=?,nav_bg=?,player_bg=?,section_bg=?,footer_bg=?,text_col=? WHERE id=1')
     ->execute([
       $in['primary']   ?? '#FFB700',
       $in['accent']    ?? '#CC0000',
       $in['dark']      ?? '#0a0a0a',
       $in['navBg']     ?? 'rgba(0,0,0,0.92)',
       $in['playerBg']  ?? '#111111',
       $in['sectionBg'] ?? '#111111',
       $in['footerBg']  ?? '#0d0d0d',
       $in['text']      ?? '#ffffff',
     ]);
  jsonResponse(['ok' => true]);
}

/* ══ DJs ═════════════════════════════════════ */
function getDJs() {
  jsonResponse(array_map('formatDJ', getDB()->query('SELECT * FROM djs ORDER BY sort_order,id')->fetchAll()));
}
function createDJ() {
  $in = getInput(); $db = getDB();
  $pd = null; $pu = '';
  if (!empty($in['photo'])) { if (strpos($in['photo'],'data:')===0) $pd=$in['photo']; else $pu=$in['photo']; }
  $db->prepare('INSERT INTO djs (name,bio,photo_url,photo_data) VALUES (?,?,?,?)')->execute([$in['name']??'New DJ',$in['bio']??'',$pu,$pd]);
  jsonResponse(['id'=>(int)$db->lastInsertId(),'ok'=>true]);
}
function updateDJ($id) {
  $in = getInput(); $db = getDB();
  $pd = null; $pu = '';
  if (!empty($in['photo'])) { if (strpos($in['photo'],'data:')===0) $pd=$in['photo']; else $pu=$in['photo']; }
  $db->prepare('UPDATE djs SET name=?,bio=?,photo_url=?,photo_data=? WHERE id=?')->execute([$in['name']??'',$in['bio']??'',$pu,$pd,$id]);
  jsonResponse(['ok'=>true]);
}
function deleteDJ($id) {
  getDB()->prepare('DELETE FROM djs WHERE id=?')->execute([$id]);
  jsonResponse(['ok'=>true]);
}

/* ══ SCHEDULE ════════════════════════════════ */
function getSchedule() {
  jsonResponse(array_map('formatShow', getDB()->query('SELECT * FROM schedule ORDER BY sort_order,id')->fetchAll()));
}
function createShow() {
  $in = getInput(); $db = getDB();
  $parts = preg_split('/\s*[–\-—]\s*/u', $in['time'] ?? '12:00 PM – 2:00 PM');
  $start = trim($parts[0] ?? '12:00 PM');
  $end   = trim($parts[1] ?? '2:00 PM');
  $db->prepare('INSERT INTO schedule (show_name,day_pattern,time_start,time_end,dj_id,live_override) VALUES (?,?,?,?,?,?)')->execute([$in['show']??'New Show',$in['day']??'Daily',$start,$end,$in['djId']??null,(int)($in['liveOverride']??0)]);
  jsonResponse(['id'=>(int)$db->lastInsertId(),'ok'=>true]);
}
function updateShow($id) {
  $in = getInput(); $db = getDB();
  $parts = preg_split('/\s*[–\-—]\s*/u', $in['time'] ?? '12:00 PM – 2:00 PM');
  $start = trim($parts[0] ?? '12:00 PM');
  $end   = trim($parts[1] ?? '2:00 PM');
  $db->prepare('UPDATE schedule SET show_name=?,day_pattern=?,time_start=?,time_end=?,dj_id=?,live_override=? WHERE id=?')->execute([$in['show']??'',$in['day']??'',$start,$end,$in['djId']??null,(int)($in['liveOverride']??0),$id]);
  jsonResponse(['ok'=>true]);
}
function deleteShow($id) {
  getDB()->prepare('DELETE FROM schedule WHERE id=?')->execute([$id]);
  jsonResponse(['ok'=>true]);
}

/* ══ DOWNLOADS ═══════════════════════════════ */
function getDownloads() {
  jsonResponse(array_map('formatDownload', getDB()->query('SELECT * FROM downloads ORDER BY sort_order,id')->fetchAll()));
}
function createDownload() {
  $in = getInput(); $db = getDB();
  $id2=null; $iu='';
  if (!empty($in['img'])) { if (strpos($in['img'],'data:')===0) $id2=$in['img']; else $iu=$in['img']; }
  $db->prepare('INSERT INTO downloads (platform,sub_label,url,icon_key,icon_color,img_url,img_data) VALUES (?,?,?,?,?,?,?)')->execute([$in['platform']??'',$in['sub']??'',$in['url']??'#',$in['icon']??'',$in['color']??'#222',$iu,$id2]);
  jsonResponse(['id'=>(int)$db->lastInsertId(),'ok'=>true]);
}
function updateDownload($id) {
  $in = getInput(); $db = getDB();
  $id2=null; $iu='';
  if (!empty($in['img'])) { if (strpos($in['img'],'data:')===0) $id2=$in['img']; else $iu=$in['img']; }
  $db->prepare('UPDATE downloads SET platform=?,sub_label=?,url=?,icon_key=?,icon_color=?,img_url=?,img_data=? WHERE id=?')->execute([$in['platform']??'',$in['sub']??'',$in['url']??'#',$in['icon']??'',$in['color']??'#222',$iu,$id2,$id]);
  jsonResponse(['ok'=>true]);
}
function deleteDownload($id) {
  getDB()->prepare('DELETE FROM downloads WHERE id=?')->execute([$id]);
  jsonResponse(['ok'=>true]);
}

/* ══ SOCIALS ═════════════════════════════════ */
function getSocials() {
  jsonResponse(array_map('formatSocial', getDB()->query('SELECT * FROM socials ORDER BY sort_order,id')->fetchAll()));
}
function createSocial() {
  $in = getInput(); $db = getDB();
  $id2=null; $iu='';
  if (!empty($in['img'])) { if (strpos($in['img'],'data:')===0) $id2=$in['img']; else $iu=$in['img']; }
  $db->prepare('INSERT INTO socials (platform,url,icon_key,icon_color,img_url,img_data) VALUES (?,?,?,?,?,?)')->execute([$in['platform']??'',$in['url']??'#',$in['icon']??'',$in['color']??'#333',$iu,$id2]);
  jsonResponse(['id'=>(int)$db->lastInsertId(),'ok'=>true]);
}
function updateSocial($id) {
  $in = getInput(); $db = getDB();
  $id2=null; $iu='';
  if (!empty($in['img'])) { if (strpos($in['img'],'data:')===0) $id2=$in['img']; else $iu=$in['img']; }
  $db->prepare('UPDATE socials SET platform=?,url=?,icon_key=?,icon_color=?,img_url=?,img_data=? WHERE id=?')->execute([$in['platform']??'',$in['url']??'#',$in['icon']??'',$in['color']??'#333',$iu,$id2,$id]);
  jsonResponse(['ok'=>true]);
}
function deleteSocial($id) {
  getDB()->prepare('DELETE FROM socials WHERE id=?')->execute([$id]);
  jsonResponse(['ok'=>true]);
}

/* ══ FLAGS ═══════════════════════════════════ */
function getFlags() {
  jsonResponse(array_map('formatFlag', getDB()->query('SELECT * FROM flags ORDER BY sort_order,id')->fetchAll()));
}
function createFlag() {
  $in = getInput();
  getDB()->prepare('INSERT INTO flags (name,code) VALUES (?,?)')->execute([$in['name']??'',$in['code']??'']);
  jsonResponse(['id'=>(int)getDB()->lastInsertId(),'ok'=>true]);
}
function updateFlag($id) {
  $in = getInput();
  getDB()->prepare('UPDATE flags SET name=?,code=? WHERE id=?')->execute([$in['name']??'',$in['code']??'',$id]);
  jsonResponse(['ok'=>true]);
}
function deleteFlag($id) {
  getDB()->prepare('DELETE FROM flags WHERE id=?')->execute([$id]);
  jsonResponse(['ok'=>true]);
}

/* ══ UPLOAD ══════════════════════════════════ */
function handleUpload() {
  if (empty($_FILES['file'])) jsonResponse(['error'=>'No file'],400);
  $file = $_FILES['file'];
  $ext  = strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
  if (!in_array($ext,['jpg','jpeg','png','gif','webp'])) jsonResponse(['error'=>'Invalid type'],400);
  $name = uniqid('img_').'.'.$ext;
  $dest = UPLOAD_DIR.$name;
  if (!move_uploaded_file($file['tmp_name'],$dest)) jsonResponse(['error'=>'Upload failed'],500);
  jsonResponse(['url'=>UPLOAD_URL.$name]);
}