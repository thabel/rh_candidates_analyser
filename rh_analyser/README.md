# RH Analyser - Système de Scoring de Candidatures par IA

<div align="center">

🤖 **Application intelligente pour l'analyse automatique de candidatures**

Utilise **Google Gemini AI** pour fournir un scoring détaillé et une analyse objective des candidats.

[![Symfony](https://img.shields.io/badge/Symfony-7.3-000000?style=flat-square&logo=symfony)](https://symfony.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat-square&logo=php)](https://php.net)
[![Docker](https://img.shields.io/badge/Docker-Containerized-2496ED?style=flat-square&logo=docker)](https://docker.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](LICENSE)

</div>

## 📋 Table des matières

- [Caractéristiques](#caractéristiques)
- [Prérequis](#prérequis)
- [Installation rapide](#installation-rapide)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [Architecture](#architecture)
- [API Endpoints](#api-endpoints)
- [Dépannage](#dépannage)
- [Développement](#développement)

## ✨ Caractéristiques

### Frontend
- 🎨 **Interface moderne et réactive** avec Tailwind CSS
- 📊 **Visualisation interactive du score** avec animation
- 💾 **Stockage local persistant** des résultats
- 📥 **Export en PDF/TXT** des analyses
- 🌍 **Support multilingue** (FR, EN)
- ♿ **Accessible** (WCAG 2.1 AA)

### Backend
- ⚡ **API REST rapide** avec Symfony 7
- 🔒 **Validation stricte** des données
- 💾 **Cache distribué** 24h des analyses identiques
- 🛡️ **Gestion des erreurs** robuste et spécifique à Gemini
- 📝 **Logging complet** de toutes les requêtes
- 🔄 **Rate limiting** avec annotations personnalisées

### Intégration Google Gemini
- 🧠 **Modèle gemini-1.5-pro** pour analyse fine
- 📍 **Scoring basé sur critères** (compétences, expérience, formation, soft skills)
- 🚀 **Réponses JSON structurées** garanties
- ⏱️ **Timeout de 60 secondes** pour les analyses longues
- 🔐 **Sécurité** : API key stockée en variable d'environnement

## 📦 Prérequis

### Minimale (développement)
- **PHP 8.2+**
- **Composer**
- **Node.js 16+** (pour asset compilation)
- **PostgreSQL 16+**

### Docker (recommandé pour la production)
- **Docker 20.10+**
- **Docker Compose 1.29+**

### Configuration
- **Clé API Google Gemini** (obtenir sur https://makersuite.google.com/app/apikey)
- **Minimum 2GB de RAM** pour les containers Docker

## 🚀 Installation rapide

### Option 1 : Avec Docker (Recommandé)

```bash
# 1. Cloner le projet
git clone <votre-repo>
cd rh_analyser

# 2. Copier la configuration
cp .env.example .env

# 3. Ajouter votre clé API Gemini
# Éditer .env et remplir GEMINI_API_KEY

# 4. Construire et démarrer
docker-compose build
docker-compose up -d

# 5. Initialiser la base de données
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:migrations:migrate

# 6. Accéder à l'application
# Frontend : http://localhost:8080
# API Health : http://localhost:8080/api/health
```

### Option 2 : Installation locale

```bash
# 1. Cloner et installer
git clone <votre-repo>
cd rh_analyser
composer install

# 2. Configuration
cp .env.example .env
# Éditer .env avec vos paramètres

# 3. Base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 4. Démarrer le serveur Symfony
php -S localhost:8000 -t public/

# Accès : http://localhost:8000
```

## ⚙️ Configuration

### Variables d'environnement essentielles

```env
# Application
APP_ENV=dev                    # dev ou prod
APP_SECRET=votre-secret        # Clé secrète Symfony

# Google Gemini API (IMPORTANT)
GEMINI_API_KEY=AIzaSy...       # Clé API Gemini
GEMINI_MODEL=gemini-1.5-pro    # Modèle à utiliser
GEMINI_TEMPERATURE=0.3         # Créativité (0.0 à 1.0)
GEMINI_MAX_TOKENS=1024         # Max tokens réponse

# Database
DATABASE_URL=postgresql://app:password@db:5432/app

# CORS (pour requests frontend)
CORS_ALLOW_ORIGIN=http://localhost:3000
```

### Obtenir la clé API Gemini

1. Visiter https://makersuite.google.com/app/apikey
2. S'authentifier avec un compte Google
3. Cliquer sur "Create API Key"
4. Copier la clé générée
5. La coller dans la variable `GEMINI_API_KEY` du fichier `.env`

### Configuration CORS

Pour permettre les requêtes depuis votre frontend :

```yaml
# config/packages/nelmio_cors.yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']  # URL de votre frontend
        allow_methods: ['GET', 'OPTIONS', 'POST']
        allow_headers: ['Content-Type']
```

## 📖 Utilisation

### Interface Web

1. **Accéder à l'application** : http://localhost:8080
2. **Remplir la fiche de poste** : Décrire le poste et ses exigences
3. **Coller le CV** : Ajouter le CV complet du candidat
4. **Cliquer sur "Analyser"** : L'IA analyse en 10-30 secondes
5. **Consulter les résultats** :
   - Score global (0-100)
   - Résumé de l'analyse
   - Points positifs (4-5)
   - Points à améliorer (3)
6. **Exporter** : Télécharger le rapport en TXT

### Exemple de requête API

```bash
curl -X POST http://localhost:8080/api/analyze-candidate \
  -H "Content-Type: application/json" \
  -d '{
    "jobDescription": "Senior QA Engineer avec 5+ ans d'\''expérience en Playwright...",
    "candidateCV": "Mohamed Ali - QA Manager, 9 ans d'\''expérience chez Microsoft..."
  }'
```

### Réponse API

```json
{
  "score": 85,
  "summary": "Candidat excellent avec expérience solide et compétences très alignées.",
  "positives": [
    "Expertise approfondie en tests automatisés (Playwright, Cypress, Selenium)",
    "Expérience chez Microsoft et autres grandes tech",
    "Leadership : progression de junior à Senior QA Manager",
    "Maîtrise complète des outils (Jira, Azure, CI/CD)"
  ],
  "negatives": [
    "Expérience en gestion d'équipe pourrait être plus détaillée",
    "Certifications professionnelles non mentionnées",
    "Expérience cloud limitée aux Azure"
  ]
}
```

## 🏗️ Architecture

### Structure du projet

```
rh_analyser/
├── src/
│   ├── Controller/
│   │   └── CandidateAnalysisController.php    # API & Frontend
│   ├── Service/
│   │   └── GeminiAnalysisService.php          # Logique Gemini
│   ├── DTO/
│   │   └── CandidateAnalysisRequest.php       # Validation
│   ├── Exception/
│   │   └── GeminiException.php                # Erreurs spécifiques
│   └── Kernel.php
├── templates/
│   ├── base.html.twig                         # Layout principal
│   └── home.html.twig                         # Accueil + interface
├── config/
│   ├── packages/
│   │   ├── framework.yaml                     # HttpClient config
│   │   └── nelmio_cors.yaml                   # CORS
│   └── services.yaml                          # Dépendances
├── docker/
│   ├── php/
│   │   └── Dockerfile                         # PHP-FPM 8.2
│   └── nginx/
│       ├── Dockerfile                         # Nginx Alpine
│       └── default.conf                       # Configuration Nginx
├── compose.yaml                               # Orchestration Docker
├── .env                                       # Variables (ne pas commit)
├── .env.example                               # Template (à commit)
├── .dockerignore                              # Exclusions Docker
└── README.md                                  # Cette doc
```

### Diagramme architectural

```
┌─────────────────────────────────────────────────────────┐
│                   Browser / Frontend                    │
│              (Tailwind CSS + Vanilla JS)                │
└────────────────────────┬────────────────────────────────┘
                         │ HTTP/POST
                         ▼
         ┌───────────────────────────────┐
         │      Nginx (Alpine)           │
         │      Port 80 → 8080           │
         └────────────┬──────────────────┘
                      │ FastCGI
                      ▼
         ┌────────────────────────────────┐
         │      PHP-FPM 8.2              │
         │    ├── Controller             │
         │    ├── Service                │
         │    └── Validation             │
         └────────┬───────────────────────┘
                  │
        ┌─────────┴──────────┐
        │                    │
        ▼                    ▼
┌──────────────────┐  ┌──────────────────┐
│   PostgreSQL     │  │  Google Gemini   │
│   (Database)     │  │  API (Cloud)     │
│   Port 5432      │  │  HTTPS           │
└──────────────────┘  └──────────────────┘
```

## 📡 API Endpoints

### Analyse de candidature

```http
POST /api/analyze-candidate
Content-Type: application/json

Request:
{
  "jobDescription": "string (50-10000 chars)",
  "candidateCV": "string (50-10000 chars)"
}

Response (200 OK):
{
  "score": number,
  "summary": "string",
  "positives": ["string", ...],
  "negatives": ["string", ...]
}
```

**Erreurs possibles :**
- `400` : Requête invalide (champs manquants ou trop courts)
- `429` : Limite Gemini dépassée (rate limiting)
- `502` : Erreur API Gemini
- `503` : Service Gemini indisponible

### Health Check

```http
GET /api/health

Response (200 OK):
{
  "status": "ok",
  "service": "RH Analyser API"
}
```

## 🛠️ Commandes Docker utiles

```bash
# Démarrer les services
docker-compose up -d

# Arrêter les services
docker-compose down

# Voir les logs
docker-compose logs -f

# Logs d'un service spécifique
docker-compose logs -f php

# Exécuter une commande PHP
docker-compose exec php php bin/console <commande>

# Shell interactif dans le container PHP
docker-compose exec php bash

# Reconstruire les images
docker-compose build --no-cache

# Vider le cache Symfony
docker-compose exec php php bin/console cache:clear

# Migrer la base de données
docker-compose exec php php bin/console doctrine:migrations:migrate

# Créer un nouvel user admin
docker-compose exec php php bin/console app:create-user admin@example.com password
```

## 📊 Monitoring et Logs

### Logs Symfony

```bash
# Logs en real-time
tail -f var/log/dev.log

# Logs Docker
docker-compose logs -f php

# Logs Nginx
docker-compose logs -f nginx
```

### Métriques d'utilisation Gemini

Chaque analyse est loggée avec :
- Timestamp
- Longueur du CV et fiche de poste
- Score final obtenu
- Durée de traitement
- Erreurs éventuelles

## 🐛 Dépannage

### Erreur : "GEMINI_API_KEY invalide"

```
Solution :
1. Vérifier que la clé est présente dans .env
2. Vérifier que la clé commence par "AIzaSy"
3. Vérifier que la clé est activée sur https://makersuite.google.com
4. Régénérer une nouvelle clé si nécessaire
```

### Erreur : "Rate limit Gemini dépassé"

```
Symptôme : HTTP 429
Solution :
1. Attendre 60 secondes avant de relancer
2. Les analyses identiques sont cachées 24h (pas de décompte)
3. Implémenter un système de queue pour les analyses massives
```

### Erreur : "Connexion database échouée"

```bash
# Vérifier que le container DB est sain
docker-compose exec database pg_isready

# Vérifier les logs
docker-compose logs database

# Redémarrer le service
docker-compose restart database
```

### Interface ne charge pas

```bash
# Vérifier que Nginx est actif
docker-compose exec nginx ps aux | grep nginx

# Vérifier les logs Nginx
docker-compose logs nginx

# Vérifier le PORT configuré (par défaut 8080)
netstat -an | grep 8080
```

### Performance lente

```bash
# Vérifier les ressources
docker stats

# Augmenter la RAM allouée à Docker (dans settings)
# Minimum recommandé : 4GB

# Vérifier la cache Symfony
docker-compose exec php php bin/console cache:clear

# Activer le profiler en dev
APP_ENV=dev dans .env
```

## 👨‍💻 Développement

### Prérequis dev

```bash
# Installer les dépendances
composer install

# Installation du frontend assets (si modifié)
php bin/console asset-map:compile

# Lancer les tests
php bin/phpunit

# Vérifier la qualité du code
php vendor/bin/phpstan analyze src/
php vendor/bin/php-cs-fixer fix src/
```

### Tests unitaires

```bash
# Tous les tests
php bin/phpunit

# Tests spécifiques
php bin/phpunit tests/Service/GeminiAnalysisServiceTest.php

# Avec couverture de code
php bin/phpunit --coverage-html=coverage/
```

### Structure des tests

```
tests/
├── Service/
│   └── GeminiAnalysisServiceTest.php
├── Controller/
│   └── CandidateAnalysisControllerTest.php
└── DTO/
    └── CandidateAnalysisRequestTest.php
```

## 🔒 Sécurité

### Bonnes pratiques implémentées

✅ **Validation stricte** des inputs (50-10000 caractères)
✅ **Sanitization** des données avant envoi à Gemini
✅ **API Key** en variable d'environnement (jamais en dur)
✅ **CORS configuré** uniquement pour domaines autorisés
✅ **Rate limiting** (10 requêtes/minute par défaut)
✅ **Logging** sans données sensibles
✅ **HTTPS ready** (configuration SSL/TLS)
✅ **Timeouts** pour éviter les requêtes zombie

### Recommandations production

```env
# 1. Secrets sécurisés
APP_SECRET=<clé aléatoire 32+ caractères>
GEMINI_API_KEY=<clé obtenue depuis Google>

# 2. CORS restrictif
CORS_ALLOW_ORIGIN='^https://mondomaine\.com$'

# 3. Logs sécurisés
APP_ENV=prod
# Logs stockés en dehors du web root

# 4. Database
# Utiliser un user DB avec droits limités
# Mot de passe fort (minimum 16 caractères)

# 5. Firewall
# Limiter l'accès SSH
# Activer WAF (Web Application Firewall)
```

## 📝 Licence

MIT License - Voir le fichier [LICENSE](LICENSE)

## 🤝 Contribution

Les contributions sont les bienvenues ! Pour participer :

1. Fork le projet
2. Créer une branche (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📧 Support

Pour toute question ou problème :
- 📝 Ouvrir une Issue
- 💬 Contacter l'équipe
- 🐛 Signaler un bug

## 🙏 Remerciements

- Google Gemini pour le moteur d'IA
- Symfony pour le framework solide
- La communauté open source

---

**Dernière mise à jour :** Novembre 2024
**Version :** 1.0.0
**Statut :** Production Ready ✅
