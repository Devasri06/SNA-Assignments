
import logging
import time
import threading
import config
import platform
import subprocess
import re

class ARPMonitor:
    def __init__(self):
        self.running = False
        self.known_macs = {} # IP -> MAC

    def get_arp_table(self):
        """
        Retrieves ARP table from OS.
        Returns list of (ip, mac) tuples.
        """
        entries = []
        try:
            # Command varies by OS. Linux/Mac 'arp -a', Windows 'arp -a'
            output = subprocess.check_output(['arp', '-a']).decode()
            
            # Simple parsing for standard output formats
            # Windows: 192.168.1.1           00-11-22-33-44-55     dynamic
            # Linux: ? (192.168.1.1) at 00:11:22:33:44:55 [ether] on eth0
            
            lines = output.split('\n')
            for line in lines:
                # Find IP and MAC
                # Regex for IP
                ip_match = re.search(r'\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}', line)
                # Regex for MAC
                mac_match = re.search(r'([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})', line)
                
                if ip_match and mac_match:
                    entries.append((ip_match.group(0), mac_match.group(0)))
        except Exception as e:
            logging.error(f"Failed to fetch ARP table: {e}")
        return entries

    def detect_conflicts(self, arp_entries):
        """
        Analyzes ARP entries for conflicts (same IP, different MAC changing frequently)
        or Duplicate MACs (Simulating ARP Spoofing detection).
        """
        current_map = {}
        for ip, mac in arp_entries:
            # Normalization
            mac = mac.replace('-', ':').upper()
            
            if ip in self.known_macs:
                last_mac = self.known_macs[ip]
                if last_mac != mac:
                    # MAC changed for the same IP! Possible Spoofing.
                    logging.warning(f"ARP SPOOFING ALERT: IP {ip} changed MAC from {last_mac} to {mac}")
                    # In a real IPS, we would block this MAC or IP.
            
            current_map[ip] = mac
        
        # Update state
        self.known_macs.update(current_map)

    def monitor_loop(self):
        logging.info("ARP Monitor started.")
        while self.running:
            entries = self.get_arp_table()
            self.detect_conflicts(entries)
            time.sleep(config.ARP_CHECK_INTERVAL)

    def start(self):
        self.running = True
        t = threading.Thread(target=self.monitor_loop)
        t.daemon = True
        t.start()

    def stop(self):
        self.running = False
