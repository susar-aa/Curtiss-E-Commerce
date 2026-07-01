<style>
    .release-container {
        display: grid;
        grid-template-columns: 320px 1fr 300px;
        gap: 20px;
        margin-top: 20px;
    }
    
    @media (max-width: 1400px) {
        .release-container {
            grid-template-columns: 1fr 1fr;
        }
        .timeline-card-wrapper {
            grid-column: span 2;
        }
    }
    
    @media (max-width: 768px) {
        .release-container {
            grid-template-columns: 1fr;
        }
        .timeline-card-wrapper {
            grid-column: span 1;
        }
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-main);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid var(--mac-border);
        border-radius: 8px;
        background: rgba(0, 0, 0, 0.02);
        color: var(--text-main);
        box-sizing: border-box;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    @media (prefers-color-scheme: dark) {
        .form-control {
            background: rgba(255, 255, 255, 0.05);
        }
    }

    .form-control:focus {
        border-color: #0066cc;
        outline: none;
        box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.15);
    }

    .checkbox-group {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 20px;
    }

    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        color: var(--text-main);
    }

    .checkbox-label input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: #0066cc;
        cursor: pointer;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 24px;
        background: #0066cc;
        color: #fff;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn:hover {
        background: #005bb5;
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: rgba(0, 0, 0, 0.05);
        color: var(--text-main);
        border: 1px solid var(--mac-border);
    }

    .btn-secondary:hover {
        background: rgba(0, 0, 0, 0.1);
        color: var(--text-main);
    }

    @media (prefers-color-scheme: dark) {
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
        }
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
        }
    }

    .btn-danger {
        background: #ff3b30;
    }

    .btn-danger:hover {
        background: #e02e24;
    }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }

    .alert-success {
        background: #e8f5e9;
        color: #2e7d32;
        border: 1px solid #a5d6a7;
    }

    .alert-error {
        background: #ffebee;
        color: #c62828;
        border: 1px solid #ef9a9a;
    }

    @media (prefers-color-scheme: dark) {
        .alert-success {
            background: rgba(46, 125, 50, 0.15);
            color: #81c784;
            border-color: rgba(76, 175, 80, 0.3);
        }
        .alert-error {
            background: rgba(198, 40, 40, 0.15);
            color: #e57373;
            border-color: rgba(244, 67, 54, 0.3);
        }
    }

    .card {
        background: #fff;
        border-radius: 12px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        border: 1px solid var(--mac-border);
    }

    @media (prefers-color-scheme: dark) {
        .card {
            background: #1e1e24;
        }
    }

    .card-title {
        margin-top: 0;
        margin-bottom: 20px;
        font-size: 18px;
        font-weight: 700;
        border-bottom: 1px solid var(--mac-border);
        padding-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Release History List/Table style */
    .release-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
    }

    .release-table th, .release-table td {
        padding: 14px;
        text-align: left;
        border-bottom: 1px solid var(--mac-border);
    }

    .release-table th {
        font-weight: 600;
        color: var(--text-muted);
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
    }

    .release-table tr:hover {
        background: rgba(0, 0, 0, 0.01);
    }

    @media (prefers-color-scheme: dark) {
        .release-table tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }
    }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }

    .badge-latest {
        background: #e3f2fd;
        color: #0d47a1;
        border: 1px solid #90caf9;
    }

    .badge-force {
        background: #ffebee;
        color: #c62828;
        border: 1px solid #ef9a9a;
    }

    .badge-optional {
        background: #f1f8e9;
        color: #33691e;
        border: 1px solid #c5e1a5;
    }

    @media (prefers-color-scheme: dark) {
        .badge-latest {
            background: rgba(13, 71, 161, 0.2);
            color: #64b5f6;
            border-color: rgba(30, 136, 229, 0.3);
        }
        .badge-force {
            background: rgba(198, 40, 40, 0.2);
            color: #ef9a9a;
            border-color: rgba(229, 115, 115, 0.3);
        }
        .badge-optional {
            background: rgba(51, 105, 30, 0.2);
            color: #aed581;
            border-color: rgba(139, 195, 74, 0.3);
        }
    }

    .notes-preview {
        max-width: 250px;
        white-space: pre-wrap;
        font-size: 13px;
        color: var(--text-muted);
        line-height: 1.4;
    }

    /* Timeline Stepper CSS styles */
    .timeline {
        position: relative;
        padding-left: 24px;
        list-style: none;
        margin: 10px 0 0 0;
    }

    .timeline::before {
        content: '';
        position: absolute;
        top: 8px;
        bottom: 8px;
        left: 7px;
        width: 2px;
        background: var(--mac-border);
    }

    .timeline-item {
        position: relative;
        margin-bottom: 24px;
    }

    .timeline-item:last-child {
        margin-bottom: 0;
    }

    .timeline-marker {
        position: absolute;
        left: -24px;
        top: 4px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #94a3b8;
        border: 3px solid #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
    }

    @media (prefers-color-scheme: dark) {
        .timeline-marker {
            border-color: #1e1e24;
        }
    }

    .timeline-item.is-latest .timeline-marker {
        background: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25);
    }

    .timeline-item.is-forced .timeline-marker {
        background: #ef4444;
    }

    .timeline-content {
        background: rgba(0, 0, 0, 0.02);
        border: 1px solid var(--mac-border);
        border-radius: 8px;
        padding: 12px;
    }

    @media (prefers-color-scheme: dark) {
        .timeline-content {
            background: rgba(255, 255, 255, 0.02);
        }
    }

    .timeline-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
    }

    .timeline-version {
        font-weight: 700;
        font-size: 14px;
        color: var(--text-main);
    }

    .timeline-date {
        font-size: 11px;
        color: var(--text-muted);
    }

    .timeline-notes {
        font-size: 12px;
        color: var(--text-muted);
        line-height: 1.4;
        white-space: pre-wrap;
    }
