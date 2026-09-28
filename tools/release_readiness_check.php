<?php
/**
 * SaQshi release readiness checker.*
 * Run from the open_source directory:
 * php tools/release_readiness_check.php*
 * The checker is intentionally conservative. A warning does not always mean
 * the project is broken; it means a release owner should review the item
 * before publishing the repository publicly.
 */
declare(strict_types=1);
/*
 * MODULE 1: Resolve Project Root
 * ------------------------------
 * Determines the SaQshi project root directory relative to this script.
 * The checker stops immediately if the project root cannot be resolved.
 */
$root = realpath(__DIR__ . '/..');
if ($root === false) {
    fwrite(STDERR, "Unable to resolve project root.\n");
    exit(2);
}
/*
 * MODULE 2: Initialize Result Containers
 * --------------------------------------
 * Stores blocking errors separately from non-blocking review warnings.
 * Errors fail the release check; warnings require manual review.
 */
$errors = [];
$warnings = [];
/*
 * MODULE 3: Result Helper Functions
 * ---------------------------------
 * rr_error() records a release-blocking problem.
 * rr_warning() records a non-blocking item that should be manually reviewed.
 */
function rr_error(array &$errors, string $message): void
{
        $errors[] = $message;
}
function rr_warning(array &$warnings, string $message): void
{
    $warnings[] = $message;
}
/*
 * MODULE 4: Secret-Scan Exclusion Rules
 * -------------------------------------
 * Decides which files should be skipped during text-based secret/error scanning.
 * Runtime folders, dependencies, Git metadata, temporary folders, and binary
 * formats are excluded because scanning them would create noise or false positives.
 */
function rr_should_skip_file(string $relativePath): bool
{
    $relativePath = str_replace('\\\\', '/', $relativePath);
    $skipPrefixes = [
        'uploads/',
        'api/storage/',
        '.git/',
        'node_modules/',
        'vendor/',
        '.codex_tmp/',
    ];
    foreach ($skipPrefixes as $prefix) {
        if (str_starts_with($relativePath, $prefix)) {
            return true;
        }
    }
    return (bool) preg_match('/\\.(png|jpg|jpeg|gif|webp|ico|pdf|docx|xlsx|xls|zip|rar|7z|gz)$/i', $relativePath);
}
/*
 * MODULE 5: Private/Binary Artifact Detection
 * -------------------------------------------
 * Identifies archive, backup, dump, Office, and similar files that should normally
 * not be published in a public source repository.
 * The explicitly approved non-PII sample export ZIP is exempted.
 */
function rr_is_private_artifact(string $relativePath): bool
{
    $relativePath = str_replace('\\\\', '/', $relativePath);
    if ($relativePath === 'docs/compliance/sample_exports/non_pii_sample_exports.zip') {
        return false;
    }
    return (bool) preg_match('/\\.(zip|rar|7z|bak|backup|dump|sql\\.gz|docx|xlsx|xls)$/i', $relativePath);
}
/*
 * MODULE 6: Approved Large Configuration Files
 * --------------------------------------------
 * Whitelists known configuration files that are expected to exceed the normal
 * 1 MB review threshold.
 */
function rr_is_approved_large_config(string $relativePath): bool
{
    $relativePath = str_replace('\\\\', '/', $relativePath);
    $approvedLargeConfigs = [
        'api/config/frameworks/saqshi-nqas.json',
        'api/config/performance/outcome.json',
    ];
    return in_array($relativePath, $approvedLargeConfigs, true);
}
/*
 * MODULE 7: UI Asset Path Resolution
 * ----------------------------------
 * Converts CSS/JavaScript asset references from UI JSON configuration into actual
 * local filesystem paths so the checker can verify that referenced assets exist.
 * External HTTP/HTTPS assets are ignored because they are not local files.
 */
