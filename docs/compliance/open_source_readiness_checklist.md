# SaQshi Open Source Readiness Checklist

Version: 1.0  
Reviewed: 2026-07-13  
Repository path: `D:\sAQshi_new_27112025\up\SaQSHI_Main`

## Executive Summary

SaQshi is **open-source capable**, but the repository is **not yet fully release-ready as a clean public open-source distribution**.

The project includes an OSI-style permissive license at the repository root, contribution guidance, a code of conduct, API documentation, testing documents, VAPT notes, WCAG notes, and a sample environment file. These are strong open-source foundations.

The main gaps before public release are:

- The root license is **MIT**, but many UI/API headers and footer metadata say **Apache-2.0**.
- `SECURITY.md` is missing.
- `NOTICE` or third-party attribution documentation is missing.
- Dependency/license inventory is not yet documented.
- `README.md` is too minimal for a public developer onboarding page.
- Local `.env` exists and must stay untracked and never be committed.

## Source Criteria Used

This checklist is based on generally accepted open-source release expectations and the OSI Open Source Definition:

- OSI Open Source Definition: `https://opensource.org/osd`
- MIT License text currently present in this repository: `LICENSE.txt`
- Apache License 2.0 reference, because many source headers currently mention Apache-2.0: `https://www.apache.org/licenses/LICENSE-2.0`

## Current Verdict

| Area | Verdict |
|---|---|
| Can this be open source? | **Yes** |
| Is the license currently clear and consistent? | **Partial** |
| Is it safe to publish immediately? | **Partial / Not recommended until gaps are fixed** |
| Biggest blocker | MIT root license conflicts with Apache-2.0 source headers/footer |
| Security-publication status | `.env` is ignored and not tracked, but `SECURITY.md` is missing |

## OSI Open Source Criteria Checklist

| # | Criterion | Status | Evidence / Notes |
|---:|---|---|---|
| 1 | Free redistribution allowed | Done | `LICENSE.txt` uses MIT text, which permits use, copy, modification, merge, publication, distribution, sublicense, and sale. |
| 2 | Source code available | Done | Repository contains PHP API source, UI HTML/CSS/JS, config JSON, docs, and scripts. |
| 3 | Derived works allowed | Done | MIT license permits modification and sublicensing. |
| 4 | Integrity of author's source code | Done | MIT has no restrictive patch-only requirement. |
| 5 | No discrimination against persons or groups | Done | MIT does not discriminate. |
| 6 | No discrimination against fields of endeavor | Done | MIT does not restrict medical, government, commercial, or research use. |
| 7 | License distribution | Done | Root `LICENSE.txt` applies to recipients if kept with the software. |
| 8 | License not specific to a product | Done | MIT is project-independent. |
| 9 | License does not restrict other software | Done | MIT does not impose restrictions on bundled/adjacent software. |
| 10 | License technology-neutral | Done | MIT is technology-neutral. |

## Repository Readiness Checklist

