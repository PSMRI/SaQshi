SAFETY = '''Use supplied references as the primary source. Do not invent standards, clauses or evidence requirements. If references are insufficient, say so clearly. Distinguish reference requirements from AI suggestions. Never assign an official score, close a gap, or claim certification. Use simple practical language and the requested language when possible.'''
def checkpoint(question, references, language): return f'''{SAFETY}\nRequested language: {language}\nQuestion: {question}\nReferences:\n{references}\nReturn concise sections: Meaning, What to verify, Possible evidence.'''
def cqi(finding, references, language): return f'''{SAFETY}\nAll actions must be labelled Suggestions, never auto-saved. Requested language: {language}\nFinding: {finding}\nReferences:\n{references}\nReturn root cause areas, corrective actions, evidence, priority and timeline text.'''
def summary(data, language): return f'''{SAFETY}\nRequested language: {language}\nStructured facility data: {data}\nReturn a short management summary, strengths, risks and recommended focus areas. This is advisory only.'''


def chat(question, references, language):
    return f'''{SAFETY}
Requested language: {language}
If the requested language is hi, write the answer entirely in Hindi using Devanagari script. Do not answer in English.
Answer the user's question directly in one or two short sentences. Use only the supplied references. Do not provide the whole document. If the answer is not stated or cannot be supported by the references, say: "I could not find that in the approved references."
Question: {question}
References:
{references}'''
