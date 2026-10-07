<?php

declare(strict_types=1);

namespace app\helpers;

use DateTimeImmutable;

final class OdsWriter
{
    private const MIMETYPE = 'application/vnd.oasis.opendocument.spreadsheet';
    public const CHECKED = '☑';
    public const UNCHECKED = '☐';

    // Estimation des largeurs de colonne (police par défaut, 10 pt)
    private const CHAR_WIDTH_CM = 0.19;
    private const MIN_WIDTH_CM = 1.8;
    private const MAX_WIDTH_CM = 9.0;
    private const CHECKBOX_WIDTH_CM = 1.6;

    /**
     * @param list<string> $header
     * @param list<list<string|int|float|bool|DateTimeImmutable|null>> $rows
     * @param int $hiddenLeadingColumns Nombre de colonnes de tête masquées (aucune protection)
     * @param bool $freezeHeader Fige la première ligne (titres) en haut de la feuille
     */
    public static function build(
        array $header,
        array $rows,
        string $sheetName,
        int $hiddenLeadingColumns = 0,
        bool $autoFilter = true,
        bool $freezeHeader = true
    ): string {
        $files = [
            // mimetype doit être la première entrée de l'archive, non compressée
            'mimetype' => self::MIMETYPE,
            'META-INF/manifest.xml' => self::manifest($freezeHeader),
            'content.xml' => self::content($header, $rows, $sheetName, max(0, $hiddenLeadingColumns), $autoFilter),
        ];
        if ($freezeHeader) {
            $files['settings.xml'] = self::settings($sheetName);
        }

        return self::zip($files);
    }

