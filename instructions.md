# Prompt pour IA - Génération Backend Scoring Candidatures (Symfony + Gemini)

## Contexte
Je développe une application de scoring automatique de candidatures avec IA. Le frontend React est déjà prêt et utilise le stockage persistant de Claude. J'ai besoin d'une vraie intégration avec l'API Google Gemini pour analyser les candidatures en utilisant Symfony.

## Objectif
Créer un backend Symfony qui :
1. Reçoit une fiche de poste et un CV de candidat via API REST
2. Envoie ces données à l'API Google Gemini (gemini-pro ou gemini-1.5-pro)
3. Retourne un scoring structuré avec analyse détaillée
4. Utilise les bonnes pratiques Symfony (Services, Dependency Injection, etc.)

## Structure de la réponse attendue

L'API doit retourner un objet JSON avec cette structure exacte :
```json
{
  "score": 85,
  "summary": "Candidat excellent avec 7 compétences clés identifiées...",
  "positives": [
    "Expertise approfondie en automatisation des tests avec Playwright, Cypress et Selenium",
    "Expérience significative chez Microsoft et autres entreprises tech de renom",
    "Maîtrise complète des outils de gestion de projet (Jira, Azure)",
    "Progression de carrière exemplaire de consultant junior à Senior QA Manager"
  ],
  "negatives": [
    "Expérience en gestion d'équipe pourrait être plus détaillée",
    "Certaines compétences en tests de performance non mentionnées"
  ]
}
```

## Spécifications techniques

**Backend requis :**
- Symfony 6.x ou 7.x
- API Platform (optionnel mais recommandé) OU Controller classique
- Service dédié pour l'intégration Google Gemini API
- Endpoint POST `/api/analyze-candidate`
- Validation des données avec Symfony Validator
- Gestion des erreurs avec Exception Handler personnalisé
- CORS configuré via nelmio/cors-bundle
- Variables d'environnement dans .env pour la clé Gemini

**Packages recommandés :**
```bash
composer require symfony/http-client
composer require symfony/serializer
composer require symfony/validator
composer require nelmio/cors-bundle
```

**Structure du projet souhaité :**
```
symfony-backend/
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
│   └── packages/
│       └── nelmio_cors.yaml
├── docker/
│   ├── php/
│   │   └── Dockerfile
│   └── nginx/
│       ├── Dockerfile
│       └── default.conf
├── docker-compose.yml
├── .env.example
├── .dockerignore
├── Makefile (optionnel)
└── README.md
```

## Architecture Symfony souhaitée

**1. Controller :**
```php
#[Route('/api/analyze-candidate', methods: ['POST'])]
public function analyze(Request $request): JsonResponse
```

**2. Service Gemini :**
```php
class GeminiAnalysisService
{
    public function analyzeCandidate(string $jobDescription, string $cv): array
    {
        // Logique d'appel à Google Gemini API
    }
}
```

**3. DTO pour la validation :**
```php
class CandidateAnalysisRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 50, max: 10000)]
    private string $jobDescription;

    #[Assert\NotBlank]
    #[Assert\Length(min: 50, max: 10000)]
    private string $candidateCV;
}
```

## API Google Gemini - Détails techniques

**Endpoint Gemini :**
```
POST https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent
```

**Format de la requête Gemini :**
```json
{
  "contents": [{
    "parts": [{
      "text": "PROMPT_SYSTÈME + jobDescription + candidateCV"
    }]
  }],
  "generationConfig": {
    "temperature": 0.3,
    "topK": 40,
    "topP": 0.95,
    "maxOutputTokens": 1024,
    "response_mime_type": "application/json"
  }
}
```

**Headers requis :**
```
Content-Type: application/json
x-goog-api-key: YOUR_GEMINI_API_KEY
```

## Prompt système pour Gemini

Le prompt système à utiliser avec l'API Gemini doit :
- Être un expert RH en analyse de candidatures
- Comparer objectivement le CV avec la fiche de poste
- Donner un score de 0 à 100 basé sur :
  * Adéquation des compétences techniques (40%)
  * Expérience pertinente (30%)
  * Formation et certifications (15%)
  * Soft skills et progression de carrière (15%)
- Identifier 3-5 points positifs concrets
- Identifier 2-3 points négatifs ou axes d'amélioration
- Être factuel et professionnel dans l'analyse
- **Répondre UNIQUEMENT en JSON valide** avec la structure exacte demandée

