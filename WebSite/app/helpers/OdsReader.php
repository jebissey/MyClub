<?php

declare(strict_types=1);

namespace app\helpers;

use RuntimeException;
use XMLReader;

final class OdsReader
{
    private const MIMETYPE = 'application/vnd.oasis.opendocument.spreadsheet';
    private const NS_OFFICE = 'urn:oasis:names:tc:opendocument:xmlns:office:1.0';
    private const NS_TABLE = 'urn:oasis:names:tc:opendocument:xmlns:table:1.0';
    private const NS_TEXT = 'urn:oasis:names:tc:opendocument:xmlns:text:1.0';
    private const MAX_FILE_BYTES = 10 * 1024 * 1024;
    private const MAX_XML_BYTES = 64 * 1024 * 1024;
    private const MAX_ROWS = 20000;
    private const MAX_COLUMNS = 200;

    /**
     * Lit la première feuille d'un fichier .ods.
     * Seules les cellules non vides sont retournées ; les dates sont en AAAA-MM-JJ,
     * les nombres en notation brute, les booléens en true/false.
     *
     * @return array<int, array<int, string>> n° de ligne (1 = première) => [index de colonne (0 = A) => valeur]
     */
    public static function readFirstSheet(string $filePath): array
    {
        $size = filesize($filePath);
        if ($size === false || $size > self::MAX_FILE_BYTES) {
            throw new RuntimeException('ODS file too large or unreadable');
        }
        $binary = file_get_contents($filePath);
        if ($binary === false) {
            throw new RuntimeException('ODS file unreadable');
        }

        $entries = self::zipEntries($binary);
        if (
            !isset($entries['mimetype'], $entries['content.xml'])
            || trim(self::zipExtract($binary, $entries['mimetype'])) !== self::MIMETYPE
        ) {
            throw new RuntimeException('Not an ODS file');
        }

        return self::parseSheet(self::zipExtract($binary, $entries['content.xml']));
    }

    /** @return array<string, array{method: int, compressedSize: int, size: int, offset: int}> */
    private static function zipEntries(string $zip): array
    {
        $eocd = strrpos($zip, "PK\x05\x06");
        if ($eocd === false || strlen($zip) < $eocd + 22) {
            throw new RuntimeException('Not a ZIP archive');
        }
        $end = unpack('vdisk/vcdDisk/vdiskEntries/ventries/VcdSize/VcdOffset', substr($zip, $eocd + 4, 16));
        if ($end === false) {
            throw new RuntimeException('Invalid ZIP archive');
        }

        $entries = [];
        $position = (int)$end['cdOffset'];
        for ($i = 0; $i < (int)$end['entries']; $i++) {
            if (substr($zip, $position, 4) !== "PK\x01\x02") {
                throw new RuntimeException('Invalid ZIP central directory');
            }
            $h = unpack(
                'vmadeBy/vneeded/vflags/vmethod/vtime/vdate/Vcrc/VcompressedSize/Vsize/vnameLength/'
                    . 'vextraLength/vcommentLength/vdisk/vinternal/Vexternal/Voffset',
                substr($zip, $position + 4, 42)
            );
            if ($h === false) {
                throw new RuntimeException('Invalid ZIP entry');
            }
            $name = substr($zip, $position + 46, (int)$h['nameLength']);
            $entries[$name] = [
                'method' => (int)$h['method'],
                'compressedSize' => (int)$h['compressedSize'],
                'size' => (int)$h['size'],
                'offset' => (int)$h['offset'],
            ];
            $position += 46 + (int)$h['nameLength'] + (int)$h['extraLength'] + (int)$h['commentLength'];
        }
        return $entries;
    }

    /** @param array{method: int, compressedSize: int, size: int, offset: int} $entry */
    private static function zipExtract(string $zip, array $entry): string
    {
        if ($entry['size'] > self::MAX_XML_BYTES) {
            throw new RuntimeException('ZIP entry too large');
        }
        if (substr($zip, $entry['offset'], 4) !== "PK\x03\x04") {
            throw new RuntimeException('Invalid ZIP local header');
        }
        $local = unpack('vnameLength/vextraLength', substr($zip, $entry['offset'] + 26, 4));
        if ($local === false) {
            throw new RuntimeException('Invalid ZIP local header');
        }
        $start = $entry['offset'] + 30 + (int)$local['nameLength'] + (int)$local['extraLength'];
        $data = substr($zip, $start, $entry['compressedSize']);

        if ($entry['method'] === 0) {
            return $data;
        }
        if ($entry['method'] === 8) {
            $inflated = function_exists('gzinflate') ? gzinflate($data, self::MAX_XML_BYTES) : false;
            if ($inflated === false) {
                throw new RuntimeException('Unable to inflate ZIP entry');
            }
            return $inflated;
        }
        throw new RuntimeException('Unsupported ZIP compression method');
    }

