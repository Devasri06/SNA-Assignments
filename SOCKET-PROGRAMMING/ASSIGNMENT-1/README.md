# DoS Firewall Implementation
**Advanced Socket Programming - Assignment 01**

## Overview
This project processes incoming TCP traffic and acts as a firewall reverse proxy for a hypothetical "Math Bc Tool" backend. It inspects the source IP of incoming connections and enforces rate-limiting policies to mitigate Denial of Service (DoS) attacks.

## Project Structure
- `firewall.py`: Contains the `DoSProtector` class with the core logic for tracking request history, detecting anomalies, and managing blocks.
- `run_firewall.py`: The entry point script. It runs an `asyncio` TCP server that filters connections before proxying them to the backend using python's `asyncio` streams.
- `config.py`: Central configuration for ports, thresholds, and time windows.
- `mock_server.py`: A dummy TCP echo server acting as the "Math Bc" backend for testing purposes.
- `tests/test_firewall.py`: Unit tests ensuring the DoS logic (sliding window, blocking) works correctly.
- `test_attack_simulation.py`: A script to flood the server with requests to demonstrate the firewall in action.

## DoS Detection Logic
The firewall uses a **Sliding Window** algorithm:
1. **Tracking**: For every IP address, we maintain a deque (double-ended queue) of timestamps for all requests received within the last `TIME_WINDOW` (default: 60 seconds).
2. **Threshold**: If the number of requests in the deque exceeds `MAX_REQUESTS_PER_WINDOW` (default: 50), the IP is flagged.
3. **Action**: flagged IPs are added to a `blocked_ips` list with an expiry time (`BLOCK_DURATION`, default: 30s).
4. **Enforcement**: Any subsequent connection attempts from a blocked IP are immediately closed without being forwarded to the backend.

## How to Run

### prerequisites
Python 3.7+ is required. No external dependencies are needed.

### 1. Start the Backend Server
First, run the mock backend server which simulates the application we are protecting.
```bash
python mock_server.py
```
*Output: [Backend] Math Server running on 127.0.0.1:9000*

### 2. Start the Firewall
In a new terminal, run the firewall.
```bash
python run_firewall.py
```
*Output: Firewall running on 127.0.0.1:8080*

### 3. Run Tests
You can run the unit tests to verify the logic:
```bash
python -m unittest tests/test_firewall.py
```

To see the firewall in action blocking a simulated attack:
```bash
python test_attack_simulation.py
```
You will see requests initially succeeding ("ALLOWED") and then failing ("BLOCKED") once the threshold is reached.

### 4. Check Logs
Everything is logged to `dos_firewall.log`.
```bash
tail -f dos_firewall.log
# Windows (PowerShell): Get-Content dos_firewall.log -Wait
```
Sample Log Entry:
`2023-10-27 10:00:05 - WARNING - DETECTED DoS: Blocking 127.0.0.1 for 30s. Request count: 51`
