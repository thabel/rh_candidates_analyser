# Test Candidate Application

This file contains test data in Markdown format that you can convert to PDF and use for testing the application.

## 1. Client Submission API Test

**Endpoint:** `POST /api/submit-candidate`

**Example Request:**
```json
{
  "firstName": "Mohamed",
  "lastName": "Ali Bannour",
  "email": "mohamed.ali@example.com",
  "cvText": "# Mohamed Ali Bannour\n\n## Professional Summary\nExperienced QA Engineer with 9+ years of expertise in test automation, quality assurance, and team management. Proven track record with leading tech companies like Microsoft and Google.",
  "cvFileName": "cv_mohammed_ali.pdf"
}
```

---

## 2. Sample CV (Markdown Format)

Convert this markdown to PDF and paste the content into the `cvText` field above.

---

# Mohamed Ali Bannour

**Email:** mohamed.ali@example.com  
**Phone:** +1 (555) 123-4567  
**Location:** San Francisco, CA  
**LinkedIn:** linkedin.com/in/mohamedali  

---

## PROFESSIONAL SUMMARY

Results-driven Senior QA Manager with 9+ years of expertise in test automation, quality assurance strategy, and team leadership. Proven track record of implementing robust QA processes for high-traffic applications at Fortune 500 companies including Microsoft and Google. Specialized in building and managing distributed QA teams, architecting automated testing frameworks, and reducing defect rates by up to 60%.

---

## CORE COMPETENCIES

**Test Automation & Frameworks:**
- Playwright (Advanced)
- Cypress (Advanced)
- Selenium (Advanced)
- TestNG, JUnit
- API Testing (RestAssured, Postman)
- Load Testing (JMeter, Gatling)

**Tools & Technologies:**
- Azure DevOps, Jira, Confluence
- Git, Jenkins, GitHub Actions
- Docker, Kubernetes
- SQL, NoSQL databases
- AWS, Google Cloud Platform

**Management & Leadership:**
- Team Building & Mentoring
- Process Documentation
- Risk Assessment & Management
- Agile/Scrum Methodology
- Cross-functional Collaboration

---

## PROFESSIONAL EXPERIENCE

### Senior QA Manager
**Microsoft Corporation** | Seattle, WA | Jan 2020 - Present
- Lead a distributed QA team of 15+ engineers across 3 continents
- Architected and implemented end-to-end automation framework reducing testing time by 50%
- Implemented CI/CD pipelines resulting in faster release cycles
- Mentored 8 junior QA engineers, 3 promoted to QA Lead positions
- Reduced critical defects in production by 65% through advanced testing strategies
- Managed QA budget of $2M+, optimizing costs while maintaining quality standards

**Key Achievements:**
- Designed automation strategy for 5 major product releases
- Implemented performance testing reducing latency by 40%
- Established QA best practices adopted across 20+ teams
- Certified in ISO 9001 Quality Management Systems

### QA Lead
**Google (Alphabet Inc.)** | Mountain View, CA | Mar 2018 - Dec 2019
- Supervised QA operations for Android Testing Framework (9 engineers)
- Developed comprehensive test automation suite using Playwright covering 2000+ test cases
- Implemented mobile testing infrastructure on real devices and emulators
- Coordinated testing efforts for 3 major product launches (all successful)
- Achieved 98% automation coverage for critical user workflows

**Key Achievements:**
- Reduced bug escape rate by 55% through intelligent test design
- Created automated regression suite reducing manual testing by 60%
- Established QA documentation standards adopted company-wide
- Presented QA automation best practices at 2 industry conferences

### QA Engineer & Team Lead
**Amazon Web Services (AWS)** | Arlington, VA | Jun 2016 - Feb 2018
- Managed automated testing for AWS Console interfaces
- Built Selenium-based testing framework supporting 500+ test scenarios
- Led migration from manual testing to 85% automation coverage
- Mentored 3 junior engineers in test automation practices

**Key Achievements:**
- Improved test execution speed by 70% through parallel testing
- Identified and documented 200+ production-critical bugs during UAT
- Established QA metrics dashboard for real-time visibility

### QA Automation Engineer
**TechCorp Solutions** | Boston, MA | Aug 2014 - May 2016
- Designed and implemented Cypress-based testing framework for web applications
- Developed API testing suite using RestAssured and Postman
- Collaborated with developers to improve code testability

### Junior QA Engineer
**StartupTech Inc.** | Boston, MA | Jul 2013 - Jul 2014
- Performed manual testing on web and mobile applications
- Created test cases and test plans for multiple projects
- Assisted in setting up Selenium testing infrastructure

---

## EDUCATION

**Bachelor of Science in Computer Science**
Boston University | Boston, MA | Graduated: May 2013
- GPA: 3.8/4.0
- Honors: Magna Cum Laude

**Relevant Coursework:**
- Software Quality Assurance
- Database Management Systems
- Object-Oriented Programming
- Web Development

---

## CERTIFICATIONS & TRAINING