function rr_asset_path_to_file(string $root, string $assetPath): ?string
{
    $pathOnly = preg_replace('/[?#].*$/', '', trim($assetPath));
    if ($pathOnly === '' || preg_match('/^https?:\\/\\//i', $pathOnly)) {
        return null;
    }
    $pathOnly = str_replace('\\\\', '/', $pathOnly);
    if (str_starts_with($pathOnly, '/assets/')) {
        $relative = 'ui' . $pathOnly;
    } elseif (str_starts_with($pathOnly, '/ui/')) {
        $relative = ltrim($pathOnly, '/');
    } else {
        $relative = 'ui/' . ltrim($pathOnly, '/');
    }
    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

/*
 * MODULE 8: Mandatory Open-Source / Release Files
 * -----------------------------------------------
 * Defines the minimum documentation, governance, security, compliance, database,
 * and GitHub workflow files required for a release-ready public repository.
 */
$requiredFiles = [
    'LICENSE',
    'NOTICE',
    'README.md',
    'CONTRIBUTING.md',
    'CODE_OF_CONDUCT.md',
    'MAINTAINERS.md',
    'SECURITY.md',
    'CHANGELOG.md',
    '.env.example',
    'docs/compliance/release_checklist.md',
    'docs/compliance/release_versioning_policy.md',
    'docs/compliance/third_party_licenses.md',
    'docs/compliance/dpg_readiness_assessment.md',
    'docs/compliance/legal_privacy_confirmation.md',
    'docs/database/database_setup_and_migration.md',
    'docs/security/production_hardening.md',
    'docs/security/role_access_matrix.md',
    '.github/ISSUE_TEMPLATE/bug_report.md',
    '.github/ISSUE_TEMPLATE/feature_request.md',
    '.github/ISSUE_TEMPLATE/security_advisory.md',
    '.github/ISSUE_TEMPLATE/config.yml',
    '.github/PULL_REQUEST_TEMPLATE.md',
];
/*
 * MODULE 9: Required File Validation
 * ----------------------------------
 * Verifies that every mandatory release file exists.
 * Missing required files are treated as blocking release errors.
 */
foreach ($requiredFiles as $file) {
    if (!is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file))) {
        rr_error($errors, "Required release file missing: {$file}");
    }
}
/*
 * MODULE 10: Local Environment File Check
 * ---------------------------------------
 * Warns when a real .env file exists in the project tree because it may contain
 * passwords, API keys, database credentials, or other deployment secrets.
 * Only .env.example should normally be published.
 */
if (is_file($root . DIRECTORY_SEPARATOR . '.env')) {
    rr_warning($warnings, "Local .env exists. Keep it untracked and never publish it.");
}
/*
 * MODULE 11: Sanitized Database Schema Check
 * ------------------------------------------
 * Confirms that a publishable base database schema is available so external users
 * can initialize the application without relying on private production dumps.
 */
if (!is_file($root . DIRECTORY_SEPARATOR . 'api/sql/schema/001_base_schema.sql')) {
    rr_warning($warnings, "Sanitized base schema not found at api/sql/schema/001_base_schema.sql.");
}

/*
 * MODULE 12: Data Redistribution Approval Status
 * ----------------------------------------------
 * Checks whether the data redistribution approval document still contains
 * "Pending", which means release ownership should review it before publication.
 */
$dataApproval = $root . DIRECTORY_SEPARATOR . 'docs/compliance/data_redistribution_approval.md';
if (is_file($dataApproval)) {
    $approvalText = file_get_contents($dataApproval);
    if ($approvalText !== false && stripos($approvalText, 'Pending') !== false) {
        rr_warning($warnings, "Some data redistribution approvals are still pending: docs/compliance/data_redistribution_approval.md.");
    }
}

/*
 * MODULE 13: Maintainer / Security Contact Status
 * -----------------------------------------------
 * Checks whether maintainer, security, or release-owner information is still
 * marked as pending.
 */
$maintainersFile = $root . DIRECTORY_SEPARATOR . 'MAINTAINERS.md';

if (is_file($maintainersFile)) {
    $maintainersText = file_get_contents($maintainersFile);
    if ($maintainersText !== false && stripos($maintainersText, 'Pending') !== false) {
        rr_warning($warnings, "Maintainer/security/release contacts are still pending: MAINTAINERS.md.");
    }
}

/*
 * MODULE 14: Legal and Privacy Confirmation Status
 * ------------------------------------------------
 * Checks whether legal/privacy confirmation is still pending and raises a review
 * warning when the repository has not yet received final confirmation.
 */
$legalPrivacyFile = $root . DIRECTORY_SEPARATOR . 'docs/compliance/legal_privacy_confirmation.md';
if (is_file($legalPrivacyFile)) {
    $legalPrivacyText = file_get_contents($legalPrivacyFile);
    if ($legalPrivacyText !== false && stripos($legalPrivacyText, 'Status: Pending') !== false) {
        rr_warning($warnings, "Legal/privacy confirmation is still pending: docs/compliance/legal_privacy_confirmation.md.");
    }
}
/*
 * MODULE 15: UI Page Asset Reference Validation
 * ---------------------------------------------
 * Reads JSON page definitions under ui/pages and validates that every locally
 * referenced CSS and JavaScript asset actually exists in the repository.
 * Missing assets are reported as review warnings because they can break the UI.
 */
