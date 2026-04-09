<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_login();

require_once __DIR__ . '/includes/dashboard_shell.php';

$cfg = trackify_config();
$exiftoolBin = trim((string)($cfg['exiftool_bin'] ?? 'exiftool'));
if ($exiftoolBin === '') {
    $exiftoolBin = 'exiftool';
}

$error = '';
$result = null;
$rawOutput = '';
$uploadedName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        http_response_code(419);
        $error = 'Invalid CSRF token. Please refresh and try again.';
    } elseif (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
        $error = 'No file was uploaded.';
    } else {
        $f = $_FILES['image'];
        $uploadedName = (string)($f['name'] ?? '');

        $uploadErr = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadErr !== UPLOAD_ERR_OK) {
            $error = match ($uploadErr) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large.',
                UPLOAD_ERR_PARTIAL => 'Upload was interrupted. Please try again.',
                UPLOAD_ERR_NO_FILE => 'No file was selected.',
                default => 'Upload failed (error code ' . $uploadErr . ').',
            };
        } else {
            $tmp = (string)($f['tmp_name'] ?? '');
            if ($tmp === '' || !is_uploaded_file($tmp)) {
                $error = 'Upload failed (temporary file missing).';
            } else {
                $size = (int)($f['size'] ?? 0);
                if ($size <= 0) {
                    $error = 'Upload failed (empty file).';
                } elseif ($size > 25 * 1024 * 1024) {
                    $error = 'Please upload an image up to 25 MB.';
                } else {
                    $mime = '';
                    if (class_exists('finfo')) {
                        $fi = new finfo(FILEINFO_MIME_TYPE);
                        $mime = (string)@$fi->file($tmp);
                    }
                    if ($mime !== '' && !str_starts_with($mime, 'image/')) {
                        $error = 'Only image uploads are allowed.';
                    } else {
                        $dir = __DIR__ . '/data/exiftool_uploads';
                        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
                            $error = 'Server error: cannot create upload directory.';
                        } else {
                            $ext = '';
                            $nameLower = strtolower($uploadedName);
                            if (preg_match('/\.(jpg|jpeg|png|webp|gif|tif|tiff|heic|heif)$/', $nameLower, $m)) {
                                $ext = '.' . $m[1];
                            }
                            $dest = $dir . '/exif_' . bin2hex(random_bytes(16)) . $ext;

                            if (!@move_uploaded_file($tmp, $dest)) {
                                $error = 'Server error: failed to store uploaded file.';
                            } else {
                                // Ask exiftool for a "complete" dump:
                                // -j: JSON output
                                // -G: include group names
                                // -a: allow duplicate tags
                                // -u: include unknown tags
                                // -ee: extract embedded metadata (common for videos / some images)
                                $cmd = escapeshellarg($exiftoolBin) . ' -j -G -a -u -ee ' . escapeshellarg($dest) . ' 2>&1';
                                $lines = [];
                                $exit = 0;
                                @exec($cmd, $lines, $exit);
                                $rawOutput = trim(implode("\n", $lines));

                                if ($exit !== 0) {
                                    $error = 'EXIFTool failed. Make sure exiftool is installed and reachable (config: exiftool_bin).';
                                } else {
                                    $decoded = json_decode($rawOutput, true);
                                    if (is_array($decoded) && isset($decoded[0]) && is_array($decoded[0])) {
                                        $result = $decoded[0];
                                    } else {
                                        $error = 'Could not parse EXIFTool JSON output.';
                                    }
                                }

                                @unlink($dest);
                            }
                        }
                    }
                }
            }
        }
    }
}

header('Content-Type: text/html; charset=UTF-8');

