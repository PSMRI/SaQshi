from hashlib import sha256
from pathlib import Path

def file_hash(path: Path) -> str:
    digest = sha256()
    with path.open('rb') as stream:
        for block in iter(lambda: stream.read(1024 * 1024), b''): digest.update(block)
    return digest.hexdigest()

def infer_metadata(path: Path, page: int | None = None) -> dict:
    parts = [p.lower() for p in path.parts]
    framework = next((x.upper() for x in ('nqas', 'musqan', 'laqshya') if x in parts or x in path.name.lower()), '')
    metadata = {
        'document_name': path.name,
        'document_id': file_hash(path),
        'file_hash': file_hash(path),
        'source_type': 'guideline',
        'framework': framework,
        'language': 'en'
    }
    # Chroma metadata supports scalar values only; omit unavailable values.
    if page is not None:
        metadata['page'] = page
    return metadata
