
import logging
import ipaddress

class FirewallRule:
    def __init__(self, action, src_ip=None, dst_port=None, protocol='TCP'):
        self.action = action.upper() # ALLOW, DENY
        self.src_ip = src_ip
        self.dst_port = dst_port
        self.protocol = protocol.upper()

    def matches(self, client_ip, server_port, protocol='TCP'):
        if self.src_ip and self.src_ip != '*':
            # Handle CIDR or single IP
            if '/' in self.src_ip:
                if ipaddress.ip_address(client_ip) not in ipaddress.ip_network(self.src_ip):
                    return False
            elif self.src_ip != client_ip:
                return False
        
        if self.dst_port and self.dst_port != '*':
            if int(self.dst_port) != int(server_port):
                return False

        if self.protocol and self.protocol != '*':
            if self.protocol != protocol.upper():
                return False
        
        return True

class FirewallCore:
    def __init__(self):
        self.rules = []
        # Default policy
        self.default_action = 'ALLOW' 
        self.load_default_rules()

    def load_default_rules(self):
        # Example Rules
        # Block specific malicious IP
        self.add_rule('DENY', src_ip='192.168.1.100')
        # Allow everything else explicitly (optional if default is ALLOW)
        self.add_rule('ALLOW', src_ip='*')

    def add_rule(self, action, src_ip='*', dst_port='*', protocol='*'):
        self.rules.append(FirewallRule(action, src_ip, dst_port, protocol))

    def evaluate_connection(self, client_ip, server_port, protocol='TCP'):
        """
        Evaluate connection against rules.
        Returns: 'ALLOW' or 'DENY'
        """
        for rule in self.rules:
            if rule.matches(client_ip, server_port, protocol):
                logging.info(f"Rule Matched: Action={rule.action} for {client_ip}:{protocol}")
                return rule.action
        
        return self.default_action
