<?php
namespace App\Services\Lab;

class DicomWebService
{
    public function __construct(
        private string $wadoBaseUrl = '',
        private string $stowBaseUrl = '',
    ) {
        if (!$this->wadoBaseUrl) {
            $this->wadoBaseUrl = config('services.pacs.wado_url', '');
        }
        if (!$this->stowBaseUrl) {
            $this->stowBaseUrl = config('services.pacs.stow_url', '');
        }
    }

    public function buildWadoUrl(string $studyUid): string
    {
        return rtrim($this->wadoBaseUrl, '/') . '/studies/' . urlencode($studyUid);
    }

    public function buildSeriesUrl(string $studyUid, string $seriesUid): string
    {
        return $this->buildWadoUrl($studyUid) . '/series/' . urlencode($seriesUid);
    }
}
