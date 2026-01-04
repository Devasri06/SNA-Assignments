
"""
Shared protocol definition for the Chat Server.
Defines message types and helper functions for encoding/decoding.
"""

# Separator used in messages
SEPARATOR = "|"

# Message Types
TYPE_JOIN = "JOIN"
TYPE_MSG = "MSG"
TYPE_LEAVE = "LEAVE"
TYPE_INFO = "INFO"   # Server notifications (e.g., user joined)
TYPE_ERROR = "ERROR" # Error messages

def encode_message(msg_type, *args):
    """
    Encodes arguments into a protocol message string.
    Format: TYPE|arg1|arg2...
    """
    payload = SEPARATOR.join(args)
    if payload:
        return f"{msg_type}{SEPARATOR}{payload}"
    return msg_type

def decode_message(message_str):
    """
    Decodes a protocol message string into (type, params_list).
    """
    if not message_str:
        return None, []
    
    parts = message_str.strip().split(SEPARATOR)
    msg_type = parts[0]
    params = parts[1:] if len(parts) > 1 else []
    return msg_type, params
