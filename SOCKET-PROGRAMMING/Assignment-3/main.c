#include <stdio.h>
#include <stdlib.h>
#include <pcap.h>
#include "sniffer.h"
#include "arp_detector.h"
#include "logger.h"

int main(int argc, char *argv[]) {
    char *dev = NULL;
    char errbuf[PCAP_ERRBUF_SIZE];

    // Initialize Logger
    logger_init("arp_spoof.log");

    // Interface Selection
    if (argc > 1) {
        dev = argv[1];
    } else {
        // Find default device
        pcap_if_t *alldevs;
        if (pcap_findalldevs(&alldevs, errbuf) == -1) {
            fprintf(stderr, "Error finding devices: %s\n", errbuf);
            return 1;
        }
        if (alldevs != NULL) {
            dev = alldevs->name; // Take first one
            // Note: In real app, might want to copy name as alldevs is freed
        } else {
            fprintf(stderr, "No devices found.\n");
            return 1;
        }
    }

    if (dev == NULL) {
        fprintf(stderr, "Could not determine network device.\n");
        return 2;
    }

    // Initialize Detector
    arp_detector_init();

    // Start Sniffer (BLOCKING)
    sniffer_start(dev);

    // Cleanup (Unreachable in infinite loop unless broke)
    arp_detector_cleanup();
    logger_close();
    
    return 0;
}
