<?php
/**
 * Minimal styled .xlsx writer — no library, no zip extension needed (entries are stored, not compressed).
 * Gives exports the CampusVoice look: navy title band, gold rule, banded rows, colour-coded severity / status,
 * frozen header, filters and print-ready landscape pages.
 *
 *   $b = new XlsxBook();
 *   $s = $b->sheet('Concerns', [12, 40, 18]);            // column widths in characters
 *   $s->add([['Title', 'title']], 30);                   // [value, style] cells; a bare value uses 'text'
 *   $b->download('file.xlsx');
 */
class XlsxBook
{
    /** style name => [font, fill, border, horizontal, wrap]. Indexes refer to the lists in styles(). */
    const XF = [
        'default'  => [0, 0, 0, 'left', 0],
        'title'    => [1, 2, 0, 'left', 0],
        'band'     => [1, 2, 0, 'left', 0],
        'sub'      => [2, 0, 0, 'left', 0],
        'subband'  => [11, 2, 0, 'left', 0],
        'head'     => [3, 2, 3, 'center', 1],
        'headleft' => [3, 2, 3, 'left', 1],
        'text'     => [0, 0, 1, 'left', 1],
        'textz'    => [0, 3, 1, 'left', 1],
        'ctr'      => [0, 0, 1, 'center', 1],
        'ctrz'     => [0, 3, 1, 'center', 1],
        'minor'    => [6, 4, 1, 'center', 0],
        'major'    => [7, 5, 1, 'center', 0],
        'urgent'   => [8, 6, 1, 'center', 0],
        'st_open'  => [9, 7, 1, 'center', 0],
        'st_done'  => [6, 4, 1, 'center', 0],
        'st_wait'  => [7, 5, 1, 'center', 0],
        'st_bad'   => [8, 6, 1, 'center', 0],
        'st_off'   => [10, 8, 1, 'center', 0],
        'h2'       => [4, 0, 2, 'left', 0],
        'label'    => [5, 0, 1, 'left', 1],
        'num'      => [5, 0, 1, 'center', 0],
        'kpilabel' => [10, 3, 0, 'center', 1],
        'kpi'      => [12, 3, 0, 'center', 0],
    ];
    private $sheets = [];

    public function sheet(string $name, array $widths = [], string $tab = '0F2A5E'): XlsxSheet
    {
        $s = new XlsxSheet($name, $widths, $tab, array_keys(self::XF));
        $this->sheets[] = $s;
        return $s;
    }