- **ISTQB Certified Tester (CTFL)** - International Software Testing Qualifications Board, 2016
- **Certified Scrum Master (CSM)** - Scrum Alliance, 2017
- **AWS Solutions Architect Associate** - Amazon Web Services, 2018
- **Google Cloud Associate Cloud Engineer** - Google, 2019
- **Playwright Advanced Certification** - Official Training, 2021

---

## TECHNICAL PROJECTS

### Automated Testing Framework Migration (2021)
- Led migration from Selenium to Playwright across 20+ teams
- Improved test execution speed by 45%
- Achieved zero-defect deployment rate for 6 consecutive months

### Performance Testing Infrastructure (2020)
- Implemented JMeter-based load testing infrastructure
- Identified and resolved 15 critical performance bottlenecks
- Reduced page load time by 40%

### Mobile Testing Platform (2019)
- Designed cloud-based mobile testing platform
- Supported testing on 500+ device configurations
- Reduced mobile testing time by 50%

---

## LANGUAGES

- English (Native)
- Arabic (Fluent)
- French (Intermediate)

---

## ADDITIONAL INFORMATION

- **Publications:** "Best Practices in Test Automation" - Software Testing Magazine, 2020
- **Speaking:** QA Summit 2021, TestCon Europe 2019
- **Open Source:** Active contributor to Playwright community
- **Volunteer:** Mentor for underprivileged youth in STEM programs

---

## REFERENCES

Available upon request

---

# Admin Test Scenarios

## 1. List All Candidates

**Endpoint:** `GET /api/admin/candidates`

**Expected Response:**
```json
{
  "total": 1,
  "candidates": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "firstName": "Mohamed",
      "lastName": "Ali Bannour",
      "email": "mohamed.ali@example.com",
      "status": "pending",
      "score": null,
      "submittedAt": "2025-11-01 10:30:45",
      "analyzedAt": null
    }
  ]
}
```

## 2. List Pending Candidates

**Endpoint:** `GET /api/admin/candidates-pending`

**Expected Response:** Same as above but only pending candidates

## 3. Get Candidate Details

**Endpoint:** `GET /api/admin/candidate/{id}`

**Expected Response:**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "firstName": "Mohamed",
  "lastName": "Ali Bannour",
  "email": "mohamed.ali@example.com",
  "cvText": "# Mohamed Ali Bannour...",
  "cvFileName": "cv_mohammed_ali.pdf",
  "status": "pending",
  "score": null,
  "analysis": null,
  "submittedAt": "2025-11-01 10:30:45",
  "analyzedAt": null
}
```

## 4. Analyze Candidate with Job Description

**Endpoint:** `POST /api/admin/candidate/{id}/analyze`

**Request Body:**
```json
{
  "jobDescription": "We are looking for a Senior QA Manager with 7+ years of test automation experience, leadership skills, and expertise in Playwright and Cypress. Must have experience with cloud platforms and team management."
}
```

**Expected Response:**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "firstName": "Mohamed",
  "lastName": "Ali Bannour",
  "email": "mohamed.ali@example.com",
  "status": "analyzed",
  "score": 88,
  "analysis": {
    "score": 88,
    "summary": "Excellent candidate with extensive QA automation experience and proven leadership at major tech companies.",
    "positives": [
      "9+ years of QA experience exceeding the 7+ years requirement",
      "Expert-level skills in Playwright, Cypress, and Selenium",
      "Proven team management experience at Microsoft with 15+ engineers",
      "Cloud platform expertise (AWS, GCP, Azure)",
      "Multiple industry certifications and speaking engagements"
    ],
    "negatives": [
      "CV lacks specific examples of performance testing optimization metrics",
      "Limited mention of security testing and vulnerability assessment experience"
    ]
  },
  "analyzedAt": "2025-11-01 10:35:20",
  "message": "Analyse réussie"
}
```

## 5. Delete Candidate

**Endpoint:** `DELETE /api/admin/candidate/{id}`

**Expected Response:**
```json
{
  "message": "Candidature supprimée"
}
```

---

# Testing Instructions

1. **Convert this Markdown to PDF**
   - Use an online tool like pandoc, markdown-to-pdf.com, or your preferred converter
   - Save as `test_cv.pdf`

2. **Extract the CV text**
   - Use the CV section (from "# Mohamed Ali Bannour" to "## References")
   - This is your `cvText` content

3. **Submit a Candidate**
   ```bash
   curl -X POST http://localhost:8080/api/submit-candidate \
     -H "Content-Type: application/json" \
     -d '{
       "firstName": "Mohamed",
       "lastName": "Ali Bannour",
       "email": "mohamed.ali@example.com",
       "cvText": "[PASTE_CV_TEXT_HERE]",
       "cvFileName": "test_cv.pdf"
     }'
   ```

4. **Get the Candidate ID** from the response

5. **Analyze the Candidate**
   ```bash
   curl -X POST http://localhost:8080/api/admin/candidate/{id}/analyze \
     -H "Content-Type: application/json" \
     -d '{
       "jobDescription": "Senior QA Manager with 7+ years of test automation experience..."
     }'
   ```

---

**Note:** All test data is fictional and created for demonstration purposes only.
