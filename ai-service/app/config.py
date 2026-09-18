from pathlib import Path
import os
from dotenv import load_dotenv

ROOT = Path(__file__).resolve().parents[1]
# The project .env is the SaQshi service configuration. It must override an
# old system-level OLLAMA_MODEL value left behind by earlier local testing.
load_dotenv(ROOT / '.env', override=True)
OLLAMA_BASE_URL = os.getenv('OLLAMA_BASE_URL', 'http://127.0.0.1:11434').rstrip('/')
OLLAMA_MODEL = os.getenv('OLLAMA_MODEL', 'qwen3:8b')
CHROMA_PATH = ROOT / os.getenv('CHROMA_PATH', 'storage/chroma')
EMBEDDING_MODEL = os.getenv('EMBEDDING_MODEL', 'sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2')
TOP_K = int(os.getenv('TOP_K', '5'))
MAX_CONTEXT_CHARS = int(os.getenv('MAX_CONTEXT_CHARS', '12000'))
MAX_RETRIEVAL_DISTANCE = float(os.getenv('MAX_RETRIEVAL_DISTANCE', '0.55'))
WEB_SEARCH_ENABLED = os.getenv('WEB_SEARCH_ENABLED', 'true').lower() == 'true'
WEB_SEARCH_MAX_RESULTS = max(1, min(10, int(os.getenv('WEB_SEARCH_MAX_RESULTS', '5'))))
WEB_SEARCH_TIMEOUT_SECONDS = max(3, min(20, int(os.getenv('WEB_SEARCH_TIMEOUT_SECONDS', '12'))))
WEB_SEARCH_MAX_PER_MINUTE = max(1, min(60, int(os.getenv('WEB_SEARCH_MAX_PER_MINUTE', '10'))))
WEB_ALLOWED_DOMAINS = tuple(
    domain.strip().lower().lstrip('.')
    for domain in os.getenv(
        'WEB_ALLOWED_DOMAINS',
        'who.int,mohfw.gov.in,mohfw-dohfw.gov.in,nhm.gov.in,nhsrcindia.org,qps.nhsrcindia.org,'
        'dghs.mohfw.gov.in,cbhidghs.mohfw.gov.in,ncdc.mohfw.gov.in,ncvbdc.mohfw.gov.in,'
        'naco.mohfw.gov.in,tbcindia.mohfw.gov.in,icmr.gov.in,cdsco.mohfw.gov.in,abdm.gov.in,'
        'nha.gov.in,pmjay.gov.in,esanjeevani.mohfw.gov.in,nihfw.ac.in,hmsc.dhr.gov.in,'
        'nikshay.in,ntep.in,nmc.org.in,notto.mohfw.gov.in,ayush.gov.in,arp.ayush.gov.in,'
        'india.gov.in,rbsk.mohfw.gov.in,ors.gov.in,cdc.gov,nih.gov,nhs.uk'
    ).split(',') if domain.strip()
)
WEB_PRIORITY_DOMAINS = tuple(
    domain.strip().lower().lstrip('.')
    for domain in os.getenv(
        'WEB_PRIORITY_DOMAINS',
        'qps.nhsrcindia.org,nhsrcindia.org,nhm.gov.in,mohfw.gov.in'
    ).split(',') if domain.strip()
)
