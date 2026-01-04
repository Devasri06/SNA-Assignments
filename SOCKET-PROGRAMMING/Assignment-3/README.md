
# Sniffing and ARP Spoofing Detection
**Advanced Socket Programming - Assignment 03**

## Overview
This C application captures ARP packets on a specified network interface using the `libpcap` library and detects ARP Spoofing attempts. It maintains an internal table of IP-MAC mappings and alerts the user if an IP address suddenly changes its associated MAC address, which is a common sign of an ARP poisoning attack.

## Features
-   **Packet Sniffing**: Captures live ARP traffic.
-   **Spoofing Detection**: Identifies and logs conflicting IP-MAC entries.
-   **Real-time Logging**: Displays alerts to the console and writes to `arp_spoof.log`.

## Prerequisites
-   **Linux Environment** (or WSL/Cygwin/MinGW with pcap support).
-   **GCC** Compiler.
-   **libpcap** development libraries:
    ```bash
    sudo apt-get install libpcap-dev
    ```

## Compilation
To build the project, run:
```bash
make
```

## Usage
Run the sniffer with root privileges (required for packet capture). Optionally specify the interface name.

```bash
sudo ./arp_sniffer [interface]
```
Example:
```bash
sudo ./arp_sniffer eth0
```
If no interface is provided, it attempts to use the first available one.

## How it Works
1.  **Capture**: The program uses `pcap_open_live` to listen on the interface.
2.  **Filter**: A BPF filter `arp` is applied to only process ARP packets.
3.  **Analysis**:
    -   When an ARP packet (Request or Reply) is seen, the valid IP-MAC pair (Sender IP, Sender MAC) is extracted.
    -   The program checks its internal `host_table`.
    -   **New Host**: If the IP is unknown, it adds the (IP, MAC) to the table.
    -   **Existing Host**: If the IP is known, it compares the new MAC with the stored MAC.
    -   **Conflict**: If the MACs differ, it flags an **ARP SPOOFING ALERT**.

## Testing
To test this without a real attacker:
1.  Run the sniffer on one terminal: `sudo ./arp_sniffer`
2.  On another machine (or using `scapy` on the same machine but be careful with loops), generate ARP packets.
    -   Ping a host to generate a legitimate ARP entry.
    -   Then, craft a packet claiming the same IP has a different MAC.
    -   You should see a red "ARP SPOOFING DETECTED" message in the console and log file.

## Files
-   `main.c`: Entry point.
-   `sniffer.c`: libpcap setup and loop.
-   `arp_detector.c`: Parsing logic and spoof detection.
-   `logger.c`: Logging utilities.
-   `Makefile`: Build script.
-   `arp_spoof.log`: Output log file.
