from docx import Document
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Inches, Pt, RGBColor

OUT = 'deliverables/optional_profile_consent_request_and_example.docx'

def font(run, bold=False, size=10.5, color='000000'):
    run.bold = bold
    run.font.name = 'Aptos'
    run._element.rPr.rFonts.set(qn('w:ascii'), 'Aptos')
    run._element.rPr.rFonts.set(qn('w:hAnsi'), 'Aptos')
    run.font.size = Pt(size)
    run.font.color.rgb = RGBColor.from_string(color)

def border(cell, color='D9D9D9'):
    tcpr = cell._tc.get_or_add_tcPr()
    borders = OxmlElement('w:tcBorders')
    for edge in ('top', 'left', 'bottom', 'right'):
        node = OxmlElement(f'w:{edge}')
        node.set(qn('w:val'), 'single')
        node.set(qn('w:sz'), '6')
        node.set(qn('w:color'), color)
        borders.append(node)
    tcpr.append(borders)

def shade(cell, fill):
    tcpr = cell._tc.get_or_add_tcPr()
    node = OxmlElement('w:shd')
    node.set(qn('w:fill'), fill)
    tcpr.append(node)

def para(parent, text='', bold=False, size=10.5, after=6, align=None):
    p = parent.add_paragraph()
    p.paragraph_format.space_after = Pt(after)
    p.paragraph_format.line_spacing = 1.15
    if align is not None:
        p.alignment = align
    font(p.add_run(text), bold, size)
    return p

def heading(doc, text):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(14)
    p.paragraph_format.space_after = Pt(6)
    font(p.add_run(text), True, 14)
    return p

doc = Document()
sec = doc.sections[0]
sec.top_margin, sec.bottom_margin = Inches(.7), Inches(.7)
sec.left_margin, sec.right_margin = Inches(.8), Inches(.8)
doc.styles['Normal'].font.name = 'Aptos'
doc.styles['Normal'].font.size = Pt(10.5)

p = doc.add_paragraph(style='Title')
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
p.paragraph_format.space_after = Pt(5)
font(p.add_run('Optional Profile Details Consent Request and Example'), True, 20)
p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
p.paragraph_format.space_after = Pt(14)
font(p.add_run('For organisation review before implementation in SaQshi'), False, 10, '4B5563')

heading(doc, 'Email Draft for Team Approval')
table = doc.add_table(rows=2, cols=2)
table.alignment = WD_TABLE_ALIGNMENT.LEFT
rows = [('Subject', 'Approval needed for optional profile data consent wording'), ('To', '[Privacy Legal Product and Programme Team]')]
for row, (label, value) in zip(table.rows, rows):
    row.cells[0].width, row.cells[1].width = Inches(1), Inches(5.9)
    row.cells[0].vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
    shade(row.cells[0], 'EAF0F8')
    border(row.cells[0]); border(row.cells[1])
    font(row.cells[0].paragraphs[0].add_run(label), True)
    font(row.cells[1].paragraphs[0].add_run(value))

para(doc, 'Dear Team,', after=8)
para(doc, 'We plan to make First Name, Middle Name, Last Name, Mobile Number and Email optional in the SaQshi Facility User profile. When a user adds or changes any of these details, the system will display a consent form before saving the optional profile data.', after=8)
para(doc, 'Please review and approve the attached example wording, or provide the final organisation-approved text. We need confirmation of the following before development is enabled:', after=6)
for text in [
    'Organisation or Data Fiduciary name to display.',
    'Approved purposes for using optional profile details.',
    'Privacy Policy URL and contact email or helpdesk number.',
    'Consent withdrawal or profile-data removal process.',
    'Retention requirements and the approved consent version identifier.',
]:
    p = doc.add_paragraph(style='List Bullet'); p.paragraph_format.space_after = Pt(3); font(p.add_run(text))
para(doc, 'The implementation will record the user ID, date and time, selected field names, consent version and acceptance decision. It will not copy the entered mobile number or email into the consent audit record.', after=8)
para(doc, 'Please share approval or amendments so that we can finalise the consent text and implementation.', after=8)
para(doc, 'Regards,\n[Your Name]\n[Team or Role]')

doc.add_page_break()
heading(doc, 'Consent Form Example')
para(doc, 'This is a review draft. Replace all text in square brackets with organisation-approved wording before release.', after=10)
form = doc.add_table(rows=1, cols=1)
form.alignment = WD_TABLE_ALIGNMENT.CENTER
cell = form.cell(0, 0)
border(cell, '9CA3AF'); shade(cell, 'F8FAFC')
cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.TOP
p = cell.paragraphs[0]
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
p.paragraph_format.space_after = Pt(10)
font(p.add_run('Consent for Optional Profile Details'), True, 16)

para(cell, 'What you are choosing to provide', True, 11, 5)
para(cell, 'You may choose to provide or update one or more of the following optional details in your SaQshi profile: First Name, Middle Name, Last Name, Mobile Number and Email Address. The form should list only the fields selected or changed in the current save request.', after=8)
para(cell, 'Purpose of use', True, 11, 5)
para(cell, '[Organisation Name] will use these details only for [approved purposes, for example profile identification and authorised account communication related to SaQshi].', after=8)
para(cell, 'Your choices', True, 11, 5)
for text in [
    'Providing these profile details is optional. You can continue using the account without them, except where a field is separately required by law or an approved service process.',
    'You can update or remove these optional details later through your profile, subject to [retention requirements].',
    'For questions, requests or withdrawal of consent, contact [privacy contact] or visit [privacy policy URL].',
]:
    p = cell.add_paragraph(style='List Bullet'); p.paragraph_format.space_after = Pt(3); font(p.add_run(text))
para(cell, '[ ] I have read this notice and voluntarily consent to the collection and use of the selected optional profile details for the purpose stated above.', True, 10.5, 8)
para(cell, 'Buttons shown in the application:  Cancel    Accept and Save', True, 10.5, 2)

heading(doc, 'Implementation Notes')
for text in [
    'Show this form only when optional profile data is added or changed. Do not show it for a password-only update.',
    'Show the specific field names being saved, not the values themselves, in the consent prompt and audit record.',
    'Save the profile changes only after the user selects Accept and Save.',
    'Store the consent version, acceptance timestamp, user ID and selected field names for audit. Do not store the actual optional data again in the consent log.',
]:
    p = doc.add_paragraph(style='List Bullet'); p.paragraph_format.space_after = Pt(3); font(p.add_run(text))

doc.save(OUT)
