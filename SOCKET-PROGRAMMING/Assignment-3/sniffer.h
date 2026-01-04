#ifndef SNIFFER_H
#define SNIFFER_H

/**
 * Start sniffing on the given device.
 * Loop forever (or until error/interrupt).
 */
void sniffer_start(const char *device_name);

#endif
