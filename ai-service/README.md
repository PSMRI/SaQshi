# SaQshi Local AI Service

This is a local, advisory-only RAG gateway. The browser never calls Ollama directly: browser → authenticated SaQshi PHP API → this service → Ollama.

## Windows development

1. Install Ollama and run `ollama pull qwen3:8b`.
2. In this folder, create a virtual environment and install dependencies: `py -m venv .venv`, `.\.venv\Scripts\pip install -r requirements.txt`.
3. Copy `.env.example` to `.env` and review model/path settings.
4. Place approved documents under `knowledge/nqas`, `knowledge/musqan`, `knowledge/laqshya`, `knowledge/sop`, or `knowledge/manuals`.
5. Index them with `.\.venv\Scripts\python scripts\ingest.py`.
6. Start with `.\.venv\Scripts\uvicorn app.main:app --host 127.0.0.1 --port 18001 --reload`.

Test: `curl http://127.0.0.1:18001/health`.

## Answer sources (no paid service)

`/v1/ask` accepts `search_mode`: `local` searches only the indexed SaQshi
documents, `web` searches public web results, and `auto` (the default) uses
SaQshi documents first and falls back to the web only when no local reference
is found. Web search uses DuckDuckGo's public HTML results: it needs no API key
or subscription, but it does require an internet connection and can be slower.

## Linux deployment

Run the service under a dedicated non-privileged account, bind it to loopback or a private network, and proxy only the PHP application. Store source documents and Chroma data on persistent volumes. Configure a systemd service with `uvicorn app.main:app --host 127.0.0.1 --port 8000`; keep Ollama private on port 11434.

## Reindexing and safeguards

Ingestion supports PDF, DOCX, TXT, Markdown, and JSON. It hashes each file, skips unchanged files, and deletes chunks with the same document name before changed content is added. Scanned-PDF OCR is intentionally not enabled yet. Do not place patient data, credentials, or session data in `knowledge`.

## PHP API examples

All PHP endpoints require the existing SaQshi session and CSRF token for POST requests.

`GET /api/ai/v1/health.php`

`POST /api/ai/v1/explain_checkpoint.php`
```json
{"assessment_id":12,"checkpoint_code":"C2.4","checkpoint_text":"...","framework":"NQAS","facility_type":"CHC","department":"Labour Room","language":"en"}
```

`POST /api/ai/v1/suggest_action_plan.php`
```json
{"assessment_id":12,"checkpoint_code":"C2.4","finding":"Daily cleaning log is incomplete.","current_score":1,"framework":"NQAS","facility_type":"CHC","department":"Labour Room"}
```

## Test checklist

- `/health` reports Chroma status and model.
- Index one text PDF and one DOCX, then rerun ingestion and verify unchanged files are skipped.
- Query with framework/department filters; verify citations include document and page where applicable.
- Test a Hindi or other multilingual query.
- Query with no matching source and confirm the response says references are insufficient.
- Stop Ollama or FastAPI and verify PHP returns `AI_SERVICE_UNAVAILABLE` without blocking assessment work.
- Verify unauthenticated PHP requests are rejected, CQI suggestions are review-only, and scores remain unchanged.

## Rollback

Set `enabled` to `false` in `api/config/ai/ai.json` or remove the UI action. The service is isolated; no assessment, CQI, scoring, certification, or permission data is modified by it. Audit records are stored in `api/storage/audit/ai-usage.jsonl`.