| Item | Status | Evidence | Required Action |
|---|---|---|---|
| Root license file | Done | `LICENSE.txt` exists. | Confirm final license choice. |
| License consistency | Partial | Root license is MIT; many files and UI footer mention Apache-2.0. | Choose one license and align file headers, footer metadata, README, and docs. |
| README | Partial | `README.md` only contains `https://saqshi.readme.io/`. | Add local overview, setup, requirements, configuration, database setup, testing, API docs, and contribution links. |
| Contribution guide | Partial | `CONTRIBUTING.md` exists but is very short. | Add branch workflow, coding standards, test expectations, issue/PR process. |
| Code of conduct | Done | `CODE_OF_CONDUCT.md` exists. | Keep updated with contact/escalation details. |
| Security policy | Missing | `SECURITY.md` not found. | Add vulnerability reporting process, supported versions, disclosure policy. |
| Notice/attribution file | Missing | `NOTICE` not found. | Add third-party attribution, especially if Apache-2.0 is selected or bundled libraries require notices. |
| Third-party dependency inventory | Partial | Bundled assets include Font Awesome references; no central dependency/license inventory found. | Add `docs/compliance/third_party_licenses.md` or SBOM. |
| Environment sample | Done | `.env.example` exists. | Keep secrets out of examples. |
| Real secrets excluded | Done | `.gitignore` excludes `.env`, `.env.*`, keys, logs, uploads. Git does not list `.env` as tracked. | Continue checking before every release. |
| Generated/private storage excluded | Done | `.gitignore` excludes `api/storage/events/*.log`, `api/storage/logs/*.log`, `api/storage/keys/`, `uploads/`. | Good. |
| API documentation | Done | `docs/api/openapi.yaml`, `swagger-ui.html`, Postman collection, and guide exist. | Keep synchronized with API changes. |
| Testing documentation | Done | Test plan, black-box/white-box, VAPT, load testing, WCAG docs exist under `docs/testing`. | Keep results updated with each release. |
| Accessibility statement | Done | `docs/testing/saqshi_wcag_web_platform_compliance.md` exists. | Add manual screen-reader/keyboard results when completed. |
| Security review notes | Done | `docs/security/sql_injection_security_review.md` exists. | Keep remediation status current. |
| Database migration/install docs | Missing / Unknown | No migration guide found in this review. | Add schema import, migration order, seed data, and rollback notes. |
| Public issue templates | Missing / Unknown | `.github` directory not found in quick scan. | Add bug report, feature request, security advisory templates. |
| Release/versioning policy | Partial | UI/footer mentions version, docs contain versions, but no release policy found. | Add semantic versioning and changelog policy. |
| Changelog | Missing / Unknown | No `CHANGELOG.md` found in quick scan. | Add release history and migration notes. |
| Governance/maintainers | Missing / Unknown | No maintainers/governance file found. | Add maintainers, review rules, decision process. |
| Trademark/branding policy | Missing | SaQshi name/logo usage is not defined. | Add `TRADEMARK.md` or branding section if public reuse matters. |
| Data/privacy guidance | Partial | App handles facility/user/health quality data; security docs exist, but public privacy guidance not found. | Add privacy and deployment hardening guide. |

## Application-Specific Open Source Checklist

| Area | Status | Notes |
|---|---|---|
| Facility assessment workflow source available | Done | UI/API files are present. |
| CQI workflow source available | Done | Gap analysis, action plan, closure modules have been built. |
| Performance monitoring source available | Done | KPI/outcome/dashboard/trend modules exist. |
| State monitoring source available | Done | State dashboard, map, certification, CQI, performance, reports, drill-down, user admin modules exist. |
| API event abstraction | Done | `api/core/Event.php` exists for future event-driven/Kafka migration. |
| Friendly error handling | Partial | Error handling exists, but legacy endpoints may still need review. |
| Secrets handling | Partial | `.env` pattern exists and DB config is env-based, but release needs secret scanning before publication. |
| Large config data | Partial | Large JSON config files are present; public data ownership/licensing should be confirmed. |
| Healthcare data caution | Partial | No real patient data should be included in public repo. Facility master data licensing should be confirmed. |

## Required Fixes Before Public Release

### 1. Decide Final License

Pick one:

- **MIT**: matches current `LICENSE.txt`; simpler and permissive.
- **Apache-2.0**: matches many file headers and footer text; includes explicit patent license and NOTICE conventions.

After selecting, update:

- `LICENSE.txt`
- Source file headers
- `ui/components/footer/footer.js`
- Login/footer visible license text
- README license section
- Any generated docs mentioning license

### 2. Add `SECURITY.md`

Minimum content:

- How to report vulnerabilities.
- What information to include.
- Expected response timeline.
- Supported versions.
- Request not to disclose publicly until reviewed.

### 3. Add Third-Party Attribution

Create one of:

- `NOTICE`
- `docs/compliance/third_party_licenses.md`
- `SBOM` file

Include bundled libraries/assets such as icons, fonts, maps, spreadsheet/report libraries, and any copied templates.

### 4. Expand `README.md`

Recommended sections:

- What SaQshi is.
- Features.
- Architecture.
- Requirements.
- Installation.
- `.env` configuration.
- Database setup.
- Running locally.
- API docs and Postman links.
- Testing.
- Security.
- Contributing.
- License.

### 5. Add Release Safety Checklist

Before pushing publicly:

- Confirm `.env` is not tracked.
- Run secret scan.
- Remove test credentials and sample private data.
- Verify database dumps do not contain live data.
- Verify upload/log/key directories are ignored.
- Run syntax checks and smoke tests.
- Update OpenAPI/Postman docs.
- Update changelog.

## Final Recommendation

SaQshi should be treated as **open-source eligible but not yet public-release complete**.

The next best step is to fix the license inconsistency first. Once the root license, source headers, visible footer text, and README all say the same thing, the project will have a much cleaner open-source foundation.
