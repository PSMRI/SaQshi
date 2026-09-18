import re

def chunks(text: str, target: int = 1000, overlap: int = 150) -> list[str]:
    paragraphs = [re.sub(r'\s+', ' ', p).strip() for p in re.split(r'\n\s*\n+', text) if p.strip()]
    output, current = [], ''
    for paragraph in paragraphs:
        if current and len(current) + len(paragraph) + 1 > target:
            output.append(current)
            current = (current[-overlap:] + ' ' + paragraph).strip()
        else: current = (current + ' ' + paragraph).strip()
    if current: output.append(current)
    return [item for item in output if item.strip()]
