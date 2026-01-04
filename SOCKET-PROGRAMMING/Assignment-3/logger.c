#include "logger.h"
#include <stdio.h>
#include <stdarg.h>
#include <time.h>
#include <string.h>

static FILE *log_file = NULL;

void logger_init(const char *filename) {
    log_file = fopen(filename, "a");
    if (!log_file) {
        perror("Failed to open log file");
    }
}

void logger_log(LogLevel level, const char *format, ...) {
    time_t now;
    char time_str[20];
    const char *level_str;
    const char *color_code;
    
    time(&now);
    strftime(time_str, sizeof(time_str), "%Y-%m-%d %H:%M:%S", localtime(&now));

    switch (level) {
        case LOG_INFO:
            level_str = "INFO";
            color_code = "\033[0m"; // Reset/White
            break;
        case LOG_WARNING:
            level_str = "WARNING";
            color_code = "\033[33m"; // Yellow
            break;
        case LOG_ALERT:
            level_str = "ALERT";
            color_code = "\033[31m"; // Red
            break;
        default:
            level_str = "UNKNOWN";
            color_code = "\033[0m";
    }

    // Format the message
    va_list args;
    
    // 1. Write to File
    if (log_file) {
        fprintf(log_file, "[%s] [%s] ", time_str, level_str);
        va_start(args, format);
        vfprintf(log_file, format, args);
        va_end(args);
        fprintf(log_file, "\n");
        fflush(log_file);
    }

    // 2. Write to Console
    printf("%s[%s] [%s] ", color_code, time_str, level_str);
    va_start(args, format);
    vprintf(format, args);
    va_end(args);
    printf("\033[0m\n"); // Reset color
}

void logger_close() {
    if (log_file) {
        fclose(log_file);
        log_file = NULL;
    }
}
