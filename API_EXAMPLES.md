# API Examples - Candidate Scoring Endpoint

## Overview

Your backend will provide two endpoints after running `setup.sh`:

- `GET /api/health` - Health check
- `POST /api/analyze-candidate` - Analyze CV against job description

---

## 1. Health Check Endpoint

### Request
```bash
curl -X GET http://localhost:8000/api/health
```

### Response (200 OK)
```json
{
  "status": "ok"
}
```

---

## 2. Analyze Candidate Endpoint

### Request
```bash
curl -X POST http://localhost:8000/api/analyze-candidate \
  -H "Content-Type: application/json" \
  -d '{
    "jobDescription": "...",
    "candidateCV": "..."
  }'
```

### Required Fields

1. **jobDescription** (string, 50-10000 characters)
   - Job description with requirements
   - Skills, experience, and qualifications

2. **candidateCV** (string, 50-10000 characters)
   - Candidate's CV/resume
   - Experience, education, skills

---

## Example 1: PHP Developer

### Request
```bash
curl -X POST http://localhost:8000/api/analyze-candidate \
  -H "Content-Type: application/json" \
  -d '{
    "jobDescription": "We are hiring a Senior PHP Developer. Requirements: 5+ years of PHP experience, 3+ years with Symfony framework, REST API development experience, MySQL/PostgreSQL knowledge, Docker containerization experience. Nice to have: GraphQL, Redis, AWS.",
    "candidateCV": "JANE SMITH - Senior PHP Developer\n\nSUMMARY:\nExperienced PHP developer with 8 years in web development.\n\nEXPERIENCE:\nSenior PHP Developer at TechCorp (Jan 2020 - Present)\n- Developed 15+ REST APIs using Symfony 6.x\n- Managed databases: MySQL, PostgreSQL\n- Docker containerization of 10+ applications\n- Mentored 3 junior developers\n\nPHP Developer at WebDev Inc (Mar 2016 - Dec 2019)\n- 4 years of Symfony development (4.x and 5.x)\n- Built customer management systems\n- Performance optimization\n\nEDUCATION:\nBS Computer Science - State University (2015)\nCertifications: AWS Solutions Architect Associate (2021)\n\nSKILLS:\nPHP 8.0+, Symfony, REST APIs, MySQL, PostgreSQL, Docker, Linux, Git, Redis caching"
  }'
```

### Response (200 OK)
```json
{
  "score": 88,
  "summary": "Excellent match with extensive Symfony experience and all required technical skills. Exceeds minimum experience requirements significantly.",
  "positives": [
    "8 years PHP experience exceeds requirement (5+ required)",
    "Strong Symfony expertise with versions 4.x, 5.x, and 6.x",
    "Proven REST API development with 15+ APIs built",
    "Database knowledge includes both MySQL and PostgreSQL",
    "Docker containerization experience demonstrated",
    "AWS certification shows cloud knowledge (nice-to-have)",
    "Leadership experience mentoring junior developers"
  ],
  "negatives": [
    "No GraphQL experience mentioned (nice-to-have)",
    "Limited Redis caching details despite listing it"
  ]
}
```

---

## Example 2: QA Engineer

### Request
```bash
curl -X POST http://localhost:8000/api/analyze-candidate \
  -H "Content-Type: application/json" \
  -d '{
    "jobDescription": "QA Automation Engineer - 5+ years required. Must have: Selenium expertise, JavaScript/Python programming, Test frameworks (Jest, PyTest), CI/CD pipeline knowledge, Agile experience. Nice to have: Mobile testing, Performance testing, Docker.",
    "candidateCV": "JOHN MARTINEZ - QA Engineer\n\nEXPERIENCE:\nQA Automation Engineer at SoftwareCorp (2021-2024)\n- 3 years automation testing with Selenium WebDriver\n- JavaScript for test automation\n- 200+ automated test cases\n\nQA Tester at TestHub (2018-2021)\n- Manual testing for 3 years\n- No automation experience\n\nEDUCATION:\nBachelor in Information Technology (2017)\n\nTECHNICAL SKILLS:\nSelenium, JavaScript, TestNG framework"
  }'
```

### Response (200 OK)
```json
{
  "score": 62,
  "summary": "Candidate has some relevant skills but lacks the required years of experience. Has 3 years automation experience but needs 5+.",
  "positives": [
    "Selenium WebDriver experience demonstrated",
    "JavaScript programming skills present",
    "200+ automated test cases shows practical experience",
    "Familiar with test frameworks (TestNG)"
  ],
  "negatives": [
    "Only 3 years automation experience vs 5+ required",
    "No Python programming mentioned (alternative to JavaScript acceptable)",
    "No CI/CD pipeline experience mentioned",
    "No mobile testing experience",
    "Agile experience not documented",
    "No performance testing mentioned"
  ]
}
```

---

## Example 3: DevOps Engineer

