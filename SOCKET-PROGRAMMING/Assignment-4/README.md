
# Simple Chat Server
**Advanced Socket Programming - Assignment 04**

## Overview
This is a multi-client chat application implemented in Python using TCP sockets. It allows users to join a chat room, exchange messages in real-time, and see notifications when others join or leave.

## Features
-   **Multi-Client Support**: Uses threading to handle multiple simultaneous connections.
-   **Broadcasting**: Messages sent by one user are instantly received by all other connected users.
-   **Protocol**: Custom text-based protocol (`TYPE|arg1|arg2`) for structured communication.
-   **Interactive Client**: Simple command-line interface with a separated listener thread for receiving messages while typing.

## File Structure
-   `server.py`: The chat server that manages connections and broadcasting.
-   `client.py`: The client application for users.
-   `protocol.py`: Shared module defining the message format and helper functions.

## Protocol
Communication uses a simple pipe-separated format: `TYPE|Payload...`

**From Client to Server:**
-   **JOIN**: `JOIN|<username>` (Register logic)
-   **MSG**: `MSG|<content>` (Send a message)
-   **LEAVE**: `LEAVE` (Graceful exit)

**From Server to Client:**
-   **MSG**: `MSG|<sender_username>|<content>` (Broadcasted chat)
-   **INFO**: `INFO|<notification>` (Server alerts, e.g., "Bob joined.")
-   **ERROR**: `ERROR|<reason>` (Feedback on invalid actions)

## How to Run

### 1. Start the Server
Open a terminal and run the server. It listens on port `9999` by default.
```bash
python server.py
```
*Output:*
```
[*] Chat Server started on 0.0.0.0:9999
[*] Waiting for connections...
```

### 2. Connect one or more Clients
Open new terminal windows (one for each user) and run the client.
You can specify IP and Port as arguments, or follow the prompts.

```bash
# GUI Mode (Recommended)
python client_gui.py

# CLI Mode
python client.py
# OR
python client.py 127.0.0.1 9999
```

### Example Session
**Client A (Alice):**
```
> Hello everyone!
```

**Client B (Bob):**
```
[INFO] Alice has joined the chat.
[Alice]: Hello everyone!
> Hi Alice!
```

## Requirements & Dependencies
-   **Python 3.x**
-   Standard libraries: `socket`, `threading`, `sys`.
-   No external `pip` packages required.
