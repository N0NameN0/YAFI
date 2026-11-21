<?php
/***************************************************************************
 *
 *  YAFI - Yet Another File Indexer
 *  A modern, single-file PHP file browser with audio and image players.
 *
 *  Original snif 1.5.2 by Kai Blankenhorn (www.bitfolge.de)
 *  Modernized and maintained by Antoine Santo aka N0NameN0 - 2026
 *
 *  v4 highlights
 *  ------------
 *  Security
 *   - Real path containment for browsing, downloads and thumbnails
 *     (blocks ../, absolute paths and symlinks escaping the root)
 *   - Rejects files in .htaccess-protected subdirectories
 *   - No user data in HTML attributes unescaped, no inline event handlers
 *   - CSP with per-request nonce, nosniff, frame and referrer policies
 *   - Safe Content-Disposition with RFC 5987 UTF-8 filename
 *  Correctness
 *   - No double URL decoding, no PHP_SELF/Host XSS, no magic-quotes mangling
 *   - UTF-8 aware truncation, titles, search and natural sorting
 *   - Folders stay first in both sort directions
 *   - Video files are finally detected as video (not binary)
 *   - Thumbnails keep alpha, honour EXIF orientation, cache correctly
 *  Speed
 *   - One SVG sprite instead of one inline SVG per file
 *   - Grid view rendered once in JS from a small JSON payload
 *   - No per-row stagger animation, no per-card backdrop blur
 *   - Debounced filtering, lazy thumbnails, conditional GET for thumbnails
 *   - System fonts, no Google Fonts request, no animated background
 *  Comfort
 *   - Real pagination UI, folder-first natural sort, grid/list persistence
 *   - Audio playlist for every browser-supported format (mp3, ogg, opus,
 *     flac, wav, m4a/m4b, aac, weba, aiff, caf...) with per-track play buttons,
 *     prev/next, Media Session and live waveform; pausing folds the bar away
 *     and keeps the track position for the next play
 *   - Image lightbox with fixed prev/next buttons, pixelated wheel zoom,
 *     drag pan and a zoom factor that follows you between images
 *   - Keyboard shortcuts dialog, copy-link action, dark/light/system theme
 *
 *  Requires PHP 8.1+ and the GD extension for thumbnails (optional).
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License v2+.
 *
 ***************************************************************************/


/***************************************************************************/
/**  CONFIGURATION (compatible with the original snif)                    **/
/***************************************************************************/

$hiddenFilesWildcards    = array("*~", "*.md", "old*");
$allowSubDirs            = true;
$allowPHPDownloads       = false;
$useAutoThumbnails       = true;
$cacheThumbnails         = true;

$snifServer              = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
$snifDateFormat          = 'd-m-y';
$hiddenFilesRegex        = array();
$useDescriptionsFrom     = "descript.ion";
$separationString        = "\t";
$useExternalImages       = false;
$externalIcons           = array();
$externalStylesheet      = "";
$externalConfig          = "";
$descriptionFilenamesCaseSensitive = false;
$usePaging               = 0;
$directDirectoryLinks    = false;
$thumbnailHeight         = 80;
$thumbnailWidth          = 200;
$useBackForDirUp         = true;
$displayColumns          = array("icon", "name", "type", "size", "date", "description");
$tableWidth100Percent    = true;
$descriptionColumnWidth  = 0;
$truncateLength          = 50;
$protectDirsWithHtaccess = true;
$alwaysUseLanguage       = "en";       // en (default) | fr | de | "" = auto-detect

/* YAFI additions (all optional) */
$snifVersion             = '4.0';
$hideSelf                = true;      // hide this script from its own listing
$showDirItemCounts       = true;      // false avoids scanning every subdirectory
$defaultTheme            = 'dark';    // dark | light | auto
$defaultView             = 'list';    // list | grid
$allowIndexing           = false;     // allow search engines to index listings
$debug                   = false;     // display PHP errors (never enable in production)
$thumbnailQuality        = 82;
$thumbnailMaxPixels      = 20000000;
$useWebPThumbnails       = true;
$thumbnailCacheDir       = rtrim(sys_get_temp_dir(), '/\\') . '/yafi-thumbs-' . substr(sha1(__DIR__), 0, 12);
$thumbnailCacheMaxAge    = 2592000;   // 30 days
$downloadDenyExtensions  = array('php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phar', 'phps', 'inc');


/***************************************************************************/
/**  TRANSLATIONS                                                         **/
/***************************************************************************/

$languageStrings = array(
    "en" => array(
        "Index of" => "",
        "name" => "",
        "type" => "",
        "size" => "",
        "date" => "",
        "description" => "",
        "DATEFORMAT" => $snifDateFormat,
        "folder" => "",
        "archive" => "",
        "image" => "",
        "text" => "",
        "HTML" => "",
        "unknown" => "",
        "valid" => "",
        "binary" => "",
        "dirup" => "",
        "download" => "",
        "asc" => "",
        "desc" => "",
        "[ back ]" => "",
        "1 item" => "",
        "%d items" => "",
        "%s is not a subdirectory of the current directory." => "",
        "File not found: %s" => "",
        "Illegal characters detected in URL, ignoring." => "",
        "Illegal path specified, ignoring." => "",
        "Bytes" => "",
        "B" => "",
        "KB" => "",
        "MB" => "",
        "GB" => "",
        "TB" => "",
        "pages" => "",
        "previous" => "",
        "next" => "",
        "music" => ""
    ),
    "fr" => array(
        "Index of" => "Index de",
        "name" => "nom",
        "type" => "type",
        "size" => "taille",
        "date" => "date",
        "description" => "description",
        "DATEFORMAT" => "d-m-y",
        "folder" => "r\xC3\xA9pertoire",
        "archive" => "archive",
        "image" => "image",
        "text" => "texte",
        "HTML" => "HTML",
        "unknown" => "inconnu",
        "valid" => "valide",
        "binary" => "binaire",
        "dirup" => "niveau sup\xC3\xA9rieur",
        "download" => "t\xC3\xA9l\xC3\xA9charger",
        "asc" => "asc",
        "desc" => "desc",
        "[ back ]" => "[ retour ]",
        "1 item" => "1 \xC3\xA9l\xC3\xA9ment",
        "%d items" => "%d \xC3\xA9l\xC3\xA9ments",
        "%s is not a subdirectory of the current directory." => "%s n'est pas un sous-r\xC3\xA9pertoire du r\xC3\xA9pertoire courant",
        "File not found: %s" => "Fichier non trouv\xC3\xA9: %s",
        "Illegal characters detected in URL, ignoring." => "Caract\xC3\xA8res ill\xC3\xA9gaux dans l'URL, ignor\xC3\xA9s.",
        "Illegal path specified, ignoring." => "Chemin ill\xC3\xA9gal, ignor\xC3\xA9.",
        "Bytes" => "octets",
        "B" => "o",
        "KB" => "Ko",
        "MB" => "Mo",
        "GB" => "Go",
        "TB" => "To",
        "pages" => "pages",
        "previous" => "pr\xC3\xA9c\xC3\xA9dente",
        "next" => "suivante",
        "music" => "musique",
        "Search files..." => "Rechercher des fichiers...",
        "Search this page..." => "Rechercher dans cette page...",
        "Search" => "Rechercher",
        "Toggle theme" => "Changer de th\xC3\xA8me",
        "Grid view" => "Vue grille",
        "List view" => "Vue liste",
        "Copy link" => "Copier le lien",
        "Link copied" => "Lien copi\xC3\xA9",
        "Could not copy the link." => "Impossible de copier le lien.",
        "Close" => "Fermer",
        "Play" => "Lecture",
        "Pause" => "Pause",
        "Previous" => "Pr\xC3\xA9c\xC3\xA9dent",
        "Next" => "Suivant",
        "Volume" => "Volume",
        "Mute" => "Couper le son",
        "Unmute" => "R\xC3\xA9tablir le son",
        "Audio player" => "Lecteur audio",
        "Seek" => "Position",
        "Keyboard shortcuts" => "Raccourcis clavier",
        "Credits" => "Cr\xC3\xA9dits",
        "Created by" => "Cr\xC3\xA9\xC3\xA9 par",
        "Based on" => "Bas\xC3\xA9 sur",
        "Sort by" => "Trier par",
        "Pagination" => "Pagination",
        "Page %d of %d" => "Page %d sur %d",
        "1 folder" => "1 dossier",
        "%d folders" => "%d dossiers",
        "1 file" => "1 fichier",
        "%d files" => "%d fichiers",
        "Empty folder" => "Dossier vide",
        "No files match your search." => "Aucun fichier ne correspond \xC3\xA0 votre recherche.",
        "Unable to read this directory." => "Impossible de lire ce dossier.",
        "Could not play this file." => "Impossible de lire ce fichier.",
        "This audio format is not supported by your browser." => "Ce format audio n'est pas pris en charge par votre navigateur.",
        "Showing %d of %d" => "Affichage de %d sur %d",
        "just now" => "\xC3\xA0 l'instant",
        "%d min ago" => "il y a %d min",
        "%d h ago" => "il y a %d h",
        "%d d ago" => "il y a %d j",
        "%d w ago" => "il y a %d sem.",
        "%d mo ago" => "il y a %d mois",
        "%d y ago" => "il y a %d an(s)",
        "link" => "lien",
        "Home" => "Accueil",
        "Preview" => "Aper\xC3\xA7u",
        "New window" => "Nouvelle fen\xC3\xAAtre"
    ),
    "de" => array(
        "Index of" => "Inhalt von",
        "name" => "Name",
        "type" => "Typ",
        "size" => "Gr\xC3\xB6\xC3\x9Fe",
        "date" => "Datum",
        "DATEFORMAT" => "d.m.y",
        "description" => "Beschreibung",
        "folder" => "Verzeichnis",
        "archive" => "Archiv",
        "image" => "Bild",
        "text" => "Text-Datei",
        "HTML" => "HTML-Datei",
        "unknown" => "unbekannt",
        "valid" => "g\xC3\xBCltiges",
        "binary" => "Bin\xC3\xA4rdatei",
        "dirup" => "Aufw\xC3\xA4rts",
        "download" => "Download",
        "asc" => "aufsteigend",
        "desc" => "absteigend",
        "[ back ]" => "[ aufw\xC3\xA4rts ]",
        "1 item" => "1 Eintrag",
        "%d items" => "%d Eintr\xC3\xA4ge",
        "%s is not a subdirectory of the current directory." => "%s ist kein Unterverzeichnis.",
        "File not found: %s" => "Datei nicht gefunden: %s",
        "Illegal characters detected in URL, ignoring." => "Ung\xC3\xBCltige Zeichen in der URL.",
        "Illegal path specified, ignoring." => "Ung\xC3\xBCltiger Pfad.",
        "Bytes" => "Bytes",
        "B" => "",
        "KB" => "",
        "MB" => "",
        "GB" => "",
        "TB" => "",
        "pages" => "Seiten",
        "previous" => "vorherige",
        "next" => "n\xC3\xA4chste",
        "music" => "Musik",
        "Search files..." => "Dateien durchsuchen...",
        "Search this page..." => "Diese Seite durchsuchen...",
        "Search" => "Suchen",
        "Toggle theme" => "Design wechseln",
        "Grid view" => "Rasteransicht",
        "List view" => "Listenansicht",
        "Copy link" => "Link kopieren",
        "Link copied" => "Link kopiert",
        "Could not copy the link." => "Der Link konnte nicht kopiert werden.",
        "Close" => "Schlie\xC3\x9Fen",
        "Play" => "Abspielen",
        "Pause" => "Pause",
        "Previous" => "Zur\xC3\xBCck",
        "Next" => "Weiter",
        "Volume" => "Lautst\xC3\xA4rke",
        "Mute" => "Stummschalten",
        "Unmute" => "Ton einschalten",
        "Audio player" => "Audioplayer",
        "Seek" => "Position",
        "Keyboard shortcuts" => "Tastenk\xC3\xBCrzel",
        "Credits" => "Credits",
        "Created by" => "Erstellt von",
        "Based on" => "Basiert auf",
        "Sort by" => "Sortieren nach",
        "Pagination" => "Seitennavigation",
        "Page %d of %d" => "Seite %d von %d",
        "1 folder" => "1 Ordner",
        "%d folders" => "%d Ordner",
        "1 file" => "1 Datei",
        "%d files" => "%d Dateien",
        "Empty folder" => "Leerer Ordner",
        "No files match your search." => "Keine Dateien entsprechen Ihrer Suche.",
        "Unable to read this directory." => "Dieses Verzeichnis kann nicht gelesen werden.",
        "Could not play this file." => "Diese Datei kann nicht abgespielt werden.",
        "This audio format is not supported by your browser." => "Dieses Audioformat wird von Ihrem Browser nicht unterstützt.",
        "Showing %d of %d" => "%d von %d werden angezeigt",
        "just now" => "gerade eben",
        "%d min ago" => "vor %d Min.",
        "%d h ago" => "vor %d Std.",
        "%d d ago" => "vor %d T.",
        "%d w ago" => "vor %d Wo.",
        "%d mo ago" => "vor %d Mon.",
        "%d y ago" => "vor %d J.",
        "link" => "Link",
        "Home" => "Start",
        "Preview" => "Vorschau",
        "New window" => "Neues Fenster"
    )
);


/***************************************************************************/
/**  BOOTSTRAP                                                            **/
/***************************************************************************/

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

$snifRoot       = realpath(__DIR__) ?: __DIR__;
$snifScript     = basename(__FILE__);
$snifSelfReal   = realpath(__FILE__) ?: __FILE__;
$snifBasePath   = '';
$scriptName     = isset($_SERVER['SCRIPT_NAME']) && is_string($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
$scriptPosition = strpos($scriptName, '/' . $snifScript);
$scriptDir      = $scriptPosition !== false ? substr($scriptName, 0, $scriptPosition) : dirname($scriptName);
$scriptDir      = str_replace('\\', '/', $scriptDir);
$scriptDir      = preg_replace('#/+#', '/', $scriptDir);
$scriptDir      = rtrim((string) $scriptDir, '/');
if ($scriptDir === '.' || $scriptDir === '/') {
    $scriptDir = '';
}
if ($scriptDir === '' || preg_match('#^/[A-Za-z0-9._~!$&\'()*+,;=:@% /-]*$#', $scriptDir)) {
    $snifBasePath = $scriptDir;
}
$snifSelf       = ($snifBasePath === '' ? '' : $snifBasePath) . '/' . rawurlencode($snifScript);
$snifNonce      = base64_encode(random_bytes(18));
$snifNow        = time();
$displayError   = array();
$descriptions   = array();
$sort           = 'name';
$order          = 'asc';
$path           = '';

if ($externalConfig !== '') {
    $configPath = $externalConfig;
    if ($configPath[0] !== '/' && !preg_match('/^[A-Za-z]:[\\\\\/]/', $configPath)) {
        $configPath = __DIR__ . '/' . $configPath;
    }
    if (is_file($configPath)) {
        ob_start();
        include $configPath;
        ob_end_clean();
    }
}

/* Language negotiation is done after the external config so it can override it. */
function snifLanguage(): string
{
    global $languageStrings, $alwaysUseLanguage;
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }

    $valid = array_keys($languageStrings);
    if ($alwaysUseLanguage !== '' && in_array($alwaysUseLanguage, $valid, true)) {
        $resolved = $alwaysUseLanguage;
        return $resolved;
    }

    $candidates = array();
    $header = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && is_string($_SERVER['HTTP_ACCEPT_LANGUAGE'])
        ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
    foreach (explode(',', $header) as $part) {
        $pieces = explode(';', trim($part));
        $tag = strtolower(trim($pieces[0]));
        if ($tag === '') {
            continue;
        }
        $q = 1.0;
        if (isset($pieces[1]) && preg_match('/^\s*q\s*=\s*([0-9.]+)/i', $pieces[1], $m)) {
            $q = (float) $m[1];
        }
        if ($q <= 0) {
            continue;
        }
        $primary = substr($tag, 0, 2);
        if (in_array($primary, $valid, true) && (!isset($candidates[$primary]) || $candidates[$primary] < $q)) {
            $candidates[$primary] = $q;
        }
    }
    arsort($candidates);
    $resolved = $candidates ? (string) array_key_first($candidates) : $valid[0];
    return $resolved;
}

function translate($string)
{
    global $languageStrings;
    $lang = snifLanguage();
    $value = $languageStrings[$lang][$string] ?? '';
    return $value !== '' ? $value : $string;
}

