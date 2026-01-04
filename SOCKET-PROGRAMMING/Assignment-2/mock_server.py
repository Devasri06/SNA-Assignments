
import socket
import threading
import config

def handle_client(client_socket, address):
    print(f"[Backend] Connected: {address}")
    try:
        while True:
            data = client_socket.recv(1024)
            if not data:
                break
            
            decoded = data.decode(errors='ignore')
            print(f"[Backend] Received: {decoded}")
            
            # Simple Math BC Tool Logic simulation
            response = b"Math/BC Result: " + data
            client_socket.sendall(response)
    except Exception as e:
        print(f"[Backend] Error: {e}")
    finally:
        client_socket.close()

def start_server():
    server = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server.bind((config.BACKEND_HOST, config.BACKEND_PORT))
    server.listen(5)
    print(f"[Backend] Listening on {config.BACKEND_HOST}:{config.BACKEND_PORT}")

    try:
        while True:
            client, addr = server.accept()
            client_handler = threading.Thread(target=handle_client, args=(client, addr))
            client_handler.start()
    except KeyboardInterrupt:
        print("\n[Backend] Stopped.")

if __name__ == "__main__":
    start_server()
