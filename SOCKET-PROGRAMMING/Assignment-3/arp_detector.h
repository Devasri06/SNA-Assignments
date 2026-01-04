#ifndef ARP_DETECTOR_H
#define ARP_DETECTOR_H

#include <pcap.h>
#include <netinet/in.h>

// ARP Header structure (if not using net/if_arp.h directly for portability)
struct arp_header {
    u_int16_t htype;    // Hardware type
    u_int16_t ptype;    // Protocol type
    u_char hlen;        // Hardware address length 
    u_char plen;        // Protocol address length 
    u_int16_t oper;     // Operation
    u_char sha[6];      // Sender hardware address
    u_char spa[4];      // Sender protocol address
    u_char tha[6];      // Target hardware address
    u_char tpa[4];      // Target protocol address
};

// Function prototypes
void arp_detector_init();
void process_arp_packet(const struct pcap_pkthdr *pkthdr, const u_char *packet);
void arp_detector_cleanup();

#endif