function snifH($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function snifLower(string $value): string
{
    if (function_exists('mb_strtolower') && (!function_exists('mb_check_encoding') || mb_check_encoding($value, 'UTF-8'))) {
        return mb_strtolower($value, 'UTF-8');
    }
    return strtolower($value);
}

function snifUtf8(string $value): string
{
    if (function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) {
        return mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
    }
    return $value;
}

function snifHeader(string $line): void
{
    if (!headers_sent()) {
        header($line);
    }
}

function snifGet(string $key): string
{
    return isset($_GET[$key]) && is_string($_GET[$key]) ? $_GET[$key] : '';
}

/* ---- HTTP helpers ------------------------------------------------------ */

if (!headers_sent()) {
    header_remove('X-Powered-By');
}
snifHeader('X-Content-Type-Options: nosniff');
snifHeader('Referrer-Policy: same-origin');
snifHeader('X-Frame-Options: SAMEORIGIN');
snifHeader('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
if (!$allowIndexing) {
    snifHeader('X-Robots-Tag: noindex, nofollow');
}

$styleSources = array("'self'", "'nonce-" . $snifNonce . "'");
if ($externalStylesheet !== '') {
    $parsedStyle = @parse_url($externalStylesheet);
    if (is_array($parsedStyle) && !empty($parsedStyle['host'])) {
        $scheme = $parsedStyle['scheme'] ?? 'https';
        if ($scheme === 'http' || $scheme === 'https') {
            $styleSources[] = $scheme . '://' . $parsedStyle['host'];
        }
    }
}
snifHeader("Content-Security-Policy: default-src 'self'; script-src 'nonce-" . $snifNonce . "'; style-src " . implode(' ', $styleSources) . "; img-src 'self' data:; media-src 'self'; font-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'self'");


/***************************************************************************/
/**  FILESYSTEM / URL HELPERS                                             **/
/***************************************************************************/

function snifRootPrefix(string $root): string
{
    $root = rtrim($root, '/\\');
    return $root === '' ? DIRECTORY_SEPARATOR : $root . DIRECTORY_SEPARATOR;
}

function snifIsWithin(string $candidate, string $root): bool
{
    if ($candidate === $root) {
        return true;
    }
    return strpos($candidate, snifRootPrefix($root)) === 0;
}

function snifWildcardRegex(array $wildcards): string
{
    $parts = array();
    foreach ($wildcards as $wildcard) {
        $wildcard = (string) $wildcard;
        if ($wildcard === '') {
            continue;
        }
        $quoted = preg_quote($wildcard, '/');
        $quoted = str_replace(array('\*', '\?'), array('.*', '.'), $quoted);
        $parts[] = '^' . $quoted . '$';
    }
    $parts[] = '^\..+$';
    $regex = '/(' . implode('|', $parts) . ')/i';
    if (@preg_match($regex, '') === false) {
        return '/^\..+$/';
    }
    return $regex;
}

function snifNameIsHidden(string $name): bool
{
    global $hiddenFilesWholeRegex;
    return $name !== '' && preg_match($hiddenFilesWholeRegex, $name) === 1;
}

function snifDirectoryIsProtected(string $directory): bool
{
    return is_file($directory . DIRECTORY_SEPARATOR . '.htaccess');
}

function snifHasProtectedAncestor(string $root, string $directory): bool
{
    global $protectDirsWithHtaccess;
    if (!$protectDirsWithHtaccess) {
        return false;
    }
    $prefix = snifRootPrefix($root);
    $dir = $directory;
    while ($dir !== $root && $dir !== '' && strpos($dir, $prefix) === 0) {
        if (snifDirectoryIsProtected($dir)) {
            return true;
        }
        $parent = dirname($dir);
        if ($parent === $dir) {
            break;
        }
        $dir = $parent;
    }
    return false;
}

function snifContainsHiddenSegment(string $relative): bool
{
    foreach (explode('/', trim($relative, '/')) as $segment) {
        if ($segment !== '' && snifNameIsHidden($segment)) {
            return true;
        }
    }
    return false;
}

/**
 * Normalise a user-supplied relative path. Returns null when the path is
 * absolute, contains a traversal segment or a control character.
 * A non-empty result always ends with "/".
 */
function snifNormalizeRelative(string $raw): ?string
{
    if (strpos($raw, "\0") !== false || $raw === '') {
        return '';
    }
    if (DIRECTORY_SEPARATOR === '\\') {
        $raw = str_replace('\\', '/', $raw);
    }
    if ($raw[0] === '/' || preg_match('/^[A-Za-z]:\//', $raw)) {
        return null;
    }
    $segments = array();
    foreach (explode('/', $raw) as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }
        if ($segment === '..') {
            return null;
        }
        $segments[] = $segment;
    }
    if (!$segments) {
        return '';
    }
    return implode('/', $segments) . '/';
}

function snifResolveDirectory(string $root, string $relative): ?string
{
    if ($relative === '') {
        return $root;
    }
    $normalized = snifNormalizeRelative($relative);
    if ($normalized === null) {
        return null;
    }
    $relative = rtrim($normalized, '/');
    if ($relative === '' || snifContainsHiddenSegment($relative)) {
        return null;
    }
    $real = realpath($root . '/' . $relative);
    if ($real === false || !is_dir($real) || !snifIsWithin($real, $root)) {
        return null;
    }
    if (snifHasProtectedAncestor($root, $real)) {
        return null;
    }
    return $real;
}

function snifResolveFile(string $root, string $relative, bool $imageOnly = false): ?string
{
    if (DIRECTORY_SEPARATOR === '\\') {
        $relative = str_replace('\\', '/', $relative);
    }
    if ($relative === '') {
        return null;
    }
    $normalized = snifNormalizeRelative($relative);
    if ($normalized === null) {
        return null;
    }
    $relative = rtrim($normalized, '/');
    if ($relative === '' || snifContainsHiddenSegment($relative)) {
        return null;
    }
    $real = realpath($root . '/' . $relative);
    if ($real === false || !is_file($real) || !is_readable($real) || !snifIsWithin($real, $root)) {
        return null;
    }
    if (snifHasProtectedAncestor($root, dirname($real))) {
        return null;
    }
    if ($imageOnly && !snifIsPreviewableImage(strtolower(pathinfo($real, PATHINFO_EXTENSION)))) {
        return null;
    }
    return $real;
}

function snifFileUrl(string $relative): string
{
    global $snifBasePath;
    $segments = array();
    foreach (explode('/', $relative) as $segment) {
        if ($segment !== '' && $segment !== '.') {
            $segments[] = rawurlencode($segment);
        }
    }
    return ($snifBasePath === '' ? '' : $snifBasePath) . '/' . implode('/', $segments);
}

function snifQueryUrl(array $params): string
{
    global $snifSelf;
    if (!$params) {
        return $snifSelf;
    }
    return $snifSelf . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

function snifDirUrl(string $relative): string
{
    global $directDirectoryLinks, $snifBasePath;
    $relative = trim($relative, '/');
    if ($directDirectoryLinks) {
        return $relative === ''
            ? ($snifBasePath === '' ? '/' : $snifBasePath . '/')
            : snifFileUrl($relative) . '/';
    }
    if ($relative === '') {
        return snifQueryUrl(array());
    }
    return snifQueryUrl(array('path' => $relative . '/'));
}

function snifDownloadUrl(string $name): string
{
    global $path;
    $params = array();
    if ($path !== '') {
        $params['path'] = rtrim($path, '/');
    }
    $params['download'] = $name;
    return snifQueryUrl($params);
}

function snifThumbUrl(string $relative, int $mtime): string
{
    return snifQueryUrl(array('thumbnail' => $relative, 'v' => $mtime));
}

function snifSortUrl(string $key): string
{
    global $path, $sort, $order;
    $newOrder = $sort === $key
        ? ($order === 'asc' ? 'desc' : 'asc')
        : (in_array($key, array('size', 'date'), true) ? 'desc' : 'asc');
    $params = array();
    if ($path !== '') {
        $params['path'] = rtrim($path, '/');
    }
    $params['sort'] = $key;
    $params['order'] = $newOrder;
    return snifQueryUrl($params);
}

function snifPageUrl(int $start): string
{
    global $path, $sort, $order;
    $params = array();
    if ($path !== '') {
        $params['path'] = rtrim($path, '/');
    }
    $params['sort'] = $sort;
    $params['order'] = $order;
    $params['start'] = $start;
    return snifQueryUrl($params);
}

function snifAriaSort(string $key): string
{
    global $sort, $order;
    if ($sort !== $key) {
        return 'none';
    }
    return $order === 'asc' ? 'ascending' : 'descending';
}


/***************************************************************************/
/**  FORMATTING / CLASSIFICATION                                          **/
/***************************************************************************/

function niceSize($size): string
{
    if ($size === '' || $size === null || !is_numeric($size)) {
        return '';
    }
    $size = (float) $size;
    $units = array(translate('B'), 'KB', 'MB', 'GB', 'TB');
    $index = 0;
    while ($size >= 1024.0 && $index < count($units) - 1) {
        $size /= 1024.0;
        $index++;
    }
    $decimals = $index === 0 ? 0 : 1;
    return number_format($size, $decimals, '.', ',') . ' ' . $units[$index];
}

function iTrunc(string $string, int $length): string
{
    if ($length <= 0) {
        return $string;
    }
    $isUtf8 = function_exists('mb_check_encoding') && mb_check_encoding($string, 'UTF-8');
    $strlen = $isUtf8 ? 'mb_strlen' : 'strlen';
    $substr = $isUtf8 ? 'mb_substr' : 'substr';
    $strrpos = $isUtf8 ? 'mb_strrpos' : 'strrpos';

    $total = $strlen($string);
    if ($total <= $length) {
        return $string;
    }
    if ($length < 4) {
        return $substr($string, 0, $length) . "\u{2026}";
    }

    $dot = $strrpos($string, '.');
    if ($dot !== false && $dot > 0 && $total - $dot <= 12) {
        $extension = $substr($string, $dot);
        $room = $length - $strlen($extension) - 1;
        if ($room >= 4) {
            return $substr($string, 0, $room) . "\u{2026}" . $extension;
        }
    }

    return $substr($string, 0, $length - 1) . "\u{2026}";
}

function snifFileType(string $extension): string
{
    static $map = null;
    if ($map === null) {
        $groups = array(
            'HTML' => array('html', 'htm', 'xhtml', 'shtml'),
            'image' => array('gif', 'jpg', 'jpeg', 'png', 'apng', 'webp', 'bmp', 'tif', 'tiff', 'art', 'svg', 'ico', 'avif', 'heic', 'heif', 'jxl'),
            'music' => array('mp3', 'mp2', 'flac', 'wav', 'wave', 'aac', 'm4a', 'm4b', 'm4p', 'wma', 'opus', 'ogg', 'oga', 'spx', 'weba', 'aif', 'aiff', 'aifc', 'caf', 'alac', 'mid', 'midi'),
            'video' => array('mp4', 'mkv', 'avi', 'mov', 'wmv', 'flv', 'webm', 'm4v', 'ogv', 'mpg', 'mpeg', '3gp'),
            'archive' => array('ace', 'arc', 'bz2', 'cab', 'gz', 'lha', 'jar', 'war', 'rar', 'sit', 'tar', 'tbz2', 'tgz', 'z', 'zip', 'zoo', '7z', 'xz', 'zst', 'iso', 'dmg', 'apk', 'deb', 'rpm'),
            'text' => array('asp', 'aspx', 'c', 'h', 'cpp', 'hpp', 'cc', 'cs', 'cfg', 'conf', 'css', 'csv', 'cue', 'diz', 'go', 'inf', 'ini', 'java', 'js', 'jsx', 'json', 'log', 'lua', 'md', 'markdown', 'nfo', 'patch', 'php', 'phps', 'pl', 'ps1', 'py', 'rdf', 'rb', 'rs', 'rss', 'rtf', 'sass', 'scss', 'sh', 'bash', 'zsh', 'fish', 'sql', 'swift', 'kt', 'toml', 'ts', 'tsx', 'txt', 'vbs', 'vue', 'svelte', 'xml', 'yaml', 'yml', 'diff', 'bat'),
            'binary' => array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'ps', 'exe', 'bin', 'class', 'swf', 'wmf', 'asf', 'au', 'rm', 'divx', 'dat', 'torrent', 'sqlite', 'db', 'ttf', 'otf', 'woff', 'woff2', 'epub')
        );
        $map = array();
        foreach ($groups as $type => $extensions) {
            foreach ($extensions as $ext) {
                if (!isset($map[$ext])) {
                    $map[$ext] = $type;
                }
            }
        }
    }
    return $map[$extension] ?? 'unknown';
}

function snifIsPreviewableImage(string $extension): bool
{
    return in_array($extension, array('gif', 'jpg', 'jpeg', 'png', 'apng', 'webp', 'bmp', 'svg', 'avif', 'ico'), true);
}

function snifIsPlayableAudio(string $extension): bool
{
    return in_array($extension, array(
        'mp3', 'mp2', 'ogg', 'oga', 'opus', 'spx', 'wav', 'wave', 'flac',
        'm4a', 'm4b', 'm4p', 'aac', 'weba', 'aif', 'aiff', 'aifc', 'caf', 'alac'
    ), true);
}

function snifAudioMime(string $extension): ?string
{
    $map = array(
        'mp3' => 'audio/mpeg',
        'mp2' => 'audio/mpeg',
        'ogg' => 'audio/ogg',
        'oga' => 'audio/ogg',
        'opus' => 'audio/ogg',
        'spx' => 'audio/ogg; codecs="speex"',
        'wav' => 'audio/wav',
        'wave' => 'audio/wav',
        'flac' => 'audio/flac',
        'm4a' => 'audio/mp4',
        'm4b' => 'audio/mp4',
        'm4p' => 'audio/mp4',
        'aac' => 'audio/aac',
        'weba' => 'audio/webm',
        'aif' => 'audio/aiff',
        'aiff' => 'audio/aiff',
        'aifc' => 'audio/aiff',
        'caf' => 'audio/x-caf',
        'alac' => 'audio/mp4; codecs="alac"'
    );
    return $map[$extension] ?? null;
}

function snifIsBlockedDownload(string $extension): bool
{
    global $downloadDenyExtensions;
    return in_array($extension, $downloadDenyExtensions, true);
}

function snifMemoryLimitBytes(): int
{
    $value = trim((string) ini_get('memory_limit'));
    if ($value === '' || $value === '-1') {
        return PHP_INT_MAX;
    }
    $unit = strtolower(substr($value, -1));
    $number = (float) $value;
    if ($unit === 'g') {
        $number *= 1073741824;
    } elseif ($unit === 'm') {
        $number *= 1048576;
    } elseif ($unit === 'k') {
        $number *= 1024;
    }
    return $number > 0 ? (int) $number : PHP_INT_MAX;
}

function snifImageMime(string $extension): ?string
{
    $map = array(
        'gif' => 'image/gif',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'apng' => 'image/apng',
        'webp' => 'image/webp',
        'bmp' => 'image/bmp',
        'svg' => 'image/svg+xml',
        'avif' => 'image/avif',
        'ico' => 'image/x-icon'
    );
    return $map[$extension] ?? null;
}

function snifTimeAgo(int $timestamp, int $now): string
{
    $diff = $now - $timestamp;
    if ($diff < 60) {
        return translate('just now');
    }
    if ($diff < 3600) {
        return sprintf(translate('%d min ago'), (int) floor($diff / 60));
    }
    if ($diff < 86400) {
        return sprintf(translate('%d h ago'), (int) floor($diff / 3600));
    }
    if ($diff < 604800) {
        return sprintf(translate('%d d ago'), (int) floor($diff / 86400));
    }
    if ($diff < 2592000) {
        return sprintf(translate('%d w ago'), (int) floor($diff / 604800));
    }
    if ($diff < 31536000) {
        return sprintf(translate('%d mo ago'), (int) floor($diff / 2592000));
    }
    return sprintf(translate('%d y ago'), (int) floor($diff / 31536000));
}

function snifPlural(int $count, string $one, string $many): string
{
    return $count === 1 ? translate($one) : sprintf(translate($many), $count);
}

function snifCountLabel(int $count): string
{
    if ($count < 0) {
        return "\u{2014}";
    }
    return snifPlural($count, '1 item', '%d items');
}

function getDescription(string $filename): string
{
    global $descriptions, $descriptionFilenamesCaseSensitive;
    $key = $descriptionFilenamesCaseSensitive ? $filename : snifLower($filename);
    return $descriptions[$key] ?? '';
}

function getSvgIcon(string $type): string
{
    static $valid = array('folder', 'dirup', 'image', 'music', 'video', 'archive', 'text', 'HTML', 'binary', 'unknown');
    if (!in_array($type, $valid, true)) {
        $type = 'unknown';
    }
    return '<svg class="icon" aria-hidden="true" focusable="false"><use href="#i-' . $type . '"></use></svg>';
}

function snifUiIcon(string $name, string $class = 'icon'): string
{
    if (!preg_match('/^[a-z0-9-]+$/', $name)) {
        $name = 'unknown';
    }
    return '<svg class="' . snifH($class) . '" aria-hidden="true" focusable="false"><use href="#i-' . $name . '"></use></svg>';
}


/***************************************************************************/
/**  REQUEST HANDLING                                                     **/
/***************************************************************************/

$hiddenWildcards = $hiddenFilesWildcards;
if ($useDescriptionsFrom !== '') {
    $hiddenWildcards[] = $useDescriptionsFrom;
}
if ($externalStylesheet !== '') {
    $hiddenWildcards[] = $externalStylesheet;
}
$hiddenWildcards[] = '.';
$hiddenFilesWholeRegex = snifWildcardRegex($hiddenWildcards);

/* ---- Thumbnail endpoint ------------------------------------------------ */

function snifCacheDirectory(): ?string
{
    global $thumbnailCacheDir;
    if ($thumbnailCacheDir === '' || is_link($thumbnailCacheDir)) {
        return null;
    }
    if (!is_dir($thumbnailCacheDir)) {
        if (!@mkdir($thumbnailCacheDir, 0700, true) && !is_dir($thumbnailCacheDir)) {
            return null;
        }
        @chmod($thumbnailCacheDir, 0700);
    }
    if (is_link($thumbnailCacheDir) || !is_dir($thumbnailCacheDir) || !is_writable($thumbnailCacheDir)) {
        return null;
    }
    /* Refuse a shared temporary directory prepared by another local user. */
    if (function_exists('posix_geteuid') && function_exists('fileowner')) {
        $owner = @fileowner($thumbnailCacheDir);
        if ($owner !== false && $owner !== posix_geteuid()) {
            return null;
        }
    }
    return $thumbnailCacheDir;
}

function snifSendFile(string $path, string $mime, string $etag, int $mtime, int $maxAge): void
{
    $etag = '"' . $etag . '"';
    $ifNoneMatch = isset($_SERVER['HTTP_IF_NONE_MATCH']) && is_string($_SERVER['HTTP_IF_NONE_MATCH'])
        ? $_SERVER['HTTP_IF_NONE_MATCH'] : '';
    $notModified = false;
    if ($ifNoneMatch !== '') {
        foreach (explode(',', $ifNoneMatch) as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === $etag || $candidate === 'W/' . $etag) {
                $notModified = true;
                break;
            }
        }
    } elseif (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
        $since = strtotime((string) $_SERVER['HTTP_IF_MODIFIED_SINCE']);
        $notModified = $since !== false && $mtime <= $since;
    }

    snifHeader('Cache-Control: private, max-age=' . $maxAge . ', immutable');
    snifHeader('ETag: ' . $etag);
    snifHeader('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
    if ($notModified) {
        http_response_code(304);
        exit;
    }

    snifHeader('Content-Type: ' . $mime);
    snifHeader('Content-Length: ' . (string) filesize($path));
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
        exit;
    }
    readfile($path);
    exit;
}

function snifServeThumbnailSource(string $file, string $extension): void
{
    $mime = snifImageMime($extension);
    if ($mime === null) {
        http_response_code(404);
        exit;
    }
    if ($extension === 'svg') {
        snifHeader("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src data:; sandbox");
    }
    snifSendFile(
        $file,
        $mime,
        md5($file . '|' . (string) @filemtime($file) . '|' . (string) @filesize($file)),
        (int) @filemtime($file),
        604800
    );
}

