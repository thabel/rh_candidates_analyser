# 🚀 Guide de démarrage rapide - RH Analyser

## 5 minutes pour avoir l'app en fonctionnement !

### Étape 1️⃣ : Préparer la clé API Gemini

```bash
# 1. Aller sur https://makersuite.google.com/app/apikey
# 2. Cliquer sur "Create API Key"
# 3. Copier la clé (commence par AIzaSy...)
# 4. La garder pour l'étape 4
```

### Étape 2️⃣ : Cloner et accéder au projet

```bash
git clone <votre-repo>
cd rh_analyser
```

### Étape 3️⃣ : Créer le fichier .env

```bash
cp .env.example .env
```

### Étape 4️⃣ : Ajouter la clé API

Éditer le fichier `.env` et remplacer :

```env
# AVANT
GEMINI_API_KEY=

# APRÈS
GEMINI_API_KEY=AIzaSyxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### Étape 5️⃣ : Démarrer avec Docker

```bash
# Construire les images
docker-compose build

# Démarrer les services
docker-compose up -d

# Initialiser la base de données
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

### Étape 6️⃣ : Accéder à l'application

Ouvrir dans le navigateur :
👉 **http://localhost:8080**

---

## ✅ Vérifier que tout fonctionne

### Test 1 : Health Check

```bash
curl http://localhost:8080/api/health
```

Vous devez voir :
```json
{"status":"ok","service":"RH Analyser API"}
```

### Test 2 : Analyser une candidature

```bash
curl -X POST http://localhost:8080/api/analyze-candidate \
  -H "Content-Type: application/json" \
  -d '{
    "jobDescription": "Senior QA Engineer avec 5+ ans d'expérience en tests automatisés",
    "candidateCV": "Jean Dupont, 7 ans d'expérience QA chez Microsoft et Google"
  }'
```

Vous devez recevoir un JSON avec score, summary, positives et negatives.

### Test 3 : Interface Web

1. Aller à http://localhost:8080
2. Remplir les deux champs (job description et CV)
3. Cliquer sur "Analyser avec IA"
4. Voir les résultats s'afficher en 10-30 secondes

---

## 🛠️ Commandes utiles

```bash
# Voir l'état des services
docker-compose ps

# Voir les logs en direct
docker-compose logs -f

# Arrêter les services
docker-compose down

# Redémarrer
docker-compose restart

# Ouvrir un shell dans PHP
docker-compose exec php bash
```

---

## ⚠️ Problèmes courants

### ❌ "Erreur : Clé API invalide"
→ Vérifier que GEMINI_API_KEY dans .env commence par `AIzaSy`

### ❌ "Erreur : Impossible de connexion à database"
→ Vérifier que le container DB est sain :
```bash
docker-compose logs database
```

### ❌ "Erreur : Port 8080 déjà en utilisation"
→ Changer le port dans .env :
```env
NGINX_PORT=8081  # au lieu de 8080
```

### ❌ "L'interface ne charge pas"
→ Vérifier que Nginx est actif :
```bash
docker-compose logs nginx
```

---

## 🎯 Prochaines étapes

1. **Lire la doc complète** : [README.md](README.md)
2. **Configurer CORS** si frontend sur autre port
3. **Mettre en place les logs** pour monitoring
4. **Tester avec de vrais CVs** pour affiner le scoring

---

## 📊 Architecture rapide

```
Frontend (Tailwind CSS)
        ↓ HTTP POST
   Nginx (Port 8080)
        ↓ FastCGI
    PHP 8.2 Symfony 7
        ↓
    PostgreSQL (Database)
        ↓
  Google Gemini API (IA)
```

---

## 💡 Tips

- Les analyses identiques sont cachées **24h** → pas d'appel API répété
- Timeout: **60 secondes** maximum par analyse
- Chaque erreur Gemini est loggée pour débogage

---

## 📞 Besoin d'aide ?

```bash
# Afficher l'aide des commandes
make help

# Ou lancer cette commande pour voir toutes les commandes disponibles
docker-compose exec php php bin/console list
```

---

**Vous êtes prêt ! 🎉**

Lancez l'app et commencez à analyser des candidatures ! 🚀
