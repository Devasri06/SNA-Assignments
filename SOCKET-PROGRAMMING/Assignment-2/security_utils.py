
import ssl
import config
import logging
import os

def create_server_ssl_context():
    """
    Creates an SSL context for the server (Firewall acting as SSL termination).
    """
    if not os.path.exists(config.CERT_FILE) or not os.path.exists(config.KEY_FILE):
        logging.error("Certificate files not found. SSL cannot be enabled.")
        return None

    try:
        context = ssl.create_default_context(ssl.Purpose.CLIENT_AUTH)
        context.load_cert_chain(certfile=config.CERT_FILE, keyfile=config.KEY_FILE)
        return context
    except Exception as e:
        logging.error(f"Failed to create SSL context: {e}")
        return None

def create_client_ssl_context():
    """
    Context for the backend if backend is also using SSL (Optional).
    For this assignment, we assume backend is plain TCP and Firewall terminates SSL.
    """
    return ssl.create_default_context(ssl.Purpose.SERVER_AUTH)