</style>

<div class="header-actions" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; font-size: 24px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
            <i class="ph ph-upload-simple text-primary"></i> ERP Mobile App Releases
        </h2>
        <p style="color: var(--text-muted); margin: 5px 0 0 0;">Manage client-side updates, APK distributions, and version rollout strategies.</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="<?= APP_URL ?>/release/api_latest_version" target="_blank" class="btn btn-secondary">
            <i class="ph ph-code"></i> View API JSON
        </a>
    </div>
</div>

<?php if(!empty($data['success'])): ?>
    <div class="alert alert-success" id="syncSuccessAlert">
        <i class="ph ph-check-circle" style="font-size: 18px;"></i> <?= htmlspecialchars($data['success']) ?>
    </div>
<?php endif; ?>
<?php if(!empty($data['error'])): ?>
    <div class="alert alert-error" id="syncErrorAlert">
        <i class="ph ph-warning-circle" style="font-size: 18px;"></i> <?= htmlspecialchars($data['error']) ?>
    </div>
<?php endif; ?>

<!-- Client-side script notification banner -->
<div class="alert alert-error" id="ajaxErrorAlert" style="display:none;">
    <i class="ph ph-warning-circle" style="font-size: 18px;"></i> <span id="ajaxErrorMsg"></span>
</div>

