"""Controlled, zero-key web search; results are limited to approved domains."""
import html
import re
from collections import deque
from time import monotonic
from urllib.parse import parse_qs, unquote, urlparse
from xml.etree import ElementTree
import httpx
from app.config import (WEB_ALLOWED_DOMAINS, WEB_SEARCH_ENABLED,
                        WEB_SEARCH_MAX_PER_MINUTE, WEB_SEARCH_MAX_RESULTS, WEB_SEARCH_TIMEOUT_SECONDS)

class ControlledSearchError(Exception):
    pass

_searches: deque[float] = deque()

def allowed_domain(url: str) -> bool:
    host = (urlparse(url).hostname or '').lower()
    return any(host == domain or host.endswith('.' + domain) for domain in WEB_ALLOWED_DOMAINS)

def safe_question(question: str) -> str:
    value = question.strip()
    blocked = (
        r'\b\d{12}\b',  # Aadhaar-like number
        r'\b[\w.+-]+@[\w-]+\.[\w.-]+\b',
        r'\b(?:\+?91[- ]?)?[6-9]\d{9}\b',
        r'\b(?:uhid|mrn|patient\s*(?:name|id)|registration\s*(?:number|no))\b',
    )
    if any(re.search(pattern, value, re.IGNORECASE) for pattern in blocked):
        raise ControlledSearchError('Web search is blocked for questions containing personal or patient-identifying information.')
    return value

def check_rate_limit() -> None:
    now = monotonic()
    while _searches and now - _searches[0] > 60:
        _searches.popleft()
    if len(_searches) >= WEB_SEARCH_MAX_PER_MINUTE:
        raise ControlledSearchError('Web search limit reached. Please wait one minute and try again.')
    _searches.append(now)

async def search(question: str, limit: int = 5) -> list[dict]:
    if not WEB_SEARCH_ENABLED:
        raise ControlledSearchError('Web search is disabled by the SaQshi administrator.')
    question = safe_question(question)
    check_rate_limit()
    limit = min(limit, WEB_SEARCH_MAX_RESULTS)
    search_question = question
    # Keep the original question broad. Results are filtered after search against
    # the approved domain list, so QPS, NHSRC, NHM and MoHFW can all contribute.
    headers = {'User-Agent': 'Mozilla/5.0 (compatible; SaQshiAI/1.0)'}
    results = []
    try:
        async with httpx.AsyncClient(timeout=WEB_SEARCH_TIMEOUT_SECONDS, follow_redirects=True, headers=headers) as client:
            response = await client.get('https://html.duckduckgo.com/html/', params={'q': search_question})
            response.raise_for_status()
        body = response.text
        matches = re.findall(r'<a[^>]*class="result__a"[^>]*href="([^"]+)"[^>]*>(.*?)</a>', body, re.S)
        snippets = re.findall(r'<a[^>]*class="result__snippet"[^>]*>(.*?)</a>|<div[^>]*class="result__snippet"[^>]*>(.*?)</div>', body, re.S)
        for index, (url, title) in enumerate(matches[:30]):
            parsed = parse_qs(urlparse(html.unescape(url)).query)
            target = unquote(parsed.get('uddg', [url])[0])
            snippet_pair = snippets[index] if index < len(snippets) else ('', '')
            results.extend(controlled_result(target, title, snippet_pair[0] or snippet_pair[1], limit - len(results)))
            if len(results) >= limit:
                return results
    except httpx.HTTPError:
        pass

    # Bing RSS is a no-key fallback when a network policy blocks DuckDuckGo's
    # HTML endpoint. The same domain allow-list is applied before returning it.
    try:
        async with httpx.AsyncClient(timeout=WEB_SEARCH_TIMEOUT_SECONDS, follow_redirects=True, headers=headers) as client:
            response = await client.get('https://www.bing.com/search', params={'q': search_question, 'format': 'rss'})
            response.raise_for_status()
        root = ElementTree.fromstring(response.content)
        for item in root.findall('.//item'):
            link = item.findtext('link', '')
            title = item.findtext('title', '')
            description = item.findtext('description', '')
            results.extend(controlled_result(link, title, description, limit - len(results)))
            if len(results) >= limit:
                break
    except (httpx.HTTPError, ElementTree.ParseError):
        pass
    return results

def controlled_result(url: str, title: str, snippet: str, remaining: int) -> list[dict]:
    """Convert one provider result only if it is an approved public source."""
    if remaining <= 0 or not url.startswith('http') or not allowed_domain(url):
        return []
    clean_title = re.sub(r'<[^>]+>', '', title).strip()
    clean_snippet = re.sub(r'<[^>]+>', '', snippet).strip()
    return [{'document_name': html.unescape(clean_title), 'url': url, 'source_type': 'web',
             'source_mode': 'web', 'text': f'{clean_title}\n{html.unescape(clean_snippet)}'}]