**Template du prompt :**
```
Tu es un expert RH spécialisé dans l'analyse de candidatures. Analyse objectivement le CV suivant par rapport à la fiche de poste fournie.

FICHE DE POSTE :
{jobDescription}

CV DU CANDIDAT :
{candidateCV}

Réponds UNIQUEMENT avec un objet JSON valide (pas de markdown, pas de texte supplémentaire) avec cette structure exacte :
{
  "score": <nombre entre 0 et 100>,
  "summary": "<résumé en 1-2 phrases>",
  "positives": ["point1", "point2", "point3", "point4"],
  "negatives": ["point1", "point2", "point3"]
}

Critères de notation (total 100 points) :
- Compétences techniques (40 points)
- Expérience pertinente (30 points)
- Formation et certifications (15 points)
- Soft skills et progression (15 points)
```

## Format de la requête attendue

```http
POST /api/analyze-candidate
Content-Type: application/json

{
  "jobDescription": "Senior QA Engineer avec 5+ ans d'expérience...",
  "candidateCV": "Mohamed Ali Bannour, 9 ans d'expérience en QA..."
}
```

## Configuration Gemini

**Dans .env :**
```env
# Application
APP_ENV=dev
APP_SECRET=your-secret-key

# Gemini API
GEMINI_API_KEY=AIzaSyxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
GEMINI_MODEL=gemini-1.5-pro
GEMINI_TEMPERATURE=0.3
GEMINI_MAX_TOKENS=1024

# Database (Docker)
DATABASE_URL="mysql://symfony:symfony@database:3306/recruitment?serverVersion=8.0"

# CORS
CORS_ALLOW_ORIGIN=http://localhost:3000
```

**.env.example :**
```env
# Application
APP_ENV=dev
APP_SECRET=changeme

# Gemini API - Obtenir la clé sur https://makersuite.google.com/app/apikey
GEMINI_API_KEY=YOUR_GEMINI_API_KEY_HERE
GEMINI_MODEL=gemini-1.5-pro
GEMINI_TEMPERATURE=0.3
GEMINI_MAX_TOKENS=1024

# Database
DATABASE_URL="mysql://symfony:symfony@database:3306/recruitment?serverVersion=8.0"

# CORS - URL du frontend React
CORS_ALLOW_ORIGIN=http://localhost:3000
```

**.dockerignore :**
```
.git
.gitignore
.env.local
.env.*.local
var/
vendor/
node_modules/
*.md
.docker/
docker-compose.yml
Makefile
```

**HttpClient configuration :**
```yaml
# config/packages/framework.yaml
framework:
    http_client:
        scoped_clients:
            gemini.client:
                base_uri: 'https://generativelanguage.googleapis.com/v1beta/'
                headers:
                    Content-Type: 'application/json'
```

## Exemple d'implémentation du Service

```php
class GeminiAnalysisService
{
    public function __construct(
        private HttpClientInterface $geminiClient,
        private string $geminiApiKey,
        private string $geminiModel
    ) {}

    public function analyzeCandidate(string $jobDescription, string $cv): array
    {
        $prompt = $this->buildPrompt($jobDescription, $cv);
        
        $response = $this->geminiClient->request('POST', 
            "models/{$this->geminiModel}:generateContent?key={$this->geminiApiKey}",
            [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 1024,
                        'responseMimeType' => 'application/json'
                    ]
                ],
                'timeout' => 60
            ]
        );

        $data = $response->toArray();
        
        // Extraire le JSON de la réponse Gemini
        $content = $data['candidates'][0]['content']['parts'][0]['text'];
        
        return json_decode($content, true);
    }
    
    private function buildPrompt(string $jobDesc, string $cv): string
    {
        return "Tu es un expert RH..."; // Prompt complet ici
    }
}
```

## Contraintes importantes

1. **Timeout :** La requête Gemini peut prendre 10-30 secondes
   - Configurer le timeout du HttpClient à 60 secondes
   - Ajouter un timeout côté frontend

2. **Coûts :** Gemini 1.5 Pro est plus économique qu'OpenAI
   - Logger chaque appel pour suivre l'utilisation
   - Limite de 1024 tokens de réponse

3. **Erreurs Gemini spécifiques :**
   - `400` - Requête invalide (JSON malformé)
   - `403` - API Key invalide
   - `429` - Rate limit dépassé
   - `500` - Erreur serveur Google
   - `SAFETY` - Contenu bloqué par les filtres de sécurité

