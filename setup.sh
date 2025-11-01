#!/bin/bash

# Symfony Backend Setup Script (Docker-First Approach)
# Creates a Symfony 7.0 project using official Docker setup from symfony.com

set -e  # Exit on any error

echo "🚀 Starting Symfony Backend Setup (Docker-First)..."
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Step 1: Check Docker and Docker Compose
echo -e "${YELLOW}[1/4] Checking Docker installation...${NC}"
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker is not installed. Please install Docker first.${NC}"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}❌ Docker Compose is not installed. Please install Docker Compose first.${NC}"
    exit 1
fi

echo "✓ Docker version: $(docker --version)"
echo "✓ Docker Compose version: $(docker-compose --version)"

# Step 2: Create project structure using Docker
echo -e "${YELLOW}[2/4] Creating Symfony 7.0 project with Docker...${NC}"

# Backup .git if exists
if [ -d .git ]; then
    cp -r .git /tmp/.git_backup_$$
    GIT_BACKUP=true
fi

# Remove all files except setup/cleanup scripts and git
find . -maxdepth 1 -type f -not -name "setup.sh" -not -name "cleanup.sh" -not -name ".gitignore" -delete
find . -maxdepth 1 -type d -not -name "." -not -name ".git" -exec rm -rf {} + 2>/dev/null || true

# Restore git if it existed
if [ "$GIT_BACKUP" = true ]; then
    mv /tmp/.git_backup_$$ .git
fi

# Create Symfony project using Docker
docker run --rm -u "$(id -u):$(id -g)" \
    -v "$(pwd):/workdir" \
    -w /workdir \
    php:8.2-cli \
    bash -c "
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/tmp --filename=composer
        /tmp/composer create-project symfony/skeleton . --no-interaction --prefer-dist '7.0.*'
    "

# Step 3: Install required packages
echo -e "${YELLOW}[3/4] Installing required Symfony packages...${NC}"

docker run --rm -u "$(id -u):$(id -g)" \
    -v "$(pwd):/workdir" \
    -w /workdir \
    php:8.2-cli \
    bash -c "
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/tmp --filename=composer
        /tmp/composer require \
            symfony/http-client \
            symfony/serializer \
            symfony/validator \
            nelmio/cors-bundle \
            --no-interaction \
            --prefer-dist
    "

# Step 4: Create project structure and application code
echo -e "${YELLOW}[4/4] Setting up project structure and application code...${NC}"
mkdir -p src/{Controller,Service,DTO,Exception}
mkdir -p docker/{php,nginx}

# Generate DTOs
cat > src/DTO/CandidateAnalysisRequest.php << 'EOF'
<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CandidateAnalysisRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 50, max: 10000)]
    private string $jobDescription;

    #[Assert\NotBlank]
    #[Assert\Length(min: 50, max: 10000)]
    private string $candidateCV;

    public function getJobDescription(): string
    {
        return $this->jobDescription;
    }

    public function setJobDescription(string $jobDescription): self
    {
        $this->jobDescription = $jobDescription;
        return $this;
    }

    public function getCandidateCV(): string
    {
        return $this->candidateCV;
    }

    public function setCandidateCV(string $candidateCV): self
    {
        $this->candidateCV = $candidateCV;
        return $this;
    }
}
EOF

cat > src/DTO/CandidateAnalysisResponse.php << 'EOF'
<?php

namespace App\DTO;

class CandidateAnalysisResponse
{
    private int $score;
    private string $summary;
    private array $positives;
    private array $negatives;

    public function __construct(int $score, string $summary, array $positives, array $negatives)
    {
        $this->score = $score;
        $this->summary = $summary;
        $this->positives = $positives;
        $this->negatives = $negatives;
    }

    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'summary' => $this->summary,
            'positives' => $this->positives,
            'negatives' => $this->negatives,
        ];
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getPositives(): array
    {
        return $this->positives;
    }

    public function getNegatives(): array
    {
        return $this->negatives;
    }
}
EOF

# Generate Exception
cat > src/Exception/GeminiException.php << 'EOF'
<?php

namespace App\Exception;

use RuntimeException;

class GeminiException extends RuntimeException
{
    public static function fromResponse(string $message, ?array $response = null): self
    {
        $message = sprintf('Gemini API Error: %s', $message);
        if ($response !== null) {
            $message .= sprintf(' | Response: %s', json_encode($response));
        }
        return new self($message);
    }

    public static function invalidResponse(): self
    {
        return new self('Gemini API returned invalid response structure');
    }

    public static function missingApiKey(): self
    {
        return new self('GEMINI_API_KEY environment variable is not set');
    }
}
EOF

# Generate Service
cat > src/Service/GeminiAnalysisService.php << 'EOF'
<?php

namespace App\Service;