    /** @return array<int, array<int, string>> */
    private static function parseSheet(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        $reader = new XMLReader();
        try {
            if (!$reader->XML($xml, null, LIBXML_NONET)) {
                throw new RuntimeException('Invalid content.xml');
            }

            $rows = [];
            $rowNumber = 0;
            $tableDepth = null;
            while ($reader->read()) {
                if ($reader->namespaceURI !== self::NS_TABLE) {
                    continue;
                }
                if ($tableDepth === null) {
                    if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'table') {
                        $tableDepth = $reader->depth;
                    }
                    continue;
                }
                if (
                    $reader->nodeType === XMLReader::END_ELEMENT
                    && $reader->localName === 'table'
                    && $reader->depth === $tableDepth
                ) {
                    break; // seule la première feuille est lue
                }
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'table-row') {
                    continue;
                }

                $repeat = max(1, (int)($reader->getAttributeNs('number-rows-repeated', self::NS_TABLE) ?? '1'));
                $cells = self::readRow($reader);
                if ($cells === []) {
                    $rowNumber += $repeat; // lignes vides (souvent répétées des milliers de fois)
                    continue;
                }
                // Deux lignes identiques adjacentes sont écrites une seule fois avec un compteur de répétition
                for ($i = 0; $i < $repeat; $i++) {
                    $rowNumber++;
                    $rows[$rowNumber] = $cells;
                    if (count($rows) > self::MAX_ROWS) {
                        throw new RuntimeException('Too many rows');
                    }
                }
            }

            foreach (libxml_get_errors() as $error) {
                if ($error->level >= LIBXML_ERR_ERROR) {
                    throw new RuntimeException('Malformed content.xml');
                }
            }
            return $rows;
        } finally {
            $reader->close();
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /** @return array<int, string> */
    private static function readRow(XMLReader $reader): array
    {
        if ($reader->isEmptyElement) {
            return [];
        }
        $rowDepth = $reader->depth;
        $cells = [];
        $column = 0;
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->depth === $rowDepth) {
                break;
            }
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->namespaceURI !== self::NS_TABLE) {
                continue;
            }
            $name = $reader->localName;
            if ($name !== 'table-cell' && $name !== 'covered-table-cell') {
                continue;
            }
            $repeat = max(1, (int)($reader->getAttributeNs('number-columns-repeated', self::NS_TABLE) ?? '1'));
            $value = $name === 'table-cell' ? self::readCell($reader) : '';
            if ($value !== '') {
                for ($i = 0; $i < $repeat && $column + $i < self::MAX_COLUMNS; $i++) {
                    $cells[$column + $i] = $value;
                }
            }
            $column += $repeat;
        }
        return $cells;
    }

    private static function readCell(XMLReader $reader): string
    {
        $valueType = $reader->getAttributeNs('value-type', self::NS_OFFICE) ?? '';
        $officeValue = $reader->getAttributeNs('value', self::NS_OFFICE);
        $dateValue = $reader->getAttributeNs('date-value', self::NS_OFFICE);
        $boolValue = $reader->getAttributeNs('boolean-value', self::NS_OFFICE);
        $stringValue = $reader->getAttributeNs('string-value', self::NS_OFFICE);

        $paragraphs = [];
        if (!$reader->isEmptyElement) {
            $cellDepth = $reader->depth;
            $buffer = null;
            $paragraphDepth = -1;
            $skipDepth = null;
            while ($reader->read()) {
                $nodeType = $reader->nodeType;
                if ($nodeType === XMLReader::END_ELEMENT && $reader->depth === $cellDepth) {
                    break;
                }
                if ($skipDepth !== null) { // commentaire de cellule (office:annotation) ignoré
                    if ($nodeType === XMLReader::END_ELEMENT && $reader->depth === $skipDepth) {
                        $skipDepth = null;
                    }
                    continue;
                }
                if ($nodeType === XMLReader::ELEMENT) {
                    if ($reader->namespaceURI === self::NS_OFFICE && $reader->localName === 'annotation') {
                        if (!$reader->isEmptyElement) {
                            $skipDepth = $reader->depth;
                        }
                        continue;
                    }
                    if ($reader->namespaceURI !== self::NS_TEXT) {
                        continue;
                    }
                    switch ($reader->localName) {
                        case 'p':
                        case 'h':
                            if ($reader->isEmptyElement) {
                                $paragraphs[] = '';
                            } else {
                                $buffer = '';
                                $paragraphDepth = $reader->depth;
                            }
                            break;
                        case 's':
                            if ($buffer !== null) {
                                $buffer .= str_repeat(' ', min(1000, max(1, (int)($reader->getAttributeNs('c', self::NS_TEXT) ?? '1'))));
                            }
                            break;
                        case 'tab':
                            $buffer = $buffer === null ? null : $buffer . "\t";
                            break;
                        case 'line-break':
                            $buffer = $buffer === null ? null : $buffer . "\n";
                            break;
                    }
                } elseif (
                    $buffer !== null && in_array($nodeType, [
                    XMLReader::TEXT,
                    XMLReader::SIGNIFICANT_WHITESPACE,
                    XMLReader::CDATA
                    ], true)
                ) {
                    $buffer .= $reader->value;
                } elseif ($nodeType === XMLReader::END_ELEMENT && $buffer !== null && $reader->depth === $paragraphDepth) {
                    $paragraphs[] = $buffer;
                    $buffer = null;
                }
            }
        }

        $text = trim(implode("\n", $paragraphs));
        return trim(match ($valueType) {
            'float', 'percentage', 'currency' => $officeValue ?? $text,
            'date' => substr($dateValue ?? $text, 0, 10),
            'boolean' => $boolValue ?? $text,
            default => $text !== '' ? $text : ($stringValue ?? ''),
        });
    }
}
