import socket
import threading
import config

def handle_client(client_socket, address):
    print(f"[Backend] Accepted connection from {address}")
    try:
        while True:
            data = client_socket.recv(1024)
            if not data:
                break
            # Simple Echo / Math response
            response = b"Math Server: Received -> " + data
            client_socket.send(response)
    except ConnectionResetError:
        pass
    finally:
        client_socket.close()

def start_server():
    server = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    server.bind((config.BACKEND_HOST, config.BACKEND_PORT))
    server.listen(5)
    print(f"[Backend] Math Server running on {config.BACKEND_HOST}:{config.BACKEND_PORT}")

    try:
        while True:
            client, addr = server.accept()
            client_handler = threading.Thread(target=handle_client, args=(client, addr))
            client_handler.start()
    except KeyboardInterrupt:
        print("\n[Backend] Server stopped.")

if __name__ == "__main__":
    start_server()