<div class="release-container">
    <!-- 1. Upload Panel -->
    <div class="card" style="height: fit-content;">
        <h3 class="card-title">
            <i class="ph ph-plus-circle"></i> Create New Release
        </h3>
        
        <form action="<?= APP_URL ?>/release/upload" method="POST" enctype="multipart/form-data" id="uploadReleaseForm">
            <div class="form-group">
                <label for="apk">APK Binary File</label>
                <input type="file" name="apk" id="apk" class="form-control" accept=".apk" required style="padding: 8px;">
                <small style="color: var(--text-muted); font-size: 11px; margin-top: 4px; display: block;">Upload the compiled Android app binary package.</small>
                <small style="color: #2e7d32; font-weight: 600; font-size: 12px; margin-top: 6px; display: block; line-height: 1.4; background: rgba(46, 125, 50, 0.05); padding: 8px; border-left: 3px solid #2e7d32; border-radius: 4px;">
                    ℹ️ APK metadata (Package Name, Version Name, and Version Code) will be extracted automatically from the uploaded APK. Ensure you have bumped <code>versionCode</code> and <code>versionName</code> in your Android build configuration before building.
                </small>
            </div>

            <div class="form-group">
                <label for="release_notes">Release Notes & Changelog</label>
                <textarea name="release_notes" id="release_notes" class="form-control" rows="5" placeholder="• Fixed crash on Billing POS screen&#10;• Added Sample Code search indexing&#10;• General performance optimizations"></textarea>
            </div>

            <div class="checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="force_update" value="1">
                    <span>Force Update (Blocks app access)</span>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="is_latest" value="1" checked>
                    <span>Mark as Active/Latest Release</span>
                </label>
            </div>

            <!-- Upload Progress Bar (AJAX Mode) -->
            <div id="uploadProgressContainer" style="display: none; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 6px;">
                    <span style="color: var(--text-muted); font-weight: 500;">Uploading APK File...</span>
                    <span id="uploadPercent" style="font-weight: 700; color: #0066cc;">0%</span>
                </div>
                <div style="width: 100%; height: 6px; background: rgba(0,0,0,0.05); border-radius: 3px; overflow: hidden;">
                    <div id="uploadProgressBar" style="width: 0%; height: 100%; background: #0066cc; transition: width 0.1s ease;"></div>
                </div>
            </div>

            <button type="submit" class="btn" style="width: 100%;" id="btnUploadSubmit">
                <i class="ph ph-cloud-arrow-up"></i> Upload and Deploy Release
            </button>
        </form>
    </div>

    <!-- 2. Deployment History Panel -->
    <div class="card">
        <h3 class="card-title">
            <i class="ph ph-clock"></i> Deployment History
        </h3>

        <?php if (empty($data['releases'])): ?>
            <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                <i class="ph ph-cloud-slash" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                <p style="margin: 0; font-size: 16px; font-weight: 600;">No releases deployed yet</p>
                <p style="margin: 5px 0 0 0; font-size: 13px;">Upload your first APK package to initialize the update delivery system.</p>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="release-table">
                    <thead>
                        <tr>
                            <th>Version & Package</th>
                            <th>Status</th>
                            <th>Release Notes</th>
                            <th>Upload Date</th>
                            <th>Downloads</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data['releases'] as $r): ?>
                            <tr>
                                <td style="font-weight: 700; font-size: 15px; color: var(--text-main);">
                                    v<?= htmlspecialchars($r->version) ?>
                                    <?php if (!empty($r->build_version)): ?>
                                        <span style="font-weight: normal; color: var(--text-muted); font-size: 12px;">(Build <?= $r->build_version ?>)</span>
                                    <?php endif; ?>
                                    <?php if (!empty($r->package_name)): ?>
                                        <div style="font-size: 10px; color: var(--text-muted); font-weight: normal; margin-top: 2px;"><?= htmlspecialchars($r->package_name) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                                        <?php if ($r->is_latest == 1): ?>
                                            <span class="badge badge-latest">
                                                <i class="ph ph-check-square" style="margin-right: 4px;"></i> Latest Active
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($r->force_update == 1): ?>
                                            <span class="badge badge-force">
                                                <i class="ph ph-shield-warning" style="margin-right: 4px;"></i> Force Update
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-optional">Optional</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="notes-preview"><?= htmlspecialchars($r->release_notes) ?></div>
                                </td>
                                <td style="color: var(--text-muted); font-size: 13px;">
                                    <?= date('M d, Y H:i', strtotime($r->created_at)) ?>
                                </td>
                                <td>
                                    <a href="<?= APP_URL ?>/releases/app-v<?= htmlspecialchars($r->version) ?>.apk" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">
                                        <i class="ph ph-download-simple"></i> Download APK
                                    </a>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                        <?php if ($r->is_latest == 0): ?>
                                            <a href="<?= APP_URL ?>/release/set_latest/<?= $r->id ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;" title="Set as Latest Active version">
                                                <i class="ph ph-star"></i> Activate
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= APP_URL ?>/release/delete/<?= $r->id ?>" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="return confirm('Are you sure you want to permanently delete and remove release v<?= htmlspecialchars($r->version) ?>? This cannot be undone.');">
                                            <i class="ph ph-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- 3. Vertical Timeline Panel (Right) -->
    <div class="card timeline-card-wrapper" style="height: fit-content;">
        <h3 class="card-title">
            <i class="ph ph-git-commit"></i> Release Timeline
        </h3>

        <?php if (empty($data['releases'])): ?>
            <div style="text-align: center; padding: 20px 0; color: var(--text-muted); font-size: 13px;">
                No milestone timeline available.
            </div>
        <?php else: ?>
            <ul class="timeline">
                <?php foreach($data['releases'] as $r): ?>
                    <?php 
                        $class = '';
                        if ($r->is_latest == 1) $class .= ' is-latest';
                        if ($r->force_update == 1) $class .= ' is-forced';
                    ?>
                    <li class="timeline-item<?= $class ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <span class="timeline-version">v<?= htmlspecialchars($r->version) ?> <?= !empty($r->build_version) ? '(Build ' . $r->build_version . ')' : '' ?></span>
                                <span class="timeline-date"><?= date('M d, Y', strtotime($r->created_at)) ?></span>
                            </div>
                            <div class="timeline-notes"><?= htmlspecialchars($r->release_notes ?: 'Initial system release/upload.') ?></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const uploadForm = document.getElementById("uploadReleaseForm");
    
    if (uploadForm) {
        uploadForm.addEventListener("submit", function(e) {
            e.preventDefault();
            
            const fileInput = document.getElementById("apk");
            const releaseNotesInput = document.getElementById("release_notes");
            const forceUpdateInput = uploadForm.querySelector('input[name="force_update"]');
            const isLatestInput = uploadForm.querySelector('input[name="is_latest"]');
            
            if (!fileInput.files || fileInput.files.length === 0) {
                alert("Please select an APK file to upload.");
                return;
            }

            const file = fileInput.files[0];
            const releaseNotes = releaseNotesInput.value;
            const forceUpdate = forceUpdateInput.checked ? "1" : "0";
            const isLatest = isLatestInput.checked ? "1" : "0";

            // Generate unique temporary token for this upload session
            const tempToken = "temp_" + Math.random().toString(36).substring(2, 15) + "_" + Date.now();

            console.log("--- Chunked APK Upload Initiated ---");
            console.log("Temp Token: " + tempToken);
            console.log("File Name: " + file.name);
            console.log("File Size: " + (file.size / (1024 * 1024)).toFixed(2) + " MB");

            const progressContainer = document.getElementById("uploadProgressContainer");
            const progressBar = document.getElementById("uploadProgressBar");
            const percentText = document.getElementById("uploadPercent");
            const submitBtn = document.getElementById("btnUploadSubmit");
            const errorAlert = document.getElementById("ajaxErrorAlert");
            const errorMsgSpan = document.getElementById("ajaxErrorMsg");

            // Hide standard status banners if visible
            const syncSuccessAlert = document.getElementById("syncSuccessAlert");
            const syncErrorAlert = document.getElementById("syncErrorAlert");
            if (syncSuccessAlert) syncSuccessAlert.style.display = 'none';
            if (syncErrorAlert) syncErrorAlert.style.display = 'none';
            errorAlert.style.display = 'none';

            // Show progress elements and disable submit button
            progressContainer.style.display = 'block';
            progressBar.style.width = '0%';
            percentText.innerText = '0%';
            submitBtn.disabled = true;

            const CHUNK_SIZE = 1024 * 1024; // 1MB chunk size
            const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
            let chunkIndex = 0;

            console.log("Total chunks to upload: " + totalChunks);

            function uploadNextChunk() {
                const start = chunkIndex * CHUNK_SIZE;
                const end = Math.min(start + CHUNK_SIZE, file.size);
                const chunk = file.slice(start, end);

                const chunkData = new FormData();
                chunkData.append("temp_token", tempToken);
                chunkData.append("chunk_index", chunkIndex);
                chunkData.append("chunk", chunk);

                const xhr = new XMLHttpRequest();
                xhr.open("POST", "<?= APP_URL ?>/release/upload_chunk", true);
                xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        const overallLoaded = (chunkIndex * CHUNK_SIZE) + evt.loaded;
                        const overallPercent = Math.round((overallLoaded / file.size) * 100);
                        progressBar.style.width = overallPercent + '%';
                        percentText.innerText = overallPercent + '%';
                    }
                });

                xhr.onreadystatechange = function() {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    console.log("Successfully uploaded chunk " + (chunkIndex + 1) + "/" + totalChunks);
                                    chunkIndex++;
                                    if (chunkIndex < totalChunks) {
                                        uploadNextChunk();
                                    } else {
                                        assembleUploadedChunks();
                                    }
                                } else {
                                    showError("Chunk upload error: " + response.error);
                                }
                            } catch (e) {
                                showError("Failed to parse server chunk response: " + xhr.responseText);
                            }
                        } else {
                            showError("Chunk upload request failed with status: " + xhr.status);
                        }
                    }
                };

                xhr.send(chunkData);
            }

            function assembleUploadedChunks() {
                console.log("All chunks uploaded. Assembling file on server...");
                percentText.innerText = "Assembling & Parsing metadata...";
                progressBar.style.width = '100%';

                const assembleData = new FormData();
                assembleData.append("temp_token", tempToken);
                assembleData.append("release_notes", releaseNotes);
                assembleData.append("force_update", forceUpdate);
                assembleData.append("is_latest", isLatest);
                assembleData.append("total_chunks", totalChunks);

                const xhr = new XMLHttpRequest();
                xhr.open("POST", "<?= APP_URL ?>/release/assemble_chunks", true);
                xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

                xhr.onreadystatechange = function() {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        submitBtn.disabled = false;
                        progressContainer.style.display = 'none';

                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                if (response.success) {
                                    console.log("Assembled successfully!");
                                    alert(response.message || "Upload and assembly completed successfully!");
                                    window.location.reload();
                                } else {
                                    showError("Assembly failed: " + response.error);
                                }
                            } catch (e) {
                                showError("Failed to parse assembly response: " + xhr.responseText);
                            }
                        } else {
                            showError("Assemble request failed with status: " + xhr.status);
                        }
                    }
                };

                xhr.send(assembleData);
            }

            function showError(msg) {
                console.error(msg);
                submitBtn.disabled = false;
                progressContainer.style.display = 'none';
                errorMsgSpan.innerText = msg;
                errorAlert.style.display = 'flex';
                errorAlert.scrollIntoView({ behavior: 'smooth' });
            }

            // Start uploading the first chunk
            uploadNextChunk();
        });
    }
});
</script>
