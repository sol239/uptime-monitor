FROM python:3.11-slim

WORKDIR /app

# Copy only service code
COPY Services/python-checker/ ./

# Install Python dependencies (ignore if no requirements.txt)
RUN if [ -f requirements.txt ]; then pip install --no-cache-dir -r requirements.txt; fi

CMD ["python", "main.py"]
