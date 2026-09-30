<?php
// backend/NextcloudClient.php

require_once __DIR__ . '/config.php';

class NextcloudClient {
    private string $baseUrl;
    private string $uploadsBaseUrl;
    private string $username;
    private string $password;

    public function __construct() {
        $this->baseUrl = rtrim(NEXTCLOUD_URL, '/') . '/';
        $this->username = NEXTCLOUD_USER;
        $this->password = NEXTCLOUD_PASS;
        
        // Nextcloud Native WebDAV Chunked Uploads URL (/remote.php/dav/uploads/USERNAME/)
        $this->uploadsBaseUrl = str_replace('/remote.php/dav/files/', '/remote.php/dav/uploads/', $this->baseUrl);
    }

    /**
     * Build properly URL-encoded WebDAV endpoint URL for cURL
     */
    private function buildUrl(string $path = '', bool $isUploadsApi = false): string {
        $base = $isUploadsApi ? $this->uploadsBaseUrl : $this->baseUrl;
        $path = ltrim($path, '/');
        if (empty($path)) {
            return $base;
        }
        $segments = explode('/', $path);
        $encodedSegments = array_map('rawurlencode', $segments);
        return $base . implode('/', $encodedSegments);
    }

    /**
     * Send HTTP WebDAV request using cURL with speed optimizations
     */
    private function request(string $method, string $path = '', array $headers = [], $body = null, bool $isUploadsApi = false) {
        $url = $this->buildUrl($path, $isUploadsApi);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        // Speed Optimizations for cURL WebDAV
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 1048576); // 1MB Buffer size
        curl_setopt($ch, CURLOPT_TCP_NODELAY, 1);      // Disable packet delay

        // Required headers for Nextcloud WebDAV API
        $defaultHeaders = [
            'OCS-APIREQUEST: true',
            'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password)
        ];
        $mergedHeaders = array_merge($defaultHeaders, $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $mergedHeaders);

        if ($body !== null) {
            if (is_resource($body)) {
                curl_setopt($ch, CURLOPT_INFILE, $body);
                curl_setopt($ch, CURLOPT_UPLOAD, true);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }

        return [
            'code' => $httpCode,
            'body' => $response
        ];
    }

    /**
     * Create folder on Nextcloud if it doesn't exist
     */
    public function ensureFolderExists(string $folderPath): bool {
        $res = $this->request('MKCOL', $folderPath);
        return in_array($res['code'], [201, 405]); // 201 Created, 405 Already exists
    }

    /**
     * List files in a Nextcloud directory using WebDAV PROPFIND
     */
    public function listDirectory(string $folderPath = ''): array {
        $headers = ['Depth: 1'];
        $res = $this->request('PROPFIND', $folderPath, $headers);

        if ($res['code'] !== 207) { // 207 Multi-Status
            return [];
        }

        return $this->parsePropfindXml($res['body'], $folderPath);
    }

    /**
     * Parse SabreDAV / Nextcloud PROPFIND XML response
     */
    private function parsePropfindXml(string $xmlContent, string $targetFolder): array {
        $files = [];
        // Handle XML namespaces
        $xmlContent = str_replace(['d:', 'oc:', 's:'], ['d_', 'oc_', 's_'], $xmlContent);
        $xml = @simplexml_load_string($xmlContent);

        if (!$xml) {
            return [];
        }

        foreach ($xml->d_response as $response) {
            $href = (string)$response->d_href;
            $prop = $response->d_propstat->d_prop ?? null;

            if (!$prop) continue;

            $isCollection = isset($prop->d_resourcetype->d_collection);
            
            // Get file name from href
            $decodedPath = rawurldecode($href);
            $fileName = basename($decodedPath);

            // Skip current directory itself
            if (trim(rtrim($decodedPath, '/'), '/') === trim(rtrim(parse_url($this->baseUrl, PHP_URL_PATH) . $targetFolder, '/'), '/')) {
                continue;
            }

            if (!$isCollection && !empty($fileName)) {
                $contentLength = (int)($prop->d_getcontentlength ?? 0);
                $lastModified = (string)($prop->d_getlastmodified ?? '');
                $contentType = (string)($prop->d_getcontenttype ?? 'video/mp4');

                // Generate stream URL via our PHP Proxy API
                $relativePath = ltrim($targetFolder . '/' . $fileName, '/');
                $streamUrl = BASE_API_URL . '/api/stream.php?file=' . urlencode($relativePath) . '&raw=1';

                $files[] = [
                    'name' => $fileName,
                    'path' => $relativePath,
                    'size' => $contentLength,
                    'formatted_size' => $this->formatBytes($contentLength),
                    'last_modified' => $lastModified,
                    'content_type' => $contentType,
                    'stream_url' => $streamUrl
                ];
            }
        }

        return $files;
    }

