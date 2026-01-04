
import socket
import threading
import sys
import protocol

# Configuration
HOST = '0.0.0.0'
PORT = 9999

# Global state
clients = {} # mapping: socket -> username
clients_lock = threading.Lock()

def broadcast(message_str, exclude_socket=None):
    """
    Sends a raw message string to all connected clients.
    """
    with clients_lock:
        for client_sock in clients:
            if client_sock != exclude_socket:
                try:
                    client_sock.sendall(message_str.encode('utf-8'))
                except Exception as e:
                    print(f"[!] Error broadcasting to a client: {e}")
                    # We could remove the client here, but handle_client loop deals with it usually.

def handle_client(client_socket, client_address):
    print(f"[+] New connection from {client_address}")
    username = None

    try:
        while True:
            data = client_socket.recv(1024)
            if not data:
                break
            
            message_str = data.decode('utf-8')
            msg_type, params = protocol.decode_message(message_str)

            if msg_type == protocol.TYPE_JOIN:
                # Client wants to join: JOIN|username
                if params:
                    username = params[0]
                    # Check if empty
                    if not username:
                        username = f"User-{client_address[1]}"
                    
                    with clients_lock:
                        clients[client_socket] = username
                    
                    print(f"[*] User '{username}' joined from {client_address}")
                    
                    # Notify everyone
                    welcome_msg = protocol.encode_message(protocol.TYPE_INFO, f"{username} has joined the chat.")
                    broadcast(welcome_msg)
                else:
                    # Invalid JOIN
                    err = protocol.encode_message(protocol.TYPE_ERROR, "Invalid JOIN format.")
                    client_socket.sendall(err.encode('utf-8'))

            elif msg_type == protocol.TYPE_MSG:
                # Client sent a message: MSG|content
                if not username:
                    err = protocol.encode_message(protocol.TYPE_ERROR, "You must JOIN first.")
                    client_socket.sendall(err.encode('utf-8'))
                    continue
                
                if params:
                    content = params[0]
                    # Broadcast: MSG|username|content
                    # We reconstruct it closer to the prompt requirement: MSG|<username>|<message>
                    broadcast_msg = protocol.encode_message(protocol.TYPE_MSG, username, content)
                    broadcast(broadcast_msg, exclude_socket=client_socket)
                    print(f"[{username}]: {content}")

            elif msg_type == protocol.TYPE_LEAVE:
                print(f"[-] {username} sent LEAVE.")
                break # Break loop to cleanup

            else:
                print(f"[!] Unknown message type from {client_address}: {message_str}")

    except ConnectionResetError:
        print(f"[!] Connection reset by {client_address}")
    except Exception as e:
        print(f"[!] Error handling client {client_address}: {e}")
    finally:
        # Cleanup
        client_socket.close()
        with clients_lock:
            if client_socket in clients:
                del clients[client_socket]
        
        if username:
            print(f"[-] User '{username}' disconnected.")
            leave_msg = protocol.encode_message(protocol.TYPE_INFO, f"{username} has left the chat.")
            broadcast(leave_msg)

def start_server():
    server = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    # Reuse address to avoid 'Address already in use' errors on restart
    server.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    
    try:
        server.bind((HOST, PORT))
        server.listen(5)
        print(f"[*] Chat Server started on {HOST}:{PORT}")
        print("[*] Waiting for connections...")
        
        while True:
            client_sock, addr = server.accept()
            # Start a thread for this client
            t = threading.Thread(target=handle_client, args=(client_sock, addr))
            t.daemon = True # Allow server to exit even if threads are running
            t.start()
            
    except KeyboardInterrupt:
        print("\n[*] Server stopping...")
    except Exception as e:
        print(f"[!] Server Error: {e}")
    finally:
        server.close()

if __name__ == "__main__":
    start_server()
