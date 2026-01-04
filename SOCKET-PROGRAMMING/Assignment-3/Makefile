
CC = gcc
CFLAGS = -Wall -g
LIBS = -lpcap

TARGET = arp_sniffer
OBJS = main.o sniffer.o arp_detector.o logger.o

all: $(TARGET)

$(TARGET): $(OBJS)
	$(CC) $(CFLAGS) -o $(TARGET) $(OBJS) $(LIBS)

main.o: main.c sniffer.h arp_detector.h logger.h
	$(CC) $(CFLAGS) -c main.c

sniffer.o: sniffer.c sniffer.h arp_detector.h logger.h
	$(CC) $(CFLAGS) -c sniffer.c

arp_detector.o: arp_detector.c arp_detector.h logger.h
	$(CC) $(CFLAGS) -c arp_detector.c

logger.o: logger.c logger.h
	$(CC) $(CFLAGS) -c logger.c

clean:
	rm -f $(TARGET) $(OBJS) arp_spoof.log