    /**
     * Upload a 5MB Chunk directly to Nextcloud's Native WebDAV Upload Session
     */
    public function uploadChunkToNextcloud(string $fileId, int $chunkIndex, string $localChunkPath): bool {
        // Ensure session directory exists on Nextcloud
        $sessionDir = $fileId;
        $this->request('MKCOL', $sessionDir, [], null, true);

        // Format chunk name with 8 digits (e.g. 00000000, 00000001)
        $chunkName = sprintf('%08d', $chunkIndex);
        $remoteChunkPath = $sessionDir . '/' . $chunkName;

        $fp = fopen($localChunkPath, 'r');
        $chunkSize = filesize($localChunkPath);
        $headers = [
            'Content-Type: application/octet-stream',
            'Content-Length: ' . $chunkSize
        ];

        $res = $this->request('PUT', $remoteChunkPath, $headers, $fp, true);
        if (is_resource($fp)) fclose($fp);

        return in_array($res['code'], [200, 201, 204]);
    }

    /**
     * Finalize Nextcloud Native Upload Session by MOVING assembled file to destination (Takes < 1 second!)
     */
    public function finalizeNextcloudChunkUpload(string $fileId, string $targetFilename): bool {
        $sourcePath = $fileId . '/.file';
        $destinationUrl = $this->buildUrl(MOVIES_FOLDER . '/' . $targetFilename, false);

        $headers = [
            'Destination: ' . $destinationUrl
        ];

        $res = $this->request('MOVE', $sourcePath, $headers, null, true);

        if (in_array($res['code'], [200, 201, 204])) {
            return true;
        }

        // Auto-recovery if target file is locked: append timestamp
        if ($res['code'] === 423 || (is_string($res['body']) && str_contains($res['body'], 'FileLocked'))) {
            $info = pathinfo($targetFilename);
            $newFilename = $info['filename'] . '_' . time() . (isset($info['extension']) ? '.' . $info['extension'] : '');
            $fallbackDest = $this->buildUrl(MOVIES_FOLDER . '/' . $newFilename, false);
            
            $headers2 = ['Destination: ' . $fallbackDest];
            $res2 = $this->request('MOVE', $sourcePath, $headers2, null, true);
            return in_array($res2['code'], [200, 201, 204]);
        }

        return false;
    }

    /**
     * Direct Upload a whole file to Nextcloud using PUT
     */
    public function uploadFile(string $remotePath, string $localFilePath): bool {
        if (!file_exists($localFilePath)) {
            throw new Exception("Local file not found: " . $localFilePath);
        }

        $fp = fopen($localFilePath, 'r');
        $fileSize = filesize($localFilePath);

        $headers = [
            'Content-Type: application/octet-stream',
            'Content-Length: ' . $fileSize
        ];

        $res = $this->request('PUT', $remotePath, $headers, $fp);
        if (is_resource($fp)) fclose($fp);

        return in_array($res['code'], [200, 201, 204]);
    }

