from app.rag.retriever import keyword_grounded, retrieve
from app.llm.client import generate
from app.llm.prompts import checkpoint, chat
from app.config import MAX_CONTEXT_CHARS
from app.web_search import ControlledSearchError, search as web_search
import httpx
import re
def sources(question, rows):
    # Do not cite a merely-near vector result. A displayed source must contain
    # a meaningful question term, unless it is the only available reference.
    grounded = [row for row in rows if row.get('source_type') == 'web' or keyword_grounded(question, str(row.get('text', '')))]
    rows = grounded or rows[:1]
    unique, seen = [], set()
    for row in rows:
        source = {key: row.get(key) for key in ('document_name', 'page', 'framework', 'source_type', 'url', 'source_mode') if row.get(key) is not None}
        key = (source.get('document_name'), source.get('page'), source.get('framework'), source.get('url'))
        if key not in seen:
            seen.add(key)
            unique.append(source)
    return unique
async def explain(request):
    question = request.question or f'Explain checkpoint {request.checkpoint_code}: {request.checkpoint_text}'
    dump = request.model_dump if hasattr(request, 'model_dump') else request.dict
    rows = retrieve(question, dump(include={'framework','facility_type','department'}))
    rows = relevant_person_rows(question, rows)
    mode = request.search_mode.lower()
    if mode == 'web' or (mode == 'auto' and not rows):
        try:
            rows = await web_search(question) if mode == 'web' else rows + (await web_search(question))
        except ControlledSearchError as error:
            return {'success': True, 'answer': str(error), 'sources': [], 'model_available': False}
        except httpx.HTTPError:
            return {'success': True, 'answer': 'Approved web search is temporarily unavailable. Please try again shortly or use SaQshi Repository.', 'sources': [], 'model_available': False}
    if not rows:
        message = ('No results were found on SaQshi-approved web sources.' if mode == 'web'
                   else 'No sufficiently relevant approved reference was found. Please consult the applicable guideline.')
        return {'success': True, 'answer': message, 'sources': []}
    reference = '\n\n'.join(row['text'] for row in rows)[:MAX_CONTEXT_CHARS]
    if not request.generate:
        return {'success': True, 'answer': reference_answer(question, reference), 'sources': sources(question, rows), 'model_available': False}
    try:
        prompt = chat(question, reference, request.language) if request.mode == 'chat' else checkpoint(question, reference, request.language)
        answer = await generate(prompt, request.max_tokens, request.language)
        if request.language == 'hi' and garbled_hindi(answer):
            answer = ('स्थानीय मॉडल विश्वसनीय हिंदी उत्तर तैयार नहीं कर पाया। '
                      'कृपया पुनः प्रयास करें या हिंदी उत्तरों के लिए qwen3:8b मॉडल का उपयोग करें।')
        if request.mode == 'chat' and re.search(r'could not find|not found|insufficient', answer, flags=re.IGNORECASE):
            extracted = extractive_answer(question, reference)
            if extracted:
                answer = extracted + ' (From the approved reference.)'
        model_available = True
    except httpx.HTTPError:
        # A RAG answer remains useful and safe while a local model is loading.
        answer = 'Reference requirement (local model temporarily unavailable):\n\n' + reference[:3000]
        model_available = False
    return {'success': True, 'answer': answer, 'sources': sources(question, rows), 'model_available': model_available}

def reference_answer(question: str, reference: str) -> str:
    """Fast, deterministic answers for simple factual chat questions."""
    normalized = question.lower()
    is_birth_question = (
        'born' in normalized
        or 'dob' in normalized
        or 'date of birth' in normalized
        or 'birth date' in normalized
    )
    if is_birth_question:
        match = re.search(r'\bborn\s+on\s+([^,.\n]+)', reference, flags=re.IGNORECASE)
        if match:
            date = match.group(1).strip()
            return f'Date of birth: {date}. (From the approved reference.)'
    extracted = extractive_answer(question, reference)
    if extracted:
        return extracted + ' (From the approved reference.)'
    # Prefer a compact first paragraph for general non-generative chat.
    first = next((part.strip() for part in reference.split('\n\n') if part.strip()), reference.strip())
    return 'Reference information:\n\n' + first[:1200]

def relevant_person_rows(question: str, rows: list[dict]) -> list[dict]:
    """Never answer 'Who is X?' from a document that does not mention X."""
    match = re.search(r'\bwho\s+(?:is|was)\s+([a-z][a-z .\'-]{1,80})\??\s*$', question.strip(), flags=re.IGNORECASE)
    if not match:
        return rows
    name = match.group(1).strip().split()[0]
    return [row for row in rows if re.search(rf'\b{re.escape(name)}\b', str(row.get('text', '')), flags=re.IGNORECASE)]

def extractive_answer(question: str, reference: str) -> str:
    """Return the best-supported source sentence when a small model abstains."""
    terms = {
        term for term in re.findall(r"[a-z0-9']+", question.lower())
        if len(term) > 2 and term not in {'who', 'what', 'when', 'where', 'why', 'how', 'are', 'was', 'the', 'and', 'with', 'from'}
    }
    sentences = [item.strip() for item in re.split(r'(?<=[.!?])\s+|\n+', reference) if item.strip()]
    ranked = sorted(sentences, key=lambda sentence: sum(bool(re.search(rf'\b{re.escape(term)}\b', sentence, re.IGNORECASE)) for term in terms), reverse=True)
    return ranked[0] if ranked and any(re.search(rf'\b{re.escape(term)}\b', ranked[0], re.IGNORECASE) for term in terms) else ''

def garbled_hindi(answer: str) -> bool:
    """Reject obvious small-model loops, such as a phrase repeated three times."""
    words = re.findall(r'[\u0900-\u097F]{2,}', answer)
    if len(words) < 4:
        return False
    phrases = [' '.join(words[index:index + 3]) for index in range(len(words) - 2)]
    return any(phrases.count(phrase) >= 3 for phrase in set(phrases))
