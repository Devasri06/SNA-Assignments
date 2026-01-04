#include "sniffer.h"
#include "arp_detector.h"
#include "logger.h"
#include <pcap.h>
#include <stdlib.h>

void packet_handler(u_char *args, const struct pcap_pkthdr *header, const u_char *packet) {
    // We assume the filter "arp" is applied, so all packets are ARP.
    // However, if we sniffer everything, we would check the Ethernet Header type first.
    // EtherType 0x0806 is ARP.
    
    // For this assignment, assuming filter is "arp".
    process_arp_packet(header, packet);
}

void sniffer_start(const char *device_name) {
    char errbuf[PCAP_ERRBUF_SIZE];
    pcap_t *handle;
    struct bpf_program fp;
    char filter_exp[] = "arp";
    bpf_u_int32 mask;
    bpf_u_int32 net;

    // Get net/mask
    if (pcap_lookupnet(device_name, &net, &mask, errbuf) == -1) {
        logger_log(LOG_WARNING, "Can't get netmask for device %s. Assuming 0.", device_name);
        net = 0;
        mask = 0;
    }

    // Open Capture
    handle = pcap_open_live(device_name, BUFSIZ, 1, 1000, errbuf);
    if (handle == NULL) {
        logger_log(LOG_ALERT, "Couldn't open device %s: %s", device_name, errbuf);
        exit(EXIT_FAILURE);
    }
    
    // Check Ethernet Header avail
    if (pcap_datalink(handle) != DLT_EN10MB) {
        logger_log(LOG_ALERT, "Device %s doesn't provide Ethernet headers - not supported", device_name);
        exit(EXIT_FAILURE);
    }

    // Compile Filter
    if (pcap_compile(handle, &fp, filter_exp, 0, net) == -1) {
        logger_log(LOG_ALERT, "Couldn't parse filter %s: %s", filter_exp, pcap_geterr(handle));
        exit(EXIT_FAILURE);
    }

    // Set Filter
    if (pcap_setfilter(handle, &fp) == -1) {
        logger_log(LOG_ALERT, "Couldn't install filter %s: %s", filter_exp, pcap_geterr(handle));
        exit(EXIT_FAILURE);
    }

    logger_log(LOG_INFO, "Sniffing on device: %s (Filter: %s)", device_name, filter_exp);
    logger_log(LOG_INFO, "Press Ctrl+C to stop...");

    // Loop
    pcap_loop(handle, -1, packet_handler, NULL);

    pcap_freecode(&fp);
    pcap_close(handle);
}
