# API Documentation - RH Candidate Scoring System

## Overview

This API provides two main sections:
1. **Client API** - For candidates to submit their applications
2. **Admin API** - For HR team to manage and analyze applications

---

## BASE URL

```
http://localhost:8080/api
```

---

## CLIENT API

### 1. Submit Candidate Application

**Endpoint:** `POST /api/submit-candidate`

**Description:** Allows a candidate to submit their application with personal information and CV.

**Request Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "firstName": "string (required, 2-255 chars)",
  "lastName": "string (required, 2-255 chars)",
  "email": "string (required, valid email)",
  "cvText": "string (required, 100-50000 chars)",
  "cvFileName": "string (optional, default: 'cv.pdf')"
}
```

**Example Request:**
```bash
curl -X POST http://localhost:8080/api/submit-candidate \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "Mohamed",
    "lastName": "Ali Bannour",
    "email": "mohamed.ali@example.com",
    "cvText": "# Mohamed Ali Bannour\n\nSenior QA Manager with 9+ years...",
    "cvFileName": "cv_mohammed.pdf"
  }'
```

**Success Response (201 Created):**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "firstName": "Mohamed",
  "lastName": "Ali Bannour",
  "email": "mohamed.ali@example.com",
  "status": "pending",
  "message": "Candidature soumise avec succès",
  "submittedAt": "2025-11-01 10:30:45"
}
```

**Error Responses:**

- **400 Bad Request** - Invalid JSON or validation errors
```json
{
  "message": "Erreurs de validation",
  "errors": [
    "firstName: Le prénom doit contenir au moins 2 caractères"
  ]
}
```

- **409 Conflict** - Email already exists
```json
{
  "message": "Un candidat avec cet email existe déjà"
}
```

- **500 Internal Server Error**
```json
{
  "message": "Erreur serveur: [error details]"
}
```

---

### 2. Get Candidate Application Status

**Endpoint:** `GET /api/candidate/{id}`

**Description:** Retrieve the status and analysis results of a submitted application.

**Path Parameters:**
- `id` (string, UUID) - Candidate ID from submission response

**Example Request:**
```bash
curl -X GET http://localhost:8080/api/candidate/550e8400-e29b-41d4-a716-446655440000
```

**Success Response (200 OK):**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "firstName": "Mohamed",
  "lastName": "Ali Bannour",
  "email": "mohamed.ali@example.com",
  "status": "analyzed",
  "submittedAt": "2025-11-01 10:30:45",
  "score": 88,
  "analysis": {
    "score": 88,
    "summary": "Excellent candidate with extensive QA experience...",
    "positives": [
      "9+ years of QA experience",
      "Expert in test automation frameworks"
    ],
    "negatives": [
      "Limited security testing background"
    ]
  },
  "analyzedAt": "2025-11-01 10:35:20"
}
```

**Status values:**
- `pending` - Waiting to be analyzed
- `analyzing` - Currently being analyzed
- `analyzed` - Analysis complete

**Error Response:**

- **404 Not Found**
```json
{
  "message": "Candidature non trouvée"
}
```

---

## ADMIN API

All Admin endpoints are prefixed with `/api/admin/`

### 1. List All Candidates

**Endpoint:** `GET /api/admin/candidates`

**Description:** Retrieve all candidates with their current status.

**Query Parameters:** None

**Example Request:**
```bash
curl -X GET http://localhost:8080/api/admin/candidates
```

**Success Response (200 OK):**
```json
{
  "total": 3,
  "candidates": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "firstName": "Mohamed",
      "lastName": "Ali Bannour",
      "email": "mohamed.ali@example.com",
      "status": "analyzed",
      "score": 88,
      "submittedAt": "2025-11-01 10:30:45",
      "analyzedAt": "2025-11-01 10:35:20"
    },
    {
      "id": "550e8400-e29b-41d4-a716-446655440001",
      "firstName": "Jane",
      "lastName": "Smith",
      "email": "jane.smith@example.com",
      "status": "pending",
      "score": null,
      "submittedAt": "2025-11-01 11:00:00",
      "analyzedAt": null
    }
  ]
}
```

---

### 2. List Pending Candidates

**Endpoint:** `GET /api/admin/candidates-pending`

**Description:** Retrieve only candidates awaiting analysis.

**Example Request:**
```bash
curl -X GET http://localhost:8080/api/admin/candidates-pending
```

**Success Response (200 OK):**
```json
{
  "total": 1,
  "candidates": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440001",
      "firstName": "Jane",
      "lastName": "Smith",
      "email": "jane.smith@example.com",
      "submittedAt": "2025-11-01 11:00:00"
    }
  ]
}
```

---

### 3. Get Candidate Details

**Endpoint:** `GET /api/admin/candidate/{id}`

**Description:** Retrieve full details of a specific candidate including their CV.

**Path Parameters:**
- `id` (string, UUID) - Candidate ID

**Example Request:**
```bash
curl -X GET http://localhost:8080/api/admin/candidate/550e8400-e29b-41d4-a716-446655440000
```

**Success Response (200 OK):**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "firstName": "Mohamed",
  "lastName": "Ali Bannour",
  "email": "mohamed.ali@example.com",
  "cvText": "# Mohamed Ali Bannour\n\nSenior QA Manager...",
  "cvFileName": "cv_mohammed.pdf",
  "status": "analyzed",
  "score": 88,
  "analysis": {
    "score": 88,
    "summary": "Excellent candidate...",
    "positives": ["..."],
    "negatives": ["..."]
  },
  "submittedAt": "2025-11-01 10:30:45",
  "analyzedAt": "2025-11-01 10:35:20"
}
```

