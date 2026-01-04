
# Configuration for the Firewall

# Network Configuration
FIREWALL_HOST = '127.0.0.1'
FIREWALL_PORT = 8080

BACKEND_HOST = '127.0.0.1'
BACKEND_PORT = 9000

# DoS Protection Configuration
TIME_WINDOW = 60  # Sliding window in seconds
MAX_REQUESTS_PER_WINDOW = 50  # Max requests per IP in the time window
BLOCK_DURATION = 30  # Duration to block an IP in seconds

# Logging
LOG_FILE = 'dos_firewall.log'
