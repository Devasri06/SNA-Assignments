#ifndef LOGGER_H
#define LOGGER_H

#include <stdio.h>

// Log levels
typedef enum {
    LOG_INFO,
    LOG_WARNING,
    LOG_ALERT
} LogLevel;

/**
 * Initialize the logger. opens the log file.
 */
void logger_init(const char *filename);

/**
 * Log a message to file and print to stdout with formatting.
 */
void logger_log(LogLevel level, const char *format, ...);

/**
 * Close the logger.
 */
void logger_close();

#endif
