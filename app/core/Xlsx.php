<?php

/**
 * Minimal, dependency-free XLSX (Office Open XML) writer.
 * Multiple sheets, styled header row, frozen header, auto-filter, column widths,
 * typed cells (int, decimal, percent, date, text) and red/amber/green status cells.
 *
 *   $x = new Xlsx('TUV EAST IMS');
 *   $x->sheet('Summary', ['KPI' => 'text', 'Value' => 'dec', 'Status' => 'rag'], $rows, ['title' => 'Admin KPIs', 'subtitle' => '01-Oct-2026 – 14-Oct-2026']);
 *   $x->download('admin-kpis');
 */
final class Xlsx
{
    private array $sheets = [];
    private string $creator;

    // cellXfs indices (see styles())
    private const S_DEFAULT = 0, S_HEADER = 1, S_INT = 2, S_DEC = 3, S_PCT = 4, S_DATE = 5, S_TITLE = 6,
                  S_GREEN = 7, S_AMBER = 8, S_RED = 9, S_BOLD = 10, S_MUTED = 11, S_DATETIME = 12;

    public function __construct(string $creator = 'TUV EAST IMS')
    {
        $this->creator = $creator;
    }

    /**
     * @param array $columns ['Header' => type] type: text | int | dec | pct (0-1 or 0-100 with pct100) | pct100 | date | datetime | rag
     * @param array $rows    list of arrays in column order (or assoc arrays matching header order)
     * @param array $opt     title, subtitle, widths (list), filter (bool, default true)
     */
    public function sheet(string $name, array $columns, array $rows, array $opt = []): self
    {
        $name = mb_substr(preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $name), 0, 31);
        $this->sheets[] = compact('name', 'columns', 'rows', 'opt');
        return $this;
    }

    public function download(string $filename): void
    {
        $tmp = $this->build();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . preg_replace('/[^\w\-]+/', '-', $filename) . '-' . date('Ymd') . '.xlsx"');
        header('Content-Length: ' . filesize($tmp));
        header('Cache-Control: private, no-store');
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    /** Builds the workbook into a temp file and returns its path. */
    public function build(): string
    {
        if (!$this->sheets) $this->sheet('Sheet1', ['Info' => 'text'], [['No data']]);
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $n = count($this->sheets);

        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>';
        for ($i = 1; $i <= $n; $i++) $ct .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $zip->addFromString('[Content_Types].xml', $ct . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>');
        $now = gmdate('Y-m-d\TH:i:s\Z');
        $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>' . $this->x($this->creator) . '</dc:creator><dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created></cp:coreProperties>');
        $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>TUV EAST IMS</Application></Properties>');

        $wb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $defined = '';
        $names = [];
        foreach ($this->sheets as $i => $s) {
            $nm = $s['name'] ?: 'Sheet' . ($i + 1);
            $base = $nm; $k = 2;
            while (isset($names[mb_strtolower($nm)])) $nm = mb_substr($base, 0, 28) . ' ' . $k++;
            $names[mb_strtolower($nm)] = true;
            $wb .= '<sheet name="' . $this->x($nm) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
            $rels .= '<Relationship Id="rId' . ($i + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
            [$xml, $filterRef] = $this->sheetXml($s);
            if ($filterRef) $defined .= '<definedName name="_xlnm._FilterDatabase" localSheetId="' . $i . '" hidden="1">\'' . str_replace("'", "''", $this->x($nm)) . '\'!' . $filterRef . '</definedName>';
            $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $xml);
        }
        $wb .= '</sheets>' . ($defined ? '<definedNames>' . $defined . '</definedNames>' : '') . '</workbook>';
        $rels .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $zip->addFromString('xl/workbook.xml', $wb);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $rels);
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->close();
        return $tmp;
    }

    private function sheetXml(array $s): array
    {
        $cols = array_keys($s['columns']);
        $types = array_values($s['columns']);
        $nc = count($cols);
        $rowsXml = '';
        $r = 0;
        if (!empty($s['opt']['title'])) {
            $r++;
            $rowsXml .= '<row r="' . $r . '" ht="22" customHeight="1">' . $this->str($r, 0, $s['opt']['title'], self::S_TITLE) . '</row>';
        }
        if (!empty($s['opt']['subtitle'])) {
            $r++;
            $rowsXml .= '<row r="' . $r . '">' . $this->str($r, 0, $s['opt']['subtitle'], self::S_MUTED) . '</row>';
        }
        if ($r) $r++;                                   // blank spacer row
        $headerRow = $r + 1;
        $r++;
        $rowsXml .= '<row r="' . $r . '" ht="20" customHeight="1">';
        foreach ($cols as $c => $h) $rowsXml .= $this->str($r, $c, (string) $h, self::S_HEADER);
        $rowsXml .= '</row>';

        $widths = $s['opt']['widths'] ?? [];
        $auto = array_map(fn ($h) => min(60, max(10, mb_strlen((string) $h) + 4)), $cols);
        foreach ($s['rows'] as $row) {
            $row = array_values((array) $row);
            $r++;
            $rowsXml .= '<row r="' . $r . '">';
            for ($c = 0; $c < $nc; $c++) {
                $v = $row[$c] ?? null;
                $rowsXml .= $this->cell($r, $c, $v, $types[$c]);
                if (!isset($widths[$c]) && $v !== null && !is_array($v)) $auto[$c] = min(60, max($auto[$c], mb_strlen((string) $v) + 2));
            }
            $rowsXml .= '</row>';
        }
        $colsXml = '<cols>';
        for ($c = 0; $c < $nc; $c++) $colsXml .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . ($widths[$c] ?? $auto[$c]) . '" customWidth="1"/>';
        $colsXml .= '</cols>';
        $last = $this->ref($r, $nc - 1);
        $filter = ($s['opt']['filter'] ?? true) && $r > $headerRow ? '$A$' . $headerRow . ':$' . $this->col($nc - 1) . '$' . $r : '';
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<dimension ref="A1:' . $last . '"/>'
            . '<sheetViews><sheetView workbookViewId="0"' . ($s['opt']['grid'] ?? true ? '' : ' showGridLines="0"') . '><pane ySplit="' . $headerRow . '" topLeftCell="A' . ($headerRow + 1) . '" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>' . $colsXml . '<sheetData>' . $rowsXml . '</sheetData>'
            . ($filter ? '<autoFilter ref="' . str_replace('$', '', $filter) . '"/>' : '')
            . '<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/><pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/></worksheet>';
        return [$xml, $filter];
    }

    private function cell(int $r, int $c, $v, string $type): string
    {
        if ($v === null || $v === '') return '';
        $ref = $this->ref($r, $c);
        switch ($type) {
            case 'int':
                return is_numeric($v) ? '<c r="' . $ref . '" s="' . self::S_INT . '"><v>' . (0 + $v) . '</v></c>' : $this->str($r, $c, $v);
            case 'num':        // whole numbers as integers, otherwise one decimal
                if (!is_numeric($v)) return $this->str($r, $c, $v);
                return '<c r="' . $ref . '" s="' . ((float) $v == floor((float) $v) ? self::S_INT : self::S_DEC) . '"><v>' . round((float) $v, 4) . '</v></c>';
            case 'dec':
                return is_numeric($v) ? '<c r="' . $ref . '" s="' . self::S_DEC . '"><v>' . round((float) $v, 4) . '</v></c>' : $this->str($r, $c, $v);
            case 'pct':        // value 0..1
            case 'pct100':     // value 0..100
                if (!is_numeric($v)) return $this->str($r, $c, $v);
                $f = $type === 'pct100' ? $v / 100 : (float) $v;
                return '<c r="' . $ref . '" s="' . self::S_PCT . '"><v>' . round($f, 6) . '</v></c>';
            case 'date':
            case 'datetime':
                $ts = is_numeric($v) ? (int) $v : strtotime((string) $v);
                if (!$ts) return $this->str($r, $c, $v);
                $serial = 25569 + ($ts + (int) date('Z', $ts)) / 86400;
                if ($type === 'date') $serial = floor($serial);
                return '<c r="' . $ref . '" s="' . ($type === 'date' ? self::S_DATE : self::S_DATETIME) . '"><v>' . round($serial, 6) . '</v></c>';
            case 'rag':
                $map = ['green' => self::S_GREEN, 'on target' => self::S_GREEN, 'amber' => self::S_AMBER, 'watch' => self::S_AMBER, 'red' => self::S_RED, 'off target' => self::S_RED];
                return $this->str($r, $c, (string) $v, $map[strtolower((string) $v)] ?? self::S_DEFAULT);
            case 'bold':
                return $this->str($r, $c, (string) $v, self::S_BOLD);
            default:
                return $this->str($r, $c, (string) $v);
        }
    }

    private function str(int $r, int $c, string $v, int $style = self::S_DEFAULT): string
    {
        // guard against spreadsheet formula injection from user-entered text
        if ($v !== '' && strpbrk($v[0], '=+-@') !== false && !is_numeric($v)) $v = "'" . $v;
        return '<c r="' . $this->ref($r, $c) . '" t="inlineStr"' . ($style ? ' s="' . $style . '"' : '') . '><is><t xml:space="preserve">' . $this->x($v) . '</t></is></c>';
    }

    private function col(int $c): string
    {
        $s = '';
        for ($c++; $c > 0; $c = intdiv($c - 1, 26)) $s = chr(65 + ($c - 1) % 26) . $s;
        return $s;
    }

    private function ref(int $r, int $c): string
    {
        return $this->col($c) . $r;
    }

    private function x(string $s): string
    {
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s) ?? '';
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="4"><numFmt numFmtId="164" formatCode="#,##0.0"/><numFmt numFmtId="165" formatCode="0.0%"/><numFmt numFmtId="166" formatCode="dd-mmm-yyyy"/><numFmt numFmtId="167" formatCode="dd-mmm-yyyy hh:mm"/></numFmts>'
            . '<fonts count="8">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="15"/><color rgb="FF24418F"/><name val="Calibri"/></font>'
            . '<font><sz val="11"/><color rgb="FF006100"/><name val="Calibri"/></font>'
            . '<font><sz val="11"/><color rgb="FF9C5700"/><name val="Calibri"/></font>'
            . '<font><sz val="11"/><color rgb="FF9C0006"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><i/><sz val="10"/><color rgb="FF64748B"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="6"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF24418F"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFC6EFCE"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFEB9C"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFC7CE"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFD0D7E2"/></left><right style="thin"><color rgb="FFD0D7E2"/></right><top style="thin"><color rgb="FFD0D7E2"/></top><bottom style="thin"><color rgb="FFD0D7E2"/></bottom><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="13">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'                                                          // 0 default
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>' // 1 header
            . '<xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'                                    // 2 int
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'                                  // 3 decimal
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'                                  // 4 percent
            . '<xf numFmtId="166" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'                                  // 5 date
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                                            // 6 title
            . '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'              // 7 green
            . '<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'              // 8 amber
            . '<xf numFmtId="0" fontId="5" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'              // 9 red
            . '<xf numFmtId="0" fontId="6" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                                            // 10 bold
            . '<xf numFmtId="0" fontId="7" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                                            // 11 muted
            . '<xf numFmtId="167" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'                                  // 12 datetime
            . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }
}
