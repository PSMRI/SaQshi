from fastapi import FastAPI, Request
from fastapi.responses import JSONResponse
from httpx import HTTPError
from app.api.health import router as health_router
from app.api.ask import router as ask_router
from app.api.checkpoint import router as checkpoint_router
from app.api.cqi import router as cqi_router
from app.api.summary import router as summary_router

app = FastAPI(title='SaQshi Local AI Gateway', version='1.0.0')
for router in (health_router, ask_router, checkpoint_router, cqi_router, summary_router): app.include_router(router)
@app.exception_handler(HTTPError)
async def offline(_: Request, __):
    return JSONResponse(status_code=503, content={'success': False, 'code': 'AI_SERVICE_UNAVAILABLE', 'message': 'Local model is unavailable.'})
