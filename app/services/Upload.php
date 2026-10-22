<?php
declare(strict_types=1);

/** Validated file uploads stored under uploads/ with random names. */
final class Upload
{
    private const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    private const DOCUMENT_TYPES = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

    public static function has(string $field): bool
    {
        return isset($_FILES[$field]) && is_array($_FILES[$field]) && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    public static function image(string $field, string $folder): string
    {
        return self::store($field, $folder, self::IMAGE_TYPES, (int)config('uploads.max_image_mb', 5), t('Please upload a JPG, PNG, WEBP or GIF image.'));
    }

    public static function document(string $field, string $folder): string
    {
        return self::store($field, $folder, self::DOCUMENT_TYPES, (int)config('uploads.max_document_mb', 5), t('The document must be a PDF, JPG or PNG file.'));
    }

    public static function delete(?string $path): void
    {
        if ($path && str_starts_with($path, 'uploads/')) {
            $full = BASE_PATH . '/' . $path;
            if (is_file($full)) {
                @unlink($full);
            }
        }
    }

    private static function store(string $field, string $folder, array $types, int $maxMb, string $typeMessage): string
    {
        $file = $_FILES[$field] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException(t('Please choose a file to upload.'));
        }
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE || $file['size'] > $maxMb * 1024 * 1024) {
            throw new RuntimeException(t('The file must be smaller than :size MB.', ['size' => $maxMb]));
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException(t('The file could not be uploaded. Please try again.'));
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
        if (!isset($types[$mime])) {
            throw new RuntimeException($typeMessage);
        }
        $dir = BASE_PATH . '/uploads/' . $folder;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = bin2hex(random_bytes(12)) . '.' . $types[$mime];
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            throw new RuntimeException(t('The file could not be saved. Please try again.'));
        }
        return 'uploads/' . $folder . '/' . $name;
    }
}
