
import tkinter as tk
from tkinter import scrolledtext, simpledialog, messagebox
import socket
import threading
import sys
import protocol

# Default Config
DEFAULT_HOST = '127.0.0.1'
DEFAULT_PORT = 9999

class ChatClientGUI:
    def __init__(self, master, host, port):
        self.master = master
        self.host = host
        self.port = port
        self.sock = None
        self.username = None
        self.running = True

        self.setup_ui()
        self.connect()

    def setup_ui(self):
        self.master.title("Socket Chat")
        self.master.geometry("500x600")
        self.master.configure(bg="#2c2f33")

        # Chat Area
        self.chat_area = scrolledtext.ScrolledText(self.master, state='disabled', bg="#23272a", fg="#ffffff", font=("Arial", 10))
        self.chat_area.pack(padx=10, pady=10, fill=tk.BOTH, expand=True)

        # Message Entry Frame
        input_frame = tk.Frame(self.master, bg="#2c2f33")
        input_frame.pack(padx=10, pady=(0, 10), fill=tk.X)

        self.msg_entry = tk.Entry(input_frame, bg="#40444b", fg="#ffffff", insertbackground="white", bd=0, font=("Arial", 11))
        self.msg_entry.pack(side=tk.LEFT, fill=tk.X, expand=True, ipady=8, padx=(0, 10))
        self.msg_entry.bind("<Return>", self.send_message)

        send_btn = tk.Button(input_frame, text="Send", command=self.send_message, bg="#7289da", fg="white", bd=0, px=20, font=("Arial", 10, "bold"))
        send_btn.pack(side=tk.RIGHT)

        # Tag configurations for coloring
        self.chat_area.tag_config('info', foreground="#99aab5", font=("Arial", 9, "italic"))
        self.chat_area.tag_config('error', foreground="#f04747")
        self.chat_area.tag_config('self', foreground="#7289da", font=("Arial", 10, "bold"))
        self.chat_area.tag_config('other', foreground="#43b581", font=("Arial", 10, "bold"))
        self.chat_area.tag_config('content', foreground="#ffffff")

        self.master.protocol("WM_DELETE_WINDOW", self.on_close)

    def connect(self):
        try:
            self.sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            self.sock.connect((self.host, self.port))
            
            # Ask for Username
            self.username = simpledialog.askstring("Username", "Enter your username:", parent=self.master)
            if not self.username:
                self.username = "Guest"
            
            # Send Join
            join_msg = protocol.encode_message(protocol.TYPE_JOIN, self.username)
            self.sock.sendall(join_msg.encode('utf-8'))

            # Start listening thread
            threading.Thread(target=self.listen_loop, daemon=True).start()

        except Exception as e:
            messagebox.showerror("Connection Error", f"Could not connect to server: {e}")
            self.master.destroy()

    def listen_loop(self):
        while self.running:
            try:
                data = self.sock.recv(1024)
                if not data:
                    self.display_message("Disconnected from server.", "error")
                    break
                
                message_str = data.decode('utf-8')
                msg_type, params = protocol.decode_message(message_str)
                
                if msg_type == protocol.TYPE_MSG:
                    if len(params) >= 2:
                        sender, content = params[0], params[1]
                        self.display_chat_message(sender, content)
                elif msg_type == protocol.TYPE_INFO:
                    if params:
                        self.display_message(f"[INFO] {params[0]}", "info")
                elif msg_type == protocol.TYPE_ERROR:
                     if params:
                        self.display_message(f"[ERROR] {params[0]}", "error")

            except Exception:
                break
    
    def display_message(self, text, tag):
        self.chat_area.config(state='normal')
        self.chat_area.insert(tk.END, text + "\n", tag)
        self.chat_area.see(tk.END)
        self.chat_area.config(state='disabled')

    def display_chat_message(self, sender, content):
        self.chat_area.config(state='normal')
        
        # Color logic
        tag = 'self' if sender == self.username else 'other'
        
        self.chat_area.insert(tk.END, f"{sender}: ", tag)
        self.chat_area.insert(tk.END, f"{content}\n", 'content')
        self.chat_area.see(tk.END)
        self.chat_area.config(state='disabled')

    def send_message(self, event=None):
        msg = self.msg_entry.get().strip()
        if not msg:
            return
        
        try:
            # Send to server
            packet = protocol.encode_message(protocol.TYPE_MSG, msg)
            self.sock.sendall(packet.encode('utf-8'))
            
            # Clear input
            self.msg_entry.delete(0, tk.END)
            
            # Optimistically display own message (or wait for broadcast echoed if server supported echo)
            # Since our server does NOT echo back to sender, we must display locally.
            self.display_chat_message(self.username, msg)

        except Exception as e:
            self.display_message(f"Error sending: {e}", "error")

    def on_close(self):
        self.running = False
        try:
            if self.sock:
                self.sock.sendall(protocol.encode_message(protocol.TYPE_LEAVE).encode('utf-8'))
                self.sock.close()
        except:
            pass
        self.master.destroy()

if __name__ == "__main__":
    host = sys.argv[1] if len(sys.argv) > 1 else DEFAULT_HOST
    port = int(sys.argv[2]) if len(sys.argv) > 2 else DEFAULT_PORT

    root = tk.Tk()
    app = ChatClientGUI(root, host, port)
    root.mainloop()