use App\DTO\CandidateAnalysisResponse;
use App\Exception\GeminiException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiAnalysisService
{
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent';
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are an expert HR professional specialized in candidate analysis. Your task is to:

1. Compare the candidate's CV objectively with the job description
2. Evaluate the candidate on:
   - Technical Skills Alignment (40% weight)
   - Relevant Experience (30% weight)
   - Education & Certifications (15% weight)
   - Soft Skills & Career Progression (15% weight)
3. Provide a score from 0 to 100
4. Identify 3-5 key strengths with concrete evidence from the CV
5. Identify 2-3 areas for improvement or missing competencies
6. Be factual, professional, and objective

You MUST respond ONLY with valid JSON (no other text) with this exact structure:
{
  "score": <integer 0-100>,
  "summary": "<2-3 sentence summary of the overall fit>",
  "positives": ["<strength 1>", "<strength 2>", ...],
  "negatives": ["<improvement area 1>", "<improvement area 2>", ...]
}
PROMPT;

    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    public function analyzeCandidate(string $jobDescription, string $candidateCV): CandidateAnalysisResponse
    {
        $apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
        if (!$apiKey) {
            throw GeminiException::missingApiKey();
        }

        $prompt = sprintf(
            "JOB DESCRIPTION:\n%s\n\nCANDIDATE CV:\n%s",
            $jobDescription,
            $candidateCV
        );

        $response = $this->httpClient->request('POST', self::GEMINI_API_URL, [
            'query' => ['key' => $apiKey],
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => self::SYSTEM_PROMPT . "\n\n" . $prompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 1024,
                    'response_mime_type' => 'application/json',
                ],
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw GeminiException::fromResponse(
                sprintf('HTTP %d', $response->getStatusCode()),
                json_decode($response->getContent(false), true)
            );
        }

        return $this->parseResponse($response->toArray());
    }

    private function parseResponse(array $response): CandidateAnalysisResponse
    {
        if (!isset($response['contents'][0]['parts'][0]['text'])) {
            throw GeminiException::invalidResponse();
        }

        $jsonText = $response['contents'][0]['parts'][0]['text'];

        // Extract JSON from potential markdown code blocks
        if (preg_match('/```json\s*(.*?)\s*```/s', $jsonText, $matches)) {
            $jsonText = $matches[1];
        } elseif (preg_match('/```\s*(.*?)\s*```/s', $jsonText, $matches)) {
            $jsonText = $matches[1];
        }

        $data = json_decode(trim($jsonText), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw GeminiException::fromResponse(
                sprintf('Invalid JSON in response: %s', json_last_error_msg())
            );
        }

        if (!isset($data['score'], $data['summary'], $data['positives'], $data['negatives'])) {
            throw GeminiException::invalidResponse();
        }

        return new CandidateAnalysisResponse(
            (int) $data['score'],
            (string) $data['summary'],
            (array) $data['positives'],
            (array) $data['negatives']
        );
    }
}
EOF

# Generate Controller
cat > src/Controller/CandidateAnalysisController.php << 'EOF'
<?php

namespace App\Controller;

