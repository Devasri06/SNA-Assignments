#include "arp_detector.h"
#include "logger.h"
#include <stdlib.h>
#include <string.h>
#include <arpa/inet.h>
#include <netinet/if_ether.h>

#define MAX_HOSTS 256

// Simple hash table or list for IP-MAC mapping
// For this assignment, a simple array for a /24 subnet or small network is robust enough.
typedef struct {
    char ip_str[INET_ADDRSTRLEN];
    u_char mac[6];
    int active;
} HostEntry;

static HostEntry host_table[MAX_HOSTS];
static int host_count = 0;

void arp_detector_init() {
    memset(host_table, 0, sizeof(host_table));
    host_count = 0;
}

// Convert mac bytes to string
static void mac_to_str(const u_char *mac, char *buffer) {
    sprintf(buffer, "%02x:%02x:%02x:%02x:%02x:%02x", 
            mac[0], mac[1], mac[2], mac[3], mac[4], mac[5]);
}

// Check if IP matches but MAC is different
static void check_and_update_host(const char *ip_str, const u_char *mac) {
    char mac_str[18];
    mac_to_str(mac, mac_str);

    // Search for existing IP
    for (int i = 0; i < host_count; i++) {
        if (strcmp(host_table[i].ip_str, ip_str) == 0) {
            // IP Found, check MAC
            if (memcmp(host_table[i].mac, mac, 6) != 0) {
                // Mismatch!
                char old_mac_str[18];
                mac_to_str(host_table[i].mac, old_mac_str);
                
                logger_log(LOG_ALERT, "ARP SPOOFING DETECTED! IP: %s is being claimed by %s (Previously: %s)", 
                           ip_str, mac_str, old_mac_str);
                
                // Optional: Update table to new MAC if we assume legitimate movement, 
                // but for spoof detection, we just alert.
            } else {
                // Known legitimate refresh
                // logger_log(LOG_INFO, "ARP Refresh: IP %s is at %s", ip_str, mac_str);
            }
            return;
        }
    }

    // New Host
    if (host_count < MAX_HOSTS) {
        strncpy(host_table[host_count].ip_str, ip_str, INET_ADDRSTRLEN);
        memcpy(host_table[host_count].mac, mac, 6);
        host_table[host_count].active = 1;
        host_count++;
        logger_log(LOG_INFO, "New Host Discovered: %s at %s", ip_str, mac_str);
    }
}

void process_arp_packet(const struct pcap_pkthdr *pkthdr, const u_char *packet) {
    // Offset for Ethernet Header (14 bytes)
    struct arp_header *arp = (struct arp_header *)(packet + 14);

    // Validate if it is ARP
    // Ethernet EtherType for ARP is 0x0806. 
    // Usually pcap filter ensures this, but we can verify if needed.

    uint16_t op_code = ntohs(arp->oper);
    
    char sender_ip[INET_ADDRSTRLEN];
    char target_ip[INET_ADDRSTRLEN];
    char sender_mac[18];
    char target_mac[18];

    inet_ntop(AF_INET, arp->spa, sender_ip, INET_ADDRSTRLEN);
    inet_ntop(AF_INET, arp->tpa, target_ip, INET_ADDRSTRLEN);
    mac_to_str(arp->sha, sender_mac);
    mac_to_str(arp->tha, target_mac);

    if (op_code == ARPOP_REQUEST) {
        // Who has TARGET_IP? Tell SENDER_IP (at SENDER_MAC)
        logger_log(LOG_INFO, "ARP Request: Who has %s? Tell %s", target_ip, sender_ip);
        
        // We can learn the Sender's Mapping from a Request too
        check_and_update_host(sender_ip, arp->sha);
        
    } else if (op_code == ARPOP_REPLY) {
        // TARGET_IP is at SENDER_MAC
        // (The structure names are Sender and Target. In a reply, Sender is the one answering)
        // Sender IP is valid at Sender MAC
        
        logger_log(LOG_INFO, "ARP Reply: %s is at %s", sender_ip, sender_mac);
        check_and_update_host(sender_ip, arp->sha);
    }
}

void arp_detector_cleanup() {
    // Cleanup if using dynamic memory
}