    private static function manifest(bool $withSettings): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0" manifest:version="1.2">'
            . '<manifest:file-entry manifest:full-path="/" manifest:media-type="' . self::MIMETYPE . '"/>'
            . '<manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>'
            . ($withSettings ? '<manifest:file-entry manifest:full-path="settings.xml" manifest:media-type="text/xml"/>' : '')
            . '</manifest:manifest>';
    }

    /**
     * État de la vue : fige la première ligne de la feuille.
     */
    private static function settings(string $sheetName): string
    {
        $name = self::esc($sheetName);
        $items = [
            ['HorizontalSplitMode', 'short', '0'],
            ['VerticalSplitMode', 'short', '2'],      // 2 = volet figé
            ['HorizontalSplitPosition', 'int', '0'],
            ['VerticalSplitPosition', 'int', '1'],    // 1 ligne figée
            ['ActiveSplitRange', 'short', '2'],
            ['PositionLeft', 'int', '0'],
            ['PositionRight', 'int', '0'],
            ['PositionTop', 'int', '0'],
            ['PositionBottom', 'int', '1'],
        ];
        $tableItems = '';
        foreach ($items as [$itemName, $type, $value]) {
            $tableItems .= '<config:config-item config:name="' . $itemName . '" config:type="' . $type . '">'
                . $value . '</config:config-item>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<office:document-settings'
            . ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
            . ' xmlns:config="urn:oasis:names:tc:opendocument:xmlns:config:1.0"'
            . ' xmlns:xlink="http://www.w3.org/1999/xlink"'
            . ' xmlns:ooo="http://openoffice.org/2004/office"'
            . ' office:version="1.2">'
            . '<office:settings>'
            . '<config:config-item-set config:name="ooo:view-settings">'
            . '<config:config-item-map-indexed config:name="Views">'
            . '<config:config-item-map-entry>'
            . '<config:config-item config:name="ViewId" config:type="string">view1</config:config-item>'
            . '<config:config-item-map-named config:name="Tables">'
            . '<config:config-item-map-entry config:name="' . $name . '">' . $tableItems . '</config:config-item-map-entry>'
            . '</config:config-item-map-named>'
            . '<config:config-item config:name="ActiveTable" config:type="string">' . $name . '</config:config-item>'
            . '</config:config-item-map-entry>'
            . '</config:config-item-map-indexed>'
            . '</config:config-item-set>'
            . '</office:settings>'
            . '</office:document-settings>';
    }

    /**
     * Archive ZIP minimale, sans compression (méthode « stored »), sans dépendance à ext-zip.
     *
     * @param array<string, string> $files nom de l'entrée => contenu
     */
    private static function zip(array $files): string
    {
        $now = getdate();
        $dosTime = ($now['hours'] << 11) | ($now['minutes'] << 5) | intdiv($now['seconds'], 2);
        $dosDate = (max(0, $now['year'] - 1980) << 9) | ($now['mon'] << 5) | $now['mday'];

        $local = '';
        $central = '';
        $count = 0;
        foreach ($files as $name => $data) {
            $name = (string)$name;
            $crc = crc32($data);
            $size = strlen($data);
            $nameLength = strlen($name);
            $offset = strlen($local);

            $local .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0)
                . $name . $data;
            $central .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                0,
                $dosTime,
                $dosDate,
                $crc,
                $size,
                $size,
                $nameLength,
                0,
                0,
                0,
                0,
                0,
                $offset
            ) . $name;
            $count++;
        }

        $end = pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($central), strlen($local), 0);

        return $local . $central . $end;
    }

    /**
     * @param list<string> $header
     * @param list<list<string|int|float|bool|DateTimeImmutable|null>> $rows
     */
    private static function content(
        array $header,
        array $rows,
        string $sheetName,
        int $hiddenLeadingColumns,
        bool $autoFilter
    ): string {
        $columnCount = count($header);
        $hidden = min($hiddenLeadingColumns, $columnCount);
        $quotedSheet = "'" . str_replace("'", "''", $sheetName) . "'";

        // Colonnes : une colonne masquée (Id) puis un style de largeur par colonne visible
        $widths = self::columnWidths($header, $rows, $autoFilter);
        $columnStyles = '';
        $columns = '';
        if ($hidden > 0) {
            $columns .= '<table:table-column table:number-columns-repeated="' . $hidden . '" table:visibility="collapse"/>';
        }
        for ($i = $hidden; $i < $columnCount; $i++) {
            $columnStyles .= '<style:style style:name="co' . $i . '" style:family="table-column">'
                . '<style:table-column-properties style:column-width="' . sprintf('%.3F', $widths[$i]) . 'cm"/>'
                . '</style:style>';
            $columns .= '<table:table-column table:style-name="co' . $i . '"/>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<office:document-content'
            . ' xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0"'
            . ' xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0"'
            . ' xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0"'
            . ' xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0"'
            . ' xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0"'
            . ' xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0"'
            . ' office:version="1.2">'
            . '<office:automatic-styles>'
            . $columnStyles
            . '<number:date-style style:name="NDate">'
            . '<number:day number:style="long"/><number:text>/</number:text>'
            . '<number:month number:style="long"/><number:text>/</number:text>'
            . '<number:year number:style="long"/>'
            . '</number:date-style>'
            . '<style:style style:name="ceDate" style:family="table-cell" style:parent-style-name="Default" style:data-style-name="NDate"/>'
            . '<style:style style:name="ceCheck" style:family="table-cell" style:parent-style-name="Default">'
            . '<style:paragraph-properties fo:text-align="center"/>'
            . '<style:text-properties fo:font-size="14pt"/>'
            . '</style:style>'
            . '<style:style style:name="ceHead" style:family="table-cell" style:parent-style-name="Default">'
            . '<style:text-properties fo:font-weight="bold"/>'
            . '</style:style>'
            . '</office:automatic-styles>'
            . '<office:body><office:spreadsheet>'
            . '<table:content-validations>'
            . '<table:content-validation table:name="valCheck"'
            . ' table:condition="of:cell-content-is-in-list(&quot;' . self::CHECKED . '&quot;;&quot;' . self::UNCHECKED . '&quot;)"'
            . ' table:allow-empty-cell="false" table:display-list="unsorted"'
            . ' table:base-cell-address="' . self::esc($quotedSheet) . '.A1">'
            . '<table:error-message table:message-type="stop" table:display="true">'
            . '<text:p>' . self::CHECKED . ' / ' . self::UNCHECKED . '</text:p>'
            . '</table:error-message>'
            . '</table:content-validation>'
            . '</table:content-validations>'
            . '<table:table table:name="' . self::esc($sheetName) . '">'
            . $columns;

        $xml .= '<table:table-row>';
        foreach ($header as $title) {
            $xml .= self::stringCell($title, 'ceHead');
        }
        $xml .= '</table:table-row>';

        foreach ($rows as $row) {
            $xml .= '<table:table-row>';
            foreach ($row as $value) {
                $xml .= self::cell($value);
            }
            $xml .= '</table:table-row>';
        }

        $xml .= '</table:table>';

        if ($autoFilter && $columnCount > 0) {
            $range = $quotedSheet . '.A1:' . $quotedSheet . '.' . self::columnLetter($columnCount) . (count($rows) + 1);
            $xml .= '<table:database-ranges>'
                . '<table:database-range table:name="__Anonymous_Sheet_DB__0"'
                . ' table:target-range-address="' . self::esc($range) . '"'
                . ' table:display-filter-buttons="true"/>'
                . '</table:database-ranges>';
        }

        return $xml . '</office:spreadsheet></office:body></office:document-content>';
    }

    /**
     * Largeur estimée (cm) de chaque colonne d'après son contenu, bornée.
     *
     * @param list<string> $header
     * @param list<list<string|int|float|bool|DateTimeImmutable|null>> $rows
     * @return list<float>
     */
    private static function columnWidths(array $header, array $rows, bool $autoFilter): array
    {
        $widths = [];
        foreach ($header as $index => $title) {
            // Titre en gras (+10 %) et place pour le bouton de l'auto-filtre
            $widths[$index] = mb_strlen($title) * self::CHAR_WIDTH_CM * 1.1 + ($autoFilter ? 0.8 : 0.0);
        }
        foreach ($rows as $row) {
            foreach ($row as $index => $value) {
                $widths[$index] = max($widths[$index] ?? 0.0, self::textWidth($value));
            }
        }

        return array_values(array_map(
            static fn(float $width): float => min(self::MAX_WIDTH_CM, max(self::MIN_WIDTH_CM, $width + 0.4)),
            $widths
        ));
    }

    private static function textWidth(string|int|float|bool|DateTimeImmutable|null $value): float
    {
        return match (true) {
            $value === null || $value === '' => 0.0,
            is_bool($value) => self::CHECKBOX_WIDTH_CM,
            $value instanceof DateTimeImmutable => 10 * self::CHAR_WIDTH_CM,
            is_int($value) || is_float($value) => strlen((string)$value) * self::CHAR_WIDTH_CM,
            default => mb_strlen($value) * self::CHAR_WIDTH_CM,
        };
    }

    private static function cell(string|int|float|bool|DateTimeImmutable|null $value): string
    {
        if ($value === null || $value === '') {
            return '<table:table-cell/>';
        }
        if (is_bool($value)) {
            return '<table:table-cell table:style-name="ceCheck" table:content-validation-name="valCheck"'
                . ' office:value-type="string"><text:p>'
                . ($value ? self::CHECKED : self::UNCHECKED) . '</text:p></table:table-cell>';
        }
        if ($value instanceof DateTimeImmutable) {
            return '<table:table-cell table:style-name="ceDate"'
                . ' office:value-type="date" office:date-value="' . $value->format('Y-m-d') . '"/>';
        }
        if (is_int($value) || is_float($value)) {
            return '<table:table-cell office:value-type="float" office:value="' . $value . '"/>';
        }
        return self::stringCell($value, null);
    }

    private static function stringCell(string $text, ?string $style): string
    {
        $styleAttr = $style === null ? '' : ' table:style-name="' . $style . '"';
        return '<table:table-cell' . $styleAttr . ' office:value-type="string"><text:p>'
            . self::esc($text) . '</text:p></table:table-cell>';
    }

    private static function columnLetter(int $number): string
    {
        $letters = '';
        while ($number > 0) {
            $number--;
            $letters = chr(65 + $number % 26) . $letters;
            $number = intdiv($number, 26);
        }
        return $letters;
    }

    private static function esc(string $text): string
    {
        // Retire les caractères interdits en XML 1.0, puis échappe
        $clean = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text) ?? '';
        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
