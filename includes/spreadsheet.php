<?php
/**
 * Read a class masterlist into plain rows: .xlsx (Excel), .csv, or the first table of a .docx (Word).
 * No libraries needed — xlsx/docx are zip files of XML. Columns we don't use (NO., SIGNATURE, ...) are ignored.
 */

/** @return array{rows: array<int, array<int, string>>, title: string} */
function sheet_read(string $path, string $ext): array
{
    $ext = strtolower($ext);
    if ($ext === 'csv' || $ext === 'txt') return ['rows' => sheet_csv($path), 'title' => ''];
    $zip = class_exists('ZipArchive') ? new ZipArchive() : new SheetZip();
    if ($zip->open($path) !== true) throw new RuntimeException('The file could not be opened. Is it a real .xlsx, .docx or .csv file?');
    try {
        if ($ext === 'xlsx') return ['rows' => sheet_xlsx($zip), 'title' => ''];
        if ($ext === 'docx') return sheet_docx($zip);
    } finally {
        $zip->close();
    }
    throw new RuntimeException('Upload an .xlsx, .csv or .docx file.');
}

function sheet_xml(string $xml): SimpleXMLElement
{
    $prev = libxml_use_internal_errors(true);
    $x = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
    libxml_use_internal_errors($prev);
    if ($x === false) throw new RuntimeException('The file looks damaged.');
    return $x;
}

function sheet_csv(string $path): array
{
    $raw = (string)file_get_contents($path);
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);                 // Excel's UTF-8 BOM
    if (!mb_check_encoding($raw, 'UTF-8')) $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
    $first = strtok($raw, "\n") ?: '';
    $delim = substr_count($first, ';') > substr_count($first, ',') ? ';' : (substr_count($first, "\t") > substr_count($first, ',') ? "\t" : ',');
    $fh = fopen('php://memory', 'r+'); fwrite($fh, $raw); rewind($fh);
    $rows = [];
    while (($r = fgetcsv($fh, 0, $delim, '"', '\\')) !== false) $rows[] = array_map(fn($v) => trim((string)$v), $r);
    fclose($fh);
    return $rows;
}

function sheet_col_index(string $ref): int
{
    $letters = preg_replace('/\d+/', '', strtoupper($ref)); $n = 0;
    foreach (str_split($letters) as $ch) $n = $n * 26 + (ord($ch) - 64);
    return $n - 1;
}

