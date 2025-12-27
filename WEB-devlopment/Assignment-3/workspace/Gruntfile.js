module.exports = function (grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        // Minify CSS
        cssmin: {
            target: {
                files: [{
                    expand: true,
                    cwd: 'src/css',
                    src: ['*.css', '!*.min.css'],
                    dest: '../htdocs/css',
                    ext: '.min.css'
                }]
            }
        },

        // Uglify JS
        uglify: {
            target: {
                files: [{
                    expand: true,
                    cwd: 'src/js',
                    src: '**/*.js',
                    dest: '../htdocs/js'
                }]
            }
        },

        // Copy other files (PHP, HTML, Images)
        copy: {
            main: {
                files: [
                    // Copy PHP backend files
                    { expand: true, cwd: 'src/backend', src: ['**'], dest: '../htdocs/api/' },
                    // Copy HTML files
                    { expand: true, cwd: 'src', src: ['*.html'], dest: '../htdocs/' },
                    // Copy vendor assets (Bootstrap etc)
                    { expand: true, cwd: 'src/vendor', src: ['**'], dest: '../htdocs/vendor/' },
                    // Copy composer for runtime install
                    { expand: true, src: ['composer.json'], dest: '../htdocs/' }
                ],
            },
        },
    });

    grunt.loadNpmTasks('grunt-contrib-cssmin');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-copy');

    grunt.registerTask('default', ['cssmin', 'uglify', 'copy']);
};