### Request
```bash
curl -X POST http://localhost:8000/api/analyze-candidate \
  -H "Content-Type: application/json" \
  -d '{
    "jobDescription": "DevOps Engineer - Looking for someone with: 6+ years infrastructure experience, Kubernetes administration, Docker mastery, AWS/GCP experience, Infrastructure as Code (Terraform), CI/CD pipelines, Linux system administration.",
    "candidateCV": "ALEX KUMAR - DevOps Specialist\n\nPROFESSIONAL SUMMARY:\nDevOps engineer with 7 years of experience in cloud infrastructure and containerization.\n\nWORK EXPERIENCE:\nSenior DevOps Engineer at CloudServices Inc (2021-2024) - 3 years\n- Kubernetes cluster administration and optimization\n- 50+ Docker container deployments\n- Terraform infrastructure management\n- AWS services: EC2, RDS, S3, Lambda\n- Jenkins and GitLab CI/CD pipeline setup\n\nDevOps Engineer at InfrastructureCo (2018-2021) - 3 years\n- Container management with Docker\n- AWS infrastructure setup and maintenance\n- Linux system administration\n- Bash scripting for automation\n\nSystems Administrator at HostingPro (2017-2018) - 1 year\n- Linux servers management\n- Network configuration\n\nEDUCATION:\nBachelor in Computer Science (2017)\nCertifications: AWS Solutions Architect (2020), Kubernetes Administrator (2022)\n\nKEY SKILLS:\nKubernetes, Docker, Terraform, AWS, GCP, Linux, Bash, Jenkins, GitLab CI, Python"
  }'
```

### Response (200 OK)
```json
{
  "score": 92,
  "summary": "Outstanding match with 7 years relevant experience and expertise in all required technologies. Exceeds all expectations with senior-level skills.",
  "positives": [
    "7 years infrastructure experience exceeds 6+ requirement",
    "Advanced Kubernetes administration with optimization experience",
    "Extensive Docker experience with 50+ deployments",
    "Terraform Infrastructure as Code expertise demonstrated",
    "Comprehensive AWS knowledge across multiple services",
    "Strong CI/CD pipeline experience with Jenkins and GitLab",
    "Expert Linux system administration and Bash scripting",
    "Additional GCP experience beyond requirements",
    "Relevant certifications (AWS Architect, K8s Administrator)"
  ],
  "negatives": [
    "No specific mention of GCP depth (AWS-focused)"
  ]
}
```

---

## Error Responses

### 400 Bad Request - Validation Error
```bash
curl -X POST http://localhost:8000/api/analyze-candidate \
  -H "Content-Type: application/json" \
  -d '{
    "jobDescription": "Short",
    "candidateCV": ""
  }'
```

Response:
```json
{
  "error": "Validation failed",
  "details": {
    "jobDescription": "This value is too short. It should have 50 characters or more.",
    "candidateCV": "This value is not blank."
  }
}
```

### 502 Bad Gateway - Gemini API Error
```json
{
  "error": "Gemini API Error: HTTP 401"
}
```

Response when GEMINI_API_KEY is invalid or missing:
```json
{
  "error": "Gemini API Error: GEMINI_API_KEY environment variable is not set"
}
```

### 500 Internal Server Error
```json
{
  "error": "Internal server error",
  "message": "Database connection failed"
}
```

---

## Response Fields Explained

### score (integer 0-100)
- **0-20**: Poor fit, missing key requirements
- **21-40**: Below average, significant gaps
- **41-60**: Moderate fit, acceptable experience
- **61-80**: Good fit, meets requirements well
- **81-100**: Excellent fit, exceeds expectations

### summary (string)
2-3 sentence overview of the candidate's fit for the position.

### positives (array of strings)
3-5 concrete strengths with evidence from CV:
- Technical skills match
- Relevant experience
- Exceeding minimums
- Leadership/soft skills
- Certifications

### negatives (array of strings)
2-3 areas for improvement:
- Missing skills (nice-to-haves)
- Below requirement experience
- Gaps in expertise
- Missing certifications

---

## Using with Your React Frontend

### Fetch Example
```javascript
async function analyzeCandidate(jobDescription, candidateCV) {
  const response = await fetch('http://localhost:8000/api/analyze-candidate', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      jobDescription,
      candidateCV
    })
  });

  if (!response.ok) {
    const error = await response.json();
    console.error('Error:', error);
    return null;
  }

  return await response.json();
}

// Usage
const result = await analyzeCandidate(jobDesc, cv);
console.log(`Score: ${result.score}`);
console.log(`Summary: ${result.summary}`);
console.log('Positives:', result.positives);
console.log('Negatives:', result.negatives);
```

### React Component Example
```jsx
function CandidateAnalysis() {
  const [result, setResult] = useState(null);
  const [loading, setLoading] = useState(false);

  const handleAnalyze = async (jobDesc, cv) => {
    setLoading(true);
    try {
      const response = await fetch(
        'http://localhost:8000/api/analyze-candidate',
        {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            jobDescription: jobDesc,
            candidateCV: cv
          })
        }
      );

      const data = await response.json();
      setResult(data);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      {loading && <p>Analyzing...</p>}
      {result && (
        <div>
          <h2>Score: {result.score}/100</h2>
          <p>{result.summary}</p>
          <ul>
            {result.positives.map((p, i) => <li key={i}>{p}</li>)}
          </ul>
        </div>
      )}
    </div>
  );
}
```

---

## Testing Locally

### Test with Real Curl
```bash
# Health check
curl -v http://localhost:8000/api/health

# Analyze with pretty JSON output
curl -X POST http://localhost:8000/api/analyze-candidate \
  -H "Content-Type: application/json" \
  -d '{
    "jobDescription": "Senior Developer with 5+ years experience...",
    "candidateCV": "Jane Doe\n\nExperience:\nSenior Developer..."
  }' | jq .
```

### Using Postman
1. Create POST request to `http://localhost:8000/api/analyze-candidate`
2. Set Header: `Content-Type: application/json`
3. Set Body (raw JSON):
   ```json
   {
     "jobDescription": "...",
     "candidateCV": "..."
   }
   ```
4. Click Send

---

## Rate Limiting Considerations

For production, add rate limiting:
```bash
# Example: 100 requests per minute per IP
docker-compose exec php bin/console cache:clear
```

---

That's your complete API! Ready to integrate with your React frontend. 🚀
