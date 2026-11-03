<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Escribe un .xlsx real sin dependencias: es un zip con unos pocos XML. Todas
 * las celdas van como texto, así Excel no daña los documentos (ceros a la
 * izquierda, notación científica), no cambia las fechas y no ejecuta como
 * fórmula algo que alguien haya escrito en un dato.
 */
class SpreadsheetWriter
{
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const DOC_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const RELS_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';

    /**
     * @param  array<string, array{headers: list<string>, rows: iterable<list<string|null>>}>  $sheets  nombre de la hoja => datos
     * @return string contenido del archivo
     */
    public static function build(array $sheets): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo de Excel.');
        }

        $names = array_keys($sheets);
        $zip->addFromString('[Content_Types].xml', self::contentTypes(count($names)));
        $zip->addFromString('_rels/.rels', self::xml('<Relationships xmlns="'.self::RELS_NS.'"><Relationship Id="rId1" Type="'.self::DOC_NS.'/officeDocument" Target="xl/workbook.xml"/></Relationships>'));
        $zip->addFromString('xl/workbook.xml', self::workbook($names));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels(count($names)));
        $zip->addFromString('xl/styles.xml', self::styles());

        foreach (array_values($sheets) as $i => $sheet) {
            $zip->addFromString('xl/worksheets/sheet'.($i + 1).'.xml', self::sheet($sheet['headers'], $sheet['rows']));
        }

        $zip->close();
        $content = (string) file_get_contents($path);
        unlink($path);

        return $content;
    }

    private static function xml(string $body): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n".$body;
    }

    private static function contentTypes(int $sheets): string
    {
        $overrides = '';
        for ($i = 1; $i <= $sheets; $i++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return self::xml('<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .$overrides.'</Types>');
    }

    /** @param  list<string>  $names */
    private static function workbook(array $names): string
    {
        $sheets = '';
        foreach ($names as $i => $name) {
            $id = $i + 1;
            $sheets .= '<sheet name="'.self::escape(mb_substr($name, 0, 31)).'" sheetId="'.$id.'" r:id="rId'.$id.'"/>';
        }

        return self::xml('<workbook xmlns="'.self::NS.'" xmlns:r="'.self::DOC_NS.'"><sheets>'.$sheets.'</sheets></workbook>');
    }

    private static function workbookRels(int $sheets): string
    {
        $rels = '';
        for ($i = 1; $i <= $sheets; $i++) {
            $rels .= '<Relationship Id="rId'.$i.'" Type="'.self::DOC_NS.'/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        }
        $rels .= '<Relationship Id="rId'.($sheets + 1).'" Type="'.self::DOC_NS.'/styles" Target="styles.xml"/>';

        return self::xml('<Relationships xmlns="'.self::RELS_NS.'">'.$rels.'</Relationships>');
    }

    /** Estilo 0: normal. Estilo 1: encabezado en negrilla con fondo azul claro. */
    private static function styles(): string
    {
        return self::xml('<styleSheet xmlns="'.self::NS.'">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFE6F0FB"/></patternFill></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>'
            .'</styleSheet>');
    }

    /**
     * @param  list<string>  $headers
     * @param  iterable<list<string|null>>  $rows
     */
    private static function sheet(array $headers, iterable $rows): string
    {
        $widths = array_map(fn ($h) => mb_strlen($h), $headers);
        $body = self::row(1, $headers, 1);
        $number = 1;

        foreach ($rows as $row) {
            $number++;
            $body .= self::row($number, $row, 0);
            foreach (array_values($row) as $i => $cell) {
                $widths[$i] = max($widths[$i] ?? 0, mb_strlen((string) $cell));
            }
        }

        $cols = '';
        foreach ($widths as $i => $width) {
            $cols .= '<col min="'.($i + 1).'" max="'.($i + 1).'" width="'.(min($width, 50) + 3).'" customWidth="1"/>';
        }
        $range = 'A1:'.self::column(count($headers) - 1).$number;

        // Encabezado fijo al bajar y filtros en cada columna
        return self::xml('<worksheet xmlns="'.self::NS.'">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols>'.$cols.'</cols><sheetData>'.$body.'</sheetData><autoFilter ref="'.$range.'"/></worksheet>');
    }

    /** @param  list<string|null>  $cells */
    private static function row(int $number, array $cells, int $style): string
    {
        $xml = '<row r="'.$number.'">';
        foreach (array_values($cells) as $i => $cell) {
            if ($cell === null || $cell === '') {
                continue;
            }
            $xml .= '<c r="'.self::column($i).$number.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.self::escape($cell).'</t></is></c>';
        }

        return $xml.'</row>';
    }

    /** 0 → A, 25 → Z, 26 → AA. */
    private static function column(int $index): string
    {
        $name = '';
        for ($i = $index; $i >= 0; $i = (int) ($i / 26) - 1) {
            $name = chr(65 + $i % 26).$name;
        }

        return $name;
    }

    /** Escapa para XML y quita los caracteres de control, que dañarían el archivo. */
    private static function escape(string $value): string
    {
        return htmlspecialchars(preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value) ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