4. **Sécurité :** 
   - Validation stricte des inputs (longueur max 10 000 caractères)
   - Rate limiting avec symfony/rate-limiter
   - CORS configuré uniquement pour le domaine frontend
   - Ne jamais logger la clé API ou les données sensibles

5. **Performance :**
   - Cache les analyses identiques (même CV + même poste) pendant 24h
   - Utiliser Symfony Cache Component

## Gestion des erreurs Gemini

```php
try {
    $response = $this->geminiClient->request(...);
} catch (HttpExceptionInterface $e) {
    $statusCode = $e->getResponse()->getStatusCode();
    
    match($statusCode) {
        403 => throw new GeminiException('Clé API Gemini invalide'),
        429 => throw new GeminiException('Rate limit Gemini dépassé'),
        default => throw new GeminiException('Erreur API Gemini: ' . $e->getMessage())
    };
}

// Vérifier si la réponse a été bloquée par les filtres de sécurité
if (isset($data['candidates'][0]['finishReason']) 
    && $data['candidates'][0]['finishReason'] === 'SAFETY') {
    throw new GeminiException('Contenu bloqué par les filtres de sécurité Gemini');
}
```

## Livrables attendus

### 1. Code Symfony
- **CandidateAnalysisController.php** - Controller API REST complet
- **GeminiAnalysisService.php** - Service d'intégration Gemini avec gestion d'erreurs complète
- **CandidateAnalysisRequest.php & Response.php** - DTOs avec validation
- **GeminiException.php** - Exception personnalisée
- **nelmio_cors.yaml** - Configuration CORS

### 2. Configuration Docker

**docker-compose.yml** avec les services :
```yaml
version: '3.8'

services:
  php:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
    container_name: recruitment_php
    volumes:
      - .:/var/www/html
    environment:
      - GEMINI_API_KEY=${GEMINI_API_KEY}
    depends_on:
      - database
    networks:
      - recruitment_network

  nginx:
    build:
      context: .
      dockerfile: docker/nginx/Dockerfile
    container_name: recruitment_nginx
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
    depends_on:
      - php
    networks:
      - recruitment_network

  database:
    image: mysql:8.0
    container_name: recruitment_db
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: recruitment
      MYSQL_USER: symfony
      MYSQL_PASSWORD: symfony
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql
    networks:
      - recruitment_network

networks:
  recruitment_network:
    driver: bridge

volumes:
  db_data:
```

**docker/php/Dockerfile :**
```dockerfile
FROM php:8.2-fpm

# Installation des extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copier les fichiers de l'application
COPY . .

# Installer les dépendances Symfony
RUN composer install --no-interaction --optimize-autoloader

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
```

**docker/nginx/Dockerfile :**
```dockerfile
FROM nginx:alpine

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/html
```

**docker/nginx/default.conf :**
```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass php:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

**Makefile (optionnel mais recommandé) :**
```makefile
.PHONY: help build up down logs shell composer test

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $1, $2}'

build: ## Construit les containers Docker
	docker-compose build

up: ## Démarre les containers
	docker-compose up -d

down: ## Arrête les containers
	docker-compose down

logs: ## Affiche les logs
	docker-compose logs -f

shell: ## Ouvre un shell dans le container PHP
	docker-compose exec php bash

composer: ## Installe les dépendances Composer
	docker-compose exec php composer install

test: ## Lance les tests
	docker-compose exec php php bin/phpunit

cache-clear: ## Vide le cache Symfony
	docker-compose exec php php bin/console cache:clear

migrate: ## Lance les migrations
	docker-compose exec php php bin/console doctrine:migrations:migrate

db-create: ## Crée la base de données
	docker-compose exec php php bin/console doctrine:database:create
```

**README.md** avec section Docker :
```markdown
# Système de Scoring de Candidatures - Backend Symfony

## 🐳 Installation avec Docker

### Prérequis
- Docker Desktop installé
- Docker Compose installé

### Démarrage rapide

1. Cloner le projet et se placer dans le dossier :
```bash
git clone <repo>
cd symfony-backend
```

2. Créer le fichier .env à partir de .env.example :
```bash
cp .env.example .env
```

