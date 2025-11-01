# Symfony + Google Gemini Candidate Scoring Backend - Setup Guide

## ✅ What's Been Created

Your complete backend project structure is ready with two main scripts for installation and cleanup:

### 📦 Main Scripts

1. **`setup.sh`** - Full automated setup that:
   - ✅ Checks for Docker & Docker Compose
   - ✅ Creates Symfony 7.0 project using official Docker
   - ✅ Installs all required packages (http-client, serializer, validator, nelmio/cors-bundle)
   - ✅ Generates all application code:
     - Controller (CandidateAnalysisController)
     - Service (GeminiAnalysisService)
     - DTOs (CandidateAnalysisRequest, CandidateAnalysisResponse)
     - Exception handler (GeminiException)
   - ✅ Configures CORS (nelmio_cors.yaml)
   - ✅ Creates Docker configuration:
     - Dockerfile for PHP 8.2-FPM
     - Dockerfile for Nginx
     - docker-compose.yml
     - Nginx configuration
   - ✅ Creates documentation (.env.example, README.md)

2. **`cleanup.sh`** - Safe cleanup that:
   - ✅ Removes Docker containers and volumes
   - ✅ Cleans all project files
   - ✅ Preserves .git repository
   - ✅ Keeps setup/cleanup scripts

### 📋 Project Files Currently Available

```
/home/ubuntu/to_delete/
├── setup.sh                          # Run this first!
├── cleanup.sh                        # For removal
├── .env.example                      # Environment template
├── README.md.template                # Full documentation
├── docker-compose.yml.template       # Docker Compose template
├── dockerfile.php.template           # PHP Dockerfile template
├── dockerfile.nginx.template         # Nginx Dockerfile template
└── nginx.conf.template              # Nginx config template
```

## 🚀 Quick Start (3 Steps)

### Step 1: Run Setup

```bash
cd /home/ubuntu/to_delete
bash setup.sh
```

This will:
- Create Symfony 7.0 project
- Install all dependencies
- Generate all application code
- Set up Docker configuration
- Create .env.example and README.md

**⏱️ Takes ~5-10 minutes**

### Step 2: Configure Environment

```bash
# Copy .env.example to .env
cp .env.example .env

# Edit and add your Gemini API key
nano .env
# Find GEMINI_API_KEY and paste your actual API key
```

### Step 3: Start with Docker

```bash
# Build Docker images
docker-compose build

# Start containers
docker-compose up -d

# Verify it's running
curl -X GET http://localhost:8000/api/health
# Expected response: {"status":"ok"}
```

## 📡 API Endpoints (After Setup)

### Health Check
```bash
curl -X GET http://localhost:8000/api/health
```

### Analyze Candidate
```bash
curl -X POST http://localhost:8000/api/analyze-candidate \
  -H "Content-Type: application/json" \
  -d '{
    "jobDescription": "Senior PHP Developer with 5+ years Symfony experience required. Must know Docker, REST APIs, and database design.",
    "candidateCV": "Jane Smith\n\nExperience:\nSenior PHP Developer at TechCorp (2020-2024)\n- Developed REST APIs using Symfony 6.x\n- 8 years total PHP experience\n- Docker containerization expert\n- Database optimization specialist\n\nEducation:\nBS Computer Science"
  }'
```

**Expected Response:**
```json
{
  "score": 85,
  "summary": "Excellent match with strong Symfony background and 8 years PHP experience...",
  "positives": [
    "8 years PHP experience exceeds minimum requirement",
    "Strong Symfony 6 expertise matches stack requirements",
    "Docker containerization skills match requirements",
    "Database optimization background valuable"
  ],
  "negatives": [
    "No mention of testing frameworks (nice-to-have)",
    "No specific API documentation experience mentioned"
  ]
}
```

## 📁 Generated Project Structure (After setup.sh)

After running `setup.sh`, your project will look like:

```
/home/ubuntu/to_delete/
├── public/
│   └── index.php                     # Symfony entry point
├── src/
│   ├── Controller/
│   │   └── CandidateAnalysisController.php
│   ├── Service/
│   │   └── GeminiAnalysisService.php
│   ├── DTO/
│   │   ├── CandidateAnalysisRequest.php
│   │   └── CandidateAnalysisResponse.php
│   └── Exception/
│       └── GeminiException.php
├── config/
│   ├── packages/
│   │   └── nelmio_cors.yaml
│   ├── routes.yaml
│   └── ...
├── var/
│   ├── cache/
│   └── log/
├── docker/
│   ├── php/
│   │   └── Dockerfile
│   └── nginx/
│       ├── Dockerfile
│       └── default.conf
├── docker-compose.yml
├── .env                              # Your configuration
├── .env.example                      # Template
├── .gitignore
├── composer.json
├── composer.lock
├── README.md                         # Full documentation
├── setup.sh
├── cleanup.sh
└── ... (other Symfony files)
```

