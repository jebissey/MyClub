<?php

declare(strict_types=1);

namespace app\modules\Common\valueObjects;

/**
 * The files received from a multi-file upload field, already filtered:
 * only the files uploaded without error are kept.
 */
final readonly class UploadedFilesInput
{
    /**
     * @param list<UploadedFileInput> $files
     */
    public function __construct(public array $files)
    {
    }

    /**
     * @param array<mixed, mixed>|null $phpFiles one entry of $_FILES
     */
    public static function fromPhpFiles(?array $phpFiles): self
    {
        if (
            $phpFiles === null || !isset(
                $phpFiles['tmp_name'],
                $phpFiles['error'],
                $phpFiles['name'],
                $phpFiles['type'],
                $phpFiles['size'],
            )
        ) {
            return new self([]);
        }

        $tmpNames = $phpFiles['tmp_name'];
        $errors = $phpFiles['error'];
        $names = $phpFiles['name'];
        $types = $phpFiles['type'];
        $sizes = $phpFiles['size'];
        if (!is_array($tmpNames) || !is_array($errors) || !is_array($names) || !is_array($types) || !is_array($sizes)) {
            return new self([]);
        }

        $files = [];
        foreach ($tmpNames as $index => $tmpName) {
            if (($errors[$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $name = $names[$index] ?? null;
            $type = $types[$index] ?? null;
            $size = $sizes[$index] ?? null;
            if (!is_string($tmpName) || !is_string($name) || !is_string($type) || !is_int($size)) {
                continue;
            }
            $files[] = new UploadedFileInput(name: $name, tmpName: $tmpName, size: $size, type: $type);
        }

        return new self($files);
    }
}