**Error Response:**

- **404 Not Found**
```json
{
  "message": "Candidature non trouvée"
}
```

---

### 4. Analyze Candidate with Job Description

**Endpoint:** `POST /api/admin/candidate/{id}/analyze`

**Description:** Trigger analysis of a candidate against a job description using Google Gemini AI.

**Path Parameters:**
- `id` (string, UUID) - Candidate ID

**Request Body:**
```json
{
  "jobDescription": "string (required, min 50 chars)"
}
```

**Example Request:**
```bash
curl -X POST http://localhost:8080/api/admin/candidate/550e8400-e29b-41d4-a716-446655440001/analyze \
  -H "Content-Type: application/json" \
  -d '{
    "jobDescription": "We are looking for a Senior QA Manager with 7+ years of test automation experience, expertise in Playwright and Cypress, cloud platforms knowledge, and proven team leadership skills."
  }'
```

**Success Response (200 OK):**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440001",
  "firstName": "Jane",
  "lastName": "Smith",
  "email": "jane.smith@example.com",
  "status": "analyzed",
  "score": 75,
  "analysis": {
    "score": 75,
    "summary": "Good candidate with relevant test automation experience and some leadership background.",
    "positives": [
      "7+ years of QA experience matching the requirements",
      "Strong knowledge of Playwright and Cypress frameworks",
      "Experience with cloud platforms (AWS, Azure)",
      "Some team leadership experience"
    ],
    "negatives": [
      "Limited experience with advanced performance testing",
      "Certifications could be more current"
    ]
  },
  "analyzedAt": "2025-11-01 10:40:15",
  "message": "Analyse réussie"
}
```

**Error Responses:**

- **400 Bad Request** - Missing job description
```json
{
  "message": "La fiche de poste est obligatoire"
}
```

- **400 Bad Request** - Job description too short
```json
{
  "message": "La fiche de poste doit contenir au moins 50 caractères"
}
```

- **404 Not Found** - Candidate not found
```json
{
  "message": "Candidature non trouvée"
}
```

- **502 Bad Gateway** - Gemini API error
```json
{
  "message": "Erreur lors de l'analyse IA: [error details]"
}
```

---

### 5. Delete Candidate

**Endpoint:** `DELETE /api/admin/candidate/{id}`

**Description:** Delete a candidate and their application from the system.

**Path Parameters:**
- `id` (string, UUID) - Candidate ID

**Example Request:**
```bash
curl -X DELETE http://localhost:8080/api/admin/candidate/550e8400-e29b-41d4-a716-446655440000
```

**Success Response (200 OK):**
```json
{
  "message": "Candidature supprimée"
}
```

**Error Response:**

- **404 Not Found**
```json
{
  "message": "Candidature non trouvée"
}
```

---

## Data Models

### Candidate Entity

| Field | Type | Description |
|-------|------|-------------|
| id | UUID | Unique identifier (auto-generated) |
| firstName | String(255) | Candidate's first name |
| lastName | String(255) | Candidate's last name |
| email | String(255) | Email address (unique) |
| cvText | Text | Full CV content |
| cvFileName | String(255) | Original filename |
| status | String | pending \| analyzing \| analyzed |
| score | Integer | AI score (0-100), null if not analyzed |
| analysisResult | JSON | Full analysis from Gemini API |
| submittedAt | DateTime | Submission timestamp |
| analyzedAt | DateTime | Analysis completion timestamp (null if pending) |

### Analysis Result Structure

```json
{
  "score": 85,
  "summary": "Brief summary of the candidate's fit",
  "positives": [
    "Strength 1",
    "Strength 2",
    "Strength 3"
  ],
  "negatives": [
    "Weakness 1",
    "Weakness 2"
  ]
}
```

---

## Database Setup

### Migration

Run the migration to create the `candidates` table:

```bash
docker-compose exec php php bin/console doctrine:migrations:migrate
```

### Manual Setup (if needed)

```sql
CREATE TABLE candidates (
    id CHAR(36) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    cv_text LONGTEXT NOT NULL,
    cv_file_name VARCHAR(255),
    analysis_result JSON,
    score INT,
    submitted_at DATETIME NOT NULL,
    analyzed_at DATETIME,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    PRIMARY KEY(id),
    INDEX idx_status (status),
    INDEX idx_submitted_at (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Testing

### Using cURL

See the `tests/candidate-test.md` file for complete testing examples.

### Using Postman

1. Import the endpoints into Postman
2. Use the examples provided in each section
3. Set `Content-Type: application/json` for POST requests

### Using JavaScript/Fetch

```javascript
// Submit candidate
fetch('http://localhost:8080/api/submit-candidate', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    firstName: 'Mohamed',
    lastName: 'Ali Bannour',
    email: 'test@example.com',
    cvText: 'Full CV content here...'
  })
})
.then(r => r.json())
.then(data => console.log(data));

// Analyze candidate
fetch('http://localhost:8080/api/admin/candidate/{id}/analyze', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    jobDescription: 'Job requirements here...'
  })
})
.then(r => r.json())
.then(data => console.log(data));
```

---

## Error Handling

### Common HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request (validation error) |
| 404 | Not Found |
| 409 | Conflict (duplicate email) |
| 502 | Bad Gateway (AI service error) |
| 500 | Internal Server Error |

### Error Response Format

```json
{
  "message": "Error description",
  "errors": ["field error 1", "field error 2"] // Only in 400 responses
}
```

---

## Validation Rules

### First Name
- Required
- Minimum 2 characters
- Maximum 255 characters

### Last Name
- Required
- Minimum 2 characters
- Maximum 255 characters

### Email
- Required
- Must be valid email format
- Must be unique (not already in system)

### CV Text
- Required
- Minimum 100 characters
- Maximum 50,000 characters

### Job Description (for analysis)
- Required
- Minimum 50 characters

---

## Scoring Criteria (Gemini AI)

The analysis score (0-100) is based on:

1. **Technical Skills Match (40%)** - Does the CV show the required technical skills?
2. **Relevant Experience (30%)** - Does the CV show relevant work experience?
3. **Education & Certifications (15%)** - Are qualifications adequate?
4. **Soft Skills & Career Growth (15%)** - Are soft skills and progression evident?

---

## Performance Notes

- Analysis requests typically take 10-30 seconds (Gemini API latency)
- Set HTTP client timeout to at least 60 seconds
- Consider implementing request queuing for high volume
- Database indexes on `status` and `submitted_at` for efficient queries

---

## Security Notes

- Email is unique to prevent duplicate submissions
- CV content is stored securely in the database
- All inputs are validated before processing
- Gemini API credentials are stored in environment variables (never in code)
- Log sensitive data appropriately (never log API keys or personal data)

---

## Support

For issues or questions, refer to:
- `tests/candidate-test.md` - Complete test scenarios
- Application logs in `var/log/`
- Docker logs: `docker-compose logs -f`

