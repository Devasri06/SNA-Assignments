
import logging
import socket
import threading
import sys
import config
from firewall_core import FirewallCore
from app_filter import AppLayerFilter
from arp_monitor import ARPMonitor
import security_utils

# Setup logging
logging.basicConfig(
    filename=config.LOG_FILE,
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
# Also log to stdout
console = logging.StreamHandler()
console.setLevel(logging.INFO)
logging.getLogger('').addHandler(console)

class MathBCFirewall:
    def __init__(self):
        self.firewall_core = FirewallCore()
        self.app_filter = AppLayerFilter()
        self.arp_monitor = ARPMonitor()
    
    def handle_client(self, client_socket, addr):
        client_ip = addr[0]
        logging.info(f"New connection from {client_ip}:{addr[1]}")

        # 1. Network Layer Filtering
        decision = self.firewall_core.evaluate_connection(client_ip, config.FIREWALL_PORT)
        if decision == 'DENY':
            logging.warning(f"Connection DENIED by Firewall Rule for {client_ip}")
            client_socket.close()
            return

        # 2. Connect to Backend
        try:
            backend_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            backend_socket.connect((config.BACKEND_HOST, config.BACKEND_PORT))
        except Exception as e:
            logging.error(f"Failed to connect to backend: {e}")
            client_socket.close()
            return

        # 3. Two-way Proxy with App Layer Inspection (for Client -> Server)
        # We need to peek/read data to check for SQL Injection
        
        # Threads to handle bidirectional forwarding
        client_to_server = threading.Thread(
            target=self.proxy_data, 
            args=(client_socket, backend_socket, True)
        )
        server_to_client = threading.Thread(
            target=self.proxy_data, 
            args=(backend_socket, client_socket, False)
        )
        
        client_to_server.start()
        server_to_client.start()
        
        client_to_server.join()
        server_to_client.join()
        
        client_socket.close()
        backend_socket.close()

    def proxy_data(self, src, dst, is_client_to_server):
        try:
            while True:
                data = src.recv(4096)
                if not data:
                    break
                
                # 4. App Layer Filtering (SQL Injection) 
                # Inspect data coming FROM client
                if is_client_to_server:
                    if not self.app_filter.check_payload(data):
                        # Block malicious payload
                        logging.warning("Dropping malicious packet (SQL Injection).")
                        # We can choose to close connection or just plain drop packet
                        break # Close connection
                
                dst.sendall(data)
        except Exception as e:
            # Connection reset or similar
            pass
        finally:
            try:
                src.shutdown(socket.SHUT_RDWR)
            except:
                pass
            src.close()

    def start(self):
        # Start ARP Monitor
        self.arp_monitor.start()
        
        # Start Proxy Server
        server_socket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        server_socket.bind((config.FIREWALL_HOST, config.FIREWALL_PORT))
        server_socket.listen(10)
        
        logging.info(f"MathBC Firewall listening on {config.FIREWALL_HOST}:{config.FIREWALL_PORT}")
        
        # Warp with SSL if enabled
        if config.ENABLE_SSL:
            context = security_utils.create_server_ssl_context()
            if context:
                logging.info("SSL/TLS Enabled on Firewall Entry.")
                server_socket = context.wrap_socket(server_socket, server_side=True)
            else:
                logging.warning("SSL Configuration failed, falling back to plain TCP or exiting.")
        
        try:
            while True:
                client_sock, addr = server_socket.accept()
                t = threading.Thread(target=self.handle_client, args=(client_sock, addr))
                t.start()
        except KeyboardInterrupt:
            logging.info("Stopping Firewall...")
        finally:
            self.arp_monitor.stop()
            server_socket.close()

if __name__ == '__main__':
    fw = MathBCFirewall()
    fw.start()
