# Routes Documentation

## Public Routes (Accessible to Everyone)

### 1. Homepage - Candidate Application Form
- **Route:** `/`
- **Name:** `app_public_home`
- **Method:** `GET`
- **Description:** Displays the active job offer and the candidate application form
- **Template:** `templates/public/index.html.twig`

### 2. Submit Candidate Application (Web Form)
- **Route:** `/submit`
- **Name:** `app_submit_web`
- **Method:** `POST`
- **Description:** Process candidate application submission from the web form
- **Parameters:**
  - `firstName` (string): Candidate's first name
  - `lastName` (string): Candidate's last name
  - `email` (string): Email address (unique)
  - `cvText` (string): Full CV text
- **Validation:**
  - Email must be unique (no duplicate submissions)
  - CV text minimum 100 characters
  - All fields required

### 3. Check Application Status
- **Route:** `/check-status`
- **Name:** `app_check_status`
- **Method:** `GET` / `POST`
- **Description:** Allows candidates to check their application status by email
- **Template:** `templates/public/check_status.html.twig`
- **Shows:**
  - Application status (pending, analyzing, analyzed)
  - AI score (if analyzed)
  - Feedback (strengths and weaknesses)

---

## Admin Routes (Authentication Required)

### 1. Admin Login
- **Route:** `/admin/login`
- **Name:** `admin_login`
- **Method:** `GET` / `POST`
- **Description:** Admin authentication page
- **Template:** `templates/admin/login.html.twig`
- **Default Credentials:**
  - Username: `admin`
  - Password: `admin123`

### 2. Admin Dashboard
- **Route:** `/admin/dashboard`
- **Name:** `admin_dashboard`
- **Method:** `GET`
- **Description:** Main admin panel showing all candidates and statistics
- **Template:** `templates/admin/dashboard.html.twig`
- **Authentication:** Required
- **Shows:**
  - Total candidates count
  - Pending candidates count
  - Analyzed candidates count
  - Table of all candidates with status and score

### 3. View Candidate Details
- **Route:** `/admin/candidate/{id}`
- **Name:** `admin_view_candidate`
- **Method:** `GET`
- **Description:** View detailed information about a specific candidate
- **Template:** `templates/admin/view_candidate.html.twig`
- **Authentication:** Required
- **Parameters:**
  - `id` (UUID): Candidate ID
- **Shows:**
  - Candidate information (name, email, submission date)
  - Full CV text
  - Current analysis status
  - Analysis results (if available)

### 4. Analyze Candidate (Web Form)
- **Route:** `/admin/candidate/{id}/analyze`
- **Name:** `admin_analyze_candidate_web`
- **Method:** `POST`
- **Description:** Trigger AI analysis using Google Gemini
- **Authentication:** Required
- **Parameters:**
  - `id` (UUID): Candidate ID
  - `jobDescription` (string): Job description to analyze against
- **Process:**
  1. Changes candidate status to "analyzing"
  2. Sends CV + job description to Google Gemini API
  3. Stores analysis result and score
  4. Updates candidate status to "analyzed"

### 5. Delete Candidate
- **Route:** `/admin/candidate/{id}/delete`
- **Name:** `admin_delete_candidate`
- **Method:** `POST`
- **Description:** Remove a candidate from the system
- **Authentication:** Required
- **Parameters:**
  - `id` (UUID): Candidate ID

### 6. Admin Logout
- **Route:** `/admin/logout`
- **Name:** `admin_logout`
- **Method:** `GET`
- **Description:** Destroy admin session and return to homepage
- **Authentication:** Required

---

## REST API Routes (For Programmatic Access)

### Client API Endpoints

#### Submit Candidate (API)
- **Endpoint:** `POST /api/submit-candidate`
- **Name:** `api_submit_candidate`
- **Description:** JSON API for candidate submission
- **Content-Type:** `application/json`
- **Request Body:**
  ```json
  {
    "firstName": "string",
    "lastName": "string",
    "email": "email",
    "cvText": "string",
    "cvFileName": "string (optional)"
  }
  ```