use App\DTO\CandidateAnalysisRequest;
use App\Service\GeminiAnalysisService;
use App\Exception\GeminiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CandidateAnalysisController extends AbstractController
{
    #[Route('/api/analyze-candidate', methods: ['POST'])]
    public function analyze(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        GeminiAnalysisService $geminiService
    ): JsonResponse {
        try {
            // Deserialize request
            $analysisRequest = $serializer->deserialize(
                $request->getContent(),
                CandidateAnalysisRequest::class,
                'json'
            );

            // Validate request
            $errors = $validator->validate($analysisRequest);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }
                return $this->json(
                    ['error' => 'Validation failed', 'details' => $errorMessages],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Analyze candidate
            $response = $geminiService->analyzeCandidate(
                $analysisRequest->getJobDescription(),
                $analysisRequest->getCandidateCV()
            );

            return $this->json($response->toArray(), Response::HTTP_OK);

        } catch (GeminiException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_GATEWAY
            );
        } catch (\Exception $e) {
            return $this->json(
                ['error' => 'Internal server error', 'message' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/api/health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json(['status' => 'ok'], Response::HTTP_OK);
    }
}
EOF

echo "✓ Created DTOs, Service, Controller, and Exception classes"

# Generate CORS configuration
mkdir -p config/packages
cat > config/packages/nelmio_cors.yaml << 'EOF'
nelmio_cors:
    defaults:
        allow_credentials: true
        allow_origin: ['*']
        allow_headers: ['*']
        allow_methods: ['POST', 'GET', 'OPTIONS', 'DELETE', 'PUT', 'PATCH']
        max_age: 3600
        expose_headers: ['Content-Disposition']

    paths:
        '^/api/':
            allow_origin: ['*']
            allow_headers: ['*']
            allow_methods: ['POST', 'GET', 'OPTIONS']
            max_age: 3600
EOF

echo "✓ Created CORS configuration"

# Generate Docker files
mkdir -p docker/{php,nginx}

# Create PHP Dockerfile
cat > docker/php/Dockerfile << 'EOF'
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libzip-dev \
    zip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    zip \
    curl \
    intl \
    mbstring

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /app

# Copy application code
COPY . /app

# Create cache and log directories
RUN mkdir -p var/cache var/log

# Set permissions
RUN chown -R www-data:www-data /app

# Install dependencies
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist

# Clear cache
RUN php bin/console cache:clear

EXPOSE 9000

CMD ["php-fpm"]
EOF

# Create Nginx Dockerfile
cat > docker/nginx/Dockerfile << 'EOF'
FROM nginx:latest

# Copy nginx configuration
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
EOF

# Create Nginx configuration
cat > docker/nginx/default.conf << 'EOF'
upstream backend {
    server php:9000;
}

server {
    listen 80;
    server_name _;
    root /app/public;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss;
    gzip_vary on;

    location / {
        try_files $uri @rewrite;
    }

    location @rewrite {
        rewrite ^(.*)$ /index.php/$1 last;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass backend;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_index index.php;
        include fastcgi_params;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }
}
EOF

echo "✓ Created Docker configuration (Dockerfile, docker-compose.yml, nginx.conf)"

# Create docker-compose.yml
cat > docker-compose.yml << 'EOF'
version: '3.8'

services:
  php:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: candidate-scoring-php
    environment:
      - APP_ENV=dev
      - APP_DEBUG=1
      - GEMINI_API_KEY=${GEMINI_API_KEY}
    volumes:
      - ./:/app
      - ./docker/php/conf.d/symfony.ini:/usr/local/etc/php/conf.d/symfony.ini
    ports:
      - "9000:9000"
    networks:
      - symfony-network
    labels:
      - "project=candidate-scoring"

  nginx:
    build:
      context: .
      dockerfile: docker/nginx/Dockerfile
    container_name: candidate-scoring-nginx
    depends_on:
      - php
    volumes:
      - ./public:/app/public
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
    ports:
      - "8000:80"
    networks:
      - symfony-network
    labels:
      - "project=candidate-scoring"

networks:
  symfony-network:
    driver: bridge
EOF

# Create README.md if it doesn't exist
if [ ! -f README.md ] || [ ! -s README.md ]; then
    cat > README.md << 'EOF'
# Candidate Scoring Backend - Symfony + Google Gemini

A production-ready Symfony 7 REST API backend for automatic candidate scoring using Google Gemini AI.

## 🎯 Features

- **AI-Powered Analysis**: Uses Google Gemini 1.5 Pro to analyze CVs against job descriptions
- **Structured Output**: Returns consistent JSON response with scores and analysis
- **Full Docker Support**: Complete Docker Compose setup for development and production
- **CORS Enabled**: Ready for frontend integration
- **Input Validation**: Comprehensive request validation with meaningful error messages
- **Exception Handling**: Professional error handling with detailed responses
- **Best Practices**: Follows Symfony conventions (Services, DTOs, Dependency Injection)

## 🚀 Quick Start

### 1. Configure Environment

```bash
# .env file is already created
nano .env  # Edit and add your GEMINI_API_KEY
```

### 2. Start Docker Containers

```bash
docker-compose build
docker-compose up -d
```

### 3. Verify Installation

```bash
curl -X GET http://localhost:8000/api/health
# Response: {"status":"ok"}
```

## 📡 API Endpoints

### Health Check
**GET** `/api/health`

### Analyze Candidate
**POST** `/api/analyze-candidate`

Request body:
```json
{
  "jobDescription": "Senior Developer required...",
  "candidateCV": "Jane Doe\n\nExperience:\nSenior Developer..."
}
```

Response:
```json
{
  "score": 85,
  "summary": "Excellent match with strong background...",
  "positives": ["Skill 1", "Skill 2"],
  "negatives": ["Area 1", "Area 2"]
}
```

## 🧹 Cleanup

```bash
bash cleanup.sh
```

## 📚 Documentation

See README.md for full API documentation and configuration details.
EOF
fi

echo "✓ Created docker-compose.yml and README.md"

echo ""
echo -e "${GREEN}✅ Setup completed successfully!${NC}"
echo ""
echo "Next steps:"
echo "1. Update .env with your Gemini API key:"
echo "   nano .env"
echo "   # Add: GEMINI_API_KEY=your_api_key_here"
echo ""
echo "2. Build Docker images:"
echo "   docker-compose build"
echo ""
echo "3. Start the project with Docker:"
echo "   docker-compose up -d"
echo ""
echo "4. Run migrations and cache clear inside container:"
echo "   docker-compose exec php bin/console cache:clear"
echo ""
echo "5. Test the API:"
echo "   curl -X POST http://localhost:8000/api/health"
echo ""
echo "6. Full analysis endpoint:"
echo "   curl -X POST http://localhost:8000/api/analyze-candidate \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{\"jobDescription\": \"...\", \"candidateCV\": \"...\"}'"
echo ""
echo "To clean up and uninstall everything, run: ./cleanup.sh"