function snifHandleThumbnail(string $requested): void
{
    global $snifRoot, $thumbnailWidth, $thumbnailHeight, $thumbnailQuality, $thumbnailMaxPixels,
           $thumbnailCacheMaxAge, $cacheThumbnails, $useWebPThumbnails;

    $file = snifResolveFile($snifRoot, $requested, true);
    if ($file === null) {
        http_response_code(404);
        exit;
    }
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mime = snifImageMime($extension);
    if ($mime === null) {
        http_response_code(404);
        exit;
    }

    /* Images GD cannot decode are served untouched (SVG, AVIF, ICO). */
    $generated = array('gif', 'jpg', 'jpeg', 'png', 'apng', 'webp', 'bmp');
    if (!function_exists('imagecreatetruecolor') || !in_array($extension, $generated, true)) {
        snifServeThumbnailSource($file, $extension);
    }

    $info = @getimagesize($file);
    if ($info === false || $info[0] < 1 || $info[1] < 1) {
        snifServeThumbnailSource($file, $extension);
    }
    $sourceWidth = (int) $info[0];
    $sourceHeight = (int) $info[1];
    if ($sourceWidth * $sourceHeight > $thumbnailMaxPixels) {
        snifServeThumbnailSource($file, $extension);
    }
    $memoryLimit = snifMemoryLimitBytes();
    if ($memoryLimit !== PHP_INT_MAX) {
        $budget = max(16 * 1048576, (int) ($memoryLimit / 2));
        if ($sourceWidth * $sourceHeight * 5 > $budget) {
            snifServeThumbnailSource($file, $extension);
        }
    }

    $sourceMtime = (int) @filemtime($file);
    $sourceSize = (int) @filesize($file);
    $useWebP = $useWebPThumbnails && function_exists('imagewebp');
    $format = $useWebP ? 'webp' : 'jpg';
    $cacheKey = sha1($file . '|' . $sourceMtime . '|' . $sourceSize . '|' . $thumbnailWidth . 'x' . $thumbnailHeight . '|' . $thumbnailQuality . '|' . $format . '|v3');
    $cacheDir = $cacheThumbnails ? snifCacheDirectory() : null;
    $cacheFile = $cacheDir !== null ? $cacheDir . '/' . $cacheKey . '.' . $format : null;

    if ($cacheFile !== null && is_file($cacheFile)) {
        snifSendFile($cacheFile, $useWebP ? 'image/webp' : 'image/jpeg', $cacheKey, (int) filemtime($cacheFile), 604800);
    }

    $source = null;
    switch ($extension) {
        case 'gif':  $source = function_exists('imagecreatefromgif') ? @imagecreatefromgif($file) : false; break;
        case 'jpg':
        case 'jpeg': $source = function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($file) : false; break;
        case 'png':
        case 'apng': $source = function_exists('imagecreatefrompng') ? @imagecreatefrompng($file) : false; break;
        case 'webp': $source = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file) : false; break;
        case 'bmp':  $source = function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($file) : false; break;
    }
    if (!$source) {
        snifServeThumbnailSource($file, $extension);
    }

    /* EXIF orientation for JPEG (the common camera case). */
    if (($extension === 'jpg' || $extension === 'jpeg') && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        if ($orientation === 3 || $orientation === 6 || $orientation === 8) {
            $angle = $orientation === 3 ? 180 : ($orientation === 6 ? -90 : 90);
            $background = imagecolorallocate($source, 0, 0, 0);
            $rotated = @imagerotate($source, $angle, $background);
            if ($rotated) {
                imagedestroy($source);
                $source = $rotated;
                $sourceWidth = imagesx($source);
                $sourceHeight = imagesy($source);
            }
        } elseif ($orientation === 2 && function_exists('imageflip')) {
            @imageflip($source, IMG_FLIP_HORIZONTAL);
        } elseif ($orientation === 4 && function_exists('imageflip')) {
            @imageflip($source, IMG_FLIP_VERTICAL);
        }
    }

    $boxWidth = max(1, (int) $thumbnailWidth);
    $boxHeight = max(1, (int) $thumbnailHeight);
    $scale = min($boxWidth / $sourceWidth, $boxHeight / $sourceHeight, 1.0);
    $thumbWidth = max(1, (int) round($sourceWidth * $scale));
    $thumbHeight = max(1, (int) round($sourceHeight * $scale));

    $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
    if (!$thumb) {
        imagedestroy($source);
        snifServeThumbnailSource($file, $extension);
    }

    if ($useWebP) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefilledrectangle($thumb, 0, 0, $thumbWidth, $thumbHeight, $transparent);
    } else {
        $white = imagecolorallocate($thumb, 255, 255, 255);
        imagefilledrectangle($thumb, 0, 0, $thumbWidth, $thumbHeight, $white);
    }
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $sourceWidth, $sourceHeight);
    imagedestroy($source);

    if ($cacheFile !== null) {
        $temporary = $cacheDir . '/' . $cacheKey . '.' . bin2hex(random_bytes(6)) . '.tmp';
        $ok = $useWebP
            ? @imagewebp($thumb, $temporary, $thumbnailQuality)
            : @imagejpeg($thumb, $temporary, $thumbnailQuality);
        if ($ok && is_file($temporary) && filesize($temporary) > 0) {
            @chmod($temporary, 0644);
            if (@rename($temporary, $cacheFile) && is_file($cacheFile)) {
                /* Opportunistic cleanup: ~2% of cache misses. */
                if (random_int(1, 50) === 1) {
                    $expire = time() - $thumbnailCacheMaxAge;
                    foreach ((array) @glob($cacheDir . '/*') as $old) {
                        if (is_file($old) && @filemtime($old) < $expire) {
                            @unlink($old);
                        }
                    }
                }
                imagedestroy($thumb);
                snifSendFile($cacheFile, $useWebP ? 'image/webp' : 'image/jpeg', $cacheKey, (int) filemtime($cacheFile), 604800);
            }
        }
        @unlink($temporary);
    }

    snifHeader('Content-Type: ' . ($useWebP ? 'image/webp' : 'image/jpeg'));
    snifHeader('Cache-Control: public, max-age=3600');
    if ($useWebP) {
        imagewebp($thumb, null, $thumbnailQuality);
    } else {
        imagejpeg($thumb, null, $thumbnailQuality);
    }
    imagedestroy($thumb);
    exit;
}

if (isset($_GET['thumbnail']) && is_string($_GET['thumbnail']) && $_GET['thumbnail'] !== '') {
    snifHandleThumbnail($_GET['thumbnail']);
}

/* ---- Resolve the requested directory ---------------------------------- */

if (!$allowSubDirs) {
    $path = '';
} else {
    $requestedPath = snifGet('path');
    $normalizedPath = $requestedPath === '' ? '' : snifNormalizeRelative($requestedPath);
    if ($normalizedPath === null) {
        $displayError[] = translate('Illegal path specified, ignoring.');
        $path = '';
    } else {
        $path = $normalizedPath;
    }
}

$targetPath = $path === '' ? $snifRoot : snifResolveDirectory($snifRoot, $path);
if ($targetPath === null) {
    $requestedForError = $path !== '' ? rtrim($path, '/') : snifGet('path');
    $displayError[] = sprintf(translate('%s is not a subdirectory of the current directory.'), $requestedForError);
    $path = '';
    $targetPath = $snifRoot;
}

/* ---- Download endpoint ------------------------------------------------- */

if (isset($_GET['download']) && is_string($_GET['download']) && $_GET['download'] !== '') {
    $downloadName = $_GET['download'];
    $downloadFile = snifResolveFile($snifRoot, ($path !== '' ? $path : '') . $downloadName);
    $downloadExtension = $downloadFile !== null ? strtolower(pathinfo($downloadFile, PATHINFO_EXTENSION)) : '';
    if ($downloadFile === null || (!$allowPHPDownloads && snifIsBlockedDownload($downloadExtension))) {
        http_response_code(404);
        $displayError[] = sprintf(translate('File not found: %s'), $downloadName);
    } else {
        $displayName = basename($downloadName);
        $asciiName = preg_replace('/[^\x20-\x7E]/', '_', $displayName);
        $asciiName = str_replace(array('\\', '"'), '_', (string) $asciiName);
        snifHeader('Content-Type: application/octet-stream');
        snifHeader('Content-Length: ' . (string) filesize($downloadFile));
        snifHeader('Content-Disposition: attachment; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($displayName));
        snifHeader('Cache-Control: private, max-age=0, must-revalidate');
        snifHeader('X-Content-Type-Options: nosniff');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
            $handle = @fopen($downloadFile, 'rb');
            if ($handle !== false) {
                fpassthru($handle);
                fclose($handle);
            }
        }
        exit;
    }
}


/***************************************************************************/
/**  DESCRIPTIONS / LISTING                                               **/
/***************************************************************************/

if ($useDescriptionsFrom !== '' && $separationString !== '') {
    $descriptionsFile = $targetPath . DIRECTORY_SEPARATOR . $useDescriptionsFrom;
    if (is_file($descriptionsFile) && is_readable($descriptionsFile)) {
        $lines = @file($descriptionsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                $parts = explode($separationString, $line, 2);
                if (count($parts) < 2) {
                    continue;
                }
                $key = $descriptionFilenamesCaseSensitive ? $parts[0] : snifLower($parts[0]);
                $descriptions[$key] = snifUtf8(trim($parts[1]));
            }
        }
    }
}

$sort = strtolower(snifGet('sort'));
if (!in_array($sort, array('name', 'type', 'size', 'date', 'description'), true)) {
    $sort = 'name';
}
$order = strtolower(snifGet('order')) === 'desc' ? 'desc' : 'asc';

if ($useDescriptionsFrom === '') {
    $displayColumns = array_values(array_diff((array) $displayColumns, array('description')));
}
$showType = in_array('type', (array) $displayColumns, true);
$showSize = in_array('size', (array) $displayColumns, true);
$showDate = in_array('date', (array) $displayColumns, true);
$showDescription = in_array('description', (array) $displayColumns, true);

$entries = @scandir($targetPath, SCANDIR_SORT_NONE);
if ($entries === false) {
    $displayError[] = translate('Unable to read this directory.');
    $entries = array();
}

$files = array();
$fileCount = 0;
$dirCount = 0;
$entryCount = 0;
$totalSize = 0;
$dateFormat = translate('DATEFORMAT');

/* Parent link (kept out of pagination so it is always available). */
$backEntry = null;
if ($path !== '') {
    $parentPath = trim(dirname('/' . trim($path, '/')), '/');
    $backEntry = array(
        'name' => '..',
        'displayName' => $useBackForDirUp ? translate('[ back ]') : '..',
        'isDirectory' => true,
        'isBack' => true,
        'isLink' => false,
        'type' => '',
        'filetype' => 'dirup',
        'size' => 0,
        'niceSize' => '',
        'sizeTitle' => '',
        'sortSize' => 0,
        'date' => 0,
        'shortDate' => '',
        'fullDate' => '',
        'timeAgo' => '',
        'description' => '',
        'searchBlob' => '',
        'link' => $parentPath === '' ? $snifSelf : snifQueryUrl(array('path' => $parentPath . '/')),
        'downloadUrl' => '',
        'thumbnailUrl' => null,
        'isDownloadable' => false,
        'previewable' => false,
        'playable' => false,
        'imageIndex' => null,
        'audioIndex' => null
    );
}

foreach ($entries as $entry) {
    if ($entry === '.' || $entry === '..') {
        continue;
    }
    if (snifNameIsHidden($entry)) {
        continue;
    }
    $full = $targetPath . DIRECTORY_SEPARATOR . $entry;
    $isLink = is_link($full);
    $isDirectory = is_dir($full);

    if ($isLink) {
        $realLink = realpath($full);
        if ($realLink === false || !snifIsWithin($realLink, $snifRoot)) {
            continue;
        }
    }
    if ($hideSelf && $entry === $snifScript && realpath($full) === $snifSelfReal) {
        continue;
    }

    if ($isDirectory) {
        if (!$allowSubDirs) {
            continue;
        }
        if ($protectDirsWithHtaccess && snifDirectoryIsProtected($full)) {
            continue;
        }
        $itemCount = -1;
        if ($showDirItemCounts) {
            $itemCount = 0;
            $children = @scandir($full, SCANDIR_SORT_NONE);
            if (is_array($children)) {
                foreach ($children as $child) {
                    if ($child === '.' || $child === '..' || snifNameIsHidden($child)) {
                        continue;
                    }
                    $itemCount++;
                }
            } else {
                $itemCount = -1;
            }
        }
        $mtime = (int) @filemtime($full);
        $description = getDescription($entry);
        $displayName = iTrunc($entry, $truncateLength);
        $files[] = array(
            'name' => $entry,
            'displayName' => $displayName,
            'isDirectory' => true,
            'isBack' => false,
            'isLink' => $isLink,
            'type' => '',
            'filetype' => 'folder',
            'size' => 0,
            'niceSize' => $itemCount >= 0 ? snifCountLabel($itemCount) : "\u{2014}",
            'sizeTitle' => $itemCount >= 0 ? snifCountLabel($itemCount) : '',
            'sortSize' => $itemCount >= 0 ? $itemCount : 0,
            'date' => $mtime,
            'shortDate' => $mtime > 0 ? date($dateFormat, $mtime) : '',
            'fullDate' => $mtime > 0 ? date('Y-m-d H:i:s T', $mtime) : '',
            'timeAgo' => $mtime > 0 ? snifTimeAgo($mtime, $snifNow) : '',
            'description' => $description,
            'searchBlob' => snifLower($entry . ' ' . $description),
            'sortName' => snifLower($entry),
            'sortType' => 'folder',
            'sortDescription' => snifLower($description),
            'link' => snifDirUrl($path . $entry),
            'downloadUrl' => '',
            'thumbnailUrl' => null,
            'isDownloadable' => false,
            'previewable' => false,
            'playable' => false,
            'imageIndex' => null,
            'audioIndex' => null
        );
        $dirCount++;
        $entryCount++;
        continue;
    }

    if (!is_file($full)) {
        continue;
    }

    $stat = @stat($full);
    if ($stat === false) {
        continue;
    }
    $mtime = (int) $stat['mtime'];
    $size = (int) $stat['size'];
    $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
    $filetype = snifFileType($extension);
    $previewable = $filetype === 'image' && snifIsPreviewableImage($extension);
    $playable = $filetype === 'music' && snifIsPlayableAudio($extension);
    $description = getDescription($entry);
    $displayName = iTrunc($entry, $truncateLength);

    $files[] = array(
        'name' => $entry,
        'displayName' => $displayName,
        'isDirectory' => false,
        'isBack' => false,
        'isLink' => $isLink,
        'type' => $extension,
        'filetype' => $filetype,
        'size' => $size,
        'niceSize' => niceSize($size),
        'sizeTitle' => number_format($size, 0, '.', ',') . ' ' . translate('Bytes'),
        'sortSize' => $size,
        'date' => $mtime,
        'shortDate' => $mtime > 0 ? date($dateFormat, $mtime) : '',
        'fullDate' => $mtime > 0 ? date('Y-m-d H:i:s T', $mtime) : '',
        'timeAgo' => $mtime > 0 ? snifTimeAgo($mtime, $snifNow) : '',
        'description' => $description,
        'searchBlob' => snifLower($entry . ' ' . $extension . ' ' . $filetype . ' ' . $description),
        'sortName' => snifLower($entry),
        'sortType' => snifLower($filetype . ' ' . $extension),
        'sortDescription' => snifLower($description),
        'link' => snifFileUrl($path . $entry),
        'downloadUrl' => snifDownloadUrl($entry),
        'thumbnailUrl' => ($useAutoThumbnails && $previewable) ? snifThumbUrl($path . $entry, $mtime) : null,
        'isDownloadable' => $allowPHPDownloads || !snifIsBlockedDownload($extension),
        'previewable' => $previewable,
        'playable' => $playable,
        'imageIndex' => null,
        'audioIndex' => null
    );
    $fileCount++;
    $entryCount++;
    $totalSize += $size;
}

usort($files, function (array $a, array $b) use ($sort, $order): int {
    if ($a['isDirectory'] !== $b['isDirectory']) {
        return $a['isDirectory'] ? -1 : 1;
    }

    switch ($sort) {
        case 'size':
            $comparison = $a['sortSize'] <=> $b['sortSize'];
            break;
        case 'date':
            $comparison = $a['date'] <=> $b['date'];
            break;
        case 'type':
            $comparison = strnatcmp($a['sortType'], $b['sortType']);
            break;
        case 'description':
            $comparison = strnatcmp($a['sortDescription'], $b['sortDescription']);
            break;
        case 'name':
        default:
            $comparison = strnatcmp($a['sortName'], $b['sortName']);
            break;
    }
    if ($comparison === 0) {
        $comparison = strnatcmp($a['name'], $b['name']);
    }
    return $order === 'desc' ? -$comparison : $comparison;
});

$totalFiles = count($files);
$pageSize = max(0, (int) $usePaging);
$pageStart = max(0, (int) snifGet('start'));
if ($pageSize > 0) {
    $pageCount = max(1, (int) ceil($totalFiles / $pageSize));
    $pageStart = min($pageStart, ($pageCount - 1) * $pageSize);
} else {
    $pageCount = 1;
    $pageStart = 0;
}
$pagingInEffect = $pageSize > 0 && $totalFiles > $pageSize;
$currentPage = $pageSize > 0 ? (int) floor($pageStart / $pageSize) + 1 : 1;
$pageFiles = array_slice($files, $pageStart, $pageSize > 0 ? $pageSize : $totalFiles);
$displayFiles = $pageFiles;
if ($backEntry !== null) {
    array_unshift($displayFiles, $backEntry);
}

/* Build the small payload used by the JS grid, audio playlist and lightbox. */
$jsItems = array();
$jsImages = array();
$jsAudio = array();
foreach ($displayFiles as &$file) {
    if (!empty($file['isBack'])) {
        $jsItems[] = array(
            'name' => '..',
            'short' => $file['displayName'],
            'url' => $file['link'],
            'type' => 'dirup',
            'meta' => '',
            'thumb' => null,
            'search' => '',
            'back' => true,
            'imageIndex' => null,
            'audioIndex' => null
        );
        continue;
    }
    $imageIndex = null;
    if ($file['previewable']) {
        $imageIndex = count($jsImages);
        $jsImages[] = array('url' => $file['link'], 'name' => $file['name']);
    }
    $audioIndex = null;
    if ($file['playable']) {
        $audioIndex = count($jsAudio);
        $jsAudio[] = array(
            'url' => $file['link'],
            'name' => $file['name'],
            'mime' => snifAudioMime($file['type']) ?? ''
        );
    }
    $file['imageIndex'] = $imageIndex;
    $file['audioIndex'] = $audioIndex;
    $jsItems[] = array(
        'name' => $file['name'],
        'short' => $file['displayName'],
        'url' => $file['link'],
        'type' => $file['filetype'],
        'meta' => $file['niceSize'],
        'thumb' => $file['thumbnailUrl'],
        'search' => $file['searchBlob'],
        'back' => false,
        'imageIndex' => $imageIndex,
        'audioIndex' => $audioIndex
    );
}
unset($file);

$jsPayload = array(
    'items' => $jsItems,
    'images' => $jsImages,
    'audio' => $jsAudio
);

/*
 * The page embeds a fresh CSP nonce on every request. Never allow the HTML
 * to be cached or answered with 304: the cached body would carry the old
 * nonce while the new response would carry a different CSP header.
 */
