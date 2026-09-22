<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use ZipArchive;

class ChatAttachment implements ValidationRule
{
    private const TYPES = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp',
        'pdf' => 'application/pdf', 'txt' => 'text/plain', 'doc' => 'application/msword',
        'xls' => 'application/vnd.ms-excel',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public static function mimeType(UploadedFile $file): string
    {
        return self::TYPES[strtolower($file->getClientOriginalExtension())];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile || !$value->isValid() || !$this->validContent($value)) {
            $fail('Adjunta JPG, PNG, WEBP, PDF, DOC, DOCX, XLS, XLSX o TXT con contenido válido.');
        }
    }

    private function validContent(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (!isset(self::TYPES[$extension])) {
            return false;
        }
        // Inspecciona los bytes, nunca el Content-Type enviado por el navegador.
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getPathname());
        if (in_array($extension, ['docx', 'xlsx'], true)) {
            return $this->validOfficePackage($file, $extension);
        }
        if (in_array($extension, ['doc', 'xls'], true)) {
            if (!in_array($mime, ['application/msword', 'application/vnd.ms-excel', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2', 'application/octet-stream'], true)) {
                return false;
            }
            $bytes = file_get_contents($file->getPathname());
            $streams = $extension === 'doc' ? ['WordDocument'] : ['Workbook', 'Book'];
            if (!str_starts_with($bytes, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")) {
                return false;
            }
            foreach ($streams as $stream) {
                if (str_contains($bytes, mb_convert_encoding($stream, 'UTF-16LE', 'UTF-8'))) {
                    return true;
                }
            }
            return false;
        }
        if ($mime !== self::TYPES[$extension]) {
            return false;
        }
        return !str_starts_with($mime, 'image/') || @getimagesize($file->getPathname()) !== false;
    }

    private function validOfficePackage(UploadedFile $file, string $extension): bool
    {
        // OOXML es un contenedor ZIP: un ZIP cualquiera renombrado no es un documento.
        $zip = new ZipArchive;
        if ($zip->open($file->getPathname(), ZipArchive::RDONLY) !== true) {
            return false;
        }
        try {
            $folder = $extension === 'docx' ? 'word' : 'xl';
            $document = $extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml';
            $mainType = $extension === 'docx'
                ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml';
            $types = $zip->getFromName('[Content_Types].xml', 65536);
            return $zip->locateName($document) !== false && $zip->locateName('_rels/.rels') !== false
                && $zip->locateName($folder . '/vbaProject.bin') === false
                && is_string($types) && str_contains($types, $mainType);
        } finally {
            $zip->close();
        }
    }
}
