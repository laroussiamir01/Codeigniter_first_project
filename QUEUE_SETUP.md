# Redis Queue Setup Guide

This project uses Redis queues for asynchronous email processing, specifically for 2FA OTP emails. This eliminates delays during login and improves user experience.

## Prerequisites

1. **Redis Server**: A running Redis instance (local or managed service like Upstash)
2. **PHP CLI**: For running the queue worker
3. **No external dependencies required** - Uses native PHP socket functions

## Installation Steps

### 1. Configure Environment Variables

Set the following environment variables in your `.env` or server configuration:

```env
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=           # (optional) Leave empty if no password
```

**Using Upstash Redis** (Recommended for production):
- Sign up at https://upstash.com
- Create a new Redis database
- Copy the connection details to your environment variables

### 2. Verify Queue Configuration

The queue configuration file is automatically loaded from `application/config/queue.php`. It reads environment variables and falls back to defaults (localhost:6379).

## Running the Queue Worker

The queue worker processes jobs from the Redis queue and sends OTP emails asynchronously.

### Start Worker (Development)

```bash
cd /path/to/project
php application/commands/queue_worker.php
```

The worker will display:
```
[2024-06-18 14:30:00] [info] Queue Worker started
[2024-06-18 14:30:00] [info] Polling Redis queue for jobs...
```

To run in background during development:
```bash
nohup php application/commands/queue_worker.php > storage/logs/queue-worker.log 2>&1 &
```

Or use `screen` for a detachable session:
```bash
screen -S queue-worker
cd /path/to/project
php application/commands/queue_worker.php
# Press Ctrl+A then D to detach
# Reconnect: screen -r queue-worker
```

### Running Worker in Background (Production)

Use a process manager like **Supervisor** to keep the worker running:

#### Using Supervisor

Create `/etc/supervisor/conf.d/codeigniter-queue.conf`:

```ini
[program:codeigniter-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/project/application/commands/queue_worker.php
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/codeigniter-queue.log
directory=/path/to/project
user=www-data
```

Start the worker:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start codeigniter-queue:*
```

Check status:
```bash
sudo supervisorctl status
```

#### Using Systemd

Create `/etc/systemd/system/codeigniter-queue.service`:

```ini
[Unit]
Description=CodeIgniter Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/project
ExecStart=/usr/bin/php /path/to/project/application/commands/queue_worker.php
Restart=on-failure
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl enable codeigniter-queue
sudo systemctl start codeigniter-queue
```

Check status:
```bash
sudo systemctl status codeigniter-queue
```

## How It Works

### Flow

1. **User Login**: User enters email and password
2. **Password Verification**: System verifies credentials
3. **Queue Push**: System issues OTP code and **immediately** pushes "send_otp" job to Redis queue
4. **User Sees Verify Page**: User is redirected to verify page **instantly** (no email delay)
5. **Worker Processes**: Background worker picks up job and sends OTP email
6. **User Receives Email**: Email arrives within seconds
7. **Verification**: User enters code and completes 2FA

### Key Benefits

- ✅ **Instant UX**: Login page shows immediately, no 1-5s delay
- ✅ **Reliable**: Jobs persist in Redis queue if worker is temporarily offline
- ✅ **Scalable**: Can run multiple workers if needed
- ✅ **Debuggable**: All jobs and errors are logged

## Testing

### Test Queue Connection

Create a test script at `/path/to/project/test_queue.php`:

```php
<?php
define('BASEPATH', dirname(__FILE__));
define('APPPATH', dirname(__FILE__) . '/application/');
require_once(BASEPATH . '/index.php');

$CI = &get_instance();
$CI->load->library('queue');

// Test push
$result = $CI->queue->push('test', ['message' => 'Hello from queue']);
echo "Push result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";

// Test queue size
echo "Queue size: " . $CI->queue->size() . "\n";

// Test pop
$job = $CI->queue->pop();
echo "Popped job: " . json_encode($job) . "\n";
?>
```

Run:
```bash
php test_queue.php
```

### Monitor Queue

Check queue size at any time:
```php
$CI->load->library('queue');
echo "Jobs pending: " . $CI->queue->size();
```

## Troubleshooting

### Redis Connection Failed

**Error**: `Queue: Failed to connect to Redis`

**Solutions**:
1. Check if Redis is running: `redis-cli ping` (should return PONG)
2. Verify environment variables are set correctly
3. Check firewall rules if Redis is on a different server
4. For Upstash, verify the connection string format

### Worker Stops Unexpectedly

**Solutions**:
1. Check logs: `tail -f /var/log/codeigniter-queue.log`
2. Check system resources (RAM, CPU, disk space)
3. Restart worker: `sudo supervisorctl restart codeigniter-queue:*`
4. Ensure PHP CLI is installed: `php -v`

### OTP Emails Not Being Sent

**Check**:
1. Is worker running? `ps aux | grep queue_worker`
2. Is queue receiving jobs? Check queue size
3. Are email credentials correct in `application/config/email.php`?
4. Check CodeIgniter logs: `application/logs/`

### Reset Queue (Clear All Jobs)

```php
$CI->load->library('queue');
$CI->queue->flush();
echo "Queue flushed";
```

## Performance Tuning

### Adjust Polling Interval

Edit `application/commands/queue_worker.php`, line ~45:

```php
sleep(1); // Change to higher value for less CPU usage, lower for faster processing
```

### Multiple Workers

Run multiple worker instances for higher throughput:

```bash
# Start 3 workers
for i in {1..3}; do
  php index.php queue_worker &
done
```

Or configure in Supervisor:
```ini
numprocs=3  # Run 3 workers
```

## Security Notes

- Keep `REDIS_PASSWORD` in a secure `.env` file (not in version control)
- Use Upstash Redis over the internet instead of local Redis
- Restrict Redis network access to application servers only
- Monitor Redis memory usage and implement eviction policies

## References

- [Predis Documentation](https://github.com/nrk/predis)
- [Redis Documentation](https://redis.io/documentation)
- [Upstash Redis](https://upstash.com)
- [CodeIgniter Email Library](https://codeigniter.com/user_guide/libraries/email.html)

---

For issues or questions, check the application logs in `application/logs/`.
