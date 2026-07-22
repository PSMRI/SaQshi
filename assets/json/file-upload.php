<?php
// Legacy unauthenticated uploader.  It accepted arbitrary filenames and
// content directly into a web-accessible directory.  No active caller uses
// this endpoint; retain the file only to return an explicit safe response.
http_response_code(410);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => 'This upload endpoint is disabled.']);
exit;