    /**
     * Stream a file from Nextcloud to client supporting HTTP Range & HEAD requests for Safari/iOS inline playback
     */
    public function streamFile(string $remotePath, string $forcedMimeType = 'video/mp4') {
        $url = $this->buildUrl($remotePath);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        @ignore_user_abort(false);
        // Instant seek buffer (64KB chunks: 20ms initial frame delivery + high throughput)
        curl_setopt($ch, CURLOPT_TCP_NODELAY, 1);

        $isHeadRequest = ($_SERVER['REQUEST_METHOD'] === 'HEAD');
        if ($isHeadRequest) {
            curl_setopt($ch, CURLOPT_NOBODY, true);
        }

        // Clean headers: Only OCS-APIREQUEST and Range (cURL USERPWD handles auth cleanly)
        $headers = [
            'OCS-APIREQUEST: true'
        ];

        // Valid Range format check (supports bytes=start-end, bytes=start-, bytes=-suffix)
        $rawRange = $_SERVER['HTTP_RANGE'] ?? '';
        if (empty($rawRange) && function_exists('getallheaders')) {
            foreach (getallheaders() as $k => $v) {
                if (strcasecmp($k, 'Range') === 0) {
                    $rawRange = $v;
                    break;
                }
            }
        }
        if (empty($rawRange) && !empty($_GET['range'])) {
            $rawRange = $_GET['range'];
        }

        $rangeHeader = '';
        if (!$isHeadRequest && !empty($rawRange) && preg_match('/bytes\s*=\s*((\d+-\d*)|(-\d+))/i', trim($rawRange), $m)) {
            $rangeHeader = 'bytes=' . $m[1];
        }

        if (!empty($rangeHeader)) {
            $headers[] = 'Range: ' . $rangeHeader;
            http_response_code(206);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0); // Allow long streaming sessions

        $httpCode = 200;
        $isError = false;
        $filename = basename($remotePath);

        $isImage = str_starts_with($forcedMimeType, 'image/');
        header("Accept-Ranges: bytes");
        header("Content-Disposition: inline; filename=\"" . rawurlencode($filename) . "\"");
        header("Content-Type: " . $forcedMimeType);
        header("X-Accel-Buffering: no");
        header("X-LiteSpeed-Buffer: no");

        if ($isImage) {
            header("Cache-Control: public, max-age=604800, immutable");
        } else {
            // Streaming headers: Never send ETags or 86400 cache to prevent 304 Not Modified stall on range seek
            header("Cache-Control: no-cache, no-store, must-revalidate");
            header("Pragma: no-cache");
            header("Expires: 0");
        }

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$httpCode, &$isError, $forcedMimeType) {
            $len = strlen($header);
            $headerParts = explode(':', $header, 2);
            if (count($headerParts) == 2) {
                $headerName = strtolower(trim($headerParts[0]));
                $headerVal = trim($headerParts[1]);

                // Only forward essential byte-range & content-length headers
                if (!$isError && in_array($headerName, ['content-length', 'content-range', 'accept-ranges'])) {
                    if ($headerName === 'content-range') {
                        http_response_code(206);
                        header("Content-Range: " . $headerVal);
                    } elseif ($headerName === 'content-length') {
                        header("Content-Length: " . $headerVal);
                    } elseif ($headerName === 'accept-ranges') {
                        header("Accept-Ranges: " . $headerVal);
                    }
                } elseif ($headerName === 'content-type') {
                    header("Content-Type: " . $forcedMimeType);
                }
            } else {
                // Support both HTTP/1.1 and HTTP/2 status lines from Nextcloud
                if (preg_match('/^HTTP\/[\d\.]+\s+(\d+)/i', $header, $matches)) {
                    $httpCode = (int)$matches[1];
                    if ($httpCode === 206) {
                        http_response_code(206);
                    } elseif ($httpCode >= 400) {
                        $isError = true;
                        http_response_code($httpCode);
                    } else {
                        http_response_code(200);
                    }
                }
            }
            return $len;
        });

        // Clean PHP output buffers to prevent server-side buffering delay
        while (ob_get_level()) {
            ob_end_clean();
        }

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) use (&$isError, $isHeadRequest) {
            if ($isError || $isHeadRequest) {
                return strlen($data);
            }
            if (connection_status() !== CONNECTION_NORMAL) {
                return 0;
            }
            echo $data;
            flush();
            return strlen($data);
        });

        curl_exec($ch);
        curl_close($ch);

        if ($isError && !$isHeadRequest) {
            header('Content-Type: text/plain');
            echo "Error 404: File is not ready or still syncing to cloud storage. Please wait until upload completes.";
        }
        exit();
    }

    private function formatBytes(int $bytes, int $precision = 2): string {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
