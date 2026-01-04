import socket
import time
import config

TARGET_HOST = config.FIREWALL_HOST
TARGET_PORT = config.FIREWALL_PORT

def send_request(msg_id):
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        s.settimeout(2)
        s.connect((TARGET_HOST, TARGET_PORT))
        s.send(f"Hello {msg_id}".encode())
        data = s.recv(1024)
        s.close()
        if data:
            return True, data.decode()
        else:
            return False, "Empty Response"
    except Exception as e:
        return False, str(e)

def run_attack_simulation():
    print(f"Starting attack simulation on {TARGET_HOST}:{TARGET_PORT}...")
    
    # Send enough requests to trigger block
    # Config default is 50. Let's send 60.
    
    success_count = 0
    blocked_count = 0
    
    for i in range(1, 70):
        print(f"Request {i}...", end=" ", flush=True)
        success, response = send_request(i)
        
        if success:
            print(f"ALLOWED. Response: {response}")
            success_count += 1
        else:
            print(f"BLOCKED/FAILED. Error: {response}")
            blocked_count += 1
        
        # Small delay to not overwhelm the OS socket buffer immediately, 
        # but fast enough to hit rate limit
        time.sleep(0.05) 

    print("\n--- Summary ---")
    print(f"Total Requests: 69")
    print(f"Allowed: {success_count}")
    print(f"Blocked: {blocked_count}")
    print("\nCheck dos_firewall.log for server-side logs.")

if __name__ == "__main__":
    run_attack_simulation()