function sheet_xlsx($zip): array
{
    $shared = [];
    if (($ss = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
        $x = sheet_xml($ss);
        foreach ($x->si as $si) {
            $t = '';
            if (isset($si->t)) $t = (string)$si->t;
            foreach ($si->r as $run) $t .= (string)$run->t;
            $shared[] = $t;
        }
    }
    // first worksheet in workbook order
    $sheetPath = 'xl/worksheets/sheet1.xml';
    $wb = $zip->getFromName('xl/workbook.xml'); $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($wb !== false && $rels !== false) {
        $w = sheet_xml($wb); $r = sheet_xml($rels);
        $first = $w->sheets->sheet[0] ?? null;
        if ($first) {
            $rid = (string)$first->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            foreach ($r->Relationship as $rel) {
                if ((string)$rel['Id'] === $rid) { $t = ltrim((string)$rel['Target'], '/'); $sheetPath = strpos($t, 'xl/') === 0 ? $t : 'xl/' . $t; }
            }
        }
    }
    $xml = $zip->getFromName($sheetPath);
    if ($xml === false) throw new RuntimeException('No worksheet found in this Excel file.');
    $x = sheet_xml($xml);
    $rows = [];
    foreach ($x->sheetData->row as $row) {
        $cells = [];
        foreach ($row->c as $c) {
            $type = (string)$c['t'];
            if ($type === 's') $v = $shared[(int)$c->v] ?? '';
            elseif ($type === 'inlineStr') $v = (string)($c->is->t ?? '');
            else $v = (string)$c->v;
            $cells[sheet_col_index((string)$c['r'])] = trim($v);
        }
        if (!$cells) continue;
        $line = array_fill(0, max(array_keys($cells)) + 1, '');
        foreach ($cells as $i => $v) $line[$i] = $v;
        $rows[] = $line;
    }
    return $rows;
}

function sheet_docx($zip): array
{
    $xml = $zip->getFromName('word/document.xml');
    if ($xml === false) throw new RuntimeException('This Word file has no content.');
    $x = sheet_xml($xml);
    $x->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    $text = function (SimpleXMLElement $node): string {
        $node->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        return trim(implode('', array_map('strval', $node->xpath('.//w:t') ?: [])));
    };
    $title = '';
    foreach ($x->xpath('//w:body/w:p') ?: [] as $p) { $t = $text($p); if ($t !== '') { $title = $t; break; } }
    $tbl = ($x->xpath('//w:tbl') ?: [])[0] ?? null;
    if ($tbl === null) throw new RuntimeException('No table was found in this Word file.');
    $tbl->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
    $rows = [];
    foreach ($tbl->xpath('./w:tr') ?: [] as $tr) {
        $tr->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $rows[] = array_map($text, $tr->xpath('./w:tc') ?: []);
    }
    return ['rows' => $rows, 'title' => $title];
}

const SHEET_HEADERS = [
    'school_id'  => ['STUDENTID', 'STUDENTNO', 'STUDENTNUMBER', 'IDNO', 'IDNUMBER', 'ID', 'USERNAME', 'SCHOOLID'],
    'full_name'  => ['FULLNAME', 'NAME', 'STUDENTNAME', 'NAMEOFSTUDENT', 'NAMES'],
    'last_name'  => ['LASTNAME', 'SURNAME', 'FAMILYNAME'],
    'first_name' => ['FIRSTNAME', 'GIVENNAME'],
    'middle'     => ['MI', 'MIDDLEINITIAL', 'MIDDLENAME', 'MIDDLE'],
    'course'     => ['COURSE', 'PROGRAM', 'COURSEPROGRAM'],
    'year_level' => ['YEAR', 'YEARLEVEL', 'YR', 'YRLEVEL'],
    'section'    => ['SECTION', 'SEC', 'BLOCK'],
    'email'      => ['EMAIL', 'EMAILADDRESS', 'UNIVERSITYEMAIL'],
];

/** Map header cells to fields. Unknown columns (NO., SIGNATURE, REMARKS...) are simply not mapped. */
function sheet_columns(array $header): array
{
    $map = [];
    foreach ($header as $i => $h) {
        $k = preg_replace('/[^A-Z]/', '', strtoupper($h));
        $field = null;                                         // NO., SIGNATURE, REMARKS... stay unmapped on purpose
        foreach (SHEET_HEADERS as $f => $names) if (in_array($k, $names, true)) { $field = $f; break; }
        if ($field && !isset($map[$field])) $map[$field] = $i;
    }
    return $map;
}

/** Find the header row (the first one with a student-ID column and a name column) within the first 15 rows. */
function sheet_find_header(array $rows): ?array
{
    foreach (array_slice($rows, 0, 15, true) as $i => $r) {
        $m = sheet_columns($r);
        if (isset($m['school_id']) && (isset($m['full_name']) || isset($m['last_name']))) return [$i, $m];
    }
    return null;
}

function sheet_title_case(string $s): string
{
    $s = mb_convert_case(mb_strtolower(trim(preg_replace('/\s+/', ' ', $s))), MB_CASE_TITLE, 'UTF-8');
    return preg_replace_callback("/(?<=[-'])\\p{Ll}/u", fn($m) => mb_strtoupper($m[0]), $s);
}

/**
 * "VILLANUEVA, ALREEN MIGUEL DE LEON" → last / first / middle initial.
 * $lastIsMiddle: treat the last word (or a "De Leon"-style pair) of the given names as the middle name.
 */
function sheet_split_name(string $full, bool $lastIsMiddle): array
{
    $full = trim(preg_replace('/\s+/', ' ', $full));
    if (strpos($full, ',') !== false) {
        [$last, $rest] = array_map('trim', explode(',', $full, 2));
    } else {                                                   // "Juan A. Dela Cruz" — last word is the surname
        $parts = explode(' ', $full); $last = array_pop($parts); $rest = implode(' ', $parts);
    }
    $words = $rest === '' ? [] : explode(' ', $rest);
    $mi = '';
    $lastWord = end($words);
    if ($words && preg_match('/^\p{L}\.?$/u', (string)$lastWord)) {                // explicit initial "A." / "A"
        $mi = mb_substr(array_pop($words), 0, 1);
    } elseif ($lastIsMiddle && count($words) >= 2) {
        $particles = ['DE', 'DEL', 'DELA', 'DELOS', 'DELAS', 'LA', 'LOS', 'SAN', 'STA', 'STA.', 'STO', 'STO.', 'DI', 'VAN', 'VON'];
        $mid = array_pop($words);
        while (count($words) >= 2 && in_array(mb_strtoupper(end($words)), $particles, true)) $mid = array_pop($words) . ' ' . $mid;
        $mi = mb_substr($mid, 0, 1);
    }
    return [sheet_title_case($last), sheet_title_case(implode(' ', $words)), mb_strtoupper($mi)];
}

/** Minimal read-only zip reader for hosts without the zip extension (stored + deflate entries only). */
class SheetZip
{
    private string $data = '';
    private array $entries = [];

    public function open(string $path)
    {
        $this->data = (string)@file_get_contents($path);
        $eocd = strrpos($this->data, "PK");
        if ($eocd === false || !function_exists('gzinflate')) return false;
        $e = unpack('vdisk/vcdisk/vthis/vtotal/Vsize/Voffset', substr($this->data, $eocd + 4, 16));
        $pos = $e['offset'];
        for ($i = 0; $i < $e['total']; $i++) {
            if (substr($this->data, $pos, 4) !== "PK") return false;
            $h = unpack('vmade/vneed/vflag/vmethod/vtime/vdate/Vcrc/Vcsize/Vsize/vnlen/velen/vclen/vdisk/vint/Vext/Vlocal', substr($this->data, $pos + 4, 42));
            $name = substr($this->data, $pos + 46, $h['nlen']);
            $this->entries[$name] = $h;
            $pos += 46 + $h['nlen'] + $h['elen'] + $h['clen'];
        }
        return true;
    }

    public function getFromName(string $name)
    {
        if (!isset($this->entries[$name])) return false;
        $h = $this->entries[$name];
        $l = unpack('vnlen/velen', substr($this->data, $h['local'] + 26, 4));
        $raw = substr($this->data, $h['local'] + 30 + $l['nlen'] + $l['elen'], $h['csize']);
        if ($h['method'] === 0) return $raw;
        if ($h['method'] === 8) { $out = @gzinflate($raw); return $out === false ? false : $out; }
        return false;
    }

    public function close(): void { $this->data = ''; $this->entries = []; }
}
