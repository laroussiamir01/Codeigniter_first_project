# Redis Queue Architecture

## System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────┐
│                         USER BROWSER                                 │
│                                                                      │
│  1. Submit Login (email + password)                                 │
│  ┌──────────────────────────────────────┐                          │
│  │   Login Form                         │                          │
│  └──────────────────────────────────────┘                          │
│           │                                                         │
│           ▼ (POST /user/login)                                      │
└─────────────────────────────────────────────────────────────────────┘
            │
            │
            ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      APPLICATION SERVER                              │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  User.php::login()                                           │  │
│  │  - Verify email + password                                  │  │
│  │  - Set session: pending_2fa                                 │  │
│  │  - Redirect to /two_factor/verify                           │  │
│  └──────────────────────────────────────────────────────────────┘  │
│           │                                                          │
│           ▼                                                          │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  Two_factor.php::verify() - PAGE LOAD                       │  │
│  │  ┌──────────────────────────────────────────────────────┐   │  │
│  │  │  maybe_issue_initial_code()                         │   │  │
│  │  │    │                                                 │   │  │
│  │  │    ├─ Two_factor_model::issue_code($user_id)       │   │  │
│  │  │    │  └─> Generates random 6-digit OTP            │   │  │
│  │  │    │  └─> Stores in user_otp table with TTL        │   │  │
│  │  │    │                                                 │   │  │
│  │  │    └─ dispatch_otp($user_id, $username)            │   │  │
│  │  │       │                                              │   │  │
│  │  │       ├─ get_user_email() → user@example.com       │   │  │
│  │  │       │                                              │   │  │
│  │  │       └─ Queue::push('send_otp', {                 │   │  │
│  │  │            user_id,                                 │   │  │
│  │  │            username,                                │   │  │
│  │  │            code,                                    │   │  │
│  │  │            email                                    │   │  │
│  │  │          })                                          │   │  │
│  │  │          ✓ Job pushed instantly                      │   │  │
│  │  │          ✓ Returns TRUE                              │   │  │
│  │  │                                                      │   │  │
│  │  └──────────────────────────────────────────────────────┘   │  │
│  │           │                                                   │  │
│  │           ▼ (Response returned INSTANTLY)                    │  │
│  │  ┌──────────────────────────────────────────────────────┐   │  │
│  │  │  Render verify_otp_view.php                         │   │  │
│  │  │  - Show OTP input form                              │   │  │
│  │  │  - User can enter code now                          │   │  │
│  │  │  (Email is being sent in background)                │   │  │
│  │  └──────────────────────────────────────────────────────┘   │  │
│  └──────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
            │ (Continues polling for user input)
            │
            ▼ (Response < 100ms)
┌─────────────────────────────────────────────────────────────────────┐
│                      USER BROWSER (INSTANT)                          │
│                                                                      │
│  ✓ Verify OTP Page Loaded                                           │
│  ✓ Ready for user to enter code                                     │
│  ✓ No waiting for email sending!                                    │
└─────────────────────────────────────────────────────────────────────┘


PARALLEL: In the background...

┌─────────────────────────────────────────────────────────────────────┐
│                         REDIS SERVER                                 │
│                                                                      │
│  Queue: "codeigniter:queue"                                         │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  [{                                                          │  │
│  │    "type": "send_otp",                                       │  │
│  │    "data": {                                                 │  │
│  │      "user_id": 1,                                           │  │
│  │      "username": "amir",                                     │  │
│  │      "code": "123456",                                       │  │
│  │      "email": "amir@example.com"                             │  │
│  │    },                                                        │  │
│  │    "created_at": 1718721000,                                 │  │
│  │    "attempts": 0                                             │  │
│  │  }]                                                          │  │
│  └──────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
            │
            │ (Worker polls every 1 second)
            │
            ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      QUEUE WORKER (CLI)                              │
│                                                                      │
│  $ php index.php queue_worker                                       │
│                                                                      │
│  [2024-06-18 14:30:45] [info] Queue Worker started                 │
│  [2024-06-18 14:30:45] [info] Polling Redis queue for jobs...      │
│  [2024-06-18 14:30:46] [info] Processing job: send_otp              │
│                                                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  handle_send_otp($job)                                       │  │
│  │    │                                                          │  │
│  │    ├─ Extract: code, email, user_id                         │  │
│  │    │                                                          │  │
│  │    ├─ Load Email Library                                     │  │
│  │    │                                                          │  │
│  │    ├─ $email->from('no-reply@example.com')                  │  │
│  │    ├─ $email->to('amir@example.com')                        │  │
│  │    ├─ $email->subject('OTP: 123456')                        │  │
│  │    ├─ $email->message('123456')                             │  │
│  │    │                                                          │  │
│  │    └─ $email->send() ✓                                       │  │
│  │                                                              │  │
│  │  [2024-06-18 14:30:47] [info]                               │  │
│  │  OTP email sent to amir@example.com for user 1              │  │
│  │                                                              │  │
│  │  Processed 1 jobs, 0 errors                                 │  │
│  └──────────────────────────────────────────────────────────────┘  │
│           │                                                         │
│           ▼ (Back to polling)                                       │
│  [2024-06-18 14:30:48] [info] Polling Redis queue for jobs...      │
└─────────────────────────────────────────────────────────────────────┘
            │
            │ (Sends via SMTP/Mail Service)
            │
            ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    EMAIL SERVICE / SMTP                              │
