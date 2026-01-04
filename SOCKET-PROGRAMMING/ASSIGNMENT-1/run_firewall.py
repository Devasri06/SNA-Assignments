import asyncio
import logging
import sys
import config
from firewall import firewall_engine

async def forward(reader, writer):
    """Forward data from reader to writer."""
    try:
        while True:
            data = await reader.read(4096)
            if not data:
                break
            writer.write(data)
            await writer.drain()
    except Exception as e:
        # Connection issues are expected (client disconnects, etc.)
        pass
    finally:
        writer.close()

async def handle_client(client_reader, client_writer):
    """
    Handle incoming client connection.
    Acts as a proxy if traffic is allowed by the firewall.
    """
    peername = client_writer.get_extra_info('peername')
    client_ip = peername[0] if peername else 'unknown'

    # Check Firewall Rule
    if not firewall_engine.process_request(client_ip):
        # Blocked
        logging.warning(f"Connection rejected from {client_ip}")
        client_writer.close()
        await client_writer.wait_closed()
        return

    # Allowed: Connect to Backend
    try:
        backend_reader, backend_writer = await asyncio.open_connection(
            config.BACKEND_HOST, config.BACKEND_PORT
        )
    except OSError as e:
        logging.error(f"Failed to connect to backend server at {config.BACKEND_HOST}:{config.BACKEND_PORT}. Error: {e}")
        client_writer.close()
        await client_writer.wait_closed()
        return

    # Proxy Data Bidirectionally
    # We run two tasks: client->backend and backend->client
    task1 = asyncio.create_task(forward(client_reader, backend_writer))
    task2 = asyncio.create_task(forward(backend_reader, client_writer))

    # Wait for either to finish (one side closes)
    done, pending = await asyncio.wait(
        [task1, task2],
        return_when=asyncio.FIRST_COMPLETED
    )

    # Cancel the other task
    for task in pending:
        task.cancel()

async def main():
    server = await asyncio.start_server(
        handle_client, config.FIREWALL_HOST, config.FIREWALL_PORT
    )

    addr = server.sockets[0].getsockname()
    print(f"Firewall running on {addr}")
    print(f"Filtering traffic for backend at {config.BACKEND_HOST}:{config.BACKEND_PORT}")
    print(f"Rules: Max {config.MAX_REQUESTS_PER_WINDOW} requests / {config.TIME_WINDOW}s")
    print("Press Ctrl+C to stop.")

    async with server:
        await server.serve_forever()

if __name__ == '__main__':
    # Ensure logging is set up to print key info to console as well if desired,
    # but the assignment asks for a log file. We will print startup info to stdout.
    try:
        if sys.platform == 'win32':
             asyncio.set_event_loop_policy(asyncio.WindowsSelectorEventLoopPolicy())
        asyncio.run(main())
    except KeyboardInterrupt:
        print("\nFirewall stopped.")