snifHeader('Cache-Control: private, no-store, max-age=0, must-revalidate');
snifHeader('Pragma: no-cache');
snifHeader('Vary: Accept-Language');
snifHeader('Content-Type: text/html; charset=UTF-8');

$directoryDescription = $descriptions['.'] ?? '';
$segments = $path === '' ? array() : explode('/', trim($path, '/'));
$pageTitle = translate('Index of') . ' /' . ($path === '' ? '' : trim($path, '/'));
$i18nPayload = array(
    'linkCopied' => translate('Link copied'),
    'copyFailed' => translate('Could not copy the link.'),
    'noResults' => translate('No files match your search.'),
    'play' => translate('Play'),
    'pause' => translate('Pause'),
    'previous' => translate('Previous'),
    'next' => translate('Next'),
    'close' => translate('Close'),
    'mute' => translate('Mute'),
    'unmute' => translate('Unmute'),
    'couldNotPlay' => translate('Could not play this file.'),
    'audioUnsupported' => translate('This audio format is not supported by your browser.'),
    'showing' => translate('Showing %d of %d'),
    'toggleTheme' => translate('Toggle theme'),
    'search' => translate('Search'),
    'gridView' => translate('Grid view'),
    'listView' => translate('List view'),
    'shortcuts' => translate('Keyboard shortcuts')
);
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;
$jsonPayload = json_encode($jsPayload, $jsonFlags);
$i18nJson = json_encode($i18nPayload, $jsonFlags);
if ($jsonPayload === false) {
    $jsonPayload = '{"items":[],"images":[],"audio":[]}';
}
if ($i18nJson === false) {
    $i18nJson = '{}';
}
$searchPlaceholder = $usePaging > 0 ? translate('Search this page...') : translate('Search files...');
?>
<!DOCTYPE html>
<!--
  YAFI - Yet Another File Indexer
  Author / maintainer: Antoine Santo aka N0NameN0
  Original project: snif 1.5.2 by Kai Blankenhorn (www.bitfolge.de)
  License: GPL-2.0-or-later

  A single-file PHP file browser with audio and image players.
-->
<html lang="<?= snifH(snifLanguage()) ?>" data-theme="<?= $defaultTheme === 'light' ? 'light' : 'dark' ?>" data-view="list">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="dark light">
<meta name="theme-color" id="themeColor" content="<?= $defaultTheme === 'light' ? '#f5f6fa' : '#08080d' ?>">
<meta name="generator" content="YAFI <?= snifH($snifVersion) ?> - Yet Another File Indexer">
<?php if (!$allowIndexing): ?><meta name="robots" content="noindex, nofollow"><?php endif; ?>
<title><?= snifH($pageTitle) ?> - <?= snifH($snifServer) ?></title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%238b93ff' d='M3 7.5A2.5 2.5 0 0 1 5.5 5H9l2 2h7.5A2.5 2.5 0 0 1 21 9.5v8A2.5 2.5 0 0 1 18.5 20h-13A2.5 2.5 0 0 1 3 17.5z'/%3E%3C/svg%3E">
<script nonce="<?= snifH($snifNonce) ?>">
(function () {
    var root = document.documentElement;
    try {
        var theme = localStorage.getItem('snif-theme');
        if (theme !== 'dark' && theme !== 'light') {
            theme = <?= json_encode($defaultTheme, $jsonFlags) ?>;
            if (theme === 'auto') {
                theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
            }
        }
        root.dataset.theme = theme === 'light' ? 'light' : 'dark';
        var view = localStorage.getItem('snif-view');
        if (view !== 'grid' && view !== 'list') {
            view = <?= json_encode($defaultView, $jsonFlags) ?>;
        }
        if (view === 'grid') {
            root.dataset.view = 'grid';
        }
    } catch (e) {}
})();
</script>
<!-- Application styles are inline to keep the deployment to one file. -->
<style nonce="<?= snifH($snifNonce) ?>">
:root {
  --bg: #08080d;
  --bg-soft: #0c0c14;
  --card: rgba(17, 17, 27, 0.82);
  --card-solid: #111119;
  --row-a: rgba(255, 255, 255, 0.018);
  --row-b: rgba(255, 255, 255, 0.035);
  --row-hover: rgba(139, 147, 255, 0.10);
  --text: #e7e9f0;
  --muted: #a3abba;
  --dim: #7e8798;
  --border: rgba(255, 255, 255, 0.075);
  --border-strong: rgba(255, 255, 255, 0.16);
  --accent: #8b93ff;
  --accent-2: #38bdf8;
  --accent-soft: rgba(139, 147, 255, 0.13);
  --danger: #f87171;
  --shadow: 0 18px 50px rgba(0, 0, 0, 0.42);
  --radius: 14px;
  --radius-sm: 9px;
  --font: "Inter", ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  --font-mono: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  --c-folder: #fbbf24;
  --c-image: #f472b6;
  --c-music: #34d399;
  --c-video: #fb7185;
  --c-archive: #c084fc;
  --c-text: #60a5fa;
  --c-html: #fb923c;
  --c-binary: #94a3b8;
  --c-unknown: #7c8595;
  --c-dirup: #8b93ff;
  --desc-max: <?= $descriptionColumnWidth > 0 ? (int) $descriptionColumnWidth : 360 ?>px;
  color-scheme: dark;
}
html[data-theme="light"] {
  --bg: #f5f6fa;
  --bg-soft: #eceef5;
  --card: rgba(255, 255, 255, 0.86);
  --card-solid: #ffffff;
  --row-a: rgba(10, 15, 30, 0.016);
  --row-b: rgba(10, 15, 30, 0.038);
  --row-hover: rgba(79, 70, 229, 0.075);
  --text: #171a22;
  --muted: #515b6b;
  --dim: #667085;
  --border: rgba(15, 23, 42, 0.09);
  --border-strong: rgba(15, 23, 42, 0.18);
  --accent: #4f46e5;
  --accent-2: #0284c7;
  --accent-soft: rgba(79, 70, 229, 0.10);
  --danger: #dc2626;
  --shadow: 0 18px 50px rgba(15, 23, 42, 0.10);
  --c-folder: #b45309;
  --c-image: #be185d;
  --c-music: #047857;
  --c-video: #be123c;
  --c-archive: #7e22ce;
  --c-text: #1d4ed8;
  --c-html: #c2410c;
  --c-binary: #475569;
  --c-unknown: #64748b;
  --c-dirup: #4f46e5;
  color-scheme: light;
}
*, *::before, *::after { box-sizing: border-box; }
html { scroll-behavior: smooth; }
body {
  margin: 0;
  min-height: 100vh;
  font-family: var(--font);
  font-size: 14px;
  line-height: 1.55;
  color: var(--text);
  background:
    radial-gradient(1100px 500px at 15% -10%, var(--accent-soft), transparent 65%),
    radial-gradient(900px 480px at 100% 0%, rgba(56, 189, 248, 0.08), transparent 60%),
    var(--bg);
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
}
[hidden] { display: none !important; }
a { color: var(--accent); }
button, input { font: inherit; }
button { color: inherit; }
.sr-only {
  position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
  overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;
}
.sprite { position: absolute; width: 0; height: 0; overflow: hidden; }
.icon {
  width: 1.15em; height: 1.15em; flex: 0 0 auto;
  fill: none; stroke: currentColor; stroke-width: 1.8;
  stroke-linecap: round; stroke-linejoin: round;
}
.icon-fill { fill: currentColor; stroke: none; }
.app {
  width: min(1480px, 100%);
  margin: 0 auto;
  padding: 18px;
  transition: padding-bottom 0.3s ease;
}
body.audio-open .app { padding-bottom: 128px; }
body.dialog-open { overflow: hidden; }

/* ---- header ---- */
.header {
  background: var(--card);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 16px 20px;
  margin-bottom: 12px;
}
.header-top { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; }
.brand { display: inline-flex; align-items: center; gap: 9px; color: var(--text); text-decoration: none; min-width: 0; }
.brand-mark {
  display: inline-flex; align-items: center; justify-content: center;
  width: 34px; height: 34px; border-radius: 10px;
  color: var(--accent);
  background: var(--accent-soft);
  border: 1px solid var(--border);
}
.brand-mark .icon { width: 19px; height: 19px; }
.brand-name { font-weight: 650; letter-spacing: -0.01em; font-size: 15px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.header-actions { display: flex; align-items: center; gap: 6px; }
.icon-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 34px; height: 34px; padding: 0;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: transparent; color: var(--muted);
  cursor: pointer; text-decoration: none;
  transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}
.icon-btn:hover { background: var(--row-hover); border-color: var(--border-strong); color: var(--text); }
.icon-btn:focus-visible, .btn:focus-visible, .file-link:focus-visible, .sort-link:focus-visible,
.brand:focus-visible, .crumb:focus-visible, .pager-btn:focus-visible, .footer-link:focus-visible,
input:focus-visible, .grid-item:focus-visible, .audio-play:focus-visible, .lightbox-nav:focus-visible,
.track-play:focus-visible {
  outline: 2px solid var(--accent); outline-offset: 2px;
}
html[data-theme="light"] .icon-moon { display: none; }
html[data-theme="dark"] .icon-sun { display: none; }
.breadcrumb { display: flex; align-items: center; gap: 3px; flex-wrap: wrap; font-size: 13px; margin-top: 12px; min-width: 0; }
.crumb { color: var(--muted); text-decoration: none; padding: 2px 6px; border-radius: 6px; transition: background 0.15s ease, color 0.15s ease; }
.crumb:hover { background: var(--accent-soft); color: var(--accent); }
.crumb.current { color: var(--text); font-weight: 600; }
.crumb.home { display: inline-flex; align-items: center; padding: 4px 6px; color: var(--accent); }
.sep { color: var(--dim); font-size: 11px; user-select: none; }
.dir-desc { margin: 10px 0 0; font-size: 13px; color: var(--muted); }
.errors { display: grid; gap: 8px; margin-bottom: 12px; }
.error-banner {
  padding: 10px 14px; border-radius: var(--radius-sm);
  border: 1px solid rgba(239, 68, 68, 0.35);
  background: rgba(239, 68, 68, 0.12);
  border-color: color-mix(in srgb, var(--danger) 35%, transparent);
  background-color: color-mix(in srgb, var(--danger) 12%, transparent);
  color: var(--danger); font-size: 13px;
}

/* ---- toolbar ---- */
.toolbar {
  display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
  padding: 10px 12px; margin-bottom: 12px;
  background: var(--card);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
}
.search-box { position: relative; flex: 1 1 260px; min-width: 180px; }
.search-box .icon {
  position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
  width: 15px; height: 15px; color: var(--dim); pointer-events: none;
}
.search-box input {
  width: 100%; height: 36px;
  padding: 0 42px 0 34px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--bg-soft);
  color: var(--text); font-size: 13px;
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.search-box input::placeholder { color: var(--dim); }
.search-box input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }
.search-box input::-webkit-search-cancel-button { filter: grayscale(1); }
.search-kbd {
  position: absolute; right: 9px; top: 50%; transform: translateY(-50%);
  padding: 1px 6px; border: 1px solid var(--border); border-radius: 5px;
  font-family: var(--font-mono); font-size: 10px; color: var(--dim);
  background: var(--card-solid); pointer-events: none;
}
.stats { display: flex; align-items: center; gap: 6px; color: var(--dim); font-size: 12px; white-space: nowrap; font-variant-numeric: tabular-nums; }
.stats .dot { opacity: 0.6; }
.view-toggle { display: inline-flex; border: 1px solid var(--border); border-radius: var(--radius-sm); overflow: hidden; }
.view-toggle .icon-btn { border: 0; border-radius: 0; width: 34px; height: 32px; }
.view-toggle .icon-btn + .icon-btn { border-left: 1px solid var(--border); }
.view-toggle .icon-btn[aria-pressed="true"] { background: var(--accent-soft); color: var(--accent); }

/* ---- table ---- */
.table-wrap {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  overflow: clip;
}
.file-table { width: 100%; border-collapse: collapse; }
.file-table.auto { width: auto; }
.file-table caption { text-align: left; }
.file-table thead th {
  position: sticky; top: 0; z-index: 2;
  padding: 9px 14px;
  font-size: 10.5px; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase;
  color: var(--dim); background: var(--card-solid);
  border-bottom: 1px solid var(--border);
  white-space: nowrap; text-align: left;
}
.file-table thead th.col-size { text-align: right; }
.sort-link {
  display: inline-flex; align-items: center; gap: 4px;
  color: inherit; text-decoration: none; border-radius: 4px;
}
.sort-link:hover { color: var(--accent); }
.col-size .sort-link { justify-content: flex-end; }
.sort-icon { width: 12px; height: 12px; color: var(--accent); }
.file-table tbody tr { border-bottom: 1px solid var(--border); transition: background 0.12s ease; }
.file-table tbody tr:nth-child(odd) { background: var(--row-a); }
.file-table tbody tr:nth-child(even) { background: var(--row-b); }
.file-table tbody tr:hover { background: var(--row-hover); }
.file-table tbody tr.is-playing-audio { background: var(--accent-soft); }
.file-table tbody tr:last-child { border-bottom: 0; }
.file-table td { padding: 7px 14px; font-size: 13px; vertical-align: middle; white-space: nowrap; }
.file-table td.col-icon { width: 42px; padding-right: 4px; color: var(--tc, var(--c-unknown)); }
.file-table td.col-icon .icon { width: 21px; height: 21px; display: block; }
.file-table td.col-name { min-width: 0; width: 55%; }
.file-table td.col-type { color: var(--dim); font-family: var(--font-mono); font-size: 11px; }
.file-table td.col-size { text-align: right; color: var(--muted); font-family: var(--font-mono); font-size: 11.5px; font-variant-numeric: tabular-nums; }
.file-table td.col-date { color: var(--muted); font-size: 12px; }
.file-table td.col-desc { color: var(--muted); font-size: 12px; white-space: normal; max-width: var(--desc-max); }
.file-table td.col-actions { width: 84px; text-align: right; }
.file-table .name-cell { display: flex; align-items: center; gap: 7px; min-width: 0; }
.track-play {
  display: inline-flex; align-items: center; justify-content: center;
  width: 24px; height: 24px; padding: 0; flex: 0 0 auto;
  border: 1px solid transparent;
  border-radius: 50%;
  background: var(--accent-soft);
  color: var(--accent);
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease, transform 0.15s ease;
}
.track-play:hover { background: var(--accent); border-color: var(--accent); color: #fff; transform: scale(1.06); }
.track-play .icon { width: 11px; height: 11px; }
.track-play .icon-track-pause { display: none; }
.track-play.is-playing { background: var(--accent); border-color: var(--accent); color: #fff; }
.track-play.is-playing .icon-track-play { display: none; }
.track-play.is-playing .icon-track-pause { display: block; }
.file-link {
  color: var(--text); text-decoration: none; font-weight: 520;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  max-width: 100%;
}
.file-link:hover { color: var(--accent); }
.tag {
  flex: 0 0 auto; padding: 0 5px; border: 1px solid var(--border);
  border-radius: 5px; font-size: 9.5px; line-height: 1.5; text-transform: uppercase;
  letter-spacing: 0.04em; color: var(--dim);
}
.actions { display: inline-flex; align-items: center; justify-content: flex-end; gap: 2px; }
.actions .icon-btn { width: 28px; height: 28px; border-color: transparent; background: transparent; }
.actions .icon-btn:hover { border-color: var(--border); background: var(--row-hover); }
.actions .icon { width: 15px; height: 15px; }
.file-row.t-folder { --tc: var(--c-folder); }
.file-row.t-dirup { --tc: var(--c-dirup); }
.file-row.t-image { --tc: var(--c-image); }
.file-row.t-music { --tc: var(--c-music); }
.file-row.t-video { --tc: var(--c-video); }
.file-row.t-archive { --tc: var(--c-archive); }
.file-row.t-text { --tc: var(--c-text); }
.file-row.t-HTML { --tc: var(--c-html); }
.file-row.t-binary { --tc: var(--c-binary); }
.file-row.t-unknown { --tc: var(--c-unknown); }
.file-row.is-back .file-link { color: var(--muted); font-weight: 500; }

/* ---- grid ---- */
.view-grid { display: none; }
html[data-view="grid"] .view-list { display: none; }
html[data-view="grid"] .view-grid { display: block; }
.file-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(172px, 1fr));
  gap: 11px;
}
.file-grid-item {
  position: relative;
  display: flex; flex-direction: column; gap: 8px;
  min-width: 0; padding: 11px;
  color: var(--text); text-decoration: none;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  transition: transform 0.15s ease, border-color 0.15s ease, background 0.15s ease;
}
.file-grid-item:hover {
  transform: translateY(-2px);
  background: var(--bg-soft);
  border-color: var(--border-strong);
}
.file-grid-item .grid-icon {
  display: flex; align-items: center; justify-content: center;
  width: 100%; aspect-ratio: 5 / 2;
  border-radius: var(--radius-sm);
  background: var(--bg-soft);
  color: var(--tc, var(--c-unknown));
}
.file-grid-item .grid-icon .icon { width: 34px; height: 34px; }
.file-grid-item .grid-thumb {
  display: block; width: 100%; aspect-ratio: 5 / 2;
  object-fit: cover; border-radius: var(--radius-sm);
  background: var(--bg-soft);
}
.file-grid-item .grid-name {
  font-size: 12.5px; font-weight: 600; line-height: 1.35;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.file-grid-item .grid-meta { font-size: 10.5px; color: var(--dim); font-variant-numeric: tabular-nums; }
.grid-play {
  position: absolute; top: 8px; right: 8px;
  display: inline-flex; align-items: center; justify-content: center;
  width: 25px; height: 25px; border-radius: 50%;
  background: var(--accent); color: #fff;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.28);
  pointer-events: none;
}
.grid-play .icon { width: 11px; height: 11px; }
.grid-play .icon-track-pause { display: none; }
.file-grid-item.is-playing-audio {
  background: var(--accent-soft);
  border-color: var(--accent);
}
.file-grid-item.is-playing-audio .grid-play .icon-track-play { display: none; }
.file-grid-item.is-playing-audio .grid-play .icon-track-pause { display: block; }
.grid-item.t-folder { --tc: var(--c-folder); }
.grid-item.t-dirup { --tc: var(--c-dirup); }
.grid-item.t-image { --tc: var(--c-image); }
.grid-item.t-music { --tc: var(--c-music); }
.grid-item.t-video { --tc: var(--c-video); }
.grid-item.t-archive { --tc: var(--c-archive); }
.grid-item.t-text { --tc: var(--c-text); }
.grid-item.t-HTML { --tc: var(--c-html); }
.grid-item.t-binary { --tc: var(--c-binary); }
.grid-item.t-unknown { --tc: var(--c-unknown); }
/* ---- empty / pager / footer ---- */
.empty-state, .no-results {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 10px; padding: 54px 20px; margin-top: 12px;
  color: var(--dim); text-align: center;
  border: 1px dashed var(--border-strong);
  border-radius: var(--radius);
}
.empty-state .icon, .no-results .icon { width: 38px; height: 38px; opacity: 0.5; }
.empty-state p, .no-results p { margin: 0; font-size: 13px; }
.pager { display: flex; align-items: center; justify-content: center; gap: 12px; margin-top: 14px; flex-wrap: wrap; }
.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  min-height: 34px; padding: 6px 12px;
  border: 1px solid var(--border); border-radius: var(--radius-sm);
  background: var(--card); color: var(--muted);
  font-size: 12.5px; text-decoration: none; cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}
