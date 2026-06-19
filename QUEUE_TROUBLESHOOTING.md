# Queue System Troubleshooting Guide

## Problem: Verification OTP page shows with delay, no email received

### Checklist

#### 1. Verify Redis is Running

```bash
# Check if Redis is running
redis-cli ping

# Should return: PONG

# If not running, start Redis:
redis-server
```

#### 2. Check Redis Connection Configuration

Your environment variables should be set:

```bash
echo $REDIS_HOST      # Should print: localhost (or your Redis host)
echo $REDIS_PORT      # Should print: 6379 (or your port)
echo $REDIS_PASSWORD  # Should print your password (if set) or empty
```

**In `.env` file:**
```env
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=           # Leave empty if no password
```

#### 3. Enable Debug Output

When you see the debug logs from Queue library:

```
[v0] Queue: Attempting to connect to Redis at localhost:6379
[v0] Queue: Connected to Redis successfully
[v0] Queue: Pushing send_otp job to Redis
[v0] Queue: Push result: SUCCESS
```

This means the queue is working. If you see error messages instead, note them and follow below.

#### 4. Common Issues & Solutions

##### Issue: `[v0] Queue ERROR: Failed to connect to Redis`

**Cause**: Redis server is not running or not accessible at the configured host/port

**Solution**:
- Make sure Redis is running: `redis-server`
- Check host is correct: `REDIS_HOST=localhost` (not `127.0.0.1` on some systems)
- Check port is correct: `REDIS_PORT=6379`
- Test connection manually: `redis-cli -h localhost -p 6379 ping`

##### Issue: `[v0] Queue ERROR: Redis connection not available - cannot push send_otp job`

**Cause**: Queue library is loaded but not connected to Redis

**Solution**:
- Restart your web server (Apache/Nginx)
- Verify Redis is running
- Check logs in `application/logs/` for detailed error messages

##### Issue: Jobs are pushed but worker doesn't process them

**Cause**: Queue worker process is not running

**Solution**:
1. Start the queue worker:
   ```bash
   php application/commands/queue_worker.php
   ```

2. It should print:
   ```
   [2026-06-19 09:00:35] [info] Queue Worker started
   [2026-06-19 09:00:35] [info] Polling Redis queue for jobs...
   ```

3. Keep this running (in background or supervisor)

##### Issue: Worker is running but still no emails

**Cause**: Email configuration might be wrong

**Solution**:
1. Check `application/config/email.php` has correct settings
2. Test email manually in a controller:
   ```php
   $this->load->library('email');
   $this->email->from('sender@example.com', 'App Name');
   $this->email->to('recipient@example.com');
   $this->email->subject('Test');
   $this->email->message('Test message');
   $this->email->send();
   ```

#### 5. Full Testing Workflow

1. **Start Redis**:
   ```bash
   redis-server
   ```

2. **Start Queue Worker** (in another terminal):
   ```bash
   cd /path/to/project
   php application/commands/queue_worker.php
   ```

3. **Login to app** and trigger 2FA:
   - Go to login page
   - Enter username/password
   - Watch the terminal with queue worker - you should see job processing
   - Watch application logs in `application/logs/`

4. **Expected flow**:
   - Login page shows OTP verify form immediately (< 100ms)
   - Queue worker logs show: `Processing job: send_otp`
   - Email arrives within 1-5 seconds
   - You receive the OTP code in your email

#### 6. Check Application Logs

Detailed logs are written to `application/logs/`:

```bash
# View latest logs
tail -f application/logs/log-*.php

# Search for queue errors
grep -i "queue" application/logs/log-*.php
```

#### 7. Check if Jobs are in Redis Queue

Connect to Redis and inspect the queue:

```bash
redis-cli
> LLEN codeigniter:queue    # Returns number of jobs waiting
> LRANGE codeigniter:queue 0 -1  # Shows all jobs in queue
```

If queue is full of jobs but worker isn't processing, the worker process isn't running.

## Quick Debug Steps

1. Add this to your login page to see debug output:
   ```php
   // In Two_factor controller, after dispatch_otp call
   echo "[v0] dispatch_otp returned: " . ($result ? 'TRUE' : 'FALSE');
   ```

2. Watch the queue worker output for processing confirmation

3. Check logs: `tail -f application/logs/log-*.php | grep -i queue`

## Still Having Issues?

1. Copy the debug output showing the error
2. Check Redis is definitely running: `redis-cli ping`
3. Check worker is definitely running: `ps aux | grep queue_worker`
4. Check email configuration: `cat application/config/email.php`
5. Review application logs: `tail -20 application/logs/log-*.php`