3. Ajouter votre clé Gemini dans .env :
```env
GEMINI_API_KEY=AIzaSyxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

4. Construire et démarrer les containers :
```bash
docker-compose build
docker-compose up -d
```

5. Installer les dépendances Symfony :
```bash
docker-compose exec php composer install
```

6. Créer la base de données :
```bash
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:migrations:migrate
```

7. L'API est maintenant accessible sur http://localhost:8080

### Commandes utiles

```bash
# Voir les logs
docker-compose logs -f

# Accéder au shell PHP
docker-compose exec php bash

# Arrêter les containers
docker-compose down

# Reconstruire après modifications
docker-compose build --no-cache
docker-compose up -d

# Vider le cache Symfony
docker-compose exec php php bin/console cache:clear
```

### Tester l'API

```bash
curl -X POST http://localhost:8080/api/analyze-candidate \
  -H "Content-Type: application/json" \
  -d '{
    "jobDescription": "Senior QA Engineer avec Playwright...",
    "candidateCV": "Mohamed Ali, 9 ans d'\''expérience..."
  }'
```

### Avec Makefile (optionnel)

Si le Makefile est fourni :
```bash
make help          # Affiche toutes les commandes
make build         # Construit les containers
make up            # Démarre l'application
make logs          # Affiche les logs
make shell         # Ouvre un shell dans le container
make composer      # Installe les dépendances
```
```

### 3. Configuration et Documentation
- **.env.example** - Template avec GEMINI_API_KEY et variables Docker
- **.dockerignore** - Fichiers à exclure du build Docker
- **Exemple de prompt système** optimisé pour Gemini

## Fonctionnalités bonus souhaitées

1. **Command Symfony** pour tester l'API Gemini :
   ```bash
   php bin/console app:test-gemini "job description" "cv text"
   ```

2. **Rate Limiting** avec annotations :
   ```php
   #[RateLimit(limit: 10, period: 60)] // 10 requêtes par minute
   ```

3. **Logging** avec Monolog :
   - Logger chaque analyse (durée, tokens utilisés)
   - Logger les erreurs Gemini avec contexte

4. **Tests unitaires** :
   - Mock de l'API Gemini
   - Tests du service d'analyse
   - Tests du controller

## Points d'attention Gemini

- **Response format :** Gemini peut retourner le JSON dans `candidates[0].content.parts[0].text`
- **Safety filters :** Gemini peut bloquer certains contenus, vérifier `finishReason`
- **API Key :** Obtenir la clé sur https://makersuite.google.com/app/apikey
- **Modèles disponibles :** `gemini-pro`, `gemini-1.5-pro`, `gemini-1.5-flash`
- **Prix :** Gratuit jusqu'à 60 requêtes/minute, puis payant
- **responseMimeType :** Utiliser `application/json` pour forcer le format JSON

## Points d'attention Symfony

- Utiliser les **Services** avec autowiring et autoconfigure
- **Injection de dépendances** via le constructeur
- **Environment variables** pour tous les secrets
- **Serializer** pour la transformation JSON
- **Validator** pour la validation des inputs
- **EventListener** pour la gestion centralisée des erreurs
- Respecter **PSR-12** pour le code style

## Points d'attention Docker

- **Multi-stage build** si possible pour réduire la taille des images
- **Variables d'environnement** passées via docker-compose.yml
- **Volumes** pour le développement (hot reload)
- **Networks** isolés pour la sécurité
- **Health checks** pour les services (optionnel)
- **Logs** accessibles via `docker-compose logs`
- **.dockerignore** pour exclure les fichiers inutiles

## Architecture complète

```
┌─────────────────┐
│   Frontend      │
│   React App     │
│   (Port 3000)   │
└────────┬────────┘
         │ HTTP
         │ POST /api/analyze-candidate
         ▼
┌─────────────────┐
│   Nginx         │
│   (Port 8080)   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐      ┌─────────────────┐
│   PHP-FPM       │──────│   MySQL         │
│   Symfony       │      │   (Port 3306)   │
│   + Gemini API  │      └─────────────────┘
└────────┬────────┘
         │
         │ HTTPS
         ▼
┌─────────────────┐
│  Google Gemini  │
│  API Cloud      │
└─────────────────┘
```

---

**Note importante :** Je veux un code Symfony production-ready utilisant Google Gemini API, suivant les best practices du framework, avec gestion d'erreurs complète spécifique à Gemini, logging, cache, et commentaires en français.