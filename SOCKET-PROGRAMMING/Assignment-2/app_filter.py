
import re
import logging
import config

class AppLayerFilter:
    def __init__(self):
        self.sqli_regexes = [re.compile(p, re.IGNORECASE) for p in config.SQLI_PATTERNS]

    def check_payload(self, data: bytes) -> bool:
        """
        Inspect the payload for SQL injection patterns.
        Returns:
            bool: True if safe, False if malicious pattern detected.
        """
        try:
            # Decode bytes to string for regex matching
            # Best effort decoding; if binary, might fail or look garbage, but checking text protocols (HTTP)
            content = data.decode('utf-8', errors='ignore')
            
            for regex in self.sqli_regexes:
                if regex.search(content):
                    logging.warning(f"SQL INJECTION DETECTED: Pattern {regex.pattern} matched in payload.")
                    return False
            return True
        except Exception as e:
            logging.error(f"Error inspecting payload: {e}")
            return True # Fail open or closed? Let's fail open for stability in assignment, but log error.

