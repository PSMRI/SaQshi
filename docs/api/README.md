# SaQshi API Testing Assets

Version: 1.0  
Updated: 2026-07-13

## Files

| File | Purpose |
|---|---|
| `saqshi_postman_collection.json` | Postman collection with grouped requests and sample payloads. |
| `openapi.yaml` | Swagger/OpenAPI 3.0 specification for API documentation and client/testing tools. |
| `swagger-ui.html` | Optional Swagger UI viewer for `openapi.yaml`. It uses Swagger UI CDN assets. |
| `POSTMAN_TESTING_GUIDE.md` | Step-by-step Postman guide covering login, CSRF, captcha, sample payloads, uploads, downloads and common errors. |

## Postman Setup

1. Import `docs/api/saqshi_postman_collection.json` into Postman.
2. Set collection variable `baseUrl` to your API base path. Example: `http://localhost:94/api`.
3. Call `Auth > Get CSRF Token`.
4. Copy the token into collection variable `csrfToken`.
5. Call `Auth > Get Captcha` and solve/enter the captcha value.
6. For login, generate `password_enc` using the public key from `Auth > Get Login Public Key`. The browser login page already does this automatically; Postman requires a pre-request script or manual encrypted value.
7. After login, Postman should keep the `SAQSHI_SESSION` cookie for authenticated requests.

Detailed step-by-step guide:

```text
docs/api/POSTMAN_TESTING_GUIDE.md
```

## Swagger Setup

Use any Swagger/OpenAPI viewer and load:

```text
docs/api/openapi.yaml
```

For local browser viewing, open:

```text
docs/api/swagger-ui.html
```

If the YAML does not load from a direct file open, serve the project through localhost and open the same HTML page from that host.

## Notes

- State-changing APIs require `X-CSRF-TOKEN`.
- Authenticated APIs require the `SAQSHI_SESSION` cookie.
- Monitoring APIs are automatically scoped by logged-in role:
  - Role 9: State
  - Role 5: Regional/Division
  - Role 4: District
  - Role 8: Block
- Some report endpoints return file downloads instead of JSON.
