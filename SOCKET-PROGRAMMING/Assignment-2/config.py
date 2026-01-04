
# Math/BC Firewall Configuration

# Network
FIREWALL_HOST = '0.0.0.0'
FIREWALL_PORT = 8000
BACKEND_HOST = '127.0.0.1'
BACKEND_PORT = 9000

# Security
ENABLE_SSL = True
CERT_FILE = 'server.crt'
KEY_FILE = 'server.key'

# SQL Injection Patterns
SQLI_PATTERNS = [
    r"(\%27)|(\')",          # Single quote
    r"(\-\-)",               # Comment
    r"(\%23)|(#)",           # Comment
    r"((\%3D)|(=))[^\n]*((\%27)|(\')|(\-\-)|(\%3B)|(;))", # Meta-characters with =
    r"\w*((\%27)|(\'))(\s*)((\%6F)|o|(\%4F))((\%72)|r|(\%52))", # ' or '
    r"((\%27)|(\'))union",   # ' union
    r"exec(\s|\+)+(s|x)p\w+", # exec sp_
    r"UNION(\s|\+)+SELECT",  # UNION SELECT
    r"DROP(\s|\+)+TABLE",    # DROP TABLE
]

# ARP Monitoring
ARP_CHECK_INTERVAL = 10  # Seconds
EXPECTED_GATEWAY_MAC = "00:11:22:33:44:55" # Example MAC for simulation

# Logging
LOG_FILE = 'firewall.log'