$userNavName = trim((string) ($_SESSION['user_name'] ?? ''));
if ($userNavName === '') {
    $userNavName = (string) ($_SESSION['user_email'] ?? 'Account');
}
$userNavInitial = function_exists('mb_substr')
    ? mb_strtoupper(mb_substr($userNavName, 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($userNavName, 0, 1) ?: '?');

dashboard_shell_begin('EXIFTool viewer', 'exiftool', $userNavName, $userNavInitial);
?>
    <style>
        .dashboard-main-inner {
            max-width: 1240px;
        }
        .exif-layout {
            display: grid;
            grid-template-columns: 0.95fr 1.35fr;
            gap: 22px;
            align-items: start;
        }
        @media (max-width: 980px) {
            .exif-layout { grid-template-columns: 1fr; }
        }
        .exif-lead {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.5;
            margin: -8px 0 16px;
        }
        .exif-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 10px;
        }
        .exif-meta .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 12px;
        }
        .exif-file[type="file"] {
            width: 100%;
            padding: 12px 12px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 14px;
            font-family: inherit;
        }
        .exif-file[type="file"]::file-selector-button {
            height: 38px;
            padding: 0 14px;
            margin-right: 12px;
            border-radius: 8px;
            border: 1px solid rgba(88, 166, 255, 0.35);
            background: rgba(88, 166, 255, 0.12);
            color: var(--text);
            font-weight: 600;
            cursor: pointer;
        }
        .exif-file[type="file"]::file-selector-button:hover {
            background: rgba(88, 166, 255, 0.18);
            border-color: rgba(88, 166, 255, 0.55);
        }
        .exif-file:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
        .exif-toolbar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 12px;
        }
        .exif-search {
            flex: 1;
            min-width: 220px;
            height: 44px;
            padding: 0 14px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 14px;
            font-family: inherit;
        }
        .exif-search:focus { outline: none; border-color: var(--accent); }
        pre.exif-pre {
            margin-top: 14px;
            background: #0b1220;
            border: 1px solid rgba(48,54,61,.95);
            border-radius: 10px;
            padding: 14px;
            max-height: 420px;
            overflow: auto;
            color: var(--text);
            font-size: 12px;
            line-height: 1.5;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .exif-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 13px;
        }
        .exif-table th, .exif-table td {
            border-bottom: 1px solid rgba(48,54,61,.85);
            padding: 10px 8px;
            vertical-align: top;
        }
        .exif-table th {
            text-align: left;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
        }
        .exif-tag { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
        .exif-small {
            font-size: 12px;
            color: var(--text-muted);
        }
        .exif-success {
            background: rgba(63, 185, 80, 0.12);
            color: #7ee787;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 16px;
            border: 1px solid rgba(63, 185, 80, 0.25);
        }
        .card {
            padding: 24px 26px;
        }
        .card h2 {
            font-size: 18px;
        }
    </style>

    <div class="exif-layout">
        <div>
            <div class="card">
                <h2>EXIFTool Viewer</h2>
                <p class="exif-lead">Upload an image and extract its complete EXIF/metadata using EXIFTool.</p>

                <form method="post" enctype="multipart/form-data" autocomplete="off">
                    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">

                    <label for="image">Image file</label>
                    <input id="image" name="image" class="exif-file" type="file" accept="image/*" required>

                    <div class="exif-meta">
                        <button type="submit" class="btn btn-primary">Extract</button>
                        <div class="exif-small">Max 25 MB · EXIFTool: <span class="mono"><?= h($exiftoolBin) ?></span></div>
                    </div>
                </form>

                <?php if ($error !== ''): ?>
                    <div class="settings-alert" role="alert" style="margin-top:16px"><?= h($error) ?></div>
                    <?php if ($rawOutput !== ''): ?>
                        <pre class="exif-pre"><?= h($rawOutput) ?></pre>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div class="card">
                <h2>Results</h2>
                <?php if (!is_array($result)): ?>
                    <p class="exif-lead" style="margin-top:-6px">Run an extract to see the raw JSON and a filterable tag list.</p>
                <?php else: ?>
                    <?php
                        $pretty = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                        if (!is_string($pretty)) {
                            $pretty = '';
                        }
                        $count = count($result);
                    ?>
                    <div class="exif-success" role="status">
                        Extracted <?= h((string) $count) ?> tags<?php if ($uploadedName !== ''): ?> · <span class="exif-tag"><?= h($uploadedName) ?></span><?php endif; ?>
                    </div>

                    <div class="exif-toolbar">
                        <input id="filter" class="exif-search" type="search" placeholder="Filter tags (GPS, Make, Lens, DateTimeOriginal…)" aria-label="Filter tags">
                        <button type="button" class="btn btn-secondary" onclick="copyJson()">Copy JSON</button>
                    </div>

                    <pre id="jsonOut" class="exif-pre"><?= h($pretty) ?></pre>

                    <table class="exif-table" aria-label="EXIF tags">
                        <thead>
                            <tr>
                                <th style="width:36%">Tag</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result as $k => $v): ?>
                                <?php
                                    $key = (string) $k;
                                    if (is_array($v) || is_object($v)) {
                                        $val = json_encode($v, JSON_UNESCAPED_SLASHES);
                                        if (!is_string($val)) {
                                            $val = '[unprintable]';
                                        }
                                    } elseif (is_bool($v)) {
                                        $val = $v ? 'true' : 'false';
                                    } elseif ($v === null) {
                                        $val = 'null';
                                    } else {
                                        $val = (string) $v;
                                    }
                                ?>
                                <tr class="tagRow" data-hay="<?= h(strtolower($key . ' ' . $val)) ?>">
                                    <td class="exif-tag"><?= h($key) ?></td>
                                    <td><?= h($val) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const filter = document.getElementById('filter');
            if (!filter) return;
            filter.addEventListener('input', function () {
                const q = (filter.value || '').trim().toLowerCase();
                const rows = document.querySelectorAll('.tagRow');
                rows.forEach(function (r) {
                    const hay = r.getAttribute('data-hay') || '';
                    r.style.display = q === '' || hay.indexOf(q) !== -1 ? '' : 'none';
                });
            });
        })();

        function copyJson() {
            const el = document.getElementById('jsonOut');
            if (!el) return;
            const text = el.innerText || '';
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text);
                return;
            }
            const ta = document.createElement('textarea');
            ta.value = text;
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
        }
    </script>
<?php
dashboard_shell_end();

