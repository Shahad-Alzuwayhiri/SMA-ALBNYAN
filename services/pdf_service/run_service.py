"""
FastAPI PDF Service Launcher
Runs the PDF service with proper configuration for background operation
"""
import uvicorn
import os
import sys

if __name__ == "__main__":
    # Add the service directory to path
    service_dir = os.path.dirname(os.path.abspath(__file__))
    sys.path.insert(0, service_dir)
    
    # Import the app
    from main import app
    
    # Run with proper configuration
    uvicorn.run(
        app,
        host="127.0.0.1",
        port=8001,
        log_level="info",
        access_log=True
    )