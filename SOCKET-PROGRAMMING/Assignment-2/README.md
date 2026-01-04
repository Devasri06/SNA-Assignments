
# Math/BC Server Firewall (Assignment 02)

## Overview
This is a comprehensive firewall solution designed to protect the Math/BC Server. It provides filtering at the Network Layer (IP/Port), Application Layer (SQL Injection detection), and includes monitoring for ARP Spoofing. Communications are secured using TLS/SSL.

## Architecture
The system acts as a Reverse Proxy sitting in front of the actual Math/BC backend.

1.  **Entry Point**: `mathbc_firewall.py` listens on port 8000 (public facing).
2.  **SSL Termination**: Wraps client connections in SSL/TLS layer.
3.  **Firewall Core**: `firewall_core.py` checks IP/Port rules against `config.py`.
4.  **App Filtering**: `app_filter.py` inspects payloads for SQL Injection patterns.
5.  **ARP Monitor**: `arp_monitor.py` runs in the background checking system ARP table for anomalies.
6.  **Backend**: Valid traffic is forwarded to `mock_server.py` on port 9000.

## Setup & Running

### 1. Generate SSL Certificates
Because this project uses SSL, you must generate a self-signed certificate.
You can use the provided python script (requires `cryptography` module) OR `openssl`.

**Option A: Python Script (Recommended if dependencies installed)**
`pip install cryptography`
`python generate_certs.py`

**Option B: OpenSSL (Standard Method)**
```bash
openssl req -new -newkey rsa:2048 -days 365 -nodes -x509 -keyout server.key -out server.crt
# (Press Enter through prompts)
```

### 2. Start the Backend
Open a terminal and run the mock backend server:
```bash
python mock_server.py
```
*Output: [Backend] Listening on 127.0.0.1:9000*

### 3. Start the Firewall
Open a second terminal and run the firewall proxy:
```bash
python mathbc_firewall.py
```
*Output: MathBC Firewall listening on 0.0.0.0:8000*
*(Note: If certs are missing, it will log an error and might fail or run insecurely depending on config)*

### 4. Testing
You can test using a simple client or `openssl s_client`.

**Test Normal Connection (Secure):**
```bash
# Using OpenSSL Client
openssl s_client -connect localhost:8000
# Then type "3+3" and hit enter.
```

**Test Python Client (Secure):**
Create a `client.py`:
```python
import socket
import ssl

context = ssl.create_default_context()
context.check_hostname = False
context.verify_mode = ssl.CERT_NONE

with socket.create_connection(('localhost', 8000)) as sock:
    with context.wrap_socket(sock, server_hostname='localhost') as ssock:
        ssock.sendall(b"Hello World")
        print(ssock.recv(1024))
```

**Test SQL Injection Blocking:**
Send a payload containing `' OR 1=1`:
```bash
# In the openssl session or python client
' OR 1=1
```
*Check `firewall.log`: You should see "SQL INJECTION DETECTED" and the connection will close.*

## Modules

-   **config.py**: Edit this to change ports, enable/disable SSL, or add SQL patterns.
-   **firewall_core.py**: Logic for ALLOW/DENY rules based on IP/Port.
-   **app_filter.py**: Contains Regex patterns for identifying SQL attacks.
-   **arp_monitor.py**: Reads `arp -a` and detects if a MAC address for an IP changes unexpectedly.
-   **security_utils.py**: Helper to load certificates.

## Logs
All events are recorded in `firewall.log`.
