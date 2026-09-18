from typing import Any
from pydantic import BaseModel, Field

class RagRequest(BaseModel):
    question: str = ''
    checkpoint_code: str = ''
    checkpoint_text: str = ''
    framework: str = ''
    facility_type: str = ''
    department: str = ''
    language: str = 'en'
    generate: bool = True
    mode: str = 'checkpoint'
    max_tokens: int = 800
    search_mode: str = 'auto'  # local, web, or auto (local then web fallback)

class CQIRequest(RagRequest):
    finding: str
    current_score: float | None = None
    local_context: str = ''

class SummaryRequest(BaseModel):
    facility: dict[str, Any]
    assessment: dict[str, Any]
    gaps: dict[str, Any] | list[Any] = Field(default_factory=dict)
    performance: dict[str, Any] = Field(default_factory=dict)
    certification: dict[str, Any] = Field(default_factory=dict)
    language: str = 'en'