│                                                                      │
│  From: no-reply@example.com                                         │
│  To: amir@example.com                                               │
│  Subject: OTP: 123456                                               │
│  Body: 123456                                                       │
│                                                                      │
│  Status: Sent ✓                                                     │
└─────────────────────────────────────────────────────────────────────┘
            │
            │ (Email arrives in seconds)
            │
            ▼
┌─────────────────────────────────────────────────────────────────────┐
│                     USER'S EMAIL INBOX                               │
│                                                                      │
│  From: no-reply@example.com                                         │
│  Subject: OTP: 123456                                               │
│  Body: 123456                                                       │
│                                                                      │
│  User copies code and enters it in the browser                      │
└─────────────────────────────────────────────────────────────────────┘
            │
            │ (User enters code)
            │
            ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      APPLICATION SERVER                              │
│                                                                      │
│  Two_factor.php::verify() - CODE VERIFICATION                       │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  $this->form_validation->set_rules(...)                     │  │
│  │  Code submitted: 123456                                     │  │
│  │                                                              │  │
│  │  Two_factor_model::verify(user_id, code)                   │  │
│  │    ├─ Check: code matches stored code? ✓                   │  │
│  │    ├─ Check: code not expired? ✓                           │  │
│  │    └─ Return: 'ok'                                          │  │
│  │                                                              │  │
│  │  Result: 'ok' → Set session as logged in                    │  │
│  │           Redirect to /home                                 │  │
│  │  Result: 'mismatch' → Show error, stay on verify page       │  │
│  │  Result: 'expired' → Show error, require new code           │  │
│  │  Result: 'locked' → Account locked, too many attempts       │  │
│  └──────────────────────────────────────────────────────────────┘  │
│           │                                                         │
│           ▼ (If verification succeeds)                              │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  Set Session:                                               │  │
│  │  - user_id: 1                                               │  │
│  │  - username: amir                                           │  │
│  │  - logged_in: true                                          │  │
│  │                                                              │  │
│  │  Redirect to /home                                          │  │
│  └──────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
            │
            ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      USER BROWSER - LOGGED IN                        │
│                                                                      │
│  ✓ Home page loaded                                                 │
│  ✓ User is authenticated                                            │
│  ✓ 2FA verification complete!                                       │
└─────────────────────────────────────────────────────────────────────┘
```

## Component Interaction Diagram

```
                    ┌────────────────────────────┐
                    │   CodeIgniter Framework    │
                    └────────────────────────────┘
                              │
                ┌─────────────┼─────────────┐
                │             │             │
                ▼             ▼             ▼
         ┌──────────┐  ┌──────────┐  ┌──────────┐
         │ User     │  │Two_factor│  │ Models  │
         │Controller│  │Controller│  │         │
         └──────────┘  └──────────┘  └──────────┘
                │             │             │
                │        ┌────┼────┐        │
                │        ▼         │        │
                │    ┌────────┐    │        │
                └───►│ Queue  │◄───┴────────┘
                     │Library │
                     └────────┘
                         │
            ┌────────────┼────────────┐
            │            │            │
            ▼            ▼            ▼
        ┌────────┐  ┌────────┐  ┌────────┐
        │ Predis │  │Redis   │  │ Logs  │
        │ Client │  │Server  │  │       │
        └────────┘  └────────┘  └────────┘
                        │
            ┌───────────┼───────────┐
            │           │           │
            ▼           ▼           ▼
        ┌────────┐  ┌────────┐  ┌────────┐
        │Queue   │  │Job     │  │Worker  │
        │Storage │  │Status  │  │Process │
        └────────┘  └────────┘  └────────┘
                                    │
                        ┌───────────┼───────────┐
                        ▼           ▼           ▼
                    ┌────────┐ ┌────────┐ ┌────────┐
                    │ Email  │ │Logging │ │Status  │
                    │Library │ │System  │ │Tracking│
                    └────────┘ └────────┘ └────────┘
                        │
                        ▼
                    ┌────────┐
                    │SMTP    │
                    │Service │
                    └────────┘
```

## Data Flow Sequence

```
Timestamp  │ Component            │ Action
───────────┼──────────────────────┼─────────────────────────────────────
00:00:00   │ User                 │ Submit login form
00:00:01   │ User_Controller      │ Verify credentials
00:00:01   │ Session              │ Set pending_2fa
00:00:01   │ User                 │ Redirected to /two_factor/verify
00:00:02   │ Browser              │ Load verify page
00:00:02   │ Two_factor_Ctrl      │ maybe_issue_initial_code()
00:00:02   │ Two_factor_Model     │ issue_code() → generate OTP
00:00:02   │ Database             │ INSERT user_otp (code, user_id, TTL)
00:00:02   │ Two_factor_Ctrl      │ dispatch_otp()
00:00:02   │ Queue::push()        │ Push job to Redis ← INSTANT RETURN
00:00:02   │ Two_factor_Ctrl      │ Return TRUE
00:00:02   │ View Renderer        │ Render verify_otp_view.php
00:00:02   │ Browser              │ Receive page ← < 100ms total
00:00:03   │ Browser              │ ✓ Display verify form (ready for input)
           │
           │ ╔════════════════════════════════════════════════╗
           │ ║ Background Processing (While User Types Code) ║
           │ ╚════════════════════════════════════════════════╝
           │