.btn:hover { background: var(--row-hover); border-color: var(--border-strong); color: var(--text); }
.btn.is-disabled { opacity: 0.42; pointer-events: none; }
.pager-info { font-size: 12px; color: var(--dim); font-variant-numeric: tabular-nums; }
.footer {
  display: flex; align-items: center; justify-content: center; gap: 8px; flex-wrap: wrap;
  padding: 20px 6px 8px; font-size: 11px; color: var(--dim);
}
.footer-link { color: var(--dim); text-decoration: none; }
.footer-link:hover { color: var(--accent); text-decoration: underline; }

/* ---- audio player ---- */
.audio-player {
  position: fixed; left: 0; right: 0; bottom: 0; z-index: 60;
  background: var(--card-solid);
  background: color-mix(in srgb, var(--card-solid) 92%, transparent);
  backdrop-filter: blur(24px);
  -webkit-backdrop-filter: blur(24px);
  border-top: 1px solid var(--border-strong);
  box-shadow: 0 -18px 50px rgba(0, 0, 0, 0.35);
  transform: translateY(105%);
  visibility: hidden;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.3s;
}
.audio-player.active { transform: translateY(0); visibility: visible; }
.audio-wave { display: block; width: 100%; height: 44px; }
.audio-controls { display: flex; align-items: center; gap: 10px; padding: 7px 14px 11px; }
.audio-play {
  display: inline-flex; align-items: center; justify-content: center;
  width: 38px; height: 38px; flex: 0 0 auto;
  border: 0; border-radius: 50%;
  background: var(--accent); color: #fff; cursor: pointer;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.audio-play:hover { transform: scale(1.06); box-shadow: 0 0 20px var(--accent-soft); }
.audio-play .icon { width: 16px; height: 16px; }
.audio-player .icon-pause, .audio-player .icon-mute { display: none; }
.audio-player.is-playing .icon-play, .audio-player.is-muted .icon-volume { display: none; }
.audio-player.is-playing .icon-pause, .audio-player.is-muted .icon-mute { display: block; }
.audio-track { min-width: 0; flex: 0 1 220px; }
.audio-track-name { display: block; font-size: 12.5px; font-weight: 650; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.audio-track-type { display: block; font-size: 10px; color: var(--dim); letter-spacing: 0.06em; }
.audio-progress { display: flex; align-items: center; gap: 8px; flex: 1 1 260px; min-width: 130px; }
.audio-time { font-family: var(--font-mono); font-size: 10.5px; color: var(--dim); min-width: 34px; text-align: center; }
input[type="range"].range {
  -webkit-appearance: none; appearance: none;
  width: 100%; height: 18px; background: transparent; cursor: pointer; accent-color: var(--accent);
}
input[type="range"].range::-webkit-slider-runnable-track { height: 3px; border-radius: 2px; background: var(--border-strong); }
input[type="range"].range::-webkit-slider-thumb {
  -webkit-appearance: none; appearance: none; width: 11px; height: 11px; margin-top: -4px;
  border: 0; border-radius: 50%; background: var(--accent);
}
input[type="range"].range::-moz-range-track { height: 3px; border-radius: 2px; background: var(--border-strong); }
input[type="range"].range::-moz-range-thumb { width: 11px; height: 11px; border: 0; border-radius: 50%; background: var(--accent); }
.audio-volume { display: flex; align-items: center; gap: 5px; width: 104px; flex: 0 0 auto; }
.audio-volume .icon-btn { width: 28px; height: 28px; border-color: transparent; }
.audio-volume .icon { width: 15px; height: 15px; }
.audio-close { flex: 0 0 auto; }
.audio-player .icon-btn { color: var(--muted); }
.audio-player .icon-btn:hover { color: var(--text); }

/* ---- dialogs ---- */
dialog { color: var(--text); }
dialog::backdrop { background: rgba(4, 5, 10, 0.62); backdrop-filter: blur(4px); }
.lightbox {
  width: 100vw; height: 100vh; height: 100dvh; max-width: none; max-height: none;
  margin: 0; padding: 0; border: 0; overflow: hidden;
  background: rgba(5, 6, 11, 0.95);
  color: #f4f5f8;
}
.lightbox[open] { display: flex; }
.lightbox-stage {
  position: relative; display: flex; align-items: center; justify-content: center;
  width: 100%; height: 100%; overflow: hidden;
  padding: 62px 18px 48px;
}
.lightbox-bar {
  position: absolute; top: 0; left: 0; right: 0; z-index: 3;
  display: flex; align-items: center; justify-content: space-between; gap: 14px;
  padding: 12px 16px;
  background: linear-gradient(to bottom, rgba(0, 0, 0, 0.55), transparent);
}
.lightbox-caption { font-size: 13px; font-weight: 600; color: #f4f5f8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.lightbox-actions { display: flex; align-items: center; gap: 6px; flex: 0 0 auto; }
.lightbox .icon-btn { color: #d9dce6; border-color: rgba(255, 255, 255, 0.16); }
.lightbox .icon-btn:hover { background: rgba(255, 255, 255, 0.12); color: #fff; }
#lightboxImg {
  flex: 0 0 auto;
  max-width: calc(100vw - 150px); max-height: calc(100dvh - 130px);
  object-fit: contain; border-radius: 0;
  user-select: none; -webkit-user-drag: none; touch-action: none;
}
#lightboxImg.is-zoomed {
  max-width: none; max-height: none;
  cursor: grab;
  image-rendering: -moz-crisp-edges;
  image-rendering: crisp-edges;
  image-rendering: pixelated;
}
#lightboxImg.is-dragging { cursor: grabbing; }
.lightbox-nav {
  position: absolute; top: 50%; z-index: 3;
  transform: translateY(-50%);
  display: inline-flex; align-items: center; justify-content: center;
  width: 44px; height: 44px;
  border: 1px solid rgba(255, 255, 255, 0.16); border-radius: 50%;
  background: rgba(255, 255, 255, 0.06); color: #eef0f6; cursor: pointer;
}
.lightbox-prev { left: 18px; }
.lightbox-next { right: 18px; }
.lightbox-nav:hover { background: rgba(255, 255, 255, 0.14); }
.lightbox-nav .icon { width: 20px; height: 20px; }
.lightbox-counter {
  position: absolute; bottom: 14px; left: 50%; transform: translateX(-50%); z-index: 3;
  display: flex; align-items: center; gap: 10px;
  font-family: var(--font-mono); font-size: 11px; color: rgba(255, 255, 255, 0.65);
}
.lightbox-zoom { color: #b9c0ff; }
.sheet {
  width: min(420px, calc(100vw - 24px));
  max-height: calc(100vh - 32px);
  max-height: calc(100dvh - 32px);
  overflow-y: auto;
  padding: 18px 20px 20px;
  border: 1px solid var(--border-strong); border-radius: var(--radius);
  background: var(--card-solid); box-shadow: var(--shadow);
}
.sheet-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
.sheet-head h2 { margin: 0; font-size: 15px; letter-spacing: -0.01em; }
.sheet .icon-btn { border-color: transparent; }
.shortcut-list { display: grid; gap: 8px; margin: 0; }
.shortcut-row { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
.shortcut-row dt { margin: 0; color: var(--muted); font-size: 13px; }
.shortcut-row dd { margin: 0; }
.sheet-credits {
  margin-top: 16px; padding-top: 12px;
  border-top: 1px solid var(--border);
  color: var(--muted); font-size: 11.5px; line-height: 1.55;
}
.sheet-credits-title {
  display: block; margin-bottom: 4px;
  color: var(--dim); font-size: 9.5px; font-weight: 700;
  letter-spacing: 0.08em; text-transform: uppercase;
}
.sheet-credits p { margin: 0; }
.sheet-credits strong { color: var(--text); font-weight: 600; }
.sheet-credits a { color: var(--accent); text-decoration: none; }
.sheet-credits a:hover { text-decoration: underline; }
.sheet-credits .sheet-license { margin-top: 4px; color: var(--dim); font-family: var(--font-mono); font-size: 10px; }
.sheet-credits .sheet-repo { margin-top: 4px; font-family: var(--font-mono); font-size: 10px; }
kbd {
  display: inline-block; min-width: 22px; padding: 1px 6px;
  border: 1px solid var(--border-strong); border-radius: 5px;
  background: var(--bg-soft); color: var(--text);
  font-family: var(--font-mono); font-size: 11px; text-align: center;
}
.toast {
  position: fixed; left: 50%; bottom: 24px; z-index: 100;
  transform: translate(-50%, 10px);
  padding: 8px 14px; border-radius: 999px;
  background: var(--card-solid); border: 1px solid var(--border-strong);
  box-shadow: var(--shadow); color: var(--text); font-size: 12px;
  opacity: 0; pointer-events: none; transition: opacity 0.2s ease, transform 0.2s ease;
}
.toast.is-visible { opacity: 1; transform: translate(-50%, 0); }
body.audio-open .toast { bottom: 128px; }
.copy-helper { position: fixed; top: -1000px; left: -1000px; opacity: 0; }

/* ---- responsive ---- */
@media (max-width: 1000px) {
  .file-table td.col-desc, .file-table thead th.col-desc { display: none; }
}
@media (max-width: 760px) {
  .app { padding: 10px; }
  .header { padding: 13px 14px; }
  .toolbar { padding: 9px 10px; }
  .stats { display: none; }
  .file-table th, .file-table td { padding-left: 9px; padding-right: 9px; }
  .file-table td.col-type, .file-table thead th.col-type,
  .file-table td.col-date, .file-table thead th.col-date,
  .file-table td.col-desc, .file-table thead th.col-desc { display: none; }
  .file-table td.col-name { max-width: 58vw; }
  .file-table td.col-actions { width: 66px; }
  .file-grid { grid-template-columns: repeat(auto-fill, minmax(132px, 1fr)); gap: 8px; }
  .file-grid-item { padding: 8px; }
  .audio-track { flex: 1 1 100px; }
  .audio-volume { display: none; }
  .audio-controls { gap: 7px; padding: 6px 9px 9px; flex-wrap: wrap; }
  .audio-progress { order: 10; flex: 1 1 100%; min-width: 80px; }
  .lightbox-stage { padding: 58px 8px 42px; }
  #lightboxImg { max-width: calc(100vw - 112px); max-height: calc(100dvh - 120px); }
  .lightbox-nav { width: 36px; height: 36px; }
  .lightbox-prev { left: 8px; }
  .lightbox-next { right: 8px; }
  .lightbox-caption { max-width: 58vw; }
  .search-kbd { display: none; }
  .search-box input { font-size: 16px; }
}
@media (max-width: 480px) {
  .file-table td.col-size, .file-table thead th.col-size { display: none; }
  .brand-name { max-width: 46vw; }
  .file-grid { grid-template-columns: repeat(auto-fill, minmax(118px, 1fr)); }
}
@media (prefers-reduced-motion: reduce) {
  html { scroll-behavior: auto; }
  *, *::before, *::after { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; }
}
@media print {
  .toolbar, .header-actions, .footer, .audio-player, .col-actions, .col-icon,
  .track-play, .grid-play { display: none !important; }
  body { background: #fff; color: #000; }
  .app { padding: 0; }
  .header, .table-wrap { border: 0; box-shadow: none; background: #fff; }
  .file-table td, .file-table th { color: #000 !important; }
}
</style>
<?php if ($externalStylesheet !== ''): ?>
<!-- Optional extra stylesheet from the configuration. -->
<link rel="stylesheet" href="<?= snifH($externalStylesheet) ?>">
<?php endif; ?>
<!-- Progressive enhancement: hide JS-only controls when JavaScript is disabled. -->
<noscript><style nonce="<?= snifH($snifNonce) ?>">
  .search-box, .view-toggle, .header-actions, [data-copy], .track-play, .grid-play, .audio-player, dialog, .js-shortcuts { display: none !important; }
</style></noscript>
</head>
<body>
<!-- Inline SVG sprite reused by every file type and UI control. -->
<svg class="sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <symbol id="i-folder" viewBox="0 0 24 24"><path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H9l2 2h7.5A2.5 2.5 0 0 1 21 9.5v8A2.5 2.5 0 0 1 18.5 20h-13A2.5 2.5 0 0 1 3 17.5z"/></symbol>
    <symbol id="i-dirup" viewBox="0 0 24 24"><path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H9l2 2h7.5A2.5 2.5 0 0 1 21 9.5v8A2.5 2.5 0 0 1 18.5 20h-13A2.5 2.5 0 0 1 3 17.5z"/><path d="M12 17v-6m0 0-3 3m3-3 3 3"/></symbol>
    <symbol id="i-image" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2.5"/><circle cx="8.5" cy="8.5" r="1.4"/><path d="m21 15-5-5L5 21"/></symbol>
    <symbol id="i-music" viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></symbol>
    <symbol id="i-video" viewBox="0 0 24 24"><rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="m10 9 5 3-5 3z"/></symbol>
    <symbol id="i-archive" viewBox="0 0 24 24"><path d="M21 8v13H3V3h12l6 5z"/><path d="M10 12h4M10 15h4M10 9h1"/></symbol>
    <symbol id="i-text" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h6"/></symbol>
    <symbol id="i-HTML" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 15l2-2-2-2M16 15l-2-2 2-2"/></symbol>
    <symbol id="i-binary" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8.5 16h.01M12 16h.01M15.5 16h.01"/></symbol>
    <symbol id="i-unknown" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></symbol>
    <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7.5"/><path d="m21 21-4.2-4.2"/></symbol>
    <symbol id="i-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4.2"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></symbol>
    <symbol id="i-moon" viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></symbol>
    <symbol id="i-list" viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></symbol>
    <symbol id="i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.4"/><rect x="14" y="3" width="7" height="7" rx="1.4"/><rect x="3" y="14" width="7" height="7" rx="1.4"/><rect x="14" y="14" width="7" height="7" rx="1.4"/></symbol>
    <symbol id="i-download" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5M12 15V3"/></symbol>
    <symbol id="i-link" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.5.5l2-2a5 5 0 0 0-7-7l-1.2 1.2"/><path d="M14 11a5 5 0 0 0-7.5-.5l-2 2a5 5 0 0 0 7 7l1.2-1.2"/></symbol>
    <symbol id="i-play" viewBox="0 0 24 24"><path d="M6 4.5 20 12 6 19.5z"/></symbol>
    <symbol id="i-pause" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></symbol>
    <symbol id="i-prev" viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></symbol>
    <symbol id="i-next" viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></symbol>
    <symbol id="i-close" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></symbol>
    <symbol id="i-volume" viewBox="0 0 24 24"><path d="M11 5 6 9H2v6h4l5 4z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/></symbol>
    <symbol id="i-mute" viewBox="0 0 24 24"><path d="M11 5 6 9H2v6h4l5 4z"/><path d="m16 9 5 6M21 9l-5 6"/></symbol>
    <symbol id="i-help" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9.1 9a3 3 0 1 1 4.1 2.8c-.8.3-1.2 1-1.2 1.9v.3M12 17.5h.01"/></symbol>
    <symbol id="i-home" viewBox="0 0 24 24"><path d="m3 10 9-7 9 7v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 21v-8h6v8"/></symbol>
    <symbol id="i-chevron-up" viewBox="0 0 24 24"><path d="m6 15 6-6 6 6"/></symbol>
    <symbol id="i-chevron-down" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></symbol>
  </defs>
</svg>

<!-- Application shell -->
<div class="app">
<?php if ($displayError): ?>
  <!-- Request errors are escaped and shown above the header. -->
  <div class="errors" role="alert">
    <?php foreach ($displayError as $error): ?>
      <div class="error-banner"><?= snifH($error) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

  <!-- Header: brand, theme / help controls, breadcrumb and directory description. -->
  <header class="header">
    <div class="header-top">
      <a class="brand" href="<?= snifH($snifSelf) ?>" title="<?= snifH($snifServer) ?>">
        <span class="brand-mark"><?= getSvgIcon('folder') ?></span>
        <span class="brand-name"><?= snifH($snifServer) ?></span>
      </a>
      <div class="header-actions">
        <button class="icon-btn" id="themeToggle" type="button" aria-label="<?= snifH(translate('Toggle theme')) ?>" title="<?= snifH(translate('Toggle theme')) ?>">
          <?= snifUiIcon('sun', 'icon icon-sun') ?>
          <?= snifUiIcon('moon', 'icon icon-moon') ?>
        </button>
        <button class="icon-btn js-shortcuts" type="button" aria-label="<?= snifH(translate('Keyboard shortcuts')) ?>" title="<?= snifH(translate('Keyboard shortcuts')) ?>">
          <?= snifUiIcon('help') ?>
        </button>
      </div>
    </div>

    <h1 class="sr-only"><?= snifH($pageTitle) ?></h1>

    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a class="crumb home" href="<?= snifH($snifSelf) ?>" aria-label="<?= snifH(translate('Home')) ?>" title="<?= snifH(translate('Home')) ?>"><?= snifUiIcon('home') ?></a>
      <?php $prefix = ''; foreach ($segments as $index => $segment): ?>
        <?php $prefix .= ($prefix === '' ? '' : '/') . $segment; ?>
        <span class="sep" aria-hidden="true">/</span>
        <?php if ($index === count($segments) - 1): ?>
          <span class="crumb current" aria-current="page"><?= snifH($segment) ?></span>
        <?php else: ?>
          <a class="crumb" href="<?= snifH(snifDirUrl($prefix)) ?>"><?= snifH($segment) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>

    <?php if ($directoryDescription !== ''): ?>
      <p class="dir-desc"><?= snifH($directoryDescription) ?></p>
    <?php endif; ?>
  </header>

  <!-- Toolbar: live search, collection stats and list / grid switcher. -->
  <div class="toolbar">
    <label class="search-box">
      <span class="sr-only"><?= snifH(translate('Search')) ?></span>
      <?= snifUiIcon('search') ?>
      <input type="search" id="searchInput" placeholder="<?= snifH($searchPlaceholder) ?>" autocomplete="off" spellcheck="false" enterkeyhint="search">
      <kbd class="search-kbd" aria-hidden="true">/</kbd>
    </label>
    <div class="stats">
      <span><?= snifH(snifPlural($dirCount, '1 folder', '%d folders')) ?></span>
      <span class="dot" aria-hidden="true">&middot;</span>
      <span><?= snifH(snifPlural($fileCount, '1 file', '%d files')) ?></span>
      <?php if ($totalSize > 0): ?>
        <span class="dot" aria-hidden="true">&middot;</span>
        <span><?= snifH(niceSize($totalSize)) ?></span>
      <?php endif; ?>
    </div>
    <div class="view-toggle" role="group" aria-label="<?= snifH(translate('List view') . ' / ' . translate('Grid view')) ?>">
      <button class="icon-btn" id="viewList" type="button" aria-pressed="true" aria-label="<?= snifH(translate('List view')) ?>" title="<?= snifH(translate('List view')) ?>">
        <?= snifUiIcon('list') ?>
      </button>
      <button class="icon-btn" id="viewGrid" type="button" aria-pressed="false" aria-label="<?= snifH(translate('Grid view')) ?>" title="<?= snifH(translate('Grid view')) ?>">
        <?= snifUiIcon('grid') ?>
      </button>
    </div>
    <span class="sr-only" id="filterStatus" aria-live="polite"></span>
  </div>

  <!-- List view: server-rendered table, fully usable without JavaScript. -->
  <div class="view-list">
    <div class="table-wrap">
      <table class="file-table<?= $tableWidth100Percent ? '' : ' auto' ?>">
        <caption class="sr-only"><?= snifH($pageTitle) ?></caption>
        <thead>
          <tr>
            <th scope="col" class="col-icon"><span class="sr-only"><?= snifH(translate('type')) ?></span></th>
            <th scope="col" class="col-name" aria-sort="<?= snifAriaSort('name') ?>">
              <a class="sort-link" href="<?= snifH(snifSortUrl('name')) ?>" title="<?= snifH(translate('Sort by') . ' ' . translate('name')) ?>">
                <span><?= snifH(translate('name')) ?></span>
                <?php if ($sort === 'name'): ?><?= snifUiIcon($order === 'asc' ? 'chevron-up' : 'chevron-down', 'icon sort-icon') ?><?php endif; ?>
              </a>
            </th>
            <?php if ($showType): ?>
              <th scope="col" class="col-type" aria-sort="<?= snifAriaSort('type') ?>">
                <a class="sort-link" href="<?= snifH(snifSortUrl('type')) ?>" title="<?= snifH(translate('Sort by') . ' ' . translate('type')) ?>">
                  <span><?= snifH(translate('type')) ?></span>
                  <?php if ($sort === 'type'): ?><?= snifUiIcon($order === 'asc' ? 'chevron-up' : 'chevron-down', 'icon sort-icon') ?><?php endif; ?>
                </a>
              </th>
            <?php endif; ?>
            <?php if ($showSize): ?>
              <th scope="col" class="col-size" aria-sort="<?= snifAriaSort('size') ?>">
                <a class="sort-link" href="<?= snifH(snifSortUrl('size')) ?>" title="<?= snifH(translate('Sort by') . ' ' . translate('size')) ?>">
                  <span><?= snifH(translate('size')) ?></span>
                  <?php if ($sort === 'size'): ?><?= snifUiIcon($order === 'asc' ? 'chevron-up' : 'chevron-down', 'icon sort-icon') ?><?php endif; ?>
                </a>
              </th>
            <?php endif; ?>
            <?php if ($showDate): ?>
              <th scope="col" class="col-date" aria-sort="<?= snifAriaSort('date') ?>">
                <a class="sort-link" href="<?= snifH(snifSortUrl('date')) ?>" title="<?= snifH(translate('Sort by') . ' ' . translate('date')) ?>">
                  <span><?= snifH(translate('date')) ?></span>
                  <?php if ($sort === 'date'): ?><?= snifUiIcon($order === 'asc' ? 'chevron-up' : 'chevron-down', 'icon sort-icon') ?><?php endif; ?>
                </a>
              </th>
            <?php endif; ?>
            <?php if ($showDescription): ?>
              <th scope="col" class="col-desc" aria-sort="<?= snifAriaSort('description') ?>">
                <a class="sort-link" href="<?= snifH(snifSortUrl('description')) ?>" title="<?= snifH(translate('Sort by') . ' ' . translate('description')) ?>">
                  <span><?= snifH(translate('description')) ?></span>
                  <?php if ($sort === 'description'): ?><?= snifUiIcon($order === 'asc' ? 'chevron-up' : 'chevron-down', 'icon sort-icon') ?><?php endif; ?>
                </a>
              </th>
            <?php endif; ?>
            <th scope="col" class="col-actions"><span class="sr-only"><?= snifH(translate('download')) ?></span></th>
          </tr>
        </thead>
        <tbody id="fileTableBody">
        <?php foreach ($displayFiles as $file): ?>
          <?php
            $isBack = !empty($file['isBack']);
            $typeLabel = $file['isDirectory']
                ? translate($isBack ? 'dirup' : 'folder')
                : ($file['type'] !== '' ? strtoupper($file['type']) : translate('unknown'));
            $copyLabel = translate('Copy link') . ': ' . $file['name'];
            $downloadLabel = translate('download') . ': ' . $file['name'];
            $playLabel = translate('Play') . ': ' . $file['name'];
          ?>
          <tr class="file-row t-<?= snifH($file['filetype']) ?><?= $isBack ? ' is-back' : '' ?>" data-search="<?= snifH($file['searchBlob']) ?>">
            <td class="col-icon" aria-hidden="true"><?= getSvgIcon($file['filetype']) ?></td>
            <td class="col-name">
              <span class="name-cell">
                <?php if ($file['audioIndex'] !== null): ?>
                  <button class="track-play" type="button" data-audio-play="<?= (int) $file['audioIndex'] ?>" data-track-name="<?= snifH($file['name']) ?>" aria-label="<?= snifH($playLabel) ?>" title="<?= snifH($playLabel) ?>">
                    <?= snifUiIcon('play', 'icon icon-fill icon-track-play') ?>
                    <?= snifUiIcon('pause', 'icon icon-fill icon-track-pause') ?>
                  </button>
                <?php endif; ?>
                <a class="file-link" href="<?= snifH($file['link']) ?>" title="<?= snifH($isBack ? $file['displayName'] : $file['name']) ?>"<?php if ($file['imageIndex'] !== null): ?> data-image="<?= (int) $file['imageIndex'] ?>"<?php endif; ?><?php if ($file['audioIndex'] !== null): ?> data-audio="<?= (int) $file['audioIndex'] ?>"<?php endif; ?>><?= snifH($file['displayName']) ?></a>
                <?php if (!empty($file['isLink'])): ?><span class="tag"><?= snifH(translate('link')) ?></span><?php endif; ?>
              </span>
            </td>
            <?php if ($showType): ?><td class="col-type"><?= snifH($typeLabel) ?></td><?php endif; ?>
            <?php if ($showSize): ?><td class="col-size"<?php if ($file['sizeTitle'] !== ''): ?> title="<?= snifH($file['sizeTitle']) ?>"<?php endif; ?>><?= snifH($file['niceSize']) ?></td><?php endif; ?>
            <?php if ($showDate): ?><td class="col-date"><?php if ($file['date'] > 0): ?><time datetime="<?= snifH(date('c', $file['date'])) ?>" title="<?= snifH($file['fullDate']) ?>"><?= snifH($file['timeAgo']) ?></time><?php endif; ?></td><?php endif; ?>
            <?php if ($showDescription): ?><td class="col-desc"><?= snifH($file['description']) ?></td><?php endif; ?>
            <td class="col-actions">
              <?php if (!$isBack): ?>
                <span class="actions">
                  <button class="icon-btn" type="button" data-copy="<?= snifH($file['link']) ?>" aria-label="<?= snifH($copyLabel) ?>" title="<?= snifH($copyLabel) ?>"><?= snifUiIcon('link') ?></button>
                  <?php if (!$file['isDirectory'] && $file['isDownloadable']): ?>
                    <a class="icon-btn" href="<?= snifH($file['downloadUrl']) ?>" aria-label="<?= snifH($downloadLabel) ?>" title="<?= snifH($downloadLabel) ?>"><?= snifUiIcon('download') ?></a>
                  <?php endif; ?>
                </span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Grid view: rendered once from the compact JSON payload at the bottom. -->
  <div class="view-grid">
    <div class="file-grid" id="fileGrid"></div>
  </div>

  <?php if ($entryCount === 0): ?>
    <!-- Empty directory state -->
    <div class="empty-state">
      <?= snifUiIcon('folder') ?>
      <p><?= snifH(translate('Empty folder')) ?></p>
    </div>
  <?php endif; ?>

  <!-- Search no-results state -->
  <div class="no-results" id="noResults" hidden>
    <?= snifUiIcon('search') ?>
    <p><?= snifH(translate('No files match your search.')) ?></p>
  </div>

  <?php if ($pagingInEffect): ?>
    <!-- Pagination -->
    <nav class="pager" aria-label="<?= snifH(translate('Pagination')) ?>">
      <?php if ($currentPage > 1): ?>
        <a class="btn pager-btn" href="<?= snifH(snifPageUrl(max(0, $pageStart - $pageSize))) ?>" rel="prev"><?= snifUiIcon('prev') ?><span><?= snifH(translate('previous')) ?></span></a>
      <?php else: ?>
        <span class="btn pager-btn is-disabled" aria-disabled="true"><?= snifUiIcon('prev') ?><span><?= snifH(translate('previous')) ?></span></span>
      <?php endif; ?>
      <span class="pager-info"><?= snifH(sprintf(translate('Page %d of %d'), $currentPage, $pageCount)) ?></span>
      <?php if ($currentPage < $pageCount): ?>
        <a class="btn pager-btn" href="<?= snifH(snifPageUrl($pageStart + $pageSize)) ?>" rel="next"><span><?= snifH(translate('next')) ?></span><?= snifUiIcon('next') ?></a>
      <?php else: ?>
        <span class="btn pager-btn is-disabled" aria-disabled="true"><span><?= snifH(translate('next')) ?></span><?= snifUiIcon('next') ?></span>
      <?php endif; ?>
    </nav>
  <?php endif; ?>

  <!-- Footer -->
  <footer class="footer">
    <span>YAFI v<?= snifH($snifVersion) ?> - Yet Another File Indexer</span>
    <span aria-hidden="true">&middot;</span>
    <a class="footer-link" href="https://github.com/N0NameN0/YAFI" target="_blank" rel="noopener noreferrer" title="https://github.com/N0NameN0/YAFI" aria-label="YAFI on GitHub">GitHub</a>
  </footer>
</div>

<!-- Audio player: hidden until a supported track is started. -->
<section class="audio-player" id="audioPlayer" aria-label="<?= snifH(translate('Audio player')) ?>" aria-hidden="true" inert>
  <canvas class="audio-wave" id="audioCanvas" aria-hidden="true"></canvas>
  <div class="audio-controls">
    <button class="icon-btn" id="audioPrev" type="button" aria-label="<?= snifH(translate('Previous')) ?>" title="<?= snifH(translate('Previous')) ?>"><?= snifUiIcon('prev') ?></button>
    <button class="audio-play" id="audioPlay" type="button" aria-label="<?= snifH(translate('Play')) ?>">
      <?= snifUiIcon('play', 'icon icon-fill icon-play') ?>
      <?= snifUiIcon('pause', 'icon icon-fill icon-pause') ?>
    </button>
    <button class="icon-btn" id="audioNext" type="button" aria-label="<?= snifH(translate('Next')) ?>" title="<?= snifH(translate('Next')) ?>"><?= snifUiIcon('next') ?></button>
    <div class="audio-track">
      <span class="audio-track-name" id="trackName" aria-live="polite">-</span>
      <span class="audio-track-type" id="trackType"></span>
    </div>
    <div class="audio-progress">
      <span class="audio-time" id="currentTime">0:00</span>
      <input class="range" id="audioSeek" type="range" min="0" max="100" step="0.1" value="0" aria-label="<?= snifH(translate('Seek')) ?>">
      <span class="audio-time" id="totalTime">0:00</span>
    </div>
    <div class="audio-volume">
      <button class="icon-btn" id="audioMute" type="button" aria-label="<?= snifH(translate('Mute')) ?>" title="<?= snifH(translate('Mute')) ?>">
        <?= snifUiIcon('volume', 'icon icon-volume') ?>
        <?= snifUiIcon('mute', 'icon icon-mute') ?>
      </button>
      <input class="range" id="audioVolume" type="range" min="0" max="100" step="1" value="80" aria-label="<?= snifH(translate('Volume')) ?>">
    </div>
    <button class="icon-btn audio-close" id="audioClose" type="button" aria-label="<?= snifH(translate('Close')) ?>" title="<?= snifH(translate('Close')) ?>"><?= snifUiIcon('close') ?></button>
  </div>
</section>

<!-- Image lightbox: fixed navigation, wheel zoom, drag pan and download. -->
<dialog class="lightbox" id="lightbox" aria-labelledby="lightboxCaption">
  <div class="lightbox-stage" id="lightboxStage">
    <div class="lightbox-bar">
      <div class="lightbox-caption" id="lightboxCaption"></div>
      <div class="lightbox-actions">
        <a class="icon-btn" id="lightboxDownload" href="#" download aria-label="<?= snifH(translate('download')) ?>" title="<?= snifH(translate('download')) ?>"><?= snifUiIcon('download') ?></a>
        <button class="icon-btn" id="lightboxClose" type="button" aria-label="<?= snifH(translate('Close')) ?>" title="<?= snifH(translate('Close')) ?>"><?= snifUiIcon('close') ?></button>
      </div>
    </div>
    <button class="lightbox-nav lightbox-prev" id="lightboxPrev" type="button" aria-label="<?= snifH(translate('Previous')) ?>"><?= snifUiIcon('prev') ?></button>
    <img id="lightboxImg" alt="" draggable="false">
    <button class="lightbox-nav lightbox-next" id="lightboxNext" type="button" aria-label="<?= snifH(translate('Next')) ?>"><?= snifUiIcon('next') ?></button>
    <div class="lightbox-counter"><span id="lightboxCounter"></span><span class="lightbox-zoom" id="lightboxZoom" aria-hidden="true" hidden></span></div>
  </div>
</dialog>

<!-- Keyboard shortcuts and project credits -->
<dialog class="sheet" id="shortcutsDialog" aria-labelledby="shortcutsTitle">
  <div class="sheet-head">
    <h2 id="shortcutsTitle"><?= snifH(translate('Keyboard shortcuts')) ?></h2>
    <button class="icon-btn" type="button" data-close-dialog aria-label="<?= snifH(translate('Close')) ?>"><?= snifUiIcon('close') ?></button>
  </div>
  <dl class="shortcut-list">
    <div class="shortcut-row"><dt><?= snifH(translate('Search')) ?></dt><dd><kbd>/</kbd></dd></div>
    <div class="shortcut-row"><dt><?= snifH(translate('Grid view')) ?></dt><dd><kbd>G</kbd></dd></div>
    <div class="shortcut-row"><dt><?= snifH(translate('List view')) ?></dt><dd><kbd>L</kbd></dd></div>
    <div class="shortcut-row"><dt><?= snifH(translate('Keyboard shortcuts')) ?></dt><dd><kbd>?</kbd></dd></div>
    <div class="shortcut-row"><dt><?= snifH(translate('Close')) ?></dt><dd><kbd>Esc</kbd></dd></div>
  </dl>
  <div class="sheet-credits">
    <span class="sheet-credits-title"><?= snifH(translate('Credits')) ?></span>
    <p><strong>YAFI v<?= snifH($snifVersion) ?></strong> - Yet Another File Indexer</p>
    <p><?= snifH(translate('Created by')) ?> <strong>Antoine Santo aka N0NameN0</strong></p>
    <p><?= snifH(translate('Based on')) ?> <strong>snif 1.5.2</strong> - Kai Blankenhorn</p>
    <p class="sheet-license">GPL-2.0-or-later</p>
    <p class="sheet-repo"><a href="https://github.com/N0NameN0/YAFI" target="_blank" rel="noopener noreferrer">github.com/N0NameN0/YAFI</a></p>
  </div>
</dialog>

<!-- Toast notifications -->
<div class="toast" id="toast" role="status" aria-live="polite"></div>

<!-- Page data and application script -->
<script type="application/json" id="snifData" nonce="<?= snifH($snifNonce) ?>"><?= $jsonPayload ?></script>
<script nonce="<?= snifH($snifNonce) ?>">
(function () {
    'use strict';

    var root = document.documentElement;
    var body = document.body;
    var byId = function (id) { return document.getElementById(id); };
    var I18N = <?= $i18nJson ?>;
    var DATA = { items: [], images: [], audio: [] };
    try {
        var dataNode = byId('snifData');
        if (dataNode) {
            DATA = JSON.parse(dataNode.textContent || '{}');
        }
    } catch (err) {
        DATA = { items: [], images: [], audio: [] };
    }
    if (!Array.isArray(DATA.items)) { DATA.items = []; }
    if (!Array.isArray(DATA.images)) { DATA.images = []; }
    if (!Array.isArray(DATA.audio)) { DATA.audio = []; }

    /* The browser, not the extension list, is the final judge of playability. */
    var audioProbe = document.createElement('audio');
    function audioSupported(track) {
        if (!track || !track.mime) { return true; }
        try { return audioProbe.canPlayType(track.mime) !== ''; } catch (e) { return true; }
    }
    function markAudioUnsupported(index) {
        var track = DATA.audio[index];
        if (!track) { return; }
        track.supported = false;
        Array.prototype.forEach.call(document.querySelectorAll('a[data-audio="' + index + '"]'), function (link) {
            var container = link.closest('.file-row, .file-grid-item');
            if (container) {
                container.classList.remove('is-playing-audio');
                var badge = container.querySelector('.grid-play');
                if (badge && badge.parentNode) { badge.parentNode.removeChild(badge); }
            }
            link.removeAttribute('data-audio');
        });
        Array.prototype.forEach.call(document.querySelectorAll('[data-audio-play="' + index + '"]'), function (button) {
            button.hidden = true;
            button.classList.remove('is-playing');
            var row = button.closest('.file-row');
            if (row) { row.classList.remove('is-playing-audio'); }
        });
    }
    function prepareAudioTracks() {
        if (!DATA.audio.length) {
            Array.prototype.forEach.call(document.querySelectorAll('[data-audio-play]'), function (button) {
                button.hidden = true;
            });
            Array.prototype.forEach.call(document.querySelectorAll('a[data-audio]'), function (link) {
                link.removeAttribute('data-audio');
            });
            return;
        }
        DATA.audio.forEach(function (track, index) {
            if (!track) { return; }
            track.supported = audioSupported(track);
            if (!track.supported) { markAudioUnsupported(index); }
        });
    }
    prepareAudioTracks();

    function lsGet(key) {
        try { return window.localStorage.getItem(key); } catch (e) { return null; }
    }
    function lsSet(key, value) {
        try { window.localStorage.setItem(key, value); } catch (e) {}
    }
    function readAccent() {
        var value = window.getComputedStyle(root).getPropertyValue('--accent').trim();
        return value || '#8b93ff';
    }
    var accentColor = readAccent();

    /* ---- theme ---- */
    function syncThemeMeta() {
        var meta = byId('themeColor');
        if (meta) {
            meta.setAttribute('content', root.dataset.theme === 'light' ? '#f5f6fa' : '#08080d');
        }
        var button = byId('themeToggle');
        if (button) {
            var label = I18N.toggleTheme || 'Toggle theme';
            button.setAttribute('aria-label', label);
            button.title = label;
        }
    }
    syncThemeMeta();
    var themeButton = byId('themeToggle');
    if (themeButton) {
        themeButton.addEventListener('click', function () {
            var next = root.dataset.theme === 'light' ? 'dark' : 'light';
            root.dataset.theme = next;
            lsSet('snif-theme', next);
            accentColor = readAccent();
            syncThemeMeta();
        });
    }

    /* ---- grid view ---- */
    var SVG_NS = 'http://www.w3.org/2000/svg';
    var grid = byId('fileGrid');
    var gridBuilt = false;

    function makeIcon(type) {
        var svg = document.createElementNS(SVG_NS, 'svg');
        svg.setAttribute('class', 'icon');
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('focusable', 'false');
        var use = document.createElementNS(SVG_NS, 'use');
        use.setAttribute('href', '#i-' + type);
        svg.appendChild(use);
        return svg;
    }

    function renderGrid() {
        if (!grid || gridBuilt) { return; }
        var fragment = document.createDocumentFragment();
        DATA.items.forEach(function (item) {
            var type = typeof item.type === 'string' && /^[A-Za-z]+$/.test(item.type) ? item.type : 'unknown';
            var card = document.createElement('a');
            card.className = 'file-grid-item grid-item t-' + type + (item.back ? ' is-back' : '');
            card.href = item.url || '#';
            card.title = item.name || '';
            card.dataset.search = item.search || '';
            card.dataset.name = (item.name || '').toLowerCase();
            if (typeof item.imageIndex === 'number') {
                card.dataset.image = String(item.imageIndex);
            }
            var audioIndex = typeof item.audioIndex === 'number' ? item.audioIndex : -1;
            var audioTrack = audioIndex >= 0 ? DATA.audio[audioIndex] : null;
            var audioPlayable = !!(audioTrack && audioTrack.supported !== false);
            if (audioPlayable) {
                card.dataset.audio = String(audioIndex);
            }
            if (item.thumb) {
                var image = document.createElement('img');
                image.className = 'grid-thumb';
                image.alt = '';
                image.loading = 'lazy';
                image.decoding = 'async';
                image.width = 200;
                image.height = 80;
                image.addEventListener('error', function () {
                    if (image.parentNode === card) {
                        var fallbackIcon = document.createElement('span');
                        fallbackIcon.className = 'grid-icon';
                        fallbackIcon.appendChild(makeIcon(type));
                        card.replaceChild(fallbackIcon, image);
                    }
                }, false);
                card.appendChild(image);
                image.src = item.thumb;
            } else {
                var iconWrap = document.createElement('span');
                iconWrap.className = 'grid-icon';
                iconWrap.appendChild(makeIcon(type));
                card.appendChild(iconWrap);
            }
            var name = document.createElement('span');
            name.className = 'grid-name';
            name.textContent = item.short || item.name || '';
            card.appendChild(name);
            if (item.meta) {
                var meta = document.createElement('span');
                meta.className = 'grid-meta';
                meta.textContent = item.meta;
                card.appendChild(meta);
            }
            if (audioPlayable) {
                var audioBadge = document.createElement('span');
                audioBadge.className = 'grid-play';
                audioBadge.setAttribute('aria-hidden', 'true');
                var badgePlay = makeIcon('play');
                badgePlay.classList.add('icon-fill', 'icon-track-play');
                var badgePause = makeIcon('pause');
                badgePause.classList.add('icon-fill', 'icon-track-pause');
                audioBadge.appendChild(badgePlay);
                audioBadge.appendChild(badgePause);
                card.appendChild(audioBadge);
            }
            fragment.appendChild(card);
        });
        grid.textContent = '';
        grid.appendChild(fragment);
        gridBuilt = true;
        applyFilter();
        if (playerAudio) { syncTrackButtons(); }
    }

    function setView(view, persist) {
        view = view === 'grid' ? 'grid' : 'list';
        root.dataset.view = view;
        var listButton = byId('viewList');
        var gridButton = byId('viewGrid');
        if (listButton) { listButton.setAttribute('aria-pressed', view === 'list' ? 'true' : 'false'); }
        if (gridButton) { gridButton.setAttribute('aria-pressed', view === 'grid' ? 'true' : 'false'); }
        if (view === 'grid') { renderGrid(); }
        if (persist) { lsSet('snif-view', view); }
        applyFilter();
    }

    var viewListButton = byId('viewList');
    var viewGridButton = byId('viewGrid');
    if (viewListButton) { viewListButton.addEventListener('click', function () { setView('list', true); }); }
    if (viewGridButton) { viewGridButton.addEventListener('click', function () { setView('grid', true); }); }

    /* ---- search / filter ---- */
    var searchInput = byId('searchInput');
    var noResults = byId('noResults');
    var filterStatus = byId('filterStatus');
    var rows = Array.prototype.slice.call(document.querySelectorAll('#fileTableBody .file-row'));
    var filterFrame = 0;

    function applyFilter() {
        var query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        var visible = 0;
        var total = 0;
        rows.forEach(function (row) {
            if (row.classList.contains('is-back')) {
                row.hidden = false;
                return;
            }
            total++;
            var match = !query || (row.getAttribute('data-search') || '').indexOf(query) !== -1;
            row.hidden = !match;
            if (match) { visible++; }
        });
        if (gridBuilt && grid) {
            Array.prototype.forEach.call(grid.children, function (card) {
                if (card.classList.contains('is-back')) {
                    card.hidden = false;
                    return;
                }
                var match = !query || (card.getAttribute('data-search') || '').indexOf(query) !== -1;
                card.hidden = !match;
            });
        }
        if (noResults) {
            noResults.hidden = !(query && visible === 0 && total > 0);
        }
        if (filterStatus) {
            if (query) {
                var template = I18N.showing || 'Showing %d of %d';
                filterStatus.textContent = template.replace('%d', String(visible)).replace('%d', String(total));
            } else {
                filterStatus.textContent = '';
            }
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            if (filterFrame) { cancelAnimationFrame(filterFrame); }
            filterFrame = requestAnimationFrame(function () {
                filterFrame = 0;
                applyFilter();
            });
        });
    }
    applyFilter();
    setView(root.dataset.view === 'grid' ? 'grid' : 'list', false);

    /* ---- copy link + media interception ---- */
    var toast = byId('toast');
    var toastTimer = 0;
    function showToast(message) {
        if (!toast) { return; }
        toast.textContent = message;
        toast.classList.add('is-visible');
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(function () {
            toast.classList.remove('is-visible');
        }, 2200);
    }

    function fallbackCopy(text) {
        var helper = document.createElement('textarea');
        helper.className = 'copy-helper';
        helper.value = text;
        helper.setAttribute('readonly', 'readonly');
        body.appendChild(helper);
        helper.select();
        var copied = false;
        try { copied = document.execCommand('copy'); } catch (e) { copied = false; }
        body.removeChild(helper);
        showToast(copied ? (I18N.linkCopied || 'Link copied') : (I18N.copyFailed || 'Could not copy the link.'));
    }

    function copyText(value) {
        var text = value;
        try { text = new URL(value, window.location.href).href; } catch (e) {}
        if (navigator.clipboard && navigator.clipboard.writeText && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function () {
                showToast(I18N.linkCopied || 'Link copied');
            }, function () {
                fallbackCopy(text);
            });
        } else {
            fallbackCopy(text);
        }
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || !target.closest) { return; }
        var copyButton = target.closest('[data-copy]');
        if (copyButton) {
            event.preventDefault();
            copyText(copyButton.getAttribute('data-copy') || '');
            return;
        }
        var playControl = target.closest('[data-audio-play]');
        if (playControl) {
            event.preventDefault();
            var playIndex = parseInt(playControl.getAttribute('data-audio-play'), 10);
            if (!isNaN(playIndex)) {
                playAudio(playIndex);
            }
            return;
        }
        if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
            return;
        }
        var mediaLink = target.closest('a[data-image], a[data-audio]');
        if (!mediaLink) { return; }
        if (mediaLink.hasAttribute('data-image')) {
            var imageIndex = parseInt(mediaLink.getAttribute('data-image'), 10);
            if (!isNaN(imageIndex)) {
                event.preventDefault();
                openLightbox(imageIndex);
            }
        } else {
            var audioIndex = parseInt(mediaLink.getAttribute('data-audio'), 10);
            if (!isNaN(audioIndex)) {
                event.preventDefault();
                playAudio(audioIndex);
            }
        }
    });

    /* ---- audio player ---- */
    var audioPlayer = byId('audioPlayer');
    var playerAudio = new Audio();
    playerAudio.preload = 'metadata';
    var audioCanvas = byId('audioCanvas');
    var audioContext = null;
    var audioAnalyser = null;
    var audioSource = null;
    var visualizerFrame = 0;
    var frequencyData = null;
    var canvasWidth = 0;
    var canvasHeight = 0;
    var currentAudio = -1;
    var seeking = false;
    var storedVolume = parseFloat(lsGet('snif-volume'));
    var volume = isFinite(storedVolume) ? Math.min(1, Math.max(0, storedVolume)) : 0.8;
    var lastVolume = volume > 0 ? volume : 0.8;

    playerAudio.volume = volume;
    var seekInput = byId('audioSeek');
    var volumeInput = byId('audioVolume');
    var volumeButton = byId('audioMute');
    if (volumeInput) { volumeInput.value = String(Math.round(volume * 100)); }

    function formatTime(seconds) {
        if (!isFinite(seconds) || seconds < 0) { return '0:00'; }
        var minutes = Math.floor(seconds / 60);
        var rest = Math.floor(seconds % 60);
        return minutes + ':' + (rest < 10 ? '0' : '') + rest;
    }

    function trackUrl(track) {
        try { return new URL(track.url, window.location.href).href; } catch (e) { return track.url; }
    }

    function playCurrentAudio() {
        if (!playerAudio.src && DATA.audio.length) {
            stepAudio(1);
            return;
        }
        if (currentAudio >= 0 && DATA.audio[currentAudio] && DATA.audio[currentAudio].supported === false) {
            stepAudio(1);
            return;
        }
        if (currentAudio >= 0 && playerAudio.src) {
            setPlayerOpen(true);
        }
        ensureAudioGraph();
        try {
            var promise = playerAudio.play();
            if (promise && promise.catch) {
                promise.catch(function (error) {
                    /* Changing tracks aborts the previous play() promise. */
                    if (error && error.name === 'AbortError') { return; }
                    showToast(I18N.couldNotPlay || 'Could not play this file.');
                });
            }
        } catch (e) {
            showToast(I18N.couldNotPlay || 'Could not play this file.');
        }
    }

    function setPlayerOpen(open) {
        if (!audioPlayer) { return; }
        audioPlayer.classList.toggle('active', open);
        audioPlayer.setAttribute('aria-hidden', open ? 'false' : 'true');
        if (open) {
            audioPlayer.removeAttribute('inert');
        } else {
            audioPlayer.setAttribute('inert', '');
        }
        body.classList.toggle('audio-open', open);
    }

    /* Pause keeps the track and its position, but folds the bottom bar away. */
    function hideAudioPlayer() {
        if (!playerAudio.paused) {
            playerAudio.pause();
        }
        stopVisualizer();
        setPlayerOpen(false);
        syncPlayState();
    }

    /* Closing releases the source entirely; the next play starts fresh. */
    function closeAudioPlayer() {
        if (!playerAudio.paused) {
            playerAudio.pause();
        }
        playerAudio.removeAttribute('src');
        try { playerAudio.load(); } catch (e) {}
        currentAudio = -1;
        stopVisualizer();
        setPlayerOpen(false);
        syncPlayState();
    }

    function updateMuteState() {
        if (!audioPlayer) { return; }
        var muted = playerAudio.muted || playerAudio.volume === 0;
        audioPlayer.classList.toggle('is-muted', muted);
        if (volumeButton) {
            volumeButton.setAttribute('aria-label', muted ? (I18N.unmute || 'Unmute') : (I18N.mute || 'Mute'));
        }
    }

    function syncPlayState() {
        if (!audioPlayer) { return; }
        var playing = !playerAudio.paused && !playerAudio.ended && !playerAudio.error;
        audioPlayer.classList.toggle('is-playing', playing);
        var playButton = byId('audioPlay');
        if (playButton) {
            playButton.setAttribute('aria-label', playing ? (I18N.pause || 'Pause') : (I18N.play || 'Play'));
        }
        syncTrackButtons();
    }

    function syncTrackButtons() {
        if (!playerAudio) { return; }
        var playing = !playerAudio.paused && !playerAudio.ended && !playerAudio.error && currentAudio >= 0;
        Array.prototype.forEach.call(document.querySelectorAll('[data-audio-play]'), function (button) {
            var index = parseInt(button.getAttribute('data-audio-play'), 10);
            var active = playing && index === currentAudio;
            var name = button.getAttribute('data-track-name') || '';
            var label = (active ? (I18N.pause || 'Pause') : (I18N.play || 'Play')) + (name ? ': ' + name : '');
            button.classList.toggle('is-playing', active);
            button.setAttribute('aria-label', label);
            button.title = label;
        });
        Array.prototype.forEach.call(document.querySelectorAll('a[data-audio]'), function (link) {
            var index = parseInt(link.getAttribute('data-audio'), 10);
            var container = link.closest('.file-row, .file-grid-item');
            if (container) {
                container.classList.toggle('is-playing-audio', playing && index === currentAudio);
            }
        });
    }

    function ensureAudioGraph() {
        var Ctor = window.AudioContext || window.webkitAudioContext;
        if (audioContext || !Ctor) { return; }
        try {
            audioContext = new Ctor();
            audioSource = audioContext.createMediaElementSource(playerAudio);
            try {
                audioAnalyser = audioContext.createAnalyser();
                audioAnalyser.fftSize = 128;
                audioAnalyser.smoothingTimeConstant = 0.82;
                audioSource.connect(audioAnalyser);
                audioAnalyser.connect(audioContext.destination);
                frequencyData = new Uint8Array(audioAnalyser.frequencyBinCount);
            } catch (analysisError) {
                /* Playback still works without the visualizer. */
                audioAnalyser = null;
                try { audioSource.connect(audioContext.destination); } catch (connectionError) {}
            }
            if (audioContext.state === 'suspended') {
                audioContext.resume().catch(function () {});
            }
        } catch (e) {
            audioContext = null;
            audioAnalyser = null;
        }
    }

    function sizeAudioCanvas() {
        if (!audioCanvas) { return; }
        var rect = audioCanvas.getBoundingClientRect();
        var ratio = Math.min(window.devicePixelRatio || 1, 2);
        canvasWidth = Math.max(1, Math.round(rect.width));
        canvasHeight = Math.max(1, Math.round(rect.height));
        var pixelWidth = Math.round(canvasWidth * ratio);
        var pixelHeight = Math.round(canvasHeight * ratio);
        if (audioCanvas.width !== pixelWidth) { audioCanvas.width = pixelWidth; }
        if (audioCanvas.height !== pixelHeight) { audioCanvas.height = pixelHeight; }
        var context = audioCanvas.getContext('2d');
        if (context) { context.setTransform(ratio, 0, 0, ratio, 0, 0); }
    }

    function drawVisualizer() {
        if (!audioAnalyser || !frequencyData || !audioCanvas) { return; }
        visualizerFrame = window.requestAnimationFrame(drawVisualizer);
        var context = audioCanvas.getContext('2d');
        if (!context) { return; }
        if (!canvasWidth || !canvasHeight) { sizeAudioCanvas(); }
        audioAnalyser.getByteFrequencyData(frequencyData);
        context.clearRect(0, 0, canvasWidth, canvasHeight);
        context.fillStyle = accentColor;
        var barWidth = canvasWidth / frequencyData.length;
        for (var i = 0; i < frequencyData.length; i++) {
            var value = frequencyData[i] / 255;
            var height = value * canvasHeight * 0.92;
            context.globalAlpha = 0.22 + value * 0.68;
            context.fillRect(i * barWidth, canvasHeight - height, Math.max(1, barWidth - 1), height);
        }
        context.globalAlpha = 1;
    }

    function startVisualizer() {
        if (!audioAnalyser || visualizerFrame) { return; }
        drawVisualizer();
    }

    function stopVisualizer() {
        if (visualizerFrame) {
            window.cancelAnimationFrame(visualizerFrame);
            visualizerFrame = 0;
        }
        if (audioCanvas) {
            var context = audioCanvas.getContext('2d');
            if (context) { context.clearRect(0, 0, canvasWidth, canvasHeight); }
        }
    }

    function updateMediaSession(track) {
        if (!('mediaSession' in navigator) || !window.MediaMetadata) { return; }
        try {
            navigator.mediaSession.metadata = new window.MediaMetadata({
                title: track.name,
                artist: window.location.hostname,
                album: 'snif'
            });
        } catch (e) {}
    }

    function playAudio(index) {
        var track = DATA.audio[index];
        if (!track || !audioPlayer) { return; }
        if (track.supported === false) {
            showToast(I18N.audioUnsupported || 'This audio format is not supported by your browser.');
            return;
        }
        if (index === currentAudio && playerAudio.src) {
            if (playerAudio.paused) {
                ensureAudioGraph();
                playCurrentAudio();
            } else {
                hideAudioPlayer();
            }
            return;
        }
        currentAudio = index;
        ensureAudioGraph();
        setPlayerOpen(true);
        syncTrackButtons();
        playerAudio.src = trackUrl(track);
        var nameNode = byId('trackName');
        var typeNode = byId('trackType');
        if (nameNode) { nameNode.textContent = track.name; }
        if (typeNode) {
            var parts = track.name.split('.');
            typeNode.textContent = parts.length > 1 ? parts.pop().toUpperCase() : 'AUDIO';
        }
        var seek = byId('audioSeek');
        if (seek) { seek.value = '0'; }
        var current = byId('currentTime');
        if (current) { current.textContent = '0:00'; }
        var total = byId('totalTime');
        if (total) { total.textContent = '0:00'; }
        sizeAudioCanvas();
        playCurrentAudio();
        updateMediaSession(track);
    }

    function stepAudio(direction) {
        var count = DATA.audio.length;
        if (!count) { return; }
        var start = currentAudio >= 0 ? currentAudio : (direction > 0 ? -1 : 0);
        for (var step = 1; step <= count; step++) {
            var next = ((start + direction * step) % count + count) % count;
            if (DATA.audio[next] && DATA.audio[next].supported !== false) {
                playAudio(next);
                return;
            }
        }
    }

    playerAudio.addEventListener('play', function () {
        syncPlayState();
        ensureAudioGraph();
        sizeAudioCanvas();
        startVisualizer();
    });
    playerAudio.addEventListener('pause', function () {
        syncPlayState();
        stopVisualizer();
    });
    playerAudio.addEventListener('ended', function () {
        syncPlayState();
        stepAudio(1);
    });
    playerAudio.addEventListener('timeupdate', function () {
        if (seeking || !isFinite(playerAudio.duration) || playerAudio.duration <= 0) { return; }
        var percent = (playerAudio.currentTime / playerAudio.duration) * 100;
        if (seekInput) {
            seekInput.value = String(percent);
            seekInput.setAttribute('aria-valuetext', formatTime(playerAudio.currentTime) + ' / ' + formatTime(playerAudio.duration));
        }
        var current = byId('currentTime');
        if (current) { current.textContent = formatTime(playerAudio.currentTime); }
    });
    playerAudio.addEventListener('loadedmetadata', function () {
        var total = byId('totalTime');
        if (total) { total.textContent = formatTime(playerAudio.duration); }
        if (seekInput) {
            seekInput.setAttribute('aria-valuetext', '0:00 / ' + formatTime(playerAudio.duration));
        }
    });
    playerAudio.addEventListener('error', function () {
        var mediaError = playerAudio.error;
        if (mediaError && mediaError.code === 1) { return; } /* MEDIA_ERR_ABORTED */
        if (!playerAudio.getAttribute('src')) { return; }
        var failedIndex = currentAudio;
        var expectedUrl = failedIndex >= 0 && DATA.audio[failedIndex] ? trackUrl(DATA.audio[failedIndex]) : '';
        if (expectedUrl && playerAudio.src && playerAudio.src !== expectedUrl) { return; }
        if (mediaError && (mediaError.code === 3 || mediaError.code === 4) && failedIndex >= 0) {
            markAudioUnsupported(failedIndex);
        }
        if (!playerAudio.paused) {
            try { playerAudio.pause(); } catch (e) {}
        }
        stopVisualizer();
        syncPlayState();
        showToast(I18N.couldNotPlay || 'Could not play this file.');
    });

    if (seekInput) {
        seekInput.addEventListener('input', function () {
            seeking = true;
            if (isFinite(playerAudio.duration) && playerAudio.duration > 0) {
                playerAudio.currentTime = playerAudio.duration * (parseFloat(seekInput.value) / 100);
            }
            var current = byId('currentTime');
            if (current) { current.textContent = formatTime(playerAudio.currentTime); }
        });
        seekInput.addEventListener('change', function () { seeking = false; });
    }
    if (volumeInput) {
        volumeInput.addEventListener('input', function () {
            volume = Math.min(1, Math.max(0, parseFloat(volumeInput.value) / 100));
            playerAudio.volume = volume;
            playerAudio.muted = volume === 0;
            if (volume > 0) { lastVolume = volume; }
            lsSet('snif-volume', String(volume));
            updateMuteState();
        });
    }
    if (volumeButton) {
        volumeButton.addEventListener('click', function () {
            if (playerAudio.muted || playerAudio.volume === 0) {
                playerAudio.muted = false;
                if (playerAudio.volume === 0) { playerAudio.volume = lastVolume; }
                if (volumeInput) { volumeInput.value = String(Math.round(playerAudio.volume * 100)); }
            } else {
                lastVolume = playerAudio.volume;
                playerAudio.muted = true;
            }
            updateMuteState();
        });
    }

    var playButton = byId('audioPlay');
    if (playButton) {
        playButton.addEventListener('click', function () {
            if (!playerAudio.src) {
                if (DATA.audio.length) { stepAudio(1); }
                return;
            }
            if (playerAudio.paused) {
                ensureAudioGraph();
                playCurrentAudio();
            } else {
                hideAudioPlayer();
            }
        });
    }
    var previousButton = byId('audioPrev');
    if (previousButton) { previousButton.addEventListener('click', function () { stepAudio(-1); }); }
    var nextButton = byId('audioNext');
    if (nextButton) { nextButton.addEventListener('click', function () { stepAudio(1); }); }
    var closeAudioButton = byId('audioClose');
    if (closeAudioButton) {
        closeAudioButton.addEventListener('click', closeAudioPlayer);
    }
    updateMuteState();
    syncPlayState();

    if ('mediaSession' in navigator) {
        var mediaActions = {
            play: function () { playCurrentAudio(); },
            pause: function () { hideAudioPlayer(); },
            previoustrack: function () { stepAudio(-1); },
            nexttrack: function () { stepAudio(1); }
        };
        Object.keys(mediaActions).forEach(function (action) {
            try {
                navigator.mediaSession.setActionHandler(action, mediaActions[action]);
            } catch (e) {}
        });
    }

    /* ---- lightbox ---- */
    var lightbox = byId('lightbox');
    var lightboxImage = byId('lightboxImg');
    var lightboxCaption = byId('lightboxCaption');
    var lightboxCounter = byId('lightboxCounter');
    var lightboxDownload = byId('lightboxDownload');
    var lightboxPrev = byId('lightboxPrev');
    var lightboxNext = byId('lightboxNext');
    var lightboxStage = byId('lightboxStage');
    var lightboxZoomLabel = byId('lightboxZoom');
    var currentImage = 0;
    var lightboxZoom = 1;
    var lightboxPanX = 0;
    var lightboxPanY = 0;
    var lightboxBaseWidth = 0;
    var lightboxBaseHeight = 0;
    var lightboxBaseReady = false;
    var lightboxDragging = false;
    var lightboxDragStartX = 0;
    var lightboxDragStartY = 0;
    var lightboxDragPanX = 0;
    var lightboxDragPanY = 0;
    var lightboxMaxZoom = 20;

    function syncDialogBody() {
        body.classList.toggle('dialog-open', !!document.querySelector('dialog[open]'));
    }

    function measureLightboxBase() {
        if (!lightboxImage) { return; }
        var visibility = lightboxImage.style.visibility;
        lightboxImage.style.visibility = 'hidden';
        lightboxImage.classList.remove('is-zoomed', 'is-dragging');
        lightboxImage.style.width = '';
        lightboxImage.style.height = '';
        lightboxImage.style.transform = '';
        lightboxImage.style.imageRendering = '';
        void lightboxImage.offsetWidth;
        lightboxBaseWidth = lightboxImage.clientWidth;
        lightboxBaseHeight = lightboxImage.clientHeight;
        lightboxBaseReady = lightboxBaseWidth > 0 && lightboxBaseHeight > 0;
        lightboxImage.style.visibility = visibility;
    }

    function applyLightboxZoom() {
        if (!lightboxImage) { return; }
        if (lightboxZoom <= 1.001) {
            lightboxZoom = 1;
            lightboxPanX = 0;
            lightboxPanY = 0;
            lightboxBaseReady = false;
            lightboxImage.style.width = '';
            lightboxImage.style.height = '';
            lightboxImage.style.transform = '';
            lightboxImage.style.imageRendering = '';
            lightboxImage.classList.remove('is-zoomed', 'is-dragging');
            if (lightboxZoomLabel) {
                lightboxZoomLabel.textContent = '';
                lightboxZoomLabel.hidden = true;
            }
            return;
        }
        if (!lightboxBaseReady) {
            measureLightboxBase();
            if (!lightboxBaseReady) { return; }
        }
        var maxX = lightboxBaseWidth * (lightboxZoom - 1) / 2;
        var maxY = lightboxBaseHeight * (lightboxZoom - 1) / 2;
        lightboxPanX = Math.max(-maxX, Math.min(maxX, lightboxPanX));
        lightboxPanY = Math.max(-maxY, Math.min(maxY, lightboxPanY));

        /* Resize the element itself so the browser rasterises the image at
           the zoomed size with nearest-neighbour sampling. Transform-scaling
           keeps the already smoothed bitmap on the first zoom step. */
        lightboxImage.classList.add('is-zoomed');
        lightboxImage.style.imageRendering = 'pixelated';
        lightboxImage.style.width = (lightboxBaseWidth * lightboxZoom) + 'px';
        lightboxImage.style.height = (lightboxBaseHeight * lightboxZoom) + 'px';
        lightboxImage.style.transform = 'translate3d(' + lightboxPanX + 'px,' + lightboxPanY + 'px,0)';

        if (lightboxZoomLabel) {
            lightboxZoomLabel.textContent = Math.round(lightboxZoom * 100) + '%';
            lightboxZoomLabel.hidden = false;
        }
    }

    function resetLightboxZoom() {
        lightboxZoom = 1;
        lightboxPanX = 0;
        lightboxPanY = 0;
        lightboxBaseWidth = 0;
        lightboxBaseHeight = 0;
        lightboxBaseReady = false;
        lightboxDragging = false;
        if (lightboxImage) {
            lightboxImage.style.width = '';
            lightboxImage.style.height = '';
            lightboxImage.style.transform = '';
            lightboxImage.style.imageRendering = '';
            lightboxImage.style.visibility = '';
            lightboxImage.classList.remove('is-zoomed', 'is-dragging');
        }
        if (lightboxZoomLabel) {
            lightboxZoomLabel.textContent = '';
            lightboxZoomLabel.hidden = true;
        }
    }

    function zoomLightbox(event) {
        if (!lightbox || !lightbox.open || !lightboxImage || !lightboxStage) { return; }
        if (event.target && event.target.closest && event.target.closest('button, a')) { return; }
        event.preventDefault();
        if (!lightboxImage.clientWidth || !lightboxImage.clientHeight) { return; }

        var delta = event.deltaY;
        if (event.deltaMode === 1) { delta *= 16; }
        else if (event.deltaMode === 2) { delta *= 100; }
        var factor = Math.exp(-delta * 0.0015);
        var nextZoom = Math.min(lightboxMaxZoom, Math.max(1, lightboxZoom * factor));
        if (Math.abs(nextZoom - lightboxZoom) < 0.0001) { return; }

        var rect = lightboxImage.getBoundingClientRect();
        var centerX = (rect.left + rect.width / 2) - lightboxPanX;
        var centerY = (rect.top + rect.height / 2) - lightboxPanY;
        var pointerX = event.clientX - centerX;
        var pointerY = event.clientY - centerY;
        var ratio = nextZoom / lightboxZoom;
        lightboxPanX = lightboxPanX + (1 - ratio) * (pointerX - lightboxPanX);
        lightboxPanY = lightboxPanY + (1 - ratio) * (pointerY - lightboxPanY);
        lightboxZoom = nextZoom;
        applyLightboxZoom();
    }

    function startLightboxDrag(event) {
        if (lightboxZoom <= 1) { return; }
        if (event.pointerType === 'mouse' && event.button !== 0) { return; }
        event.preventDefault();
        lightboxDragging = true;
        lightboxDragStartX = event.clientX;
        lightboxDragStartY = event.clientY;
        lightboxDragPanX = lightboxPanX;
        lightboxDragPanY = lightboxPanY;
        lightboxImage.classList.add('is-dragging');
        try {
            if (lightboxImage.setPointerCapture) { lightboxImage.setPointerCapture(event.pointerId); }
        } catch (e) {}
    }

    function moveLightboxDrag(event) {
        if (!lightboxDragging) { return; }
        event.preventDefault();
        lightboxPanX = lightboxDragPanX + (event.clientX - lightboxDragStartX);
        lightboxPanY = lightboxDragPanY + (event.clientY - lightboxDragStartY);
        applyLightboxZoom();
    }

    function stopLightboxDrag(event) {
        if (!lightboxDragging) { return; }
        lightboxDragging = false;
        lightboxImage.classList.remove('is-dragging');
        try {
            if (lightboxImage.releasePointerCapture && lightboxImage.hasPointerCapture && lightboxImage.hasPointerCapture(event.pointerId)) {
                lightboxImage.releasePointerCapture(event.pointerId);
            }
        } catch (e) {}
    }

    function updateLightbox() {
        var image = DATA.images[currentImage];
        if (!image || !lightboxImage) { return; }
        /* Changing image keeps the zoom factor, but recenters the new image. */
        lightboxPanX = 0;
        lightboxPanY = 0;
        lightboxBaseReady = false;
        lightboxDragging = false;
        lightboxImage.classList.remove('is-zoomed', 'is-dragging');
        lightboxImage.style.width = '';
        lightboxImage.style.height = '';
        lightboxImage.style.transform = '';
        lightboxImage.style.imageRendering = '';
        /* At zoom > 1 the new image stays hidden until its fit size has been
           measured, then it is shown directly at the preserved zoom. */
        lightboxImage.style.visibility = lightboxZoom > 1 ? 'hidden' : '';
        lightboxImage.src = image.url;
        lightboxImage.alt = image.name;
        if (lightboxCaption) { lightboxCaption.textContent = image.name; }
        if (lightboxCounter) { lightboxCounter.textContent = (currentImage + 1) + ' / ' + DATA.images.length; }
        if (lightboxDownload) {
            lightboxDownload.href = image.url;
            lightboxDownload.setAttribute('download', image.name);
        }
        var multiple = DATA.images.length > 1;
        if (lightboxPrev) { lightboxPrev.hidden = !multiple; }
        if (lightboxNext) { lightboxNext.hidden = !multiple; }
        if (lightboxImage.complete && lightboxImage.naturalWidth) { handleLightboxImageLoad(); }
    }

    function openLightbox(index) {
        if (!lightbox || !DATA.images.length) { return; }
        currentImage = ((index % DATA.images.length) + DATA.images.length) % DATA.images.length;
        if (!lightbox.open) { resetLightboxZoom(); }
        updateLightbox();
        if (!lightbox.open) {
            try { lightbox.showModal(); } catch (e) { lightbox.setAttribute('open', ''); }
        }
        syncDialogBody();
        var close = byId('lightboxClose');
        if (close) { close.focus(); }
    }

    function stepImage(direction) {
        if (!DATA.images.length) { return; }
        currentImage = (currentImage + direction + DATA.images.length) % DATA.images.length;
        updateLightbox();
    }

    var lightboxCloseButton = byId('lightboxClose');
    if (lightboxCloseButton) { lightboxCloseButton.addEventListener('click', function () { if (lightbox) { lightbox.close(); } }); }
    if (lightboxPrev) { lightboxPrev.addEventListener('click', function () { stepImage(-1); }); }
    if (lightboxNext) { lightboxNext.addEventListener('click', function () { stepImage(1); }); }
    if (lightboxStage) {
        lightboxStage.addEventListener('click', function (event) {
            if (event.target === lightboxStage) { lightbox.close(); }
        });
        lightboxStage.addEventListener('wheel', zoomLightbox, { passive: false });
    }
    function handleLightboxImageLoad() {
        if (!lightboxImage) { return; }
        applyLightboxZoom();
        lightboxImage.style.visibility = '';
    }

    if (lightboxImage) {
        lightboxImage.addEventListener('pointerdown', startLightboxDrag, false);
        lightboxImage.addEventListener('pointermove', moveLightboxDrag, false);
        lightboxImage.addEventListener('pointerup', stopLightboxDrag, false);
        lightboxImage.addEventListener('pointercancel', stopLightboxDrag, false);
        lightboxImage.addEventListener('load', handleLightboxImageLoad, false);
        lightboxImage.addEventListener('error', function () {
            resetLightboxZoom();
            lightboxImage.style.visibility = '';
        }, false);
    }
    if (lightbox) {
        lightbox.addEventListener('close', function () {
            resetLightboxZoom();
            if (lightboxImage) { lightboxImage.removeAttribute('src'); }
            syncDialogBody();
        });
    }
    window.addEventListener('resize', function () {
        if (lightbox && lightbox.open) {
            lightboxBaseReady = false;
            applyLightboxZoom();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (!lightbox || !lightbox.open) { return; }
        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            stepImage(-1);
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            stepImage(1);
        }
    });

    /* ---- shortcuts dialog ---- */
    var shortcutsDialog = byId('shortcutsDialog');
    function openShortcuts() {
        if (!shortcutsDialog || shortcutsDialog.open) { return; }
        try { shortcutsDialog.showModal(); } catch (e) { shortcutsDialog.setAttribute('open', ''); }
        syncDialogBody();
    }
    Array.prototype.forEach.call(document.querySelectorAll('.js-shortcuts'), function (button) {
        button.addEventListener('click', openShortcuts);
    });
    Array.prototype.forEach.call(document.querySelectorAll('[data-close-dialog]'), function (button) {
        button.addEventListener('click', function () {
            var dialog = button.closest('dialog');
            if (dialog) { dialog.close(); }
        });
    });
    if (shortcutsDialog) {
        shortcutsDialog.addEventListener('close', syncDialogBody);
    }

    function isTyping(element) {
        if (!element) { return false; }
        var tag = element.tagName;
        return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || element.isContentEditable;
    }

    document.addEventListener('keydown', function (event) {
        if (event.defaultPrevented || event.ctrlKey || event.metaKey || event.altKey) { return; }
        var typing = isTyping(document.activeElement);
        var dialogOpen = !!document.querySelector('dialog[open]');
        if (event.key === '/' && !typing && !dialogOpen) {
            event.preventDefault();
            if (searchInput) { searchInput.focus(); searchInput.select(); }
        } else if ((event.key === 'g' || event.key === 'G') && !typing && !dialogOpen) {
            setView('grid', true);
        } else if ((event.key === 'l' || event.key === 'L') && !typing && !dialogOpen) {
            setView('list', true);
        } else if (event.key === '?' && !typing && !dialogOpen) {
            event.preventDefault();
            openShortcuts();
        } else if (event.key === 'Escape' && searchInput && document.activeElement === searchInput) {
            if (searchInput.value) {
                searchInput.value = '';
                applyFilter();
            } else {
                searchInput.blur();
            }
        }
    });

    window.addEventListener('resize', function () {
        if (!playerAudio.paused) { sizeAudioCanvas(); }
    });

})();
</script>
</body>
</html>