$uiPagesDirectory = $root . DIRECTORY_SEPARATOR . 'ui/pages';
if (is_dir($uiPagesDirectory)) {
    $pageFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uiPagesDirectory, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($pageFiles as $pageFile) {
        if (!$pageFile->isFile() || strtolower($pageFile->getExtension()) !== 'json') {
            continue;
        }
        $json = json_decode((string) file_get_contents($pageFile->getPathname()), true);
        if (!is_array($json)) {
            continue;
        }
        foreach (['css', 'js'] as $assetType) {
            $assets = $json['assets'][$assetType] ?? [];
            if (!is_array($assets)) {
                continue;
            }
            foreach ($assets as $assetPath) {
                if (!is_string($assetPath)) {
                    continue;
                }
                $localFile = rr_asset_path_to_file($root, $assetPath);
                if ($localFile !== null && !is_file($localFile)) {
                    $relativePage = str_replace('\\\\', '/', substr($pageFile->getPathname(), strlen($root) + 1));
                    rr_warning($warnings, "Page asset reference is missing: {$assetPath} declared in {$relativePage}");
                }
            }
        }
    }
}
/*
 * MODULE 16: Runtime / Private Data Validation
 * --------------------------------------------
 * Defines runtime folders and sensitive file patterns that should not be committed
 * to a public repository, including uploads, logs, events, and cryptographic keys.
 */
$runtimeDataChecks = [
    'uploads' => '/^(?!README\\.md$).+/i',
    'api/storage/events' => '/\\.(log|json|txt)$/i',
    'api/storage/logs' => '/\\.(log|json|txt)$/i',
    'api/storage/keys' => '/\\.(pem|key|crt|cer|p12|pfx)$/i',
];
/*
 * MODULE 17: Runtime / Private File Scan
 * --------------------------------------
 * Recursively scans the configured runtime directories and reports files that
 * match private or sensitive patterns.
 */
foreach ($runtimeDataChecks as $relativeDirectory => $pattern) {
    $directory = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);
    if (!is_dir($directory)) {
        continue;
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($items as $item) {
        if (!$item->isFile()) {
            continue;
        }
        $relativePath = str_replace('\\\\', '/', substr($item->getPathname(), strlen($root) + 1));
        $fileName = $item->getFilename();
        if (preg_match($pattern, $fileName) && $relativePath !== 'uploads/README.md' && $relativePath !== 'api/storage/README.md') {
            rr_warning($warnings, "Runtime/private data file must not be published: {$relativePath}");
        }
    }
}
/*
 * MODULE 18: Project File Enumeration
 * -----------------------------------
 * Builds the list of repository files that will be inspected for secrets and raw
 * error exposure. It first uses ripgrep (rg --files) when available.
 */
$previousDirectory = getcwd();
chdir($root);
$fileList = [];
$rgExitCode = 1;
$rgCommand = DIRECTORY_SEPARATOR === '\\\\' ? 'rg --files 2>NUL' : 'rg --files 2>/dev/null';
exec($rgCommand, $fileList, $rgExitCode);
if ($previousDirectory !== false) {
    chdir($previousDirectory);
}
/*
 * MODULE 19: File Enumeration Fallback
 * ------------------------------------
 * If ripgrep is unavailable or returns no files, PHP recursively enumerates the
 * project tree itself. This keeps the checker portable in minimal environments
 * such as CI runners and production images.
 */
if ($rgExitCode !== 0 || !$fileList) {
    // GitHub-hosted runners and minimal production images may not include
    // ripgrep. Use PHP's filesystem iterator so the release check remains
    // self-contained and scans the full project tree.
    $fileList = [];
    $fallbackFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($fallbackFiles as $fallbackFile) {
        if (!$fallbackFile->isFile()) {
            continue;
        }
        $relativePath = str_replace('\\\\', '/', substr($fallbackFile->getPathname(), strlen($root) + 1));
        if (str_starts_with($relativePath, '.git/')) {
            continue;
        }
        $fileList[] = $relativePath;
    }
    if (!$fileList) {
        rr_warning($warnings, "Unable to enumerate project files for secret and error-exposure checks.");
        $fileList = $requiredFiles;
    }
}
/*
 * MODULE 20: Secret Detection Patterns
 * ------------------------------------
 * Defines regular expressions for potentially hardcoded database passwords,
 * application passwords, API keys, generic secrets, and private key material.
 * Matches are warnings because each result should be reviewed for false positives.
 */
