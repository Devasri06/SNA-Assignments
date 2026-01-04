
import socket
import threading
import sys
import protocol

# Default Config
DEFAULT_HOST = '127.0.0.1'
DEFAULT_PORT = 9999

def listen_for_messages(sock):
    """
    Thread function to listen for incoming messages from the server.
    """
    while True:
        try:
            data = sock.recv(1024)
            if not data:
                print("\n[!] Disconnected from server.")
                break # Server closed connection
            
            message_str = data.decode('utf-8')
            msg_type, params = protocol.decode_message(message_str)

            if msg_type == protocol.TYPE_MSG:
                # MSG|username|content
                if len(params) >= 2:
                    sender = params[0]
                    content = params[1]
                    print(f"\r[{sender}]: {content}")
                    print("> ", end="", flush=True) # Restore prompt
                else:
                    print(f"\r[MSG] {params}")
                    print("> ", end="", flush=True)

            elif msg_type == protocol.TYPE_INFO:
                # INFO|content
                if params:
                    print(f"\r[INFO] {params[0]}")
                    print("> ", end="", flush=True)

            elif msg_type == protocol.TYPE_ERROR:
                if params:
                    print(f"\r[ERROR] {params[0]}")
                    print("> ", end="", flush=True)
            
            else:
                # Raw fallback
                print(f"\r{message_str}")
                print("> ", end="", flush=True)

        except ConnectionAbortedError:
            break
        except Exception as e:
            print(f"\n[!] Error receiving message: {e}")
            break
    # When loop exits, ensure main program knows or exits? 
    # For a simple script, we just let the main thread eventually fail sending.

def start_client():
    if len(sys.argv) < 3:
        # Ask for details if not provided
        host_input = input(f"Enter server IP (default {DEFAULT_HOST}): ")
        server_host = host_input if host_input else DEFAULT_HOST
        
        port_input = input(f"Enter server port (default {DEFAULT_PORT}): ")
        server_port = int(port_input) if port_input else DEFAULT_PORT
    else:
        server_host = sys.argv[1]
        server_port = int(sys.argv[2])

    client_sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    
    try:
        print(f"[*] Connecting to {server_host}:{server_port}...")
        client_sock.connect((server_host, server_port))
    except Exception as e:
        print(f"[!] Unable to connect: {e}")
        return

    # User Setup
    username = input("Enter your username: ")
    # Send JOIN
    join_msg = protocol.encode_message(protocol.TYPE_JOIN, username)
    client_sock.sendall(join_msg.encode('utf-8'))

    # Start listener thread
    listen_thread = threading.Thread(target=listen_for_messages, args=(client_sock,))
    listen_thread.daemon = True
    listen_thread.start()

    print("[*] You are now in the chat.")
    print("[*] Type your message and press Enter. Type '/quit' to leave.")
    
    # Send Loop
    try:
        while True:
            msg = input("> ")
            if msg.lower() == '/quit':
                leave_msg = protocol.encode_message(protocol.TYPE_LEAVE, username)
                client_sock.sendall(leave_msg.encode('utf-8'))
                break
            
            # Send MSG
            # Note: Protocol expects MSG|content
            # Client doesn't need to send username in MSG, server knows it from socket
            if msg.strip():
                chat_packet = protocol.encode_message(protocol.TYPE_MSG, msg)
                client_sock.sendall(chat_packet.encode('utf-8'))
                
    except KeyboardInterrupt:
        print("\n[*] Exiting...")
    except Exception as e:
        print(f"[!] Error sending message: {e}")
    finally:
        client_sock.close()
        print("[-] Disconnected.")

if __name__ == "__main__":
    start_client()
