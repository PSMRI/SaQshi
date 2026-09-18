from pathlib import Path
import fitz
from docx import Document
from .metadata import infer_metadata

def load(path: Path):
    suffix = path.suffix.lower()
    if suffix == '.pdf':
        document = fitz.open(path)
        for index, page in enumerate(document, 1): yield page.get_text('text'), infer_metadata(path, index)
    elif suffix == '.docx':
        yield '\n\n'.join(p.text for p in Document(path).paragraphs), infer_metadata(path)
    elif suffix in {'.txt', '.md', '.json'}:
        yield path.read_text(encoding='utf-8', errors='ignore'), infer_metadata(path)