- **Response:** `201 Created`

#### Get Candidate Status (API)
- **Endpoint:** `GET /api/candidate/{id}`
- **Name:** `api_get_candidate`
- **Description:** Check candidate application status
- **Response:** `200 OK`

---

### Admin API Endpoints

#### List All Candidates (API)
- **Endpoint:** `GET /api/admin/candidates`
- **Name:** `admin_list_candidates`
- **Description:** Get all candidates in JSON format
- **Response:** `200 OK`

#### List Pending Candidates (API)
- **Endpoint:** `GET /api/admin/candidates-pending`
- **Name:** `admin_list_pending`
- **Description:** Get only pending (not yet analyzed) candidates
- **Response:** `200 OK`

#### Get Candidate Details (API)
- **Endpoint:** `GET /api/admin/candidate/{id}`
- **Name:** `admin_get_candidate_detail`
- **Description:** Get full candidate information
- **Response:** `200 OK`

#### Analyze Candidate (API)
- **Endpoint:** `POST /api/admin/candidate/{id}/analyze`
- **Name:** `admin_analyze_candidate`
- **Description:** Trigger analysis with job description
- **Content-Type:** `application/json`
- **Request Body:**
  ```json
  {
    "jobDescription": "string"
  }
  ```
- **Response:** `200 OK`

#### Delete Candidate (API)
- **Endpoint:** `DELETE /api/admin/candidate/{id}`
- **Name:** `admin_delete_candidate_api`
- **Description:** Remove candidate via API
- **Response:** `200 OK`

---

## Health Check

#### Health Check Endpoint
- **Endpoint:** `GET /api/health`
- **Name:** `api_health`
- **Description:** Service status check
- **Response:** `{"status": "ok", "service": "RH Analyser API"}`

---

## URL Generation Examples

### In Twig Templates

```twig
{# Public routes #}
{{ path('app_public_home') }}           {# / #}
{{ path('app_submit_web') }}            {# /submit #}
{{ path('app_check_status') }}          {# /check-status #}

{# Admin routes #}
{{ path('admin_login') }}               {# /admin/login #}
{{ path('admin_dashboard') }}           {# /admin/dashboard #}
{{ path('admin_view_candidate', {id: candidate.id}) }} {# /admin/candidate/{id} #}
{{ path('admin_logout') }}              {# /admin/logout #}
```

### In PHP Controllers

```php
// Redirect to homepage
return $this->redirectToRoute('app_public_home');

// Generate URL
$url = $this->generateUrl('app_public_home');

// Redirect with parameters
return $this->redirectToRoute('admin_view_candidate', [
    'id' => $candidateId
]);
```

---

## Security

### Session-Based Authentication
- Uses PHP sessions to store admin login state
- Session keys:
  - `admin_id`: Admin user ID
  - `admin_username`: Admin username

### Authentication Check
```php
private function checkAuth(): ?Response
{
    if (!$this->authService->isLoggedIn()) {
        return $this->redirectToRoute('admin_login');
    }
    return null;
}
```

---

## Error Handling

### HTTP Status Codes

| Code | Scenario |
|------|----------|
| 200 | Successful GET request |
| 201 | Resource created |
| 400 | Bad request (validation error) |
| 404 | Not found |
| 409 | Conflict (duplicate email) |
| 502 | Gemini API error |
| 500 | Server error |

### Flash Messages

Routes use Symfony flash messages for user feedback:
```php
$this->addFlash('success', 'Message');  // Green message
$this->addFlash('error', 'Message');    // Red message
$this->addFlash('warning', 'Message');  // Yellow message
```

---

## Summary

### Public Web Interface
- `/` - View job and submit application
- `/check-status` - Track application status

### Admin Web Interface
- `/admin/login` - Authenticate
- `/admin/dashboard` - View all candidates
- `/admin/candidate/{id}` - View details and analyze

### REST API (Optional)
- All endpoints also available via JSON API

---

**Note:** Authentication is session-based and not implemented as middleware. The `checkAuth()` method must be called manually in each route handler.