$secretPatterns = [
    '/DB_PASSWORD\s*=\s*(?!change_me|""|\'\')\S+/i' => 'Possible database password',
    '/password\s*[:=]\s*[\'"][^\'"]{6,}[\'"]/i' => 'Possible hardcoded password',
    '/api[_-]?key\s*[:=]\s*[\'"][^\'"]{10,}[\'"]/i' => 'Possible API key',
    '/secret\s*[:=]\s*[\'"][^\'"]{10,}[\'"]/i' => 'Possible secret value',
    '/-----BEGIN (RSA |EC |OPENSSH |)PRIVATE KEY-----/' => 'Private key material',
];
/*
 * MODULE 21: Raw Error Exposure Detection Patterns
 * ------------------------------------------------
 * Detects code that may return internal exception or database error details
 * directly to API/UI users. Such output can disclose implementation details,
 * database structure, filesystem paths, or sensitive diagnostic information.
 */
$rawErrorExposurePatterns = [
    '/Response::error\s*\\(\s*\\$e->getMessage\s*\\(/' => 'Possible raw exception returned to API user',
    '/Response::error\s*\\([^;]*(\\$con->error|\\$stmt->error|mysqli_error\s*\\()/s' => 'Possible raw database error returned to API user',
    '/\b(echo|print|die|exit)\s*\\([^;]*(\\$e->getMessage\s*\\(|\\$con->error|\\$stmt->error|mysqli_error\s*\\()/s' => 'Possible raw error output',
];
/*
 * MODULE 22: Repository Content Security Scan
 * -------------------------------------------
 * Processes each enumerated project file:
 *   1. Normalizes and validates the path.
 *   2. Flags private/binary release artifacts.
 *   3. Flags unexpected files larger than 1 MB.
 *   4. Skips excluded/binary files from text scanning.
 *   5. Scans readable text files for secrets and raw error exposure.
 */
foreach ($fileList as $relative) {
    $relative = str_replace('\\\\', '/', trim((string) $relative));
    if ($relative === '') {
        continue;
    }
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!is_file($path)) {
        continue;
    }
    $size = filesize($path);
    if ($size === false) {
        rr_warning($warnings, "Unable to read file size: {$relative}");
        continue;
    }
    if (rr_is_private_artifact($relative)) {
        rr_warning($warnings, "Review/remove private or binary release artifact: {$relative}");
    }
    if ($size > 1048576) {
        if (rr_is_approved_large_config($relative)) {
            continue;
        }
        rr_warning($warnings, "Review large release file over 1 MB: {$relative}");
        continue;
    }
    if (rr_should_skip_file($relative)) {
        continue;
    }
    $content = file_get_contents($path);
    if ($content === false) {
        rr_warning($warnings, "Unable to read file during scan: {$relative}");
        continue;
    }
    foreach ($secretPatterns as $pattern => $label) {
        if (preg_match($pattern, $content)) {
            rr_warning($warnings, "{$label} in {$relative}");
        }
    }
    foreach ($rawErrorExposurePatterns as $pattern => $label) {
        if (preg_match($pattern, $content)) {
            rr_warning($warnings, "{$label} in {$relative}");
        }
    }
}
/*
 * MODULE 23: Human-Readable Result Output
 * ---------------------------------------
 * Prints all blocking errors and review warnings in a clear console format.
 * If no issues are found, the checker prints an explicit OK message.
 */
echo "SaQshi Release Readiness Check\n";
echo "==============================\n\n";
if ($errors) {
    echo "Errors:\n";
    foreach ($errors as $error) {
        echo "  [FAIL] {$error}\n";
    }
    echo "\n";
}
if ($warnings) {
    echo "Warnings:\n";
    foreach ($warnings as $warning) {
        echo "  [REVIEW] {$warning}\n";
    }
    echo "\n";
}
if (!$errors && !$warnings) {
    echo "[OK] No release blockers or review warnings found.\n";
}
/*
 * MODULE 24: Final Status and Exit Code
 * -------------------------------------
 * Returns FAILED with exit code 1 when blocking errors exist.
 * Otherwise returns PASSED_WITH_REVIEW with exit code 0.
 * Warnings intentionally do not fail the process because they require human review.
 */
echo "Result: " . ($errors ? 'FAILED' : 'PASSED_WITH_REVIEW') . "\n";
exit($errors ? 1 : 0);
//**********END*************************************** */
