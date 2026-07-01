<?php
require_once '../app/Core/ApkParser.php';

class ReleaseController extends Controller {
    private $releaseModel;

    public function __construct() {
        // We only bypass auth for the api method
        $url = $this->parseUrl();
        $isApi = false;
        foreach ($url as $segment) {
            if (strpos($segment, 'api_') === 0) {
                $isApi = true;
                break;
            }
        }

        if (!$isApi) {
            if (!isset($_SESSION['user_id'])) {
                header('Location: ' . APP_URL . '/auth/login');
                exit;
            }
            if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Manager') {
                die("Access Denied: You do not have permission to view this module.");
            }
        }
        $this->releaseModel = $this->model('AppRelease');
    }

    private function parseUrl() {
        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return [];
    }

    public function index() {
        $releases = $this->releaseModel->getAllReleases();
        
        // Suggest next version
        $suggestedVersion = '1.0.0';
        if (!empty($releases)) {
            $latest = $releases[0]; // Ordered by major, minor, patch DESC
            $parts = explode('.', $latest->version);
            if (count($parts) === 3) {
                $patch = intval($parts[2]) + 1;
                $suggestedVersion = $parts[0] . '.' . $parts[1] . '.' . $patch;
            }
        }

        $data = [
            'title' => 'App Release Management',
            'content_view' => 'release/index',
            'releases' => $releases,
            'suggested_version' => $suggestedVersion,
            'error' => $_SESSION['release_error'] ?? '',
            'success' => $_SESSION['release_success'] ?? ''
        ];

        unset($_SESSION['release_error']);
        unset($_SESSION['release_success']);

        $this->view('layouts/main', $data);
    }

    public function upload() {
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        error_log("--- APK Upload Trace Start ---");
        error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST fields: " . json_encode($_POST));
        error_log("FILES metadata: " . json_encode($_FILES));

        $maxUpload = ini_get('upload_max_filesize');
        $maxPost = ini_get('post_max_size');

        // Check if content length exceeded post_max_size
        if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
            $err = "Upload failed: The file size exceeds the server's post_max_size limit ({$maxPost}). Please update your php.ini.";
            error_log($err);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $err]);
                exit;
            } else {
                $_SESSION['release_error'] = $err;
                header('Location: ' . APP_URL . '/release');
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
                exit;
            }
            header('Location: ' . APP_URL . '/release');
            exit;
        }

        $releaseNotes = trim($_POST['release_notes'] ?? '');
        $forceUpdate = isset($_POST['force_update']) ? 1 : 0;
        $isLatest = isset($_POST['is_latest']) ? 1 : 0;

        // Validate file upload
        if (!isset($_FILES['apk']) || $_FILES['apk']['error'] !== UPLOAD_ERR_OK) {
            $errorCode = $_FILES['apk']['error'] ?? UPLOAD_ERR_NO_FILE;
            $errMapping = [
                UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini (Limit: {$maxUpload}).",
                UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form.",
                UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded.",
                UPLOAD_ERR_NO_FILE => "No file was uploaded.",
                UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder for uploads.",
                UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
                UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload."
            ];
            $err = $errMapping[$errorCode] ?? "Unknown upload error code: {$errorCode}";
            error_log("APK file upload error: " . $err);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $err]);
                exit;
            } else {
                $_SESSION['release_error'] = $err;
                header('Location: ' . APP_URL . '/release');
                exit;
            }
        }

        $fileTmpPath = $_FILES['apk']['tmp_name'];
        $fileName = $_FILES['apk']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension !== 'apk') {
            $err = "Invalid file type. Only .apk files are allowed.";
            error_log($err);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $err]);
                exit;
            } else {
                $_SESSION['release_error'] = $err;
                header('Location: ' . APP_URL . '/release');
                exit;
            }
        }

        // Create releases folder if not exists
        $uploadDir = '../public/releases';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Extract APK metadata
        try {
            $parser = new ApkParser($fileTmpPath);
            $apkMeta = $parser->parse();
            if (empty($apkMeta['versionName']) || empty($apkMeta['versionCode'])) {
                throw new Exception("Unable to extract Version Name or Build Version (Version Code) from APK metadata.");
            }
        } catch (Exception $e) {
            $err = "APK Metadata Extraction Failed: " . $e->getMessage();
            error_log($err);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $err]);
                exit;
            } else {
                $_SESSION['release_error'] = $err;
                header('Location: ' . APP_URL . '/release');
                exit;
            }
        }

        $versionName = $apkMeta['versionName'];
        $versionCode = intval($apkMeta['versionCode']);
        $packageName = $apkMeta['packageName'];
        $appName = $apkMeta['appName'];

        // Validate if build version already exists
        $existing = $this->releaseModel->getReleaseByBuildVersion($versionCode);
        if ($existing) {
            $err = "Upload Rejected: A release with build version {$versionCode} (version name {$existing->version_name}) already exists.";
            error_log($err);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $err]);
                exit;
            } else {
                $_SESSION['release_error'] = $err;
                header('Location: ' . APP_URL . '/release');
                exit;
            }
        }

        $parts = explode('.', $versionName);
        $major = isset($parts[0]) ? intval($parts[0]) : 1;
        $minor = isset($parts[1]) ? intval($parts[1]) : 0;
        $patch = isset($parts[2]) ? intval($parts[2]) : 0;

        $newFileName = 'app-v' . $versionName . '.apk';
        $destPath = $uploadDir . '/' . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $apkRelativePath = 'public/releases/' . $newFileName;

            $releaseData = [
                'version' => $versionName,
                'build_version' => $versionCode,
                'version_name' => $versionName,
                'package_name' => $packageName,
                'app_name' => $appName,
                'major' => $major,
                'minor' => $minor,
                'patch' => $patch,
                'release_notes' => $releaseNotes,
                'apk_path' => $apkRelativePath,
                'force_update' => $forceUpdate,
                'is_latest' => $isLatest
            ];

            try {
                $id = $this->releaseModel->addRelease($releaseData);
                if ($id) {
                    $msg = "New release v{$versionName} (Build {$versionCode}) uploaded and registered successfully.";
                    error_log($msg);
                    
                    if ($isLatest) {
                        // Copy to latest.apk
                        $latestPath = $uploadDir . '/latest.apk';
                        copy($destPath, $latestPath);
                    }

                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => $msg]);
                        exit;
                    } else {
                        $_SESSION['release_success'] = $msg;
                    }
                } else {
                    $err = "Failed to save release to database.";
                    error_log($err);
                    unlink($destPath); // Remove the file
                    
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => $err]);
                        exit;
                    } else {
                        $_SESSION['release_error'] = $err;
                    }
                }
            } catch (Exception $e) {
                $err = "Error registering release: " . $e->getMessage();
                error_log($err);
                if (file_exists($destPath)) {
                    unlink($destPath);
                }

                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $err]);
                    exit;
                } else {
                    $_SESSION['release_error'] = $err;
                }
            }
        } else {
            $err = "Failed to upload APK file. Check directory permissions on standard public/releases folder.";
            error_log($err);
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $err]);
                exit;
            } else {
                $_SESSION['release_error'] = $err;
            }
        }

        error_log("--- APK Upload Trace End ---");
        header('Location: ' . APP_URL . '/release');
        exit;
    }

    public function set_latest($id) {
        $release = $this->releaseModel->getReleaseById($id);
        if ($release) {
            if ($this->releaseModel->setLatestRelease($id)) {
                // Copy the versioned file to latest.apk
                $uploadDir = '../public/releases';
                $versionedFile = $uploadDir . '/app-v' . $release->version . '.apk';
                $latestPath = $uploadDir . '/latest.apk';
                if (file_exists($versionedFile)) {
                    copy($versionedFile, $latestPath);
                }
                $_SESSION['release_success'] = "Release v{$release->version} marked as latest.";
            } else {
                $_SESSION['release_error'] = "Failed to update database.";
            }
        } else {
            $_SESSION['release_error'] = "Release not found.";
        }
        header('Location: ' . APP_URL . '/release');
        exit;
    }

    public function delete($id) {
        $release = $this->releaseModel->getReleaseById($id);
        if ($release) {
            $uploadDir = '../public/releases';
            $versionedFile = $uploadDir . '/app-v' . $release->version . '.apk';
            
            // Delete versioned file
            if (file_exists($versionedFile)) {
                unlink($versionedFile);
            }

            $wasLatest = $release->is_latest;

            if ($this->releaseModel->deleteRelease($id)) {
                $_SESSION['release_success'] = "Release v{$release->version} deleted successfully.";

                // If deleted release was the latest one, set the next highest version as latest
                if ($wasLatest) {
                    $latestPath = $uploadDir . '/latest.apk';
                    if (file_exists($latestPath)) {
                        unlink($latestPath);
                    }

                    $remaining = $this->releaseModel->getAllReleases();
                    if (!empty($remaining)) {
                        $newLatest = $remaining[0];
                        $this->releaseModel->setLatestRelease($newLatest->id);
                        
                        $newLatestFile = $uploadDir . '/app-v' . $newLatest->version . '.apk';
                        if (file_exists($newLatestFile)) {
                            copy($newLatestFile, $latestPath);
                        }
                    }
                }
            } else {
                $_SESSION['release_error'] = "Failed to delete release from database.";
            }
        } else {
            $_SESSION['release_error'] = "Release not found.";
        }
        header('Location: ' . APP_URL . '/release');
        exit;
    }

    // Version API for Mobile App (Bypasses Session Login checks)
    public function api_latest_version() {
        header('Content-Type: application/json');
        
        $latest = $this->releaseModel->getLatestRelease();
        if ($latest) {
            // Process release notes into an array of strings
            $notes = [];
            if (!empty($latest->release_notes)) {
                $rawNotes = explode("\n", $latest->release_notes);
                foreach ($rawNotes as $line) {
                    $trimmed = trim($line);
                    if ($trimmed !== '') {
                        $notes[] = $trimmed;
                    }
                }
            }

            // Calculate MD5 of versioned APK first, fallback to latest.apk
            $apkMd5 = '';
            $versionedApkPath = '../' . $latest->apk_path;
            if (file_exists($versionedApkPath)) {
                $apkMd5 = md5_file($versionedApkPath);
            } else {
                $latestApkPath = '../public/releases/latest.apk';
                if (file_exists($latestApkPath)) {
                    $apkMd5 = md5_file($latestApkPath);
                }
            }

            $major = intval($latest->major);
            $minor = intval($latest->minor);
            $patch = intval($latest->patch);
            $latestVersionCode = intval($latest->build_version ?: ($major * 100 + $minor * 10 + $patch));
            $latestVersionCodeNormalized = intval($latest->build_version ?: ($major * 10000 + $minor * 100 + $patch));

            echo json_encode([
                'latestVersion' => $latest->version_name ?: $latest->version,
                'latestVersionCode' => $latestVersionCode,
                'latestVersionCodeNormalized' => $latestVersionCodeNormalized,
                'apkUrl' => APP_URL . '/releases/latest.apk?v=' . ($latest->version_name ?: $latest->version) . '&t=' . time(),
                'forceUpdate' => (bool)$latest->force_update,
                'releaseNotes' => $notes,
                'apkMd5' => $apkMd5,
                'packageName' => $latest->package_name ?: 'com.example.curtiss'
            ]);
        } else {
            // Suggest default if no releases uploaded yet
            echo json_encode([
                'latestVersion' => '1.0.0',
                'latestVersionCode' => 100,
                'latestVersionCodeNormalized' => 10000,
                'apkUrl' => APP_URL . '/releases/latest.apk',
                'forceUpdate' => false,
                'releaseNotes' => ['Initial release'],
                'apkMd5' => '',
                'packageName' => 'com.example.curtiss'
            ]);
        }
        exit;
    }

    // Chunked Upload Handlers to bypass server-side file upload limits (e.g. 2MB)
    public function upload_chunk() {
        header('Content-Type: application/json');
        
        $tempToken = trim($_POST['temp_token'] ?? '');
        $chunkIndex = intval($_POST['chunk_index'] ?? 0);
        
        if (empty($tempToken)) {
            echo json_encode(['success' => false, 'error' => 'Missing temporary upload token.']);
            exit;
        }

        if (!isset($_FILES['chunk']) || $_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Chunk file upload error or exceeded server limits.']);
            exit;
        }

        $uploadDir = '../public/releases/temp';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $tempChunkPath = $uploadDir . "/temp_{$tempToken}_{$chunkIndex}";
        if (move_uploaded_file($_FILES['chunk']['tmp_name'], $tempChunkPath)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save temporary chunk on server.']);
        }
        exit;
    }

    public function assemble_chunks() {
        header('Content-Type: application/json');
        
        $tempToken = trim($_POST['temp_token'] ?? '');
        $releaseNotes = trim($_POST['release_notes'] ?? '');
        $forceUpdate = (isset($_POST['force_update']) && $_POST['force_update'] === '1') ? 1 : 0;
        $isLatest = (isset($_POST['is_latest']) && $_POST['is_latest'] === '1') ? 1 : 0;
        $totalChunks = intval($_POST['total_chunks'] ?? 0);

        if (empty($tempToken)) {
            echo json_encode(['success' => false, 'error' => 'Missing temporary upload token.']);
            exit;
        }

        $uploadDir = '../public/releases';
        $tempDir = $uploadDir . '/temp';
        
        $assembledFileName = 'assembled_' . $tempToken . '.apk';
        $assembledPath = $tempDir . '/' . $assembledFileName;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // Open final file for writing in binary append mode
        $finalFile = fopen($assembledPath, 'wb');
        if (!$finalFile) {
            echo json_encode(['success' => false, 'error' => 'Failed to write final assembled file on server. Check folder permissions.']);
            exit;
        }

        // Stitch chunk files together in order
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $tempDir . "/temp_{$tempToken}_{$i}";
            if (!file_exists($chunkPath)) {
                fclose($finalFile);
                if (file_exists($assembledPath)) {
                    unlink($assembledPath);
                }
                echo json_encode(['success' => false, 'error' => "Missing temporary chunk index {$i} on server."]);
                exit;
            }

            $chunkData = file_get_contents($chunkPath);
            fwrite($finalFile, $chunkData);
            unlink($chunkPath); // clean up chunk file immediately
        }

        fclose($finalFile);

        // Parse APK metadata
        try {
            $parser = new ApkParser($assembledPath);
            $apkMeta = $parser->parse();
            if (empty($apkMeta['versionName']) || empty($apkMeta['versionCode'])) {
                throw new Exception("Unable to extract Version Name or Build Version (Version Code) from APK metadata.");
            }
        } catch (Exception $e) {
            if (file_exists($assembledPath)) {
                unlink($assembledPath);
            }
            echo json_encode(['success' => false, 'error' => 'APK Metadata Extraction Failed: ' . $e->getMessage()]);
            exit;
        }

        $versionName = $apkMeta['versionName'];
        $versionCode = intval($apkMeta['versionCode']);
        $packageName = $apkMeta['packageName'];
        $appName = $apkMeta['appName'];

        // Validate if build version already exists
        $existing = $this->releaseModel->getReleaseByBuildVersion($versionCode);
        if ($existing) {
            if (file_exists($assembledPath)) {
                unlink($assembledPath);
            }
            echo json_encode(['success' => false, 'error' => "Upload Rejected: A release with build version {$versionCode} (version name {$existing->version_name}) already exists."]);
            exit;
        }

        $parts = explode('.', $versionName);
        $major = isset($parts[0]) ? intval($parts[0]) : 1;
        $minor = isset($parts[1]) ? intval($parts[1]) : 0;
        $patch = isset($parts[2]) ? intval($parts[2]) : 0;

        $finalFileName = 'app-v' . $versionName . '.apk';
        $finalPath = $uploadDir . '/' . $finalFileName;

        // Move to final path
        if (!rename($assembledPath, $finalPath)) {
            if (file_exists($assembledPath)) {
                unlink($assembledPath);
            }
            echo json_encode(['success' => false, 'error' => 'Failed to move assembled APK to final path. Check folder permissions.']);
            exit;
        }

        // Save metadata to database
        $apkRelativePath = 'public/releases/' . $finalFileName;
        $releaseData = [
            'version' => $versionName,
            'build_version' => $versionCode,
            'version_name' => $versionName,
            'package_name' => $packageName,
            'app_name' => $appName,
            'major' => $major,
            'minor' => $minor,
            'patch' => $patch,
            'release_notes' => $releaseNotes,
            'apk_path' => $apkRelativePath,
            'force_update' => $forceUpdate,
            'is_latest' => $isLatest
        ];

        try {
            $id = $this->releaseModel->addRelease($releaseData);
            if ($id) {
                if ($isLatest) {
                    $latestPath = $uploadDir . '/latest.apk';
                    copy($finalPath, $latestPath);
                }
                echo json_encode(['success' => true, 'message' => "New release v{$versionName} (Build {$versionCode}) uploaded and assembled successfully."]);
            } else {
                if (file_exists($finalPath)) {
                    unlink($finalPath);
                }
                echo json_encode(['success' => false, 'error' => 'Failed to register release in database.']);
            }
        } catch (Exception $e) {
            if (file_exists($finalPath)) {
                unlink($finalPath);
            }
            echo json_encode(['success' => false, 'error' => 'Database registration error: ' . $e->getMessage()]);
        }
        exit;
    }
}
