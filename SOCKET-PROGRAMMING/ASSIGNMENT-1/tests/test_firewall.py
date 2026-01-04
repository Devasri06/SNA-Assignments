import unittest
import time
import sys
import os

# Add parent directory to path so we can import firewall
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from firewall import DoSProtector
import config

class TestDoSProtector(unittest.TestCase):
    def setUp(self):
        self.firewall = DoSProtector()
        # Override config for faster testing
        self.firewall.max_requests = 5
        self.firewall.window = 2
        self.firewall.block_duration = 3

    def test_normal_traffic(self):
        ip = "192.168.1.1"
        for _ in range(5):
            allowed = self.firewall.process_request(ip)
            self.assertTrue(allowed, "Traffic within limit should be allowed")

    def test_blocking_logic(self):
        ip = "192.168.1.50"
        # Send max requests
        for _ in range(5):
            self.firewall.process_request(ip)
        
        # Next one should block
        blocked_req = self.firewall.process_request(ip)
        self.assertFalse(blocked_req, "Traffic exceeding limit should be blocked")
        
        # Check status
        self.assertTrue(self.firewall.is_blocked(ip))

    def test_sliding_window_expiry(self):
        ip = "10.0.0.1"
        # Fill quota
        for _ in range(5):
            self.firewall.process_request(ip)
        
        # Wait for window to pass
        time.sleep(2.1)
        
        # Should be allowed again
        allowed = self.firewall.process_request(ip)
        self.assertTrue(allowed, "Traffic after window expiry should be allowed")

    def test_block_expiry(self):
        ip = "10.0.0.2"
        # Trigger block
        for _ in range(6):
            self.firewall.process_request(ip)
            
        self.assertTrue(self.firewall.is_blocked(ip))
        
        # Wait for block duration
        time.sleep(3.1)
        
        self.assertFalse(self.firewall.is_blocked(ip), "IP should be unblocked after duration")

if __name__ == "__main__":
    unittest.main()