00:00:04   │ Queue_Worker        │ Pop job from Redis
00:00:04   │ Worker              │ handle_send_otp()
00:00:04   │ Email_Library       │ Configure email
00:00:04   │ SMTP_Service        │ Connect to server
00:00:05   │ SMTP_Service        │ Send email
00:00:05   │ Email_Service       │ Email queued for delivery
00:00:06   │ ISP_Mail_Server     │ Receive email
00:00:07   │ User_Mailbox        │ Email delivered ← User receives OTP
00:00:08   │ User                │ Check email (finds code)
00:00:09   │ User                │ Return to browser
00:00:10   │ User                │ Type OTP code: 123456
00:00:11   │ User                │ Click "Verify" button
00:00:12   │ Two_factor_Ctrl     │ verify() - CODE VERIFICATION
00:00:12   │ Two_factor_Model    │ verify(user_id, code)
00:00:12   │ Database            │ SELECT * FROM user_otp WHERE...
00:00:12   │ Comparison          │ Code matches ✓, Not expired ✓
00:00:12   │ Session             │ Set logged_in = TRUE
00:00:12   │ Two_factor_Ctrl     │ Redirect to /home
00:00:13   │ Browser             │ Load home page
00:00:13   │ User                │ ✓ Successfully logged in!
```

## Timeline Comparison

### Before (Synchronous Email)
```
User Action    Server Processing
│              │
├─Login        ├─Verify credentials
│              ├─Send OTP email (blocking) ⏳ 1-5 seconds
│              └─Render verify page
│
└─Wait 1-5s ◀──┘
```

**Total Time: 1-5+ seconds before user sees verify page**

### After (Asynchronous Queue)
```
User Action    Server Processing          Background Worker
│              │                           │
├─Login        ├─Verify credentials       │
│              ├─Push job to Redis ✓      │
│              ├─Render verify page       │
│              └─Return (< 100ms) ✓       │
│                                         ├─Poll queue
└─Instantly ◀──┘                          ├─Send OTP email ⏳ 1-5s
               See verify page             └─Complete
```

**Total Time to See Verify Page: < 100ms**  
**Email Delivery: 1-5s (in background)**

## Deployment Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                   WEB SERVER (Nginx/Apache)                     │
│                                                                 │
│  Port 80/443 ◄─── User Requests                               │
│     │                                                           │
│     ▼                                                           │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │  PHP-FPM (Handles Web Requests)                         │  │
│  │  ├─ Two_factor_Controller                              │  │
│  │  │  └─ dispatch_otp() → Push to Redis                 │  │
│  │  └─ Return response (< 100ms)                          │  │
│  └─────────────────────────────────────────────────────────┘  │
│     │                                                           │
│     ├──► REDIS ◄───┐                                          │
│     │              │                                           │
│     └──────────────┘                                          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
                       │
        ┌──────────────┼──────────────┐
        │              │              │
        ▼              ▼              ▼
    ┌────────┐  ┌────────┐  ┌─────────────────┐
    │Database│  │ REDIS  │  │Queue Worker CLI │
    │ Server │  │ Server │  │(Separate Proc)  │
    │        │  │        │  │                 │
    │user    │  │Queue   │  │ Runs:           │
    │user_otp│  │Jobs    │  │ $ php index.php │
    │        │  │        │  │   queue_worker  │
    └────────┘  └────────┘  └─────────────────┘
                                    │
                                    ├─ Polls Redis every 1s
                                    ├─ Processes jobs
                                    ├─ Sends emails
                                    └─ Logs results
```

## Critical Paths

### Fast Path (User Experience)
```
Login Form Submission
  ▼
User_Controller::login()
  ├─ Verify Password
  ├─ Set Session
  ▼
Two_factor_Controller::verify()
  ├─ Load Page
  ├─ Push OTP Job to Redis
  ├─ Render View
  ▼
Return Response to Browser ✓ (< 100ms)
  ▼
User Sees Verify Form Instantly ✓
```

### Reliable Path (Email Delivery)
```
Queue Worker Starts
  ▼
Poll Redis Every 1 Second
  ▼
Job Found
  ├─ Extract Job Data
  ├─ Load Email Library
  ├─ Verify Data
  ▼
Send Email via SMTP
  ├─ Connect to SMTP Server
  ├─ Authenticate
  ├─ Send Message
  ▼
Log Success/Failure
  ▼
Return to Polling ✓
```

---

This architecture ensures fast user feedback while reliably delivering emails asynchronously.
