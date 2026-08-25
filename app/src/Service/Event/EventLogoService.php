<?php
declare(strict_types=1);

namespace App\Service\Event;

use Cake\Core\Exception\CakeException;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Stores the event logo image uploaded on the SCR-OPR-2403 大会新規作成
 * "基本情報" step (mockup: "推奨サイズ：横1200px×縦630px (JPG/PNG)").
 * Files live under webroot/uploads/events/{id}/ so they're served directly
 * by Apache like the rest of webroot/, with no dedicated download action.
 */
class EventLogoService
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    private const MAX_BYTES = 5 * 1024 * 1024;

    /**
     * @param \Psr\Http\Message\UploadedFileInterface $file The uploaded file.
     * @param int $eventId The event this logo belongs to.
     * @param string|null $previousPath The event's current `logo` column value, if any, to delete.
     * @return string The relative path to store in `events.logo` (e.g. `uploads/events/12/logo.png`).
     * @throws \Cake\Core\Exception\CakeException When the file is missing, too large, or not an allowed image type.
     */
    public function store(UploadedFileInterface $file, int $eventId, ?string $previousPath): string
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new CakeException(__('画像のアップロードに失敗しました。'));
        }

        if ($file->getSize() === null || $file->getSize() > self::MAX_BYTES) {
            throw new CakeException(__('画像サイズは5MB以下にしてください。'));
        }

        $mimeType = $file->getClientMediaType();
        if ($mimeType === null || !isset(self::ALLOWED_MIME_TYPES[$mimeType])) {
            throw new CakeException(__('画像はJPGまたはPNG形式にしてください。'));
        }

        $directory = WWW_ROOT . 'uploads' . DS . 'events' . DS . $eventId;
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if ($previousPath !== null) {
            $this->delete($previousPath);
        }

        $extension = self::ALLOWED_MIME_TYPES[$mimeType];
        $filename = 'logo-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $file->moveTo($directory . DS . $filename);

        return 'uploads/events/' . $eventId . '/' . $filename;
    }

    /**
     * @param string $relativePath A value previously returned by {@see store()}.
     * @return void
     */
    public function delete(string $relativePath): void
    {
        $fullPath = WWW_ROOT . str_replace('/', DS, $relativePath);
        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }
}