## 🔐 Environment Configuration

The `.env.example` file created contains:

```env
###> symfony/framework-bundle ###
APP_ENV=dev
APP_DEBUG=1
APP_SECRET=ChangeMe!ChangeMe!ChangeMe!ChangeMe!ChangeMe!ChangeMe!
###< symfony/framework-bundle ###

###> Gemini API Configuration ###
GEMINI_API_KEY=your_gemini_api_key_here
###< Gemini API Configuration ###
```

**Required changes:**
1. Keep `APP_ENV=dev` for development
2. Set `GEMINI_API_KEY` to your actual API key from Google AI Studio

## 🐳 Docker Architecture

After setup, you'll have:

### PHP Container (9000)
- PHP 8.2-FPM
- All Symfony packages
- Composer dependencies
- Connected to symfony-network

### Nginx Container (8000)
- Nginx web server
- Configured as reverse proxy to PHP-FPM
- Serves on http://localhost:8000
- Static file caching enabled
- Connected to symfony-network

**Communication:**
```
Your Machine (port 8000)
        ↓
   Nginx Container
        ↓
   PHP-FPM Container
        ↓
   Google Gemini API
```

## 🧹 To Remove Everything Later

```bash
bash cleanup.sh
```

This safely removes:
- Docker containers
- Docker volumes
- All project files
- Keeps .git repository

## ❓ Troubleshooting

### Setup fails with permission denied
```bash
chmod +x setup.sh cleanup.sh
bash setup.sh
```

### Docker not installed
Install Docker from: https://docs.docker.com/get-docker/

### Containers won't start
```bash
# Check what went wrong
docker-compose logs php
docker-compose logs nginx

# Rebuild from scratch
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

### Port 8000 already in use
Edit `docker-compose.yml`:
```yaml
ports:
  - "8080:80"  # Change 8000 to 8080 or another port
```

### API key not working
1. Verify .env file: `cat .env | grep GEMINI`
2. Restart containers: `docker-compose restart`
3. Check Google API key is valid in Google AI Studio
4. Check API is enabled in Google Cloud Console

## 📚 Additional Resources

After setup, see `README.md` for:
- Full API documentation
- All configuration options
- Production considerations
- Development workflow
- Database setup (if needed)
- Testing guidelines

## 💡 Pro Tips

1. **Development Loop:**
   ```bash
   # Make code changes
   nano src/Service/GeminiAnalysisService.php

   # Clear cache in container
   docker-compose exec php bin/console cache:clear

   # Test changes
   curl http://localhost:8000/api/health
   ```

2. **View Live Logs:**
   ```bash
   docker-compose logs -f php
   docker-compose logs -f nginx
   ```

3. **Execute Commands in Container:**
   ```bash
   docker-compose exec php bin/console debug:routes
   docker-compose exec php bin/console cache:clear
   ```

4. **Backup Before Cleanup:**
   ```bash
   # Save your work
   git add .
   git commit -m "My changes"

   # Now safe to cleanup
   bash cleanup.sh
   ```

## 🎯 What's Next?

1. ✅ Run `setup.sh`
2. ✅ Configure `.env` with Gemini API key
3. ✅ Run `docker-compose build`
4. ✅ Run `docker-compose up -d`
5. ✅ Test with curl examples above
6. ✅ Read README.md for full documentation
7. ✅ Integrate with your React frontend

## ✨ Key Features

Your backend includes:

- ✅ **Full Gemini Integration**: Sends CV and job description to Gemini 1.5 Pro
- ✅ **Structured Output**: Always returns JSON with score, summary, positives, negatives
- ✅ **Input Validation**: 50-10000 character limits on both fields
- ✅ **Error Handling**: Proper HTTP status codes and error messages
- ✅ **CORS Enabled**: Ready for React frontend integration
- ✅ **Docker Ready**: Start development immediately without local setup
- ✅ **Best Practices**: Services, DTOs, Dependency Injection, Exception Handling
- ✅ **Production Ready**: Includes caching, optimization, security headers

## 📞 Need Help?

1. Check troubleshooting section above
2. Review Docker logs: `docker-compose logs`
3. Check Google Gemini API documentation
4. Verify Gemini API key has correct permissions

---

**You're all set! Run `bash setup.sh` to begin! 🚀**