    public function download(string $filename): void
    {
        $bin = $this->build();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($bin));
        header('Cache-Control: private, no-store');
        echo $bin;
    }

    public function build(): string
    {
        $files = [
            '[Content_Types].xml' => $this->contentTypes(),
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => $this->workbook(),
            'xl/_rels/workbook.xml.rels' => $this->workbookRels(),
            'xl/styles.xml' => $this->styles(),
        ];
        foreach ($this->sheets as $i => $s) $files['xl/worksheets/sheet' . ($i + 1) . '.xml'] = $s->xml();
        return self::zip($files);
    }

    private function contentTypes(): string
    {
        $o = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        foreach ($this->sheets as $i => $s) $o .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1) . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        return $o . '</Types>';
    }

    private function workbook(): string
    {
        $o = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView activeTab="0"/></bookViews><sheets>';
        foreach ($this->sheets as $i => $s) $o .= '<sheet name="' . XlsxSheet::esc($s->name) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
        return $o . '</sheets></workbook>';
    }

    private function workbookRels(): string
    {
        $o = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($this->sheets as $i => $s) $o .= '<Relationship Id="rId' . ($i + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
        return $o . '<Relationship Id="rId' . (count($this->sheets) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function styles(): string
    {
        $font = function ($sz, $rgb, $bold = false, $italic = false) {
            return '<font>' . ($bold ? '<b/>' : '') . ($italic ? '<i/>' : '') . '<sz val="' . $sz . '"/><color rgb="FF' . $rgb . '"/><name val="Calibri"/><family val="2"/></font>';
        };
        $fonts = [
            $font(11, '1F2937'),            // 0 body
            $font(18, 'FFFFFF', true),      // 1 title
            $font(10, '66758A', false, true), // 2 subtitle
            $font(10.5, 'FFFFFF', true),    // 3 header
            $font(12, '0F2A5E', true),      // 4 section
            $font(11, '1F2937', true),      // 5 bold
            $font(10.5, '0F7B4F', true),    // 6 green
            $font(10.5, 'B25E09', true),    // 7 amber
            $font(10.5, 'B42318', true),    // 8 red
            $font(10.5, '1F5FBF', true),    // 9 blue
            $font(10.5, '5F6B7A', true),    // 10 grey
            $font(10, 'D9A441', false, true), // 11 gold on navy
            $font(20, '0F2A5E', true),      // 12 big number
        ];
        $fill = function ($rgb) { return '<fill><patternFill patternType="solid"><fgColor rgb="FF' . $rgb . '"/><bgColor indexed="64"/></patternFill></fill>'; };
        $fills = ['<fill><patternFill patternType="none"/></fill>', '<fill><patternFill patternType="gray125"/></fill>',
            $fill('0F2A5E'), $fill('F4F6FB'), $fill('E4F5EC'), $fill('FDF0DC'), $fill('FDE8E6'), $fill('E6EFFC'), $fill('ECEFF3')];
        $edge = function ($side, $style, $rgb) { return '<' . $side . ' style="' . $style . '"><color rgb="FF' . $rgb . '"/></' . $side . '>'; };
        $none = '<left/><right/><top/><bottom/><diagonal/>';
        $borders = [
            '<border>' . $none . '</border>',
            '<border><left/><right/><top/>' . $edge('bottom', 'thin', 'E3E8F0') . '<diagonal/></border>',
            '<border><left/><right/><top/>' . $edge('bottom', 'medium', 'D9A441') . '<diagonal/></border>',
            '<border><left/><right/><top/>' . $edge('bottom', 'medium', 'D9A441') . '<diagonal/></border>',
        ];
        $xfs = '';
        foreach (self::XF as [$f, $fi, $b, $h, $w]) {
            $xfs .= '<xf numFmtId="0" fontId="' . $f . '" fillId="' . $fi . '" borderId="' . $b . '" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="' . $h . '" vertical="center"' . ($w ? ' wrapText="1"' : '') . '/></xf>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="' . count($fonts) . '">' . implode('', $fonts) . '</fonts>'
            . '<fills count="' . count($fills) . '">' . implode('', $fills) . '</fills>'
            . '<borders count="' . count($borders) . '">' . implode('', $borders) . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count(self::XF) . '">' . $xfs . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    /** Zip with stored (uncompressed) entries: plain PHP, works without ext-zip. */
    public static function zip(array $files): string
    {
        $out = ''; $central = ''; $n = 0;
        $t = getdate(); $dosTime = ($t['hours'] << 11) | ($t['minutes'] << 5) | (int)($t['seconds'] / 2);
        $dosDate = (($t['year'] - 1980) << 9) | ($t['mon'] << 5) | $t['mday'];
        foreach ($files as $name => $data) {
            $crc = crc32($data); $len = strlen($data); $off = strlen($out);
            $out .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, 0, $dosTime, $dosDate, $crc, $len, $len, strlen($name), 0) . $name . $data;
            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, 0, $dosTime, $dosDate, $crc, $len, $len, strlen($name), 0, 0, 0, 0, 0, $off) . $name;
            $n++;
        }
        return $out . $central . pack('VvvvvVVv', 0x06054b50, 0, 0, $n, $n, strlen($central), strlen($out), 0);
    }
}

class XlsxSheet
{
    public $name; private $widths; private $tab; private $styles;
    private $rows = []; private $merges = []; private $freeze = 0; private $filter = '';

    public function __construct(string $name, array $widths, string $tab, array $styleNames)
    {
        $this->name = mb_substr(preg_replace('/[\[\]\*\?\/\\\\:]/', ' ', $name), 0, 31);
        $this->widths = $widths; $this->tab = $tab; $this->styles = array_flip($styleNames);
    }

    public static function esc($v): string
    {
        $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string)$v);
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    public static function col(int $i): string          // 0 -> A
    {
        $s = ''; for ($i++; $i > 0; $i = intdiv($i - 1, 26)) $s = chr(65 + ($i - 1) % 26) . $s; return $s;
    }

    /** Add a row. Cell = value, or [value, styleName]. $fill = style used to paint empty cells out to $span columns. */
    public function add(array $cells, float $height = 0, int $span = 0, string $fill = 'default'): int
    {
        $r = count($this->rows) + 1;
        while ($span && count($cells) < $span) $cells[] = ['', $fill];
        $this->rows[] = [$cells, $height];
        return $r;
    }
    public function blank(float $h = 8): void { $this->rows[] = [[], $h]; }
    public function merge(string $range): void { $this->merges[] = $range; }
    public function freezeRows(int $n): void { $this->freeze = $n; }
    public function filter(string $range): void { $this->filter = $range; }
    public function rowCount(): int { return count($this->rows); }

    public function xml(): string
    {
        $o = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
           . '<sheetPr><tabColor rgb="FF' . $this->tab . '"/><pageSetUpPr fitToPage="1"/></sheetPr>'
           . '<sheetViews><sheetView showGridLines="0" workbookViewId="0">'
           . ($this->freeze ? '<pane ySplit="' . $this->freeze . '" topLeftCell="A' . ($this->freeze + 1) . '" activePane="bottomLeft" state="frozen"/>' : '')
           . '</sheetView></sheetViews><sheetFormatPr defaultRowHeight="20"/>';
        if ($this->widths) {
            $o .= '<cols>';
            foreach ($this->widths as $i => $w) $o .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
            $o .= '</cols>';
        }
        $o .= '<sheetData>';
        foreach ($this->rows as $ri => [$cells, $h]) {
            $r = $ri + 1;
            $o .= '<row r="' . $r . '"' . ($h ? ' ht="' . $h . '" customHeight="1"' : '') . '>';
            foreach ($cells as $ci => $cell) {
                [$v, $st] = is_array($cell) ? [$cell[0], $cell[1] ?? 'text'] : [$cell, 'text'];
                $s = $this->styles[$st] ?? 0;
                $ref = self::col($ci) . $r;
                if ($v === '' || $v === null) $o .= '<c r="' . $ref . '" s="' . $s . '"/>';
                elseif (is_int($v) || is_float($v)) $o .= '<c r="' . $ref . '" s="' . $s . '"><v>' . $v . '</v></c>';
                else $o .= '<c r="' . $ref . '" s="' . $s . '" t="inlineStr"><is><t xml:space="preserve">' . self::esc($v) . '</t></is></c>';
            }
            $o .= '</row>';
        }
        $o .= '</sheetData>';
        if ($this->filter) $o .= '<autoFilter ref="' . $this->filter . '"/>';
        if ($this->merges) { $o .= '<mergeCells count="' . count($this->merges) . '">'; foreach ($this->merges as $m) $o .= '<mergeCell ref="' . $m . '"/>'; $o .= '</mergeCells>'; }
        return $o . '<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/><pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/></worksheet>';
    }
}
