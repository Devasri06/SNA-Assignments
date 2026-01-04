import time
import logging
from collections import deque, defaultdict
import config

# Configure logging
logging.basicConfig(
    filename=config.LOG_FILE,
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)

class DoSProtector:
    def __init__(self):
        # Stores request timestamps for each IP: {ip: deque([t1, t2, ...])}
        self.request_history = defaultdict(deque)
        
        # Stores blocked IPs and their unblock time: {ip: unblock_timestamp}
        self.blocked_ips = {}
        
        self.window = config.TIME_WINDOW
        self.max_requests = config.MAX_REQUESTS_PER_WINDOW
        self.block_duration = config.BLOCK_DURATION
        
        # Stats for reporting
        self.total_tracked_ips = 0

    def cleanup_old_requests(self, ip, current_time):
        """Remove timestamps that are outside the sliding window."""
        timestamps = self.request_history[ip]
        while timestamps and timestamps[0] < (current_time - self.window):
            timestamps.popleft()

    def is_blocked(self, ip):
        """Check if an IP is currently blocked."""
        current_time = time.time()
        if ip in self.blocked_ips:
            if current_time < self.blocked_ips[ip]:
                return True
            else:
                # Block expired
                del self.blocked_ips[ip]
                logging.info(f"Unblocked IP: {ip} (Block expired)")
        return False

    def process_request(self, ip):
        """
        Analyze the request from an IP.
        Returns:
            bool: True if request allowed, False if blocked.
        """
        current_time = time.time()

        # 1. Check if already blocked
        if self.is_blocked(ip):
            logging.warning(f"BLOCKED request from {ip} (Active Block)")
            return False

        # 2. Cleanup old history for this IP
        self.cleanup_old_requests(ip, current_time)

        # 3. Add current request
        self.request_history[ip].append(current_time)
        request_count = len(self.request_history[ip])

        # 4. Check threshold
        if request_count > self.max_requests:
            self.block_ip(ip, current_time)
            return False

        # 5. Allow request
        return True

    def block_ip(self, ip, current_time):
        """Block the IP for the configured duration."""
        unblock_time = current_time + self.block_duration
        self.blocked_ips[ip] = unblock_time
        logging.warning(f"DETECTED DoS: Blocking {ip} for {self.block_duration}s. Request count: {len(self.request_history[ip])}")

# Global instance
firewall_engine = DoSProtector()
